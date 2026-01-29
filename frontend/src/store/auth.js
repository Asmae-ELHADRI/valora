import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        status: 'idle' // 'idle', 'loading', 'succeeded', 'failed'
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
        isProvider: (state) => state.user?.role === 'provider',
        isClient: (state) => state.user?.role === 'client'
    },
    actions: {
        async register(userData) {
            this.status = 'loading'
            try {
                const response = await api.post('/api/register', userData)
                this.token = response.data.access_token
                this.user = response.data.user
                localStorage.setItem('token', this.token)
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                this.status = 'succeeded'
            } catch (error) {
                this.status = 'failed'
                throw error
            }
        },
        async login(credentials) {
            this.status = 'loading'
            try {
                const response = await api.post('/api/login', credentials)
                this.token = response.data.access_token
                this.user = response.data.user
                localStorage.setItem('token', this.token)
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                this.status = 'succeeded'
            } catch (error) {
                this.status = 'failed'
                throw error
            }
        },
        async logout() {
            try {
                await api.post('/api/logout')
            } catch (err) {
                console.error('Logout API call failed:', err);
            } finally {
                this.user = null
                this.token = null
                localStorage.removeItem('token')
                delete api.defaults.headers.common['Authorization']
            }
        },
        async fetchUser() {
            if (!this.token) return
            try {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                const response = await api.get('/api/user')
                this.user = response.data
            } catch (error) {
                await this.logout()
            }
        }
    }
})
