<template>
  <div>
    <!-- Burger Menu Button -->
    <button
      @click="toggleMenu"
      class="burger-menu z-50 relative"
      :class="{ 'is-open': isOpen }"
      aria-label="Toggle menu"
    >
      <span class="block w-full h-0.5 bg-current transform transition-transform duration-300"
            :class="isOpen ? 'rotate-45 translate-y-2' : ''"></span>
      <span class="block w-full h-0.5 bg-current mt-1.5 transition-opacity duration-300"
            :class="isOpen ? 'opacity-0' : ''"></span>
      <span class="block w-full h-0.5 bg-current mt-1.5 transform transition-transform duration-300"
            :class="isOpen ? '-rotate-45 -translate-y-2' : ''"></span>
    </button>

    <!-- Mobile Menu Overlay -->
    <transition name="menu">
      <div v-if="isOpen" class="mobile-menu">
        <div class="container mx-auto px-4 py-20">
          <nav class="space-y-6">
            <a href="#" class="block text-2xl font-medium hover:text-caspary-accent transition-colors">
              Leistungen
            </a>
            <a href="#" class="block text-2xl font-medium hover:text-caspary-accent transition-colors">
              Über uns
            </a>
            <a href="#" class="block text-2xl font-medium hover:text-caspary-accent transition-colors">
              Vorsorge
            </a>
            <a href="#" class="block text-2xl font-medium hover:text-caspary-accent transition-colors">
              Kontakt
            </a>
          </nav>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const isOpen = ref(false)

const toggleMenu = () => {
  isOpen.value = !isOpen.value
  // Prevent body scroll when menu is open
  document.body.style.overflow = isOpen.value ? 'hidden' : ''
}
</script>

<style scoped>
.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>