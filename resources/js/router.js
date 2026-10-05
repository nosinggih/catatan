import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import HomePage from './pages/HomePage.vue';
import LoginPage from './pages/LoginPage.vue';
import ConsentPage from './pages/ConsentPage.vue';
import ActivityPage from './pages/ActivityPage.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/masuk', name: 'login', component: LoginPage, meta: { guest: true } },
        { path: '/persetujuan', name: 'consent', component: ConsentPage },
        { path: '/kegiatan/:id(\\d+)', name: 'activity', component: ActivityPage, props: true },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    await auth.load();

    if (!auth.user) {
        return to.meta.guest ? true : { name: 'login' };
    }
    if (to.meta.guest) {
        return { name: 'home' };
    }
    if (!auth.user.terms_accepted && to.name !== 'consent') {
        return { name: 'consent' };
    }
    if (auth.user.terms_accepted && to.name === 'consent') {
        return { name: 'home' };
    }
    return true;
});

export default router;
