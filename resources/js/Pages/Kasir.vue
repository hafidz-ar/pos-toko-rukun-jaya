<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    categories: Array,
    auth: Object,
});

const showConfirmationModal = ref(false);
const showReceiptModal = ref(false);
const selectedPaymentMethod = ref('tunai');
const searchQuery = ref('');
const selectedCategory = ref('');
const cart = ref([]);
const discount = ref(0);
const isSubmitting = ref(false);
const errorMessage = ref('');
const receiptData = ref(null);
const cashInput = ref(0);

// Modal pilih unit
const showUnitModal = ref(false);
const pendingProduct = ref(null);

const filteredProducts = computed(() => {
    let result = props.products || [];

    // Filter by Category
    if (selectedCategory.value) {
        result = result.filter(p => p.category === selectedCategory.value);
    }

    // Search query (name, location, SKU)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(p => {
            const sku = 'sku-' + p.id.toString().padStart(4, '0');
            return p.name.toLowerCase().includes(query) ||
                (p.location && p.location.toLowerCase().includes(query)) ||
                sku.includes(query);
        });
    }

    return result;
});

// Buka modal pilih unit sebelum tambah ke keranjang
const openUnitModal = (product) => {
    if (!product.units || product.units.length === 0) {
        // Tidak ada unit alternatif, langsung tambah dengan unit dasar
        addToCartDirect(product, product.base_unit, 1, product.selling_price_per_base_unit);
        return;
    }
    pendingProduct.value = product;
    showUnitModal.value = true;
};

const closeUnitModal = () => {
    showUnitModal.value = false;
    pendingProduct.value = null;
};

const selectUnit = (product, unit) => {
    addToCartDirect(product, unit.unit_name, unit.conversion_factor, unit.selling_price);
    closeUnitModal();
};

const selectBaseUnit = (product) => {
    addToCartDirect(product, product.base_unit, 1, product.selling_price_per_base_unit);
    closeUnitModal();
};

const addToCartDirect = (product, unitName, conversionFactor, pricePerUnit) => {
    const key = `${product.id}-${unitName}`;
    const existing = cart.value.find(item => item.key === key);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({
            key,
            product_id: product.id,
            name: product.name,
            qty: 1,
            unit_name: unitName,
            conversion_factor: conversionFactor,
            price: pricePerUnit,
            stock_qty_base_unit: product.stock_qty_base_unit,
        });
    }
};

// Untuk backward compat: jika produk tidak punya units, langsung tambah
const addToCart = (product) => {
    openUnitModal(product);
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const totalHPP = computed(() => {
    // HPP tidak dikirim ke frontend untuk keamanan, validasi dilakukan di backend
    return 0;
});

const subtotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.qty * item.price), 0);
});

const totalAmount = computed(() => {
    return Math.max(0, subtotal.value - (discount.value || 0));
});

watch(discount, (val) => {
    if (val < 0 || isNaN(val) || val === '') {
        discount.value = 0;
    } else if (val > subtotal.value) {
        discount.value = subtotal.value;
    }
});

const preventInvalidNumberKeys = (event) => {
    if (['-', '+', 'e', 'E'].includes(event.key)) {
        event.preventDefault();
    }
};

const kembalian = computed(() => {
    if (selectedPaymentMethod.value === 'tunai') {
        return Math.max(0, (cashInput.value || 0) - totalAmount.value);
    }
    return 0;
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};

const openConfirmation = () => {
    if (cart.value.length === 0) return;
    errorMessage.value = '';
    cashInput.value = totalAmount.value;
    showConfirmationModal.value = true;
};

const closeConfirmation = () => {
    showConfirmationModal.value = false;
    errorMessage.value = '';
};

const openReceipt = (data) => {
    receiptData.value = data;
    showReceiptModal.value = true;
    showConfirmationModal.value = false;
};

const confirmPayment = () => {
    if (cart.value.length === 0) return;
    if (selectedPaymentMethod.value === 'tunai' && cashInput.value < totalAmount.value) {
        errorMessage.value = 'Uang tunai tidak mencukupi.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    const payload = {
        items: cart.value.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            unit_name: item.unit_name,
            conversion_factor: item.conversion_factor,
            price_per_unit: item.price,
        })),
        payment_method: selectedPaymentMethod.value,
        discount_amount: discount.value || 0,
        cash_received: selectedPaymentMethod.value === 'tunai' ? cashInput.value : totalAmount.value,
    };

    fetch('/kasir/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    })
        .then(async res => {
            if (res.status === 419) {
                return Promise.reject('Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman (refresh) dan login kembali.');
            }
            if (res.status === 401 || res.status === 403) {
                return Promise.reject('Akses ditolak atau sesi telah habis. Silakan muat ulang halaman.');
            }

            const isJson = res.headers.get('content-type')?.includes('application/json');
            const data = isJson ? await res.json() : null;

            if (!res.ok) {
                const errorMsg = (data && data.message) || `Terjadi kesalahan (Status: ${res.status})`;
                return Promise.reject(errorMsg);
            }

            return data;
        })
        .then(data => {
            if (data && data.success) {
                openReceipt(data.receipt);
            } else {
                errorMessage.value = (data && data.message) || 'Terjadi kesalahan.';
            }
        })
        .catch(err => {
            errorMessage.value = typeof err === 'string' ? err : 'Gagal terhubung ke server. Pastikan server aktif dan koneksi internet stabil.';
        })
        .finally(() => {
            isSubmitting.value = false;
        });
};

const closeReceipt = () => {
    showReceiptModal.value = false;
    receiptData.value = null;
    cart.value = [];
    discount.value = 0;
    cashInput.value = 0;
    errorMessage.value = '';
};

const printReceipt = () => {
    window.print();
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closeConfirmation();
        closeReceipt();
        closeUnitModal();
    }
    if (event.key === 'F12') {
        event.preventDefault();
        openConfirmation();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Kasir - Toko Rukun Jaya" />
    
    <div class="fixed inset-0 bg-background text-on-background flex flex-col md:flex-row overflow-hidden w-full h-full font-sans">
        
        <!-- Top Navigation Bar (Mobile only) -->
        <nav class="md:hidden flex justify-between items-center w-full px-margin-mobile h-touch-target-min bg-surface border-b-2 border-outline-variant shrink-0 z-30">
            <span class="text-headline-md font-headline-md font-bold text-primary">Toko Rukun Jaya</span>
            <div class="flex gap-4">
                <button @click="handleLogout" class="material-symbols-outlined text-error active:scale-90 transition-transform" title="Keluar">logout</button>
            </div>
        </nav>

        <!-- Side Navigation Bar (Desktop) -->
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
                    class="w-full bg-primary text-on-primary font-bold min-h-[48px] rounded hover:brightness-90 active:translate-y-[1px] transition-all cursor-pointer shadow border-2 border-primary"
                >
                    Transaksi Baru
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-background">
            <!-- TopNavBar -->
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant shrink-0">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Transaksi Baru</span>
                </div>
            </header>

            <!-- Main POS Interface -->
            <div class="flex-grow flex flex-row overflow-hidden">
            <!-- Left Column: Daftar Produk (70%) -->
            <section class="w-[70%] flex flex-col border-r border-outline-variant bg-surface-container-low overflow-hidden">
                <div class="p-gutter flex flex-col h-full bg-surface-container-low">
                    <div class="flex gap-3 mb-gutter">
                        <div class="relative flex-grow">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">search</span>
                            <input v-model="searchQuery" class="w-full h-12 bg-surface-container-lowest border border-outline-variant rounded-lg pl-12 pr-4 text-body-md focus:ring-2 focus:ring-primary focus:outline-none transition-all" placeholder="Cari berdasarkan nama, lokasi, atau SKU..." type="text"/>
                        </div>
                        <div class="relative shrink-0 w-52">
                            <select v-model="selectedCategory" class="w-full h-12 bg-surface-container-lowest border border-outline-variant rounded-lg px-4 text-body-md focus:ring-2 focus:ring-primary focus:outline-none transition-all appearance-none cursor-pointer">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    
                    <div class="flex-grow overflow-y-auto bg-surface-container-lowest rounded-xl border border-outline-variant custom-scrollbar">
                        <div class="p-4 border-b border-outline-variant bg-surface-container-high">
                            <h3 class="text-label-xl font-bold text-on-surface uppercase tracking-wider">Produk Inventori ({{ filteredProducts.length }} item)</h3>
                        </div>
                        <div class="divide-y divide-outline-variant">
                            <div v-if="filteredProducts.length === 0" class="p-8 text-center text-secondary">
                                Tidak ada produk ditemukan.
                            </div>
                            <div v-for="product in filteredProducts" :key="product.id" class="p-4 hover:bg-surface-bright transition-colors flex items-center gap-4 group">
                                <div class="size-16 rounded bg-surface-container-high flex items-center justify-center border border-outline-variant overflow-hidden shrink-0">
                                    <img v-if="product.photo_url" :src="product.photo_url" class="w-full h-full object-cover" />
                                    <span v-else class="material-symbols-outlined text-outline">inventory_2</span>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-body-lg font-bold text-on-surface truncate">{{ product.name }}</p>
                                    <p class="text-xs text-outline truncate">
                                        <span class="font-mono text-xs text-primary font-bold tracking-wider mr-2 bg-surface-container px-1.5 py-0.5 rounded">SKU-{{ product.id.toString().padStart(4, '0') }}</span>
                                        Stok: {{ product.stock_qty_base_unit }} {{ product.base_unit }} | Kat: {{ product.category }} | Lokasi: {{ product.location || '-' }}
                                    </p>
                                    <p class="text-body-md font-bold text-primary mt-1">{{ formatRupiah(product.selling_price_per_base_unit) }} / {{ product.base_unit }}</p>
                                    <p v-if="product.units && product.units.length > 0" class="text-xs text-secondary mt-0.5">
                                        + {{ product.units.length }} satuan lain tersedia
                                    </p>
                                </div>
                                <button @click="addToCart(product)" class="size-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-all active:translate-y-[1px] shrink-0">
                                    <span class="material-symbols-outlined">add</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right Column: Keranjang (30%) -->
            <aside class="w-[30%] flex flex-col bg-surface-container-highest">
                <div class="flex flex-col h-full bg-surface-container-highest border-l border-outline-variant">
                    <div class="p-4 bg-surface-container-high border-b border-outline-variant">
                        <h3 class="text-label-md font-bold text-on-surface uppercase tracking-wider">KERANJANG BELANJA</h3>
                    </div>
                    
                    <div class="flex-grow overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        <div v-if="cart.length === 0" class="text-center text-secondary py-8 text-sm">
                            Keranjang kosong
                        </div>
                        <div v-for="(item, index) in cart" :key="index" class="p-3 bg-surface-container-lowest rounded border border-outline-variant">
                            <div class="flex justify-between items-start">
                                <div class="flex-grow min-w-0 pr-2">
                                    <p class="text-sm font-bold text-on-surface truncate">{{ item.name }}</p>
                                    <p class="text-xs text-secondary">{{ item.unit_name }} · {{ formatRupiah(item.price) }}</p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-error hover:text-error/80 shrink-0">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <div class="flex items-center gap-2">
                                    <input v-model.number="item.qty" type="number" min="1" class="w-16 h-7 bg-surface border border-outline rounded px-1 text-center text-sm focus:ring-1 focus:ring-primary focus:outline-none" />
                                    <span class="text-xs text-outline">x {{ formatRupiah(item.price) }}</span>
                                </div>
                                <span class="text-sm font-bold text-primary">{{ formatRupiah(item.qty * item.price) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-surface-container-lowest border-t border-outline-variant space-y-2">
                        <div class="flex justify-between text-label-md text-on-surface-variant">
                            <span>Subtotal</span>
                            <span>{{ formatRupiah(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-1">
                            <label class="text-label-md text-on-surface-variant whitespace-nowrap">Diskon (Rp)</label>
                            <input v-model.number="discount" @keydown="preventInvalidNumberKeys" class="w-32 h-8 bg-surface-container-low border border-outline-variant rounded px-2 text-right text-body-md focus:ring-1 focus:ring-primary focus:outline-none" placeholder="0" type="number" min="0"/>
                        </div>
                        <div class="flex justify-between items-center pt-3 mt-2 border-t-2 border-dashed border-outline-variant">
                            <span class="text-label-xl font-bold text-on-surface">TOTAL</span>
                            <span class="text-headline-md text-primary font-bold">{{ formatRupiah(totalAmount) }}</span>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <p class="text-xs font-bold text-outline uppercase tracking-tighter">Metode Pembayaran</p>
                            <div class="flex gap-2">
                                <button 
                                    @click="selectedPaymentMethod = 'tunai'"
                                    :class="selectedPaymentMethod === 'tunai' ? 'border-2 border-primary bg-primary-container text-on-primary-container' : 'border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 rounded font-bold text-sm flex items-center justify-center gap-1 active:translate-y-[1px] transition-all">
                                    <span class="material-symbols-outlined text-sm">payments</span> Tunai
                                </button>
                                <button 
                                    @click="selectedPaymentMethod = 'qris'"
                                    :class="selectedPaymentMethod === 'qris' ? 'border-2 border-primary bg-primary-container text-on-primary-container' : 'border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 rounded font-bold text-sm flex items-center justify-center gap-1 active:translate-y-[1px] transition-all">
                                    <span class="material-symbols-outlined text-sm">qr_code_2</span> QRIS
                                </button>
                            </div>
                        </div>
                        
                        <button class="btn-primary w-full h-14 mt-4 text-label-xl font-bold flex items-center justify-center gap-2 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" @click="openConfirmation" :disabled="cart.length === 0">
                            <span class="material-symbols-outlined">payments</span> BAYAR (F12)
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </main>

        <!-- Modal Pilih Unit -->
        <Transition name="fade">
            <div v-if="showUnitModal && pendingProduct" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" @click.self="closeUnitModal">
                <div class="bg-surface-container-lowest w-full max-w-sm rounded border border-outline-variant shadow-2xl overflow-hidden flex flex-col">
                    <div class="p-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                        <h2 class="text-label-xl font-bold text-on-surface">Pilih Satuan</h2>
                        <button @click="closeUnitModal" class="text-on-surface hover:text-error transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-secondary mb-3 font-bold">{{ pendingProduct.name }}</p>
                        <!-- Unit Dasar -->
                        <button @click="selectBaseUnit(pendingProduct)" class="w-full text-left p-3 rounded border border-outline-variant hover:bg-surface-container-high transition-colors mb-2 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-on-surface">{{ pendingProduct.base_unit }}</span>
                                <span class="text-xs text-secondary ml-2">(Satuan Dasar)</span>
                            </div>
                            <span class="font-bold text-primary">{{ formatRupiah(pendingProduct.selling_price_per_base_unit) }}</span>
                        </button>
                        <!-- Unit Alternatif -->
                        <button v-for="unit in pendingProduct.units" :key="unit.id"
                            @click="selectUnit(pendingProduct, unit)"
                            class="w-full text-left p-3 rounded border border-outline-variant hover:bg-surface-container-high transition-colors mb-2 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-on-surface">{{ unit.unit_name }}</span>
                                <span class="text-xs text-secondary ml-2">(1 {{ unit.unit_name }} = {{ unit.conversion_factor }} {{ pendingProduct.base_unit }})</span>
                            </div>
                            <span class="font-bold text-primary">{{ formatRupiah(unit.selling_price) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal Konfirmasi Pembayaran -->
        <Transition name="fade">
            <div v-if="showConfirmationModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" id="confirmation-modal">
                <div class="bg-surface-container-lowest w-full max-w-sm rounded border border-outline-variant shadow-2xl overflow-hidden flex flex-col">
                    <div class="p-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                        <h2 class="text-label-xl font-bold text-on-surface uppercase tracking-wider">Konfirmasi Pembayaran</h2>
                        <button @click="closeConfirmation" class="text-on-surface hover:text-error transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Ringkasan Item -->
                        <div class="max-h-32 overflow-y-auto space-y-1 custom-scrollbar">
                            <div v-for="item in cart" :key="item.key" class="flex justify-between text-sm">
                                <span class="text-secondary truncate pr-2">{{ item.name }} ({{ item.qty }} {{ item.unit_name }})</span>
                                <span class="font-bold shrink-0">{{ formatRupiah(item.qty * item.price) }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between text-body-md text-on-surface-variant">
                                <span>Subtotal</span>
                                <span>{{ formatRupiah(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-body-md text-on-surface-variant">
                                <span>Diskon</span>
                                <span class="text-error">- {{ formatRupiah(discount) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-outline-variant">
                                <span class="text-label-md font-bold text-on-surface">TOTAL AKHIR</span>
                                <span class="text-headline-md text-primary font-bold">{{ formatRupiah(totalAmount) }}</span>
                            </div>
                        </div>

                        <div class="p-3 bg-surface-container-low rounded border border-outline-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">{{ selectedPaymentMethod === 'tunai' ? 'payments' : 'qr_code_2' }}</span>
                            <span class="text-label-md text-on-surface">Metode: <span class="font-bold">{{ selectedPaymentMethod === 'tunai' ? 'Tunai' : 'QRIS' }}</span></span>
                        </div>

                        <!-- Input uang tunai -->
                        <div v-if="selectedPaymentMethod === 'tunai'" class="space-y-2">
                            <label class="text-label-md text-on-surface-variant">Uang Tunai Diterima (Rp)</label>
                            <input v-model.number="cashInput" type="number" min="0" class="w-full h-10 bg-surface border border-outline-variant rounded px-3 text-right text-body-md font-bold focus:ring-1 focus:ring-primary focus:outline-none" />
                            <div v-if="cashInput >= totalAmount" class="flex justify-between text-sm font-bold text-green-600">
                                <span>Kembalian:</span>
                                <span>{{ formatRupiah(kembalian) }}</span>
                            </div>
                        </div>

                        <!-- Error message -->
                        <div v-if="errorMessage" class="p-3 bg-error-container rounded text-sm text-on-error-container font-semibold">
                            {{ errorMessage }}
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button class="btn-secondary flex-1 h-12" @click="closeConfirmation">Batal</button>
                            <button class="btn-primary flex-1 h-12 flex justify-center items-center gap-2" @click="confirmPayment" :disabled="isSubmitting">
                                <span v-if="isSubmitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                                <span v-else class="material-symbols-outlined">check_circle</span>
                                Konfirmasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal Struk -->
        <Transition name="fade">
            <div v-if="showReceiptModal && receiptData" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" id="receipt-modal">
                <div class="bg-white w-full max-w-sm rounded shadow-2xl overflow-hidden flex flex-col font-mono text-black p-6 border-t-8 border-primary">
                    <div class="text-center border-b border-dashed border-gray-300 pb-4 mb-4">
                        <h2 class="text-xl font-bold uppercase">STRUK PEMBAYARAN</h2>
                        <p class="text-xs">TOKO RUKUN JAYA</p>
                        <p class="text-[10px]">No: TX-{{ String(receiptData.id).padStart(6, '0') }}</p>
                        <p class="text-[10px]">{{ receiptData.datetime }}</p>
                        <p class="text-[10px]">Kasir: {{ receiptData.cashier }}</p>
                    </div>
                    <div class="space-y-2 text-xs mb-4">
                        <template v-for="(item, idx) in receiptData.items" :key="idx">
                            <div class="flex justify-between">
                                <span class="flex-grow pr-2">{{ item.product_name }}</span>
                                <span class="shrink-0">{{ new Intl.NumberFormat('id-ID').format(item.subtotal) }}</span>
                            </div>
                            <div class="pl-4 italic text-gray-500">{{ item.qty }} {{ item.unit }} x {{ new Intl.NumberFormat('id-ID').format(item.price) }}</div>
                        </template>
                    </div>
                    <div class="border-t border-dashed border-gray-300 pt-4 space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>{{ formatRupiah(receiptData.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Diskon</span>
                            <span :class="receiptData.discount > 0 ? 'text-red-600' : ''">
                                {{ receiptData.discount > 0 ? '-' : '' }} {{ formatRupiah(receiptData.discount) }}
                            </span>
                        </div>
                        <div class="flex justify-between font-bold text-sm pt-2 border-t border-gray-300">
                            <span>TOTAL</span>
                            <span>{{ formatRupiah(receiptData.total) }}</span>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-dashed border-gray-300 my-3"></div>

                        <!-- Payment Details -->
                        <div class="pt-1 text-xs space-y-1">
                            <div class="flex">
                                <span class="w-28 shrink-0">Metode Bayar</span>
                                <span>: {{ receiptData.payment_method === 'qris' ? 'QRIS' : 'Tunai' }}</span>
                            </div>
                            <div v-if="receiptData.payment_method === 'tunai'" class="flex">
                                <span class="w-28 shrink-0">Bayar</span>
                                <span>: {{ formatRupiah(receiptData.cash_received) }}</span>
                            </div>
                            <div v-if="receiptData.payment_method === 'tunai'" class="flex">
                                <span class="w-28 shrink-0">Kembalian</span>
                                <span :class="receiptData.change > 0 ? 'font-bold' : ''">: {{ formatRupiah(receiptData.change) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center text-[10px] text-gray-500">
                        <p>Terima Kasih Atas Kunjungan Anda</p>
                        <p>Barang yang sudah dibeli tidak dapat ditukar</p>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button class="flex-1 h-12 border border-gray-300 rounded font-bold font-sans text-gray-700 hover:bg-gray-50 flex items-center justify-center gap-1" @click="printReceipt">
                            <span class="material-symbols-outlined text-sm">print</span> Cetak
                        </button>
                        <button class="flex-1 h-12 bg-primary text-white rounded font-bold font-sans active:translate-y-[1px] transition-transform" @click="closeReceipt">TRANSAKSI BARU</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: var(--color-surface-container-low, #f1f1f1); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-outline-variant, #d1d5db); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--color-outline, #9ca3af); }

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@page {
    margin: 0;
}

@media print {
    /* Hide all elements on the body except the receipt modal content */
    body * {
        visibility: hidden;
    }
    
    /* Make the receipt modal container and all its descendants visible */
    #receipt-modal,
    #receipt-modal * {
        visibility: visible;
    }
    
    /* Remove grey/black backdrop background and border lines for print */
    #receipt-modal {
        background: white !important;
        backdrop-filter: none !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: auto !important;
        display: flex !important;
        justify-content: center !important;
        align-items: start !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Keep the card design exactly as in the popup menu (rounded, top border, shadow, margins) */
    #receipt-modal > div {
        width: 100% !important;
        max-width: 384px !important; /* matches max-w-sm */
        margin: 20px auto !important;
        background: white !important;
    }
    
    /* Hide receipt control buttons (Cetak & Transaksi Baru) during printing */
    #receipt-modal .flex.gap-2 {
        display: none !important;
    }
}
</style>
