<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    period: String,
    totalOmset: Number,
    jumlahTransaksi: Number,
    totalLabaKotor: Number,
    labaPerProduk: Array,
    labaPerKategori: Array,
    stokKritis: Array,
    transaksiMerugi: Number,
    chartData: Array,
    riwayatHariIni: Array,
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

// Chart hover state
const hoveredPoint = ref(null);

const chartPoints = computed(() => {
    const data = props.chartData || [];
    if (data.length === 0) return [];
    const maxAmount = Math.max(...data.map(d => d.amount), 1);
    
    return data.map((d, index) => {
        return {
            day: d.day,
            date: d.date,
            amount: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(d.amount),
            rawAmount: d.amount,
            x: (index / Math.max(1, data.length - 1)) * 700,
            y: 180 - ((d.amount / maxAmount) * 140) // 180 max y down, 40 min y up
        };
    });
});

const chartPath = computed(() => {
    if (!chartPoints.value || chartPoints.value.length === 0) return '';
    return chartPoints.value.map((p, i) => `${i===0?'M':'L'}${p.x},${p.y}`).join(' ');
});

const chartMaxAmount = computed(() => {
    const data = props.chartData || [];
    const maxAmount = Math.max(...data.map(d => d.amount), 0);
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(maxAmount);
});

const chartAvgAmount = computed(() => {
    const data = props.chartData || [];
    if (data.length === 0) return 'Rp 0';
    const sum = data.reduce((acc, val) => acc + val.amount, 0);
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(sum / data.length);
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};

const selectPoint = (point) => {
    hoveredPoint.value = point;
};

const clearPoint = () => {
    hoveredPoint.value = null;
};

// Logout handler
const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.visit('/');
    }, 800);
};

// Navigation menu helpers
const currentTab = ref('dashboard');
const setTab = (tab) => {
    currentTab.value = tab;
    if (tab === 'inventory') {
        router.visit('/inventaris');
    } else if (tab === 'dashboard') {
        router.visit('/dashboard');
    } else if (tab === 'sales') {
        router.visit('/penjualan');
    } else {
        triggerToast(`Menu ${tab.charAt(0).toUpperCase() + tab.slice(1)} sedang dalam pengembangan.`);
    }
};

const startTransaction = () => {
    router.visit('/kasir');
};

const checkStock = () => {
    router.visit('/inventaris');
};

const openOwnerMenu = (menuName) => {
    triggerToast(`Akses ditolak: Menu "${menuName}" hanya untuk Owner.`);
};
</script>

<template>
    <Head title="Dashboard | Toko Material POS" />

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
            <div v-if="showToast" class="fixed top-4 right-4 z-50 max-w-sm bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded-lg shadow-lg border border-outline flex items-center gap-3">
                <span class="material-symbols-outlined text-primary-fixed">info</span>
                <span class="text-sm font-semibold">{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- Top Navigation Bar (Mobile only) -->
        <nav class="md:hidden flex justify-between items-center w-full px-margin-mobile h-touch-target-min bg-surface border-b-2 border-outline-variant shrink-0 z-30">
            <span class="text-headline-md font-headline-md font-bold text-primary">Toko Material POS</span>
            <div class="flex gap-4">
                <button @click="triggerToast('Profil Admin')" class="material-symbols-outlined text-secondary active:scale-90 transition-transform">account_circle</button>
                <button @click="handleLogout" class="material-symbols-outlined text-error active:scale-90 transition-transform" title="Keluar">logout</button>
            </div>
        </nav>

        <!-- Side Navigation Bar (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0">
            <div class="px-4 py-6">
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Material POS</h1>
            </div>
            
            <div class="flex flex-col gap-1 flex-1">
                <!-- Dashboard Tab (Active) -->
                <Link 
                    href="/dashboard"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
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

        <!-- Main Content Canvas -->
        <main class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop h-full bg-background relative pb-24 md:pb-8">
            <!-- Header Section -->
            <header class="flex flex-row justify-between items-center mb-gutter">
                <div>
                    <h2 class="text-headline-md font-headline-md text-on-background">Halo, {{ props.auth?.user?.name?.split(' ')[0] }}</h2>
                    <p class="text-body-md font-body-md text-secondary">Selamat datang kembali di sistem kasir material.</p>
                </div>
                <div class="flex items-center bg-surface-container-high border border-outline-variant px-4 py-2 rounded-lg">
                    <span class="material-symbols-outlined text-secondary mr-2" style="font-size: 20px;">badge</span>
                    <span class="text-label-md font-label-md text-on-secondary-fixed-variant">{{ props.auth?.user?.role === 'owner' ? 'Owner' : 'Karyawan' }}</span>
                </div>
            </header>

            <!-- Bento Grid / Tiles Dashboard -->
            <div class="bento-grid">
                
                <!-- KASIR / JUALAN -->
                <div class="lg:col-span-8 lg:row-span-2 group">
                    <button 
                        @click="startTransaction"
                        class="w-full h-full text-left bg-surface-container-lowest border-2 border-outline-variant hover:border-primary transition-all duration-300 p-margin-desktop rounded-xl flex flex-col justify-between active-press relative overflow-hidden min-h-[320px] cursor-pointer"
                    >
                        <div class="flex justify-between items-start w-full">
                            <div class="bg-primary text-on-primary w-20 h-20 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined !text-[40px]">point_of_sale</span>
                            </div>
                            <span class="text-primary font-bold text-label-md tracking-widest bg-primary-fixed px-3 py-1 rounded-full">LIVE POS</span>
                        </div>
                        <div>
                            <h3 class="text-headline-lg font-headline-lg text-on-background">KASIR / JUALAN</h3>
                            <p class="text-body-lg font-body-lg text-secondary mt-2">Mulai transaksi penjualan baru untuk pelanggan toko.</p>
                        </div>
                        <!-- Subtle pattern for industrial feel -->
                        <div class="absolute right-0 bottom-0 opacity-[0.03] pointer-events-none">
                            <span class="material-symbols-outlined !text-[180px]">point_of_sale</span>
                        </div>
                    </button>
                </div>

                <!-- CEK STOK -->
                <div class="lg:col-span-4 lg:row-span-2 group">
                    <button 
                        @click="checkStock"
                        class="w-full h-full text-left bg-surface-container-lowest border-2 border-outline-variant hover:border-tertiary transition-all duration-300 p-margin-desktop rounded-xl flex flex-col justify-between active-press min-h-[320px] cursor-pointer"
                    >
                        <div class="bg-tertiary text-on-tertiary w-16 h-16 rounded-xl flex items-center justify-center shadow-md group-hover:rotate-6 transition-transform">
                            <span class="material-symbols-outlined !text-[32px]">warehouse</span>
                        </div>
                        <div>
                            <h3 class="text-headline-md font-headline-md text-on-background">CEK STOK</h3>
                            <p class="text-body-md font-body-md text-secondary mt-2">Cari ketersediaan barang di gudang utama & cabang.</p>
                        </div>
                    </button>
                </div>

                <!-- LAPORAN (Owner style) -->
                <div class="lg:col-span-6 group">
                    <button 
                        @click="openOwnerMenu('Laporan')"
                        class="w-full h-full text-left bg-surface border-2 border-dashed border-outline-variant opacity-60 hover:opacity-100 hover:border-secondary transition-all duration-300 p-8 rounded-xl flex items-center gap-6 active-press min-h-[160px] cursor-pointer"
                    >
                        <div class="bg-surface-container-highest text-secondary w-14 h-14 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined !text-[28px]">analytics</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-label-xl font-label-xl text-on-background">LAPORAN</h3>
                                <span class="material-symbols-outlined text-outline" style="font-size: 16px;">lock</span>
                            </div>
                            <p class="text-body-md font-body-md text-secondary">Analisa penjualan & laba rugi bulanan (Owner Only).</p>
                        </div>
                    </button>
                </div>

                <!-- HARGA MODAL (Owner style) -->
                <div class="lg:col-span-6 group">
                    <button 
                        @click="openOwnerMenu('Harga Modal')"
                        class="w-full h-full text-left bg-surface border-2 border-dashed border-outline-variant opacity-60 hover:opacity-100 hover:border-secondary transition-all duration-300 p-8 rounded-xl flex items-center gap-6 active-press min-h-[160px] cursor-pointer"
                    >
                        <div class="bg-surface-container-highest text-secondary w-14 h-14 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined !text-[28px]">settings_suggest</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-label-xl font-label-xl text-on-background">HARGA MODAL</h3>
                                <span class="material-symbols-outlined text-outline" style="font-size: 16px;">lock</span>
                            </div>
                            <p class="text-body-md font-body-md text-secondary">Kelola margin keuntungan & biaya operasional.</p>
                        </div>
                    </button>
                </div>

                <!-- Quick Stats Section -->
                <div class="lg:col-span-12 mt-4">
                    <div class="bg-surface-container p-6 rounded-xl border border-outline-variant flex flex-col md:flex-row items-center justify-between gap-6">
                        
                        <!-- Transaksi Hari Ini -->
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="p-3 bg-secondary-container rounded-lg shrink-0">
                                <span class="material-symbols-outlined text-on-secondary-container">assignment_turned_in</span>
                            </div>
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold tracking-wider">Transaksi ({{ props.period }})</p>
                                <p class="text-headline-md font-headline-md">{{ props.jumlahTransaksi }} Nota</p>
                            </div>
                        </div>
                        
                        <div class="h-10 w-px bg-outline-variant hidden md:block"></div>
                        
                        <!-- Omset Berjalan -->
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="p-3 bg-tertiary-fixed rounded-lg shrink-0">
                                <span class="material-symbols-outlined text-on-tertiary-fixed-variant">trending_up</span>
                            </div>
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold tracking-wider">Omset ({{ props.period }})</p>
                                <p class="text-headline-md font-headline-md">{{ formatRupiah(props.totalOmset) }}</p>
                            </div>
                        </div>
                        
                        <div class="h-10 w-px bg-outline-variant hidden md:block"></div>
                        
                        <!-- Stok Menipis -->
                        <div class="flex items-center gap-4 w-full md:w-auto cursor-pointer hover:opacity-80" @click="router.visit('/inventaris')">
                            <div class="p-3 bg-error-container rounded-lg shrink-0">
                                <span class="material-symbols-outlined text-on-error-container">warning</span>
                            </div>
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold tracking-wider">Stok Menipis</p>
                                <p class="text-headline-md font-headline-md text-error">{{ props.stokKritis?.length || 0 }} Item</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Sales Trend History Section (Interactive SVG) -->
                <div class="lg:col-span-12 mt-8">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 relative">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-headline-md font-headline-md text-on-background">Tren Penjualan (7 Hari Terakhir)</h3>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-primary"></span>
                                <span class="text-label-md text-secondary">Total Penjualan</span>
                            </div>
                        </div>

                        <!-- Chart with Y-Axis and SVG Area -->
                        <div class="flex gap-4 h-64 w-full relative select-none">
                            <!-- Y-axis Labels -->
                            <div class="flex flex-col justify-between text-[10px] md:text-xs text-secondary font-semibold h-full pb-1 pr-2 border-r border-outline-variant/30 text-right shrink-0 w-[80px] select-none">
                                <span>{{ chartMaxAmount }}</span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span>Rp 0</span>
                            </div>

                            <!-- SVG Chart Area -->
                            <div class="relative flex-1 h-full">
                                <!-- Grid Lines -->
                                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                    <div class="border-b border-outline-variant opacity-20 w-full"></div>
                                    <div class="border-b border-outline-variant opacity-20 w-full"></div>
                                    <div class="border-b border-outline-variant opacity-20 w-full"></div>
                                    <div class="border-b border-outline-variant opacity-20 w-full"></div>
                                    <div class="border-b border-outline-variant opacity-20 w-full"></div>
                                </div>

                                <svg class="absolute inset-0 w-full h-full" preserveAspectRatio="none" viewBox="0 0 700 200">
                                    <!-- Area Path with Gradient -->
                                    <path v-if="chartPath" :d="chartPath + ' V200 H0 Z'" fill="url(#chartGradient)" opacity="0.1"></path>
                                    <!-- Line Path -->
                                    <path v-if="chartPath" :d="chartPath" fill="none" stroke="var(--color-primary)" stroke-linecap="round" stroke-linejoin="round" stroke-width="4"></path>
                                    
                                    <defs>
                                        <linearGradient id="chartGradient" x1="0%" x2="0%" y1="0%" y2="100%">
                                            <stop offset="0%" style="stop-color: var(--color-primary); stop-opacity: 1"></stop>
                                            <stop offset="100%" style="stop-color: var(--color-primary); stop-opacity: 0"></stop>
                                        </linearGradient>
                                    </defs>
                                </svg>

                                <!-- Hoverable Circles for Data Points -->
                                <div class="absolute inset-0 pointer-events-none">
                                    <div 
                                        v-for="(point, idx) in chartPoints" 
                                        :key="idx" 
                                        class="absolute top-0 bottom-0 pointer-events-auto cursor-pointer flex flex-col items-center"
                                        :style="{ left: `${(idx * 100) / Math.max(1, chartPoints.length - 1)}%`, width: '40px', transform: 'translateX(-20px)' }"
                                        @mouseenter="selectPoint(point)"
                                        @mouseleave="clearPoint"
                                    >
                                        <!-- Invisible handle to ease hover on mobile / desktop -->
                                        <div class="w-full h-full"></div>

                                        <!-- Interactive Tooltip -->
                                        <div 
                                            v-if="hoveredPoint && hoveredPoint.day === point.day" 
                                            class="absolute bg-inverse-surface text-inverse-on-surface px-3 py-1.5 rounded text-xs font-semibold shadow-md whitespace-nowrap z-20 pointer-events-none transition-all duration-155 left-1/2 -translate-x-1/2"
                                            :style="{ top: `calc(${Math.max(0, Math.min(100, point.y / 2))}% - 45px)` }"
                                        >
                                            <div class="font-bold text-primary-fixed">{{ point.day }} ({{ point.date }}): {{ point.amount }}</div>
                                        </div>

                                        <!-- Small dot on line path -->
                                        <div 
                                            class="absolute w-3 h-3 rounded-full border-2 border-surface bg-primary transition-all duration-150 pointer-events-none left-[14px]"
                                            :style="{ top: `calc(${Math.max(0, Math.min(100, point.y / 2))}% - 6px)` }"
                                            :class="{ 'scale-150 shadow-md ring-2 ring-primary-fixed': hoveredPoint && hoveredPoint.day === point.day }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Axis Labels -->
                        <div class="flex gap-4 mt-8 text-xs text-secondary font-semibold pt-2 border-t border-outline-variant/30">
                            <!-- Spacer to align with Y-axis -->
                            <div class="w-[80px] shrink-0 border-r border-transparent"></div>
                            <!-- X-axis Labels -->
                            <div class="flex-1 flex justify-between">
                                <span v-for="point in chartPoints" :key="point.day">{{ point.day }}</span>
                            </div>
                        </div>

                        <!-- Mini Stats Summary -->
                        <div class="mt-8 flex justify-around text-center border-t border-outline-variant pt-6">
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold">Tertinggi</p>
                                <p class="text-label-xl text-primary">{{ chartMaxAmount }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold">Rata-rata Harian</p>
                                <p class="text-label-xl text-on-background">{{ chartAvgAmount }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales Table Section -->
                <div class="lg:col-span-12 mt-8">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-headline-md font-headline-md text-on-background">Riwayat Penjualan Hari Ini</h3>
                        <button @click="triggerToast('Membuka seluruh riwayat penjualan...')" class="text-primary font-bold text-label-md hover:underline">Lihat Semua</button>
                    </div>
                    
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[600px]">
                            <thead>
                                <tr class="bg-surface-container border-b border-outline-variant text-label-md text-secondary">
                                    <th class="p-4 font-semibold">Waktu</th>
                                    <th class="p-4 font-semibold">Item Terjual</th>
                                    <th class="p-4 font-semibold">Total Harga</th>
                                    <th class="p-4 font-semibold">Metode Pembayaran</th>
                                    <th class="p-4 font-semibold">Status Diskon</th>
                                </tr>
                            </thead>
                            <tbody class="text-body-md text-on-background">
                                <tr v-if="props.riwayatHariIni && props.riwayatHariIni.length === 0">
                                    <td colspan="5" class="p-8 text-center text-secondary">Belum ada transaksi hari ini.</td>
                                </tr>
                                <tr v-for="trx in props.riwayatHariIni" :key="trx.id" class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                    <td class="p-4 text-secondary">{{ trx.waktu }}</td>
                                    <td class="p-4">{{ trx.items_summary }} ({{ trx.items_count }} item)</td>
                                    <td class="p-4 font-bold">{{ formatRupiah(trx.total) }}</td>
                                    <td class="p-4">
                                        <span :class="[
                                            'px-2 py-1 rounded-md text-xs font-bold',
                                            trx.payment_method === 'QRIS' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container-highest text-on-surface'
                                        ]">{{ trx.payment_method }}</span>
                                    </td>
                                    <td class="p-4">
                                        <span v-if="trx.discount > 0" class="text-primary font-bold text-label-md">-{{ formatRupiah(trx.discount) }}</span>
                                        <span v-else class="text-secondary">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <!-- Bottom Navigation Bar (Mobile Only) -->
        <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-16 px-4 bg-surface border-t-2 border-outline-variant shadow-lg md:hidden z-30">
            <button 
                @click="setTab('dashboard')"
                :class="[
                    'flex flex-col items-center justify-center rounded-full px-4 py-1 transition-all duration-200 active:scale-90',
                    currentTab === 'dashboard' ? 'bg-primary text-on-primary font-bold' : 'text-secondary'
                ]"
            >
                <span class="material-symbols-outlined">home</span>
                <span class="text-[10px] font-semibold">Home</span>
            </button>
            <button 
                @click="setTab('inventory')"
                :class="[
                    'flex flex-col items-center justify-center rounded-full px-4 py-1 transition-all duration-200 active:scale-90',
                    currentTab === 'inventory' ? 'bg-primary text-on-primary font-bold' : 'text-secondary'
                ]"
            >
                <span class="material-symbols-outlined">apps</span>
                <span class="text-[10px] font-semibold">Inventory</span>
            </button>
            <button 
                @click="startTransaction"
                class="flex flex-col items-center justify-center rounded-full px-4 py-1 text-secondary active:scale-90 transition-all duration-200"
            >
                <span class="material-symbols-outlined">add_shopping_cart</span>
                <span class="text-[10px] font-semibold">POS</span>
            </button>
            <button 
                @click="setTab('reports')"
                :class="[
                    'flex flex-col items-center justify-center rounded-full px-4 py-1 transition-all duration-200 active:scale-90',
                    currentTab === 'reports' ? 'bg-primary text-on-primary font-bold' : 'text-secondary'
                ]"
            >
                <span class="material-symbols-outlined">assessment</span>
                <span class="text-[10px] font-semibold">Reports</span>
            </button>
        </nav>

        <!-- Abstract Industrial Background Decoration -->
        <div class="fixed top-0 right-0 w-64 h-64 opacity-[0.02] pointer-events-none transform translate-x-32 -translate-y-32 z-0">
            <svg class="text-on-background" fill="currentColor" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="50"></circle>
            </svg>
        </div>
    </div>
</template>

<style scoped>
.bento-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}
@media (min-width: 1024px) {
    .bento-grid {
        grid-template-columns: repeat(12, 1fr);
    }
}
.active-press:active {
    transform: translateY(1px);
}
</style>
