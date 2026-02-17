<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { animate, stagger } from 'motion'
import ParallaxFloating from './ui/ParallaxFloating.vue'
import FloatingElement from './ui/FloatingElement.vue'

interface Props {
  title?: string
  subtitle?: string
  ctaText?: string
  ctaLink?: string
  sensitivity?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: 'VALORA',
  subtitle: 'Trouvez le prestataire parfait pour votre projet',
  ctaText: 'Découvrir nos services',
  ctaLink: '/search',
  sensitivity: -1,
})

const router = useRouter()
const heroRef = ref<HTMLElement | null>(null)
const contentRef = ref<HTMLElement | null>(null)

// Floating images showcasing Valora services
const floatingImages = [
  {
    url: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&q=80', // Nettoyage
    size: 'w-20 h-20 md:w-28 md:h-28',
    position: 'top-[8%] left-[8%]',
    depth: 0.5,
  },
  {
    url: 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=400&q=80', // Électricien
    size: 'w-24 h-24 md:w-32 md:h-32',
    position: 'top-[10%] right-[8%]',
    depth: 1.5,
  },
  {
    url: 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?w=400&q=80', // Peinture
    size: 'w-28 h-28 md:w-36 md:h-36',
    position: 'top-[35%] left-[3%]',
    depth: 2,
  },
  {
    url: 'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=400&q=80', // Plombier
    size: 'w-24 h-24 md:w-32 md:h-32',
    position: 'top-[50%] right-[5%]',
    depth: 1.5,
  },
  {
    url: 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=400&q=80', // Mécanique
    size: 'w-28 h-28 md:w-36 md:h-36',
    position: 'top-[30%] right-[15%]',
    depth: 2,
  },
  {
    url: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&q=80', // Jardinage
    size: 'w-24 h-24 md:w-30 md:h-30',
    position: 'top-[65%] left-[12%]',
    depth: 1,
  },
  {
    url: 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=400&q=80', // Agriculture
    size: 'w-28 h-28 md:w-36 md:h-36',
    position: 'top-[60%] right-[25%]',
    depth: 1.5,
  },
  {
    url: 'https://images.unsplash.com/photo-1600518464441-9154a4dea21b?w=400&q=80', // Déménagement
    size: 'w-26 h-26 md:w-34 md:h-34',
    position: 'bottom-[15%] left-[40%]',
    depth: 2,
  },
  {
    url: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&q=80', // Nettoyage (répété)
    size: 'w-20 h-20 md:w-26 md:h-26',
    position: 'bottom-[12%] right-[12%]',
    depth: 0.5,
  },
  {
    url: 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=400&q=80', // Électricien (répété)
    size: 'w-22 h-22 md:w-30 md:h-30',
    position: 'top-[75%] left-[50%] -translate-x-1/2',
    depth: 1,
  },
]

const navigateTo = () => {
  router.push(props.ctaLink)
}

onMounted(() => {
  // Animate images with staggered fade-in
  if (heroRef.value) {
    const images = heroRef.value.querySelectorAll('img')
    animate(
      images,
      { opacity: [0, 1] },
      { duration: 0.5, delay: stagger(0.15) }
    )
  }

  // Animate content
  if (contentRef.value) {
    animate(
      contentRef.value,
      { opacity: [0, 1], y: [10, 0] },
      { duration: 0.88, delay: 1.5 }
    )
  }
})
</script>

<template>
  <div
    ref="heroRef"
    class="relative flex w-full h-full min-h-[600px] md:min-h-screen justify-center items-center bg-dark-600 overflow-hidden"
  >
    <!-- Content -->
    <div
      ref="contentRef"
      class="z-50 text-center space-y-8 items-center flex flex-col px-4 opacity-0"
    >
      <!-- Premium Title with Gradient and Shimmer -->
      <div class="relative">
        <!-- Glow Effect Behind -->
        <div class="absolute inset-0 blur-3xl opacity-30">
          <h1 class="text-5xl md:text-7xl lg:text-9xl font-black bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-400 bg-clip-text text-transparent">
            {{ title }}
          </h1>
        </div>
        
        <!-- Main Title with Animated Gradient -->
        <h1 class="relative text-6xl md:text-8xl lg:text-9xl font-light tracking-wider leading-none italic animate-gradient bg-gradient-to-r from-slate-900 via-amber-600 to-slate-900 bg-[length:200%_auto] bg-clip-text text-transparent drop-shadow-2xl">
          {{ title }}
        </h1>
        
        <!-- Shimmer Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer pointer-events-none"></div>
      </div>

      <!-- Premium Subtitle with Elegant Typography -->
      <div class="relative max-w-3xl">
        <!-- Decorative Line Above -->
        <div class="flex items-center justify-center gap-4 mb-6">
          <div class="h-px w-12 bg-gradient-to-r from-transparent via-amber-500 to-transparent"></div>
          <div class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></div>
          <div class="h-px w-12 bg-gradient-to-r from-transparent via-amber-500 to-transparent"></div>
        </div>
        
        <p class="text-xl md:text-3xl text-slate-900 font-light tracking-wide leading-relaxed italic">
          <span class="inline-block animate-fade-in-up font-semibold bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 bg-clip-text text-transparent">Valorisez</span>
          <span class="inline-block animate-fade-in-up animation-delay-100 mx-1">l'effort qui</span>
          <span class="inline-block animate-fade-in-up animation-delay-200 font-semibold bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 bg-clip-text text-transparent">révèle</span>
          <span class="inline-block animate-fade-in-up animation-delay-300 mx-1">l'humain</span>
        </p>
        
        <!-- Decorative Line Below -->
        <div class="flex items-center justify-center gap-4 mt-6">
          <div class="h-px w-20 bg-gradient-to-l from-amber-500 via-transparent to-transparent"></div>
          <div class="w-2 h-2 rounded-full bg-amber-500 shadow-lg shadow-amber-500/50"></div>
          <div class="h-px w-20 bg-gradient-to-r from-amber-500 via-transparent to-transparent"></div>
        </div>
      </div>
    </div>

    <!-- Floating Images -->
    <ParallaxFloating :sensitivity="sensitivity" class="overflow-hidden">
      <FloatingElement
        v-for="(image, index) in floatingImages"
        :key="index"
        :depth="image.depth"
        :class="image.position"
      >
        <img
          :src="image.url"
          :alt="`Floating image ${index + 1}`"
          :class="[
            image.size,
            image.url.includes('v-logo') ? 'rounded-full' : 'rounded-lg',
            image.url.includes('v-logo') ? 'opacity-100 relative -mt-4' : 'opacity-0',
            'object-cover shadow-2xl hover:scale-105 duration-200 cursor-pointer transition-transform',
          ]"
          :style="image.url.includes('v-logo') ? 'background: transparent;' : ''"
          loading="lazy"
        />
      </FloatingElement>
    </ParallaxFloating>

    <!-- Subtle Overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-white/30 via-transparent to-white/50 pointer-events-none" />
  </div>
</template>

<style scoped>
/* Ensure smooth performance */
img {
  will-change: opacity, transform;
}

/* Premium Gradient Animation */
@keyframes gradient {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.animate-gradient {
  animation: gradient 8s ease infinite;
}

/* Shimmer Effect */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 3s ease-in-out infinite;
}

/* Fade In Up Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.8s ease-out forwards;
  opacity: 0;
}

/* Animation Delays */
.animation-delay-100 {
  animation-delay: 0.1s;
}

.animation-delay-200 {
  animation-delay: 0.2s;
}

.animation-delay-300 {
  animation-delay: 0.3s;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
