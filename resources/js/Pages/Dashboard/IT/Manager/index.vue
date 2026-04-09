<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    MonitorSmartphone,
    Server,
    Box,
    Search,
    Filter,
    Plus,
    MoreHorizontal,
    QrCode,
    Laptop,
    ShieldAlert,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Download
} from 'lucide-vue-next';

// Mock KPI Data
const stats = ref([
    { title: 'Total IT Assets', value: '1,248', icon: MonitorSmartphone, color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
    { title: 'Currently Assigned', value: '984', icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { title: 'Available / Spare', value: '215', icon: Box, color: 'text-indigo-600', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
    { title: 'Maintenance / Expiring', value: '49', icon: AlertTriangle, color: 'text-amber-600', bg: 'bg-amber-50 dark:bg-amber-900/20' },
]);

// Mock Asset Data
const assets = ref([
    { id: 'AST-10042', name: 'MacBook Pro 16" M2', category: 'Hardware', type: 'Laptop', assigned_to: 'Sarah Jenkins', department: 'Design', location: 'HQ - 3rd Floor', warranty_end: '2026-11-15', status: 'In Use' },
    { id: 'AST-10043', name: 'Dell PowerEdge R740', category: 'Hardware', type: 'Server', assigned_to: 'IT Infrastructure', department: 'IT', location: 'Server Room A', warranty_end: '2025-08-20', status: 'In Use' },
    { id: 'AST-10044', name: 'Zebra DS2208 Scanner', category: 'Hardware', type: 'Scanner', assigned_to: 'Logistics Team', department: 'Warehouse', location: 'Warehouse 1 - Dock B', warranty_end: '2024-12-01', status: 'Maintenance' },
    { id: 'AST-10045', name: 'Adobe Creative Cloud', category: 'Software', type: 'License', assigned_to: 'Marketing Dept', department: 'Marketing', location: 'Cloud / Remote', warranty_end: '2025-01-30', status: 'Expiring Soon' },
    { id: 'AST-10046', name: 'Lenovo ThinkPad X1', category: 'Hardware', type: 'Laptop', assigned_to: 'Unassigned', department: '-', location: 'IT Storage Room', warranty_end: '2027-03-10', status: 'Available' },
    { id: 'AST-10047', name: 'Cisco Catalyst 9300', category: 'Hardware', type: 'Network', assigned_to: 'IT Infrastructure', department: 'IT', location: 'Server Room B', warranty_end: '2026-05-22', status: 'In Use' },
    { id: 'AST-10048', name: 'Microsoft Office 365', category: 'Software', type: 'License', assigned_to: 'Sales Team', department: 'Sales', location: 'Cloud / Remote', warranty_end: '2024-11-05', status: 'Expired' },
]);

const searchQuery = ref('');
const filterCategory = ref('All');

// Status color helper
const getStatusClass = (status) => {
    switch (status) {
        case 'In Use': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'Available': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'Maintenance': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'Expiring Soon': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        case 'Expired': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    }
};

// Type icon helper
const getTypeIcon = (type) => {
    switch (type) {
        case 'Laptop': return Laptop;
        case 'Server': return Server;
        case 'Scanner': return QrCode;
        case 'License': return ShieldAlert;
        default: return MonitorSmartphone;
    }
};
</script>

<template>

    <Head title="Asset Management | IT Department" />

    <AuthenticatedLayout>
        <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Asset <span class="text-blue-600">Management</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                    Track hardware, software licenses, warranties, and assignments.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <Download class="w-4 h-4" /> Export
                </button>
                <button
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 active:scale-95">
                    <Plus class="w-4 h-4" /> Add Asset
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div v-for="stat in stats" :key="stat.title"
                class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <div :class="['p-2.5 rounded-xl', stat.bg]">
                        <component :is="stat.icon" :class="['h-5 w-5', stat.color]" />
                    </div>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ stat.title }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</p>
            </div>
        </div>

        <div
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">

            <div
                class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-4 justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                <div class="relative w-full sm:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search by Asset ID, Name, or User..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 placeholder-slate-400 transition-all" />
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-auto">
                        <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <select v-model="filterCategory"
                            class="w-full sm:w-48 pl-9 pr-8 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 appearance-none font-medium">
                            <option value="All">All Categories</option>
                            <option value="Hardware">Hardware</option>
                            <option value="Software">Software</option>
                            <option value="Network">Network</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700">
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                Asset Details</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                Assigned To</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                Location</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                Warranty / Expiry</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                Status</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr v-for="asset in assets" :key="asset.id"
                            class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors group">

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 border border-slate-200 dark:border-slate-600">
                                        <component :is="getTypeIcon(asset.type)"
                                            class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ asset.name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-wider font-mono">{{
                                                asset.id }}</span>
                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                            <span
                                                class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">{{
                                                asset.type }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <p
                                    :class="['text-sm font-bold', asset.assigned_to === 'Unassigned' ? 'text-slate-400 italic' : 'text-slate-700 dark:text-slate-200']">
                                    {{ asset.assigned_to }}
                                </p>
                                <p v-if="asset.department !== '-'"
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                                    {{ asset.department }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                <p
                                    class="text-sm font-medium text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    {{ asset.location }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <Clock class="w-3.5 h-3.5 text-slate-400" />
                                    <span
                                        :class="['text-sm font-medium font-mono', new Date(asset.warranty_end) < new Date() ? 'text-red-500 font-bold' : 'text-slate-600 dark:text-slate-300']">
                                        {{ asset.warranty_end }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    :class="['inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', getStatusClass(asset.status)]">
                                    {{ asset.status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <button
                                    class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                    <MoreHorizontal class="w-5 h-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="assets.length === 0" class="py-12 flex flex-col items-center justify-center text-slate-400">
                    <MonitorSmartphone class="w-12 h-12 mb-3 opacity-20" />
                    <p class="text-sm font-bold">No assets found matching your criteria.</p>
                </div>
            </div>

            <div
                class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Showing <span
                        class="font-bold text-slate-700 dark:text-slate-300">1</span> to <span
                        class="font-bold text-slate-700 dark:text-slate-300">7</span> of <span
                        class="font-bold text-slate-700 dark:text-slate-300">1,248</span> assets</span>
                <div class="flex gap-2">
                    <button
                        class="px-3 py-1.5 text-xs font-bold text-slate-500 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-50"
                        disabled>Previous</button>
                    <button
                        class="px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">Next</button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Optional horizontal scrollbar styling for the table */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 20px;
}
</style>