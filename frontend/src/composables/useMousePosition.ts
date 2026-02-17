import { ref, Ref, onMounted, onUnmounted } from 'vue'

export interface MousePosition {
    x: number
    y: number
}

export function useMousePosition(containerRef?: Ref<HTMLElement | null>) {
    const position = ref<MousePosition>({ x: 0, y: 0 })

    const updatePosition = (x: number, y: number) => {
        if (containerRef?.value) {
            const rect = containerRef.value.getBoundingClientRect()
            const centerX = rect.left + rect.width / 2
            const centerY = rect.top + rect.height / 2

            // Calculate position relative to center of container
            const relativeX = x - centerX
            const relativeY = y - centerY

            position.value = { x: relativeX, y: relativeY }
        } else {
            position.value = { x, y }
        }
    }

    const handleMouseMove = (ev: MouseEvent) => {
        updatePosition(ev.clientX, ev.clientY)
    }

    const handleTouchMove = (ev: TouchEvent) => {
        const touch = ev.touches[0]
        if (touch) {
            updatePosition(touch.clientX, touch.clientY)
        }
    }

    onMounted(() => {
        // Listen for both mouse and touch events
        window.addEventListener('mousemove', handleMouseMove)
        window.addEventListener('touchmove', handleTouchMove)
    })

    onUnmounted(() => {
        window.removeEventListener('mousemove', handleMouseMove)
        window.removeEventListener('touchmove', handleTouchMove)
    })

    return position
}
