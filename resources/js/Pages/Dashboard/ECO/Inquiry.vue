<template>
    <Head title="Inquiries - ECO Module" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <MessageSquare class="h-3.5 w-3.5" />
                        Client Engagement
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Client <span class="text-indigo-600">Inquiries</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Manage all product inquiries, conversations, and quotations.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <RefreshCw class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Open Inquiries</p>
                    <p class="text-3xl font-black text-blue-600 mt-1">{{ openCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Quotations Sent</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ quotationSentCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Converted to Orders</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ convertedCount }}</p>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by company or product..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="statusFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Status</option>
                            <option value="open">Open</option>
                            <option value="quotation_sent">Quotation Sent</option>
                            <option value="converted">Converted</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Client & Product</th>
                                <th class="px-8 py-5">Initial Message</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Last Activity</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="inquiry in filteredInquiries" :key="inquiry.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <Building2 class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ inquiry.client?.company_name }}</p>
                                            <p class="text-[10px] font-bold text-gray-400">{{ inquiry.product?.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ inquiry.initial_message || '—' }}</p>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="statusBadge(inquiry.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ inquiry.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center text-xs text-gray-500">
                                    {{ formatDate(inquiry.last_message_at) }}
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <Link :href="route('eco.inquiry.show', inquiry.id)"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-105 transition-all">
                                        Open Conversation
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredInquiries.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No inquiries found.
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
import { MessageSquare, RefreshCw, Search, Building2, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    inquiries: {
        type: Array,
        default: () => []
    }
});

const searchTerm = ref('');
const statusFilter = ref('all');

const filteredInquiries = computed(() => {
    let list = props.inquiries;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(i =>
            i.client?.company_name?.toLowerCase().includes(term) ||
            i.product?.name?.toLowerCase().includes(term)
        );
    }
    if (statusFilter.value !== 'all') {
        list = list.filter(i => i.status === statusFilter.value);
    }
    return list;
});

const openCount = computed(() => props.inquiries.filter(i => i.status === 'open').length);
const quotationSentCount = computed(() => props.inquiries.filter(i => i.status === 'quotation_sent').length);
const convertedCount = computed(() => props.inquiries.filter(i => i.status === 'converted').length);

const statusBadge = (status) => {
    const map = {
        open: 'bg-blue-100 text-blue-700',
        quotation_sent: 'bg-indigo-100 text-indigo-700',
        converted: 'bg-emerald-100 text-emerald-700',
        abandoned: 'bg-gray-100 text-gray-500'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const refreshData = () => {
    router.reload({ only: ['inquiries'] });
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>