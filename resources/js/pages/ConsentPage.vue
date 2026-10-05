<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const agreed = ref(false);
const saving = ref(false);
const error = ref('');

async function submit() {
    saving.value = true;
    error.value = '';
    try {
        await auth.acceptTerms();
        router.replace({ name: 'home' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Gagal menyimpan. Coba lagi.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-dvh max-w-md flex-col px-6 py-10">
        <h1 class="text-2xl font-semibold">Sebelum mulai</h1>
        <p class="mt-2 text-stone-600">Mohon baca dan setujui ketentuan berikut.</p>

        <div class="mt-6 space-y-3 rounded-2xl bg-white p-5 text-stone-700 shadow-sm">
            <p>
                Catatan adalah aplikasi pencatat kegiatan sehari-hari, bukan tempat menyimpan data penting.
            </p>
            <p class="font-medium text-stone-900">
                Jangan simpan data pribadi penting seperti nomor KTP/KK, kata sandi, PIN, nomor rekening atau
                kartu, maupun data kesehatan.
            </p>
            <p>
                Aplikasi ini masih dalam tahap pengembangan. Kamu menggunakannya atas tanggung jawab sendiri.
            </p>
        </div>

        <label class="mt-6 flex items-start gap-3 text-stone-800">
            <input v-model="agreed" type="checkbox" class="mt-1 size-5 accent-teal-700" />
            <span>
                Saya setuju dengan Syarat Penggunaan dan Kebijakan Privasi Catatan, dan tidak akan menyimpan data
                pribadi penting.
            </span>
        </label>

        <p v-if="error" class="mt-4 text-sm text-red-700">{{ error }}</p>

        <button
            type="button"
            :disabled="!agreed || saving"
            class="mt-auto h-14 w-full rounded-2xl bg-teal-700 text-lg font-medium text-white disabled:bg-stone-300"
            @click="submit"
        >
            Setuju dan lanjutkan
        </button>
    </main>
</template>
