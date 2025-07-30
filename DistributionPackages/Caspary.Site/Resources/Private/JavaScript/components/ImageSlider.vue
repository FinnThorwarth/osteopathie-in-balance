<template>
  <div class="content-image-slider mb-12">
    <div class="relative">
      <div class="overflow-hidden">
        <div 
          class="slider-container flex transition-transform duration-300"
          :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
          <slot></slot>
        </div>
      </div>
      
      <!-- Navigation buttons -->
      <button
        v-if="slidesPerView < totalSlides"
        type="button"
        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 bg-white rounded-full p-2 shadow-lg hover:shadow-xl transition-shadow disabled:opacity-50"
        @click="previousSlide"
        :disabled="currentSlide === 0"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      
      <button
        v-if="slidesPerView < totalSlides"
        type="button"
        class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 bg-white rounded-full p-2 shadow-lg hover:shadow-xl transition-shadow disabled:opacity-50"
        @click="nextSlide"
        :disabled="currentSlide >= maxSlide"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
      
      <!-- Dots indicator -->
      <div v-if="showDots && totalSlides > slidesPerView" class="flex justify-center mt-4 space-x-2">
        <button
          v-for="i in totalPages"
          :key="i"
          class="w-2 h-2 rounded-full transition-colors"
          :class="currentSlide === (i - 1) ? 'bg-gray-800' : 'bg-gray-300'"
          @click="goToSlide(i - 1)"
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
    showDots: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      currentSlide: 0,
      totalSlides: 0
    }
  },
  computed: {
    maxSlide() {
      return Math.max(0, this.totalSlides - this.slidesPerView)
    },
    totalPages() {
      return Math.ceil(this.totalSlides / this.slidesPerView)
    }
  },
  mounted() {
    this.countSlides()
    
    // Watch for changes in slot content
    const observer = new MutationObserver(() => {
      this.countSlides()
    })
    
    observer.observe(this.$el, {
      childList: true,
      subtree: true
    })
    
    this.$once('hook:beforeDestroy', () => {
      observer.disconnect()
    })
  },
  methods: {
    countSlides() {
      this.totalSlides = this.$el.querySelectorAll('.slider-item').length
    },
    nextSlide() {
      if (this.currentSlide < this.maxSlide) {
        this.currentSlide++
      }
    },
    previousSlide() {
      if (this.currentSlide > 0) {
        this.currentSlide--
      }
    },
    goToSlide(index) {
      this.currentSlide = Math.min(index, this.maxSlide)
    }
  }
}
</script>