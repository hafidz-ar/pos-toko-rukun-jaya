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

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.visit('/');
    }, 800);
};

</script>

<template>
    <Head title="Laporan & Analitik - Toko Material POS" />

    <div class="bg-background text-on-surface font-body-md h-screen flex flex-col md:flex-row overflow-hidden">
        
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

                <!-- Reports Tab (Active) -->
                <Link 
                    href="/laporan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
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

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- TopNavBar -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Laporan Keuangan</span>
                </div>
            </header>

            <!-- Dashboard Canvas -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-margin-desktop space-y-gutter pb-24 md:pb-8">
                <!-- Page Title & Actions -->
                <div class="bg-surface-container-low border-2 border-outline-variant rounded p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <span class="font-bold text-on-surface">Periode:</span>
                        <div class="flex flex-wrap bg-surface-container-lowest border-2 border-outline-variant rounded p-1">
                            <button class="px-4 py-1.5 text-secondary font-bold rounded hover:bg-surface-container-low transition-colors">Harian</button>
                            <button class="px-4 py-1.5 bg-primary/10 text-primary font-bold rounded border-2 border-primary">Mingguan</button>
                            <button class="px-4 py-1.5 text-secondary font-bold rounded hover:bg-surface-container-low transition-colors">Bulanan</button>
                            <button class="flex items-center gap-2 px-4 py-1.5 text-secondary font-bold rounded hover:bg-surface-container-low transition-colors">
                                <span class="material-symbols-outlined text-sm">calendar_month</span>Custom
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 md:space-x-4">
                        <button class="flex items-center justify-center gap-2 border-2 border-outline-variant px-4 py-2 rounded text-primary font-bold hover:bg-surface-container-high transition-colors w-full md:w-auto">
                            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>Ekspor PDF
                        </button>
                        <button class="flex items-center justify-center gap-2 bg-primary text-on-primary px-4 py-2 rounded font-bold hover:brightness-90 transition-all w-full md:w-auto">
                            <span class="material-symbols-outlined text-sm">print</span>Cetak Laporan
                        </button>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">TOTAL OMZET</p>
                                <h2 class="text-display-price font-display-price mb-4">Rp<br/>142.5M</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                        </div>
                        <div class="flex items-center text-label-md text-tertiary font-bold">
                            <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
                            <span>+8.4% vs minggu lalu</span>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">TOTAL LABA KOTOR</p>
                                <h2 class="text-display-price font-display-price mb-4">Rp<br/>38.2M</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">savings</span>
                            </div>
                        </div>
                        <div class="flex items-center text-label-md text-tertiary font-bold">
                            <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
                            <span>+5.1% vs minggu lalu</span>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">JUMLAH TRANSAKSI</p>
                                <h2 class="text-display-price font-display-price mb-4">428</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">receipt_long</span>
                            </div>
                        </div>
                        <div class="flex items-center text-label-md text-secondary font-bold">
                            <span class="material-symbols-outlined text-sm mr-1">arrow_right_alt</span>
                            <span>Stabil vs minggu lalu</span>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
                    <!-- Sales History Table -->
                    <div class="lg:col-span-7 bg-surface-container-lowest border-2 border-outline-variant rounded flex flex-col">
                        <div class="p-6 border-b-2 border-outline-variant bg-surface-container-low flex justify-between items-center">
                            <h3 class="font-headline-md text-headline-md font-bold">Riwayat Penjualan</h3>
                            <button class="text-primary font-bold hover:underline">Lihat Semua</button>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-left min-w-[600px]">
                                <thead>
                                    <tr class="border-b-2 border-outline-variant bg-surface-container-lowest">
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary">Tanggal</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Trx</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Omzet</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Total HPP</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Laba Kotor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-outline-variant/30">
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">18 Nov 2023</td>
                                        <td class="px-4 py-4 text-right">65</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>22.1M</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>16.5M</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">Rp<br/>5.6M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">17 Nov 2023</td>
                                        <td class="px-4 py-4 text-right">72</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>24.5M</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>18.2M</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">Rp<br/>6.3M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">16 Nov 2023</td>
                                        <td class="px-4 py-4 text-right">58</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>19.8M</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>14.9M</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">Rp<br/>4.9M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">15 Nov 2023</td>
                                        <td class="px-4 py-4 text-right">61</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>21.0M</td>
                                        <td class="px-4 py-4 text-right">Rp<br/>15.5M</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">Rp<br/>5.5M</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Laba Kotor per Category -->
                    <div class="lg:col-span-5 bg-surface-container-lowest border-2 border-outline-variant rounded flex flex-col h-full">
                        <div class="p-6 border-b-2 border-outline-variant bg-surface-container-low flex justify-between items-center">
                            <h3 class="font-headline-md text-headline-md font-bold">Laba Kotor per Kategori</h3>
                            <button class="flex items-center gap-2 border-2 border-outline-variant px-3 py-1.5 rounded text-on-surface font-bold bg-surface-container-lowest hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined text-sm">filter_list</span>Filter</button>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-left min-w-[400px]">
                                <thead>
                                    <tr class="border-b-2 border-outline-variant bg-surface-container-lowest">
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary">Kategori</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Jml Terjual</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-center">Kontribusi</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Laba Kotor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-outline-variant/30">
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 font-bold text-on-surface">Semen &amp; Mortar</td>
                                        <td class="px-4 py-4 text-right">1,240<br/><span class="text-sm text-secondary">sak</span></td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-16 bg-surface-container h-2 rounded-full overflow-hidden">
                                                    <div class="bg-primary w-[45%] h-full"></div>
                                                </div>
                                                <span class="font-bold">45%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right font-bold text-on-surface">Rp<br/>17.2M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 font-bold text-on-surface">Besi &amp; Baja</td>
                                        <td class="px-4 py-4 text-right">850<br/><span class="text-sm text-secondary">btg</span></td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-16 bg-surface-container h-2 rounded-full overflow-hidden">
                                                    <div class="bg-tertiary w-[30%] h-full"></div>
                                                </div>
                                                <span class="font-bold">30%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right font-bold text-on-surface">Rp<br/>11.4M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 font-bold text-on-surface">Cat &amp; Thinner</td>
                                        <td class="px-4 py-4 text-right">320<br/><span class="text-sm text-secondary">klg</span></td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-16 bg-surface-container h-2 rounded-full overflow-hidden">
                                                    <div class="bg-secondary w-[15%] h-full"></div>
                                                </div>
                                                <span class="font-bold">15%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right font-bold text-on-surface">Rp<br/>5.7M</td>
                                    </tr>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 font-bold text-on-surface">Lain-lain</td>
                                        <td class="px-4 py-4 text-right">-</td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-16 bg-surface-container h-2 rounded-full overflow-hidden">
                                                    <div class="bg-outline w-[10%] h-full"></div>
                                                </div>
                                                <span class="font-bold">10%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right font-bold text-on-surface">Rp<br/>3.9M</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- BottomNavBar (Mobile Only) -->
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
                <span class="material-symbols-outlined">assessment</span>
                <span class="text-[10px] font-bold">Laporan</span>
            </button>
        </nav>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e0e3e5;
    border-radius: 10px;
}
</style>
