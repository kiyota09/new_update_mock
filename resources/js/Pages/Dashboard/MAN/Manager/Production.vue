<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    orders: Array,
});

const forwardToChecker = (orderId) => {
    if (confirm('Forward this order to Checker Quality?')) {
        router.post(route('man.manager.forward-to-checker', orderId));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Received Orders</h1>
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="order in orders" :key="order.id">
                            <td class="px-6 py-4">{{ order.po_number }}</td>
                            <td class="px-6 py-4">{{ order.product_name }}</td>
                            <td class="px-6 py-4">{{ order.quantity }}</td>
                            <td class="px-6 py-4">
                                <button @click="forwardToChecker(order.id)"
                                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                    Start Production
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>