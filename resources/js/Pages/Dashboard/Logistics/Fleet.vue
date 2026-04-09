<template>
    <Head title="Fleet Management" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Truck class="h-3.5 w-3.5" />
                        Asset Registry
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Fleet <span class="text-indigo-600">Management</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Manage trucks, track status, and maintain vehicle records.
                    </p>
                </div>
                <button @click="openCreateModal" class="px-6 py-3 rounded-2xl bg-indigo-600 text-white text-[11px] font-black uppercase tracking-widest hover:bg-indigo-700 transition flex items-center gap-2">
                    <Plus class="h-4 w-4" /> Add Truck
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Trucks</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ trucks.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Available</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ availableCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">In Use</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ inUseCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Maintenance</p>
                    <p class="text-3xl font-black text-red-600 mt-1">{{ maintenanceCount }}</p>
                </div>
            </div>

            <!-- Trucks Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by number or plate..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="statusFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Status</option>
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Truck #</th>
                                <th class="px-8 py-5">Plate #</th>
                                <th class="px-8 py-5">Model</th>
                                <th class="px-8 py-5">Year</th>
                                <th class="px-8 py-5 text-right">Mileage (km)</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="truck in filteredTrucks" :key="truck.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                            <Truck class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ truck.truck_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 font-mono text-sm">{{ truck.plate_number }}</td>
                                <td class="px-8 py-6">{{ truck.model }}</td>
                                <td class="px-8 py-6">{{ truck.year }}</td>
                                <td class="px-8 py-6 text-right">{{ truck.mileage?.toLocaleString() || 0 }}</td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="statusBadge(truck.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ formatStatus(truck.status) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal(truck)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <Edit class="h-4 w-4 text-gray-500" />
                                        </button>
                                        <button @click="confirmDelete(truck)" class="p-2 rounded-lg hover:bg-red-50 transition">
                                            <Trash2 class="h-4 w-4 text-red-500" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredTrucks.length === 0">
                                <td colspan="7" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Truck class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No trucks found. Click "Add Truck" to register your first vehicle.
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">{{ isEditing ? 'Edit Truck' : 'Add New Truck' }}</h3>
                        <button @click="closeModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Truck Number *</label>
                            <input v-model="form.truck_number" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., TRK-001">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Plate Number *</label>
                            <input v-model="form.plate_number" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., ABC-1234">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Model *</label>
                            <input v-model="form.model" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., Isuzu Elf">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Year *</label>
                            <input v-model.number="form.year" type="number" required min="1990" :max="new Date().getFullYear()"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Mileage (km)</label>
                            <input v-model.number="form.mileage" type="number" step="0.01" min="0"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Status</label>
                            <select v-model="form.status"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="under_maintenance">Under Maintenance</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Remarks</label>
                            <textarea v-model="form.remarks" rows="2"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="Additional notes..."></textarea>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="closeModal"
                                class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-indigo-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showDeleteModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-red-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Delete Truck</h3>
                        <button @click="showDeleteModal = false" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-gray-700 dark:text-gray-300">Are you sure you want to delete <span class="font-bold">{{ truckToDelete?.truck_number }}</span>?</p>
                        <p class="text-xs text-red-500">This action cannot be undone.</p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition">Cancel</button>
                            <button @click="deleteTruck" :disabled="deleting" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-red-700 transition disabled:opacity-50">
                                <Loader2 v-if="deleting" class="h-4 w-4 animate-spin inline mr-1" />
                                Delete
                            </button>
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
import { Truck, Plus, Search, Edit, Trash2, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    trucks: {
        type: Array,
        default: () => []
    }
});

// Filters
const searchTerm = ref('');
const statusFilter = ref('all');

// Modal states
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const showDeleteModal = ref(false);
const truckToDelete = ref(null);
const deleting = ref(false);
const toast = ref({ show: false, type: 'success', message: '' });

const form = useForm({
    truck_number: '',
    plate_number: '',
    model: '',
    year: new Date().getFullYear(),
    mileage: 0,
    status: 'available',
    remarks: ''
});

const filteredTrucks = computed(() => {
    let list = props.trucks;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(t => 
            t.truck_number.toLowerCase().includes(term) || 
            t.plate_number.toLowerCase().includes(term) ||
            t.model.toLowerCase().includes(term)
        );
    }
    if (statusFilter.value !== 'all') {
        list = list.filter(t => t.status === statusFilter.value);
    }
    return list;
});

const availableCount = computed(() => props.trucks.filter(t => t.status === 'available').length);
const inUseCount = computed(() => props.trucks.filter(t => t.status === 'in_use').length);
const maintenanceCount = computed(() => props.trucks.filter(t => t.status === 'under_maintenance').length);

const statusBadge = (status) => {
    const map = {
        available: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        in_use: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        under_maintenance: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        retired: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = {
        available: 'Available',
        in_use: 'In Use',
        under_maintenance: 'Under Maintenance',
        retired: 'Retired'
    };
    return map[status] || status;
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['trucks'] });
};

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.year = new Date().getFullYear();
    form.status = 'available';
    showModal.value = true;
};

const openEditModal = (truck) => {
    isEditing.value = true;
    editingId.value = truck.id;
    form.truck_number = truck.truck_number;
    form.plate_number = truck.plate_number;
    form.model = truck.model;
    form.year = truck.year;
    form.mileage = truck.mileage || 0;
    form.status = truck.status;
    form.remarks = truck.remarks || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('logistics.fleet.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', 'Truck updated successfully.');
                closeModal();
                refreshData();
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors)[0] || 'Update failed.';
                showToast('error', errorMsg);
            }
        });
    } else {
        form.post(route('logistics.fleet.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', 'Truck added successfully.');
                closeModal();
                refreshData();
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors)[0] || 'Creation failed.';
                showToast('error', errorMsg);
            }
        });
    }
};

const confirmDelete = (truck) => {
    truckToDelete.value = truck;
    showDeleteModal.value = true;
};

const deleteTruck = () => {
    deleting.value = true;
    router.delete(route('logistics.fleet.destroy', truckToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', `Truck ${truckToDelete.value.truck_number} deleted.`);
            showDeleteModal.value = false;
            truckToDelete.value = null;
            refreshData();
        },
        onError: (errors) => {
            showToast('error', 'Failed to delete truck.');
        },
        onFinish: () => {
            deleting.value = false;
        }
    });
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