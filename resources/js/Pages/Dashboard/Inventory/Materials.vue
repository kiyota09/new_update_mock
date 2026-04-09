<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Plus,
    Trash2,
    X,
    Search,
    ChevronDown,
    AlertTriangle,
    ShoppingCart,
} from 'lucide-vue-next';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const showAddModal = ref(false);
const showProcurementModal = ref(false);
const selectedMaterial = ref(null);
const processing = ref(false);

// Form for procurement request
const procurementForm = useForm({
    required_qty: 0,
    urgency: 'Medium',
    notes: '',
});

// Form for adding material
const addForm = useForm({
    name: '',
    category: 'Yarn',
    unit: 'Kg',
    reorder_point: 0,
    unit_cost: 0,
});

// Filtered materials
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) ||
        mat.mat_id.toLowerCase().includes(q)
    );
});

// Reset add material form
const resetAddForm = () => {
    addForm.name = '';
    addForm.category = 'Yarn';
    addForm.unit = 'Kg';
    addForm.reorder_point = 0;
    addForm.unit_cost = 0;
    addForm.clearErrors();
};

// Add material
const addMaterial = () => {
    addForm.post(route('inv.materials.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            resetAddForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Delete material
const deleteMaterial = (id) => {
    if (!confirm('Delete this material? This action cannot be undone.')) return;
    router.delete(route('inv.materials.destroy', id), {
        preserveScroll: true,
    });
};

// Open procurement modal
const openProcurementModal = (material) => {
    selectedMaterial.value = material;
    procurementForm.required_qty = material.reorder_point - material.total_stock > 0 
        ? Math.ceil(material.reorder_point - material.total_stock) 
        : material.reorder_point;
    procurementForm.urgency = 'Medium';
    procurementForm.notes = '';
    showProcurementModal.value = true;
};

// Submit procurement request
const submitProcurement = () => {
    if (!procurementForm.required_qty || procurementForm.required_qty <= 0) {
        alert('Please enter a valid quantity.');
        return;
    }
    
    procurementForm.post(route('inv.checker.procurement', selectedMaterial.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showProcurementModal.value = false;
            selectedMaterial.value = null;
            procurementForm.reset();
        },
        onError: (errors) => {
            alert('Failed to send procurement request: ' + (errors.message || 'Unknown error'));
        },
    });
};

// Stock helpers
const stockStatus = (mat) => {
    if (mat.total_stock <= 0) return 'Out of Stock';
    if (mat.total_stock <= mat.reorder_point) return 'Low Stock';
    return 'In Stock';
};

const statusColor = (status) => {
    if (status === 'In Stock') return 'bg-emerald-100 text-emerald-700';
    if (status === 'Low Stock') return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-600';
};

const isBelowReorder = (mat) => {
    return mat.total_stock <= mat.reorder_point && mat.total_stock > 0;
};
</script>

<template>
    <Head title="Materials | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Materials</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Manage raw materials used in production.</p>
                    </div>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm"
                    >
                        <Plus class="w-4 h-4" />
                        Add Material
                    </button>
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
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Material ID</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Name</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Category</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Unit</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Reorder Point</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Stock Status</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredMaterials.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold text-slate-500">No materials found.</p>
                                    </td>
                                </tr>
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4">
                                        <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-300">{{ mat.mat_id }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-800 dark:text-slate-200">{{ mat.name }}</td>
                                    <td class="px-5 py-4">
                                        <span class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-full">{{ mat.category }}</span>
                                    </td>
                                    <td class="px-5 py-4">{{ mat.unit }}</td>
                                    <td class="px-5 py-4 font-mono">{{ mat.reorder_point }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <span :class="['inline-flex px-2 py-1 rounded-full text-[10px] font-black', statusColor(stockStatus(mat))]">
                                                {{ stockStatus(mat) }}
                                            </span>
                                            <div v-if="isBelowReorder(mat)" class="relative group">
                                                <AlertTriangle class="w-4 h-4 text-amber-500 cursor-help" />
                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-slate-800 text-white text-[10px] font-bold px-2 py-1 rounded whitespace-nowrap z-10">
                                                    Stock below reorder point
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                @click="openProcurementModal(mat)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                                :title="mat.total_stock <= mat.reorder_point ? 'Low stock – request procurement' : 'Request procurement'"
                                            >
                                                <ShoppingCart class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="deleteMaterial(mat.id)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                                title="Delete"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400">
                        Total: {{ filteredMaterials.length }} materials
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Material Modal (unchanged) -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showAddModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Add Material</h3>
                        <button @click="showAddModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Material Name *</label>
                            <input v-model="addForm.name" type="text" placeholder="e.g. Cotton Yarn 20s" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                            <div v-if="addForm.errors.name" class="text-red-500 text-xs mt-1">{{ addForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Category *</label>
                            <div class="relative mt-1">
                                <select v-model="addForm.category" class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="Yarn">Yarn</option>
                                    <option value="Dye">Dye</option>
                                    <option value="Supplies">Supplies</option>
                                    <option value="Packaging">Packaging</option>
                                </select>
                                <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit *</label>
                            <div class="relative mt-1">
                                <select v-model="addForm.unit" class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="Rolls">Rolls</option>
                                    <option value="Kg">Kg</option>
                                    <option value="Pcs">Pcs</option>
                                </select>
                                <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reorder Point</label>
                            <input v-model="addForm.reorder_point" type="number" min="0" placeholder="0" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                            <p class="text-[10px] text-slate-400 mt-1">Alert when stock falls below this quantity.</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Cost (₱)</label>
                            <input v-model="addForm.unit_cost" type="number" min="0" step="0.01" placeholder="0.00" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="showAddModal = false" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Cancel</button>
                        <button @click="addMaterial" :disabled="addForm.processing || !addForm.name" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-40">Add Material</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Procurement Request Modal -->
        <Teleport to="body">
            <div v-if="showProcurementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showProcurementModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            Request Procurement
                        </h3>
                        <button @click="showProcurementModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-xl">
                            <p class="text-xs text-slate-500">Material</p>
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ selectedMaterial?.name }}</p>
                            <p class="text-xs text-slate-400">Current stock: {{ selectedMaterial?.total_stock || 0 }} {{ selectedMaterial?.unit }}</p>
                            <p class="text-xs text-slate-400">Reorder point: {{ selectedMaterial?.reorder_point }} {{ selectedMaterial?.unit }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Required Quantity ({{ selectedMaterial?.unit }}) *</label>
                            <input v-model="procurementForm.required_qty" type="number" min="0" step="1" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                            <div v-if="procurementForm.errors.required_qty" class="text-red-500 text-xs mt-1">{{ procurementForm.errors.required_qty }}</div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Urgency</label>
                            <div class="relative mt-1">
                                <select v-model="procurementForm.urgency" class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="High">High – Critical stockout</option>
                                    <option value="Medium">Medium – Near reorder point</option>
                                    <option value="Low">Low – Planned restock</option>
                                </select>
                                <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Additional Notes</label>
                            <textarea v-model="procurementForm.notes" rows="3" placeholder="Reason for request, delivery expectations, etc." class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="showProcurementModal = false" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Cancel</button>
                        <button @click="submitProcurement" :disabled="procurementForm.processing" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-40">Send Request</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>