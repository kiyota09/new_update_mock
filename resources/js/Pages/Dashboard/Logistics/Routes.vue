<template>
    <Head title="Route Management" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <MapPin class="h-3.5 w-3.5" />
                        Navigation
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Delivery <span class="text-indigo-600">Routes</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Define and manage delivery routes, distances, and estimated travel times.
                    </p>
                </div>
                <button @click="openCreateModal" class="px-6 py-3 rounded-2xl bg-indigo-600 text-white text-[11px] font-black uppercase tracking-widest hover:bg-indigo-700 transition flex items-center gap-2">
                    <Plus class="h-4 w-4" /> Add Route
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Routes</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ routes.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Avg Distance</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1">{{ avgDistance }} km</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Avg Duration</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ avgDuration }} min</p>
                </div>
            </div>

            <!-- Routes Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by name, origin, or destination..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Route Name</th>
                                <th class="px-8 py-5">Origin</th>
                                <th class="px-8 py-5">Destination</th>
                                <th class="px-8 py-5 text-right">Distance (km)</th>
                                <th class="px-8 py-5 text-right">Est. Time (min)</th>
                                <th class="px-8 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="route in filteredRoutes" :key="route.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                            <Navigation class="h-5 w-5" />
                                        </div>
                                        <span class="font-black text-gray-900 dark:text-white">{{ route.name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-1">
                                        <MapPin class="h-3 w-3 text-gray-400" />
                                        <span class="text-sm">{{ route.origin }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-1">
                                        <Flag class="h-3 w-3 text-gray-400" />
                                        <span class="text-sm">{{ route.destination }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right font-bold">{{ route.distance_km?.toLocaleString() || 0 }} km</td>
                                <td class="px-8 py-6 text-right">{{ route.estimated_minutes }} min</td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal(route)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <Edit class="h-4 w-4 text-gray-500" />
                                        </button>
                                        <button @click="confirmDelete(route)" class="p-2 rounded-lg hover:bg-red-50 transition">
                                            <Trash2 class="h-4 w-4 text-red-500" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRoutes.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <Navigation class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No routes found. Click "Add Route" to create your first delivery route.
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
                        <h3 class="font-black text-lg">{{ isEditing ? 'Edit Route' : 'Add New Route' }}</h3>
                        <button @click="closeModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Route Name *</label>
                            <input v-model="form.name" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., Manila to Cavite">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Origin *</label>
                            <input v-model="form.origin" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="Starting point">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Destination *</label>
                            <input v-model="form.destination" type="text" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                                placeholder="Delivery point">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Distance (km) *</label>
                                <input v-model.number="form.distance_km" type="number" step="0.01" min="0" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Est. Time (min) *</label>
                                <input v-model.number="form.estimated_minutes" type="number" min="1" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Waypoints (Optional)</label>
                            <textarea v-model="form.waypoints_text" rows="3"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 font-mono"
                                placeholder="One per line:&#10;Quezon City&#10;Pasay&#10;Paranaque"></textarea>
                            <p class="text-[9px] text-gray-400 mt-1">List intermediate stops (one per line) for GPS navigation</p>
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
                        <h3 class="font-black text-lg">Delete Route</h3>
                        <button @click="showDeleteModal = false" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-gray-700 dark:text-gray-300">Are you sure you want to delete <span class="font-bold">{{ routeToDelete?.name }}</span>?</p>
                        <p class="text-xs text-red-500">This action cannot be undone. Active deliveries using this route will be affected.</p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition">Cancel</button>
                            <button @click="deleteRoute" :disabled="deleting" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-red-700 transition disabled:opacity-50">
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
import { MapPin, Navigation, Plus, Search, Edit, Trash2, X, Loader2, Flag } from 'lucide-vue-next';

const props = defineProps({
    routes: {
        type: Array,
        default: () => []
    }
});

// Filter
const searchTerm = ref('');

// Modal states
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const showDeleteModal = ref(false);
const routeToDelete = ref(null);
const deleting = ref(false);
const toast = ref({ show: false, type: 'success', message: '' });

const form = useForm({
    name: '',
    origin: '',
    destination: '',
    distance_km: 0,
    estimated_minutes: 0,
    waypoints_text: ''
});

const filteredRoutes = computed(() => {
    if (!searchTerm.value) return props.routes;
    const term = searchTerm.value.toLowerCase();
    return props.routes.filter(r => 
        r.name.toLowerCase().includes(term) ||
        r.origin.toLowerCase().includes(term) ||
        r.destination.toLowerCase().includes(term)
    );
});

const avgDistance = computed(() => {
    if (!props.routes.length) return 0;
    const sum = props.routes.reduce((acc, r) => acc + (r.distance_km || 0), 0);
    return (sum / props.routes.length).toFixed(1);
});

const avgDuration = computed(() => {
    if (!props.routes.length) return 0;
    const sum = props.routes.reduce((acc, r) => acc + (r.estimated_minutes || 0), 0);
    return Math.round(sum / props.routes.length);
});

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['routes'] });
};

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (route) => {
    isEditing.value = true;
    editingId.value = route.id;
    form.name = route.name;
    form.origin = route.origin;
    form.destination = route.destination;
    form.distance_km = route.distance_km;
    form.estimated_minutes = route.estimated_minutes;
    form.waypoints_text = route.waypoints ? route.waypoints.join('\n') : '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    // Convert waypoints text to array
    const waypoints = form.waypoints_text
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0);

    const payload = {
        name: form.name,
        origin: form.origin,
        destination: form.destination,
        distance_km: form.distance_km,
        estimated_minutes: form.estimated_minutes,
        waypoints: waypoints.length ? waypoints : null
    };

    if (isEditing.value) {
        router.put(route('logistics.routes.update', editingId.value), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', 'Route updated successfully.');
                closeModal();
                refreshData();
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors)[0] || 'Update failed.';
                showToast('error', errorMsg);
            }
        });
    } else {
        router.post(route('logistics.routes.store'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', 'Route created successfully.');
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

const confirmDelete = (route) => {
    routeToDelete.value = route;
    showDeleteModal.value = true;
};

const deleteRoute = () => {
    deleting.value = true;
    router.delete(route('logistics.routes.destroy', routeToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', `Route "${routeToDelete.value.name}" deleted.`);
            showDeleteModal.value = false;
            routeToDelete.value = null;
            refreshData();
        },
        onError: (errors) => {
            showToast('error', 'Failed to delete route. It may be in use.');
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