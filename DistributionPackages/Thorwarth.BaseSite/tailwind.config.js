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
				// Osteopathie in Balance Brand Colors
				'smart-navy': '#122945',
				'smart-teal': '#084996',
				'smart-teal-dark': '#0b2f60',
				'smart-gray': '#F5F5F5',
				'smart-text': '#3C3C3B',
				'smart-cream': '#FAF8F5',
				'smart-teal-light': '#ecfaff',
				'smart-warm-bg': '#FFFBF5',
				'smart-blue-light': '#EFF3FA',
				'smart-beige': '#F8F3E6',
				'smart-rose-light': '#FFEBEA',
				'smart-peach': '#FFF6EA',
				// Osteopathie in Balance Primary Color Palette (Congress Blue)
				primary: {
					50: '#ecfaff',
					100: '#d4f2ff',
					200: '#b2e9ff',
					300: '#7edeff',
					400: '#41c8ff',
					500: '#16a7ff',
					600: '#0087ff',
					700: '#006efd',
					800: '#0159cc',
					900: '#084996',
				},
				// Osteopathie in Balance Secondary Color Palette (Gray)
				secondary: {
					50: '#F5F5F5',
					100: '#D5D3D3',
					200: '#909090',
					300: '#5F5F5F',
					400: '#3C3C3B',
					500: '#2a2a29',
					600: '#1e1e1d',
					700: '#141413',
					800: '#0a0a09',
					900: '#000000',
				},
			},
			fontFamily: {
				'headline': ['Zalando Sans SemiExpanded', 'system-ui', 'sans-serif'],
				'expanded': ['Zalando Sans Expanded', 'system-ui', 'sans-serif'],
				'script': ['Roboto', 'system-ui', 'sans-serif'],
				'body': ['Roboto', 'system-ui', 'sans-serif'],
				'light': ['Roboto', 'system-ui', 'sans-serif'],
				'light-body': ['Roboto', 'system-ui', 'sans-serif'],
				'sans': ['Roboto', 'system-ui', 'sans-serif'],
				'serif': ['Roboto', 'system-ui', 'sans-serif'],
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
				'160': '40rem',
				'200': '50rem',
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
			typography: (theme) => ({
				DEFAULT: {
					css: {
						color: theme('colors.smart-text'),
						fontSize: '1.25rem',
						fontFamily: theme('fontFamily.sans'),
						fontWeight: '400',
						strong: {
							color: theme('colors.smart-navy'),
							fontWeight: '700',
						},
						em: {
							fontStyle: 'italic',
						},
						a: {
							color: theme('colors.smart-teal'),
							fontFamily: theme('fontFamily.sans'),
							fontWeight: '500',
							fontSize: '1.25rem',
							textDecoration: 'none',
							'&:hover': {
								color: theme('colors.smart-teal-dark'),
							},
						},
						h1: {
							color: theme('colors.smart-navy'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '700',
							fontSize: '3.4375rem',
							marginTop: '0',
							marginBottom: '1.5rem',
						},
						h2: {
							color: theme('colors.smart-teal'),
							fontFamily: theme('fontFamily.sans'),
							fontWeight: '300',
							fontSize: '2.9375rem',
							marginTop: '2rem',
							marginBottom: '1rem',
						},
						h3: {
							color: theme('colors.smart-navy'),
							fontFamily: theme('fontFamily.sans'),
							fontWeight: '600',
							fontSize: '1.5rem',
							marginTop: '1.5rem',
							marginBottom: '0.75rem',
						},
						h4: {
							color: theme('colors.smart-navy'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '600',
							fontSize: '1.25rem',
							marginTop: '1rem',
							marginBottom: '0.5rem',
						},
						p: {
							marginBottom: '1rem',
							lineHeight: '1.75',
						},
						ul: {
							listStyleType: 'disc',
							paddingLeft: '1.5rem',
							marginBottom: '1rem',
							'li': {
								marginBottom: '0.5rem',
								paddingLeft: '0.25rem',
							},
						},
						ol: {
							listStyleType: 'decimal',
							paddingLeft: '1.5rem',
							marginBottom: '1rem',
							'li': {
								marginBottom: '0.5rem',
								paddingLeft: '0.25rem',
							},
						},
						'ul ul, ol ol': {
							marginTop: '0.5rem',
							marginBottom: '0.5rem',
						},
					},
				},
				lg: {
					css: {
						fontSize: '1.125rem',
						lineHeight: '1.75',
						p: {
							marginBottom: '1.25rem',
						},
					},
				},
			}),
		},
	},
	plugins: [
		require('@tailwindcss/typography'),
		require('@tailwindcss/aspect-ratio'),
	],
}
