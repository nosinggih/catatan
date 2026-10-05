import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useActivitiesStore } from './stores/activities';
import { useAuthStore } from './stores/auth';

const pinia = createPinia();
createApp(App).use(pinia).use(router).mount('#app');

// Send entries logged while offline as soon as the connection is back.
window.addEventListener('online', () => {
    if (useAuthStore(pinia).user) useActivitiesStore(pinia).load();
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
