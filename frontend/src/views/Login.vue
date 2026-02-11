<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { onMounted } from 'vue';
import { 
  Lock, Mail, Loader2, Search, Briefcase, User, MapPin, Camera, 
  Settings, Star, Sparkles, ArrowRight, Shield, Smartphone, PenTool,
  Hammer, Paintbrush, Zap, Scissors, Sprout, Wrench, Utensils,
  Eye, EyeOff
} from 'lucide-vue-next';
import valoraLogo from '../assets/v-logo.png';
import artisanBg from '../assets/auth-right-bg.jpg';

const orbitIcons = [
  Hammer, Wrench, Paintbrush, PenTool, Zap, Scissors, Sprout, Camera
];

const isVisible = ref(false);

const auth = useAuthStore();
const router = useRouter();

// View State toggles
const activeRole = ref('client'); // 'client' or 'provider'
const isLoginMode = ref(true);
const categories = ref([]);

const fetchCategories = async () => {
    try {
        const response = await api.get('/api/offers/categories');
        categories.value = response.data;
    } catch (err) {
        console.error('Erreur lors du chargement des catégories:', err);
    }
};

onMounted(() => {
    fetchCategories();
    isVisible.value = true;
});

// Error Handling
const error = ref('');
const loading = ref(false);
const registrationSuccess = ref(false);

// Login Form State
const loginForm = ref({
    email: '',
    password: '',
    rememberMe: false
});

// Client Registration State
const clientForm = ref({
    nom: '',
    prenom: '',
    email: '',
    password: '',
    password_confirmation: '',
    city: ''
});

// Provider Registration State
const providerForm = ref({
    nom: '',
    prenom: '',
    email: '',
    password: '',
    password_confirmation: '',
    city: ''
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const togglePassword = () => showPassword.value = !showPassword.value;
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value;

const isProcessing = computed(() => loading.value);

const getErrorMessage = (err, defaultMsg) => {
    if (err.response?.data?.errors) {
        const errors = err.response.data.errors;
        const firstField = Object.keys(errors)[0];
        return errors[firstField][0];
    }
    return err.response?.data?.message || defaultMsg;
};

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
        const message = err.response?.data?.message;
        if (message === 'Votre compte a été désactivé par un administrateur.') {
            error.value = 'auth.account_disabled';
        } else if (err.response?.status === 422 || err.response?.status === 401) {
            error.value = 'auth.invalid_credentials';
        } else {
            error.value = getErrorMessage(err, 'auth.invalid_credentials');
        }
    } finally {
        loading.value = false;
    }
};

const handleClientRegister = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.register({
            name: `${clientForm.value.prenom} ${clientForm.value.nom}`,
            email: clientForm.value.email,
            password: clientForm.value.password,
            password_confirmation: clientForm.value.password_confirmation,
            city: clientForm.value.city,
            role: 'client'
        });
        registrationSuccess.value = true;
        setTimeout(() => router.push('/dashboard'), 1200);
    } catch (err) {
        error.value = getErrorMessage(err, 'auth.register_error');
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
            city: providerForm.value.city,
            role: 'provider'
        });
        registrationSuccess.value = true;
        setTimeout(() => router.push('/dashboard'), 1200);
    } catch (err) {
        error.value = getErrorMessage(err, 'auth.register_error');
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
</script>

<template>
  <div class="relative min-h-screen w-full flex items-center justify-center p-4 sm:p-6 overflow-hidden font-outfit">
    
    <!-- Real Artisan Background with Premium Overlay -->
    <div class="absolute inset-0 z-0">
      <img :src="artisanBg" alt="Artisan Background" class="w-full h-full object-cover scale-105 animate-slow-zoom">
      <div class="absolute inset-0 bg-linear-to-br from-premium-blue/90 via-premium-blue/70 to-premium-brown/80 backdrop-blur-[2px]"></div>
      <!-- Animated Mesh Polish -->
      <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_50%_50%,rgba(250,204,21,0.1),transparent_50%)] animate-pulse"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        
        <!-- Left Side: Branding & Artisan Values -->
        <div class="hidden lg:flex flex-col space-y-10 animate-fade-in px-8">
           <div class="flex items-center space-x-12 relative">
              <!-- Shared Logo Structure scaled up for Login -->
              <div class="logo-container scale-[2.2] origin-left ml-4">
                 <LanguageSwitcher class="absolute -top-6 -left-6 z-50 scale-50" />
                 
                 <img :src="valoraLogo" alt="VALORA Logo" class="logo-image" />
                 <span class="text-3xl font-black tracking-tight logo-text logo-text-brown">
                   VALORA
                 </span>

                 <!-- Orbiting Icons attached to the shared container -->
                 <div>
                 </div>
              </div>
           </div>

            <div class="space-y-8">
               <div class="inline-flex items-center space-x-3 px-6 py-3 bg-premium-yellow/10 border border-premium-yellow/20 text-premium-yellow rounded-full backdrop-blur-sm">
                  <span class="text-[10px] font-black uppercase tracking-[0.3em]">{{ $t('auth.vision_badge') }}</span>
               </div>
               
                <h2 class="text-7xl font-black text-white tracking-tighter leading-[0.85]" v-html="$t('home.slogan')"></h2>

               <div class="space-y-6 max-w-lg">
      
                  
                  <div class="relative p-8 bg-white/5 backdrop-blur-xl rounded-[2.5rem] border border-white/10 shadow-2xl overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <PenTool class="w-12 h-12 text-white" />
                    </div>
                    <h4 class="text-white font-black uppercase text-xs tracking-widest mb-3 flex items-center">
                        <span class="w-8 h-px bg-premium-yellow mr-3"></span>
                        {{ $t('auth.concept_title') }}
                    </h4>
                    <p class="text-sm text-white/70 leading-relaxed font-light italic">
                      "Chez Valora, nous croyons que derrière chaque service se cache une expertise unique. Notre plateforme n'est pas qu'un simple outil de mise en relation, c'est un tremplin qui met en lumière la dimension humaine et l'excellence de l'artisanat marocain."
                    </p>
                  </div>
               </div>
            </div>

           <div class="grid grid-cols-2 gap-6 pt-4">
              <div class="p-6 bg-white/10 backdrop-blur-xl rounded-4xl border border-white/20 shadow-2xl group hover:-translate-y-2 transition-all">
                 <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-premium-yellow transition-colors">
                    <Shield class="w-6 h-6 text-white group-hover:text-premium-blue" />
                 </div>
                 <h4 class="font-black text-white mb-1 uppercase text-xs tracking-widest">{{ $t('common.security', 'Sécurité') }}</h4>
                 <p class="text-white/60 text-[10px] font-bold">{{ $t('auth.security_check') }}</p>
              </div>
              <div class="p-6 bg-white/10 backdrop-blur-xl rounded-4xl border border-white/20 shadow-2xl group hover:-translate-y-2 transition-all">
                 <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-premium-yellow transition-colors">
                    <Star class="w-6 h-6 text-white group-hover:text-premium-blue" />
                 </div>
                 <h4 class="font-black text-white mb-1 uppercase text-xs tracking-widest">{{ $t('common.quality', 'Qualité') }}</h4>
                 <p class="text-white/60 text-[10px] font-bold">{{ $t('auth.quality_check') }}</p>
              </div>
           </div>
        </div>

        <!-- Right Side: Unified Auth Card -->
        <div class="relative animate-fade-in animation-delay-300">
           <div class="relative bg-white/90 backdrop-blur-3xl border border-white/40 rounded-[3.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] p-8 sm:p-14 overflow-hidden">
              
              <!-- Subtle decorative elements inside the card -->
              <div class="absolute -top-24 -right-24 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl"></div>
              
              <!-- Tabs for Client/Provider switching -->
              <div class="flex p-1.5 bg-slate-200/50 backdrop-blur-lg rounded-2xl mb-12 w-full max-w-sm mx-auto shadow-inner border border-slate-200/50">
                 <button 
                  @click="activeRole = 'client'" 
                  :class="activeRole === 'client' ? 'bg-white shadow-xl text-premium-blue' : 'text-slate-500 hover:text-slate-700'"
                  class="flex-1 py-3.5 px-4 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-500 flex items-center justify-center space-x-2"
                 >
                    <User class="w-4 h-4" />
                    <span>{{ $t('auth.role_client') }}</span>
                 </button>
                 <button 
                  @click="activeRole = 'provider'" 
                  :class="activeRole === 'provider' ? 'bg-premium-blue text-white shadow-2xl shadow-premium-blue/40' : 'text-slate-500 hover:text-slate-700'"
                  class="flex-1 py-3.5 px-4 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-500 flex items-center justify-center space-x-2"
                 >
                    <Briefcase class="w-4 h-4" />
                    <span>{{ $t('auth.role_provider') }}</span>
                 </button>
              </div>

              <!-- Content Container -->
              <div class="space-y-8 relative z-10">
                  <!-- Title Section -->
                  <div class="text-center space-y-3">
                     <h3 class="text-4xl font-black text-premium-blue tracking-tighter">
                       {{ isLoginMode ? $t('auth.login_title') : $t('auth.register_title') }}
                     </h3>
                     <div class="w-12 h-1 bg-premium-yellow mx-auto rounded-full"></div>
                  </div>

                  <!-- Feedback Messages (Visible for both Login and Register) -->
                  <div v-if="error" class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-xs font-black animate-shake text-center uppercase tracking-wider relative z-20">
                      {{ error.includes('.') ? $t(error) : error }}
                  </div>
                  <div v-if="registrationSuccess" class="p-4 bg-green-50 border border-green-100 rounded-2xl text-green-600 text-xs font-black animate-bounce-slow text-center uppercase tracking-wider relative z-20">
                      {{ $t('common.success_registration') }}
                  </div>

                  <Transition name="fade-slide" mode="out-in">
                     <div :key="isLoginMode">
                        <!-- Login Form -->
                        <form v-if="isLoginMode" @submit.prevent="handleLogin" class="space-y-6">
                            <div class="space-y-2 group">
                                <label class="text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] pl-4 transition-colors group-focus-within:text-premium-blue">{{ $t('auth.email') }}</label>
                                <div class="relative">
                                    <input v-model="loginForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 focus:bg-white focus:border-premium-blue/20 transition-all font-bold text-slate-700" :placeholder="$t('auth.email_placeholder')">
                                    <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue group-focus-within:scale-110 transition-all" />
                                </div>
                            </div>

                            <div class="space-y-2 group">
                                <label class="text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] pl-4 transition-colors group-focus-within:text-premium-blue">{{ $t('auth.password') }}</label>
                                <div class="relative">
                                    <input v-model="loginForm.password" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 focus:bg-white focus:border-premium-blue/20 transition-all font-bold text-slate-700" :placeholder="$t('auth.password_placeholder')">
                                    <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue group-focus-within:scale-110 transition-all" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between px-3">
                               <label class="flex items-center space-x-3 cursor-pointer group">
                                  <div class="relative">
                                     <input type="checkbox" v-model="loginForm.rememberMe" class="peer sr-only">
                                     <div class="w-6 h-6 border-2 border-slate-200 rounded-lg group-hover:border-premium-blue/50 transition-colors peer-checked:bg-premium-blue peer-checked:border-premium-blue"></div>
                                     <svg class="absolute inset-0 w-6 h-6 text-white scale-0 peer-checked:scale-75 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                     </svg>
                                  </div>
                                  <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('auth.remember_me') }}</span>
                               </label>
                               <button type="button" class="text-[10px] font-black text-premium-blue hover:text-premium-brown transition-colors uppercase tracking-widest">{{ $t('auth.forgot_password', 'Mot de passe oublié ?') }}</button>
                            </div>

                            <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-3xl font-black text-xs uppercase tracking-[0.4em] shadow-2xl shadow-premium-blue/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center space-x-3 disabled:opacity-50 mt-8 overflow-hidden relative group">
                                <span class="relative z-10 flex items-center">
                                    <Loader2 v-if="loading" class="w-5 h-5 mr-3 animate-spin" />
                                    {{ loading ? $t('auth.logging_in') : $t('auth.login_button') }}
                                </span>
                                <div class="absolute inset-0 bg-linear-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                            </button>
                        </form>

                        <!-- Register Form (Contextual) -->
                        <div v-else>
                           <!-- Client Register -->
                           <form v-if="activeRole === 'client'" @submit.prevent="handleClientRegister" class="space-y-6">
                               <div class="space-y-5">
                                  <div class="grid grid-cols-2 gap-4">
                                     <div class="relative group">
                                         <input v-model="clientForm.prenom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.firstname')">
                                         <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                     </div>
                                     <div class="relative group">
                                         <input v-model="clientForm.nom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.name')">
                                         <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                     </div>
                                  </div>
                                  <div class="relative group">
                                     <input v-model="clientForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.email')">
                                     <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                  </div>
                                  <div class="relative group">
                                     <div class="relative">
                                         <input 
                                             v-model="citySearch" 
                                             type="text" 
                                             :placeholder="clientForm.city || $t('auth.city')"
                                             class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700"
                                         >
                                         <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                         
                                         <!-- City suggestions -->
                                         <div v-if="filteredCities.length > 0" class="absolute z-30 w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                                             <button 
                                                 v-for="city in filteredCities" 
                                                 :key="city"
                                                 type="button"
                                                 @click="selectCity(city, clientForm)"
                                                 class="w-full px-5 py-3 text-left text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-between group text-premium-blue"
                                             >
                                                 <span>{{ city }}</span>
                                                 <MapPin class="w-4 h-4 text-slate-300 group-hover:text-premium-blue" />
                                             </button>
                                         </div>
                                     </div>
                                  </div>
                                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                     <div class="relative group">
                                        <input v-model="clientForm.password" :type="showPassword ? 'text' : 'password'" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-12 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.password')">
                                        <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                        <button type="button" @click="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-premium-blue transition-colors">
                                            <Eye v-if="!showPassword" class="w-5 h-5" />
                                            <EyeOff v-else class="w-5 h-5" />
                                        </button>
                                     </div>
                                     <div class="relative group">
                                        <input v-model="clientForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-12 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.confirm_password')">
                                        <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                        <button type="button" @click="toggleConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-premium-blue transition-colors">
                                            <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                            <EyeOff v-else class="w-5 h-5" />
                                        </button>
                                     </div>
                                  </div>
                               </div>
                               <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-3xl font-black text-xs uppercase tracking-[0.4em] shadow-2xl hover:scale-[1.02] active:scale-95 transition-all mt-6">
                                  {{ loading ? $t('auth.signing_up') : $t('auth.create_client_account') }}
                                </button>
                           </form>

                           <!-- Provider Register (Simplified) -->
                           <form v-else @submit.prevent="handleProviderRegister" class="space-y-6">
                                <div class="space-y-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="relative group">
                                            <input v-model="providerForm.prenom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.firstname')">
                                            <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                        </div>
                                        <div class="relative group">
                                            <input v-model="providerForm.nom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.name')">
                                            <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                        </div>
                                    </div>
                                    <div class="relative group">
                                        <input v-model="providerForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.email')">
                                        <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                    </div>
                                    <div class="relative group">
                                        <div class="relative">
                                            <input 
                                                v-model="citySearch" 
                                                type="text" 
                                                :placeholder="providerForm.city || $t('auth.city')"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700"
                                            >
                                            <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                            
                                            <!-- City suggestions -->
                                            <div v-if="filteredCities.length > 0" class="absolute z-30 w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                                                <button 
                                                    v-for="city in filteredCities" 
                                                    :key="city"
                                                    type="button"
                                                    @click="selectCity(city, providerForm)"
                                                    class="w-full px-5 py-3 text-left text-sm font-bold hover:bg-slate-50 transition-colors flex items-center justify-between group text-premium-blue"
                                                >
                                                    <span>{{ city }}</span>
                                                    <MapPin class="w-4 h-4 text-slate-300 group-hover:text-premium-blue" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="relative group">
                                            <input v-model="providerForm.password" :type="showPassword ? 'text' : 'password'" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-12 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.password')">
                                            <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                            <button type="button" @click="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-premium-blue transition-colors">
                                                <Eye v-if="!showPassword" class="w-5 h-5" />
                                                <EyeOff v-else class="w-5 h-5" />
                                            </button>
                                        </div>
                                        <div class="relative group">
                                            <input v-model="providerForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-12 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.confirm_password')">
                                            <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                            <button type="button" @click="toggleConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-premium-blue transition-colors">
                                                <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                                <EyeOff v-else class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-4xl font-black text-xs uppercase tracking-[0.4em] shadow-2xl hover:scale-[1.01] active:scale-95 transition-all mt-4 shrink-0">
                                    {{ loading ? $t('auth.signing_up') : $t('auth.signup_provider') }}
                                </button>
                           </form>
                        </div>
                     </div>
                  </Transition>

                  <!-- Toggle Login/Register & Socials -->
                  <div class="space-y-10 pt-4">
                     <div class="flex items-center justify-center space-x-3 text-[11px] font-black uppercase tracking-[0.2em]">
                        <span class="text-slate-400">{{ isLoginMode ? $t('auth.no_account', 'Pas encore de compte ?') : $t('auth.already_account', 'Déjà inscrit ?') }}</span>
                        <button @click="isLoginMode = !isLoginMode" class="text-premium-blue hover:text-premium-brown transition-colors underline underline-offset-8 decoration-2 decoration-premium-yellow/30">
                           {{ isLoginMode ? $t('auth.register_title') : $t('auth.login_title') }}
                        </button>
                     </div>

                     <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-100"></div></div>
                        <div class="relative flex justify-center text-[10px] uppercase font-black tracking-[0.3em]"><span class="bg-white/80 px-6 text-slate-300 backdrop-blur-md">{{ $t('auth.or_social', 'Authentification sociale') }}</span></div>
                     </div>

                     <div class="flex justify-center space-x-8">
                        <button @click="auth.socialLogin('google')" class="w-16 h-16 bg-white border border-slate-100 rounded-3xl flex items-center justify-center hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-500 group">
                           <img src="https://www.google.com/favicon.ico" class="w-6 h-6 grayscale opacity-40 group-hover:grayscale-0 group-hover:opacity-100 transition-all" alt="Google">
                        </button>
                        <button @click="auth.socialLogin('facebook')" class="w-16 h-16 bg-white border border-slate-100 rounded-3xl flex items-center justify-center hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-500 group">
                           <div class="bg-blue-600/10 rounded-full w-7 h-7 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all"><span class="text-[12px] font-black">f</span></div>
                        </button>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100;400;700;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,900&display=swap');

.font-outfit { font-family: 'Outfit', sans-serif; }
.font-playfair { font-family: 'Playfair Display', serif; }

@keyframes orbit {
  0% {
    transform: rotate(var(--delay)) translateX(var(--orbit-radius)) rotate(calc(-1 * var(--delay))) scale(1.1);
    opacity: 0.9;
    filter: blur(0px);
    z-index: 20;
  }
  25% {
    transform: rotate(calc(var(--delay) + 90deg)) translateX(var(--orbit-radius)) rotate(calc(-1 * (var(--delay) + 90deg))) scale(0.9);
    opacity: 0.5;
    filter: blur(2px);
    z-index: 5;
  }
  50% {
    transform: rotate(calc(var(--delay) + 180deg)) translateX(var(--orbit-radius)) rotate(calc(-1 * (var(--delay) + 180deg))) scale(0.7);
    opacity: 0.2;
    filter: blur(4px);
    z-index: 0;
  }
  75% {
    transform: rotate(calc(var(--delay) + 270deg)) translateX(var(--orbit-radius)) rotate(calc(-1 * (var(--delay) + 270deg))) scale(0.9);
    opacity: 0.5;
    filter: blur(2px);
    z-index: 5;
  }
  100% {
    transform: rotate(calc(var(--delay) + 360deg)) translateX(var(--orbit-radius)) rotate(calc(-1 * (var(--delay) + 360deg))) scale(1.1);
    opacity: 0.9;
    filter: blur(0px);
    z-index: 20;
  }
}

.orbit-item {
  --orbit-radius: 80px;
  will-change: transform, opacity, filter;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-spin-slow {
  animation: spin 80s linear infinite;
}

@keyframes slow-zoom {
  from { transform: scale(1); }
  to { transform: scale(1.15); }
}

.animate-slow-zoom {
  animation: slow-zoom 30s linear infinite alternate;
}

@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }

.animate-fade-in { animation: fadeIn 1s ease-out forwards; opacity: 0; }
.animation-delay-300 { animation-delay: 0.3s; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-slide-enter-from { opacity: 0; transform: translateX(30px); }
.fade-slide-leave-to { opacity: 0; transform: translateX(-30px); }

.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

@keyframes bounce-slow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.animate-bounce-slow { animation: bounce-slow 4s ease-in-out infinite; }

@keyframes yellow-shine {
  0%, 100% { color: white; text-shadow: 0 0 0px rgba(250, 204, 21, 0); }
  50% { color: #facc15; text-shadow: 0 0 20px rgba(250, 204, 21, 0.8); }
}
.animate-yellow-shine {
  animation: yellow-shine 4s ease-in-out infinite;
}

.bg-premium-blue { background-color: #0f172a; }
.text-premium-blue { color: #0f172a; }
.bg-premium-yellow { background-color: #facc15; }
.text-premium-yellow { color: #facc15; }
.text-premium-brown { color: #92400e; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Backdrop intensity for artisan feel */
.backdrop-blur-3xl {
  backdrop-filter: blur(60px);
  -webkit-backdrop-filter: blur(60px);
}
</style>
