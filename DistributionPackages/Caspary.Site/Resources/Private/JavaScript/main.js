import { createApp } from 'vue'
import '../Styles/app.css'
import { initMobileMenu } from './mobile-menu.js'

// Import Vue components
import MobileMenu from './components/MobileMenu.vue'

// Initialize functionality
document.addEventListener('DOMContentLoaded', () => {
  // Initialize mobile menu
  initMobileMenu();
  
  // Mount Vue components if needed
  const mobileMenuElement = document.getElementById('mobile-menu-vue')
  if (mobileMenuElement) {
    createApp(MobileMenu).mount(mobileMenuElement)
  }
  
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

// General JavaScript functionality
console.log('Caspary Site frontend loaded')