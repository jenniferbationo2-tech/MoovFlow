import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Inter', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Inter', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ── Palette officielle Moov Africa Burkina (style corporate) ──
                moov: {
                    // Bleu Moov (sidebar, KPIs, headings)
                    'blue':       '#1B4A8B',
                    'blue-dark':  '#143769',
                    'blue-light': '#2563AC',
                    'blue-50':    '#EEF4FB',

                    // Orange Moov (logo, accents)
                    'orange':     '#FF8000',
                    'orange-dark':'#E67300',
                    'orange-50':  '#FFF4E6',

                    // Noir corporate (boutons, titres)
                    'noir':       '#0F172A',
                    'noir-soft':  '#1E293B',
                },
                // Gris professionnel
                'page-bg':    '#F4F6FA',
                'card':       '#FFFFFF',
                'text-main':  '#0F172A',
                'text-sub':   '#64748B',
                'text-muted': '#94A3B8',
                'border-soft':'#E2E8F0',
            },
            boxShadow: {
                'card':       '0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.04)',
                'card-hover': '0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04)',
                'sidebar':    '4px 0 16px -8px rgba(15, 23, 42, 0.15)',
            },
        },
    },

    plugins: [forms],
};