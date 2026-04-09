<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Clock, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentFabrics: Array,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Knitting Yarn Dashboard" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knitting Yarn Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.knitting-yarn.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Record Fabric
                    </Link>
                    <Link :href="route('man.staff.knitting-yarn.reports')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        View Reports
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Fabrics</p>
                            <p class="text-3xl font-bold">{{ stats.pending }}</p>
                        </div>
                        <Package class="w-8 h-8 text-yellow-500" />
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Today</p>
                            <p class="text-3xl font-bold">{{ stats.total_today }}</p>
                        </div>
                        <Clock class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Machine Reports</p>
                            <p class="text-3xl font-bold">0</p>
                        </div>
                        <AlertTriangle class="w-8 h-8 text-red-500" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold">Recent Fabrics</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="fabric in recentFabrics" :key="fabric.id">
                                <td class="px-6 py-4 font-mono text-sm">{{ fabric.code }}</td>
                                <td class="px-6 py-4">{{ fabric.machine?.machine_no }}</td>
                                <td class="px-6 py-4">{{ fabric.weight }}</td>
                                <td class="px-6 py-4">{{ new Date(fabric.processed_at).toLocaleString() }}</td>
                            </tr>
                            <tr v-if="recentFabrics.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No fabrics recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>