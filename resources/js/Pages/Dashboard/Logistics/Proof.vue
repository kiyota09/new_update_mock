<template>
    <Head title="Proof of Delivery" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Camera class="h-3.5 w-3.5" />
                        Delivery Confirmation
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Proof of <span class="text-indigo-600">Delivery</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        View all completed deliveries with photo evidence and driver notes.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Delivered</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ deliveries.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">This Month</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ thisMonthCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Unique Drivers</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ uniqueDriversCount }}</p>
                </div>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by delivery #, driver, or client..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="dateFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Delivery #</th>
                                <th class="px-8 py-5">Driver</th>
                                <th class="px-8 py-5">Truck</th>
                                <th class="px-8 py-5">Delivered At</th>
                                <th class="px-8 py-5">Proof Image</th>
                                <th class="px-8 py-5">Notes</th>
                                <th class="px-8 py-5 text-center">Packages</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="delivery in filteredDeliveries" :key="delivery.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                            <CheckCircle class="h-5 w-5" />
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
                                <td class="px-8 py-6 text-sm">
                                    {{ formatDateTime(delivery.proof_of_delivery?.delivered_at || delivery.arrival_time) }}
                                </td>
                                <td class="px-8 py-6">
                                    <button @click="openImageViewer(delivery.proof_of_delivery?.image_path)" 
                                        v-if="delivery.proof_of_delivery?.image_path"
                                        class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-lg text-[10px] font-black uppercase hover:bg-indigo-100 transition flex items-center gap-1">
                                        <Image class="h-3 w-3" /> View Photo
                                    </button>
                                    <span v-else class="text-gray-400 text-xs italic">No proof</span>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" :title="delivery.proof_of_delivery?.notes">
                                        {{ delivery.proof_of_delivery?.notes || '—' }}
                                    </p>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-[10px] font-bold">
                                        {{ delivery.packages?.length || 0 }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredDeliveries.length === 0">
                                <td colspan="7" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Camera class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No delivered orders with proof yet.
                                    <br>
                                    <span class="text-xs">Drivers will upload photos upon delivery.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <Teleport to="body">
            <div v-if="showImageViewer" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="closeImageViewer">
                <div class="relative max-w-4xl max-h-[90vh] bg-white dark:bg-gray-900 rounded-2xl overflow-hidden shadow-2xl">
                    <button @click="closeImageViewer" class="absolute top-4 right-4 p-2 bg-black/50 rounded-full text-white hover:bg-black/70 transition z-10">
                        <X class="h-5 w-5" />
                    </button>
                    <img :src="imageViewUrl" class="max-w-full max-h-[85vh] object-contain" alt="Proof of Delivery" />
                    <div class="p-3 text-center text-xs text-gray-500 border-t border-gray-100 dark:border-gray-800">
                        Proof of Delivery - Captured by driver upon drop-off
                    </div>
                </div>
            </div>
        </Teleport>

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
import { ref, computed } from 'vue';
import { Camera, RefreshCw, Search, CheckCircle, Image, X } from 'lucide-vue-next';

const props = defineProps({
    deliveries: {
        type: Array,
        default: () => []
    }
});

// Filters
const searchTerm = ref('');
const dateFilter = ref('all');

// Image viewer
const showImageViewer = ref(false);
const imageViewUrl = ref('');

// Toast
const toast = ref({ show: false, type: 'success', message: '' });

const filteredDeliveries = computed(() => {
    let list = props.deliveries;
    
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(d => 
            d.delivery_number?.toLowerCase().includes(term) ||
            d.driver?.user?.name?.toLowerCase().includes(term)
        );
    }
    
    if (dateFilter.value !== 'all') {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 7);
        const monthAgo = new Date(today);
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        
        list = list.filter(d => {
            const deliveredAt = new Date(d.proof_of_delivery?.delivered_at || d.arrival_time);
            if (dateFilter.value === 'today') {
                return deliveredAt >= today;
            } else if (dateFilter.value === 'week') {
                return deliveredAt >= weekAgo;
            } else if (dateFilter.value === 'month') {
                return deliveredAt >= monthAgo;
            }
            return true;
        });
    }
    
    return list;
});

const thisMonthCount = computed(() => {
    const now = new Date();
    const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
    return props.deliveries.filter(d => {
        const deliveredAt = new Date(d.proof_of_delivery?.delivered_at || d.arrival_time);
        return deliveredAt >= monthAgo;
    }).length;
});

const uniqueDriversCount = computed(() => {
    const driverIds = new Set();
    props.deliveries.forEach(d => {
        if (d.driver?.id) driverIds.add(d.driver.id);
    });
    return driverIds.size;
});

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['deliveries'] });
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const openImageViewer = (path) => {
    if (!path) return;
    imageViewUrl.value = '/storage/' + path;
    showImageViewer.value = true;
};

const closeImageViewer = () => {
    showImageViewer.value = false;
    imageViewUrl.value = '';
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