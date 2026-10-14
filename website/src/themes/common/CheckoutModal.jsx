import React, { useState } from 'react';
import { X, CheckCircle, ShieldCheck, Truck, ArrowRight, Smartphone, AlertCircle, Phone } from 'lucide-react';
import { erpApi } from '../../services/erpApi';
import { useCustomerAuth } from './useCustomerAuth';

export default function CheckoutModal({
  isOpen,
  onClose,
  cart = [],
  subtotal = 0,
  gstAmount = 0,
  gstCalcType,
  shippingCharge = 0,
  grandTotal = 0,
  settings = {},
  primaryColor = '#10b981',
  appliedCoupon = null,
  couponDiscount = 0,
  ruleDiscount = 0,
  appliedRules = [],
  prepaidDiscount = 0,
  freeGift = null,
  onOrderPlaced,
}) {
  let customerAuth = null;
  try {
    customerAuth = useCustomerAuth();
  } catch (e) {}

  const [formData, setFormData] = useState({
    name: customerAuth?.customer?.name || '',
    phone: customerAuth?.customer?.phone || '',
    email: customerAuth?.customer?.email || '',
    address: customerAuth?.customer?.address || '',
    city: customerAuth?.customer?.city || '',
    pincode: customerAuth?.customer?.pincode || '',
    paymentMethod: 'UPI', // UPI | COD
  });

  const [step, setStep] = useState(() => (customerAuth?.customer ? 'ADDRESS' : 'FORM'));

  React.useEffect(() => {
    if (customerAuth?.customer && isOpen) {
      const c = customerAuth.customer;
      setFormData((prev) => ({
        ...prev,
        name: prev.name || c.name || '',
        phone: prev.phone || c.phone || '',
        email: prev.email || c.email || '',
        address: prev.address || c.address || '',
        city: prev.city || c.city || '',
        pincode: prev.pincode || c.pincode || '',
      }));
      setStep('ADDRESS');
    } else if (isOpen && !customerAuth?.customer) {
      setStep('FORM');
    }
  }, [customerAuth?.customer, isOpen]);
  const [otpValue, setOtpValue] = useState('');
  const [serverOtp, setServerOtp] = useState('');
  const [orderResult, setOrderResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [finalAmount, setFinalAmount] = useState(0);
  const [resendTimer, setResendTimer] = useState(30);
  const [useWallet, setUseWallet] = useState(false);
  const [showGiftCardInput, setShowGiftCardInput] = useState(false);
  const [gcCode, setGcCode] = useState('');
  const [gcLoading, setGcLoading] = useState(false);
  const [gcMessage, setGcMessage] = useState(null);
  const walletBalance = Number(customerAuth?.walletBalance ?? customerAuth?.customer?.wallet_balance ?? 0);

  const handleRedeemGiftCard = async (e) => {
    if (e) e.preventDefault();
    if (!gcCode.trim()) {
      setGcMessage({ type: 'error', text: 'Please enter your Gift Card Code.' });
      return;
    }
    setGcLoading(true);
    setGcMessage(null);
    try {
      const phone = formData.phone || customerAuth?.customer?.phone || '';
      const email = formData.email || customerAuth?.customer?.email || '';
      const id = customerAuth?.customer?.id;
      const res = await erpApi.redeemGiftCard({ id, phone, email, code: gcCode.trim() });
      if (res && res.success) {
        setGcMessage({ type: 'success', text: `🎉 ₹${res.amount_credited || 'Funds'} Gift Card credited to wallet!` });
        setGcCode('');
        if (typeof customerAuth?.refreshWallet === 'function') {
          await customerAuth.refreshWallet();
        }
        setUseWallet(true);
      } else {
        setGcMessage({ type: 'error', text: res?.message || 'Failed to redeem gift card.' });
      }
    } catch (err) {
      setGcMessage({ type: 'error', text: 'Network error redeeming gift card.' });
    } finally {
      setGcLoading(false);
    }
  };

  // Auto-refresh dynamic wallet balance when checkout opens
  React.useEffect(() => {
    if (isOpen && customerAuth?.customer && typeof customerAuth?.refreshWallet === 'function') {
      customerAuth.refreshWallet();
    }
  }, [isOpen, customerAuth?.customer?.id, customerAuth?.customer?.phone]);

  React.useEffect(() => {
    let interval = null;
    if (step === 'OTP' && resendTimer > 0) {
      interval = setInterval(() => {
        setResendTimer((prev) => (prev > 0 ? prev - 1 : 0));
      }, 1000);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [step, resendTimer]);

  React.useEffect(() => {
    if (!isOpen) return;
    window.onMsg91Verified = (data) => {
      console.log('MSG91 Widget Verified via callback:', data);
      setStep('ADDRESS');
    };
    return () => {
      window.onMsg91Verified = null;
    };
  }, [isOpen]);

  if (!isOpen) return null;

  const effectiveGstCalcType = gstCalcType || settings?.gst_calc_type || 'inclusive';
  const requiresOtp = !(
    settings?.otp_required === false ||
    settings?.otp_required === '0' ||
    settings?.otp_required === 0 ||
    settings?.otp_required === 'false'
  );

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmitContact = async (e) => {
    e.preventDefault();
    setErrorMsg('');

    let phone = formData.phone.replace(/\D/g, '');
    if (phone.length === 11 && phone.startsWith('0')) phone = phone.substring(1);
    if (phone.length < 10) {
      setErrorMsg('Please enter a valid 10-digit mobile number.');
      return;
    }

    const fullPhone = phone.length === 10 ? '91' + phone : phone;

    if (requiresOtp) {
      setLoading(true);
      setErrorMsg('');

      // 1. Dispatch real OTP via MSG91 client SDK
      const sendOtpFn = window.sendOtp || window.sendOTP;
      if (typeof sendOtpFn === 'function') {
        try {
          sendOtpFn(
            fullPhone,
            (data) => console.log('MSG91 Widget OTP dispatched:', data),
            (error) => console.warn('MSG91 Widget sendOtp error:', error)
          );
        } catch (e) {
          console.warn('sendOtp call error:', e);
        }
      }

      // 2. Also dispatch backend ERP OTP
      try {
        await erpApi.sendOtp(formData.phone);
      } catch (err) {
        console.warn('Backend sendOtp error:', err);
      } finally {
        setLoading(false);
        setResendTimer(30);
        setStep('OTP');
      }
    } else {
      setStep('ADDRESS');
    }
  };

  const handleResendOtp = async () => {
    if (resendTimer > 0 || loading) return;
    setErrorMsg('');
    setLoading(true);
    try {
      const res = await erpApi.sendOtp(formData.phone);
      if (res && res.success) {
        setErrorMsg('New OTP sent to your phone.');
        setResendTimer(30);
      } else {
        setErrorMsg(res?.message || 'Failed to resend OTP.');
      }
    } catch (e) {
      setErrorMsg('Error resending OTP.');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    if (!otpValue || otpValue.length < 4) {
      setErrorMsg('Please enter the 4-digit verification code.');
      return;
    }

    setLoading(true);

    const onOtpSuccess = (cust) => {
      if (cust) {
        setFormData((prev) => ({
          ...prev,
          name: cust.name || prev.name,
          email: cust.email || prev.email,
          address: cust.address || prev.address,
        }));
      }
      try {
        if (customerAuth?.quickLogin) {
          customerAuth.quickLogin({
            phone: formData.phone,
            name: cust?.name || formData.name || 'Customer',
            email: cust?.email || formData.email || '',
            address: cust?.address || formData.address || '',
          });
        }
      } catch (e) {}
      setStep('ADDRESS');
      setLoading(false);
    };

    // 1. Verify with MSG91 widget first if available
    const verifyOtpFn = window.verifyOtp || window.verifyOTP;
    if (typeof verifyOtpFn === 'function') {
      try {
        verifyOtpFn(
          otpValue,
          async (data) => {
            console.log('MSG91 Widget OTP verified successfully:', data);
            try {
              const res = await erpApi.verifyOtp(formData.phone, otpValue);
              onOtpSuccess(res?.customer);
            } catch {
              onOtpSuccess(null);
            }
          },
          async (error) => {
            console.warn('MSG91 verifyOtp failed, trying ERP verification:', error);
            try {
              const res = await erpApi.verifyOtp(formData.phone, otpValue);
              if (res && res.success) {
                onOtpSuccess(res.customer);
              } else if (otpValue === '1234' || otpValue === '0000') {
                onOtpSuccess({ phone: formData.phone });
              } else {
                setErrorMsg(error?.message || res?.message || 'Invalid or expired OTP. (Demo: 1234)');
                setLoading(false);
              }
            } catch (err) {
              if (otpValue === '1234' || otpValue === '0000') {
                onOtpSuccess({ phone: formData.phone });
              } else {
                setErrorMsg('Invalid or expired OTP. Use 1234 for demo.');
                setLoading(false);
              }
            }
          }
        );
        return;
      } catch (e) {
        console.warn('verifyOtp error:', e);
      }
    }

    // 2. Verify with ERP backend
    try {
      const res = await erpApi.verifyOtp(formData.phone, otpValue);
      if (res && res.success) {
        onOtpSuccess(res.customer);
      } else if (otpValue === '1234' || otpValue === '0000') {
        onOtpSuccess({ phone: formData.phone });
      } else {
        setErrorMsg(res?.message || 'Invalid or expired OTP. (Demo code: 1234)');
        setLoading(false);
      }
    } catch (err) {
      if (otpValue === '1234' || otpValue === '0000') {
        onOtpSuccess({ phone: formData.phone });
      } else {
        setErrorMsg('Error verifying OTP. Use 1234 for demo.');
        setLoading(false);
      }
    }
  };

  const handleOpenMsg91Widget = () => {
    const widgetId = settings?.widget_id || '36697164476b323432353839';
    const tokenAuth = settings?.token_auth || '572040TDOpHdLN6aab6e64P1';
    let phone = formData.phone.replace(/\D/g, '');
    if (phone.length === 11 && phone.startsWith('0')) phone = phone.substring(1);
    const fullPhone = phone.length === 10 ? '91' + phone : phone;

    if (typeof window.initSendOTP === 'function') {
      try {
        window.initSendOTP({
          widgetId,
          tokenAuth,
          identifier: fullPhone,
          exposeMethods: false,
          success: (data) => {
            console.log('MSG91 Widget Verified:', data);
            setStep('ADDRESS');
          },
          failure: (error) => {
            console.warn('MSG91 Widget Failure:', error);
            setErrorMsg('MSG91 popup closed or not completed.');
          },
        });
      } catch (e) {
        console.warn('initSendOTP invocation error:', e);
        setErrorMsg('Please enter the OTP received on your phone.');
      }
    } else {
      setErrorMsg('MSG91 Widget is not ready. Please enter the OTP received on your phone.');
    }
  };

  const handleAddressSubmit = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    if (!formData.name || !formData.name.trim()) {
      setErrorMsg('Please enter your full name for delivery.');
      return;
    }
    if (!formData.address || !formData.address.trim()) {
      setErrorMsg('Please enter your delivery address.');
      return;
    }
    await finalizeOrder();
  };

  const finalizeOrder = async () => {
    setLoading(true);
    try {
      const isOnline = formData.paymentMethod === 'UPI';
      const effectivePrepaidDisc = isOnline ? Number(prepaidDiscount || 0) : 0;
      const effectiveCouponDisc = Number(couponDiscount || 0);
      const totalDisc = effectivePrepaidDisc + effectiveCouponDisc;
      const isWalletAllowed = settings?.wallet_enabled !== false && settings?.wallet_enabled !== '0' && settings?.wallet_enabled !== 0;
      const maxWalletPercent = Number(settings?.wallet_max_usage_percent ?? 100);
      const computedGrandTotal = Math.max(0, (Number(subtotal) || 0) - totalDisc) + (Number(shippingCharge) || 0);
      const maxSpendableWallet = isWalletAllowed
        ? Math.min(walletBalance, Math.max(0, computedGrandTotal * (maxWalletPercent / 100)))
        : 0;
      const walletDeduction = useWallet && isWalletAllowed ? maxSpendableWallet : 0;
      const netPayable = Math.max(0, computedGrandTotal - walletDeduction);

      // Robust cart items extraction (handles array, { cart: [...] }, or localStorage fallback)
      let cartList = [];
      if (Array.isArray(cart) && cart.length > 0) {
        cartList = cart;
      } else if (cart && Array.isArray(cart.cart) && cart.cart.length > 0) {
        cartList = cart.cart;
      } else if (cart && Array.isArray(cart.items) && cart.items.length > 0) {
        cartList = cart.items;
      } else {
        try {
          const stored = localStorage.getItem('trexo_website_cart');
          if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
              cartList = parsed;
            }
          }
        } catch (e) {}
      }

      if (!cartList || cartList.length === 0) {
        setErrorMsg('Your cart is empty. Please add items to your cart before proceeding.');
        setLoading(false);
        return;
      }

      const orderItems = cartList.map((i) => ({
        product_id: i.id || null,
        product_name: i.product_name || i.name || i.title || 'Product Item',
        quantity: Math.max(1, parseInt(i.quantity, 10) || 1),
        price: Math.max(0, parseFloat(i.selling_price ?? i.price ?? 0)),
        total: Math.max(0, parseFloat(i.selling_price ?? i.price ?? 0)) * Math.max(1, parseInt(i.quantity, 10) || 1),
      }));

      const payload = {
        customer: {
          name: formData.name,
          phone: formData.phone,
          email: formData.email,
          shipping_address: `${formData.address}, ${formData.city} - ${formData.pincode}`,
          billing_address: `${formData.address}, ${formData.city} - ${formData.pincode}`,
        },
        customer_name: formData.name,
        customer_phone: formData.phone,
        customer_email: formData.email,
        shipping_address: `${formData.address}, ${formData.city} - ${formData.pincode}`,
        payment_method: walletDeduction >= computedGrandTotal ? 'WALLET' : formData.paymentMethod,
        wallet_amount: walletDeduction,
        use_wallet: useWallet,
        coupon_code: appliedCoupon?.code || null,
        coupon_discount: effectiveCouponDisc,
        prepaid_discount: effectivePrepaidDisc,
        discount_amount: totalDisc,
        items: orderItems,
        subtotal: Number(subtotal) || 0,
        tax: Number(gstAmount) || 0,
        tax_amount: Number(gstAmount) || 0,
        shipping_charge: Number(shippingCharge) || 0,
        shipping_amount: Number(shippingCharge) || 0,
        grand_total: computedGrandTotal,
      };

      const finalAmountVal = netPayable;
      setFinalAmount(finalAmountVal);

      const placeFn = erpApi.placeOrder ? erpApi.placeOrder.bind(erpApi) : erpApi.createOrder.bind(erpApi);
      const result = await placeFn(payload);
      if (result && (result.success || result.order_id || result.id || result.order)) {
        setOrderResult(result);
        setStep('SUCCESS');
        if (walletDeduction > 0 && customerAuth?.updateProfile) {
          customerAuth.updateProfile({ wallet_balance: Math.max(0, walletBalance - walletDeduction) });
        }
        if (onOrderPlaced) onOrderPlaced(result);
      } else {
        setErrorMsg(result?.message || 'Failed to record order. Please try again.');
      }
    } catch (err) {
      console.error('Order placement exception:', err);
      setErrorMsg(err?.message ? `Error saving order: ${err.message}` : 'Error saving order into ERP.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ position: 'fixed', inset: 0, zIndex: 9500, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
      {/* Backdrop */}
      <div
        onClick={step === 'SUCCESS' ? onClose : undefined}
        style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(6px)',
        }}
      />

      {/* Modal Content */}
      <div
        style={{
          position: 'relative',
          width: '100%',
          maxWidth: (step === 'FORM' || step === 'OTP') ? '430px' : '560px',
          maxHeight: '92vh',
          overflowY: 'auto',
          background: '#ffffff',
          color: '#0f172a',
          borderRadius: '24px',
          boxShadow: '0 25px 60px rgba(0,0,0,0.3)',
          padding: (step === 'FORM' || step === 'OTP') ? 0 : '1.75rem 2rem 2rem',
          zIndex: 9501,
          fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          boxSizing: 'border-box',
          transition: 'max-width 0.2s ease',
        }}
      >
        {/* Dark Header for Login & OTP (Matching exact screenshot) */}
        {(step === 'FORM' || step === 'OTP') && (
          <div
            style={{
              background: '#1e1e1e',
              padding: '1.35rem 1.4rem 1.25rem',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.85rem' }}>
              <div
                style={{
                  width: '46px',
                  height: '46px',
                  borderRadius: '50%',
                  background: 'rgba(255, 255, 255, 0.08)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: primaryColor,
                  border: `1.5px solid ${primaryColor}66`,
                  flexShrink: 0,
                }}
              >
                <Phone size={22} strokeWidth={2.2} />
              </div>
              <div>
                <h3 style={{ margin: 0, color: '#ffffff', fontSize: '1.25rem', fontWeight: 700, letterSpacing: '-0.01em' }}>
                  {step === 'FORM' ? 'Login to Continue' : 'Verify Mobile Number'}
                </h3>
                <p style={{ margin: '0.2rem 0 0', color: '#9ca3af', fontSize: '0.84rem' }}>
                  {step === 'FORM' ? 'Enter your phone number to proceed' : `Enter OTP sent to +91 ${formData.phone}`}
                </p>
              </div>
            </div>
            <button
              onClick={onClose}
              style={{
                background: 'transparent',
                border: 'none',
                color: '#9ca3af',
                cursor: 'pointer',
                padding: '6px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'color 0.15s ease',
              }}
              onMouseEnter={(e) => (e.currentTarget.style.color = '#ffffff')}
              onMouseLeave={(e) => (e.currentTarget.style.color = '#9ca3af')}
            >
              <X size={20} />
            </button>
          </div>
        )}

        {/* Regular close button for Address/Success steps */}
        {step !== 'FORM' && step !== 'OTP' && (
          <button
            onClick={onClose}
            style={{
              position: 'absolute',
              top: '1.25rem',
              right: '1.25rem',
              width: '36px',
              height: '36px',
              borderRadius: '10px',
              border: 'none',
              background: '#f1f5f9',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#64748b',
            }}
          >
            <X size={18} />
          </button>
        )}

        {/* Body Container */}
        <div style={{ padding: (step === 'FORM' || step === 'OTP') ? '1.5rem 1.5rem 1.6rem' : 0 }}>
          {errorMsg && (
            <div
              style={{
                padding: '0.75rem 1rem',
                borderRadius: '12px',
                background: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#b91c1c',
                fontSize: '0.82rem',
                display: 'flex',
                alignItems: 'center',
                gap: '0.5rem',
                marginBottom: '1rem',
                fontWeight: 600,
              }}
            >
              <AlertCircle size={16} />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* STEP 1: LOGIN TO CONTINUE (Exact Screenshot Design) */}
          {step === 'FORM' && (
            <form onSubmit={handleSubmitContact} style={{ display: 'flex', flexDirection: 'column' }}>
              <label style={{ display: 'block', fontSize: '0.88rem', fontWeight: 600, color: '#374151', marginBottom: '0.5rem' }}>
                Phone Number
              </label>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  border: '1.5px solid #18181b',
                  borderRadius: '12px',
                  height: '52px',
                  background: '#ffffff',
                  boxSizing: 'border-box',
                  overflow: 'hidden',
                }}
              >
                <span
                  style={{
                    padding: '0 0.85rem',
                    fontSize: '1rem',
                    fontWeight: 600,
                    color: '#18181b',
                    userSelect: 'none',
                  }}
                >
                  +91
                </span>
                <div style={{ width: '1px', height: '24px', background: '#e4e4e7' }} />
                <input
                  required
                  autoFocus
                  type="tel"
                  maxLength={10}
                  name="phone"
                  value={formData.phone}
                  onChange={(e) => {
                    const val = e.target.value.replace(/\D/g, '');
                    setFormData((prev) => ({ ...prev, phone: val }));
                  }}
                  placeholder="Enter 10-digit mobile number"
                  style={{
                    border: 'none',
                    outline: 'none',
                    flex: 1,
                    height: '100%',
                    padding: '0 0.85rem',
                    fontSize: '0.98rem',
                    fontWeight: 600,
                    color: '#18181b',
                    background: 'transparent',
                  }}
                />
              </div>

              <p style={{ margin: '0.55rem 0 0', fontSize: '0.82rem', color: '#6b7280' }}>
                We'll send you a one-time password to verify
              </p>

              <button
                type="submit"
                disabled={loading || formData.phone.length < 10}
                style={{
                  width: '100%',
                  height: '48px',
                  marginTop: '1.5rem',
                  borderRadius: '12px',
                  border: 'none',
                  background: formData.phone.length === 10 ? primaryColor : `${primaryColor}66`,
                  color: '#ffffff',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  cursor: formData.phone.length === 10 && !loading ? 'pointer' : 'not-allowed',
                  boxShadow: formData.phone.length === 10 ? `0 4px 14px ${primaryColor}40` : 'none',
                  transition: 'all 0.15s ease',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {loading ? 'Sending OTP…' : 'Send OTP'}
              </button>

              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.45rem',
                  marginTop: '1.35rem',
                  color: '#6b7280',
                  fontSize: '0.78rem',
                  fontWeight: 500,
                }}
              >
                <ShieldCheck size={16} color="#6b7280" />
                <span>Your data is secure and encrypted</span>
              </div>
            </form>
          )}

          {/* STEP 2: OTP VERIFICATION */}
          {step === 'OTP' && (
            <form onSubmit={handleVerifyOtp} style={{ display: 'flex', flexDirection: 'column' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <label style={{ fontSize: '0.88rem', fontWeight: 600, color: '#374151' }}>
                  Verification Code
                </label>
                <button
                  type="button"
                  onClick={() => setStep('FORM')}
                  style={{ background: 'none', border: 'none', color: primaryColor, fontSize: '0.82rem', fontWeight: 700, cursor: 'pointer', padding: 0 }}
                >
                  Change number
                </button>
              </div>

              <div
                style={{
                  border: '1.5px solid #18181b',
                  borderRadius: '12px',
                  height: '52px',
                  background: '#ffffff',
                  display: 'flex',
                  alignItems: 'center',
                  overflow: 'hidden',
                }}
              >
                <input
                  required
                  autoFocus
                  type="text"
                  maxLength={6}
                  value={otpValue}
                  onChange={(e) => setOtpValue(e.target.value.replace(/\D/g, ''))}
                  placeholder="Enter 4-digit code (demo: 1234)"
                  style={{
                    width: '100%',
                    border: 'none',
                    outline: 'none',
                    textAlign: 'center',
                    fontSize: '1.15rem',
                    fontWeight: 700,
                    letterSpacing: '0.25em',
                    color: '#18181b',
                    background: 'transparent',
                  }}
                />
              </div>

              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '0.65rem', fontSize: '0.82rem' }}>
                <span style={{ color: '#6b7280' }}>
                  {resendTimer > 0 ? (
                    <>Resend OTP in <b style={{ color: '#18181b' }}>{resendTimer}s</b></>
                  ) : (
                    <button
                      type="button"
                      onClick={handleResendOtp}
                      disabled={loading}
                      style={{ background: 'none', border: 'none', color: primaryColor, fontWeight: 700, cursor: 'pointer', padding: 0 }}
                    >
                      Resend OTP
                    </button>
                  )}
                </span>
                <span style={{ color: '#94a3b8', fontSize: '0.75rem' }}>Demo code: <b>1234</b></span>
              </div>

              <button
                type="submit"
                disabled={loading || otpValue.length < 4}
                style={{
                  width: '100%',
                  height: '48px',
                  marginTop: '1.5rem',
                  borderRadius: '12px',
                  border: 'none',
                  background: otpValue.length >= 4 ? primaryColor : `${primaryColor}66`,
                  color: '#ffffff',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  cursor: otpValue.length >= 4 && !loading ? 'pointer' : 'not-allowed',
                  boxShadow: otpValue.length >= 4 ? `0 4px 14px ${primaryColor}40` : 'none',
                  transition: 'all 0.15s ease',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {loading ? 'Verifying…' : 'Verify & Continue'}
              </button>

              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.45rem',
                  marginTop: '1.35rem',
                  color: '#6b7280',
                  fontSize: '0.78rem',
                  fontWeight: 500,
                }}
              >
                <ShieldCheck size={16} color="#6b7280" />
                <span>Your data is secure and encrypted</span>
              </div>
            </form>
          )}
        </div>

        {/* STEP 3: DELIVERY ADDRESS & PAYMENT */}
        {step === 'ADDRESS' && (
          <form onSubmit={handleAddressSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div>
              <h2 style={{ fontSize: '1.4rem', fontWeight: 800, color: '#0f172a', margin: 0 }}>
                Delivery Address & Payment
              </h2>
            </div>

            {/* Verified contact banner */}
            <div style={{
              padding: '0.65rem 0.85rem',
              borderRadius: '12px',
              background: '#f0fdf4',
              border: '1px solid #bbf7d0',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              fontSize: '0.8rem',
              color: '#166534',
              fontWeight: 600
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                <CheckCircle size={15} color="#16a34a" />
                <span>{requiresOtp ? 'Mobile Verified: ' : 'Contact Phone: '}<b>+91 {formData.phone}</b></span>
              </div>
              <button
                type="button"
                onClick={() => setStep('FORM')}
                style={{
                  background: 'none',
                  border: 'none',
                  color: '#15803d',
                  fontSize: '0.75rem',
                  fontWeight: 700,
                  cursor: 'pointer',
                  textDecoration: 'underline'
                }}
              >
                Change
              </button>
            </div>

            {/* Customer Name & Email */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
              <div>
                <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.35rem' }}>
                  Full Name *
                </label>
                <input
                  required
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  placeholder="e.g. Rahul Sharma"
                  style={inputStyle}
                />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.35rem' }}>
                  Email Address (Optional)
                </label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleInputChange}
                  placeholder="For tracking updates"
                  style={inputStyle}
                />
              </div>
            </div>

            <div>
              <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.35rem' }}>
                Delivery Address *
              </label>
              <textarea
                required
                rows={2}
                name="address"
                value={formData.address}
                onChange={handleInputChange}
                placeholder="Flat / House No., Street, Building, Landmark"
                style={{ ...inputStyle, height: 'auto', padding: '0.6rem 0.75rem' }}
              />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
              <div>
                <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.35rem' }}>
                  City / Town
                </label>
                <input
                  type="text"
                  name="city"
                  value={formData.city}
                  onChange={handleInputChange}
                  placeholder="e.g. Coimbatore"
                  style={inputStyle}
                />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.35rem' }}>
                  PIN Code
                </label>
                <input
                  type="text"
                  name="pincode"
                  value={formData.pincode}
                  onChange={handleInputChange}
                  placeholder="e.g. 641001"
                  style={inputStyle}
                />
              </div>
            </div>

            {/* Store Wallet Deduction Option (Sections 10 & 17 of SRS) */}
            {walletBalance > 0 && settings?.wallet_enabled !== false && settings?.wallet_enabled !== '0' && settings?.wallet_enabled !== 0 && (
              <div
                style={{
                  padding: '0.85rem 1rem',
                  background: useWallet ? '#f0fdf4' : '#f8fafc',
                  border: `1.5px solid ${useWallet ? '#86efac' : '#e2e8f0'}`,
                  borderRadius: '14px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  transition: 'all 0.15s ease',
                }}
              >
                <label style={{ display: 'flex', alignItems: 'center', gap: '0.65rem', cursor: 'pointer', flex: 1 }}>
                  <input
                    type="checkbox"
                    checked={useWallet}
                    onChange={(e) => setUseWallet(e.target.checked)}
                    style={{ width: '17px', height: '17px', accentColor: '#16a34a', cursor: 'pointer' }}
                  />
                  <div>
                    <span style={{ fontSize: '0.84rem', fontWeight: 800, color: useWallet ? '#166534' : '#0f172a', display: 'block' }}>
                      Pay using Store Wallet {settings?.wallet_max_usage_percent && Number(settings.wallet_max_usage_percent) < 100 ? `(Up to ${settings.wallet_max_usage_percent}%)` : ''}
                    </span>
                    <span style={{ fontSize: '0.74rem', color: useWallet ? '#15803d' : '#64748b' }}>
                      Available Balance: ₹{walletBalance.toFixed(2)}
                    </span>
                  </div>
                </label>
                <span style={{ fontSize: '0.92rem', fontWeight: 800, color: '#16a34a' }}>
                  {useWallet ? `-₹${Math.min(walletBalance, Math.max(0, ((subtotal - (couponDiscount || 0) - (formData.paymentMethod === 'UPI' ? (prepaidDiscount || 0) : 0)) + shippingCharge) * (Number(settings?.wallet_max_usage_percent ?? 100) / 100))).toFixed(2)}` : `₹${walletBalance.toFixed(2)}`}
                </span>
              </div>
            )}

            {/* Have a Gift Card / Voucher option */}
            <div style={{ marginTop: '0.4rem', marginBottom: '0.4rem' }}>
              {!showGiftCardInput ? (
                <button
                  type="button"
                  onClick={() => setShowGiftCardInput(true)}
                  style={{
                    background: 'none',
                    border: 'none',
                    padding: 0,
                    color: '#b45309',
                    fontSize: '0.8rem',
                    fontWeight: 700,
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.35rem',
                  }}
                >
                  <span>🎁 Have a Gift Card or Voucher? Redeem here</span>
                </button>
              ) : (
                <div
                  style={{
                    background: '#fffbeb',
                    border: '1.5px solid #fde68a',
                    borderRadius: '14px',
                    padding: '0.75rem',
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
                    <span style={{ fontSize: '0.78rem', fontWeight: 800, color: '#92400e' }}>Redeem Gift Card to Wallet</span>
                    <button
                      type="button"
                      onClick={() => { setShowGiftCardInput(false); setGcMessage(null); }}
                      style={{ background: 'none', border: 'none', color: '#b45309', fontSize: '0.75rem', cursor: 'pointer', fontWeight: 600 }}
                    >
                      Close
                    </button>
                  </div>
                  <div style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: '0.5rem' }}>
                    <input
                      type="text"
                      placeholder="Enter Gift Card Code (GC-XXXX)"
                      value={gcCode}
                      onChange={(e) => setGcCode(e.target.value.toUpperCase())}
                      style={{ ...inputStyle, padding: '0.6rem 0.85rem', fontSize: '0.82rem', textTransform: 'uppercase', fontFamily: 'monospace' }}
                    />
                    <button
                      type="button"
                      disabled={gcLoading}
                      onClick={handleRedeemGiftCard}
                      style={{
                        background: '#d97706',
                        color: '#fff',
                        border: 'none',
                        borderRadius: '10px',
                        padding: '0 1.25rem',
                        fontSize: '0.8rem',
                        fontWeight: 800,
                        cursor: gcLoading ? 'not-allowed' : 'pointer',
                        opacity: gcLoading ? 0.6 : 1,
                      }}
                    >
                      {gcLoading ? '...' : 'Apply Code'}
                    </button>
                  </div>
                  {gcMessage && (
                    <div
                      style={{
                        marginTop: '0.4rem',
                        fontSize: '0.75rem',
                        fontWeight: 700,
                        color: gcMessage.type === 'success' ? '#15803d' : '#b91c1c',
                      }}
                    >
                      {gcMessage.text}
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Payment Method */}
            <div>
              <label style={{ display: 'block', fontSize: '0.78rem', fontWeight: 700, color: '#334155', marginBottom: '0.4rem' }}>
                Payment Method
              </label>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
                <label
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.6rem',
                    padding: '0.75rem',
                    borderRadius: '12px',
                    border: `1.5px solid ${formData.paymentMethod === 'COD' ? primaryColor : '#e2e8f0'}`,
                    background: formData.paymentMethod === 'COD' ? `${primaryColor}08` : '#fff',
                    cursor: 'pointer',
                  }}
                >
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="COD"
                    checked={formData.paymentMethod === 'COD'}
                    onChange={handleInputChange}
                  />
                  <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#0f172a' }}>Cash on Delivery</span>
                </label>
                <label
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.6rem',
                    padding: '0.75rem',
                    borderRadius: '12px',
                    border: `1.5px solid ${formData.paymentMethod === 'UPI' ? primaryColor : '#e2e8f0'}`,
                    background: formData.paymentMethod === 'UPI' ? `${primaryColor}08` : '#fff',
                    cursor: 'pointer',
                  }}
                >
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="UPI"
                    checked={formData.paymentMethod === 'UPI'}
                    onChange={handleInputChange}
                  />
                  <div>
                    <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#0f172a', display: 'block' }}>UPI / Online</span>
                    <span style={{ fontSize: '0.68rem', fontWeight: 800, color: '#16a34a' }}>⚡ Extra 5% OFF</span>
                  </div>
                </label>
              </div>
            </div>

            {/* Price Summary */}
            <div style={{ padding: '0.875rem 1rem', background: '#f8fafc', borderRadius: '14px', marginTop: '0.25rem', display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#64748b' }}>
                <span>Total Items</span>
                <span style={{ fontWeight: 600, color: '#0f172a' }}>{cart.reduce((s, i) => s + i.quantity, 0)} items</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#64748b' }}>
                <span>Subtotal</span>
                <span style={{ fontWeight: 600, color: '#0f172a' }}>₹{subtotal?.toLocaleString('en-IN')}</span>
              </div>

              {appliedCoupon && Number(couponDiscount) > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#16a34a' }}>
                  <span>Coupon Discount ({appliedCoupon.code})</span>
                  <span style={{ fontWeight: 800 }}>-₹{Number(couponDiscount).toLocaleString('en-IN')}</span>
                </div>
              )}

              {appliedRules?.filter(r => r.discount_amount > 0 && r.type !== 'coupon' && !r.coupon_code).map(r => (
                <div key={r.rule_id || r.name} style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#16a34a' }}>
                  <span>⚡ {r.name}</span>
                  <span style={{ fontWeight: 800 }}>-₹{Number(r.discount_amount).toLocaleString('en-IN')}</span>
                </div>
              ))}

              {formData.paymentMethod === 'UPI' && Number(prepaidDiscount) > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#16a34a' }}>
                  <span>Prepaid 5% Extra Discount</span>
                  <span style={{ fontWeight: 800 }}>-₹{Number(prepaidDiscount).toLocaleString('en-IN')}</span>
                </div>
              )}

              {useWallet && walletBalance > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#16a34a' }}>
                  <span>Wallet Balance Applied</span>
                  <span style={{ fontWeight: 800 }}>-₹{Math.min(walletBalance, Math.max(0, (subtotal - (couponDiscount || 0) - (ruleDiscount || 0) - (formData.paymentMethod === 'UPI' ? (prepaidDiscount || 0) : 0)) + shippingCharge)).toFixed(2)}</span>
                </div>
              )}

              {freeGift && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.78rem', color: '#d97706', background: '#fef3c7', padding: '0.25rem 0.5rem', borderRadius: '6px' }}>
                  <span>🎁 Free Gift: {freeGift}</span>
                  <span style={{ fontWeight: 800 }}>FREE</span>
                </div>
              )}

              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#64748b' }}>
                <span>{effectiveGstCalcType === 'inclusive' ? 'GST (Included)' : 'GST'}</span>
                <span style={{ fontWeight: 600, color: '#0f172a' }}>
                  {gstAmount > 0 ? `₹${Number(gstAmount).toFixed(2)}` : '₹0'}
                </span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#64748b' }}>
                <span>Delivery</span>
                <span style={{ fontWeight: 600, color: shippingCharge === 0 ? '#16a34a' : '#0f172a' }}>
                  {shippingCharge === 0 ? 'FREE' : `₹${shippingCharge}`}
                </span>
              </div>
              {(() => {
                const isWalletAllowed = settings?.wallet_enabled !== false && settings?.wallet_enabled !== '0' && settings?.wallet_enabled !== 0;
                const maxWalletPercent = Number(settings?.wallet_max_usage_percent ?? 100);
                const orderGross = Math.max(0, (subtotal - (couponDiscount || 0) - (ruleDiscount || 0) - (formData.paymentMethod === 'UPI' ? (prepaidDiscount || 0) : 0)) + shippingCharge);
                const maxSpendable = isWalletAllowed ? Math.min(walletBalance, Math.max(0, orderGross * (maxWalletPercent / 100))) : 0;
                const appliedWallet = useWallet && isWalletAllowed ? maxSpendable : 0;
                const toPay = Math.max(0, orderGross - appliedWallet);

                return (
                  <>
                    {appliedWallet > 0 && (
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', color: '#16a34a', fontWeight: 700 }}>
                        <span>Wallet Credit Applied</span>
                        <span>-₹{appliedWallet.toFixed(2)}</span>
                      </div>
                    )}
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1rem', fontWeight: 800, color: '#0f172a', paddingTop: '0.4rem', borderTop: '1px dashed #e2e8f0', marginTop: '0.2rem' }}>
                      <span>{appliedWallet > 0 ? 'Net Payable Amount' : 'Order Total'}</span>
                      <span style={{ color: primaryColor }}>
                        ₹{toPay.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  </>
                );
              })()}
            </div>


            <button
              type="submit"
              disabled={loading}
              style={{
                marginTop: '0.5rem',
                width: '100%',
                padding: '0.95rem',
                borderRadius: '14px',
                border: 'none',
                background: primaryColor,
                color: '#fff',
                fontWeight: 800,
                fontSize: '0.95rem',
                cursor: loading ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
              }}
            >
              {loading ? (
                'Placing Order…'
              ) : (
                <>
                  <span>
                    Place Order ({(() => {
                      const isWalletAllowed = settings?.wallet_enabled !== false && settings?.wallet_enabled !== '0' && settings?.wallet_enabled !== 0;
                      const maxWalletPercent = Number(settings?.wallet_max_usage_percent ?? 100);
                      const orderGross = ((subtotal - (couponDiscount || 0) - (formData.paymentMethod === 'UPI' ? (prepaidDiscount || 0) : 0)) + shippingCharge);
                      const maxSpendable = isWalletAllowed ? Math.min(walletBalance, Math.max(0, orderGross * (maxWalletPercent / 100))) : 0;
                      const appliedWallet = useWallet && isWalletAllowed ? maxSpendable : 0;
                      const toPay = Math.max(0, orderGross - appliedWallet);
                      return `₹${toPay.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    })()})
                  </span>
                  <CheckCircle size={18} />
                </>
              )}
            </button>

            <button
              type="button"
              onClick={() => setStep(requiresOtp ? 'OTP' : 'FORM')}
              style={{ background: 'none', border: 'none', color: '#64748b', fontSize: '0.82rem', fontWeight: 600, cursor: 'pointer', textAlign: 'center' }}
            >
              ← Back
            </button>
          </form>
        )}

        {/* STEP 3: SUCCESS */}
        {step === 'SUCCESS' && (
          <div style={{ textAlign: 'center', padding: '1rem 0' }}>
            <div style={{ width: '64px', height: '64px', borderRadius: '22px', background: '#dcfce7', color: '#16a34a', margin: '0 auto 1.25rem', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <CheckCircle size={36} />
            </div>
            <h2 style={{ fontSize: '1.4rem', fontWeight: 900, color: '#0f172a', margin: '0 0 0.4rem' }}>
              Order Placed Successfully!
            </h2>
            <p style={{ fontSize: '0.88rem', color: '#64748b', maxWidth: '380px', margin: '0 auto 1.5rem', lineHeight: 1.5 }}>
              Thank you for ordering. Your order has been placed in TrexoERP and warehouse fulfillment is now underway.
            </p>

            <div style={{ background: '#f8fafc', borderRadius: '16px', padding: '1rem', marginBottom: '1.5rem', textAlign: 'left' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem', fontSize: '0.85rem' }}>
                <span style={{ color: '#64748b' }}>Order Number:</span>
                <span style={{ fontWeight: 800, color: '#0f172a' }}>{orderResult?.order_number || orderResult?.order_id || 'CONFIRMED'}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem', fontSize: '0.85rem' }}>
                <span style={{ color: '#64748b' }}>Amount:</span>
                <span style={{ fontWeight: 800, color: primaryColor }}>
                  ₹{Number(orderResult?.order?.grand_total ?? orderResult?.grand_total ?? finalAmount ?? grandTotal ?? 0).toLocaleString('en-IN')}
                </span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem' }}>
                <span style={{ color: '#64748b' }}>Customer:</span>
                <span style={{ fontWeight: 700, color: '#0f172a' }}>{formData.name} ({formData.phone})</span>
              </div>
            </div>

            <button
              onClick={onClose}
              style={{
                width: '100%',
                padding: '0.95rem',
                borderRadius: '14px',
                border: 'none',
                background: primaryColor,
                color: '#fff',
                fontWeight: 800,
                fontSize: '0.95rem',
                cursor: 'pointer',
              }}
            >
              Continue Shopping
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

const inputStyle = {
  width: '100%',
  height: '42px',
  padding: '0 0.85rem',
  borderRadius: '12px',
  border: '1.5px solid #e2e8f0',
  fontSize: '0.85rem',
  outline: 'none',
  boxSizing: 'border-box',
  background: '#f8fafc',
  fontFamily: 'inherit',
};
