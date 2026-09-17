<template>
    <AdminLayout title="Detail Pengawasan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <Link href="/pengawasan" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <ArrowLeft :size="20" class="text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ pengawasan.nama_pelaku_usaha }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">NIB {{ pengawasan.nib || '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 ml-11 sm:ml-0">
                <span
                    v-if="pengawasan.tingkat_risiko"
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium"
                    :class="risikoClass(pengawasan.tingkat_risiko)"
                >
                    {{ pengawasan.tingkat_risiko }}
                </span>
                <Link
                    :href="`/pengawasan/${pengawasan.id}/edit`"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                >
                    <Pencil :size="16" />
                    Edit
                </Link>
                <button
                    @click="handleDelete"
                    class="inline-flex items-center gap-2 bg-white border border-red-200 text-red-600 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-red-50 transition-colors"
                >
                    <Trash2 :size="16" />
                    Hapus
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Data Sanksi -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-red-50/50 border-b border-red-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                            <FileWarning :size="16" class="text-red-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Data Sanksi</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="Nomor Sanksi" :value="pengawasan.nomor_sanksi" />
                        <DetailRow label="Tanggal Pengenaan" :value="formatDate(pengawasan.tanggal_pengenaan_sanksi)" />
                        <DetailRow label="Tenggat Waktu" :value="formatDate(pengawasan.tenggat_waktu_pemenuhan_kewajiban_tanggapan)" />
                        <DetailRow label="Jenis Sanksi" :value="pengawasan.jenis_sanksi" />
                        <DetailRow label="Masa Berlaku" :value="pengawasan.masa_berlaku" />
                        <DetailRow label="Status Sanksi" :value="pengawasan.status_sanksi" />
                        <DetailRow label="Sumber Sanksi" :value="pengawasan.sumber_sanksi" :full="true" />
                    </dl>
                </div>
            </div>

            <!-- Data Pelaku Usaha -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-primary-50/50 border-b border-primary-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                            <Building2 :size="16" class="text-primary-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Data Pelaku Usaha</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="Nama" :value="pengawasan.nama_pelaku_usaha" />
                        <DetailRow label="NIB" :value="pengawasan.nib" :mono="true" />
                        <DetailRow label="Jenis PM" :value="pengawasan.jenis_penanaman_modal" />
                        <DetailRow label="Skala Usaha" :value="pengawasan.skala_usaha" />
                        <DetailRow label="Sumber Data" :value="pengawasan.sumber_data" />
                        <div class="flex items-start justify-between py-2 border-b border-gray-50">
                            <dt class="text-sm text-gray-500">Tingkat Risiko</dt>
                            <dd>
                                <span
                                    v-if="pengawasan.tingkat_risiko"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="risikoClass(pengawasan.tingkat_risiko)"
                                >
                                    {{ pengawasan.tingkat_risiko }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Perizinan -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-green-50/50 border-b border-green-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <FileCheck :size="16" class="text-green-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Perizinan Berusaha</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="Jenis Perizinan" :value="pengawasan.jenis_perizinan" />
                        <DetailRow label="Nomor Perizinan" :value="pengawasan.nomor_perizinan" :mono="true" />
                        <DetailRow label="Status" :value="pengawasan.status_perizinan" />
                        <DetailRow label="Kementerian/Lembaga" :value="pengawasan.kementerian_lembaga" />
                        <DetailRow label="Kewenangan" :value="pengawasan.kewenangan" />
                    </dl>
                </div>
            </div>

            <!-- Lokasi -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <MapPin :size="16" class="text-amber-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Lokasi Usaha</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="Alamat" :value="pengawasan.alamat" :full="true" />
                        <DetailRow label="Kelurahan" :value="pengawasan.kelurahan" />
                        <DetailRow label="Kecamatan" :value="pengawasan.kecamatan" />
                        <DetailRow label="Kab/Kota" :value="pengawasan.kab_kota" />
                        <DetailRow label="Provinsi" :value="pengawasan.provinsi" />
                    </dl>
                </div>
            </div>

            <!-- Data Usaha -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-purple-50/50 border-b border-purple-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <Briefcase :size="16" class="text-purple-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Data Usaha</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="Kode Proyek" :value="pengawasan.nomor_kode_proyek" :mono="true" />
                        <DetailRow label="Luas Lahan" :value="formatLuas(pengawasan)" />
                        <DetailRow label="Tenaga Kerja" :value="pengawasan.jumlah_tenaga_kerja ? pengawasan.jumlah_tenaga_kerja + ' orang' : null" />
                        <DetailRow label="Investasi" :value="formatRupiah(pengawasan.rencana_investasi)" />
                    </dl>
                </div>
            </div>

            <!-- Sumber Import -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                            <FileSearch :size="16" class="text-gray-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Sumber Import</h3>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <DetailRow label="File" :value="pengawasan.sumber_file" />
                        <DetailRow label="Halaman" :value="pengawasan.sumber_halaman" />
                        <DetailRow label="Metode" :value="pengawasan.sumber_metode" />
                        <DetailRow label="Skor OCR" :value="pengawasan.skor_ocr" />
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DetailRow from '@/Components/DetailRow.vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Pencil,
    Trash2,
    FileWarning,
    Building2,
    FileCheck,
    MapPin,
    Briefcase,
    FileSearch,
} from '@lucide/vue';

const props = defineProps({
    pengawasan: Object,
});

function formatDate(date) {
    if (!date) return null;
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatRupiah(val) {
    if (!val) return null;
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}

function formatLuas(data) {
    if (!data.luas_lahan) return null;
    return Number(data.luas_lahan).toLocaleString('id-ID') + ' ' + (data.satuan_luas || '');
}

function risikoClass(risiko) {
    return {
        'bg-red-50 text-red-700': risiko === 'Tinggi',
        'bg-orange-50 text-orange-700': risiko === 'Menengah Tinggi',
        'bg-yellow-50 text-yellow-700': risiko === 'Menengah',
        'bg-lime-50 text-lime-700': risiko === 'Menengah Rendah',
        'bg-green-50 text-green-700': risiko === 'Rendah',
    };
}

function handleDelete() {
    if (confirm('Yakin ingin menghapus data ini?')) {
        router.delete(`/pengawasan/${props.pengawasan.id}`);
    }
}
</script>
