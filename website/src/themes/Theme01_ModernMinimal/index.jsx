/**
 * Theme 01 — Modern Minimal
 * Clean white editorial design, large typography, airy layout.
 * Supports: Drag-and-drop section reordering & Direct inline text editing!
 */
import React, { useState, useEffect } from 'react';
import {
  ShoppingBag, Search, Truck, ShieldCheck, ArrowRight, X,
  Phone, Mail, Tag, ChevronRight, Package, Zap
} from 'lucide-react';
import { erpApi } from '../../services/erpApi';
import { useCart } from '../common/useCart';
import CartDrawer from '../common/CartDrawer';
import CheckoutModal from '../common/CheckoutModal';
import CouponModal from '../common/CouponModal';
import OrderTracker from '../common/OrderTracker';
import AccountDropdown from '../common/AccountDropdown';
import ProductModal from '../common/ProductModal';
import { EditableText } from '../common/EditableText';
import { SectionWrapper } from '../common/SectionWrapper';

export default function Theme01_ModernMinimal({
  settings,
  updateField,
  reorderSections,
  toggleSectionVisibility,
  isEditor = false,
}) {
  const primary = settings?.primary_color || '#10b981';
  const accent  = settings?.accent_color  || '#059669';
  const textColor = settings?.text_color || '#0f172a';
  const font    = settings?.font_family   || 'Inter';

  const [products, setProducts]               = useState([]);
  const [categories, setCategories]           = useState(['All']);
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [searchQuery, setSearchQuery]         = useState('');
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [isCheckoutOpen, setIsCheckoutOpen]   = useState(false);
  const [isTrackerOpen, setIsTrackerOpen]     = useState(false);
  const [loading, setLoading]                 = useState(true);

  const cart = useCart(settings);

  useEffect(() => {
    (async () => {
      const list = await erpApi.getProducts();
      setProducts(list);
      const cats = ['All', ...new Set(list.map((p) => p.category).filter(Boolean))];
      setCategories(cats);
      setLoading(false);
    })();
  }, []);

  const filtered = products.filter((p) => {
    const matchCat = selectedCategory === 'All' || p.category === selectedCategory;
    const q = searchQuery.toLowerCase();
    const matchQ = !q || p.product_name?.toLowerCase().includes(q) || p.brand?.toLowerCase().includes(q);
    return matchCat && matchQ;
  });

  const storeName = settings?.store_name || 'Square Store';
  const heroTitle = settings?.hero_title || 'Discover. Shop. Enjoy.';
  const heroSub   = settings?.hero_subtitle || 'Curated products, instant delivery.';
  const heroBadge = settings?.hero_badge || 'Curated Readers Choice';
  const heroCta   = settings?.hero_cta_text || 'Explore Catalog';
  const announcement = settings?.announcement || '';

  // Sections management
  const defaultOrder = ['announcement', 'header', 'hero', 'categories', 'products', 'features', 'footer'];
  const sectionsOrder = Array.isArray(settings?.sections_order)
    ? settings.sections_order
    : defaultOrder;
  const sectionsVis = settings?.sections_visibility || {};

  const sectionTitles = {
    announcement: '📢 Announcement Bar',
    header: '🧭 Navigation Header',
    hero: '🦸 Hero Showcase',
    categories: '🏷️ Category Pills',
    products: '🛍️ Products Grid',
    features: '🛡️ Trust & Features',
    footer: '🦶 Store Footer',
  };

  const moveSection = (secId, direction) => {
    const idx = sectionsOrder.indexOf(secId);
    if (idx < 0) return;
    const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
    if (targetIdx < 0 || targetIdx >= sectionsOrder.length) return;
    const next = [...sectionsOrder];
    const temp = next[idx];
    next[idx] = next[targetIdx];
    next[targetIdx] = temp;
    if (reorderSections) reorderSections(next);
  };

  const handleDrop = (e, targetSecId) => {
    e.preventDefault();
    const sourceSecId = e.dataTransfer.getData('text/plain');
    if (!sourceSecId || sourceSecId === targetSecId) return;
    const sourceIdx = sectionsOrder.indexOf(sourceSecId);
    const targetIdx = sectionsOrder.indexOf(targetSecId);
    if (sourceIdx < 0 || targetIdx < 0) return;
    const next = [...sectionsOrder];
    const [moved] = next.splice(sourceIdx, 1);
    next.splice(targetIdx, 0, moved);
    if (reorderSections) reorderSections(next);
  };

  // Section contents definitions
  const renderSectionContent = (secId) => {
    switch (secId) {
      case 'announcement':
        return (
          <div style={{ background: primary, color: '#fff', textAlign: 'center', padding: '0.6rem 1rem', fontSize: '0.82rem', fontWeight: 600, letterSpacing: '0.02em' }}>
            <EditableText
              fieldKey="announcement"
              value={announcement}
              onChange={updateField}
              placeholder="Enter announcement text…"
            />
          </div>
        );

      case 'header':
        return (
          <header style={{
            background: '#fff', borderBottom: '1px solid #f1f5f9',
            position: 'sticky', top: 0, zIndex: 100,
            boxShadow: '0 1px 20px rgba(0,0,0,0.06)',
          }}>
            <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '0 1.5rem', display: 'flex', alignItems: 'center', gap: '1.5rem', height: '68px' }}>
              {/* Logo */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem', textDecoration: 'none', flex: '0 0 auto' }}>
                <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: primary, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <ShoppingBag size={18} color="#fff" />
                </div>
                <EditableText
                  fieldKey="store_name"
                  value={storeName}
                  onChange={updateField}
                  as="span"
                  style={{ fontWeight: 800, fontSize: '1.1rem', color: '#0f172a', letterSpacing: '-0.02em' }}
                />
              </div>

              {/* Search bar */}
              <div style={{ flex: 1, position: 'relative', maxWidth: '480px' }}>
                <Search size={16} style={{ position: 'absolute', left: '1rem', top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }} />
                <input
                  type="text"
                  placeholder="Search products…"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  style={{
                    width: '100%', paddingLeft: '2.5rem', paddingRight: '1rem', height: '42px',
                    border: '1.5px solid #e2e8f0', borderRadius: '12px', outline: 'none',
                    fontSize: '0.88rem', background: '#f8fafc', fontFamily: 'inherit',
                    boxSizing: 'border-box', transition: 'border-color 0.2s',
                  }}
                  onFocus={(e) => (e.target.style.borderColor = primary)}
                  onBlur={(e) => (e.target.style.borderColor = '#e2e8f0')}
                />
              </div>

              {/* Nav buttons */}
              <nav style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', marginLeft: 'auto' }}>
                <button onClick={() => setIsTrackerOpen(true)} style={navBtnStyle}>Track Order</button>
                <AccountDropdown primaryColor={primary} />
                <button
                  onClick={() => cart.setIsCartOpen(true)}
                  style={{
                    position: 'relative', background: primary, color: '#fff',
                    border: 'none', borderRadius: '12px', padding: '0.5rem 1rem',
                    fontWeight: 700, fontSize: '0.85rem', cursor: 'pointer',
                    display: 'flex', alignItems: 'center', gap: '0.4rem',
                  }}
                >
                  <ShoppingBag size={16} />
                  Cart
                  {cart.cartQty > 0 && (
                    <span style={{
                      position: 'absolute', top: '-6px', right: '-6px',
                      background: '#ef4444', color: '#fff', borderRadius: '99px',
                      width: '18px', height: '18px', fontSize: '0.65rem', fontWeight: 800,
                      display: 'flex', alignItems: 'center', justifyContent: 'center',
                    }}>
                      {cart.cartQty}
                    </span>
                  )}
                </button>
              </nav>
            </div>
          </header>
        );

      case 'hero':
        return (
          <section style={{
            background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
            padding: '4.5rem 1.5rem 3.5rem',
            borderBottom: '1px solid #e2e8f0',
          }}>
            <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '4rem', alignItems: 'center' }}>
              <div>
                <div style={{
                  display: 'inline-flex', alignItems: 'center', gap: '0.4rem',
                  background: `${primary}15`, color: primary,
                  padding: '0.35rem 0.875rem', borderRadius: '99px',
                  fontSize: '0.78rem', fontWeight: 700, marginBottom: '1.25rem',
                  border: `1px solid ${primary}30`,
                }}>
                  <Zap size={12} />
                  <EditableText
                    fieldKey="hero_badge"
                    value={heroBadge}
                    onChange={updateField}
                  />
                </div>

                <EditableText
                  fieldKey="hero_title"
                  value={heroTitle}
                  onChange={updateField}
                  as="h1"
                  style={{
                    fontSize: 'clamp(2rem, 4vw, 3rem)', fontWeight: 900,
                    lineHeight: 1.15, letterSpacing: '-0.03em',
                    color: textColor, marginBottom: '1rem',
                  }}
                />

                <EditableText
                  fieldKey="hero_subtitle"
                  value={heroSub}
                  onChange={updateField}
                  as="p"
                  multiline={true}
                  style={{ fontSize: '1.05rem', color: '#64748b', lineHeight: 1.7, marginBottom: '2rem', maxWidth: '440px' }}
                />

                <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap' }}>
                  <button
                    onClick={() => document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' })}
                    style={{
                      background: primary, color: '#fff', border: 'none',
                      padding: '0.875rem 2rem', borderRadius: '14px',
                      fontWeight: 700, fontSize: '0.95rem', cursor: 'pointer',
                      display: 'flex', alignItems: 'center', gap: '0.5rem',
                      boxShadow: `0 8px 24px ${primary}40`,
                    }}
                  >
                    <EditableText
                      fieldKey="hero_cta_text"
                      value={heroCta}
                      onChange={updateField}
                    />
                    <ArrowRight size={16} />
                  </button>
                  <button
                    onClick={() => setIsTrackerOpen(true)}
                    style={{
                      background: '#fff', color: '#475569', border: '2px solid #e2e8f0',
                      padding: '0.875rem 1.5rem', borderRadius: '14px',
                      fontWeight: 600, fontSize: '0.95rem', cursor: 'pointer',
                      display: 'flex', alignItems: 'center', gap: '0.5rem',
                    }}
                  >
                    <Package size={16} /> Track Order
                  </button>
                </div>
              </div>

              {/* Hero visual grid */}
              <div style={{ position: 'relative' }}>
                <div style={{
                  width: '100%', aspectRatio: '4/3',
                  background: `linear-gradient(135deg, ${primary}20 0%, ${accent}30 100%)`,
                  borderRadius: '28px', display: 'flex', alignItems: 'center', justifyContent: 'center',
                  border: `1px solid ${primary}20`, overflow: 'hidden',
                }}>
                  {products.slice(0, 4).length > 0 ? (
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', padding: '1rem', width: '100%' }}>
                      {products.slice(0, 4).map((p) => (
                        <div key={p.id} style={{
                          background: '#fff', borderRadius: '16px', overflow: 'hidden',
                          boxShadow: '0 4px 16px rgba(0,0,0,0.08)',
                        }}>
                          {p.image ? (
                            <img
                              src={p.image}
                              alt={p.product_name}
                              style={{ width: '100%', height: '80px', objectFit: 'cover' }}
                            />
                          ) : (
                            <div style={{ width: '100%', height: '80px', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#f1f5f9', color: primary }}>
                              <Package size={24} />
                            </div>
                          )}
                          <div style={{ padding: '0.5rem 0.6rem' }}>
                            <div style={{ fontSize: '0.65rem', fontWeight: 700, color: '#0f172a', lineHeight: 1.3 }}>{p.product_name?.slice(0, 20)}</div>
                            <div style={{ fontSize: '0.7rem', color: primary, fontWeight: 800 }}>₹{p.selling_price?.toLocaleString('en-IN')}</div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div style={{ textAlign: 'center', color: primary, opacity: 0.5 }}>
                      <ShoppingBag size={64} />
                      <div style={{ marginTop: '1rem', fontWeight: 600 }}>Products loading…</div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </section>
        );

      case 'categories':
        return (
          <section style={{ padding: '2rem 1.5rem 0', maxWidth: '1200px', margin: '0 auto' }}>
            <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', paddingBottom: '1.5rem', borderBottom: '1px solid #f1f5f9' }}>
              {categories.map((cat) => (
                <button
                  key={cat}
                  onClick={() => setSelectedCategory(cat)}
                  style={{
                    padding: '0.5rem 1.25rem', borderRadius: '99px',
                    border: `2px solid ${selectedCategory === cat ? primary : '#e2e8f0'}`,
                    background: selectedCategory === cat ? primary : '#fff',
                    color: selectedCategory === cat ? '#fff' : '#475569',
                    fontWeight: 600, fontSize: '0.85rem', cursor: 'pointer',
                    transition: 'all 0.2s',
                  }}
                >
                  {cat}
                </button>
              ))}
            </div>
          </section>
        );

      case 'products':
        return (
          <section id="products-section" style={{ maxWidth: '1200px', margin: '0 auto', padding: '2.5rem 1.5rem 4rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: '1.5rem' }}>
              <div>
                {selectedCategory === 'All' ? (
                  <EditableText
                    fieldKey="products_title"
                    value={settings?.products_title || 'All Products'}
                    onChange={updateField}
                    as="h2"
                    style={{ fontSize: '1.4rem', fontWeight: 800, color: textColor }}
                  />
                ) : (
                  <h2 style={{ fontSize: '1.4rem', fontWeight: 800, color: textColor }}>
                    {selectedCategory}
                  </h2>
                )}
                <EditableText
                  fieldKey="products_subtitle"
                  value={
                    settings?.products_subtitle !== undefined && settings?.products_subtitle !== ''
                      ? settings.products_subtitle
                      : `Showing ${filtered.length} products`
                  }
                  onChange={updateField}
                  as="p"
                  style={{ fontSize: '0.85rem', color: '#64748b' }}
                />
              </div>
            </div>

            {loading ? (
              <div style={{ textAlign: 'center', padding: '4rem', color: '#94a3b8' }}>
                <div style={{ width: '40px', height: '40px', border: `3px solid ${primary}`, borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 1s linear infinite', margin: '0 auto 1rem' }} />
                <p>Loading products from warehouse…</p>
              </div>
            ) : filtered.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '4rem', color: '#94a3b8' }}>
                <Package size={48} style={{ margin: '0 auto 1rem', opacity: 0.4 }} />
                <p style={{ fontWeight: 600 }}>No products found</p>
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: '1.5rem' }}>
                {filtered.map((product) => (
                  <ProductCard01
                    key={product.id}
                    product={product}
                    primary={primary}
                    onView={() => setSelectedProduct(product)}
                    onAdd={() => cart.addToCart(product)}
                  />
                ))}
              </div>
            )}
          </section>
        );

      case 'features':
        return (
          <section style={{ background: '#f8fafc', borderTop: '1px solid #e2e8f0', borderBottom: '1px solid #e2e8f0', padding: '2.5rem 1.5rem' }}>
            <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '2rem' }}>
              <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                <div style={{ width: '48px', height: '48px', borderRadius: '14px', background: `${primary}15`, color: primary, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Truck size={24} />
                </div>
                <div>
                  <EditableText
                    fieldKey="feature_1_title"
                    value={settings?.feature_1_title || 'Fast Dispatch'}
                    onChange={updateField}
                    as="h4"
                    style={{ margin: '0 0 0.25rem', fontSize: '0.95rem', fontWeight: 800, color: textColor }}
                  />
                  <EditableText
                    fieldKey="feature_1_desc"
                    value={settings?.feature_1_desc || `Free shipping on orders over ₹${settings?.free_delivery_min || 999}`}
                    onChange={updateField}
                    as="p"
                    style={{ margin: 0, fontSize: '0.8rem', color: '#64748b' }}
                  />
                </div>
              </div>
              <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                <div style={{ width: '48px', height: '48px', borderRadius: '14px', background: `${primary}15`, color: primary, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <ShieldCheck size={24} />
                </div>
                <div>
                  <EditableText
                    fieldKey="feature_2_title"
                    value={settings?.feature_2_title || '100% Authentic'}
                    onChange={updateField}
                    as="h4"
                    style={{ margin: '0 0 0.25rem', fontSize: '0.95rem', fontWeight: 800, color: textColor }}
                  />
                  <EditableText
                    fieldKey="feature_2_desc"
                    value={settings?.feature_2_desc || 'Directly fulfilled from verified ERP inventory'}
                    onChange={updateField}
                    as="p"
                    style={{ margin: 0, fontSize: '0.8rem', color: '#64748b' }}
                  />
                </div>
              </div>
              <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                <div style={{ width: '48px', height: '48px', borderRadius: '14px', background: `${primary}15`, color: primary, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Zap size={24} />
                </div>
                <div>
                  <EditableText
                    fieldKey="feature_3_title"
                    value={settings?.feature_3_title || 'Instant GST Invoicing'}
                    onChange={updateField}
                    as="h4"
                    style={{ margin: '0 0 0.25rem', fontSize: '0.95rem', fontWeight: 800, color: textColor }}
                  />
                  <EditableText
                    fieldKey="feature_3_desc"
                    value={settings?.feature_3_desc || 'Automated compliant billing with live tracking'}
                    onChange={updateField}
                    as="p"
                    style={{ margin: 0, fontSize: '0.8rem', color: '#64748b' }}
                  />
                </div>
              </div>
            </div>
          </section>
        );

      case 'footer':
        return (
          <footer style={{ background: '#0f172a', color: '#94a3b8', padding: '3rem 1.5rem 2rem' }}>
            <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '2rem', marginBottom: '2rem' }}>
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem', marginBottom: '0.75rem' }}>
                    <div style={{ width: '32px', height: '32px', borderRadius: '8px', background: primary, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <ShoppingBag size={16} color="#fff" />
                    </div>
                    <EditableText
                      fieldKey="store_name"
                      value={storeName}
                      onChange={updateField}
                      as="span"
                      style={{ fontWeight: 800, color: '#f1f5f9', fontSize: '1rem' }}
                    />
                  </div>
                  <EditableText
                    fieldKey="footer_text"
                    value={settings?.footer_text || `${settings?.business_type || 'Premium store'} powered by TrexoERP — real-time inventory sync.`}
                    onChange={updateField}
                    as="p"
                    multiline={true}
                    style={{ fontSize: '0.82rem', lineHeight: 1.7, maxWidth: '220px' }}
                  />
                </div>
                <div>
                  <EditableText
                    fieldKey="footer_support_title"
                    value={settings?.footer_support_title || 'Customer Support'}
                    onChange={updateField}
                    as="div"
                    style={{ fontWeight: 700, color: '#f1f5f9', marginBottom: '0.75rem', fontSize: '0.88rem' }}
                  />
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.82rem', marginBottom: '0.4rem' }}>
                    <Phone size={13} />
                    <EditableText fieldKey="support_phone" value={settings?.support_phone || '+91 98765 43210'} onChange={updateField} />
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.82rem', marginBottom: '0.4rem' }}>
                    <Mail size={13} />
                    <EditableText fieldKey="support_email" value={settings?.support_email || 'support@store.com'} onChange={updateField} />
                  </div>
                </div>
                <div>
                  <div style={{ fontWeight: 700, color: '#f1f5f9', marginBottom: '0.75rem', fontSize: '0.88rem' }}>Quick Links</div>
                  {[
                    { label: 'Track Order', action: () => setIsTrackerOpen(true) },
                  ].map((link) => (
                    <div
                      key={link.label}
                      onClick={link.action}
                      style={{ fontSize: '0.82rem', marginBottom: '0.35rem', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '0.3rem', transition: 'color 0.2s' }}
                    >
                      <ChevronRight size={12} style={{ color: primary }} /> {link.label}
                    </div>
                  ))}
                </div>
                <div>
                  <div style={{ fontWeight: 700, color: '#f1f5f9', marginBottom: '0.75rem', fontSize: '0.88rem' }}>Payment Modes</div>
                  {['UPI / GPay / PhonePe', 'Credit / Debit Cards', 'Net Banking', 'Cash on Delivery'].map((m) => (
                    <div key={m} style={{ fontSize: '0.8rem', marginBottom: '0.3rem' }}>✓ {m}</div>
                  ))}
                </div>
              </div>
              <div style={{ borderTop: '1px solid #1e293b', paddingTop: '1.25rem', display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.5rem', fontSize: '0.78rem' }}>
                <span>© {new Date().getFullYear()} {storeName}. All rights reserved.</span>
                <span style={{ display: 'flex', gap: '1rem' }}>
                  <span style={{ color: primary, display: 'flex', alignItems: 'center', gap: '0.3rem' }}><ShieldCheck size={12} /> SSL Secured</span>
                  <span style={{ color: '#64748b' }}>Powered by TrexoERP</span>
                </span>
              </div>
            </div>
          </footer>
        );

      default:
        return null;
    }
  };

  return (
    <div style={{ fontFamily: `'${font}', system-ui, sans-serif`, background: '#fff', minHeight: '100vh', color: textColor }}>
      {/* Render all sections in custom drag-and-drop order */}
      {sectionsOrder.map((secId, idx) => (
        <SectionWrapper
          key={secId}
          sectionId={secId}
          title={sectionTitles[secId] || secId}
          index={idx}
          totalSections={sectionsOrder.length}
          visible={sectionsVis[secId] !== false}
          onMoveUp={() => moveSection(secId, 'up')}
          onMoveDown={() => moveSection(secId, 'down')}
          onDrop={handleDrop}
          onToggleVisibility={() => toggleSectionVisibility && toggleSectionVisibility(secId)}
        >
          {renderSectionContent(secId)}
        </SectionWrapper>
      ))}

      {/* Shared Drawers & Modals */}
      <CartDrawer
        {...cart}
        primaryColor={primary}
        onOpenCouponModal={() => cart.setIsCouponModalOpen(true)}
        onRemoveCoupon={cart.removeCoupon}
        onCheckout={() => { cart.setIsCartOpen(false); setIsCheckoutOpen(true); }}
      />
      <CouponModal
        isOpen={cart.isCouponModalOpen}
        onClose={() => cart.setIsCouponModalOpen(false)}
        subtotal={cart.subtotal}
        appliedCoupon={cart.appliedCoupon}
        onApplyCoupon={cart.applyCoupon}
        onRemoveCoupon={cart.removeCoupon}
        primaryColor={primary}
      />
      <CheckoutModal
        isOpen={isCheckoutOpen}
        onClose={() => setIsCheckoutOpen(false)}
        cart={cart.cart}
        subtotal={cart.subtotal}
        gstAmount={cart.gstAmount}
        shippingCharge={cart.shippingCharge}
        grandTotal={cart.grandTotal}
        appliedCoupon={cart.appliedCoupon}
        couponDiscount={cart.couponDiscount}
        ruleDiscount={cart.ruleDiscount}
        appliedRules={cart.appliedRules}
        prepaidDiscount={cart.prepaidDiscount}
        freeGift={cart.freeGift}
        settings={settings}
        primaryColor={primary}
        onOrderPlaced={() => { cart.clearCart(); }}
      />
      <OrderTracker isOpen={isTrackerOpen} onClose={() => setIsTrackerOpen(false)} primaryColor={primary} />

      {/* Product Detail Modal */}
      <ProductModal
        isOpen={!!selectedProduct}
        onClose={() => setSelectedProduct(null)}
        product={selectedProduct}
        primaryColor={primary}
        onAddToCart={(item, qty) => cart.addToCart(item, qty)}
      />

      <style>{`
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.5; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        * { box-sizing: border-box; }
      `}</style>
    </div>
  );
}

// ── Product Card Sub-component ──────────────────────────────────
function ProductCard01({ product, primary, onView, onAdd }) {
  const [hovered, setHovered] = useState(false);
  const isOOS = product.stock <= 0;

  return (
    <div
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      style={{
        background: '#fff', borderRadius: '20px', overflow: 'hidden',
        border: '1px solid #f1f5f9',
        boxShadow: hovered ? '0 16px 40px rgba(0,0,0,0.12)' : '0 2px 12px rgba(0,0,0,0.06)',
        transition: 'all 0.25s ease', transform: hovered ? 'translateY(-4px)' : 'none',
        cursor: 'pointer',
      }}
    >
      <div style={{ position: 'relative', overflow: 'hidden', height: '200px', background: '#f8fafc' }}>
        {product.image ? (
          <img
            src={product.image}
            alt={product.product_name}
            onClick={onView}
            style={{ width: '100%', height: '100%', objectFit: 'cover', transition: 'transform 0.4s ease', transform: hovered ? 'scale(1.05)' : 'scale(1)' }}
            onError={(e) => { e.currentTarget.style.display = 'none'; const ph = e.currentTarget.parentElement?.querySelector('.product-fallback-ph'); if (ph) ph.style.display = 'flex'; }}
          />
        ) : null}
        <div
          className="product-fallback-ph"
          onClick={onView}
          style={{
            display: product.image ? 'none' : 'flex',
            width: '100%', height: '100%', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            background: 'linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%)',
            color: '#94a3b8', gap: '0.4rem', cursor: 'pointer',
          }}
        >
          <Package size={38} style={{ opacity: 0.6, color: primary }} />
          <span style={{ fontSize: '0.7rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.05em', color: '#64748b' }}>
            {product.category || 'Product'}
          </span>
        </div>
        {product.discount > 0 && (
          <div style={{
            position: 'absolute', top: '10px', left: '10px',
            background: '#ef4444', color: '#fff', borderRadius: '8px',
            padding: '0.2rem 0.55rem', fontSize: '0.72rem', fontWeight: 800,
          }}>
            -{product.discount}%
          </div>
        )}
        {isOOS && (
          <div style={{
            position: 'absolute', inset: 0, background: 'rgba(255,255,255,0.8)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
          }}>
            <span style={{ fontWeight: 800, color: '#ef4444', fontSize: '0.85rem' }}>Out of Stock</span>
          </div>
        )}
      </div>

      <div style={{ padding: '1rem' }}>
        <div style={{ fontSize: '0.72rem', color: '#94a3b8', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '0.25rem' }}>
          {product.brand} · {product.category}
        </div>
        <div style={{ fontWeight: 700, color: '#0f172a', fontSize: '0.92rem', lineHeight: 1.4, marginBottom: '0.75rem', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
          {product.product_name}
        </div>
        <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.5rem', marginBottom: '0.875rem' }}>
          <span style={{ fontWeight: 800, fontSize: '1.1rem', color: '#0f172a' }}>₹{product.selling_price?.toLocaleString('en-IN')}</span>
          {product.mrp > product.selling_price && (
            <span style={{ fontSize: '0.8rem', color: '#94a3b8', textDecoration: 'line-through' }}>₹{product.mrp?.toLocaleString('en-IN')}</span>
          )}
        </div>
        <button
          onClick={isOOS ? undefined : onAdd}
          disabled={isOOS}
          style={{
            width: '100%', padding: '0.65rem', border: 'none',
            borderRadius: '12px',
            background: isOOS ? '#f1f5f9' : primary,
            color: isOOS ? '#94a3b8' : '#fff',
            fontWeight: 700, fontSize: '0.85rem', cursor: isOOS ? 'not-allowed' : 'pointer',
            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.4rem',
            transition: 'all 0.2s',
          }}
        >
          <ShoppingBag size={14} />
          {isOOS ? 'Out of Stock' : 'Add to Cart'}
        </button>
      </div>
    </div>
  );
}

const navBtnStyle = {
  background: 'none', border: 'none', color: '#475569', cursor: 'pointer',
  fontWeight: 600, fontSize: '0.85rem', padding: '0.5rem 0.75rem', borderRadius: '10px',
};
