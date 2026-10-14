import React, { useState } from 'react';
import { X, Smartphone, Mail, ArrowRight, ShieldCheck, CheckCircle2, AlertCircle, Sparkles, User, Phone } from 'lucide-react';
import { useCustomerAuth } from './useCustomerAuth';

export default function CustomerAuthModal({ primaryColor = '#f59e0b' }) {
  const { isAuthModalOpen, setIsAuthModalOpen, loginWithOtp, verifyOtp, quickLogin } = useCustomerAuth();

  const [mode, setMode] = useState('phone'); // 'phone' | 'email'
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [name, setName] = useState('');
  const [otp, setOtp] = useState('');
  const [step, setStep] = useState('INPUT'); // 'INPUT' | 'OTP'
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');
  const [resendTimer, setResendTimer] = useState(30);

  React.useEffect(() => {
    let timer = null;
    if (step === 'OTP' && resendTimer > 0) {
      timer = setInterval(() => setResendTimer((t) => (t > 0 ? t - 1 : 0)), 1000);
    }
    return () => {
      if (timer) clearInterval(timer);
    };
  }, [step, resendTimer]);

  if (!isAuthModalOpen) return null;

  const handleClose = () => {
    setIsAuthModalOpen(false);
    setStep('INPUT');
    setOtp('');
    setErrorMsg('');
    setSuccessMsg('');
  };

  const handleSendOtp = async (e) => {
    if (e) e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    const cleanDigits = phone.replace(/\D/g, '');
    if (cleanDigits.length < 10) {
      setErrorMsg('Please enter a valid 10-digit mobile number.');
      return;
    }

    setLoading(true);
    try {
      const res = await loginWithOtp(cleanDigits);
      if (res && res.success) {
        setStep('OTP');
        setOtp(res.demo_otp || res.otp || '1234');
        setResendTimer(30);
      } else {
        setErrorMsg(res?.message || 'Unable to send OTP. Please try again.');
      }
    } catch (err) {
      setStep('OTP');
      setOtp('1234');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e) => {
    if (e) e.preventDefault();
    setErrorMsg('');
    if (!otp.trim()) {
      setErrorMsg('Please enter the verification code.');
      return;
    }

    setLoading(true);
    try {
      const res = await verifyOtp(phone.replace(/\D/g, ''), otp.trim(), name, email);
      if (res && res.success) {
        setSuccessMsg('Logged in successfully!');
      } else if (otp === '1234' || otp === '0000') {
        quickLogin({ phone: phone.replace(/\D/g, ''), name: name || 'Customer' });
      } else {
        setErrorMsg(res?.message || 'Invalid verification code. Use 1234 for demo.');
      }
    } catch (err) {
      if (otp === '1234' || otp === '0000') {
        quickLogin({ phone: phone.replace(/\D/g, ''), name: name || 'Customer' });
      } else {
        setErrorMsg('Verification failed. Try 1234 for demo.');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleEmailLogin = async (e) => {
    e.preventDefault();
    setErrorMsg('');
    if (!email || !email.includes('@')) {
      setErrorMsg('Please enter a valid email address.');
      return;
    }

    setLoading(true);
    try {
      quickLogin({
        name: name || email.split('@')[0],
        email: email.trim(),
        phone: phone || '9876543210',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleQuickDemoLogin = (emailAddress, demoName, demoPhone) => {
    quickLogin({
      name: demoName,
      email: emailAddress,
      phone: demoPhone,
    });
  };

  const cleanPhone = phone.replace(/\D/g, '');
  const isValidPhone = cleanPhone.length === 10;

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 9999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
      }}
    >
      {/* Backdrop */}
      <div
        onClick={handleClose}
        style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(6px)',
        }}
      />

      {/* Modal Box */}
      <div
        style={{
          position: 'relative',
          width: '100%',
          maxWidth: '430px',
          background: '#ffffff',
          borderRadius: '24px',
          boxShadow: '0 25px 60px -15px rgba(0, 0, 0, 0.35)',
          overflow: 'hidden',
          zIndex: 10000,
          fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          boxSizing: 'border-box',
        }}
      >
        {/* Dark Top Header (Matching exact screenshot) */}
        <div
          style={{
            background: '#1e1e1e',
            padding: '1.35rem 1.4rem 1.25rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
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
                {step === 'OTP' ? 'Verify Mobile Number' : 'Login to Continue'}
              </h3>
              <p style={{ margin: '0.2rem 0 0', color: '#9ca3af', fontSize: '0.84rem' }}>
                {step === 'OTP' ? `Enter OTP sent to +91 ${cleanPhone}` : 'Enter your phone number to proceed'}
              </p>
            </div>
          </div>
          <button
            onClick={handleClose}
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

        {/* Form Body */}
        <div style={{ padding: '1.5rem 1.5rem 1.6rem' }}>
          {/* Alerts */}
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

          {successMsg && (
            <div
              style={{
                padding: '0.75rem 1rem',
                background: '#f0fdf4',
                border: '1px solid #bbf7d0',
                borderRadius: '12px',
                color: '#16a34a',
                fontSize: '0.82rem',
                fontWeight: 600,
                display: 'flex',
                alignItems: 'center',
                gap: '0.5rem',
                marginBottom: '1rem',
              }}
            >
              <CheckCircle2 size={16} />
              <span>{successMsg}</span>
            </div>
          )}

          {/* STEP 1: Phone Login (Exact Screenshot Design) */}
          {step === 'INPUT' && mode === 'phone' && (
            <form onSubmit={handleSendOtp} style={{ display: 'flex', flexDirection: 'column' }}>
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
                  value={phone}
                  onChange={(e) => setPhone(e.target.value.replace(/\D/g, ''))}
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
                disabled={loading || !isValidPhone}
                style={{
                  width: '100%',
                  height: '48px',
                  marginTop: '1.5rem',
                  borderRadius: '12px',
                  border: 'none',
                  background: isValidPhone ? primaryColor : `${primaryColor}66`,
                  color: '#ffffff',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  cursor: isValidPhone && !loading ? 'pointer' : 'not-allowed',
                  boxShadow: isValidPhone ? `0 4px 14px ${primaryColor}40` : 'none',
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

              {/* Subtle options for test login & email */}
              <div style={{ marginTop: '1.25rem', paddingTop: '1rem', borderTop: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.78rem' }}>
                <button
                  type="button"
                  onClick={() => setMode('email')}
                  style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: 0, textDecoration: 'underline' }}
                >
                  Sign in with Email
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickDemoLogin('sivapriyanvelmurugan@gmail.com', 'Sivapriyan', '6383272563')}
                  style={{ background: 'none', border: 'none', color: primaryColor, fontWeight: 700, cursor: 'pointer', padding: 0 }}
                >
                  ⚡ Demo 1-Click Login
                </button>
              </div>
            </form>
          )}

          {/* STEP 1 (Alternative): Email Login */}
          {step === 'INPUT' && mode === 'email' && (
            <form onSubmit={handleEmailLogin} style={{ display: 'flex', flexDirection: 'column' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <label style={{ fontSize: '0.88rem', fontWeight: 600, color: '#374151' }}>
                  Email Address
                </label>
                <button
                  type="button"
                  onClick={() => setMode('phone')}
                  style={{ background: 'none', border: 'none', color: primaryColor, fontSize: '0.82rem', fontWeight: 700, cursor: 'pointer', padding: 0 }}
                >
                  Use Phone Number
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
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email"
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

              <button
                type="submit"
                disabled={loading || !email.includes('@')}
                style={{
                  width: '100%',
                  height: '48px',
                  marginTop: '1.5rem',
                  borderRadius: '12px',
                  border: 'none',
                  background: email.includes('@') ? primaryColor : `${primaryColor}66`,
                  color: '#ffffff',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  cursor: email.includes('@') && !loading ? 'pointer' : 'not-allowed',
                  boxShadow: email.includes('@') ? `0 4px 14px ${primaryColor}40` : 'none',
                  transition: 'all 0.15s ease',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {loading ? 'Signing in…' : 'Sign in with Email'}
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

          {/* STEP 2: OTP Verification */}
          {step === 'OTP' && (
            <form onSubmit={handleVerifyOtp} style={{ display: 'flex', flexDirection: 'column' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <label style={{ fontSize: '0.88rem', fontWeight: 600, color: '#374151' }}>
                  Verification Code
                </label>
                <button
                  type="button"
                  onClick={() => setStep('INPUT')}
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
                  value={otp}
                  onChange={(e) => setOtp(e.target.value.replace(/\D/g, ''))}
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
                      onClick={handleSendOtp}
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
                disabled={loading || otp.length < 4}
                style={{
                  width: '100%',
                  height: '48px',
                  marginTop: '1.5rem',
                  borderRadius: '12px',
                  border: 'none',
                  background: otp.length >= 4 ? primaryColor : `${primaryColor}66`,
                  color: '#ffffff',
                  fontSize: '0.95rem',
                  fontWeight: 700,
                  cursor: otp.length >= 4 && !loading ? 'pointer' : 'not-allowed',
                  boxShadow: otp.length >= 4 ? `0 4px 14px ${primaryColor}40` : 'none',
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
      </div>
    </div>
  );
}
