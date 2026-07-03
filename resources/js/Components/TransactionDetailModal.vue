<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    show: Boolean,
    transaction: Object, // detail data loaded from API
});

const emit = defineEmits(['close']);

const closeDetail = () => {
    emit('close');
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};
</script>

<template>
    <!-- Modal Detail Transaksi -->
    <Transition name="fade">
        <div v-if="props.show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" @click.self="closeDetail">
            <div class="bg-surface-container-lowest w-full max-w-lg rounded border border-outline-variant shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h2 class="text-label-xl font-bold text-on-surface">Detail Transaksi</h2>
                    <button @click="closeDetail" class="text-on-surface hover:text-error transition-colors flex items-center">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Fallback: No Transaction Object -->
                <div v-if="!props.transaction" class="p-8 text-center text-secondary">
                    <span class="material-symbols-outlined text-4xl mb-2 text-outline">error_outline</span>
                    <p class="font-bold">Data transaksi tidak tersedia.</p>
                </div>

                <!-- Transaction Detail Content -->
                <div v-else class="overflow-y-auto flex-1 p-6 space-y-4">
                    <!-- Header Info -->
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-secondary text-xs uppercase font-bold">No. Transaksi</p>
                            <p class="font-bold text-on-surface">TX-{{ String(props.transaction.id).padStart(6, '0') }}</p>
                        </div>
                        <div>
                            <p class="text-secondary text-xs uppercase font-bold">Waktu</p>
                            <p class="font-bold">{{ props.transaction.time || '-' }} | {{ props.transaction.date || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-secondary text-xs uppercase font-bold">Kasir</p>
                            <p class="font-bold">{{ props.transaction.cashier ?? 'Tidak diketahui' }}</p>
                        </div>
                        <div>
                            <p class="text-secondary text-xs uppercase font-bold">Metode Bayar</p>
                            <p class="font-bold">{{ props.transaction.payment_method?.toUpperCase() ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="border border-outline-variant rounded overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-container-high">
                                <tr>
                                    <th class="px-4 py-2 text-left font-bold text-secondary">Produk</th>
                                    <th class="px-4 py-2 text-right font-bold text-secondary">Qty</th>
                                    <th class="px-4 py-2 text-right font-bold text-secondary">Harga/Unit</th>
                                    <th class="px-4 py-2 text-right font-bold text-secondary">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                <!-- Fallback: Empty Items -->
                                <tr v-if="!props.transaction.items || props.transaction.items.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-secondary">
                                        Detail item transaksi tidak tersedia.
                                    </td>
                                </tr>
                                <tr v-else v-for="item in props.transaction.items" :key="item.product_id || item.product_name" class="hover:bg-surface-container-low">
                                    <td class="px-4 py-3 font-semibold text-on-surface">{{ item.product_name }}</td>
                                    <td class="px-4 py-3 text-right text-secondary">{{ item.qty }} {{ item.unit }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatRupiah(item.price) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-on-surface">{{ formatRupiah(item.subtotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="space-y-2 pt-2">
                        <div class="flex justify-between text-sm text-secondary">
                            <span>Subtotal</span>
                            <span>{{ formatRupiah(props.transaction.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-secondary">
                            <span>Diskon</span>
                            <span :class="props.transaction.discount > 0 ? 'text-error' : ''">
                                {{ props.transaction.discount > 0 ? '-' : '' }} {{ formatRupiah(props.transaction.discount) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-on-surface pt-2 border-t border-outline-variant">
                            <span>TOTAL</span>
                            <span class="text-primary">{{ formatRupiah(props.transaction.total) }}</span>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-dashed border-outline-variant my-3"></div>

                        <!-- Payment Details -->
                        <div class="pt-1 text-sm text-secondary space-y-1">
                            <div class="flex">
                                <span class="w-32 shrink-0">Metode Bayar</span>
                                <span>: {{ props.transaction.payment_method?.toUpperCase() ?? '-' }}</span>
                            </div>
                            <div v-if="props.transaction.payment_method === 'tunai'" class="flex">
                                <span class="w-32 shrink-0">Bayar</span>
                                <span>: {{ formatRupiah(props.transaction.cash_received) }}</span>
                            </div>
                            <div v-if="props.transaction.payment_method === 'tunai'" class="flex">
                                <span class="w-32 shrink-0">Kembalian</span>
                                <span :class="props.transaction.change > 0 ? 'font-semibold text-primary' : ''">: {{ formatRupiah(props.transaction.change) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-outline-variant">
                    <button @click="closeDetail" class="w-full h-12 border border-outline-variant bg-surface-container-low text-on-surface rounded font-bold hover:bg-surface-container-high transition-all">Tutup</button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
