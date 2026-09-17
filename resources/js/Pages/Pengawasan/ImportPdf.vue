<template>
    <AdminLayout title="Import PDF">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Impor Dokumen</h1>
                <p class="text-sm text-gray-500 mt-1">Unggah dokumen untuk ekstraksi data pengawasan secara otomatis</p>
            </div>
        </div>

        <!-- Flash Message -->
        <div v-if="$page.props.flash?.success" class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <CheckCircle2 :size="16" class="text-green-600" />
            </div>
            <p class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
        </div>

        <!-- Upload Area -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 bg-purple-50/50 border-b border-purple-100/50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <Upload :size="16" class="text-purple-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Unggah Dokumen</h3>
                </div>
                <p class="text-xs text-gray-500 mt-2">Format yang didukung: PDF, JPG, PNG, BMP, TIFF, WEBP</p>
            </div>
            <div class="p-6">
                <form @submit.prevent="uploadFile">
                    <label
                        class="relative flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200"
                        :class="uploading
                            ? 'border-primary-300 bg-primary-50/50'
                            : 'border-gray-200 hover:border-primary-400 hover:bg-primary-50/30'"
                    >
                        <input
                            type="file"
                            ref="fileInput"
                            accept=".pdf,.jpg,.jpeg,.png,.bmp,.tiff,.webp"
                            class="hidden"
                            @change="onFileChange"
                            :disabled="uploading"
                        >
                        <template v-if="!uploading">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3 group-hover:bg-primary-100 transition-colors">
                                <FileUp :size="28" class="text-gray-400 group-hover:text-primary-500" />
                            </div>
                            <p class="text-sm font-medium text-gray-600">{{ fileName || 'Seret dan lepas berkas di sini, atau klik untuk memilih' }}</p>
                            <p class="text-xs text-gray-400 mt-1">Ukuran maksimum 20 MB per berkas</p>
                        </template>
                        <template v-else>
                            <div class="w-14 h-14 rounded-2xl bg-primary-100 flex items-center justify-center mb-3">
                                <Loader2 :size="28" class="text-primary-500 animate-spin" />
                            </div>
                            <p class="text-sm font-medium text-primary-600">Memproses dokumen...</p>
                            <p class="text-xs text-gray-400 mt-1">Mohon tunggu, ekstraksi data sedang berlangsung</p>
                        </template>
                    </label>
                </form>
            </div>
        </div>

        <!-- No Results -->
        <div v-if="results && results.length === 0" class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <div class="flex items-center gap-4 p-4 bg-amber-50 rounded-xl">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                    <AlertTriangle :size="20" class="text-amber-600" />
                </div>
                <div>
                    <p class="font-medium text-amber-800">Data Tidak Ditemukan</p>
                    <p class="text-sm text-amber-600 mt-0.5">Dokumen berhasil diproses, namun tidak ada data yang dapat diekstrak. Silakan periksa kualitas dokumen atau lakukan entri manual.</p>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div v-if="results && results.length > 0" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                        <FileSearch :size="16" class="text-primary-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Tinjau Hasil Ekstraksi</h3>
                        <p class="text-sm text-gray-500">{{ results.length }} data berhasil dikenali — verifikasi kembali sebelum menyimpan</p>
                    </div>
                </div>
                <button
                    @click="toggleAll"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 hover:bg-white transition-colors"
                >
                    <component :is="allExpanded ? PanelLeftClose : PanelLeftOpen" :size="16" />
                    {{ allExpanded ? 'Ciutkan Semua' : 'Perluas Semua' }}
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="simpanData" novalidate>
                <div v-for="(r, i) in results" :key="i" class="border-b border-gray-100 last:border-b-0">
                    <!-- Row Header -->
                    <div
                        class="flex items-center gap-4 px-6 py-4 cursor-pointer select-none hover:bg-gray-50/50 transition-colors"
                        @click="toggleSection(i)"
                    >
                        <input
                            type="checkbox"
                            v-model="r.selected"
                            class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            @click.stop
                        >
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ r.nama_pelaku_usaha || 'Data Tanpa Nama' }}</p>
                            <p class="text-sm text-gray-400 mt-0.5">NIB {{ r.nib || '—' }} · {{ r.nomor_sanksi || 'Nomor sanksi belum tersedia' }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-600">
                                {{ r.sumber_metode || 'OCR' }}
                            </span>
                            <ChevronDown :size="18" class="text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded[i] }" />
                        </div>
                    </div>

                    <!-- Detail Form -->
                    <div v-show="expanded[i]" class="px-6 pb-6 space-y-5">
                        <!-- Sanksi -->
                        <SectionCard v-if="hasSanksi(r)" title="Data Sanksi" color="red">
                            <FieldGroup>
                                <Field label="Nomor Sanksi" v-model="r.nomor_sanksi" :span="2" />
                                <Field label="Tanggal Pengenaan" v-model="r.tanggal_pengenaan_sanksi" type="date" :span="2" />
                                <Field label="Tenggat Waktu" v-model="r.tenggat_waktu_pemenuhan_kewajiban_tanggapan" type="date" :span="2" />
                                <Field label="Jenis Sanksi" v-model="r.jenis_sanksi" :span="2" />
                                <Field label="Masa Berlaku" v-model="r.masa_berlaku" :span="2" />
                                <Field label="Status Sanksi" v-model="r.status_sanksi" :span="2" />
                                <Field label="Sumber Sanksi" v-model="r.sumber_sanksi" :span="6" />
                            </FieldGroup>
                        </SectionCard>

                        <!-- Pelaku Usaha -->
                        <SectionCard title="Pelaku Usaha" color="blue">
                            <FieldGroup>
                                <Field label="Nama Pelaku Usaha *" v-model="r.nama_pelaku_usaha" :span="4" />
                                <Field label="NIB" v-model="r.nib" :span="2" mono />
                                <Field label="Jenis Penanaman Modal" v-model="r.jenis_penanaman_modal" :span="2" />
                                <Field label="Skala Usaha" v-model="r.skala_usaha" :span="2" />
                                <Field label="Sumber Data" v-model="r.sumber_data" :span="2" />
                            </FieldGroup>
                        </SectionCard>

                        <!-- Perizinan -->
                        <SectionCard title="Perizinan Berusaha" color="green">
                            <FieldGroup>
                                <Field label="Jenis Perizinan" v-model="r.jenis_perizinan" :span="2" />
                                <Field label="Nomor Perizinan" v-model="r.nomor_perizinan" :span="2" mono />
                                <Field label="Status" v-model="r.status_perizinan" :span="2" />
                                <Field label="Kementerian/Lembaga" v-model="r.kementerian_lembaga" :span="2" />
                                <Field label="Kewenangan" v-model="r.kewenangan" :span="2" />
                            </FieldGroup>
                        </SectionCard>

                        <!-- Lokasi -->
                        <SectionCard title="Lokasi Usaha" color="amber">
                            <FieldGroup>
                                <Field label="Alamat" v-model="r.alamat" :span="6" />
                                <Field label="Kelurahan" v-model="r.kelurahan" :span="1" />
                                <Field label="Kecamatan" v-model="r.kecamatan" :span="1" />
                                <Field label="Kab/Kota" v-model="r.kab_kota" :span="2" />
                                <Field label="Provinsi" v-model="r.provinsi" :span="2" />
                            </FieldGroup>
                        </SectionCard>

                        <!-- Data Usaha -->
                        <SectionCard title="Data Usaha" color="purple">
                            <FieldGroup>
                                <Field label="Nomor Kode Proyek" v-model="r.nomor_kode_proyek" :span="2" mono />
                                <div class="col-span-3 md:col-span-2">
                                    <label class="label">Luas Lahan</label>
                                    <div class="flex gap-2">
                                        <input type="number" step="0.01" v-model="r.luas_lahan" class="input" style="width: 65%;" placeholder="0">
                                        <input type="text" v-model="r.satuan_luas" class="input" style="width: 35%; text-align: center;" placeholder="M²">
                                    </div>
                                </div>
                                <Field label="Tenaga Kerja" v-model="r.jumlah_tenaga_kerja" type="number" :span="1" />
                                <div class="col-span-3 md:col-span-2">
                                    <label class="label">Rencana Investasi</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="text" v-model="r.rencana_investasi_display" @input="onRupiahInput(r)" class="input" style="padding-left: 2.5rem;" placeholder="0">
                                    </div>
                                </div>
                                <Field label="Risiko" v-model="r.tingkat_risiko" :span="1" />
                            </FieldGroup>
                        </SectionCard>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-700">{{ selectedCount }}</span> dari {{ results.length }} data terpilih untuk disimpan
                    </p>
                    <div class="flex items-center gap-2">
                        <a
                            href="/pengawasan/import/clear"
                            class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                        >
                            Buang Hasil
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="selectedCount === 0"
                        >
                            <Save :size="16" />
                            Simpan Data Terpilih ({{ selectedCount }})
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SectionCard from '@/Components/SectionCard.vue';
import FieldGroup from '@/Components/FieldGroup.vue';
import Field from '@/Components/Field.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import {
    Upload,
    FileUp,
    FileSearch,
    CheckCircle2,
    AlertTriangle,
    ChevronDown,
    PanelLeftOpen,
    PanelLeftClose,
    Loader2,
    Save,
} from '@lucide/vue';

const props = defineProps({
    results: { type: Array, default: () => [] },
    filename: { type: String, default: '' },
});

const fileInput = ref(null);
const uploading = ref(false);
const fileName = ref('');
const expanded = reactive({});
const allExpanded = ref(false);

function formatRupiah(val) {
    if (val === null || val === undefined || val === '') return '';
    const num = typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) : val;
    return isNaN(num) ? '' : num.toLocaleString('id-ID');
}

function initDisplayValues() {
    props.results?.forEach(r => {
        r.selected = true;
        r.rencana_investasi_display = formatRupiah(r.rencana_investasi);
    });
}

import { onMounted, watch } from 'vue';
onMounted(() => { initDisplayValues(); });
watch(() => props.results, () => { initDisplayValues(); });

const selectedCount = computed(() => {
    return props.results?.filter(r => r.selected)?.length || 0;
});

function hasSanksi(r) {
    return r.nomor_sanksi || r.tanggal_pengenaan_sanksi || r.jenis_sanksi;
}

function onFileChange(e) {
    if (!e.target.files.length) return;
    fileName.value = e.target.files[0].name;
    uploadFile();
}

function uploadFile() {
    if (!fileInput.value?.files?.length) return;
    uploading.value = true;
    const formData = new FormData();
    formData.append('file_pdf', fileInput.value.files[0]);
    router.post('/pengawasan/import', formData, {
        forceFormData: true,
        onFinish: () => { uploading.value = false; },
    });
}

function toggleSection(i) {
    expanded[i] = !expanded[i];
}

function toggleAll() {
    allExpanded.value = !allExpanded.value;
    props.results?.forEach((_, i) => {
        expanded[i] = allExpanded.value;
    });
}

function onRupiahInput(r) {
    let val = (r.rencana_investasi_display || '').replace(/[^0-9]/g, '');
    if (val) {
        r.rencana_investasi = parseInt(val);
        r.rencana_investasi_display = parseInt(val).toLocaleString('id-ID');
    } else {
        r.rencana_investasi = null;
        r.rencana_investasi_display = '';
    }
}

function simpanData() {
    const data = props.results
        .filter(r => r.selected && r.nama_pelaku_usaha)
        .map(r => {
            const item = { ...r };
            delete item.selected;
            delete item.rencana_investasi_display;
            return item;
        });
    router.post('/pengawasan/import/simpan', { data }, {
        onStart: () => { uploading.value = true; },
        onFinish: () => { uploading.value = false; },
    });
}
</script>
