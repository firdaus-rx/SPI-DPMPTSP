<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col overflow-hidden border-r border-white/[0.06] bg-primary-900 text-primary-100/75 transition-all duration-300 ease-out"
        :class="[
            expanded ? 'lg:w-56' : 'lg:w-14',
            mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
        ]"
        aria-label="Navigasi samping"
    >
        <!-- Brand -->
        <div
            class="flex h-12 shrink-0 items-center gap-2 border-b border-white/[0.06] px-2.5"
            :class="!expanded && 'lg:justify-center lg:px-0'"
        >
            <div
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-white/10"
            >
                <img src="/logo-pidie.svg" alt="Logo SPI DPMPTSP" class="h-4 w-4 object-contain" />
            </div>
            <div v-if="expanded" class="min-w-0 flex-1 leading-none">
                <h1 class="truncate text-xs font-bold tracking-tight text-white">SPI DPMPTSP</h1>
                <p class="truncate text-[10px] font-medium text-white/50">Kab. Pidie</p>
            </div>
            <button
                v-if="expanded"
                type="button"
                class="ml-auto rounded-md p-1 text-white/40 transition-colors hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Tutup menu"
                @click="$emit('close')"
            >
                <X :size="14" />
            </button>
        </div>

        <!-- Navigation -->
        <nav
            class="flex-1 space-y-3.5 px-2 py-3"
            :class="expanded ? 'sidebar-scroll overflow-y-auto overflow-x-hidden' : 'overflow-visible'"
        >
            <div v-for="section in NAV_SECTIONS" :key="section.label">
                <p
                    v-if="expanded"
                    class="px-2 pb-1.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-white/30"
                >
                    {{ section.label }}
                </p>
                <div v-else class="mx-1.5 mb-2 border-t border-white/10" aria-hidden="true" />

                <ul class="space-y-0.5">
                    <li v-for="item in section.items" :key="item.href" class="relative">
                        <Link
                            :href="item.href"
                            class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium leading-none outline-none transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-white/20"
                            :class="[
                                isActiveUrl(page.url, item)
                                    ? 'bg-white text-primary-700 shadow-sm'
                                    : 'text-white/60 hover:bg-white/[0.06] hover:text-white',
                                !expanded && 'lg:justify-center lg:px-1.5',
                            ]"
                            :aria-current="isActiveUrl(page.url, item) ? 'page' : undefined"
                        >
                            <component
                                :is="item.icon"
                                :size="14"
                                class="shrink-0"
                                :class="isActiveUrl(page.url, item) ? 'text-primary-700' : 'text-white/40 group-hover:text-white/70'"
                            />

                            <template v-if="expanded">
                                <span class="truncate font-medium">{{ item.label }}</span>
                                <span
                                    v-if="item.badge"
                                    class="ml-auto rounded px-1 py-0.5 text-[9px] font-bold leading-none tracking-wide"
                                    :class="isActiveUrl(page.url, item) ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-200' : 'bg-white/10 text-white/70'"
                                >
                                    {{ item.badge }}
                                </span>
                            </template>

                            <!-- Tooltip saat collapsed -->
                            <span
                                v-if="!expanded"
                                class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-medium text-white shadow-xl ring-1 ring-white/10 group-hover:block"
                            >
                                {{ item.label }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Footer -->
        <div class="shrink-0 border-t border-white/[0.06] p-2">
            <button
                type="button"
                class="hidden w-full items-center justify-center gap-1.5 rounded-md py-1.5 text-xs font-medium text-white/40 transition-colors hover:bg-white/[0.06] hover:text-white lg:flex"
                :class="!expanded && 'lg:px-0'"
                :aria-label="expanded ? 'Ciutkan sidebar' : 'Perluas sidebar'"
                @click="$emit('toggle')"
            >
                <PanelLeftClose v-if="expanded" :size="14" />
                <PanelLeftOpen v-else :size="14" />
                <span v-if="expanded" class="text-[11px]">Ciutkan</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { PanelLeftClose, PanelLeftOpen, X } from '@lucide/vue';
import { NAV_SECTIONS, isActiveUrl } from './menu';

const props = defineProps({
    open: { type: Boolean, default: true },
    mobileOpen: { type: Boolean, default: false },
});

defineEmits(['toggle', 'close']);

const page = usePage();

const expanded = computed(() => props.open || props.mobileOpen);
</script>
