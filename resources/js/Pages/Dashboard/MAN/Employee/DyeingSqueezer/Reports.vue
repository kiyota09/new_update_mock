<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { AlertTriangle, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    machines: Array,
    myReports: Array,
});

const showReportForm = ref(false);
const form = useForm({
    machine_id: '',
    issue: '',
});

const submitReport = () => {
    form.post(route('man.staff.dyeing-color.report-machine'), {
        preserveScroll: true,
        onSuccess: () => {
            showReportForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Dyeing Squeezer Reports">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Machine Reports</h1>
                <button @click="showReportForm = !showReportForm"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                    Report Issue
                </button>
            </div>

            <!-- Report Form -->
            <div v-if="showReportForm" class="bg-white rounded-2xl shadow-sm border p-6 mb-8">
                <h2 class="text-lg font-bold mb-4">Report a Machine Issue</h2>
                <form @submit.prevent="submitReport" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Machine</label>
                        <select v-model="form.machine_id" required
                            class="w-full border rounded-lg p-2 dark:bg-zinc-800 dark:border-zinc-700">
                            <option value="">Select Machine</option>
                            <option v-for="machine in machines" :key="machine.id" :value="machine.id">
                                {{ machine.machine_no }} ({{ machine.status }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Issue Description</label>
                        <textarea v-model="form.issue" rows="3" required
                            class="w-full border rounded-lg p-2 dark:bg-zinc-800 dark:border-zinc-700"
                            placeholder="Describe the problem..."></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="form.processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700">
                            Submit Report
                        </button>
                        <button type="button" @click="showReportForm = false"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-400">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reports List -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold">My Reported Issues</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in myReports" :key="report.id">
                                <td class="px-6 py-4">{{ report.machine?.machine_no }} ({{ report.machine?.type }})</td>
                                <td class="px-6 py-4">{{ report.issue }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="report.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                        class="px-2 py-1 rounded text-xs font-bold">
                                        {{ report.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ new Date(report.created_at).toLocaleString() }}</td>
                            </tr>
                            <tr v-if="myReports.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No reports submitted yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>