/** @type {import('tailwindcss').Config} */
export default {
  prefix: 'tw-',
  darkMode: ['selector', '[data-theme="dark"]'],
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        // Deep modern slate for dark mode
        'glass-dark': 'rgba(15, 23, 42, 0.75)',
        'glass-dark-border': 'rgba(255, 255, 255, 0.1)',
        // Crisp light mode glass
        'glass-light': 'rgba(255, 255, 255, 0.8)',
        'glass-light-border': 'rgba(0, 0, 0, 0.05)',
      },
      animation: {
        'fade-in-up': 'fadeInUp 0.3s ease-out',
        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
      keyframes: {
        fadeInUp: {
          '0%': { opacity: 0, transform: 'translateY(10px)' },
          '100%': { opacity: 1, transform: 'translateY(0)' },
        }
      }
    },
  },
  plugins: [],
}
