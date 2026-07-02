<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    products: Array,
    categories: Array,
    filters: Object,
});

// Toast
const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;
const triggerToast = (message) => {
    toastMessage.value = message;
    showToast.value = true;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => { showToast.value = false; }, 3000);
};

const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => { router.post('/logout'); }, 800);
};

// Polling stok tiap 10 detik
let pollInterval = null;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['products'],
            preserveState: true,
            preserveScroll: true
        });
    }, 10000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// ───────── Filter / Search ─────────
const search = ref(props.filters?.search || '');
const categoryId = ref(props.filters?.category_id || '');
const lowStockOnly = ref(props.filters?.low_stock || false);

const applyFilter = () => {
    router.get('/inventaris', {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        low_stock: lowStockOnly.value || undefined,
    }, { preserveState: false });
};

const resetFilter = () => {
    search.value = '';
    categoryId.value = '';
    lowStockOnly.value = false;
    router.get('/inventaris', {}, { preserveState: false });
};

// ───────── Stats dari data real ─────────
const totalProducts = computed(() => props.products?.length || 0);
const lowStockCount = computed(() => props.products?.filter(p => p.is_low_stock).length || 0);
const categoryCount = computed(() => props.categories?.length || 0);
const totalValuation = computed(() => {
    return props.products?.reduce((sum, p) => sum + (p.stock_qty_base_unit * p.cost_price_per_base_unit), 0) || 0;
});

const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
const formatShortRupiah = (v) => {
    if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + 'M';
    if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + 'Jt';
    return formatRupiah(v);
};

// ───────── Modal Tambah Produk ─────────
const showAddModal = ref(false);
const addForm = reactive({
    name: '',
    category_id: '',
    base_unit: '',
    cost_price_per_base_unit: '',
    selling_price_per_base_unit: '',
    stock_qty_base_unit: 0,
    location: '',
    photo_url: '',
    photo_file: null,
    min_stock_threshold: 10,
    units: [],
});
const addErrors = ref({});
const isAddSubmitting = ref(false);
const addPhotoPreviewUrl = ref('');

const handleAddPhotoChange = (e) => {
    const file = e.target.files[0];
    addForm.photo_file = file;
    if (file) {
        addPhotoPreviewUrl.value = URL.createObjectURL(file);
    } else {
        addPhotoPreviewUrl.value = '';
    }
};

const openAddModal = () => {
    Object.assign(addForm, {
        name: '', category_id: '', base_unit: '', cost_price_per_base_unit: '',
        selling_price_per_base_unit: '', stock_qty_base_unit: 0, location: '',
        photo_url: '', photo_file: null, min_stock_threshold: 10, units: [],
    });
    addPhotoPreviewUrl.value = '';
    addErrors.value = {};
    showAddModal.value = true;
};

const addUnit = () => {
    addForm.units.push({ unit_name: '', conversion_factor: '' });
};
const removeUnit = (i) => {
    addForm.units.splice(i, 1);
};

const submitAdd = () => {
    isAddSubmitting.value = true;
    addErrors.value = {};
    router.post('/products', addForm, {
        onSuccess: () => {
            showAddModal.value = false;
            addPhotoPreviewUrl.value = '';
            triggerToast('Produk berhasil ditambahkan!');
        },
        onError: (errors) => {
            addErrors.value = errors;
        },
        onFinish: () => { isAddSubmitting.value = false; },
    });
};

// ───────── Modal Edit Produk ─────────
const showEditModal = ref(false);
const editForm = reactive({
    id: null,
    name: '',
    category_id: '',
    base_unit: '',
    selling_price_per_base_unit: '',
    location: '',
    photo_url: '',
    photo_file: null,
    min_stock_threshold: 10,
    units: [],
});
const editErrors = ref({});
const isEditSubmitting = ref(false);
const editPhotoPreviewUrl = ref('');

const handleEditPhotoChange = (e) => {
    const file = e.target.files[0];
    editForm.photo_file = file;
    if (file) {
        editPhotoPreviewUrl.value = URL.createObjectURL(file);
    } else {
        editPhotoPreviewUrl.value = '';
    }
};

const openEditModal = (product) => {
    Object.assign(editForm, {
        id: product.id,
        name: product.name,
        category_id: product.category_id,
        base_unit: product.base_unit,
        selling_price_per_base_unit: product.selling_price_per_base_unit,
        location: product.location || '',
        photo_url: product.photo_url || '',
        photo_file: null,
        min_stock_threshold: product.min_stock_threshold || 10,
        units: (product.units || []).map(u => ({ id: u.id, unit_name: u.unit_name, conversion_factor: u.conversion_factor })),
    });
    editPhotoPreviewUrl.value = '';
    editErrors.value = {};
    showEditModal.value = true;
};

const addEditUnit = () => {
    editForm.units.push({ id: null, unit_name: '', conversion_factor: '' });
};
const removeEditUnit = (i) => {
    editForm.units.splice(i, 1);
};

const submitEdit = () => {
    isEditSubmitting.value = true;
    editErrors.value = {};

    // Spoof PUT via POST for multipart data support
    const formData = {
        _method: 'PUT',
        name: editForm.name,
        category_id: editForm.category_id,
        base_unit: editForm.base_unit,
        selling_price_per_base_unit: editForm.selling_price_per_base_unit,
        location: editForm.location,
        photo_url: editForm.photo_url,
        photo_file: editForm.photo_file,
        min_stock_threshold: editForm.min_stock_threshold,
        units: editForm.units,
    };

    router.post(`/products/${editForm.id}`, formData, {
        onSuccess: () => {
            showEditModal.value = false;
            triggerToast('Produk berhasil diperbarui!');
        },
        onError: (errors) => {
            editErrors.value = errors;
        },
        onFinish: () => { isEditSubmitting.value = false; },
    });
};

// ───────── Hapus Produk ─────────
const confirmDelete = (product) => {
    if (confirm(`Nonaktifkan produk "${product.name}"? Produk tidak akan muncul lagi di kasir.`)) {
        router.delete(`/products/${product.id}`, {
            onSuccess: () => triggerToast('Produk berhasil dinonaktifkan.'),
        });
    }
};

// ───────── Riwayat Stok Modal ─────────
const showMovementsModal = ref(false);
const movementsProduct = ref(null);
const movements = ref([]);
const isLoadingMovements = ref(false);

const openMovements = (product) => {
    movementsProduct.value = product;
    movements.value = [];
    isLoadingMovements.value = true;
    showMovementsModal.value = true;

    fetch(`/inventaris/${product.id}/movements`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        }
    })
        .then(r => r.json())
        .then(data => { movements.value = data; })
        .catch(() => triggerToast('Gagal memuat riwayat stok.'))
        .finally(() => { isLoadingMovements.value = false; });
};
const closeMovements = () => {
    showMovementsModal.value = false;
    movementsProduct.value = null;
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
                <Link href="/dashboard" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </Link>
                <Link href="/inventaris" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <span>Inventaris</span>
                </Link>
                <Link href="/restock" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span>Restok</span>
                </Link>
                <Link href="/penjualan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">point_of_sale</span>
                    <span>Penjualan</span>
                </Link>
                <Link href="/laporan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">analytics</span>
                    <span>Laporan</span>
                </Link>
                <Link href="/pengaturan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Pengaturan</span>
                </Link>
            </div>

            <div class="mt-auto border-t border-outline-variant pt-4 pb-2 px-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-secondary text-on-secondary flex items-center justify-center font-bold">A</div>
                        <div>
                            <p class="text-label-md font-label-md leading-none">{{ props.auth?.user?.name }}</p>
                            <p class="text-xs text-secondary mt-1">{{ props.auth?.user?.role === 'owner' ? 'Owner' : 'Karyawan' }}</p>
                        </div>
                    </div>
                    <button @click="handleLogout" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Keluar dari sistem">logout</button>
                </div>
                <button @click="router.visit('/kasir')" class="w-full bg-primary text-on-primary font-bold min-h-[48px] rounded hover:brightness-90 active:translate-y-[1px] transition-all cursor-pointer">
                    Transaksi Baru
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col min-w-0 bg-background overflow-hidden">
            <!-- TOP NAVBAR -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant shrink-0">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Daftar Inventori</span>
                </div>
            </header>

            <!-- CONTENT BODY -->
            <div class="flex-1 overflow-y-auto p-margin-desktop space-y-6 pb-20 md:pb-6">

                <!-- FILTER BAR -->
                <div class="bg-surface-container-low border border-outline-variant rounded p-4 flex flex-wrap gap-3 items-end">
                    <div class="flex flex-col gap-1 flex-1 min-w-[160px]">
                        <label class="text-xs font-bold text-secondary uppercase">Cari Produk</label>
                        <input v-model="search" @keyup.enter="applyFilter" type="text" placeholder="Nama, lokasi, atau kategori..." class="h-10 bg-surface border border-outline-variant rounded px-3 text-body-md focus:ring-1 focus:ring-primary focus:outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-secondary uppercase">Kategori</label>
                        <select v-model="categoryId" class="h-10 bg-surface border border-outline-variant rounded px-3 text-body-md focus:ring-1 focus:ring-primary focus:outline-none">
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <label class="flex items-center gap-2 h-10 cursor-pointer">
                            <input v-model="lowStockOnly" type="checkbox" class="w-4 h-4" />
                            <span class="text-sm font-bold text-secondary">Stok Rendah</span>
                        </label>
                    </div>
                    <button @click="applyFilter" class="h-10 bg-primary text-on-primary px-4 rounded font-bold hover:brightness-90 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">search</span> Cari
                    </button>
                    <button v-if="filters?.search || filters?.category_id || filters?.low_stock" @click="resetFilter" class="h-10 border border-outline-variant bg-surface-container px-4 rounded font-bold text-secondary hover:bg-surface-container-high transition-all">
                        Reset
                    </button>
                    <!-- Tombol Tambah (Owner only) -->
                    <button v-if="props.auth?.user?.role === 'owner'" @click="openAddModal" class="ml-auto h-10 bg-[#ee6c12] text-white px-6 font-bold flex items-center gap-2 hover:brightness-110 active:translate-y-px transition-all rounded">
                        <span class="material-symbols-outlined">add_box</span> Tambah Barang
                    </button>
                </div>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white border-2 border-outline-variant p-4 flex items-center gap-4 rounded">
                        <div class="bg-primary-container/10 p-3 text-primary rounded">
                            <span class="material-symbols-outlined">inventory</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Total SKU</p>
                            <p class="text-headline-md font-bold">{{ totalProducts }}</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-4 flex items-center gap-4 rounded">
                        <div class="bg-error-container/20 p-3 text-error rounded">
                            <span class="material-symbols-outlined">warning</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Stok Rendah</p>
                            <p class="text-headline-md font-bold text-error">{{ lowStockCount }}</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-4 flex items-center gap-4 rounded">
                        <div class="bg-secondary-container/10 p-3 text-secondary rounded">
                            <span class="material-symbols-outlined">category</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Kategori</p>
                            <p class="text-headline-md font-bold">{{ categoryCount }}</p>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-outline-variant p-4 flex items-center gap-4 rounded">
                        <div class="bg-primary-container/10 p-3 text-primary rounded">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div>
                            <p class="text-label-md text-secondary">Valuasi Stok</p>
                            <p class="text-headline-md font-bold">{{ formatShortRupiah(totalValuation) }}</p>
                        </div>
                    </div>
                </div>

                <!-- INVENTORY TABLE -->
                <div class="bg-white border-2 border-outline-variant overflow-hidden rounded">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-surface-container-low border-b-2 border-outline-variant">
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider">Foto</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider">Produk</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider">Kategori</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider text-right">Stok</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider text-right">Harga Jual</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider text-center">Lokasi</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider text-center">Prediksi</th>
                                    <th class="px-4 py-4 text-label-md text-secondary uppercase tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                <tr v-if="!props.products || props.products.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-secondary">Belum ada barang di inventaris.</td>
                                </tr>
                                <tr v-for="(product, index) in props.products" :key="product.id" :class="['hover:bg-surface-container-low transition-colors align-middle', index % 2 !== 0 ? 'bg-surface-container-lowest' : '']">
                                    <td class="px-4 py-3">
                                        <div class="w-14 h-14 rounded border border-outline-variant overflow-hidden bg-white flex items-center justify-center">
                                            <img v-if="product.photo_url" :alt="product.name" class="w-full h-full object-cover" :src="product.photo_url">
                                            <span v-else class="material-symbols-outlined text-outline">inventory_2</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface text-body-md">{{ product.name }}</span>
                                            <span class="font-mono text-xs text-primary font-bold tracking-wider">SKU-{{ product.id.toString().padStart(4, '0') }}</span>
                                            <span v-if="product.alt_stock_display" class="text-xs text-secondary">{{ product.alt_stock_display }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight">{{ product.category }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-col items-end">
                                            <span :class="['font-bold', product.is_low_stock ? 'text-error' : 'text-on-surface']">{{ product.stock_qty_base_unit }}</span>
                                            <span :class="['text-[10px] font-bold uppercase', product.is_low_stock ? 'text-error' : 'text-secondary']">{{ product.is_low_stock ? '⚠ Low Stock' : product.base_unit }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-bold text-on-surface text-sm">{{ formatRupiah(product.selling_price_per_base_unit) }}</span>
                                        <span class="text-xs text-secondary block">/ {{ product.base_unit }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="bg-surface-container-high border border-outline-variant px-2 py-1 font-mono text-xs font-bold rounded">{{ product.location || '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-secondary">
                                        <span v-if="product.prediction">
                                            <span class="font-bold text-warning">~{{ product.prediction.days_remaining }}</span> hari
                                        </span>
                                        <span v-else class="text-outline">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openMovements(product)" class="p-1.5 rounded text-secondary hover:text-primary hover:bg-surface-container-high transition-all" title="Riwayat Stok">
                                                <span class="material-symbols-outlined text-[18px]">history</span>
                                            </button>
                                            <button v-if="props.auth?.user?.role === 'owner'" @click="openEditModal(product)" class="p-1.5 rounded text-secondary hover:text-primary hover:bg-surface-container-high transition-all" title="Edit Produk">
                                                <span class="material-symbols-outlined text-[18px]">edit_square</span>
                                            </button>
                                            <button v-if="props.auth?.user?.role === 'owner'" @click="confirmDelete(product)" class="p-1.5 rounded text-secondary hover:text-error hover:bg-error-container/20 transition-all" title="Nonaktifkan">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ============ MODAL TAMBAH PRODUK ============ -->
    <Transition name="fade">
        <div v-if="showAddModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" @click.self="showAddModal = false">
            <div class="bg-surface w-full max-w-2xl border-2 border-outline shadow-2xl overflow-hidden rounded max-h-[90vh] flex flex-col">
                <div class="bg-surface-container-high px-6 py-4 border-b-2 border-outline-variant flex justify-between items-center">
                    <h3 class="text-headline-md font-bold text-on-surface">Tambah Barang Baru</h3>
                    <button @click="showAddModal = false" class="text-secondary hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="overflow-y-auto flex-1 p-6">
                    <form @submit.prevent="submitAdd" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-label-md font-bold text-secondary mb-2">Nama Barang *</label>
                            <input v-model="addForm.name" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Contoh: Semen Tiga Roda 40kg" type="text" required>
                            <p v-if="addErrors.name" class="text-error text-xs mt-1">{{ addErrors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Kategori *</label>
                            <select v-model="addForm.category_id" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                                <option value="">Pilih Kategori</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="addErrors.category_id" class="text-error text-xs mt-1">{{ addErrors.category_id }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Satuan Dasar *</label>
                            <input v-model="addForm.base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Contoh: sak, kg, batang" type="text" required>
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Harga Jual (Rp) *</label>
                            <input v-model="addForm.selling_price_per_base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="0" type="number" min="0" required>
                            <p v-if="addErrors.selling_price_per_base_unit" class="text-error text-xs mt-1">{{ addErrors.selling_price_per_base_unit }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Harga Modal / HPP (Rp) *</label>
                            <input v-model="addForm.cost_price_per_base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="0" type="number" min="0" required>
                            <p class="text-xs text-secondary mt-1">HPP selanjutnya hanya bisa diubah via Restock</p>
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Stok Awal</label>
                            <input v-model="addForm.stock_qty_base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="0" type="number" min="0">
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Kode Rak / Lokasi</label>
                            <input v-model="addForm.location" class="w-full p-3 border-2 border-outline-variant bg-white font-mono rounded focus:ring-0 focus:border-primary uppercase" placeholder="RAK-A1" type="text">
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Batas Stok Minimum</label>
                            <input v-model="addForm.min_stock_threshold" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="10" type="number" min="0">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-label-md font-bold text-secondary mb-2">Upload Foto Produk (Cloudinary)</label>
                            <input type="file" accept="image/*" @change="handleAddPhotoChange" class="w-full p-2 border-2 border-outline-variant rounded bg-white text-sm focus:ring-0 focus:border-primary">
                            <p v-if="addErrors.photo_file" class="text-error text-xs mt-1">{{ addErrors.photo_file }}</p>
                            <div v-if="addPhotoPreviewUrl" class="mt-3 w-32 h-32 border-2 border-outline-variant rounded-lg overflow-hidden bg-surface-container-low flex items-center justify-center">
                                <img :src="addPhotoPreviewUrl" class="w-full h-full object-cover" />
                            </div>
                        </div>

                        <!-- Unit Konversi -->
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-label-md font-bold text-secondary">Satuan Jual Alternatif</label>
                                <button type="button" @click="addUnit" class="text-sm text-primary font-bold flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-sm">add</span> Tambah Satuan
                                </button>
                            </div>
                            <div v-for="(unit, i) in addForm.units" :key="i" class="flex gap-3 mb-2 items-center">
                                <input v-model="unit.unit_name" class="flex-1 p-2 border border-outline-variant bg-white rounded text-sm" placeholder="Nama satuan (cth: ton)" type="text">
                                <span class="text-secondary text-sm">= </span>
                                <input v-model="unit.conversion_factor" class="w-28 p-2 border border-outline-variant bg-white rounded text-sm" placeholder="Faktor (cth: 1000)" type="number" min="0.0001" step="any">
                                <span class="text-secondary text-sm">{{ addForm.base_unit || 'unit' }}</span>
                                <button type="button" @click="removeUnit(i)" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant flex justify-end gap-4">
                    <button type="button" @click="showAddModal = false" class="px-6 py-3 border-2 border-outline-variant font-bold text-secondary hover:bg-surface-container-low rounded transition-colors">Batal</button>
                    <button @click="submitAdd" :disabled="isAddSubmitting" class="bg-[#ee6c12] text-white px-8 py-3 font-bold hover:brightness-110 active:scale-95 transition-all rounded disabled:opacity-50">
                        <span v-if="isAddSubmitting">Menyimpan...</span>
                        <span v-else>Simpan Barang</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- ============ MODAL EDIT PRODUK ============ -->
    <Transition name="fade">
        <div v-if="showEditModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" @click.self="showEditModal = false">
            <div class="bg-surface w-full max-w-2xl border-2 border-outline shadow-2xl overflow-hidden rounded max-h-[90vh] flex flex-col">
                <div class="bg-surface-container-high px-6 py-4 border-b-2 border-outline-variant flex justify-between items-center">
                    <h3 class="text-headline-md font-bold text-on-surface">Edit Data Barang</h3>
                    <button @click="showEditModal = false" class="text-secondary hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="overflow-y-auto flex-1 p-6">
                    <div class="mb-4 p-3 bg-surface-container-low rounded border border-outline-variant text-sm text-secondary">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">info</span>
                        HPP (harga modal) tidak bisa diedit di sini. Gunakan menu <strong>Restok</strong> untuk mengubah HPP.
                    </div>
                    <form @submit.prevent="submitEdit" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-label-md font-bold text-secondary mb-2">Nama Barang *</label>
                            <input v-model="editForm.name" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" type="text" required>
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Kategori *</label>
                            <select v-model="editForm.category_id" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                                <option value="">Pilih Kategori</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Satuan Dasar *</label>
                            <input v-model="editForm.base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" type="text" required>
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Harga Jual (Rp) *</label>
                            <input v-model="editForm.selling_price_per_base_unit" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" type="number" min="0" required>
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Lokasi / Kode Rak</label>
                            <input v-model="editForm.location" class="w-full p-3 border-2 border-outline-variant bg-white rounded font-mono uppercase focus:ring-0 focus:border-primary" type="text">
                        </div>

                        <div>
                            <label class="block text-label-md font-bold text-secondary mb-2">Batas Stok Minimum</label>
                            <input v-model="editForm.min_stock_threshold" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" type="number" min="0">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-label-md font-bold text-secondary mb-2">Upload Foto Produk Baru (Cloudinary)</label>
                            <input type="file" accept="image/*" @change="handleEditPhotoChange" class="w-full p-2 border-2 border-outline-variant rounded bg-white text-sm focus:ring-0 focus:border-primary">
                            <p v-if="editErrors.photo_file" class="text-error text-xs mt-1">{{ editErrors.photo_file }}</p>
                            
                            <div v-if="editPhotoPreviewUrl || editForm.photo_url" class="mt-3 flex items-start gap-4">
                                <div class="w-32 h-32 border-2 border-outline-variant rounded-lg overflow-hidden bg-surface-container-low flex items-center justify-center shrink-0">
                                    <img :src="editPhotoPreviewUrl || editForm.photo_url" class="w-full h-full object-cover" />
                                </div>
                                <div v-if="editForm.photo_url" class="text-xs text-secondary mt-1">
                                    <span class="block font-bold">Foto saat ini:</span>
                                    <a :href="editForm.photo_url" target="_blank" class="text-primary hover:underline font-bold truncate block max-w-xs mt-1">{{ editForm.photo_url }}</a>
                                </div>
                            </div>
                        </div>

                        <!-- Unit Konversi -->
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-label-md font-bold text-secondary">Satuan Jual Alternatif</label>
                                <button type="button" @click="addEditUnit" class="text-sm text-primary font-bold flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-sm">add</span> Tambah Satuan
                                </button>
                            </div>
                            <div v-for="(unit, i) in editForm.units" :key="i" class="flex gap-3 mb-2 items-center">
                                <input v-model="unit.unit_name" class="flex-1 p-2 border border-outline-variant bg-white rounded text-sm" placeholder="Nama satuan" type="text">
                                <span class="text-secondary text-sm">= </span>
                                <input v-model="unit.conversion_factor" class="w-28 p-2 border border-outline-variant bg-white rounded text-sm" placeholder="Faktor" type="number" min="0.0001" step="any">
                                <span class="text-secondary text-sm">{{ editForm.base_unit || 'unit' }}</span>
                                <button type="button" @click="removeEditUnit(i)" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant flex justify-end gap-4">
                    <button type="button" @click="showEditModal = false" class="px-6 py-3 border-2 border-outline-variant font-bold text-secondary hover:bg-surface-container-low rounded transition-colors">Batal</button>
                    <button @click="submitEdit" :disabled="isEditSubmitting" class="bg-[#ee6c12] text-white px-8 py-3 font-bold hover:brightness-110 active:scale-95 transition-all rounded disabled:opacity-50">
                        <span v-if="isEditSubmitting">Menyimpan...</span>
                        <span v-else>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- ============ MODAL RIWAYAT STOK ============ -->
    <Transition name="fade">
        <div v-if="showMovementsModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4" @click.self="closeMovements">
            <div class="bg-surface w-full max-w-2xl border-2 border-outline shadow-2xl overflow-hidden rounded max-h-[80vh] flex flex-col">
                <div class="bg-surface-container-high px-6 py-4 border-b-2 border-outline-variant flex justify-between items-center">
                    <h3 class="text-headline-md font-bold text-on-surface">Riwayat Stok — {{ movementsProduct?.name }}</h3>
                    <button @click="closeMovements" class="text-secondary hover:text-error transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div v-if="isLoadingMovements" class="p-8 text-center text-secondary">
                    <span class="material-symbols-outlined animate-spin text-4xl">progress_activity</span>
                    <p class="mt-2">Memuat riwayat...</p>
                </div>

                <div v-else class="overflow-y-auto flex-1">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-container-high sticky top-0">
                            <tr>
                                <th class="px-4 py-3 font-bold text-secondary">Jenis</th>
                                <th class="px-4 py-3 font-bold text-secondary">Qty</th>
                                <th class="px-4 py-3 font-bold text-secondary">Info</th>
                                <th class="px-4 py-3 font-bold text-secondary">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr v-if="movements.length === 0">
                                <td colspan="4" class="p-6 text-center text-secondary">Belum ada riwayat stok.</td>
                            </tr>
                            <tr v-for="(m, i) in movements" :key="i" class="hover:bg-surface-container-low">
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold', m.type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                        <span class="material-symbols-outlined text-[14px]">{{ m.type === 'in' ? 'add_circle' : 'remove_circle' }}</span>
                                        {{ m.type === 'in' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-bold text-on-surface">
                                    {{ m.type === 'in' ? '+' : '-' }}{{ m.qty }} unit dasar
                                    <span v-if="m.unit_display" class="text-secondary font-normal">({{ m.qty_display || m.qty }} {{ m.unit_display }})</span>
                                </td>
                                <td class="px-4 py-3 text-secondary">
                                    <span v-if="m.type === 'in'">HPP: Rp {{ Number(m.hpp).toLocaleString('id-ID') }} | Lokasi: {{ m.location || '-' }}</span>
                                    <span v-else>Kasir: {{ m.user }}</span>
                                </td>
                                <td class="px-4 py-3 text-secondary">{{ m.datetime }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant">
                    <button @click="closeMovements" class="w-full h-10 border border-outline-variant bg-surface-container-low text-on-surface rounded font-bold hover:bg-surface-container-high">Tutup</button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- BOTTOM NAV (Mobile Only) -->
    <nav class="fixed bottom-0 left-0 w-full bg-surface border-t-2 border-outline-variant flex justify-around items-center h-16 px-4 md:hidden z-50">
        <Link href="/dashboard" class="flex flex-col items-center justify-center text-secondary cursor-pointer">
            <span class="material-symbols-outlined">home</span>
            <span class="text-[10px] font-semibold">Home</span>
        </Link>
        <Link href="/inventaris" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1 cursor-pointer">
            <span class="material-symbols-outlined">inventory_2</span>
            <span class="text-[10px] font-semibold">Inventaris</span>
        </Link>
        <Link href="/kasir" class="flex flex-col items-center justify-center text-secondary active:translate-y-[1px] transition-all cursor-pointer">
            <span class="material-symbols-outlined">add_shopping_cart</span>
            <span class="text-[10px] font-semibold">POS</span>
        </Link>
        <Link href="/laporan" class="flex flex-col items-center justify-center text-secondary active:translate-y-[1px] transition-all cursor-pointer">
            <span class="material-symbols-outlined">assessment</span>
            <span class="text-[10px] font-semibold">Laporan</span>
        </Link>
    </nav>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
