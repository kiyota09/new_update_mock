<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    AlertTriangle,
    Package,
    XCircle,
    Truck,
    RefreshCw,
    Search,
    ChevronDown,
    Calendar,
    User,
    CheckCircle, 
    Trash2,
} from 'lucide-vue-next';

const props = defineProps({
    rejects: {
        type: Array,
        default: () => [],
    },
    auth: Object,
});

// State
const searchQuery = ref('');
const sourceFilter = ref('all');
const processing = ref(false);
const returningId = ref(null);

// Filtered rejects
const filteredRejects = computed(() => {
    let items = [...props.rejects];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(r =>
            (r.material_name || r.product_name || '').toLowerCase().includes(q) ||
            r.reason.toLowerCase().includes(q)
        );
    }
    if (sourceFilter.value !== 'all') {
        items = items.filter(r => r.source === sourceFilter.value);
    }
    return items;
});

// Mark as returned (update status)
const markReturned = (reject) => {
    if (!confirm(`Mark this reject as returned?`)) return;
    returningId.value = reject.id;
    router.post(route('warehouse.rejects.return', reject.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            returningId.value = null;
        },
    });
};

// Status badge style
const statusBadge = (status) => {
    const styles = {
        pending_return: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        returned: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return styles[status] || 'bg-gray-100 text-gray-700';
};

// Source badge style
const sourceBadge = (source) => {
    return source === 'receiving'
        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
        : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
};

// Format date
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString();
};
</script>

<template>
    <Head title="Rejected Items | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Rejected Items</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Items rejected during receiving or manufacturing.</p>
                    </div>
                    <div class="flex gap-2">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by material or reason..."
                                class="pl-9 pr-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 w-64"
                            />
                        </div>
                        <div class="relative">
                            <select
                                v-model="sourceFilter"
                                class="appearance-none pl-3 pr-8 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="all">All Sources</option>
                                <option value="receiving">Receiving</option>
                                <option value="manufacturing">Manufacturing</option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Rejects Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Source</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Item</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Quantity</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Reason</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Date</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredRejects.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <XCircle class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold text-slate-500">No rejected items found.</p>
                                    </td>
                                </tr>
                                <tr v-for="rej in filteredRejects" :key="rej.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full', sourceBadge(rej.source)]">
                                            <Truck v-if="rej.source === 'receiving'" class="w-3 h-3" />
                                            <Package v-else class="w-3 h-3" />
                                            {{ rej.source === 'receiving' ? 'Receiving' : 'Manufacturing' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ rej.material_name || rej.product_name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ rej.unit }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center font-black text-red-600 dark:text-red-400">{{ Number(rej.quantity).toLocaleString() }}</td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-400 max-w-xs">{{ rej.reason }}</td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-xs">{{ formatDate(rej.created_at) }}</td>
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full', statusBadge(rej.status)]">
                                            <RefreshCw v-if="rej.status === 'pending_return'" class="w-3 h-3" />
                                            <CheckCircle v-else class="w-3 h-3" />
                                            {{ rej.status === 'pending_return' ? 'Pending Return' : 'Returned' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            v-if="rej.status === 'pending_return'"
                                            @click="markReturned(rej)"
                                            :disabled="processing && returningId === rej.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-50"
                                        >
                                            <RefreshCw class="w-3.5 h-3.5" />
                                            {{ returningId === rej.id ? 'Processing...' : 'Mark Returned' }}
                                        </button>
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer stats -->
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <span>Total: {{ filteredRejects.length }} rejects</span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Pending return: {{ props.rejects.filter(r => r.status === 'pending_return').length }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>