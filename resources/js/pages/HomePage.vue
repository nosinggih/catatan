<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const installPrompt = ref(null);

function captureInstallPrompt(event) {
    event.preventDefault();
    installPrompt.value = event;
}

async function install() {
    installPrompt.value.prompt();
    await installPrompt.value.userChoice;
    installPrompt.value = null;
}

async function logout() {
    await auth.logout();
    router.replace({ name: 'login' });
}

onMounted(() => window.addEventListener('beforeinstallprompt', captureInstallPrompt));
onBeforeUnmount(() => window.removeEventListener('beforeinstallprompt', captureInstallPrompt));
</script>

<template>
    <main class="mx-auto flex min-h-dvh max-w-md flex-col px-6 py-8">
        <header class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Catatan</h1>
            <button type="button" class="text-sm text-stone-500" @click="logout">Keluar</button>
        </header>

        <p class="mt-2 text-stone-600">Halo, {{ auth.user?.name }}.</p>

        <button
            v-if="installPrompt"
            type="button"
            class="mt-6 h-12 w-full rounded-2xl border border-teal-700 font-medium text-teal-800"
            @click="install"
        >
            Pasang aplikasi di layar utama
        </button>

        <section class="mt-10 flex flex-1 flex-col items-center justify-center text-center text-stone-500">
            <p class="text-lg font-medium text-stone-700">Belum ada kegiatan</p>
            <p class="mt-1">Tombol kegiatan akan muncul di sini.</p>
        </section>
    </main>
</template>
