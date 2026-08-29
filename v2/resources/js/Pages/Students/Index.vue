<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    students: Object,
});
</script>

<template>
    <Head title="Data Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Data Siswa
                </h2>
                <Link
                    :href="route('students.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Tambah Siswa
                </Link>
            </div>
        </template>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">No</th>
                            <th scope="col" class="px-6 py-4 font-medium">NIS</th>
                            <th scope="col" class="px-6 py-4 font-medium">Nama Lengkap</th>
                            <th scope="col" class="px-6 py-4 font-medium">Gender</th>
                            <th scope="col" class="px-6 py-4 font-medium">Agama</th>
                            <th scope="col" class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="(student, index) in students.data"
                            :key="student.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                {{ (students.current_page - 1) * students.per_page + index + 1 }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                {{ student.nis }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-indigo-600">
                                {{ student.name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{ student.religion }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="route('students.edit', student.id)"
                                    class="text-indigo-600 hover:text-indigo-900 font-medium mr-4"
                                >
                                    Edit
                                </Link>
                                <button
                                    class="text-red-600 hover:text-red-900 font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <tr v-if="students.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Belum ada data siswa.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="students.links && students.data.length > 0" class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium">{{ students.from }}</span> - <span class="font-medium">{{ students.to }}</span> dari <span class="font-medium">{{ students.total }}</span> data
                </div>
                <div class="flex space-x-1">
                    <template v-for="(link, i) in students.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 rounded text-sm transition-colors"
                            :class="link.active ? 'bg-indigo-600 text-white font-medium' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'"
                        />
                        <span 
                            v-else 
                            v-html="link.label"
                            class="px-3 py-1 rounded text-sm bg-gray-100 text-gray-400 cursor-not-allowed"
                        ></span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
