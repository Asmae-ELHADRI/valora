<script setup lang="ts">
import { ref, onMounted, onUnmounted, provide } from 'vue'
import { useMousePosition } from '../../composables/useMousePosition'

interface FloatingElement {
  element: HTMLDivElement
  depth: number
  currentPosition: { x: number; y: number }
}

interface FloatingContext {
  registerElement: (id: string, element: HTMLDivElement, depth: number) => void
  unregisterElement: (id: string) => void
}

interface Props {
  sensitivity?: number
  easingFactor?: number
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  sensitivity: 1,
  easingFactor: 0.02,
})

const containerRef = ref<HTMLElement | null>(null)
const elementsMap = new Map<string, FloatingElement>()
const mousePosition = useMousePosition(containerRef)
let animationFrameId: number | null = null

// Check for reduced motion preference
const prefersReducedMotion = ref(false)

onMounted(() => {
  if (typeof window !== 'undefined') {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    prefersReducedMotion.value = mediaQuery.matches
    
    mediaQuery.addEventListener('change', (e) => {
      prefersReducedMotion.value = e.matches
    })
  }
})

const registerElement = (id: string, element: HTMLDivElement, depth: number) => {
  elementsMap.set(id, {
    element,
    depth,
    currentPosition: { x: 0, y: 0 },
  })
}

const unregisterElement = (id: string) => {
  elementsMap.delete(id)
}

// Provide context for child components
provide<FloatingContext>('floatingContext', {
  registerElement,
  unregisterElement,
})

// Animation loop
const animate = () => {
  if (!containerRef.value || prefersReducedMotion.value) {
    animationFrameId = requestAnimationFrame(animate)
    return
  }

  elementsMap.forEach((data) => {
    const strength = (data.depth * props.sensitivity) / 20

    // Calculate new target position
    const newTargetX = mousePosition.value.x * strength
    const newTargetY = mousePosition.value.y * strength

    // Calculate delta
    const dx = newTargetX - data.currentPosition.x
    const dy = newTargetY - data.currentPosition.y

    // Update position with easing
    data.currentPosition.x += dx * props.easingFactor
    data.currentPosition.y += dy * props.easingFactor

    // Apply transform for GPU acceleration
    data.element.style.transform = `translate3d(${data.currentPosition.x}px, ${data.currentPosition.y}px, 0)`
  })

  animationFrameId = requestAnimationFrame(animate)
}

onMounted(() => {
  animationFrameId = requestAnimationFrame(animate)
})

onUnmounted(() => {
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId)
  }
  elementsMap.clear()
})
</script>

<template>
  <div
    ref="containerRef"
    :class="['absolute top-0 left-0 w-full h-full', props.class]"
  >
    <slot />
  </div>
</template>
