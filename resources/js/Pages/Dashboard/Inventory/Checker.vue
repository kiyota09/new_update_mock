<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    ShoppingCart,
    AlertTriangle,
    AlertCircle,
    CheckCircle,
    RefreshCw,
    Search,
} from 'lucide-vue-next';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    pendingOrdersCount: {
        type: Number,
        default: 0,
    },
    auth: Object,
});

// UI state
const searchQuery = ref('');
const processingMaterial = ref(null);
const checkingOrders = ref(false);

// Filter materials
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) ||
        mat.mat_id.toLowerCase().includes(q)
    );
});

// Request procurement for a specific material
const requestProcurement = (materialId) => {
    if (!confirm('Send procurement request for this material to SCM?')) return;
    processingMaterial.value = materialId;
    router.post(route('inv.checker.procurement', materialId), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingMaterial.value = null;
        },
    });
};

// Trigger order check (e.g., for all pending orders or a specific one)
const checkOrders = () => {
    if (!confirm('Check material sufficiency for all pending orders? This will update order statuses and create material requests if needed.')) return;
    checkingOrders.value = true;
    router.post(route('inv.checker.orders'), {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingOrders.value = false;
        },
    });
};

// Status badge helper
const statusBadge = (status) => {
    switch (status) {
        case 'ok': return { class: 'bg-emerald-100 text-emerald-700', label: 'In Stock' };
        case 'low': return { class: 'bg-amber-100 text-amber-700', label: 'Low Stock' };
        case 'out': return { class: 'bg-red-100 text-red-600', label: 'Out of Stock' };
        default: return { class: 'bg-gray-100 text-gray-700', label: status };
    }
};
</script>

<template>
    <Head title="Stock Checker | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Stock Checker</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Monitor material stock levels and trigger procurement or order checks.</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="checkOrders"
                            :disabled="checkingOrders"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-sm disabled:opacity-50"
                        >
                            <RefreshCw :class="['w-4 h-4', checkingOrders && 'animate-spin']" />
                            Check Pending Orders ({{ pendingOrdersCount }})
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search materials..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>
                </div>

                <!-- Materials Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Material ID</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Name</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Category</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-right">Total Stock</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Unit</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Reorder Point</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3.5 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredMaterials.length === 0">
                                    <td colspan="8" class="px-5 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold">No materials found.</p>
                                    </td>
                                </tr>
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4 font-mono text-xs font-bold">{{ mat.mat_id }}</td>
                                    <td class="px-5 py-4 font-semibold">{{ mat.name }}</td>
                                    <td class="px-5 py-4">{{ mat.category }}</td>
                                    <td class="px-5 py-4 text-right font-black">{{ mat.total_stock.toLocaleString() }}</td>
                                    <td class="px-5 py-4">{{ mat.unit }}</td>
                                    <td class="px-5 py-4">{{ mat.reorder_point.toLocaleString() }}</td>
                                    <td class="px-5 py-4">
                                        <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-black', statusBadge(mat.status).class]">
                                            <AlertTriangle v-if="mat.status === 'low'" class="w-3 h-3" />
                                            <CheckCircle v-else-if="mat.status === 'ok'" class="w-3 h-3" />
                                            <AlertCircle v-else class="w-3 h-3" />
                                            {{ statusBadge(mat.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            v-if="mat.status !== 'ok'"
                                            @click="requestProcurement(mat.id)"
                                            :disabled="processingMaterial === mat.id"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-[10px] font-black rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition disabled:opacity-50"
                                        >
                                            <ShoppingCart class="w-3.5 h-3.5" />
                                            {{ processingMaterial === mat.id ? 'Sending...' : 'Request Procurement' }}
                                        </button>
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400 flex justify-between">
                        <span>Total: {{ filteredMaterials.length }} materials</span>
                        <span>⚠️ Low/Out: {{ filteredMaterials.filter(m => m.status !== 'ok').length }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>