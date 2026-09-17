<template>
    <AdminLayout title="Sanksi Administratif — Usulan Pencabutan Perizinan Berusaha">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl">Sanksi Administratif Usulan Pencabutan Perizinan Berusaha</h1>
                <p class="text-sm text-gray-500 mt-1">{{ sanksi.total }} usulan terdaftar</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button
                    type="button"
                    :disabled="!selectedCount"
                    @click="cetakTerpilih"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border transition-colors"
                    :class="selectedCount
                        ? 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
                        : 'bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed'"
                    :title="selectedCount ? `Cetak ${selectedCount} SP1 terpilih` : 'Pilih data terlebih dahulu'"
                >
                    <Printer :size="16" /> Cetak SP1<span v-if="selectedCount"> ({{ selectedCount }})</span>
                </button>
                <button
                    type="button"
                    @click="cetakRekap"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                    :title="selectedCount ? `Rekap ${selectedCount} terpilih` : 'Rekap sesuai filter (semua halaman)'"
                >
                    <FileSpreadsheet :size="16" /> Cetak Rekap
                </button>
                <Link href="/sanksi-administratif/import" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                    <FileUp :size="16" /> Impor Dokumen
                </Link>
                <Link href="/sanksi-administratif/create" class="inline-flex items-center gap-2 bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm">
                    <Plus :size="18" /> Tambah Usulan
                </Link>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-5">
            <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-gray-900 sm:hidden" @click="filtersOpen = !filtersOpen">
                <span class="flex items-center gap-2">
                    <Filter :size="16" class="text-primary-600" /> Filter &amp; Pencarian
                    <span v-if="activeFilterCount" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-[11px] font-bold text-white">{{ activeFilterCount }}</span>
                </span>
                <ChevronDown :size="18" class="text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': filtersOpen }" />
            </button>
            <form class="border-t border-gray-100 p-5 sm:border-t-0" :class="filtersOpen ? 'block' : 'hidden sm:block'" @submit.prevent="filter">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1 basis-full sm:basis-3/5 lg:basis-auto lg:min-w-56 xl:min-w-72">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Cari</label>
                        <div class="relative">
                            <Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input v-model="form.search" type="text" placeholder="Nama, NIB, alamat, kelurahan..." class="h-10 w-full rounded-xl border border-gray-200 bg-gray-50 pl-9 pr-9 text-sm outline-none placeholder:text-gray-400 focus:border-primary-300 focus:bg-white focus:ring-2 focus:ring-primary-500/15" />
                            <button v-if="form.search" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="form.search = ''; filter()"><X :size="14" /></button>
                        </div>
                    </div>
                    <div class="w-full sm:w-auto sm:min-w-36">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Skala Usaha</label>
                        <select v-model="form.skala_usaha" class="h-10 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-primary-300 focus:bg-white focus:ring-2 focus:ring-primary-500/15">
                            <option value="">Semua Skala</option>
                            <option value="Usaha Mikro">Usaha Mikro</option>
                            <option value="Usaha Kecil">Usaha Kecil</option>
                            <option value="Usaha Menengah">Usaha Menengah</option>
                            <option value="Usaha Besar">Usaha Besar</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-auto sm:min-w-36">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kecamatan</label>
                        <input v-model="form.kecamatan" type="text" placeholder="Pidie, Padang Tiji..." class="h-10 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-primary-300 focus:bg-white focus:ring-2 focus:ring-primary-500/15" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-primary-600 px-5 text-sm font-medium text-white shadow-sm hover:bg-primary-700"><Filter :size="16" /><span class="hidden sm:inline">Terapkan</span></button>
                        <button v-if="activeFilterCount" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="resetFilters"><RotateCcw :size="14" /><span class="hidden sm:inline">Reset</span></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-100 lg:hidden">
                <div v-if="sanksi.data.length === 0" class="px-4 py-16 text-center">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3 mx-auto"><Inbox :size="24" class="text-gray-400" /></div>
                    <p class="text-gray-500 font-medium">Belum ada usulan</p>
                    <p class="text-sm text-gray-400 mt-1">Belum ada usulan pencabutan perizinan berusaha</p>
                </div>
                <template v-else>
                    <div v-for="(item, index) in sanksi.data" :key="item.id" class="flex items-center gap-3 px-3 py-3.5 hover:bg-gray-50/70">
                        <input type="checkbox" :checked="isSelected(item.id)" @change="toggleOne(item.id, $event.target.checked)" class="h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <Link :href="`/sanksi-administratif/${item.id}`" class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-medium text-gray-400 tabular-nums">#{{ sanksi.from + index }}</span>
                                <p class="truncate text-sm font-semibold text-gray-900">{{ item.nama_pelaku_usaha || 'Data Tanpa Nama' }}</p>
                            </div>
                            <p class="mt-1 truncate font-mono text-[11px] text-gray-500">{{ item.nib || 'NIB -' }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500">
                                <span v-if="item.kab_kota" class="inline-flex items-center gap-1"><MapPin :size="12" />{{ item.kab_kota }}<template v-if="item.kecamatan"> — {{ item.kecamatan }}</template></span>
                                <span v-if="item.jenis_penanaman_modal" class="inline-flex items-center gap-1"><Building2 :size="12" />{{ item.jenis_penanaman_modal }}</span>
                            </div>
                        </Link>
                        <div class="flex items-center gap-1 shrink-0">
                            <a :href="`/sanksi-administratif/${item.id}/sp1`" target="_blank" @click.stop class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100" title="Cetak SP1"><Printer :size="14" /></a>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="skalaBadge(item.skala_usaha)">{{ item.skala_usaha || '-' }}</span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="w-9 px-3 py-2.5"><input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" /></th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Pelaku Usaha</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">NIB</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Penanaman Modal</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Skala</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-4 py-2.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">SP1</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(item, index) in sanksi.data" :key="item.id" class="group hover:bg-gray-50/70" :class="isSelected(item.id) ? 'bg-primary-50/40' : ''">
                            <td class="px-3 py-2.5 text-center"><input type="checkbox" :checked="isSelected(item.id)" @change="toggleOne(item.id, $event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" /></td>
                            <td class="px-4 py-2.5 text-xs text-gray-400 tabular-nums">{{ sanksi.from + index }}</td>
                            <td class="px-4 py-2.5">
                                <Link :href="`/sanksi-administratif/${item.id}`" class="font-medium text-gray-900 underline-offset-2 decoration-transparent group-hover:decoration-gray-300 hover:text-primary-700">{{ item.nama_pelaku_usaha || 'Data Tanpa Nama' }}</Link>
                                <div class="text-[11px] text-gray-400 truncate max-w-56">{{ item.alamat || item.kelurahan || '-' }}</div>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ item.nib || '-' }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-600 max-w-44 truncate">{{ item.jenis_penanaman_modal || '-' }}</td>
                            <td class="px-4 py-2.5"><span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="skalaBadge(item.skala_usaha)">{{ item.skala_usaha || '-' }}</span></td>
                            <td class="px-4 py-2.5"><div class="text-xs text-gray-600">{{ item.kab_kota || '-' }}</div><div class="text-[11px] text-gray-400">{{ item.kecamatan || '' }}<template v-if="item.kecamatan && item.kelurahan"> — </template>{{ item.kelurahan || '' }}</div></td>
                            <td class="px-4 py-2.5 text-center"><a :href="`/sanksi-administratif/${item.id}/sp1`" target="_blank" class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100" title="Cetak SP1"><Printer :size="14" /></a></td>
                        </tr>
                        <tr v-if="sanksi.data.length === 0"><td colspan="8" class="px-4 py-12 text-center"><Inbox :size="24" class="mx-auto mb-3 text-gray-400" /><p class="text-sm font-medium text-gray-600">Tidak ada data</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-3">
                <p class="text-xs text-gray-500 sm:text-sm">Menampilkan {{ sanksi.from }}-{{ sanksi.to }} dari {{ sanksi.total }} data</p>
                <div class="relative">
                    <select v-model="form.per_page" class="h-8 rounded-lg border border-gray-200 bg-white px-2.5 pr-7 text-xs font-medium text-gray-600 outline-none focus:border-primary-300 focus:ring-2 focus:ring-primary-500/15" @change="changePageSize">
                        <option :value="10">10</option><option :value="15">15</option><option :value="25">25</option><option :value="50">50</option>
                    </select>
                    <ChevronDown :size="12" class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
            </div>
            <nav v-if="sanksi.last_page > 1" class="flex items-center gap-1">
                <template v-for="link in sanksi.links" :key="link.label">
                    <span v-if="link.label.includes('Previous') || link.label.includes('Next')" class="hidden sm:inline"><Link :href="link.url || '#'" class="flex h-8 w-8 items-center justify-center rounded-lg text-sm" :class="link.url ? 'text-gray-600 hover:bg-gray-100' : 'pointer-events-none text-gray-300'" v-html="link.label" /></span>
                    <span v-else-if="link.label === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">...</span>
                    <Link v-else :href="link.url || '#'" class="flex h-8 min-w-8 items-center justify-center rounded-lg text-sm font-medium" :class="link.active ? 'bg-primary-600 text-white shadow-sm' : link.url ? 'text-gray-600 hover:bg-gray-100' : 'pointer-events-none text-gray-300'" v-html="link.label" />
                </template>
            </nav>
        </div>
    </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { Plus, Search, Filter, ChevronDown, X, RotateCcw, Inbox, MapPin, Building2, FileUp, Printer, FileSpreadsheet } from '@lucide/vue';
const props = defineProps({ sanksi: Object, filters: Object });
const form = reactive({ search: props.filters?.search || '', skala_usaha: props.filters?.skala_usaha || '', kecamatan: props.filters?.kecamatan || '', per_page: props.filters?.per_page || 15 });
const filtersOpen = ref(false);
const selectedIds = ref([]);
const activeFilterCount = computed(() => [form.search, form.skala_usaha, form.kecamatan].filter(Boolean).length);
const selectedCount = computed(() => selectedIds.value.length);
const allChecked = computed(() => props.sanksi?.data?.length > 0 && selectedIds.value.length === props.sanksi.data.length && props.sanksi.data.every(d => selectedIds.value.includes(d.id)));
function isSelected(id){ return selectedIds.value.includes(id); }
function toggleOne(id, checked){
    if (checked) { if (!selectedIds.value.includes(id)) selectedIds.value.push(id); }
    else { selectedIds.value = selectedIds.value.filter(v => v !== id); }
}
function toggleAll(checked){
    if (checked) selectedIds.value = props.sanksi.data.map(d => d.id);
    else selectedIds.value = [];
}
function cetakTerpilih(){
    if (!selectedIds.value.length) return;
    const ids = selectedIds.value.join(',');
    window.open(`/sanksi-administratif/sp1/cetak?ids=${ids}`, '_blank');
}
function cetakRekap(){
    const params = new URLSearchParams();
    if (selectedIds.value.length) {
        params.set('ids', selectedIds.value.join(','));
    } else {
        if (form.search) params.set('search', form.search);
        if (form.skala_usaha) params.set('skala_usaha', form.skala_usaha);
        if (form.kecamatan) params.set('kecamatan', form.kecamatan);
    }
    const qs = params.toString();
    window.open(`/sanksi-administratif/rekap/cetak${qs ? `?${qs}` : ''}`, '_blank');
}
function skalaBadge(v){ return {'bg-emerald-50 text-emerald-700': v==='Usaha Mikro','bg-blue-50 text-blue-700': v==='Usaha Kecil','bg-amber-50 text-amber-700': v==='Usaha Menengah','bg-purple-50 text-purple-700': v==='Usaha Besar','bg-gray-100 text-gray-500': !v}; }
function filter(){ filtersOpen.value=false; router.get('/sanksi-administratif', { ...form }, { preserveState:true, replace:true }); }
function resetFilters(){ form.search=''; form.skala_usaha=''; form.kecamatan=''; form.per_page=15; filter(); }
function changePageSize(){ router.get('/sanksi-administratif', { ...form, page:1 }, { preserveState:true, replace:true }); }
</script>
