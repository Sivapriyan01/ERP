import { useState, useEffect, useCallback } from 'react';
import { erpApi } from '../../services/erpApi';

const DEFAULT_SECTIONS_ORDER = [
  'announcement',
  'header',
  'hero',
  'categories',
  'products',
  'features',
  'footer',
];

const DEFAULT_SECTIONS_VISIBILITY = {
  announcement: true,
  header: true,
  hero: true,
  categories: true,
  products: true,
  features: true,
  footer: true,
};

const DEFAULT_SETTINGS = {
  store_name: 'Square Store',
  primary_color: '#10b981',
  accent_color: '#059669',
  text_color: '#0f172a',
  secondary_color: '#f1f5f9',
  bg_color: '#ffffff',
  font_family: 'Inter',
  border_radius: '16px',
  hero_title: 'Seamless Shopping. Instant ERP Fulfillment.',
  hero_subtitle: 'Browse authentic products with live warehouse stock sync.',
  hero_badge: 'Official ERP Connected Store',
  hero_cta_text: 'Explore Catalog',
  products_title: 'All Products',
  products_subtitle: '',
  announcement: '🎉 FREE Delivery on orders above ₹999!',
  support_phone: '',
  support_email: '',
  free_delivery_min: 999,
  shipping_fee: 99,
  header_style: 'regular',
  layout_style: 'split',
  card_style: 'modern',
  shadow_style: 'small',
  footer_text: '',
  social_instagram: '',
  social_facebook: '',
  social_whatsapp: '',
  otp_required: true,
  sections_order: DEFAULT_SECTIONS_ORDER,
  sections_visibility: DEFAULT_SECTIONS_VISIBILITY,
};

function safeParse(val, fallback) {
  if (!val) return fallback;
  if (typeof val === 'object') return val;
  try {
    return JSON.parse(val);
  } catch {
    return fallback;
  }
}

export function useSettings() {
  const [settings, setSettings] = useState(DEFAULT_SETTINGS);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const isEditor =
    typeof window !== 'undefined' &&
    (window.self !== window.top ||
      new URLSearchParams(window.location.search).get('edit') === '1');

  const fetchSettings = useCallback(async () => {
    try {
      setLoading(true);
      const data = await erpApi.getSettings();

      if (data && typeof data === 'object') {
        setSettings((prev) => ({
          ...prev,
          ...data,
          free_delivery_min: Number(data.free_delivery_min ?? prev.free_delivery_min),
          shipping_fee: Number(data.shipping_fee ?? prev.shipping_fee),
          otp_required: data.otp_required !== false && data.otp_required !== '0',
          sections_order: safeParse(data.sections_order, DEFAULT_SECTIONS_ORDER),
          sections_visibility: safeParse(data.sections_visibility, DEFAULT_SECTIONS_VISIBILITY),
        }));
      }
    } catch (err) {
      console.warn('Failed to load settings, using defaults:', err);
      setError(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchSettings();

    // Listen for postMessage from ERP Configurator
    const handleMessage = (event) => {
      if (!event.data || typeof event.data !== 'object') return;

      if (event.data.type === 'TREXO_THEME_UPDATE') {
        const payload = event.data.settings || {};
        setSettings((prev) => ({
          ...prev,
          ...payload,
          sections_order: payload.sections_order ? safeParse(payload.sections_order, prev.sections_order) : prev.sections_order,
          sections_visibility: payload.sections_visibility ? safeParse(payload.sections_visibility, prev.sections_visibility) : prev.sections_visibility,
        }));
      } else if (event.data.type === 'TREXO_SECTIONS_ORDER_UPDATE') {
        if (Array.isArray(event.data.order)) {
          setSettings((prev) => ({ ...prev, sections_order: event.data.order }));
        }
      } else if (event.data.type === 'TREXO_SECTIONS_VISIBILITY_UPDATE') {
        if (event.data.visibility && typeof event.data.visibility === 'object') {
          setSettings((prev) => ({ ...prev, sections_visibility: event.data.visibility }));
        }
      }
    };

    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  }, [fetchSettings]);

  // Direct text edit updater
  const updateField = useCallback((fieldKey, newValue) => {
    setSettings((prev) => ({
      ...prev,
      [fieldKey]: newValue,
    }));
  }, []);

  // Section reorder updater
  const reorderSections = useCallback((newOrder) => {
    setSettings((prev) => ({
      ...prev,
      sections_order: newOrder,
    }));
    try {
      if (window.parent && window.parent !== window) {
        window.parent.postMessage(
          {
            type: 'TREXO_SECTIONS_ORDER_UPDATE',
            order: newOrder,
          },
          '*'
        );
      }
    } catch (e) {}
  }, []);

  // Section visibility toggle
  const toggleSectionVisibility = useCallback((sectionId) => {
    setSettings((prev) => {
      const currentVis = prev.sections_visibility || DEFAULT_SECTIONS_VISIBILITY;
      const updated = { ...currentVis, [sectionId]: !currentVis[sectionId] };
      try {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage(
            {
              type: 'TREXO_SECTIONS_VISIBILITY_UPDATE',
              visibility: updated,
            },
            '*'
          );
        }
      } catch (e) {}
      return { ...prev, sections_visibility: updated };
    });
  }, []);

  return {
    settings,
    loading,
    error,
    isEditor,
    updateField,
    reorderSections,
    toggleSectionVisibility,
    refreshSettings: fetchSettings,
  };
}
