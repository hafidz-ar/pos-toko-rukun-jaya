<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TransactionDetailModal from '../Components/TransactionDetailModal.vue';
import Pagination from '../Components/Pagination.vue';
import BaseSelect from '../Components/BaseSelect.vue';

const props = defineProps({
    auth: Object,
    transactions: Object, // paginated
    cashiers: Array, // kasir list
    summary: Object, // metrik ringkasan
    filters: Object,
    validationError: String,
    selectedTransaction: Object, // untuk detail modal
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
        router.post('/logout');
    }, 800);
};

// Filter states
const search = ref(props.filters?.search || '');
const paymentMethod = ref(props.filters?.payment_method || '');
const cashier_user_id = ref(props.filters?.cashier_user_id || '');
const sort = ref(props.filters?.sort || 'latest');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const perPage = ref(parseInt(localStorage.getItem('pos_per_page_penjualan') || props.filters?.per_page || '10'));

const applyFilter = () => {
    localStorage.setItem('pos_per_page_penjualan', perPage.value.toString());
    router.get('/penjualan', {
        search: search.value || undefined,
        payment_method: paymentMethod.value || undefined,
        cashier_user_id: cashier_user_id.value || undefined,
        sort: sort.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        per_page: perPage.value || undefined,
        // Reset page to 1 on filter application
        page: undefined,
    }, { preserveState: false });
};

const resetFilter = () => {
    search.value = '';
    paymentMethod.value = '';
    cashier_user_id.value = '';
    sort.value = 'latest';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/penjualan', {
        per_page: perPage.value || undefined,
        // Reset page to 1 on filter reset
        page: undefined,
    }, { preserveState: false });
};

onMounted(() => {
    const savedPerPage = localStorage.getItem('pos_per_page_penjualan');
    if (savedPerPage && !props.filters?.per_page) {
        router.get('/penjualan', {
            search: search.value || undefined,
            payment_method: paymentMethod.value || undefined,
            cashier_user_id: cashier_user_id.value || undefined,
            sort: sort.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: savedPerPage
        }, { replace: true, preserveState: false });
    }
});

// Export state
const isExporting = ref(false);

const exportExcel = () => {
    if (isExporting.value) return;

    isExporting.value = true;

    const params = new URLSearchParams({
        search: search.value || '',
        payment_method: paymentMethod.value || '',
        cashier_user_id: cashier_user_id.value || '',
        date_from: dateFrom.value || '',
        date_to: dateTo.value || '',
        sort: sort.value || 'latest',
    });

    window.location.href = `/penjualan/export?${params.toString()}`;

    window.setTimeout(() => {
        isExporting.value = false;
    }, 2000);
};

window.addEventListener('focus', () => {
    isExporting.value = false;
});

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

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};

// Pagination
const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, { preserveState: false });
};

// Active filter labels formatting
const activeFilterContext = computed(() => {
    const parts = [];
    
    // 1. Tanggal
    if (props.filters?.date_from && props.filters?.date_to) {
        parts.push(`Rentang: ${formatDateLabel(props.filters.date_from)} s/d ${formatDateLabel(props.filters.date_to)}`);
    }
    
    // 2. Pembayaran
    if (props.filters?.payment_method && props.filters.payment_method !== 'semua') {
        parts.push(`Pembayaran: ${props.filters.payment_method === 'qris' ? 'QRIS' : 'Tunai'}`);
    }
    
    // 3. Kasir
    if (props.filters?.cashier_user_id) {
        const cashierObj = props.cashiers?.find(c => c.id == props.filters.cashier_user_id);
        if (cashierObj) {
            parts.push(`Kasir: ${cashierObj.name}`);
        } else if (props.auth?.user?.role !== 'owner') {
            parts.push(`Kasir: ${props.auth.user.name}`);
        }
    } else if (props.auth?.user?.role !== 'owner') {
        parts.push(`Kasir: ${props.auth.user.name}`);
    }
    
    // 4. Urutan
    if (props.filters?.sort) {
        const sortLabels = {
            'latest': 'Terbaru',
            'oldest': 'Terlama',
            'highest_price': 'Harga Tertinggi',
            'lowest_price': 'Harga Terendah',
        };
        parts.push(`Urutan: ${sortLabels[props.filters.sort] || 'Terbaru'}`);
    }
    
    // 5. Pencarian
    if (props.filters?.search) {
        parts.push(`Kata kunci: "${props.filters.search}"`);
    }

    return parts.length > 0 ? parts.join(' | ') : 'Menampilkan seluruh transaksi';
});

const formatDateLabel = (dateStr) => {
    if (!dateStr) return '';
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
};
</script>

<template>
    <Head title="Penjualan | Toko Rukun Jaya" />

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
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Rukun Jaya</h1>
            </div>
            
            <div class="flex flex-col gap-1 flex-1">
                <!-- Dashboard Tab -->
                <Link href="/dashboard" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </Link>

                <!-- Inventory Tab -->
                <Link href="/inventaris" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Inventaris</span>
                </Link>

                <!-- Restock Tab -->
                <Link href="/restock" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span>Restok</span>
                </Link>

                <!-- Sales Tab (Active) -->
                <Link href="/penjualan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md">
                    <span class="material-symbols-outlined">point_of_sale</span>
                    <span>Riwayat Penjualan</span>
                </Link>

                <!-- Reports Tab -->
                <Link v-if="props.auth?.user?.role === 'owner'" href="/laporan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Laporan</span>
                </Link>

                <!-- Settings Tab -->
                <Link v-if="props.auth?.user?.role === 'owner'" href="/pengaturan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Pengaturan</span>
                </Link>
            </div>

            <!-- Profile & Logout -->
            <div class="mt-auto border-t border-outline-variant pt-4 pb-2 px-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-secondary text-on-secondary flex items-center justify-center font-bold">A</div>
                        <div>
                            <p class="text-label-md font-label-md leading-none">{{ props.auth?.user?.name }}</p>
                            <p class="text-xs text-secondary mt-1">{{ props.auth?.user?.role === 'owner' ? 'Owner' : 'Karyawan' }}</p>
                        </div>
                    </div>
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
                <!-- Validation Warning Banner -->
                <div v-if="props.validationError" class="bg-error-container text-on-error-container border border-error/20 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-error">warning</span>
                    <span class="text-sm font-semibold">{{ props.validationError }}</span>
                </div>

                <!-- Filter Bar -->
                <div class="bg-surface-container-low border border-outline-variant rounded-lg p-4 space-y-4 shadow-sm">
                    <!-- Baris 1: Pencarian, Pembayaran, Kasir -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Pencarian Universal</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-2.5 text-secondary text-sm">search</span>
                                <input 
                                    v-model="search" 
                                    type="text" 
                                    placeholder="Cari ID transaksi, barang, atau kasir..." 
                                    class="w-full h-10 pl-9 pr-3 bg-surface border border-outline-variant rounded text-body-md focus:ring-1 focus:ring-primary focus:outline-none" 
                                    @keyup.enter="applyFilter"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Metode Pembayaran</label>
                            <BaseSelect v-model="paymentMethod" size="small" class="w-full">
                                <option value="">Semua Pembayaran</option>
                                <option value="tunai">Tunai</option>
                                <option value="qris">QRIS</option>
                            </BaseSelect>
                        </div>

                        <div v-if="props.auth?.user?.role === 'owner'" class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Kasir</label>
                            <BaseSelect v-model="cashier_user_id" size="small" class="w-full">
                                <option value="">Semua Kasir</option>
                                <option v-for="cashier in props.cashiers" :key="cashier.id" :value="cashier.id">
                                    {{ cashier.name }} ({{ cashier.username }})
                                </option>
                            </BaseSelect>
                        </div>
                    </div>

                    <!-- Baris 2: Tanggal & Urutan -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Dari Tanggal</label>
                            <input v-model="dateFrom" type="date" class="h-10 bg-surface border border-outline-variant rounded px-3 text-body-md focus:ring-1 focus:ring-primary focus:outline-none w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Sampai Tanggal</label>
                            <input v-model="dateTo" type="date" class="h-10 bg-surface border border-outline-variant rounded px-3 text-body-md focus:ring-1 focus:ring-primary focus:outline-none w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-secondary uppercase">Urutkan</label>
                            <BaseSelect v-model="sort" size="small" class="w-full">
                                <option value="latest">Transaksi Terbaru</option>
                                <option value="oldest">Transaksi Terlama</option>
                                <option value="highest_price">Total Harga Tertinggi</option>
                                <option value="lowest_price">Total Harga Terendah</option>
                            </BaseSelect>
                        </div>
                    </div>

                    <!-- Baris 3: Aksi tombol -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-outline-variant">
                        <div class="flex gap-2">
                            <button @click="applyFilter" class="h-10 bg-primary text-on-primary px-6 rounded font-bold hover:brightness-90 transition-all flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">filter_list</span>
                                Terapkan Filter
                            </button>
                            <button @click="resetFilter" class="h-10 border border-outline-variant bg-surface-container px-6 rounded font-bold text-secondary hover:bg-surface-container-high transition-all flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">restart_alt</span>
                                Atur Ulang
                            </button>
                        </div>
                        <div class="w-full md:w-auto">
                            <button 
                                type="button"
                                @click="exportExcel" 
                                :disabled="isExporting"
                                class="w-full md:w-auto h-10 bg-primary text-on-primary px-6 rounded font-bold hover:brightness-90 active:translate-y-[1px] transition-all flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-sm" v-if="!isExporting">download</span>
                                <span class="material-symbols-outlined text-sm animate-spin" v-else>progress_activity</span>
                                <span>{{ isExporting ? 'Menyiapkan file...' : 'Ekspor Excel' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div v-if="props.summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Card 1: Total Transaksi -->
                    <div class="bg-surface-container border border-outline-variant rounded-lg p-3 shadow-sm flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-secondary uppercase leading-none">Total Transaksi</p>
                            <p class="text-xl font-bold text-on-surface mt-1">{{ props.summary.total_count }}</p>
                        </div>
                    </div>

                    <!-- Card 2: Total Omset -->
                    <div class="bg-surface-container border border-outline-variant rounded-lg p-3 shadow-sm flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-tertiary-container text-on-tertiary-container flex items-center justify-center">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-secondary uppercase leading-none">Total Omset</p>
                            <p class="text-xl font-bold text-on-surface mt-1">{{ formatRupiah(props.summary.total_omset) }}</p>
                        </div>
                    </div>

                    <!-- Card 3: Tunai -->
                    <div class="bg-surface-container border border-outline-variant rounded-lg p-3 shadow-sm flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-surface-container-high text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-secondary uppercase leading-none">Bayar Tunai</p>
                            <p class="text-xl font-bold text-on-surface mt-1">{{ formatRupiah(props.summary.total_tunai) }}</p>
                        </div>
                    </div>

                    <!-- Card 4: QRIS -->
                    <div class="bg-surface-container border border-outline-variant rounded-lg p-3 shadow-sm flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined">qr_code_2</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-secondary uppercase leading-none">Bayar QRIS</p>
                            <p class="text-xl font-bold text-on-surface mt-1">{{ formatRupiah(props.summary.total_qris) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Konteks Filter Aktif -->
                <div class="flex items-center justify-between text-xs font-semibold text-secondary px-1 pt-1">
                    <span>{{ activeFilterContext }}</span>
                </div>

                <!-- Transactions Table -->
                <div class="industrial-border bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden border border-outline-variant">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-surface-container text-secondary text-label-md font-label-md">
                                    <th class="p-4 border-b border-outline-variant">ID / Waktu</th>
                                    <th class="p-4 border-b border-outline-variant">Barang Dibeli</th>
                                    <th class="p-4 border-b border-outline-variant">Total Harga</th>
                                    <th class="p-4 border-b border-outline-variant">Pembayaran</th>
                                    <th v-if="props.auth?.user?.role === 'owner'" class="p-4 border-b border-outline-variant">Kasir</th>
                                    <th class="p-4 border-b border-outline-variant text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody v-if="props.transactions && props.transactions.data">
                                <tr v-if="props.transactions.data.length === 0">
                                    <td :colspan="props.auth?.user?.role === 'owner' ? 6 : 5" class="p-12 text-center text-secondary">
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <span class="material-symbols-outlined text-4xl mb-2 text-outline">search_off</span>
                                            <p class="font-bold text-on-surface">Tidak ada transaksi yang sesuai dengan filter.</p>
                                            <p class="text-xs text-secondary mt-1">Coba ubah kata kunci, tanggal, atau metode pembayaran.</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="txn in props.transactions.data" :key="txn.id" class="hover:bg-primary-container/5 transition-colors group">
                                    <td class="p-4 border-b border-outline-variant">
                                        <p class="font-bold text-on-surface">TX-{{ txn.id.toString().padStart(6, '0') }}</p>
                                        <p class="text-[12px] text-secondary">{{ txn.time }} | {{ txn.date }}</p>
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant text-[12px]">{{ txn.items_summary }} ({{ txn.items_count }} item)</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-on-surface border-b border-outline-variant">
                                        {{ formatRupiah(txn.total) }}
                                    </td>
                                    <td class="p-4 border-b border-outline-variant">
                                        <span :class="[
                                            'inline-flex items-center gap-1 px-3 py-1 rounded-full text-label-md font-label-md',
                                            txn.payment_method === 'qris' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-secondary-container'
                                        ]">
                                            <span class="material-symbols-outlined text-[16px]">{{ txn.payment_method === 'qris' ? 'qr_code_2' : 'payments' }}</span>
                                            {{ txn.payment_method === 'qris' ? 'QRIS' : 'Tunai' }}
                                        </span>
                                    </td>
                                    <td v-if="props.auth?.user?.role === 'owner'" class="p-4 border-b border-outline-variant text-sm text-secondary">
                                        {{ txn.cashier || '-' }}
                                    </td>
                                    <td class="p-4 text-right border-b border-outline-variant">
                                        <button @click="openDetail(txn.id)" class="text-secondary hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="props.transactions && props.transactions.total > 0" class="p-4 bg-surface-container-low border-t border-outline-variant flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label for="transactions-per-page" class="text-label-md font-label-md text-secondary whitespace-nowrap">Tampilkan</label>
                                <BaseSelect id="transactions-per-page" v-model="perPage" @change="applyFilter" size="small" class="w-20">
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                </BaseSelect>
                                <span class="text-label-md font-label-md text-secondary whitespace-nowrap">data per halaman</span>
                            </div>
                            <p class="text-label-md font-label-md text-secondary">
                                Menampilkan {{ props.transactions.from || 0 }}–{{ props.transactions.to || 0 }} dari {{ props.transactions.total || 0 }} transaksi
                            </p>
                        </div>
                        <Pagination :links="props.transactions.links" />
                    </div>
                </div>
            </div>
            
            <!-- BottomNavBar (Mobile Only) -->
            <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-16 px-4 md:hidden bg-surface border-t-2 border-outline-variant shadow-lg z-50">
                <Link href="/dashboard" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-[10px] font-label-md">Beranda</span>
                </Link>
                <Link href="/inventaris" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">apps</span>
                    <span class="text-[10px] font-label-md">Inventaris</span>
                </Link>
                <Link href="/kasir" class="flex flex-col items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">add_shopping_cart</span>
                    <span class="text-[10px] font-label-md">POS</span>
                </Link>
                <Link href="/penjualan" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span class="text-[10px] font-label-md">Penjualan</span>
                </Link>
            </nav>
        </main>
    </div>

    <!-- Shared Transaction Detail Modal -->
    <TransactionDetailModal 
        :show="showDetailModal" 
        :transaction="detailData" 
        @close="closeDetail" 
    />
</template>
