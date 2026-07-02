<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    users: Array,
    categories: Array,
    units: Array,
});

// Toast
const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;
const triggerToast = (msg, type = 'info') => {
    toastMessage.value = msg;
    showToast.value = true;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => { showToast.value = false; }, 3500);
};
const handleLogout = () => {
    triggerToast('Keluar dari sistem...');
    setTimeout(() => { router.post('/logout'); }, 800);
};

const activeTab = ref('manajemen-user');

// ────────── Manajemen User ──────────
const showAddUserModal = ref(false);
const showEditUserModal = ref(false);
const showResetPwModal = ref(false);
const userForm = reactive({ name: '', username: '', password: '', role: 'karyawan' });
const editUserForm = reactive({ id: null, name: '', username: '', role: 'karyawan', is_active: true });
const resetPwForm = reactive({ id: null, name: '', password: '' });
const userErrors = ref({});
const isUserSubmitting = ref(false);

const openAddUser = () => {
    Object.assign(userForm, { name: '', username: '', password: '', role: 'karyawan' });
    userErrors.value = {};
    showAddUserModal.value = true;
};

const submitAddUser = () => {
    isUserSubmitting.value = true;
    userErrors.value = {};
    router.post('/users', userForm, {
        onSuccess: () => { showAddUserModal.value = false; triggerToast('User berhasil ditambahkan!'); },
        onError: (e) => { userErrors.value = e; },
        onFinish: () => { isUserSubmitting.value = false; },
    });
};

const openEditUser = (user) => {
    Object.assign(editUserForm, { id: user.id, name: user.name, username: user.username, role: user.role, is_active: user.is_active });
    userErrors.value = {};
    showEditUserModal.value = true;
};

const submitEditUser = () => {
    isUserSubmitting.value = true;
    userErrors.value = {};
    router.put(`/users/${editUserForm.id}`, editUserForm, {
        onSuccess: () => { showEditUserModal.value = false; triggerToast('User berhasil diperbarui!'); },
        onError: (e) => { userErrors.value = e; },
        onFinish: () => { isUserSubmitting.value = false; },
    });
};

const openResetPw = (user) => {
    Object.assign(resetPwForm, { id: user.id, name: user.name, password: '' });
    showResetPwModal.value = true;
};

const submitResetPw = () => {
    isUserSubmitting.value = true;
    router.post(`/users/${resetPwForm.id}/reset-password`, { password: resetPwForm.password }, {
        onSuccess: () => { showResetPwModal.value = false; triggerToast('Password berhasil direset!'); },
        onError: (e) => { triggerToast(e?.password || 'Gagal reset password.'); },
        onFinish: () => { isUserSubmitting.value = false; },
    });
};

const deactivateUser = (user) => {
    if (user.id === props.auth?.user?.id) {
        triggerToast('Tidak bisa menonaktifkan akun sendiri.');
        return;
    }
    if (confirm(`Nonaktifkan user "${user.name}"?`)) {
        router.delete(`/users/${user.id}`, {
            onSuccess: () => triggerToast('User berhasil dinonaktifkan.'),
        });
    }
};

// ────────── Manajemen Kategori ──────────
const showAddCatModal = ref(false);
const showEditCatModal = ref(false);
const catForm = reactive({ name: '' });
const editCatForm = reactive({ id: null, name: '' });
const catErrors = ref({});
const isCatSubmitting = ref(false);

const openAddCat = () => {
    catForm.name = '';
    catErrors.value = {};
    showAddCatModal.value = true;
};
const submitAddCat = () => {
    isCatSubmitting.value = true;
    router.post('/categories', catForm, {
        onSuccess: () => { showAddCatModal.value = false; triggerToast('Kategori berhasil ditambahkan!'); },
        onError: (e) => { catErrors.value = e; },
        onFinish: () => { isCatSubmitting.value = false; },
    });
};

const openEditCat = (cat) => {
    Object.assign(editCatForm, { id: cat.id, name: cat.name });
    catErrors.value = {};
    showEditCatModal.value = true;
};
const submitEditCat = () => {
    isCatSubmitting.value = true;
    router.put(`/categories/${editCatForm.id}`, editCatForm, {
        onSuccess: () => { showEditCatModal.value = false; triggerToast('Kategori berhasil diperbarui!'); },
        onError: (e) => { catErrors.value = e; },
        onFinish: () => { isCatSubmitting.value = false; },
    });
};
const deleteCategory = (cat) => {
    if (confirm(`Hapus kategori "${cat.name}"? Hanya bisa dihapus jika tidak ada produk aktif.`)) {
        router.delete(`/categories/${cat.id}`, {
            onSuccess: () => triggerToast('Kategori dihapus.'),
            onError: () => triggerToast('Gagal: Kategori masih digunakan produk aktif.'),
        });
    }
};

// ────────── Manajemen Satuan ──────────
const showAddUnitModal = ref(false);
const showEditUnitModal = ref(false);
const isUnitSubmitting = ref(false);
const unitForm = reactive({ name: '', symbol: '' });
const editUnitForm = reactive({ id: null, name: '', symbol: '' });
const unitErrors = ref({});

const openAddUnit = () => {
    unitForm.name = '';
    unitForm.symbol = '';
    unitErrors.value = {};
    showAddUnitModal.value = true;
};
const submitAddUnit = () => {
    isUnitSubmitting.value = true;
    unitErrors.value = {};
    router.post('/units', unitForm, {
        onSuccess: () => { showAddUnitModal.value = false; triggerToast('Satuan berhasil ditambahkan!'); },
        onError: (err) => { unitErrors.value = err; },
        onFinish: () => { isUnitSubmitting.value = false; },
    });
};

const openEditUnit = (unit) => {
    Object.assign(editUnitForm, { id: unit.id, name: unit.name, symbol: unit.symbol || '' });
    unitErrors.value = {};
    showEditUnitModal.value = true;
};
const submitEditUnit = () => {
    isUnitSubmitting.value = true;
    unitErrors.value = {};
    router.put(`/units/${editUnitForm.id}`, editUnitForm, {
        onSuccess: () => { showEditUnitModal.value = false; triggerToast('Satuan berhasil diperbarui!'); },
        onError: (err) => { unitErrors.value = err; },
        onFinish: () => { isUnitSubmitting.value = false; },
    });
};

const deleteUnit = (unit) => {
    if (confirm(`Hapus satuan "${unit.name}"? Hanya bisa dihapus jika tidak digunakan oleh produk mana pun.`)) {
        router.delete(`/units/${unit.id}`, {
            onSuccess: () => triggerToast('Satuan berhasil dihapus.'),
            onError: (err) => {
                if (err.error) {
                    triggerToast('Gagal: ' + err.error);
                } else if (err.name) {
                    triggerToast('Gagal: ' + err.name);
                } else {
                    triggerToast('Gagal menghapus satuan.');
                }
            }
        });
    }
};

// ────────── Backup & Data ──────────
const backups = ref([]);
const isLoadingBackups = ref(false);
const isCreatingBackup = ref(false);

const loadBackups = () => {
    isLoadingBackups.value = true;
    fetch('/backups', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        }
    })
        .then(r => r.json())
        .then(data => { backups.value = data; })
        .catch(() => triggerToast('Gagal memuat daftar backup.'))
        .finally(() => { isLoadingBackups.value = false; });
};

const createBackup = () => {
    isCreatingBackup.value = true;
    fetch('/backups/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                triggerToast('Backup berhasil dibuat!');
                loadBackups();
            } else {
                triggerToast(data.message || 'Gagal membuat backup.');
            }
        })
        .catch(() => triggerToast('Gagal terhubung ke server.'))
        .finally(() => { isCreatingBackup.value = false; });
};

const downloadBackup = (backup) => {
    window.location.href = `/backups/${backup.id}/download`;
};

// Restore Database
const restoreFile = ref(null);
const restoreConfirmationText = ref('');
const isRestoring = ref(false);
const showRestoreModal = ref(false);

const handleRestoreFileChange = (e) => {
    restoreFile.value = e.target.files[0];
};

const triggerRestore = () => {
    if (!restoreFile.value) {
        triggerToast('Silakan pilih file backup terlebih dahulu.');
        return;
    }
    if (restoreConfirmationText.value !== 'PULIHKAN') {
        triggerToast('Harap ketik "PULIHKAN" untuk konfirmasi.');
        return;
    }

    isRestoring.value = true;
    const formData = new FormData();
    formData.append('backup_file', restoreFile.value);
    formData.append('confirmation', restoreConfirmationText.value);

    router.post('/backups/restore', formData, {
        onSuccess: () => {
            triggerToast('Database berhasil dipulihkan!');
            showRestoreModal.value = false;
            restoreFile.value = null;
            restoreConfirmationText.value = '';
        },
        onError: (err) => {
            triggerToast(err.backup_file || err.confirmation || 'Gagal memulihkan database.');
        },
        onFinish: () => {
            isRestoring.value = false;
        }
    });
};


// Load backups saat tab aktif
const switchTab = (tab) => {
    activeTab.value = tab;
    if (tab === 'backup-data') loadBackups();
};

// ────────── Link Telegram ──────────
const telegramChatId = ref(props.auth?.user?.telegram_chat_id || '');
const isLinkingTelegram = ref(false);

const linkTelegram = () => {
    if (!telegramChatId.value) { triggerToast('Masukkan Chat ID Telegram.'); return; }
    isLinkingTelegram.value = true;
    router.post('/pengaturan/telegram', { telegram_chat_id: telegramChatId.value }, {
        onSuccess: () => triggerToast('Telegram berhasil dihubungkan!'),
        onError: () => triggerToast('Gagal menghubungkan Telegram.'),
        onFinish: () => { isLinkingTelegram.value = false; },
    });
};
</script>

<template>
    <Head title="Settings - Toko Rukun Jaya" />

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
            <div v-if="showToast" class="fixed top-4 right-4 z-50 max-w-sm bg-inverse-surface text-inverse-on-surface px-4 py-3 rounded border border-outline flex items-center gap-3">
                <span class="material-symbols-outlined text-primary-fixed">info</span>
                <span class="text-sm font-semibold">{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- SIDE NAVBAR (Desktop) -->
        <aside class="hidden md:flex flex-col h-full w-64 bg-surface-container border-r-2 border-outline-variant py-base px-base space-y-2 shrink-0 z-30">
            <div class="px-4 py-6">
                <h1 class="text-headline-md font-headline-md text-primary font-bold">Toko Rukun Jaya</h1>
            </div>
            
            <div class="flex flex-col gap-1 flex-1">
                <Link href="/dashboard" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </Link>
                <Link href="/inventaris" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer text-secondary hover:bg-surface-container-high text-label-md font-label-md">
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
                <Link href="/pengaturan" class="flex items-center gap-3 px-4 min-h-[48px] font-bold rounded transition-all active:translate-y-[1px] text-left w-full cursor-pointer bg-secondary-container text-on-secondary-container text-label-md font-label-md">
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
        <main class="flex-1 flex flex-col min-w-0 h-screen bg-background overflow-hidden">
            <header class="flex justify-between items-center w-full px-margin-desktop h-touch-target-min bg-surface border-b-2 border-outline-variant">
                <div class="flex items-center gap-4">
                    <span class="text-headline-md font-headline-md font-bold text-primary">Pengaturan Sistem</span>
                </div>
            </header>

            <div class="flex-1 flex overflow-hidden">
                <!-- SETTINGS CATEGORIES (Vertical Tabs) -->
                <nav class="w-72 bg-surface-container-low border-r border-outline-variant overflow-y-auto shrink-0">
                    <div class="p-4">
                        <p class="text-[12px] font-bold text-secondary uppercase tracking-wider mb-4 px-2">Kategori Utama</p>
                        <div class="space-y-1">
                            <button @click="switchTab('manajemen-user')" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'manajemen-user' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">group</span>
                                <span class="font-label-md text-label-md">Manajemen User</span>
                            </button>
                             <button @click="switchTab('manajemen-kategori')" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'manajemen-kategori' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">category</span>
                                <span class="font-label-md text-label-md">Manajemen Kategori</span>
                            </button>
                            <button @click="switchTab('manajemen-satuan')" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'manajemen-satuan' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">straighten</span>
                                <span class="font-label-md text-label-md">Manajemen Satuan</span>
                            </button>
                            <button @click="switchTab('telegram')" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'telegram' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">send</span>
                                <span class="font-label-md text-label-md">Notifikasi Telegram</span>
                            </button>
                            <button @click="switchTab('backup-data')" :class="['w-full flex items-center space-x-3 px-4 py-4 text-left transition-all', activeTab === 'backup-data' ? 'active-tab font-bold' : 'text-secondary hover:bg-surface-container']">
                                <span class="material-symbols-outlined">database</span>
                                <span class="font-label-md text-label-md">Backup & Data</span>
                            </button>
                        </div>
                    </div>
                </nav>

                <!-- FORM CONTENT AREA -->
                <div class="flex-1 overflow-y-auto p-margin-desktop bg-background">
                    <div class="max-w-4xl mx-auto pb-24 md:pb-8">

                        <!-- 1. Manajemen User -->
                        <section v-if="activeTab === 'manajemen-user'" class="space-y-gutter">
                            <div class="flex justify-between items-end border-b border-outline-variant pb-base mb-base">
                                <div>
                                    <h3 class="text-label-xl font-label-xl text-on-surface">Manajemen User & Akses</h3>
                                    <p class="text-body-md text-secondary">Kelola daftar staf dan hak akses sistem.</p>
                                </div>
                                <button @click="openAddUser" class="btn-primary-industrial font-bold px-6 py-2 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">person_add</span>
                                    <span>Tambah User</span>
                                </button>
                            </div>
                            <div class="bg-surface border-2 border-outline-variant rounded-xl overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-surface-container-high border-b border-outline-variant">
                                        <tr>
                                            <th class="px-6 py-4 text-label-md font-label-md">Nama Staf</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Role</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Status</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Dibuat</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <tr v-if="!props.users || props.users.length === 0">
                                            <td colspan="5" class="px-6 py-8 text-center text-secondary">Belum ada user terdaftar.</td>
                                        </tr>
                                        <tr v-for="(user, index) in props.users" :key="user.id" :class="['hover:bg-surface-container-low transition-colors', index % 2 !== 0 ? 'bg-surface-container-lowest' : '']">
                                            <td class="px-6 py-4">
                                                <div class="font-bold">{{ user.name }}</div>
                                                <div class="text-[12px] text-secondary">@{{ user.username }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span :class="['px-3 py-1 rounded-full text-[12px] font-bold', user.role === 'owner' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-secondary-fixed text-on-secondary-fixed-variant']">
                                                    {{ user.role === 'owner' ? 'Owner' : 'Karyawan' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span :class="['flex items-center space-x-1', user.is_active ? 'text-green-600' : 'text-error']">
                                                    <span :class="['w-2 h-2 rounded-full', user.is_active ? 'bg-green-600' : 'bg-error']"></span>
                                                    <span>{{ user.is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-secondary">{{ user.created_at }}</td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2">
                                                    <button @click="openEditUser(user)" class="material-symbols-outlined text-secondary hover:text-primary transition-colors cursor-pointer" title="Edit User">edit</button>
                                                    <button @click="openResetPw(user)" class="material-symbols-outlined text-secondary hover:text-warning transition-colors cursor-pointer" title="Reset Password">lock_reset</button>
                                                    <button @click="deactivateUser(user)" :disabled="user.id === props.auth?.user?.id" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer disabled:opacity-30" title="Nonaktifkan">delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                         <!-- 2. Manajemen Kategori -->
                        <section v-if="activeTab === 'manajemen-kategori'" class="space-y-gutter">
                            <div class="flex justify-between items-end border-b border-outline-variant pb-base mb-base">
                                <div>
                                    <h3 class="text-label-xl font-label-xl text-on-surface">Manajemen Kategori Produk</h3>
                                    <p class="text-body-md text-secondary">Kelola kategori untuk mengorganisir produk inventaris.</p>
                                </div>
                                <button @click="openAddCat" class="btn-primary-industrial font-bold px-6 py-2 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">add</span>
                                    <span>Tambah Kategori</span>
                                </button>
                            </div>
                            <div class="bg-surface border-2 border-outline-variant rounded-xl overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-surface-container-high border-b border-outline-variant">
                                        <tr>
                                            <th class="px-6 py-4 text-label-md font-label-md">Nama Kategori</th>
                                            <th class="px-6 py-4 text-label-md font-label-md text-right">Jumlah Produk</th>
                                            <th class="px-6 py-4 text-label-md font-label-md text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <tr v-if="!props.categories || props.categories.length === 0">
                                            <td colspan="3" class="px-6 py-8 text-center text-secondary">Belum ada kategori.</td>
                                        </tr>
                                        <tr v-for="cat in props.categories" :key="cat.id" class="hover:bg-surface-container-low transition-colors">
                                            <td class="px-6 py-4 font-bold text-on-surface">{{ cat.name }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="bg-surface-container-high px-3 py-1 rounded-full text-sm font-bold">{{ cat.products_count || 0 }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button @click="openEditCat(cat)" class="material-symbols-outlined text-secondary hover:text-primary transition-colors cursor-pointer" title="Edit">edit</button>
                                                    <button @click="deleteCategory(cat)" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Hapus">delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- 2.5. Manajemen Satuan -->
                        <section v-if="activeTab === 'manajemen-satuan'" class="space-y-gutter">
                            <div class="flex justify-between items-end border-b border-outline-variant pb-base mb-base">
                                <div>
                                    <h3 class="text-label-xl font-label-xl text-on-surface">Manajemen Master Satuan</h3>
                                    <p class="text-body-md text-secondary">Kelola master satuan global untuk inventori produk.</p>
                                </div>
                                <button @click="openAddUnit" class="btn-primary-industrial font-bold px-6 py-2 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">add</span>
                                    <span>Tambah Satuan</span>
                                </button>
                            </div>
                            <div class="bg-surface border-2 border-outline-variant rounded-xl overflow-hidden">
                                <table class="w-full text-left">
                                    <thead class="bg-surface-container-high border-b border-outline-variant">
                                        <tr>
                                            <th class="px-6 py-4 text-label-md font-label-md">Nama Satuan</th>
                                            <th class="px-6 py-4 text-label-md font-label-md">Simbol / Singkatan</th>
                                            <th class="px-6 py-4 text-label-md font-label-md text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <tr v-if="!props.units || props.units.length === 0">
                                            <td colspan="3" class="px-6 py-8 text-center text-secondary">Belum ada satuan.</td>
                                        </tr>
                                        <tr v-for="unit in props.units" :key="unit.id" class="hover:bg-surface-container-low transition-colors">
                                            <td class="px-6 py-4 font-bold text-on-surface">{{ unit.name }}</td>
                                            <td class="px-6 py-4 text-secondary">{{ unit.symbol || '-' }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button @click="openEditUnit(unit)" class="material-symbols-outlined text-secondary hover:text-primary transition-colors cursor-pointer" title="Edit">edit</button>
                                                    <button @click="deleteUnit(unit)" class="material-symbols-outlined text-secondary hover:text-error transition-colors cursor-pointer" title="Hapus">delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- 3. Telegram -->
                        <section v-if="activeTab === 'telegram'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Notifikasi Telegram</h3>
                                <p class="text-body-md text-secondary">Hubungkan akun Telegram untuk menerima notifikasi restock, stok kritis, dan backup.</p>
                            </div>
                            <div class="bg-surface p-gutter border-2 border-outline-variant rounded-xl space-y-6 max-w-lg">
                                <div class="p-4 bg-surface-container-low rounded border border-outline-variant text-sm text-secondary space-y-2">
                                    <p class="font-bold text-on-surface">Cara mendapatkan Chat ID:</p>
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Buka Telegram, cari bot <code class="bg-surface-container px-1 rounded">@userinfobot</code></li>
                                        <li>Kirim pesan <code class="bg-surface-container px-1 rounded">/start</code></li>
                                        <li>Bot akan membalas dengan Chat ID Anda</li>
                                        <li>Masukkan angka tersebut di kolom bawah</li>
                                    </ol>
                                </div>
                                
                                <div v-if="props.auth?.user?.telegram_chat_id" class="p-3 bg-green-500/10 text-green-700 border border-green-500/20 rounded-lg text-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    <span>Status: <strong class="text-green-800">Terhubung</strong> (ID Aktif: {{ props.auth.user.telegram_chat_id }})</span>
                                </div>
                                <div v-else class="p-3 bg-surface-container-low border border-outline-variant rounded-lg text-sm flex items-center gap-2 text-secondary">
                                    <span class="material-symbols-outlined text-sm">info</span>
                                    <span>Status: <strong>Belum Terhubung</strong></span>
                                </div>

                                <div>
                                    <label class="block text-label-md font-label-md text-on-surface mb-2">Chat ID Telegram</label>
                                    <input v-model="telegramChatId" type="text" class="w-full px-4 py-3 bg-surface border-2 border-outline-variant rounded-lg font-body-md focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" placeholder="Contoh: 123456789">
                                </div>
                                <button @click="linkTelegram" :disabled="isLinkingTelegram" class="btn-primary-industrial font-bold px-8 py-3 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">send</span>
                                    <span>{{ isLinkingTelegram ? 'Menghubungkan...' : 'Hubungkan Telegram' }}</span>
                                </button>
                            </div>
                        </section>

                        <!-- 4. Backup & Data -->
                        <section v-if="activeTab === 'backup-data'" class="space-y-gutter">
                            <div class="border-b border-outline-variant pb-base mb-base">
                                <h3 class="text-label-xl font-label-xl text-on-surface">Keamanan & Backup Data</h3>
                                <p class="text-body-md text-secondary">Buat dan kelola backup database. Backup otomatis setiap 7 hari.</p>
                            </div>

                            <!-- Create Backup -->
                            <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-gutter flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-tertiary-container text-white rounded-full flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[32px]">cloud_upload</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-headline-md">Buat Backup Sekarang</h4>
                                        <p class="text-body-md text-secondary">Download backup database lengkap dalam format .sql</p>
                                    </div>
                                </div>
                                <button @click="createBackup" :disabled="isCreatingBackup" class="btn-primary-industrial font-bold px-8 py-3 rounded-lg flex items-center space-x-2 disabled:opacity-50">
                                    <span v-if="isCreatingBackup" class="material-symbols-outlined animate-spin">progress_activity</span>
                                    <span v-else class="material-symbols-outlined">download</span>
                                    <span>{{ isCreatingBackup ? 'Membuat...' : 'Buat Backup' }}</span>
                                </button>
                            </div>

                            <!-- Restore Database -->
                            <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-xl p-gutter flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-error-container text-error rounded-full flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[32px]">settings_backup_restore</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-headline-md">Restore Database</h4>
                                        <p class="text-body-md text-secondary">Pulihkan database dari file backup .sql manual.</p>
                                    </div>
                                </div>
                                <button @click="showRestoreModal = true" class="border-2 border-error text-error hover:bg-error-container/10 font-bold px-8 py-3 rounded-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined">restore</span>
                                    <span>Restore Data</span>
                                </button>
                            </div>

                            <!-- Backup List -->
                            <div class="bg-surface border-2 border-outline-variant rounded-xl overflow-hidden">
                                <div class="p-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                                    <h4 class="font-bold text-on-surface">Daftar Backup Tersedia</h4>
                                    <button @click="loadBackups" class="text-sm text-primary font-bold flex items-center gap-1 hover:underline">
                                        <span class="material-symbols-outlined text-sm">refresh</span> Refresh
                                    </button>
                                </div>
                                <div v-if="isLoadingBackups" class="p-6 text-center text-secondary">
                                    <span class="material-symbols-outlined animate-spin">progress_activity</span>
                                </div>
                                <div v-else-if="backups.length === 0" class="p-6 text-center text-secondary">
                                    Belum ada backup. Klik "Buat Backup" untuk membuat backup pertama.
                                </div>
                                <table v-else class="w-full text-left">
                                    <thead class="bg-surface-container text-sm text-secondary">
                                        <tr>
                                            <th class="px-4 py-3">Nama File</th>
                                            <th class="px-4 py-3">Ukuran</th>
                                            <th class="px-4 py-3">Dibuat</th>
                                            <th class="px-4 py-3 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <tr v-for="backup in backups" :key="backup.id" class="hover:bg-surface-container-low">
                                            <td class="px-4 py-3 font-mono text-sm">{{ backup.filename }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary">{{ backup.file_size }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary">{{ backup.created_at }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <button @click="downloadBackup(backup)" class="text-primary font-bold text-sm hover:underline flex items-center gap-1 ml-auto">
                                                    <span class="material-symbols-outlined text-sm">download</span> Download
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

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
                <Link href="/pengaturan" class="flex flex-col items-center justify-center bg-primary text-on-primary rounded-full px-4 py-1 cursor-pointer">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="text-[10px] font-bold">Settings</span>
                </Link>
            </nav>
        </main>
    </div>

    <!-- ===== MODALS ===== -->

    <!-- Modal Tambah User -->
    <Transition name="fade">
        <div v-if="showAddUserModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showAddUserModal = false">
            <div class="bg-surface w-full max-w-md border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Tambah User Baru</h3>
                    <button @click="showAddUserModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Lengkap *</label>
                        <input v-model="userForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Nama lengkap" required>
                        <p v-if="userErrors.name" class="text-error text-xs mt-1">{{ userErrors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Username *</label>
                        <input v-model="userForm.username" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="username (tanpa spasi)" required>
                        <p v-if="userErrors.username" class="text-error text-xs mt-1">{{ userErrors.username }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Password *</label>
                        <input v-model="userForm.password" type="password" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Min. 6 karakter" required>
                        <p v-if="userErrors.password" class="text-error text-xs mt-1">{{ userErrors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Role *</label>
                        <select v-model="userForm.role" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary">
                            <option value="karyawan">Karyawan</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showAddUserModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitAddUser" :disabled="isUserSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isUserSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Edit User -->
    <Transition name="fade">
        <div v-if="showEditUserModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showEditUserModal = false">
            <div class="bg-surface w-full max-w-md border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Edit User</h3>
                    <button @click="showEditUserModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Lengkap *</label>
                        <input v-model="editUserForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Username *</label>
                        <input v-model="editUserForm.username" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                        <p v-if="userErrors.username" class="text-error text-xs mt-1">{{ userErrors.username }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Role *</label>
                        <select v-model="editUserForm.role" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary">
                            <option value="karyawan">Karyawan</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <input v-model="editUserForm.is_active" type="checkbox" id="is-active" class="w-4 h-4">
                        <label for="is-active" class="text-sm font-bold text-secondary cursor-pointer">Akun Aktif</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showEditUserModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitEditUser" :disabled="isUserSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isUserSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Reset Password -->
    <Transition name="fade">
        <div v-if="showResetPwModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showResetPwModal = false">
            <div class="bg-surface w-full max-w-md border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Reset Password — {{ resetPwForm.name }}</h3>
                    <button @click="showResetPwModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Password Baru *</label>
                        <input v-model="resetPwForm.password" type="password" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showResetPwModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitResetPw" :disabled="isUserSubmitting" class="bg-warning text-white px-6 py-2.5 rounded font-bold hover:brightness-90 disabled:opacity-50">
                            {{ isUserSubmitting ? 'Mereset...' : 'Reset Password' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Tambah Kategori -->
    <Transition name="fade">
        <div v-if="showAddCatModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showAddCatModal = false">
            <div class="bg-surface w-full max-w-sm border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Tambah Kategori</h3>
                    <button @click="showAddCatModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Kategori *</label>
                        <input v-model="catForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Contoh: Semen, Pipa, Cat..." required>
                        <p v-if="catErrors.name" class="text-error text-xs mt-1">{{ catErrors.name }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button @click="showAddCatModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitAddCat" :disabled="isCatSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isCatSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Edit Kategori -->
    <Transition name="fade">
        <div v-if="showEditCatModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showEditCatModal = false">
            <div class="bg-surface w-full max-w-sm border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Edit Kategori</h3>
                    <button @click="showEditCatModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Kategori *</label>
                        <input v-model="editCatForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                        <p v-if="catErrors.name" class="text-error text-xs mt-1">{{ catErrors.name }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button @click="showEditCatModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitEditCat" :disabled="isCatSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isCatSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Tambah Satuan -->
    <Transition name="fade">
        <div v-if="showAddUnitModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showAddUnitModal = false">
            <div class="bg-surface w-full max-w-sm border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Tambah Satuan</h3>
                    <button @click="showAddUnitModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Satuan *</label>
                        <input v-model="unitForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Contoh: sak, kg, pcs, box..." required>
                        <p v-if="unitErrors.name" class="text-error text-xs mt-1">{{ unitErrors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Simbol / Singkatan</label>
                        <input v-model="unitForm.symbol" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" placeholder="Contoh: kg, sak, box">
                        <p v-if="unitErrors.symbol" class="text-error text-xs mt-1">{{ unitErrors.symbol }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button @click="showAddUnitModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitAddUnit" :disabled="isUnitSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isUnitSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Edit Satuan -->
    <Transition name="fade">
        <div v-if="showEditUnitModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showEditUnitModal = false">
            <div class="bg-surface w-full max-w-sm border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Edit Satuan</h3>
                    <button @click="showEditUnitModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Nama Satuan *</label>
                        <input v-model="editUnitForm.name" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary" required>
                        <p v-if="unitErrors.name" class="text-error text-xs mt-1">{{ unitErrors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Simbol / Singkatan</label>
                        <input v-model="editUnitForm.symbol" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary">
                        <p v-if="unitErrors.symbol" class="text-error text-xs mt-1">{{ unitErrors.symbol }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button @click="showEditUnitModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="submitEditUnit" :disabled="isUnitSubmitting" class="btn-primary-industrial px-6 py-2.5 rounded font-bold disabled:opacity-50">
                            {{ isUnitSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Restore Database -->
    <Transition name="fade">
        <div v-if="showRestoreModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showRestoreModal = false">
            <div class="bg-surface w-full max-w-md border-2 border-outline rounded-xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-high border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-bold text-on-surface">Restore Database</h3>
                    <button @click="showRestoreModal = false" class="text-secondary hover:text-error"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-error-container/20 text-on-surface border border-error/20 rounded-lg text-sm space-y-2">
                        <p class="font-bold text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">warning</span> PERINGATAN KONTEN RUSAK!
                        </p>
                        <p class="text-xs text-secondary leading-relaxed">
                            Proses ini akan menimpa seluruh database saat ini dengan isi file backup yang Anda upload. Operasi ini merusak data yang ada dan tidak dapat dibatalkan (undo).
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">File SQL Backup *</label>
                        <input type="file" accept=".sql,.txt" @change="handleRestoreFileChange" class="w-full p-2 border-2 border-outline-variant rounded bg-white text-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-secondary mb-1">Ketik "PULIHKAN" untuk Konfirmasi *</label>
                        <input v-model="restoreConfirmationText" type="text" class="w-full p-3 border-2 border-outline-variant bg-white rounded focus:ring-0 focus:border-primary font-mono uppercase" placeholder="PULIHKAN" required>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showRestoreModal = false" class="px-5 py-2.5 border border-outline-variant rounded font-bold text-secondary hover:bg-surface-container-low">Batal</button>
                        <button @click="triggerRestore" :disabled="isRestoring || !restoreFile || restoreConfirmationText !== 'PULIHKAN'" class="bg-error text-on-error px-6 py-2.5 rounded font-bold hover:bg-error/90 transition-colors disabled:opacity-50 flex items-center gap-2">
                            <span v-if="isRestoring" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                            <span>{{ isRestoring ? 'Memulihkan...' : 'Pulihkan Database' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.active-tab {
    background-color: var(--color-secondary-container, #d5e0f8);
    color: var(--color-on-secondary-container, #586377);
    border-left: 4px solid var(--color-primary, #9e4300);
}

.btn-primary-industrial {
    background-color: #9e4300;
    color: #ffffff;
    font-weight: 700;
    transition: all 150ms ease;
    border: none;
    cursor: pointer;
}
.btn-primary-industrial:hover {
    background-color: #8e3c00;
}
.btn-primary-industrial:active {
    transform: translateY(1px);
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
