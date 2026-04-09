<template>
    <Head title="Logistics Dashboard" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Truck class="h-3.5 w-3.5" />
                        Fleet Operations
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Logistics <span class="text-indigo-600">Dashboard</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Monitor shipments, fleet status, and delivery performance.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <RefreshCw class="h-4 w-4 text-gray-500" />
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <Package class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Pending</span>
                    </div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.pendingPackages }}</p>
                    <p class="text-xs text-gray-500 mt-1">Packages waiting to be loaded</p>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <Send class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Dispatched</span>
                    </div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.dispatchedDeliveries }}</p>
                    <p class="text-xs text-gray-500 mt-1">On the way to departure</p>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <Navigation class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">In Transit</span>
                    </div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.inTransitDeliveries }}</p>
                    <p class="text-xs text-gray-500 mt-1">Currently on the road</p>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <Truck class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Available Trucks</span>
                    </div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.availableTrucks }}</p>
                    <p class="text-xs text-gray-500 mt-1">Ready for dispatch</p>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Available Drivers</span>
                    </div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.availableDrivers }}</p>
                    <p class="text-xs text-gray-500 mt-1">Ready to drive</p>
                </div>
            </div>

            <!-- Recent Deliveries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <Clock class="h-5 w-5 text-indigo-500" />
                        Recent Deliveries
                    </h2>
                    <Link :href="route('logistics.dispatch.index')" class="text-xs font-bold text-indigo-600 hover:underline">
                        View All
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Delivery #</th>
                                <th class="px-8 py-5">Driver</th>
                                <th class="px-8 py-5">Truck</th>
                                <th class="px-8 py-5">Route</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Departure</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="delivery in recentDeliveries" :key="delivery.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                            <Truck class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ delivery.delivery_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[10px] font-black">
                                            {{ delivery.driver?.user?.name?.charAt(0) || '?' }}
                                        </div>
                                        <span class="text-sm font-medium">{{ delivery.driver?.user?.name || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm">{{ delivery.truck?.truck_number || '—' }}</td>
                                <td class="px-8 py-6 text-sm">{{ delivery.route?.name || '—' }}</td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="statusBadge(delivery.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ formatStatus(delivery.status) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center text-sm text-gray-500">
                                    {{ formatDateTime(delivery.scheduled_departure) }}
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <Link :href="route('logistics.dispatch.index')" class="inline-flex items-center gap-1 text-indigo-600 text-xs font-bold hover:underline">
                                        Track <ArrowRight class="h-3 w-3" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentDeliveries.length === 0">
                                <td colspan="7" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No recent deliveries. Start by loading packages from the manufacturing module.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Link :href="route('logistics.load.index')" class="bg-gradient-to-r from-indigo-500 to-indigo-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest opacity-80">Step 1</p>
                            <p class="text-xl font-black mt-1">Load Packages</p>
                            <p class="text-sm opacity-90 mt-2">Receive finished goods from Manufacturing</p>
                        </div>
                        <Package class="h-12 w-12 opacity-80 group-hover:scale-110 transition-transform" />
                    </div>
                </Link>

                <Link :href="route('logistics.dispatch.index')" class="bg-gradient-to-r from-amber-500 to-amber-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest opacity-80">Step 2</p>
                            <p class="text-xl font-black mt-1">Dispatch</p>
                            <p class="text-sm opacity-90 mt-2">Assign drivers, trucks, and routes</p>
                        </div>
                        <Send class="h-12 w-12 opacity-80 group-hover:scale-110 transition-transform" />
                    </div>
                </Link>

                <Link :href="route('logistics.fleet.index')" class="bg-gradient-to-r from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest opacity-80">Fleet</p>
                            <p class="text-xl font-black mt-1">Manage Fleet</p>
                            <p class="text-sm opacity-90 mt-2">Trucks, drivers, and conductors</p>
                        </div>
                        <Users class="h-12 w-12 opacity-80 group-hover:scale-110 transition-transform" />
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Truck, Package, Send, Navigation, Users, RefreshCw, Clock, ArrowRight } from 'lucide-vue-next';

defineProps({
    stats: {
        type: Object,
        default: () => ({
            pendingPackages: 0,
            dispatchedDeliveries: 0,
            inTransitDeliveries: 0,
            availableTrucks: 0,
            availableDrivers: 0,
        })
    },
    recentDeliveries: {
        type: Array,
        default: () => []
    }
});

const refreshData = () => {
    router.reload({ only: ['stats', 'recentDeliveries'] });
};

const statusBadge = (status) => {
    const map = {
        pending: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        dispatched: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        in_transit: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = {
        pending: 'Pending',
        dispatched: 'Dispatched',
        in_transit: 'In Transit',
        delivered: 'Delivered',
        cancelled: 'Cancelled',
    };
    return map[status] || status;
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>