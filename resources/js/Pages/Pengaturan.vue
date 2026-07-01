<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    users: Array,
    categories: Array,
});

// Toast state
const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;

const triggerToast = (message) => {
    toastMessage.value = message;
    showToast.value = true;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.visit('/');
    }, 800);
};

const activeTab = ref('profil-toko');

</script>

<template>
    <Head title="Settings - Toko Material POS" />

    <div class="bg-background text-on-background font-body-md overflow-hidden h-screen flex">
        
        <!-- Toast Notification -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showToast" class="fixed top-4 right-4 z-50 max-w-sm bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded border border-outline flex items-center gap-3">
                <span class="material-symbols-outlined text-primary-fixed">info</span>
                <span class="text-sm font-semibold">{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- SIDE NAVBAR (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0 z-30">
            <div class="px-4 py-6">
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Material POS</h1>
            </div>
            
            <div class="flex flex-col gap-1 flex-1">
                <!-- Dashboard Tab -->
                <Link 
                    href="/dashboard"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </Link>

                <!-- Inventory Tab -->
                <Link 
                    href="/inventaris"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Inventaris</span>
                </Link>

                <!-- Sales Tab -->
                <Link 
                    href="/penjualan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">point_of_sale</span>
                    <span>Penjualan</span>
                </Link>

                <!-- Reports Tab -->
                <Link 
                    href="/laporan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Laporan</span>
                </Link>

                <!-- Settings Tab (Active) -->
                <Link 
                    href="/pengaturan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">settings</span>
                    <span>Pengaturan</span>
                </Link>
            </div>

            <!-- Profile & New Transaction Area -->
            <div class="mt-auto border-t border-outline-variant pt-4 pb-2 px-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-secondary text-on-secondary flex items-center justify-center font-bold">A</div>
                        <div>
                            <p class="text-label-md font-label-md leading-none">{{ props.auth?.user?.name }}</p>
                            <p class="text-xs text-secondary mt-1">{{ props.auth?.user?.role === 'owner' ? 'Owner' : 'Karyawan' }}</p>
                        </div>
                    </div>
                    <!-- Logout button on desktop -->
                    <button @click="handleLogout" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Keluar dari sistem">
                        logout
                    </button>
                </div>
                <button 
                    @click="router.visit('/kasir')" 
                    class="w-full bg-primary text-on-primary font-bold min-h-[48px] rounded hover:brightness-90 active:translate-y-[1px] transition-all cursor-pointer"
                >
                    Transaksi Baru
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col min-w-0 h-screen bg-background overflow-hidden">
            <!-- TopNavBar Component -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Pengaturan Sistem</span>
                </div>
            </header>

            <!-- SETTINGS LAYOUT CONTAINER -->
            <div class="flex-1 flex overflow-hidden">
                <!-- SETTINGS CATEGORIES (Vertical Tabs) -->
                <nav class="w-72 bg-surface-container-low border-r border-outline-variant overflow-y-auto">
                    <div class="p-4">
                        <p class="text-[12px] font-bold text-secondary uppercase tracking-wider mb-4 px-2">Kategori Utama</p>
                        <div class="space-y-1">
                            <button @click="activeTab = 'profil-toko'" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'profil-toko' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">store</span>
                                <span class="font-label-md text-label-md">Profil Toko</span>
                            </button>
                            <button @click="activeTab = 'manajemen-user'" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'manajemen-user' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">group</span>
                                <span class="font-label-md text-label-md">Manajemen User</span>
                            </button>
                            <button @click="activeTab = 'konfigurasi-printer'" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'konfigurasi-printer' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">print</span>
                                <span class="font-label-md text-label-md">Printer &amp; Struk</span>
                            </button>
                            <button @click="activeTab = 'pembayaran-pajak'" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'pembayaran-pajak' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">payments</span>
                                <span class="font-label-md text-label-md">Pembayaran &amp; Pajak</span>
                            </button>
                            <button @click="activeTab = 'backup-data'" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'backup-data' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">database</span>
                                <span class="font-label-md text-label-md">Backup &amp; Data</span>
                            </button>
                        </div>
                    </div>
                </nav>

                <!-- FORM CONTENT AREA -->
                <div class="flex-1 overflow-y-auto p-margin-desktop bg-background">
                    <div class="max-w-4xl mx-auto pb-24 md:pb-8">
                        
                        <!-- 1. Profil Toko -->
                        <section v-if="activeTab === 'profil-toko'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Informasi Profil Toko</h3>
                                <p class="text-body-md text-secondary">Kelola identitas publik toko Anda pada struk dan platform.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-label-md font-label-md text-on-surface mb-2">Nama Toko</label>
                                        <input class="w-full px-4 py-3 bg-surface border-2 border-outline-variant rounded-lg font-body-md focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" placeholder="Masukkan nama toko" type="text" value="Toko Bangunan Sumber Makmur">
                                    </div>
                                    <div>
                                        <label class="block text-label-md font-label-md text-on-surface mb-2">Kontak Telepon</label>
                                        <input class="w-full px-4 py-3 bg-surface border-2 border-outline-variant rounded-lg font-body-md focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" placeholder="Contoh: 0812..." type="text" value="+62 21 8899 7766">
                                    </div>
                                    <div>
                                        <label class="block text-label-md font-label-md text-on-surface mb-2">Alamat Lengkap</label>
                                        <textarea class="w-full px-4 py-3 bg-surface border-2 border-outline-variant rounded-lg font-body-md focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" rows="4">Jl. Industri No. 45, Kawasan Pergudangan, Jakarta Barat, 11820</textarea>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center justify-start border-2 border-dashed border-outline-variant rounded-xl p-gutter bg-surface-container-lowest">
                                    <label class="block text-label-md font-label-md text-on-surface mb-4 w-full">Logo Toko</label>
                                    <div class="w-48 h-48 bg-surface-container rounded-lg flex flex-col items-center justify-center border border-outline text-secondary cursor-pointer hover:bg-surface-container-high transition-colors overflow-hidden">
                                        <img class="w-full h-full object-contain p-4" data-alt="A bold, professional logo for a construction supply company called 'Sumber Makmur'." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrcUTF6zkqK50EhdCfJk-uVTig5jsJU38JzTTTu0Ooq3jwQpKkXZf4STDy-uJKcQDs9s97dGh0hWStzJSxMjUtfSWtCFc5yWhqwV3cLVXBexhiUylDa2vP4fCB-mHG1vXMCIMI021CFT_vYzStPkbbC3Yk7Z_7WsQAJ-CPnHpJkrLiuOLWt1JHmrj4ooQbU34GcCi09gKZaBDfkZC-c-cMhxWOfgyEp1RDAQ4KxZagAoaRno_JM6QugvDcMdPVGGbnbieheXhlhIh2">
                                    </div>
                                    <button class="mt-4 px-6 py-2 border-2 border-primary text-primary font-bold rounded-lg hover:bg-primary-fixed transition-colors">Ubah Logo</button>
                                </div>
                            </div>
                            <div class="flex justify-end pt-gutter">
                                <button class="btn-primary-industrial font-bold px-8 py-3 rounded-lg shadow-sm" @click="triggerToast('Profil tersimpan')">Simpan Profil</button>
                            </div>
                        </section>

                        <!-- 2. Manajemen User -->
                        <section v-if="activeTab === 'manajemen-user'" class="space-y-gutter">
                            <div class="flex justify-between items-end border-b border-outline-variant pb-base mb-base">
                                <div>
                                    <h3 class="text-label-xl font-label-xl text-on-surface">Manajemen User &amp; Akses</h3>
                                    <p class="text-body-md text-secondary">Kelola daftar staf dan hak akses sistem.</p>
                                </div>
                                <button class="btn-primary-industrial font-bold px-6 py-2 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">person_add</span>
                                    <span>Tambah User</span>
                                </button>
                            </div>
                            <div class="bg-surface border-2 border-outline-variant rounded-xl overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-surface-container-high border-b border-outline-variant">
                                        <tr>
                                            <th class="px-6 py-4 text-label-md font-label-md">Nama Staf</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Role / Jabatan</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Status</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <tr v-if="!props.users || props.users.length === 0">
                                            <td colspan="4" class="px-6 py-8 text-center text-secondary">Belum ada user terdaftar.</td>
                                        </tr>
                                        <tr v-for="(user, index) in props.users" :key="user.id" :class="['hover:bg-surface-container-low transition-colors', index % 2 !== 0 ? 'bg-surface-container-lowest' : '']">
                                            <td class="px-6 py-5">
                                                <div class="font-bold">{{ user.name }}</div>
                                                <div class="text-[12px] text-secondary">@{{ user.username }}</div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span :class="[
                                                    'px-3 py-1 rounded-full text-[12px] font-bold',
                                                    user.role === 'owner' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-secondary-fixed text-on-secondary-fixed-variant'
                                                ]">{{ user.role === 'owner' ? 'Owner / Admin' : 'Karyawan' }}</span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span :class="['flex items-center space-x-1', user.is_active ? 'text-green-600' : 'text-error']">
                                                    <span :class="['w-2 h-2 rounded-full', user.is_active ? 'bg-green-600' : 'bg-error']"></span> 
                                                    <span>{{ user.is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="flex items-center space-x-4">
                                                    <button class="material-symbols-outlined text-secondary hover:text-primary transition-colors cursor-pointer" title="Edit">edit</button>
                                                    <button class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Hapus">delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- 3. Konfigurasi Printer -->
                        <section v-if="activeTab === 'konfigurasi-printer'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Konfigurasi Printer &amp; Struk</h3>
                                <p class="text-body-md text-secondary">Atur perangkat pencetak dan tampilan struk pembayaran.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
                                <div class="md:col-span-2 space-y-6">
                                    <div class="bg-surface p-gutter border-2 border-outline-variant rounded-xl space-y-4">
                                        <h4 class="font-bold text-primary flex items-center space-x-2">
                                            <span class="material-symbols-outlined">receipt</span>
                                            <span>Teks Struk Belanja</span>
                                        </h4>
                                        <div>
                                            <label class="block text-[12px] font-bold text-secondary uppercase mb-2">Header Struk (Atas)</label>
                                            <textarea class="w-full px-4 py-2 bg-background border-2 border-outline-variant rounded-lg text-body-md focus:border-primary-container outline-none" placeholder="Pesan pembuka struk" rows="2">Selamat Datang di Sumber Makmur!
Solusi Bahan Bangunan Anda</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[12px] font-bold text-secondary uppercase mb-2">Footer Struk (Bawah)</label>
                                            <textarea class="w-full px-4 py-2 bg-background border-2 border-outline-variant rounded-lg text-body-md focus:border-primary-container outline-none" placeholder="Pesan penutup struk" rows="3">Barang yang sudah dibeli tidak dapat ditukar.
Terima kasih atas kunjungan Anda!</textarea>
                                        </div>
                                    </div>
                                    <div class="bg-surface p-gutter border-2 border-outline-variant rounded-xl space-y-4">
                                        <h4 class="font-bold text-primary flex items-center space-x-2">
                                            <span class="material-symbols-outlined">print_connect</span>
                                            <span>Pilih Printer Aktif</span>
                                        </h4>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between p-3 bg-secondary-container border border-outline rounded-lg">
                                                <div class="flex items-center space-x-3">
                                                    <span class="material-symbols-outlined text-on-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                                    <div>
                                                        <p class="font-bold text-on-secondary-container">Epson TM-T88VI (Thermal)</p>
                                                        <p class="text-[12px] text-secondary">USB Connected • 80mm</p>
                                                    </div>
                                                </div>
                                                <button class="text-primary font-bold text-label-md hover:underline cursor-pointer">Test Print</button>
                                            </div>
                                            <div class="flex items-center justify-between p-3 border border-outline-variant rounded-lg hover:bg-surface-container transition-colors cursor-pointer">
                                                <div class="flex items-center space-x-3">
                                                    <span class="material-symbols-outlined text-secondary">radio_button_unchecked</span>
                                                    <div>
                                                        <p class="font-bold text-on-surface">Star Micronics mPOP</p>
                                                        <p class="text-[12px] text-secondary">Bluetooth • 58mm</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-surface-container-high p-4 rounded-xl border border-outline-variant h-fit">
                                    <p class="text-[12px] font-bold text-secondary uppercase mb-4 text-center">Preview Struk</p>
                                    <div class="bg-white shadow-md p-4 text-[10px] font-mono text-black space-y-2 w-full">
                                        <div class="text-center border-b border-dashed border-gray-300 pb-2">
                                            <p class="font-bold">TOKO SUMBER MAKMUR</p>
                                            <p>Selamat Datang di Sumber Makmur!</p>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex justify-between"><span>Semen Tiga Roda</span><span>55.000</span></div>
                                            <div class="flex justify-between"><span>Paku Beton 5cm</span><span>12.500</span></div>
                                        </div>
                                        <div class="border-t border-dashed border-gray-300 pt-2 font-bold">
                                            <div class="flex justify-between text-[12px]"><span>TOTAL</span><span>67.500</span></div>
                                        </div>
                                        <div class="text-center pt-2 italic">
                                            <p>Barang yang sudah dibeli tidak dapat ditukar.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 4. Pembayaran & Pajak -->
                        <section v-if="activeTab === 'pembayaran-pajak'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Metode Pembayaran &amp; Pajak</h3>
                                <p class="text-body-md text-secondary">Konfigurasi opsi pembayaran kasir dan persentase pajak.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                                <div class="bg-surface p-gutter border-2 border-outline-variant rounded-xl space-y-6">
                                    <h4 class="font-bold text-primary">Metode Pembayaran</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span class="material-symbols-outlined text-on-surface">payments</span>
                                                <span class="font-bold">Tunai (Cash)</span>
                                            </div>
                                            <div class="w-12 h-6 bg-primary-container rounded-full relative cursor-pointer">
                                                <div class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span class="material-symbols-outlined text-on-surface">qr_code_2</span>
                                                <span class="font-bold">QRIS Dinamis</span>
                                            </div>
                                            <div class="w-12 h-6 bg-primary-container rounded-full relative cursor-pointer">
                                                <div class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span class="material-symbols-outlined text-on-surface">credit_card</span>
                                                <span class="font-bold">Kartu Debit/Kredit</span>
                                            </div>
                                            <div class="w-12 h-6 bg-surface-variant rounded-full relative cursor-pointer">
                                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-surface p-gutter border-2 border-outline-variant rounded-xl space-y-6">
                                    <h4 class="font-bold text-primary">Pajak &amp; Biaya</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-label-md font-label-md text-on-surface mb-2">Persentase PPN (%)</label>
                                            <div class="relative">
                                                <input class="w-full px-4 py-3 bg-background border-2 border-outline-variant rounded-lg font-display-price text-primary focus:border-primary-container outline-none" type="number" value="11">
                                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-headline-md font-bold text-secondary">%</span>
                                            </div>
                                            <p class="mt-2 text-[12px] text-secondary">Akan otomatis ditambahkan ke setiap transaksi baru.</p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <input checked class="w-5 h-5 text-primary-container border-outline-variant rounded" id="tax-inclusive" type="checkbox">
                                            <label class="text-label-md text-on-surface cursor-pointer" for="tax-inclusive">Harga Produk Sudah Termasuk Pajak</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 5. Backup & Data -->
                        <section v-if="activeTab === 'backup-data'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Keamanan &amp; Backup Data</h3>
                                <p class="text-body-md text-secondary">Ekspor data transaksi atau lakukan pembersihan database berkala.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-gutter">
                                <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-gutter flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-16 h-16 bg-tertiary-container text-white rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[32px]">cloud_upload</span>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-headline-md">Ekspor Database</h4>
                                            <p class="text-body-md text-secondary">Download seluruh data inventaris dan transaksi ke format .xlsx atau .csv</p>
                                        </div>
                                    </div>
                                    <button class="btn-primary-industrial font-bold px-8 py-3 rounded-lg flex items-center space-x-2">
                                        <span class="material-symbols-outlined">download</span>
                                        <span>Ekspor Sekarang</span>
                                    </button>
                                </div>
                                <div class="bg-error-container border-2 border-error rounded-xl p-gutter flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-16 h-16 bg-error text-white rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[32px]">warning</span>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-headline-md text-on-error-container">Reset Semua Transaksi</h4>
                                            <p class="text-body-md text-on-error-container">Menghapus seluruh riwayat penjualan. Tindakan ini tidak dapat dibatalkan.</p>
                                        </div>
                                    </div>
                                    <button class="bg-error text-white font-bold px-8 py-3 rounded-lg hover:bg-red-700 transition-colors cursor-pointer">Reset Data</button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
            
            <!-- BottomNavBar Component (Mobile Only) -->
            <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-16 px-4 bg-surface border-t-2 border-outline-variant shadow-lg md:hidden z-50">
                <button class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-[10px] font-bold">Home</span>
                </button>
                <button class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                    <span class="material-symbols-outlined">apps</span>
                    <span class="text-[10px] font-bold">Inventory</span>
                </button>
                <button class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                    <span class="material-symbols-outlined">add_shopping_cart</span>
                    <span class="text-[10px] font-bold">POS</span>
                </button>
                <button class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1 cursor-pointer">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="text-[10px] font-bold">Settings</span>
                </button>
            </nav>
        </main>
    </div>
</template>

<style scoped>
.active-tab {
    background-color: var(--color-secondary-container, #d5e0f8);
    color: var(--color-on-secondary-container, #586377);
    border-left: 4px solid var(--color-primary, #9e4300);
}

.btn-primary-industrial {
    background-color: var(--color-primary-container, #ee6c12);
    color: var(--color-on-primary-container, #4d1d00);
    transition: all 100ms;
}
.btn-primary-industrial:hover {
    filter: brightness(90%);
}
.btn-primary-industrial:active {
    transform: translateY(1px);
}
</style>
