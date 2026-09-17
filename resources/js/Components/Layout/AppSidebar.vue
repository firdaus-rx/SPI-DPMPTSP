<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col overflow-hidden bg-primary-900 text-primary-100/80 shadow-2xl shadow-primary-950/60 transition-all duration-300 ease-out lg:shadow-none"
        :class="[
            expanded ? 'lg:w-60' : 'lg:w-16',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
        aria-label="Navigasi samping"
    >
        <!-- Brand -->
        <div
            class="relative flex h-14 shrink-0 items-center gap-2.5 border-b border-white/10 px-3"
            :class="!expanded && 'lg:justify-center lg:px-0'"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-500 to-primary-800 shadow-md shadow-primary-950/60 ring-1 ring-white/20"
            >
                <img src="/logo-pidie.svg" alt="Logo SPI DPMPTSP" class="h-5 w-5 object-contain" />
            </div>
            <div v-if="expanded" class="min-w-0 flex-1">
                <h1 class="truncate text-[13px] font-bold tracking-tight text-white">SPI DPMPTSP</h1>
                <p class="truncate text-[10px] leading-tight text-primary-200/60">Pengawasan Kepatuhan</p>
            </div>
            <button
                v-if="expanded"
                type="button"
                class="ml-auto rounded-lg p-1.5 text-primary-200/60 transition-colors hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Tutup menu"
                @click="$emit('close')"
            >
                <X :size="16" />
            </button>
        </div>

        <!-- Navigation -->
        <nav
            class="relative flex-1 space-y-4 px-2.5 py-4"
            :class="expanded ? 'sidebar-scroll overflow-y-auto overflow-x-hidden' : 'overflow-visible'"
        >
            <div v-for="section in NAV_SECTIONS" :key="section.label">
                <p
                    v-if="expanded"
                    class="px-2.5 pb-1 text-[9px] font-bold uppercase tracking-[0.16em] text-primary-200/40"
                >
                    {{ section.label }}
                </p>
                <div v-else class="mx-2 mb-2 border-t border-white/10" aria-hidden="true" />

                <ul class="space-y-0.5">
                    <li v-for="item in section.items" :key="item.href" class="relative">
                        <Link
                            :href="item.href"
                            class="group relative flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium outline-none transition-colors duration-200 focus-visible:ring-2 focus-visible:ring-primary-300/70"
                            :class="[
                                isActiveUrl(page.url, item)
                                    ? 'bg-primary-600 text-white shadow-md shadow-primary-950/50'
                                    : 'text-primary-100/80 hover:bg-white/[0.08] hover:text-white',
                                !expanded && 'lg:justify-center lg:px-0',
                            ]"
                            :aria-current="isActiveUrl(page.url, item) ? 'page' : undefined"
                        >
                            <!-- Indikator aktif -->
                            <span
                                v-if="isActiveUrl(page.url, item) && expanded"
                                class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-r-full bg-primary-200"
                                aria-hidden="true"
                            />

                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md transition-colors"
                                :class="isActiveUrl(page.url, item)
                                    ? 'bg-white/15'
                                    : 'bg-white/[0.04] group-hover:bg-white/10'"
                            >
                                <component :is="item.icon" :size="15" />
                            </span>

                            <template v-if="expanded">
                                <span class="truncate">{{ item.label }}</span>
                                <span
                                    v-if="item.badge"
                                    class="ml-auto rounded bg-white/15 px-1 py-0.5 text-[9px] font-bold tracking-wide text-primary-100"
                                >
                                    {{ item.badge }}
                                </span>
                            </template>

                            <!-- Tooltip saat rail desktop menyempit -->
                            <span
                                v-if="!expanded"
                                class="pointer-events-none absolute left-full z-50 ml-2.5 hidden whitespace-nowrap rounded-md bg-primary-950 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-xl ring-1 ring-white/10 transition-opacity duration-150 group-hover:opacity-100 lg:block"
                            >
                                {{ item.label }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Footer -->
        <div class="relative shrink-0 border-t border-white/10 p-2.5">
            <button
                type="button"
                class="hidden w-full items-center justify-center gap-2 rounded-lg px-2.5 py-2 text-[13px] font-medium text-primary-200/60 transition-colors hover:bg-white/[0.08] hover:text-white lg:flex"
                :aria-label="expanded ? 'Ciutkan sidebar' : 'Perluas sidebar'"
                @click="$emit('toggle')"
            >
                <PanelLeftClose v-if="expanded" :size="16" />
                <PanelLeftOpen v-else :size="16" />
                <span v-if="expanded">Ciutkan</span>
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
