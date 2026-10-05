<script setup>
import { computed, ref } from 'vue';
import BottomSheet from './BottomSheet.vue';
import { isSameName, search } from '../lib/match';
import { useActivitiesStore } from '../stores/activities';
import { isOffline } from '../lib/storage';

const props = defineProps({ initialName: { type: String, default: '' } });
const emit = defineEmits(['close', 'log']);
const store = useActivitiesStore();

const name = ref(props.initialName);
const saving = ref(false);
const error = ref('');

const similar = computed(() => (name.value.trim() ? search(name.value, store.items).slice(0, 3) : []));
const exact = computed(() => store.items.find((a) => isSameName(a.name, name.value)));

async function add(logNow) {
    saving.value = true;
    error.value = '';
    try {
        const activity = await store.create(name.value.trim());
        emit('close');
        if (logNow) emit('log', store.byId(activity.id));
    } catch (e) {
        error.value = isOffline(e)
            ? 'Sedang offline. Kegiatan baru bisa ditambah setelah online.'
            : (e.response?.data?.message ?? 'Gagal menyimpan.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <BottomSheet title="Kegiatan baru" @close="emit('close')">
        <form @submit.prevent="add(true)">
            <input
                v-model="name"
                autofocus
                maxlength="80"
                placeholder="Contoh: Ganti sprei"
                class="h-14 w-full rounded-xl border border-stone-300 px-4 text-lg"
            />

            <div v-if="similar.length" class="mt-4">
                <p class="text-sm text-stone-500">Sudah ada yang mirip:</p>
                <ul class="mt-2 space-y-2">
                    <li v-for="activity in similar" :key="activity.id">
                        <button
                            type="button"
                            class="flex h-12 w-full items-center justify-between rounded-xl bg-stone-100 px-4 text-left"
                            @click="emit('close'), emit('log', activity)"
                        >
                            <span>{{ activity.icon }} {{ activity.name }}</span>
                            <span class="text-sm font-medium text-teal-800">Catat</span>
                        </button>
                    </li>
                </ul>
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>

            <div v-if="name.trim() && !exact" class="mt-6 space-y-2">
                <button
                    type="submit"
                    :disabled="saving"
                    class="h-14 w-full rounded-2xl bg-teal-700 text-lg font-medium text-white disabled:bg-stone-300"
                >
                    Tambah dan catat sekarang
                </button>
                <button type="button" :disabled="saving" class="h-12 w-full text-teal-800" @click="add(false)">
                    Tambah saja
                </button>
            </div>
        </form>
    </BottomSheet>
</template>
