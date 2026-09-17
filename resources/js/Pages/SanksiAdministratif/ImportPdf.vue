<template>
    <AdminLayout title="Impor Usulan Pencabutan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Impor Sanksi Administratif Usulan Pencabutan Perizinan Berusaha</h1>
                <p class="text-sm text-gray-500 mt-1">Unggah dokumen usulan pencabutan untuk ekstraksi data pelaku usaha secara otomatis</p>
            </div>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0"><CheckCircle2 :size="16" class="text-green-600" /></div>
            <p class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 bg-purple-50/50 border-b border-purple-100/50">
                <div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center"><Upload :size="16" class="text-purple-600" /></div><h3 class="font-semibold text-gray-900">Unggah Dokumen</h3></div>
                <p class="text-xs text-gray-500 mt-2">Format yang didukung: PDF, gambar (JPG, PNG, TIFF), atau JSON hasil OCR</p>
            </div>
            <div class="p-6">
                <form @submit.prevent="uploadFile">
                    <label class="relative flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-xl cursor-pointer transition-all" :class="uploading ? 'border-primary-300 bg-primary-50/50' : 'border-gray-200 hover:border-primary-400 hover:bg-primary-50/30'">
                        <input type="file" ref="fileInput" accept=".pdf,.jpg,.jpeg,.png,.bmp,.tiff,.webp,.json" class="hidden" @change="onFileChange" :disabled="uploading">
                        <template v-if="!uploading">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3"><FileUp :size="28" class="text-gray-400" /></div>
                            <p class="text-sm font-medium text-gray-600">{{ fileName || 'Seret dan lepas berkas di sini, atau klik untuk memilih' }}</p>
                            <p class="text-xs text-gray-400 mt-1">Ukuran maksimum 20 MB per berkas</p>
                        </template>
                        <template v-else>
                            <div class="w-14 h-14 rounded-2xl bg-primary-100 flex items-center justify-center mb-3"><Loader2 :size="28" class="text-primary-500 animate-spin" /></div>
                            <p class="text-sm font-medium text-primary-600">Memproses dokumen...</p>
                            <p class="text-xs text-gray-400 mt-1">Mohon tunggu, ekstraksi data sedang berlangsung</p>
                        </template>
                    </label>
                </form>
            </div>
        </div>

        <div v-if="localResults && localResults.length === 0 && props.results && props.results.length === 0" class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <div class="flex items-center gap-4 p-4 bg-amber-50 rounded-xl">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0"><AlertTriangle :size="20" class="text-amber-600" /></div>
                <div><p class="font-medium text-amber-800">Data Tidak Ditemukan</p><p class="text-sm text-amber-600 mt-0.5">Dokumen berhasil diproses, namun tidak ada data yang dapat diekstrak. Periksa kualitas dokumen atau lakukan entri manual.</p></div>
            </div>
        </div>

        <div v-if="localResults && localResults.length > 0" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center"><FileSearch :size="16" class="text-primary-600" /></div>
                    <div><h3 class="font-semibold text-gray-900">Tinjau Hasil Ekstraksi</h3><p class="text-sm text-gray-500">{{ localResults.length }} data berhasil dikenali — verifikasi kembali sebelum menyimpan</p></div>
                </div>
                <button @click="toggleAll" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 hover:bg-white"><component :is="allExpanded ? PanelLeftClose : PanelLeftOpen" :size="16" />{{ allExpanded ? 'Ciutkan Semua' : 'Perluas Semua' }}</button>
            </div>

            <form @submit.prevent="simpanData" novalidate>
                <div v-for="(r, i) in localResults" :key="i" class="border-b border-gray-100 last:border-b-0">
                    <div class="flex items-center gap-4 px-6 py-4 cursor-pointer select-none hover:bg-gray-50/50" @click="toggleSection(i)">
                        <input type="checkbox" v-model="r.selected" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" @click.stop>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate"><span class="text-gray-400 font-normal">#{{ r.no ?? i+1 }} · </span>{{ r.nama_pelaku_usaha || 'Nama belum terdeteksi' }}</p>
                            <p class="text-sm text-gray-400 mt-0.5">NIB {{ r.nib || '—' }} · {{ r.kab_kota || 'Lokasi belum terdeteksi' }}{{ r.kecamatan ? ' — ' + r.kecamatan : '' }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-600">{{ r.sumber_metode || 'OCR' }}</span>
                            <ChevronDown :size="18" class="text-gray-400 transition-transform" :class="{ 'rotate-180': expanded[i] }" />
                        </div>
                    </div>

                    <div v-show="expanded[i]" class="px-6 pb-6 space-y-5">
                        <SectionCard title="Pelaku Usaha" color="blue">
                            <FieldGroup>
                                <div class="col-span-1"><label class="label">No. Urut</label><input v-model="r.no" type="number" class="input" placeholder="-"></div>
                                <Field label="Nama Pelaku Usaha *" v-model="r.nama_pelaku_usaha" :span="5" />
                                <Field label="Nomor Induk Berusaha (NIB)" v-model="r.nib" :span="3" mono />
                                <Field label="Jenis Penanaman Modal" v-model="r.jenis_penanaman_modal" :span="3" />
                                <Field label="Skala Usaha" v-model="r.skala_usaha" :span="3" />
                            </FieldGroup>
                        </SectionCard>
                        <SectionCard title="Lokasi Usaha" color="amber">
                            <FieldGroup>
                                <Field label="Jalan / Alamat" v-model="r.alamat" :span="6" />
                                <Field label="Kelurahan / Desa" v-model="r.kelurahan" :span="3" />
                                <Field label="Kecamatan" v-model="r.kecamatan" :span="3" />
                                <Field label="Kabupaten / Kota" v-model="r.kab_kota" :span="3" />
                                <Field label="Provinsi" v-model="r.provinsi" :span="3" />
                            </FieldGroup>
                        </SectionCard>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-700">{{ savableCount }}</span> dari {{ localResults.length }} data siap disimpan
                        <span v-if="selectedCount !== savableCount" class="text-amber-600">· {{ selectedCount - savableCount }} tanpa nama akan diabaikan</span>
                        <span class="hidden sm:inline"> · duplikasi NIB akan diperbarui</span>
                    </p>
                    <div class="flex items-center gap-2">
                        <a href="/sanksi-administratif/import/clear" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50">Buang Hasil</a>
                        <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-emerald-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" :disabled="savableCount === 0 || uploading"><Save :size="16" />Simpan Data Terpilih ({{ savableCount }})</button>
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
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { Upload, FileUp, FileSearch, CheckCircle2, AlertTriangle, ChevronDown, PanelLeftOpen, PanelLeftClose, Loader2, Save } from '@lucide/vue';
const props = defineProps({ results: { type: Array, default: () => [] }, filename: { type: String, default: '' } });
const fileInput = ref(null); const uploading = ref(false); const fileName = ref(''); const expanded = reactive({}); const allExpanded = ref(false);

// Salin props ke state lokal agar checkbox reaktif — mutasi props langsung tidak memicu computed di Inertia
const localResults = ref([]);
function syncResults(){
    localResults.value = (props.results || []).map(r => ({ ...r, selected: r.selected ?? true }));
    // buka section pertama otomatis agar user lihat field
    if (localResults.value.length > 0 && Object.keys(expanded).length === 0) {
        localResults.value.forEach((_, i) => { expanded[i] = i === 0; });
        allExpanded.value = false;
    }
}
onMounted(syncResults);
watch(() => props.results, syncResults, { deep: true });

const selectedCount = computed(() => localResults.value.filter(r => r.selected).length);
const savableCount = computed(() => localResults.value.filter(r => r.selected && r.nama_pelaku_usaha).length);

function onFileChange(e){ if(!e.target.files.length) return; fileName.value=e.target.files[0].name; uploadFile(); }
function uploadFile(){ if(!fileInput.value?.files?.length) return; uploading.value=true; const fd=new FormData(); fd.append('file_pdf', fileInput.value.files[0]); router.post('/sanksi-administratif/import', fd, { forceFormData:true, onFinish:() => uploading.value=false }); }
function toggleSection(i){ expanded[i]=!expanded[i]; }
function toggleAll(){ allExpanded.value=!allExpanded.value; localResults.value.forEach((_,i)=> expanded[i]=allExpanded.value); }
function simpanData(){
    const data = localResults.value.filter(r=>r.selected && r.nama_pelaku_usaha).map(r=>{ const c={...r}; delete c.selected; return c; });
    if (data.length === 0) {
        alert('Pilih minimal satu data dengan Nama Pelaku Usaha terisi.');
        return;
    }
    router.post('/sanksi-administratif/import/simpan', { data }, { onStart:()=> uploading.value=true, onFinish:()=> uploading.value=false });
}
</script>
