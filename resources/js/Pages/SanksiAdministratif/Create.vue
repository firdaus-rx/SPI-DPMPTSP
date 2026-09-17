<template>
    <AdminLayout title="Tambah Usulan Pencabutan">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <Link href="/sanksi-administratif" class="p-2 rounded-lg hover:bg-gray-100 transition-colors"><ArrowLeft :size="20" class="text-gray-500" /></Link>
                <div><h1 class="text-2xl font-bold text-gray-900">Tambah Usulan Pencabutan</h1><p class="text-sm text-gray-500 mt-0.5">Sanksi Administratif Usulan Pencabutan Perizinan Berusaha</p></div>
            </div>
        </div>
        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-primary-50/50 border-b border-primary-100/50"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center"><Building2 :size="16" class="text-primary-600" /></div><h3 class="font-semibold text-gray-900">Pelaku Usaha</h3></div></div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div><label class="label">No (urut OCR)</label><input v-model="form.no" type="number" min="1" class="input" placeholder="1"></div>
                        <div class="sm:col-span-2"><label class="label">Nama Pelaku Usaha <span class="text-red-500">*</span></label><input v-model="form.nama_pelaku_usaha" type="text" class="input" required placeholder="BUMG LEUNGGOH JAYA"><span v-if="form.errors.nama_pelaku_usaha" class="text-red-500 text-xs mt-1">{{ form.errors.nama_pelaku_usaha }}</span></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div><label class="label">NIB</label><input v-model="form.nib" type="text" class="input font-mono" placeholder="9120300950831"><p class="text-[11px] text-gray-400 mt-1">String, 0 depan tidak hilang</p></div>
                        <div><label class="label">Penanaman Modal</label><input v-model="form.jenis_penanaman_modal" type="text" class="input" placeholder="Penanaman Modal Dalam Negeri (PMDN)"></div>
                    </div>
                    <div><label class="label">Skala Usaha</label><input v-model="form.skala_usaha" type="text" class="input" placeholder="Usaha Mikro"></div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-100/50"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center"><MapPin :size="16" class="text-amber-600" /></div><h3 class="font-semibold text-gray-900">Lokasi Usaha</h3></div></div>
                <div class="p-6 space-y-5">
                    <div><label class="label">Jalan / Alamat</label><textarea v-model="form.alamat" rows="2" class="input" placeholder="Gampong Tuha Gogo"></textarea></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div><label class="label">Kelurahan / Desa</label><input v-model="form.kelurahan" type="text" class="input"></div>
                        <div><label class="label">Kecamatan</label><input v-model="form.kecamatan" type="text" class="input"></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div><label class="label">Kabupaten / Kota</label><input v-model="form.kab_kota" type="text" class="input"></div>
                        <div><label class="label">Provinsi</label><input v-model="form.provinsi" type="text" class="input"></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pb-6">
                <Link href="/sanksi-administratif" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50">Batal</Link>
                <button type="submit" class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 shadow-sm disabled:opacity-50" :disabled="form.processing"><Save :size="16" v-if="!form.processing" /><Loader2 :size="16" v-else class="animate-spin" />{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
        </form>
    </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2, Building2, MapPin } from '@lucide/vue';
const form = useForm({ no: '', nama_pelaku_usaha: '', nib: '', jenis_penanaman_modal: '', skala_usaha: '', alamat: '', kelurahan: '', kecamatan: '', kab_kota: '', provinsi: '' });
function submit(){ form.post('/sanksi-administratif'); }
</script>
