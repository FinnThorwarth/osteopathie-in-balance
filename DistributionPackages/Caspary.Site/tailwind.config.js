/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './Resources/Private/**/*.{html,fusion,yaml}',
    './Resources/Public/**/*.{js,vue}',
    './NodeTypes/**/*.{fusion,yaml}'
  ],
  theme: {
    extend: {
      colors: {
        // These colors should be extracted from the design PDF
        'caspary': {
          'primary': '#1a1a1a',
          'secondary': '#666666',
          'accent': '#8B7355',
          'light': '#f5f5f5'
        }
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        'serif': ['Georgia', 'serif']
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ],
}