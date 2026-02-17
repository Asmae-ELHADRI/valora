<script setup lang="ts">
import { ref, inject, onMounted, onUnmounted } from 'vue'

interface FloatingContext {
  registerElement: (id: string, element: HTMLDivElement, depth: number) => void
  unregisterElement: (id: string) => void
}

interface Props {
  depth?: number
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  depth: 1,
})

const elementRef = ref<HTMLDivElement | null>(null)
const elementId = Math.random().toString(36).substring(7)
const context = inject<FloatingContext>('floatingContext')

onMounted(() => {
  if (elementRef.value && context) {
    const nonNullDepth = props.depth ?? 0.01
    context.registerElement(elementId, elementRef.value, nonNullDepth)
  }
})

onUnmounted(() => {
  if (context) {
    context.unregisterElement(elementId)
  }
})
</script>

<template>
  <div
    ref="elementRef"
    :class="['absolute will-change-transform', props.class]"
  >
    <slot />
  </div>
</template>
