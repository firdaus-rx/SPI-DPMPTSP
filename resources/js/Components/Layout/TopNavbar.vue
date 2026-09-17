<template>
    <header class="sticky top-0 z-30 border-b border-gray-200/70 bg-white/80 backdrop-blur-xl">
        <div class="flex h-16 items-center gap-2 px-4 sm:gap-3 sm:px-5 lg:px-7">
            <!-- Hamburger (mobile) -->
            <button
                type="button"
                class="rounded-xl p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 lg:hidden"
                aria-label="Buka menu navigasi"
                @click="$emit('menu')"
            >
                <Menu :size="20" />
            </button>

            <!-- Breadcrumb -->
            <nav class="hidden min-w-0 items-center gap-1.5 text-sm md:flex" aria-label="Breadcrumb">
                <template v-for="(crumb, i) in crumbs" :key="i">
                    <ChevronRight v-if="i > 0" :size="14" class="shrink-0 text-gray-300" />
                    <Link
                        v-if="crumb.href && i < crumbs.length - 1"
                        :href="crumb.href"
                        class="shrink-0 rounded-md px-1 text-gray-500 transition-colors hover:text-primary-600"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span
                        v-else
                        class="truncate rounded-md px-1 font-semibold text-gray-900"
                        aria-current="page"
                    >
                        {{ crumb.label }}
                    </span>
                </template>
            </nav>

            <!-- Judul halaman saat breadcrumb disembunyikan -->
            <span class="truncate text-sm font-semibold text-gray-900 md:hidden">
                {{ currentLabel }}
            </span>

            <div class="flex-1" />

            <!-- Notifikasi -->
            <div class="relative">
                <button
                    type="button"
                    class="rounded-xl p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
                    :class="openMenu === 'notif' && 'bg-gray-100 text-gray-700'"
                    aria-label="Notifikasi"
                    :aria-expanded="openMenu === 'notif'"
                    @click="toggleMenu('notif')"
                >
                    <Bell :size="20" />
                </button>
            </div>

            <!-- Menu pengguna -->
            <div class="relative">
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-xl p-1.5 transition-colors hover:bg-gray-100"
                    :class="openMenu === 'user' && 'bg-gray-100'"
                    aria-label="Menu pengguna"
                    :aria-expanded="openMenu === 'user'"
                    @click="toggleMenu('user')"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-800 text-sm font-semibold text-white"
                    >
                        {{ (authUser?.name ?? 'A').trim().charAt(0).toUpperCase() }}
                    </span>
                    <ChevronDown :size="14" class="hidden text-gray-400 sm:block" />
                </button>
            </div>

            <!-- Click-away -->
            <div
                v-if="openMenu"
                class="fixed inset-0 z-40 cursor-default"
                aria-hidden="true"
                @click="openMenu = null"
            />

            <!-- Panel notifikasi -->
            <Transition name="dropdown">
                <div
                    v-if="openMenu === 'notif'"
                    class="absolute right-4 top-[calc(4rem-0.25rem)] z-50 w-72 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl shadow-gray-200/60 sm:right-5 lg:right-7"
                >
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
                    </div>
                    <div class="flex flex-col items-center px-4 py-8 text-center">
                        <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                            <Inbox :size="18" class="text-gray-400" />
                        </span>
                        <p class="text-sm font-medium text-gray-600">Belum ada notifikasi</p>
                        <p class="mt-0.5 text-xs text-gray-400">Pemberitahuan baru akan tampil di sini</p>
                    </div>
                </div>
            </Transition>

            <!-- Panel pengguna — hanya Profil & Keluar -->
            <Transition name="dropdown">
                <div
                    v-if="openMenu === 'user'"
                    class="absolute right-4 top-[calc(4rem-0.25rem)] z-50 w-56 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl shadow-gray-200/60 sm:right-5 lg:right-7"
                >
                    <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/70 px-4 py-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-800 text-sm font-semibold text-white"
                        >
                            {{ (authUser?.name ?? 'A').trim().charAt(0).toUpperCase() }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ authUser?.name ?? 'Administrator' }}</p>
                            <p class="truncate text-xs text-gray-500">{{ authUser?.email ?? 'SPI DPMPTSP' }}</p>
                        </div>
                    </div>
                    <div class="p-2">
                        <div class="rounded-xl px-3 py-2.5 text-sm">
                            <p class="text-xs text-gray-400">Masuk sebagai</p>
                            <p class="truncate text-sm font-medium text-gray-900">{{ authUser?.email ?? '—' }}</p>
                        </div>
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                            @click="logout"
                        >
                            <LogOut :size="17" class="shrink-0" />
                            Keluar
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu, Bell, Inbox, ChevronDown, ChevronRight, LogOut } from '@lucide/vue';
import { breadcrumbs } from './menu';

defineEmits(['menu']);

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const openMenu = ref(null);

const crumbs = computed(() => breadcrumbs(page.url));
const currentLabel = computed(() => crumbs.value[crumbs.value.length - 1]?.label ?? '');

function toggleMenu(name) {
    openMenu.value = openMenu.value === name ? null : name;
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        openMenu.value = null;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

function logout() {
    router.post('/logout');
}

router.on('navigate', () => {
    openMenu.value = null;
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>