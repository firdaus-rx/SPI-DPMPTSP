<template>
    <AdminLayout title="Daftar List Sanksi Pencabutan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar List Sanksi Pencabutan</h1>
                <p class="text-sm text-gray-500 mt-1">{{ pengawasan.total }} sanksi pencabutan terdaftar</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <Link
                    href="/pengawasan/import"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                >
                    <FileUp :size="16" />
                    Impor Dokumen
                </Link>
                <Link
                    href="/pengawasan/create"
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm"
                >
                    <Plus :size="18" />
                    Tambah Data
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-5">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-gray-900 sm:hidden"
                @click="filtersOpen = !filtersOpen"
            >
                <span class="flex items-center gap-2">
                    <Filter :size="16" class="text-primary-600" />
                    Filter &amp; Pencarian
                    <span
                        v-if="activeFilterCount"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-[11px] font-bold text-white"
                    >
                        {{ activeFilterCount }}
                    </span>
                </span>
                <ChevronDown
                    :size="18"
                    class="text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': filtersOpen }"
                />
            </button>

            <form
                class="border-t border-gray-100 p-5 sm:border-t-0"
                :class="filtersOpen ? 'block' : 'hidden sm:block'"
                @submit.prevent="filter"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1 basis-full sm:basis-3/5 lg:basis-auto lg:min-w-56 xl:min-w-72">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Cari</label>
                        <div class="relative">
                            <Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Nama, NIB, perizinan..."
                                class="h-10 w-full rounded-xl border border-gray-200 bg-gray-50 pl-9 pr-9 text-sm outline-none transition-all placeholder:text-gray-400 focus:border-primary-300 focus:bg-white focus:ring-2 focus:ring-primary-500/15"
                            />
                            <button
                                v-if="form.search"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                aria-label="Hapus pencarian"
                                @click="form.search = ''; filter()"
                            >
                                <X :size="14" />
                            </button>
                        </div>
                    </div>

                    <div class="w-full sm:w-auto sm:min-w-36">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Risiko</label>
                        <select
                            v-model="form.tingkat_risiko"
                            class="h-10 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm outline-none transition-all focus:border-primary-300 focus:bg-white focus:ring-2 focus:ring-primary-500/15"
                        >
                            <option value="">Semua Risiko</option>
                            <option value="Rendah">Rendah</option>
                            <option value="Menengah Rendah">Menengah Rendah</option>
                            <option value="Menengah">Menengah</option>
                            <option value="Menengah Tinggi">Menengah Tinggi</option>
                            <option value="Tinggi">Tinggi</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-primary-600 px-5 text-sm font-medium text-white shadow-sm shadow-primary-600/20 transition-colors hover:bg-primary-700"
                        >
                            <Filter :size="16" />
                            <span class="hidden sm:inline">Terapkan</span>
                        </button>
                        <button
                            v-if="activeFilterCount"
                            type="button"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900"
                            @click="resetFilters"
                        >
                            <RotateCcw :size="14" />
                            <span class="hidden sm:inline">Reset</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table / Cards -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <!-- Mobile: cards -->
            <div class="divide-y divide-gray-100 lg:hidden">
                <div v-if="pengawasan.data.length === 0" class="px-4 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                            <Inbox :size="24" class="text-gray-400" />
                        </div>
                        <p class="text-gray-500 font-medium">Tidak ada data</p>
                                <p class="text-sm text-gray-400 mt-1">Belum ada sanksi pencabutan yang tersedia</p>
                    </div>
                </div>
                <template v-else>
                    <Link
                        v-for="(item, index) in pengawasan.data"
                        :key="item.id"
                        :href="`/pengawasan/${item.id}`"
                        class="block px-4 py-3.5 transition-colors active:bg-gray-50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-medium text-gray-400 tabular-nums">{{ pengawasan.from + index }}</span>
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ item.nama_pelaku_usaha || 'Data Tanpa Nama' }}</p>
                                </div>
                                <p class="mt-1 truncate font-mono text-[11px] text-gray-500">{{ item.nib || 'NIB -' }}</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                :class="riskBadgeClass(item.tingkat_risiko)"
                            >
                                {{ item.tingkat_risiko || '-' }}
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                            <span v-if="item.kab_kota" class="inline-flex items-center gap-1"><MapPin :size="12" />{{ item.kab_kota }}</span>
                            <span v-if="item.jenis_penanaman_modal" class="inline-flex items-center gap-1"><Building2 :size="12" />{{ item.jenis_penanaman_modal }}</span>
                            <span v-if="item.nomor_sanksi" class="inline-flex items-center gap-1 font-mono text-primary-600"><FileText :size="12" />{{ item.nomor_sanksi }}</span>
                        </div>
                    </Link>
                </template>
            </div>

            <!-- Desktop: table -->
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Pelaku Usaha</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">NIB</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Jenis PM</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Risiko</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Nomor Sanksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(item, index) in pengawasan.data" :key="item.id" class="group transition-colors hover:bg-gray-50/70">
                            <td class="px-4 py-2.5 text-xs text-gray-400 tabular-nums">{{ pengawasan.from + index }}</td>
                            <td class="px-4 py-2.5">
                                <Link
                                    :href="`/pengawasan/${item.id}`"
                                    class="font-medium text-gray-900 underline-offset-2 decoration-transparent transition-colors group-hover:decoration-gray-300 hover:text-primary-700"
                                >
                                    {{ item.nama_pelaku_usaha || 'Data Tanpa Nama' }}
                                </Link>
                                <div class="text-[11px] text-gray-400">{{ item.skala_usaha || '-' }}</div>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ item.nib || '-' }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-600">{{ item.jenis_penanaman_modal || '-' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="text-xs text-gray-600">{{ item.kab_kota || '-' }}</div>
                                <div class="text-[11px] text-gray-400">{{ item.provinsi || '-' }}</div>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="riskBadgeClass(item.tingkat_risiko)">
                                    {{ item.tingkat_risiko || '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="item.nomor_sanksi" class="font-mono text-xs text-gray-600">{{ item.nomor_sanksi }}</span>
                                <span v-else class="font-mono text-xs text-gray-300">-</span>
                            </td>
                        </tr>
                        <tr v-if="pengawasan.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <Inbox :size="24" class="mx-auto mb-3 text-gray-400" />
                                <p class="text-sm font-medium text-gray-600">Tidak ada data</p>
                                <p class="mt-1 text-xs text-gray-500">Belum ada sanksi pencabutan yang sesuai.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination + Page Size -->
        <div class="mt-5 flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-3">
                <p class="text-xs text-gray-500 sm:text-sm">
                    Menampilkan {{ pengawasan.from }}-{{ pengawasan.to }} dari {{ pengawasan.total }} data
                </p>
                <div class="relative">
                    <select
                        v-model="form.per_page"
                        class="h-8 rounded-lg border border-gray-200 bg-white px-2.5 pr-7 text-xs font-medium text-gray-600 outline-none transition-all focus:border-primary-300 focus:ring-2 focus:ring-primary-500/15"
                        @change="changePageSize"
                    >
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <ChevronDown :size="12" class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
            </div>

            <nav v-if="pengawasan.last_page > 1" class="flex items-center gap-1" aria-label="Navigasi halaman">
                <template v-for="link in pengawasan.links" :key="link.label">
                    <span
                        v-if="link.label.includes('Previous') || link.label.includes('Next')"
                        class="hidden sm:inline"
                    >
                        <Link
                            :href="link.url || '#'"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-sm transition-colors"
                            :class="link.url
                                ? 'text-gray-600 hover:bg-gray-100'
                                : 'pointer-events-none text-gray-300'"
                            v-html="link.label"
                        />
                    </span>
                    <span v-else-if="link.label === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">
                        ...
                    </span>
                    <Link
                        v-else
                        :href="link.url || '#'"
                        class="flex h-8 min-w-8 items-center justify-center rounded-lg text-sm font-medium transition-colors"
                        :class="link.active
                            ? 'bg-primary-600 text-white shadow-sm'
                            : link.url
                                ? 'text-gray-600 hover:bg-gray-100'
                                : 'pointer-events-none text-gray-300'"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import {
    Plus,
    FileUp,
    Search,
    Filter,
    ChevronDown,
    X,
    RotateCcw,
    Inbox,
    MapPin,
    Building2,
    FileText,
} from '@lucide/vue';

const props = defineProps({
    pengawasan: Object,
    filters: Object,
});

const form = reactive({
    search: props.filters?.search || '',
    tingkat_risiko: props.filters?.tingkat_risiko || '',
    per_page: props.filters?.per_page || 15,
});

const filtersOpen = ref(false);

const activeFilterCount = computed(() =>
    [form.search, form.tingkat_risiko].filter(Boolean).length
);

function riskBadgeClass(risiko) {
    return {
        'bg-red-50 text-red-700': risiko === 'Tinggi',
        'bg-orange-50 text-orange-700': risiko === 'Menengah Tinggi',
        'bg-yellow-50 text-yellow-700': risiko === 'Menengah',
        'bg-lime-50 text-lime-700': risiko === 'Menengah Rendah',
        'bg-green-50 text-green-700': risiko === 'Rendah',
        'bg-gray-100 text-gray-500': !risiko,
    };
}

function filter() {
    filtersOpen.value = false;
    router.get('/pengawasan', { ...form }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    form.search = '';
    form.tingkat_risiko = '';
    form.per_page = 15;
    filter();
}

function changePageSize() {
    router.get('/pengawasan', { ...form, page: 1 }, {
        preserveState: true,
        replace: true,
    });
}
</script>
