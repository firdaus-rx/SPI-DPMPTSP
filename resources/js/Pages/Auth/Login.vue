<template>
    <Head title="Masuk — SPI DPMPTSP" />
    <div class="min-h-screen bg-white flex flex-col lg:flex-row">
        <!-- Left — branding (desktop) -->
        <div class="hidden lg:flex lg:w-[46%] xl:w-[42%] relative overflow-hidden bg-primary-600 flex-col justify-between">
            <img src="/guest/assets/images/grid-pattern.svg" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-20" />
            <img src="/guest/assets/images/garis-abu.svg" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-10 mix-blend-screen" />
            <div class="absolute inset-0 bg-gradient-to-br from-primary-600/0 via-primary-600/0 to-black/20" aria-hidden="true" />

            <div class="relative p-10 xl:p-12">
                <a href="/" class="inline-flex items-center gap-3 text-white">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-white/20">
                        <img src="/logo-pidie.svg" alt="Logo Pidie" class="h-6 w-6 object-contain" />
                    </span>
                    <span class="text-sm font-bold leading-none">SPI DPMPTSP<br><span class="text-xs font-medium text-white/70">Kabupaten Pidie</span></span>
                </a>
            </div>

            <div class="relative px-10 xl:px-12 pb-6">
                <p class="text-xs font-semibold tracking-[0.18em] text-white/60">SISTEM PENGAWASAN KEPATUHAN</p>
                <h1 class="mt-3 text-[32px] xl:text-[36px] font-bold leading-[1.05] tracking-tight text-white">
                    Satu pintu<br>
                    <span class="text-white/80">untuk sanksi &amp; pengawasan.</span>
                </h1>
            </div>

            <div class="relative px-10 xl:px-12 pb-10">
                <p class="text-xs leading-relaxed text-white/60">© {{ new Date().getFullYear() }} DPMPTSP Kabupaten Pidie · Jln. Tgk. Chiek Direubee No.5 Sigli</p>
            </div>
        </div>

        <!-- Right — form -->
        <div class="flex flex-1 flex-col bg-gray-50">
            <!-- Mobile top bar -->
            <div class="flex items-center justify-between px-6 py-5 lg:px-10">
                <a href="/" class="inline-flex items-center gap-2.5 lg:hidden">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-200">
                        <img src="/logo-pidie.svg" alt="Logo" class="h-5 w-5 object-contain" />
                    </span>
                    <span class="text-sm font-bold leading-none text-gray-900">SPI DPMPTSP<span class="ml-1 text-xs font-normal text-gray-500">Pidie</span></span>
                </a>
                <a href="/" class="hidden lg:inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-gray-600">
                    <ArrowLeft :size="14" /> Kembali ke beranda
                </a>
                <a href="/" class="inline-flex lg:hidden items-center gap-1 text-xs font-semibold text-gray-500 hover:text-gray-700">
                    <ArrowLeft :size="14" /> Beranda
                </a>
            </div>

            <div class="flex flex-1 items-center justify-center px-6 py-6 sm:px-8 lg:p-10">
                <div class="w-full max-w-[400px]">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Masuk</h2>
                        <p class="mt-1.5 text-sm text-gray-500">Lanjutkan ke dashboard pengawasan.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-7">
                        <div v-if="form.errors.email" class="mb-5 flex gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm leading-snug text-red-700">
                            <span class="mt-0.5 shrink-0 text-red-500"><CircleAlert :size="16" /></span>
                            <span>{{ form.errors.email }}</span>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit" novalidate>
                            <div>
                                <label for="email" class="label">Email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"><Mail :size="16" /></span>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="username"
                                        required
                                        autofocus
                                        placeholder="nama@dpmptsp.go.id"
                                        class="input !pl-10"
                                        :class="form.errors.email ? '!border-red-300 focus:!border-red-400 focus:!ring-red-500/15' : ''"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="password" class="label">Password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"><Lock :size="16" /></span>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        :type="show ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        required
                                        placeholder="••••••••"
                                        class="input !pl-10 !pr-10"
                                        :class="form.errors.email ? '!border-red-300 focus:!border-red-400 focus:!ring-red-500/15' : ''"
                                    />
                                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" @click="show = !show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" tabindex="-1">
                                        <Eye v-if="!show" :size="16" />
                                        <EyeOff v-else :size="16" />
                                    </button>
                                </div>
                            </div>

                            <label class="flex cursor-pointer select-none items-center gap-2 py-1 text-sm text-gray-600">
                                <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                Ingat saya
                            </label>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2 v-if="form.processing" :size="16" class="animate-spin" />
                                <LogIn v-else :size="16" />
                                {{ form.processing ? 'Memproses...' : 'Masuk' }}
                            </button>
                        </form>

                        <p class="mt-5 text-center text-xs text-gray-400">
                            Kendala masuk? <span class="font-medium text-gray-600">Hubungi administrator.</span>
                        </p>
                    </div>

                    <p class="mt-6 text-center text-xs text-gray-400 lg:hidden">© {{ new Date().getFullYear() }} DPMPTSP Kabupaten Pidie</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CircleAlert, Eye, EyeOff, Loader2, Lock, LogIn, Mail } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const show = ref(false);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>
