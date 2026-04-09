<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Droplets, Clock, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentJobs: Array,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Dyeing Squeezer Dashboard" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dyeing Squeezer Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.dyeing-squeezer.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Squeeze Fabrics
                    </Link>
                    <Link :href="route('man.staff.dyeing-squeezer.reports')"
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
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pending to Squeeze</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.pending }}</p>
                        </div>
                        <Droplets class="w-8 h-8 text-cyan-500" />
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
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Squeezer Jobs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Softener Job
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="job in recentJobs" :key="job.id">
                                <td class="px-6 py-4 font-mono text-sm">{{ job.code }}</td>
                                <td class="px-6 py-4">{{ job.softener_job?.code || 'N/A' }}</td>
                                <td class="px-6 py-4">{{ job.machine?.machine_no || 'N/A' }}</td>
                                <td class="px-6 py-4">{{ new Date(job.processed_at).toLocaleString() }}</td>
                            </tr>
                            <tr v-if="recentJobs.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No squeezer jobs recorded
                                    yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>