import React, { useState, useEffect, useMemo } from 'react';
import { X, ChevronDown, AlertCircle, Check, Sparkles, Lock } from 'lucide-react';
import confetti from 'canvas-confetti';
import { erpApi } from '../../services/erpApi';

export default function CouponModal({
  isOpen,
  onClose,
  subtotal = 0,
  appliedCoupon = null,
  onApplyCoupon,
  onRemoveCoupon,
  primaryColor = '#111827',
}) {
  const [coupons, setCoupons] = useState([]);
  const [inputCode, setInputCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [expandedTerms, setExpandedTerms] = useState({});

  useEffect(() => {
    if (isOpen) {
      setErrorMsg('');
      erpApi.getCoupons().then((list) => {
        setCoupons(list || []);
      });
    }
  }, [isOpen]);

  // Helper to calculate realistic potential savings
  const calculateSavings = (coupon, amount = subtotal) => {
    if (!amount) return coupon.discount_value || 0;
    if (coupon.discount_type === 'percent') {
      const disc = Math.round((amount * Number(coupon.discount_value || 0)) / 100);
      return coupon.max_discount ? Math.min(disc, Number(coupon.max_discount)) : disc;
    }
    return Math.min(amount, Number(coupon.discount_value || 0));
  };

  // Identify best coupon for the current bill rate
  const bestCouponCode = useMemo(() => {
    const eligible = coupons.filter((c) => subtotal >= (c.min_order_value || 0));
    if (!eligible.length) return null;
    let best = eligible[0];
    let maxSav = calculateSavings(best, subtotal);
    for (const c of eligible) {
      const sav = calculateSavings(c, subtotal);
      if (sav > maxSav) {
        maxSav = sav;
        best = c;
      }
    }
    return best.code;
  }, [coupons, subtotal]);

  // Dynamically sort coupons by user's bill rate
  const sortedCoupons = useMemo(() => {
    if (!coupons || !coupons.length) return [];

    const eligible = coupons.filter((c) => subtotal >= (c.min_order_value || 0));
    const locked = coupons.filter((c) => (c.min_order_value || 0) > subtotal);

    // Eligible: highest savings first
    eligible.sort((a, b) => {
      const savA = calculateSavings(a, subtotal);
      const savB = calculateSavings(b, subtotal);
      if (savB !== savA) return savB - savA;
      if (b.free_gift_name && !a.free_gift_name) return 1;
      if (a.free_gift_name && !b.free_gift_name) return -1;
      return (b.discount_value || 0) - (a.discount_value || 0);
    });

    // Locked: smallest amount needed first
    locked.sort((a, b) => {
      const needA = (a.min_order_value || 0) - subtotal;
      const needB = (b.min_order_value || 0) - subtotal;
      return needA - needB;
    });

    return [...eligible, ...locked];
  }, [coupons, subtotal]);

  if (!isOpen) return null;

  const handleApply = async (codeToApply) => {
    const code = (codeToApply || inputCode).trim().toUpperCase();
    if (!code) {
      setErrorMsg('Please enter a coupon code.');
      return;
    }

    setLoading(true);
    setErrorMsg('');

    try {
      const res = await onApplyCoupon(code);
      if (res.success) {
        setInputCode('');
        try {
          confetti({
            particleCount: 50,
            spread: 60,
            origin: { y: 0.6 },
            colors: ['#111827', '#16a34a', '#f59e0b', '#3b82f6'],
          });
        } catch {}
        onClose(); // Automatically return to cart drawer with coupon applied
      } else {
        setErrorMsg(res.message || 'Invalid or inapplicable coupon code.');
      }
    } catch (err) {
      setErrorMsg('Failed to validate coupon. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const toggleTerms = (code) => {
    setExpandedTerms((prev) => ({ ...prev, [code]: !prev[code] }));
  };

  return (
    <div style={{ position: 'fixed', inset: 0, height: '100vh', width: '100vw', overflow: 'hidden', zIndex: 9005 }}>
      {/* Backdrop */}
      <div
        onClick={onClose}
        style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(0, 0, 0, 0.45)',
          backdropFilter: 'blur(2px)',
          transition: 'opacity 0.2s ease',
        }}
      />

      {/* Slide-in Drawer Body (Exact BUYWOW style) */}
      <div
        style={{
          position: 'fixed',
          top: 0,
          right: 0,
          bottom: 0,
          width: '100%',
          maxWidth: '420px',
          height: '100vh',
          maxHeight: '100vh',
          background: '#ffffff',
          color: '#111827',
          boxShadow: '-10px 0 35px rgba(0, 0, 0, 0.15)',
          display: 'flex',
          flexDirection: 'column',
          overflow: 'hidden',
          zIndex: 9006,
          animation: 'slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1)',
          fontFamily: "'Outfit', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
        }}
      >
        {/* ── 1. Header: [Offers & coupons     ×] ───────────────────── */}
        <div
          style={{
            padding: '1.25rem 1.25rem 1rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            borderBottom: '1px solid #f3f4f6',
            flexShrink: 0,
            background: '#ffffff',
          }}
        >
          <h2
            style={{
              fontSize: '1.2rem',
              fontWeight: 800,
              color: '#111827',
              margin: 0,
              letterSpacing: '-0.01em',
            }}
          >
            Offers &amp; coupons
          </h2>
          <button
            onClick={onClose}
            style={{
              width: '32px',
              height: '32px',
              border: 'none',
              background: 'transparent',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#111827',
              padding: 0,
            }}
            aria-label="Close"
          >
            <X size={22} strokeWidth={2.2} />
          </button>
        </div>

        {/* ── 2. Scrollable Body ───────────────────────────────────── */}
        <div
          className="coupon-scroll-area"
          style={{
            flex: '1 1 auto',
            minHeight: 0,
            overflowY: 'auto',
            padding: '1.15rem',
            display: 'flex',
            flexDirection: 'column',
            gap: '1rem',
          }}
        >
          {/* Have a coupon code? Card */}
          <div
            style={{
              background: '#f9fafb',
              border: '1px solid #e5e7eb',
              borderRadius: '12px',
              padding: '1rem',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.35rem' }}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111827" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" />
                <circle cx="7.5" cy="7.5" r=".5" fill="#111827" />
              </svg>
              <h3 style={{ margin: 0, fontSize: '0.92rem', fontWeight: 700, color: '#111827' }}>
                Have a coupon code?
              </h3>
            </div>
            <p style={{ margin: '0 0 0.85rem', fontSize: '0.78rem', color: '#6b7280' }}>
              Enter your code here and apply it to this cart.
            </p>

            {/* Input + Apply button */}
            <div style={{ display: 'flex', gap: '0.5rem' }}>
              <input
                type="text"
                placeholder="Enter coupon code"
                value={inputCode}
                onChange={(e) => setInputCode(e.target.value.toUpperCase())}
                onKeyDown={(e) => e.key === 'Enter' && handleApply()}
                style={{
                  flex: 1,
                  padding: '0.65rem 0.85rem',
                  border: '1px solid #d1d5db',
                  borderRadius: '8px',
                  fontSize: '0.85rem',
                  fontWeight: 600,
                  textTransform: 'uppercase',
                  outline: 'none',
                  background: '#ffffff',
                }}
              />
              <button
                onClick={() => handleApply()}
                disabled={loading || !inputCode.trim()}
                style={{
                  padding: '0.65rem 1.25rem',
                  borderRadius: '8px',
                  border: 'none',
                  background: inputCode.trim() ? '#111827' : '#9ca3af',
                  color: '#ffffff',
                  fontWeight: 700,
                  fontSize: '0.85rem',
                  cursor: loading || !inputCode.trim() ? 'not-allowed' : 'pointer',
                  transition: 'background 0.15s ease',
                }}
              >
                {loading ? '...' : 'Apply'}
              </button>
            </div>

            {errorMsg && (
              <div style={{ marginTop: '0.6rem', display: 'flex', alignItems: 'center', gap: '0.4rem', color: '#dc2626', fontSize: '0.75rem', fontWeight: 600 }}>
                <AlertCircle size={14} />
                <span>{errorMsg}</span>
              </div>
            )}
          </div>

          {/* Section: AVAILABLE COUPON CODES */}
          <div>
            <div
              style={{
                fontSize: '0.72rem',
                fontWeight: 700,
                color: '#6b7280',
                letterSpacing: '0.06em',
                textTransform: 'uppercase',
                marginBottom: '0.75rem',
              }}
            >
              AVAILABLE COUPON CODES
            </div>

            {/* Coupons List */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
              {sortedCoupons.map((c) => {
                const isApplied = appliedCoupon?.code === c.code;
                const isEligible = subtotal >= (c.min_order_value || 0);
                const needed = Math.max(0, (c.min_order_value || 0) - subtotal);
                const potentialSavings = calculateSavings(c);
                const isBestOffer = isEligible && c.code === bestCouponCode;
                const isTermsOpen = expandedTerms[c.code];

                return (
                  <div
                    key={c.code}
                    style={{
                      background: '#ffffff',
                      border: isApplied
                        ? '1.5px solid #16a34a'
                        : isBestOffer
                        ? '1.5px solid #111827'
                        : '1px solid #e5e7eb',
                      borderRadius: '12px',
                      padding: '1rem',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '0.45rem',
                      boxShadow: isBestOffer ? '0 3px 12px rgba(0,0,0,0.06)' : '0 1px 2px rgba(0,0,0,0.02)',
                      transition: 'all 0.15s ease',
                      position: 'relative',
                    }}
                  >
                    {/* Top Row: Badge + Apply now Button */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', flexWrap: 'wrap' }}>
                        {isBestOffer && (
                          <span
                            style={{
                              background: '#ecfdf5',
                              color: '#047857',
                              border: '1px solid #a7f3d0',
                              fontWeight: 800,
                              fontSize: '0.7rem',
                              padding: '2px 7px',
                              borderRadius: '4px',
                              display: 'inline-flex',
                              alignItems: 'center',
                              gap: '3px',
                            }}
                          >
                            <Sparkles size={11} />
                            BEST FOR YOUR BILL
                          </span>
                        )}
                        <span
                          style={{
                            background: '#fff7ed',
                            color: '#ea580c',
                            border: '1px dashed #fdba74',
                            fontWeight: 800,
                            fontSize: '0.72rem',
                            padding: '2px 8px',
                            borderRadius: '4px',
                          }}
                        >
                          {c.badge || 'SPECIAL OFFER'}
                        </span>
                      </div>

                      <div>
                        {isApplied ? (
                          <button
                            onClick={onRemoveCoupon}
                            style={{
                              background: '#16a34a',
                              color: '#ffffff',
                              border: 'none',
                              borderRadius: '8px',
                              padding: '0.4rem 0.95rem',
                              fontWeight: 700,
                              fontSize: '0.8rem',
                              cursor: 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '0.3rem',
                            }}
                          >
                            <Check size={14} strokeWidth={3} />
                            <span>Applied</span>
                          </button>
                        ) : isEligible ? (
                          <button
                            onClick={() => handleApply(c.code)}
                            disabled={loading}
                            style={{
                              background: isBestOffer ? '#111827' : '#1f2937',
                              color: '#ffffff',
                              border: 'none',
                              borderRadius: '8px',
                              padding: '0.4rem 0.95rem',
                              fontWeight: 700,
                              fontSize: '0.8rem',
                              cursor: 'pointer',
                              transition: 'background 0.15s ease',
                            }}
                            onMouseEnter={(e) => (e.currentTarget.style.background = '#000000')}
                            onMouseLeave={(e) => (e.currentTarget.style.background = isBestOffer ? '#111827' : '#1f2937')}
                          >
                            Apply now
                          </button>
                        ) : (
                          <span
                            style={{
                              background: '#f8fafc',
                              color: '#64748b',
                              border: '1px solid #e2e8f0',
                              borderRadius: '8px',
                              padding: '0.35rem 0.75rem',
                              fontWeight: 700,
                              fontSize: '0.74rem',
                            }}
                          >
                            Add ₹{Math.round(needed)} more
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Title */}
                    <div
                      style={{
                        fontSize: '0.95rem',
                        fontWeight: 700,
                        color: '#111827',
                        marginTop: '0.25rem',
                      }}
                    >
                      {c.name || `Get ${c.badge} with ${c.code}`}
                    </div>

                    {/* Savings text */}
                    {isEligible ? (
                      potentialSavings > 0 && (
                        <div
                          style={{
                            color: '#16a34a',
                            fontWeight: 700,
                            fontSize: '0.82rem',
                          }}
                        >
                          Save ₹{potentialSavings} on this bill
                        </div>
                      )
                    ) : (
                      potentialSavings > 0 && (
                        <div
                          style={{
                            color: '#64748b',
                            fontWeight: 600,
                            fontSize: '0.78rem',
                          }}
                        >
                          Unlock to save up to ₹{potentialSavings}
                        </div>
                      )
                    )}

                    {/* Free gift mention if applicable */}
                    {c.free_gift_name && (
                      <div style={{ fontSize: '0.76rem', color: '#15803d', fontWeight: 600 }}>
                        🎁 Includes: {c.free_gift_name}
                      </div>
                    )}

                    {/* Progress Bar for Locked Coupons */}
                    {!isEligible && (
                      <div
                        style={{
                          marginTop: '0.35rem',
                          padding: '0.5rem 0.65rem',
                          background: '#f8fafc',
                          borderRadius: '8px',
                          border: '1px solid #edf2f7',
                        }}
                      >
                        <div
                          style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            fontSize: '0.72rem',
                            color: '#64748b',
                            marginBottom: '4px',
                          }}
                        >
                          <span>Current bill: ₹{Math.round(subtotal)}</span>
                          <span style={{ fontWeight: 600, color: '#1e293b' }}>
                            Target: ₹{c.min_order_value}
                          </span>
                        </div>
                        <div
                          style={{
                            height: '4px',
                            background: '#e2e8f0',
                            borderRadius: '999px',
                            overflow: 'hidden',
                          }}
                        >
                          <div
                            style={{
                              width: `${Math.min(100, Math.max(5, (subtotal / (c.min_order_value || 1)) * 100))}%`,
                              height: '100%',
                              background: 'linear-gradient(90deg, #ea580c, #f59e0b)',
                              borderRadius: '999px',
                              transition: 'width 0.3s ease',
                            }}
                          />
                        </div>
                      </div>
                    )}

                    {/* Code Pill + Status */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginTop: '0.2rem' }}>
                      <span
                        style={{
                          border: '1px dashed #cbd5e1',
                          background: '#f8fafc',
                          padding: '2px 8px',
                          borderRadius: '4px',
                          fontWeight: 700,
                          fontSize: '0.78rem',
                          fontFamily: 'monospace',
                          color: '#334155',
                        }}
                      >
                        {c.code}
                      </span>
                      <span style={{ fontSize: '0.75rem', color: isEligible ? '#16a34a' : '#6b7280', fontWeight: isEligible ? 600 : 400 }}>
                        {isApplied ? 'Currently active' : isEligible ? 'Eligible on current bill' : `Requires min bill ₹${c.min_order_value}`}
                      </span>
                    </div>

                    {/* Collapsible Terms & Conditions */}
                    <div style={{ marginTop: '0.35rem' }}>
                      <button
                        onClick={() => toggleTerms(c.code)}
                        style={{
                          border: 'none',
                          background: 'transparent',
                          color: '#6b7280',
                          fontSize: '0.72rem',
                          cursor: 'pointer',
                          padding: 0,
                          display: 'flex',
                          alignItems: 'center',
                          gap: '0.25rem',
                        }}
                      >
                        <ChevronDown
                          size={13}
                          style={{
                            transform: isTermsOpen ? 'rotate(180deg)' : 'rotate(0deg)',
                            transition: 'transform 0.2s ease',
                          }}
                        />
                        <span>Terms &amp; conditions</span>
                      </button>

                      {isTermsOpen && (
                        <div
                          style={{
                            marginTop: '0.45rem',
                            padding: '0.55rem 0.75rem',
                            background: '#f9fafb',
                            borderRadius: '8px',
                            fontSize: '0.72rem',
                            color: '#64748b',
                            lineHeight: 1.5,
                          }}
                        >
                          <div>• {c.description || `Valid on orders above ₹${c.min_order_value || 0}`}</div>
                          {c.min_order_value > 0 && <div>• Minimum cart value ₹{c.min_order_value}</div>}
                          {c.max_discount && <div>• Maximum discount cap of ₹{c.max_discount}</div>}
                          <div>• Applicable once per order, cannot be clubbed with other promos</div>
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Bottom Footnote */}
            <div
              style={{
                textAlign: 'center',
                color: '#94a3b8',
                fontSize: '0.72rem',
                marginTop: '1.5rem',
                marginBottom: '0.5rem',
              }}
            >
              One coupon per order. Coupon terms may change without notice.
            </div>
          </div>
        </div>
      </div>

      <style>{`
        @keyframes slideInRight {
          from { transform: translateX(100%); }
          to { transform: translateX(0); }
        }
        .coupon-scroll-area::-webkit-scrollbar {
          width: 5px;
        }
        .coupon-scroll-area::-webkit-scrollbar-track {
          background: transparent;
        }
        .coupon-scroll-area::-webkit-scrollbar-thumb {
          background: #e2e8f0;
          border-radius: 9999px;
        }
        .coupon-scroll-area::-webkit-scrollbar-thumb:hover {
          background: #cbd5e1;
        }
      `}</style>
    </div>
  );
}
