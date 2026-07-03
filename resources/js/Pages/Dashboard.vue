<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TransactionDetailModal from '../Components/TransactionDetailModal.vue';

const props = defineProps({
    auth: Object,
    period: String,
    chartPeriod: String,
    todayCount: Number,
    todayOmset: Number,
    totalOmset: Number,
    jumlahTransaksi: Number,
    totalLabaKotor: Number,
    labaPerProduk: Array,
    labaPerKategori: Array,
    criticalStockCount: Number,
    lowStockCount: Number,
    outOfStockCount: Number,
    criticalProducts: Array,
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
            label: d.label,
            date: d.date,
            amount: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(d.amount),
            rawAmount: d.amount,
            count: d.count,
            full_date: d.full_date,
            x: (index / Math.max(1, data.length - 1)) * 700,
            y: 200 - ((d.amount / maxAmount) * 200) // 200 max y down (Rp 0), 0 min y up (Rp max)
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

// Adaptive Tooltip boundary logic
const getTooltipStyle = (idx, total) => {
    const fraction = idx / Math.max(1, total - 1);
    if (fraction < 0.25) {
        return { left: '12px', transform: 'none' };
    } else if (fraction > 0.75) {
        return { right: '12px', left: 'auto', transform: 'none' };
    } else {
        return { left: '50%', transform: 'translateX(-50%)' };
    }
};

// Chart Period changing via Inertia partial reload
const chartPeriodRef = ref(props.chartPeriod || '7_hari');
const isChartLoading = ref(false);

const changeChartPeriod = (period) => {
    chartPeriodRef.value = period;
    isChartLoading.value = true;
    router.get('/dashboard', {
        period: props.period,
        chart_period: period,
    }, {
        only: ['chartData', 'chartPeriod'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isChartLoading.value = false;
        }
    });
};

// Detail transaksi modal
const showDetailModal = ref(false);
const detailData = ref(null);
const isLoadingDetail = ref(false);

const openDetail = (txnId) => {
    isLoadingDetail.value = true;
    showDetailModal.value = true;
    detailData.value = null;

    fetch(`/penjualan/${txnId}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        }
    })
        .then(r => r.json())
        .then(data => {
            detailData.value = data;
        })
        .catch(() => {
            triggerToast('Gagal memuat detail transaksi.');
            showDetailModal.value = false;
        })
        .finally(() => {
            isLoadingDetail.value = false;
        });
};

const closeDetail = () => {
    showDetailModal.value = false;
    detailData.value = null;
};

// Logout handler
const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.post('/logout');
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
    } else if (tab === 'reports') {
        router.visit('/laporan');
    } else if (tab === 'settings') {
        router.visit('/pengaturan');
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

const openOwnerMenu = (menuName, path) => {
    if (path) {
        router.visit(path);
    } else {
        triggerToast(`Menu "${menuName}" hanya untuk Owner.`);
    }
};
</script>

<template>
    <Head title="Dashboard | Toko Rukun Jaya" />

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
            <span class="text-headline-md font-headline-md font-bold text-primary">Toko Rukun Jaya</span>
            <div class="flex gap-4">
                <button @click="triggerToast('Profil Admin')" class="material-symbols-outlined text-secondary active:scale-90 transition-transform">account_circle</button>
                <button @click="handleLogout" class="material-symbols-outlined text-error active:scale-90 transition-transform" title="Keluar">logout</button>
            </div>
        </nav>

        <!-- Side Navigation Bar (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0">
            <div class="px-4 py-6">
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Rukun Jaya</h1>
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
                    v-if="props.auth?.user?.role === 'owner'"
                    href="/laporan"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md"
                >
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Laporan</span>
                </Link>

                <!-- Settings Tab -->
                <Link 
                    v-if="props.auth?.user?.role === 'owner'"
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
                            <p class="text-body-lg font-body-lg text-secondary mt-1">Mulai transaksi penjualan baru.</p>
                            <div class="mt-4 pt-3 border-t border-outline-variant/30 flex flex-wrap gap-4 text-xs font-semibold text-secondary">
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                                    <span class="font-bold text-on-surface">{{ props.todayCount }}</span> Transaksi hari ini
                                </div>
                                <div class="w-px h-4 bg-outline-variant/40 hidden sm:block"></div>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">payments</span>
                                    <span>Omzet hari ini: <span class="font-bold text-primary">{{ formatRupiah(props.todayOmset) }}</span></span>
                                </div>
                            </div>
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
                            <p class="text-body-md font-body-md text-secondary mt-1">Cek ketersediaan produk.</p>
                            <div class="mt-4 pt-3 border-t border-outline-variant/30 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold text-secondary">
                                <div class="flex items-center gap-1 text-error" v-if="props.outOfStockCount > 0">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                    <span><span class="font-bold">{{ props.outOfStockCount }}</span> Habis</span>
                                </div>
                                <div class="flex items-center gap-1 text-warning" v-if="props.lowStockCount > 0">
                                    <span class="material-symbols-outlined text-[16px]">warning</span>
                                    <span><span class="font-bold">{{ props.lowStockCount }}</span> Perlu Restok</span>
                                </div>
                                <div class="flex items-center gap-1 text-success" v-if="props.criticalStockCount === 0">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    <span>Stok Aman</span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
 
                <div v-if="props.auth?.user?.role === 'owner'" class="lg:col-span-6 group">
                    <button 
                        @click="router.visit('/laporan')"
                        class="w-full h-full text-left bg-surface-container-lowest border-2 border-outline-variant hover:border-primary transition-all duration-300 p-8 rounded-xl flex items-center gap-6 active-press min-h-[160px] cursor-pointer"
                    >
                        <div class="bg-primary text-on-primary w-14 h-14 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined !text-[28px]">analytics</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-label-xl font-label-xl text-on-background">LAPORAN</h3>
                            </div>
                            <p class="text-body-md font-body-md text-secondary">Analisa penjualan & laba rugi bulanan.</p>
                        </div>
                    </button>
                </div>
 
                <div v-if="props.auth?.user?.role === 'owner'" class="lg:col-span-6 group">
                    <button 
                        @click="router.visit('/pengaturan')"
                        class="w-full h-full text-left bg-surface-container-lowest border-2 border-outline-variant hover:border-primary transition-all duration-300 p-8 rounded-xl flex items-center gap-6 active-press min-h-[160px] cursor-pointer"
                    >
                        <div class="bg-primary text-on-primary w-14 h-14 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined !text-[28px]">settings_suggest</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-label-xl font-label-xl text-on-background">PENGATURAN</h3>
                            </div>
                            <p class="text-body-md font-body-md text-secondary">Kelola user, kategori, backup, dan Telegram.</p>
                        </div>
                    </button>
                </div>
 
                <!-- Quick Stats Section -->
                <div v-if="props.auth?.user?.role === 'owner'" class="lg:col-span-12 mt-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        
                        <!-- Transaksi Minggu Ini -->
                        <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex items-center gap-4 shadow-sm hover:bg-surface-container-high transition-colors">
                            <div class="p-3 bg-secondary-container rounded-lg shrink-0 text-on-secondary-container">
                                <span class="material-symbols-outlined">assignment_turned_in</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-secondary uppercase tracking-wider leading-none">Transaksi Minggu Ini</p>
                                <p class="text-headline-md font-headline-md mt-1">{{ props.jumlahTransaksi }} Nota</p>
                            </div>
                        </div>
                        
                        <!-- Omset Minggu Ini -->
                        <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex items-center gap-4 shadow-sm hover:bg-surface-container-high transition-colors">
                            <div class="p-3 bg-tertiary-fixed rounded-lg shrink-0 text-on-tertiary-fixed-variant">
                                <span class="material-symbols-outlined">trending_up</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-secondary uppercase tracking-wider leading-none">Omset Minggu Ini</p>
                                <p class="text-headline-md font-headline-md mt-1">{{ formatRupiah(props.totalOmset) }}</p>
                            </div>
                        </div>
                        
                        <!-- Laba Kotor Minggu Ini -->
                        <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex items-center gap-4 shadow-sm hover:bg-surface-container-high transition-colors">
                            <div class="p-3 bg-primary-container rounded-lg shrink-0 text-on-primary-container">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-secondary uppercase tracking-wider leading-none">Laba Kotor Minggu Ini</p>
                                <p class="text-headline-md font-headline-md mt-1">{{ formatRupiah(props.totalLabaKotor) }}</p>
                            </div>
                        </div>
                        
                        <!-- Stok Menipis -->
                        <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex items-center gap-4 shadow-sm hover:bg-surface-container-high transition-colors cursor-pointer" @click="router.visit('/inventaris')">
                            <div class="p-3 bg-error-container rounded-lg shrink-0 text-on-error-container">
                                <span class="material-symbols-outlined">warning</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-secondary uppercase tracking-wider leading-none">Stok Menipis</p>
                                <p class="text-headline-md font-headline-md text-error mt-1">{{ props.criticalStockCount }} Produk</p>
                            </div>
                        </div>

                    </div>
                </div>
 
                <!-- Sales Trend History Section (Interactive SVG) -->
                <div v-if="props.auth?.user?.role === 'owner'" class="lg:col-span-12 mt-8">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 relative">
                        <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
                            <h3 class="text-headline-md font-headline-md text-on-background font-bold">Tren Penjualan</h3>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex bg-surface-container rounded-lg p-0.5 border border-outline-variant text-xs font-semibold">
                                    <button 
                                        @click="changeChartPeriod('7_hari')"
                                        :class="[
                                            'px-3 py-1.5 rounded-md transition-all cursor-pointer',
                                            chartPeriodRef === '7_hari' ? 'bg-primary text-on-primary shadow font-bold' : 'text-secondary hover:text-on-surface'
                                        ]"
                                    >
                                        7 Hari
                                    </button>
                                    <button 
                                        @click="changeChartPeriod('30_hari')"
                                        :class="[
                                            'px-3 py-1.5 rounded-md transition-all cursor-pointer',
                                            chartPeriodRef === '30_hari' ? 'bg-primary text-on-primary shadow font-bold' : 'text-secondary hover:text-on-surface'
                                        ]"
                                    >
                                        30 Hari
                                    </button>
                                    <button 
                                        @click="changeChartPeriod('bulan_ini')"
                                        :class="[
                                            'px-3 py-1.5 rounded-md transition-all cursor-pointer',
                                            chartPeriodRef === 'bulan_ini' ? 'bg-primary text-on-primary shadow font-bold' : 'text-secondary hover:text-on-surface'
                                        ]"
                                    >
                                        Bulan Ini
                                    </button>
                                </div>
                            </div>
                        </div>
 
                        <!-- Chart with Y-Axis and SVG Area -->
                        <div class="relative w-full">
                            
                            <!-- Loading Overlay -->
                            <div v-if="isChartLoading" class="absolute inset-0 bg-surface-container-lowest/70 backdrop-blur-[1px] z-10 flex items-center justify-center text-secondary text-sm font-semibold gap-2">
                                <span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>
                                <span>Memuat data tren...</span>
                            </div>

                            <!-- Empty State: All Data Zero -->
                            <div v-if="!isChartLoading && chartPoints.length === 0" class="flex flex-col items-center justify-center py-16 text-secondary text-center border-2 border-dashed border-outline-variant rounded-lg">
                                <span class="material-symbols-outlined text-4xl mb-2 text-outline">query_stats</span>
                                <p class="font-bold text-on-surface">Belum ada transaksi pada periode ini.</p>
                                <p class="text-xs text-secondary mt-1">Mulai lakukan transaksi untuk melihat tren penjualan.</p>
                            </div>

                            <div v-else class="flex gap-4 h-64 w-full relative select-none">
                                <!-- Y-axis Labels -->
                                <div class="flex flex-col justify-between text-[10px] md:text-xs text-secondary font-semibold h-full pr-2 border-r border-outline-variant/30 text-right shrink-0 w-[80px] select-none">
                                    <span class="h-0 flex items-center justify-end">{{ chartMaxAmount }}</span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span class="h-0 flex items-center justify-end">Rp 0</span>
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
 
                                    <svg class="absolute inset-0 w-full h-full" style="overflow: visible;" preserveAspectRatio="none" viewBox="0 0 700 200">
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
 
                                            <!-- Adaptive Tooltip -->
                                            <div 
                                                v-if="hoveredPoint && hoveredPoint.label === point.label" 
                                                class="absolute bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded-lg text-xs font-semibold shadow-md whitespace-nowrap z-20 pointer-events-none transition-all duration-155 text-left border border-outline animate-fade-in"
                                                :style="[{ top: `calc(${Math.max(0, Math.min(100, point.y / 2))}% - 75px)` }, getTooltipStyle(idx, chartPoints.length)]"
                                            >
                                                <p class="font-bold text-primary-fixed mb-1">{{ point.full_date }}</p>
                                                <p class="leading-relaxed">Penjualan: <span class="font-bold text-on-primary-fixed">{{ point.amount }}</span></p>
                                                <p class="leading-relaxed">Jumlah Transaksi: <span class="font-bold text-primary-fixed">{{ point.count }} Nota</span></p>
                                            </div>
 
                                            <!-- Small dot on line path -->
                                            <div 
                                                class="absolute w-3 h-3 rounded-full border-2 border-surface bg-primary transition-all duration-150 pointer-events-none left-1/2 -translate-x-1/2"
                                                :style="{ top: `calc(${Math.max(0, Math.min(100, point.y / 2))}% - 6px)` }"
                                                :class="{ 'scale-150 shadow-md ring-2 ring-primary-fixed': hoveredPoint && hoveredPoint.label === point.label }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
 
                        <!-- Axis Labels -->
                        <div v-if="chartPoints.length > 0" class="flex gap-4 mt-8 text-[10px] md:text-xs text-secondary font-semibold pt-2 border-t border-outline-variant/30">
                            <!-- Spacer to align with Y-axis -->
                            <div class="w-[80px] shrink-0 border-r border-transparent"></div>
                            <!-- X-axis Labels -->
                            <div class="flex-1 relative h-6">
                                <span 
                                    v-for="(point, idx) in chartPoints" 
                                    :key="point.label" 
                                    class="absolute text-secondary font-semibold -translate-x-1/2 whitespace-nowrap"
                                    :style="{ left: `${(idx * 100) / Math.max(1, chartPoints.length - 1)}%` }"
                                >
                                    {{ point.day }}
                                </span>
                            </div>
                        </div>
 
                        <!-- Mini Stats Summary -->
                        <div v-if="chartPoints.length > 0" class="mt-8 flex justify-around text-center border-t border-outline-variant pt-6">
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold">Tertinggi</p>
                                <p class="text-label-xl text-primary font-bold">{{ chartMaxAmount }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-secondary uppercase font-bold">Rata-rata Harian</p>
                                <p class="text-label-xl text-on-background font-bold">{{ chartAvgAmount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
 
                <!-- Recent Sales Table Section -->
                <div class="lg:col-span-8 mt-8">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-headline-md font-headline-md text-on-background font-bold">Riwayat Penjualan Hari Ini</h3>
                        <button @click="router.visit('/penjualan')" class="text-primary font-bold text-label-md hover:underline cursor-pointer">Lihat Semua</button>
                    </div>
                    
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[500px]">
                            <thead>
                                <tr class="bg-surface-container border-b border-outline-variant text-label-md text-secondary">
                                    <th class="p-4 font-semibold">Waktu</th>
                                    <th class="p-4 font-semibold">Item Terjual</th>
                                    <th class="p-4 font-semibold">Total Harga</th>
                                    <th class="p-4 font-semibold">Pembayaran</th>
                                    <th class="p-4 font-semibold">Kasir</th>
                                    <th class="p-4 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-body-md text-on-background">
                                <tr v-if="props.riwayatHariIni && props.riwayatHariIni.length === 0">
                                    <td colspan="6" class="p-8 text-center text-secondary">Belum ada transaksi hari ini.</td>
                                </tr>
                                <tr v-for="trx in props.riwayatHariIni" :key="trx.id" class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                                    <td class="p-4 text-secondary">{{ trx.waktu }}</td>
                                    <td class="p-4 text-sm">{{ trx.items_summary }} ({{ trx.items_count }} item)</td>
                                    <td class="p-4 font-bold text-primary">{{ formatRupiah(trx.total) }}</td>
                                    <td class="p-4">
                                        <span :class="[
                                            'px-2.5 py-0.5 rounded text-[11px] font-bold uppercase',
                                            trx.payment_method === 'qris' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container-highest text-on-surface'
                                        ]">{{ trx.payment_method?.toUpperCase() }}</span>
                                    </td>
                                    <td class="p-4 text-secondary text-sm">{{ trx.cashier }}</td>
                                    <td class="p-4 text-center">
                                        <button @click="openDetail(trx.id)" class="text-secondary hover:text-primary transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
 
                <!-- Perlu Perhatian Section -->
                <div class="lg:col-span-4 mt-8 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-headline-md font-headline-md text-on-background font-bold">Perlu Perhatian</h3>
                        <button @click="router.visit('/inventaris')" class="text-error font-bold text-label-md hover:underline cursor-pointer">Lihat Semua</button>
                    </div>
                    
                    <div class="bg-surface-container p-6 rounded-xl border border-outline-variant flex-1 flex flex-col justify-between min-h-[300px]">
                        <div class="space-y-4">
                            <!-- All Stok Safe Empty State -->
                            <div v-if="props.criticalProducts && props.criticalProducts.length === 0" class="text-sm text-secondary py-8 text-center flex flex-col items-center">
                                <span class="material-symbols-outlined text-success text-5xl mb-2">check_circle</span>
                                <p class="font-bold text-on-surface">✓ Semua stok dalam kondisi aman</p>
                                <p class="text-xs text-secondary mt-1">Tidak ada produk yang perlu direstok saat ini.</p>
                            </div>
 
                            <div v-else class="space-y-3.5">
                                <div v-for="item in props.criticalProducts" :key="item.id" class="flex items-start justify-between gap-3 text-sm border-b border-outline-variant/30 pb-3 last:border-0 last:pb-0">
                                    <div class="flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-error shrink-0 mt-0.5" style="font-size: 20px;">
                                            {{ item.stock <= 0 ? 'cancel' : 'warning' }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-on-surface leading-tight hover:underline cursor-pointer" @click="router.visit('/inventaris', { search: item.name })">{{ item.name }}</p>
                                            <p class="text-xs text-secondary mt-1">
                                                Sisa: <span :class="item.stock <= 0 ? 'text-error font-bold' : 'text-on-surface font-semibold'">{{ item.stock }}</span> {{ item.base_unit }} 
                                                dari minimum {{ item.threshold }} {{ item.base_unit }}
                                            </p>
                                        </div>
                                    </div>
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold shrink-0 uppercase',
                                        item.stock <= 0 ? 'bg-error-container text-on-error-container border border-error/20' : 'bg-warning-container text-on-warning-container border border-warning/20'
                                    ]">
                                        {{ item.stock <= 0 ? 'HABIS' : 'MENIPIS' }}
                                    </span>
                                </div>
                            </div>
                        </div>
 
                        <!-- Footer navigasi / count info -->
                        <div class="mt-6 pt-4 border-t border-outline-variant/40">
                            <p class="text-xs text-secondary mb-3 text-center" v-if="props.criticalStockCount > 5">
                                Menampilkan 5 dari <span class="font-bold text-error">{{ props.criticalStockCount }}</span> produk stok menipis
                            </p>
                            <button 
                                @click="router.visit('/inventaris')" 
                                class="w-full h-10 border border-outline-variant bg-surface-container-low hover:bg-surface-container-high rounded text-xs font-bold text-secondary transition-all cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                                <span>Lihat Semua Inventaris</span>
                            </button>
                        </div>
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

        <!-- Shared Transaction Detail Modal -->
        <TransactionDetailModal 
            :show="showDetailModal" 
            :transaction="detailData" 
            @close="closeDetail" 
        />
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
