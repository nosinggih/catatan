<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { useActivitiesStore } from '../stores/activities';

const emit = defineEmits(['custom']);
const store = useActivitiesStore();
const templates = ref([]);
const picked = ref(new Set());
const saving = ref(false);

onMounted(async () => {
    const { data } = await axios.get('/api/templates');
    templates.value = data.data;
});

function toggle(name) {
    picked.value.has(name) ? picked.value.delete(name) : picked.value.add(name);
}

async function save() {
    saving.value = true;
    try {
        for (const t of templates.value.filter((t) => picked.value.has(t.name))) await store.create(t.name, t.icon);
        await store.load();
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <section class="mt-8">
        <h2 class="text-lg font-semibold">Pilih kegiatan yang sering kamu lakukan</h2>
        <p class="mt-1 text-stone-600">Nanti tinggal tap sekali setiap selesai melakukannya.</p>

        <div class="mt-5 flex flex-wrap gap-2">
            <button
                v-for="t in templates"
                :key="t.name"
                type="button"
                :aria-pressed="picked.has(t.name)"
                class="rounded-full border px-4 py-2"
                :class="picked.has(t.name) ? 'border-teal-700 bg-teal-50 text-teal-900' : 'border-stone-300 bg-white'"
                @click="toggle(t.name)"
            >
                {{ t.icon }} {{ t.name }}
            </button>
        </div>

        <button
            type="button"
            :disabled="!picked.size || saving"
            class="mt-8 h-14 w-full rounded-2xl bg-teal-700 text-lg font-medium text-white disabled:bg-stone-300"
            @click="save"
        >
            Simpan {{ picked.size ? `(${picked.size})` : '' }}
        </button>
        <button type="button" class="mt-2 h-12 w-full text-teal-800" @click="emit('custom')">Tambah sendiri</button>
    </section>
</template>
