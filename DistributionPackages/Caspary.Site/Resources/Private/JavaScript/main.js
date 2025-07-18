import { createApp } from 'vue'
import '../Styles/app.css'

// Import components
import MobileMenu from './components/MobileMenu.vue'

// Initialize Vue components
document.addEventListener('DOMContentLoaded', () => {
  // Mount mobile menu component
  const mobileMenuElement = document.getElementById('mobile-menu')
  if (mobileMenuElement) {
    createApp(MobileMenu).mount(mobileMenuElement)
  }
  
  // Add other component mounts here as needed
})

// General JavaScript functionality
console.log('Caspary Site frontend loaded')