<template>
  <div class="accordion-item border border-gray-200 rounded-lg overflow-hidden">
    <button
      type="button"
      class="accordion-header w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors"
      @click="toggle"
      :aria-expanded="isOpen"
    >
      <span class="text-lg font-semibold">{{ title }}</span>
      <svg
        class="w-5 h-5 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    <transition
      name="accordion"
      @before-enter="beforeEnter"
      @enter="enter"
      @before-leave="beforeLeave"
      @leave="leave"
    >
      <div v-show="isOpen" class="accordion-content">
        <div class="px-6 py-4 prose prose-lg max-w-none">
          <slot></slot>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'AccordionItem',
  props: {
    title: {
      type: String,
      required: true
    },
    itemId: {
      type: String,
      required: true
    },
    defaultOpen: {
      type: Boolean,
      default: false
    }
  },
  inject: ['accordion'],
  computed: {
    isOpen() {
      return this.accordion.activeItem() === this.itemId || (this.defaultOpen && !this.accordion.activeItem())
    }
  },
  methods: {
    toggle() {
      this.accordion.setActiveItem(this.itemId)
    },
    beforeEnter(el) {
      el.style.height = '0'
    },
    enter(el) {
      el.style.height = el.scrollHeight + 'px'
      el.style.transition = 'height 0.3s ease'
    },
    beforeLeave(el) {
      el.style.height = el.scrollHeight + 'px'
    },
    leave(el) {
      el.style.transition = 'height 0.3s ease'
      el.style.height = '0'
    }
  }
}
</script>

<style scoped>
.accordion-enter-active,
.accordion-leave-active {
  overflow: hidden;
}
</style>