import { createApp } from 'vue'
import '../Styles/app.css'

// Import GLightbox for gallery lightbox functionality
import GLightbox from 'glightbox'
import 'glightbox/dist/css/glightbox.min.css'

// Import Vue components
import Navigation from './components/Navigation.vue'
import Accordion from './components/Accordion.vue'
import AccordionItem from './components/AccordionItem.vue'
import ImageSlider from './components/ImageSlider.vue'
import Search from './components/Search.vue'
import TeamCarousel from './components/TeamCarousel.vue'

// Function to mount Vue components
function mountVueComponents() {
	// Check if we're in Neos backend
	const isNeosBackend = window.parent !== window && window.parent.Neos;
	
	// Mount Vue components if needed

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
		
		// Dispatch event for Neos backend
		if (isNeosBackend) {
			document.dispatchEvent(new CustomEvent('vue:mounted', { detail: { component: 'Navigation' } }));
		}
	}

	// Mount Accordion components
	document.querySelectorAll('[data-vue-accordion]').forEach(element => {
		if (!element._vueInstance) {
			const accordionApp = createApp(Accordion)
			// Register AccordionItem as a global component for this app instance
			accordionApp.component('AccordionItem', AccordionItem)
			element._vueInstance = accordionApp.mount(element)
			
			// Dispatch event for Neos backend
			if (isNeosBackend) {
				document.dispatchEvent(new CustomEvent('vue:mounted', { detail: { component: 'Accordion' } }));
			}
		}
	})

	// Mount ImageSlider components
	document.querySelectorAll('[data-vue-slider]').forEach(element => {
		if (!element._vueInstance) {
			const slidesPerView = element.getAttribute('data-slides-per-view') || 3
			const aspectRatio = element.getAttribute('data-aspect-ratio') || '16-9'
			const autoplay = element.getAttribute('data-autoplay') === 'true'
			const autoplayDelay = parseInt(element.getAttribute('data-autoplay-delay') || 3000)
			const sliderApp = createApp(ImageSlider, {
				slidesPerView: parseInt(slidesPerView),
				aspectRatio: aspectRatio,
				autoplay: autoplay,
				autoplayDelay: autoplayDelay
			})
			element._vueInstance = sliderApp.mount(element)

			// Dispatch event for Neos backend
			if (isNeosBackend) {
				document.dispatchEvent(new CustomEvent('vue:mounted', { detail: { component: 'ImageSlider' } }));
			}
		}
	})

	// Mount TeamCarousel components
	document.querySelectorAll('[data-vue-team-carousel]').forEach(element => {
		if (!element._vueInstance) {
			const autoplayDelay = parseInt(element.getAttribute('data-autoplay-delay') || 5000)
			const teamApp = createApp(TeamCarousel, {
				autoplayDelay: autoplayDelay
			})
			element._vueInstance = teamApp.mount(element)

			// Dispatch event for Neos backend
			if (isNeosBackend) {
				document.dispatchEvent(new CustomEvent('vue:mounted', { detail: { component: 'TeamCarousel' } }));
			}
		}
	})

	// Mount Search component
	const searchElement = document.getElementById('search-vue')
	if (searchElement && !searchElement._vueInstance) {
		const searchApp = createApp(Search, {
			placeholder: searchElement.getAttribute('data-placeholder') || 'Suche...',
			noResultsMessage: searchElement.getAttribute('data-no-results-message') || 'Keine Ergebnisse gefunden.',
			searchTerm: searchElement.getAttribute('data-search-term') || '',
			resultsPerPage: parseInt(searchElement.getAttribute('data-results-per-page') || '10')
		})
		searchElement._vueInstance = searchApp.mount(searchElement)
		console.log('Search Vue component mounted')

		// Dispatch event for Neos backend
		if (isNeosBackend) {
			document.dispatchEvent(new CustomEvent('vue:mounted', { detail: { component: 'Search' } }));
		}
	}

	// Initialize gallery lightboxes (skip in Neos backend to preserve editing)
	if (!isNeosBackend) {
		initializeGalleryLightboxes()
	}
}

// Function to initialize GLightbox for gallery components
function initializeGalleryLightboxes() {
	document.querySelectorAll('[data-gallery-lightbox]').forEach(galleryElement => {
		// Skip if already initialized
		if (galleryElement._glightboxInstance) {
			galleryElement._glightboxInstance.reload()
			return
		}

		const galleryId = galleryElement.getAttribute('data-gallery-lightbox')

		// Initialize GLightbox for this gallery
		const lightbox = GLightbox({
			selector: `.glightbox[data-gallery="${galleryId}"]`,
			touchNavigation: true,
			loop: true,
			autoplayVideos: false,
			closeButton: true,
			touchFollowAxis: true,
			keyboardNavigation: true,
			closeOnOutsideClick: true,
			openEffect: 'zoom',
			closeEffect: 'fade'
		})

		// Store reference for later refresh
		galleryElement._glightboxInstance = lightbox
	})
}

// Function to setup mutation observer for dynamic content
function setupMutationObserver() {
	const observer = new MutationObserver((mutations) => {
		// Check if navigation-vue element was added
		const hasNavigationElement = document.getElementById('navigation-vue')

		if (hasNavigationElement) {
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
			whitespace: 'preserve',
			// Don't treat these as Vue components - they're Neos elements
			isCustomElement: tag => {
				return tag.includes('neos-') || 
					   tag === 'div' && document.querySelector(tag)?.hasAttribute('contenteditable');
			}
		}
	})

	// Mount main app if app element exists - but exclude Neos editable areas
	const appElement = document.getElementById('app')
	if (appElement) {
		// Check if we're in Neos backend
		const isNeosBackend = window.parent !== window && window.location.href.includes('/neos/');
		
		if (isNeosBackend) {
			console.log('Neos backend detected - mounting Vue with restrictions');
			// In backend, don't mount Vue on the entire app to preserve Neos functionality
			// Only mount specific components that don't interfere with inline editing
		} else {
			// In frontend, mount Vue normally
			app.mount('#app')
			console.log('Main Vue app mounted on #app element')
		}
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
