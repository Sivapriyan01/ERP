import { useState, useEffect, useMemo, useCallback } from 'react';
import { erpApi } from '../../services/erpApi';

const CART_STORAGE_KEY = 'trexo_website_cart';
const COUPON_STORAGE_KEY = 'trexo_website_coupon';

export function useCart(settings = {}) {
  const [cart, setCart] = useState(() => {
    try {
      const saved = localStorage.getItem(CART_STORAGE_KEY);
      return saved ? JSON.parse(saved) : [];
    } catch {
      return [];
    }
  });

  const [appliedCoupon, setAppliedCoupon] = useState(() => {
    try {
      const saved = localStorage.getItem(COUPON_STORAGE_KEY);
      return saved ? JSON.parse(saved) : null;
    } catch {
      return null;
    }
  });

  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isCouponModalOpen, setIsCouponModalOpen] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('ONLINE'); // 'ONLINE' or 'COD'
  const [couponError, setCouponError] = useState('');

  // Sync to local storage
  useEffect(() => {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    } catch (e) {
      console.warn('Failed to save cart to localStorage:', e);
    }
  }, [cart]);

  useEffect(() => {
    try {
      if (appliedCoupon) {
        localStorage.setItem(COUPON_STORAGE_KEY, JSON.stringify(appliedCoupon));
      } else {
        localStorage.removeItem(COUPON_STORAGE_KEY);
      }
    } catch { }
  }, [appliedCoupon]);

  const addToCart = (product, qty = 1) => {
    if (!product || product.stock <= 0) return;

    setCart((prev) => {
      const existing = prev.find((item) => item.id === product.id);
      if (existing) {
        const newQty = Math.min(existing.quantity + qty, product.stock);
        return prev.map((item) =>
          item.id === product.id ? { ...item, quantity: newQty } : item
        );
      }
      return [
        ...prev,
        {
          id: product.id,
          product_name: product.product_name,
          selling_price: Number(product.selling_price || 0),
          mrp: Number(product.mrp || product.selling_price || 0),
          discount: product.discount || 0,
          image: product.image,
          stock: product.stock,
          tax: Number(product.tax || 0),
          quantity: Math.min(qty, product.stock),
        },
      ];
    });
    setIsCartOpen(true);
  };

  const removeFromCart = (productId) => {
    setCart((prev) => prev.filter((item) => item.id !== productId));
  };

  const updateQuantity = (productId, newQty) => {
    if (newQty <= 0) {
      removeFromCart(productId);
      return;
    }
    setCart((prev) =>
      prev.map((item) => {
        if (item.id === productId) {
          const clampedQty = Math.min(newQty, item.stock || 999);
          return { ...item, quantity: clampedQty };
        }
        return item;
      })
    );
  };

  const clearCart = () => {
    setCart([]);
    setAppliedCoupon(null);
    try {
      localStorage.removeItem(CART_STORAGE_KEY);
      localStorage.removeItem(COUPON_STORAGE_KEY);
    } catch { }
  };

  // Calculations
  const cartQty = useMemo(
    () => cart.reduce((sum, item) => sum + (item.quantity || 0), 0),
    [cart]
  );
  const totalItems = cartQty;

  const subtotal = useMemo(
    () => cart.reduce((sum, item) => sum + (item.selling_price || 0) * (item.quantity || 0), 0),
    [cart]
  );

  const [availableCoupons, setAvailableCoupons] = useState([]);

  // Fetch all active coupons from backend
  const refreshCoupons = useCallback(() => {
    erpApi.getCoupons().then((list) => {
      if (Array.isArray(list)) {
        setAvailableCoupons(list);
      }
    }).catch((err) => {
      console.warn('Failed to load available coupons:', err);
    });
  }, []);

  useEffect(() => {
    refreshCoupons();
  }, [refreshCoupons]);

  // Dynamic Coupon Validation on subtotal change
  useEffect(() => {
    if (appliedCoupon && subtotal > 0) {
      erpApi.validateCoupon(appliedCoupon.code, subtotal, paymentMethod).then((res) => {
        if (res.success && res.coupon) {
          setAppliedCoupon((prev) => ({
            ...prev,
            discount_amount: res.coupon.discount_amount,
            free_gift: res.coupon.free_gift,
          }));
          setCouponError('');
        } else {
          // Keep but warn if below minimum
          setCouponError(res.message);
        }
      });
    } else if (subtotal === 0) {
      setAppliedCoupon(null);
      setCouponError('');
    }
  }, [subtotal, paymentMethod]);

  const applyCoupon = useCallback(async (code) => {
    setCouponError('');
    const res = await erpApi.validateCoupon(code, subtotal, paymentMethod);
    if (res.success && res.coupon) {
      setAppliedCoupon(res.coupon);
      return { success: true, coupon: res.coupon, message: res.message };
    } else {
      setCouponError(res.message);
      return { success: false, message: res.message };
    }
  }, [subtotal, paymentMethod]);

  const removeCoupon = useCallback(() => {
    setAppliedCoupon(null);
    setCouponError('');
  }, []);

  // Helper to calculate coupon discount based on bill rate (subtotal)
  const calculateCouponSavings = useCallback((coupon, amount) => {
    if (!coupon || !amount || amount < (coupon.min_order_value || 0)) return 0;
    let disc = 0;
    if (coupon.discount_type === 'percent') {
      disc = (amount * Number(coupon.discount_value || 0)) / 100;
      if (coupon.max_discount && disc > Number(coupon.max_discount)) {
        disc = Number(coupon.max_discount);
      }
    } else {
      disc = Math.min(amount, Number(coupon.discount_value || 0));
    }
    return Math.round(disc * 100) / 100;
  }, []);

  // Suggested best coupon for the customer's current bill rate
  const suggestedCoupon = useMemo(() => {
    if (!availableCoupons.length || subtotal <= 0) return null;

    // Filter eligible coupons based on current bill rate
    const eligible = availableCoupons.filter(
      (c) => subtotal >= (c.min_order_value || 0)
    );

    if (!eligible.length) return null;

    // Sort by savings descending, then free gift, then discount_value
    const sorted = [...eligible].sort((a, b) => {
      const savingsA = calculateCouponSavings(a, subtotal);
      const savingsB = calculateCouponSavings(b, subtotal);
      if (savingsB !== savingsA) return savingsB - savingsA;
      if (b.free_gift_name && !a.free_gift_name) return 1;
      if (a.free_gift_name && !b.free_gift_name) return -1;
      return (b.discount_value || 0) - (a.discount_value || 0);
    });

    const best = sorted[0];
    const savings = calculateCouponSavings(best, subtotal);

    return {
      ...best,
      savings,
      isApplied: appliedCoupon?.code === best.code,
    };
  }, [availableCoupons, subtotal, calculateCouponSavings, appliedCoupon]);

  // Next-tier coupon unlocked with a higher bill rate
  const nextTierCoupon = useMemo(() => {
    if (!availableCoupons.length) return null;

    // Locked coupons (need a higher bill rate)
    const locked = availableCoupons.filter(
      (c) => (c.min_order_value || 0) > subtotal
    );

    if (!locked.length) return null;

    // Sort by smallest amount needed to unlock
    const sorted = [...locked].sort((a, b) => {
      const neededA = (a.min_order_value || 0) - subtotal;
      const neededB = (b.min_order_value || 0) - subtotal;
      if (neededA !== neededB) return neededA - neededB;
      const potA = calculateCouponSavings(a, a.min_order_value);
      const potB = calculateCouponSavings(b, b.min_order_value);
      return potB - potA;
    });

    const closest = sorted[0];
    const amountNeeded = Math.max(0, (closest.min_order_value || 0) - subtotal);
    const potentialSavings = calculateCouponSavings(closest, closest.min_order_value);

    return {
      ...closest,
      amountNeeded,
      potentialSavings,
    };
  }, [availableCoupons, subtotal, calculateCouponSavings]);

  // Better coupon available if current coupon saves less than what bill qualifies for
  const betterCoupon = useMemo(() => {
    if (!appliedCoupon || !suggestedCoupon) return null;
    if (suggestedCoupon.code === appliedCoupon.code) return null;
    const currentDiscount = Number(appliedCoupon.discount_amount || 0);
    if (suggestedCoupon.savings > currentDiscount) {
      return {
        ...suggestedCoupon,
        extraSavings: Math.round((suggestedCoupon.savings - currentDiscount) * 100) / 100,
      };
    }
    return null;
  }, [appliedCoupon, suggestedCoupon]);

  // Dynamic promotional settings from ERP
  const prepaidPercent = Number(settings?.prepaid_discount_percent ?? settings?.website_prepaid_discount_percent ?? 5);
  const freeGiftThreshold = Number(settings?.free_gift_threshold ?? settings?.website_free_gift_threshold ?? 0);
  const freeGiftCustomName = (settings?.free_gift_name || settings?.website_free_gift_name || '').trim();
  const freeDeliveryMin = Number(settings?.free_shipping_threshold ?? settings?.free_delivery_min ?? settings?.website_free_shipping_threshold ?? 999);
  const standardShipping = Number(settings?.shipping_fee ?? settings?.website_shipping_fee ?? 99);

  // ── Server-Side Promotion Engine Evaluation (SRS Section 7 & 9) ───
  const [serverPromotionData, setServerPromotionData] = useState(null);

  useEffect(() => {
    if (cart.length === 0) {
      setServerPromotionData(null);
      return;
    }

    const payloadItems = cart.map((item) => ({
      id: item.id,
      product_name: item.product_name,
      quantity: item.quantity,
      price: item.selling_price || item.mrp || 0,
    }));

    erpApi
      .validateCartPricing({
        items: payloadItems,
        coupon_code: appliedCoupon?.code || null,
        shipping_charge: subtotal >= freeDeliveryMin ? 0 : standardShipping,
        tax_mode: settings?.gst_calc_type || 'exclusive',
        tax_rate: 18,
      })
      .then((res) => {
        if (res?.success && res.data) {
          setServerPromotionData(res.data);
        }
      })
      .catch((err) => {
        console.warn('Cart pricing evaluation error:', err);
      });
  }, [cart, appliedCoupon, subtotal, freeDeliveryMin, standardShipping, settings?.gst_calc_type]);

  // Promotional rules from server (excluding duplicate coupon entry)
  const promotionalRules = useMemo(() => {
    return (serverPromotionData?.applied_rules || []).filter(
      (r) => r.type !== 'coupon' && !r.coupon_code && Number(r.discount_amount) > 0
    );
  }, [serverPromotionData]);

  const ruleDiscount = useMemo(() => {
    return promotionalRules.reduce((sum, r) => sum + Number(r.discount_amount || 0), 0);
  }, [promotionalRules]);

  // Synchronized coupon discount amount
  const couponDiscount = useMemo(() => {
    if (!appliedCoupon) return 0;
    if (serverPromotionData?.coupon_discount !== undefined && serverPromotionData.coupon_discount !== null && Number(serverPromotionData.coupon_discount) > 0) {
      return Number(serverPromotionData.coupon_discount);
    }
    return Number(appliedCoupon.discount_amount || 0);
  }, [appliedCoupon, serverPromotionData]);

  // Prevent double-discounting if user applied a PREPAID coupon
  const isPrepaidCouponApplied = useMemo(() => {
    return Boolean(
      appliedCoupon &&
      (appliedCoupon.code?.toUpperCase().includes('PREPAID') ||
       appliedCoupon.name?.toLowerCase().includes('prepaid'))
    );
  }, [appliedCoupon]);

  // Combined promotional + coupon discount
  const effectiveDiscount = useMemo(() => {
    return Math.min(subtotal, Math.round((ruleDiscount + (appliedCoupon ? couponDiscount : 0)) * 100) / 100);
  }, [subtotal, ruleDiscount, appliedCoupon, couponDiscount]);

  // Prepaid discount (UPI/Online, not double-counted with prepaid coupon)
  const prepaidDiscount = useMemo(() => {
    if (paymentMethod !== 'ONLINE' || subtotal === 0 || isPrepaidCouponApplied) return 0;
    const baseAfterDiscount = Math.max(0, subtotal - effectiveDiscount);
    const disc = baseAfterDiscount * (prepaidPercent / 100);
    return Math.round(Math.min(disc, 200) * 100) / 100;
  }, [paymentMethod, subtotal, effectiveDiscount, isPrepaidCouponApplied, prepaidPercent]);

  // Free gift unlocked via Server Promotion Engine, Coupon, OR Storefront settings
  const freeGift = useMemo(() => {
    if (serverPromotionData?.allocated_freebies?.length > 0) {
      return serverPromotionData.allocated_freebies[0].product_name;
    }
    if (appliedCoupon?.free_gift) {
      return appliedCoupon.free_gift;
    }
    if (freeGiftThreshold > 0 && freeGiftCustomName && subtotal >= freeGiftThreshold) {
      return freeGiftCustomName;
    }
    return null;
  }, [serverPromotionData, appliedCoupon, subtotal, freeGiftThreshold, freeGiftCustomName]);

  const freeGiftImage = settings?.free_gift_image || settings?.website_free_gift_image || appliedCoupon?.free_gift_image || '/gift-icon.png';

  const gstCalcType = settings?.gst_calc_type || 'inclusive';

  const gstAmount = useMemo(() => {
    const discountedRatio = subtotal > 0 ? Math.max(0, subtotal - effectiveDiscount - prepaidDiscount) / subtotal : 1;
    return cart.reduce((sum, item) => {
      const taxRate = Number(item.tax || 0);
      if (taxRate <= 0) return sum;
      const itemTotal = (item.selling_price || 0) * (item.quantity || 0) * discountedRatio;
      if (gstCalcType === 'inclusive') {
        return sum + (itemTotal * taxRate) / (100 + taxRate);
      } else {
        return sum + (itemTotal * taxRate) / 100;
      }
    }, 0);
  }, [cart, gstCalcType, subtotal, effectiveDiscount, prepaidDiscount]);

  const shippingCharge = useMemo(() => {
    if (subtotal === 0) return 0;
    return subtotal >= freeDeliveryMin ? 0 : standardShipping;
  }, [subtotal, freeDeliveryMin, standardShipping]);

  const grandTotal = useMemo(() => {
    const netBase = Math.max(0, subtotal - effectiveDiscount - prepaidDiscount);
    if (gstCalcType === 'inclusive') {
      return Math.round((netBase + shippingCharge) * 100) / 100;
    } else {
      return Math.round((netBase + gstAmount + shippingCharge) * 100) / 100;
    }
  }, [subtotal, effectiveDiscount, prepaidDiscount, gstAmount, shippingCharge, gstCalcType]);

  return {
    cart,
    isCartOpen,
    setIsCartOpen,
    isCouponModalOpen,
    setIsCouponModalOpen,
    appliedCoupon,
    applyCoupon,
    removeCoupon,
    couponDiscount,
    prepaidDiscount,
    isPrepaidCouponApplied,
    couponError,
    freeGift,
    freeGiftImage,
    paymentMethod,
    setPaymentMethod,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    cartQty,
    totalItems,
    subtotal,
    gstAmount,
    gstCalcType,
    shippingCharge,
    grandTotal,
    freeDeliveryMin,
    availableCoupons,
    suggestedCoupon,
    nextTierCoupon,
    betterCoupon,
    calculateCouponSavings,
    refreshCoupons,
    freebieProgress: serverPromotionData?.freebie_progress,
    appliedRules: promotionalRules,
    allocatedFreebies: serverPromotionData?.allocated_freebies || [],
    ruleDiscount,
  };
}
