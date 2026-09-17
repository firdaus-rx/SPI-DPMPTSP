<template>
    <Head title="Masuk — SPI DPMPTSP" />
    <div class="min-h-screen flex flex-col bg-gray-50">
        <!-- Top bar -->
        <div class="h-1 w-full bg-gradient-to-r from-primary-600 via-primary-500 to-primary-800" />

        <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">
            <div class="w-full max-w-md">
                <!-- Brand -->
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 shadow-lg shadow-primary-900/20 ring-1 ring-white/10">
                        <img src="/logo-pidie.svg" alt="Logo" class="h-7 w-7 object-contain" />
                    </div>
                    <h1 class="mt-4 text-xl font-bold tracking-tight text-gray-900">SPI DPMPTSP</h1>
                    <p class="mt-1 text-sm text-gray-500">Masuk untuk mengelola pengawasan kepatuhan</p>
                </div>

                <!-- Card -->
                <div class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-7">
                    <h2 class="text-lg font-semibold text-gray-900">Masuk</h2>
                    <p class="mt-1 text-sm text-gray-500">Gunakan akun yang telah didaftarkan administrator.</p>

                    <div v-if="form.errors.email" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ form.errors.email }}
                    </div>

                    <form class="mt-5 space-y-5" @submit.prevent="submit">
                        <div>
                            <label for="email" class="label">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                required
                                autofocus
                                placeholder="admin@dpmptsp.pidie.go.id"
                                class="input"
                                :class="form.errors.email ? 'border-red-300 focus:border-red-400 focus:ring-red-500/15' : ''"
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="label mb-0">Password</label>
                            </div>
                            <div class="relative mt-1.5">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="show ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••"
                                    class="input pr-10"
                                    :class="form.errors.email ? 'border-red-300 focus:border-red-400 focus:ring-red-500/15' : ''"
                                />
                                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="show = !show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" tabindex="-1">
                                    <Eye v-if="!show" :size="16" />
                                    <EyeOff v-else :size="16" />
                                </button>
                            </div>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                            <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            Ingat saya
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition-colors hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Loader2 v-if="form.processing" :size="16" class="animate-spin" />
                            <LogIn v-else :size="16" />
                            {{ form.processing ? 'Memproses...' : 'Masuk' }}
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs leading-relaxed text-gray-400">
                        Tidak memiliki akun? Hubungi administrator untuk pembuatan akun.<br />
                        Lupa password? Hubungi administrator untuk reset.
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-gray-400">© {{ new Date().getFullYear() }} DPMPTSP Kabupaten Pidie</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Loader2, LogIn } from '@lucide/vue';

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
