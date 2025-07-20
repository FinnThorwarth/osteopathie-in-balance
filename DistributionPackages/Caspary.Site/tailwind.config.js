/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./Resources/Private/Fusion/**/*.fusion",
    "./Resources/Private/Templates/**/*.html",
    "./Resources/Private/JavaScript/**/*.{js,vue}",
    "./Resources/Private/Styles/**/*.css"
  ],
  theme: {
    extend: {
      colors: {
        // Caspary brand colors extracted from design
        primary: {
          50: '#faf9f9',
          100: '#f5f3f3',
          200: '#ebe7e7',
          300: '#dfd8d8',
          400: '#cfc5c5',
          500: '#b8abab', // Main text gray
          600: '#9e8f8f',
          700: '#847676',
          800: '#6b5f5f',
          900: '#574e4e',
        },
        secondary: {
          50: '#e8f5f4',
          100: '#d1ebe9',
          200: '#a3d7d3',
          300: '#75c3bd',
          400: '#47afa7',
          500: '#3a9991', // Teal from header
          600: '#2e7a74',
          700: '#235b57',
          800: '#173c3a',
          900: '#0c1d1d',
        },
        accent: {
          // Additional accent colors
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#b9e5fe',
          300: '#7cd1fd',
          400: '#36bafc',
          500: '#0da2e7', // Blue accents
          600: '#0182c5',
          700: '#02669f',
          800: '#065083',
          900: '#0b436d',
        },
        caspary: {
          // Specific brand colors
          teal: '#3a9991',
          'teal-light': '#4ca49d',
          'teal-dark': '#2e7a74',
          gray: '#b8abab',
          'gray-light': '#f5f3f3',
          'gray-dark': '#574e4e',
          black: '#1a1a1a',
          white: '#ffffff',
        }
      },
      fontFamily: {
        'sans': ['apparat', 'ui-sans-serif', 'system-ui'],
        'serif': ['ui-serif', 'Georgia'],
        'mono': ['ui-monospace', 'SFMono-Regular'],
      },
      fontSize: {
        'xs': '.75rem',
        'sm': '.875rem',
        'base': '1rem',
        'lg': '1.125rem',
        'xl': '1.25rem',
        '2xl': '1.5rem',
        '3xl': '1.875rem',
        '4xl': '2.25rem',
        '5xl': '3rem',
        '6xl': '3.75rem',
        '7xl': '4.5rem',
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },
      borderRadius: {
        'sm': '0.125rem',
        'DEFAULT': '0.25rem',
        'md': '0.375rem',
        'lg': '0.5rem',
        'xl': '0.75rem',
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      container: {
        center: true,
        padding: {
          DEFAULT: '1rem',
          sm: '2rem',
          lg: '4rem',
          xl: '5rem',
          '2xl': '6rem',
        },
      },
    },
  },
  plugins: [],
}