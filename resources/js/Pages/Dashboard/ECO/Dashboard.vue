<template>
    <Head title="ECO Dashboard" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <ShoppingBag class="h-3.5 w-3.5" />
                        E‑Commerce Control Center
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        ECO <span class="text-indigo-600">Dashboard</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Real‑time overview of client inquiries, quotations, and sales orders.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <Calendar class="w-4 h-4" />
                    <span>{{ formattedDate }}</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="group bg-white dark:bg-gray-900 rounded-[2.5rem] p-7 shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">New Inquiries</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.inquiries }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl group-hover:bg-blue-100 transition">
                            <MessageSquare class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                        <TrendingUp class="w-3 h-3" /> last 30 days
                    </div>
                </div>

                <div class="group bg-white dark:bg-gray-900 rounded-[2.5rem] p-7 shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Quotations Sent</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.quotations }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl group-hover:bg-indigo-100 transition">
                            <FileText class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-indigo-600 dark:text-indigo-400">this month</div>
                </div>

                <div class="group bg-white dark:bg-gray-900 rounded-[2.5rem] p-7 shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sales Orders</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.salesOrders }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl group-hover:bg-emerald-100 transition">
                            <ShoppingBag class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-emerald-600 dark:text-emerald-400">converted from quotations</div>
                </div>
            </div>

            <!-- Charts Row: Monthly Trend (simple bar chart) -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2 mb-6">
                    <BarChart3 class="w-5 h-5 text-indigo-500" />
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-widest">Monthly Performance</h3>
                </div>
                <div class="h-64 flex items-end gap-4">
                    <div v-for="(item, idx) in monthlyData" :key="idx" class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-indigo-500 rounded-t-lg transition-all duration-500"
                            :style="{ height: `${(item.value / maxValue) * 100}%`, minHeight: '4px' }">
                        </div>
                        <span class="text-[10px] sm:text-xs mt-3 text-gray-500 font-bold">{{ item.month }}</span>
                        <span class="text-[9px] font-black text-gray-600 dark:text-gray-400 mt-1">{{ item.value }}</span>
                    </div>
                </div>
                <div class="flex justify-center gap-6 mt-6 text-xs font-bold text-gray-500">
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-indigo-500"></span> Inquiries</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-indigo-300"></span> Quotations</div>
                </div>
            </div>

            <!-- Recent Activity Feed (placeholder) -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2 mb-6">
                    <Activity class="w-5 h-5 text-gray-500" />
                    <h3 class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-widest">Recent Activity</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start gap-3 text-sm border-b border-gray-100 dark:border-gray-800 pb-3">
                        <div class="p-1.5 bg-blue-50 rounded-full">
                            <MessageSquare class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">New inquiry from <span class="font-bold">QuantumLeap Inc</span></p>
                            <p class="text-xs text-gray-400">2 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 text-sm border-b border-gray-100 dark:border-gray-800 pb-3">
                        <div class="p-1.5 bg-indigo-50 rounded-full">
                            <FileText class="w-4 h-4 text-indigo-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">Quotation <span class="font-mono">QT-2026-0042</span> issued to <span class="font-bold">SilverLine Software</span></p>
                            <p class="text-xs text-gray-400">5 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 text-sm">
                        <div class="p-1.5 bg-emerald-50 rounded-full">
                            <ShoppingBag class="w-4 h-4 text-emerald-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">Sales order <span class="font-mono">PO-2026-0103</span> pushed to SCM</p>
                            <p class="text-xs text-gray-400">Yesterday</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ShoppingBag,
    MessageSquare,
    FileText,
    Calendar,
    TrendingUp,
    BarChart3,
    Activity
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            inquiries: 0,
            quotations: 0,
            salesOrders: 0
        })
    }
});

// Dummy monthly data – in real implementation, this would come from the controller
// For demonstration, we create a placeholder. You can replace with real data from backend.
const monthlyData = [
    { month: 'Jan', value: 12 },
    { month: 'Feb', value: 19 },
    { month: 'Mar', value: 15 },
    { month: 'Apr', value: 22 },
    { month: 'May', value: 28 },
    { month: 'Jun', value: 24 }
];

const maxValue = computed(() => Math.max(...monthlyData.map(d => d.value), 1));

const formattedDate = computed(() => {
    return new Date().toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
});
</script>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>