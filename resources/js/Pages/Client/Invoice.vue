<template>
    <Head title="Invoices & Payments" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <CreditCard class="h-3.5 w-3.5" />
                        Financial Overview
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Invoices & <span class="text-indigo-600">Payments</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Track your outstanding balances and payment history.
                    </p>
                </div>
                <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Outstanding</p>
                    <p class="text-3xl font-black text-rose-600 mt-1">₱{{ formatCurrency(totalOutstanding) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Paid Orders</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ paidCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Payments</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ pendingCount }}</p>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by PO number..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="statusFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Orders</option>
                            <option value="pending">Pending Payment</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Invoice / PO #</th>
                                <th class="px-8 py-5">Date</th>
                                <th class="px-8 py-5 text-right">Amount</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Due Date</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="order in filteredOrders" :key="order.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <FileText class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ order.po_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
                                <td class="px-8 py-6 text-right font-black text-gray-900">₱{{ formatCurrency(order.total_amount) }}</td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="getStatusBadge(order.payment_status || 'pending')" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ order.payment_status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center text-sm" :class="isOverdue(order) ? 'text-red-600 font-bold' : 'text-gray-500'">
                                    {{ formatDate(order.due_date || order.created_at) }}
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="payNow(order)" 
                                        :disabled="order.payment_status === 'paid'"
                                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ order.payment_status === 'paid' ? 'Paid' : 'Pay Now' }}
                                    </button>
                                </td>
                             </tr>
                            <tr v-if="filteredOrders.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No invoices found.
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Modal (Placeholder for Finance Module) -->
            <Teleport to="body">
                <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showPaymentModal = false">
                    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                            <h3 class="font-black text-lg">Make Payment</h3>
                            <button @click="showPaymentModal = false" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <p class="text-sm text-gray-500">Order #</p>
                                <p class="font-mono font-bold">{{ selectedOrder?.po_number }}</p>
                                <p class="text-sm text-gray-500 mt-2">Amount Due</p>
                                <p class="text-2xl font-black text-indigo-600">₱{{ formatCurrency(selectedOrder?.total_amount) }}</p>
                            </div>
                            <div class="text-center text-sm text-gray-500">
                                <p class="font-bold">Payment Gateway Integration</p>
                                <p>This will connect to the Finance Module.</p>
                                <p class="mt-2 text-xs">Supported methods: Bank Transfer, Credit Card, GCash</p>
                            </div>
                            <button @click="simulatePayment" class="w-full py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition">
                                Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Toast Notification -->
            <Transition name="toast">
                <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                    :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                    {{ toast.message }}
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { CreditCard, RefreshCw, Search, FileText, X } from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

// Filter state
const searchTerm = ref('');
const statusFilter = ref('all');

// Payment modal
const showPaymentModal = ref(false);
const selectedOrder = ref(null);

// Toast
const toast = ref({ show: false, type: 'success', message: '' });

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

// Computed
const filteredOrders = computed(() => {
    let list = props.orders;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(o => o.po_number.toLowerCase().includes(term));
    }
    if (statusFilter.value === 'pending') {
        list = list.filter(o => o.payment_status !== 'paid');
    } else if (statusFilter.value === 'paid') {
        list = list.filter(o => o.payment_status === 'paid');
    } else if (statusFilter.value === 'overdue') {
        list = list.filter(o => isOverdue(o) && o.payment_status !== 'paid');
    }
    return list;
});

const totalOutstanding = computed(() => {
    return props.orders
        .filter(o => o.payment_status !== 'paid')
        .reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);
});

const paidCount = computed(() => props.orders.filter(o => o.payment_status === 'paid').length);
const pendingCount = computed(() => props.orders.filter(o => o.payment_status !== 'paid').length);

const formatCurrency = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });

const isOverdue = (order) => {
    if (order.payment_status === 'paid') return false;
    const dueDate = new Date(order.due_date || order.created_at);
    const today = new Date();
    return dueDate < today;
};

const getStatusBadge = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700',
        pending: 'bg-amber-100 text-amber-700',
        overdue: 'bg-red-100 text-red-700'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const refreshData = () => {
    router.reload({ only: ['orders'] });
};

const payNow = (order) => {
    if (order.payment_status === 'paid') {
        showToast('error', 'This invoice is already paid.');
        return;
    }
    selectedOrder.value = order;
    showPaymentModal.value = true;
};

const simulatePayment = () => {
    // This will be replaced with actual Finance module integration
    showToast('success', `Payment for ${selectedOrder.value.po_number} initiated. You will be redirected to the payment gateway.`);
    showPaymentModal.value = false;
    // In real implementation, redirect to payment gateway or open modal from Finance module
    // For now, just reload to simulate status change
    setTimeout(() => refreshData(), 1500);
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>