<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Users, Package, Factory, TrendingUp, Search,
    X, ChevronRight, ArrowRight, Briefcase,
    Wrench, Sparkles, CheckCircle2, Palette,
    AlertCircle, RefreshCw, UserCheck, Clock,
    Calendar, BarChart3, PieChart, Zap, Activity,
    ShieldCheck
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    staff: Array,
});

// Reactive staff list
const staffList = ref([]);
const searchQuery = ref('');

// Modal state
const showConfirmModal = ref(false);
const pendingStaffId = ref(null);
const pendingNewRole = ref('');
const pendingOldRole = ref('');
const pendingStaffName = ref('');
const isUpdating = ref(false);

// Toast state
const toastMessage = ref('');
const toastType = ref('success');
const showToast = ref(false);
let toastTimeout = null;

onMounted(() => {
    staffList.value = props.staff.map(staff => ({
        ...staff,
        newRole: staff.manufacturing_role || ''
    }));
});

// Filter staff
const filteredStaff = computed(() => {
    if (!searchQuery.value.trim()) return staffList.value;
    const query = searchQuery.value.toLowerCase();
    return staffList.value.filter(staff =>
        staff.name.toLowerCase().includes(query) ||
        (staff.manufacturing_role && staff.manufacturing_role.replace(/_/g, ' ').toLowerCase().includes(query))
    );
});

// Helper for greeting
const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
};

// Chart data (placeholder – replace with real data)
const productionTrend = computed(() => ({
    months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    values: [45, 52, 48, 67, 72, 68],
}));

const machineUtilization = computed(() => ({
    available: 7,
    maintenance: 2,
    retired: 1,
}));

const maxTrendValue = computed(() => Math.max(...productionTrend.value.values, 1));
const totalMachines = computed(() => machineUtilization.value.available + machineUtilization.value.maintenance + machineUtilization.value.retired);

const clearSearch = () => {
    searchQuery.value = '';
};

// Show toast notification
const showToastMessage = (message, type = 'success') => {
    if (toastTimeout) clearTimeout(toastTimeout);
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    toastTimeout = setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

// Open confirmation modal
const openConfirmModal = (staffId, newRole, oldRole, staffName) => {
    pendingStaffId.value = staffId;
    pendingNewRole.value = newRole;
    pendingOldRole.value = oldRole;
    pendingStaffName.value = staffName;
    showConfirmModal.value = true;
};

// Close modal without changes
const closeModal = () => {
    showConfirmModal.value = false;
    const index = staffList.value.findIndex(s => s.id === pendingStaffId.value);
    if (index !== -1) {
        staffList.value[index].newRole = pendingOldRole.value;
    }
    pendingStaffId.value = null;
    pendingNewRole.value = '';
    pendingOldRole.value = '';
    pendingStaffName.value = '';
};

// Confirm and update role
const confirmUpdate = () => {
    if (isUpdating.value) return;
    isUpdating.value = true;

    router.post(
        route('man.manager.update-staff-role', pendingStaffId.value),
        { manufacturing_role: pendingNewRole.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                const index = staffList.value.findIndex(s => s.id === pendingStaffId.value);
                if (index !== -1) {
                    staffList.value[index].manufacturing_role = pendingNewRole.value;
                    staffList.value[index].newRole = pendingNewRole.value;
                }
                showToastMessage(`Role updated for ${pendingStaffName.value}`, 'success');
                closeModal();
            },
            onError: () => {
                showToastMessage('Failed to update role. Please try again.', 'error');
                closeModal();
            },
            onFinish: () => {
                isUpdating.value = false;
            }
        }
    );
};

// Handle role change from select
const onRoleChange = (staffId, newRole, oldRole, staffName) => {
    if (!newRole) return;
    openConfirmModal(staffId, newRole, oldRole, staffName);
};

// Format role for display
const formatRole = (role) => {
    if (!role) return 'Unassigned';
    return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Role options
const roleOptions = [
    { value: '', label: 'Select Role' },
    { value: 'knitting_yarn', label: 'Knitting Yarn' },
    { value: 'dyeing_color', label: 'Dyeing Color' },
    { value: 'dyeing_fabric_softener', label: 'Dyeing Fabric Softener' },
    { value: 'dyeing_squeezer', label: 'Dyeing Squeezer' },
    { value: 'dyeing_ironing', label: 'Dyeing Ironing' },
    { value: 'dyeing_forming', label: 'Dyeing Forming' },
    { value: 'dyeing_packaging', label: 'Dyeing Packaging' },
    { value: 'maintenance_checker', label: 'Maintenance Checker' },
    { value: 'checker_quality', label: 'Checker Quality' },
];

// Get role icon
const getRoleIcon = (role) => {
    if (!role) return Briefcase;
    if (role.includes('knitting')) return Sparkles;
    if (role.includes('dyeing')) return Palette;
    if (role.includes('maintenance')) return Wrench;
    if (role.includes('checker')) return CheckCircle2;
    return Briefcase;
};

// Avatar color
const getAvatarColor = (name) => {
    const colors = ['from-blue-500 to-indigo-600', 'from-emerald-500 to-teal-600', 'from-rose-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-purple-500 to-violet-600'];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Manufacturing Management" />
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-black">
                            Manufacturing Management <span class="text-blue-600">Dashboard</span>
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ getGreeting() }}, oversee production and staff</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <Calendar class="w-4 h-4" />
                        <span>{{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <div class="group relative overflow-hidden bg-white dark:bg-zinc-900/80 backdrop-blur-sm p-5 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-zinc-800 hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Received Orders</p>
                                <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.receivedOrders }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center gap-1"><TrendingUp class="w-3 h-3" /> +12%</p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl group-hover:rotate-6 transition-transform">
                                <Package class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                    <div class="group relative overflow-hidden bg-white dark:bg-zinc-900/80 backdrop-blur-sm p-5 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-zinc-800 hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">In Production</p>
                                <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.inProduction }}</p>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Active orders</p>
                            </div>
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl group-hover:rotate-6 transition-transform">
                                <Factory class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </div>
                    </div>
                    <div class="group relative overflow-hidden bg-white dark:bg-zinc-900/80 backdrop-blur-sm p-5 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-zinc-800 hover:scale-[1.02] sm:col-span-2 lg:col-span-1">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Machines</p>
                                <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.activeMachines }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center gap-1"><CheckCircle2 class="w-3 h-3" /> Operational</p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-2xl group-hover:rotate-6 transition-transform">
                                <TrendingUp class="w-5 h-5 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link :href="route('man.manager.production')"
                        class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <Factory class="w-5 h-5 group-hover:rotate-12 transition-transform" />
                        View Production Orders
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    </Link>
                    <Link :href="route('man.manager.rejected')"
                        class="group relative overflow-hidden bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <X class="w-5 h-5 group-hover:rotate-90 transition-transform" />
                        View Rejected Items
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    </Link>
                    <!-- <Link :href="route('man.access.manage')"
                        class="group relative overflow-hidden bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <ShieldCheck class="w-5 h-5 group-hover:scale-110 transition-transform" />
                        Access Control (Supervisors)
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    </Link> -->
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Production Trend -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2 mb-4">
                            <BarChart3 class="w-4 h-4 text-blue-500" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Production Output Trend</h3>
                        </div>
                        <div class="h-48 flex items-end gap-2">
                            <div v-for="(value, idx) in productionTrend.values" :key="idx" class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-gradient-to-t from-blue-500 to-blue-600 rounded-t-lg transition-all duration-500" :style="{ height: `${(value / maxTrendValue) * 100}%`, minHeight: '4px' }"></div>
                                <span class="text-[10px] sm:text-xs mt-2 text-gray-500">{{ productionTrend.months[idx] }}</span>
                                <span class="text-[9px] font-bold text-gray-600 dark:text-gray-400">{{ value }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Machine Utilization -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2 mb-4">
                            <PieChart class="w-4 h-4 text-emerald-500" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Machine Status</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="w-20 text-sm font-medium text-gray-600 dark:text-gray-400">Available</span>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full transition-all duration-700" :style="{ width: `${(machineUtilization.available / totalMachines) * 100}%` }"></div>
                                </div>
                                <span class="text-sm font-bold">{{ machineUtilization.available }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-20 text-sm font-medium text-gray-600 dark:text-gray-400">Maintenance</span>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500 rounded-full transition-all duration-700" :style="{ width: `${(machineUtilization.maintenance / totalMachines) * 100}%` }"></div>
                                </div>
                                <span class="text-sm font-bold">{{ machineUtilization.maintenance }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-20 text-sm font-medium text-gray-600 dark:text-gray-400">Retired</span>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full transition-all duration-700" :style="{ width: `${(machineUtilization.retired / totalMachines) * 100}%` }"></div>
                                </div>
                                <span class="text-sm font-bold">{{ machineUtilization.retired }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Staff Management -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Activity (Placeholder) -->
                    <div class="lg:col-span-1 bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2 mb-4">
                            <Activity class="w-4 h-4 text-purple-500" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Recent Activity</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="p-1.5 bg-blue-50 rounded-lg"><Factory class="w-3 h-3 text-blue-600" /></div>
                                <div><p class="text-sm text-gray-700 dark:text-gray-300">Order #PO-2026-045 sent to production</p><p class="text-xs text-gray-400">2 hours ago</p></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="p-1.5 bg-green-50 rounded-lg"><Users class="w-3 h-3 text-green-600" /></div>
                                <div><p class="text-sm text-gray-700 dark:text-gray-300">Staff role updated: John → Dyeing Color</p><p class="text-xs text-gray-400">Yesterday</p></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="p-1.5 bg-amber-50 rounded-lg"><AlertCircle class="w-3 h-3 text-amber-600" /></div>
                                <div><p class="text-sm text-gray-700 dark:text-gray-300">Low yarn stock alert for Polyester</p><p class="text-xs text-gray-400">2 days ago</p></div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Management Section -->
                    <div class="lg:col-span-2 bg-white dark:bg-zinc-900/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-800 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-gray-50 to-white dark:from-zinc-800/50 dark:to-zinc-900/50">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-xl"><Users class="w-5 h-5 text-blue-600 dark:text-blue-400" /></div>
                                    <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Manufacturing Staff</h2>
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 rounded-full">{{ filteredStaff.length }} members</span>
                                </div>
                                <div class="relative w-full sm:w-64">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input v-model="searchQuery" type="text" placeholder="Search by name or role..." class="w-full pl-9 pr-8 py-2 text-sm border border-gray-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
                                    <button v-if="searchQuery" @click="clearSearch" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"><X class="w-4 h-4" /></button>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="block md:hidden divide-y divide-gray-100 dark:divide-zinc-800">
                            <div v-for="user in filteredStaff" :key="user.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-all">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <div :class="['w-10 h-10 bg-gradient-to-br rounded-xl flex items-center justify-center text-white font-bold text-sm', getAvatarColor(user.name)]">{{ user.name.charAt(0).toUpperCase() }}</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ user.name }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 capitalize flex items-center gap-1">
                                            <component :is="getRoleIcon(user.manufacturing_role)" class="w-3 h-3" /> {{ formatRole(user.manufacturing_role) }}
                                        </p>
                                        <!-- Supervisor badge -->
                                        <p v-if="user.is_manufacturing_supervisor" class="text-xs mt-1 text-purple-600 dark:text-purple-400 font-medium">Supervisor</p>
                                        <div class="mt-3">
                                            <select v-model="user.newRole" @change="onRoleChange(user.id, user.newRole, user.manufacturing_role, user.name)" class="w-full border border-gray-200 dark:border-zinc-700 rounded-lg p-2 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                                <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <ChevronRight class="w-4 h-4 text-gray-400 flex-shrink-0 mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-100 dark:border-zinc-800">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff Member</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Role</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supervisor</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Update Role</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                    <tr v-for="user in filteredStaff" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-all">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div :class="['w-8 h-8 bg-gradient-to-br rounded-lg flex items-center justify-center text-white font-bold text-xs', getAvatarColor(user.name)]">{{ user.name.charAt(0).toUpperCase() }}</div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ user.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1">
                                                <component :is="getRoleIcon(user.manufacturing_role)" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-gray-300 capitalize">{{ formatRole(user.manufacturing_role) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="user.is_manufacturing_supervisor" class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">Yes</span>
                                            <span v-else class="text-gray-400 text-xs">No</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <select v-model="user.newRole" @change="onRoleChange(user.id, user.newRole, user.manufacturing_role, user.name)" class="border border-gray-200 dark:border-zinc-700 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 w-48">
                                                <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="filteredStaff.length === 0" class="text-center py-12">
                            <Users class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">No staff members found</p>
                            <button @click="clearSearch" class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">Clear search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full transform transition-all border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center"><UserCheck class="w-6 h-6 text-amber-600 dark:text-amber-400" /></div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Confirm Role Change</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Are you sure you want to change the role of <span class="font-semibold">{{ pendingStaffName }}</span> from
                        <span class="font-semibold text-gray-900 dark:text-white">{{ formatRole(pendingOldRole) }}</span> to
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ formatRole(pendingNewRole) }}</span>?
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="closeModal" :disabled="isUpdating" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-800 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-700 transition disabled:opacity-50">Cancel</button>
                        <button @click="confirmUpdate" :disabled="isUpdating" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 disabled:opacity-50">
                            <RefreshCw v-if="isUpdating" class="w-4 h-4 animate-spin" /> Confirm Change
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="showToast" class="fixed bottom-4 right-4 z-50 transform transition-all duration-300" :class="{ 'bg-green-100 dark:bg-green-900/80 border-green-400': toastType === 'success', 'bg-red-100 dark:bg-red-900/80 border-red-400': toastType === 'error' }" style="animation: slide-up 0.3s ease-out;">
            <div class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border">
                <component :is="toastType === 'success' ? CheckCircle2 : AlertCircle" class="w-5 h-5" :class="toastType === 'success' ? 'text-green-600' : 'text-red-600'" />
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ toastMessage }}</span>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
* { scroll-behavior: smooth; }
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.dark ::-webkit-scrollbar-track { background: #1f1f1f; }
.dark ::-webkit-scrollbar-thumb { background: #3f3f46; }
.dark ::-webkit-scrollbar-thumb:hover { background: #52525b; }
@keyframes slide-up {
    from { transform: translateY(1rem); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>