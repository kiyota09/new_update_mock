<template>
    <Head title="Dispatch Center" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Send class="h-3.5 w-3.5" />
                        Fleet Control
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Dispatch <span class="text-indigo-600">Center</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Assign trucks, drivers, and routes to pending deliveries.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pending Dispatch</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ deliveries.length }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2.5rem] p-7 text-white shadow-lg">
                    <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest">Available Resources</p>
                    <div class="flex justify-between mt-2">
                        <div>
                            <p class="text-2xl font-black">{{ trucks.length }}</p>
                            <p class="text-[9px] opacity-80">Trucks</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black">{{ drivers.length }}</p>
                            <p class="text-[9px] opacity-80">Drivers</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black">{{ conductors.length }}</p>
                            <p class="text-[9px] opacity-80">Conductors</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by delivery number..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Delivery #</th>
                                <th class="px-8 py-5">Packages</th>
                                <th class="px-8 py-5">Products</th>
                                <th class="px-8 py-5 text-center">Total Qty</th>
                                <th class="px-8 py-5 text-center">Created</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="delivery in filteredDeliveries" :key="delivery.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                            <Truck class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ delivery.delivery_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="space-y-1">
                                        <div v-for="pkg in delivery.packages" :key="pkg.id" class="text-xs">
                                            {{ pkg.package_number }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="space-y-1">
                                        <div v-for="pkg in delivery.packages" :key="pkg.id" class="text-xs font-medium">
                                            {{ pkg.product?.name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center font-bold">
                                    {{ delivery.packages.reduce((sum, p) => sum + (p.quantity || 0), 0) }} pcs
                                </td>
                                <td class="px-8 py-6 text-center text-sm text-gray-500">
                                    {{ formatDate(delivery.created_at) }}
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="openDispatchModal(delivery)"
                                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition">
                                        Assign & Dispatch
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredDeliveries.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Send class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No pending deliveries.
                                    <br>
                                    <span class="text-xs">Load packages first to create deliveries.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dispatch Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Dispatch Delivery</h3>
                        <button @click="closeModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>

                    <form @submit.prevent="submitDispatch" class="p-6 space-y-6">
                        <!-- Delivery Info -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-wider">Delivery #</p>
                            <p class="font-mono font-bold text-lg">{{ selectedDelivery?.delivery_number }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ selectedDelivery?.packages?.length }} package(s) · {{ totalQuantity }} pcs total</p>
                        </div>

                        <!-- Truck Selection -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Assign Truck *</label>
                            <select v-model="form.truck_id" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select a truck</option>
                                <option v-for="truck in trucks" :key="truck.id" :value="truck.id">
                                    {{ truck.truck_number }} - {{ truck.model }} ({{ truck.plate_number }})
                                </option>
                            </select>
                        </div>

                        <!-- Driver Selection -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Assign Driver *</label>
                            <select v-model="form.driver_id" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select a driver</option>
                                <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                    {{ driver.user?.name }} (License: {{ driver.license_number }}) - Rating: {{ driver.rating }}★
                                </option>
                            </select>
                        </div>

                        <!-- Conductor 1 -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Conductor 1 (Optional)</label>
                            <select v-model="form.conductor1_id"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">None</option>
                                <option v-for="conductor in conductors" :key="conductor.id" :value="conductor.id">
                                    {{ conductor.user?.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Conductor 2 -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Conductor 2 (Optional)</label>
                            <select v-model="form.conductor2_id"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">None</option>
                                <option v-for="conductor in conductors" :key="conductor.id" :value="conductor.id">
                                    {{ conductor.user?.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Route Selection -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Route *</label>
                            <select v-model="form.route_id" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select a route</option>
                                <option v-for="route in routes" :key="route.id" :value="route.id">
                                    {{ route.name }} ({{ route.distance_km }}km, ~{{ route.estimated_minutes }}min)
                                </option>
                            </select>
                        </div>

                        <!-- Scheduled Departure -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Scheduled Departure *</label>
                            <input type="datetime-local" v-model="form.scheduled_departure" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Notes (Optional)</label>
                            <textarea v-model="form.notes" rows="2"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="Special instructions for driver/conductor..."></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="closeModal"
                                class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 px-4 py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-emerald-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                {{ form.processing ? 'Dispatching...' : 'Dispatch Now' }}
                            </button>
                        </div>
                    </form>
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
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Send, RefreshCw, Search, Truck, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    deliveries: {
        type: Array,
        default: () => []
    },
    trucks: {
        type: Array,
        default: () => []
    },
    drivers: {
        type: Array,
        default: () => []
    },
    conductors: {
        type: Array,
        default: () => []
    },
    routes: {
        type: Array,
        default: () => []
    }
});

const searchTerm = ref('');
const showModal = ref(false);
const selectedDelivery = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

const form = useForm({
    truck_id: '',
    driver_id: '',
    conductor1_id: '',
    conductor2_id: '',
    route_id: '',
    scheduled_departure: '',
    notes: ''
});

const filteredDeliveries = computed(() => {
    if (!searchTerm.value) return props.deliveries;
    const term = searchTerm.value.toLowerCase();
    return props.deliveries.filter(d => d.delivery_number.toLowerCase().includes(term));
});

const totalQuantity = computed(() => {
    if (!selectedDelivery.value) return 0;
    return selectedDelivery.value.packages.reduce((sum, p) => sum + (p.quantity || 0), 0);
});

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['deliveries', 'trucks', 'drivers', 'conductors', 'routes'] });
};

const openDispatchModal = (delivery) => {
    selectedDelivery.value = delivery;
    form.reset();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedDelivery.value = null;
    form.reset();
};

const submitDispatch = () => {
    if (!selectedDelivery.value) return;
    form.post(route('logistics.dispatch.assign', selectedDelivery.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', `Delivery ${selectedDelivery.value.delivery_number} dispatched successfully.`);
            closeModal();
            refreshData();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Failed to dispatch. Please check all fields.';
            showToast('error', errorMsg);
        }
    });
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