<template>
    <Head title="Load Packages" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Package class="h-3.5 w-3.5" />
                        Logistics Operations
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Load <span class="text-indigo-600">Packages</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Receive finished goods from Manufacturing and prepare for dispatch.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Packages</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ packages.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Selected</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ selectedIds.length }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2.5rem] p-7 text-white shadow-lg">
                    <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest">Ready to Dispatch</p>
                    <p class="text-3xl font-black">{{ selectedIds.length }}</p>
                    <p class="text-xs opacity-80 mt-1">Packages will be moved to Dispatch</p>
                </div>
            </div>

            <!-- Packages Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by package number or product..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-3 ml-4">
                        <button @click="selectAllPackages" 
                            class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase hover:bg-gray-50 transition">
                            Select All
                        </button>
                        <button @click="clearSelection" 
                            v-if="selectedIds.length > 0"
                            class="px-4 py-2.5 rounded-xl border border-red-200 text-red-600 text-[10px] font-black uppercase hover:bg-red-50 transition">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5 w-12">
                                    <input type="checkbox" @change="toggleSelectAll" v-model="selectAllFlag" class="rounded border-gray-300">
                                </th>
                                <th class="px-8 py-5">Package #</th>
                                <th class="px-8 py-5">Product</th>
                                <th class="px-8 py-5 text-center">Quantity</th>
                                <th class="px-8 py-5">From Order</th>
                                <th class="px-8 py-5 text-center">Created At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="pkg in filteredPackages" :key="pkg.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <input type="checkbox" v-model="selectedIds" :value="pkg.id" class="rounded border-gray-300">
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                            <Package class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ pkg.package_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ pkg.product?.name || '—' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ pkg.product?.sku }}</p>
                                </td>
                                <td class="px-8 py-6 text-center font-bold">{{ pkg.quantity }} pcs</td>
                                <td class="px-8 py-6 text-sm text-gray-500">
                                    {{ pkg.manufacturing_order?.purchase_order?.po_number || '—' }}
                                </td>
                                <td class="px-8 py-6 text-center text-sm text-gray-500">
                                    {{ formatDate(pkg.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="filteredPackages.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Package class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No pending packages from Manufacturing.
                                    <br>
                                    <span class="text-xs">Finished goods will appear here automatically.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Footer -->
                <div v-if="filteredPackages.length > 0" class="px-8 py-5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        <span class="font-bold">{{ selectedIds.length }}</span> of <span class="font-bold">{{ filteredPackages.length }}</span> packages selected
                    </div>
                    <button @click="passToDispatch" 
                        :disabled="selectedIds.length === 0 || processing"
                        class="px-8 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        {{ processing ? 'Processing...' : 'Pass to Dispatch' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                {{ toast.message }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Package, RefreshCw, Search, Send, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    packages: {
        type: Array,
        default: () => []
    }
});

const selectedIds = ref([]);
const selectAllFlag = ref(false);
const searchTerm = ref('');
const processing = ref(false);
const toast = ref({ show: false, type: 'success', message: '' });

const filteredPackages = computed(() => {
    if (!searchTerm.value) return props.packages;
    const term = searchTerm.value.toLowerCase();
    return props.packages.filter(pkg => 
        pkg.package_number.toLowerCase().includes(term) ||
        pkg.product?.name?.toLowerCase().includes(term) ||
        pkg.product?.sku?.toLowerCase().includes(term)
    );
});

// Select All logic
const toggleSelectAll = () => {
    if (selectAllFlag.value) {
        selectedIds.value = filteredPackages.value.map(p => p.id);
    } else {
        selectedIds.value = [];
    }
};

const selectAllPackages = () => {
    selectedIds.value = filteredPackages.value.map(p => p.id);
    selectAllFlag.value = true;
};

const clearSelection = () => {
    selectedIds.value = [];
    selectAllFlag.value = false;
};

// Watch filtered packages to update selectAllFlag
watch([filteredPackages, selectedIds], () => {
    if (filteredPackages.value.length === 0) {
        selectAllFlag.value = false;
        return;
    }
    const allSelected = filteredPackages.value.every(p => selectedIds.value.includes(p.id));
    selectAllFlag.value = allSelected;
});

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['packages'] });
};

const passToDispatch = async () => {
    if (selectedIds.value.length === 0) return;
    processing.value = true;
    try {
        await router.post(route('logistics.load.pass'), { package_ids: selectedIds.value });
        showToast('success', `${selectedIds.value.length} package(s) passed to dispatch.`);
        selectedIds.value = [];
        selectAllFlag.value = false;
        refreshData();
    } catch (error) {
        showToast('error', 'Failed to pass packages. Please try again.');
    } finally {
        processing.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>