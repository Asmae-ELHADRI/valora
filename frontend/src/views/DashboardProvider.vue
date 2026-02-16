<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { 
  Search, FileText, Star, TrendingUp, Clock, CheckCircle, Check, XCircle, MapPin, Loader2, User, 
  Settings, LogOut, Power, MessageCircle, ShieldCheck, Award, Plus, ArrowRight, Mail, Phone, 
  Briefcase, Calendar, Camera, AlertCircle, Edit3, Save, X, Fingerprint, Coins, ChevronDown, 
  LifeBuoy, Zap, Trophy, Users, ExternalLink, Download, UploadCloud, FileCheck, Trash2, Smartphone, 
  ChevronLeft, ChevronRight, Globe, Linkedin, Facebook, Instagram,
  PenTool, ArrowLeft
} from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';
import AvailabilityScheduler from '../components/AvailabilityScheduler.vue';
import html2pdf from 'html2pdf.js'; // Direct import for PDF generation

const auth = useAuthStore();
const router = useRouter();
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const categories = ref([]);
const loading = ref(true);
const activeTab = ref('overview');
const saving = ref(false);
const success = ref(null);
const error = ref(null);
const cvMode = ref(false); // Toggle between Edit and CV View
const categorySearch = ref('');
const currentStep = ref(1);

const switchTab = async (tabId) => {
    console.log('[Dashboard] Switching to tab:', tabId);
    
    if (tabId === 'messages') {
        router.push('/messages');
        return;
    }

    try {
        activeTab.value = tabId;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Reset specific states when switching
        if (tabId === 'missions') {
            console.log('[Dashboard] Fetching applications (non-blocking)...');
            fetchApplications().catch(err => console.error('Bg fetch failed', err));
        }
        if (tabId === 'profile') {
            // Ensure profile form is initialized if not already
            if (!profileForm.value.first_name) {
                console.log('[Dashboard] Initializing profile form...');
                initProfileForm();
            }
        }
    } catch (err) {
        console.error('[Dashboard] Error switching tab:', err);
        alert('Une erreur est survenue lors de la navigation : ' + err.message);
    }
};



const prevStep = () => {
    console.log('[Dashboard] Prev Step', currentStep.value);
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const nextStep = () => {
    console.log('[Dashboard] Next Step', currentStep.value);
    
    // Validation Step 1
    if (currentStep.value === 1) {
        if (!profileForm.value.first_name || !profileForm.value.last_name || !profileForm.value.phone || !profileForm.value.city) {
             error.value = "Veuillez remplir les informations obligatoires (Nom, Prénom, Téléphone, Ville).";
             window.scrollTo({ top: 0, behavior: 'smooth' });
             setTimeout(() => error.value = null, 4000);
             return;
        }
    }

    // Validation Step 2
    if (currentStep.value === 2) {
         if (!profileForm.value.category_ids || profileForm.value.category_ids.length === 0) {
             error.value = "Veuillez sélectionner au moins une catégorie.";
             window.scrollTo({ top: 0, behavior: 'smooth' });
             setTimeout(() => error.value = null, 4000);
             return;
         }
    }

    if (currentStep.value < 3) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const moroccanCities = [
    'Agadir', 'Al Hoceima', 'Béni Mellal', 'Berkane', 'Berrechid', 'Casablanca', 
    'Chefchaouen', 'Dakhla', 'El Jadida', 'Errachidia', 'Essaouira', 'Fès', 
    'Fquih Ben Salah', 'Guelmim', 'Ifrane', 'Kénitra', 'Khemisset', 'Khénifra', 
    'Khouribga', 'Ksar El Kebir', 'Larache', 'Marrakech', 'Meknès', 'Mohammédia', 
    'Nador', 'Ouarzazate', 'Oujda', 'Rabat', 'Safi', 'Saidía', 'Salé', 'Settat', 
    'Sidi Ifni', 'Sidi Kacem', 'Sidi Slimane', 'Tanger', 'Tan-Tan', 'Taroudant', 
    'Taza', 'Tétouan', 'Tiznit'
];

const showCityDropdown = ref(false);
const cityQuery = ref('');
const filteredCities = computed(() => {
    if (!cityQuery.value) return moroccanCities;
    return moroccanCities.filter(city => 
        city.toLowerCase().includes(cityQuery.value.toLowerCase())
    );
});

const selectCity = (city) => {
    profileForm.value.city = city;
    showCityDropdown.value = false;
    cityQuery.value = '';
};

// Fermer le dropdown au clic extérieur
onMounted(() => {
    window.addEventListener('click', (e) => {
        const dropdown = document.getElementById('city-dropdown-container');
        if (dropdown && !dropdown.contains(e.target)) {
            showCityDropdown.value = false;
        }
    });
});

// Grouper les catégories par domaine
const categoryGroups = {
    'Construction & Rénovation': [
        'Plomberie', 'Électricité', 'Maçonnerie', 'Peinture & Décoration', 
        'Menuiserie', 'Carrelage', 'Chauffage & Climatisation', 'Isolation', 
        'Toiture & Couverture', 'Vitrerie'
    ],
    'Services à domicile': [
        'Ménage & Nettoyage', 'Jardinage & Paysagisme', 'Garde d\'enfants', 
        'Aide à domicile', 'Repassage', 'Cuisine à domicile'
    ],
    'Services professionnels': [
        'Informatique & Dépannage', 'Cours particuliers', 'Coaching & Formation', 
        'Traduction', 'Rédaction & Correction', 'Comptabilité', 'Conseil juridique', 
        'Marketing & Communication', 'Design graphique', 'Développement web'
    ],
    'Transport & Logistique': [
        'Déménagement', 'Transport de marchandises', 'Livraison', 'Coursier'
    ],
    'Événementiel': [
        'Traiteur', 'Photographie', 'Vidéographie', 'Animation', 
        'DJ & Musicien', 'Décoration événementielle'
    ],
    'Bien-être & Santé': [
        'Coiffure à domicile', 'Esthétique & Beauté', 'Massage', 
        'Fitness & Sport', 'Diététique'
    ],
    'Automobile': [
        'Mécanique auto', 'Carrosserie', 'Dépannage auto', 'Nettoyage auto'
    ],
    'Animaux': [
        'Toilettage', 'Garde d\'animaux', 'Promenade de chiens', 'Éducation canine'
    ]
};


const filteredCategoryGroups = computed(() => {
    const searchLower = categorySearch.value.toLowerCase();
    const result = [];
    const usedCategoryIds = new Set();
    
    // Process defined groups
    for (const [groupName, categoryNames] of Object.entries(categoryGroups)) {
        const groupCategories = categories.value.filter(cat => 
            categoryNames.includes(cat.name) &&
            cat.name.toLowerCase().includes(searchLower)
        );
        
        if (groupCategories.length > 0) {
            groupCategories.forEach(c => usedCategoryIds.add(c.id));
            result.push({
                name: groupName,
                categories: groupCategories
            });
        }
    }

    // specific fix for "mekanisyan" or other dynamic categories
    const otherCategories = categories.value.filter(cat => 
        !usedCategoryIds.has(cat.id) && 
        cat.name.toLowerCase().includes(searchLower)
    );

    // New: Check if no categories matched groups but exist in list (fallback)
    const groupedIds = new Set();
    result.forEach(g => {
        if (g.categories && Array.isArray(g.categories)) {
            g.categories.forEach(c => groupedIds.add(c.id));
        }
    });
    
    // Safety check for any missed categories
    const missed = categories.value.filter(c => 
        !groupedIds.has(c.id) && !usedCategoryIds.has(c.id) && c.name.toLowerCase().includes(searchLower)
    );

    if (missed.length > 0) {
        const otherGroup = result.find(r => r.name === 'Autres Spécialités');
        if (otherGroup) {
            if (!otherGroup.categories) otherGroup.categories = [];
            otherGroup.categories.push(...missed);
        } else {
             result.push({ name: 'Autres Spécialités', categories: missed });
        }
    }

    return result;
});

const selectedCategoriesCount = computed(() => {
    return profileForm.value.category_ids?.length || 0;
});

const hasDiploma = ref('');

watch(hasDiploma, (val) => {
    if (val === 'Non') {
        profileForm.value.diplomas = 'Non';
    } else if (val === 'Oui') {
        if (profileForm.value.diplomas === 'Non' || !profileForm.value.diplomas) {
             profileForm.value.diplomas = '';
        }
    }
});

// Form Data
const profileForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  birth_date: '',
  cin: '',
  address: '',
  city: '',
  skills: '',
  experience: '',
  diplomas: '',
  hourly_rate: '',
  category_ids: [],
  is_visible: true,
  availabilities: {},

  achievements: [], 
  languages: [], // { name: 'Français', level: 'Courant' }
  formations: [], // { title: '', institution: '', year: '' }
  description: '' // Bio
});

const addLanguage = () => {
    if (!profileForm.value.languages) profileForm.value.languages = [];
    profileForm.value.languages.push({ name: '', level: 'Intermédiaire' });
};

const removeLanguage = (index) => {
    if (profileForm.value.languages) {
        profileForm.value.languages.splice(index, 1);
    }
};

// -- Custom Services Logic --
const newServiceInput = ref('');
const addCustomService = () => {
    if (!newServiceInput.value.trim()) return;
    
    const currentSkills = profileForm.value.skills ? profileForm.value.skills.split(',').map(s => s.trim()) : [];
    if (!currentSkills.includes(newServiceInput.value.trim())) {
        currentSkills.push(newServiceInput.value.trim());
        profileForm.value.skills = currentSkills.join(', ');
    }
    newServiceInput.value = '';
};

const removeCustomService = (serviceToRemove) => {
    if (!profileForm.value.skills) return;
    const currentSkills = profileForm.value.skills.split(',').map(s => s.trim());
    const updatedSkills = currentSkills.filter(s => s !== serviceToRemove);
    profileForm.value.skills = updatedSkills.join(', ');
};

const customServicesList = computed(() => {
    return profileForm.value.skills ? profileForm.value.skills.split(',').map(s => s.trim()).filter(s => s !== '') : [];
});
// ---------------------------



const addDetailedExperience = () => {
    if (!profileForm.value.achievements) profileForm.value.achievements = [];
    profileForm.value.achievements.push('');
};

const removeDetailedExperience = (index) => {
    profileForm.value.achievements.splice(index, 1);
};

const visibility = ref(true);

const fetchApplications = async () => {
  try {
    const response = await api.get('/api/my-applications');
    applications.value = response.data.data;
  } catch (err) {
    console.error('Erreur chargement candidatures:', err);
  }
};

const fetchReviews = async () => {
  try {
    const response = await api.get('/api/reviews/provider');
    reviewsData.value = response.data;
  } catch (err) {
    console.error('Erreur chargement avis:', err);
  }
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/api/offers/categories');
    categories.value = response.data;
  } catch (err) {
    console.error('Erreur chargement catégories:', err);
  }
};

const safeJsonParse = (value, fallback) => {
    if (value === null || value === undefined) return fallback;
    if (Array.isArray(value)) return value;
    if (typeof value === 'object') return value; // Handle already parsed objects
    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);
            return parsed === null ? fallback : parsed;
        } catch (e) {
            console.warn('JSON Parse Error:', e);
            return fallback;
        }
    }
    return fallback;
};

const initProfileForm = () => {
  const user = auth.user;
  if (user) {
    const userCategoryIds = user.prestataire?.categories?.map(c => c.id) || [];
    if (userCategoryIds.length === 0 && user.prestataire?.category_id) {
        userCategoryIds.push(user.prestataire.category_id);
    }

    const normalizedLanguages = safeJsonParse(user.prestataire?.languages, []).map(l => 
        typeof l === 'string' ? { name: l, level: 'Intermédiaire' } : l
    );
    const normalizedFormations = safeJsonParse(user.prestataire?.formations, []).map(f => 
        typeof f === 'string' ? { title: f, institution: '', year: '' } : f
    );
    const normalizedAchievements = safeJsonParse(user.prestataire?.achievements, []);

    profileForm.value = {
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      phone: user.phone || '',
      birth_date: user.prestataire?.birth_date ? user.prestataire.birth_date.substring(0, 10) : '',
      cin: user.prestataire?.cin || '',
      address: user.address || '',
      city: user.prestataire?.city || '',
      skills: user.prestataire?.skills || '',
      experience: user.prestataire?.experience || '',
      diplomas: user.prestataire?.diplomas || '',
      hourly_rate: user.prestataire?.hourly_rate || '',
      achievements: Array.isArray(normalizedAchievements) ? normalizedAchievements : [],
      languages: normalizedLanguages,
      formations: normalizedFormations,
      description: user.prestataire?.description || '',
      category_ids: userCategoryIds,
      is_visible: user.prestataire?.is_visible !== false,
      availabilities: user.prestataire?.availabilities || {}
    };
    
    // Initialize hasDiploma state
    if (user.prestataire?.diplomas === 'Non') {
        hasDiploma.value = 'Non';
    } else if (user.prestataire?.diplomas && user.prestataire.diplomas.length > 0) {
        hasDiploma.value = 'Oui';
    } else {
        hasDiploma.value = '';
    }

    visibility.value = user.prestataire?.is_visible ?? true;
  }
};

const addFormation = () => {
    if (!profileForm.value.formations) profileForm.value.formations = [];
    profileForm.value.formations.push({ title: '', institution: '', year: '' });
};

const removeFormation = (index) => {
    profileForm.value.formations.splice(index, 1);
};

onMounted(async () => {
  await Promise.all([fetchApplications(), fetchReviews(), fetchCategories()]);
  // Ensure we have the latest user data including provider details
  if (auth.token) {
      await auth.fetchUser();
  }
  initProfileForm();
  
  // Auto-switch to CV mode if profile is sufficiently completed
  if (profileCompletion.value >= 80) {
      cvMode.value = true;
  }
  
  loading.value = false;
});

// Watch for user changes to keep form in sync if reloaded (Removed deep: true for performance)
watch(() => auth.user, (newVal, oldVal) => {
    if (newVal?.id !== oldVal?.id) {
        initProfileForm();
    }
});

const cvUploading = ref(false);
const cvInput = ref(null);

const handleCvUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validation Basic
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedTypes.includes(file.type)) {
        error.value = "Format invalide. PDF ou DOCX uniquement.";
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        error.value = "Le fichier dépasse 5Mo.";
        return;
    }

    cvUploading.value = true;
    const formData = new FormData();
    formData.append('cv', file);

    try {
        const response = await api.post('/api/provider/cv', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        // Update local state
        if (auth.user.prestataire) {
            auth.user.prestataire.cv_url = response.data.cv_url;
        }
        success.value = "CV ajouté avec succès !";
        setTimeout(() => success.value = null, 3000);
    } catch (err) {
        console.error(err);
        error.value = "Erreur lors de l'envoi du CV.";
    } finally {
        cvUploading.value = false;
        // Reset input
        if (event.target) event.target.value = '';
    }
};

const saveProfile = async () => {
    saving.value = true;
    success.value = null;
    error.value = null;
    try {
        const dataToSend = {
            ...profileForm.value,
            achievements: profileForm.value.achievements.filter(a => a && a.trim() !== ''),
            description: profileForm.value.description 
        };

        // Parallelize requests for speed
        const [profileResponse] = await Promise.all([
            api.post('/api/provider/profile', dataToSend),
            api.post('/api/provider/availability', { availabilities: profileForm.value.availabilities })
        ]);

        auth.user = profileResponse.data.user; // Update store
        
        success.value = "Profil mis à jour avec succès !";
        setTimeout(() => { success.value = null; }, 4000);
        
        // Switch to CV View automatically
        cvMode.value = true;
        currentStep.value = 1;
    } catch (err) {
        console.error(err);
        const userInfo = `[User: #${auth.user?.id || '?'} - ${auth.user?.name || 'Inconnu'}]`;
        let errorMsg = "Erreur lors de la mise à jour du profil.";
        
        if (err.response && err.response.data && err.response.data.errors) {
            const firstError = Object.values(err.response.data.errors)[0][0];
            errorMsg = `Erreur API : ${firstError}`;
        } else if (err.response && err.response.data && err.response.data.message) {
            errorMsg = `Erreur API : ${err.response.data.message}`;
        }
        
        error.value = `${userInfo} ${errorMsg}`;
        setTimeout(() => { error.value = null; }, 6000);
    } finally {
        saving.value = false;
    }
};

const validateAndSubmit = () => {
    const d = profileForm.value;
    const requiredFields = [
        { key: 'first_name', label: 'Prénom' },
        { key: 'last_name', label: 'Nom' },
        { key: 'phone', label: 'Téléphone' },
        { key: 'city', label: 'Ville' },
        { key: 'address', label: 'Adresse' },
        { key: 'birth_date', label: 'Date de naissance' },
        { key: 'cin', label: 'CIN' },
        { key: 'description', label: 'Biographie' },
        { key: 'experience', label: 'Années d\'expérience' },
        { key: 'hourly_rate', label: 'Taux horaire' }
    ];

    const missing = requiredFields.filter(f => !d[f.key]);
    const userInfo = `[User: #${auth.user?.id || '?'} - ${auth.user?.name || 'Inconnu'}]`;
    
    if (missing.length > 0) {
        error.value = `${userInfo} Veuillez remplir les champs obligatoires : ${missing.map(f => f.label).join(', ')}`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { error.value = null; }, 6000);
        return;
    }

    if (!d.category_ids || d.category_ids.length === 0) {
        error.value = `${userInfo} Veuillez sélectionner au moins un domaine d'expertise.`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { error.value = null; }, 6000);
        return;
    }

    saveProfile();
};

const handlePhotoUpdate = (newUrl) => {
    auth.fetchUser();
};

const handleAvailabilityUpdate = (newAvailability) => {
    profileForm.value.availabilities = newAvailability;
};

const toggleVisibility = async () => {
  try {
    const response = await api.post('/api/provider/visibility');
    visibility.value = response.data.is_visible;
    if (auth.user && auth.user.prestataire) {
        auth.user.prestataire.is_visible = visibility.value;
    }
  } catch (err) {
    console.error(err);
    alert('Erreur lors du changement de visibilité');
  }
};

const deleteAccount = async () => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.')) {
        return;
    }
    
    try {
        await api.delete('/api/account/delete');
        auth.logout();
        window.location.href = '/'; // Force redirect
    } catch (err) {
        console.error(err);
        alert('Erreur lors de la suppression du compte');
    }
};

const generatePDF = () => {
    const element = document.getElementById('cv-content');
    if (!element) return;

    // Clone element to modify styles for PDF without affecting UI
    const clone = element.cloneNode(true);
    
    // Force background colors to simple hex to avoid oklab issues
    // This is a brute-force fix for the specific error
    const allElements = clone.querySelectorAll('*');
    allElements.forEach(el => {
        const style = window.getComputedStyle(el);
        if (style.backgroundColor.includes('oklab')) {
            el.style.backgroundColor = '#facc15'; // Fallback to yellow or transparent
        }
    });

    const opt = {
        margin: [0, 0, 0, 0],
        filename: `CV-${auth.user?.last_name || 'Valora'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            useCORS: true, 
            logging: false,
            backgroundColor: '#ffffff'
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().catch(err => {
        console.error('PDF Generation Error:', err);
        alert("Une erreur est survenue lors de la génération du PDF. Assurez-vous d'avoir une connexion stable.");
    });
};

// Existing Stats & Helpers
const profileCompletion = computed(() => {
    const fields = [
        profileForm.value.first_name,
        profileForm.value.last_name,
        profileForm.value.phone,
        profileForm.value.address,
        profileForm.value.birth_date,
        profileForm.value.cin,
        profileForm.value.city,
        profileForm.value.hourly_rate,
        auth.user?.prestataire?.photo_url,
        profileForm.value.skills,
        profileForm.value.category_ids?.length > 0
    ];
    const completedFields = fields.filter(f => f && (Array.isArray(f) ? f.length > 0 : true));
    return Math.round((completedFields.length / fields.length) * 100);
});

const currentBadge = computed(() => {
    const count = auth.user?.prestataire?.missions_count || 0;
    if (count >= 20) {
        return { 
            name: 'Premium', color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200', icon: Zap 
        };
    } else if (count >= 5) {
        return { 
            name: 'Or', color: 'text-yellow-600', bg: 'bg-yellow-50', border: 'border-yellow-200', icon: Star 
        };
    } else {
        return { 
            name: 'Bronze', color: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200', icon: ShieldCheck 
        };
    }
});

const currentCategoryNames = computed(() => {
    if (!categories.value || !profileForm.value.category_ids || profileForm.value.category_ids.length === 0) return 'Non spécifié';
    const names = [];
    categories.value.forEach(group => {
        if (group.categories && Array.isArray(group.categories)) {
            group.categories.forEach(cat => {
                if (profileForm.value.category_ids.some(id => id == cat.id)) {
                    names.push(cat.name);
                }
            });
        } else if (group.id && profileForm.value.category_ids.some(id => id == group.id)) {
             names.push(group.name);
        }
    });
    return names.length > 0 ? names.join(' • ') : 'Non spécifié';
});

const selectedCategoryList = computed(() => {
    if (!categories.value || !profileForm.value.category_ids || profileForm.value.category_ids.length === 0) return [];
    const names = [];
    categories.value.forEach(group => {
        if (group.categories && Array.isArray(group.categories)) {
            group.categories.forEach(cat => {
                if (profileForm.value.category_ids.some(id => id == cat.id)) {
                    names.push(cat.name);
                }
            });
        } else if (group.id && profileForm.value.category_ids.some(id => id == group.id)) {
            // Handle flat structure fallback
            names.push(group.name);
        }
    });
    return names;
});

const userFullName = computed(() => {
  return `${profileForm.value.first_name} ${profileForm.value.last_name}`.trim() || auth.user?.name;
});

const activeMissions = computed(() => {
  return applications.value.filter(a => ['accepted', 'completed'].includes(a.status));
});

const pendingApplications = computed(() => {
  return applications.value.filter(a => a.status === 'pending');
});

const performanceData = computed(() => {
    // Mocking performance evolution for the visualization
    // In a real app, this might come from an analytics endpoint
    return [
        { label: 'Lun', value: 20 },
        { label: 'Mar', value: 45 },
        { label: 'Mer', value: 30 },
        { label: 'Jeu', value: 65 },
        { label: 'Ven', value: 50 },
        { label: 'Sam', value: 80 },
        { label: 'Dim', value: 95 },
    ];
});

const nextLevel = computed(() => {
    const score = auth.user?.prestataire?.pro_score || 0;
    if (score < 100) return { name: 'Confirmé', target: 100, remaining: 100 - score };
    if (score < 300) return { name: 'Expert', target: 300, remaining: 300 - score };
    return null;
});

const getBadgeClass = (level) => {
    switch (level) {
        case 'Expert': return 'bg-premium-brown text-white shadow-xl shadow-orange-900/20';
        case 'Confirmé': return 'bg-premium-blue text-white shadow-xl shadow-blue-900/20';
        default: return 'bg-gray-400 text-white';
    }
};

const getScoreColor = (score) => {
    if (score >= 300) return 'text-premium-brown';
    if (score >= 100) return 'text-premium-blue';
    return 'text-gray-400';
};

const stats = computed(() => {
  return {
    total: applications.value.length,
    accepted: applications.value.filter(a => a.status === 'accepted').length,
    completed: applications.value.filter(a => a.status === 'completed').length,
  };
});

const getStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-50 text-yellow-700 border-yellow-100';
    case 'accepted': return 'bg-green-50 text-green-700 border-green-100';
    case 'rejected': return 'bg-red-50 text-red-700 border-red-100';
    case 'completed': return 'bg-blue-50 text-blue-700 border-blue-100';
    default: return 'bg-gray-50 text-gray-700 border-gray-100';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'pending': return 'En attente';
    case 'accepted': return 'Acceptée';
    case 'rejected': return 'Refusée';
    case 'completed': return 'Terminée';
    default: return status;
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  });
};

const updateStatus = async (id, status) => {
  try {
    await api.post(`/api/service-requests/${id}/status`, { status });
    await fetchApplications();
  } catch (err) {
    alert('Erreur lors de la mise à jour du statut');
  }
};

const orderedDays = computed(() => {
    const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const labels = {
        monday: 'Lundi', tuesday: 'Mardi', wednesday: 'Mercredi', thursday: 'Jeudi',
        friday: 'Vendredi', saturday: 'Samedi', sunday: 'Dimanche'
    };
    
    return dayKeys.map(key => ({
        key,
        label: labels[key],
        ...(profileForm.value.availabilities[key] || { active: false, start: '00:00', end: '00:00' })
    }));
});
</script>

<template>
  <div :class="cvMode ? 'max-w-[1500px] p-0 pb-0' : 'max-w-7xl px-4 sm:px-6 lg:px-8 py-8 pb-32'" class="mx-auto font-sans selection:bg-premium-yellow selection:text-slate-900 overflow-x-hidden transition-all duration-300">
    <!-- Modern Notifications -->
    <Transition name="fade">
        <div v-if="success || error" class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-6 pointer-events-none">
            <div :class="success ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-red-600 shadow-red-500/20'" class="p-4 rounded-4xl text-white shadow-2xl backdrop-blur-md flex items-center space-x-4 animate-in slide-in-from-top-10 duration-500 pointer-events-auto">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <CheckCircle v-if="success" class="w-6 h-6" />
                    <XCircle v-else class="w-6 h-6" />
                </div>
                <p class="font-black text-xs uppercase tracking-widest leading-tight grow">{{ success || error }}</p>
                <button @click="success = null; error = null" class="w-8 h-8 rounded-xl hover:bg-white/10 transition-colors flex items-center justify-center">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </Transition>

    <!-- Header: Premium Greetings -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div class="space-y-2">
            <div class="inline-flex items-center space-x-2 bg-premium-yellow/10 px-4 py-1.5 rounded-full border border-premium-yellow/20">
                <div class="w-1.5 h-1.5 bg-premium-yellow rounded-full animate-pulse"></div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-premium-brown">Espace Professionnel</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tighter">
                Bonjour, <span class="text-premium-brown">{{ auth.user?.first_name || auth.user?.name }}</span>
            </h1>
            <div class="flex items-center space-x-3">
                <div v-if="visibility" class="flex items-center px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100 text-[10px] font-black uppercase tracking-widest">
                    <CheckCircle class="w-3 h-3 mr-1.5" />
                    Profil en ligne
                </div>
                <div v-else class="flex items-center px-3 py-1 bg-slate-50 text-slate-400 rounded-full border border-slate-200 text-[10px] font-black uppercase tracking-widest">
                    <XCircle class="w-3 h-3 mr-1.5" />
                    Profil masqué
                </div>
                <div v-if="nextLevel" class="text-[10px] text-slate-400 font-bold">
                    {{ nextLevel.remaining }} pts pour le badge <span class="text-slate-900">{{ nextLevel.name }}</span>
                </div>
            </div>
        </div>
        
        <div class="relative group cursor-pointer" @click="switchTab('profile')">
            <div class="w-20 h-20 md:w-24 md:h-24 rounded-[2.5rem] bg-white shadow-2xl border-4 border-white overflow-hidden transition-transform duration-500 group-hover:scale-105 group-hover:rotate-3">
                <img 
                   :src="auth.user?.prestataire?.photo_url || `https://ui-avatars.com/api/?name=${auth.user?.first_name}+${auth.user?.last_name}&background=f1f5f9&color=cbd5e1&size=256&font-size=0.33`" 
                   class="w-full h-full object-cover"
                />
            </div>
            <div class="absolute -bottom-2 -right-2 bg-premium-yellow p-2.5 rounded-2xl shadow-xl transform rotate-12 group-hover:rotate-0 transition-all border-4 border-white">
                <Settings class="w-4 h-4 text-slate-900" />
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        
        <!-- Sidebar: Stats & Progress -->
        <div class="lg:col-span-4 space-y-8 sticky top-28 order-2 lg:order-none">
            <!-- Profile Completion Card (Wizard style) -->
            <div class="bg-slate-900 rounded-[2.5rem] md:rounded-[3rem] p-6 md:p-8 text-white shadow-2xl relative overflow-hidden group">
                <div class="relative z-10 space-y-8">
                    <div class="space-y-2">
                        <h3 class="text-xl md:text-2xl font-black tracking-tight">Optimisez votre vitrine</h3>
                        <p class="text-slate-400 text-xs font-medium leading-relaxed">
                            Complétez votre profil pour apparaître en tête des résultats de recherche.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-premium-yellow opacity-80">Score de complétion</span>
                                <div class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">Profil Public</div>
                            </div>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-4xl font-black text-white tracking-tighter">{{ profileCompletion }}</span>
                                <span class="text-sm font-black text-premium-yellow">%</span>
                            </div>
                        </div>
                        <div class="h-4 bg-white/5 rounded-2xl overflow-hidden p-1 border border-white/5 shadow-inner">
                            <div 
                                class="h-full bg-linear-to-r from-premium-yellow via-yellow-400 to-yellow-500 rounded-xl shadow-[0_0_20px_rgba(250,204,21,0.3)] transition-all duration-500 relative"
                                :style="{ width: profileCompletion + '%' }"
                            >
                                <div class="absolute inset-0 bg-linear-to-b from-white/20 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-premium-yellow/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 left-4 opacity-5 pointer-events-none">
                    <TrendingUp class="w-32 h-32" />
                </div>
            </div>

            <!-- Pro Score Card -->
            <div class="bg-white rounded-[3rem] p-8 shadow-sm border border-slate-100 relative group overflow-hidden">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-widest">Reputation</h3>
                    <Award class="w-5 h-5 text-premium-brown" />
                </div>

                <div class="flex items-center gap-8 mb-8">
                    <div class="relative w-28 h-28 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="56" cy="56" r="50" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
                            <circle 
                                cx="56" cy="56" r="50" stroke="#0f172a" stroke-width="10" fill="transparent" 
                                :stroke-dasharray="314" 
                                :stroke-dashoffset="314 - (Math.min(auth.user?.prestataire?.pro_score || 0, 300) / 300) * 314"
                                stroke-linecap="round"
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black text-slate-900 tracking-tighter">{{ auth.user?.prestataire?.pro_score || 0 }}</span>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest text-center leading-none">Pro<br>Score</span>
                        </div>
                    </div>
                    
                    <div class="space-y-4 flex-1">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 group-hover:border-premium-yellow transition-colors">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Missions</span>
                                <span class="text-xs font-black text-slate-900">+{{ (auth.user?.prestataire?.completed_missions_count || 0) * 10 }}</span>
                            </div>
                            <div class="h-1 bg-slate-200 rounded-full">
                                <div class="h-full bg-slate-900 rounded-full" :style="{ width: '60%' }"></div>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 group-hover:border-premium-yellow transition-colors">
                             <div class="flex justify-between items-center mb-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Qualité</span>
                                <span class="text-xs font-black text-slate-900">+{{ Math.round((auth.user?.prestataire?.rating || 0) * 20) }}</span>
                            </div>
                            <div class="h-1 bg-slate-200 rounded-full">
                                <div class="h-full bg-premium-yellow rounded-full" :style="{ width: '80%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Card -->
            <div class="bg-indigo-600 p-8 rounded-[3rem] shadow-xl shadow-indigo-200 relative overflow-hidden group/support flex flex-col justify-between">
                <div class="relative z-10 space-y-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                        <LifeBuoy class="w-6 h-6 text-white" />
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-white font-black text-lg">{{ $t('common.support_title') }}</h4>
                        <p class="text-indigo-100 text-[10px] font-medium leading-relaxed">{{ $t('common.support_desc') }}</p>
                    </div>
                    <router-link to="/messages" class="inline-flex items-center space-x-2 bg-white text-indigo-600 px-6 py-3 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-lg active:scale-95">
                        <span>{{ $t('common.contact_admin') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </router-link>
                </div>
                <!-- Decor -->
                <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover/support:scale-150 transition-transform duration-300"></div>
            </div>
        </div>
        <!-- Main Content Area -->
        <div class="lg:col-span-8 order-1 lg:order-none">

            <!-- Navigation Tabs -->
            <div class="flex items-center space-x-10 border-b border-slate-100 mb-10 pb-0.5 overflow-x-auto scrollbar-hide">
                <button 
                  v-for="tab in [
                    { id: 'overview', label: 'Vue d\'ensemble' },
                    { id: 'missions', label: 'Mes Missions' },
                    { id: 'messages', label: 'Messagerie' },
                    { id: 'profile', label: 'Mon Profil & Vitrine' },
                    { id: 'settings', label: 'Paramètres & Sécurité' }
                  ]"
                  :key="tab.id"
                  @click="switchTab(tab.id)"
                  class="pb-5 relative group transition-all shrink-0"
                  :class="activeTab === tab.id ? 'text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                >
                    <span class="text-sm font-black uppercase tracking-[0.2em]">{{ tab.label }}</span>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-slate-900 rounded-full transition-all scale-x-0 group-hover:scale-x-50" :class="{ 'scale-x-100': activeTab === tab.id }"></div>
                    <div v-if="(tab.id === 'overview' || tab.id === 'missions') && pendingApplications?.length > 0" class="absolute -top-1 -right-4 w-4 h-4 bg-premium-yellow rounded-full flex items-center justify-center text-[8px] font-black text-slate-900 border-2 border-white animate-bounce-slow">
                        {{ pendingApplications.length }}
                    </div>
                </button>
            </div>

            <!-- Content: Overview -->
            <div v-if="activeTab === 'overview'" class="space-y-10 animate-in fade-in slide-in-from-bottom-5 duration-300">
                <!-- Certification Status (If certified) -->
                <div v-if="auth.user?.prestataire?.is_certified" class="bg-linear-to-br from-premium-blue to-slate-800 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div class="flex items-center space-x-8">
                            <div class="w-20 h-20 bg-premium-yellow rounded-3xl flex items-center justify-center shadow-2xl shadow-yellow-500/20 transform group-hover:rotate-6 transition-transform">
                                <ShieldCheck class="w-12 h-12 text-premium-blue" />
                            </div>
                            <div class="space-y-2">
                                <h2 class="text-3xl font-black tracking-tighter">Artisan Certifié <span class="text-premium-yellow">Valora</span></h2>
                                <p class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] italic flex items-center">
                                    <CheckCircle class="w-3.5 h-3.5 mr-2 text-premium-yellow" />
                                    Identité & Compétences Validées
                                </p>
                            </div>
                        </div>
                        <router-link to="/provider/certificate" class="bg-white text-slate-900 px-10 py-5 rounded-[2rem] font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:scale-105 transition-all shadow-2xl active:scale-95 text-center flex items-center justify-center gap-2 group">
                             <Award class="w-5 h-5 text-premium-brown group-hover:text-slate-900 transition-colors" />
                            Voir mon certificat
                        </router-link>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div v-for="stat in [
                        { icon: FileText, label: 'Applications', value: stats.total, color: 'text-purple-600', bg: 'bg-purple-50' },
                        { icon: Star, label: 'Note Moyenne', value: reviewsData.average_rating, color: 'text-amber-600', bg: 'bg-amber-50', sub: reviewsData.total_reviews + ' avis' },
                        { icon: TrendingUp, label: 'Réussites', value: stats.completed, color: 'text-blue-600', bg: 'bg-blue-50' }
                    ]" :key="stat.label" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500 group">
                        <div class="flex flex-col items-center text-center space-y-4">
                            <div class="w-14 h-14 rounded-3xl flex items-center justify-center transition-transform group-hover:scale-110" :class="stat.bg">
                                <component :is="stat.icon" class="w-7 h-7" :class="stat.color" />
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</h4>
                                <div class="text-3xl font-black text-slate-900 tracking-tighter">{{ stat.value }}</div>
                                <p v-if="stat.sub" class="text-[9px] text-slate-400 font-bold mt-1 uppercase tracking-widest">{{ stat.sub }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Missions -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between px-4">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Missions Actives</h3>
                        <router-link to="/search" class="text-[10px] font-black text-premium-brown uppercase tracking-widest hover:text-slate-900 transition-colors">Explorer plus <ArrowRight class="inline w-3 h-3 ml-1" /></router-link>
                    </div>

                    <div v-if="activeMissions.length === 0" class="bg-white rounded-[3rem] p-16 text-center border-2 border-dashed border-slate-100 group hover:border-premium-yellow transition-colors cursor-pointer" @click="$router.push('/search')">
                        <div class="max-w-xs mx-auto space-y-6">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto transition-transform group-hover:scale-110 group-hover:bg-premium-yellow/10">
                                <Search class="w-8 h-8 text-slate-300 group-hover:text-premium-yellow" />
                            </div>
                            <p class="text-slate-400 text-sm font-medium">Vous n'avez aucune mission en cours. Commencez à postuler pour booster votre activité !</p>
                            <span class="inline-block bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl">Rechercher des missions</span>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="mission in activeMissions" :key="mission.id" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group">
                            <div class="space-y-6">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center group-hover:bg-premium-yellow/20 transition-colors">
                                            <Briefcase class="w-6 h-6 text-blue-600 group-hover:text-slate-900" />
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 text-sm leading-tight group-hover:text-premium-brown transition-colors">{{ mission.service_offer?.title }}</h4>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ mission.service_offer?.user?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">Active</div>
                                </div>
                                
                                <div class="bg-slate-50 p-4 rounded-2xl space-y-3">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-500">
                                        <span>Progression</span>
                                        <span class="text-slate-900">En cours</span>
                                    </div>
                                    <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-linear-to-r from-blue-500 to-blue-400 rounded-full w-2/3 shadow-sm"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-1.5 bg-slate-50 px-3 py-1.5 rounded-xl text-[10px] font-black text-slate-500">
                                        <MapPin class="w-3 h-3" />
                                        <span>{{ mission.service_offer?.city || 'Maroc' }}</span>
                                    </div>
                                    <button @click="switchTab('missions')" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-lg active:scale-95">
                                        Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Feedback -->
                <div class="bg-slate-900 rounded-[3.5rem] p-10 text-white relative overflow-hidden group shadow-2xl shadow-slate-900/40">
                    <div class="relative z-10 flex flex-col md:flex-row gap-10">
                        <div class="md:w-1/3 space-y-4">
                            <h3 class="text-2xl font-black tracking-tight">Derniers Avis</h3>
                            <div class="flex items-center space-x-2">
                                <div class="flex text-premium-yellow">
                                    <Star v-for="i in 5" :key="i" class="w-5 h-5 fill-current" />
                                </div>
                                <span class="text-3xl font-black">{{ reviewsData.average_rating }}</span>
                            </div>
                            <p class="text-slate-400 text-xs font-medium leading-relaxed">
                                Votre réputation est votre meilleur atout. Continuez à fournir un service d'excellence.
                            </p>
                        </div>

                        <div class="flex-1">
                            <div v-if="reviewsData.reviews.length > 0" class="space-y-6">
                                <div class="bg-white/5 backdrop-blur-md p-8 rounded-[2.5rem] border border-white/10 relative">
                                    <p class="text-lg italic text-slate-200 font-medium leading-relaxed mb-8">
                                        "{{ reviewsData.reviews[0].comment }}"
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-premium-yellow font-black text-sm">
                                                {{ reviewsData.reviews[0].reviewer?.name.substring(0,2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <h5 class="text-sm font-black">{{ reviewsData.reviews[0].reviewer?.name }}</h5>
                                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest italic">Client vérifié</p>
                                            </div>
                                        </div>
                                        <div class="bg-white/10 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            {{ formatDate(reviewsData.reviews[0].created_at) }}
                                        </div>
                                    </div>
                                    <MessageCircle class="absolute top-6 right-8 w-12 h-12 text-white/5" />
                                </div>
                            </div>
                            <div v-else class="h-full flex items-center justify-center border-2 border-dashed border-white/10 rounded-[2.5rem] py-12">
                                <p class="text-slate-500 font-black uppercase tracking-widest text-[10px]">Aucun avis pour le moment</p>
                            </div>
                        </div>
                    </div>
                    <!-- Decor -->
                     <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl"></div>
                </div>
            </div>

            <!-- TAB: MISSIONS (New) -->
            <div v-if="activeTab === 'missions'" class="space-y-8 animate-in fade-in slide-in-from-bottom-5 duration-300">
                <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                    <h3 class="text-xl font-black text-slate-900 tracking-tight mb-8 flex items-center">
                        <Briefcase class="w-6 h-6 mr-3 text-premium-brown" />
                        Toutes vos missions
                    </h3>

                    <div v-if="loading" class="text-center py-20">
                         <Loader2 class="w-10 h-10 animate-spin text-premium-yellow mx-auto" />
                    </div>

                    <div v-else-if="applications.length === 0" class="text-center py-20 border-2 border-dashed border-slate-100 rounded-[2.5rem]">
                        <p class="text-slate-400 font-bold">Aucune candidature ou mission trouvée.</p>
                        <router-link to="/search" class="mt-4 inline-block bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:text-slate-900 shadow-xl transition-all">
                            Trouver des missions
                        </router-link>
                    </div>

                    <div v-else class="space-y-6">
                         <div v-for="app in applications" :key="app.id" class="p-6 bg-slate-50 rounded-[2.5rem] border border-slate-100 md:flex md:items-center md:justify-between group hover:bg-white hover:shadow-xl hover:border-premium-yellow/20 transition-all duration-300">
                            <div class="space-y-2 md:w-1/3">
                                <div class="flex items-center space-x-3">
                                    <div class="p-3 bg-white rounded-xl shadow-sm">
                                        <Briefcase class="w-5 h-5 text-slate-700" />
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 leading-tight">{{ app.service_offer?.title }}</h4>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ app.service_offer?.city }} • {{ formatDate(app.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 md:mt-0 md:w-1/4 flex items-center space-x-3">
                                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600 text-xs">
                                    {{ app.service_offer?.user?.name.substring(0,2).toUpperCase() }}
                                </div>
                                <div class="text-sm font-bold text-slate-700">{{ app.service_offer?.user?.name }}</div>
                            </div>

                            <div class="mt-4 md:mt-0 flex items-center justify-between md:justify-end gap-4 md:w-1/3">
                                <div :class="getStatusClass(app.status)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border">
                                    {{ getStatusLabel(app.status) }}
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button 
                                        v-if="app.status === 'pending' && app.created_by_id !== auth.user?.id" 
                                        @click="updateStatus(app.id, 'accepted')"
                                        class="p-2 bg-emerald-100 text-emerald-600 rounded-xl hover:bg-emerald-200 transition-colors"
                                        title="Accepter"
                                    >
                                        <Check class="w-5 h-5" />
                                    </button>
                                    <button 
                                        v-if="app.status === 'pending' && app.created_by_id !== auth.user?.id" 
                                        @click="updateStatus(app.id, 'rejected')"
                                        class="p-2 bg-red-100 text-red-600 rounded-xl hover:bg-red-200 transition-colors"
                                        title="Refuser"
                                    >
                                        <X class="w-5 h-5" />
                                    </button>
                                     <button 
                                        v-if="app.status === 'accepted'" 
                                        @click="updateStatus(app.id, 'completed')"
                                        class="p-2 bg-blue-100 text-blue-600 rounded-xl hover:bg-blue-200 transition-colors"
                                        title="Marquer comme terminé"
                                    >
                                        <CheckCircle class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
    
    <!-- Other Tabs (Profile, Settings) kept simple for now or reused from previous structure if needed -->
    <!-- ... same as before but wrapped in v-show ... -->



            <!-- TAB: PROFILE -->
            <div v-if="activeTab === 'profile'" class="space-y-10 animate-in fade-in slide-in-from-bottom-5 duration-300">
                <!-- CV / Edit Toggle -->
                <div class="flex items-center justify-between bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex items-center space-x-4 pl-4">
                        <button 
                            @click="cvMode = !cvMode"
                            class="p-2 bg-premium-yellow/10 rounded-xl hover:bg-premium-yellow hover:text-slate-900 transition-all active:scale-95 cursor-pointer"
                            title="Basculer le mode"
                            type="button"
                        >
                            <Eye v-if="cvMode" class="w-5 h-5 text-premium-brown group-hover:text-slate-900" />
                            <PenTool v-else class="w-5 h-5 text-premium-brown group-hover:text-slate-900" />
                        </button>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">
                            {{ cvMode ? 'Aperçu de votre CV' : 'Modifier ma vitrine' }}
                        </h3>
                    </div>
                    <button 
                        @click="cvMode = !cvMode"
                        class="flex items-center space-x-3 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95"
                        :class="cvMode ? 'bg-slate-900 text-white shadow-xl shadow-slate-900/20' : 'bg-premium-yellow text-slate-900 shadow-xl shadow-yellow-500/10 hover:bg-premium-brown hover:text-white'"
                    >
                        <span>{{ cvMode ? 'Mode Édition' : 'Aperçu CV' }}</span>
                        <ArrowRight v-if="!cvMode" class="w-4 h-4" />
                        <Settings v-else class="w-4 h-4" />
                    </button>
                </div>

                <!-- CV MODE VIEW (Canva Style Refined) -->
                <div v-if="cvMode" class="space-y-6 animate-in fade-in zoom-in-95 duration-300 pb-20 justify-center flex flex-col items-center">
                    
                    <!-- Floating Actions (Compact & Functional) -->
                    <div class="w-full max-w-[21cm] flex justify-between items-center px-4 md:px-0 z-50 print:hidden sticky top-4">
                         <button 
                            @click="cvMode = false" 
                            class="group flex items-center space-x-2 text-slate-400 hover:text-slate-900 transition-all duration-300"
                        >
                            <div class="p-2 rounded-full bg-white shadow-sm border border-slate-200 group-hover:border-slate-300 group-hover:scale-110 transition-all">
                                <ArrowLeft class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-opacity duration-300">Retour</span>
                        </button>

                        <div class="flex items-center space-x-3 scale-90 origin-right">
                             <button 
                                @click="deleteAccount" 
                                class="flex items-center space-x-2 px-4 py-2 rounded-full bg-white text-red-500 border border-red-100 hover:bg-red-500 hover:text-white transition-all font-black text-[10px] uppercase tracking-widest shadow-sm hover:shadow-red-500/30 group active:scale-95"
                                title="Supprimer le CV"
                            >
                                <Trash2 class="w-3.5 h-3.5 group-hover:rotate-12 transition-transform" />
                                <span class="hidden sm:inline">Supprimer</span>
                            </button>
                             <button 
                                @click="generatePDF" 
                                class="flex items-center space-x-2 px-4 py-2 rounded-full bg-white text-slate-900 border border-slate-200 hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-all font-black text-[10px] uppercase tracking-widest shadow-sm hover:shadow-xl group active:scale-95"
                            >
                                <Download class="w-3.5 h-3.5 group-hover:-translate-y-0.5 transition-transform" />
                                <span class="hidden sm:inline">Télécharger</span>
                            </button>
                            <button 
                                @click="cvMode = false" 
                                class="flex items-center space-x-2 px-5 py-2 rounded-full bg-premium-yellow text-slate-900 border border-premium-yellow hover:bg-premium-brown hover:text-white hover:border-premium-brown transition-all shadow-lg hover:shadow-premium-yellow/20 font-black text-[10px] uppercase tracking-widest group active:scale-95"
                            >
                                <PenTool class="w-3.5 h-3.5 group-hover:rotate-12 transition-transform" />
                                <span>Modifier</span>
                            </button>
                        </div>
                    </div>

                    <!-- The Creative Document (Refined & Compact) -->
                    <div id="cv-content" class="w-full max-w-[21cm] bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.1)] relative overflow-hidden flex flex-col md:flex-row min-h-fit print:w-full print:max-w-none group/doc rounded-sm">
                        
                        <!-- LEFT SIDEBAR (Dark - Compact) -->
                        <div class="w-full md:w-[35%] bg-[#1e293b] text-white relative overflow-hidden p-8 flex flex-col">
                            <!-- Background Depth -->
                            <div class="absolute inset-0 bg-linear-to-b from-[#1e293b] to-[#0f172a]"></div>
                            <div class="absolute top-0 left-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-[80px] -ml-20 -mt-20"></div>

                            <!-- Photo Section -->
                            <div class="relative z-10 flex flex-col items-center mb-10 pt-4">
                                <div class="w-40 h-40 rounded-full p-1.5 border border-white/10 relative group-hover/doc:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 rounded-full border border-premium-yellow/30 scale-110 opacity-0 group-hover/doc:opacity-100 transition-all duration-300 delay-100"></div>
                                    <div class="w-full h-full rounded-full overflow-hidden bg-slate-800 shadow-2xl relative">
                                        <img 
                                            :src="auth.user?.prestataire?.photo_url || `https://ui-avatars.com/api/?name=${auth.user?.first_name}+${auth.user?.last_name}&background=cbd5e1&color=1e293b&size=256&font-size=0.33`" 
                                            class="w-full h-full object-cover"
                                            alt="Profile"
                                            crossorigin="anonymous"
                                            loading="lazy"
                                        >
                                    </div>
                                    <!-- Certified Badge -->
                                    <div v-if="auth.user?.prestataire?.is_certified" class="absolute bottom-1 right-1 bg-emerald-500 text-white p-2 rounded-full border-[3px] border-[#1e293b] shadow-lg">
                                        <ShieldCheck class="w-3.5 h-3.5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar Content -->
                            <div class="space-y-10 relative z-10 flex-1">
                                <!-- Contact -->
                                <div>
                                    <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
                                        <span class="w-6 h-px bg-premium-yellow/50"></span> Contact
                                    </h4>
                                    <ul class="space-y-4">
                                        <li class="flex items-center gap-3 group/contact">
                                            <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0 group-hover/contact:bg-premium-yellow group-hover/contact:text-slate-900 transition-colors">
                                                <Phone class="w-3.5 h-3.5" />
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-[11px] font-medium text-slate-200 block truncate">{{ profileForm.phone || 'Non renseigné' }}</span>
                                            </div>
                                        </li>
                                        <li class="flex items-center gap-3 group/contact">
                                            <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0 group-hover/contact:bg-premium-yellow group-hover/contact:text-slate-900 transition-colors">
                                                <Mail class="w-3.5 h-3.5" />
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-[11px] font-medium text-slate-200 block truncate">{{ auth.user?.email }}</span>
                                            </div>
                                        </li>
                                         <li class="flex items-center gap-3 group/contact">
                                            <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0 group-hover/contact:bg-premium-yellow group-hover/contact:text-slate-900 transition-colors">
                                                <MapPin class="w-3.5 h-3.5" />
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-[11px] font-medium text-slate-200 block truncate">{{ profileForm.city || 'Maroc' }}</span>
                                            </div>
                                        </li>
                                    </ul>

                                </div>

                                <!-- Services (New Section) -->
                                <div v-if="selectedCategoryList.length > 0">
                                    <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
                                        <span class="w-6 h-px bg-premium-yellow/50"></span> Services
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span 
                                            v-for="service in selectedCategoryList" 
                                            :key="service" 
                                            class="px-3 py-1.5 bg-premium-yellow/10 border border-premium-yellow/20 rounded-md text-[11px] font-bold text-premium-yellow uppercase tracking-wide"
                                        >
                                            {{ service }}
                                        </span>
                                        <!-- Custom Services Mixed In -->
                                        <span 
                                            v-for="skill in customServicesList" 
                                            :key="skill" 
                                            class="px-3 py-1.5 bg-white/10 border border-white/10 rounded-md text-[11px] font-bold text-slate-300 uppercase tracking-wide"
                                        >
                                            {{ skill }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Languages (New) -->
                                <div v-if="profileForm.languages && profileForm.languages.length > 0">
                                    <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
                                        <span class="w-6 h-px bg-premium-yellow/50"></span> Langues
                                    </h4>
                                    <ul class="space-y-3">
                                        <li v-for="(lang, idx) in profileForm.languages" :key="idx" class="flex items-end justify-between border-b border-white/5 pb-1">
                                            <span class="text-xs font-bold text-slate-200">{{ lang.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">{{ lang.level }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Skills -->
                                <div v-if="profileForm.skills">
                                    <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
                                        <span class="w-6 h-px bg-premium-yellow/50"></span> Expertise
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span 
                                            v-for="skill in profileForm.skills.split(',')" 
                                            :key="skill" 
                                            class="px-2.5 py-1 bg-white/5 border border-white/5 rounded text-[10px] font-bold text-slate-300 uppercase tracking-wider hover:bg-white hover:text-slate-900 transition-colors cursor-default"
                                        >
                                            {{ skill.trim() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Brand Footer -->
                            <div class="mt-auto pt-10 border-t border-white/5 text-center opacity-20">
                                <span class="text-3xl font-black tracking-tighter text-white">VALORA</span>
                            </div>
                        </div>

                        <!-- RIGHT MAIN CONTENT (White - Structured) -->
                        <div class="w-full md:w-[65%] bg-white p-10 flex flex-col relative">
                            <!-- Header -->
                            <div class="relative z-10 mb-10 pb-6 border-b border-slate-100">
                                <div class="inline-flex items-center space-x-2 mb-2">
                                     <span class="w-8 h-0.5 bg-premium-yellow"></span>
                                     <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 whitespace-normal leading-relaxed">{{ currentCategoryNames }}</span>
                                </div>
                                <h1 class="text-4xl md:text-5xl font-black text-slate-900 uppercase tracking-tighter leading-[0.9]">
                                    {{ profileForm.first_name || auth.user?.first_name }}
                                    <span class="text-slate-400 block">{{ profileForm.last_name || auth.user?.last_name }}</span>
                                </h1>
                            </div>

                            <!-- Bio -->
                            <div class="relative z-10 mb-10">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <User class="w-3.5 h-3.5 text-premium-yellow" /> Profil Pro
                                </h3>
                                <div class="text-sm text-slate-600 leading-relaxed font-medium text-justify">
                                    {{ profileForm.description || "Professionnel qualifié et rigoureux, prêt à intervenir pour vos missions." }}
                                </div>
                            </div>

                            <!-- Experience Timeline -->
                            <div class="relative z-10 mb-8 grow">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-6 flex items-center gap-2">
                                    <Briefcase class="w-3.5 h-3.5 text-premium-yellow" /> Expérience
                                </h3>
                                
                                <div v-if="!profileForm.achievements || profileForm.achievements.length === 0" class="py-6 border border-dashed border-slate-200 rounded-lg text-center bg-slate-50">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Aucune expérience ajoutée</span>
                                </div>

                                <div v-else class="space-y-0 relative pl-4">
                                    <!-- Timeline Line -->
                                    <div class="absolute left-[6px] top-1.5 bottom-4 w-px bg-slate-200"></div>

                                    <div v-for="(exp, idx) in profileForm.achievements" :key="idx" class="relative pl-8 pb-8 last:pb-0 group/timeline">
                                        <!-- Dot -->
                                        <div class="absolute -left-[5px] top-1.5 w-[11px] h-[11px] rounded-full bg-white border-[3px] border-slate-300 group-hover/timeline:border-premium-yellow group-hover/timeline:scale-125 transition-all z-10"></div>
                                        
                                        <div class="group-hover/timeline:translate-x-1 transition-transform duration-300">
                                            <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 mb-1">
                                                <h4 class="font-black text-slate-900 text-sm uppercase tracking-wide">{{ exp.split('@')[0].trim() }}</h4>
                                                <span v-if="exp.includes('@')" class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">{{ exp.split('@')[1].trim() }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 font-medium leading-relaxed" v-if="exp.split('@').length > 2">
                                                {{ exp.split('@')[2].trim() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Diplomas (Conditional - Strict) -->
                            <div v-if="profileForm.diplomas && profileForm.diplomas !== 'Non'" class="relative z-10 mt-auto pt-6 border-t border-slate-100">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <Award class="w-3.5 h-3.5 text-premium-yellow" /> Certifications
                                </h3>
                                <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100/50">
                                    <div class="p-2 bg-white shadow-sm rounded-lg text-premium-brown shrink-0">
                                        <ShieldCheck class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700 leading-snug whitespace-pre-line">{{ profileForm.diplomas }}</p>
                                        <div class="flex items-center gap-1 mt-1 text-[9px] font-black uppercase tracking-widest text-emerald-600">
                                            <CheckCircle class="w-3 h-3" /> <span>Vérifié par Valora</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT MODE WIZARD -->
                <div v-else class="space-y-10 animate-in fade-in duration-300">
                    <!-- Progress Indicator -->
                    <div class="max-w-3xl mx-auto space-y-4 mb-12">
                        <div class="flex items-end justify-between px-2">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Étape {{ currentStep }} sur 3</p>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">
                                    {{ currentStep === 1 ? 'Identité Visuelle' : (currentStep === 2 ? 'Expertise & Expérience' : 'Vos Disponibilités') }}
                                </h3>
                            </div>
                            <span class="text-lg font-black text-premium-yellow">{{ Math.round((currentStep / 3) * 100) }}%</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden p-0.5">
                            <div 
                                class="h-full bg-premium-yellow rounded-full transition-all duration-300 ease-out shadow-lg"
                                :style="{ width: `${(currentStep / 3) * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- STEP 1: Identité Visuelle -->
                    <div v-if="currentStep === 1" class="max-w-5xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="bg-white p-12 rounded-[3.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
                            <!-- Background Decor -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
                            
                            <div class="relative z-10 flex flex-col lg:flex-row gap-16">
                                <!-- Photo & Brand Side -->
                                <div class="lg:w-1/3 flex flex-col items-center space-y-8">
                                    <div class="relative group/photo">
                                        <div class="absolute -inset-4 bg-linear-to-tr from-premium-yellow/20 via-transparent to-premium-brown/20 rounded-[4rem] blur-2xl opacity-0 group-hover/photo:opacity-100 transition-opacity duration-300"></div>
                                        <PhotoUploader 
                                            :current-photo="auth.user?.prestataire?.photo_url" 
                                            @photo-updated="handlePhotoUpdate" 
                                            class="relative z-10"
                                        />
                                    </div>
                                    
                                    <!-- CV Upload Section -->
                                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 space-y-4 w-full relative overflow-hidden group/cv">
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50/50 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover/cv:bg-blue-100/50"></div>
                                        
                                        <div class="flex items-center justify-between relative z-10">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-colors" :class="auth.user?.prestataire?.cv_url ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-50 text-slate-400'">
                                                    <FileCheck v-if="auth.user?.prestataire?.cv_url" class="w-5 h-5" />
                                                    <FileText v-else class="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Votre CV</h4>
                                                    <p class="text-[9px] font-bold text-slate-400" v-if="auth.user?.prestataire?.cv_url">Document ajouté</p>
                                                    <p class="text-[9px] font-bold text-slate-400" v-else>Format PDF, DOCX (Max 5Mo)</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="relative z-10">
                                            <input 
                                                type="file" 
                                                ref="cvInput" 
                                                class="hidden" 
                                                accept=".pdf,.doc,.docx"
                                                @change="handleCvUpload"
                                            >
                                            <button 
                                                @click="$refs.cvInput.click()" 
                                                :disabled="cvUploading"
                                                class="w-full py-3 px-4 rounded-xl border-2 border-dashed border-slate-200 hover:border-premium-yellow hover:bg-premium-yellow/5 active:scale-95 transition-all flex items-center justify-center space-x-2 group/btn"
                                            >
                                                <Loader2 v-if="cvUploading" class="w-4 h-4 animate-spin text-slate-400" />
                                                <UploadCloud v-else class="w-4 h-4 text-slate-400 group-hover/btn:text-premium-yellow transition-colors" />
                                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest group-hover/btn:text-slate-900 transition-colors">
                                                    {{ cvUploading ? 'Envoi...' : (auth.user?.prestataire?.cv_url ? 'Remplacer le CV' : 'Ajouter un CV') }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100 space-y-4 w-full">
                                        <div class="flex items-center space-x-3 text-premium-brown">
                                            <ShieldCheck class="w-5 h-5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">Conseil Pro</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 leading-relaxed font-medium">
                                            Un portrait soigné et professionnel multiplie par **3** vos chances d'être contacté par de nouveaux clients.
                                        </p>
                                    </div>
                                </div>

                                <!-- Form Fields Side -->
                                <div class="flex-1 space-y-10">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-1.5 h-10 bg-premium-yellow rounded-full"></div>
                                            <div>
                                                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Identité Visuelle</h3>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Étape cruciale de votre profil</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                        <!-- Row 1: Identity -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 group-focus-within:text-premium-brown transition-colors">Prénom</label>
                                            <div class="relative">
                                                <User class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.first_name" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 group-focus-within:text-premium-brown transition-colors">Nom</label>
                                            <div class="relative">
                                                <Fingerprint class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.last_name" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>

                                        <!-- Row 2: Location & Birth -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Ville de résidence</label>
                                            <div class="relative z-50" id="city-dropdown-container">
                                                <!-- Custom Select Trigger -->
                                                <div 
                                                    @click="showCityDropdown = !showCityDropdown"
                                                    class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus-within:bg-white focus-within:ring-8 focus-within:ring-premium-yellow/5 focus-within:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm cursor-pointer flex items-center justify-between"
                                                >
                                                    <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                    <span :class="profileForm.city ? 'text-slate-900' : 'text-slate-400'">
                                                        {{ profileForm.city || 'Sélectionnez votre ville' }}
                                                    </span>
                                                    <ChevronDown 
                                                        class="w-4 h-4 text-slate-400 transition-transform duration-300"
                                                        :class="{ 'rotate-180': showCityDropdown }"
                                                    />
                                                </div>

                                                <!-- Custom Dropdown Menu -->
                                                <div 
                                                    v-if="showCityDropdown"
                                                    class="absolute top-full left-0 right-0 mt-2 bg-white rounded-3xl shadow-2xl border border-slate-100 z-[100] overflow-hidden animate-in fade-in slide-in-from-top-2 duration-300"
                                                >
                                                    <div class="p-4 border-b border-slate-50">
                                                        <div class="relative">
                                                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                                            <input 
                                                                v-model="cityQuery"
                                                                type="text"
                                                                placeholder="Rechercher une ville..."
                                                                class="w-full pl-10 pr-4 py-3 bg-slate-50 rounded-xl text-xs font-bold text-slate-900 outline-none border border-transparent focus:border-premium-yellow/30 transition-all"
                                                                @click.stop
                                                            >
                                                        </div>
                                                    </div>
                                                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                        <div 
                                                            v-for="city in filteredCities" 
                                                            :key="city"
                                                            @click="selectCity(city)"
                                                            class="px-6 py-3.5 text-sm font-bold text-slate-600 hover:text-premium-brown hover:bg-premium-yellow/5 cursor-pointer transition-colors flex items-center justify-between group/item"
                                                        >
                                                            {{ city }}
                                                            <Check v-if="profileForm.city === city" class="w-4 h-4 text-premium-yellow" />
                                                        </div>
                                                        <div v-if="filteredCities.length === 0" class="px-6 py-8 text-center">
                                                            <p class="text-xs font-bold text-slate-400">Aucune ville trouvée</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Adresse Complète</label>
                                            <div class="relative">
                                                <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.address" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm" placeholder="Ex: 123 Bd Zerktouni, Maarif">
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Date de naissance</label>
                                            <div class="relative">
                                                <Calendar class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.birth_date" type="date" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>

                                        <!-- Row 3: ID & Phone -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">CIN</label>
                                            <div class="relative">
                                                <Award class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.cin" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm" placeholder="Ex: AB123456">
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Téléphone</label>
                                            <div class="relative">
                                                <Smartphone class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.phone" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email (Locked) -->
                                    <div class="pt-6 border-t border-slate-50">
                                        <div class="flex items-center space-x-4 bg-slate-50/50 p-6 rounded-3xl border border-slate-100">
                                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-300">
                                                <Mail class="w-6 h-6" />
                                            </div>
                                            <div class="grow">
                                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Compte vérifié</p>
                                                <p class="text-sm font-black text-slate-700">{{ auth.user?.email }}</p>
                                            </div>
                                            <div class="p-2 bg-emerald-50 text-emerald-500 rounded-xl">
                                                <CheckCircle class="w-5 h-5" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Languages Section (Moved to Step 1) -->
                                    <div class="space-y-4 pt-4 border-t border-slate-50 mt-4">
                                        <div class="flex items-center justify-between px-2">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Langues</label>
                                            <button type="button" @click="addLanguage" class="w-8 h-8 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-900 hover:text-white transition-all flex items-center justify-center">
                                                <Plus class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="(lang, index) in profileForm.languages" :key="index" class="flex items-center gap-3 animate-in slide-in-from-right-2 duration-300">
                                                <input 
                                                    v-model="lang.name" 
                                                    type="text" 
                                                    placeholder="Langue (ex: Français)" 
                                                    class="flex-1 pl-4 pr-4 py-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-premium-yellow/20 outline-none text-xs font-bold"
                                                >
                                                <div class="relative w-32">
                                                    <select v-model="lang.level" class="w-full pl-3 pr-8 py-3 rounded-xl border border-slate-100 bg-slate-50 focus:bg-white outline-none text-xs font-bold appearance-none cursor-pointer">
                                                        <option>Débutant</option>
                                                        <option>Intermédiaire</option>
                                                        <option>Courant</option>
                                                        <option>Bilingue</option>
                                                        <option>Natif</option>
                                                    </select>
                                                    <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 pointer-events-none" />
                                                </div>
                                                <button type="button" @click="removeLanguage(index)" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div v-if="!profileForm.languages || profileForm.languages.length === 0" class="text-center py-4 text-[10px] text-slate-400 italic bg-slate-50/30 rounded-xl">
                                                Ajoutez les langues que vous maîtrisez
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Expertise & Expérience -->
                    <div v-if="currentStep === 2" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Categories Card -->
                            <div class="bg-white p-10 rounded-[3rem] shadow-xl border border-slate-100 flex flex-col h-[700px]">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-premium-yellow/10 rounded-2xl flex items-center justify-center border border-premium-yellow/20">
                                            <Briefcase class="w-6 h-6 text-premium-brown" />
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Expertise Pro</h3>
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mt-1">Choisissez vos spécialités</p>
                                        </div>
                                    </div>
                                    <div v-if="selectedCategoriesCount > 0" class="flex flex-col items-end">
                                        <span class="text-[10px] font-black bg-slate-900 text-white px-4 py-2 rounded-xl uppercase tracking-widest shadow-lg shadow-slate-900/10">{{ selectedCategoriesCount }} service{{ selectedCategoriesCount > 1 ? 's' : '' }}</span>
                                    </div>
                                </div>

                                <div class="relative group mb-6">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <Search class="h-4 w-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                    </div>
                                    <input 
                                        v-model="categorySearch" 
                                        type="text" 
                                        placeholder="Que savez-vous faire ?"
                                        class="w-full pl-12 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs shadow-inner"
                                    >
                                </div>

                                <div class="flex-1 overflow-y-auto pr-2 space-y-8 scrollbar-hide py-2">
                                    <div v-for="group in filteredCategoryGroups" :key="group.name" class="space-y-4">
                                        <div class="flex items-center space-x-3 sticky top-0 bg-white/90 backdrop-blur-sm z-10 py-2">
                                            <div class="h-px bg-slate-100 grow"></div>
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] px-3">{{ group.name }}</h4>
                                            <div class="h-px bg-slate-100 grow"></div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2.5">
                                            <label 
                                                v-for="cat in group.categories" 
                                                :key="cat.id"
                                                class="flex items-center space-x-4 p-4 rounded-2xl border transition-all cursor-pointer group/cat relative overflow-hidden"
                                                :class="profileForm.category_ids.includes(cat.id) ? 'bg-premium-yellow/5 border-premium-yellow/30 shadow-sm' : 'bg-slate-50 border-transparent hover:bg-slate-100 hover:border-slate-200'"
                                            >
                                                <div class="relative z-10 flex items-center justify-center w-5 h-5 rounded-md border-2 transition-all"
                                                     :class="profileForm.category_ids.includes(cat.id) ? 'bg-premium-yellow border-premium-yellow scale-110 shadow-lg shadow-yellow-500/20' : 'bg-white border-slate-200 group-hover/cat:border-slate-300'"
                                                >
                                                    <Check v-if="profileForm.category_ids.includes(cat.id)" class="w-3.5 h-3.5 text-slate-900" />
                                                </div>
                                                <input type="checkbox" :value="cat.id" v-model="profileForm.category_ids" class="hidden">
                                                <span class="relative z-10 text-[11px] font-bold text-slate-700 transition-colors group-hover/cat:text-slate-900">{{ cat.name }}</span>
                                                
                                                <!-- Subtle bg glow on hover -->
                                                <div class="absolute right-0 top-0 w-32 h-full bg-linear-to-l from-white/20 to-transparent translate-x-32 group-hover/cat:translate-x-0 transition-transform duration-500"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Experience & Bio Card -->
                            <div class="bg-white p-10 rounded-[3rem] shadow-xl border border-slate-100 space-y-10 flex flex-col h-[700px]">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center border border-amber-100 shadow-sm">
                                        <Trophy class="w-6 h-6 text-amber-500" />
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Parcours & Bio</h3>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mt-1">Racontez votre histoire</p>
                                    </div>
                                </div>

                                <div class="space-y-8 flex-1 overflow-y-auto pr-2 scrollbar-hide">
                                    <!-- Years of Experience Selector -->
                                    <div class="space-y-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Expérience globale</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button 
                                                v-for="opt in [
                                                    { value: 'Débutant', label: 'Débutant' },
                                                    { value: 'Entre 1 an et 2 ans', label: '1 - 2 ans' },
                                                    { value: '2 à 4 ans', label: '2 - 4 ans' },
                                                    { value: 'Plus de 4 ans', label: 'Plus de 4 ans' }
                                                ]"
                                                :key="opt.value"
                                                @click="profileForm.experience = opt.value"
                                                class="px-4 py-5 rounded-[1.5rem] font-black text-[10px] uppercase tracking-wider transition-all relative overflow-hidden group/opt flex items-center justify-center text-center"
                                                :class="profileForm.experience === opt.value ? 'bg-slate-900 text-white shadow-2xl scale-[1.02] z-10' : 'bg-slate-50 text-slate-500 border border-slate-100 hover:bg-white hover:shadow-lg'"
                                            >
                                                <span class="relative z-10">{{ opt.label }}</span>
                                                <div v-if="profileForm.experience === opt.value" class="absolute inset-0 bg-linear-to-br from-white/10 to-transparent"></div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Notable Experiences List -->
                                    <div class="space-y-5 pt-4">
                                        <div class="flex items-center justify-between px-2">
                                            <div class="space-y-1">
                                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Expériences marquantes</label>
                                                <p class="text-[8px] text-slate-400 font-medium">Ajoutez vos références clés</p>
                                            </div>
                                            <button @click="addDetailedExperience" class="w-10 h-10 bg-slate-900 text-white rounded-2xl hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-xl flex items-center justify-center active:scale-90">
                                                <Plus class="w-5 h-5" />
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="(exp, index) in profileForm.achievements" :key="index" class="relative group/item animate-in slide-in-from-right-2 duration-300">
                                                <div class="absolute left-4 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-premium-yellow"></div>
                                                <input 
                                                    v-model="profileForm.achievements[index]" 
                                                    type="text" 
                                                    placeholder="Ex: Rénovation Maison Casablanca..." 
                                                    class="w-full pl-10 pr-12 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-premium-yellow/10 focus:border-premium-yellow outline-none transition-all text-xs font-black text-slate-800 shadow-sm"
                                                >
                                                <button @click="removeDetailedExperience(index)" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all opacity-0 group-hover/item:opacity-100">
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div v-if="profileForm.achievements.length === 0" class="py-12 bg-slate-50/50 rounded-[2rem] border-2 border-dashed border-slate-100 flex flex-col items-center justify-center space-y-3 group cursor-pointer hover:border-premium-yellow/30 transition-colors" @click="addDetailedExperience">
                                                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-300 group-hover:scale-110 transition-transform">
                                                    <Zap class="w-6 h-6" />
                                                </div>
                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Aucune expérience ajoutée</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                     <!-- Skills Input (Tag System) -->
                                    <div class="space-y-4 pt-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Autres Services (Ajout libre)</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                                <Zap class="h-5 w-5 text-slate-300 transition-colors group-focus-within:text-premium-yellow" />
                                            </div>
                                            <input 
                                                v-model="newServiceInput" 
                                                @keydown.enter.prevent="addCustomService"
                                                type="text" 
                                                placeholder="Ajoutez un service et appuyez sur Entrée (Ex: Dépannage urgence)" 
                                                class="w-full pl-14 pr-16 py-5 rounded-[2rem] border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs shadow-inner"
                                            >
                                            <button 
                                                @click="addCustomService"
                                                class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-slate-900 text-white rounded-xl hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-md active:scale-90"
                                            >
                                                <Plus class="w-4 h-4" />
                                            </button>
                                        </div>
                                        
                                        <!-- Tags Display -->
                                        <div class="flex flex-wrap gap-2 px-2" v-if="customServicesList.length > 0">
                                            <span 
                                                v-for="(service, idx) in customServicesList" 
                                                :key="idx" 
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-white border border-slate-200 shadow-sm text-[11px] font-black text-slate-700 uppercase tracking-wide group/tag hover:border-premium-yellow/50 transition-colors"
                                            >
                                                {{ service }}
                                                <button @click="removeCustomService(service)" class="ml-2 text-slate-400 hover:text-red-500 transition-colors">
                                                    <X class="w-3 h-3" />
                                                </button>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Hourly Rate Input -->
                                    <div class="space-y-4 pt-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Taux Horaire (DH/h)</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                                <Coins class="h-5 w-5 text-slate-300 transition-colors group-focus-within:text-premium-yellow" />
                                            </div>
                                            <input 
                                                v-model="profileForm.hourly_rate" 
                                                type="number" 
                                                placeholder="Ex: 150" 
                                                class="w-full pl-14 pr-8 py-5 rounded-[2rem] border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs shadow-inner"
                                            >
                                        </div>
                                    </div>

                                    <div class="space-y-4 pt-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Bio Professionnelle</label>
                                        <div class="relative group">
                                            <textarea 
                                                v-model="profileForm.description" 
                                                rows="4" 
                                                placeholder="Partagez votre passion et votre rigueur..." 
                                                class="w-full px-8 py-6 rounded-[2rem] border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs resize-none shadow-inner leading-relaxed"
                                            ></textarea>
                                            <div class="absolute bottom-4 right-6 text-[8px] font-black text-slate-300 uppercase tracking-widest">Conseil: Soyez précis</div>
                                        </div>
                                    <!-- Formations & Certifications Section (New) -->
                                    <div class="space-y-4 pt-4 border-t border-slate-50 mt-4">
                                        <div class="flex items-center justify-between px-2">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Formations & Certifications</label>
                                            <button @click="addFormation" class="w-8 h-8 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-900 hover:text-white transition-all flex items-center justify-center">
                                                <Plus class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="(form, index) in profileForm.formations" :key="index" class="p-3 bg-slate-50/50 rounded-xl border border-slate-100 animate-in slide-in-from-right-2 duration-300 space-y-2">
                                                <div class="flex items-start justify-between">
                                                    <input 
                                                        v-model="form.title" 
                                                        type="text" 
                                                        placeholder="Titre (ex: Master Génie Civil)" 
                                                        class="w-full bg-transparent outline-none text-xs font-bold text-slate-900 placeholder:text-slate-300"
                                                    >
                                                    <button @click="removeFormation(index)" class="text-slate-300 hover:text-red-500 transition-colors ml-2">
                                                        <X class="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                                <div class="flex gap-2">
                                                    <input 
                                                        v-model="form.institution" 
                                                        type="text" 
                                                        placeholder="École / Organisme" 
                                                        class="flex-1 bg-white px-2 py-1.5 rounded-lg border border-slate-100 text-[10px] font-bold outline-none"
                                                    >
                                                    <input 
                                                        v-model="form.year" 
                                                        type="text" 
                                                        placeholder="Année" 
                                                        class="w-16 bg-white px-2 py-1.5 rounded-lg border border-slate-100 text-[10px] font-bold outline-none"
                                                    >
                                                </div>
                                            </div>
                                            <div v-if="!profileForm.formations || profileForm.formations.length === 0" class="text-center py-4 text-[10px] text-slate-400 italic bg-slate-50/30 rounded-xl">
                                                Ajoutez vos diplômes et certifications
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Diplomas / Certifications -->
                                    <div class="space-y-4 pt-4 border-t border-slate-50 mt-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Avez-vous des diplômes ou certifications ?</label>
                                        <div class="flex items-center space-x-6">
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <div class="relative">
                                                    <input type="radio" v-model="hasDiploma" value="Oui" class="peer sr-only">
                                                    <div class="w-6 h-6 rounded-full border-2 border-slate-200 peer-checked:border-premium-yellow peer-checked:bg-premium-yellow transition-all"></div>
                                                    <div class="absolute inset-0 flex items-center justify-center transform scale-0 peer-checked:scale-100 transition-transform">
                                                        <div class="w-2 h-2 bg-slate-900 rounded-full"></div>
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Oui, j'en ai</span>
                                            </label>
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <div class="relative">
                                                    <input type="radio" v-model="hasDiploma" value="Non" class="peer sr-only">
                                                    <div class="w-6 h-6 rounded-full border-2 border-slate-200 peer-checked:border-slate-900 peer-checked:bg-slate-900 transition-all"></div>
                                                </div>
                                                <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Non, pas pour l'instant</span>
                                            </label>
                                        </div>

                                        <div v-if="hasDiploma === 'Oui'" class="relative group animate-in fade-in slide-in-from-top-2 duration-300">
                                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                                <Award class="h-5 w-5 text-slate-300 transition-colors group-focus-within:text-premium-yellow" />
                                            </div>
                                            <input 
                                                v-model="profileForm.diplomas" 
                                                type="text" 
                                                placeholder="Ex: CAP Plomberie, Certificat Élec..." 
                                                class="w-full pl-14 pr-8 py-5 rounded-[2rem] border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs shadow-inner"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- STEP 3: Disponibilités -->
                    <div v-if="currentStep === 3" class="max-w-4xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="bg-white p-10 rounded-4xl shadow-sm border border-slate-100 space-y-10">
                            <div class="flex items-center space-x-4">
                                <div class="w-1.5 h-8 bg-premium-yellow rounded-full"></div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Vos Disponibilités</h3>
                            </div>

                            <AvailabilityScheduler 
                                :initial-availability="profileForm.availabilities"
                                @update="handleAvailabilityUpdate"
                            />
                        </div>
                    </div>

                    <!-- WIZARD NAVIGATION -->
                    <div class="max-w-4xl mx-auto flex items-center justify-between pt-10 border-t border-slate-100 relative z-20">
                        <button 
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep" 
                            class="flex items-center space-x-3 px-8 py-4 rounded-3xl border-2 border-slate-100 text-slate-900 font-black text-xs uppercase tracking-widest transition-all hover:border-slate-900 active:scale-95 cursor-pointer bg-white"
                        >
                            <ChevronLeft class="w-5 h-5" />
                            <span>Précédent</span>
                        </button>
                        <div v-else></div>

                        <div class="flex items-center space-x-4">
                            <button 
                                v-if="currentStep < 3"
                                type="button"
                                @click="nextStep" 
                                class="flex items-center space-x-3 bg-premium-yellow text-slate-900 px-10 py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all hover:bg-yellow-400 hover:shadow-xl active:scale-95"
                            >
                                <span>Suivant</span>
                                <ChevronRight class="w-5 h-5" />
                            </button>
                            <button 
                                v-else
                                type="button"
                                @click="validateAndSubmit"  
                                :disabled="saving"
                                class="flex items-center space-x-3 bg-premium-yellow text-slate-900 px-10 py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all hover:bg-yellow-400 hover:shadow-xl active:scale-95 disabled:opacity-50"
                            >
                                <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                                <Award v-else class="w-5 h-5" />
                                <span>{{ saving ? 'Enregistrement...' : 'Terminer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SETTINGS -->
            <div v-if="activeTab === 'settings'" class="space-y-8 animate-in fade-in slide-in-from-bottom-5 duration-300 max-w-2xl mx-auto">
                <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 space-y-8">
                    <div class="flex items-center space-x-4">
                        <div class="w-1.5 h-8 bg-blue-500 rounded-full"></div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Sécurité & Visibilité</h3>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <div class="space-y-1">
                            <h4 class="text-sm font-black text-slate-900">Visibilité globale</h4>
                            <p class="text-[10px] text-slate-500 font-medium">Si désactivé, vous ne recevrez plus de propositions.</p>
                        </div>
                        <button 
                            @click="toggleVisibility"
                            :class="visibility ? 'bg-slate-900 text-white shadow-xl shadow-slate-900/20' : 'bg-white text-slate-300 border border-slate-100'"
                            class="px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 flex items-center space-x-2"
                        >
                            <Power class="w-3.5 h-3.5" />
                            <span>{{ visibility ? 'Publié' : 'Brouillon' }}</span>
                        </button>
                    </div>

                    <div class="p-6 bg-red-50 rounded-3xl border border-red-100 space-y-6">
                        <div class="space-y-1">
                            <h4 class="text-sm font-black text-red-900">Zone de danger</h4>
                            <p class="text-[10px] text-red-600/60 font-medium">La suppression de votre compte est irréversible et effacera tout votre historique.</p>
                        </div>
                        <button @click="deleteAccount" class="w-full py-4 text-center border-2 border-red-200 text-red-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-600 hover:text-white hover:border-red-600 transition-all active:scale-95">
                            Supprimer mon compte définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
