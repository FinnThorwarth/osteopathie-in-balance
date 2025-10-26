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
				// Mobility Brand Colors
				'mobility-red': '#C8102E',
				'mobility-red-dark': '#A00D24',
				'mobility-red-light': '#E31E3A',
				'mobility-navy': '#1E3A5F',
				'mobility-navy-dark': '#162D47',
				'mobility-navy-light': '#2C5282',
				'mobility-blue': '#003C71',
				'mobility-blue-light': '#0066B3',
				'mobility-gray': '#2B2B2A',
				'mobility-gray-dark': '#1A1A1A',
				'mobility-gray-medium': '#63635F',
				'mobility-gray-light': '#B4B1B8',
				'mobility-text': '#4A4A49',
				'mobility-text-light': '#7B7B7B',
				'mobility-white': '#FFFFFF',
				'mobility-bg-light': '#F5F5F5',
				'mobility-footer': '#2B2B2A',
				'mobility-accent': '#FFDD00',
				// Mobility Primary Color Palette (Red)
				primary: {
					50: '#fef2f2',
					100: '#fee2e2',
					200: '#fecaca',
					300: '#fca5a5',
					400: '#f87171',
					500: '#C8102E',
					600: '#A00D24',
					700: '#8B0A1F',
					800: '#7A081B',
					900: '#670616',
				},
				// Mobility Secondary Color Palette (Navy Blue)
				secondary: {
					50: '#f0f4f8',
					100: '#dce5ed',
					200: '#b8cad9',
					300: '#8ca8bf',
					400: '#5f84a2',
					500: '#1E3A5F',
					600: '#162D47',
					700: '#112439',
					800: '#0d1d2d',
					900: '#0a1621',
				},
				// Mobility Accent Color Palette (Yellow/Gold)
				accent: {
					50: '#fffef0',
					100: '#fffbcc',
					200: '#fff899',
					300: '#fff566',
					400: '#fff233',
					500: '#FFDD00',
					600: '#ccb100',
					700: '#998500',
					800: '#665900',
					900: '#332c00',
				},
			},
			fontFamily: {
				'headline': ['Ubuntu Sans', 'Arial', 'sans-serif'],
				'body': ['Roboto Slab', 'Georgia', 'serif'],
				'light': ['Ubuntu Sans light'],
				'light-body': ['Roboto Slab light'],
				'thin': ['Ubuntu Sans thin'],
				'medium-body': ['Roboto Slab medium'],
				'sans': ['Ubuntu Sans', 'ui-sans-serif', 'system-ui'],
				'serif': ['Roboto Serif', 'Georgia'],
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
						color: theme('colors.mobility-text'),
						fontWeight: '300',
						strong: {
							color: theme('colors.mobility-gray'),
							fontWeight: '700',
						},
						em: {
							fontStyle: 'italic',
						},
						a: {
							color: theme('colors.mobility-red'),
							textDecoration: 'underline',
							'&:hover': {
								color: theme('colors.mobility-red-dark'),
							},
						},
						h1: {
							color: theme('colors.mobility-gray'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '700',
							marginTop: '0',
							marginBottom: '1.5rem',
						},
						h2: {
							color: theme('colors.mobility-gray'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '600',
							marginTop: '2rem',
							marginBottom: '1rem',
						},
						h3: {
							color: theme('colors.mobility-gray'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '600',
							marginTop: '1.5rem',
							marginBottom: '0.75rem',
						},
						h4: {
							color: theme('colors.mobility-gray'),
							fontFamily: theme('fontFamily.headline'),
							fontWeight: '500',
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
