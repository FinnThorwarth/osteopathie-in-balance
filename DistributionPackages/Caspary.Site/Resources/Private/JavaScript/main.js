import { createApp } from 'vue'
import '../Styles/app.css'
import { initMobileMenu } from './mobile-menu.js'

// Import Vue components
import MobileMenu from './components/MobileMenu.vue'
import Navigation from './components/Navigation.vue'
import Accordion from './components/Accordion.vue'
import AccordionItem from './components/AccordionItem.vue'
import ImageSlider from './components/ImageSlider.vue'

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
		console.log('Navigation element found, checking for navigation data...')
		
		// Get navigation data from data attribute
		let navigationItems = navigationElement.getAttribute('data-navigation')
		console.log('Navigation data from attribute:', navigationItems)
		
		// Create app with props
		const navApp = createApp(Navigation, {
			navigationItems: navigationItems
		})
		
		navigationElement._vueInstance = navApp.mount(navigationElement)
		console.log('Navigation Vue component mounted with data:', navigationItems ? 'yes' : 'no')
	}
	
	// Mount Accordion components
	document.querySelectorAll('[data-vue-accordion]').forEach(element => {
		if (!element._vueInstance) {
			const accordionApp = createApp(Accordion)
			// Register AccordionItem as a global component for this app instance
			accordionApp.component('AccordionItem', AccordionItem)
			element._vueInstance = accordionApp.mount(element)
		}
	})
	
	// Mount ImageSlider components
	document.querySelectorAll('[data-vue-slider]').forEach(element => {
		if (!element._vueInstance) {
			const slidesPerView = element.getAttribute('data-slides-per-view') || 3
			const sliderApp = createApp(ImageSlider, {
				slidesPerView: parseInt(slidesPerView)
			})
			element._vueInstance = sliderApp.mount(element)
		}
	})
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
				console.log('toggleGrayscale called, current state:', this.grayscaleActive);
				this.grayscaleActive = !this.grayscaleActive;
				console.log('toggleGrayscale new state:', this.grayscaleActive);
			}
		},
		mounted() {
			// Make toggleGrayscale available globally
			window.toggleGrayscale = this.toggleGrayscale.bind(this);
		},
		compilerOptions: {
			// Allow Vue to work with server-rendered content
			whitespace: 'preserve'
		}
	})

	// Mount main app if app element exists
	const appElement = document.getElementById('app')
	if (appElement) {
		// Mount the app directly without wrapper to preserve Vue directives
		app.mount('#app')
		console.log('Main Vue app mounted on #app element')
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
