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
        // Caspary brand colors from design
        primary: {
          50: '#f4f1f2',
          100: '#e8e1e3',
          200: '#d1c3c7',
          300: '#b89ea6',
          400: '#a17985',
          500: '#7b5664', // Main brand color
          600: '#6d4d59',
          700: '#5c414c',
          800: '#4a363e',
          900: '#3b2b31',
        },
        secondary: {
          50: '#f0f6f7',
          100: '#e1ecef',
          200: '#c3d9df',
          300: '#9bc0ca',
          400: '#73a7b5',
          500: '#528495', // Sub-headline color
          600: '#4a767f',
          700: '#3e626a',
          800: '#325055',
          900: '#294044',
        },
        accent: {
          // Teal colors from hamburger menu
          50: '#f0fefe',
          100: '#e0fcfc',
          200: '#c1f9f9',
          300: '#a9cfcf', // Light teal from SVG
          400: '#7dbdbd',
          500: '#6fa1ae', // Main teal from SVG
          600: '#5a8a96',
          700: '#4a737e',
          800: '#3a5c66',
          900: '#2e4a52',
        }
      },
      fontFamily: {
        // Apparat font from Adobe Fonts
        'sans': ['apparat', 'ui-sans-serif', 'system-ui'],
        'serif': ['ui-serif', 'Georgia'],
        'mono': ['ui-monospace', 'SFMono-Regular'],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      }
    },
  },
  plugins: [],
}