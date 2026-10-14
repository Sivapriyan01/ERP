/**
 * App.jsx — TrexoERP Website Frontend Entry Point
 *
 * Acts as a ThemeRouter: fetches active settings from the ERP API,
 * dynamically loads the correct theme component, and renders it.
 *
 * No logic lives here — cart, OTP, checkout are all inside each theme
 * via shared hooks from themes/common/.
 */
import React, { Suspense } from 'react';
import { useSettings } from './themes/common/useSettings';
import { getTheme } from './themes/index.js';
import { CustomerAuthProvider } from './themes/common/useCustomerAuth';
import CustomerAuthModal from './themes/common/CustomerAuthModal';
import AccountModal from './themes/common/AccountModal';

// ── Loading screen shown while settings & theme chunk load ────────
function SplashLoader() {
  return (
    <div style={{
      minHeight: '100vh', display: 'flex', flexDirection: 'column',
      alignItems: 'center', justifyContent: 'center',
      background: '#f8fafc', fontFamily: 'system-ui, sans-serif',
    }}>
      <div style={{
        width: '56px', height: '56px', borderRadius: '16px',
        background: '#10b981', display: 'flex', alignItems: 'center', justifyContent: 'center',
        marginBottom: '1.25rem', animation: 'pulse 1.2s ease-in-out infinite',
      }}>
        <svg width="28" height="28" fill="none" stroke="#fff" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5}
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
      </div>
      <p style={{ color: '#64748b', fontWeight: 600, fontSize: '0.9rem' }}>Loading storefront…</p>
      <style>{`@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.7;transform:scale(.95)}}`}</style>
    </div>
  );
}

// ── Theme Error Boundary ──────────────────────────────────────────
class ThemeErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }
  static getDerivedStateFromError(error) { return { hasError: true, error }; }
  render() {
    if (this.state.hasError) {
      return (
        <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: 'system-ui', flexDirection: 'column', gap: '1rem', background: '#fff8f8' }}>
          <div style={{ fontSize: '2rem' }}>⚠️</div>
          <h2 style={{ color: '#dc2626', fontWeight: 700 }}>Theme failed to load</h2>
          <p style={{ color: '#64748b', fontSize: '0.88rem' }}>{String(this.state.error?.message || 'Unknown error')}</p>
          <button onClick={() => window.location.reload()} style={{ padding: '0.6rem 1.5rem', background: '#10b981', color: '#fff', border: 'none', borderRadius: '10px', cursor: 'pointer', fontWeight: 700 }}>
            Reload Page
          </button>
        </div>
      );
    }
    return this.props.children;
  }
}

// ── Main App ──────────────────────────────────────────────────────
export default function App() {
  const {
    settings,
    loading,
    isEditor,
    updateField,
    reorderSections,
    toggleSectionVisibility,
  } = useSettings();

  // Document title synchronization
  React.useEffect(() => {
    if (!settings) return;
    document.title = settings.website_company_name || settings.store_name || 'Store';
  }, [settings]);

  if (loading) return <SplashLoader />;

  // Storefront theme component
  const themeEntry = getTheme();
  const ThemeComponent = themeEntry.component;
  const primaryColor = settings.website_primary_color || settings.primary_color || '#217a6e';

  return (
    <ThemeErrorBoundary>
      <CustomerAuthProvider>
        <Suspense fallback={<SplashLoader />}>
          <ThemeComponent
            settings={settings}
            updateField={updateField}
            reorderSections={reorderSections}
            toggleSectionVisibility={toggleSectionVisibility}
            isEditor={isEditor}
          />
        </Suspense>
        <CustomerAuthModal primaryColor={primaryColor} />
        <AccountModal primaryColor={primaryColor} settings={settings} />
      </CustomerAuthProvider>
    </ThemeErrorBoundary>
  );
}
