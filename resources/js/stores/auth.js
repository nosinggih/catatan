import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loaded: false,
    }),

    actions: {
        async load() {
            if (this.loaded) return;
            try {
                const { data } = await axios.get('/api/me');
                this.user = data.data;
            } catch (error) {
                if (error.response?.status !== 401) throw error;
                this.user = null;
            }
            this.loaded = true;
        },

        async acceptTerms() {
            const { data } = await axios.post('/api/consent', { accept: true });
            this.user = data.data;
        },

        async logout() {
            await axios.post('/auth/logout');
            this.user = null;
        },
    },
});
