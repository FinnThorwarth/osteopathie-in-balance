import { createApp } from 'vue'
import '../Styles/main.css'

// Import Vue components
import ContactForm from './components/ContactForm.vue'
import AppointmentBooking from './components/AppointmentBooking.vue'
import MobileMenu from './components/MobileMenu.vue'

// Initialize Vue apps for specific components
document.addEventListener('DOMContentLoaded', () => {
  // Mobile Menu
  const mobileMenuEl = document.getElementById('mobile-menu')
  if (mobileMenuEl) {
    createApp(MobileMenu).mount('#mobile-menu')
  }

  // Contact Form
  const contactFormEl = document.getElementById('contact-form')
  if (contactFormEl) {
    createApp(ContactForm).mount('#contact-form')
  }

  // Appointment Booking
  const appointmentEl = document.getElementById('appointment-booking')
  if (appointmentEl) {
    createApp(AppointmentBooking).mount('#appointment-booking')
  }
})