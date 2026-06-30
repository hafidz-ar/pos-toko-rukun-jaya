<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Toko Material POS - Daftar Inventori" />

    <div class="flex h-screen overflow-hidden">
        <!-- SIDE NAVBAR (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0">
            <div class="flex items-center px-2 py-4 mb-4">
                <span class="text-headline-md font-headline-md font-bold text-primary">Toko Material</span>
            </div>
            
            <div class="space-y-4">
                <div class="px-2">
                    <p class="text-xs font-bold text-secondary uppercase tracking-wider mb-2">Main Menu</p>
                    <nav class="space-y-1">
                        <Link href="/dashboard" class="flex items-center gap-3 px-3 py-3 text-secondary hover:bg-surface-container-high transition-colors text-label-md font-label-md">
                            <span class="material-symbols-outlined">dashboard</span> Dashboard
                        </Link>
                        <Link href="/inventory" class="flex items-center gap-3 px-3 py-3 bg-secondary-container text-on-secondary-container font-bold rounded-lg text-label-md font-label-md">
                            <span class="material-symbols-outlined">inventory_2</span> Inventory
                        </Link>
                        <a href="#" class="flex items-center gap-3 px-3 py-3 text-secondary hover:bg-surface-container-high transition-colors text-label-md font-label-md">
                            <span class="material-symbols-outlined">point_of_sale</span> Sales
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-3 text-secondary hover:bg-surface-container-high transition-colors text-label-md font-label-md">
                            <span class="material-symbols-outlined">analytics</span> Reports
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-3 text-secondary hover:bg-surface-container-high transition-colors text-label-md font-label-md">
                            <span class="material-symbols-outlined">settings</span> Settings
                        </a>
                    </nav>
                </div>
                
                <div class="px-2 mt-auto">
                    <button class="w-full flex items-center justify-center gap-2 bg-primary-container text-white font-bold py-4 rounded-lg active:scale-95 transition-transform">
                        <span class="material-symbols-outlined">add</span> New Transaction
                    </button>
                </div>
            </div>
            
            <div class="mt-auto p-4 border-t border-outline-variant">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-secondary rounded-full flex items-center justify-center text-white">
                        <span class="material-symbols-outlined">account_circle</span>
                    </div>
                    <div>
                        <p class="text-label-md font-label-md">Admin Staff</p>
                        <p class="text-[10px] text-secondary">Main Warehouse</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col min-w-0 bg-background overflow-y-auto pb-20 md:pb-0" :style="{ overflow: (isAddModalOpen || isEditModalOpen) ? 'hidden' : 'auto' }">
            <!-- TOP NAVBAR -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Daftar Inventori</span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="hidden md:flex relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-secondary">
                            <span class="material-symbols-outlined">search</span>
                        </span>
                        <input class="pl-10 pr-4 py-2 bg-surface-container rounded-lg border-none text-label-md focus:ring-0 w-64" placeholder="Cari barang..." type="text">
                    </div>
                    <div class="flex gap-4">
                        <span class="material-symbols-outlined text-secondary cursor-pointer">settings</span>
                        <span class="material-symbols-outlined text-secondary cursor-pointer">account_circle</span>
                    </div>
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
                    
                    <button class="bg-[#ee6c12] text-white px-6 py-3 font-bold flex items-center gap-2 min-h-[56px] hover:brightness-110 active:translate-y-px transition-all" @click="openAddItemModal">
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
                                <!-- Item 1: PVC Pipe -->
                                <tr class="hover:bg-surface-container-low transition-colors align-middle">
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-lg border border-outline-variant overflow-hidden bg-white shadow-sm">
                                            <img alt="PVC Pipe" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida/AP1WRLv2wWezj5LFqdV7oVdxNHt47w02ESWpxXqklHLRsRVzo3oVhCv5BIyH5FfKnJaGBRacD7Lf0LUC05NytqOe5Ky7LiCHd2M4UmBLNnyDkO88lSE6l6tEzVdocRHoCSecWSSO_JPWkceWsLK8ZJEjH1u-PqmDSSRm_EGswW4jrf0jcg7d8G3Hf2w4ZwN9XpV1flqC5ectfgI3gLTi_MztFFA7YruGmp7XYUmI_JVE3aP5S4_6y_ytTBMtGgI">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">Pipa PVC Wavin 3/4" (AW)</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">PVC-34-WAV-01</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Pipa</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-bold text-on-surface">150</span>
                                            <span class="text-[10px] text-secondary uppercase">Batang</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-on-surface">Rp 25.000</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">RAK-A1</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button class="bg-primary-container/10 text-primary hover:bg-primary hover:text-white transition-all p-2 rounded-lg flex items-center justify-center mx-auto" @click="openEditModal" title="Update Stock &amp; Price">
                                            <span class="material-symbols-outlined">edit_square</span>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Item 2: White Paint -->
                                <tr class="bg-surface-container-lowest hover:bg-surface-container-low transition-colors align-middle">
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-lg border border-outline-variant overflow-hidden bg-white shadow-sm">
                                            <img alt="White Paint" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida/AP1WRLuMrNSLP3aG2yyjWy6jwhVMilXYd0NkLnAMTgr7YV_UsX1rB3oNFLoIjnX1arvjsF-cWw0ikB1Oec4VeHXM5w1Q-MDBjvfiEvvFzZpHoY5iTEG_RNbRj8q_CmYdkIy1mFDamlw_UZ3Z-bjHC6EYtv4gjFVrfke6Ua7jodBnxImQYWhv54U11iil7SiDIleuIdfoLEQfNQo6SLgRse494tosDszYlOcdBpbNPRPr86mDGREzDw8bGSPQnqU">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">Cat Tembok Nippon Paint 5kg</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">CAT-NP-WHT-05</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Cat</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-bold text-error">12</span>
                                            <span class="text-[10px] text-error font-bold uppercase">Low Stock</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-on-surface">Rp 120.000</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">RAK-C2</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button class="bg-primary-container/10 text-primary hover:bg-primary hover:text-white transition-all p-2 rounded-lg flex items-center justify-center mx-auto" @click="openEditModal" title="Update Stock &amp; Price">
                                            <span class="material-symbols-outlined">edit_square</span>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Item 3: Cement -->
                                <tr class="hover:bg-surface-container-low transition-colors align-middle">
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-lg border border-outline-variant overflow-hidden bg-white shadow-sm">
                                            <img alt="Cement" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida/AP1WRLtpJxMGRj-5-WpjBrBHHq7FEzzM9htfXIdyBjYMYMZXO_Atc-NvDGhy7_nDKvFdElkV36N3MbnCtka7L22Cajr9gYgtszphvYUT72_SRWyL1BhkiXSVyWOXEwlcF8jHls2R1bi4iD3dIT9WrXhq480UGCK-lCBKs0jsbpImhtNXnOh3oZbAdfRifDrpz6tkhMPmrfFgYw8eyCpGzQaBRpoVHfq1l32Dbrx0F5uLuulSEuPL4VItVKzwWic">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">Semen Tiga Roda 40kg</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">TR-PC-40KG</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Semen</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-bold text-on-surface">200</span>
                                            <span class="text-[10px] text-secondary uppercase">Sak</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-on-surface">Rp 68.500</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">BLOK-B1</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button class="bg-primary-container/10 text-primary hover:bg-primary hover:text-white transition-all p-2 rounded-lg flex items-center justify-center mx-auto" @click="openEditModal" title="Update Stock &amp; Price">
                                            <span class="material-symbols-outlined">edit_square</span>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Item 4: Wood Nails -->
                                <tr class="bg-surface-container-lowest hover:bg-surface-container-low transition-colors align-middle">
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-lg border border-outline-variant overflow-hidden bg-white shadow-sm">
                                            <img alt="Wood Nails" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida/AP1WRLuafDkHBlh-MUWfzbSh8NqCc_Zyg-9p-nGK4AlilY4uFuiqpUu0MDuI5Ncqgi77-Hhf71CbQsTUvIT9qVdWXlmwnObJBYGoWZPaqY0qYhjMOYVLLiAmQw5pxg4RaBgvMw-3J5Qf64tEYPuWN11X92wBRolZNwr_bfgn0mGrcMgsVIweQWmUYfkdsv_UUjY7xDkalZitwktLP9PtJZHcWHV2JH8jv8qRnO-9suwSKiHzxraEjv4V_-__mw">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">Paku Kayu 3 Inch (Box)</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">PAKU-K-3INC</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">Hardware</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-bold text-on-surface">50</span>
                                            <span class="text-[10px] text-secondary uppercase">Box</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-on-surface">Rp 42.000</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">RAK-D3</span>
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
        <Link href="/dashboard" class="flex flex-col items-center justify-center text-secondary">
            <span class="material-symbols-outlined">home</span>
            <span class="text-[10px] font-bold">Home</span>
        </Link>
        <Link href="/inventory" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1">
            <span class="material-symbols-outlined">apps</span>
            <span class="text-[10px] font-bold">Inventory</span>
        </Link>
        <button class="flex flex-col items-center justify-center text-secondary">
            <span class="material-symbols-outlined">add_shopping_cart</span>
            <span class="text-[10px] font-bold">POS</span>
        </button>
        <button class="flex flex-col items-center justify-center text-secondary">
            <span class="material-symbols-outlined">assessment</span>
            <span class="text-[10px] font-bold">Reports</span>
        </button>
    </nav>
</template>
