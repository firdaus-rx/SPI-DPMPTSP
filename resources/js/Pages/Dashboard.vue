<template>
    <AdminLayout title="Dashboard">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Ringkasan eksekutif pengawasan &amp; usulan pencabutan — pantau risiko, skala, dan sebaran wilayah.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <Clock :size="14" /> {{ today }}
                </div>
            </div>
        </div>

        <!-- KPI 3 kartu -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Total Data</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ totalAll }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ totalPengawasan }} pencabutan · {{ totalSanksi }} usulan</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-900 text-white"><Layers :size="18" /></span>
                </div>
                <div class="mt-4 flex h-1.5 overflow-hidden rounded-full bg-gray-100">
                    <span class="bg-gray-900" :style="{ width: pct(totalPengawasan, totalAll) + '%' }" />
                    <span class="bg-primary-500" :style="{ width: pct(totalSanksi, totalAll) + '%' }" />
                </div>
                <p class="mt-2 text-[11px] text-gray-400">{{ pct(totalPengawasan, totalAll) }}% Daftar List Sanksi Pencabutan · {{ pct(totalSanksi, totalAll) }}% Usulan Pencabutan</p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-700">Daftar List Sanksi Pencabutan</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ totalPengawasan }}</p>
                        <p class="mt-1 text-xs text-amber-700/70">Pengawasan kepatuhan</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-200"><ClipboardList :size="18" /></span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-white px-2 py-1 text-amber-700 ring-1 ring-amber-200">{{ risikoTinggi }} risiko tinggi</span>
                    <span class="text-amber-700/60">{{ belumDiisi }} belum diisi</span>
                </div>
            </div>

            <div class="rounded-2xl border border-primary-200 bg-primary-50/50 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-primary-700">Usulan Pencabutan PB</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ totalSanksi }}</p>
                        <p class="mt-1 text-xs text-primary-700/70">Sanksi Administratif</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-primary-600 ring-1 ring-primary-200"><Scale :size="18" /></span>
                </div>
                <p class="mt-4 text-xs text-primary-700/60">Sinkron OCR: nib, alamat, skala</p>
            </div>
        </div>

        <!-- Insight bar -->
        <div v-if="risikoTinggi || belumDiisi" class="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
            <AlertTriangle :size="16" class="text-amber-600" />
            <span class="font-medium text-amber-800">Perlu perhatian:</span>
            <span class="text-amber-700">{{ risikoTinggi }} data risiko Menengah Tinggi/Tinggi</span>
            <span v-if="risikoTinggi && belumDiisi" class="text-amber-300">·</span>
            <span v-if="belumDiisi" class="text-amber-700">{{ belumDiisi }} tanpa tingkat risiko</span>
            <Link href="/pengawasan?tingkat_risiko=Tinggi" class="ml-auto text-xs font-semibold text-amber-700 underline-offset-2 hover:underline">Lihat risiko tinggi →</Link>
        </div>

        <!-- Baris 1: Risiko & Skala -->
        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-5">
            <!-- Risiko -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 lg:col-span-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-700">Sebaran Tingkat Risiko</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ totalSemuaRisiko }} terklasifikasi</span>
                </div>
                <div v-if="risiko.length" class="mt-5 space-y-3">
                    <Link v-for="item in risiko" :key="item.label" :href="`/pengawasan?tingkat_risiko=${encodeURIComponent(item.label === 'Belum Diisi' ? '' : item.label)}`" class="group flex items-center gap-3 rounded-xl border border-gray-100 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="riskStyle(item.label).icon"><component :is="riskStyle(item.label).iconComponent" :size="16" :class="riskStyle(item.label).text" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-gray-900">{{ item.label }}</span>
                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full" :class="riskStyle(item.label).bar" :style="{ width: persen(item.total) + '%' }" /></span>
                        </span>
                        <span class="text-right">
                            <span class="block text-lg font-bold" :class="riskStyle(item.label).text">{{ item.total }}</span>
                            <span class="block text-xs text-gray-400">{{ persen(item.total) }}%</span>
                        </span>
                    </Link>
                </div>
                <p v-else class="py-8 text-center text-sm text-gray-400">Belum ada data risiko.</p>
            </div>

            <!-- Skala -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 lg:col-span-2">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-700">Skala Usaha — Usulan Pencabutan</h2>
                <p class="mt-1 text-xs text-gray-400">{{ totalSkala }} usulan terklasifikasi</p>
                <div v-if="skala.length" class="mt-5 space-y-3">
                    <Link v-for="item in skala" :key="item.label" :href="`/sanksi-administratif?skala_usaha=${encodeURIComponent(item.label === 'Belum Diisi' ? '' : item.label)}`" class="flex items-center gap-3 rounded-xl border border-gray-100 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="skalaStyle(item.label).icon"><component :is="skalaStyle(item.label).iconComponent" :size="16" :class="skalaStyle(item.label).text" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-gray-900">{{ item.label }}</span>
                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full" :class="skalaStyle(item.label).bar" :style="{ width: persenSkala(item.total) + '%' }" /></span>
                        </span>
                        <span class="text-right"><span class="block text-lg font-bold" :class="skalaStyle(item.label).text">{{ item.total }}</span><span class="block text-xs text-gray-400">{{ persenSkala(item.total) }}%</span></span>
                    </Link>
                </div>
                <p v-else class="py-8 text-center text-sm text-gray-400">Belum ada data skala.</p>
            </div>
        </div>

        <!-- Baris 2: Penanaman Modal, Kecamatan, Kab/Kota -->
        <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Penanaman Modal</h3>
                <p class="mt-1 text-xs text-gray-400">Dominasi PMDN</p>
                <ul class="mt-4 space-y-2">
                    <li v-for="item in penanamanModal" :key="item.label" class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="truncate text-sm text-gray-700">{{ item.label || 'Tidak Tercatat' }}</span>
                        <span class="ml-3 flex items-center gap-2"><span class="text-sm font-bold text-gray-900">{{ item.total }}</span><span class="rounded-full bg-white px-2 py-0.5 text-xs text-gray-500 ring-1 ring-gray-200">{{ pct(item.total, totalSanksi) }}%</span></span>
                    </li>
                    <li v-if="!penanamanModal.length" class="py-6 text-center text-sm text-gray-400">Belum ada data.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Top Kecamatan</h3>
                <p class="mt-1 text-xs text-gray-400">Usulan terbanyak</p>
                <ol class="mt-4 space-y-2">
                    <li v-for="(item, idx) in topKecamatan" :key="item.label" class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white">{{ idx + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900">{{ item.label }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700">{{ item.total }}</span>
                    </li>
                    <li v-if="!topKecamatan.length" class="py-6 text-center text-sm text-gray-400">Belum ada data.</li>
                </ol>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Sebaran Wilayah</h3>
                <p class="mt-1 text-xs text-gray-400">Kab/Kota &amp; status sanksi</p>
                <div class="mt-4 space-y-3">
                    <div v-for="item in topKabKota" :key="item.label" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                        <span class="inline-flex items-center gap-2 text-sm text-gray-700"><MapPin :size="14" class="text-gray-400" />{{ item.label }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ item.total }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Status Sanksi</p>
                        <div v-for="item in statusSanksi" :key="item.label" class="mt-2 flex items-center justify-between text-sm">
                            <span class="truncate text-gray-600">{{ item.label }}</span>
                            <span class="font-semibold text-gray-900">{{ item.total }}</span>
                        </div>
                        <p v-if="!statusSanksi.length" class="py-2 text-center text-sm text-gray-400">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data terbaru -->
        <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Terbaru — Daftar List Sanksi Pencabutan</h3>
                    <Link href="/pengawasan" class="text-xs font-semibold text-primary-600 hover:underline">Lihat semua →</Link>
                </div>
                <div class="mt-4 divide-y divide-gray-100">
                    <Link v-for="row in recentPengawasan" :key="row.id" :href="`/pengawasan/${row.id}`" class="flex items-center gap-3 py-3 hover:bg-gray-50 -mx-2 px-2 rounded-xl">
                        <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 sm:flex"><ClipboardList :size="16" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-gray-900">{{ row.nama_pelaku_usaha || '—' }}</span>
                            <span class="block truncate font-mono text-xs text-gray-500">NIB {{ row.nib || '—' }} · {{ row.kab_kota || '—' }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-1 text-xs font-semibold" :class="riskBadge(row.tingkat_risiko)">{{ row.tingkat_risiko || '—' }}</span>
                    </Link>
                    <p v-if="!recentPengawasan.length" class="py-8 text-center text-sm text-gray-400">Belum ada data.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Terbaru — Usulan Pencabutan PB</h3>
                    <Link href="/sanksi-administratif" class="text-xs font-semibold text-primary-600 hover:underline">Lihat semua →</Link>
                </div>
                <div class="mt-4 divide-y divide-gray-100">
                    <Link v-for="row in recentSanksi" :key="row.id" :href="`/sanksi-administratif/${row.id}`" class="flex items-center gap-3 py-3 hover:bg-gray-50 -mx-2 px-2 rounded-xl">
                        <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-700 sm:flex"><Scale :size="16" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-gray-900"><span class="text-gray-400">#{{ row.no ?? '—' }} · </span>{{ row.nama_pelaku_usaha || '—' }}</span>
                            <span class="block truncate font-mono text-xs text-gray-500">NIB {{ row.nib || '—' }} · {{ row.kecamatan || '—' }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-1 text-xs font-semibold" :class="skalaBadge(row.skala_usaha)">{{ row.skala_usaha || '—' }}</span>
                    </Link>
                    <p v-if="!recentSanksi.length" class="py-8 text-center text-sm text-gray-400">Belum ada data.</p>
                </div>
            </div>
        </div>

        <!-- Aksi cepat -->
        <div class="mt-6 rounded-2xl border border-gray-100 bg-white p-6">
            <h3 class="text-sm font-bold uppercase tracking-widest text-gray-700">Aksi Cepat</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link href="/pengawasan" class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-white"><ClipboardList :size="16" /></span><span><span class="block text-sm font-semibold text-gray-900">Daftar Sanksi</span><span class="block text-xs text-gray-500">List pencabutan</span></span></Link>
                <Link href="/sanksi-administratif" class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600 text-white"><Scale :size="16" /></span><span><span class="block text-sm font-semibold text-gray-900">Usulan Pencabutan</span><span class="block text-xs text-gray-500">Sanksi administratif</span></span></Link>
                <Link href="/pengawasan/import" class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-600 text-white"><FileUp :size="16" /></span><span><span class="block text-sm font-semibold text-gray-900">Impor Pengawasan</span><span class="block text-xs text-gray-500">OCR PDF</span></span></Link>
                <Link href="/sanksi-administratif/import" class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-gray-300 hover:bg-gray-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white"><FileSpreadsheet :size="16" /></span><span><span class="block text-sm font-semibold text-gray-900">Impor Usulan</span><span class="block text-xs text-gray-500">OCR sanksi</span></span></Link>
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
