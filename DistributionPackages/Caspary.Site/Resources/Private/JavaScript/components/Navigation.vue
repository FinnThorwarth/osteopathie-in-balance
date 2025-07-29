<template>
  <div class="navigation-wrapper">
    <button @click="toggleMenu" class="flex items-center p-2 hover:bg-gray-100 rounded">
      <img :src="menuIconUrl" alt="Menü" class="h-8">
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
          <!-- Column 1: IM TRAUERFALL -->
          <div class="bg-white border border-gray-300 shadow-xl p-6">
            <h3
              class="font-semibold font-headline uppercase mb-4 pb-4 border-b border-caspary-purple"
            >
              <a href="/im-trauerfall" @click="handleNavClick">Im Trauerfall</a>
            </h3>
          </div>

          <!-- Column 2: LEISTUNGEN -->
          <div class="bg-white border border-l-0 border-gray-300 shadow-xl p-6">
            <h3
              class="font-semibold font-headline uppercase mb-4 pb-4 border-b border-caspary-purple"
            >
              <a href="/leistungen" @click="handleNavClick">Leistungen</a>
            </h3>
            <ul class="space-y-3">
              <li>
                <button
                  @click="toggleSubmenu('bestattungsarten')"
                  class="flex items-center justify-between w-full uppercase hover:text-caspary-blue"
                >
                  <span>Bestattungsarten</span>
                  <span>{{ submenuOpen.bestattungsarten ? "−" : "+" }}</span>
                </button>
                <ul
                  v-if="submenuOpen.bestattungsarten"
                  class="ml-4 mt-2 space-y-2 text-base"
                >
                  <li>
                    <a
                      href="/bestattungsarten/feuerbestattung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Feuerbestattung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/erdbestattung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Erdbestattung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/seebestattung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Seebestattung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/waldbestattung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Wald-/Baumbestattung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/ascheausstreuung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Ascheausstreuung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/urnenbeisetzung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Urnenbeisetzung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/anonyme-beisetzung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Anonyme Beisetzung</a
                    >
                  </li>
                  <li>
                    <a
                      href="/bestattungsarten/halbanonyme-beisetzung"
                      @click="handleNavClick"
                      class="hover:text-caspary-blue"
                      >Halbanonyme Beisetzung</a
                    >
                  </li>
                </ul>
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/grundleistungen"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Grundleistungen</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/verstorbenenfuersorge"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Verstorbenenfürsorge</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/sarg"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Sarg</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/urne"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Urne</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/ueberfuehrungen"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Überführungen</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/ruheraum-aufbahrung"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Ruheraum & Aufbahrung</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/aussegnung"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Aussegnung</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/trauerdruck"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Trauerdruck</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/trauerfeier"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Trauerfeier</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/beisetzung"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Beisetzung</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/kosten"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Kosten</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <button
                  @click="toggleSubmenu('vorsorge')"
                  class="flex items-center justify-between w-full uppercase hover:text-caspary-blue"
                >
                  <span>Vorsorge</span>
                  <span>{{ submenuOpen.vorsorge ? "−" : "+" }}</span>
                </button>
              </li>
            </ul>
          </div>

          <!-- Column 3: VERANSTALTUNGEN -->
          <div class="bg-white border border-l-0 border-gray-300 shadow-xl p-6">
            <h3
              class="font-semibold font-headline uppercase mb-4 pb-4 border-b border-caspary-purple"
            >
              <a href="/veranstaltungen" @click="handleNavClick"
                >Veranstaltungen</a
              >
            </h3>
          </div>

          <!-- Column 4: WIR -->
          <div class="bg-white border border-l-0 border-gray-300 shadow-xl p-6">
            <h3
              class="font-semibold font-headline uppercase mb-4 pb-4 border-b border-caspary-purple"
            >
              Wir
            </h3>
            <ul class="space-y-3">
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/team"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Team</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/was-uns-ausmacht"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Was uns ausmacht</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/journal"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Journal</a
                >
              </li>
              <li class="border-b border-caspary-purple pb-3">
                <a
                  href="/kontakt"
                  @click="handleNavClick"
                  class="uppercase hover:text-caspary-blue"
                  >Kontakt</a
                >
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
            class="border-b border-gray-600"
          >
            <button
              @click="toggleMobileSubmenu(item.title)"
              class="w-full flex items-center justify-between py-4 text-left"
            >
              <span class="text-lg tracking-wide uppercase">{{
                item.title
              }}</span>
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
                  >
                    {{ subItem.title }}
                  </a>
                  <div v-else>
                    <button
                      @click="toggleMobileSubmenu(subItem.title)"
                      class="w-full flex items-center justify-between text-left"
                    >
                      <span class="text-gray-600 uppercase">{{
                        subItem.title
                      }}</span>
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
                        >
                          {{ child.title }}
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
  data() {
    return {
      isMenuOpen: false,
      submenuOpen: {
        bestattungsarten: false,
        vorsorge: false,
      },
      mobileMenuItems: [
        {
          title: "Im Trauerfall",
          href: "/im-trauerfall",
          isOpen: false,
          items: [],
        },
        {
          title: "Leistungen",
          isOpen: false,
          items: [
            {
              title: "Bestattungsarten",
              isOpen: false,
              children: [
                {
                  title: "Feuerbestattung",
                  href: "/bestattungsarten/feuerbestattung",
                },
                {
                  title: "Erdbestattung",
                  href: "/bestattungsarten/erdbestattung",
                },
                {
                  title: "Seebestattung",
                  href: "/bestattungsarten/seebestattung",
                },
                {
                  title: "Wald-/Baumbestattung",
                  href: "/bestattungsarten/waldbestattung",
                },
                {
                  title: "Ascheausstreuung",
                  href: "/bestattungsarten/ascheausstreuung",
                },
                {
                  title: "Urnenbeisetzung",
                  href: "/bestattungsarten/urnenbeisetzung",
                },
                {
                  title: "Anonyme Beisetzung",
                  href: "/bestattungsarten/anonyme-beisetzung",
                },
                {
                  title: "Halbanonyme Beisetzung",
                  href: "/bestattungsarten/halbanonyme-beisetzung",
                },
              ],
            },
            { title: "Grundleistungen", href: "/grundleistungen" },
            { title: "Verstorbenenfürsorge", href: "/verstorbenenfuersorge" },
            { title: "Sarg", href: "/sarg" },
            { title: "Urne", href: "/urne" },
            { title: "Überführungen", href: "/ueberfuehrungen" },
            { title: "Ruheraum & Aufbahrung", href: "/ruheraum-aufbahrung" },
            { title: "Aussegnung", href: "/aussegnung" },
            { title: "Trauerdruck", href: "/trauerdruck" },
            { title: "Trauerfeier", href: "/trauerfeier" },
            { title: "Beisetzung", href: "/beisetzung" },
            { title: "Kosten", href: "/kosten" },
          ],
        },
        {
          title: "Vorsorge",
          href: "/vorsorge",
          isOpen: false,
          items: [],
        },
        {
          title: "Veranstaltungen",
          href: "/veranstaltungen",
          isOpen: false,
          items: [],
        },
        {
          title: "Wir",
          isOpen: false,
          items: [
            { title: "Team", href: "/team" },
            { title: "Was uns ausmacht", href: "/was-uns-ausmacht" },
            { title: "Journal", href: "/journal" },
            { title: "Kontakt", href: "/kontakt" },
          ],
        },
      ],
    };
  },
  computed: {
    menuIconUrl() {
      return '/_Resources/Static/Packages/Caspary.Site/Images/menu-icon.svg';
    },
    logoUrl() {
      return '/_Resources/Static/Packages/Caspary.Site/Images/logo.svg';
    },
  },
  methods: {
    toggleMenu() {
      this.isMenuOpen = !this.isMenuOpen;
      this.updateBodyScroll();
    },
    closeMenu() {
      this.isMenuOpen = false;
      this.updateBodyScroll();
    },
    toggleSubmenu(menu) {
      this.submenuOpen[menu] = !this.submenuOpen[menu];
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
