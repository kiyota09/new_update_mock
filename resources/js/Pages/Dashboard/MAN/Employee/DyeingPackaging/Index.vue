<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Box, Package, Clock } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentPackages: Array,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Packaging Dashboard" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Packaging Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.dyeing-packaging.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Create Package
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Available Products</p>
                            <p class="text-3xl font-bold">{{ stats.pending }}</p>
                        </div>
                        <Package class="w-8 h-8 text-green-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Packages Today</p>
                            <p class="text-3xl font-bold">{{ stats.total_today }}</p>
                        </div>
                        <Clock class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Shipments</p>
                            <p class="text-3xl font-bold">0</p>
                        </div>
                        <Box class="w-8 h-8 text-purple-500" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold">Recent Packages</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Items</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pkg in recentPackages" :key="pkg.id">
                                <td class="px-6 py-4 font-mono">{{ pkg.code }}</td>
                                <td>{{ pkg.items?.length }} items</td>
                                <td>{{ new Date(pkg.packaged_at).toLocaleString() }}</td>
                                <td><span
                                        :class="pkg.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                        class="px-2 py-1 rounded text-xs">{{ pkg.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>