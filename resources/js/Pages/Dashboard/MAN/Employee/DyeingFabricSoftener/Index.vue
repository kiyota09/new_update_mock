<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Shirt, Clock, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentJobs: Array,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Dyeing Fabric Softener" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Fabric Softener Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.dyeing-fabric-softener.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Soften Fabrics
                    </Link>
                    <Link :href="route('man.staff.dyeing-fabric-softener.reports')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        View Reports
                    </Link>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending to Soften</p>
                            <p class="text-3xl font-bold">{{ stats.pending }}</p>
                        </div>
                        <Shirt class="w-8 h-8 text-indigo-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Today</p>
                            <p class="text-3xl font-bold">{{ stats.total_today }}</p>
                        </div>
                        <Clock class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Machine Reports</p>
                            <p class="text-3xl font-bold">0</p>
                        </div>
                        <AlertTriangle class="w-8 h-8 text-red-500" />
                    </div>
                </div>
            </div>
            <!-- Recent jobs table (similar to previous) -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold">Recent Softening Jobs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Fabric</th>
                                <th>Softener Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="job in recentJobs" :key="job.id">
                                <td class="px-6 py-4 font-mono">{{ job.code }}</td>
                                <td>{{ job.fabric?.code }}</td>
                                <td>{{ job.softener_type }}</td>
                                <td>{{ new Date(job.processed_at).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>