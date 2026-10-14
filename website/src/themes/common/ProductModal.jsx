import React, { useState } from 'react';
import { X, ShoppingBag, ShieldCheck, Truck, RotateCcw, Check, Plus, Minus, Package } from 'lucide-react';

export default function ProductModal({
  isOpen,
  onClose,
  product,
  primaryColor = '#10b981',
  onAddToCart,
}) {
  const [qty, setQty] = useState(1);
  const [added, setAdded] = useState(false);

  React.useEffect(() => {
    if (isOpen) {
      setQty(1);
      setAdded(false);
    }
  }, [isOpen, product]);

  if (!isOpen || !product) return null;

  const isOOS = product.stock <= 0;
  const sellingPrice = Number(product.selling_price || product.price || 0);
  const mrp = Number(product.mrp || 0);
  const discount = product.discount || (mrp > sellingPrice ? Math.round(((mrp - sellingPrice) / mrp) * 100) : 0);

  const handleAdd = () => {
    if (isOOS) return;
    if (onAddToCart) {
      onAddToCart(product, qty);
    }
    setAdded(true);
    setTimeout(() => {
      setAdded(false);
      onClose();
    }, 800);
  };

  return (
    <div style={{ position: 'fixed', inset: 0, zIndex: 9990, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem' }}>
      {/* Backdrop */}
      <div
        onClick={onClose}
        style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.7)',
          backdropFilter: 'blur(6px)',
        }}
      />

      {/* Modal Dialog */}
      <div
        style={{
          position: 'relative',
          width: '100%',
          maxWidth: '760px',
          maxHeight: '90vh',
          background: '#ffffff',
          borderRadius: '24px',
          boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
          overflowY: 'auto',
          zIndex: 9991,
          fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
        }}
      >
        {/* Close Button */}
        <button
          onClick={onClose}
          style={{
            position: 'absolute',
            top: '16px',
            right: '16px',
            width: '34px',
            height: '34px',
            borderRadius: '50%',
            border: 'none',
            background: 'rgba(255, 255, 255, 0.9)',
            boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
            color: '#475569',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            zIndex: 10,
          }}
        >
          <X size={18} />
        </button>

        {/* Product Image Column */}
        <div style={{ background: '#f8fafc', padding: '2rem', display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '340px', position: 'relative' }}>
          {product.image ? (
            <img
              src={product.image}
              alt={product.product_name}
              style={{ maxWidth: '100%', maxHeight: '320px', objectFit: 'contain', borderRadius: '16px' }}
              onError={(e) => { e.currentTarget.style.display = 'none'; e.currentTarget.nextElementSibling.style.display = 'flex'; }}
            />
          ) : null}
          <div
            style={{
              display: product.image ? 'none' : 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#94a3b8',
              gap: '0.75rem',
            }}
          >
            <Package size={64} style={{ color: primaryColor, opacity: 0.6 }} />
            <span style={{ fontSize: '0.85rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              {product.category || 'Product'}
            </span>
          </div>

          {discount > 0 && (
            <div style={{ position: 'absolute', top: '16px', left: '16px', background: '#ef4444', color: '#fff', padding: '0.3rem 0.7rem', borderRadius: '8px', fontWeight: 800, fontSize: '0.8rem' }}>
              {discount}% OFF
            </div>
          )}
        </div>

        {/* Product Details Column */}
        <div style={{ padding: '2rem', display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
              <span style={{ fontSize: '0.72rem', fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.05em', color: primaryColor, background: `${primaryColor}15`, padding: '0.2rem 0.55rem', borderRadius: '6px' }}>
                {product.brand || 'Original Brand'}
              </span>
              <span style={{ fontSize: '0.72rem', fontWeight: 700, color: '#64748b' }}>
                {product.category}
              </span>
            </div>

            <h2 style={{ fontSize: '1.25rem', fontWeight: 900, color: '#0f172a', lineHeight: 1.3, marginBottom: '0.75rem' }}>
              {product.product_name}
            </h2>

            {/* Price Row */}
            <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.75rem', marginBottom: '1.25rem' }}>
              <span style={{ fontSize: '1.5rem', fontWeight: 900, color: '#0f172a' }}>
                ₹{sellingPrice.toLocaleString('en-IN')}
              </span>
              {mrp > sellingPrice && (
                <span style={{ fontSize: '1rem', color: '#94a3b8', textDecoration: 'line-through' }}>
                  ₹{mrp.toLocaleString('en-IN')}
                </span>
              )}
              <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#16a34a' }}>
                Inclusive of GST
              </span>
            </div>

            {/* Stock Availability */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.25rem' }}>
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: isOOS ? '#ef4444' : '#16a34a' }} />
              <span style={{ fontSize: '0.8rem', fontWeight: 700, color: isOOS ? '#ef4444' : '#16a34a' }}>
                {isOOS ? 'Currently Out of Stock' : (product.stock < 10 ? `Only ${product.stock} units left in stock!` : 'In Stock & Ready to Dispatch')}
              </span>
            </div>

            {/* Description */}
            <div style={{ fontSize: '0.85rem', color: '#475569', lineHeight: 1.6, marginBottom: '1.25rem', background: '#f8fafc', padding: '0.85rem 1rem', borderRadius: '12px' }}>
              {product.description || 'Authentic product sourced directly from official warehouse inventory with quality assurance.'}
            </div>
          </div>

          {/* Action Row */}
          <div>
            {!isOOS && (
              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1rem' }}>
                <span style={{ fontSize: '0.82rem', fontWeight: 700, color: '#334155' }}>Quantity:</span>
                <div style={{ display: 'flex', alignItems: 'center', border: '1.5px solid #e2e8f0', borderRadius: '10px', overflow: 'hidden' }}>
                  <button
                    type="button"
                    onClick={() => setQty(Math.max(1, qty - 1))}
                    style={{ width: '36px', height: '36px', border: 'none', background: '#f8fafc', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#475569' }}
                  >
                    <Minus size={14} />
                  </button>
                  <span style={{ width: '40px', textAlign: 'center', fontWeight: 800, fontSize: '0.9rem', color: '#0f172a' }}>
                    {qty}
                  </span>
                  <button
                    type="button"
                    onClick={() => setQty(Math.min(product.stock || 99, qty + 1))}
                    style={{ width: '36px', height: '36px', border: 'none', background: '#f8fafc', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#475569' }}
                  >
                    <Plus size={14} />
                  </button>
                </div>
              </div>
            )}

            <button
              onClick={handleAdd}
              disabled={isOOS || added}
              style={{
                width: '100%',
                padding: '0.85rem 1.5rem',
                borderRadius: '14px',
                border: 'none',
                background: isOOS ? '#f1f5f9' : (added ? '#16a34a' : primaryColor),
                color: isOOS ? '#94a3b8' : '#ffffff',
                fontSize: '0.95rem',
                fontWeight: 800,
                cursor: isOOS ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
                transition: 'all 0.2s',
                boxShadow: isOOS ? 'none' : `0 4px 14px ${primaryColor}40`,
              }}
            >
              {added ? (
                <>
                  <Check size={18} /> Added to Cart!
                </>
              ) : (
                <>
                  <ShoppingBag size={18} /> {isOOS ? 'Out of Stock' : `Add ${qty > 1 ? qty + ' Items' : ''} to Cart`}
                </>
              )}
            </button>

            {/* Trust Perks */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.5rem', marginTop: '1.25rem', paddingTop: '1rem', borderTop: '1px solid #f1f5f9' }}>
              <div style={{ textAlign: 'center', fontSize: '0.7rem', color: '#64748b' }}>
                <Truck size={14} style={{ color: primaryColor, margin: '0 auto 0.2rem auto' }} />
                <span>Fast Delivery</span>
              </div>
              <div style={{ textAlign: 'center', fontSize: '0.7rem', color: '#64748b' }}>
                <ShieldCheck size={14} style={{ color: primaryColor, margin: '0 auto 0.2rem auto' }} />
                <span>100% Genuine</span>
              </div>
              <div style={{ textAlign: 'center', fontSize: '0.7rem', color: '#64748b' }}>
                <RotateCcw size={14} style={{ color: primaryColor, margin: '0 auto 0.2rem auto' }} />
                <span>7-Day Return</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
