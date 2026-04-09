<template>
    <Head title="Credit Management - ECO Module" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <CreditCard class="h-3.5 w-3.5" />
                        Financial Risk Monitoring
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Credit <span class="text-indigo-600">Ledger</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Monitor client outstanding balances, approve pending credit reviews, and view order history.
                    </p>
                </div>
                <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Outstanding</p>
                    <p class="text-3xl font-black text-rose-600 mt-1">₱{{ formatCurrency(totalOutstanding) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Good Payers</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ goodPayersCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">At Risk</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ atRiskCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Clients</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ clients.length }}</p>
                </div>
            </div>

            <!-- Pending Credit Reviews Section -->
            <div v-if="pendingCreditReviews.length > 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-amber-200 dark:border-amber-800 shadow-sm overflow-hidden">
                <div class="px-8 py-5 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
                    <div class="flex items-center gap-2">
                        <AlertCircle class="h-5 w-5 text-amber-600" />
                        <h2 class="text-sm font-black uppercase tracking-wider text-amber-800 dark:text-amber-300">
                            Pending Credit Review ({{ pendingCreditReviews.length }})
                        </h2>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">PO Number</th>
                                <th class="px-8 py-5">Client</th>
                                <th class="px-8 py-5 text-right">Total Amount</th>
                                <th class="px-8 py-5 text-center">Order Date</th>
                                <th class="px-8 py-5 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="order in pendingCreditReviews" :key="order.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ order.po_number }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                            <Building2 class="h-4 w-4 text-indigo-600" />
                                        </div>
                                        <span class="text-sm font-bold">{{ order.client?.company_name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right font-black text-gray-900">₱{{ formatCurrency(order.total_amount) }}</td>
                                <td class="px-8 py-6 text-center text-xs text-gray-500">{{ formatDate(order.created_at) }}</td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="approveCreditReview(order)" :disabled="approving[order.id]"
                                        class="inline-flex items-center gap-1 px-4 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-emerald-700 transition disabled:opacity-50">
                                        <CheckCircle v-if="!approving[order.id]" class="h-3 w-3" />
                                        <Loader2 v-else class="h-3 w-3 animate-spin" />
                                        {{ approving[order.id] ? 'Approving...' : 'Approve' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by company name..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="filterStatus"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Clients</option>
                            <option value="good">Good Payers</option>
                            <option value="risk">At Risk</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Client</th>
                                <th class="px-8 py-5 text-right">Outstanding Balance</th>
                                <th class="px-8 py-5 text-center">Payment Standing</th>
                                <th class="px-8 py-5 text-center">Total Orders</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="client in filteredClients" :key="client.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <Building2 class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ client.company_name }}</p>
                                            <p class="text-[10px] font-bold text-gray-400">{{ client.contact_person }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-lg font-black text-rose-600">₱{{ formatCurrency(client.outstanding_balance || 0) }}</span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="client.is_good_payer ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ client.is_good_payer ? 'Good Payer' : 'At Risk' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center font-bold text-gray-700">{{ client.orders_count || 0 }}</td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="openHistoryModal(client)" class="p-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 hover:text-indigo-600 transition">
                                        <History class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredClients.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No clients found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order History Modal -->
        <Teleport to="body">
            <div v-if="showHistoryModal && selectedClient" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showHistoryModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-tighter">Order History</h3>
                            <p class="text-sm opacity-90">{{ selectedClient.company_name }}</p>
                        </div>
                        <button @click="showHistoryModal = false" class="p-2 bg-white/10 rounded-xl hover:bg-white/20"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl flex justify-between">
                            <div><span class="text-sm text-gray-500">Outstanding:</span> <span class="font-black text-rose-600">₱{{ formatCurrency(selectedClient.outstanding_balance) }}</span></div>
                            <div><span class="text-sm text-gray-500">Status:</span> <span :class="selectedClient.is_good_payer ? 'text-emerald-600' : 'text-amber-600'" class="font-black">{{ selectedClient.is_good_payer ? 'Good Payer' : 'At Risk' }}</span></div>
                        </div>
                        <div class="space-y-3">
                            <div v-for="order in selectedClient.orders" :key="order.id" class="border rounded-xl p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-mono text-sm font-bold">{{ order.po_number }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(order.created_at) }}</p>
                                    </div>
                                    <span :class="order.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" class="px-2 py-1 rounded text-[9px] font-black uppercase">
                                        {{ order.status }}
                                    </span>
                                </div>
                                <div class="mt-2 flex justify-between items-center">
                                    <span class="text-sm">Total: <span class="font-black">₱{{ formatCurrency(order.total_amount) }}</span></span>
                                    <span class="text-xs text-gray-500">{{ order.items_count || 0 }} items</span>
                                </div>
                            </div>
                            <div v-if="!selectedClient.orders || selectedClient.orders.length === 0" class="text-center py-8 text-gray-400">
                                No orders found for this client.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Transition name="toast">
            <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                {{ toast.message }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { CreditCard, RefreshCw, Search, Building2, History, X, AlertCircle, CheckCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    clients: { type: Array, default: () => [] },
    pendingCreditReviews: { type: Array, default: () => [] }
});

const searchTerm = ref('');
const filterStatus = ref('all');
const showHistoryModal = ref(false);
const selectedClient = ref(null);
const approving = ref({});
const toast = ref({ show: false, type: 'success', message: '' });

const filteredClients = computed(() => {
    let list = props.clients;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(c => c.company_name.toLowerCase().includes(term));
    }
    if (filterStatus.value === 'good') {
        list = list.filter(c => c.is_good_payer);
    } else if (filterStatus.value === 'risk') {
        list = list.filter(c => !c.is_good_payer);
    }
    return list;
});

const totalOutstanding = computed(() => {
    return props.clients.reduce((sum, c) => sum + (parseFloat(c.outstanding_balance) || 0), 0);
});

const goodPayersCount = computed(() => props.clients.filter(c => c.is_good_payer).length);
const atRiskCount = computed(() => props.clients.filter(c => !c.is_good_payer).length);

const formatCurrency = (val) => {
    return Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const openHistoryModal = (client) => {
    selectedClient.value = client;
    showHistoryModal.value = true;
};

const approveCreditReview = (order) => {
    if (!confirm(`Approve order ${order.po_number}? This will move it to the Push Center.`)) return;
    approving.value[order.id] = true;
    router.post(route('eco.credit.approve', order.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', `Order ${order.po_number} approved.`);
            refreshData();
        },
        onError: (errors) => {
            showToast('error', errors.error || 'Failed to approve order.');
        },
        onFinish: () => {
            approving.value[order.id] = false;
        }
    });
};

const refreshData = () => {
    router.reload({ only: ['clients', 'pendingCreditReviews'] });
};
</script>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>