<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import { Lock, Mail, Loader2, Search, Briefcase, User, MapPin, Camera, Settings, Facebook, Smartphone } from 'lucide-vue-next';
import authBg from '../assets/auth-bg.png';
import authRightBg from '../assets/auth-right-bg.jpg';
import valoraLogo from '../assets/logo-valora.png';

const auth = useAuthStore();
const router = useRouter();

// View State toggles
const activeRole = ref('client'); // 'client' or 'provider'
const isLoginMode = ref(true);
const isLoginHovered = ref(false);

// Error Handling
const error = ref('');
const loading = ref(false);

// Login Form State
const loginForm = ref({
    email: '',
    password: '',
    rememberMe: false
});

// Client Registration State
const clientForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

// Provider Registration State
const providerForm = ref({
    nom: '',
    prenom: '',
    email: '',
    password: '',
    password_confirmation: '',
    metier: '',
    expertise: '',
    ville: '',
    experience: '',
    tarif: '',
    description: ''
});

// Computed for simplified validation/loading
const isProcessing = computed(() => loading.value);

const handleLogin = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.login({ 
            email: loginForm.value.email, 
            password: loginForm.value.password 
        });
        router.push('/dashboard');
    } catch (err) {
        error.value = err.response?.data?.message || 'Identifiants incorrects';
    } finally {
        loading.value = false;
    }
};

const handleClientRegister = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.register({
            name: clientForm.value.name,
            email: clientForm.value.email,
            password: clientForm.value.password,
            password_confirmation: clientForm.value.password_confirmation,
            role: 'client'
        });
        router.push('/dashboard');
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors de l\'inscription';
    } finally {
        loading.value = false;
    }
};

const handleProviderRegister = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.register({
            name: `${providerForm.value.prenom} ${providerForm.value.nom}`,
            email: providerForm.value.email,
            password: providerForm.value.password,
            password_confirmation: providerForm.value.password_confirmation,
            role: 'provider',
            city: providerForm.value.ville,
            hourly_rate: providerForm.value.tarif,
            experience: providerForm.value.experience,
            description: providerForm.value.description
        });
        router.push('/dashboard');
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors de l\'inscription';
    } finally {
        loading.value = false;
    }
};

const cities = [
    "Casablanca", "Rabat", "Marrakech", "Fès", "Tanger", "Agadir", "Meknès", "Oujda", 
    "Kenitra", "Tetouan", "Safi", "Temara", "Inezgane", "Mohammedia", "Laayoune", 
    "Khouribga", "Beni Mellal", "El Jadida", "Taza", "Nador", "Settat", "Larache", 
    "Ksar El Kebir", "Khemisset", "Guelmim", "Berrechid", "Wad Zem", "Fquih Ben Salah", 
    "Taourirt", "Berkane", "Sidi Slimane", "Errachidia", "Sidi Kacem", "Khenifra", 
    "Tifelt", "Essaouira", "Taroudant", "El Kelaa des Sraghna", "Ouarzazate", "Sefrou", 
    "Souk El Arbaa", "Tan-Tan", "Ouazzane", "Guercif", "Dakhla", "Midelt", "Azrou", 
    "Tinghir", "Chefchaouen", "Jerada", "Mrirt"
].sort();

const jobs = [
    "Ménage", "Bricolage", "Jardinage", "Plomberie", "Electricité", "Peinture", 
    "Transport / Livraison", "Garde d'enfants", "Soins aux personnes agées", 
    "Informatique", "Mécanique", "Cuisine", "Serrurerie", "Menuiserie", "Autres"
];

const selectVille = (ville) => {
    providerForm.value.ville = ville;
};

// Social colors and icons customization if needed
</script>

<template>
  <div class="relative min-h-screen w-full flex flex-col md:flex-row bg-[#111827] overflow-x-hidden font-sans">
    <!-- Background Layer: Parallel Split -->
    <div class="fixed inset-0 z-0 flex flex-col md:flex-row overflow-hidden bg-[#111827]">
      <!-- Left Background Segment (Behind Login) -->
      <div 
        :class="[
          'w-full md:w-[45%] lg:w-[40%]',
          isLoginHovered ? 'opacity-100 scale-100' : 'opacity-40 scale-105'
        ]"
        class="h-full relative overflow-hidden transition-all duration-[1.2s] ease-out border-r border-white/5"
      >
        <img :src="authBg" alt="Login Background" class="absolute inset-0 w-full h-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/40 to-black/20"></div>
      </div>
      
      <!-- Right Background Segment (Behind Register) -->
      <div 
        class="hidden md:block flex-1 h-full relative overflow-hidden"
      >
        <!-- Artisan Workspace image from user -->
        <img 
          :src="authRightBg" 
          alt="Authentic Artisan Craftsmanship" 
          class="absolute inset-0 w-full h-full object-cover" 
        />
        <!-- Subtle dark overlay to ensure readability -->
        <div class="absolute inset-0 bg-black/30 pointer-events-none"></div>
      </div>
    </div>

    <!-- Left Section: Unified Login & App Branding -->
    <div 
        @mouseenter="isLoginHovered = true"
        @mouseleave="isLoginHovered = false"
        :class="activeRole === 'client' ? 'text-white' : 'text-gray-900'"
        class="relative z-10 w-full md:w-[45%] lg:w-[40%] flex flex-col p-6 sm:p-12 lg:p-20 justify-start pt-10 sm:pt-16 md:pt-20 min-h-screen transition-colors duration-1000"
    >
      <div class="mb-24 flex flex-col items-center md:items-start group">
        <!-- Outer Circle / Ring -->
        <div 
            :class="activeRole === 'client' ? 'border-[#D4A373]/30' : 'border-[#D4A373]/60'"
            class="p-2 border-2 rounded-full animate-pulse-slow transition-colors"
        >
            <!-- Inner Circle -->
            <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full flex items-center justify-center shadow-2xl bg-white border-4 border-white overflow-hidden relative">
                <img :src="valoraLogo" alt="Valora Logo" class="w-full h-full object-cover scale-126 transition-transform duration-700 group-hover:scale-110">
            </div>
        </div>
      </div>

      <div class="max-w-md w-full mx-auto md:mx-0">
        <h2 
            class="text-2xl font-bold mb-8 text-center md:text-left flex items-center space-x-3 text-white"
        >
             <div class="w-1 h-8 bg-[#D4A373] rounded-full hidden md:block"></div>
             <span>{{ $t('auth.login_title') }}</span>
        </h2>
        
        <form @submit.prevent="handleLogin" class="space-y-6">
            <div v-if="error" class="bg-red-500/20 backdrop-blur-md border border-red-500/50 text-red-200 p-4 rounded-2xl text-sm animate-pulse">
                {{ error }}
            </div>

            <div class="relative group">
                <input 
                    v-model="loginForm.email" 
                    type="text" 
                    required
                    :class="activeRole === 'client' ? 'bg-white/5 border-white/10 text-white focus:bg-white/10' : 'bg-black/5 border-black/10 text-gray-900 focus:bg-black/10'"
                    class="w-full border pl-5 pr-12 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-[#D4A373] transition-all duration-300 placeholder:text-gray-500 text-base"
                    :placeholder="$t('auth.email_placeholder')"
                >
                <Smartphone class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 group-focus-within:text-[#D4A373] transition-colors" />
            </div>

            <div class="relative group">
                <input 
                    v-model="loginForm.password" 
                    type="password" 
                    required
                    :class="activeRole === 'client' ? 'bg-white/5 border-white/10 text-white focus:bg-white/10' : 'bg-black/5 border-black/10 text-gray-900 focus:bg-black/10'"
                    class="w-full border pl-5 pr-12 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-[#D4A373] transition-all duration-300 placeholder:text-gray-500 text-base"
                    :placeholder="$t('auth.password_placeholder')"
                >
                <Lock class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 group-focus-within:text-[#D4A373] transition-colors" />
            </div>

            <div class="flex items-center justify-between px-2">
                <label 
                    :class="activeRole === 'client' ? 'text-gray-400 group-hover:text-white' : 'text-gray-600 group-hover:text-gray-900'"
                    class="flex items-center space-x-3 cursor-pointer group transition-colors"
                >
                    <div class="relative">
                        <input type="checkbox" v-model="loginForm.rememberMe" class="peer sr-only">
                        <div 
                            :class="activeRole === 'client' ? 'border-white/20' : 'border-black/20'"
                            class="w-5 h-5 border-2 rounded group-hover:border-[#D4A373]/50 transition-colors peer-checked:bg-[#D4A373] peer-checked:border-[#D4A373]"
                        ></div>
                        <svg class="absolute inset-0 w-5 h-5 text-white scale-0 peer-checked:scale-75 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ $t('auth.remember_me') }}</span>
                </label>
            </div>

            <button 
                type="submit" 
                :disabled="loading"
                class="w-full bg-[#D4A373] text-white py-4 rounded-full font-bold text-lg hover:bg-[#BC8F8F] transition-all duration-300 shadow-2xl shadow-amber-900/40 hover:scale-[1.02] active:scale-95 flex items-center justify-center space-x-3 disabled:opacity-50"
            >
                <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
                <span>{{ loading ? $t('auth.logging_in') : $t('auth.login_button') }}</span>
            </button>
        </form>

        <!-- Social Logins -->
        <div class="mt-10">
            <div class="flex items-center space-x-6 justify-center md:justify-start">
                <button 
                    @click="auth.socialLogin('google')" 
                    :class="activeRole === 'client' ? 'text-white/60 hover:text-white' : 'text-gray-500 hover:text-gray-900'"
                    class="flex items-center space-x-2 transition group font-sans"
                >
                   <img src="https://www.google.com/favicon.ico" class="w-4 h-4 grayscale group-hover:grayscale-0 transition-all opacity-60 group-hover:opacity-100" alt="Google">
                   <span class="text-sm font-medium italic">{{ $t('auth.social_google') }}</span>
                </button>
                <button 
                    @click="auth.socialLogin('facebook')" 
                    :class="activeRole === 'client' ? 'text-white/60 hover:text-white' : 'text-gray-500 hover:text-gray-900'"
                    class="flex items-center space-x-2 transition group font-sans"
                >
                   <div class="bg-blue-600 p-1 rounded-full w-5 h-5 flex items-center justify-center">
                       <span class="text-[12px] font-black text-white">f</span>
                   </div>
                   <span class="text-sm font-medium italic">{{ $t('auth.social_facebook') }}</span>
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Right Section: Welcome & Contextual Registration -->
    <div class="relative z-10 w-full md:w-[55%] lg:w-[60%] flex flex-col p-6 sm:p-12 lg:p-20 items-center justify-start pt-20 sm:pt-32 md:pt-40 overflow-y-auto">
        <div class="max-w-xl w-full">
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
                    {{ $t('auth.welcome_title') }} <span class="text-[#D4A373]">Valora</span>
                </h2>
                <p class="text-xl md:text-2xl text-white/70 font-medium">{{ $t('auth.what_to_do') }}</p>
            </div>

            <!-- Role Selection Tabs -->
            <div class="grid grid-cols-2 gap-4 mb-10">
                <button 
                    @click="activeRole = 'client'"
                    :class="activeRole === 'client' ? 'bg-[#5697d5] border-[#5697d5] ring-4 ring-[#5697d5]/20' : 'bg-white/5 border-white/10 text-white hover:bg-white/10'"
                    class="flex-1 flex items-center justify-center space-x-3 py-5 px-6 rounded-[2rem] font-bold transition-all duration-400 text-white border"
                >
                    <Search class="w-6 h-6" />
                    <span>{{ $t('auth.role_client') }}</span>
                </button>
                <button 
                    @click="activeRole = 'provider'"
                    :class="activeRole === 'provider' ? 'bg-[#D4A373] border-[#D4A373] ring-4 ring-[#D4A373]/20' : 'bg-white/5 border-white/10 text-white hover:bg-white/10'"
                    class="flex-1 flex items-center justify-center space-x-3 py-5 px-6 rounded-[2rem] font-bold transition-all duration-400 text-white border"
                >
                    <Briefcase class="w-6 h-6" />
                    <span>{{ $t('auth.role_provider') }}</span>
                </button>
            </div>

            <!-- Forms Layer with Transitions -->
            <Transition name="slide-up" mode="out-in">
                <!-- Provider Form Card -->
                <div v-if="activeRole === 'provider'" class="bg-white rounded-[2.5rem] p-8 sm:p-10 shadow-2xl relative overflow-hidden ring-1 ring-white/20">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#D4A373]/10 rounded-full blur-2xl"></div>
                    <div class="absolute top-8 right-8 text-[#D4A373]/20">
                         <div class="w-12 h-12 border-4 border-current border-t-transparent rounded-full animate-spin-slow"></div>
                         <Settings class="absolute inset-0 m-auto w-6 h-6" />
                    </div>
                    
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter mb-1">{{ $t('auth.provider_form_title') }}</h3>
                    <p class="text-sm text-[#D4A373] font-bold italic mb-10 drop-shadow-sm">{{ $t('auth.provider_subtitle') }}</p>

                    <form @submit.prevent="handleProviderRegister" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.name') }}</label>
                                <input v-model="providerForm.nom" type="text" required class="w-full border-b-2 border-gray-100 focus:border-[#D4A373] py-2 px-1 outline-none transition-all font-semibold text-gray-800 text-base bg-transparent">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.firstname') }}</label>
                                <input v-model="providerForm.prenom" type="text" required class="w-full border-b-2 border-gray-100 focus:border-[#D4A373] py-2 px-1 outline-none transition-all font-semibold text-gray-800 text-base bg-transparent">
                            </div>
                        </div>

                        <!-- Credentials block -->
                        <div class="space-y-4">
                            <div class="relative group">
                                <input v-model="providerForm.email" type="email" :placeholder="$t('auth.email')" class="w-full bg-gray-50 border border-gray-100 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#D4A373]/20 text-sm font-bold text-gray-700">
                                <Mail class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="relative group">
                                    <input v-model="providerForm.password" type="password" :placeholder="$t('auth.password')" class="w-full bg-gray-50 border border-gray-100 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#D4A373]/20 text-sm font-bold text-gray-700">
                                    <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                </div>
                                <div class="relative group">
                                    <input v-model="providerForm.password_confirmation" type="password" :placeholder="$t('auth.confirm_password')" class="w-full bg-gray-50 border border-gray-100 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#D4A373]/20 text-sm font-bold text-gray-700">
                                    <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.job') }}</label>
                                <select v-model="providerForm.metier" class="w-full bg-gray-50 border border-gray-100 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#D4A373]/20 text-sm font-bold text-gray-700 cursor-pointer hover:bg-gray-100 transition-colors">
                                    <option value="" disabled>{{ $t('auth.select_job') }}</option>
                                    <option v-for="job in jobs" :key="job" :value="job">{{ job }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.expertise') }}</label>
                                <div class="relative">
                                    <input v-model="providerForm.expertise" type="text" class="w-full bg-gray-50 border border-gray-100 rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#D4A373]/20 text-sm font-bold text-gray-700" placeholder="Ex: Peinture, Electricité">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.city') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <div class="relative flex-1 min-w-[120px]">
                                    <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <select v-model="providerForm.ville" class="w-full bg-gray-50 border border-gray-100 rounded-xl py-3 pl-9 pr-3 text-sm font-bold outline-none focus:border-[#D4A373] appearance-none cursor-pointer">
                                        <option value="" disabled>{{ $t('auth.select_city') }}</option>
                                        <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest pl-1">{{ $t('auth.experience_label') }}</label>
                            <div class="relative group">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                                    <Search class="w-5 h-5 text-gray-300 group-focus-within:text-[#D4A373] transition-colors" />
                                </div>
                                <input v-model="providerForm.experience" type="text" class="w-full bg-gray-50 border border-gray-100 rounded-[1.25rem] py-4 pl-12 pr-4 outline-none focus:ring-2 focus:ring-[#D4A373]/20 focus:bg-white transition-all text-xs font-medium text-gray-600 italic" :placeholder="$t('auth.experience_placeholder')">
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center sm:space-x-4 p-4 bg-blue-50/50 rounded-2xl border border-blue-100/30 gap-4">
                            <button type="button" class="flex items-center space-x-3 text-blue-800 hover:translate-x-1 transition-transform">
                                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm">
                                    <Camera class="w-5 h-5 text-blue-600" />
                                </div>
                                <span class="text-xs font-black uppercase tracking-tight">{{ $t('auth.add_photos') }}</span>
                            </button>
                            <div class="flex-1"></div>
                            <div class="flex items-center space-x-2 bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                                <span class="text-[9px] uppercase font-black text-gray-400 pl-2">{{ $t('auth.rate_label') }}</span>
                                <input v-model="providerForm.tarif" type="text" class="w-24 py-2 px-3 text-sm font-black text-center text-gray-900 outline-none" placeholder="100 MAD">
                            </div>
                        </div>


                        <button 
                            type="submit"
                            :disabled="loading"
                            class="w-full bg-[#D4A373] text-white py-5 rounded-[1.5rem] font-black uppercase text-sm tracking-[0.2em] hover:bg-[#BC8F8F] transition-all shadow-xl shadow-amber-100 hover:scale-[1.01] active:scale-95 disabled:opacity-50"
                        >
                            {{ loading ? $t('auth.signing_up') : $t('auth.signup_provider') }}
                        </button>
                    </form>
                </div>

                <!-- Client Form Card -->
                <div v-else class="bg-white/10 backdrop-blur-xl rounded-[2.5rem] p-10 border border-white/20 shadow-2xl w-full">
                    <div class="flex items-center space-x-4 mb-8">
                         <div class="w-12 h-12 bg-blue-500 rounded-2xl flex items-center justify-center text-white shadow-lg">
                             <User class="w-6 h-6" />
                         </div>
                          <div>
                             <h3 class="text-xl font-bold text-white">{{ $t('auth.client_account') }}</h3>
                             <p class="text-blue-200 text-sm italic">{{ $t('auth.client_subtitle') }}</p>
                         </div>
                    </div>

                    <form @submit.prevent="handleClientRegister" class="space-y-6">
                          <div class="space-y-4">
                              <div class="relative group">
                                  <input v-model="clientForm.name" type="text" :placeholder="$t('auth.fullname')" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-5 outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                                  <User class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
                              </div>
                              <div class="relative group">
                                  <input v-model="clientForm.email" type="email" :placeholder="$t('auth.email')" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-5 outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                                  <Mail class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
                              </div>
                              <div class="grid grid-cols-2 gap-4">
                                  <input v-model="clientForm.password" type="password" :placeholder="$t('auth.password')" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-5 outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                                  <input v-model="clientForm.password_confirmation" type="password" :placeholder="$t('auth.confirm_password')" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-5 outline-none focus:ring-2 focus:ring-blue-400 transition-all">
                              </div>
                         </div>

                         <div class="grid grid-cols-2 gap-6 mt-8">
                              <div class="flex items-center space-x-3 bg-white/5 p-4 rounded-2xl border border-white/10">
                                  <div class="p-2 bg-blue-500/20 rounded-lg"><Settings class="w-4 h-4 text-blue-400" /></div>
                                  <span class="text-xs font-bold text-white italic">{{ $t('auth.certified_services') }}</span>
                              </div>
                              <div class="flex items-center space-x-3 bg-white/5 p-4 rounded-2xl border border-white/10">
                                  <div class="p-2 bg-amber-500/20 rounded-lg"><User class="w-4 h-4 text-amber-400" /></div>
                                  <span class="text-xs font-bold text-white italic">{{ $t('auth.verified_profiles') }}</span>
                              </div>
                         </div>

                          <button type="submit" :disabled="loading" class="w-full bg-blue-600 text-white py-5 rounded-[1.5rem] font-bold text-lg hover:bg-blue-700 hover:scale-[1.02] transition-all shadow-xl shadow-blue-900/40">
                              {{ $t('auth.create_client_account') }}
                          </button>
                    </form>
                </div>
            </Transition>
        </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100;400;700;900&display=swap');

.font-sans {
    font-family: 'Outfit', sans-serif;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
    opacity: 0;
    transform: translateY(40px) scale(0.98);
}
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(-40px) scale(0.98);
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 1s ease-in-out;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.animate-spin-slow {
    animation: spin 8s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-pulse-slow {
    animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.03); }
}

@keyframes flow-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(10%, 15%) scale(1.1); }
    66% { transform: translate(-5%, 10%) scale(0.9); }
}

@keyframes flow-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-15%, -10%) scale(0.9); }
    66% { transform: translate(10%, -15%) scale(1.2); }
}

.animate-flow-1 { animation: flow-1 15s ease-in-out infinite; }
.animate-flow-2 { animation: flow-2 20s ease-in-out infinite; }

/* Custom Scrollbar for the form section */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

input::placeholder {
    font-style: italic;
    opacity: 0.6;
}
</style>
