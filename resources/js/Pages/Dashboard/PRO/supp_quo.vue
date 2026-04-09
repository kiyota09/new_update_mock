<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Eye, CheckCircle, XCircle, AlertTriangle, Ban,
    X, Clock, BadgeCheck, FileText, Send, DollarSign
} from 'lucide-vue-next';

const props = defineProps({
    rfqs: Array,
});

// Modals state
const showDetailModal = ref(false);
const selectedRFQ = ref(null);
const showAcceptModal = ref(false);
const acceptTarget = ref(null);
const showDeclineModal = ref(false);
const declineTarget = ref(null);
const declineReason = ref('');
const isLoading = ref(false);

// Helpers
const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const statusBadge = (status) => {
    const map = {
        sent: 'bg-blue-100 text-blue-700',
        responded: 'bg-green-100 text-green-700',
        closed: 'bg-gray-100 text-gray-700',
        pending_review: 'bg-amber-100 text-amber-700',
        accepted: 'bg-emerald-100 text-emerald-700',
        declined: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
    if (!status) return '';
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Modal handlers
const openDetail = (rfq) => {
    selectedRFQ.value = rfq;
    showDetailModal.value = true;
};

const openAccept = (rfq, response) => {
    acceptTarget.value = { rfq, response };
    showAcceptModal.value = true;
};

const confirmAccept = () => {
    isLoading.value = true;
    router.post(route('pro.manager.quotations.accept', acceptTarget.value.response.id), {
        rfq_id: acceptTarget.value.rfq.id,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAcceptModal.value = false;
            isLoading.value = false;
        },
        onError: () => { isLoading.value = false; }
    });
};

const openDecline = (rfq, response) => {
    declineTarget.value = { rfq, response };
    declineReason.value = '';
    showDeclineModal.value = true;
};

const confirmDecline = () => {
    if (!declineReason.value.trim()) return;
    isLoading.value = true;
    router.post(route('pro.manager.quotations.decline', declineTarget.value.response.id), {
        reason: declineReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeclineModal.value = false;
            isLoading.value = false;
        },
        onError: () => { isLoading.value = false; }
    });
};
</script>

<template>

    <Head title="PRO - Supplier Quotations" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Supplier Quotations</h2>
            <p class="text-sm text-gray-500">Review and accept supplier quotes.</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div v-if="rfqs.length === 0" class="text-center py-12 text-gray-400">
                    No RFQs have been sent.
                </div>
                <div class="space-y-4">
                    <div v-for="rfq in rfqs" :key="rfq.id" class="border rounded-xl overflow-hidden">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                            <div>
                                <div class="flex gap-2 items-center">
                                    <span class="font-mono text-sm font-bold">{{ rfq.rfq_number }}</span>
                                    <span :class="statusBadge(rfq.status)" class="text-xs px-2 py-0.5 rounded-full">{{
                                        statusLabel(rfq.status) }}</span>
                                </div>
                                <p class="font-bold">{{ rfq.material_name }} ({{ rfq.required_qty }} {{ rfq.unit }})</p>
                                <p class="text-xs text-gray-500">Deadline: {{ rfq.deadline }}</p>
                            </div>
                            <button @click="openDetail(rfq)"
                                class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                                <Eye class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="p-4 space-y-2">
                            <div v-for="res in rfq.responses" :key="res.id"
                                class="flex justify-between items-center p-3 rounded-xl border"
                                :class="res.status === 'accepted' ? 'border-green-200 bg-green-50' : res.status === 'declined' ? 'border-red-200 bg-red-50 opacity-70' : 'border-gray-200'">
                                <div>
                                    <p class="font-bold">{{ res.supplier_name }}</p>
                                    <p class="text-xs">Unit: {{ formatCurrency(res.unit_price) }} | Total: {{
                                        formatCurrency(res.total_price) }} | Lead: {{ res.lead_time }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <template v-if="res.status === 'pending_review'">
                                        <button @click="openAccept(rfq, res)"
                                            class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs">Accept</button>
                                        <button @click="openDecline(rfq, res)"
                                            class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs">Decline</button>
                                    </template>
                                    <span v-else-if="res.status === 'accepted'"
                                        class="text-emerald-600 text-xs font-bold">✓ Accepted</span>
                                    <span v-else-if="res.status === 'declined'" class="text-red-600 text-xs font-bold">✗
                                        Declined</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RFQ Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedRFQ"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showDetailModal = false">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                    <div
                        class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                        <div>
                            <h3 class="text-lg font-black flex items-center gap-2">
                                <FileText class="w-5 h-5 text-blue-500" /> RFQ Details
                            </h3>
                            <p class="text-xs font-mono font-bold text-gray-500 mt-1">{{ selectedRFQ.rfq_number }}</p>
                        </div>
                        <button @click="showDetailModal = false"
                            class="p-2 bg-white dark:bg-gray-700 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 border border-gray-200 dark:border-gray-600">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-5 overflow-y-auto flex-1">
                        <div
                            class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="col-span-2">
                                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Target Material</p>
                                <p class="text-sm font-black">{{ selectedRFQ.material_name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Required Qty</p>
                                <p class="text-sm font-bold">{{ Number(selectedRFQ.required_qty).toLocaleString() }}
                                    <span class="text-xs text-gray-500">{{ selectedRFQ.unit }}</span></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Deadline</p>
                                <p class="text-sm font-bold">{{ selectedRFQ.deadline }}</p>
                            </div>
                            <div class="col-span-2" v-if="selectedRFQ.notes">
                                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Notes</p>
                                <p class="text-xs italic border-l-2 border-gray-200 pl-2">{{ selectedRFQ.notes }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-black flex items-center justify-between mb-3 border-b pb-2">
                                <span>Supplier Responses</span>
                                <span
                                    class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md text-[10px] uppercase tracking-widest">{{
                                    selectedRFQ.responses?.length || 0 }} Total</span>
                            </p>
                            <div v-if="!selectedRFQ.responses?.length" class="text-center py-6 text-gray-400 italic">
                                Awaiting vendor responses...
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="res in selectedRFQ.responses" :key="res.id"
                                    :class="['p-3 rounded-xl border', res.status === 'accepted' ? 'border-green-200 bg-green-50' : res.status === 'declined' ? 'border-red-200 bg-red-50 opacity-70' : 'border-gray-200']">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="font-black text-sm truncate">{{ res.supplier_name }}</p>
                                        <span
                                            :class="['text-[9px] font-black uppercase px-2 py-0.5 rounded-md', statusBadge(res.status)]">{{
                                            statusLabel(res.status) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                                        <div>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase">Unit Price</p>
                                            <p class="font-bold">{{ formatCurrency(res.unit_price) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase">Lead Time</p>
                                            <p class="font-bold">{{ res.lead_time }}</p>
                                        </div>
                                        <div
                                            class="col-span-2 pt-2 border-t border-gray-100 mt-1 flex justify-between items-center">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase">Total Value</p>
                                            <p class="font-black text-sm">{{ formatCurrency(res.total_price) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Accept Quotation Modal -->
        <Teleport to="body">
            <div v-if="showAcceptModal && acceptTarget"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showAcceptModal = false">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div
                        class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <CheckCircle class="w-5 h-5 text-emerald-500" /> Accept Quotation
                        </h3>
                        <button @click="showAcceptModal = false"
                            class="p-2 rounded-xl border border-gray-200 dark:border-gray-600">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-5">
                        <div
                            class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4">
                            <p class="text-[10px] font-black text-emerald-600 uppercase mb-3">Generating Purchase Order
                                for:</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-semibold text-emerald-600 text-xs">Supplier</span>
                                    <strong class="font-black truncate max-w-[180px]">{{
                                        acceptTarget.response.supplier_name
                                        }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold text-emerald-600 text-xs">Material</span>
                                    <strong class="font-black">{{ acceptTarget.rfq.material_name }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold text-emerald-600 text-xs">Quantity</span>
                                    <strong class="font-black">{{ Number(acceptTarget.rfq.required_qty).toLocaleString()
                                        }} {{
                                        acceptTarget.rfq.unit }}</strong>
                                </div>
                                <div class="pt-3 mt-3 border-t border-emerald-200">
                                    <div class="flex justify-between">
                                        <span class="font-black uppercase text-[10px] text-emerald-600">Subtotal</span>
                                        <strong>{{ formatCurrency(acceptTarget.response.total_price) }}</strong>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <span class="font-black uppercase text-[10px] text-emerald-600">Tax (10%)</span>
                                        <strong>{{ formatCurrency(acceptTarget.response.total_price * 0.1) }}</strong>
                                    </div>
                                </div>
                                <div class="pt-3 mt-1 border-t-2 border-emerald-300 border-dashed">
                                    <div class="flex justify-between items-end">
                                        <span class="font-black uppercase text-xs text-emerald-700 mb-0.5">Grand
                                            Total</span>
                                        <strong class="text-xl font-black">{{
                                            formatCurrency(acceptTarget.response.total_price *
                                            1.1) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 text-center">Accepting will decline all other quotes and create
                            a draft
                            PO.</p>
                    </div>
                    <div class="p-5 border-t border-gray-100 flex gap-3 bg-gray-50 dark:bg-gray-800">
                        <button @click="showAcceptModal = false"
                            class="flex-1 py-2 border rounded-xl text-sm font-bold">Cancel</button>
                        <button @click="confirmAccept" :disabled="isLoading"
                            class="flex-1 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                            <CheckCircle class="w-4 h-4" /> {{ isLoading ? 'Processing...' : 'Confirm & Create PO' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Decline Quotation Modal -->
        <Teleport to="body">
            <div v-if="showDeclineModal && declineTarget"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showDeclineModal = false">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div
                        class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <XCircle class="w-5 h-5 text-red-500" /> Decline Quotation
                        </h3>
                        <button @click="showDeclineModal = false"
                            class="p-2 rounded-xl border border-gray-200 dark:border-gray-600">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-5">
                        <div
                            class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 p-4 rounded-xl flex items-start gap-3">
                            <AlertTriangle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                            <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                Declining quote from <strong class="font-black">{{ declineTarget.response.supplier_name
                                    }}</strong>.
                                This action is final and the supplier will be notified.
                            </p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-1.5 uppercase">Reason for
                                Declining
                                *</label>
                            <textarea v-model="declineReason" rows="3"
                                placeholder="e.g. Price too high, better offer found..."
                                class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl resize-none"></textarea>
                        </div>
                    </div>
                    <div class="p-5 border-t border-gray-100 flex gap-3 bg-gray-50 dark:bg-gray-800">
                        <button @click="showDeclineModal = false"
                            class="flex-1 py-2 border rounded-xl text-sm font-bold">Cancel</button>
                        <button @click="confirmDecline" :disabled="isLoading || !declineReason.trim()"
                            class="flex-1 py-2 bg-red-600 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                            <Ban class="w-4 h-4" /> {{ isLoading ? 'Processing...' : 'Confirm Decline' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>