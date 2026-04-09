<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ShoppingCart, Package, Eye, Send, Clock, AlertCircle, CheckCircle, X } from 'lucide-vue-next';

const props = defineProps({ orders: Array });

const isLoading = ref(false);
const selectedOrder = ref(null);
const showDetailModal = ref(false);

const checkInventory = (orderId) => {
    if (confirm('Request inventory check for this order?')) {
        isLoading.value = true;
        router.post(route('scm.sales-order.check-inventory', orderId), {}, {
            preserveScroll: true,
            onFinish: () => isLoading.value = false,
        });
    }
};

const pushToProduction = (orderId) => {
    if (confirm('Push this order to Manufacturing?')) {
        isLoading.value = true;
        router.post(route('scm.sales-order.push-to-production', orderId), {}, {
            preserveScroll: true,
            onFinish: () => isLoading.value = false,
        });
    }
};

const openDetail = (order) => {
    selectedOrder.value = order;
    showDetailModal.value = true;
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString();
const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusBadge = (stage, sufficient) => {
    if (stage === 'man_production') return 'bg-purple-100 text-purple-700';
    if (stage === 'inv_checked' && sufficient) return 'bg-green-100 text-green-700';
    if (stage === 'inv_check') return 'bg-amber-100 text-amber-700';
    return 'bg-blue-100 text-blue-700';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Sales Orders" />
        <div class="max-w-7xl mx-auto p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Orders from ECO</h1>
                <p class="text-sm text-gray-500">Manage orders, check inventory, push to production.</p>
            </div>

            <div v-if="orders.length === 0" class="bg-white dark:bg-gray-800 rounded-lg p-12 text-center">
                <ShoppingCart class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">No sales orders pending.</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="order in orders" :key="order.id" class="bg-white dark:bg-gray-800 rounded-lg border shadow-sm p-5">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-mono font-bold text-gray-500">#{{ order.po_number }}</span>
                                <span :class="getStatusBadge(order.stage, order.inv_check_sufficient)" class="text-xs px-2 py-0.5 rounded-full">
                                    {{ order.stage.replace('_', ' ') }}
                                </span>
                            </div>
                            <p class="font-medium">{{ order.client_name }}</p>
                            <div class="flex gap-4 mt-1 text-sm text-gray-500">
                                <span>₱{{ formatCurrency(order.total_amount) }}</span>
                                <span>{{ formatDate(order.created_at) }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openDetail(order)" class="px-3 py-2 border rounded-lg text-sm">Details</button>
                            <button @click="checkInventory(order.id)" :disabled="isLoading" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm">
                                Check Inventory
                            </button>
                            <button v-if="order.stage === 'inv_checked' && order.inv_check_sufficient" 
                                @click="pushToProduction(order.id)" :disabled="isLoading" 
                                class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm">
                                Push to Production
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[80vh] overflow-auto">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Order Details</h2>
                        <button @click="showDetailModal = false"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6">
                        <p><strong>PO Number:</strong> {{ selectedOrder.po_number }}</p>
                        <p><strong>Client:</strong> {{ selectedOrder.client_name }}</p>
                        <p><strong>Total:</strong> ₱{{ formatCurrency(selectedOrder.total_amount) }}</p>
                        <h3 class="font-semibold mt-4 mb-2">Items</h3>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr><th class="text-left p-2">Product</th><th class="text-right p-2">Qty</th><th class="text-right p-2">Price</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in selectedOrder.items" :key="item.product_name">
                                    <td class="p-2">{{ item.product_name }}</td>
                                    <td class="p-2 text-right">{{ item.quantity }}</td>
                                    <td class="p-2 text-right">₱{{ formatCurrency(item.unit_price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>