import React, { useState, useRef, useEffect } from 'react';
import { User, LayoutGrid, ShoppingBag, LogOut, ChevronDown, Wallet, Coins } from 'lucide-react';
import { useCustomerAuth } from './useCustomerAuth';

export default function AccountDropdown({ primaryColor = '#217a6e', dark = false }) {
  const { customer, isLoggedIn, openLogin, openAccount, logout, walletBalance = 0, rewardPoints = 0 } = useCustomerAuth();
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    const handleOutsideClick = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => document.removeEventListener('mousedown', handleOutsideClick);
  }, []);

  if (!isLoggedIn) {
    return (
      <button
        onClick={openLogin}
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: '0.45rem',
          background: dark ? 'rgba(255,255,255,0.1)' : 'transparent',
          border: dark ? '1px solid rgba(255,255,255,0.2)' : '1.5px solid #cbd5e1',
          color: dark ? '#ffffff' : '#334155',
          borderRadius: '12px',
          padding: '0.55rem 0.95rem',
          fontWeight: 700,
          fontSize: '0.82rem',
          cursor: 'pointer',
          transition: 'all 0.15s ease',
        }}
        onMouseEnter={(e) => {
          if (!dark) {
            e.currentTarget.style.borderColor = primaryColor;
            e.currentTarget.style.color = primaryColor;
          }
        }}
        onMouseLeave={(e) => {
          if (!dark) {
            e.currentTarget.style.borderColor = '#cbd5e1';
            e.currentTarget.style.color = '#334155';
          }
        }}
      >
        <User size={16} />
        <span>Login</span>
      </button>
    );
  }

  const displayName = customer.name || customer.email?.split('@')[0] || 'My Account';

  return (
    <div ref={dropdownRef} style={{ position: 'relative' }}>
      {/* Trigger Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: '0.5rem',
          background: dark ? 'rgba(255,255,255,0.1)' : '#f8fafc',
          border: dark ? '1px solid rgba(255,255,255,0.2)' : '1.5px solid #e2e8f0',
          color: dark ? '#ffffff' : '#0f172a',
          borderRadius: '12px',
          padding: '0.45rem 0.85rem',
          fontWeight: 800,
          fontSize: '0.82rem',
          cursor: 'pointer',
          boxShadow: '0 2px 6px rgba(0,0,0,0.02)',
        }}
      >
        <div
          style={{
            width: '24px',
            height: '24px',
            borderRadius: '50%',
            background: primaryColor,
            color: '#fff',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '0.72rem',
            fontWeight: 900,
          }}
        >
          {displayName.charAt(0).toUpperCase()}
        </div>
        <span style={{ maxWidth: '100px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
          {displayName}
        </span>
        {walletBalance > 0 && (
          <span
            style={{
              background: '#dcfce7',
              color: '#15803d',
              fontSize: '0.7rem',
              fontWeight: 800,
              padding: '1px 6px',
              borderRadius: '99px',
            }}
          >
            ₹{walletBalance.toFixed(0)}
          </span>
        )}
        <ChevronDown size={14} color={dark ? '#cbd5e1' : '#64748b'} />
      </button>

      {/* Dropdown Menu */}
      {isOpen && (
        <div
          style={{
            position: 'absolute',
            top: 'calc(100% + 8px)',
            right: 0,
            width: '220px',
            background: '#ffffff',
            borderRadius: '16px',
            boxShadow: '0 12px 30px rgba(0, 0, 0, 0.14), 0 2px 6px rgba(0,0,0,0.06)',
            padding: '0.75rem',
            zIndex: 1000,
            border: '1px solid #f1f5f9',
            fontFamily: "'Roboto', system-ui, sans-serif",
            animation: 'fadeIn 0.15s ease-out',
          }}
        >
          {/* Dynamic Wallet & Rewards Quick Summary Box */}
          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0.65rem 0.75rem',
              marginBottom: '0.5rem',
            }}
          >
            <div
              onClick={() => {
                setIsOpen(false);
                openAccount('wallet');
              }}
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                fontSize: '0.78rem',
                cursor: 'pointer',
                marginBottom: '4px',
              }}
            >
              <span style={{ color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <Wallet size={13} color="#16a34a" /> Wallet
              </span>
              <span style={{ fontWeight: 800, color: '#16a34a' }}>₹{Number(walletBalance).toFixed(2)}</span>
            </div>
            <div
              onClick={() => {
                setIsOpen(false);
                openAccount('rewards');
              }}
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                fontSize: '0.78rem',
                cursor: 'pointer',
              }}
            >
              <span style={{ color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <Coins size={13} color="#d97706" /> Coins
              </span>
              <span style={{ fontWeight: 800, color: '#d97706' }}>⭐ {Number(rewardPoints).toFixed(0)}</span>
            </div>
          </div>

          {/* Your Profile */}
          <button
            onClick={() => {
              setIsOpen(false);
              openAccount('profile');
            }}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: '0.65rem',
              padding: '0.55rem 0.75rem',
              border: 'none',
              background: 'transparent',
              color: '#1e293b',
              fontWeight: 700,
              fontSize: '0.85rem',
              borderRadius: '10px',
              cursor: 'pointer',
              textAlign: 'left',
              transition: 'background 0.15s ease',
            }}
            onMouseEnter={(e) => (e.currentTarget.style.background = '#f8fafc')}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <LayoutGrid size={15} color="#475569" />
            <span>Your Profile</span>
          </button>

          {/* Order History */}
          <button
            onClick={() => {
              setIsOpen(false);
              openAccount('orders');
            }}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: '0.65rem',
              padding: '0.55rem 0.75rem',
              border: 'none',
              background: 'transparent',
              color: '#1e293b',
              fontWeight: 700,
              fontSize: '0.85rem',
              borderRadius: '10px',
              cursor: 'pointer',
              textAlign: 'left',
              transition: 'background 0.15s ease',
            }}
            onMouseEnter={(e) => (e.currentTarget.style.background = '#f8fafc')}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <ShoppingBag size={15} color="#475569" />
            <span>Order History</span>
          </button>

          {/* Wallet & Passbook */}
          <button
            onClick={() => {
              setIsOpen(false);
              openAccount('wallet');
            }}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: '0.65rem',
              padding: '0.55rem 0.75rem',
              border: 'none',
              background: 'transparent',
              color: '#1e293b',
              fontWeight: 700,
              fontSize: '0.85rem',
              borderRadius: '10px',
              cursor: 'pointer',
              textAlign: 'left',
              transition: 'background 0.15s ease',
            }}
            onMouseEnter={(e) => (e.currentTarget.style.background = '#f8fafc')}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <Wallet size={15} color="#16a34a" />
            <span>Wallet &amp; Gift Cards</span>
          </button>

          {/* Rewards Club */}
          <button
            onClick={() => {
              setIsOpen(false);
              openAccount('rewards');
            }}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: '0.65rem',
              padding: '0.55rem 0.75rem',
              border: 'none',
              background: 'transparent',
              color: '#1e293b',
              fontWeight: 700,
              fontSize: '0.85rem',
              borderRadius: '10px',
              cursor: 'pointer',
              textAlign: 'left',
              transition: 'background 0.15s ease',
            }}
            onMouseEnter={(e) => (e.currentTarget.style.background = '#f8fafc')}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <Coins size={15} color="#d97706" />
            <span>Rewards Club</span>
          </button>

          <div style={{ height: '1px', background: '#f1f5f9', margin: '0.4rem 0' }} />

          {/* Logout Pill Button (Exact match from Dr. Sheth's screenshot) */}
          <button
            onClick={() => {
              setIsOpen(false);
              logout();
            }}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '0.45rem',
              padding: '0.55rem',
              border: '1.5px solid #e2e8f0',
              background: '#ffffff',
              color: '#0f172a',
              fontWeight: 800,
              fontSize: '0.82rem',
              borderRadius: '99px',
              cursor: 'pointer',
              marginTop: '0.2rem',
              transition: 'all 0.15s ease',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.borderColor = '#dc2626';
              e.currentTarget.style.color = '#dc2626';
              e.currentTarget.style.background = '#fef2f2';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.borderColor = '#e2e8f0';
              e.currentTarget.style.color = '#0f172a';
              e.currentTarget.style.background = '#ffffff';
            }}
          >
            <span>Logout</span>
            <LogOut size={14} />
          </button>
        </div>
      )}
    </div>
  );
}
