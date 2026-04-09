<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ClipboardList, Send, ArrowRight, X, CheckCircle, Users, AlertTriangle, Clock, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    materialRequests: Array,
    warehouses: Array,
    suppliers: Array,
    stats: Object,
});

// Stats for dashboard
const totalRequests = computed(() => props.materialRequests.length);
const highUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'High').length);
const mediumUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'Medium').length);
const lowUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'Low').length);

// Urgency distribution (for chart)
const urgencyData = computed(() => [
    { label: 'High', count: highUrgencyCount.value, color: 'bg-red-500' },
    { label: 'Medium', count: mediumUrgencyCount.value, color: 'bg-yellow-500' },
    { label: 'Low', count: lowUrgencyCount.value, color: 'bg-green-500' },
]);
const maxUrgency = computed(() => Math.max(highUrgencyCount.value, mediumUrgencyCount.value, lowUrgencyCount.value, 1));

// Modal state
const showRFQModal = ref(false);
const selectedRequest = ref(null);
const rfqForm = useForm({
    mr_id: null,
    deadline: '',
    delivery_address: '',
    payment_terms: 'Net 30',
    notes: '',
    selected_suppliers: [],
});
const supplierStep = ref(false);

const openRFQ = (req) => {
    selectedRequest.value = req;
    rfqForm.mr_id = req.id;
    rfqForm.deadline = '';
    rfqForm.delivery_address = '';
    rfqForm.payment_terms = 'Net 30';
    rfqForm.notes = '';
    rfqForm.selected_suppliers = [];
    supplierStep.value = false;
    showRFQModal.value = true;
};

const proceedToSuppliers = () => {
    if (!rfqForm.deadline || !rfqForm.delivery_address) {
        alert('Please fill all required fields');
        return;
    }
    supplierStep.value = true;
};

const toggleSupplier = (id) => {
    const idx = rfqForm.selected_suppliers.indexOf(id);
    if (idx === -1) rfqForm.selected_suppliers.push(id);
    else rfqForm.selected_suppliers.splice(idx, 1);
};

const submitRFQ = () => {
    if (rfqForm.selected_suppliers.length === 0) return;
    rfqForm.post(route('pro.manager.rfq.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showRFQModal.value = false;
        },
    });
};
</script>

<template>

    <Head title="Procurement - Material Requests" />
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Procurement Management<span class="text-blue-600">Dashboard</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage requests forwarded by SCM and create
                        RFQs</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Requests</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                totalRequests }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <ClipboardList class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">High Urgency</p>
                            <p class="text-2xl sm:text-3xl font-black text-red-600 dark:text-red-400 mt-1">{{
                                highUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Medium Urgency</p>
                            <p class="text-2xl sm:text-3xl font-black text-yellow-600 dark:text-yellow-400 mt-1">{{
                                mediumUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                            <Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Low Urgency</p>
                            <p class="text-2xl sm:text-3xl font-black text-green-600 dark:text-green-400 mt-1">{{
                                lowUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <TrendingUp class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Urgency Distribution Chart (Simple Bar) -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <TrendingUp class="w-4 h-4" /> Urgency Distribution
                </h3>
                <div class="space-y-3">
                    <div v-for="item in urgencyData" :key="item.label" class="flex items-center gap-3">
                        <span class="w-16 text-sm font-medium text-gray-600 dark:text-gray-400">{{ item.label }}</span>
                        <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" :class="item.color"
                                :style="{ width: `${(item.count / maxUrgency) * 100}%` }"></div>
                        </div>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ item.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Material Requests List -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-zinc-800">
                    <h3 class="text-sm font-black uppercase">Pending Material Requests</h3>
                    <p class="text-xs text-gray-500 mt-1">Requests forwarded by SCM that need RFQ creation</p>
                </div>
                <div class="p-4 sm:p-6">
                    <div v-if="materialRequests.length === 0" class="text-center py-12 text-gray-500">
                        <ClipboardList class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                        No material requests forwarded.
                    </div>
                    <div class="space-y-4">
                        <div v-for="req in materialRequests" :key="req.id"
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/30 hover:shadow-md transition">
                            <div class="space-y-1">
                                <p class="font-bold text-gray-900 dark:text-white text-lg">{{ req.material_name }}</p>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="px-2 py-1 bg-gray-200 dark:bg-zinc-700 rounded-full">Qty: {{
                                        req.required_qty }} {{ req.unit }}</span>
                                    <span :class="{
                                        'bg-red-100 text-red-700': req.urgency === 'High',
                                        'bg-yellow-100 text-yellow-700': req.urgency === 'Medium',
                                        'bg-green-100 text-green-700': req.urgency === 'Low'
                                    }" class="px-2 py-1 rounded-full font-semibold">Urgency: {{ req.urgency }}</span>
                                    <span class="px-2 py-1 bg-gray-200 dark:bg-zinc-700 rounded-full">Ref: {{
                                        req.req_number }}</span>
                                </div>
                                <p v-if="req.notes" class="text-xs text-gray-500 mt-1 italic">"{{ req.notes }}"</p>
                            </div>
                            <div class="mt-3 sm:mt-0">
                                <button @click="openRFQ(req)"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold flex items-center gap-1 transition">
                                    Create RFQ
                                    <Send class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RFQ Modal (Enhanced) -->
        <Teleport to="body">
            <div v-if="showRFQModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showRFQModal = false">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
                    <div
                        class="sticky top-0 bg-white dark:bg-gray-900 p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <h3 class="text-xl font-black">Create Request for Quotation (RFQ)</h3>
                        <button @click="showRFQModal = false"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-6">
                        <div v-if="!supplierStep">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl mb-6">
                                <p class="font-bold text-blue-900 dark:text-blue-300">Material: {{
                                    selectedRequest.material_name }}</p>
                                <p class="text-sm text-blue-700 dark:text-blue-400">Required Quantity: {{
                                    selectedRequest.required_qty }} {{ selectedRequest.unit }}</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Response
                                        Deadline *</label>
                                    <input v-model="rfqForm.deadline" type="date"
                                        :min="new Date().toISOString().slice(0, 10)"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Delivery
                                        Address *</label>
                                    <select v-model="rfqForm.delivery_address"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-800">
                                        <option value="">Select Warehouse</option>
                                        <option v-for="wh in warehouses" :key="wh.id"
                                            :value="wh.name + ' - ' + wh.location">
                                            {{ wh.name }} ({{ wh.location }})
                                        </option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Payment
                                        Terms</label>
                                    <select v-model="rfqForm.payment_terms"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-800">
                                        <option>Net 30</option>
                                        <option>Net 45</option>
                                        <option>Cash on Delivery</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Notes
                                        (Optional)</label>
                                    <textarea v-model="rfqForm.notes" rows="3"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-800"
                                        placeholder="Additional instructions..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Select suppliers to send this RFQ
                                to:</p>
                            <div class="space-y-2 max-h-80 overflow-y-auto">
                                <div v-for="sup in suppliers" :key="sup.id" @click="toggleSupplier(sup.id)"
                                    class="flex items-center p-3 border rounded-xl cursor-pointer transition"
                                    :class="rfqForm.selected_suppliers.includes(sup.id)
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'">
                                    <div class="w-6 h-6 mr-3 flex items-center justify-center">
                                        <CheckCircle v-if="rfqForm.selected_suppliers.includes(sup.id)"
                                            class="w-5 h-5 text-blue-600" />
                                        <div v-else
                                            class="w-5 h-5 border border-gray-300 dark:border-gray-600 rounded-full">
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ sup.business_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ sup.representative_name
                                        }} | {{ sup.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                        <button v-if="supplierStep" @click="supplierStep = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            Back
                        </button>
                        <button v-if="!supplierStep" @click="proceedToSuppliers"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                            Next
                        </button>
                        <button v-if="supplierStep" @click="submitRFQ"
                            :disabled="rfqForm.selected_suppliers.length === 0"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Send RFQ
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>