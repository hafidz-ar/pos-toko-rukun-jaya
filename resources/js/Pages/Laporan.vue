<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    period: String,
    periodLabel: String,
    summary: Object,
    labaPerProduk: Array,
    labaPerKategori: Array,
});

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};

const formatShortRupiah = (value) => {
    if (value >= 1e9) return 'Rp ' + (value / 1e9).toFixed(1) + 'M';
    if (value >= 1e6) return 'Rp ' + (value / 1e6).toFixed(1) + 'Jt';
    return formatRupiah(value);
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

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.post('/logout');
    }, 800);
};

// Period selector
const changePeriod = (period) => {
    router.get('/laporan', { period }, { preserveState: false });
};

// Export PDF
const exportPdf = () => {
    const period = props.period || 'mingguan';
    window.location.href = `/laporan/export-pdf?period=${period}`;
};

// Print Report
const printReport = () => {
    window.print();
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

                <!-- Restock Tab -->
                <Link 
                    href="/restock"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span>Restok</span>
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
                            <button @click="changePeriod('harian')" :class="props.period === 'harian' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors">Harian</button>
                            <button @click="changePeriod('mingguan')" :class="(props.period === 'mingguan' || !props.period) ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors">Mingguan</button>
                            <button @click="changePeriod('bulanan')" :class="props.period === 'bulanan' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors">Bulanan</button>
                        </div>
                        <span v-if="props.periodLabel" class="text-sm text-secondary">{{ props.periodLabel }}</span>
                    </div>
                    <div class="flex items-center gap-2 md:space-x-4">
                        <button @click="exportPdf" class="flex items-center justify-center gap-2 border-2 border-outline-variant px-4 py-2 rounded text-primary font-bold hover:bg-surface-container-high transition-colors w-full md:w-auto">
                            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>Ekspor PDF
                        </button>
                        <button @click="printReport" class="flex items-center justify-center gap-2 bg-primary text-on-primary px-4 py-2 rounded font-bold hover:brightness-90 transition-all w-full md:w-auto">
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
                                <h2 class="text-display-price font-display-price mb-4">{{ formatShortRupiah(props.summary?.total_omset || 0) }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">TOTAL LABA KOTOR</p>
                                <h2 class="text-display-price font-display-price mb-4">{{ formatShortRupiah(props.summary?.total_laba_kotor || 0) }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">savings</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">JUMLAH TRANSAKSI</p>
                                <h2 class="text-display-price font-display-price mb-4">{{ props.summary?.jumlah_transaksi || 0 }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">receipt_long</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
                    <!-- Sales History Table -->
                    <div class="lg:col-span-7 bg-surface-container-lowest border-2 border-outline-variant rounded flex flex-col">
                        <div class="p-6 border-b-2 border-outline-variant bg-surface-container-low flex justify-between items-center">
                            <h3 class="font-headline-md text-headline-md font-bold">Laba Kotor per Produk</h3>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-left min-w-[600px]">
                                <thead>
                                    <tr class="border-b-2 border-outline-variant bg-surface-container-lowest">
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary">Produk</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Kategori</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Omzet</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Total HPP</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Laba Kotor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-outline-variant/30">
                                    <tr v-if="!props.labaPerProduk || props.labaPerProduk.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-secondary">Tidak ada data untuk periode ini.</td>
                                    </tr>
                                    <tr v-for="produk in props.labaPerProduk" :key="produk.product_id" class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">{{ produk.product_name }}</td>
                                        <td class="px-4 py-4 text-right">{{ produk.category }}</td>
                                        <td class="px-4 py-4 text-right">{{ formatShortRupiah(produk.total_revenue) }}</td>
                                        <td class="px-4 py-4 text-right">{{ formatShortRupiah(produk.total_hpp) }}</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">{{ formatShortRupiah(produk.total_profit) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Laba Kotor per Category -->
                    <div class="lg:col-span-5 bg-surface-container-lowest border-2 border-outline-variant rounded flex flex-col h-full">
                        <div class="p-6 border-b-2 border-outline-variant bg-surface-container-low flex justify-between items-center">
                            <h3 class="font-headline-md text-headline-md font-bold">Laba Kotor per Kategori</h3>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-left min-w-[400px]">
                                <thead>
                                    <tr class="border-b-2 border-outline-variant bg-surface-container-lowest">
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary">Kategori</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Omzet</th>
                                        <th class="px-4 py-4 text-label-md font-bold text-secondary text-right">Laba Kotor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-outline-variant/30">
                                    <tr v-if="!props.labaPerKategori || props.labaPerKategori.length === 0">
                                        <td colspan="3" class="px-4 py-8 text-center text-secondary">Tidak ada data untuk periode ini.</td>
                                    </tr>
                                    <tr v-for="kat in props.labaPerKategori" :key="kat.category_name" class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 font-bold text-on-surface">{{ kat.category_name }}</td>
                                        <td class="px-4 py-4 text-right">{{ formatShortRupiah(kat.total_revenue) }}</td>
                                        <td class="px-4 py-4 text-right font-bold text-on-surface">{{ formatShortRupiah(kat.total_profit) }}</td>
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
            <Link href="/dashboard" class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                <span class="material-symbols-outlined">home</span>
                <span class="text-[10px] font-bold">Home</span>
            </Link>
            <Link href="/inventaris" class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                <span class="material-symbols-outlined">apps</span>
                <span class="text-[10px] font-bold">Inventory</span>
            </Link>
            <Link href="/kasir" class="flex flex-col items-center justify-center text-secondary cursor-pointer">
                <span class="material-symbols-outlined">add_shopping_cart</span>
                <span class="text-[10px] font-bold">POS</span>
            </Link>
            <Link href="/laporan" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1 cursor-pointer">
                <span class="material-symbols-outlined">assessment</span>
                <span class="text-[10px] font-bold">Laporan</span>
            </Link>
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
