<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Users, UserCheck, UserX, Calendar, Clock, Search, Filter,
    Briefcase, Building2, ChevronDown, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    employees: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            present: 0,
            absent: 0,
            on_leave: 0
        })
    }
});

// Filters
const searchQuery = ref('');
const selectedModule = ref('ALL');
const selectedDepartment = ref('ALL');

// Extract unique modules and departments from employees
const modules = computed(() => {
    const mods = ['ALL', ...new Set(props.employees.map(e => e.role))];
    return mods;
});

const departments = computed(() => {
    const deps = ['ALL', ...new Set(props.employees.map(e => e.department).filter(Boolean))];
    return deps;
});

// Filtered employees
const filteredEmployees = computed(() => {
    let list = props.employees;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e =>
            e.name.toLowerCase().includes(q) ||
            e.role.toLowerCase().includes(q) ||
            e.department.toLowerCase().includes(q)
        );
    }
    if (selectedModule.value !== 'ALL') {
        list = list.filter(e => e.role === selectedModule.value);
    }
    if (selectedDepartment.value !== 'ALL') {
        list = list.filter(e => e.department === selectedDepartment.value);
    }
    return list;
});

// Helper functions
const getInitials = (name) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'On-Time': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'Late': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'Absent': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700';
    }
};
const getShiftBadgeClass = (shift) => {
    switch (shift) {
        case 'Morning': return 'bg-amber-100 text-amber-700';
        case 'Afternoon': return 'bg-blue-100 text-blue-700';
        case 'Graveyard': return 'bg-indigo-100 text-indigo-700';
        default: return 'bg-slate-100 text-slate-600';
    }
};
</script>

<template>

    <Head title="Workforce Dashboard" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Workforce <span class="text-blue-600">Dashboard</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Real-time employee attendance and shift overview.
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Employees</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <Users class="w-5 h-5 text-blue-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Present Today</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.present }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <UserCheck class="w-5 h-5 text-emerald-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Absent Today</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.absent }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <UserX class="w-5 h-5 text-red-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">On Leave</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.on_leave }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <Calendar class="w-5 h-5 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search by name, role, department..."
                        class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600 text-sm" />
                </div>
                <div class="flex gap-3">
                    <select v-model="selectedModule"
                        class="px-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm font-medium">
                        <option value="ALL">All Modules</option>
                        <option v-for="mod in modules.slice(1)" :key="mod" :value="mod">{{ mod }}</option>
                    </select>
                    <select v-model="selectedDepartment"
                        class="px-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 text-sm font-medium">
                        <option value="ALL">All Departments</option>
                        <option v-for="dept in departments.slice(1)" :key="dept" :value="dept">{{ dept }}</option>
                    </select>
                </div>
            </div>

            <!-- Employees Table -->
            <div
                class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Employee</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Role</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Department</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Shift</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="filteredEmployees.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No employees match the current filters.
                                </td>
                            </tr>
                            <tr v-for="emp in filteredEmployees" :key="emp.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-600">
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ emp.name }}
                                            </p>
                                            <p class="text-xs text-slate-400">{{ emp.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-700 dark:text-slate-300">{{ emp.role
                                    }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 rounded-md text-[10px] font-black uppercase bg-slate-100 text-slate-600">
                                        {{ emp.department }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['px-2 py-1 rounded-md text-[10px] font-black uppercase', getShiftBadgeClass(emp.shift)]">
                                        {{ emp.shift }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase', getStatusBadgeClass(emp.status)]">
                                        {{ emp.status }}
                                    </span>
                                    <span v-if="emp.is_on_leave"
                                        class="ml-2 px-2 py-1 rounded-full text-[9px] font-black uppercase bg-purple-100 text-purple-700">
                                        On Leave
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Optional custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>