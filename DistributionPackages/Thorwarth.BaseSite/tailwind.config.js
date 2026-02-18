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
				// SmartGrund Brand Colors
				'smart-navy': '#1B3A4B',
				'smart-navy-light': '#2A5065',
				'smart-navy-dark': '#122A36',
				'smart-teal': '#2AAFB5',
				'smart-teal-light': '#5FC8CC',
				'smart-teal-dark': '#1E8A8F',
				'smart-gray': '#F0F2F4',
				'smart-gray-light': '#F7F8FA',
				'smart-gray-dark': '#E0E3E7',
				'smart-text': '#333333',
				'smart-text-light': '#555555',
				'smart-white': '#FFFFFF',
				// SmartGrund Primary Color Palette (Teal)
				primary: {
					50: '#e8f8f9',
					100: '#d1f1f2',
					200: '#a3e3e6',
					300: '#75d5d9',
					400: '#47C7CD',
					500: '#2AAFB5',
					600: '#1E8A8F',
					700: '#166669',
					800: '#0E4244',
					900: '#072122',
				},
				// SmartGrund Secondary Color Palette (Gray)
				secondary: {
					50: '#FAFBFC',
					100: '#F7F8FA',
					200: '#F0F2F4',
					300: '#E0E3E7',
					400: '#C8CDD3',
					500: '#A0A8B2',
					600: '#788291',
					700: '#5A6370',
					800: '#3C434F',
					900: '#1E222E',
				},
				// SmartGrund Accent Color Palette (Navy)
				accent: {
					50: '#e8eef1',
					100: '#d1dde3',
					200: '#a3bbc7',
					300: '#7599ab',
					400: '#47778F',
					500: '#1B3A4B',
					600: '#163040',
					700: '#122A36',
					800: '#0D1F28',
					900: '#09151B',
				},
			},
			fontFamily: {
				'headline': ['Inter', 'system-ui', 'sans-serif'],
				'script': ['Inter', 'system-ui', 'sans-serif'],
				'body': ['Inter', 'system-ui', 'sans-serif'],
				'light': ['Inter', 'system-ui', 'sans-serif'],
				'light-body': ['Inter', 'system-ui', 'sans-serif'],
				'sans': ['Inter', 'system-ui', 'sans-serif'],
				'serif': ['Inter', 'system-ui', 'sans-serif'],
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
			typography: (theme) => ({
				DEFAULT: {
					css: {
						color: theme('colors.smart-text'),
						fontWeight: '400',
						strong: {
							color: theme('colors.smart-navy'),
							fontWeight: '700',
						},
						em: {
							fontStyle: 'italic',
						},
						a: {
							color: theme('colors.smart-teal-dark'),
							textDecoration: 'underline',
							'&:hover': {
								color: theme('colors.smart-navy'),
							},
						},
						h1: {
							color: theme('colors.smart-navy'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '700',
							fontSize: '2.75rem',
							marginTop: '0',
							marginBottom: '1.5rem',
						},
						h2: {
							color: theme('colors.smart-navy'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '700',
							fontSize: '2rem',
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
