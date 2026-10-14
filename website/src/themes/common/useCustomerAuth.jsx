import React, { createContext, useContext, useState, useEffect } from 'react';
import { erpApi } from '../../services/erpApi';

const CustomerAuthContext = createContext(null);

const STORAGE_KEY = 'square_customer_user';
const ORDERS_STORAGE_KEY = 'square_website_orders';

export function CustomerAuthProvider({ children }) {
  const [customer, setCustomer] = useState(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      return saved ? JSON.parse(saved) : null;
    } catch (e) {
      return null;
    }
  });

  const [orders, setOrders] = useState([]);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authSuccessCallback, setAuthSuccessCallback] = useState(null);
  const [isAccountModalOpen, setIsAccountModalOpen] = useState(false);
  const [activeAccountTab, setActiveAccountTab] = useState('dashboard');
  const [loadingOrders, setLoadingOrders] = useState(false);

  // Dynamic Wallet & Rewards State (SRS Section 10 & 17)
  const [walletData, setWalletData] = useState(null);
  const [rewardsData, setRewardsData] = useState(null);
  const [loadingWallet, setLoadingWallet] = useState(false);
  const [loadingRewards, setLoadingRewards] = useState(false);

  // Sync customer state with localStorage
  useEffect(() => {
    try {
      if (customer) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(customer));
      } else {
        localStorage.removeItem(STORAGE_KEY);
      }
    } catch (e) {}
  }, [customer]);

  const refreshWallet = async () => {
    if (!customer) return;
    setLoadingWallet(true);
    try {
      const res = await erpApi.getCustomerWallet({
        id: customer.id,
        phone: customer.phone,
        email: customer.email,
      });
      if (res && res.success) {
        setWalletData(res);
        if (res.wallet) {
          setCustomer((prev) => (prev ? { ...prev, wallet_balance: Number(res.wallet.balance || 0) } : prev));
        }
      }
    } catch (e) {
      console.warn('Failed to load wallet', e);
    } finally {
      setLoadingWallet(false);
    }
  };

  const refreshRewards = async () => {
    if (!customer) return;
    setLoadingRewards(true);
    try {
      const res = await erpApi.getCustomerRewards({
        id: customer.id,
        phone: customer.phone,
        email: customer.email,
      });
      if (res && res.success) {
        setRewardsData(res);
        if (res.rewards) {
          setCustomer((prev) => (prev ? { ...prev, points: Number(res.rewards.points_balance || 0) } : prev));
        }
      }
    } catch (e) {
      console.warn('Failed to load rewards', e);
    } finally {
      setLoadingRewards(false);
    }
  };

  // Load customer orders, wallet, and rewards when customer changes
  useEffect(() => {
    if (customer) {
      loadCustomerOrders();
      refreshWallet();
      refreshRewards();
    } else {
      setOrders([]);
      setWalletData(null);
      setRewardsData(null);
    }
  }, [customer?.id, customer?.phone, customer?.email]);

  const loadCustomerOrders = async () => {
    if (!customer) return;
    setLoadingOrders(true);
    try {
      const res = await erpApi.getCustomerOrders({
        id: customer.id,
        phone: customer.phone,
        email: customer.email,
      });
      if (Array.isArray(res)) {
        // Also combine with any local orders that match this phone/email
        const localOrders = JSON.parse(localStorage.getItem(ORDERS_STORAGE_KEY) || '[]');
        const matchingLocal = localOrders.filter(
          (o) =>
            o.customer?.phone === customer.phone ||
            o.customer_phone === customer.phone ||
            o.customer?.email === customer.email ||
            o.customer_email === customer.email
        );

        // Deduplicate by ID / invoice_no / website_order_id
        const orderMap = new Map();
        [...res, ...matchingLocal].forEach((o) => {
          const key = o.invoice_no || o.website_order_id || o.id;
          if (key && !orderMap.has(key)) {
            orderMap.set(key, o);
          }
        });
        setOrders(Array.from(orderMap.values()));
      }
    } catch (err) {
      console.warn('Failed to load orders:', err);
    } finally {
      setLoadingOrders(false);
    }
  };

  const loginWithOtp = async (phone) => {
    return await erpApi.customerLogin({ identifier: phone, type: 'otp' });
  };

  const verifyOtp = async (phone, otp, name = '', email = '') => {
    const res = await erpApi.customerVerifyOtp({ phone, otp, name, email });
    if (res.success && res.customer) {
      setCustomer(res.customer);
      if (res.orders && Array.isArray(res.orders)) {
        setOrders(res.orders);
      }
      setIsAuthModalOpen(false);
      if (typeof authSuccessCallback === 'function') {
        const cb = authSuccessCallback;
        setAuthSuccessCallback(null);
        cb(res.customer);
      }
      return { success: true, customer: res.customer };
    }
    return { success: false, message: res.message || 'OTP verification failed' };
  };

  const quickLogin = (userData) => {
    const profile = {
      id: userData.id || Math.floor(1000 + Math.random() * 9000),
      name: userData.name || (userData.email ? userData.email.split('@')[0] : 'Customer'),
      email: userData.email || '',
      phone: userData.phone || '9876543210',
      address: userData.address || 'Flat 402, Royal Residency, Outer Ring Road, Bengaluru',
      city: userData.city || 'Bengaluru',
      state: userData.state || 'Karnataka',
      pincode: userData.pincode || '560103',
      points: userData.points ?? 250,
      wallet_balance: userData.wallet_balance ?? 150,
      addresses: userData.addresses || [
        {
          id: 1,
          name: userData.name || 'Sivapriyan',
          phone: userData.phone || '9876543210',
          address: 'Flat 402, Royal Residency, Outer Ring Road',
          city: 'Bengaluru',
          state: 'Karnataka',
          pincode: '560103',
          isDefault: true,
        },
      ],
    };
    setCustomer(profile);
    setIsAuthModalOpen(false);
    if (typeof authSuccessCallback === 'function') {
      const cb = authSuccessCallback;
      setAuthSuccessCallback(null);
      cb(profile);
    }
    return profile;
  };

  const logout = () => {
    setCustomer(null);
    setOrders([]);
    setIsAccountModalOpen(false);
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) {}
  };

  const updateProfile = async (updatedData) => {
    if (!customer) return;
    const merged = { ...customer, ...updatedData };
    setCustomer(merged);
    try {
      await erpApi.updateCustomerProfile(merged);
    } catch (e) {}
    return merged;
  };

  const addAddress = (newAddr) => {
    if (!customer) return;
    const existing = customer.addresses || [];
    const item = {
      id: Date.now(),
      ...newAddr,
      isDefault: existing.length === 0 || newAddr.isDefault,
    };
    const updated = [...existing, item];
    updateProfile({ addresses: updated, address: item.address, city: item.city, state: item.state, pincode: item.pincode });
  };

  const openAccount = (tab = 'dashboard') => {
    setActiveAccountTab(tab);
    setIsAccountModalOpen(true);
  };

  const openLogin = (onSuccess = null) => {
    if (typeof onSuccess === 'function') {
      setAuthSuccessCallback(() => onSuccess);
    } else {
      setAuthSuccessCallback(null);
    }
    setIsAuthModalOpen(true);
  };

  return (
    <CustomerAuthContext.Provider
      value={{
        customer,
        isLoggedIn: Boolean(customer),
        orders,
        loadingOrders,
        isAuthModalOpen,
        setIsAuthModalOpen,
        isAccountModalOpen,
        setIsAccountModalOpen,
        activeAccountTab,
        setActiveAccountTab,
        loginWithOtp,
        verifyOtp,
        quickLogin,
        logout,
        updateProfile,
        addAddress,
        openAccount,
        openLogin,
        refreshOrders: loadCustomerOrders,
        walletData,
        rewardsData,
        walletBalance: Number(walletData?.wallet?.balance ?? customer?.wallet_balance ?? 0),
        rewardPoints: Number(rewardsData?.rewards?.points_balance ?? customer?.points ?? 0),
        refreshWallet,
        refreshRewards,
        loadingWallet,
        loadingRewards,
      }}
    >
      {children}
    </CustomerAuthContext.Provider>
  );
}

export function useCustomerAuth() {
  const context = useContext(CustomerAuthContext);
  if (!context) {
    throw new Error('useCustomerAuth must be used within a CustomerAuthProvider');
  }
  return context;
}
