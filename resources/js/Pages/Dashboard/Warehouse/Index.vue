<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';  // <-- added Link for navigation
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Warehouse,
    Plus,
    Pencil,
    Trash2,
    X,
    MapPin,
    User,
    Building2,
    CheckCircle,
    AlertCircle,
    Search,
    ChevronDown,
    Eye,  // <-- added for monitor button
} from 'lucide-vue-next';

const props = defineProps({
    warehouses: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [], // List of users eligible to be warehouse supervisors
    },
    auth: Object,
});

const user = computed(() => props.auth?.user);
const canManage = computed(() => {
    const role = user.value?.role;
    const position = user.value?.position;
    return role === 'CEO' || position === 'secretary' || position === 'general_manager';
});

// UI state
const showAddModal = ref(false);
const showEditModal = ref(false);
const editingWarehouse = ref(null);
const searchQuery = ref('');
const processing = ref(false);

// Form data - only supervisor_id, manager removed
const form = ref({
    name: '',
    location: '',
    supervisor_id: '',
});

// Filtered warehouses based on search
const filteredWarehouses = computed(() => {
    if (!searchQuery.value) return props.warehouses;
    const q = searchQuery.value.toLowerCase();
    return props.warehouses.filter(wh =>
        wh.name.toLowerCase().includes(q) ||
        wh.location.toLowerCase().includes(q)
    );
});

// Reset form
const resetForm = () => {
    form.value = {
        name: '',
        location: '',
        supervisor_id: '',
    };
};

// Open add modal
const openAddModal = () => {
    resetForm();
    showAddModal.value = true;
};

// Open edit modal
const openEditModal = (warehouse) => {
    editingWarehouse.value = warehouse;
    form.value = {
        name: warehouse.name,
        location: warehouse.location,
        supervisor_id: warehouse.supervisor_id || '',
    };
    showEditModal.value = true;
};

// Submit add warehouse
const addWarehouse = () => {
    if (!form.value.name || !form.value.location) return;
    processing.value = true;
    router.post(route('warehouse.store'), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            resetForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Submit edit warehouse
const updateWarehouse = () => {
    if (!form.value.name || !form.value.location) return;
    processing.value = true;
    router.put(route('warehouse.update', editingWarehouse.value.id), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingWarehouse.value = null;
            resetForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Delete warehouse
const deleteWarehouse = (id) => {
    if (!confirm('Are you sure? This will remove the warehouse and all its data.')) return;
    router.delete(route('warehouse.destroy', id), {
        preserveScroll: true,
    });
};

// Helper to get user name by id (only for supervisor)
const getUserName = (id) => {
    const user = props.users.find(u => u.id === id);
    return user ? user.name : '—';
};

// Color palette for warehouse cards
const getColorClass = (color) => {
    const colors = {
        blue: 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800',
        emerald: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800',
        amber: 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800',
        violet: 'bg-violet-50 border-violet-200 dark:bg-violet-900/20 dark:border-violet-800',
        rose: 'bg-rose-50 border-rose-200 dark:bg-rose-900/20 dark:border-rose-800',
        cyan: 'bg-cyan-50 border-cyan-200 dark:bg-cyan-900/20 dark:border-cyan-800',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head title="Warehouse Management | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            Warehouse Management
                        </h1>
                        <p class="text-slate-500 text-sm mt-0.5">
                            Manage warehouse locations and supervisors.
                        </p>
                    </div>
                    <button
                        v-if="canManage"
                        @click="openAddModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm"
                    >
                        <Plus class="w-4 h-4" />
                        Add Warehouse
                    </button>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search warehouses..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200"
                        />
                    </div>
                </div>

                <!-- Warehouse Grid -->
                <div v-if="filteredWarehouses.length === 0" class="text-center py-16 text-slate-400">
                    <Warehouse class="w-12 h-12 mx-auto mb-3 opacity-30" />
                    <p class="font-bold text-slate-500">No warehouses found </p>
                    <p v-if="canManage" class="text-sm mt-1">Click "Add Warehouse" to get started.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="wh in filteredWarehouses"
                        :key="wh.id"
                        :class="['rounded-2xl border p-5 transition-all hover:shadow-md', getColorClass(wh.color)]"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div :class="['w-2 h-2 rounded-full', wh.color === 'blue' ? 'bg-blue-500' : wh.color === 'emerald' ? 'bg-emerald-500' : wh.color === 'amber' ? 'bg-amber-500' : 'bg-slate-400']" />
                                <h3 class="font-black text-slate-800 dark:text-white text-lg">{{ wh.name }}</h3>
                            </div>
                            <div class="flex gap-1">
                                <!-- Monitor button (visible to anyone who can view the warehouse) -->
                                <Link :href="route('warehouse.monitor', wh.id)" preserve-scroll
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition"
                                    title="Monitor Layout">
                                    <Eye class="w-3.5 h-3.5" />
                                </Link>
                                <!-- Edit/Delete buttons (only for managers) -->
                                <template v-if="canManage">
                                    <button
                                        @click="openEditModal(wh)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                        title="Edit"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="deleteWarehouse(wh.id)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                        title="Delete"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <MapPin class="w-3.5 h-3.5 text-slate-400" />
                                <span>{{ wh.location }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <User class="w-3.5 h-3.5 text-slate-400" />
                                <span>Supervisor: {{ wh.supervisor ? wh.supervisor.name : getUserName(wh.supervisor_id) || '—' }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-between text-xs">
                            <span class="text-slate-500">{{ wh.skus || 0 }} SKUs</span>
                            <span class="text-slate-500">{{ Number(wh.total_units || 0).toLocaleString() }} units</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Warehouse Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showAddModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Add Warehouse</h3>
                        <button @click="showAddModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Warehouse Name *</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Monti South Warehouse" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Location *</label>
                            <input v-model="form.location" type="text" placeholder="e.g. Pampanga, PH" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Supervisor</label>
                            <div class="relative">
                                <select v-model="form.supervisor_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">— Select Supervisor —</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.position }})</option>
                                </select>
                                <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Only users with Warehouse module access are listed.</p>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="showAddModal = false" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Cancel</button>
                        <button @click="addWarehouse" :disabled="processing || !form.name || !form.location" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:opacity-80 transition disabled:opacity-40">Add Warehouse</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Warehouse Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showEditModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Edit Warehouse</h3>
                        <button @click="showEditModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Warehouse Name *</label>
                            <input v-model="form.name" type="text" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Location *</label>
                            <input v-model="form.location" type="text" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Supervisor</label>
                            <div class="relative">
                                <select v-model="form.supervisor_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">— Select Supervisor —</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.position }})</option>
                                </select>
                                <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="showEditModal = false" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Cancel</button>
                        <button @click="updateWarehouse" :disabled="processing || !form.name || !form.location" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-40">Save Changes</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>