<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    products: Array,
    categories: Array,
    filters: Object,
});

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);

const openAddItemModal = () => {
    isAddModalOpen.value = true;
};

const closeAddItemModal = () => {
    isAddModalOpen.value = false;
};

const openEditModal = () => {
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
};

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

// Logout handler
const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.visit('/');
    }, 800);
};

// Navigation menu helpers
const handleNavAction = (menuName) => {
    triggerToast(`Menu ${menuName} sedang dalam pengembangan.`);
};
</script>

<template>
    <Head title="Toko Material POS - Daftar Inventori" />

    <div class="fixed inset-0 bg-background text-on-background flex flex-col md:flex-row overflow-hidden w-full h-full font-sans">
        
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

                <!-- Inventory Tab (Active) -->
                <Link 
                    href="/inventaris"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
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

                <!-- Settings Tab -->
                <Link 
                    href="/pengaturan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
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
        <main class="flex-1 flex flex-col min-w-0 bg-background overflow-y-auto pb-20 md:pb-0" :style="{ overflow: (isAddModalOpen || isEditModalOpen) ? 'hidden' : 'auto' }">
            <!-- TOP NAVBAR -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Daftar Inventori</span>
                </div>
            </header>

            <!-- CONTENT BODY -->
            <div class="p-margin-desktop space-y-6">
                <!-- ACTIONS BAR -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex gap-2">
                        <button class="px-4 py-2 border-2 border-outline-variant text-secondary font-bold flex items-center gap-2 hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined">filter_list</span> Filter
                        </button>
                        <button class="px-4 py-2 border-2 border-outline-variant text-secondary font-bold flex items-center gap-2 hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined">download</span> Export
                        </button>
                    </div>
                    
                    <button class="bg-[#ee6c12] text-white px-6 py-3 font-bold flex items-center gap-2 min-h-[48px] hover:brightness-110 active:translate-y-px transition-all" @click="openAddItemModal">
                        <span class="material-symbols-outlined">add_box</span> Tambah Barang
                    </button>
                </div>

                <!-- INVENTORY TABLE -->
                <div class="bg-white border-2 border-outline-variant overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-surface-container-low border-b-2 border-outline-variant">
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider">Produk</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider">Detail</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider text-right">Stok</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider text-right">Harga Jual</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider text-center">Lokasi</th>
                                    <th class="px-6 py-4 text-label-md font-label-md text-secondary uppercase tracking-wider text-center">edit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                <tr v-if="props.products && props.products.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-secondary">Belum ada barang di inventaris.</td>
                                </tr>
                                <tr v-for="(product, index) in props.products" :key="product.id" :class="[
                                    'hover:bg-surface-container-low transition-colors align-middle',
                                    index % 2 !== 0 ? 'bg-surface-container-lowest' : ''
                                ]">
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-lg border border-outline-variant overflow-hidden bg-white shadow-sm flex items-center justify-center">
                                            <img v-if="product.photo_url" :alt="product.name" class="w-full h-full object-cover" :src="product.photo_url">
                                            <span v-else class="material-symbols-outlined text-outline">inventory_2</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">{{ product.name }}</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">SKU-{{ product.id.toString().padStart(4, '0') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">{{ product.category }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span :class="['font-bold', product.is_low_stock ? 'text-error' : 'text-on-surface']">{{ product.stock_qty_base_unit }}</span>
                                            <span :class="['text-[10px] font-bold uppercase', product.is_low_stock ? 'text-error' : 'text-secondary']">{{ product.is_low_stock ? 'Low Stock' : product.base_unit }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-on-surface">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(product.selling_price_per_base_unit) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">{{ product.location || '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button class="bg-primary-container/10 text-primary hover:bg-primary hover:text-white transition-all p-2 rounded-lg flex items-center justify-center mx-auto" @click="openEditModal" title="Update Stock &amp; Price">
                                            <span class="material-symbols-outlined">edit_square</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                    <div class="bg-white border-2 border-outline-variant p-6 flex items-center gap-4">
                        <div class="bg-primary-container/10 p-3 text-primary">
                            <span class="material-symbols-outlined">inventory</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Total SKU</p>
                            <p class="text-headline-md font-bold">1,248</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-6 flex items-center gap-4">
                        <div class="bg-tertiary-container/10 p-3 text-tertiary">
                            <span class="material-symbols-outlined">trending_up</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Stok Rendah</p>
                            <p class="text-headline-md font-bold text-error">12</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-6 flex items-center gap-4">
                        <div class="bg-secondary-container/10 p-3 text-secondary">
                            <span class="material-symbols-outlined">category</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Kategori</p>
                            <p class="text-headline-md font-bold">24</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-6 flex items-center gap-4">
                        <div class="bg-primary-container/10 p-3 text-primary">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Valuasi Stok</p>
                            <p class="text-headline-md font-bold">Rp 1.2B</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD ITEM MODAL OVERLAY -->
    <div v-if="isAddModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" @click.self="closeAddItemModal">
        <div class="bg-surface w-full max-w-2xl border-2 border-outline shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Modal Header -->
            <div class="bg-surface-container-high px-margin-desktop py-4 border-b-2 border-outline-variant flex justify-between items-center">
                <h3 class="text-headline-md font-headline-md font-bold text-on-surface">Tambah Barang Baru</h3>
                <button class="text-secondary hover:text-error transition-colors" @click="closeAddItemModal">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-margin-desktop">
                <form class="grid grid-cols-1 md:grid-cols-2 gap-6" @submit.prevent="closeAddItemModal">
                    <div class="md:col-span-2">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Nama Barang</label>
                        <input class="w-full p-3 border-2 border-outline-variant bg-white focus:ring-0 text-body-md" placeholder="Contoh: Semen Tiga Roda 40kg" type="text">
                    </div>
                    
                    <div>
                        <label class="block text-label-md font-label-md text-secondary mb-2">Harga Jual (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary font-bold">Rp</span>
                            <input class="w-full p-3 pl-10 border-2 border-outline-variant bg-white focus:ring-0 text-body-md" placeholder="0" type="number">
                        </div>
                    </div>
                    <div>
                        <label class="block text-label-md font-label-md text-secondary mb-2">Harga Beli (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary font-bold">Rp</span>
                            <input class="w-full p-3 pl-10 border-2 border-outline-variant bg-white focus:ring-0 text-body-md" placeholder="0" type="number">
                        </div>
                    </div>
                    
                    <div class="">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Kategori</label>
                        <select class="w-full p-3 border-2 border-outline-variant bg-white focus:ring-0 text-body-md">
                            <option>Pilih Kategori</option>
                            <option>Pipa</option>
                            <option>Cat</option>
                            <option>Semen</option>
                            <option>Lumber</option>
                            <option>Hardware</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-label-md font-label-md text-secondary mb-2">Kode Rak / Lokasi</label>
                        <input class="w-full p-3 border-2 border-outline-variant bg-white font-mono focus:ring-0 text-body-md uppercase" placeholder="RAK-X0" type="text">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-label-md font-label-md text-secondary mb-2">Stok Awal</label>
                            <input class="w-full p-3 border-2 border-outline-variant bg-white focus:ring-0 text-body-md" placeholder="0" type="number">
                        </div>
                        <div>
                            <label class="block text-label-md font-label-md text-secondary mb-2">Satuan</label>
                            <input class="w-full p-3 border-2 border-outline-variant bg-white focus:ring-0 text-body-md" placeholder="Sak/Pcs" type="text">
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Foto Produk</label>
                        <div class="border-2 border-dashed border-outline-variant bg-surface-container-low p-8 text-center cursor-pointer hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-4xl text-secondary mb-2">add_a_photo</span>
                            <p class="text-label-md text-secondary">Upload Foto Produk</p>
                            <p class="text-[10px] text-outline mt-1 uppercase tracking-tighter">Foto ini akan ditampilkan di daftar inventori utama</p>
                            <p class="text-[10px] text-outline mt-1">PNG, JPG up to 5MB</p>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2 flex justify-end gap-4 pt-4 border-t border-outline-variant mt-2">
                        <button type="button" class="px-6 py-3 border-2 border-outline-variant font-bold text-secondary hover:bg-surface-container-low transition-colors" @click="closeAddItemModal">
                            Batal
                        </button>
                        <button type="submit" class="bg-[#ee6c12] text-white px-8 py-3 font-bold hover:brightness-110 active:scale-95 transition-all">
                            Simpan Barang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT ITEM MODAL OVERLAY -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" @click.self="closeEditModal">
        <div class="bg-surface w-full max-w-2xl border-2 border-outline shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Modal Header -->
            <div class="bg-surface-container-high px-margin-desktop py-4 border-b-2 border-outline-variant flex justify-between items-center">
                <h3 class="text-headline-md font-headline-md font-bold text-on-surface">Edit Data Barang</h3>
                <button class="text-secondary hover:text-error transition-colors" @click="closeEditModal">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-margin-desktop">
                <form class="grid grid-cols-1 md:grid-cols-2 gap-6" @submit.prevent="closeEditModal">
                    <div class="md:col-span-2 flex items-center gap-4 p-4 bg-surface-container-low border-2 border-outline-variant rounded-lg mb-2">
                        <div class="w-20 h-20 rounded border border-outline-variant overflow-hidden bg-white shrink-0">
                            <img alt="Cement" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida/AP1WRLtpJxMGRj-5-WpjBrBHHq7FEzzM9htfXIdyBjYMYMZXO_Atc-NvDGhy7_nDKvFdElkV36N3MbnCtka7L22Cajr9gYgtszphvYUT72_SRWyL1BhkiXSVyWOXEwlcF8jHls2R1bi4iD3dIT9WrXhq480UGCK-lCBKs0jsbpImhtNXnOh3oZbAdfRifDrpz6tkhMPmrfFgYw8eyCpGzQaBRpoVHfq1l32Dbrx0F5uLuulSEuPL4VItVKzwWic">
                        </div>
                        <div>
                            <p class="text-label-md text-secondary uppercase tracking-wider">Produk</p>
                            <p class="text-headline-md font-bold text-on-surface">Semen Tiga Roda 40kg</p>
                            <p class="font-mono text-xs text-primary font-bold">TR-PC-40KG</p>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Stok Saat Ini</label>
                        <div class="flex items-center gap-0">
                            <button type="button" class="h-12 w-12 border-2 border-outline-variant bg-surface-container-high flex items-center justify-center hover:bg-surface-container-low transition-colors">
                                <span class="material-symbols-outlined">remove</span>
                            </button>
                            <input type="number" class="h-12 flex-1 text-center border-y-2 border-x-0 border-outline-variant bg-white focus:ring-0 text-body-md font-bold" value="200">
                            <button type="button" class="h-12 w-12 border-2 border-outline-variant bg-surface-container-high flex items-center justify-center hover:bg-surface-container-low transition-colors">
                                <span class="material-symbols-outlined">add</span>
                            </button>
                            <div class="ml-4 px-4 py-3 bg-surface-container-high border-2 border-outline-variant text-label-md font-bold text-secondary">SAK</div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Harga Jual (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary font-bold">Rp</span>
                            <input type="text" class="w-full p-3 pl-10 border-2 border-outline-variant bg-white focus:ring-0 text-body-md font-bold" value="68.500">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-label-md font-label-md text-secondary mb-2">Harga Beli (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary font-bold">Rp</span>
                            <input type="text" class="w-full p-3 pl-10 border-2 border-outline-variant bg-white focus:ring-0 text-body-md font-bold" value="55.000">
                        </div>
                    </div>
                    
                    <div class="md:col-span-2 flex justify-end gap-4 pt-6 border-t border-outline-variant mt-4">
                        <button type="button" class="px-6 py-3 border-2 border-outline-variant font-bold text-secondary hover:bg-surface-container-low transition-colors" @click="closeEditModal">Batal</button>
                        <button type="submit" class="bg-[#ee6c12] text-white px-8 py-3 font-bold hover:brightness-110 active:scale-95 transition-all">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- BOTTOM NAV (Mobile Only) -->
    <nav class="fixed bottom-0 left-0 w-full bg-surface border-t-2 border-outline-variant flex justify-around items-center h-16 px-4 md:hidden z-50">
        <Link 
            href="/dashboard" 
            class="flex flex-col items-center justify-center rounded-full px-4 py-1 min-h-[48px] w-16 text-secondary transition-all active:translate-y-[1px]"
        >
            <span class="material-symbols-outlined">home</span>
            <span class="text-[10px] font-semibold">Home</span>
        </Link>
        <Link 
            href="/inventaris" 
            class="flex flex-col items-center justify-center rounded-full px-4 py-1 min-h-[48px] w-16 bg-primary text-on-primary font-bold transition-all active:translate-y-[1px]"
        >
            <span class="material-symbols-outlined">apps</span>
            <span class="text-[10px] font-semibold">Inventory</span>
        </Link>
        <button 
            @click="triggerToast('Membuka kasir untuk transaksi baru...')"
            class="flex flex-col items-center justify-center rounded-full px-4 py-1 min-h-[48px] w-16 text-secondary active:translate-y-[1px] transition-all duration-200"
        >
            <span class="material-symbols-outlined">add_shopping_cart</span>
            <span class="text-[10px] font-semibold">POS</span>
        </button>
        <button 
            @click="triggerToast('Menu Reports sedang dalam pengembangan.')"
            class="flex flex-col items-center justify-center rounded-full px-4 py-1 min-h-[48px] w-16 text-secondary active:translate-y-[1px] transition-all duration-200"
        >
            <span class="material-symbols-outlined">assessment</span>
            <span class="text-[10px] font-semibold">Reports</span>
        </button>
    </nav>
</template>
