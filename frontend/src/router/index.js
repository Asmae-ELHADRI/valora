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
    } else if (to.meta.role && auth.user?.role !== to.meta.role) {
        next('/dashboard')
    } else {
        next()
    }
})

export default router
