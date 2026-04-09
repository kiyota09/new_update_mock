<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Calendar, CheckCircle, XCircle, Clock, Search, Filter,
    User, Briefcase, AlertCircle, ChevronDown, X
} from 'lucide-vue-next';

const props = defineProps({
    leaveRequests: {
        type: Array,
        default: () => []
    }
});

// Toast notification
const showToast = ref(false);
const toastMessage = ref('');
const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 4000);
};

// Flash messages from server
const page = usePage();
if (page.props.flash?.message) {
    triggerToast(page.props.flash.message);
}

// Filters
const searchQuery = ref('');
const statusFilter = ref('ALL');

const filteredRequests = computed(() => {
    let list = props.leaveRequests;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(r =>
            r.user_name.toLowerCase().includes(q) ||
            r.user_role.toLowerCase().includes(q) ||
            r.leave_type.toLowerCase().includes(q)
        );
    }
    if (statusFilter.value !== 'ALL') {
        list = list.filter(r => r.status === statusFilter.value);
    }
    return list;
});

// Stats
const stats = computed(() => ({
    total: props.leaveRequests.length,
    pending: props.leaveRequests.filter(r => r.status === 'pending').length,
    approved: props.leaveRequests.filter(r => r.status === 'approved').length,
    rejected: props.leaveRequests.filter(r => r.status === 'rejected').length,
}));

// Rejection modal state
const isRejectModalOpen = ref(false);
const selectedRequest = ref(null);
const rejectReason = ref('');

const openRejectModal = (request) => {
    selectedRequest.value = request;
    rejectReason.value = '';
    isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
    isRejectModalOpen.value = false;
    selectedRequest.value = null;
    rejectReason.value = '';
};

const approveRequest = (id) => {
    if (confirm('Approve this leave request?')) {
        router.post(route('workforce.leave.approve', id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                triggerToast('Leave request approved.');
            },
            onError: (errors) => {
                triggerToast(Object.values(errors)[0] || 'Approval failed.');
            }
        });
    }
};

const rejectRequest = () => {
    if (!rejectReason.value.trim()) {
        alert('Please provide a reason for rejection.');
        return;
    }
    router.post(route('workforce.leave.reject', selectedRequest.value.id), {
        reason: rejectReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast('Leave request rejected.');
            closeRejectModal();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Rejection failed.');
        }
    });
};

// Helper functions
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'rejected': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700';
    }
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'N/A';
const getInitials = (name) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
</script>

<template>
    <Head title="Leave Management" />

    <AuthenticatedLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400 dark:text-emerald-600" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Leave <span class="text-blue-600">Management</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Review and manage employee leave requests.
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Requests</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <Calendar class="w-5 h-5 text-blue-600" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.pending }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                            <Clock class="w-5 h-5 text-amber-600" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Approved</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.approved }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <CheckCircle class="w-5 h-5 text-emerald-600" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rejected</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.rejected }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <XCircle class="w-5 h-5 text-red-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search by name, role, or leave type..."
                        class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600 text-sm" />
                </div>
                <div class="flex gap-2">
                    <button v-for="status in ['ALL', 'pending', 'approved', 'rejected']" :key="status"
                        @click="statusFilter = status" :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold uppercase transition-all',
                            statusFilter === status
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700'
                        ]">
                        {{ status === 'ALL' ? 'All' : status }}
                    </button>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Employee</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Leave Type</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Duration</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reason</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No leave requests found.
                                </td>
                            </tr>
                            <tr v-for="req in filteredRequests" :key="req.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-xs">
                                            {{ getInitials(req.user_name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ req.user_name }}</p>
                                            <p class="text-xs text-slate-400">{{ req.user_role }}</p>
                                        </div>
                                    </div>
                                 </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-700 dark:text-slate-300 capitalize">{{ req.leave_type }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(req.start_date) }} – {{ formatDate(req.end_date) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 max-w-xs truncate">{{ req.reason || 'No reason provided' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[getStatusBadgeClass(req.status), 'px-3 py-1 rounded-full text-[10px] font-black uppercase']">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div v-if="req.status === 'pending'" class="flex justify-end gap-2">
                                        <button @click="approveRequest(req.id)" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition" title="Approve">
                                            <CheckCircle class="h-5 w-5" />
                                        </button>
                                        <button @click="openRejectModal(req)" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition" title="Reject">
                                            <XCircle class="h-5 w-5" />
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">No action</span>
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rejection Modal -->
        <div v-if="isRejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeRejectModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-red-600 p-4 text-white flex justify-between items-center">
                    <h2 class="text-lg font-black uppercase">Reject Leave Request</h2>
                    <button @click="closeRejectModal" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">Rejecting request for <strong>{{ selectedRequest?.user_name }}</strong></p>
                    <div>
                        <label class="text-xs font-black text-slate-500 uppercase block mb-1">Reason for Rejection</label>
                        <textarea v-model="rejectReason" rows="3" class="w-full px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-900 border-none" placeholder="Explain why this request is rejected..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button @click="closeRejectModal" class="flex-1 py-2 text-slate-500 font-bold">Cancel</button>
                        <button @click="rejectRequest" class="flex-1 py-2 bg-red-600 text-white rounded-xl font-bold">Confirm Rejection</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}
</style>