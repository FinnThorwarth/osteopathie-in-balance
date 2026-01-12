<template>
  <div class="team-carousel-component">
    <!-- Team Member Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      <button
        v-for="(member, index) in members"
        :key="index"
        type="button"
        class="team-member-card group focus:outline-none rounded-lg transition-all duration-300"
        :class="{ 'ring-2 ring-apfel-green ring-offset-2': activeIndex === index }"
        @click="selectMember(index)"
        @mouseenter="pauseAndSelect(index)"
        @mouseleave="resumeAutoplay"
        @focus="pauseAndSelect(index)"
        @blur="resumeAutoplay"
      >
        <div class="relative overflow-hidden mx-auto rounded-full w-32 h-32 sm:w-40 sm:h-40 md:w-48 md:h-48 lg:w-56 lg:h-56">
          <img
            v-if="member.image"
            :src="member.image"
            :alt="member.imageAlt || member.name"
            :title="member.imageTitle"
            class="w-full h-full object-cover transition-all duration-300"
            :class="{ 'grayscale-0 scale-105': activeIndex === index, 'grayscale opacity-70': activeIndex !== index }"
            loading="lazy"
          />
          <div
            v-else
            class="flex items-center justify-center w-full h-full bg-apfel-rose"
          >
            <svg class="w-16 h-16 md:w-24 md:h-24 text-apfel-olive opacity-30" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
        <!-- Name and Position below image -->
        <div class="mt-3 text-center">
          <h3 class="font-headline text-lg md:text-xl font-bold uppercase transition-colors duration-300"
              :class="activeIndex === index ? 'text-apfel-green' : 'text-apfel-text/70'">
            {{ member.name }}
          </h3>
          <p v-if="member.position" class="text-sm text-apfel-text/60">
            {{ member.position }}
          </p>
        </div>
      </button>
    </div>

    <!-- Quote Detail Zone -->
    <div
      v-if="activeMember && activeMember.quote"
      class="detail-zone mt-8 md:mt-12 relative"
    >
      <!-- Decorative line with quotation mark -->
      <div class="flex items-center justify-center mb-6">
        <div class="flex-1 h-px bg-apfel-text/20"></div>
        <div class="mx-4 text-5xl md:text-6xl text-apfel-rose font-serif leading-none select-none">"</div>
        <div class="flex-1 h-px bg-apfel-text/20"></div>
      </div>

      <!-- Quote content with fade transition -->
      <transition name="fade" mode="out-in">
        <div :key="activeIndex" class="text-center">
          <blockquote>
            <p class="font-body text-lg md:text-xl text-apfel-text max-w-3xl mx-auto">
              {{ activeMember.quote }}
            </p>
          </blockquote>
        </div>
      </transition>

      <!-- Progress indicators -->
      <div class="flex justify-center gap-2 mt-8">
        <button
          v-for="(member, index) in members"
          :key="'dot-' + index"
          type="button"
          class="h-2 rounded-full transition-all duration-300"
          :class="activeIndex === index ? 'bg-apfel-green w-6' : 'bg-apfel-olive/30 w-2 hover:bg-apfel-olive/50'"
          @click="selectMember(index)"
          :aria-label="'Zu ' + member.name + ' wechseln'"
        ></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TeamCarousel',
  props: {
    autoplayDelay: {
      type: Number,
      default: 5000
    }
  },
  data() {
    return {
      members: [],
      activeIndex: 0,
      autoplayInterval: null,
      isPaused: false
    }
  },
  computed: {
    activeMember() {
      return this.members[this.activeIndex] || null
    }
  },
  mounted() {
    this.extractMembers()
    this.startAutoplay()

    // Observe DOM changes for Neos backend editing
    const observer = new MutationObserver(() => {
      this.extractMembers()
    })

    if (this.$el.parentElement) {
      observer.observe(this.$el.parentElement, {
        childList: true,
        subtree: true,
        attributes: true
      })
    }

    this.$once?.('hook:beforeDestroy', () => {
      observer.disconnect()
      this.stopAutoplay()
    })
  },
  beforeUnmount() {
    this.stopAutoplay()
  },
  methods: {
    extractMembers() {
      // Extract member data from data attribute (set by Fusion)
      const dataAttr = this.$el.parentElement?.getAttribute('data-team-members')
      if (dataAttr) {
        try {
          this.members = JSON.parse(dataAttr)
          // Reset active index if out of bounds
          if (this.activeIndex >= this.members.length) {
            this.activeIndex = 0
          }
        } catch (e) {
          console.error('Failed to parse team members data:', e)
          this.members = []
        }
      }
    },
    selectMember(index) {
      this.activeIndex = index
    },
    pauseAndSelect(index) {
      this.isPaused = true
      this.stopAutoplay()
      this.selectMember(index)
    },
    resumeAutoplay() {
      this.isPaused = false
      this.startAutoplay()
    },
    startAutoplay() {
      if (this.autoplayInterval) return
      if (this.members.length <= 1) return

      this.autoplayInterval = setInterval(() => {
        if (!this.isPaused) {
          this.activeIndex = (this.activeIndex + 1) % this.members.length
        }
      }, this.autoplayDelay)
    },
    stopAutoplay() {
      if (this.autoplayInterval) {
        clearInterval(this.autoplayInterval)
        this.autoplayInterval = null
      }
    }
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.team-member-card {
  cursor: pointer;
}

.team-member-card img {
  transition: filter 0.3s ease, transform 0.3s ease, opacity 0.3s ease;
}
</style>
