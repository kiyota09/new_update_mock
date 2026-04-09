<template>
    <Head title="Conductor Portal - My Trips" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <ClipboardList class="h-3.5 w-3.5" />
                        Conductor Portal
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        My <span class="text-indigo-600">Trips & Reports</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        View assigned deliveries, submit on‑road reports to the logistics manager.
                    </p>
                </div>
            </div>

            <!-- Delivery Cards -->
            <div v-if="deliveries.length === 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-12 text-center border border-gray-100 dark:border-gray-800">
                <ClipboardList class="h-16 w-16 mx-auto text-gray-300 mb-4" />
                <p class="text-gray-500 font-bold">No trips assigned.</p>
                <p class="text-xs text-gray-400">You will appear here when assigned to a delivery.</p>
            </div>

            <div v-for="delivery in deliveries" :key="delivery.id" class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4 text-white flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-black opacity-80">DELIVERY #</p>
                        <p class="font-mono text-lg font-bold">{{ delivery.delivery_number }}</p>
                    </div>
                    <span :class="statusBadge(delivery.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-white/20 text-white">
                        {{ formatStatus(delivery.status) }}
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Driver & Route -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <Users class="h-5 w-5 text-indigo-500 mt-0.5" />
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase">Driver</p>
                                <p class="font-medium">{{ delivery.driver?.user?.name || '—' }}</p>
                                <p class="text-xs text-gray-500">License: {{ delivery.driver?.license_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Navigation class="h-5 w-5 text-emerald-500 mt-0.5" />
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase">Route</p>
                                <p class="font-medium">{{ delivery.route?.name || '—' }}</p>
                                <p class="text-xs text-gray-500">{{ delivery.route?.origin }} → {{ delivery.route?.destination }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Packages Summary -->
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                        <p class="text-[10px] font-black text-gray-400 uppercase">Packages on Board</p>
                        <p class="text-sm">{{ delivery.packages?.length || 0 }} package(s) · {{ totalQuantity(delivery) }} pcs total</p>
                    </div>

                    <!-- Report Button (only for in_transit or delivered) -->
                    <div v-if="delivery.status !== 'dispatched'" class="pt-2">
                        <button @click="openReportModal(delivery)" class="w-full py-3 border border-indigo-300 text-indigo-700 rounded-xl text-[10px] font-black uppercase hover:bg-indigo-50 transition flex items-center justify-center gap-2">
                            <FileText class="h-4 w-4" />
                            Submit Report
                        </button>
                    </div>
                    <div v-else class="text-center text-xs text-gray-400 italic">
                        Report available after trip starts.
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Modal -->
        <Teleport to="body">
            <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeReportModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-amber-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Submit Conductor Report</h3>
                        <button @click="closeReportModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <form @submit.prevent="submitReport" class="p-6 space-y-5">
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                            <p class="text-xs font-bold">Delivery #{{ selectedDelivery?.delivery_number }}</p>
                            <p class="text-xs text-gray-500">Driver: {{ selectedDelivery?.driver?.user?.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Report Details *</label>
                            <textarea v-model="reportText" rows="5" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 px-4 py-3 text-sm"
                                placeholder="e.g., Road conditions, delays, accidents, customer feedback, vehicle issues..."></textarea>
                        </div>
                        <div class="text-xs text-gray-400">
                            Your report will be sent directly to the Logistics Manager.
                        </div>
                        <button type="submit" :disabled="submitting"
                            class="w-full py-3 bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-amber-700 transition flex items-center justify-center gap-2">
                            <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            {{ submitting ? 'Sending...' : 'Send Report' }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Toast -->
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
import { ref } from 'vue';
import { ClipboardList, Users, Navigation, FileText, X, Loader2, Send } from 'lucide-vue-next';

const props = defineProps({
    deliveries: {
        type: Array,
        default: () => []
    }
});

const showReportModal = ref(false);
const selectedDelivery = ref(null);
const reportText = ref('');
const submitting = ref(false);
const toast = ref({ show: false, type: 'success', message: '' });

const statusBadge = (status) => {
    const map = {
        dispatched: 'bg-amber-100 text-amber-700',
        in_transit: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = { dispatched: 'Dispatched', in_transit: 'In Transit', delivered: 'Delivered' };
    return map[status] || status;
};

const totalQuantity = (delivery) => {
    return delivery.packages?.reduce((sum, p) => sum + (p.quantity || 0), 0) || 0;
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const openReportModal = (delivery) => {
    selectedDelivery.value = delivery;
    reportText.value = '';
    showReportModal.value = true;
};

const closeReportModal = () => {
    showReportModal.value = false;
    selectedDelivery.value = null;
};

const submitReport = async () => {
    if (!reportText.value.trim()) {
        showToast('error', 'Please enter report details.');
        return;
    }
    submitting.value = true;
    try {
        await router.post(route('logistics.conductor.report', selectedDelivery.value.id), {
            report: reportText.value
        });
        showToast('success', 'Report submitted to Logistics Manager.');
        closeReportModal();
    } catch (error) {
        showToast('error', 'Failed to submit report.');
    } finally {
        submitting.value = false;
    }
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