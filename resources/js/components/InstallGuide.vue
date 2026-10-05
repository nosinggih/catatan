<script setup>
import { ref } from 'vue';
import { isIos, isStandalone } from '../lib/push';
import { read, write } from '../lib/storage';

// iPhone has no install prompt, so explain the Share menu instead.
const visible = ref(isIos() && !isStandalone() && !read('installGuideDismissed', false));

function dismiss() {
    visible.value = false;
    write('installGuideDismissed', true);
}
</script>

<template>
    <aside v-if="visible" class="mx-2 mt-4 rounded-2xl bg-teal-50 p-4 text-sm text-teal-950">
        <div class="flex items-start justify-between gap-2">
            <p class="font-medium">Pasang Catatan di layar utama</p>
            <button type="button" class="-mt-2 -mr-2 size-10 text-teal-800" aria-label="Tutup" @click="dismiss">✕</button>
        </div>
        <ol class="mt-1 list-decimal space-y-1 pl-5">
            <li>Ketuk tombol <strong>Bagikan</strong> (kotak dengan panah ke atas) di Safari.</li>
            <li>Pilih <strong>Tambah ke Layar Utama</strong>.</li>
            <li>Buka Catatan dari ikon di layar utama supaya pengingat bisa muncul.</li>
        </ol>
    </aside>
</template>
