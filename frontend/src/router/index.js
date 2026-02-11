import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('../views/Home.vue')
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/Login.vue')
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../views/Register.vue')
    },
    {
        path: '/auth/callback',
        name: 'SocialCallback',
        component: () => import('../views/SocialCallback.vue')
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/dashboard-admin',
        name: 'DashboardAdmin',
        component: () => import('../views/DashboardAdmin.vue'),
        meta: { requiresAuth: true, role: 'admin' }
    },
    {
        path: '/dashboard-provider',
        name: 'DashboardProvider',
        component: () => import('../views/DashboardProvider.vue'),
        meta: { requiresAuth: true, role: 'provider' }
    },
    {
        path: '/dashboard-client',
        name: 'DashboardClient',
        component: () => import('../views/DashboardClient.vue'),
        meta: { requiresAuth: true, role: 'client' }
    },
    {
        path: '/search',
        name: 'Search',
        component: () => import('../views/Search.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/profile',
        name: 'Profile',
        component: () => import('../views/Profile.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/messages',
        name: 'Messages',
        component: () => import('../views/Messages.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../views/ForgotPassword.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/password-reset',
        name: 'ResetPassword',
        component: () => import('../views/ResetPassword.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/post-offer',
        name: 'PostOffer',
        component: () => import('../views/PostOffer.vue'),
        meta: { requiresAuth: true, role: 'client' }
    },
    {
        path: '/edit-offer/:id',
        name: 'EditOffer',
        component: () => import('../views/PostOffer.vue'),
        meta: { requiresAuth: true, role: 'client' }
    },
    {
        path: '/security',
        name: 'Security',
        component: () => import('../views/Security.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/providers',
        name: 'ProviderSearch',
        component: () => import('../views/ProviderSearch.vue'),
        meta: { requiresAuth: true, role: 'client' }
    },
    {
        path: '/certificate',
        name: 'Certificate',
        component: () => import('../views/Certificate.vue'),
        meta: { requiresAuth: true, role: 'provider' }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore()

    if (auth.token && !auth.user) {
        await auth.fetchUser()
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next('/login')
    } else if (to.meta.guestOnly && auth.isAuthenticated) {
        next('/dashboard')
    } else if (to.meta.role && auth.user?.role !== to.meta.role) {
        next('/dashboard')
    } else {
        next()
    }
})

export default router
