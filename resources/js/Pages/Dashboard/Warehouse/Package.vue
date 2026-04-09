<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Truck,
    CheckCircle,
    Clock,
    AlertCircle,
    Search,
    ChevronDown,
    Filter,
} from 'lucide-vue-next';

const props = defineProps({
    packages: {
        type: Array,
        default: () => [],
    },
    auth: Object,
});

// State
const searchQuery = ref('');
const statusFilter = ref('all');
const processing = ref(false);
const pushingId = ref(null);

// Filtered packages
const filteredPackages = computed(() => {
    let items = [...props.packages];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(pkg =>
            pkg.package_number.toLowerCase().includes(q) ||
            pkg.product?.name?.toLowerCase().includes(q) ||
            pkg.product?.sku?.toLowerCase().includes(q)
        );
    }
    if (statusFilter.value !== 'all') {
        items = items.filter(pkg => pkg.status === statusFilter.value);
    }
    return items;
});

// Push to logistics
const pushToLogistics = (pkg) => {
    if (!confirm(`Push package ${pkg.package_number} to Logistics?`)) return;
    pushingId.value = pkg.id;
    router.post(route('warehouse.packages.push', pkg.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            pushingId.value = null;
        },
    });
};

// Status badge style
const statusBadge = (status) => {
    const styles = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        pushed_to_logistics: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        delivered: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return styles[status] || 'bg-gray-100 text-gray-700';
};

const statusIcon = (status) => {
    if (status === 'pending') return Clock;
    if (status === 'pushed_to_logistics') return Truck;
    return CheckCircle;
};

// Format date
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString();
};
</script>

<template>
    <Head title="Warehouse Packages | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Packages</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Finished goods ready for dispatch to logistics.</p>
                    </div>
                    <div class="flex gap-2">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search packages..."
                                class="pl-9 pr-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 w-64"
                            />
                        </div>
                        <div class="relative">
                            <select
                                v-model="statusFilter"
                                class="appearance-none pl-3 pr-8 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="pushed_to_logistics">Pushed to Logistics</option>
                                <option value="delivered">Delivered</option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Packages Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Package #</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Product</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Quantity</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Manufacturing Order</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Created At</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredPackages.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold text-slate-500">No packages found.</p>
                                    </td>
                                </tr>
                                <tr v-for="pkg in filteredPackages" :key="pkg.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4">
                                        <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">{{ pkg.package_number }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ pkg.product?.name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ pkg.product?.sku }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center font-black text-slate-700 dark:text-slate-300">{{ pkg.quantity.toLocaleString() }} pcs</td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ pkg.manufacturing_order?.id || '—' }}</td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-xs">{{ formatDate(pkg.created_at) }}</td>
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full', statusBadge(pkg.status)]">
                                            <component :is="statusIcon(pkg.status)" class="w-3 h-3" />
                                            {{ pkg.status.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            v-if="pkg.status === 'pending'"
                                            @click="pushToLogistics(pkg)"
                                            :disabled="processing && pushingId === pkg.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm disabled:opacity-50"
                                        >
                                            <Truck class="w-3.5 h-3.5" />
                                            {{ pushingId === pkg.id ? 'Pushing...' : 'Push to Logistics' }}
                                        </button>
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer stats -->
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <span>Total: {{ filteredPackages.length }} packages</span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Pending: {{ props.packages.filter(p => p.status === 'pending').length }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>