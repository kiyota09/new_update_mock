<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircle, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    reports: Array,
});

const resolveReport = (reportId) => {
    if (confirm('Mark this report as resolved?')) {
        router.patch(route('man.staff.maintenance-checker.resolve-report', reportId), {
            preserveScroll: true,
        });
    }
};

const getStatusBadgeClass = (status) => {
    return status === 'pending'
        ? 'bg-yellow-100 text-yellow-800'
        : 'bg-green-100 text-green-800';
};
</script>

<template>
    <AuthenticatedLayout title="Maintenance Reports">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Machine Reports</h1>
                <Link :href="route('man.staff.maintenance-checker.page')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                Manage Machines
                </Link>
            </div>

            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reported By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium">{{ report.machine?.machine_no }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ report.machine?.type }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ report.reporter?.name }}</td>
                                <td class="px-6 py-4 max-w-md">{{ report.issue }}</td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusBadgeClass(report.status)"
                                        class="px-2 py-1 rounded text-xs font-bold">
                                        {{ report.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">{{ new Date(report.created_at).toLocaleString() }}</td>
                                <td class="px-6 py-4">
                                    <button v-if="report.status === 'pending'" @click="resolveReport(report.id)"
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center gap-1">
                                        <CheckCircle class="w-4 h-4" /> Resolve
                                    </button>
                                    <span v-else class="text-gray-400 text-sm">Resolved</span>
                                </td>
                            </tr>
                            <tr v-if="!reports || reports.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <AlertTriangle class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                    No machine reports found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>