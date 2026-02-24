<template>
  <div class="image-slider-component">
    <div class="relative">
      <div class="overflow-hidden rounded-lg">
        <div
          class="flex transition-transform duration-500 ease-in-out"
          :style="{ transform: `translateX(-${currentSlide * slideWidth}%)` }"
        >
          <div
            v-for="(image, index) in images"
            :key="index"
            class="slider-slide flex-shrink-0"
            :style="{ width: slideWidth + '%' }"
          >
            <div :class="aspectRatioClass + ' relative overflow-hidden'">
              <img
                :src="image.src"
                :alt="image.alt || ''"
                :title="image.title || ''"
                class="absolute inset-0 w-full h-full object-cover"
                loading="lazy"
              />
            </div>
          </div>
        </div>
      </div>

      <button
        v-if="showNavigation"
        type="button"
        class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow-lg transition-all z-10"
        @click="previousSlide"
        :disabled="!canGoPrevious"
        aria-label="Vorheriges Bild"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <button
        v-if="showNavigation"
        type="button"
        class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow-lg transition-all z-10"
        @click="nextSlide"
        :disabled="!canGoNext"
        aria-label="Nächstes Bild"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <div
        v-if="showDots"
        class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-10"
      >
        <button
          v-for="i in totalPages"
          :key="i"
          class="w-2 h-2 rounded-full transition-all"
          :class="currentPage === i - 1 ? 'bg-white w-8' : 'bg-white/50 hover:bg-white/75'"
          @click="goToPage(i - 1)"
          :aria-label="'Zu Seite ' + i + ' springen'"
        ></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageSlider',
  props: {
    slidesPerView: {
      type: Number,
      default: 3
    },
    aspectRatio: {
      type: String,
      default: '16-9'
    },
    autoplay: {
      type: Boolean,
      default: false
    },
    autoplayDelay: {
      type: Number,
      default: 3000
    },
    initialImages: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      currentSlide: 0,
      images: this.initialImages,
      autoplayInterval: null
    }
  },
  computed: {
    slideWidth() {
      return 100 / this.slidesPerView
    },
    totalSlides() {
      return this.images.length
    },
    maxSlide() {
      return Math.max(0, this.totalSlides - this.slidesPerView)
    },
    totalPages() {
      return Math.ceil(this.totalSlides / this.slidesPerView)
    },
    currentPage() {
      return Math.floor(this.currentSlide / this.slidesPerView)
    },
    showNavigation() {
      return this.totalSlides > this.slidesPerView
    },
    showDots() {
      return this.totalPages > 1
    },
    canGoPrevious() {
      return this.currentSlide > 0
    },
    canGoNext() {
      return this.currentSlide < this.maxSlide
    },
    aspectRatioClass() {
      const ratioMap = {
        '16-9': 'aspect-w-16 aspect-h-9',
        '4-3': 'aspect-w-4 aspect-h-3',
        '1-1': 'aspect-w-1 aspect-h-1',
        '3-4': 'aspect-w-3 aspect-h-4'
      }
      return ratioMap[this.aspectRatio] || 'aspect-w-16 aspect-h-9'
    }
  },
  mounted() {
    this.startAutoplay()
  },
  beforeUnmount() {
    this.stopAutoplay()
  },
  methods: {
    nextSlide() {
      if (this.canGoNext) {
        this.currentSlide = Math.min(this.currentSlide + this.slidesPerView, this.maxSlide)
      } else if (this.autoplay) {
        this.currentSlide = 0
      }
    },
    previousSlide() {
      if (this.canGoPrevious) {
        this.currentSlide = Math.max(this.currentSlide - this.slidesPerView, 0)
      }
    },
    goToPage(page) {
      this.currentSlide = Math.min(page * this.slidesPerView, this.maxSlide)
    },
    startAutoplay() {
      if (this.autoplay && this.showNavigation) {
        this.autoplayInterval = setInterval(() => {
          this.nextSlide()
        }, this.autoplayDelay)
      }
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
.slider-slide {
  padding: 0 0.5rem;
}
</style>
