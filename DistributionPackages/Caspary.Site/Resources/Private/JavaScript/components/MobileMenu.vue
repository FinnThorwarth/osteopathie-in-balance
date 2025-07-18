<template>
  <div class="mobile-menu">
    <!-- Mobile menu button -->
    <button
      @click="toggleMenu"
      class="mobile-menu-button lg:hidden flex items-center space-x-2 p-2 text-gray-900 hover:text-gray-700 focus:outline-none"
      aria-expanded="false"
    >
      <span class="sr-only">Open main menu</span>
      <!-- Custom hamburger icon matching design -->
      <svg class="w-8 h-8" viewBox="0 0 246.6 56.7">
        <rect class="fill-accent-500" x="11" y="2.8" width="79.4" height="11.3"/>
        <rect class="fill-accent-300" x="11" y="22.7" width="79.4" height="11.3"/>
        <rect class="fill-gray-400" x="11" y="42.5" width="79.4" height="11.3"/>
        <g class="fill-gray-900">
          <path d="M222.6,46.3c9,0,13.7-5.3,13.7-14.7V11.4h-5.5v19.9c0,6.9-2.5,9.9-8.2,9.9s-8.2-3-8.2-9.8V11.4h-5.5v20.3c0,9.6,4.4,14.6,13.7,14.6M227.5,7.7c1.7,0,3-1.3,3-3s-1.3-3-3-3-3,1.3-3,3,1.2,3,3,3M217.8,7.7c1.7,0,3-1.3,3-3s-1.3-3-3-3-3,1.3-3,3,1.2,3,3,3M176.5,45.3v-15.4c0-3.3,0-6.1-.2-9h0c2.3,2.9,4.3,5.4,6.3,7.7l14.7,17.2h3.1V11.4h-5.5v14.5c0,3.4,0,6,.2,8.9h-.1c-2.2-2.9-4.2-5.3-6.2-7.8l-13.3-15.7h-4.5v34h5.5ZM162.3,30.2v-4.5h-13.9v-9.5h15.2l-.6-4.9h-20.2v34h20.2l.6-4.9h-15.2v-10.2h13.9ZM104.1,45.3v-16.6c0-3.4,0-6.9,0-10.3h.1c1.3,2.6,2.6,5.2,4,7.9l7.8,15.4h.9l7.9-15.4,4-7.9h.1c0,3.4,0,6.9,0,10.3v16.6h5.4V11.4h-7.1l-10.6,21.2h-.1l-10.6-21.2h-7.1v34h5.4Z"/>
        </g>
      </svg>
      <span class="font-bold text-lg">MENÜ</span>
    </button>

    <!-- Mobile menu overlay -->
    <div
      v-if="isMenuOpen"
      class="mobile-menu-overlay fixed inset-0 z-50 bg-black bg-opacity-95 text-white transform transition-transform duration-300 ease-in-out"
      :class="{ 'translate-x-0': isMenuOpen, 'translate-x-full': !isMenuOpen }"
    >
      <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 bg-white text-black">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center">
              <span class="text-white font-bold text-lg">C</span>
            </div>
          </div>
          <button
            @click="closeMenu"
            class="flex items-center space-x-2 p-2 text-gray-900 hover:text-gray-700"
          >
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <span class="font-bold text-lg">CLOSE</span>
          </button>
        </div>

        <!-- Menu items -->
        <nav class="flex-1 overflow-y-auto">
          <ul class="divide-y divide-gray-600">
            <li
              v-for="item in menuItems"
              :key="item.name"
              class="menu-item"
              :data-has-submenu="item.hasSubmenu"
            >
              <div class="flex items-center justify-between">
                <a
                  :href="item.href"
                  class="flex-1 py-4 px-4 text-xl font-bold uppercase tracking-wide hover:bg-gray-800 transition-colors"
                  @click="closeMenu"
                >
                  {{ item.name }}
                </a>
                <button
                  v-if="item.hasSubmenu"
                  @click="toggleSubmenu(item.name)"
                  class="submenu-toggle p-4 text-2xl font-bold"
                  aria-label="Toggle submenu"
                >
                  {{ item.submenuOpen ? '-' : '+' }}
                </button>
              </div>
              
              <ul
                v-if="item.hasSubmenu && item.submenuOpen"
                class="submenu bg-gray-900"
              >
                <li
                  v-for="subItem in item.subItems"
                  :key="subItem.name"
                >
                  <a
                    :href="subItem.href"
                    class="block py-3 px-8 text-lg text-gray-300 hover:text-white hover:bg-gray-800 transition-colors"
                    @click="closeMenu"
                  >
                    {{ subItem.name }}
                  </a>
                </li>
              </ul>
            </li>
          </ul>
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
        { 
          name: 'STARTSEITE', 
          href: '/',
          hasSubmenu: false
        },
        { 
          name: 'IM TRAUERFALL', 
          href: '/im-trauerfall',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Was ist zu tun?', href: '/im-trauerfall/was-ist-zu-tun' },
            { name: 'Checkliste', href: '/im-trauerfall/checkliste' },
            { name: 'Bestattungsarten', href: '/im-trauerfall/bestattungsarten' },
            { name: 'Ablauf einer Bestattung', href: '/im-trauerfall/ablauf' },
            { name: 'Kosten', href: '/im-trauerfall/kosten' },
            { name: 'Rechtliches', href: '/im-trauerfall/rechtliches' }
          ]
        },
        { 
          name: 'BESTATTUNGSARTEN', 
          href: '/bestattungsarten',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Erdbestattung', href: '/bestattungsarten/erdbestattung' },
            { name: 'Feuerbestattung', href: '/bestattungsarten/feuerbestattung' },
            { name: 'Seebestattung', href: '/bestattungsarten/seebestattung' },
            { name: 'Baumbestattung', href: '/bestattungsarten/baumbestattung' },
            { name: 'Anonyme Bestattung', href: '/bestattungsarten/anonyme-bestattung' }
          ]
        },
        { 
          name: 'TRAUERRAUM', 
          href: '/trauerraum',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Aufbahrung', href: '/trauerraum/aufbahrung' },
            { name: 'Abschiednahme', href: '/trauerraum/abschiednahme' },
            { name: 'Trauerfloristik', href: '/trauerraum/trauerfloristik' },
            { name: 'Räumlichkeiten', href: '/trauerraum/raeumlichkeiten' }
          ]
        },
        { 
          name: 'VORSORGE', 
          href: '/vorsorge',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Bestattungsvorsorge', href: '/vorsorge/bestattungsvorsorge' },
            { name: 'Vorsorgevertrag', href: '/vorsorge/vorsorgevertrag' },
            { name: 'Sterbegeldversicherung', href: '/vorsorge/sterbegeldversicherung' },
            { name: 'Patientenverfügung', href: '/vorsorge/patientenverfuegung' }
          ]
        },
        { 
          name: 'ÜBER UNS', 
          href: '/ueber-uns',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Unternehmen', href: '/ueber-uns/unternehmen' },
            { name: 'Team', href: '/ueber-uns/team' },
            { name: 'Geschichte', href: '/ueber-uns/geschichte' },
            { name: 'Leitbild', href: '/ueber-uns/leitbild' }
          ]
        },
        { 
          name: 'SERVICE', 
          href: '/service',
          hasSubmenu: true,
          submenuOpen: false,
          subItems: [
            { name: 'Friedhöfe', href: '/service/friedhoefe' },
            { name: 'Trauerdrucksachen', href: '/service/trauerdrucksachen' },
            { name: 'Steinmetz', href: '/service/steinmetz' },
            { name: 'Behördengänge', href: '/service/behoerdengaenge' }
          ]
        },
        { 
          name: 'KONTAKT', 
          href: '/kontakt',
          hasSubmenu: false
        }
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
    toggleSubmenu(itemName) {
      const item = this.menuItems.find(item => item.name === itemName)
      if (item) {
        item.submenuOpen = !item.submenuOpen
      }
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