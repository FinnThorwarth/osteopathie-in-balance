<template>
  <div class="mobile-menu">
    <!-- Mobile menu button -->
    <button
      @click="toggleMenu"
      class="mobile-menu-button md:hidden p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
      aria-expanded="false"
    >
      <span class="sr-only">Open main menu</span>
      <!-- Hamburger icon -->
      <svg
        class="block h-6 w-6"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>
    </button>

    <!-- Mobile menu overlay -->
    <div
      v-if="isMenuOpen"
      class="fixed inset-0 z-50 md:hidden"
      @click="closeMenu"
    >
      <div class="fixed inset-0 bg-black bg-opacity-25"></div>
      
      <!-- Mobile menu panel -->
      <div class="relative flex flex-col w-full max-w-sm bg-white shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b">
          <h2 class="text-lg font-semibold text-gray-900">Menu</h2>
          <button
            @click="closeMenu"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <span class="sr-only">Close menu</span>
            <svg
              class="h-6 w-6"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Menu items -->
        <nav class="flex-1 px-4 py-6 space-y-6">
          <div class="space-y-1">
            <a
              v-for="item in menuItems"
              :key="item.name"
              :href="item.href"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="closeMenu"
            >
              {{ item.name }}
            </a>
          </div>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MobileMenu',
  data() {
    return {
      isMenuOpen: false,
      menuItems: [
        { name: 'Home', href: '/' },
        { name: 'Über uns', href: '/ueber-uns' },
        { name: 'Leistungen', href: '/leistungen' },
        { name: 'Bestattungsarten', href: '/bestattungsarten' },
        { name: 'Vorsorge', href: '/vorsorge' },
        { name: 'Kontakt', href: '/kontakt' },
      ]
    }
  },
  methods: {
    toggleMenu() {
      this.isMenuOpen = !this.isMenuOpen
      this.updateBodyScroll()
    },
    closeMenu() {
      this.isMenuOpen = false
      this.updateBodyScroll()
    },
    updateBodyScroll() {
      if (this.isMenuOpen) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    }
  },
  mounted() {
    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isMenuOpen) {
        this.closeMenu()
      }
    })
  },
  beforeUnmount() {
    document.body.style.overflow = ''
  }
}
</script>