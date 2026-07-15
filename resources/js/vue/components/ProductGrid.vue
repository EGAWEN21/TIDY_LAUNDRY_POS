<template>
    <div class="tw-flex tw-flex-col">
        <div class="icon-field has-validation">
            <span class="icon tw-translate-y-[2px]">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-search" viewBox="0 0 16 16">
                    <path
                        d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                </svg>
            </span>
            <input type="text" class="form-control" v-model="internalSearch"
                placeholder="Search Here" required="">
        </div>
        <div
            class="tw-w-full tw-h-[calc(100vh-9rem)] tw-overflow-y-scroll custom-scroll tw-mt-4 tw-flex tw-px-1 tw-pb-4">
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-4 tw-h-fit tw-w-full">
                <template v-for="item in filteredServices" :key="item.id">
                    <a type="button" class="tw-block tw-h-full tw-w-full tw-group" data-bs-toggle="modal"
                        data-bs-target="#servicetype" @click="$emit('select-service', item)">
                        <div class="tw-bg-white/60 dark:tw-bg-slate-800/60 tw-backdrop-blur-sm tw-border tw-border-white/20 dark:tw-border-white/5 tw-shadow-sm tw-rounded-2xl tw-transition-all tw-duration-300 hover:tw-shadow-[0_8px_30px_rgb(0,0,0,0.12)] dark:hover:tw-shadow-[0_8px_30px_rgb(0,0,0,0.4)] hover:-tw-translate-y-1.5 tw-h-full">
                            <div
                                class="tw-p-4 tw-flex tw-items-center tw-justify-center tw-flex-col tw-h-full">
                                <template v-if="item.icon && item.icon.includes(':')">
                                    <div class="tw-w-[40%] md:tw-w-20 tw-aspect-square tw-flex tw-items-center tw-justify-center tw-bg-slate-50/50 dark:tw-bg-slate-900/50 tw-rounded-xl tw-transition-colors group-hover:tw-bg-primary-50 dark:group-hover:tw-bg-primary-900/30">
                                        <iconify-icon :icon="item.icon" class="tw-text-4xl text-primary dark:tw-text-primary-400 tw-transition-transform tw-duration-300 group-hover:tw-scale-110"></iconify-icon>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="tw-w-[40%] md:tw-w-20 tw-aspect-square tw-flex tw-items-center tw-justify-center tw-bg-slate-50/50 dark:tw-bg-slate-900/50 tw-rounded-xl tw-overflow-hidden">
                                        <img :src="'/assets/img/service-icons/' + item.icon" class="tw-h-full tw-w-full tw-object-contain tw-rounded-xl tw-transition-transform tw-duration-500 group-hover:tw-scale-110">
                                    </div>
                                </template>
                                <div
                                    class="tw-pt-3 tw-w-full tw-flex tw-justify-center tw-items-center">
                                    <div class="tw-text-sm tw-text-center tw-truncate tw-font-semibold tw-text-slate-800 dark:tw-text-slate-200 tw-w-[95%]">
                                        {{ item.service_name }}</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    searchQuery: String,
    filteredServices: Array
});

const emit = defineEmits(['update:searchQuery', 'select-service']);

const internalSearch = computed({
    get: () => props.searchQuery,
    set: (val) => emit('update:searchQuery', val)
});
</script>
