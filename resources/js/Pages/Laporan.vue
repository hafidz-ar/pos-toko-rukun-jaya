<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    period: String,
    preset: String,
    periodLabel: String,
    comparisonLabel: String,
    comparisonDateLabel: String,
    summary: Object,
    labaPerProduk: Object,
    labaPerKategori: Array,
    filters: Object,
    validationError: String,
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

// Filter states initialized from filters prop
const activePeriodTab = ref(props.filters?.period || 'bulanan');
const selectedPreset = ref(props.filters?.preset || (props.filters?.period === 'bulanan' ? 'bulan_ini' : 'hari_ini'));
const selectedDate = ref(props.filters?.date || '');
const selectedMonth = ref(props.filters?.month ? parseInt(props.filters.month) : new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year ? parseInt(props.filters.year) : new Date().getFullYear());
const customStartDate = ref(props.filters?.start_date || '');
const customEndDate = ref(props.filters?.end_date || '');

const perPage = ref(parseInt(localStorage.getItem('pos_per_page_laporan') || props.filters?.per_page || '10'));
const isFiltering = ref(false);

const setPeriodTab = (tab) => {
    activePeriodTab.value = tab;
    if (tab === 'harian') {
        selectedPreset.value = 'hari_ini';
    } else if (tab === 'mingguan') {
        selectedPreset.value = 'minggu_ini';
    } else if (tab === 'bulanan') {
        selectedPreset.value = 'bulan_ini';
    } else if (tab === 'tahunan') {
        selectedPreset.value = 'tahun_ini';
    } else {
        selectedPreset.value = '';
    }
};

const setPreset = (preset) => {
    selectedPreset.value = preset;
};

const getMonthName = (m) => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return months[m - 1];
};

const getAvailableYears = () => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let y = 2020; y <= currentYear + 1; y++) {
        years.push(y);
    }
    return years;
};

const applyFilter = () => {
    isFiltering.value = true;
    
    const query = {
        period: activePeriodTab.value,
        per_page: perPage.value || undefined,
    };

    if (activePeriodTab.value === 'harian') {
        query.preset = selectedPreset.value;
        if (selectedPreset.value === 'pilih_tanggal') {
            query.date = selectedDate.value;
        }
    } else if (activePeriodTab.value === 'mingguan') {
        query.preset = selectedPreset.value;
        if (selectedPreset.value === 'pilih_minggu') {
            query.date = selectedDate.value;
        }
    } else if (activePeriodTab.value === 'bulanan') {
        query.preset = selectedPreset.value;
        if (selectedPreset.value === 'pilih_bulan') {
            query.month = selectedMonth.value;
            query.year = selectedYear.value;
        }
    } else if (activePeriodTab.value === 'tahunan') {
        query.preset = selectedPreset.value;
        if (selectedPreset.value === 'pilih_tahun') {
            query.year = selectedYear.value;
        }
    } else if (activePeriodTab.value === 'kustom') {
        query.start_date = customStartDate.value;
        query.end_date = customEndDate.value;
    }

    router.get('/laporan', query, {
        preserveState: false,
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const applyPerPage = () => {
    localStorage.setItem('pos_per_page_laporan', perPage.value.toString());
    const query = {
        ...props.filters,
        per_page: perPage.value || undefined,
    };
    router.get('/laporan', query, { preserveState: false });
};

onMounted(() => {
    const savedPerPage = localStorage.getItem('pos_per_page_laporan');
    if (savedPerPage && !props.filters?.per_page) {
        const query = {
            ...props.filters,
            per_page: savedPerPage
        };
        router.get('/laporan', query, { replace: true, preserveState: false });
    }
});

const exportPdf = () => {
    const query = {
        period: props.filters?.period || 'bulanan',
        preset: props.filters?.preset || undefined,
        date: props.filters?.date || undefined,
        month: props.filters?.month || undefined,
        year: props.filters?.year || undefined,
        start_date: props.filters?.start_date || undefined,
        end_date: props.filters?.end_date || undefined,
    };
    
    const params = new URLSearchParams();
    Object.keys(query).forEach(key => {
        if (query[key] !== undefined && query[key] !== null) {
            params.append(key, query[key]);
        }
    });
    
    const printUrl = `/laporan/export-pdf?${params.toString()}`;

    // Remove existing iframe if any
    const existingIframe = document.getElementById('print-iframe');
    if (existingIframe) {
        existingIframe.remove();
    }

    // Create hidden iframe to load and trigger print on the same page
    const iframe = document.createElement('iframe');
    iframe.id = 'print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    iframe.style.visibility = 'hidden';
    iframe.src = printUrl;
    
    document.body.appendChild(iframe);
};
</script>

<template>
    <Head title="Laporan & Analitik - Toko Rukun Jaya" />

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
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0 z-30 print:hidden">
            <div class="px-4 py-6">
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Rukun Jaya</h1>
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
        <main class="flex-1 flex flex-col h-screen overflow-hidden print:h-auto print:overflow-visible print:bg-white">
            <!-- TopNavBar -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant print:hidden">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Laporan Keuangan</span>
                </div>
            </header>

            <!-- Dashboard Canvas -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-margin-desktop space-y-gutter pb-24 md:pb-8 print:p-0 print:overflow-visible">
                <!-- Print Only Header -->
                <div class="hidden print:flex flex-col items-center text-center border-b-4 border-double border-outline-variant pb-4 mb-6">
                    <h1 class="text-headline-lg font-bold text-primary">Toko Rukun Jaya</h1>
                    <h2 class="text-headline-md font-bold mt-1">Laporan Keuangan</h2>
                    <p class="text-body-md text-secondary mt-1">Periode: {{ props.periodLabel }}</p>
                    <p class="text-xs text-secondary mt-2">Dicetak pada: {{ new Date().toLocaleString('id-ID') }} WIB</p>
                </div>

                <!-- Page Title & Actions (Dynamic Filter Area) -->
                <div class="bg-surface-container-low border-2 border-outline-variant rounded p-6 flex flex-col gap-4 print:hidden">
                    <!-- Tab Periode -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <span class="font-bold text-on-surface text-sm">Periode:</span>
                        <div class="flex flex-wrap bg-surface-container-lowest border-2 border-outline-variant rounded p-1">
                            <button @click="setPeriodTab('harian')" :class="activePeriodTab === 'harian' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors text-sm">Harian</button>
                            <button @click="setPeriodTab('mingguan')" :class="activePeriodTab === 'mingguan' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors text-sm">Mingguan</button>
                            <button @click="setPeriodTab('bulanan')" :class="activePeriodTab === 'bulanan' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors text-sm">Bulanan</button>
                            <button @click="setPeriodTab('tahunan')" :class="activePeriodTab === 'tahunan' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors text-sm">Tahunan</button>
                            <button @click="setPeriodTab('kustom')" :class="activePeriodTab === 'kustom' ? 'bg-primary/10 text-primary border-2 border-primary' : 'text-secondary hover:bg-surface-container-low'" class="px-4 py-1.5 font-bold rounded transition-colors text-sm">Kustom</button>
                        </div>
                    </div>

                    <!-- Pilihan Presets / Inputs -->
                    <div class="flex flex-wrap items-center gap-4 bg-surface-container-lowest border border-outline-variant rounded p-4">
                        <!-- Harian Options -->
                        <template v-if="activePeriodTab === 'harian'">
                            <div class="flex flex-wrap gap-2">
                                <button @click="setPreset('hari_ini')" :class="selectedPreset === 'hari_ini' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Hari Ini</button>
                                <button @click="setPreset('kemarin')" :class="selectedPreset === 'kemarin' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Kemarin</button>
                                <button @click="setPreset('pilih_tanggal')" :class="selectedPreset === 'pilih_tanggal' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Pilih Tanggal</button>
                            </div>
                            <div v-if="selectedPreset === 'pilih_tanggal'" class="flex items-center gap-2">
                                <input type="date" v-model="selectedDate" class="h-10 bg-surface border border-outline-variant rounded px-3 text-sm focus:ring-1 focus:ring-primary focus:outline-none" />
                            </div>
                        </template>

                        <!-- Mingguan Options -->
                        <template v-if="activePeriodTab === 'mingguan'">
                            <div class="flex flex-wrap gap-2">
                                <button @click="setPreset('minggu_ini')" :class="selectedPreset === 'minggu_ini' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Minggu Ini</button>
                                <button @click="setPreset('minggu_lalu')" :class="selectedPreset === 'minggu_lalu' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Minggu Lalu</button>
                                <button @click="setPreset('pilih_minggu')" :class="selectedPreset === 'pilih_minggu' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Pilih Minggu</button>
                            </div>
                            <div v-if="selectedPreset === 'pilih_minggu'" class="flex items-center gap-2">
                                <input type="week" v-model="selectedDate" class="h-10 bg-surface border border-outline-variant rounded px-3 text-sm focus:ring-1 focus:ring-primary focus:outline-none" />
                            </div>
                        </template>

                        <!-- Bulanan Options -->
                        <template v-if="activePeriodTab === 'bulanan'">
                            <div class="flex flex-wrap gap-2">
                                <button @click="setPreset('bulan_ini')" :class="selectedPreset === 'bulan_ini' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Bulan Ini</button>
                                <button @click="setPreset('bulan_lalu')" :class="selectedPreset === 'bulan_lalu' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Bulan Lalu</button>
                                <button @click="setPreset('pilih_bulan')" :class="selectedPreset === 'pilih_bulan' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Pilih Bulan & Tahun</button>
                            </div>
                            <div v-if="selectedPreset === 'pilih_bulan'" class="flex items-center gap-2">
                                <select v-model="selectedMonth" class="h-10 bg-surface border border-outline-variant rounded px-2 text-sm focus:ring-1 focus:ring-primary focus:outline-none">
                                    <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
                                </select>
                                <select v-model="selectedYear" class="h-10 bg-surface border border-outline-variant rounded px-2 text-sm focus:ring-1 focus:ring-primary focus:outline-none">
                                    <option v-for="y in getAvailableYears()" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </template>

                        <!-- Tahunan Options -->
                        <template v-if="activePeriodTab === 'tahunan'">
                            <div class="flex flex-wrap gap-2">
                                <button @click="setPreset('tahun_ini')" :class="selectedPreset === 'tahun_ini' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Tahun Ini</button>
                                <button @click="setPreset('tahun_lalu')" :class="selectedPreset === 'tahun_lalu' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Tahun Lalu</button>
                                <button @click="setPreset('pilih_tahun')" :class="selectedPreset === 'pilih_tahun' ? 'bg-secondary-container text-on-secondary-container border border-secondary' : 'bg-surface border border-outline hover:bg-surface-container-high'" class="px-4 py-1.5 font-semibold rounded text-sm transition-colors">Pilih Tahun</button>
                            </div>
                            <div v-if="selectedPreset === 'pilih_tahun'" class="flex items-center gap-2">
                                <select v-model="selectedYear" class="h-10 bg-surface border border-outline-variant rounded px-2 text-sm focus:ring-1 focus:ring-primary focus:outline-none">
                                    <option v-for="y in getAvailableYears()" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </template>

                        <!-- Kustom Options -->
                        <template v-if="activePeriodTab === 'kustom'">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-secondary">Mulai:</span>
                                    <input type="date" v-model="customStartDate" class="h-10 bg-surface border border-outline-variant rounded px-3 text-sm focus:ring-1 focus:ring-primary focus:outline-none" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-secondary">Selesai:</span>
                                    <input type="date" v-model="customEndDate" class="h-10 bg-surface border border-outline-variant rounded px-3 text-sm focus:ring-1 focus:ring-primary focus:outline-none" />
                                </div>
                            </div>
                        </template>

                        <!-- Apply Button & Export PDF in filter section -->
                        <div class="ml-auto flex items-center gap-2">
                            <button @click="applyFilter" :disabled="isFiltering" class="bg-primary text-on-primary font-bold px-5 py-2 rounded text-sm hover:brightness-90 active:translate-y-[1px] transition-all disabled:opacity-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm" v-if="!isFiltering">filter_alt</span>
                                <span>{{ isFiltering ? 'Menerapkan...' : 'Terapkan Filter' }}</span>
                            </button>
                            <button @click="exportPdf" class="flex items-center justify-center gap-2 border-2 border-outline-variant px-4 py-2 rounded text-primary font-bold text-sm hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>Ekspor PDF
                            </button>
                        </div>
                    </div>

                    <!-- Active Report Label & Comparison Info -->
                    <div class="border-t border-outline-variant/60 pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <p class="text-sm text-secondary font-medium">
                                Periode laporan: <strong class="text-on-surface font-bold text-base">{{ props.periodLabel }}</strong>
                            </p>
                            <p class="text-xs text-secondary/80 mt-0.5">
                                Dibandingkan dengan: <strong class="text-secondary font-medium">{{ props.comparisonDateLabel }}</strong> ({{ props.comparisonLabel }})
                            </p>
                        </div>
                        <div v-if="props.validationError" class="bg-error-container text-on-error-container border border-error px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            <span>{{ props.validationError }}</span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview (4-Card Grid) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                    <!-- Total Omset Card -->
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">TOTAL OMSET</p>
                                <h2 class="text-2xl lg:text-3xl font-bold mb-1 text-on-surface">{{ formatShortRupiah(props.summary?.total_omset || 0) }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                        </div>
                        <div class="mt-4 text-xs flex items-center gap-1 flex-wrap border-t border-outline-variant/30 pt-2">
                            <span v-if="props.summary?.comparison?.omset?.status === 'increase'" class="text-green-600 font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                <span>{{ props.summary.comparison.omset.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.omset?.status === 'decrease'" class="text-error font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                <span>{{ props.summary.comparison.omset.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.omset?.status === 'new_data'" class="text-primary font-bold">
                                Data baru
                            </span>
                            <span v-else-if="props.summary?.comparison?.omset?.status === 'unchanged'" class="text-secondary font-bold">
                                Tidak berubah
                            </span>
                            <span class="text-secondary/70">
                                {{ props.summary?.comparison?.omset?.label }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Total Laba Kotor Card -->
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">TOTAL LABA KOTOR</p>
                                <h2 class="text-2xl lg:text-3xl font-bold mb-1 text-on-surface">{{ formatShortRupiah(props.summary?.total_laba_kotor || 0) }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">savings</span>
                            </div>
                        </div>
                        <div class="mt-4 text-xs flex items-center gap-1 flex-wrap border-t border-outline-variant/30 pt-2">
                            <span v-if="props.summary?.comparison?.laba_kotor?.status === 'increase'" class="text-green-600 font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                <span>{{ props.summary.comparison.laba_kotor.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.laba_kotor?.status === 'decrease'" class="text-error font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                <span>{{ props.summary.comparison.laba_kotor.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.laba_kotor?.status === 'new_data'" class="text-primary font-bold">
                                Data baru
                            </span>
                            <span v-else-if="props.summary?.comparison?.laba_kotor?.status === 'unchanged'" class="text-secondary font-bold">
                                Tidak berubah
                            </span>
                            <span class="text-secondary/70">
                                {{ props.summary?.comparison?.laba_kotor?.label }}
                            </span>
                        </div>
                    </div>

                    <!-- Margin Laba Kotor Card -->
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">MARGIN LABA KOTOR</p>
                                <h2 class="text-2xl lg:text-3xl font-bold mb-1 text-on-surface">{{ props.summary?.margin_laba_kotor || 0 }}%</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">percent</span>
                            </div>
                        </div>
                        <div class="mt-4 text-xs flex items-center gap-1 flex-wrap border-t border-outline-variant/30 pt-2">
                            <span v-if="props.summary?.comparison?.margin?.status === 'increase'" class="text-green-600 font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                <span>{{ props.summary.comparison.margin.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.margin?.status === 'decrease'" class="text-error font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                <span>{{ props.summary.comparison.margin.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.margin?.status === 'new_data'" class="text-primary font-bold">
                                Data baru
                            </span>
                            <span v-else-if="props.summary?.comparison?.margin?.status === 'unchanged'" class="text-secondary font-bold">
                                Tidak berubah
                            </span>
                            <span class="text-secondary/70">
                                {{ props.summary?.comparison?.margin?.label }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Jumlah Transaksi Card -->
                    <div class="bg-surface-container-lowest border-2 border-outline-variant p-6 rounded relative overflow-hidden group flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-label-md text-secondary font-bold uppercase tracking-wider mb-2">JUMLAH TRANSAKSI</p>
                                <h2 class="text-2xl lg:text-3xl font-bold mb-1 text-on-surface">{{ props.summary?.jumlah_transaksi || 0 }}</h2>
                            </div>
                            <div class="text-secondary">
                                <span class="material-symbols-outlined">receipt_long</span>
                            </div>
                        </div>
                        <div class="mt-4 text-xs flex items-center gap-1 flex-wrap border-t border-outline-variant/30 pt-2">
                            <span v-if="props.summary?.comparison?.jumlah_transaksi?.status === 'increase'" class="text-green-600 font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                <span>{{ props.summary.comparison.jumlah_transaksi.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.jumlah_transaksi?.status === 'decrease'" class="text-error font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                <span>{{ props.summary.comparison.jumlah_transaksi.percentage }}%</span>
                            </span>
                            <span v-else-if="props.summary?.comparison?.jumlah_transaksi?.status === 'new_data'" class="text-primary font-bold">
                                Data baru
                            </span>
                            <span v-else-if="props.summary?.comparison?.jumlah_transaksi?.status === 'unchanged'" class="text-secondary font-bold">
                                Tidak berubah
                            </span>
                            <span class="text-secondary/70">
                                {{ props.summary?.comparison?.jumlah_transaksi?.label }}
                            </span>
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
                                    <tr v-if="!props.labaPerProduk?.data || props.labaPerProduk.data.length === 0">
                                        <td colspan="5" class="px-4 py-16 text-center text-secondary bg-surface-container-lowest">
                                            <div class="flex flex-col items-center justify-center gap-2 max-w-md mx-auto">
                                                <span class="material-symbols-outlined text-display-price text-outline">query_stats</span>
                                                <p class="font-bold text-on-surface">Belum ada transaksi pada periode ini.</p>
                                                <p class="text-sm text-secondary">Coba pilih periode lain atau periksa kembali data penjualan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="produk in props.labaPerProduk.data" :key="produk.product_id" class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-4 py-4 text-on-surface">{{ produk.product_name }}</td>
                                        <td class="px-4 py-4 text-right">{{ produk.category }}</td>
                                        <td class="px-4 py-4 text-right">{{ formatShortRupiah(produk.total_revenue) }}</td>
                                        <td class="px-4 py-4 text-right">{{ formatShortRupiah(produk.total_hpp) }}</td>
                                        <td class="px-4 py-4 text-right font-bold text-tertiary">{{ formatShortRupiah(produk.total_profit) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination Bar -->
                        <div v-if="props.labaPerProduk && props.labaPerProduk.total > 0" class="p-4 bg-surface-container-low border-t-2 border-outline-variant flex items-center justify-between flex-wrap gap-4 print:hidden">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label for="products-per-page" class="text-label-md font-label-md text-secondary whitespace-nowrap">Tampilkan</label>
                                    <select id="products-per-page" v-model="perPage" @change="applyPerPage" class="h-10 bg-surface border border-outline-variant rounded px-2 text-body-md focus:ring-1 focus:ring-primary focus:outline-none">
                                        <option :value="5">5</option>
                                        <option :value="10">10</option>
                                        <option :value="20">20</option>
                                        <option :value="50">50</option>
                                    </select>
                                    <span class="text-label-md font-label-md text-secondary whitespace-nowrap">data per halaman</span>
                                </div>
                                <p class="text-label-md font-label-md text-secondary">
                                    Menampilkan {{ props.labaPerProduk.from || 0 }}–{{ props.labaPerProduk.to || 0 }} dari {{ props.labaPerProduk.total || 0 }} data
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    @click="router.get(props.labaPerProduk.prev_page_url, {}, { preserveState: false })"
                                    :disabled="!props.labaPerProduk.prev_page_url"
                                    class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="Halaman Sebelumnya">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <template v-for="link in props.labaPerProduk.links" :key="link.label">
                                    <button
                                        v-if="link.label && !String(link.label).includes('Previous') && !String(link.label).includes('Next')"
                                        @click="router.get(link.url, {}, { preserveState: false })"
                                        :class="[
                                            'w-10 h-10 flex items-center justify-center rounded font-bold text-sm transition-colors',
                                            link.active ? 'bg-primary text-on-primary' : 'border border-outline hover:bg-surface-container-high text-secondary'
                                        ]"
                                        :disabled="!link.url"
                                        :aria-label="'Halaman ' + link.label">
                                        {{ link.label }}
                                    </button>
                                </template>
                                <button
                                    @click="router.get(props.labaPerProduk.next_page_url, {}, { preserveState: false })"
                                    :disabled="!props.labaPerProduk.next_page_url"
                                    class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="Halaman Berikutnya">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
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
                                        <td colspan="3" class="px-4 py-16 text-center text-secondary bg-surface-container-lowest">
                                            <div class="flex flex-col items-center justify-center gap-2 max-w-md mx-auto">
                                                <span class="material-symbols-outlined text-display-price text-outline">category</span>
                                                <p class="font-bold text-on-surface">Belum ada transaksi pada periode ini.</p>
                                                <p class="text-sm text-secondary">Coba pilih periode lain atau periksa kembali data penjualan.</p>
                                            </div>
                                        </td>
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
        <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-16 px-4 bg-surface border-t-2 border-outline-variant shadow-lg md:hidden z-50 print:hidden">
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
