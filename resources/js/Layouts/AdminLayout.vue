<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Head :title="title" />

        <!-- Skip link (aksesibilitas) -->
        <a
            href="#konten"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-700 focus:shadow-lg"
        >
            Lewati ke konten utama
        </a>

        <!-- Overlay drawer mobile -->
        <Transition name="fade">
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-primary-950/70 backdrop-blur-sm lg:hidden"
                aria-hidden="true"
                @click="mobileOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <AppSidebar
            :open="sidebarOpen"
            :mobile-open="mobileOpen"
            @toggle="toggleSidebar"
            @close="mobileOpen = false"
        />

        <!-- Area konten -->
        <div
            class="flex min-h-screen flex-col transition-[padding] duration-300 ease-out"
            :class="sidebarOpen ? 'lg:pl-60' : 'lg:pl-16'"
        >
            <TopNavbar @menu="mobileOpen = true" />

            <main id="konten" class="flex-1 px-4 pb-10 pt-6 sm:px-6 lg:px-8">
                <Transition name="page" mode="out-in">
                    <div :key="page.component">
                        <slot />
                    </div>
                </Transition>
            </main>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppSidebar from '@/Components/Layout/AppSidebar.vue';
import TopNavbar from '@/Components/Layout/TopNavbar.vue';

defineProps({
    title: { type: String, default: '' },
});

const STORAGE_KEY = 'spi-sidebar-collapsed';
const page = usePage();

const sidebarOpen = ref(localStorage.getItem(STORAGE_KEY) !== '1');
const mobileOpen = ref(false);

watch(sidebarOpen, (value) => {
    localStorage.setItem(STORAGE_KEY, value ? '0' : '1');
});

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function onKeydown(event) {
    if (event.key === 'Escape') mobileOpen.value = false;
}

function onResize() {
    if (window.innerWidth >= 1024) mobileOpen.value = false;
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', onResize);
});

router.on('navigate', () => {
    mobileOpen.value = false;
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.page-enter-active,
.page-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.page-enter-from {
    opacity: 0;
    transform: translateY(6px);
}
.page-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>