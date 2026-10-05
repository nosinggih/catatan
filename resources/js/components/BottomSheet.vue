<script setup>
defineProps({ title: { type: String, required: true } });
const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40" @click.self="emit('close')">
            <div
                role="dialog"
                :aria-label="title"
                class="max-h-[90dvh] w-full max-w-md overflow-y-auto rounded-t-3xl bg-white px-6 pt-5 pb-[max(1.5rem,env(safe-area-inset-bottom))]"
            >
                <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-stone-300" />
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ title }}</h2>
                    <button type="button" class="px-2 text-stone-500" aria-label="Tutup" @click="emit('close')">✕</button>
                </div>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
