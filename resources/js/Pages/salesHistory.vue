<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'

/**
 * Halaman: Sales History (Riwayat Penjualan)
 * Route disarankan: Inertia::render('Sales/History', [...])
 * Letakkan file ini di: resources/js/Pages/Sales/History.vue
 *
 * Catatan penting:
 * - Semua token visual (warna, spacing, tipografi) diambil PERSIS dari Dashboard.vue
 *   (bg-surface-container, text-headline-md/font-headline-md, text-label-md/font-label-md,
 *   border-outline-variant, material-symbols-outlined 24px, border-r-2, dst).
 * - Font "Material Symbols Outlined" harus sudah di-load global (sama seperti di Dashboard),
 *   biasanya lewat <link> di app.blade.php / layout utama:
 *   https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined
 * - Search & Pagination SUDAH tersambung ke backend lewat:
 *     Route::get('/sales-history', [TransactionController::class, 'history']);
 *   (route ini belum diberi nama/->name(), jadi dipanggil pakai path string langsung).
 *   Query param yang dikirim: `search` dan `page`.
 * - Export PDF MASIH placeholder (emit + toast), karena belum ada route terpisah untuk
 *   export di web.php. Begitu route-nya dibuat (mis. Route::get('/sales-history/export', ...)),
 *   tinggal ganti fungsi exportPdf() di bawah untuk redirect ke endpoint tsb.
 * - Filter juga masih placeholder (UI ada, belum ada logic/parameter yang disepakati).
 * - New Transaction & View Transaction dibuat sebagai VIEW INTERNAL (bukan route baru),
 *   supaya sidebar & layout tetap sama. currentView mengatur tampilan di dalam <main>.
 */

const SALES_HISTORY_URL = '/sales-history'

const props = defineProps({
  date: {
    type: String,
    default: 'Thursday, Oct 24, 2024',
  },
  stats: {
    type: Object,
    default: () => ({
      totalSalesToday: 42850000,
      salesGrowthPercent: 12,
      transactionCount: 148,
      averagePerTransaction: 289000,
      cashPercent: 65,
      qrisPercent: 35,
      mostSoldItem: {
        name: 'Portland Cement Type I',
        unitsSold: 42,
      },
    }),
  },
  transactions: {
    type: Array,
    default: () => [
      {
        id: 'TX-2410-0912',
        time: '14:22:10',
        items: [{ label: 'Cement x5' }, { label: 'Plywood 9mm x2' }],
        total: 1450000,
        payment: 'qris',
        discountStatus: '5% Member Disc',
      },
      {
        id: 'TX-2410-0911',
        time: '14:15:05',
        items: [{ label: 'PVC Pipe 3" x10' }, { label: 'Glue Solvent x1' }],
        total: 820000,
        payment: 'cash',
        discountStatus: null,
      },
      {
        id: 'TX-2410-0910',
        time: '14:02:44',
        items: [{ label: 'Sand 1m3 x1' }],
        total: 350000,
        payment: 'cash',
        discountStatus: '5% Member Disc',
      },
      {
        id: 'TX-2410-0909',
        time: '13:50:12',
        items: [{ label: 'Steel Rebar 12mm x50' }, { label: 'Binding Wire x2' }],
        total: 6120000,
        payment: 'qris',
        discountStatus: null,
      },
      {
        id: 'TX-2410-0908',
        time: '13:45:33',
        items: [{ label: 'Roof Tiles x200' }],
        total: 2400000,
        payment: 'cash',
        discountStatus: '5% Member Disc',
      },
    ],
  },
  pagination: {
    type: Object,
    default: () => ({
      currentPage: 1,
      lastPage: 3,
      totalEntries: 148,
      from: 1,
      to: 5,
    }),
  },
  user: {
    type: Object,
    default: () => ({
      name: 'Admin Staff',
      location: 'Main Warehouse',
    }),
  },
})

const emit = defineEmits(['view-transaction', 'new-transaction'])

const formatRupiah = (value) => 'Rp ' + Number(value ?? 0).toLocaleString('id-ID')

const pageNumbers = computed(() => {
  const pages = []
  for (let i = 1; i <= props.pagination.lastPage; i++) pages.push(i)
  return pages
})

/* ===================== TOAST (disamakan persis dengan Dashboard.vue) ===================== */
const toastMessage = ref('')
const showToast = ref(false)
let toastTimeout = null

const triggerToast = (message) => {
  toastMessage.value = message
  showToast.value = true
  if (toastTimeout) clearTimeout(toastTimeout)
  toastTimeout = setTimeout(() => {
    showToast.value = false
  }, 3000)
}

/* ===================== LOGOUT (disamakan persis dengan Dashboard.vue) ===================== */
const handleLogout = () => {
  triggerToast('Keluar dari sistem...')
  setTimeout(() => {
    router.visit('/')
  }, 800)
}

/* ===================== INTERNAL VIEW STATE ===================== */
// 'list' = tabel Sales History, 'new' = kasir/New Transaction, 'detail' = Transaction Detail
const currentView = ref('list')
const selectedTransaction = ref(null)

const backToHistory = () => {
  currentView.value = 'list'
  selectedTransaction.value = null
}

const startTransaction = () => {
  currentView.value = 'new'
  emit('new-transaction')
}

const openTransactionDetail = (trx) => {
  selectedTransaction.value = trx
  currentView.value = 'detail'
  emit('view-transaction', trx)
}

/* ===================== SIDEBAR NAVIGATION (logic sama persis dengan Dashboard.vue) ===================== */
// Halaman ini adalah Sales, jadi tab aktif default = 'sales'
const currentTab = ref('sales')

const setTab = (tab) => {
  currentTab.value = tab

  switch (tab) {
    case 'dashboard':
      router.visit('/dashboard')
      break

    case 'sales':
      // Sudah berada di halaman sales -> pastikan kembali ke daftar (bukan view new/detail)
      backToHistory()
      break

    case 'inventory':
      triggerToast('Menu Inventory sedang dalam pengembangan.')
      break

    case 'reports':
      triggerToast('Menu Reports sedang dalam pengembangan.')
      break

    case 'settings':
      triggerToast('Menu Settings sedang dalam pengembangan.')
      break
  }
}

/* ===================== SEARCH & PAGINATION (tersambung ke TransactionController@history) ===================== */
const searchQuery = ref('')

const doSearch = (value) => {
  searchQuery.value = value
  router.get(
    SALES_HISTORY_URL,
    { search: value, page: 1 },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

const changePage = (page) => {
  if (page < 1 || page > props.pagination.lastPage) return
  router.get(
    SALES_HISTORY_URL,
    { search: searchQuery.value, page },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

/* ===================== EXPORT PDF (masih placeholder, belum ada route di web.php) ===================== */
const exportPdf = () => {
  triggerToast('Export PDF belum terhubung: route export belum tersedia di web.php.')
  // Begitu route tersedia, ganti dengan misalnya:
  // window.location.href = `${SALES_HISTORY_URL}/export?search=${encodeURIComponent(searchQuery.value)}`
}

/* ===================== PLACEHOLDER "New Transaction" (belum terhubung ke backend) ===================== */
const cartItems = ref([])
const productQuery = ref('')

const addPlaceholderItem = () => {
  if (!productQuery.value.trim()) return
  cartItems.value.push({ id: Date.now(), name: productQuery.value.trim(), qty: 1, price: 0 })
  productQuery.value = ''
}

const removeCartItem = (id) => {
  cartItems.value = cartItems.value.filter((i) => i.id !== id)
}

const cartTotal = computed(() => cartItems.value.reduce((sum, i) => sum + i.qty * i.price, 0))

const saveTransaction = () => {
  triggerToast('Simpan transaksi belum terhubung ke backend.')
}
</script>

<template>
  <Head title="Sales History | Toko Material POS" />

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
    <aside class="hidden md:flex flex-col h-full py-base px-base space-y-2 bg-surface-container border-r-2 border-outline-variant w-64 shrink-0">
      <div class="px-4 py-6">
        <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Material POS</h1>
      </div>

      <div class="flex flex-col gap-1 flex-1">
        <button
          @click="setTab('dashboard')"
          :class="[
            'flex items-center gap-3 px-4 py-3 font-bold rounded-lg transition-all duration-100 active:scale-95 text-left w-full cursor-pointer',
            currentTab === 'dashboard' ? 'bg-secondary-container text-on-secondary-container' : 'text-secondary hover:bg-surface-container-high'
          ]"
        >
          <span class="material-symbols-outlined">dashboard</span>
          <span class="text-label-md font-label-md">Dashboard</span>
        </button>

        <button
          @click="setTab('inventory')"
          :class="[
            'flex items-center gap-3 px-4 py-3 font-bold rounded-lg transition-all duration-100 active:scale-95 text-left w-full cursor-pointer',
            currentTab === 'inventory' ? 'bg-secondary-container text-on-secondary-container' : 'text-secondary hover:bg-surface-container-high'
          ]"
        >
          <span class="material-symbols-outlined">inventory_2</span>
          <span class="text-label-md font-label-md">Inventory</span>
        </button>

        <button
          @click="setTab('sales')"
          :class="[
            'flex items-center gap-3 px-4 py-3 font-bold rounded-lg transition-all duration-100 active:scale-95 text-left w-full cursor-pointer',
            currentTab === 'sales' ? 'bg-secondary-container text-on-secondary-container' : 'text-secondary hover:bg-surface-container-high'
          ]"
        >
          <span class="material-symbols-outlined">point_of_sale</span>
          <span class="text-label-md font-label-md">Sales</span>
        </button>

        <button
          @click="setTab('reports')"
          :class="[
            'flex items-center gap-3 px-4 py-3 font-bold rounded-lg transition-all duration-100 active:scale-95 text-left w-full cursor-pointer',
            currentTab === 'reports' ? 'bg-secondary-container text-on-secondary-container' : 'text-secondary hover:bg-surface-container-high'
          ]"
        >
          <span class="material-symbols-outlined">analytics</span>
          <span class="text-label-md font-label-md">Reports</span>
        </button>

        <button
          @click="setTab('settings')"
          :class="[
            'flex items-center gap-3 px-4 py-3 font-bold rounded-lg transition-all duration-100 active:scale-95 text-left w-full cursor-pointer',
            currentTab === 'settings' ? 'bg-secondary-container text-on-secondary-container' : 'text-secondary hover:bg-surface-container-high'
          ]"
        >
          <span class="material-symbols-outlined">settings</span>
          <span class="text-label-md font-label-md">Settings</span>
        </button>
      </div>

      <!-- Profile & New Transaction Area -->
      <div class="mt-auto border-t border-outline-variant pt-4 pb-2 px-4 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center font-bold">
              {{ user.name.charAt(0) }}
            </div>
            <div>
              <p class="text-label-md font-label-md leading-none">{{ user.name }}</p>
              <p class="text-xs text-secondary mt-1">{{ user.location }}</p>
            </div>
          </div>
          <button @click="handleLogout" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Keluar dari sistem">
            logout
          </button>
        </div>
        <button
          @click="startTransaction"
          class="w-full bg-primary text-on-primary font-bold py-3 rounded-lg hover:brightness-95 transition-all active:translate-y-px cursor-pointer"
        >
          New Transaction
        </button>
      </div>
    </aside>

    <!-- Main Content Canvas -->
    <main class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop h-full bg-background relative pb-24 md:pb-8">

      <!-- ============ VIEW: LIST (Sales History) ============ -->
      <template v-if="currentView === 'list'">
        <header class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-gutter">
          <div>
            <h2 class="text-headline-md font-headline-md text-on-background">Sales History</h2>
            <p class="text-body-md font-body-md text-secondary">{{ date }}</p>
          </div>

          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 rounded-lg border-2 border-outline-variant bg-surface-container-lowest px-3 py-2 w-full md:w-72">
              <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">search</span>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search Transaction ID..."
                class="w-full bg-transparent text-body-md text-on-background placeholder:text-secondary focus:outline-none"
                @keyup.enter="doSearch(searchQuery)"
              />
            </div>
            <button
              type="button"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border-2 border-outline-variant text-secondary hover:bg-surface-container-high cursor-pointer"
              aria-label="Filter"
              @click="triggerToast('Filter belum tersedia: parameter filter belum disepakati dengan backend.')"
            >
              <span class="material-symbols-outlined" style="font-size: 20px;">filter_list</span>
            </button>
          </div>
        </header>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div class="rounded-xl border-2 border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-secondary">Total Sales Today</p>
            <p class="mt-2 text-headline-md font-headline-md text-primary">{{ formatRupiah(stats.totalSalesToday) }}</p>
            <p class="mt-2 flex items-center gap-1 text-xs font-semibold text-tertiary">
              <span class="material-symbols-outlined" style="font-size: 16px;">trending_up</span>
              {{ stats.salesGrowthPercent }}% vs yesterday
            </p>
          </div>

          <div class="rounded-xl border-2 border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-secondary">Transaction Count</p>
            <p class="mt-2 text-headline-md font-headline-md text-on-background">{{ stats.transactionCount }}</p>
            <p class="mt-2 text-xs font-semibold text-secondary">
              Average {{ formatRupiah(stats.averagePerTransaction) }} / tx
            </p>
          </div>

          <div class="rounded-xl border-2 border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-secondary">Cash vs QRIS</p>
            <div class="mt-3 flex items-center justify-between text-label-md font-label-md text-on-background">
              <span>Cash ({{ stats.cashPercent }}%)</span>
              <span>QRIS ({{ stats.qrisPercent }}%)</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
              <div class="flex h-full w-full">
                <div class="h-full bg-primary" :style="{ width: stats.cashPercent + '%' }" />
                <div class="h-full bg-tertiary" :style="{ width: stats.qrisPercent + '%' }" />
              </div>
            </div>
          </div>

          <div class="rounded-xl border-2 border-outline-variant bg-primary-fixed p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-primary">Most Sold Item</p>
            <p class="mt-2 text-label-xl font-label-xl leading-snug text-on-background">{{ stats.mostSoldItem.name }}</p>
            <p class="mt-1 text-xs text-secondary">{{ stats.mostSoldItem.unitsSold }} Units sold today</p>
          </div>
        </div>

        <!-- Recent transactions -->
        <div class="rounded-xl border-2 border-outline-variant bg-surface-container-lowest overflow-hidden">
          <div class="flex items-center justify-between px-6 py-5">
            <h3 class="text-headline-md font-headline-md text-on-background">Recent Transactions</h3>
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border-2 border-outline-variant px-3 py-2 text-label-md font-label-md text-on-background hover:bg-surface-container-high cursor-pointer"
              @click="exportPdf"
            >
              <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
              Export PDF
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[720px]">
              <thead>
                <tr class="bg-surface-container border-y border-outline-variant text-xs font-bold uppercase tracking-wide text-secondary">
                  <th class="px-6 py-3">ID / Time</th>
                  <th class="px-6 py-3">Items Purchased</th>
                  <th class="px-6 py-3">Total Price</th>
                  <th class="px-6 py-3">Payment</th>
                  <th class="px-6 py-3">Discount Status</th>
                  <th class="px-6 py-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="text-body-md text-on-background">
                <tr
                  v-for="(trx, index) in transactions"
                  :key="trx.id"
                  class="border-b border-outline-variant last:border-b-0 hover:bg-surface-container-low transition-colors"
                  :class="index % 2 === 1 ? 'bg-surface-container/40' : ''"
                >
                  <td class="px-6 py-4 align-top">
                    <p class="font-bold text-on-background">{{ trx.id }}</p>
                    <p class="text-xs text-secondary">{{ trx.time }}</p>
                  </td>
                  <td class="px-6 py-4 align-top">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="(item, i) in trx.items"
                        :key="i"
                        class="rounded-md border border-outline-variant px-2 py-1 text-xs text-secondary"
                      >
                        {{ item.label }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 align-top font-bold text-on-background">{{ formatRupiah(trx.total) }}</td>
                  <td class="px-6 py-4 align-top">
                    <span v-if="trx.payment === 'qris'" class="inline-flex items-center rounded-md bg-tertiary-fixed px-2 py-1 text-xs font-bold text-on-tertiary-fixed-variant">
                      QRIS
                    </span>
                    <span v-else class="inline-flex items-center gap-1 text-xs font-bold text-secondary">
                      <span class="material-symbols-outlined" style="font-size: 14px;">payments</span>
                      Cash
                    </span>
                  </td>
                  <td class="px-6 py-4 align-top">
                    <span v-if="trx.discountStatus" class="text-xs font-bold text-primary">{{ trx.discountStatus }}</span>
                    <span v-else class="text-xs text-secondary">No Discount</span>
                  </td>
                  <td class="px-6 py-4 align-top text-right">
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-secondary hover:bg-surface-container-high hover:text-on-background cursor-pointer"
                      aria-label="View transaction"
                      @click="openTransactionDetail(trx)"
                    >
                      <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-col md:flex-row items-center justify-between gap-3 px-6 py-4">
            <p class="text-body-md text-secondary">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.totalEntries }} entries
            </p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-outline-variant text-secondary hover:bg-surface-container-high disabled:opacity-40 cursor-pointer"
                :disabled="pagination.currentPage === 1"
                @click="changePage(pagination.currentPage - 1)"
              >
                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span>
              </button>

              <button
                v-for="page in pageNumbers"
                :key="page"
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-label-md font-label-md cursor-pointer"
                :class="page === pagination.currentPage
                  ? 'bg-primary text-on-primary'
                  : 'border-2 border-outline-variant text-on-background hover:bg-surface-container-high'"
                @click="changePage(page)"
              >
                {{ page }}
              </button>

              <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-outline-variant text-secondary hover:bg-surface-container-high disabled:opacity-40 cursor-pointer"
                :disabled="pagination.currentPage === pagination.lastPage"
                @click="changePage(pagination.currentPage + 1)"
              >
                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- ============ VIEW: NEW TRANSACTION (kasir sederhana) ============ -->
      <template v-else-if="currentView === 'new'">
        <header class="flex items-center gap-3 mb-gutter">
          <button
            @click="backToHistory"
            class="flex items-center gap-2 text-label-md font-label-md text-secondary hover:text-primary cursor-pointer"
          >
            <span class="material-symbols-outlined" style="font-size: 20px;">arrow_back</span>
            Kembali ke Sales History
          </button>
        </header>

        <div class="mb-6">
          <h2 class="text-headline-md font-headline-md text-on-background">New Transaction</h2>
          <p class="text-body-md font-body-md text-secondary">Tambahkan produk untuk membuat transaksi baru.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
          <!-- Product search / add -->
          <div class="lg:col-span-2 bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-6">
            <div class="flex items-center gap-2 rounded-lg border-2 border-outline-variant bg-surface px-3 py-2 mb-4">
              <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">inventory_2</span>
              <input
                v-model="productQuery"
                type="text"
                placeholder="Cari nama produk lalu tekan Enter..."
                class="w-full bg-transparent text-body-md text-on-background placeholder:text-secondary focus:outline-none"
                @keyup.enter="addPlaceholderItem"
              />
              <button
                @click="addPlaceholderItem"
                class="text-label-md font-label-md text-primary hover:underline cursor-pointer shrink-0"
              >
                Tambah
              </button>
            </div>

            <div v-if="cartItems.length === 0" class="text-body-md text-secondary text-center py-10">
              Belum ada produk ditambahkan.
            </div>

            <ul v-else class="divide-y divide-outline-variant">
              <li v-for="item in cartItems" :key="item.id" class="flex items-center justify-between py-3">
                <div>
                  <p class="text-label-md font-label-md text-on-background">{{ item.name }}</p>
                  <p class="text-xs text-secondary">Qty: {{ item.qty }} &middot; Harga belum diisi</p>
                </div>
                <button
                  @click="removeCartItem(item.id)"
                  class="material-symbols-outlined text-secondary hover:text-error cursor-pointer"
                  style="font-size: 20px;"
                >
                  delete
                </button>
              </li>
            </ul>
          </div>

          <!-- Cart summary -->
          <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-6 h-fit">
            <h3 class="text-label-xl font-label-xl text-on-background mb-4">Ringkasan Transaksi</h3>
            <div class="flex justify-between text-body-md text-secondary mb-2">
              <span>Jumlah item</span>
              <span>{{ cartItems.length }}</span>
            </div>
            <div class="flex justify-between text-headline-md font-headline-md text-on-background border-t border-outline-variant pt-4 mt-2">
              <span>Total</span>
              <span>{{ formatRupiah(cartTotal) }}</span>
            </div>

            <button
              @click="saveTransaction"
              class="w-full mt-6 bg-primary text-on-primary font-bold py-3 rounded-lg hover:brightness-95 transition-all active:translate-y-px cursor-pointer"
            >
              Simpan Transaksi
            </button>
            <button
              @click="backToHistory"
              class="w-full mt-2 border-2 border-outline-variant text-on-background font-bold py-3 rounded-lg hover:bg-surface-container-high transition-all cursor-pointer"
            >
              Batal
            </button>
          </div>
        </div>
      </template>

      <!-- ============ VIEW: TRANSACTION DETAIL ============ -->
      <template v-else-if="currentView === 'detail' && selectedTransaction">
        <header class="flex items-center gap-3 mb-gutter">
          <button
            @click="backToHistory"
            class="flex items-center gap-2 text-label-md font-label-md text-secondary hover:text-primary cursor-pointer"
          >
            <span class="material-symbols-outlined" style="font-size: 20px;">arrow_back</span>
            Kembali ke Sales History
          </button>
        </header>

        <div class="max-w-2xl bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-6">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h2 class="text-headline-md font-headline-md text-on-background">{{ selectedTransaction.id }}</h2>
              <p class="text-body-md text-secondary">{{ selectedTransaction.time }}</p>
            </div>
            <span
              v-if="selectedTransaction.payment === 'qris'"
              class="rounded-md bg-tertiary-fixed px-3 py-1 text-xs font-bold text-on-tertiary-fixed-variant"
            >
              QRIS
            </span>
            <span v-else class="rounded-md bg-surface-container-highest px-3 py-1 text-xs font-bold text-on-background">
              Cash
            </span>
          </div>

          <h3 class="text-label-xl font-label-xl text-on-background mb-3">Item Dibeli</h3>
          <ul class="divide-y divide-outline-variant mb-6">
            <li v-for="(item, i) in selectedTransaction.items" :key="i" class="py-3 text-body-md text-on-background">
              {{ item.label }}
            </li>
          </ul>

          <div class="border-t border-outline-variant pt-4 space-y-2">
            <div class="flex justify-between text-body-md text-secondary">
              <span>Status Diskon</span>
              <span :class="selectedTransaction.discountStatus ? 'text-primary font-bold' : ''">
                {{ selectedTransaction.discountStatus || 'No Discount' }}
              </span>
            </div>
            <div class="flex justify-between text-headline-md font-headline-md text-on-background">
              <span>Total</span>
              <span>{{ formatRupiah(selectedTransaction.total) }}</span>
            </div>
          </div>
        </div>
      </template>
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
        @click="setTab('sales')"
        :class="[
          'flex flex-col items-center justify-center rounded-full px-4 py-1 transition-all duration-200 active:scale-90',
          currentTab === 'sales' ? 'bg-primary text-on-primary font-bold' : 'text-secondary'
        ]"
      >
        <span class="material-symbols-outlined">point_of_sale</span>
        <span class="text-[10px] font-semibold">Sales</span>
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

  </div>
</template>

<style scoped>
.active-press:active {
  transform: translateY(1px);
}
</style>