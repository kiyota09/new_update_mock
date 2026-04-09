<template>
    <Head title="Client Dashboard" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F8F9FA] dark:bg-gray-950 pb-20">

            <!-- Hero Section -->
            <div class="relative bg-gradient-to-br from-slate-900 to-indigo-950 overflow-hidden text-white pt-10 pb-20 px-4 sm:px-6 lg:px-8 shadow-inner">
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-20 pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
                    <div class="absolute top-48 -left-24 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
                </div>

                <div class="max-w-7xl mx-auto relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-[10px] font-black tracking-widest uppercase text-indigo-200 mb-4">
                                <Layers class="w-3.5 h-3.5" /> Partner Portal
                            </div>
                            <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight">
                                Welcome back, <br />
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-300">{{ client?.company_name || 'Partner' }}</span>
                            </h1>
                            <p class="text-indigo-100/80 mt-2 font-medium max-w-lg">Track your orders, manage inquiries, and access quotations in one place.</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-3xl text-center shadow-2xl">
                            <p class="text-xs font-bold text-indigo-200 uppercase tracking-widest mb-1">Account Status</p>
                            <div class="flex items-center gap-2 justify-center text-emerald-400 font-bold">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                {{ client?.status || 'Active' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20 space-y-8">

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden transition-all hover:-translate-y-1">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
                            <ShoppingBag class="w-24 h-24 text-indigo-500" />
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/50 flex items-center justify-center mb-6">
                                <Package class="h-6 w-6 text-indigo-600" />
                            </div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Total Orders</p>
                            <h3 class="text-4xl font-black text-gray-900 dark:text-white">{{ stats.totalOrders }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden transition-all hover:-translate-y-1">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <MessageSquare class="w-24 h-24 text-cyan-500" />
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-900/50 flex items-center justify-center mb-6">
                                <MessageSquare class="h-6 w-6 text-cyan-600" />
                            </div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Active Inquiries</p>
                            <h3 class="text-4xl font-black text-gray-900 dark:text-white">{{ stats.activeInquiries }}</h3>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-[2rem] p-6 shadow-xl shadow-amber-500/20 relative overflow-hidden transition-all hover:-translate-y-1 text-white">
                        <div class="absolute top-0 right-0 p-6 opacity-20">
                            <FileText class="w-24 h-24" />
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center backdrop-blur-md mb-6">
                                <FileText class="h-6 w-6 text-white" />
                            </div>
                            <p class="text-xs font-black text-amber-100 uppercase tracking-widest mb-1">Pending Quotations</p>
                            <h3 class="text-4xl font-black leading-none">{{ stats.pendingQuotations }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Recent Orders Table -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <Clock class="h-5 w-5 text-indigo-500" /> Recent Orders
                            </h2>
                            <Link :href="route('client.orders')" class="text-xs font-bold text-indigo-600 hover:underline">View All</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                    <tr>
                                        <th class="pb-3">Order #</th>
                                        <th class="pb-3">Date</th>
                                        <th class="pb-3 text-right">Total</th>
                                        <th class="pb-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="order in recentOrders" :key="order.id" class="text-sm">
                                        <td class="py-3 font-mono font-bold">{{ order.po_number }}</td>
                                        <td class="py-3 text-gray-500">{{ formatDate(order.created_at) }}</td>
                                        <td class="py-3 text-right font-black">₱{{ formatCurrency(order.total_amount) }}</td>
                                        <td class="py-3 text-center">
                                            <span :class="order.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                class="px-2 py-0.5 rounded text-[9px] font-black uppercase">
                                                {{ order.status.replace('_', ' ') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentOrders.length === 0">
                                        <td colspan="4" class="py-8 text-center text-gray-400">No orders yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Actions Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                            <h3 class="text-sm font-black uppercase tracking-widest mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <Link :href="route('client.products')" class="flex items-center justify-between p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl hover:bg-indigo-100 transition">
                                    <span class="font-bold text-indigo-700">Browse Products</span>
                                    <ShoppingBag class="h-4 w-4 text-indigo-600" />
                                </Link>
                                <Link :href="route('client.conversations')" class="flex items-center justify-between p-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl hover:bg-cyan-100 transition">
                                    <span class="font-bold text-cyan-700">View Conversations</span>
                                    <MessageSquare class="h-4 w-4 text-cyan-600" />
                                </Link>
                                <Link :href="route('client.invoices')" class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl hover:bg-emerald-100 transition">
                                    <span class="font-bold text-emerald-700">Invoices & Payments</span>
                                    <CreditCard class="h-4 w-4 text-emerald-600" />
                                </Link>
                            </div>
                        </div>

                        <!-- Support Widget -->
                        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-[2rem] p-6 text-white shadow-lg">
                            <h3 class="text-sm font-black uppercase tracking-widest mb-2">Need Help?</h3>
                            <p class="text-xs text-gray-300 mb-4">Our support team is ready to assist you.</p>
                            <Link :href="route('client.support')" class="inline-flex items-center gap-2 text-xs font-bold bg-white/10 px-4 py-2 rounded-xl hover:bg-white/20 transition">
                                Contact Support <ArrowRight class="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Deliveries / Logistics (placeholder) -->
                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                        <Truck class="h-4 w-4 text-indigo-500" /> Upcoming Deliveries
                    </h3>
                    <div v-if="pendingDeliveries.length === 0" class="text-center py-8 text-gray-400">
                        No upcoming deliveries at the moment.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="delivery in pendingDeliveries" :key="delivery.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <div>
                                <p class="font-bold text-sm">{{ delivery.po_number }}</p>
                                <p class="text-xs text-gray-500">Expected: {{ formatDate(delivery.expected_date) }}</p>
                            </div>
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[9px] font-black">In Transit</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Layers, ShoppingBag, Package, MessageSquare, FileText, Clock, CreditCard, ArrowRight, Truck } from 'lucide-vue-next';

const page = usePage();
const client = computed(() => page.props.auth?.client);

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalOrders: 0,
            activeInquiries: 0,
            pendingQuotations: 0
        })
    },
    recentOrders: {
        type: Array,
        default: () => []
    },
    pendingDeliveries: {
        type: Array,
        default: () => []
    }
});

const formatCurrency = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
</script>

<style scoped>
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>