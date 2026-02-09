<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import { Lock, Mail, Loader2, Search, Briefcase, User, MapPin, Camera, Settings, Star, Sparkles, ArrowRight, Shield, Smartphone, PenTool } from 'lucide-vue-next';
import valoraLogo from '../assets/logo-valora.png';
import artisanBg from '../assets/auth-right-bg.jpg';

const auth = useAuthStore();
const router = useRouter();

// View State toggles
const activeRole = ref('client'); // 'client' or 'provider'
const isLoginMode = ref(true);

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
            name: clientForm.value.name,
            email: clientForm.value.email,
            password: clientForm.value.password,
            password_confirmation: clientForm.value.password_confirmation,
            role: 'client'
        });
        router.push('/dashboard');
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
            role: 'provider',
            city: providerForm.value.ville,
            hourly_rate: providerForm.value.tarif,
            experience: providerForm.value.experience,
            description: providerForm.value.description
        });
        router.push('/dashboard');
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

const jobs = [
    "Ménage", "Bricolage", "Jardinage", "Plomberie", "Electricité", "Peinture", 
    "Transport / Livraison", "Garde d'enfants", "Soins aux personnes agées", 
    "Informatique", "Mécanique", "Cuisine", "Serrurerie", "Menuiserie", "Autres"
];
</script>

<template>
  <div class="relative min-h-screen w-full flex items-center justify-center p-4 sm:p-6 overflow-hidden font-outfit">
    
    <!-- Real Artisan Background with Premium Overlay -->
    <div class="absolute inset-0 z-0">
      <img :src="artisanBg" alt="Artisan Background" class="w-full h-full object-cover scale-105 animate-slow-zoom">
      <div class="absolute inset-0 bg-gradient-to-br from-premium-blue/90 via-premium-blue/70 to-premium-brown/80 backdrop-blur-[2px]"></div>
      <!-- Animated Mesh Polish -->
      <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_50%_50%,_rgba(250,204,21,0.1),transparent_50%)] animate-pulse"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        
        <!-- Left Side: Branding & Artisan Values -->
        <div class="hidden lg:flex flex-col space-y-10 animate-fade-in px-8">
           <div class="flex items-center space-x-4">
              <div class="w-20 h-20 bg-white rounded-3xl shadow-2xl p-2 flex items-center justify-center border-4 border-white group hover:rotate-6 transition-transform">
                 <img :src="valoraLogo" alt="Valora" class="w-full h-full object-contain">
              </div>
              <h1 class="text-5xl font-black text-white tracking-tighter drop-shadow-lg">VALORA<span class="text-premium-yellow">.</span></h1>
           </div>

           <div class="space-y-6">
              <div class="inline-flex items-center space-x-3 px-5 py-2.5 bg-premium-yellow text-premium-blue rounded-full shadow-xl animate-bounce-slow">
                 <PenTool class="w-5 h-5" />
                 <span class="text-xs font-black uppercase tracking-widest text-premium-blue">L'Art de Bien Servir</span>
              </div>
              <h2 class="text-6xl font-black text-white tracking-tighter leading-[0.9]">
                {{ $t('auth.welcome_back_title', 'Simplifiez votre') }}<br>
                <span class="text-premium-yellow italic font-playfair lowercase">{{ $t('auth.daily_life', 'quotidien') }}</span>
              </h2>
              <p class="text-xl text-white/70 font-medium max-w-md leading-relaxed">
                {{ $t('auth.auth_desc', 'La plateforme de confiance qui connecte le talent des artisans locaux avec vos besoins du quotidien.') }}
              </p>
           </div>

           <div class="grid grid-cols-2 gap-6 pt-4">
              <div class="p-6 bg-white/10 backdrop-blur-xl rounded-[2.5rem] border border-white/20 shadow-2xl group hover:-translate-y-2 transition-all">
                 <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-premium-yellow transition-colors">
                    <Shield class="w-6 h-6 text-white group-hover:text-premium-blue" />
                 </div>
                 <h4 class="font-black text-white mb-1 uppercase text-xs tracking-widest">Sécurité</h4>
                 <p class="text-white/60 text-[10px] font-bold">Vérification rigoureuse</p>
              </div>
              <div class="p-6 bg-white/10 backdrop-blur-xl rounded-[2.5rem] border border-white/20 shadow-2xl group hover:-translate-y-2 transition-all">
                 <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-premium-yellow transition-colors">
                    <Star class="w-6 h-6 text-white group-hover:text-premium-blue" />
                 </div>
                 <h4 class="font-black text-white mb-1 uppercase text-xs tracking-widest">Qualité</h4>
                 <p class="text-white/60 text-[10px] font-bold">Mains d'experts certifiés</p>
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

              <!-- Login Form Container -->
              <div class="space-y-8 relative z-10">
                 <div class="text-center space-y-3">
                    <h3 class="text-4xl font-black text-premium-blue tracking-tighter">
                      {{ isLoginMode ? $t('auth.login_title') : $t('auth.register_title') }}
                    </h3>
                    <div class="w-12 h-1 bg-premium-yellow mx-auto rounded-full"></div>
                 </div>

                 <Transition name="fade-slide" mode="out-in">
                    <div :key="isLoginMode">
                        <!-- Login Form -->
                        <form v-if="isLoginMode" @submit.prevent="handleLogin" class="space-y-6">
                            <div v-if="error" class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-xs font-black animate-shake text-center uppercase tracking-wider">
                                {{ error.includes('.') ? $t(error) : error }}
                            </div>

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

                            <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.4em] shadow-2xl shadow-premium-blue/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center space-x-3 disabled:opacity-50 mt-8 overflow-hidden relative group">
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
                                  <div class="relative group">
                                     <input v-model="clientForm.name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.fullname')">
                                     <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                  </div>
                                  <div class="relative group">
                                     <input v-model="clientForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.email')">
                                     <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue transition-all" />
                                  </div>
                                  <div class="grid grid-cols-2 gap-4">
                                     <input v-model="clientForm.password" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.password')">
                                     <input v-model="clientForm.password_confirmation" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-5 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.confirm_password')">
                                  </div>
                               </div>
                               <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.4em] shadow-2xl hover:scale-[1.02] active:scale-95 transition-all mt-6">
                                  {{ loading ? $t('auth.signing_up') : $t('auth.create_client_account') }}
                                </button>
                           </form>

                           <!-- Provider Register (Detailed) -->
                           <form v-else @submit.prevent="handleProviderRegister" class="space-y-5 max-h-[500px] overflow-y-auto pr-3 custom-scrollbar">
                                <div class="grid grid-cols-2 gap-4">
                                    <input v-model="providerForm.nom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.name')">
                                    <input v-model="providerForm.prenom" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.firstname')">
                                </div>
                                <div class="relative group">
                                    <input v-model="providerForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.email')">
                                    <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <input v-model="providerForm.password" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.password')">
                                    <input v-model="providerForm.password_confirmation" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.confirm_password')">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <select v-model="providerForm.metier" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-5 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700 appearance-none cursor-pointer">
                                        <option value="" disabled>{{ $t('auth.select_job') }}</option>
                                        <option v-for="job in jobs" :key="job" :value="job">{{ job }}</option>
                                    </select>
                                    <select v-model="providerForm.ville" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 px-5 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700 appearance-none cursor-pointer">
                                        <option value="" disabled>{{ $t('auth.select_city', 'Ville') }}</option>
                                        <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                                    </select>
                                </div>

                                <div class="relative group">
                                    <input v-model="providerForm.experience" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 pl-14 pr-6 outline-none focus:ring-4 focus:ring-premium-blue/5 transition-all font-bold text-slate-700" :placeholder="$t('auth.experience_placeholder')">
                                    <Smartphone class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-premium-blue" />
                                </div>

                                <div class="flex items-center space-x-6 bg-premium-yellow/5 p-5 rounded-[2rem] border border-premium-yellow/10">
                                   <div class="flex-1">
                                      <label class="text-[10px] uppercase font-black text-premium-brown block mb-1 tracking-widest opacity-60">{{ $t('auth.rate_label', 'Tarif horaire') }}</label>
                                      <input v-model="providerForm.tarif" type="text" class="w-full bg-transparent outline-none font-black text-premium-blue text-lg" placeholder="100 MAD">
                                   </div>
                                   <div class="w-px h-12 bg-premium-yellow/20"></div>
                                   <button type="button" class="flex flex-col items-center justify-center p-3 rounded-2xl hover:bg-premium-yellow/20 transition-all active:scale-95">
                                      <Camera class="w-6 h-6 text-premium-brown mb-1.5" />
                                      <span class="text-[9px] font-black uppercase text-premium-brown tracking-tighter">Photo</span>
                                   </button>
                                </div>

                                <button type="submit" :disabled="loading" class="w-full bg-premium-blue text-white py-6 rounded-[2rem] font-black text-xs uppercase tracking-[0.4em] shadow-2xl hover:scale-[1.01] active:scale-95 transition-all sticky bottom-0">
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
                       <button @click="auth.socialLogin('google')" class="w-16 h-16 bg-white border border-slate-100 rounded-[1.5rem] flex items-center justify-center hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-500 group">
                          <img src="https://www.google.com/favicon.ico" class="w-6 h-6 grayscale opacity-40 group-hover:grayscale-0 group-hover:opacity-100 transition-all" alt="Google">
                       </button>
                       <button @click="auth.socialLogin('facebook')" class="w-16 h-16 bg-white border border-slate-100 rounded-[1.5rem] flex items-center justify-center hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] hover:-translate-y-2 transition-all duration-500 group">
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
