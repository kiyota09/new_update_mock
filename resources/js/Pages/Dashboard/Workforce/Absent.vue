<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    UserX, AlertTriangle, Calendar, Search, Filter,
    User, ShieldOff, X, CheckCircle, Clock
} from 'lucide-vue-next';

const props = defineProps({
    absentEmployees: {
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
const minConsecutiveAbsent = ref(1); // Minimum days to show

const filteredEmployees = computed(() => {
    let list = props.absentEmployees;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e =>
            e.name.toLowerCase().includes(q) ||
            e.role.toLowerCase().includes(q) ||
            e.department.toLowerCase().includes(q)
        );
    }
    list = list.filter(e => e.consecutive_absent_days >= minConsecutiveAbsent.value);
    return list;
});

// Stats
const stats = computed(() => ({
    total: props.absentEmployees.length,
    highAbsence: props.absentEmployees.filter(e => e.consecutive_absent_days >= 5).length,
    mediumAbsence: props.absentEmployees.filter(e => e.consecutive_absent_days >= 3 && e.consecutive_absent_days < 5).length,
    lowAbsence: props.absentEmployees.filter(e => e.consecutive_absent_days < 3).length,
}));

// Suspend modal state
const isSuspendModalOpen = ref(false);
const selectedEmployee = ref(null);
const suspendReason = ref('');

const openSuspendModal = (employee) => {
    selectedEmployee.value = employee;
    suspendReason.value = '';
    isSuspendModalOpen.value = true;
};

const closeSuspendModal = () => {
    isSuspendModalOpen.value = false;
    selectedEmployee.value = null;
    suspendReason.value = '';
};

const suspendEmployee = () => {
    if (!suspendReason.value.trim()) {
        alert('Please provide a reason for suspension.');
        return;
    }
    router.post(route('workforce.absent.suspend', selectedEmployee.value.id), {
        reason: suspendReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`Employee ${selectedEmployee.value.name} has been suspended.`);
            closeSuspendModal();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Suspension failed.');
        }
    });
};

// Helper functions
const getAbsenceLevel = (days) => {
    if (days >= 5) return 'Critical';
    if (days >= 3) return 'Warning';
    return 'Monitor';
};

const getAbsenceClass = (days) => {
    if (days >= 5) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    if (days >= 3) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
};

const formatDate = (date) => date ? new Date(date).toLocaleString() : 'N/A';
const getInitials = (name) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
</script>

<template>
    <Head title="Absence Management" />

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
                    Absence <span class="text-red-600">Tracking</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Monitor employee absenteeism and take action.
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Absences</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <UserX class="w-5 h-5 text-red-600" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Critical (≥5 days)</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.highAbsence }}</p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                            <AlertTriangle class="w-5 h-5 text-red-700" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Warning (3-4 days)</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.mediumAbsence }}</p>
                        </div>
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                            <Clock class="w-5 h-5 text-amber-700" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Monitor (1-2 days)</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.lowAbsence }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                            <Calendar class="w-5 h-5 text-yellow-700" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search by name, role, department..."
                        class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-red-500 text-sm" />
                </div>
                <div class="flex gap-2">
                    <button v-for="days in [1, 3, 5]" :key="days"
                        @click="minConsecutiveAbsent = days"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold uppercase transition-all',
                            minConsecutiveAbsent === days
                                ? 'bg-red-600 text-white shadow-md'
                                : 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700'
                        ]">
                        {{ days }}+ days
                    </button>
                </div>
            </div>

            <!-- Absent Employees Table -->
            <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Employee</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Consecutive Absences</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Last Clock In</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="filteredEmployees.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No employees match the current filters.
                                </td>
                            </tr>
                            <tr v-for="emp in filteredEmployees" :key="emp.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-xs">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.name }}</p>
                                            <p class="text-xs text-slate-400">{{ emp.role }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ emp.department }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getAbsenceClass(emp.consecutive_absent_days)]">
                                            {{ emp.consecutive_absent_days }} days
                                        </span>
                                        <span class="text-[9px] font-bold" :class="getAbsenceLevel(emp.consecutive_absent_days) === 'Critical' ? 'text-red-600' : 'text-amber-600'">
                                            ({{ getAbsenceLevel(emp.consecutive_absent_days) }})
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ emp.last_clock_in || 'Never' }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="emp.consecutive_absent_days >= 5" class="text-red-600 text-xs font-bold uppercase">Action Required</span>
                                    <span v-else class="text-amber-600 text-xs font-bold uppercase">Monitor</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button v-if="emp.consecutive_absent_days >= 5"
                                        @click="openSuspendModal(emp)"
                                        class="px-4 py-2 bg-red-600 text-white rounded-xl text-xs font-black uppercase hover:bg-red-700 transition flex items-center gap-2 ml-auto">
                                        <ShieldOff class="h-4 w-4" /> Suspend Account
                                    </button>
                                    <span v-else class="text-slate-400 text-xs italic">No action needed</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Suspend Modal -->
        <div v-if="isSuspendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeSuspendModal">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-red-600 p-4 text-white flex justify-between items-center">
                    <h2 class="text-lg font-black uppercase">Suspend Employee</h2>
                    <button @click="closeSuspendModal" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">You are about to suspend <strong>{{ selectedEmployee?.name }}</strong> due to excessive absenteeism.</p>
                    <div>
                        <label class="text-xs font-black text-slate-500 uppercase block mb-1">Reason for Suspension</label>
                        <textarea v-model="suspendReason" rows="3" class="w-full px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-900 border-none" placeholder="e.g., Unauthorized absence for 5 consecutive days..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button @click="closeSuspendModal" class="flex-1 py-2 text-slate-500 font-bold">Cancel</button>
                        <button @click="suspendEmployee" class="flex-1 py-2 bg-red-600 text-white rounded-xl font-bold">Confirm Suspension</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}
</style>