<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Boxes,
    Warehouse,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    CheckCircle,
    ArrowRight,
    Package,
    RefreshCw,
    BarChart2,
    Activity,
    Clock,
    ChevronRight,
    DollarSign,
    LineChart,
    ShoppingCart,
} from 'lucide-vue-next';

const props = defineProps({
    auth: Object,
    kpis: Object,
    warehouses: Array,
    materials: Array,        // List of materials with stock per warehouse
    alertItems: Array,
    recentActivity: Array,
    categoryBreakdown: Array,
    valueTrend: Object,
});

const user = computed(() => props.auth?.user);
const isSupervisor = computed(() => {
    const pos = user.value?.position;
    return pos === 'supervisor' || pos === 'manager';
});

// For supervisors: selected warehouse
const selectedWarehouseId = ref(null);
const warehouseOptions = computed(() => {
    if (!isSupervisor.value) return [];
    // User's assigned warehouses (provided from backend)
    return props.warehouses.filter(w => w.assigned_to_user === true);
});

// Filter materials based on selected warehouse (for supervisor)
const filteredMaterials = computed(() => {
    if (!isSupervisor.value || !selectedWarehouseId.value) {
        // CEO/Secretary sees overall stock (sum across all warehouses)
        return props.materials.map(m => ({
            ...m,
            total_stock: Object.values(m.stock_per_warehouse || {}).reduce((a, b) => a + b, 0),
        }));
    }
    return props.materials.map(m => ({
        ...m,
        total_stock: m.stock_per_warehouse?.[selectedWarehouseId.value] || 0,
    }));
});

const isLoaded = ref(false);
onMounted(() => setTimeout(() => (isLoaded.value = true), 50));

// Helper to get status color
const statusColor = (status) => {
    if (status === 'In Stock') return 'text-emerald-600 bg-emerald-50';
    if (status === 'Low Stock') return 'text-amber-600 bg-amber-50';
    return 'text-red-600 bg-red-50';
};

// Request procurement for a material
const requestProcurement = (materialId) => {
    if (confirm(`Request procurement for ${materialId}? This will create a material request in SCM.`)) {
        router.post(route('inv.checker.procurement', materialId), {}, {
            preserveScroll: true,
        });
    }
};

// Format currency
const formatCurrency = (value) => {
    return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Inventory Dashboard | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Inventory Dashboard</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Real‑time stock overview and material tracking.</p>
                    </div>
                    <Link
                        :href="route('inv.checker')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm"
                    >
                        <ShoppingCart class="w-4 h-4" />
                        Go to Checker
                    </Link>
                </div>

                <!-- Supervisor Warehouse Toggle -->
                <div v-if="isSupervisor && warehouseOptions.length > 0" class="mb-6">
                    <label class="text-xs font-bold text-slate-500 block mb-1">Select Warehouse</label>
                    <select
                        v-model="selectedWarehouseId"
                        class="px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option :value="null">All Warehouses (Overall)</option>
                        <option v-for="wh in warehouseOptions" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-slate-500 text-xs font-bold mb-2">
                            <Boxes class="w-4 h-4" /> Total SKUs
                        </div>
                        <p class="text-2xl font-black">{{ kpis.totalSkus || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-emerald-600 text-xs font-bold mb-2">
                            <CheckCircle class="w-4 h-4" /> In Stock
                        </div>
                        <p class="text-2xl font-black text-emerald-600">{{ kpis.inStock || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-amber-600 text-xs font-bold mb-2">
                            <AlertTriangle class="w-4 h-4" /> Low Stock
                        </div>
                        <p class="text-2xl font-black text-amber-600">{{ kpis.lowStock || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-red-500 text-xs font-bold mb-2">
                            <TrendingDown class="w-4 h-4" /> Out of Stock
                        </div>
                        <p class="text-2xl font-black text-red-500">{{ kpis.outOfStock || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-slate-500 text-xs font-bold mb-2">
                            <Warehouse class="w-4 h-4" /> Warehouses
                        </div>
                        <p class="text-2xl font-black">{{ kpis.totalWarehouses || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-2 text-slate-500 text-xs font-bold mb-2">
                            <DollarSign class="w-4 h-4" /> Total Value
                        </div>
                        <p class="text-2xl font-black">{{ formatCurrency(kpis.totalValue || 0) }}</p>
                    </div>
                </div>

                <!-- Materials Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden mb-8">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">Materials Stock</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">Material ID</th>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">Name</th>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">Category</th>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">Stock</th>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">Status</th>
                                    <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredMaterials.length === 0">
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-400">No materials found.</td>
                                </tr>
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-5 py-3 font-mono text-xs">{{ mat.mat_id }}</td>
                                    <td class="px-5 py-3 font-semibold">{{ mat.name }}</td>
                                    <td class="px-5 py-3">{{ mat.category }}</td>
                                    <td class="px-5 py-3 font-bold">{{ mat.total_stock }} {{ mat.unit }}</td>
                                    <td class="px-5 py-3">
                                        <span :class="['inline-flex px-2 py-0.5 rounded-full text-[10px] font-black', statusColor(mat.status)]">
                                            {{ mat.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <button
                                            v-if="mat.status !== 'In Stock'"
                                            @click="requestProcurement(mat.id)"
                                            class="px-3 py-1 text-[10px] font-black rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                                        >
                                            Request Procurement
                                        </button>
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Charts Row (Category & Value Trend) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
                    <!-- Category Breakdown -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <BarChart2 class="w-4 h-4 text-slate-500" />
                            <h2 class="text-sm font-black uppercase tracking-wider">Materials by Category</h2>
                        </div>
                        <div v-if="!categoryBreakdown?.length" class="text-center py-8 text-slate-400">No data</div>
                        <div v-else class="space-y-3">
                            <div class="flex h-2 rounded-full overflow-hidden">
                                <div v-for="cat in categoryBreakdown" :key="cat.name" :class="cat.color" :style="{ width: cat.pct + '%' }"></div>
                            </div>
                            <div v-for="cat in categoryBreakdown" :key="cat.name" class="flex justify-between text-sm">
                                <span class="font-medium">{{ cat.name }}</span>
                                <span class="text-slate-500">{{ cat.count }} SKUs ({{ cat.pct }}%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Value Trend -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <LineChart class="w-4 h-4 text-slate-500" />
                            <h2 class="text-sm font-black uppercase tracking-wider">Inventory Value Trend (₱M)</h2>
                        </div>
                        <div v-if="!valueTrend?.values?.length" class="text-center py-8 text-slate-400">No data</div>
                        <div v-else class="flex items-end gap-2 h-40">
                            <div v-for="(val, idx) in valueTrend.values" :key="idx" class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-blue-500 rounded-t" :style="{ height: (val / Math.max(...valueTrend.values) * 100) + '%', minHeight: '4px' }"></div>
                                <span class="text-[10px] mt-1">{{ valueTrend.months[idx] }}</span>
                                <span class="text-[9px] font-bold">{{ val.toFixed(1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Alerts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Recent Activity -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                            <Activity class="w-4 h-4 text-slate-500" />
                            <h2 class="text-sm font-black uppercase tracking-wider">Recent Activity</h2>
                        </div>
                        <div v-if="!recentActivity?.length" class="p-8 text-center text-slate-400">No recent activity</div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800 max-h-72 overflow-y-auto">
                            <div v-for="act in recentActivity" :key="act.time" class="px-5 py-3 flex items-start gap-3">
                                <div :class="['w-2 h-2 rounded-full mt-1.5', act.color === 'red' ? 'bg-red-500' : act.color === 'amber' ? 'bg-amber-500' : 'bg-emerald-500']"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold">{{ act.action }}</p>
                                    <p class="text-xs text-slate-500">{{ act.item }} · {{ act.qty }} · {{ act.warehouse }}</p>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ act.time }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Alerts -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                            <AlertTriangle class="w-4 h-4 text-amber-500" />
                            <h2 class="text-sm font-black uppercase tracking-wider">Stock Alerts</h2>
                        </div>
                        <div v-if="!alertItems?.length" class="p-8 text-center text-slate-400">All stocks are healthy.</div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800 max-h-72 overflow-y-auto">
                            <div v-for="alert in alertItems" :key="alert.sku" class="px-5 py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 dark:text-white">{{ alert.name }}</p>
                                    <p class="text-xs text-slate-500">{{ alert.sku }} · {{ alert.warehouse }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-red-500">{{ alert.qty }} units</p>
                                    <p class="text-[10px] text-slate-400">Reorder: {{ alert.reorder }}</p>
                                </div>
                                <span :class="['text-[9px] font-black px-2 py-1 rounded-full', alert.type === 'out' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600']">
                                    {{ alert.type === 'out' ? 'Out' : 'Low' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>