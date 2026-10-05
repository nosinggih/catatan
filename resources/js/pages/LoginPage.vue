<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();
const devLogin = window.CATATAN?.devLogin;
</script>

<template>
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-between px-6 py-10">
        <div class="pt-16">
            <img src="/icons/icon-192.png" alt="" class="size-16 rounded-2xl" />
            <h1 class="mt-6 text-3xl font-semibold">Catatan</h1>
            <p class="mt-3 text-lg text-stone-600">
                Catat kegiatan rutin dengan satu tap. Tahu kapan terakhir melakukannya, dan kapan perlu lagi.
            </p>
        </div>

        <div class="space-y-3">
            <p v-if="route.query.gagal" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">
                Gagal masuk dengan Google. Silakan coba lagi.
            </p>
            <a
                href="/auth/google/redirect"
                class="flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-teal-700 text-lg font-medium text-white active:bg-teal-800"
            >
                Masuk dengan Google
            </a>
            <a
                v-if="devLogin"
                href="/auth/dev-login"
                class="flex h-12 w-full items-center justify-center rounded-2xl border border-stone-300 text-stone-700"
            >
                Masuk tanpa Google (dev)
            </a>
        </div>
    </main>
</template>
