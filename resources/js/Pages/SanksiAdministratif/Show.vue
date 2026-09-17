<template>
    <AdminLayout title="Detail Usulan Pencabutan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <Link href="/sanksi-administratif" class="p-2 rounded-lg hover:bg-gray-100"><ArrowLeft :size="20" class="text-gray-500" /></Link>
                <div><h1 class="text-2xl font-bold text-gray-900">{{ sanksi.nama_pelaku_usaha || 'Data Tanpa Nama' }}</h1><p class="text-sm text-gray-500 mt-0.5">No {{ sanksi.no ?? '-' }} · NIB {{ sanksi.nib || '-' }}</p></div>
            </div>
            <div class="flex items-center gap-2 ml-11 sm:ml-0">
                <a :href="`/sanksi-administratif/${sanksi.id}/sp1`" target="_blank" class="inline-flex items-center gap-2 bg-primary-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 shadow-sm"><Printer :size="16" />Cetak SP1</a>
                <Link :href="`/sanksi-administratif/${sanksi.id}/edit`" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50"><Pencil :size="16" />Edit</Link>
                <button @click="handleDelete" class="inline-flex items-center gap-2 bg-white border border-red-200 text-red-600 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-red-50"><Trash2 :size="16" />Hapus</button>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-primary-50/50 border-b border-primary-100/50"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center"><Building2 :size="16" class="text-primary-600" /></div><h3 class="font-semibold text-gray-900">Pelaku Usaha</h3></div></div>
                <div class="p-6"><dl class="space-y-4">
                    <DetailRow label="No" :value="sanksi.no" />
                    <DetailRow label="Nama" :value="sanksi.nama_pelaku_usaha" />
                    <DetailRow label="NIB" :value="sanksi.nib" :mono="true" />
                    <DetailRow label="Penanaman Modal" :value="sanksi.jenis_penanaman_modal" />
                    <DetailRow label="Skala Usaha" :value="sanksi.skala_usaha" />
                </dl></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-100/50"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center"><MapPin :size="16" class="text-amber-600" /></div><h3 class="font-semibold text-gray-900">Alamat</h3></div></div>
                <div class="p-6"><dl class="space-y-4">
                    <DetailRow label="Jalan" :value="sanksi.alamat" :full="true" />
                    <DetailRow label="Kelurahan" :value="sanksi.kelurahan" />
                    <DetailRow label="Kecamatan" :value="sanksi.kecamatan" />
                    <DetailRow label="Kab/Kota" :value="sanksi.kab_kota" />
                    <DetailRow label="Provinsi" :value="sanksi.provinsi" />
                </dl></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center"><FileSearch :size="16" class="text-gray-600" /></div><h3 class="font-semibold text-gray-900">Sumber Import</h3></div></div>
                <div class="p-6"><dl class="space-y-4">
                    <DetailRow label="File" :value="sanksi.sumber_file" />
                    <DetailRow label="Halaman" :value="sanksi.sumber_halaman" />
                    <DetailRow label="Metode" :value="sanksi.sumber_metode" />
                    <DetailRow label="Skor OCR" :value="sanksi.skor_ocr" />
                </dl></div>
            </div>
        </div>
    </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DetailRow from '@/Components/DetailRow.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2, Building2, MapPin, FileSearch, Printer } from '@lucide/vue';
const props = defineProps({ sanksi: Object });
function handleDelete(){ if(confirm('Yakin hapus?')) router.delete(`/sanksi-administratif/${props.sanksi.id}`); }
</script>
