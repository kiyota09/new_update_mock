<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Scissors, Clock, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentJobs: Array,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Dyeing Forming Dashboard" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dyeing Forming Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.dyeing-forming.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Form Products
                    </Link>
                    <Link :href="route('man.staff.dyeing-forming.reports')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        View Reports
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pending Forming</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.pending }}</p>
                        </div>
                        <Scissors class="w-8 h-8 text-purple-500" />
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Today</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_today }}</p>
                        </div>
                        <Clock class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Machine Reports</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">0</p>
                        </div>
                        <AlertTriangle class="w-8 h-8 text-red-500" />
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Forming Jobs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Code</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Remarks</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="job in recentJobs" :key="job.id">
                                <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ job.code }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ job.product?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ job.quantity }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ job.remarks || '—' }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ new
                                    Date(job.processed_at).toLocaleString() }}</td>
                            </tr>
                            <tr v-if="recentJobs.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No forming jobs recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>