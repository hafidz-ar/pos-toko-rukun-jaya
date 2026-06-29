<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
});

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const handleLogin = () => {
    // In a real application, you would post to a login route
    // form.post(route('login'));
    
    // Simulate successful login
    form.processing = true;
    setTimeout(() => {
        form.processing = false;
        console.log("Authentication successful for ID:", form.username);
    }, 1200);
};
</script>

<template>
    <Head title="Login | Toko Material POS" />
    
    <div class="fixed inset-0 bg-industrial flex items-center justify-center p-4 md:p-6 overflow-y-auto">
        <!-- Login Container -->
        <main class="w-full max-w-[420px] flex flex-col items-center my-auto py-4">
            
            <!-- Store Logo Area -->
            <div class="mb-6 md:mb-8 flex flex-col items-center animate-fade-in">
                <div class="w-16 h-16 md:w-20 md:h-20 mb-3 md:mb-4 bg-primary flex items-center justify-center rounded-xl shadow-md">
                    <span class="material-symbols-outlined text-on-primary !text-[36px] md:!text-[44px]" style="font-variation-settings: 'FILL' 1;">
                        construction
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-primary tracking-tight text-center">
                    Toko Material POS
                </h1>
                <p class="text-xs md:text-sm text-secondary mt-1">Industrial Excellence &amp; Precision</p>
            </div>
            
            <!-- Authentication Card -->
            <div class="w-full bg-surface-container-lowest border-2 border-outline-variant p-6 md:p-8 rounded-lg shadow-sm">
                <header class="mb-2">
                    <h2 class="text-xl md:text-2xl font-semibold text-on-surface">
                        Masuk ke Sistem
                    </h2>
                    <div class="h-1 w-12 bg-primary mt-1.5 rounded-full"></div>
                </header>
                
                <form class="space-y-4 md:space-y-5 mt-6" @submit.prevent="handleLogin">
                    <!-- ID Pengguna Input -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider" for="username">
                            ID Pengguna
                        </label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-secondary group-focus-within:text-primary transition-colors">
                                badge
                            </span>
                            <input 
                                id="username" 
                                v-model="form.username"
                                type="text"
                                class="input-industrial w-full h-[48px] md:h-[52px] pl-12 pr-4 bg-surface-container border-2 border-outline-variant rounded-lg text-sm md:text-base text-on-surface transition-all placeholder:text-outline-variant border-outline" 
                                placeholder="Nama Pengguna" 
                                required 
                            >
                        </div>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="flex flex-col space-y-1.5">
                        <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider" for="password">
                            PIN/Password
                        </label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-secondary group-focus-within:text-primary transition-colors">
                                lock
                            </span>
                            <input 
                                id="password" 
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="input-industrial w-full h-[48px] md:h-[52px] pl-12 pr-12 bg-surface-container border-2 border-outline-variant rounded-lg text-sm md:text-base text-on-surface transition-all placeholder:text-outline-variant border-outline" 
                                placeholder="••••••" 
                                required 
                            >
                            <button 
                                type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary hover:text-on-surface transition-colors" 
                                @click="togglePassword" 
                            >
                                <span class="material-symbols-outlined" id="eye-icon">
                                    {{ showPassword ? 'visibility_off' : 'visibility' }}
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'btn-active w-full h-[48px] md:h-[52px] text-on-primary font-semibold text-base md:text-lg rounded-lg shadow-sm hover:bg-surface-tint active:scale-[0.98] transition-all flex items-center justify-center space-x-3 mt-6',
                            form.processing ? 'bg-tertiary opacity-80 cursor-not-allowed' : 'bg-primary'
                        ]"
                    >
                        <template v-if="form.processing">
                            <span class="material-symbols-outlined animate-spin">progress_activity</span>
                            <span>Memproses...</span>
                        </template>
                        <template v-else>
                            <span>Login</span>
                            <span class="material-symbols-outlined !text-[20px] md:!text-[24px]">login</span>
                        </template>
                    </button>
                    
                    <div class="flex items-center justify-between pt-2">
                        <a class="text-xs md:text-sm font-semibold text-tertiary hover:underline" href="#">Lupa PIN?</a>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></div>
                            <span class="text-xs md:text-sm font-semibold text-secondary">Sistem Online</span>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer / Support -->
            <footer class="mt-6 md:mt-8 text-center">
                <p class="text-xs font-semibold text-secondary">
                    v2.4.0 © 2024 Toko Material POS. Build ID: TM-882
                </p>
                <div class="flex justify-center space-x-4 mt-2">
                    <span class="material-symbols-outlined text-outline-variant">support_agent</span>
                    <span class="material-symbols-outlined text-outline-variant">security</span>
                    <span class="material-symbols-outlined text-outline-variant">cloud_sync</span>
                </div>
            </footer>
        </main>
    </div>
</template>
