import { defineStore } from 'pinia';
import axios from 'axios';
import { isOffline, read, remove, write } from '../lib/storage';

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
                write('user', this.user);
            } catch (error) {
                if (isOffline(error)) {
                    // Opened without a connection: trust the last known user.
                    this.user = read('user');
                } else if (error.response.status === 401) {
                    this.user = null;
                    remove('user');
                } else {
                    throw error;
                }
            }
            this.loaded = true;
        },

        async acceptTerms() {
            const { data } = await axios.post('/api/consent', { accept: true });
            this.user = data.data;
            write('user', this.user);
        },

        async logout() {
            await axios.post('/auth/logout');
            this.user = null;
            remove('user');
            remove('activities');
            remove('queue');
        },
    },
});
