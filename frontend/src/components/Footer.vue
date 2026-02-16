<script setup>
import { Facebook, Instagram, Linkedin, Twitter, Mail, Phone, MapPin, ArrowRight } from 'lucide-vue-next';

</script>

<template>
  <footer class="bg-[#0f172a] text-slate-300 py-20 relative overflow-hidden font-sans">
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-slate-700 to-transparent opacity-50"></div>
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#facc15]/5 rounded-full blur-[100px]"></div>
    <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-[#2563eb]/5 rounded-full blur-[120px]"></div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
        
        <!-- Column 1: Brand -->
        <div class="space-y-6">
          <div class="flex items-center space-x-3">
             <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center p-1">
                <img src="../assets/v-logo.png" alt="Valora" class="w-full h-full object-contain">
             </div>
             <span class="text-2xl font-playfair font-black text-white tracking-tight">Valora</span>
          </div>
          <p class="text-sm leading-relaxed text-slate-400">
            La première plateforme marocaine connectant les meilleurs artisans avec des clients exigeants. Qualité, confiance et rapidité.
          </p>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-[#facc15] hover:text-[#0f172a] transition-all duration-300">
              <Facebook class="w-4 h-4" />
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-[#facc15] hover:text-[#0f172a] transition-all duration-300">
              <Instagram class="w-4 h-4" />
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-[#facc15] hover:text-[#0f172a] transition-all duration-300">
              <Linkedin class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div>
          <h4 class="text-white font-bold text-lg mb-6 relative inline-block">
            Liens Rapides
            <span class="absolute -bottom-2 left-0 w-8 h-1 bg-[#facc15] rounded-full"></span>
          </h4>
          <ul class="space-y-3">
            <li><router-link to="/" class="hover:text-[#facc15] transition-colors flex items-center gap-2 group"><ArrowRight class="w-3 h-3 text-[#facc15] opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all" /> Accueil</router-link></li>
            <li><router-link to="/about" class="hover:text-[#facc15] transition-colors flex items-center gap-2 group"><ArrowRight class="w-3 h-3 text-[#facc15] opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all" /> À propos</router-link></li>
            <li><router-link to="/providers" class="hover:text-[#facc15] transition-colors flex items-center gap-2 group"><ArrowRight class="w-3 h-3 text-[#facc15] opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all" /> Services</router-link></li>
            <li><router-link to="/contact" class="hover:text-[#facc15] transition-colors flex items-center gap-2 group"><ArrowRight class="w-3 h-3 text-[#facc15] opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all" /> Contact</router-link></li>
          </ul>
        </div>

        <!-- Column 3: Services -->
        <div>
          <h4 class="text-white font-bold text-lg mb-6 relative inline-block">
            Services
            <span class="absolute -bottom-2 left-0 w-8 h-1 bg-[#facc15] rounded-full"></span>
          </h4>
          <ul class="space-y-3">
            <li><a href="#" class="hover:text-[#facc15] transition-colors">Bricolage & Réparations</a></li>
            <li><a href="#" class="hover:text-[#facc15] transition-colors">Plomberie & Électricité</a></li>
            <li><a href="#" class="hover:text-[#facc15] transition-colors">Déménagement</a></li>
            <li><a href="#" class="hover:text-[#facc15] transition-colors">Nettoyage & Entretien</a></li>
            <li><a href="#" class="hover:text-[#facc15] transition-colors">Jardinage</a></li>
          </ul>
        </div>

        <!-- Column 4: Newsletter/Contact -->
        <div>
          <h4 class="text-white font-bold text-lg mb-6 relative inline-block">
            Contactez-nous
            <span class="absolute -bottom-2 left-0 w-8 h-1 bg-[#facc15] rounded-full"></span>
          </h4>
          <ul class="space-y-4 mb-6">
            <li class="flex items-start gap-4">
              <MapPin class="w-5 h-5 text-[#facc15] shrink-0 mt-1" />
              <span class="text-sm">123 Boulevard Mohamed VI, Casablanca, Maroc</span>
            </li>
             <li class="flex items-center gap-4">
              <Phone class="w-5 h-5 text-[#facc15] shrink-0" />
              <span class="text-sm">+212 5 22 00 00 00</span>
            </li>
             <li class="flex items-center gap-4">
              <Mail class="w-5 h-5 text-[#facc15] shrink-0" />
              <span class="text-sm">contact@valora.ma</span>
            </li>
          </ul>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-xs text-slate-500">
          &copy; {{ new Date().getFullYear() }} Valora. Tous droits réservés.
        </p>
        <div class="flex space-x-6 text-xs text-slate-500">
          <a href="#" class="hover:text-white transition-colors">Confidentialité</a>
          <a href="#" class="hover:text-white transition-colors">CGU</a>
          <a href="#" class="hover:text-white transition-colors">Mentions Légales</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.font-playfair {
  font-family: 'Playfair Display', serif;
}
</style>
