<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList, Send } from 'lucide-vue-next';

const props = defineProps({ procurementRequests: Array });

const sendToProcurement = (id) => {
    if (confirm('Send this request to Procurement module?')) {
        router.post(route('scm.procurement-order.send', id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Procurement Orders" />
        <div class="max-w-7xl mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">Procurement Requests</h1>
            <div v-if="procurementRequests.length === 0" class="bg-white dark:bg-gray-800 rounded-lg p-12 text-center text-gray-500">
                No pending procurement requests.
            </div>
            <div class="space-y-3">
                <div v-for="req in procurementRequests" :key="req.id" class="bg-white dark:bg-gray-800 rounded-lg border p-4 flex justify-between items-center">
                    <div>
                        <p class="font-bold">{{ req.material_name }}</p>
                        <p class="text-sm text-gray-500">Qty: {{ req.required_qty }} {{ req.unit }} | Urgency: {{ req.urgency }}</p>
                    </div>
                    <button @click="sendToProcurement(req.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm flex items-center gap-1">
                        <Send class="w-4 h-4" /> Send to PRO
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>