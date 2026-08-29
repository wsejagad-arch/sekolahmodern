<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    nis: '',
    email: '',
    gender: 'L',
    religion: 'Islam',
});

const submit = () => {
    form.post(route('students.store'));
};
</script>

<template>
    <Head title="Tambah Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tambah Siswa Baru
                </h2>
                <Link
                    :href="route('students.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    &larr; Kembali
                </Link>
            </div>
        </template>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100 max-w-2xl mx-auto mt-6">
            <div class="p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label for="nis" class="block text-sm font-medium text-gray-700">NIS (Nomor Induk Siswa)</label>
                        <input
                            id="nis"
                            v-model="form.nis"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                        <div v-if="form.errors.nis" class="text-red-500 text-xs mt-1">{{ form.errors.nis }}</div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Akun</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="religion" class="block text-sm font-medium text-gray-700">Agama</label>
                            <select
                                id="religion"
                                v-model="form.religion"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Konghucu">Konghucu</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end border-t pt-5 mt-5">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
