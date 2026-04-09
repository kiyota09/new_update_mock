<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Truck,
    Package,
    X,
    CheckCircle,
    AlertCircle,
    ChevronDown,
    ChevronUp,
    Eye,
    ClipboardList,
    Search,
    Filter,
    Calendar,
    Building2,
    User,
    FileText,
} from 'lucide-vue-next';

const props = defineProps({
    receivings: {
        type: Array,
        default: () => [],
    },
    pendingPOs: {
        type: Array,
        default: () => [],
    },
    materials: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    auth: Object,
});

// Safe computed properties
const safePendingPOs = computed(() => props.pendingPOs ?? []);
const safeReceivings = computed(() => props.receivings ?? []);
const safeMaterials = computed(() => props.materials ?? []);
const safeWarehouses = computed(() => props.warehouses ?? []);

// State
const selectedPO = ref(null);
const showReceiveModal = ref(false);
const showPastReceivings = ref(false);
const searchQuery = ref('');
const processing = ref(false);

// Form for receiving
const receiveForm = useForm({
    warehouse_id: '',
    po_id: null,
    items: [],
});

// Select a purchase order to receive
const selectPO = (po) => {
    selectedPO.value = po;
    receiveForm.po_id = po.id;
    receiveForm.warehouse_id = safeWarehouses.value.length > 0 ? safeWarehouses.value[0].id : '';
    
    // Prepare items from the PO's items (which include material relation)
    receiveForm.items = po.items.map(item => ({
        material_id: item.material_id,
        material_name: item.material?.name || item.material_name,
        expected_qty: parseFloat(item.qty),
        received_qty: parseFloat(item.qty),
        rejected_qty: 0,
        reject_reason: '',
        unit: item.unit,
    }));
    
    showReceiveModal.value = true;
};

// Update received quantity
const updateReceivedQty = (index, value) => {
    const item = receiveForm.items[index];
    let received = parseFloat(value) || 0;
    const expected = item.expected_qty;
    if (received > expected) received = expected;
    item.received_qty = received;
    item.rejected_qty = expected - received;
    if (item.rejected_qty < 0) item.rejected_qty = 0;
};

// Update rejected quantity
const updateRejectedQty = (index, value) => {
    const item = receiveForm.items[index];
    let rejected = parseFloat(value) || 0;
    const expected = item.expected_qty;
    if (rejected > expected) rejected = expected;
    item.rejected_qty = rejected;
    item.received_qty = expected - rejected;
    if (item.received_qty < 0) item.received_qty = 0;
};

// Submit receiving
const submitReceive = () => {
    if (!receiveForm.warehouse_id) {
        alert('Please select a destination warehouse.');
        return;
    }
    const itemsToSend = receiveForm.items.filter(item => item.received_qty > 0 || item.rejected_qty > 0);
    if (itemsToSend.length === 0) {
        alert('No items to receive or reject.');
        return;
    }
    receiveForm.items = itemsToSend;
    processing.value = true;
    receiveForm.post(route('warehouse.receiving.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showReceiveModal.value = false;
            selectedPO.value = null;
            receiveForm.reset();
        },
        onError: (errors) => {
            console.error(errors);
            alert('Failed to process receiving. Check logs.');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Filter past receivings
const filteredReceivings = computed(() => {
    if (!searchQuery.value) return safeReceivings.value;
    const q = searchQuery.value.toLowerCase();
    return safeReceivings.value.filter(rec =>
        rec.receiving_number?.toLowerCase().includes(q) ||
        (rec.purchase_order?.po_number || '').toLowerCase().includes(q)
    );
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString();
};

const statusBadge = (status) => {
    const styles = {
        pending: 'bg-amber-100 text-amber-700',
        partial: 'bg-blue-100 text-blue-700',
        completed: 'bg-emerald-100 text-emerald-700',
    };
    return styles[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Warehouse Receiving | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Receiving</h1>
                    <p class="text-slate-500 text-sm mt-0.5">Receive incoming deliveries and update inventory.</p>
                </div>

                <!-- Two-column layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Pending Purchase Orders -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                            <Truck class="w-4 h-4 text-emerald-500" />
                            <h2 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">Pending Deliveries</h2>
                            <span class="ml-auto text-xs font-bold text-slate-400">{{ safePendingPOs.length }} orders</span>
                        </div>

                        <div v-if="safePendingPOs.length === 0" class="p-8 text-center text-slate-400">
                            <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                            <p>No pending purchase orders from SCM.</p>
                        </div>

                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                            <div v-for="po in safePendingPOs" :key="po.id" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-300">{{ po.po_number }}</span>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ po.status }}</span>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ po.supplier_name }}</p>
                                        <div class="flex items-center gap-3 mt-1 text-xs text-slate-400">
                                            <span class="flex items-center gap-1"><Package class="w-3 h-3" /> {{ po.items?.length || 0 }} items</span>
                                            <span class="flex items-center gap-1"><Calendar class="w-3 h-3" /> {{ po.expected_delivery || '—' }}</span>
                                        </div>
                                    </div>
                                    <button
                                        @click="selectPO(po)"
                                        class="flex-shrink-0 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm"
                                    >
                                        Receive
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Past Receivings -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <button
                            @click="showPastReceivings = !showPastReceivings"
                            class="w-full px-5 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/40 transition"
                        >
                            <div class="flex items-center gap-2">
                                <ClipboardList class="w-4 h-4 text-slate-500" />
                                <h2 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">Receiving History</h2>
                                <span class="text-xs font-bold text-slate-400">{{ safeReceivings.length }} records</span>
                            </div>
                            <ChevronDown :class="['w-4 h-4 text-slate-400 transition-transform', showPastReceivings ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="showPastReceivings" class="border-t border-slate-100 dark:border-slate-800">
                            <!-- Search -->
                            <div class="p-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by receiving # or PO..."
                                        class="w-full pl-9 pr-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    />
                                </div>
                            </div>

                            <div v-if="filteredReceivings.length === 0" class="p-8 text-center text-slate-400">
                                <ClipboardList class="w-8 h-8 mx-auto mb-2 opacity-30" />
                                <p class="text-sm">No receiving records found.</p>
                            </div>

                            <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-96 overflow-y-auto">
                                <div v-for="rec in filteredReceivings" :key="rec.id" class="p-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <span class="font-mono text-xs font-bold text-slate-600">{{ rec.receiving_number }}</span>
                                                <span :class="['text-[9px] font-black px-2 py-0.5 rounded-full', statusBadge(rec.status)]">{{ rec.status }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500">PO: {{ rec.purchase_order?.po_number || '—' }}</p>
                                            <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5"><Calendar class="w-3 h-3" /> {{ formatDate(rec.received_at) }}</p>
                                            <p class="text-xs text-slate-500 flex items-center gap-1"><User class="w-3 h-3" /> {{ rec.received_by?.name || '—' }}</p>
                                        </div>
                                        <button class="text-slate-400 hover:text-slate-600">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div class="mt-2 text-xs text-slate-500">
                                        <span class="font-semibold">Warehouse:</span> {{ rec.warehouse?.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receive Modal -->
        <Teleport to="body">
            <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showReceiveModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Receive Delivery</h3>
                            <p class="text-xs text-slate-500">PO: {{ selectedPO?.po_number }} · {{ selectedPO?.supplier_name }}</p>
                        </div>
                        <button @click="showReceiveModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white dark:hover:bg-slate-700 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Destination Warehouse *</label>
                            <select v-model="receiveForm.warehouse_id" class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                <option value="">Select a warehouse...</option>
                                <option v-for="wh in safeWarehouses" :key="wh.id" :value="wh.id">{{ wh.name }} ({{ wh.location }})</option>
                            </select>
                        </div>

                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Received Items</p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Material</th>
                                            <th class="px-3 py-2 text-center">Expected</th>
                                            <th class="px-3 py-2 text-center">Received</th>
                                            <th class="px-3 py-2 text-center">Rejected</th>
                                            <th class="px-3 py-2 text-left">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, idx) in receiveForm.items" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-3 py-2.5">
                                                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ item.material_name }}</p>
                                                <p class="text-[10px] text-slate-400">{{ item.unit }}</p>
                                            </td>
                                            <td class="px-3 py-2.5 text-center font-mono text-sm">{{ item.expected_qty }}</td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    type="number"
                                                    v-model.number="item.received_qty"
                                                    @input="updateReceivedQty(idx, $event.target.value)"
                                                    min="0"
                                                    :max="item.expected_qty"
                                                    class="w-24 text-center px-2 py-1 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    type="number"
                                                    v-model.number="item.rejected_qty"
                                                    @input="updateRejectedQty(idx, $event.target.value)"
                                                    min="0"
                                                    :max="item.expected_qty"
                                                    class="w-24 text-center px-2 py-1 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-if="item.rejected_qty > 0"
                                                    v-model="item.reject_reason"
                                                    type="text"
                                                    placeholder="Reason for rejection"
                                                    class="w-full px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20"
                                                />
                                                <span v-else class="text-slate-400 text-xs">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex gap-3 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <button @click="showReceiveModal = false" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition">Cancel</button>
                        <button @click="submitReceive" :disabled="processing || !receiveForm.warehouse_id" class="flex-[2] inline-flex items-center justify-center gap-2 py-2.5 text-sm font-black rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-lg shadow-emerald-500/20 disabled:opacity-50">
                            <CheckCircle class="w-4 h-4" /> {{ processing ? 'Processing...' : 'Confirm Receiving' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>