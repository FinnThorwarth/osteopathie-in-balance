<template>
  <div class="navigation-wrapper font-headline">
    <!-- Desktop Navigation (horizontal bar) - only visible when NOT scrolled -->
    <nav
      v-if="!isScrolled"
      class="hidden xl:block bg-mobility-red-dark text-white rounded-tl-3xl"
    >
      <div class="mx-auto pl-8 pr-0">
        <ul class="flex items-center justify-end space-x-8 text-xl 2xl:text-2xl font-light">
          <!-- Main menu items (all except last two) -->
          <li
            v-for="item in menuItems.slice(0, -2)"
            :key="item.title"
            class="relative group py-8"
          >
            <a
              v-if="item.url && item.url !== '#'"
              :href="item.url"
              class=" hover:opacity-80 transition-opacity"
              :class="{ 'font-semibold': item.isActive }"
            >
              {{ item.title }}
            </a>
            <span v-else class="">{{ item.title }}</span>
          </li>

          <!-- Last two items grouped together with separator -->
          <li v-if="menuItems.length > 2" class="relative group p-8 flex items-center space-x-3 bg-mobility-red rounded-tl-3xl">
            <a
              v-if="menuItems[menuItems.length - 2].url && menuItems[menuItems.length - 2].url !== '#'"
              :href="menuItems[menuItems.length - 2].url"
              class=" hover:opacity-80 transition-opacity"
              :class="{ 'font-semibold': menuItems[menuItems.length - 2].isActive }"
            >
              {{ menuItems[menuItems.length - 2].title }}
            </a>
            <span v-else class="">{{ menuItems[menuItems.length - 2].title }}</span>

            <span class="text-white">|</span>

            <a
              v-if="menuItems[menuItems.length - 1].url && menuItems[menuItems.length - 1].url !== '#'"
              :href="menuItems[menuItems.length - 1].url"
              class=" hover:opacity-80 transition-opacity"
              :class="{ 'font-semibold': menuItems[menuItems.length - 1].isActive }"
            >
              {{ menuItems[menuItems.length - 1].title }}
            </a>
            <span v-else class="">{{ menuItems[menuItems.length - 1].title }}</span>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Compact Navigation Button (always visible on mobile, only when scrolled on desktop) -->
    <div
      class="fixed top-0 right-0 z-50 transition-transform duration-300"
      :class="[
        isScrolled ? 'xl:block' : 'xl:hidden',
        'block'
      ]"
    >
      <button
        @click="toggleMenu"
        class="flex items-center bg-mobility-red p-4 rounded-bl-3xl"
        :aria-expanded="isMenuOpen"
        aria-label="Menü öffnen/schließen"
      >
        <!-- Animated SVG Hamburger/X Icon with MENÜ Text -->
        <svg class="h-8" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 246.6 56.7">
          <defs>
            <clipPath id="clippath">
              <rect class="menu-icon0" x="103.7" y="1.7" width="137.6" height="44.6"/>
            </clipPath>
          </defs>

          <!-- Animated Hamburger/X Lines -->
          <!-- Top line -->
          <rect
            class="menu-icon3 hamburger-svg-line"
            :x="isMenuOpen ? '15' : '6'"
            :y="isMenuOpen ? '23' : '2.8'"
            :width="isMenuOpen ? '60' : '79.4'"
            :height="isMenuOpen ? '10' : '11.3'"
            :transform="isMenuOpen ? 'rotate(45 45 28.35)' : ''"
          />
          <!-- Middle line -->
          <rect
            class="menu-icon4 hamburger-svg-line"
            x="6"
            y="22.7"
            width="79.4"
            height="11.3"
            :style="isMenuOpen ? 'opacity: 0;' : 'opacity: 1;'"
          />
          <!-- Bottom line -->
          <rect
            class="menu-icon1 hamburger-svg-line"
            :x="isMenuOpen ? '15' : '6'"
            :y="isMenuOpen ? '23' : '42.5'"
            :width="isMenuOpen ? '60' : '79.4'"
            :height="isMenuOpen ? '10' : '11.3'"
            :transform="isMenuOpen ? 'rotate(-45 45 28.35)' : ''"
          />

          <!-- MENÜ Text -->
          <g class="menu-icon5">
            <path class="menu-icon2" d="M227.6,46.3c9,0,13.7-5.3,13.7-14.7V11.4h-5.5v19.9c0,6.9-2.5,9.9-8.2,9.9s-8.2-3-8.2-9.8V11.4h-5.5v20.3c0,9.6,4.4,14.6,13.7,14.6M232.5,7.7c1.7,0,3-1.3,3-3s-1.3-3-3-3-3,1.3-3,3,1.2,3,3,3M222.8,7.7c1.7,0,3-1.3,3-3s-1.3-3-3-3-3,1.3-3,3,1.2,3,3,3M181.5,45.3v-15.4c0-3.3,0-6.1-.2-9h0c2.3,2.9,4.3,5.4,6.3,7.7l14.7,17.2h3.1V11.4h-5.5v14.5c0,3.4,0,6,.2,8.9h-.1c-2.2-2.9-4.2-5.3-6.2-7.8l-13.3-15.7h-4.5v34h5.5ZM167.3,30.2v-4.5h-13.9v-9.5h15.2l-.6-4.9h-20.2v34h20.2l.6-4.9h-15.2v-10.2h13.9ZM109.1,45.3v-16.6c0-3.4,0-6.9,0-10.3h.1c1.3,2.6,2.6,5.2,4,7.9l7.8,15.4h.9l7.9-15.4,4-7.9h.1c0,3.4,0,6.9,0,10.3v16.6h5.4V11.4h-7.1l-10.6,21.2h-.1l-10.6-21.2h-7.1v34h5.4Z"/>
          </g>
        </svg>
      </button>
    </div>

    <!-- Menu Overlay (Mobile & Scrolled Desktop) -->
    <div v-if="isMenuOpen" class="fixed inset-0 z-40">
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-black bg-opacity-20"
        @click="closeMenu"
      ></div>

      <!-- Slide-in Menu Panel from right -->
      <div class="absolute right-0 top-0 bottom-0 w-full max-w-md xl:max-w-xl bg-mobility-red rounded-bl-[3rem] shadow-2xl overflow-y-auto">
        <!-- Menu Content -->
        <div class="w-full text-center px-8 py-16 min-h-full flex flex-col justify-start pt-24">
          <nav>
            <!-- Main menu items (white text, larger) -->
            <ul class="space-y-6 mb-12">
              <li
                v-for="item in menuItems.slice(0, -2)"
                :key="item.title"
              >
                <a
                  v-if="item.url && item.url !== '#'"
                  :href="item.url"
                  @click="handleNavClick"
                  class="text-white text-3xl md:text-4xl font-semibold hover:opacity-80 transition-opacity"
                >
                  {{ item.title }}
                </a>
                <span v-else class="text-white text-3xl md:text-4xl font-semibold">
                  {{ item.title }}
                </span>
              </li>
            </ul>

            <!-- Secondary menu items (darker/subdued, smaller) -->
            <ul class="space-y-4" v-if="menuItems.length > 2">
              <li
                v-for="item in menuItems.slice(-2)"
                :key="item.title"
              >
                <a
                  v-if="item.url && item.url !== '#'"
                  :href="item.url"
                  @click="handleNavClick"
                  class="text-white text-2xl md:text-3xl font-light opacity-70 hover:opacity-100 transition-opacity"
                >
                  {{ item.title }}
                </a>
                <span v-else class="text-white text-2xl md:text-3xl font-light opacity-70">
                  {{ item.title }}
                </span>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Navigation",
  props: {
    navigationItems: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      isMenuOpen: false,
      isScrolled: false,
      menuItems: [],
    };
  },
  created() {
    console.log("[Navigation] Component created");
    console.log("[Navigation] navigationItems prop:", this.navigationItems);
    console.log("[Navigation] Current URL:", window.location.pathname);

    if (this.navigationItems) {
      try {
        const items = JSON.parse(this.navigationItems);
        console.log("[Navigation] Parsed items:", items);
        console.log("[Navigation] Current node info:", {
          identifier: items.currentNodeIdentifier,
          path: items.currentNodePath,
          name: items.currentNodeName,
        });

        // Check if menuItems is an array or needs conversion
        if (
          items.menuItems &&
          typeof items.menuItems === "object" &&
          !Array.isArray(items.menuItems)
        ) {
          this.menuItems = [];
        } else {
          this.menuItems = items.menuItems || [];
        }

        this.setActiveStatesBasedOnUrl();
      } catch (e) {
        console.error("[Navigation] Failed to parse navigation items:", e);
        console.error("[Navigation] Navigation data:", this.navigationItems);
      }
    } else {
      console.warn("[Navigation] No navigation data provided from Neos");
    }
  },
  methods: {
    setActiveStatesBasedOnUrl() {
      const currentPath = window.location.pathname;
      console.log("[Navigation] Setting active states for path:", currentPath);

      // Iterate through all menu items
      this.menuItems.forEach((item) => {
        // Check if this item is active
        item.isActive = currentPath === item.url;
        item.hasActiveChild = false;

        if (item.children && item.children.length > 0) {
          item.children.forEach((child) => {
            // Check if child is active
            child.isActive = currentPath === child.url;
            child.hasActiveChild = false;

            if (child.children && child.children.length > 0) {
              child.children.forEach((subChild) => {
                // Check if subchild is active
                subChild.isActive = currentPath === subChild.url;

                // If subchild is active, mark parent and grandparent as having active children
                if (subChild.isActive) {
                  child.hasActiveChild = true;
                  item.hasActiveChild = true;
                }
              });
            }

            // If child is active, mark parent as having active child
            if (child.isActive) {
              item.hasActiveChild = true;
            }
          });
        }
      });
    },
    toggleMenu() {
      console.log('[Navigation] toggleMenu called, current state:', this.isMenuOpen);
      this.isMenuOpen = !this.isMenuOpen;
      console.log('[Navigation] Menu state changed to:', this.isMenuOpen);
      this.updateBodyScroll();
    },
    closeMenu() {
      this.isMenuOpen = false;
      this.updateBodyScroll();
    },
    handleNavClick() {
      // Close menu after navigation
      this.closeMenu();
    },
    updateBodyScroll() {
      if (this.isMenuOpen) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    },
  },
  mounted() {
    console.log('[Navigation] Component mounted successfully');
    console.log('[Navigation] Menu button element:', this.$el.querySelector('button'));

    // Handle scroll events to toggle navigation style
    this.handleScroll = () => {
      this.isScrolled = window.scrollY > 100; // Show compact nav after 100px scroll
    };

    window.addEventListener('scroll', this.handleScroll);

    // Handle window resize - close menu when switching to desktop
    this.handleResize = () => {
      const isDesktop = window.innerWidth >= 1280; // xl breakpoint
      if (isDesktop && this.isMenuOpen && !this.isScrolled) {
        // Close menu when switching to desktop breakpoint where horizontal nav is visible
        this.closeMenu();
      }
    };

    window.addEventListener('resize', this.handleResize);

    // Close menu on escape key
    this.escapeListener = (e) => {
      if (e.key === "Escape" && this.isMenuOpen) {
        this.closeMenu();
      }
    };
    document.addEventListener("keydown", this.escapeListener);
  },
  beforeUnmount() {
    document.body.style.overflow = "";
    // Remove scroll listener
    if (this.handleScroll) {
      window.removeEventListener('scroll', this.handleScroll);
    }
    // Remove resize listener
    if (this.handleResize) {
      window.removeEventListener('resize', this.handleResize);
    }
    // Remove escape key listener
    if (this.escapeListener) {
      document.removeEventListener('keydown', this.escapeListener);
    }
  },
};
</script>
