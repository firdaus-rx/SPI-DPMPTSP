<template>
    <AdminLayout title="Tambah Sanksi Pencabutan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <Link href="/pengawasan" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <ArrowLeft :size="20" class="text-gray-500" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tambah Sanksi Pencabutan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Isi formulir untuk menambah data Daftar List Sanksi Pencabutan</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
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
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Nomor Sanksi</label>
                            <input v-model="form.nomor_sanksi" type="text" class="input" placeholder="SNK-...">
                        </div>
                        <div>
                            <label class="label">Tanggal Pengenaan Sanksi</label>
                            <input v-model="form.tanggal_pengenaan_sanksi" type="date" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Tenggat Waktu</label>
                            <input v-model="form.tenggat_waktu_pemenuhan_kewajiban_tanggapan" type="date" class="input">
                        </div>
                        <div>
                            <label class="label">Jenis Sanksi</label>
                            <input v-model="form.jenis_sanksi" type="text" class="input" placeholder="Pencabutan, Denda, dll">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Masa Berlaku</label>
                            <input v-model="form.masa_berlaku" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Status Sanksi</label>
                            <input v-model="form.status_sanksi" type="text" class="input" placeholder="Terkirim ke Pelaku Usaha">
                        </div>
                    </div>
                    <div>
                        <label class="label">Sumber Sanksi</label>
                        <textarea v-model="form.sumber_sanksi" rows="2" class="input" placeholder="Pengenaan Sanksi Administratif..."></textarea>
                    </div>
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
                <div class="p-6 space-y-5">
                    <div>
                        <label class="label">Nama Pelaku Usaha <span class="text-red-500">*</span></label>
                        <input v-model="form.nama_pelaku_usaha" type="text" class="input" required placeholder="Masukkan nama pelaku usaha">
                        <span v-if="form.errors.nama_pelaku_usaha" class="text-red-500 text-xs mt-1">{{ form.errors.nama_pelaku_usaha }}</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">NIB</label>
                            <input v-model="form.nib" type="text" class="input font-mono" placeholder="0000000000000">
                        </div>
                        <div>
                            <label class="label">Jenis Penanaman Modal</label>
                            <input v-model="form.jenis_penanaman_modal" type="text" class="input" placeholder="PMDN, PMA">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Skala Usaha</label>
                            <input v-model="form.skala_usaha" type="text" class="input" placeholder="Usaha Mikro, Kecil, Menengah">
                        </div>
                        <div>
                            <label class="label">Sumber Data</label>
                            <input v-model="form.sumber_data" type="text" class="input" placeholder="OSS RBA">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Tingkat Risiko</label>
                            <select v-model="form.tingkat_risiko" class="input">
                                <option value="">Pilih tingkat risiko</option>
                                <option value="Rendah">Rendah</option>
                                <option value="Menengah">Menengah</option>
                                <option value="Menengah Tinggi">Menengah Tinggi</option>
                                <option value="Tinggi">Tinggi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perizinan Berusaha -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-green-50/50 border-b border-green-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <FileCheck :size="16" class="text-green-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Perizinan Berusaha</h3>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Jenis Perizinan</label>
                            <input v-model="form.jenis_perizinan" type="text" class="input" placeholder="Sertifikat Standar">
                        </div>
                        <div>
                            <label class="label">Nomor Perizinan</label>
                            <input v-model="form.nomor_perizinan" type="text" class="input font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Status Perizinan</label>
                            <input v-model="form.status_perizinan" type="text" class="input" placeholder="Terbit Otomatis">
                        </div>
                        <div>
                            <label class="label">Kementerian/Lembaga</label>
                            <input v-model="form.kementerian_lembaga" type="text" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Kewenangan</label>
                        <input v-model="form.kewenangan" type="text" class="input" placeholder="Menteri/Kepala Badan">
                    </div>
                </div>
            </div>

            <!-- Lokasi Usaha -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-100/50">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <MapPin :size="16" class="text-amber-600" />
                        </div>
                        <h3 class="font-semibold text-gray-900">Lokasi Usaha</h3>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="label">Alamat</label>
                        <textarea v-model="form.alamat" rows="2" class="input" placeholder="Jalan, RT/RW, dll"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Kelurahan</label>
                            <input v-model="form.kelurahan" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Kecamatan</label>
                            <input v-model="form.kecamatan" type="text" class="input">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Kab/Kota</label>
                            <input v-model="form.kab_kota" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">Provinsi</label>
                            <input v-model="form.provinsi" type="text" class="input">
                        </div>
                    </div>
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
                <div class="p-6 space-y-5">
                    <div>
                        <label class="label">Nomor Kode Proyek</label>
                        <input v-model="form.nomor_kode_proyek" type="text" class="input font-mono" placeholder="202205-1014-3945-4036-343">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Luas Lahan</label>
                            <input v-model="form.luas_lahan" type="number" step="0.01" class="input" placeholder="0">
                        </div>
                        <div>
                            <label class="label">Satuan Luas</label>
                            <input v-model="form.satuan_luas" type="text" class="input" placeholder="M2, Ha">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="label">Jumlah Tenaga Kerja</label>
                            <input v-model="form.jumlah_tenaga_kerja" type="number" class="input" placeholder="0">
                        </div>
                        <div>
                            <label class="label">Rencana Investasi (Rp)</label>
                            <input v-model="form.rencana_investasi" type="number" step="0.01" class="input" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pb-6">
                <Link href="/pengawasan" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </Link>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm disabled:opacity-50"
                    :disabled="form.processing"
                >
                    <Save :size="16" v-if="!form.processing" />
                    <Loader2 :size="16" v-else class="animate-spin" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2, FileWarning, Building2, FileCheck, MapPin, Briefcase } from '@lucide/vue';

const form = useForm({
    nomor_sanksi: '',
    tanggal_pengenaan_sanksi: '',
    tenggat_waktu_pemenuhan_kewajiban_tanggapan: '',
    jenis_sanksi: '',
    masa_berlaku: '',
    status_sanksi: '',
    sumber_sanksi: '',
    nama_pelaku_usaha: '',
    nib: '',
    jenis_penanaman_modal: '',
    skala_usaha: '',
    sumber_data: '',
    tingkat_risiko: '',
    jenis_perizinan: '',
    nomor_perizinan: '',
    status_perizinan: '',
    kementerian_lembaga: '',
    kewenangan: '',
    alamat: '',
    kelurahan: '',
    kecamatan: '',
    kab_kota: '',
    provinsi: '',
    nomor_kode_proyek: '',
    luas_lahan: '',
    satuan_luas: '',
    jumlah_tenaga_kerja: '',
    rencana_investasi: '',
});

function submit() {
    form.post('/pengawasan');
}
</script>
