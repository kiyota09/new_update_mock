<template>
    <Head title="Conductor Reports" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <FileText class="h-3.5 w-3.5" />
                        Field Intelligence
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Conductor <span class="text-indigo-600">Reports</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Review on‑road observations, incidents, and delivery feedback from conductors.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Reports</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ reports.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Active Conductors</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ uniqueConductorsCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">This Month</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ thisMonthCount }}</p>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by conductor, delivery #, or report text..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="dateFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Conductor</th>
                                <th class="px-8 py-5">Delivery #</th>
                                <th class="px-8 py-5">Report</th>
                                <th class="px-8 py-5 text-center">Submitted</th>
                                <th class="px-8 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="report in filteredReports" :key="report.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-sm font-black">
                                            {{ report.conductor?.user?.name?.charAt(0) || 'C' }}
                                        </div>
                                        <div>
                                            <p class="font-black text-gray-900 dark:text-white">{{ report.conductor?.user?.name }}</p>
                                            <p class="text-[10px] text-gray-400">{{ report.conductor?.user?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <Truck class="h-4 w-4 text-gray-400" />
                                        <span class="font-mono text-sm">{{ report.delivery?.delivery_number }}</span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">
                                        {{ formatDate(report.delivery?.scheduled_departure) }}
                                    </p>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-md">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap line-clamp-3">
                                            {{ report.report_text }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-sm font-medium">{{ formatTime(report.created_at) }}</span>
                                        <span class="text-[10px] text-gray-400">{{ formatDate(report.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="viewFullReport(report)" class="p-2 rounded-lg hover:bg-indigo-50 transition">
                                        <Eye class="h-4 w-4 text-indigo-600" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredReports.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <FileText class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No conductor reports submitted yet.
                                    <br>
                                    <span class="text-xs">Conductors can submit reports from their portal.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Full Report Modal -->
        <Teleport to="body">
            <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeReportModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Conductor Report</h3>
                        <button @click="closeReportModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <div v-if="selectedReport" class="p-6 space-y-5">
                        <!-- Header Info -->
                        <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Conductor</p>
                                <p class="font-bold">{{ selectedReport.conductor?.user?.name }}</p>
                                <p class="text-xs text-gray-500">{{ selectedReport.conductor?.user?.email }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Delivery</p>
                                <p class="font-mono">{{ selectedReport.delivery?.delivery_number }}</p>
                                <p class="text-xs text-gray-500">{{ formatDateTime(selectedReport.delivery?.scheduled_departure) }}</p>
                            </div>
                        </div>

                        <!-- Report Content -->
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Report Text</p>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                                {{ selectedReport.report_text }}
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="text-xs text-gray-400 border-t border-gray-100 pt-4 flex justify-between">
                            <span>Report ID: #{{ selectedReport.id }}</span>
                            <span>Submitted: {{ formatDateTime(selectedReport.created_at) }}</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-2">
                            <button @click="closeReportModal" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-800 rounded-xl text-[10px] font-black uppercase hover:bg-gray-200 transition">
                                Close
                            </button>
                            <button v-if="selectedReport.delivery" @click="goToDelivery(selectedReport.delivery.id)" class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                                <Truck class="h-3 w-3" />
                                View Delivery
                            </button>
                        </div>
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
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FileText, RefreshCw, Search, Truck, Eye, X } from 'lucide-vue-next';

const props = defineProps({
    reports: {
        type: Array,
        default: () => []
    }
});

// Filters
const searchTerm = ref('');
const dateFilter = ref('all');

// Modal
const showReportModal = ref(false);
const selectedReport = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

const filteredReports = computed(() => {
    let list = props.reports;
    
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(r => 
            r.conductor?.user?.name?.toLowerCase().includes(term) ||
            r.delivery?.delivery_number?.toLowerCase().includes(term) ||
            r.report_text?.toLowerCase().includes(term)
        );
    }
    
    if (dateFilter.value !== 'all') {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 7);
        const monthAgo = new Date(today);
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        
        list = list.filter(r => {
            const submittedAt = new Date(r.created_at);
            if (dateFilter.value === 'today') return submittedAt >= today;
            if (dateFilter.value === 'week') return submittedAt >= weekAgo;
            if (dateFilter.value === 'month') return submittedAt >= monthAgo;
            return true;
        });
    }
    
    return list;
});

const uniqueConductorsCount = computed(() => {
    const conductorIds = new Set();
    props.reports.forEach(r => {
        if (r.conductor?.id) conductorIds.add(r.conductor.id);
    });
    return conductorIds.size;
});

const thisMonthCount = computed(() => {
    const now = new Date();
    const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
    return props.reports.filter(r => new Date(r.created_at) >= monthAgo).length;
});

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['reports'] });
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

const viewFullReport = (report) => {
    selectedReport.value = report;
    showReportModal.value = true;
};

const closeReportModal = () => {
    showReportModal.value = false;
    selectedReport.value = null;
};

const goToDelivery = (deliveryId) => {
    router.visit(route('logistics.dispatch.index')); // or a dedicated delivery view
    closeReportModal();
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>