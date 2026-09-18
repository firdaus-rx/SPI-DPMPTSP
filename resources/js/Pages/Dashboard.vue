<template>
    <AdminLayout title="Dashboard">
        <!-- Header — compact -->
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[17px] font-bold leading-none tracking-tight text-gray-900">Dashboard</h1>
                <p class="mt-1 max-w-xl truncate text-xs leading-relaxed text-gray-500">Ringkasan pengawasan &amp; usulan pencabutan</p>
            </div>
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-gray-500 ring-1 ring-gray-200">
                <Clock :size="12" class="text-gray-400" /> {{ today }}
            </span>
        </div>

        <!-- KPI — small clean -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white px-3.5 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Data</p>
                        <p class="mt-1 text-xl font-bold leading-none tracking-tight text-gray-900">{{ totalAll }}</p>
                        <p class="mt-1 text-[11px] leading-none text-gray-500">{{ totalPengawasan }} pencabutan · {{ totalSanksi }} usulan</p>
                    </div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-white"><Layers :size="14" /></span>
                </div>
                <div class="mt-3 flex h-1 overflow-hidden rounded-full bg-gray-100">
                    <span class="bg-gray-900" :style="{ width: pct(totalPengawasan, totalAll) + '%' }" />
                    <span class="bg-primary-500" :style="{ width: pct(totalSanksi, totalAll) + '%' }" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white px-3.5 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-amber-600">Daftar List Sanksi Pencabutan</p>
                        <p class="mt-1 text-xl font-bold leading-none tracking-tight text-gray-900">{{ totalPengawasan }}</p>
                        <p class="mt-1 text-[11px] leading-none text-gray-500">{{ risikoTinggi }} risiko tinggi · {{ belumDiisi }} belum diisi</p>
                    </div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100"><ClipboardList :size="14" /></span>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200">{{ risikoTinggi }} tinggi</span>
                    <span class="text-[11px] text-gray-400">{{ pct(totalPengawasan, totalAll) }}% dari total</span>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white px-3.5 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-primary-600">Usulan Pencabutan PB</p>
                        <p class="mt-1 text-xl font-bold leading-none tracking-tight text-gray-900">{{ totalSanksi }}</p>
                        <p class="mt-1 text-[11px] leading-none text-gray-500">Sanksi administratif</p>
                    </div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 ring-1 ring-primary-100"><Scale :size="14" /></span>
                </div>
                <p class="mt-3 text-[11px] text-gray-400">OCR: NIB · alamat · skala</p>
            </div>
        </div>

        <!-- Insight — small -->
        <div v-if="risikoTinggi || belumDiisi" class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs">
            <AlertTriangle :size="13" class="shrink-0 text-amber-600" />
            <span class="font-semibold text-amber-800">Perlu perhatian</span>
            <span class="text-amber-700">{{ risikoTinggi }} risiko Menengah Tinggi/Tinggi</span>
            <span v-if="risikoTinggi && belumDiisi" class="text-amber-300">·</span>
            <span v-if="belumDiisi" class="text-amber-700">{{ belumDiisi }} tanpa tingkat risiko</span>
            <Link href="/pengawasan?tingkat_risiko=Tinggi" class="ml-auto text-[11px] font-semibold text-amber-700 underline decoration-amber-300 underline-offset-2 hover:decoration-amber-600">Lihat risiko tinggi →</Link>
        </div>

        <!-- Kelompok: Distribusi -->
        <div class="mt-5 flex items-center gap-3">
            <h2 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Distribusi Data</h2>
            <span class="h-px flex-1 bg-gray-200" aria-hidden="true" />
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-5">
            <!-- Risiko — compact -->
            <div class="rounded-xl border border-gray-200 bg-white lg:col-span-3">
                <div class="flex items-center justify-between border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Tingkat Risiko</h3>
                    <span class="rounded-full bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-500 ring-1 ring-gray-200">{{ totalSemuaRisiko }} data</span>
                </div>
                <div v-if="risiko.length" class="space-y-1 p-2">
                    <Link v-for="item in risiko" :key="item.label" :href="`/pengawasan?tingkat_risiko=${encodeURIComponent(item.label === 'Belum Diisi' ? '' : item.label)}`" class="group flex items-center gap-2.5 rounded-lg border border-transparent px-2.5 py-2 hover:border-gray-100 hover:bg-gray-50">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md" :class="riskStyle(item.label).icon"><component :is="riskStyle(item.label).iconComponent" :size="13" :class="riskStyle(item.label).text" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-medium leading-none text-gray-900">{{ item.label }}</span>
                            <span class="mt-1.5 block h-1 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full" :class="riskStyle(item.label).bar" :style="{ width: persen(item.total) + '%' }" /></span>
                        </span>
                        <span class="text-right leading-none">
                            <span class="block text-sm font-bold" :class="riskStyle(item.label).text">{{ item.total }}</span>
                            <span class="block text-[11px] text-gray-400">{{ persen(item.total) }}%</span>
                        </span>
                    </Link>
                </div>
                <p v-else class="px-3.5 py-8 text-center text-xs text-gray-400">Belum ada data risiko.</p>
            </div>

            <!-- Skala — compact -->
            <div class="rounded-xl border border-gray-200 bg-white lg:col-span-2">
                <div class="border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Skala Usaha</h3>
                    <p class="text-[11px] text-gray-400">{{ totalSkala }} usulan · Sanksi administratif</p>
                </div>
                <div v-if="skala.length" class="space-y-1 p-2">
                    <Link v-for="item in skala" :key="item.label" :href="`/sanksi-administratif?skala_usaha=${encodeURIComponent(item.label === 'Belum Diisi' ? '' : item.label)}`" class="flex items-center gap-2.5 rounded-lg border border-transparent px-2.5 py-2 hover:border-gray-100 hover:bg-gray-50">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md" :class="skalaStyle(item.label).icon"><component :is="skalaStyle(item.label).iconComponent" :size="13" :class="skalaStyle(item.label).text" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-medium leading-none text-gray-900">{{ item.label }}</span>
                            <span class="mt-1.5 block h-1 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full" :class="skalaStyle(item.label).bar" :style="{ width: persenSkala(item.total) + '%' }" /></span>
                        </span>
                        <span class="text-right leading-none"><span class="block text-sm font-bold" :class="skalaStyle(item.label).text">{{ item.total }}</span><span class="block text-[11px] text-gray-400">{{ persenSkala(item.total) }}%</span></span>
                    </Link>
                </div>
                <p v-else class="px-3.5 py-8 text-center text-xs text-gray-400">Belum ada data skala.</p>
            </div>
        </div>

        <!-- Kelompok: Wilayah & Klasifikasi -->
        <div class="mt-5 flex items-center gap-3">
            <h2 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Wilayah &amp; Klasifikasi</h2>
            <span class="h-px flex-1 bg-gray-200" aria-hidden="true" />
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Penanaman Modal</h3>
                    <p class="text-[11px] text-gray-400">Sebaran jenis modal</p>
                </div>
                <ul class="space-y-1 p-2">
                    <li v-for="item in penanamanModal" :key="item.label" class="flex items-center justify-between rounded-lg bg-gray-50 px-2.5 py-2">
                        <span class="truncate text-xs font-medium text-gray-700">{{ item.label || 'Tidak Tercatat' }}</span>
                        <span class="ml-2 flex shrink-0 items-center gap-1.5"><span class="text-xs font-bold text-gray-900">{{ item.total }}</span><span class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-medium text-gray-500 ring-1 ring-gray-200">{{ pct(item.total, totalSanksi) }}%</span></span>
                    </li>
                    <li v-if="!penanamanModal.length" class="py-6 text-center text-xs text-gray-400">Belum ada data.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Top Kecamatan</h3>
                    <p class="text-[11px] text-gray-400">Usulan terbanyak</p>
                </div>
                <ol class="space-y-1 p-2">
                    <li v-for="(item, idx) in topKecamatan" :key="item.label" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-900 text-[10px] font-bold leading-none text-white">{{ idx + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-xs font-medium text-gray-900">{{ item.label }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-700">{{ item.total }}</span>
                    </li>
                    <li v-if="!topKecamatan.length" class="py-6 text-center text-xs text-gray-400">Belum ada data.</li>
                </ol>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Sebaran Wilayah</h3>
                    <p class="text-[11px] text-gray-400">Kab/Kota &amp; status sanksi</p>
                </div>
                <div class="space-y-2 p-2">
                    <div v-for="item in topKabKota" :key="item.label" class="flex items-center justify-between rounded-lg border border-gray-100 px-2.5 py-2">
                        <span class="inline-flex items-center gap-1.5 truncate text-xs text-gray-700"><MapPin :size="12" class="shrink-0 text-gray-400" />{{ item.label }}</span>
                        <span class="ml-2 shrink-0 text-xs font-bold text-gray-900">{{ item.total }}</span>
                    </div>
                    <div v-if="!topKabKota.length" class="py-2 text-center text-xs text-gray-400">—</div>
                    <div class="rounded-lg bg-gray-50 px-2.5 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Status Sanksi</p>
                        <div v-for="item in statusSanksi" :key="item.label" class="mt-1.5 flex items-center justify-between gap-2 text-xs">
                            <span class="truncate text-gray-600">{{ item.label }}</span>
                            <span class="shrink-0 font-semibold text-gray-900">{{ item.total }}</span>
                        </div>
                        <p v-if="!statusSanksi.length" class="py-2 text-center text-xs text-gray-400">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kelompok: Aktivitas terbaru -->
        <div class="mt-5 flex items-center gap-3">
            <h2 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Aktivitas Terbaru</h2>
            <span class="h-px flex-1 bg-gray-200" aria-hidden="true" />
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Daftar List Sanksi Pencabutan</h3>
                    <Link href="/pengawasan" class="text-[11px] font-semibold text-primary-600 hover:underline">Lihat semua →</Link>
                </div>
                <div class="divide-y divide-gray-100">
                    <Link v-for="row in recentPengawasan" :key="row.id" :href="`/pengawasan/${row.id}`" class="flex items-center gap-2.5 px-3.5 py-2.5 hover:bg-gray-50">
                        <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-600 ring-1 ring-amber-100 sm:flex"><ClipboardList :size="13" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold leading-none text-gray-900">{{ row.nama_pelaku_usaha || '—' }}</span>
                            <span class="mt-1 block truncate font-mono text-[11px] leading-none text-gray-500">NIB {{ row.nib || '—' }} · {{ row.kab_kota || '—' }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold leading-none" :class="riskBadge(row.tingkat_risiko)">{{ row.tingkat_risiko || '—' }}</span>
                    </Link>
                    <p v-if="!recentPengawasan.length" class="py-8 text-center text-xs text-gray-400">Belum ada data.</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-3.5 py-2.5">
                    <h3 class="text-xs font-semibold text-gray-800">Usulan Pencabutan PB</h3>
                    <Link href="/sanksi-administratif" class="text-[11px] font-semibold text-primary-600 hover:underline">Lihat semua →</Link>
                </div>
                <div class="divide-y divide-gray-100">
                    <Link v-for="row in recentSanksi" :key="row.id" :href="`/sanksi-administratif/${row.id}`" class="flex items-center gap-2.5 px-3.5 py-2.5 hover:bg-gray-50">
                        <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary-50 text-primary-600 ring-1 ring-primary-100 sm:flex"><Scale :size="13" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold leading-none text-gray-900"><span class="font-normal text-gray-400">#{{ row.no ?? '—' }} · </span>{{ row.nama_pelaku_usaha || '—' }}</span>
                            <span class="mt-1 block truncate font-mono text-[11px] leading-none text-gray-500">NIB {{ row.nib || '—' }} · {{ row.kecamatan || '—' }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold leading-none" :class="skalaBadge(row.skala_usaha)">{{ row.skala_usaha || '—' }}</span>
                    </Link>
                    <p v-if="!recentSanksi.length" class="py-8 text-center text-xs text-gray-400">Belum ada data.</p>
                </div>
            </div>
        </div>

        <!-- Aksi cepat — small -->
        <div class="mt-5 rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-3.5 py-2.5">
                <h3 class="text-xs font-semibold text-gray-800">Aksi Cepat</h3>
            </div>
            <div class="grid grid-cols-2 gap-2 p-2 lg:grid-cols-4">
                <Link href="/pengawasan" class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gray-900 text-white"><ClipboardList :size="13" /></span><span class="min-w-0"><span class="block truncate text-xs font-semibold leading-none text-gray-900">Daftar Sanksi</span><span class="block truncate text-[11px] leading-none text-gray-500">List pencabutan</span></span></Link>
                <Link href="/sanksi-administratif" class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary-600 text-white"><Scale :size="13" /></span><span class="min-w-0"><span class="block truncate text-xs font-semibold leading-none text-gray-900">Usulan Pencabutan</span><span class="block truncate text-[11px] leading-none text-gray-500">Sanksi administratif</span></span></Link>
                <Link href="/pengawasan/import" class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-purple-600 text-white"><FileUp :size="13" /></span><span class="min-w-0"><span class="block truncate text-xs font-semibold leading-none text-gray-900">Impor Pengawasan</span><span class="block truncate text-[11px] leading-none text-gray-500">OCR PDF</span></span></Link>
                <Link href="/sanksi-administratif/import" class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white"><FileSpreadsheet :size="13" /></span><span class="min-w-0"><span class="block truncate text-xs font-semibold leading-none text-gray-900">Impor Usulan</span><span class="block truncate text-[11px] leading-none text-gray-500">OCR sanksi</span></span></Link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Layers, ClipboardList, Scale, AlertTriangle, Clock, MapPin, FileUp, FileSpreadsheet, Users, ShieldCheck, Shield, ShieldAlert, MinusCircle } from '@lucide/vue';

const props = defineProps({
    totalPelakuUsaha: { type: Number, default: 0 },
    totalPengawasan: { type: Number, default: 0 },
    totalSanksi: { type: Number, default: 0 },
    totalAll: { type: Number, default: 0 },
    risiko: { type: Array, default: () => [] },
    risikoTinggi: { type: Number, default: 0 },
    belumDiisi: { type: Number, default: 0 },
    skala: { type: Array, default: () => [] },
    penanamanModal: { type: Array, default: () => [] },
    topKecamatan: { type: Array, default: () => [] },
    topKabKota: { type: Array, default: () => [] },
    recentPengawasan: { type: Array, default: () => [] },
    recentSanksi: { type: Array, default: () => [] },
    statusSanksi: { type: Array, default: () => [] },
});

const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const totalSemuaRisiko = computed(() => props.risiko.reduce((s, x) => s + (x.total || 0), 0));
const totalSkala = computed(() => props.skala.reduce((s, x) => s + (x.total || 0), 0));
function pct(v, t) { const d = t ?? totalSemuaRisiko.value; return d ? Math.round((v / d) * 100) : 0; }
function persen(t) { return pct(t); }
function persenSkala(t) { const d = totalSkala.value; return d ? Math.round((t / d) * 100) : 0; }
function riskBadge(v) {
    return { 'bg-red-50 text-red-700 ring-1 ring-red-200': v==='Tinggi', 'bg-orange-50 text-orange-700 ring-1 ring-orange-200': v==='Menengah Tinggi', 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200': v==='Menengah', 'bg-lime-50 text-lime-700 ring-1 ring-lime-200': v==='Menengah Rendah', 'bg-green-50 text-green-700 ring-1 ring-green-200': v==='Rendah', 'bg-gray-100 text-gray-500': !v };
}
function skalaBadge(v) {
    return { 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200': v==='Usaha Mikro','bg-blue-50 text-blue-700 ring-1 ring-blue-200': v==='Usaha Kecil','bg-amber-50 text-amber-700 ring-1 ring-amber-200': v==='Usaha Menengah','bg-purple-50 text-purple-700 ring-1 ring-purple-200': v==='Usaha Besar','bg-gray-100 text-gray-500': !v };
}
const RISK_STYLES = {
    'Rendah': { text:'text-green-600', icon:'bg-green-50', card:'', bar:'bg-green-500', iconComponent: ShieldCheck },
    'Menengah Rendah': { text:'text-lime-600', icon:'bg-lime-50', card:'', bar:'bg-lime-500', iconComponent: Shield },
    'Menengah': { text:'text-yellow-600', icon:'bg-yellow-50', card:'', bar:'bg-yellow-500', iconComponent: Shield },
    'Menengah Tinggi': { text:'text-orange-600', icon:'bg-orange-50', card:'', bar:'bg-orange-500', iconComponent: ShieldAlert },
    'Tinggi': { text:'text-red-600', icon:'bg-red-50', card:'', bar:'bg-red-500', iconComponent: ShieldAlert },
    'Belum Diisi': { text:'text-gray-500', icon:'bg-gray-100', card:'', bar:'bg-gray-400', iconComponent: MinusCircle },
};
const SKALA_STYLES = {
    'Usaha Mikro': { text:'text-emerald-600', icon:'bg-emerald-50', bar:'bg-emerald-500', iconComponent: Users },
    'Usaha Kecil': { text:'text-blue-600', icon:'bg-blue-50', bar:'bg-blue-500', iconComponent: Users },
    'Usaha Menengah': { text:'text-amber-600', icon:'bg-amber-50', bar:'bg-amber-500', iconComponent: Users },
    'Usaha Besar': { text:'text-purple-600', icon:'bg-purple-50', bar:'bg-purple-500', iconComponent: Users },
    'Belum Diisi': { text:'text-gray-500', icon:'bg-gray-100', bar:'bg-gray-400', iconComponent: MinusCircle },
};
function riskStyle(l){ return RISK_STYLES[l] ?? RISK_STYLES['Belum Diisi']; }
function skalaStyle(l){ return SKALA_STYLES[l] ?? SKALA_STYLES['Belum Diisi']; }
</script>
