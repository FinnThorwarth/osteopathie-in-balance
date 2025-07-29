import { createApp } from 'vue'
import '../Styles/app.css'
import { initMobileMenu } from './mobile-menu.js'

// Import Vue components
import MobileMenu from './components/MobileMenu.vue'
import Navigation from './components/Navigation.vue'

// Function to mount Vue components
function mountVueComponents() {
	// Mount Vue components if needed
	const mobileMenuElement = document.getElementById('mobile-menu-vue')
	if (mobileMenuElement && !mobileMenuElement._vueInstance) {
		mobileMenuElement._vueInstance = createApp(MobileMenu).mount(mobileMenuElement)
		console.log('Mobile menu Vue component mounted')
	}

	// Mount Navigation component
	const navigationElement = document.getElementById('navigation-vue')
	if (navigationElement && !navigationElement._vueInstance) {
		console.log('Navigation element found, mounting Vue component...')
		navigationElement._vueInstance = createApp(Navigation).mount(navigationElement)
		console.log('Navigation Vue component mounted')
	}
}

// Function to setup mutation observer for dynamic content
function setupMutationObserver() {
	const observer = new MutationObserver((mutations) => {
		// Check if navigation-vue element was added
		const hasNavigationElement = document.getElementById('navigation-vue')
		const hasMobileMenuElement = document.getElementById('mobile-menu-vue')

		if (hasNavigationElement || hasMobileMenuElement) {
			// Try to mount components
			mountVueComponents()
		}
	})

	// Start observing the document body for changes
	observer.observe(document.body, {
		childList: true,
		subtree: true
	})
}

// Initialize functionality
document.addEventListener('DOMContentLoaded', () => {
	// Initialize mobile menu
	initMobileMenu();

	// Create main Vue app with template that preserves existing content
	const app = createApp({
		data() {
			return {
				grayscaleActive: false
			}
		},
		methods: {
			toggleGrayscale() {
				console.log('toggleGrayscale: ', this.grayscaleActive);
				this.grayscaleActive = !this.grayscaleActive;
			}
		},
		compilerOptions: {
			// Allow Vue to work with server-rendered content
			whitespace: 'preserve'
		}
	})

	// Mount main app if app element exists
	const appElement = document.getElementById('app')
	if (appElement) {
		// Store the original content
		const originalContent = appElement.innerHTML

		// Create a wrapper component that includes the original content
		app.component('app-wrapper', {
			template: originalContent
		})

		// Mount with the wrapper
		app.mount('#app')
	}

	// Initial mount attempt
	mountVueComponents()

	// Setup mutation observer for dynamic content
	setupMutationObserver()

	// Also try mounting after a short delay as a fallback
	setTimeout(() => {
		mountVueComponents()
	}, 500)

	// Add smooth scrolling for anchor links
	document.querySelectorAll('a[href^="#"]').forEach(anchor => {
		anchor.addEventListener('click', function (e) {
			e.preventDefault();
			const target = document.querySelector(this.getAttribute('href'));
			if (target) {
				target.scrollIntoView({ behavior: 'smooth' });
			}
		});
	});
})

// Also ensure components are mounted after all resources are loaded
window.addEventListener('load', () => {
	mountVueComponents()
})

// General JavaScript functionality
console.log('Caspary Site frontend loaded')
