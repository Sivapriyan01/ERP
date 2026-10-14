import React, { useState } from 'react';
import {
  X,
  Package,
  Repeat,
  Wallet,
  Gift,
  User,
  MapPin,
  Bell,
  Heart,
  LogOut,
  ChevronLeft,
  CheckCircle2,
  Clock,
  ExternalLink,
  Edit2,
  Plus,
  Trash2,
  AlertCircle,
  Sparkles,
  ShoppingBag,
  ShieldCheck,
  Search,
  Check,
  CreditCard,
  ArrowUpRight,
  ArrowDownLeft,
  KeyRound,
  RefreshCw,
  Coins,
} from 'lucide-react';
import { useCustomerAuth } from './useCustomerAuth';
import { erpApi } from '../../services/erpApi';

export default function AccountModal({ primaryColor = '#111827', settings = {} }) {
  const {
    customer,
    isAccountModalOpen,
    setIsAccountModalOpen,
    activeAccountTab = 'orders',
    setActiveAccountTab,
    logout,
    orders = [],
    loadingOrders,
    updateProfile,
    addAddress,
  } = useCustomerAuth();

  const [activeTab, setActiveTab] = useState(activeAccountTab === 'dashboard' ? 'orders' : (activeAccountTab || 'orders'));
  const [editingProfile, setEditingProfile] = useState(false);
  const [profileName, setProfileName] = useState(customer?.name || '');
  const [profileEmail, setProfileEmail] = useState(customer?.email || '');
  const [profilePhone, setProfilePhone] = useState(customer?.phone || '');

  // Add Address Form State
  const [showAddAddress, setShowAddAddress] = useState(false);
  const [newAddr, setNewAddr] = useState({
    name: customer?.name || '',
    phone: customer?.phone || '',
    address: '',
    city: '',
    state: '',
    pincode: '',
    isDefault: false,
  });

  // Notification preferences
  const [notifications, setNotifications] = useState({
    whatsapp: true,
    sms: true,
    email: false,
  });

  // Wallet State (Sections 10 & 17 of SRS)
  const [walletData, setWalletData] = useState(null);
  const [loadingWallet, setLoadingWallet] = useState(false);
  const [gcCode, setGcCode] = useState('');
  const [redeemingGc, setRedeemingGc] = useState(false);
  const [gcMessage, setGcMessage] = useState(null);

  // Rewards State
  const [rewardsData, setRewardsData] = useState(null);
  const [loadingRewards, setLoadingRewards] = useState(false);
  const [pointsToConvert, setPointsToConvert] = useState(100);
  const [convertingPoints, setConvertingPoints] = useState(false);
  const [rewardsMessage, setRewardsMessage] = useState(null);

  const fetchWallet = async () => {
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
        if (res.wallet && updateProfile) {
          updateProfile({ wallet_balance: res.wallet.balance });
        }
      }
    } catch (e) {
      console.warn('Failed to load wallet', e);
    } finally {
      setLoadingWallet(false);
    }
  };

  const fetchRewards = async () => {
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
        if (res.rewards && updateProfile) {
          updateProfile({ points: res.rewards.points_balance });
        }
      }
    } catch (e) {
      console.warn('Failed to load rewards', e);
    } finally {
      setLoadingRewards(false);
    }
  };

  React.useEffect(() => {
    if (isAccountModalOpen && customer) {
      fetchWallet();
      fetchRewards();
    }
  }, [isAccountModalOpen, customer?.id, customer?.phone]);

  const handleRedeemGiftCard = async (e) => {
    e.preventDefault();
    if (!gcCode.trim()) {
      setGcMessage({ type: 'error', text: 'Please enter your Gift Card Code.' });
      return;
    }
    setRedeemingGc(true);
    setGcMessage(null);
    try {
      const res = await erpApi.redeemGiftCard({
        id: customer.id,
        phone: customer.phone,
        email: customer.email,
        code: gcCode.trim(),
      });
      if (res.success) {
        setGcMessage({ type: 'success', text: res.message });
        setGcCode('');
        await fetchWallet();
      } else {
        setGcMessage({ type: 'error', text: res.message || 'Failed to redeem gift card.' });
      }
    } catch (err) {
      setGcMessage({ type: 'error', text: 'Error connecting to server.' });
    } finally {
      setRedeemingGc(false);
    }
  };

  const handleConvertPoints = async (pts) => {
    const ptsNum = Number(pts || pointsToConvert);
    if (!ptsNum || ptsNum < 10) {
      setRewardsMessage({ type: 'error', text: 'Minimum 10 points required for conversion.' });
      return;
    }
    setConvertingPoints(true);
    setRewardsMessage(null);
    try {
      const res = await erpApi.convertRewardsToWallet({
        id: customer.id,
        phone: customer.phone,
        email: customer.email,
        points: ptsNum,
      });
      if (res.success) {
        setRewardsMessage({ type: 'success', text: res.message });
        await fetchRewards();
        await fetchWallet();
      } else {
        setRewardsMessage({ type: 'error', text: res.message || 'Failed to convert points.' });
      }
    } catch (err) {
      setRewardsMessage({ type: 'error', text: 'Error connecting to server.' });
    } finally {
      setConvertingPoints(false);
    }
  };

  React.useEffect(() => {
    if (activeAccountTab) {
      setActiveTab(activeAccountTab === 'dashboard' ? 'profile' : activeAccountTab);
    }
  }, [activeAccountTab]);

  React.useEffect(() => {
    if (customer) {
      setProfileName(customer.name || '');
      setProfileEmail(customer.email || '');
      setProfilePhone(customer.phone || '');
    }
  }, [customer]);

  if (!isAccountModalOpen || !customer) return null;

  const handleClose = () => {
    setIsAccountModalOpen(false);
  };

  const handleSaveProfile = async (e) => {
    e.preventDefault();
    await updateProfile({
      name: profileName,
      email: profileEmail,
      phone: profilePhone,
    });
    setEditingProfile(false);
  };

  const handleCreateAddress = (e) => {
    e.preventDefault();
    if (!newAddr.address || !newAddr.pincode) return;
    addAddress(newAddr);
    setShowAddAddress(false);
    setNewAddr({
      name: customer?.name || '',
      phone: customer?.phone || '',
      address: '',
      city: '',
      state: '',
      pincode: '',
      isDefault: false,
    });
  };

  const storeName = settings?.store_name || settings?.website_store_name || 'Store';
  const customerAddresses = customer.addresses || (customer.address ? [
    {
      id: 1,
      name: customer.name || 'Default Customer',
      phone: customer.phone || '',
      address: customer.address,
      city: customer.city || '',
      state: customer.state || '',
      pincode: customer.pincode || '',
      isDefault: true,
    }
  ] : []);

  const navItems = [
    { id: 'orders', label: 'Orders', icon: Package, badge: orders.length > 0 ? orders.length : null },
    { id: 'subscriptions', label: 'Subscriptions', icon: Repeat },
    { id: 'wallet', label: 'Wallet', icon: Wallet, badge: `₹${walletData?.wallet?.balance ?? customer.wallet_balance ?? 0}` },
    { id: 'rewards', label: 'Rewards', icon: Gift, badge: `${rewardsData?.rewards?.points_balance ?? customer.points ?? 0} pts` },
    { id: 'profile', label: 'Profile', icon: User },
    { id: 'addresses', label: 'Addresses', icon: MapPin, badge: customerAddresses.length },
    { id: 'preferences', label: 'Preferences', icon: Bell },
    { id: 'wishlist', label: 'Wishlist', icon: Heart },
  ];

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 9999,
        background: '#ffffff',
        overflowY: 'auto',
        fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
      }}
    >
      {/* Top Navbar Header (BuyWOW style) */}
      <header
        style={{
          borderBottom: '1px solid #f1f5f9',
          background: '#ffffff',
          position: 'sticky',
          top: 0,
          zIndex: 100,
        }}
      >
        <div
          style={{
            maxWidth: '1240px',
            margin: '0 auto',
            padding: '1rem 1.5rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
          }}
        >
          {/* Back button */}
          <button
            onClick={handleClose}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.45rem',
              background: 'none',
              border: 'none',
              color: '#111827',
              fontSize: '0.9rem',
              fontWeight: 600,
              cursor: 'pointer',
              padding: '0.4rem 0.65rem',
              borderRadius: '8px',
              transition: 'background 0.15s',
            }}
            onMouseEnter={(e) => (e.currentTarget.style.background = '#f8fafc')}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'none')}
          >
            <ChevronLeft size={18} />
            <span>Back</span>
          </button>

          {/* Store Logo / Title */}
          <div style={{ textAlign: 'center' }}>
            <span style={{ fontSize: '1.25rem', fontWeight: 900, color: '#111827', letterSpacing: '-0.02em' }}>
              {storeName}
            </span>
          </div>

          {/* Close X */}
          <button
            onClick={handleClose}
            style={{
              background: '#f8fafc',
              border: 'none',
              borderRadius: '50%',
              width: '36px',
              height: '36px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: 'pointer',
              color: '#64748b',
              transition: 'all 0.15s ease',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.background = '#f1f5f9';
              e.currentTarget.style.color = '#111827';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = '#f8fafc';
              e.currentTarget.style.color = '#64748b';
            }}
          >
            <X size={18} />
          </button>
        </div>
      </header>

      {/* Main Page Layout Container */}
      <main
        style={{
          maxWidth: '1240px',
          margin: '0 auto',
          padding: '2rem 1.5rem 4rem',
        }}
      >
        {/* Page Title & Subtitle */}
        <div style={{ marginBottom: '2rem' }}>
          <h1
            style={{
              margin: 0,
              fontSize: '2.1rem',
              fontWeight: 800,
              color: '#111827',
              letterSpacing: '-0.025em',
            }}
          >
            My Account
          </h1>
          <p
            style={{
              margin: '0.35rem 0 0',
              fontSize: '0.95rem',
              color: '#6b7280',
            }}
          >
            Manage your orders, profile, and preferences
          </p>
        </div>

        {/* 2-Column Layout Grid (BuyWOW structure) */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: '240px 1fr',
            gap: '2.5rem',
            alignItems: 'start',
          }}
          className="account-grid"
        >
          {/* Left Sidebar Navigation */}
          <aside style={{ display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => {
                    setActiveTab(item.id);
                    if (setActiveAccountTab) setActiveAccountTab(item.id);
                  }}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.75rem',
                    padding: '0.75rem 1rem',
                    borderRadius: '12px',
                    border: 'none',
                    background: isActive ? '#111827' : 'transparent',
                    color: isActive ? '#ffffff' : '#4b5563',
                    fontWeight: isActive ? 700 : 500,
                    fontSize: '0.9rem',
                    cursor: 'pointer',
                    textAlign: 'left',
                    transition: 'all 0.15s ease',
                    boxShadow: isActive ? '0 2px 6px rgba(0,0,0,0.1)' : 'none',
                  }}
                  onMouseEnter={(e) => {
                    if (!isActive) e.currentTarget.style.background = '#f9fafb';
                  }}
                  onMouseLeave={(e) => {
                    if (!isActive) e.currentTarget.style.background = 'transparent';
                  }}
                >
                  <Icon size={18} strokeWidth={isActive ? 2.3 : 1.9} />
                  <span style={{ flex: 1 }}>{item.label}</span>
                  {item.badge && (
                    <span
                      style={{
                        fontSize: '0.72rem',
                        fontWeight: 700,
                        padding: '0.15rem 0.5rem',
                        borderRadius: '9999px',
                        background: isActive ? 'rgba(255,255,255,0.2)' : '#f1f5f9',
                        color: isActive ? '#ffffff' : '#64748b',
                      }}
                    >
                      {item.badge}
                    </span>
                  )}
                </button>
              );
            })}

            {/* Logout Red Button */}
            <button
              onClick={() => {
                if (confirm('Are you sure you want to log out?')) {
                  logout();
                  handleClose();
                }
              }}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '0.75rem',
                padding: '0.75rem 1rem',
                borderRadius: '12px',
                border: 'none',
                background: 'transparent',
                color: '#ef4444',
                fontWeight: 600,
                fontSize: '0.9rem',
                cursor: 'pointer',
                textAlign: 'left',
                marginTop: '0.5rem',
                transition: 'all 0.15s ease',
              }}
              onMouseEnter={(e) => (e.currentTarget.style.background = '#fef2f2')}
              onMouseLeave={(e) => (e.currentTarget.style.background = 'none')}
            >
              <LogOut size={18} strokeWidth={2} />
              <span>Logout</span>
            </button>
          </aside>

          {/* Right Content Area */}
          <section style={{ display: 'flex', flexDirection: 'column', gap: '1.75rem' }}>
            {/* VIP Club Top Banner (BuyWOW Style) */}
            <div
              style={{
                background: '#0a0a0a',
                color: '#ffffff',
                borderRadius: '18px',
                padding: '1.35rem 1.75rem',
                display: 'flex',
                flexDirection: 'column',
                gap: '0.4rem',
                boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.95rem', fontWeight: 700 }}>
                <span>🎁</span>
                <span>{storeName} VIP Club</span>
              </div>
              <p style={{ margin: '0 0 0.85rem', fontSize: '0.85rem', color: '#9ca3af' }}>
                Your monthly Mystery Box is ready to claim
              </p>
              <div>
                <button
                  onClick={() => alert(`🎉 Mystery Box claimed! Added 100 bonus points and an exclusive gift to your next order!`)}
                  style={{
                    background: '#ffffff',
                    color: '#0a0a0a',
                    border: 'none',
                    borderRadius: '9999px',
                    padding: '0.55rem 1.35rem',
                    fontSize: '0.82rem',
                    fontWeight: 700,
                    cursor: 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '0.45rem',
                    transition: 'transform 0.15s, background 0.15s',
                  }}
                  onMouseEnter={(e) => (e.currentTarget.style.transform = 'scale(1.02)')}
                  onMouseLeave={(e) => (e.currentTarget.style.transform = 'none')}
                >
                  <span>Claim this month's Mystery Box</span>
                </button>
              </div>
            </div>

            {/* TAB: ORDERS */}
            {activeTab === 'orders' && (
              <div>
                <h2 style={{ margin: '0 0 1.25rem', fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                  Your Orders
                </h2>

                {orders.length === 0 ? (
                  /* BuyWOW Empty Orders Card */
                  <div
                    style={{
                      background: '#f9fafb',
                      borderRadius: '18px',
                      padding: '4.5rem 2rem',
                      textAlign: 'center',
                      border: '1px solid #f3f4f6',
                    }}
                  >
                    {/* Isometric Box SVG icon */}
                    <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '1.25rem' }}>
                      <svg width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                        <line x1="12" y1="22.08" x2="12" y2="12" />
                      </svg>
                    </div>

                    <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.2rem', fontWeight: 700, color: '#111827' }}>
                      No orders yet
                    </h3>
                    <p style={{ margin: '0 0 1.5rem', fontSize: '0.9rem', color: '#6b7280' }}>
                      Start shopping to see your orders here.
                    </p>

                    <button
                      onClick={handleClose}
                      style={{
                        background: '#111827',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '10px',
                        padding: '0.75rem 1.85rem',
                        fontSize: '0.9rem',
                        fontWeight: 700,
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                      }}
                      onMouseEnter={(e) => (e.currentTarget.style.background = '#1f2937')}
                      onMouseLeave={(e) => (e.currentTarget.style.background = '#111827')}
                    >
                      Browse Products
                    </button>
                  </div>
                ) : (
                  /* Order List */
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {orders.map((order, idx) => (
                      <div
                        key={order.id || order.invoice_no || idx}
                        style={{
                          background: '#ffffff',
                          border: '1px solid #e5e7eb',
                          borderRadius: '16px',
                          padding: '1.25rem 1.5rem',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: '0.85rem',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.5rem' }}>
                          <div>
                            <span style={{ fontSize: '0.95rem', fontWeight: 800, color: '#111827' }}>
                              Order #{order.invoice_no || order.website_order_id || `SQ-${1000 + idx}`}
                            </span>
                            <span style={{ display: 'block', fontSize: '0.78rem', color: '#6b7280', marginTop: '2px' }}>
                              Placed on {order.created_at ? new Date(order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Recently'}
                            </span>
                          </div>
                          <span
                            style={{
                              background: '#f0fdf4',
                              color: '#16a34a',
                              fontSize: '0.75rem',
                              fontWeight: 700,
                              padding: '0.25rem 0.65rem',
                              borderRadius: '9999px',
                              border: '1px solid #bbf7d0',
                              textTransform: 'uppercase',
                            }}
                          >
                            {order.status || 'Confirmed'}
                          </span>
                        </div>

                        <div style={{ borderTop: '1px solid #f3f4f6', paddingTop: '0.75rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                          <span style={{ fontSize: '0.85rem', color: '#6b7280' }}>
                            {Array.isArray(order.items) ? `${order.items.length} item(s)` : 'Order Items'}
                          </span>
                          <span style={{ fontSize: '1rem', fontWeight: 800, color: '#111827' }}>
                            ₹{Number(order.grand_total || order.total_amount || order.amount || 0).toLocaleString('en-IN')}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* TAB: SUBSCRIPTIONS */}
            {activeTab === 'subscriptions' && (
              <div>
                <h2 style={{ margin: '0 0 1.25rem', fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                  Subscriptions
                </h2>
                <div
                  style={{
                    background: '#f9fafb',
                    borderRadius: '18px',
                    padding: '4rem 2rem',
                    textAlign: 'center',
                    border: '1px solid #f3f4f6',
                  }}
                >
                  <Repeat size={48} color="#9ca3af" style={{ margin: '0 auto 1rem' }} />
                  <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.15rem', fontWeight: 700, color: '#111827' }}>
                    No active subscriptions
                  </h3>
                  <p style={{ margin: '0 0 1.5rem', fontSize: '0.88rem', color: '#6b7280', maxWidth: '400px', marginInline: 'auto' }}>
                    Set up automatic recurring deliveries for your favorite daily essentials and save up to 10% on every order!
                  </p>
                  <button
                    onClick={handleClose}
                    style={{
                      background: '#111827',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      padding: '0.75rem 1.85rem',
                      fontSize: '0.88rem',
                      fontWeight: 700,
                      cursor: 'pointer',
                    }}
                  >
                    Explore Subscription Products
                  </button>
                </div>
              </div>
            )}

            {/* TAB: WALLET (Sections 10 & 17 of SRS) */}
            {activeTab === 'wallet' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '1.75rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <div>
                    <h2 style={{ margin: 0, fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                      Store Wallet
                    </h2>
                    <p style={{ margin: '0.2rem 0 0', fontSize: '0.85rem', color: '#6b7280' }}>
                      Closed-loop digital wallet powered by an immutable transaction ledger
                    </p>
                  </div>
                  <button
                    onClick={fetchWallet}
                    disabled={loadingWallet}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.4rem',
                      background: 'none',
                      border: '1.5px solid #e5e7eb',
                      borderRadius: '10px',
                      padding: '0.45rem 0.85rem',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      color: '#111827',
                      cursor: 'pointer',
                    }}
                  >
                    <RefreshCw size={14} className={loadingWallet ? 'animate-spin' : ''} />
                    <span>Refresh</span>
                  </button>
                </div>

                {/* Top Metrics Cards */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '1rem' }}>
                  {/* Available Balance */}
                  <div
                    style={{
                      background: 'linear-gradient(135deg, #111827, #1f2937)',
                      color: '#ffffff',
                      borderRadius: '18px',
                      padding: '1.5rem',
                      boxShadow: '0 4px 15px rgba(0,0,0,0.08)',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <span style={{ fontSize: '0.78rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#9ca3af', fontWeight: 700 }}>
                        Available Balance
                      </span>
                      <CreditCard size={18} color="#10b981" />
                    </div>
                    <div style={{ margin: '0.5rem 0 0.25rem' }}>
                      <span style={{ fontSize: '2.3rem', fontWeight: 900, letterSpacing: '-0.02em' }}>
                        ₹{Number(walletData?.wallet?.balance ?? customer.wallet_balance ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                      </span>
                    </div>
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '0.3rem', fontSize: '0.75rem', color: '#34d399', fontWeight: 700 }}>
                      <CheckCircle2 size={13} /> 100% usable on your next checkout
                    </span>
                  </div>

                  {/* Pending Cashback Escrow */}
                  <div
                    style={{
                      background: '#f8fafc',
                      border: '1.5px solid #e2e8f0',
                      borderRadius: '18px',
                      padding: '1.5rem',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <span style={{ fontSize: '0.78rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#64748b', fontWeight: 700 }}>
                        Pending Cashback Escrow
                      </span>
                      <Clock size={18} color="#f59e0b" />
                    </div>
                    <div style={{ margin: '0.5rem 0 0.25rem' }}>
                      <span style={{ fontSize: '2.3rem', fontWeight: 900, color: '#111827', letterSpacing: '-0.02em' }}>
                        ₹{Number(walletData?.wallet?.pending_cashback ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                      </span>
                    </div>
                    <span style={{ fontSize: '0.75rem', color: '#64748b', display: 'block' }}>
                      Unlocks to wallet automatically after the 7-day return window closes.
                    </span>
                  </div>
                </div>

                {/* Redeem Gift Card Card */}
                <div
                  style={{
                    background: '#ffffff',
                    border: '1.5px solid #e5e7eb',
                    borderRadius: '18px',
                    padding: '1.5rem',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.35rem' }}>
                    <Gift size={20} color="#111827" />
                    <h3 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 800, color: '#111827' }}>
                      Redeem Gift Card or Store Voucher
                    </h3>
                  </div>
                  <p style={{ margin: '0 0 1.25rem', fontSize: '0.82rem', color: '#6b7280' }}>
                    Enter your Gift Card code or store voucher. Funds credit instantly to your wallet.
                  </p>

                  {gcMessage && (
                    <div
                      style={{
                        padding: '0.75rem 1rem',
                        borderRadius: '10px',
                        marginBottom: '1rem',
                        fontSize: '0.84rem',
                        fontWeight: 600,
                        background: gcMessage.type === 'success' ? '#f0fdf4' : '#fef2f2',
                        color: gcMessage.type === 'success' ? '#15803d' : '#b91c1c',
                        border: gcMessage.type === 'success' ? '1px solid #bbf7d0' : '1px solid #fecaca',
                      }}
                    >
                      {gcMessage.text}
                    </div>
                  )}

                  <form onSubmit={handleRedeemGiftCard} style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: '0.75rem' }}>
                    <input
                      type="text"
                      placeholder="Gift Card Code (e.g. GC-WELCOME-500)"
                      value={gcCode}
                      onChange={(e) => setGcCode(e.target.value.toUpperCase())}
                      style={{
                        padding: '0.65rem 0.95rem',
                        borderRadius: '10px',
                        border: '1.5px solid #d1d5db',
                        fontSize: '0.88rem',
                        textTransform: 'uppercase',
                        fontWeight: 600,
                      }}
                    />
                    <button
                      type="submit"
                      disabled={redeemingGc}
                      style={{
                        background: '#111827',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '10px',
                        padding: '0.65rem 1.45rem',
                        fontSize: '0.88rem',
                        fontWeight: 700,
                        cursor: redeemingGc ? 'not-allowed' : 'pointer',
                        whiteSpace: 'nowrap',
                      }}
                    >
                      {redeemingGc ? 'Verifying...' : 'Redeem to Wallet'}
                    </button>
                  </form>
                </div>

                {/* Active FIFO Credits List */}
                {walletData?.credits && walletData.credits.length > 0 && (
                  <div>
                    <h3 style={{ margin: '0 0 0.85rem', fontSize: '1rem', fontWeight: 800, color: '#111827' }}>
                      Active Credits (FIFO Expiry Breakdown)
                    </h3>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                      {walletData.credits.map((cr) => (
                        <div
                          key={cr.id}
                          style={{
                            background: '#f9fafb',
                            border: '1px solid #e5e7eb',
                            borderRadius: '12px',
                            padding: '0.85rem 1.15rem',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                          }}
                        >
                          <div>
                            <span style={{ fontSize: '0.86rem', fontWeight: 700, color: '#111827', textTransform: 'capitalize' }}>
                              {cr.source ? cr.source.replace('_', ' ') : 'Credit'}
                            </span>
                            <span style={{ display: 'block', fontSize: '0.75rem', color: '#6b7280', marginTop: '2px' }}>
                              {cr.expires_at ? `Expires on ${new Date(cr.expires_at).toLocaleDateString('en-IN')}` : 'No Expiry'}
                            </span>
                          </div>
                          <div style={{ textAlign: 'right' }}>
                            <span style={{ fontSize: '0.95rem', fontWeight: 800, color: '#111827' }}>
                              ₹{Number(cr.remaining_amount).toFixed(2)}
                            </span>
                            {cr.is_expiring_soon && (
                              <span style={{ display: 'block', fontSize: '0.7rem', color: '#dc2626', fontWeight: 700 }}>
                                Expiring soon!
                              </span>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Immutable Ledger Table */}
                <div>
                  <h3 style={{ margin: '0 0 0.85rem', fontSize: '1rem', fontWeight: 800, color: '#111827' }}>
                    Immutable Transaction Ledger
                  </h3>
                  {(!walletData?.ledger || walletData.ledger.length === 0) ? (
                    <div style={{ padding: '2.5rem', background: '#f9fafb', borderRadius: '14px', textAlign: 'center', color: '#6b7280', fontSize: '0.86rem' }}>
                      No wallet transactions recorded yet.
                    </div>
                  ) : (
                    <div style={{ border: '1px solid #e5e7eb', borderRadius: '14px', overflow: 'hidden' }}>
                      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.84rem' }}>
                        <thead>
                          <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e5e7eb', textAlign: 'left', color: '#64748b' }}>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700 }}>Date</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700 }}>Transaction ID</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700 }}>Description</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700, textAlign: 'right' }}>Amount</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700, textAlign: 'right' }}>Balance</th>
                          </tr>
                        </thead>
                        <tbody>
                          {walletData.ledger.map((tx) => (
                            <tr key={tx.id || tx.transaction_id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                              <td style={{ padding: '0.75rem 1rem', color: '#6b7280', whiteSpace: 'nowrap' }}>
                                {tx.created_at ? new Date(tx.created_at).toLocaleDateString('en-IN') : 'Recent'}
                              </td>
                              <td style={{ padding: '0.75rem 1rem', fontWeight: 700, color: '#111827', fontFamily: 'monospace' }}>
                                {tx.transaction_id}
                              </td>
                              <td style={{ padding: '0.75rem 1rem', color: '#374151' }}>
                                {tx.description}
                              </td>
                              <td
                                style={{
                                  padding: '0.75rem 1rem',
                                  textAlign: 'right',
                                  fontWeight: 800,
                                  color: tx.direction === 'credit' ? '#16a34a' : '#dc2626',
                                  whiteSpace: 'nowrap',
                                }}
                              >
                                {tx.direction === 'credit' ? '+' : '-'} ₹{Number(tx.amount).toFixed(2)}
                              </td>
                              <td style={{ padding: '0.75rem 1rem', textAlign: 'right', fontWeight: 700, color: '#111827' }}>
                                ₹{Number(tx.running_balance).toFixed(2)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* TAB: REWARDS (Sections 10 & 17 of SRS) */}
            {activeTab === 'rewards' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '1.75rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <div>
                    <h2 style={{ margin: 0, fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                      Loyalty Coins & VIP Rewards
                    </h2>
                    <p style={{ margin: '0.2rem 0 0', fontSize: '0.85rem', color: '#6b7280' }}>
                      Earn points on every purchase and convert them into store wallet credits
                    </p>
                  </div>
                  <button
                    onClick={fetchRewards}
                    disabled={loadingRewards}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.4rem',
                      background: 'none',
                      border: '1.5px solid #e5e7eb',
                      borderRadius: '10px',
                      padding: '0.45rem 0.85rem',
                      fontSize: '0.8rem',
                      fontWeight: 700,
                      color: '#111827',
                      cursor: 'pointer',
                    }}
                  >
                    <RefreshCw size={14} className={loadingRewards ? 'animate-spin' : ''} />
                    <span>Refresh</span>
                  </button>
                </div>

                {/* VIP Hero Card */}
                <div
                  style={{
                    background: 'linear-gradient(135deg, #18181b, #27272a)',
                    color: '#ffffff',
                    borderRadius: '20px',
                    padding: '2rem',
                    boxShadow: '0 8px 25px rgba(0,0,0,0.12)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    flexWrap: 'wrap',
                    gap: '1.5rem',
                  }}
                >
                  <div>
                    <span
                      style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '0.35rem',
                        fontSize: '0.78rem',
                        textTransform: 'uppercase',
                        letterSpacing: '0.08em',
                        color: '#f59e0b',
                        fontWeight: 800,
                        background: 'rgba(245, 158, 11, 0.15)',
                        padding: '0.3rem 0.75rem',
                        borderRadius: '9999px',
                      }}
                    >
                      <Sparkles size={14} />
                      {rewardsData?.rewards?.tier || 'VIP Club Member'}
                    </span>
                    <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.5rem', margin: '0.85rem 0 0.35rem' }}>
                      <span style={{ fontSize: '3rem', fontWeight: 900, letterSpacing: '-0.03em' }}>
                        {rewardsData?.rewards?.points_balance ?? customer.points ?? 0}
                      </span>
                      <span style={{ fontSize: '1.1rem', color: '#a1a1aa', fontWeight: 600 }}>Loyalty Coins</span>
                    </div>
                    {(() => {
                      const rate = Number(rewardsData?.rewards?.conversion_rate || 0.1);
                      const coinsPerRupee = rate > 0 ? Math.round(1 / rate) : 10;
                      const userPoints = Number(rewardsData?.rewards?.points_balance ?? customer.points ?? 0);
                      return (
                        <p style={{ margin: 0, fontSize: '0.9rem', color: '#d4d4d8' }}>
                          Worth <strong style={{ color: '#ffffff' }}>₹{(userPoints * rate).toFixed(2)}</strong> in instant store wallet credit ({coinsPerRupee} Coins = ₹1).
                        </p>
                      );
                    })()}
                  </div>

                  <div style={{ textAlign: 'right' }}>
                    {(() => {
                      const rate = Number(rewardsData?.rewards?.conversion_rate || 0.1);
                      const userPoints = Number(rewardsData?.rewards?.points_balance ?? customer.points ?? 0);
                      const canRedeem100 = userPoints >= 100;
                      return (
                        <button
                          onClick={() => handleConvertPoints(100)}
                          disabled={convertingPoints || !canRedeem100}
                          style={{
                            background: '#ffffff',
                            color: '#18181b',
                            border: 'none',
                            borderRadius: '12px',
                            padding: '0.75rem 1.6rem',
                            fontSize: '0.88rem',
                            fontWeight: 800,
                            cursor: canRedeem100 ? 'pointer' : 'not-allowed',
                            opacity: canRedeem100 ? 1 : 0.6,
                            boxShadow: '0 4px 12px rgba(255,255,255,0.2)',
                          }}
                        >
                          Convert 100 Coins → ₹{(100 * rate).toFixed(rate < 0.1 ? 2 : 0)}
                        </button>
                      );
                    })()}
                  </div>
                </div>

                {/* Convert Points to Wallet Card */}
                <div
                  style={{
                    background: '#ffffff',
                    border: '1.5px solid #e5e7eb',
                    borderRadius: '18px',
                    padding: '1.5rem',
                  }}
                >
                  <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.05rem', fontWeight: 800, color: '#111827' }}>
                    Convert Coins to Store Wallet Balance
                  </h3>
                  <p style={{ margin: '0 0 1.25rem', fontSize: '0.82rem', color: '#6b7280' }}>
                    Select a coin package or type the number of coins to transfer into your store wallet balance.
                  </p>

                  {rewardsMessage && (
                    <div
                      style={{
                        padding: '0.75rem 1rem',
                        borderRadius: '10px',
                        marginBottom: '1rem',
                        fontSize: '0.84rem',
                        fontWeight: 600,
                        background: rewardsMessage.type === 'success' ? '#f0fdf4' : '#fef2f2',
                        color: rewardsMessage.type === 'success' ? '#15803d' : '#b91c1c',
                        border: rewardsMessage.type === 'success' ? '1px solid #bbf7d0' : '1px solid #fecaca',
                      }}
                    >
                      {rewardsMessage.text}
                    </div>
                  )}

                  {/* Quick Preset Buttons */}
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: '0.75rem', marginBottom: '1.25rem' }}>
                    {(() => {
                      const rate = Number(rewardsData?.rewards?.conversion_rate || 0.1);
                      const userPoints = Number(rewardsData?.rewards?.points_balance ?? customer.points ?? 0);
                      return [100, 250, 500, 1000].map((pts) => {
                        const canRedeem = userPoints >= pts;
                        const walletVal = (pts * rate).toFixed(rate < 0.1 ? 2 : 0);
                        return (
                          <button
                            key={pts}
                            onClick={() => handleConvertPoints(pts)}
                            disabled={convertingPoints || !canRedeem}
                            style={{
                              background: canRedeem ? '#f8fafc' : '#f3f4f6',
                              border: '1.5px solid #e2e8f0',
                              borderRadius: '12px',
                              padding: '0.85rem 0.5rem',
                              textAlign: 'center',
                              cursor: canRedeem ? 'pointer' : 'not-allowed',
                              opacity: canRedeem ? 1 : 0.55,
                              transition: 'all 0.15s ease',
                            }}
                          >
                            <span style={{ display: 'block', fontSize: '0.95rem', fontWeight: 800, color: '#111827' }}>
                              {pts} Coins
                            </span>
                            <span style={{ display: 'block', fontSize: '0.78rem', color: '#16a34a', fontWeight: 700, marginTop: '2px' }}>
                              = ₹{walletVal} Wallet
                            </span>
                          </button>
                        );
                      });
                    })()}
                  </div>

                  {/* Custom points conversion form */}
                  <div style={{ display: 'flex', gap: '0.75rem' }}>
                    <input
                      type="number"
                      min={10}
                      step={10}
                      placeholder="Custom Coins (e.g. 150)"
                      value={pointsToConvert}
                      onChange={(e) => setPointsToConvert(e.target.value)}
                      style={{
                        flex: 1,
                        padding: '0.65rem 0.95rem',
                        borderRadius: '10px',
                        border: '1.5px solid #d1d5db',
                        fontSize: '0.88rem',
                        fontWeight: 600,
                      }}
                    />
                    <button
                      onClick={() => handleConvertPoints(pointsToConvert)}
                      disabled={convertingPoints}
                      style={{
                        background: '#111827',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '10px',
                        padding: '0.65rem 1.5rem',
                        fontSize: '0.88rem',
                        fontWeight: 700,
                        cursor: convertingPoints ? 'not-allowed' : 'pointer',
                        whiteSpace: 'nowrap',
                      }}
                    >
                      {convertingPoints ? 'Converting...' : 'Convert Coins'}
                    </button>
                  </div>
                </div>

                {/* Earning Rules Info */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '1rem' }}>
                  <div style={{ background: '#f9fafb', borderRadius: '14px', padding: '1.25rem', border: '1px solid #f3f4f6' }}>
                    <span style={{ fontSize: '1.2rem', display: 'block', marginBottom: '0.4rem' }}>🛍️</span>
                    <strong style={{ fontSize: '0.9rem', color: '#111827', display: 'block' }}>Earn 1 Coin / ₹10</strong>
                    <p style={{ margin: '0.25rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                      Automatically awarded on every order confirmed on the store.
                    </p>
                  </div>
                  <div style={{ background: '#f9fafb', borderRadius: '14px', padding: '1.25rem', border: '1px solid #f3f4f6' }}>
                    <span style={{ fontSize: '1.2rem', display: 'block', marginBottom: '0.4rem' }}>💰</span>
                    <strong style={{ fontSize: '0.9rem', color: '#111827', display: 'block' }}>5% Cashback Escrow</strong>
                    <p style={{ margin: '0.25rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                      Earn 5% post-purchase cashback transferred directly into wallet.
                    </p>
                  </div>
                  <div style={{ background: '#f9fafb', borderRadius: '14px', padding: '1.25rem', border: '1px solid #f3f4f6' }}>
                    <span style={{ fontSize: '1.2rem', display: 'block', marginBottom: '0.4rem' }}>🎁</span>
                    <strong style={{ fontSize: '0.9rem', color: '#111827', display: 'block' }}>VIP Mystery Box</strong>
                    <p style={{ margin: '0.25rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                      Monthly claimable surprise box with free gifts and coin drops.
                    </p>
                  </div>
                </div>

                {/* Reward Points Activity History */}
                <div>
                  <h3 style={{ margin: '0 0 0.85rem', fontSize: '1rem', fontWeight: 800, color: '#111827' }}>
                    Coins Activity History
                  </h3>
                  {(!rewardsData?.history || rewardsData.history.length === 0) ? (
                    <div style={{ padding: '2rem', background: '#f9fafb', borderRadius: '14px', textAlign: 'center', color: '#6b7280', fontSize: '0.86rem' }}>
                      No points history yet.
                    </div>
                  ) : (
                    <div style={{ border: '1px solid #e5e7eb', borderRadius: '14px', overflow: 'hidden' }}>
                      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.84rem' }}>
                        <thead>
                          <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e5e7eb', textAlign: 'left', color: '#64748b' }}>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700 }}>Date</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700 }}>Activity</th>
                            <th style={{ padding: '0.75rem 1rem', fontWeight: 700, textAlign: 'right' }}>Coins</th>
                          </tr>
                        </thead>
                        <tbody>
                          {rewardsData.history.map((h, i) => (
                            <tr key={h.id || i} style={{ borderBottom: '1px solid #f1f5f9' }}>
                              <td style={{ padding: '0.75rem 1rem', color: '#6b7280', whiteSpace: 'nowrap' }}>
                                {h.created_at ? new Date(h.created_at).toLocaleDateString('en-IN') : 'Recent'}
                              </td>
                              <td style={{ padding: '0.75rem 1rem', color: '#374151', fontWeight: 600 }}>
                                {h.description || h.type}
                              </td>
                              <td
                                style={{
                                  padding: '0.75rem 1rem',
                                  textAlign: 'right',
                                  fontWeight: 800,
                                  color: h.direction === 'credit' ? '#16a34a' : '#dc2626',
                                  whiteSpace: 'nowrap',
                                }}
                              >
                                {h.direction === 'credit' ? '+' : '-'} {h.points} Coins
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* TAB: PROFILE */}
            {activeTab === 'profile' && (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1.25rem' }}>
                  <h2 style={{ margin: 0, fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                    Profile Details
                  </h2>
                  <button
                    onClick={() => setEditingProfile(!editingProfile)}
                    style={{
                      background: 'none',
                      border: '1.5px solid #e5e7eb',
                      borderRadius: '10px',
                      padding: '0.45rem 0.95rem',
                      fontSize: '0.82rem',
                      fontWeight: 700,
                      color: '#111827',
                      cursor: 'pointer',
                    }}
                  >
                    {editingProfile ? 'Cancel' : 'Edit Profile'}
                  </button>
                </div>

                {editingProfile ? (
                  <form onSubmit={handleSaveProfile} style={{ display: 'flex', flexDirection: 'column', gap: '1rem', maxWidth: '480px' }}>
                    <div>
                      <label style={{ display: 'block', fontSize: '0.82rem', fontWeight: 700, color: '#374151', marginBottom: '0.35rem' }}>Full Name</label>
                      <input
                        type="text"
                        value={profileName}
                        onChange={(e) => setProfileName(e.target.value)}
                        style={{ width: '100%', height: '44px', border: '1.5px solid #d1d5db', borderRadius: '10px', padding: '0 0.85rem', fontSize: '0.9rem', outline: 'none' }}
                      />
                    </div>
                    <div>
                      <label style={{ display: 'block', fontSize: '0.82rem', fontWeight: 700, color: '#374151', marginBottom: '0.35rem' }}>Email Address</label>
                      <input
                        type="email"
                        value={profileEmail}
                        onChange={(e) => setProfileEmail(e.target.value)}
                        style={{ width: '100%', height: '44px', border: '1.5px solid #d1d5db', borderRadius: '10px', padding: '0 0.85rem', fontSize: '0.9rem', outline: 'none' }}
                      />
                    </div>
                    <div>
                      <label style={{ display: 'block', fontSize: '0.82rem', fontWeight: 700, color: '#374151', marginBottom: '0.35rem' }}>Phone Number</label>
                      <input
                        type="tel"
                        value={profilePhone}
                        onChange={(e) => setProfilePhone(e.target.value)}
                        style={{ width: '100%', height: '44px', border: '1.5px solid #d1d5db', borderRadius: '10px', padding: '0 0.85rem', fontSize: '0.9rem', outline: 'none' }}
                      />
                    </div>
                    <button
                      type="submit"
                      style={{
                        background: '#111827',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '10px',
                        padding: '0.75rem 1.5rem',
                        fontSize: '0.88rem',
                        fontWeight: 700,
                        cursor: 'pointer',
                        alignSelf: 'flex-start',
                      }}
                    >
                      Save Profile
                    </button>
                  </form>
                ) : (
                  <div style={{ background: '#f9fafb', border: '1px solid #f3f4f6', borderRadius: '16px', overflow: 'hidden', maxWidth: '580px' }}>
                    <div style={{ display: 'flex', padding: '1rem 1.25rem', borderBottom: '1px solid #f1f5f9' }}>
                      <span style={{ width: '140px', fontSize: '0.85rem', color: '#6b7280', fontWeight: 600 }}>Full Name</span>
                      <span style={{ fontSize: '0.9rem', color: '#111827', fontWeight: 700 }}>{customer.name || 'Not provided'}</span>
                    </div>
                    <div style={{ display: 'flex', padding: '1rem 1.25rem', borderBottom: '1px solid #f1f5f9' }}>
                      <span style={{ width: '140px', fontSize: '0.85rem', color: '#6b7280', fontWeight: 600 }}>Email Address</span>
                      <span style={{ fontSize: '0.9rem', color: '#111827', fontWeight: 700 }}>{customer.email || 'Not provided'}</span>
                    </div>
                    <div style={{ display: 'flex', padding: '1rem 1.25rem' }}>
                      <span style={{ width: '140px', fontSize: '0.85rem', color: '#6b7280', fontWeight: 600 }}>Phone Number</span>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                        <span style={{ fontSize: '0.9rem', color: '#111827', fontWeight: 700 }}>
                          {customer.phone ? `+91 ${customer.phone}` : 'Not provided'}
                        </span>
                        {customer.phone && (
                          <span style={{ fontSize: '0.7rem', color: '#16a34a', background: '#dcfce7', padding: '0.1rem 0.45rem', borderRadius: '6px', fontWeight: 700 }}>
                            Verified ✓
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* TAB: ADDRESSES */}
            {activeTab === 'addresses' && (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1.25rem' }}>
                  <h2 style={{ margin: 0, fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                    Saved Addresses
                  </h2>
                  <button
                    onClick={() => setShowAddAddress(!showAddAddress)}
                    style={{
                      background: '#111827',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      padding: '0.55rem 1rem',
                      fontSize: '0.82rem',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.4rem',
                    }}
                  >
                    <Plus size={16} />
                    <span>Add New Address</span>
                  </button>
                </div>

                {showAddAddress && (
                  <form onSubmit={handleCreateAddress} style={{ background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '16px', padding: '1.5rem', marginBottom: '1.5rem', display: 'flex', flexDirection: 'column', gap: '0.85rem', maxWidth: '580px' }}>
                    <h3 style={{ margin: '0 0 0.5rem', fontSize: '1rem', fontWeight: 700, color: '#111827' }}>New Delivery Address</h3>
                    <input
                      type="text"
                      placeholder="Receiver Name"
                      value={newAddr.name}
                      onChange={(e) => setNewAddr({ ...newAddr, name: e.target.value })}
                      style={{ height: '42px', padding: '0 0.85rem', borderRadius: '8px', border: '1px solid #d1d5db', outline: 'none' }}
                      required
                    />
                    <input
                      type="tel"
                      placeholder="Phone Number"
                      value={newAddr.phone}
                      onChange={(e) => setNewAddr({ ...newAddr, phone: e.target.value })}
                      style={{ height: '42px', padding: '0 0.85rem', borderRadius: '8px', border: '1px solid #d1d5db', outline: 'none' }}
                      required
                    />
                    <textarea
                      placeholder="House / Flat No., Street, Area, Landmark"
                      rows={2}
                      value={newAddr.address}
                      onChange={(e) => setNewAddr({ ...newAddr, address: e.target.value })}
                      style={{ padding: '0.65rem 0.85rem', borderRadius: '8px', border: '1px solid #d1d5db', outline: 'none' }}
                      required
                    />
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
                      <input
                        type="text"
                        placeholder="City"
                        value={newAddr.city}
                        onChange={(e) => setNewAddr({ ...newAddr, city: e.target.value })}
                        style={{ height: '42px', padding: '0 0.85rem', borderRadius: '8px', border: '1px solid #d1d5db', outline: 'none' }}
                        required
                      />
                      <input
                        type="text"
                        placeholder="PIN Code"
                        value={newAddr.pincode}
                        onChange={(e) => setNewAddr({ ...newAddr, pincode: e.target.value })}
                        style={{ height: '42px', padding: '0 0.85rem', borderRadius: '8px', border: '1px solid #d1d5db', outline: 'none' }}
                        required
                      />
                    </div>
                    <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.5rem' }}>
                      <button
                        type="button"
                        onClick={() => setShowAddAddress(false)}
                        style={{ background: '#e5e7eb', border: 'none', borderRadius: '8px', padding: '0.65rem 1rem', fontSize: '0.82rem', fontWeight: 600, cursor: 'pointer' }}
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        style={{ background: '#111827', color: '#fff', border: 'none', borderRadius: '8px', padding: '0.65rem 1.25rem', fontSize: '0.82rem', fontWeight: 700, cursor: 'pointer' }}
                      >
                        Save Address
                      </button>
                    </div>
                  </form>
                )}

                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '1rem' }}>
                  {customerAddresses.map((addr, idx) => (
                    <div
                      key={addr.id || idx}
                      style={{
                        background: '#ffffff',
                        border: '1.5px solid #e5e7eb',
                        borderRadius: '16px',
                        padding: '1.25rem',
                        display: 'flex',
                        flexDirection: 'column',
                        justifyContent: 'space-between',
                      }}
                    >
                      <div>
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                          <span style={{ fontSize: '0.95rem', fontWeight: 700, color: '#111827' }}>{addr.name || 'Home'}</span>
                          {addr.isDefault && (
                            <span style={{ fontSize: '0.7rem', fontWeight: 700, background: '#f1f5f9', color: '#475569', padding: '0.15rem 0.5rem', borderRadius: '6px' }}>
                              Default
                            </span>
                          )}
                        </div>
                        <p style={{ margin: '0 0 0.5rem', fontSize: '0.85rem', color: '#4b5563', lineHeight: 1.4 }}>
                          {addr.address}, {addr.city} {addr.pincode}
                        </p>
                        {addr.phone && (
                          <span style={{ fontSize: '0.78rem', color: '#6b7280' }}>Phone: +91 {addr.phone}</span>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* TAB: PREFERENCES */}
            {activeTab === 'preferences' && (
              <div>
                <h2 style={{ margin: '0 0 1.25rem', fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                  Notification Preferences
                </h2>
                <div style={{ background: '#f9fafb', border: '1px solid #f3f4f6', borderRadius: '16px', padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem', maxWidth: '580px' }}>
                  <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                    <div>
                      <span style={{ fontSize: '0.9rem', fontWeight: 700, color: '#111827', display: 'block' }}>WhatsApp Order Updates</span>
                      <span style={{ fontSize: '0.78rem', color: '#6b7280' }}>Receive live tracking, shipment status & dispatch alerts</span>
                    </div>
                    <input
                      type="checkbox"
                      checked={notifications.whatsapp}
                      onChange={(e) => setNotifications({ ...notifications, whatsapp: e.target.checked })}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                  </label>
                  <div style={{ height: '1px', background: '#f1f5f9' }} />
                  <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                    <div>
                      <span style={{ fontSize: '0.9rem', fontWeight: 700, color: '#111827', display: 'block' }}>SMS Notifications</span>
                      <span style={{ fontSize: '0.78rem', color: '#6b7280' }}>Get delivery OTP and order confirmation texts</span>
                    </div>
                    <input
                      type="checkbox"
                      checked={notifications.sms}
                      onChange={(e) => setNotifications({ ...notifications, sms: e.target.checked })}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                  </label>
                  <div style={{ height: '1px', background: '#f1f5f9' }} />
                  <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                    <div>
                      <span style={{ fontSize: '0.9rem', fontWeight: 700, color: '#111827', display: 'block' }}>Promotions & Offers</span>
                      <span style={{ fontSize: '0.78rem', color: '#6b7280' }}>Exclusive discount coupons & VIP member mystery box alerts</span>
                    </div>
                    <input
                      type="checkbox"
                      checked={notifications.email}
                      onChange={(e) => setNotifications({ ...notifications, email: e.target.checked })}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                  </label>
                </div>
              </div>
            )}

            {/* TAB: WISHLIST */}
            {activeTab === 'wishlist' && (
              <div>
                <h2 style={{ margin: '0 0 1.25rem', fontSize: '1.45rem', fontWeight: 800, color: '#111827' }}>
                  Wishlist
                </h2>
                <div
                  style={{
                    background: '#f9fafb',
                    borderRadius: '18px',
                    padding: '4rem 2rem',
                    textAlign: 'center',
                    border: '1px solid #f3f4f6',
                  }}
                >
                  <Heart size={48} color="#9ca3af" style={{ margin: '0 auto 1rem' }} />
                  <h3 style={{ margin: '0 0 0.35rem', fontSize: '1.15rem', fontWeight: 700, color: '#111827' }}>
                    Your Wishlist is empty
                  </h3>
                  <p style={{ margin: '0 0 1.5rem', fontSize: '0.88rem', color: '#6b7280' }}>
                    Explore our products and tap the heart icon to save items you love!
                  </p>
                  <button
                    onClick={handleClose}
                    style={{
                      background: '#111827',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      padding: '0.75rem 1.85rem',
                      fontSize: '0.88rem',
                      fontWeight: 700,
                      cursor: 'pointer',
                    }}
                  >
                    Browse Products
                  </button>
                </div>
              </div>
            )}
          </section>
        </div>
      </main>

      <style>{`
        @media (max-width: 768px) {
          .account-grid {
            grid-template-columns: 1fr !important;
            gap: 1.5rem !important;
          }
        }
      `}</style>
    </div>
  );
}
