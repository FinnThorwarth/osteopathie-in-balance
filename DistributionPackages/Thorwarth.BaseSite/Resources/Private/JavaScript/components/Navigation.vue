<template>
  <div class="navigation-wrapper font-expanded h-full">
    <!-- Desktop Navigation teleported to hero section (when NOT scrolled) -->
    <Teleport to="#desktop-nav-target" v-if="desktopNavTargetExists && !isScrolled">
      <nav class="flex justify-center text-smart-navy">
        <ul class="flex items-center flex-wrap justify-center">
          <li
            v-for="item in menuItems"
            :key="item.title"
            class="relative group"
          >
            <a
              v-if="item.url && item.url !== '#'"
              :href="item.url"
              @click="handleNavClick"
              class="flex items-center px-5 py-2 text-lg font-light transition-colors"
              :class="item.isActive || item.hasActiveChild
                ? 'text-smart-teal'
                : 'hover:text-smart-teal'"
            >
              {{ item.title }}
            </a>
            <span
              v-else
              class="flex items-center px-5 py-2 text-lg font-light"
              :class="item.isActive || item.hasActiveChild ? 'text-smart-teal' : ''"
            >
              {{ item.title }}
            </span>
            <!-- Hover Dropdown for children -->
            <div
              v-if="item.children && item.children.length > 0"
              class="absolute left-1/2 -translate-x-1/2 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
            >
              <div class="bg-white rounded-lg shadow-lg py-2 min-w-[220px]">
                <a
                  v-for="child in item.children"
                  :key="child.title"
                  :href="child.url"
                  @click="handleNavClick"
                  class="block px-5 py-2 text-base text-smart-text hover:bg-smart-teal/10 hover:text-smart-teal transition-colors"
                  :class="child.isActive ? 'text-smart-teal font-medium' : ''"
                >
                  {{ child.title }}
                </a>
              </div>
            </div>
          </li>
        </ul>
      </nav>
    </Teleport>

    <!-- Scrolled compact navigation teleported to fixed bar (when scrolled) -->
    <Teleport to="#scrolled-nav-target" v-if="scrolledNavTargetExists && isScrolled">
      <nav class="flex justify-center text-smart-navy pb-1">
        <ul class="flex items-center flex-wrap justify-center">
          <li
            v-for="item in menuItems"
            :key="'scrolled-' + item.title"
            class="relative group"
          >
            <a
              v-if="item.url && item.url !== '#'"
              :href="item.url"
              @click="handleNavClick"
              class="flex items-center px-3 py-1 text-sm font-light transition-colors"
              :class="item.isActive || item.hasActiveChild
                ? 'text-smart-teal'
                : 'hover:text-smart-teal'"
            >
              {{ item.title }}
            </a>
            <span
              v-else
              class="flex items-center px-3 py-1 text-sm font-light"
              :class="item.isActive || item.hasActiveChild ? 'text-smart-teal' : ''"
            >
              {{ item.title }}
            </span>
            <!-- Hover Dropdown for children -->
            <div
              v-if="item.children && item.children.length > 0"
              class="absolute left-1/2 -translate-x-1/2 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
            >
              <div class="bg-white rounded-lg shadow-lg py-2 min-w-[220px]">
                <a
                  v-for="child in item.children"
                  :key="child.title"
                  :href="child.url"
                  @click="handleNavClick"
                  class="block px-5 py-2 text-sm text-smart-text hover:bg-smart-teal/10 hover:text-smart-teal transition-colors"
                  :class="child.isActive ? 'text-smart-teal font-medium' : ''"
                >
                  {{ child.title }}
                </a>
              </div>
            </div>
          </li>
        </ul>
      </nav>
    </Teleport>

    <!-- Compact Navigation Button (only visible on mobile/tablet, hidden on desktop) -->
    <div
      class="z-50 transition-transform duration-300 flex h-full xl:hidden"
    >
      <button
        @click="toggleMenu"
        class="flex items-center justify-end px-6 py-4 text-smart-navy"
        :aria-expanded="isMenuOpen"
        aria-label="Menü öffnen/schließen"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
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
      <div class="absolute right-0 top-0 bottom-0 w-full max-w-md xl:max-w-xl bg-white shadow-2xl overflow-y-auto">
        <!-- Close Button -->
        <button
          @click="closeMenu"
          class="absolute top-0 right-0 p-4 text-smart-navy"
          aria-label="Menü schließen"
        >
          <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 6l12 12M6 18L18 6" />
          </svg>
        </button>
        <!-- Menu Content -->
        <div class="w-full text-center px-8 py-16 min-h-full flex flex-col justify-start pt-24">
          <nav>
            <!-- All menu items -->
            <ul class="space-y-6">
              <li
                v-for="item in menuItems"
                :key="item.title"
              >
                <a
                  v-if="item.url && item.url !== '#'"
                  :href="item.url"
                  @click="handleNavClick"
                  class="inline-block text-2xl md:text-3xl transition-all px-6 py-3"
                  :class="item.isActive
                    ? 'bg-smart-teal text-white font-normal'
                    : 'text-smart-navy font-normal hover:bg-smart-teal hover:text-white'"
                >
                  {{ item.title }}
                </a>
                <span
                  v-else
                  class="inline-block text-2xl md:text-3xl px-6 py-3"
                  :class="item.isActive
                    ? 'bg-smart-teal text-white font-normal'
                    : 'text-smart-navy font-normal'"
                >
                  {{ item.title }}
                </span>
                <!-- Mobile children -->
                <ul v-if="item.children && item.children.length > 0" class="mt-2 space-y-2">
                  <li v-for="child in item.children" :key="child.title">
                    <a
                      :href="child.url"
                      @click="handleNavClick"
                      class="inline-block text-lg md:text-xl transition-all px-6 py-1.5"
                      :class="child.isActive
                        ? 'text-smart-teal font-normal'
                        : 'text-smart-navy/70 font-light hover:text-smart-navy'"
                    >
                      {{ child.title }}
                    </a>
                  </li>
                </ul>
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
      isOnepager: false,
      observer: null,
      headerObserver: null,
      activeSectionId: null,
      isScrolling: false,
      scrollTimeout: null,
      desktopNavTargetExists: false,
      scrolledNavTargetExists: false,
    };
  },
  computed: {},
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

        // Check if this is an onepager site - use siteIsOnepager from Fusion
        // Only enable onepager behavior when on the homepage
        const isOnHomepage = window.location.pathname === '/' || window.location.pathname === '';
        this.isOnepager = (items.siteIsOnepager || false) && isOnHomepage;

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

        if (!this.isOnepager) {
          this.setActiveStatesBasedOnUrl();
        }
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
    handleNavClick(event) {
      const href = event.target.getAttribute('href');

      // Determine if this is an anchor link we should smooth scroll to
      let targetId = null;

      if (href && href.startsWith('#')) {
        // Direct anchor link (#section)
        targetId = href.substring(1);
      } else if (href && href.startsWith('/#')) {
        // Homepage anchor link (/#section) - only smooth scroll if we're on the homepage
        const isOnHomepage = window.location.pathname === '/' || window.location.pathname === '';
        if (isOnHomepage) {
          targetId = href.substring(2);
        }
      }

      // Handle smooth scroll if we have a target
      if (targetId) {
        const targetElement = document.getElementById(targetId);

        if (targetElement) {
          event.preventDefault();

          // Disable intersection observer during scroll
          this.isScrolling = true;

          // Clear any existing timeout
          if (this.scrollTimeout) {
            clearTimeout(this.scrollTimeout);
          }

          // Calculate offset for fixed header
          const headerOffset = 100;
          const elementPosition = targetElement.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });

          // Update active section manually
          this.activeSectionId = targetId;
          this.updateActiveStates();

          // Re-enable intersection observer after scroll completes
          this.scrollTimeout = setTimeout(() => {
            this.isScrolling = false;
          }, 800);
        }
      }

      // Close menu after navigation
      this.closeMenu();
    },
    updateActiveStates() {
      // Update isActive for all menu items based on activeSectionId
      this.menuItems.forEach((item) => {
        item.isActive = item.anchorId === this.activeSectionId;
      });
    },
    setupIntersectionObserver() {
      if (!this.isOnepager || this.menuItems.length === 0) return;

      // Disconnect existing observer if any
      if (this.observer) {
        this.observer.disconnect();
      }

      const options = {
        root: null,
        rootMargin: '-20% 0px -60% 0px', // Trigger when section is in upper part of viewport
        threshold: 0
      };

      this.observer = new IntersectionObserver((entries) => {
        // Skip updates during programmatic scrolling
        if (this.isScrolling) return;

        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            this.activeSectionId = entry.target.id;
            this.updateActiveStates();
          }
        });
      }, options);

      // Observe all sections that are in the navigation
      this.menuItems.forEach((item) => {
        if (item.anchorId) {
          const element = document.getElementById(item.anchorId);
          if (element) {
            this.observer.observe(element);
          }
        }
      });
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

    // Check if teleport targets exist in the DOM
    this.desktopNavTargetExists = !!document.getElementById('desktop-nav-target');
    this.scrolledNavTargetExists = !!document.getElementById('scrolled-nav-target');

    // Use MutationObserver to watch for header-scrolled class changes
    // This syncs perfectly with the CSS transitions
    const siteHeader = document.getElementById('site-header');
    if (siteHeader) {
      this.isScrolled = siteHeader.classList.contains('header-scrolled');
      this.headerObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            this.isScrolled = siteHeader.classList.contains('header-scrolled');
          }
        }
      });
      this.headerObserver.observe(siteHeader, { attributes: true, attributeFilter: ['class'] });
    }

    // Handle window resize - close menu when switching to desktop
    this.handleResize = () => {
      const isDesktop = window.innerWidth >= 1280; // xl breakpoint
      if (isDesktop && this.isMenuOpen) {
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

    // Setup intersection observer for onepager navigation
    this.$nextTick(() => {
      this.setupIntersectionObserver();
    });
  },
  beforeUnmount() {
    document.body.style.overflow = "";
    // Disconnect header MutationObserver
    if (this.headerObserver) {
      this.headerObserver.disconnect();
    }
    // Remove resize listener
    if (this.handleResize) {
      window.removeEventListener('resize', this.handleResize);
    }
    // Remove escape key listener
    if (this.escapeListener) {
      document.removeEventListener('keydown', this.escapeListener);
    }
    // Clear scroll timeout
    if (this.scrollTimeout) {
      clearTimeout(this.scrollTimeout);
    }
    // Disconnect intersection observer
    if (this.observer) {
      this.observer.disconnect();
    }
  },
};
</script>
