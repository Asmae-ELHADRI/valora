<script setup>
import { ArrowRight, Shield, Star, Zap, Users, Briefcase } from 'lucide-vue-next';
</script>

<template>
  <div class="relative overflow-hidden bg-white">
    <!-- Hero Section -->
    <div class="relative pt-16 pb-32 sm:pt-24 sm:pb-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
            <span class="block">VALORA</span>
            <span class="block text-blue-600 mt-2">« C’est dans l’effort que l’humain se révèle »</span>
          </h1>
          <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-500">
            La plateforme qui simplifie et sécurise la mise en relation entre prestataires de talents et clients exigeants.
          </p>
          <div class="mt-10 flex justify-center space-x-6">
            <router-link to="/register" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 transition duration-300">
              Commencer maintenant
            </router-link>
            <router-link to="/search" class="px-8 py-4 bg-white text-gray-700 border border-gray-200 rounded-2xl font-bold hover:bg-gray-50 transition duration-300">
              Explorer les services
            </router-link>
          </div>
        </div>
      </div>

      <!-- Decorative abstract shapes -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full -z-10 opacity-10 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-400 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-400 rounded-full blur-[120px]"></div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-gray-50 py-20 border-y border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div>
            <p class="text-3xl font-bold text-gray-900">5k+</p>
            <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider font-semibold">Prestataires</p>
          </div>
          <div>
            <p class="text-3xl font-bold text-gray-900">12k+</p>
            <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider font-semibold">Missions réussies</p>
          </div>
          <div>
            <p class="text-3xl font-bold text-gray-900">4.9/5</p>
            <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider font-semibold">Note moyenne</p>
          </div>
          <div>
            <p class="text-3xl font-bold text-gray-900">24/7</p>
            <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider font-semibold">Support</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="py-32">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-20">
          <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">Pourquoi choisir VALORA ?</h2>
          <p class="mt-4 text-gray-500">Une expérience fluide, sécurisée et valorisante pour tous.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div class="group p-8 rounded-3xl bg-white border border-gray-100 hover:border-blue-100 hover:shadow-2xl hover:shadow-blue-50 transition duration-500">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
              <Shield class="w-7 h-7 text-blue-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Sécurité Garantie</h3>
            <p class="text-gray-500 leading-relaxed">
              Tous les échanges et paiements sont sécurisés. Profils vérifiés pour une tranquillité d'esprit totale.
            </p>
          </div>

          <div class="group p-8 rounded-3xl bg-white border border-gray-100 hover:border-purple-100 hover:shadow-2xl hover:shadow-purple-50 transition duration-500">
            <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
              <Star class="w-7 h-7 text-purple-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Excellence Reconnue</h3>
            <p class="text-gray-500 leading-relaxed">
              Le système d'évaluation permet de mettre en avant les meilleurs prestataires et les clients sérieux.
            </p>
          </div>

          <div class="group p-8 rounded-3xl bg-white border border-gray-100 hover:border-green-100 hover:shadow-2xl hover:shadow-green-50 transition duration-500">
            <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
              <Zap class="w-7 h-7 text-green-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Simplicité & Rapidité</h3>
            <p class="text-gray-500 leading-relaxed">
              Trouvez un service ou postulez à une offre en quelques clics grâce à notre interface intuitive.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gray-900 py-24 mx-4 mb-20 rounded-[40px] relative overflow-hidden">
      <div class="absolute top-0 right-0 w-1/2 h-full bg-blue-600 translate-x-1/2 -skew-x-12 opacity-10"></div>
      <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-8">Prêt à transformer vos efforts en succès ?</h2>
        <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
          <router-link to="/register" class="w-full sm:w-auto px-10 py-5 bg-white text-gray-900 rounded-2xl font-bold hover:bg-gray-100 transition flex items-center justify-center">
            Créer un compte <ArrowRight class="ml-2 w-5 h-5" />
          </router-link>
          <div class="text-gray-400 font-medium">Devenez prestataire ou trouvez un service</div>
        </div>
      </div>
    </div>
  </div>
</template>
