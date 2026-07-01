<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

const currentTab = ref('sales');
const setTab = (tab) => {
    currentTab.value = tab;
    if (tab === 'inventory') {
        router.visit('/inventory');
    } else if (tab === 'dashboard') {
        router.visit('/dashboard');
    } else if (tab === 'sales') {
        router.visit('/penjualan');
    } else {
        triggerToast(`Menu ${tab.charAt(0).toUpperCase() + tab.slice(1)} sedang dalam pengembangan.`);
    }
};

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.visit('/');
    }, 800);
};

</script>

<template>
    <Head title="Penjualan | Toko Material POS" />

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
            <div v-if="showToast" class="fixed top-4 right-4 z-50 max-w-sm bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded-lg shadow-lg border border-outline flex items-center gap-3">
                <span class="material-symbols-outlined text-primary-fixed">info</span>
                <span class="text-sm font-semibold">{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- SIDE NAVBAR (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0">
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
                    href="/inventory"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Inventaris</span>
                </Link>

                <!-- Sales Tab (Active) -->
                <Link 
                    href="/penjualan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">point_of_sale</span>
                    <span>Penjualan</span>
                </Link>

                <!-- Reports Tab -->
                <button 
                    @click="setTab('reports')"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Laporan</span>
                </button>

                <!-- Settings Tab -->
                <button 
                    @click="setTab('settings')"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">settings</span>
                    <span>Pengaturan</span>
                </button>
            </div>

            <!-- Profile & New Transaction Area -->
            <div class="mt-auto border-t border-outline-variant pt-4 pb-2 px-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-secondary text-on-secondary flex items-center justify-center font-bold">A</div>
                        <div>
                            <p class="text-label-md font-label-md leading-none">Staf Admin</p>
                            <p class="text-xs text-secondary mt-1">Gudang Utama</p>
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

        <!-- Main Content Area-->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-surface-bright relative">
            
            <!-- TopNavBar -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Riwayat Penjualan</span>
                </div>
            </header>

            <!-- Content Canvas -->
            <div class="flex-1 overflow-y-auto p-margin-desktop space-y-gutter pb-24 md:pb-margin-desktop">
                <!-- Transactions Table Section -->
                <div class="industrial-border bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-outline-variant">
                    <div class="bg-surface-container-high p-4 flex justify-between items-center border-b border-outline-variant flex-wrap gap-4">
                        <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
                            <div class="relative flex-1 md:flex-none">
                                <input class="bg-surface-container-lowest border border-outline px-10 py-2 rounded text-body-md focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none w-full md:w-64" placeholder="Cari ID Transaksi..." type="text">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary">search</span>
                            </div>
                            <div class="flex items-center gap-2 bg-surface-container-lowest border border-outline px-3 py-2 rounded">
                                <span class="material-symbols-outlined text-secondary text-[20px]">calendar_today</span>
                                <span class="text-label-md text-on-surface">24 Okt 2024</span>
                                <span class="material-symbols-outlined text-secondary">arrow_drop_down</span>
                            </div>
                            <div class="flex items-center gap-2 bg-surface-container-lowest border border-outline px-3 py-2 rounded">
                                <span class="text-label-md text-secondary">Rentang:</span>
                                <span class="text-label-md text-on-surface">Hari Ini</span>
                                <span class="material-symbols-outlined text-secondary">arrow_drop_down</span>
                            </div>
                        </div>
                        <button class="bg-surface-container-lowest border border-outline px-4 py-2 text-label-md font-label-md rounded flex items-center gap-2 hover:bg-surface-container-low transition-colors ml-auto md:ml-0">
                            <span class="material-symbols-outlined">download</span> Ekspor PDF
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-surface-container text-secondary text-label-md font-label-md">
                                    <th class="p-4 border-b border-outline-variant">ID / Waktu</th>
                                    <th class="p-4 border-b border-outline-variant">Barang Dibeli</th>
                                    <th class="p-4 border-b border-outline-variant">Total Harga</th>
                                    <th class="p-4 border-b border-outline-variant">Pembayaran</th>
                                    <th class="p-4 border-b border-outline-variant text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row 1 -->
                                <tr class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4 border-b border-outline-variant">
                                        <p class="font-bold text-on-surface">TX-2410-0912</p>
                                        <p class="text-[12px] text-secondary">14:22:10</p>
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Semen x5</span>
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Plywood 9mm x2</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface border-b border-outline-variant">Rp 1.450.000</td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <span class="inline-flex items-center gap-1 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">qr_code_2</span> QRIS
                                        </span>
                                    </td>
                                    <td class="p-4 text-right border-b border-outline-variant">
                                        <button class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Row 2 -->
                                <tr class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4 border-b border-outline-variant">
                                        <p class="font-bold text-on-surface">TX-2410-0911</p>
                                        <p class="text-[12px] text-secondary">14:15:05</p>
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Pipa PVC 3" x10</span>
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Lem Solvent x1</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface border-b border-outline-variant">Rp 820.000</td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <span class="inline-flex items-center gap-1 bg-surface-container-high text-on-secondary-container px-3 py-1 rounded-full text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">payments</span> Tunai
                                        </span>
                                    </td>
                                    <td class="p-4 text-right border-b border-outline-variant">
                                        <button class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Row 3 -->
                                <tr class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4 border-b border-outline-variant">
                                        <p class="font-bold text-on-surface">TX-2410-0910</p>
                                        <p class="text-[12px] text-secondary">14:02:44</p>
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Pasir 1m3 x1</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface border-b border-outline-variant">Rp 350.000</td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <span class="inline-flex items-center gap-1 bg-surface-container-high text-on-secondary-container px-3 py-1 rounded-full text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">payments</span> Tunai
                                        </span>
                                    </td>
                                    <td class="p-4 text-right border-b border-outline-variant">
                                        <button class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Row 4 -->
                                <tr class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4 border-b border-outline-variant">
                                        <p class="font-bold text-on-surface">TX-2410-0909</p>
                                        <p class="text-[12px] text-secondary">13:50:12</p>
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Besi Beton 12mm x50</span>
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Kawat Ikat x2</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface border-b border-outline-variant">Rp 6.120.000</td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <span class="inline-flex items-center gap-1 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">qr_code_2</span> QRIS
                                        </span>
                                    </td>
                                    <td class="p-4 text-right border-b border-outline-variant">
                                        <button class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Row 5 -->
                                <tr class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4">
                                        <p class="font-bold text-on-surface">TX-2410-0908</p>
                                        <p class="text-[12px] text-secondary">13:45:33</p>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">Genteng x200</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface">Rp 2.400.000</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1 bg-surface-container-high text-on-secondary-container px-3 py-1 rounded-full text-label-md font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">payments</span> Tunai
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-4 bg-surface-container-low border-t border-outline-variant flex items-center justify-between flex-wrap gap-4">
                        <p class="text-label-md font-label-md text-secondary">Menampilkan 1 sampai 5 dari 148 entri</p>
                        <div class="flex gap-1">
                            <button class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </button>
                            <button class="w-10 h-10 flex items-center justify-center rounded bg-primary text-on-primary font-bold">1</button>
                            <button class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors">2</button>
                            <button class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors">3</button>
                            <button class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- BottomNavBar (Mobile Only) -->
            <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-16 px-4 md:hidden bg-surface border-t-2 border-outline-variant shadow-lg z-50">
                <button @click="setTab('dashboard')" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-[10px] font-label-md">Beranda</span>
                </button>
                <button @click="setTab('inventory')" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">apps</span>
                    <span class="text-[10px] font-label-md">Inventaris</span>
                </button>
                <button @click="setTab('sales')" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1">
                    <span class="material-symbols-outlined">add_shopping_cart</span>
                    <span class="text-[10px] font-label-md">POS</span>
                </button>
                <button @click="setTab('reports')" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">assessment</span>
                    <span class="text-[10px] font-label-md">Laporan</span>
                </button>
            </nav>
        </main>
    </div>
</template>
