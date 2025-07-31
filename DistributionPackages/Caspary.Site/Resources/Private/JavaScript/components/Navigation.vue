<template>
  <div class="navigation-wrapper">
    <button
      @click="toggleMenu"
      class="flex items-center p-2 hover:bg-gray-100 rounded"
    >
      <img :src="menuIconUrl" alt="Menü" class="h-8" />
    </button>

    <!-- Desktop Menu Overlay -->
    <div
      v-if="isMenuOpen"
      class="menu-wrapper mainnav text-xl font-light fixed inset-0 z-50 hidden md:block"
    >
      <nav class="relative h-full">
        <!-- Navigation boxes - 4 columns -->
        <div
          class="max-w-7xl mx-auto grid grid-cols-4 gap-2 items-start justify-center text-caspary-purple px-8"
        >
          <!-- Dynamic columns based on menu items -->
          <div
            v-for="(item, index) in menuItems"
            :key="item.title"
            class="bg-white shadow-xl py-6 px-4"
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
                      class="uppercase block py-2"
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
                        class="ml-4 block py-1"
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
                  class="uppercase block py-2"
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
    <div v-if="isMenuOpen" class="fixed inset-0 z-50 bg-white md:hidden">
      <!-- Mobile Menu Content -->
      <div class="h-full overflow-y-auto pb-20">
        <div class="px-6 py-2">
          <div
            v-for="item in mobileMenuItems"
            :key="item.title"
            class="border-t border-gray-600"
          >
            <button
              @click="toggleMobileSubmenu(item.title)"
              class="w-full flex items-center justify-between py-4 text-left"
            >
              <span
                class="text-lg tracking-wide uppercase"
                :class="{ 'text-black': item.isActive }"
                ><span v-if="item.isActive">\</span> {{ item.title }}</span
              >
              <span class="text-2xl">{{ item.isOpen ? "−" : "+" }}</span>
            </button>
            <div v-if="item.isOpen" class="pb-4">
              <ul class="space-y-3 pl-4">
                <li v-for="subItem in item.items" :key="subItem.title">
                  <a
                    v-if="!subItem.children"
                    :href="subItem.href"
                    @click="handleNavClick"
                    class="block text-gray-600 uppercase"
                    :class="{
                      'text-black': subItem.isActive,
                    }"
                  >
                    <span v-if="subItem.isActive">\</span> {{ subItem.title }}
                  </a>
                  <div v-else>
                    <button
                      @click="toggleMobileSubmenu(subItem.title)"
                      class="w-full flex items-center justify-between text-left"
                    >
                      <span
                        class="text-gray-600 uppercase"
                        :class="{
                          'text-black': subItem.isActive,
                        }"
                        ><span v-if="subItem.isActive">\</span> {{ subItem.title }}</span
                      >
                      <span class="text-xl text-gray-400">{{
                        subItem.isOpen ? "−" : "+"
                      }}</span>
                    </button>
                    <ul v-if="subItem.isOpen" class="mt-2 space-y-2 pl-4">
                      <li v-for="child in subItem.children" :key="child.title">
                        <a
                          :href="child.href"
                          @click="handleNavClick"
                          class="block text-gray-400"
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
      this.isMenuOpen = !this.isMenuOpen;
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
      return "/_Resources/Static/Packages/Caspary.Site/Images/menu-icon.svg";
    },
    logoUrl() {
      return "/_Resources/Static/Packages/Caspary.Site/Images/logo.svg";
    },
  },
  mounted() {
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

<style scoped>
.menu-wrapper {
  transform: translateY(14rem);
}

nav ul,
nav li {
  list-style: none;
}
</style>
