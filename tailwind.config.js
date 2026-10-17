import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                serif: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                obsidian: {
                    DEFAULT: '#000000',
                    950: '#060709',
                    900: '#0B0C10',
                    800: '#14161D',
                    700: '#1F222E',
                    600: '#2F3446',
                },
                ink: {
                    DEFAULT: '#141210',
                    950: '#0A0908',
                    900: '#141210',
                    800: '#1C1917',
                    700: '#292524',
                    600: '#44403C',
                },
                'warm-gray': {
                    DEFAULT: '#78716C',
                    50: '#FAF9F6',
                    100: '#F5F1E8',
                    200: '#E7E5E4',
                    300: '#D6D3D1',
                    400: '#A8A29E',
                    500: '#78716C',
                    600: '#57534E',
                    700: '#44403C',
                    800: '#292524',
                    900: '#1C1917',
                },
                beige: {
                    DEFAULT: '#F5F5DC',
                    50: '#FDFDFC',
                    100: '#FAF8F0',
                    200: '#F5F5DC',
                    300: '#E8E8C8',
                    400: '#D8D8A8',
                },
                rose: {
                    taupe: '#C08497',
                    DEFAULT: '#C08497',
                    light: '#D3A3B2',
                    dark: '#A36579',
                    glow: 'rgba(192, 132, 151, 0.35)',
                },
                gold: {
                    antique: '#D4AF37',
                    DEFAULT: '#D4AF37',
                    light: '#E5C768',
                    dark: '#A8871F',
                    muted: '#C5A032',
                    glow: 'rgba(212, 175, 55, 0.35)',
                },
                sage: {
                    forest: '#3A5A40',
                    DEFAULT: '#3A5A40',
                    light: '#588157',
                    dark: '#2A402E',
                    glow: 'rgba(58, 90, 64, 0.35)',
                },
                crimson: {
                    DEFAULT: '#B91C1C',
                    light: '#DC2626',
                    dark: '#991B1B',
                    glow: 'rgba(185, 28, 28, 0.35)',
                },
            },
            boxShadow: {
                'glass-light': '0 8px 30px 0 rgba(0, 0, 0, 0.06), inset 0 1px 0 0 rgba(255, 255, 255, 0.9)',
                'glass-card-light': '0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.05)',
                'glass-dark': '0 8px 32px 0 rgba(0, 0, 0, 0.5), inset 0 1px 0 0 rgba(255, 255, 255, 0.1)',
                'glass-glow-rose': '0 0 25px -5px rgba(192, 132, 151, 0.4), 0 8px 24px 0 rgba(0, 0, 0, 0.15)',
                'glass-glow-gold': '0 0 25px -5px rgba(212, 175, 55, 0.4), 0 8px 24px 0 rgba(0, 0, 0, 0.15)',
                'glass-glow-sage': '0 0 25px -5px rgba(58, 90, 64, 0.4), 0 8px 24px 0 rgba(0, 0, 0, 0.15)',
                'glass-glow-crimson': '0 0 25px -5px rgba(204, 0, 0, 0.4), 0 8px 24px 0 rgba(0, 0, 0, 0.15)',
                'glass-card': '0 12px 40px 0 rgba(0, 0, 0, 0.1), inset 0 1px 1px 0 rgba(255, 255, 255, 0.8)',
                'admin-card': '0 4px 20px -2px rgba(20, 18, 16, 0.06), 0 2px 6px -1px rgba(20, 18, 16, 0.04)',
                'admin-card-hover': '0 16px 32px -6px rgba(20, 18, 16, 0.1), 0 8px 16px -4px rgba(20, 18, 16, 0.06)',
                'admin-hero': '0 20px 40px -10px rgba(20, 18, 16, 0.45), 0 0 0 1px rgba(212, 175, 55, 0.35)',
            },
            backdropBlur: {
                xs: '2px',
                '2xl': '24px',
                '3xl': '32px',
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'float': 'float 6s ease-in-out infinite',
                'shimmer': 'shimmer 2s linear infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
        },
    },

    plugins: [forms],
};
