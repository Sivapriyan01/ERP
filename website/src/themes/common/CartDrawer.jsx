import React, { useRef, useState } from 'react';
import { X, ChevronRight, ChevronDown, ChevronUp, Check, Sparkles, Package, Wallet, Coins } from 'lucide-react';
import confetti from 'canvas-confetti';
import { useCustomerAuth } from './useCustomerAuth';

export default function CartDrawer({
  isCartOpen,
  setIsCartOpen,
  cart = [],
  removeFromCart,
  updateQuantity,
  subtotal = 0,
  gstAmount = 0,
  gstCalcType = 'inclusive',
  shippingCharge = 0,
  grandTotal = 0,
  freeDeliveryMin = 399,
  primaryColor = '#111827',
  onCheckout,
  appliedCoupon = null,
  couponDiscount = 0,
  ruleDiscount = 0,
  prepaidDiscount = 0,
  isPrepaidCouponApplied = false,
  freeGift = null,
  freeGiftImage = null,
  freebieProgress = null,
  appliedRules = [],
  allocatedFreebies = [],
  onOpenCouponModal,
  onRemoveCoupon,
  onApplyCoupon,
  applyCoupon,
  suggestedCoupon = null,
  nextTierCoupon = null,
  betterCoupon = null,
  availableCoupons = [],
}) {
  const itemsScrollRef = useRef(null);
  const [isBreakdownOpen, setIsBreakdownOpen] = useState(true);
  const customerAuth = useCustomerAuth ? useCustomerAuth() : null;
  const userWallet = Number(customerAuth?.walletBalance ?? customerAuth?.customer?.wallet_balance ?? 0);
  const userCoins = Number(customerAuth?.rewardPoints ?? customerAuth?.customer?.points ?? 0);

  if (!isCartOpen) return null;

  const totalCartQty = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);

  const formatMoney = (val) => {
    const num = Number(val || 0);
    return num % 1 === 0 ? num.toLocaleString('en-IN') : num.toFixed(1);
  };

  const freeShippingThreshold = Number(freeDeliveryMin || 399);
  const remainingForFreeShipping = Math.max(0, freeShippingThreshold - subtotal);
  const isFreeShipping = subtotal >= freeShippingThreshold && subtotal > 0;
  const shippingProgress = Math.min(100, Math.max(0, (subtotal / freeShippingThreshold) * 100));

  const handleQuickApply = async (code) => {
    const applyFn = onApplyCoupon || applyCoupon;
    if (!applyFn) {
      if (onOpenCouponModal) onOpenCouponModal();
      return;
    }
    const res = await applyFn(code);
    if (res?.success) {
      try {
        confetti({
          particleCount: 50,
          spread: 60,
          origin: { y: 0.65 },
          colors: ['#111827', '#16a34a', '#f59e0b', '#3b82f6'],
        });
      } catch {}
    }
  };

  return (
    <div style={{ position: 'fixed', inset: 0, height: '100vh', width: '100vw', overflow: 'hidden', zIndex: 9000 }}>
      {/* Backdrop */}
      <div
        onClick={() => setIsCartOpen(false)}
        style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(0, 0, 0, 0.45)',
          backdropFilter: 'blur(2px)',
          transition: 'opacity 0.2s ease',
        }}
      />

      {/* Drawer Body (Clean Pure White BUYWOW style) */}
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
          boxShadow: '-10px 0 35px rgba(0, 0, 0, 0.12)',
          display: 'flex',
          flexDirection: 'column',
          overflow: 'hidden',
          zIndex: 9001,
          animation: 'slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1)',
          fontFamily: "'Outfit', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
        }}
      >
        {/* ── 1. BUYWOW Header [🛍️ Your Cart (1)  ×] ─────────────────── */}
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
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
            {/* Bag Icon */}
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#111827" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
              <path d="M3 6h18" />
              <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            <h2
              style={{
                fontSize: '1.15rem',
                fontWeight: 700,
                color: '#111827',
                margin: 0,
                letterSpacing: '-0.01em',
              }}
            >
              Your Cart <span style={{ fontWeight: 500, color: '#4b5563' }}>({totalCartQty})</span>
            </h2>
          </div>

          <button
            onClick={() => setIsCartOpen(false)}
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
              borderRadius: '8px',
            }}
            aria-label="Close cart"
          >
            <X size={22} strokeWidth={2.2} />
          </button>
        </div>

        {/* ── Dynamic Freebie & Tier Progress Banner (SRS Section 7) ────────── */}
        {cart.length > 0 && freebieProgress && (
          <div
            style={{
              padding: '0.75rem 1.25rem',
              background: freebieProgress.is_unlocked ? '#f0fdf4' : '#faf5ff',
              borderBottom: `1px solid ${freebieProgress.is_unlocked ? '#bbf7d0' : '#e9d5ff'}`,
              flexShrink: 0,
            }}
          >
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                fontSize: '0.78rem',
                fontWeight: 700,
                color: freebieProgress.is_unlocked ? '#15803d' : '#7e22ce',
                marginBottom: '0.4rem',
              }}
            >
              <span style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                {freebieProgress.is_unlocked ? '🎉' : '🎁'} {freebieProgress.message}
              </span>
              <span
                style={{
                  fontSize: '0.7rem',
                  padding: '2px 8px',
                  borderRadius: '999px',
                  background: freebieProgress.is_unlocked ? '#16a34a' : '#9333ea',
                  color: '#ffffff',
                  fontWeight: 800,
                  letterSpacing: '0.02em',
                }}
              >
                {freebieProgress.is_unlocked ? 'UNLOCKED' : `₹${formatMoney(freebieProgress.threshold)}`}
              </span>
            </div>
            <div
              style={{
                height: '6px',
                width: '100%',
                background: 'rgba(0, 0, 0, 0.06)',
                borderRadius: '999px',
                overflow: 'hidden',
              }}
            >
              <div
                style={{
                  height: '100%',
                  width: `${Math.min(100, Math.max(0, (subtotal / (freebieProgress.threshold || 999)) * 100))}%`,
                  background: freebieProgress.is_unlocked
                    ? '#16a34a'
                    : 'linear-gradient(90deg, #9333ea, #c084fc)',
                  borderRadius: '999px',
                  transition: 'width 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                }}
              />
            </div>
          </div>
        )}

        {/* ── Dynamic Logged-in Customer Wallet & Coins Notification Bar ────── */}
        {cart.length > 0 && customerAuth?.isLoggedIn && (userWallet > 0 || userCoins > 0) && (
          <div
            style={{
              padding: '0.6rem 1.25rem',
              background: '#f8fafc',
              borderBottom: '1px solid #e2e8f0',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              fontSize: '0.78rem',
              flexShrink: 0,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
              {userWallet > 0 && (
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#15803d', fontWeight: 700 }}>
                  <Wallet size={13} color="#16a34a" /> ₹{formatMoney(userWallet)} Wallet
                </span>
              )}
              {userCoins > 0 && (
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#b45309', fontWeight: 700 }}>
                  <Coins size={13} color="#d97706" /> {userCoins} Coins
                </span>
              )}
            </div>
            <span
              style={{
                fontSize: '0.7rem',
                color: '#16a34a',
                fontWeight: 700,
                background: '#dcfce7',
                padding: '2px 7px',
                borderRadius: '6px',
              }}
            >
              Use at Checkout
            </span>
          </div>
        )}

        {/* ── 2. Scrollable Items Area ─────────────────────────────── */}
        <div
          className="cart-scroll-area"
          style={{
            flex: '1 1 auto',
            minHeight: 0,
            overflowY: 'auto',
            overflowX: 'hidden',
            WebkitOverflowScrolling: 'touch',
            overscrollBehavior: 'contain',
            padding: '1rem 1.25rem',
            display: 'flex',
            flexDirection: 'column',
            gap: '1rem',
          }}
        >
          {cart.length === 0 ? (
            /* Empty Cart */
            <div
              style={{
                padding: '4rem 1.5rem',
                textAlign: 'center',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '0.85rem',
                margin: 'auto 0',
              }}
            >
              <div
                style={{
                  width: '64px',
                  height: '64px',
                  borderRadius: '50%',
                  background: '#f3f4f6',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#6b7280',
                }}
              >
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                  <line x1="3" y1="6" x2="21" y2="6" />
                  <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
              </div>
              <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700, color: '#111827' }}>
                Your cart is empty
              </h3>
              <p style={{ margin: 0, fontSize: '0.85rem', color: '#6b7280' }}>
                Explore bestsellers, natural hair care, and skincare products.
              </p>
              <button
                onClick={() => setIsCartOpen(false)}
                style={{
                  marginTop: '0.5rem',
                  padding: '0.75rem 1.8rem',
                  borderRadius: '10px',
                  border: 'none',
                  background: '#111827',
                  color: '#ffffff',
                  fontWeight: 700,
                  fontSize: '0.88rem',
                  cursor: 'pointer',
                }}
              >
                Shop Now
              </button>
            </div>
          ) : (
            <>
              {/* Product Items List */}
              <div
                ref={itemsScrollRef}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '1rem',
                }}
              >
                {/* Optional Free Gift mini */}
                {freeGift && (
                  <div
                    style={{
                      display: 'flex',
                      gap: '0.85rem',
                      alignItems: 'flex-start',
                      paddingBottom: '1rem',
                      borderBottom: '1px solid #f3f4f6',
                    }}
                  >
                    <div
                      style={{
                        width: '68px',
                        height: '68px',
                        borderRadius: '12px',
                        background: '#fef3c7',
                        border: '1px solid #fef08a',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                        overflow: 'hidden',
                        padding: '4px',
                      }}
                    >
                      <img
                        src={freeGiftImage || (typeof freeGift === 'object' && freeGift?.image) || '/gift-icon.png'}
                        alt="Free Gift"
                        style={{
                          width: '100%',
                          height: '100%',
                          objectFit: 'contain',
                        }}
                        onError={(e) => {
                          e.currentTarget.style.display = 'none';
                          if (e.currentTarget.parentElement) {
                            e.currentTarget.parentElement.innerText = '🎁';
                            e.currentTarget.parentElement.style.fontSize = '1.5rem';
                          }
                        }}
                      />
                    </div>
                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '0.5rem' }}>
                        <h4
                          style={{
                            margin: 0,
                            fontSize: '0.88rem',
                            fontWeight: 600,
                            color: '#111827',
                            lineHeight: 1.35,
                          }}
                        >
                          {typeof freeGift === 'string' ? freeGift : 'Complimentary Skincare Sample Mini'}
                        </h4>
                        <span style={{ fontSize: '0.95rem', fontWeight: 800, color: '#16a34a', whiteSpace: 'nowrap' }}>
                          FREE
                        </span>
                      </div>
                      <div style={{ fontSize: '0.75rem', color: '#6b7280', marginTop: '0.35rem' }}>
                        Qty: 1 • Unlocked with your order!
                      </div>
                    </div>
                  </div>
                )}

                {/* Regular Products */}
                {cart.map((item, idx) => (
                  <div
                    key={item.id}
                    style={{
                      display: 'flex',
                      gap: '0.85rem',
                      alignItems: 'flex-start',
                      paddingBottom: '1rem',
                      borderBottom: idx < cart.length - 1 ? '1px solid #f3f4f6' : 'none',
                    }}
                  >
                    {/* Square Image */}
                    <div
                      style={{
                        width: '68px',
                        height: '68px',
                        borderRadius: '12px',
                        background: '#ffffff',
                        border: '1px solid #f3f4f6',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        flexShrink: 0,
                        overflow: 'hidden',
                      }}
                    >
                      {item.image ? (
                        <img
                          src={item.image}
                          alt={item.product_name}
                          style={{
                            width: '100%',
                            height: '100%',
                            objectFit: 'contain',
                            padding: '4px',
                          }}
                          onError={(e) => {
                            e.currentTarget.style.display = 'none';
                            const fallback = e.currentTarget.parentElement?.querySelector('.cart-item-ph');
                            if (fallback) fallback.style.display = 'flex';
                          }}
                        />
                      ) : null}
                      <div
                        className="cart-item-ph"
                        style={{
                          display: item.image ? 'none' : 'flex',
                          width: '100%',
                          height: '100%',
                          alignItems: 'center',
                          justifyContent: 'center',
                          background: '#f8fafc',
                          color: '#94a3b8',
                        }}
                      >
                        <Package size={22} />
                      </div>
                    </div>

                    {/* Content & Actions */}
                    <div style={{ flex: 1, minWidth: 0 }}>
                      {/* Name & Price on Top Line */}
                      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '0.5rem' }}>
                        <h4
                          style={{
                            margin: 0,
                            fontSize: '0.88rem',
                            fontWeight: 600,
                            color: '#111827',
                            lineHeight: 1.35,
                            display: '-webkit-box',
                            WebkitLineClamp: 2,
                            WebkitBoxOrient: 'vertical',
                            overflow: 'hidden',
                          }}
                        >
                          {item.product_name}
                        </h4>
                        <span style={{ fontSize: '0.98rem', fontWeight: 700, color: '#111827', whiteSpace: 'nowrap' }}>
                          ₹{formatMoney(item.selling_price || 0)}
                        </span>
                      </div>

                      {/* Pill Quantity Selector + Remove Link */}
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: '0.85rem',
                          marginTop: '0.65rem',
                        }}
                      >
                        {/* [ −  1  + ] Pill */}
                        <div
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            background: '#f3f4f6',
                            borderRadius: '8px',
                            height: '28px',
                            padding: '0 4px',
                          }}
                        >
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                            style={{
                              width: '24px',
                              height: '24px',
                              border: 'none',
                              background: 'transparent',
                              cursor: 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: '#374151',
                              fontSize: '0.95rem',
                              fontWeight: 600,
                              lineHeight: 1,
                            }}
                            aria-label="Decrease quantity"
                          >
                            −
                          </button>
                          <span
                            style={{
                              fontSize: '0.85rem',
                              fontWeight: 700,
                              minWidth: '22px',
                              textAlign: 'center',
                              color: '#111827',
                            }}
                          >
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                            disabled={item.quantity >= (item.stock || 999)}
                            style={{
                              width: '24px',
                              height: '24px',
                              border: 'none',
                              background: 'transparent',
                              cursor: item.quantity >= (item.stock || 999) ? 'not-allowed' : 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: '#374151',
                              fontSize: '0.95rem',
                              fontWeight: 600,
                              lineHeight: 1,
                              opacity: item.quantity >= (item.stock || 999) ? 0.3 : 1,
                            }}
                            aria-label="Increase quantity"
                          >
                            +
                          </button>
                        </div>

                        {/* Remove Text Button */}
                        <button
                          onClick={() => removeFromCart(item.id)}
                          style={{
                            border: 'none',
                            background: 'transparent',
                            color: '#6b7280',
                            fontSize: '0.78rem',
                            fontWeight: 500,
                            cursor: 'pointer',
                            padding: 0,
                            transition: 'color 0.15s ease',
                          }}
                          onMouseEnter={(e) => (e.currentTarget.style.color = '#111827')}
                          onMouseLeave={(e) => (e.currentTarget.style.color = '#6b7280')}
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* ── 3. BUYWOW "View offers & coupons" Trigger Card ──── */}
              <div style={{ marginTop: '0.5rem', display: 'flex', flexDirection: 'column', gap: '0.45rem' }}>
                {appliedCoupon ? (
                  <>
                    {/* Applied Coupon Green Card */}
                    <div
                      style={{
                        background: '#f0fdf4',
                        border: '1px solid #bbf7d0',
                        borderRadius: '12px',
                        padding: '0.85rem 1rem',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        transition: 'all 0.2s ease',
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
                        <div
                          style={{
                            width: '24px',
                            height: '24px',
                            borderRadius: '50%',
                            background: '#16a34a',
                            color: '#ffffff',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            flexShrink: 0,
                          }}
                        >
                          <Check size={14} strokeWidth={3} />
                        </div>
                        <div>
                          <div style={{ fontSize: '0.88rem', fontWeight: 700, color: '#15803d' }}>
                            {appliedCoupon.code} applied
                          </div>
                          <div style={{ fontSize: '0.78rem', color: '#16a34a', fontWeight: 600 }}>
                            Congrats, you saved ₹{formatMoney(couponDiscount)}
                            {appliedCoupon.free_gift && <span> + Free gift</span>}
                          </div>
                        </div>
                      </div>
                      <button
                        onClick={onRemoveCoupon}
                        style={{
                          border: 'none',
                          background: 'transparent',
                          color: '#15803d',
                          cursor: 'pointer',
                          padding: '4px',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                        aria-label="Remove coupon"
                      >
                        <X size={18} strokeWidth={2.2} />
                      </button>
                    </div>

                    {/* Proactive suggestion: If bill qualifies for a BETTER coupon */}
                    {betterCoupon && (
                      <div
                        style={{
                          background: '#fffbeb',
                          border: '1px dashed #f59e0b',
                          borderRadius: '10px',
                          padding: '0.65rem 0.85rem',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          gap: '0.5rem',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', fontSize: '0.78rem', color: '#92400e' }}>
                          <Sparkles size={14} color="#d97706" style={{ flexShrink: 0 }} />
                          <span>
                            Save <strong>₹{formatMoney(betterCoupon.extraSavings)} MORE</strong> with code <strong>'{betterCoupon.code}'</strong>!
                          </span>
                        </div>
                        <button
                          onClick={() => handleQuickApply(betterCoupon.code)}
                          style={{
                            background: '#d97706',
                            color: '#ffffff',
                            border: 'none',
                            borderRadius: '6px',
                            padding: '0.3rem 0.65rem',
                            fontSize: '0.74rem',
                            fontWeight: 700,
                            cursor: 'pointer',
                            whiteSpace: 'nowrap',
                          }}
                        >
                          Switch
                        </button>
                      </div>
                    )}

                    {/* Next Tier Nudge: Higher bill unlocks an even better offer */}
                    {nextTierCoupon && (
                      <div
                        onClick={onOpenCouponModal}
                        style={{
                          background: '#f8fafc',
                          border: '1px solid #e2e8f0',
                          borderRadius: '10px',
                          padding: '0.55rem 0.85rem',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          cursor: 'pointer',
                          fontSize: '0.76rem',
                          color: '#475569',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                          <span style={{ fontSize: '0.85rem' }}>🎯</span>
                          <span>
                            Add <strong>₹{formatMoney(nextTierCoupon.amountNeeded)}</strong> more to get <strong>{nextTierCoupon.badge || (nextTierCoupon.discount_value + '% OFF')}</strong> with <strong>'{nextTierCoupon.code}'</strong>
                          </span>
                        </div>
                        <ChevronRight size={14} color="#94a3b8" />
                      </div>
                    )}
                  </>
                ) : suggestedCoupon ? (
                  /* Smart Coupon Suggestion based on current bill rate */
                  <div
                    style={{
                      background: '#ffffff',
                      border: '1.5px solid #111827',
                      borderRadius: '12px',
                      padding: '0.85rem 1rem',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '0.5rem',
                    }}
                  >
                    {/* Top Row: Suggestion Tag & 1-Click Apply */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <span
                        style={{
                          background: '#fff7ed',
                          color: '#ea580c',
                          border: '1px dashed #fdba74',
                          fontWeight: 800,
                          fontSize: '0.7rem',
                          padding: '2px 7px',
                          borderRadius: '4px',
                          letterSpacing: '0.02em',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '3px',
                        }}
                      >
                        <Sparkles size={11} />
                        BEST OFFER FOR YOUR BILL
                      </span>

                      <button
                        onClick={() => handleQuickApply(suggestedCoupon.code)}
                        style={{
                          background: '#111827',
                          color: '#ffffff',
                          border: 'none',
                          borderRadius: '8px',
                          padding: '0.35rem 0.9rem',
                          fontSize: '0.8rem',
                          fontWeight: 700,
                          cursor: 'pointer',
                          transition: 'background 0.15s ease',
                        }}
                        onMouseEnter={(e) => (e.currentTarget.style.background = '#1f2937')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = '#111827')}
                      >
                        Apply
                      </button>
                    </div>

                    {/* Savings Information */}
                    <div>
                      <div style={{ fontSize: '0.92rem', fontWeight: 800, color: '#111827' }}>
                        Save ₹{formatMoney(suggestedCoupon.savings)} with '{suggestedCoupon.code}'
                      </div>
                      <div style={{ fontSize: '0.76rem', color: '#6b7280', marginTop: '1px' }}>
                        {suggestedCoupon.name || suggestedCoupon.description || `Flat ${suggestedCoupon.discount_value}% off on your order`}
                        {suggestedCoupon.free_gift_name && (
                          <span style={{ color: '#16a34a', fontWeight: 600 }}> + {suggestedCoupon.free_gift_name}</span>
                        )}
                      </div>
                    </div>

                    {/* Bottom Row: Nudge / View all */}
                    <div
                      style={{
                        paddingTop: '0.45rem',
                        borderTop: '1px solid #f3f4f6',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        fontSize: '0.75rem',
                      }}
                    >
                      {nextTierCoupon ? (
                        <span style={{ color: '#ea580c', fontWeight: 600 }}>
                          Add ₹{formatMoney(nextTierCoupon.amountNeeded)} more for {nextTierCoupon.badge || 'extra savings'}!
                        </span>
                      ) : (
                        <span style={{ color: '#16a34a', fontWeight: 600 }}>
                          Best bill savings unlocked!
                        </span>
                      )}

                      <button
                        onClick={onOpenCouponModal}
                        style={{
                          background: 'transparent',
                          border: 'none',
                          padding: 0,
                          color: '#4b5563',
                          fontWeight: 600,
                          cursor: 'pointer',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '2px',
                        }}
                      >
                        <span>View all</span>
                        <ChevronRight size={13} />
                      </button>
                    </div>
                  </div>
                ) : nextTierCoupon ? (
                  /* Bill is below minimum coupon value: Smart progress tier nudge */
                  <div
                    style={{
                      background: '#f9fafb',
                      border: '1px solid #e5e7eb',
                      borderRadius: '12px',
                      padding: '0.85rem 1rem',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '0.35rem',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.45rem' }}>
                        <Sparkles size={15} color="#ea580c" />
                        <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#111827' }}>
                          Add ₹{formatMoney(nextTierCoupon.amountNeeded)} more to unlock {nextTierCoupon.badge || (nextTierCoupon.discount_value + '% OFF')}!
                        </span>
                      </div>
                      <button
                        onClick={onOpenCouponModal}
                        style={{
                          background: 'transparent',
                          border: 'none',
                          padding: 0,
                          color: '#ea580c',
                          fontWeight: 700,
                          fontSize: '0.76rem',
                          cursor: 'pointer',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '1px',
                        }}
                      >
                        <span>Offers</span>
                        <ChevronRight size={13} />
                      </button>
                    </div>

                    {/* Progress Bar towards this coupon */}
                    <div
                      style={{
                        width: '100%',
                        height: '4px',
                        background: '#e5e7eb',
                        borderRadius: '999px',
                        overflow: 'hidden',
                        margin: '0.2rem 0',
                      }}
                    >
                      <div
                        style={{
                          width: `${Math.min(100, Math.max(6, (subtotal / (nextTierCoupon.min_order_value || 1)) * 100))}%`,
                          height: '100%',
                          background: 'linear-gradient(90deg, #ea580c, #f59e0b)',
                          borderRadius: '999px',
                          transition: 'width 0.3s ease',
                        }}
                      />
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.72rem', color: '#6b7280' }}>
                      <span>Current bill: ₹{formatMoney(subtotal)}</span>
                      <span>Target: ₹{formatMoney(nextTierCoupon.min_order_value)} (Use '{nextTierCoupon.code}')</span>
                    </div>
                  </div>
                ) : (
                  /* Default "View offers & coupons" Card */
                  <div
                    onClick={onOpenCouponModal}
                    style={{
                      background: '#f9fafb',
                      border: '1px solid #e5e7eb',
                      borderRadius: '12px',
                      padding: '0.85rem 1rem',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      cursor: 'pointer',
                      transition: 'all 0.15s ease',
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.borderColor = '#cbd5e1';
                      e.currentTarget.style.background = '#f3f4f6';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.borderColor = '#e5e7eb';
                      e.currentTarget.style.background = '#f9fafb';
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111827" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" />
                        <circle cx="7.5" cy="7.5" r=".5" fill="#111827" />
                      </svg>
                      <span style={{ fontSize: '0.9rem', fontWeight: 600, color: '#111827' }}>
                        View offers &amp; coupons
                      </span>
                    </div>
                    <ChevronRight size={18} color="#6b7280" />
                  </div>
                )}
              </div>
            </>
          )}
        </div>

        {/* ── 4. BUYWOW Bottom Sticky Summary & Checkout ────────────── */}
        {cart.length > 0 && (
          <div
            style={{
              background: '#ffffff',
              borderTop: '1px solid #f3f4f6',
              padding: '1.15rem 1.25rem 1rem',
              display: 'flex',
              flexDirection: 'column',
              gap: isBreakdownOpen ? '0.65rem' : '0.4rem',
              flexShrink: 0,
            }}
          >
            {/* Header: Bill Summary with Up/Down Hide Button */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span style={{ fontSize: '0.74rem', fontWeight: 700, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                Bill Summary
              </span>
              <button
                type="button"
                onClick={() => setIsBreakdownOpen(!isBreakdownOpen)}
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '999px',
                  padding: '3px 10px',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                  cursor: 'pointer',
                  color: '#4b5563',
                  fontSize: '0.74rem',
                  fontWeight: 700,
                  transition: 'all 0.15s ease',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = '#f1f5f9';
                  e.currentTarget.style.borderColor = '#cbd5e1';
                  e.currentTarget.style.color = '#111827';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = '#f8fafc';
                  e.currentTarget.style.borderColor = '#e2e8f0';
                  e.currentTarget.style.color = '#4b5563';
                }}
              >
                <span>{isBreakdownOpen ? 'Hide' : 'Show details'}</span>
                {isBreakdownOpen ? <ChevronUp size={13} strokeWidth={2.5} /> : <ChevronDown size={13} strokeWidth={2.5} />}
              </button>
            </div>

            {/* Collapsible Breakdown Details */}
            {isBreakdownOpen && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.65rem' }}>
                {/* Subtotal */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.88rem', color: '#6b7280' }}>
                  <span>Subtotal</span>
                  <span style={{ fontWeight: 600, color: '#111827' }}>₹{formatMoney(subtotal)}</span>
                </div>

                {/* Coupon Discount (if applied) */}
                {appliedCoupon && couponDiscount > 0 && (
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.88rem' }}>
                    <span style={{ color: '#6b7280' }}>Coupon ({appliedCoupon.code})</span>
                    <span style={{ fontWeight: 700, color: '#16a34a' }}>-₹{formatMoney(couponDiscount)}</span>
                  </div>
                )}

                {/* Server Promotion Rule Discounts (SRS Section 7) */}
                {appliedRules?.filter(r => r.discount_amount > 0 && r.type !== 'coupon' && !r.coupon_code).map(r => (
                  <div key={r.rule_id || r.name} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.88rem' }}>
                    <span style={{ color: '#6b7280', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <span>⚡</span> {r.name}
                    </span>
                    <span style={{ fontWeight: 700, color: '#16a34a' }}>-₹{formatMoney(r.discount_amount)}</span>
                  </div>
                ))}

                {/* Prepaid Discount (if applicable) */}
                {prepaidDiscount > 0 && !isPrepaidCouponApplied && (
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.88rem' }}>
                    <span style={{ color: '#6b7280' }}>Prepaid Discount</span>
                    <span style={{ fontWeight: 700, color: '#16a34a' }}>-₹{formatMoney(prepaidDiscount)}</span>
                  </div>
                )}

                {/* Shipping Row */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: '0.88rem', color: '#6b7280' }}>
                  <span>Shipping</span>
                  <span style={{ fontWeight: 700, color: shippingCharge === 0 || isFreeShipping ? '#16a34a' : '#111827' }}>
                    {shippingCharge === 0 || isFreeShipping ? 'Free' : `₹${formatMoney(shippingCharge)}`}
                  </span>
                </div>

                {/* Inline Free Shipping Meter (BUYWOW style) */}
                {!isFreeShipping && remainingForFreeShipping > 0 && (
                  <div
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '0.5rem',
                      padding: '0.2rem 0',
                    }}
                  >
                    <span style={{ fontSize: '0.85rem' }}>🚚</span>
                    <div
                      style={{
                        flex: 1,
                        height: '4px',
                        background: '#e5e7eb',
                        borderRadius: '9999px',
                        overflow: 'hidden',
                      }}
                    >
                      <div
                        style={{
                          height: '100%',
                          width: `${shippingProgress}%`,
                          background: '#16a34a',
                          borderRadius: '9999px',
                          transition: 'width 0.3s ease',
                        }}
                      />
                    </div>
                    <span style={{ fontSize: '0.72rem', color: '#6b7280', whiteSpace: 'nowrap' }}>
                      Add ₹{formatMoney(remainingForFreeShipping)} more for free shipping
                    </span>
                  </div>
                )}
              </div>
            )}

            {/* Total Payable Line (Clickable with Up/Down Arrow button) */}
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                paddingTop: isBreakdownOpen ? '0.45rem' : '0.2rem',
                borderTop: isBreakdownOpen ? '1px solid #f3f4f6' : 'none',
                marginTop: isBreakdownOpen ? '0.2rem' : '0',
              }}
            >
              <div
                onClick={() => setIsBreakdownOpen(!isBreakdownOpen)}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.45rem',
                  cursor: 'pointer',
                  userSelect: 'none',
                }}
              >
                <span style={{ fontSize: '1rem', fontWeight: 700, color: '#111827' }}>
                  Total payable
                </span>
                <button
                  type="button"
                  onClick={(e) => {
                    e.stopPropagation();
                    setIsBreakdownOpen(!isBreakdownOpen);
                  }}
                  title={isBreakdownOpen ? 'Hide breakdown' : 'Show breakdown'}
                  style={{
                    background: '#f3f4f6',
                    border: 'none',
                    borderRadius: '6px',
                    width: '22px',
                    height: '22px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    color: '#4b5563',
                    padding: 0,
                    transition: 'all 0.15s ease',
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.background = '#e5e7eb';
                    e.currentTarget.style.color = '#111827';
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.background = '#f3f4f6';
                    e.currentTarget.style.color = '#4b5563';
                  }}
                >
                  {isBreakdownOpen ? <ChevronUp size={15} strokeWidth={2.5} /> : <ChevronDown size={15} strokeWidth={2.5} />}
                </button>
              </div>

              <div style={{ textAlign: 'right' }}>
                <span
                  style={{
                    fontSize: '1.65rem',
                    fontWeight: 900,
                    color: '#111827',
                    letterSpacing: '-0.02em',
                  }}
                >
                  ₹{formatMoney(grandTotal)}
                </span>
                {!isBreakdownOpen && (ruleDiscount > 0 || couponDiscount > 0) && (
                  <span style={{ display: 'block', fontSize: '0.72rem', fontWeight: 700, color: '#16a34a' }}>
                    Saved ₹{formatMoney(ruleDiscount + couponDiscount + prepaidDiscount)}
                  </span>
                )}
              </div>
            </div>

            {/* Proceed to Checkout CTA */}
            <button
              onClick={onCheckout}
              style={{
                width: '100%',
                background: '#111827',
                color: '#ffffff',
                borderRadius: '12px',
                padding: '0.95rem 1.25rem',
                border: 'none',
                cursor: 'pointer',
                fontSize: '0.95rem',
                fontWeight: 700,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
                boxShadow: '0 4px 14px rgba(0, 0, 0, 0.12)',
                transition: 'background 0.15s ease',
                marginTop: '0.35rem',
              }}
              onMouseEnter={(e) => (e.currentTarget.style.background = '#1f2937')}
              onMouseLeave={(e) => (e.currentTarget.style.background = '#111827')}
            >
              <span>Proceed to Checkout</span>
              <span style={{ fontSize: '1.1rem' }}>→</span>
            </button>

            {/* View Full Cart Link */}
            <div style={{ textAlign: 'center', marginTop: '0.1rem' }}>
              <button
                onClick={() => setIsCartOpen(false)}
                style={{
                  border: 'none',
                  background: 'transparent',
                  color: '#6b7280',
                  fontSize: '0.78rem',
                  fontWeight: 500,
                  cursor: 'pointer',
                  padding: '2px 6px',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.color = '#111827')}
                onMouseLeave={(e) => (e.currentTarget.style.color = '#6b7280')}
              >
                View Full Cart
              </button>
            </div>
          </div>
        )}
      </div>

      <style>{`
        @keyframes slideInRight {
          from { transform: translateX(100%); }
          to { transform: translateX(0); }
        }
        .cart-scroll-area::-webkit-scrollbar {
          width: 5px;
        }
        .cart-scroll-area::-webkit-scrollbar-track {
          background: transparent;
        }
        .cart-scroll-area::-webkit-scrollbar-thumb {
          background: #e2e8f0;
          border-radius: 9999px;
        }
        .cart-scroll-area::-webkit-scrollbar-thumb:hover {
          background: #cbd5e1;
        }
      `}</style>
    </div>
  );
}
