<template>
  <div class="navigation-wrapper font-headline">
    <button
      @click="toggleMenu"
      class="flex items-center"
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

    <!-- Desktop Menu Overlay -->
    <div
      v-if="isMenuOpen"
      class="menu-wrapper mainnav text-lg 2xl:text-xl  font-light fixed inset-0 z-50 hidden xl:block"
    >
      <nav class="relative h-full">
        <!-- Navigation boxes - 4 columns -->
        <div
          class="container grid grid-cols-4 gap-2 items-start justify-center text-caspary-purple"
        >
          <!-- Dynamic columns based on menu items -->
          <div
            v-for="(item, index) in menuItems"
            :key="item.title"
            class="bg-white shadow-xl py-6 px-4 max-h-[calc(100vh-213px)] overflow-y-auto"
          >
            <a
              v-if="item.url && item.url !== '#'"
              :href="item.url"
              @click="handleNavClick"
              class="font-semibold font-headline uppercase pb-2 block"
              :class="{ 'text-black': item.isActive }"
            >
              <span v-if="item.isActive">\</span> {{ item.title }}
            </a>
            <span v-else>{{ item.title }}</span>

            <!-- Sub navigation items -->
            <ul v-if="item.children && item.children.length > 0" class="">
              <li
                v-for="child in item.children"
                :key="child.title"
                class="border-t last-of-type:border-b border-caspary-purple"
              >
                <!-- Items with sub-children -->
                <div v-if="child.children && child.children.length > 0">
                  <div class="flex items-center justify-between w-full">
                    <a
                      v-if="child.url && child.url !== '#'"
                      :href="child.url"
                      class="uppercase block"
                      :class="{
                        'text-black': child.isActive,
                      }"
                    >
                      <span><span v-if="child.isActive">\</span> {{ child.title }}</span>
                    </a>
                    <button
                      @click="toggleSubmenu(getSubmenuKey(child.title))"
                      class="border-l border-caspary-purple px-2"
                    >
                      {{ submenuOpen[getSubmenuKey(child.title)] ? "−" : "+" }}
                    </button>
                  </div>
                  <ul
                    v-if="submenuOpen[getSubmenuKey(child.title)]"
                    class="text-lg"
                  >
                    <li
                      v-for="subChild in child.children"
                      :key="subChild.title"
                      class="border-t border-caspary-purple"
                    >
                      <a
                        :href="subChild.url"
                        @click="handleNavClick"
                        class="ml-4 block"
                        :class="{
                          'text-black': subChild.isActive,
                        }"
                      >
                        <span v-if="subChild.isActive">\</span> {{ subChild.title }}
                      </a>
                    </li>
                  </ul>
                </div>

                <!-- Regular items -->
                <a
                  v-else
                  :href="child.url"
                  @click="handleNavClick"
                  class="uppercase block"
                  :class="{ 'text-black': child.isActive }"
                >
                  <span v-if="child.isActive">\</span> {{ child.title }}
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>

    <!-- Mobile Menu Overlay -->
    <div v-if="isMenuOpen" class="fixed inset-0 top-[123px] z-50 bg-white xl:hidden">
      <!-- Mobile Menu Content -->
      <div class="h-full text-caspary-purple   overflow-y-auto pb-20">
        <div class="">
          <div
            v-for="(item, index) in mobileMenuItems"
            :key="item.title"
            class="border-t border-caspary-purple first-of-type:border-t-0 "
            :class="{ 'border-b': index === mobileMenuItems.length - 1 }"
          >
            <!-- Top level items with children -->
            <div v-if="item.items && item.items.length > 0" class="flex items-center justify-between px-4">
              <a
                v-if="item.href && item.href !== '#'"
                :href="item.href"
                @click="handleNavClick"
                class="text-lg tracking-wide uppercase flex-1"
                :class="{ 'text-black': item.isActive }"
              >
                <span v-if="item.isActive">\</span> {{ item.title }}

              </a>
              <span v-else class="text-lg tracking-wide uppercase flex-1" :class="{ 'text-black': item.isActive }">
                <span v-if="item.isActive">\</span> {{ item.title }}
              </span>
              <button
                @click="toggleMobileSubmenu(item.title)"
                class="text-2xl pl-4 border-l border-caspary-purple"
              >
                {{ item.isOpen ? "−" : "+" }}
              </button>
            </div>
            <!-- Top level items without children -->
            <a
              v-else
              :href="item.href"
              @click="handleNavClick"
              class="block text-lg tracking-wide uppercase px-4 py-1"
              :class="{ 'text-black': item.isActive }"
            >
              <span v-if="item.isActive">\</span> {{ item.title }}
            </a>

            <div v-if="item.isOpen && item.items && item.items.length > 0" class="">
              <ul class="">
                <li v-for="(subItem, subIndex) in item.items" :key="subItem.title" class="border-t border-caspary-purple" >
                  <!-- Sub items without children -->
                  <a
                    v-if="!subItem.children || subItem.children.length === 0"
                    :href="subItem.href"
                    @click="handleNavClick"
                    class="block py-2 pl-8"
                    :class="{
                      'text-black': subItem.isActive,
                    }"
                  >
                    <span v-if="subItem.isActive">\</span> {{ subItem.title }}
                  </a>
                  <!-- Sub items with children -->
                  <div v-else>
                    <div class="flex items-center justify-between pl-8">
                      <a
                        v-if="subItem.href && subItem.href !== '#'"
                        :href="subItem.href"
                        @click="handleNavClick"
                        class="flex-1"
                        :class="{
                          'text-black': subItem.isActive,
                        }"
                      >
                        <span v-if="subItem.isActive">\</span> {{ subItem.title }}
                      </a>
                      <span v-else class="text-gray-600 uppercase flex-1" :class="{ 'text-black': subItem.isActive }">
                        <span v-if="subItem.isActive">\</span> {{ subItem.title }}
                      </span>
                      <button
                        @click="toggleMobileSubmenu(subItem.title)"
                        class="text-2xl px-4 border-l border-caspary-purple"
                      >
                        {{ subItem.isOpen ? "−" : "+" }}
                      </button>
                    </div>
                    <ul v-if="subItem.isOpen" class="">
                      <li v-for="(child, childIndex) in subItem.children" :key="child.title" class="border-t border-caspary-purple">
                        <a
                          :href="child.href"
                          @click="handleNavClick"
                          class="block py-1 pl-12"
                          :class="{
                            'text-black': child.isActive,
                          }"
                        >
                          <span v-if="child.isActive">\</span> {{ child.title }}
                        </a>
                      </li>
                    </ul>
                  </div>
                </li>
              </ul>
            </div>
          </div>
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
      submenuOpen: {},
      menuItems: [],
      mobileMenuItems: [],
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

        this.processMobileMenuItems();
      } catch (e) {
        console.error("[Navigation] Failed to parse navigation items:", e);
        console.error("[Navigation] Navigation data:", this.navigationItems);
      }
    } else {
      console.warn("[Navigation] No navigation data provided from Neos");
    }
  },
  methods: {
    processMobileMenuItems() {
      // Convert menu items to mobile format with isOpen state
      this.mobileMenuItems = this.menuItems.map((item) => ({
        title: item.title,
        href: item.url,
        isActive: item.isActive,
        isOpen:
          item.isActive ||
          (item.children &&
            item.children.some(
              (child) =>
                child.isActive ||
                (child.children &&
                  child.children.some((subChild) => subChild.isActive))
            )),
        items: item.children
          ? item.children.map((child) => ({
              title: child.title,
              href: child.url,
              isActive: child.isActive,
              isOpen:
                child.isActive ||
                (child.children &&
                  child.children.some((subChild) => subChild.isActive)),
              children: child.children
                ? child.children.map((subChild) => ({
                    title: subChild.title,
                    href: subChild.url,
                    isActive: subChild.isActive,
                  }))
                : [],
            }))
          : [],
      }));

      // Initialize submenu open states and check for active children
      this.menuItems.forEach((item) => {
        if (item.children && item.children.length > 0) {
          item.children.forEach((child) => {
            if (child.children && child.children.length > 0) {
              const key = this.getSubmenuKey(child.title);
              // Open submenu if any child is active or if this item is active
              const hasActiveChild = child.children.some(
                (subChild) => subChild.isActive
              );
              this.submenuOpen[key] = child.isActive || hasActiveChild;
            }
          });
        }
      });
    },
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
    getSubmenuKey(title) {
      // Convert title to key for submenu tracking
      if (!title) {
        console.warn("[Navigation] getSubmenuKey called with empty title");
        return "untitled";
      }
      return String(title)
        .toLowerCase()
        .replace(/[^\w]+/g, "");
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
    toggleSubmenu(key) {
      this.submenuOpen[key] = !this.submenuOpen[key];
    },
    toggleMobileSubmenu(title) {
      const item = this.mobileMenuItems.find((i) => i.title === title);
      if (item) {
        item.isOpen = !item.isOpen;
      }

      // Check for nested items
      this.mobileMenuItems.forEach((mainItem) => {
        if (mainItem.items) {
          const subItem = mainItem.items.find((i) => i.title === title);
          if (subItem) {
            subItem.isOpen = !subItem.isOpen;
          }
        }
      });
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
  computed: {
    menuIconUrl() {
      const url = "/_Resources/Static/Packages/Caspary.Site/Images/menu-icon.svg";
      console.log('[Navigation] Menu icon URL:', url);
      return url;
    },
    logoUrl() {
      return "/_Resources/Static/Packages/Caspary.Site/Images/logo.svg";
    },
  },
  mounted() {
    console.log('[Navigation] Component mounted successfully');
    console.log('[Navigation] Menu button element:', this.$el.querySelector('button'));


    // Close menu on escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.isMenuOpen) {
        this.closeMenu();
      }
    });
  },
  beforeUnmount() {
    document.body.style.overflow = "";
  },
};
</script>
