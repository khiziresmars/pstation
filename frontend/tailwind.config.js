/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Telegram theme colors (CSS variables)
        'tg-bg': 'var(--tg-theme-bg-color, #ffffff)',
        'tg-text': 'var(--tg-theme-text-color, #000000)',
        'tg-hint': 'var(--tg-theme-hint-color, #999999)',
        'tg-link': 'var(--tg-theme-link-color, #2481cc)',
        'tg-button': 'var(--tg-theme-button-color, #2481cc)',
        'tg-button-text': 'var(--tg-theme-button-text-color, #ffffff)',
        'tg-secondary-bg': 'var(--tg-theme-secondary-bg-color, #f0f0f0)',
        'tg-header-bg': 'var(--tg-theme-header-bg-color, #2481cc)',
        'tg-accent-text': 'var(--tg-theme-accent-text-color, #2481cc)',
        'tg-section-bg': 'var(--tg-theme-section-bg-color, #ffffff)',
        'tg-section-header': 'var(--tg-theme-section-header-text-color, #6d7885)',
        'tg-subtitle': 'var(--tg-theme-subtitle-text-color, #6d7885)',
        'tg-destructive': 'var(--tg-theme-destructive-text-color, #ff3b30)',

        // Custom brand colors
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        ocean: {
          50: '#f0fdff',
          100: '#ccfbfe',
          200: '#99f6fc',
          300: '#5eedf8',
          400: '#2dd4ed',
          500: '#06b6d4',
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63',
        },
      },
      fontFamily: {
        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [],
};
