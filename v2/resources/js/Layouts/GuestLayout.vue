<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Left Side: Branding / Image -->
        <div class="hidden w-1/2 bg-indigo-900 lg:block relative">
            <!-- Background Image -->
            <img 
                src="/images/login-bg.png" 
                alt="School Background" 
                class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
            />
            <!-- Content overlay -->
            <div class="absolute inset-0 flex flex-col justify-center px-16 z-10">
                <div class="flex items-center gap-3 mb-8">
                    <ApplicationLogo class="h-12 w-12 text-white fill-current" />
                    <h1 class="text-3xl font-bold text-white tracking-tight">Sekolahku</h1>
                </div>
                <h2 class="text-4xl font-extrabold text-white leading-tight mb-6">
                    Sistem Manajemen Sekolah Modern.
                </h2>
                <p class="text-indigo-200 text-lg max-w-md leading-relaxed">
                    Kelola data akademik, absensi, jurnal mengajar, dan bimbingan konseling dengan mudah dan cepat dalam satu platform terpadu.
                </p>
                
                <!-- Features List -->
                <ul class="mt-10 space-y-4 text-indigo-100">
                    <li class="flex items-center gap-3">
                        <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Integrasi Data Real-time</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Akses Mudah dimana saja</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Keamanan Data Terjamin</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side: Form Content -->
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-20 xl:px-32">
            
            <!-- Mobile Logo -->
            <div class="mb-8 flex justify-center lg:hidden">
                <Link href="/" class="flex flex-col items-center gap-2">
                    <div class="bg-indigo-600 p-3 rounded-xl shadow-lg">
                        <ApplicationLogo class="h-10 w-10 text-white fill-current" />
                    </div>
                    <span class="text-2xl font-bold text-gray-900 mt-2">Sekolahku</span>
                </Link>
            </div>

            <div class="w-full max-w-md mx-auto">
                <div class="mb-10 text-center lg:text-left">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">Selamat Datang</h2>
                    <p class="mt-2 text-sm text-gray-500">Silakan masuk menggunakan kredensial Anda.</p>
                </div>

                <div class="bg-white px-8 py-8 shadow-xl shadow-indigo-100 ring-1 ring-gray-200 sm:rounded-2xl">
                    <slot />
                </div>
            </div>
            
            <!-- Footer Form -->
            <div class="mt-8 text-center text-xs text-gray-400">
                &copy; {{ new Date().getFullYear() }} Sekolahku App. All rights reserved.
            </div>
        </div>
    </div>
</template>
