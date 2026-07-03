<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getFilteredLinks } from '../utils/pagination.js';

const props = defineProps({
    links: {
        type: Array,
        required: true
    }
});

const filteredLinks = computed(() => {
    return getFilteredLinks(props.links);
});
</script>

<template>
    <div class="flex gap-1 items-center" v-if="links && links.length > 3">
        <template v-for="(link, index) in filteredLinks" :key="index">
            <!-- Ellipsis / Disabled Buttons (Non-interactive span) -->
            <span 
                v-if="link.type === 'ellipsis'"
                class="w-10 h-10 flex items-center justify-center text-secondary font-bold text-sm select-none"
            >
                {{ link.label }}
            </span>
            
            <span
                v-else-if="link.disabled"
                :class="[
                    'w-10 h-10 flex items-center justify-center rounded border border-outline opacity-40 cursor-not-allowed select-none text-secondary',
                    link.type === 'page' && link.active ? 'bg-primary text-on-primary border-none' : ''
                ]"
            >
                <span v-if="link.type === 'previous'" class="material-symbols-outlined">chevron_left</span>
                <span v-else-if="link.type === 'next'" class="material-symbols-outlined">chevron_right</span>
                <span v-else>{{ link.label }}</span>
            </span>
            
            <!-- Previous Button Link -->
            <Link
                v-else-if="link.type === 'previous'"
                :href="link.url"
                preserve-state
                preserve-scroll
                class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors text-secondary"
                aria-label="Halaman Sebelumnya"
            >
                <span class="material-symbols-outlined">chevron_left</span>
            </Link>

            <!-- Next Button Link -->
            <Link
                v-else-if="link.type === 'next'"
                :href="link.url"
                preserve-state
                preserve-scroll
                class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors text-secondary"
                aria-label="Halaman Berikutnya"
            >
                <span class="material-symbols-outlined">chevron_right</span>
            </Link>

            <!-- Page Number Button Link -->
            <Link
                v-else
                :href="link.url"
                preserve-state
                preserve-scroll
                :class="[
                    'w-10 h-10 flex items-center justify-center rounded font-bold text-sm transition-colors',
                    link.active ? 'bg-primary text-on-primary' : 'border border-outline hover:bg-surface-container-high text-secondary'
                ]"
                :aria-label="'Halaman ' + link.label"
            >
                {{ link.label }}
            </Link>
        </template>
    </div>
</template>
