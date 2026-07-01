<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
});

const showConfirmationModal = ref(false);
const showReceiptModal = ref(false);
const selectedPaymentMethod = ref('Cash');
const searchQuery = ref('');
const cart = ref([]);
const discount = ref(0);
const isSubmitting = ref(false);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products || [];
    const query = searchQuery.value.toLowerCase();
    return props.products.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.category.toLowerCase().includes(query)
    );
});

const addToCart = (product) => {
    const existing = cart.value.find(item => item.product_id === product.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            qty: 1,
            unit_name: product.base_unit,
            price: product.selling_price_per_base_unit,
        });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const subtotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.qty * item.price), 0);
});

const totalAmount = computed(() => {
    return Math.max(0, subtotal.value - (discount.value || 0));
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};

const openConfirmation = () => {
    showConfirmationModal.value = true;
};

const closeConfirmation = () => {
    showConfirmationModal.value = false;
};

const confirmPayment = () => {
    if (cart.value.length === 0) return;
    
    isSubmitting.value = true;
    router.post('/kasir/store', {
        items: cart.value.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            unit_name: item.unit_name
        })),
        payment_method: selectedPaymentMethod.value,
        discount_amount: discount.value || 0
    }, {
        onSuccess: () => {
            openReceipt();
            // Cart will be cleared on modal close
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const closeReceipt = () => {
    showReceiptModal.value = false;
    cart.value = [];
    discount.value = 0;
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closeConfirmation();
        closeReceipt();
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
</script>

<template>
    <Head title="Kasir - Toko Material POS" />
    
    <div class="bg-background text-on-background font-body-md overflow-hidden h-screen">
        <!-- Focused POS Header -->
        <header class="flex items-center justify-between px-margin-desktop py-4 bg-surface-container-lowest border-b border-outline-variant h-16">
            <div class="flex items-center gap-4">
                <button @click="router.visit('/dashboard')" class="flex items-center justify-center p-2 rounded hover:bg-surface-container h-10 w-10 transition-colors">
                    <span class="material-symbols-outlined text-on-surface">arrow_back</span>
                </button>
                <h1 class="text-headline-md text-on-surface">Transaksi Baru</h1>
            </div>
            <div class="flex items-center gap-base">
                <div class="flex flex-col items-end mr-4">
                    <p class="text-label-md text-on-surface-variant">Kasir</p>
                    <p class="text-xs text-outline">Terminal: POS-01</p>
                </div>
                <div class="size-10 rounded-full bg-surface-container-high flex items-center justify-center border border-outline-variant">
                    <span class="material-symbols-outlined text-primary">person</span>
                </div>
            </div>
        </header>

        <!-- Main POS Interface -->
        <main class="flex flex-row h-[calc(100vh-64px)] overflow-hidden">
            <!-- Left Column: Area Transaksi (70%) -->
            <section class="w-[70%] flex flex-col border-r border-outline-variant bg-surface-container-low overflow-hidden">
                <div class="p-gutter flex flex-col h-full bg-surface-container-low">
                    <div class="flex gap-2 mb-gutter">
                        <div class="relative flex-grow">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">search</span>
                            <input v-model="searchQuery" class="w-full h-12 bg-surface-container-lowest border border-outline-variant rounded-lg pl-12 pr-4 text-body-md focus:ring-2 focus:ring-primary focus:outline-none transition-all" placeholder="Cari produk inventori..." type="text"/>
                        </div>
                    </div>
                    
                    <div class="flex-grow overflow-y-auto bg-surface-container-lowest rounded-xl border border-outline-variant custom-scrollbar">
                        <div class="p-4 border-b border-outline-variant bg-surface-container-high">
                            <h3 class="text-label-xl font-bold text-on-surface uppercase tracking-wider">Produk Inventori</h3>
                        </div>
                        <div class="divide-y divide-outline-variant">
                            <div v-if="filteredProducts.length === 0" class="p-8 text-center text-secondary">
                                Tidak ada produk ditemukan.
                            </div>
                            <div v-for="product in filteredProducts" :key="product.id" class="p-4 hover:bg-surface-bright transition-colors flex items-center gap-4 group">
                                <div class="size-16 rounded bg-surface-container-high flex items-center justify-center border border-outline-variant overflow-hidden">
                                    <img v-if="product.photo_url" :src="product.photo_url" class="w-full h-full object-cover" />
                                    <span v-else class="material-symbols-outlined text-outline">inventory_2</span>
                                </div>
                                <div class="flex-grow">
                                    <p class="text-body-lg font-bold text-on-surface">{{ product.name }}</p>
                                    <p class="text-xs text-outline">Stok: {{ product.stock_qty_base_unit }} {{ product.base_unit }} | Kat: {{ product.category }}</p>
                                    <p class="text-body-md font-bold text-primary mt-1">{{ formatRupiah(product.selling_price_per_base_unit) }}</p>
                                </div>
                                <button @click="addToCart(product)" class="size-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-all active:translate-y-[1px]">
                                    <span class="material-symbols-outlined">add</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right Column: Scanner Area (30%) -->
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
                                <p class="text-sm font-bold text-on-surface truncate pr-2 flex-grow">{{ item.name }}</p>
                                <button @click="removeFromCart(index)" class="text-error hover:text-error/80">
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
                            <input v-model.number="discount" class="w-32 h-8 bg-surface-container-low border border-outline-variant rounded px-2 text-right text-body-md focus:ring-1 focus:ring-primary focus:outline-none" placeholder="0" type="number" min="0"/>
                        </div>
                        <div class="flex justify-between items-center pt-3 mt-2 border-t-2 border-dashed border-outline-variant">
                            <span class="text-label-xl font-bold text-on-surface">TOTAL</span>
                            <span class="text-headline-md text-primary font-bold">{{ formatRupiah(totalAmount) }}</span>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <p class="text-xs font-bold text-outline uppercase tracking-tighter">Metode Pembayaran</p>
                            <div class="flex gap-2">
                                <button 
                                    @click="selectedPaymentMethod = 'Cash'"
                                    :class="selectedPaymentMethod === 'Cash' ? 'border-2 border-primary bg-primary-container text-on-primary-container' : 'border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 rounded font-bold text-sm flex items-center justify-center gap-1 active:translate-y-[1px] transition-all">
                                    <span class="material-symbols-outlined text-sm">payments</span> Cash
                                </button>
                                <button 
                                    @click="selectedPaymentMethod = 'QRIS'"
                                    :class="selectedPaymentMethod === 'QRIS' ? 'border-2 border-primary bg-primary-container text-on-primary-container' : 'border border-outline-variant bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 rounded font-bold text-sm flex items-center justify-center gap-1 active:translate-y-[1px] transition-all">
                                    <span class="material-symbols-outlined text-sm">qr_code_2</span> QRIS
                                </button>
                            </div>
                        </div>
                        
                        <button class="w-full h-14 mt-4 bg-primary text-on-primary rounded-lg text-label-xl font-bold flex items-center justify-center gap-2 hover:bg-primary-container shadow-lg active:translate-y-[1px] transition-all disabled:opacity-50 disabled:cursor-not-allowed" @click="openConfirmation" :disabled="cart.length === 0">
                            <span class="material-symbols-outlined">payments</span> BAYAR (F12)
                        </button>
                    </div>
                </div>
            </aside>
        </main>

        <!-- Visual Verification Modal (Confirmation) -->
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
                        <div class="space-y-2">
                            <div class="flex justify-between text-body-md text-on-surface-variant">
                                <span>Subtotal</span>
                                <span>{{ formatRupiah(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-body-md text-on-surface-variant">
                                <span>Diskon</span>
                                <span>{{ formatRupiah(discount) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-outline-variant">
                                <span class="text-label-md font-bold text-on-surface">TOTAL AKHIR</span>
                                <span class="text-headline-md text-primary font-bold">{{ formatRupiah(totalAmount) }}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded border border-outline-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">{{ selectedPaymentMethod === 'Cash' ? 'payments' : 'qr_code_2' }}</span>
                            <span class="text-label-md text-on-surface">Metode: <span class="font-bold">{{ selectedPaymentMethod }}</span></span>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button class="flex-1 h-12 border border-outline-variant bg-surface-container-low text-on-surface-variant rounded font-bold hover:bg-surface-container-high transition-all active:translate-y-[1px]" @click="closeConfirmation">Batal</button>
                            <button class="flex-1 h-12 bg-primary text-on-primary rounded font-bold shadow-md hover:bg-primary-container transition-all active:translate-y-[1px] flex justify-center items-center gap-2" @click="confirmPayment" :disabled="isSubmitting">
                                <span v-if="isSubmitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                                Konfirmasi &amp; Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Receipt Modal -->
        <Transition name="fade">
            <div v-if="showReceiptModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" id="receipt-modal">
                <div class="bg-white w-full max-w-sm rounded shadow-2xl overflow-hidden flex flex-col font-mono text-black p-6 border-t-8 border-primary">
                    <div class="text-center border-b border-dashed border-gray-300 pb-4 mb-4">
                        <h2 class="text-xl font-bold uppercase">STRUK PEMBAYARAN</h2>
                        <p class="text-xs">TOKO MATERIAL JAYA</p>
                        <p class="text-[10px]">Jl. Industri No. 45, Jakarta</p>
                    </div>
                    <div class="space-y-2 text-xs mb-4">
                        <template v-for="(item, idx) in cart" :key="idx">
                            <div class="flex justify-between">
                                <span>{{ item.name }}</span>
                                <span>{{ (item.qty * item.price).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="pl-4 italic">{{ item.qty }} x {{ item.price.toLocaleString('id-ID') }}</div>
                        </template>
                    </div>
                    <div class="border-t border-dashed border-gray-300 pt-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>SUBTOTAL</span>
                            <span>{{ subtotal.toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>DISKON</span>
                            <span>{{ discount.toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg pt-2">
                            <span>TOTAL</span>
                            <span>{{ formatRupiah(totalAmount) }}</span>
                        </div>
                    </div>
                    <div class="mt-6 text-center text-[10px] text-gray-500">
                        <p>Terima Kasih Atas Kunjungan Anda</p>
                        <p>Barang yang sudah dibeli tidak dapat ditukar</p>
                    </div>
                    <button class="mt-6 w-full h-12 bg-primary text-on-primary rounded font-bold font-sans active:translate-y-[1px] transition-transform" @click="closeReceipt">TUTUP</button>
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
</style>
