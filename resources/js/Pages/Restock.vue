<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    restocks: Object,
    auth: Object,
    filters: Object,
});

const perPage = ref(parseInt(localStorage.getItem('pos_per_page_restok') || props.filters?.per_page || '10'));

const applyPerPage = () => {
    localStorage.setItem('pos_per_page_restok', perPage.value.toString());
    router.get('/restock', {
        per_page: perPage.value || undefined,
        product_id: props.filters?.product_id || undefined,
    }, { preserveState: false });
};

onMounted(() => {
    const savedPerPage = localStorage.getItem('pos_per_page_restok');
    if (savedPerPage && !props.filters?.per_page) {
        router.get('/restock', {
            product_id: props.filters?.product_id || undefined,
            per_page: savedPerPage
        }, { replace: true, preserveState: false });
    }
});

// Form state
const form = ref({
    product_id: '',
    qty: '',
    unit_name: '',
    cost_price_per_base_unit: '',
    location: ''
});

const selectedProduct = computed(() => {
    return props.products.find(p => p.id === form.value.product_id);
});

// Dropdown state for searchable select
const isDropdownOpen = ref(false);
const searchQuery = ref('');

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    const query = searchQuery.value.toLowerCase();
    return props.products.filter(product => {
        const sku = 'sku-' + product.id.toString().padStart(4, '0');
        return product.name.toLowerCase().includes(query) ||
            (product.location && product.location.toLowerCase().includes(query)) ||
            sku.includes(query);
    });
});

const selectProduct = (product) => {
    form.value.product_id = product.id;
    searchQuery.value = product.name;
    isDropdownOpen.value = false;
    handleProductChange();
};

const handleInputFocus = () => {
    isDropdownOpen.value = true;
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    if (selectedProduct.value) {
        searchQuery.value = selectedProduct.value.name;
    } else {
        searchQuery.value = '';
    }
};

watch(() => form.value.product_id, (newVal) => {
    if (!newVal) {
        searchQuery.value = '';
    } else {
        const prod = props.products.find(p => p.id === newVal);
        if (prod) {
            searchQuery.value = prod.name;
        }
    }
});

const availableUnits = computed(() => {
    if (!selectedProduct.value) return [];
    
    // Include base unit as default option with factor 1
    const baseUnit = {
        unit_name: selectedProduct.value.base_unit,
        conversion_factor: 1
    };
    
    return [baseUnit, ...selectedProduct.value.units];
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

// Warning Dialog State
const showWarning = ref(false);
const warnings = ref([]);
const isSubmitting = ref(false);

const handleProductChange = () => {
    form.value.unit_name = '';
    form.value.cost_price_per_base_unit = selectedProduct.value ? selectedProduct.value.cost_price_per_base_unit : '';
    form.value.location = selectedProduct.value ? selectedProduct.value.location : '';
};

const getXsrfToken = () => {
    const match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
};

const submitRestock = async () => {
    if (!form.value.product_id || !form.value.qty || !form.value.unit_name || !form.value.cost_price_per_base_unit) {
        triggerToast('Harap lengkapi semua field yang wajib.');
        return;
    }

    const selectedUnit = availableUnits.value.find(u => u.unit_name === form.value.unit_name);
    const conversionFactor = selectedUnit ? selectedUnit.conversion_factor : 1;
    const qtyBaseUnit = parseFloat(form.value.qty) * conversionFactor;

    isSubmitting.value = true;

    try {
        // Validate first using native fetch
        const response = await fetch('/restock/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getXsrfToken()
            },
            body: JSON.stringify({
                product_id: form.value.product_id,
                qty_base_unit: qtyBaseUnit,
                hpp: form.value.cost_price_per_base_unit
            })
        });
        
        const data = await response.json();

        if (data.warnings && data.warnings.length > 0) {
            warnings.value = data.warnings;
            showWarning.value = true;
            isSubmitting.value = false;
            return; // Wait for user confirmation
        }

        // No warnings, proceed to confirm
        executeSubmit(conversionFactor);

    } catch (error) {
        triggerToast('Terjadi kesalahan saat memvalidasi restock.');
        isSubmitting.value = false;
    }
};

const confirmRestock = () => {
    showWarning.value = false;
    const selectedUnit = availableUnits.value.find(u => u.unit_name === form.value.unit_name);
    const conversionFactor = selectedUnit ? selectedUnit.conversion_factor : 1;
    executeSubmit(conversionFactor);
};

const executeSubmit = (conversionFactor) => {
    router.post('/restock', {
        product_id: form.value.product_id,
        qty: form.value.qty,
        unit_name: form.value.unit_name,
        conversion_factor: conversionFactor,
        cost_price_per_base_unit: form.value.cost_price_per_base_unit,
        location: form.value.location
    }, {
        onSuccess: () => {
            triggerToast('Restock berhasil disimpan.');
            // Reset form
            form.value = {
                product_id: '',
                qty: '',
                unit_name: '',
                cost_price_per_base_unit: '',
                location: ''
            };
        },
        onError: () => {
            triggerToast('Terjadi kesalahan saat menyimpan restock.');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => {
        router.post('/logout');
    }, 800);
};

// Format currency
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
};
</script>

<template>
    <Head title="Restock Stok | Toko Rukun Jaya" />

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
        <nav class="md:hidden flex justify-between items-center w-full px-4 h-16 bg-surface border-b border-outline-variant shrink-0 z-30">
            <span class="text-xl font-bold text-primary">Toko Rukun Jaya</span>
            <div class="flex gap-4">
                <button @click="handleLogout" class="material-symbols-outlined text-error active:scale-90 transition-transform" title="Keluar">logout</button>
            </div>
        </nav>

        <!-- SIDE NAVBAR (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0 z-30">
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

                <!-- Restock Tab (Active) -->
                <Link 
                    href="/restock"
                    class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md"
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

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- TopNavBar -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Restock Stok Barang</span>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Restock Form -->
                    <div class="lg:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm h-fit">
                        <h3 class="text-lg font-bold text-primary mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined">add_circle</span> Form Restock
                        </h3>

                        <form @submit.prevent="submitRestock" class="space-y-4">
                            <div class="relative" :class="{ 'z-40': isDropdownOpen }">
                                <label class="block text-sm font-semibold text-on-surface mb-1">Produk <span class="text-error">*</span></label>
                                
                                <!-- Input Trigger -->
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="searchQuery" 
                                        @focus="handleInputFocus"
                                        @keydown.enter.prevent
                                        @keydown.esc="closeDropdown"
                                        placeholder="Ketik untuk mencari produk..." 
                                        class="w-full bg-surface border border-outline rounded-lg pl-4 pr-10 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                                        required
                                    />
                                    <span 
                                        class="absolute right-3 top-2.5 material-symbols-outlined text-on-surface-variant pointer-events-none transition-transform duration-200" 
                                        :class="{ 'rotate-180': isDropdownOpen }"
                                    >
                                        arrow_drop_down
                                    </span>
                                </div>

                                <!-- Backdrop overlay to handle click outside -->
                                <div 
                                    v-if="isDropdownOpen" 
                                    class="fixed inset-0 z-30" 
                                    @click="closeDropdown"
                                ></div>

                                <!-- Dropdown List -->
                                <div 
                                    v-if="isDropdownOpen" 
                                    class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-surface border border-outline rounded-lg shadow-lg z-40 custom-scrollbar"
                                >
                                    <ul class="py-1">
                                        <li 
                                            v-if="filteredProducts.length === 0" 
                                            class="px-4 py-2 text-sm text-secondary"
                                        >
                                            Produk tidak ditemukan
                                        </li>
                                        <li 
                                            v-for="product in filteredProducts" 
                                            :key="product.id" 
                                            @click="selectProduct(product)"
                                            :class="[
                                                'px-4 py-2 text-sm cursor-pointer hover:bg-primary/10 transition-colors flex justify-between items-center',
                                                form.product_id === product.id ? 'bg-primary/20 text-primary font-semibold' : 'text-on-surface'
                                            ]"
                                        >
                                            <span>{{ product.name }}</span>
                                            <span class="text-xs text-secondary font-normal">
                                                Stok: {{ product.stock_qty_base_unit }} {{ product.base_unit }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-on-surface mb-1">Qty <span class="text-error">*</span></label>
                                    <input v-model="form.qty" type="number" step="0.01" min="0.01" placeholder="Contoh: 10" class="w-full bg-surface border border-outline rounded-lg px-4 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-on-surface mb-1">Satuan <span class="text-error">*</span></label>
                                    <select v-model="form.unit_name" class="w-full bg-surface border border-outline rounded-lg px-4 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" required :disabled="!form.product_id">
                                        <option value="" disabled>Pilih Satuan</option>
                                        <option v-for="unit in availableUnits" :key="unit.unit_name" :value="unit.unit_name">
                                            {{ unit.unit_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-1">HPP per Unit Dasar ({{ selectedProduct ? selectedProduct.base_unit : '-' }}) <span class="text-error">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-4 top-2 text-on-surface-variant">Rp</span>
                                    <input v-model="form.cost_price_per_base_unit" type="number" step="1" min="0" placeholder="0" class="w-full bg-surface border border-outline rounded-lg pl-10 pr-4 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" required :disabled="!form.product_id">
                                </div>
                                <p v-if="selectedProduct" class="text-xs text-secondary mt-1">HPP saat ini: {{ formatRupiah(selectedProduct.cost_price_per_base_unit) }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-1">Lokasi Rak / Gudang</label>
                                <input v-model="form.location" type="text" placeholder="Contoh: Gudang Belakang" class="w-full bg-surface border border-outline rounded-lg px-4 py-2 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" :disabled="!form.product_id">
                            </div>

                            <button type="submit" :disabled="isSubmitting" class="w-full bg-primary text-on-primary py-3 rounded-full font-bold flex items-center justify-center gap-2 hover:bg-primary/90 active:scale-95 transition-all mt-6 disabled:opacity-70 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-sm" v-if="!isSubmitting">save</span>
                                <span class="material-symbols-outlined text-sm animate-spin" v-else>progress_activity</span>
                                {{ isSubmitting ? 'Memvalidasi...' : 'Simpan Restock' }}
                            </button>
                        </form>
                    </div>

                    <!-- Restock History -->
                    <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex flex-col h-[calc(100vh-140px)]">
                        <div class="p-6 border-b border-outline-variant">
                            <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined">history</span> Riwayat Restock Terbaru
                            </h3>
                        </div>
                        
                        <div class="flex-1 overflow-auto p-0">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-surface-container-low text-on-surface-variant text-sm font-bold shadow-sm z-10">
                                    <tr>
                                        <th class="py-3 px-4 border-b border-outline-variant">Waktu</th>
                                        <th class="py-3 px-4 border-b border-outline-variant">Produk</th>
                                        <th class="py-3 px-4 border-b border-outline-variant">Qty (Base Unit)</th>
                                        <th class="py-3 px-4 border-b border-outline-variant">HPP (Base Unit)</th>
                                        <th class="py-3 px-4 border-b border-outline-variant">Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!restocks?.data || restocks.data.length === 0">
                                        <td colspan="5" class="py-8 text-center text-secondary">
                                            Belum ada riwayat restock.
                                        </td>
                                    </tr>
                                    <tr v-for="item in restocks.data" :key="item.id" class="border-b border-outline-variant hover:bg-surface-container-lowest/50 transition-colors">
                                        <td class="py-3 px-4 text-sm">{{ item.datetime }}</td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-on-surface">{{ item.product_name }}</div>
                                            <div class="text-xs text-secondary">{{ item.product_category }} | {{ item.location }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-sm font-semibold">
                                            +{{ item.qty_base_unit }} {{ item.base_unit }}
                                            <div class="text-xs text-secondary font-normal">(dalam {{ item.unit_name }})</div>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-primary font-bold">
                                            {{ formatRupiah(item.hpp) }}
                                        </td>
                                        <td class="py-3 px-4 text-sm text-secondary">{{ item.user }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination Bar -->
                        <div v-if="restocks && restocks.total > 0" class="p-4 bg-surface-container-low border-t border-outline-variant flex items-center justify-between flex-wrap gap-4 rounded-b-2xl">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label for="restocks-per-page" class="text-label-md font-label-md text-secondary whitespace-nowrap">Tampilkan</label>
                                    <select id="restocks-per-page" v-model="perPage" @change="applyPerPage" class="h-10 bg-surface border border-outline-variant rounded px-2 text-body-md focus:ring-1 focus:ring-primary focus:outline-none">
                                        <option :value="5">5</option>
                                        <option :value="10">10</option>
                                        <option :value="20">20</option>
                                        <option :value="50">50</option>
                                    </select>
                                    <span class="text-label-md font-label-md text-secondary whitespace-nowrap">data per halaman</span>
                                </div>
                                <p class="text-label-md font-label-md text-secondary">
                                    Menampilkan {{ restocks.from || 0 }}–{{ restocks.to || 0 }} dari {{ restocks.total || 0 }} data
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    @click="router.get(restocks.prev_page_url, { per_page: perPage }, { preserveState: false })"
                                    :disabled="!restocks.prev_page_url"
                                    class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="Halaman Sebelumnya">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <template v-for="link in restocks.links" :key="link.label">
                                <button
                                    v-if="link.label && !String(link.label).includes('Previous') && !String(link.label).includes('Next')"
                                        @click="router.get(link.url, { per_page: perPage }, { preserveState: false })"
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
                                    @click="router.get(restocks.next_page_url, { per_page: perPage }, { preserveState: false })"
                                    :disabled="!restocks.next_page_url"
                                    class="w-10 h-10 flex items-center justify-center rounded border border-outline hover:bg-surface-container-high transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="Halaman Berikutnya">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Warning Modal for Anomalies -->
        <div v-if="showWarning" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-surface-container-lowest rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="bg-error-container p-6 flex flex-col items-center justify-center text-center">
                    <span class="material-symbols-outlined text-5xl text-error mb-2">warning</span>
                    <h3 class="text-xl font-bold text-on-error-container">Peringatan Restock!</h3>
                </div>
                
                <div class="p-6">
                    <p class="text-on-surface mb-4">Sistem mendeteksi anomali pada input restock Anda:</p>
                    
                    <ul class="space-y-3 mb-6">
                        <li v-for="(warn, idx) in warnings" :key="idx" class="flex gap-3 text-sm bg-error-container/20 text-on-surface p-3 rounded-lg border border-error/20">
                            <span class="material-symbols-outlined text-error text-xl shrink-0">error</span>
                            <span>{{ warn.message }}</span>
                        </li>
                    </ul>
                    
                    <p class="text-sm text-secondary mb-6 italic">
                        Notifikasi akan dikirimkan ke Telegram Owner jika Anda melanjutkan.
                    </p>
                    
                    <div class="flex gap-3 w-full">
                        <button @click="showWarning = false" class="flex-1 py-2.5 rounded-full font-bold text-on-surface border border-outline hover:bg-surface-container-high transition-colors">
                            Batal
                        </button>
                        <button @click="confirmRestock" class="flex-1 py-2.5 rounded-full font-bold bg-error text-on-error hover:bg-error/90 transition-colors shadow-sm">
                            Tetap Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
            <Link href="/restock" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1 cursor-pointer">
                <span class="material-symbols-outlined">local_shipping</span>
                <span class="text-[10px] font-bold">Restok</span>
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
    background: var(--color-outline-variant, #c4c7c8);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--color-outline, #74777f);
}
</style>
