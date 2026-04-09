<template>
    <Head title="Drivers Management" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Users class="h-3.5 w-3.5" />
                        Personnel Registry
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Drivers & <span class="text-indigo-600">Conductors</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Manage driver profiles, licenses, and performance ratings.
                    </p>
                </div>
                <button @click="openCreateModal" class="px-6 py-3 rounded-2xl bg-indigo-600 text-white text-[11px] font-black uppercase tracking-widest hover:bg-indigo-700 transition flex items-center gap-2">
                    <Plus class="h-4 w-4" /> Add Driver
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Drivers</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ drivers.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Available</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ availableCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Average Rating</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ avgRating }} ★</p>
                </div>
            </div>

            <!-- Drivers Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by name, license, or email..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="availabilityFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All</option>
                            <option value="available">Available</option>
                            <option value="unavailable">On Trip</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Driver</th>
                                <th class="px-8 py-5">License #</th>
                                <th class="px-8 py-5">Contact</th>
                                <th class="px-8 py-5 text-center">Rating</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Total Trips</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="driver in filteredDrivers" :key="driver.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-black">
                                            {{ driver.user?.name?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <p class="font-black text-gray-900 dark:text-white">{{ driver.user?.name }}</p>
                                            <p class="text-[10px] text-gray-400">{{ driver.user?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 font-mono text-sm">{{ driver.license_number }}</td>
                                <td class="px-8 py-6 text-sm">{{ driver.user?.phone || '—' }}</td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                        <span class="font-bold">{{ driver.rating }}</span>
                                        <span class="text-[10px] text-gray-400">/5</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="driver.is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ driver.is_available ? 'Available' : 'On Trip' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center font-bold">{{ driver.deliveries_count || 0 }}</td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="viewDetails(driver)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <Eye class="h-4 w-4 text-gray-500" />
                                        </button>
                                    </div>
                                </td>
                             </tr>
                            <tr v-if="filteredDrivers.length === 0">
                                <td colspan="7" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Users class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No drivers found. Click "Add Driver" to register.
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Driver Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeCreateModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Register New Driver</h3>
                        <button @click="closeCreateModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>

                    <form @submit.prevent="submitDriver" class="p-6 space-y-5">
                        <!-- Personal Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Full Name *</label>
                                <input v-model="driverForm.name" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Email *</label>
                                <input v-model="driverForm.email" type="email" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Phone *</label>
                                <input v-model="driverForm.phone" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">License Number *</label>
                                <input v-model="driverForm.license_number" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">License Image</label>
                            <input type="file" @change="handleLicenseUpload" accept="image/*" class="text-sm">
                            <p class="text-[9px] text-gray-400 mt-1">Upload scanned license (JPG, PNG)</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Medical Certificate</label>
                            <input type="file" @change="handleMedicalUpload" accept="image/*,application/pdf" class="text-sm">
                        </div>

                        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl border border-amber-200 dark:border-amber-800">
                            <p class="text-xs font-medium text-amber-800 dark:text-amber-200 flex items-start gap-2">
                                <Info class="h-4 w-4 mt-0.5 flex-shrink-0" />
                                The driver will receive login credentials via email. Default password is "password123" – please instruct them to change upon first login.
                            </p>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="closeCreateModal"
                                class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="driverForm.processing"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-indigo-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <Loader2 v-if="driverForm.processing" class="h-4 w-4 animate-spin" />
                                Register Driver
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Driver Details Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeDetailModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Driver Details</h3>
                        <button @click="closeDetailModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>

                    <div v-if="selectedDriver" class="p-6 space-y-6">
                        <!-- Profile Header -->
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                            <div class="h-16 w-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-black">
                                {{ selectedDriver.user?.name?.charAt(0) }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-black">{{ selectedDriver.user?.name }}</h2>
                                <p class="text-gray-500">{{ selectedDriver.user?.email }} | {{ selectedDriver.user?.phone }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="selectedDriver.is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        class="px-2 py-0.5 rounded text-[9px] font-black uppercase">
                                        {{ selectedDriver.is_available ? 'Available' : 'On Trip' }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                                        <span class="font-bold">{{ selectedDriver.rating }} / 5</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- License & Medical -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-wider">License Information</p>
                                <p class="font-mono font-bold mt-1">{{ selectedDriver.license_number }}</p>
                                <a v-if="selectedDriver.license_image" :href="'/storage/' + selectedDriver.license_image" target="_blank"
                                    class="text-xs text-indigo-600 hover:underline mt-2 inline-block">View License Image →</a>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-wider">Medical Certificate</p>
                                <a v-if="selectedDriver.medical_certificate" :href="'/storage/' + selectedDriver.medical_certificate" target="_blank"
                                    class="text-xs text-indigo-600 hover:underline mt-2 inline-block">View Certificate →</a>
                                <p v-else class="text-sm text-gray-400 mt-1">Not uploaded</p>
                            </div>
                        </div>

                        <!-- Delivery History -->
                        <div>
                            <h4 class="font-black text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <Truck class="h-4 w-4 text-indigo-500" /> Delivery History
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-[9px] font-black uppercase text-gray-400">
                                        <tr><th class="p-3">Delivery #</th><th>Date</th><th>Route</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="delivery in selectedDriver.deliveries" :key="delivery.id" class="border-t border-gray-100">
                                            <td class="p-3 font-mono">{{ delivery.delivery_number }}</td>
                                            <td class="p-3">{{ formatDate(delivery.scheduled_departure) }}</td>
                                            <td class="p-3">{{ delivery.route?.name || '—' }}</td>
                                            <td class="p-3">
                                                <span :class="statusBadge(delivery.status)" class="px-2 py-0.5 rounded text-[9px] font-black">
                                                    {{ formatStatus(delivery.status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!selectedDriver.deliveries?.length">
                                            <td colspan="4" class="p-6 text-center text-gray-400">No deliveries yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Users, Plus, Search, Eye, X, Loader2, Star, Truck, Info } from 'lucide-vue-next';

const props = defineProps({
    drivers: {
        type: Array,
        default: () => []
    }
});

// Filters
const searchTerm = ref('');
const availabilityFilter = ref('all');

// Modals
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedDriver = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

// Driver form
const driverForm = useForm({
    name: '',
    email: '',
    phone: '',
    license_number: '',
    license_image: null,
    medical_certificate: null
});

const filteredDrivers = computed(() => {
    let list = props.drivers;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(d => 
            d.user?.name?.toLowerCase().includes(term) ||
            d.license_number?.toLowerCase().includes(term) ||
            d.user?.email?.toLowerCase().includes(term)
        );
    }
    if (availabilityFilter.value === 'available') {
        list = list.filter(d => d.is_available === true);
    } else if (availabilityFilter.value === 'unavailable') {
        list = list.filter(d => d.is_available === false);
    }
    return list;
});

const availableCount = computed(() => props.drivers.filter(d => d.is_available).length);
const avgRating = computed(() => {
    if (!props.drivers.length) return 0;
    const sum = props.drivers.reduce((acc, d) => acc + (d.rating || 0), 0);
    return (sum / props.drivers.length).toFixed(1);
});

const statusBadge = (status) => {
    const map = {
        pending: 'bg-gray-100 text-gray-700',
        dispatched: 'bg-amber-100 text-amber-700',
        in_transit: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = { pending: 'Pending', dispatched: 'Dispatched', in_transit: 'In Transit', delivered: 'Delivered' };
    return map[status] || status;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH');
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['drivers'] });
};

const handleLicenseUpload = (e) => {
    driverForm.license_image = e.target.files[0];
};
const handleMedicalUpload = (e) => {
    driverForm.medical_certificate = e.target.files[0];
};

const openCreateModal = () => {
    driverForm.reset();
    showCreateModal.value = true;
};
const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitDriver = () => {
    driverForm.post(route('logistics.drivers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Driver registered successfully.');
            closeCreateModal();
            refreshData();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Registration failed.';
            showToast('error', errorMsg);
        }
    });
};

const viewDetails = async (driver) => {
    // Fetch full details including deliveries
    try {
        const response = await fetch(route('logistics.drivers.show', driver.id));
        const data = await response.json();
        selectedDriver.value = data.driver;
        showDetailModal.value = true;
    } catch (error) {
        showToast('error', 'Failed to load driver details.');
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedDriver.value = null;
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