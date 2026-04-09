<template>
    <Head title="Suppliers - ECO Module" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Users class="h-3.5 w-3.5" />
                        Supplier Management
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Suppliers <span class="text-indigo-600">Directory</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Manage communication with all active suppliers.
                    </p>
                </div>
                <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Suppliers</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ suppliers.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Active Conversations</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ activeConversations }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Requests</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ pendingRequests }}</p>
                </div>
            </div>

            <!-- Suppliers Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by business name, rep, or email..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Business Name</th>
                                <th class="px-8 py-5">Representative</th>
                                <th class="px-8 py-5">Contact</th>
                                <th class="px-8 py-5">Status</th>
                                <th class="px-8 py-5 text-center">Last Message</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="supplier in filteredSuppliers" :key="supplier.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <Building2 class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ supplier.business_name }}</p>
                                            <p class="text-[10px] font-bold text-gray-400">{{ supplier.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ supplier.representative_name }}</p>
                                        <p class="text-[10px] text-gray-400">{{ supplier.phone_number }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="space-y-1">
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ supplier.address }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-100 text-green-700">
                                        {{ supplier.status || 'Active' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center text-xs text-gray-500">
                                    {{ formatLastMessage(supplier.latest_message) }}
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <Link :href="route('eco.supplier.conversation', supplier.id)"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">
                                        Open Conversation
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredSuppliers.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No suppliers found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Users, RefreshCw, Search, Building2, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => []
    }
});

const searchTerm = ref('');

const filteredSuppliers = computed(() => {
    if (!searchTerm.value) return props.suppliers;
    const term = searchTerm.value.toLowerCase();
    return props.suppliers.filter(s =>
        s.business_name.toLowerCase().includes(term) ||
        s.representative_name.toLowerCase().includes(term) ||
        s.email.toLowerCase().includes(term)
    );
});

const activeConversations = computed(() => {
    // Simplified: count suppliers with at least one message
    return props.suppliers.filter(s => s.latest_message).length;
});

const pendingRequests = computed(() => {
    // Placeholder – would need actual data from backend
    return 0;
});

const formatLastMessage = (message) => {
    if (!message) return '—';
    return new Date(message.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
};

const refreshData = () => {
    router.reload({ only: ['suppliers'] });
};
</script>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>