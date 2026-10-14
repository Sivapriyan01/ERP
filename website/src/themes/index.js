import { lazy } from 'react';

// Single clean minimalist storefront theme
const ModernMinimalTheme = lazy(() => import('./Theme01_ModernMinimal/index.jsx'));

export const THEMES_REGISTRY = [
  {
    id: 'modern_minimal',
    name: 'Modern Minimal',
    description: 'Clean white editorial design with large typography and minimal decoration.',
    status: 'active',
    category: 'Minimalist',
    component: ModernMinimalTheme,
  },
];

/**
 * Get default storefront theme
 */
export function getTheme() {
  return THEMES_REGISTRY[0];
}
