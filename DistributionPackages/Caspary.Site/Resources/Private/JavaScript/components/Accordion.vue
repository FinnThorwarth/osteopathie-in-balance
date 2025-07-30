<template>
  <div class="content-accordion mb-12">
    <div class="space-y-4">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Accordion',
  data() {
    return {
      activeItem: null
    }
  },
  provide() {
    return {
      accordion: {
        activeItem: () => this.activeItem,
        setActiveItem: (id) => {
          this.activeItem = this.activeItem === id ? null : id
        }
      }
    }
  }
}
</script>