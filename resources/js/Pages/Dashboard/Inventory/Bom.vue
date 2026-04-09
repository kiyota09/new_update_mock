<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Plus,
    Trash2,
    X,
    Edit2,
    Save,
    ChevronDown,
    AlertCircle,
    CheckCircle,
} from 'lucide-vue-next';

const props = defineProps({
    boms: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const showForm = ref(false);
const editingId = ref(null);
const processing = ref(false);

// Form for creating/editing BOM
const form = useForm({
    client_id: '',
    product_id: '',
    yarn_type: '',
    dye_color: '',
    weave_design: '',
    materials: {}, // { material_id: quantity }
});

// Material selection for JSON editor
const materialEntries = ref([]); // [{ material_id, quantity }]
const selectedMaterialId = ref('');
const materialQuantity = ref(1);

// Watch form.materials to sync materialEntries
watch(() => form.materials, (newVal) => {
    materialEntries.value = Object.entries(newVal).map(([id, qty]) => ({
        material_id: parseInt(id),
        quantity: qty,
    }));
}, { immediate: true, deep: true });

// Add material to BOM
const addMaterialToBom = () => {
    if (!selectedMaterialId.value || !materialQuantity.value || materialQuantity.value <= 0) return;
    const matId = parseInt(selectedMaterialId.value);
    if (form.materials[matId]) {
        alert('Material already added. Update quantity directly in the list.');
        return;
    }
    form.materials[matId] = parseFloat(materialQuantity.value);
    selectedMaterialId.value = '';
    materialQuantity.value = 1;
};

// Remove material from BOM
const removeMaterialFromBom = (materialId) => {
    delete form.materials[materialId];
};

// Update quantity for a material
const updateMaterialQty = (materialId, newQty) => {
    form.materials[materialId] = parseFloat(newQty) || 0;
    if (form.materials[materialId] <= 0) delete form.materials[materialId];
};

// Reset form
const resetForm = () => {
    form.client_id = '';
    form.product_id = '';
    form.yarn_type = '';
    form.dye_color = '';
    form.weave_design = '';
    form.materials = {};
    materialEntries.value = [];
    form.clearErrors();
    editingId.value = null;
};

// Open form to create new BOM
const openCreate = () => {
    resetForm();
    showForm.value = true;
};

// Open form to edit existing BOM
const openEdit = (bom) => {
    editingId.value = bom.id;
    form.client_id = bom.client_id;
    form.product_id = bom.product_id;
    form.yarn_type = bom.yarn_type;
    form.dye_color = bom.dye_color;
    form.weave_design = bom.weave_design;
    form.materials = bom.materials || {};
    showForm.value = true;
};

// Submit form (create or update)
const submitForm = () => {
    if (!form.client_id || !form.product_id || !form.yarn_type || !form.dye_color || !form.weave_design) {
        alert('Please fill all required fields.');
        return;
    }
    if (Object.keys(form.materials).length === 0) {
        alert('Please add at least one material to the BOM.');
        return;
    }
    processing.value = true;
    const url = editingId.value ? route('inv.bom.update', editingId.value) : route('inv.bom.store');
    const method = editingId.value ? 'put' : 'post';
    router[method](url, form, {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            resetForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Delete BOM
const deleteBom = (id) => {
    if (!confirm('Delete this BOM record? This action cannot be undone.')) return;
    router.delete(route('inv.bom.destroy', id), { preserveScroll: true });
};

// Filter BOMs by search
const filteredBoms = computed(() => {
    if (!searchQuery.value) return props.boms;
    const q = searchQuery.value.toLowerCase();
    return props.boms.filter(bom =>
        bom.client?.company_name?.toLowerCase().includes(q) ||
        bom.product?.name?.toLowerCase().includes(q) ||
        bom.yarn_type.toLowerCase().includes(q)
    );
});

// Helper: get client name by id
const getClientName = (id) => {
    const client = props.clients.find(c => c.id === id);
    return client ? client.company_name : '—';
};

// Helper: get product name by id
const getProductName = (id) => {
    const product = props.products.find(p => p.id === id);
    return product ? product.name : '—';
};

// Helper: get material name by id
const getMaterialName = (id) => {
    const mat = props.materials.find(m => m.id === id);
    return mat ? `${mat.mat_id} - ${mat.name}` : 'Unknown';
};
</script>

<template>
    <Head title="Bill of Materials (BOM) | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Bill of Materials (BOM)</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Client‑specific fabric formulas (yarn, dye, design, materials).</p>
                    </div>
                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm"
                    >
                        <Plus class="w-4 h-4" />
                        Add BOM
                    </button>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by client, product, or yarn type..."
                        class="w-full max-w-md px-4 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>

                <!-- BOM List Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Client</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Product</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Yarn Type</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Dye Color</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Weave Design</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Materials</th>
                                    <th class="px-5 py-3.5 text-center w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredBoms.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold">No BOM records found.</p>
                                    </td>
                                </tr>
                                <tr v-for="bom in filteredBoms" :key="bom.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4 font-semibold">{{ bom.client?.company_name || getClientName(bom.client_id) }}</td>
                                    <td class="px-5 py-4">{{ bom.product?.name || getProductName(bom.product_id) }}</td>
                                    <td class="px-5 py-4"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ bom.yarn_type }}</span></td>
                                    <td class="px-5 py-4"><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">{{ bom.dye_color }}</span></td>
                                    <td class="px-5 py-4">{{ bom.weave_design }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="(qty, matId) in bom.materials" :key="matId" class="text-[10px] font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                                {{ getMaterialName(parseInt(matId)) }}: {{ qty }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openEdit(bom)" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit">
                                                <Edit2 class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteBom(bom.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition" title="Delete">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400">
                        Total: {{ filteredBoms.length }} BOMs
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit BOM Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm overflow-y-auto" @click.self="showForm = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl my-8">
                    <div class="px-6 py-5 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-black">{{ editingId ? 'Edit BOM' : 'Create BOM' }}</h3>
                            <p class="text-xs text-slate-400">Client‑specific fabric formula.</p>
                        </div>
                        <button @click="showForm = false; resetForm()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600"><X class="w-4 h-4" /></button>
                    </div>
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <!-- Client & Product -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Client *</label>
                                <select v-model="form.client_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border rounded-xl">
                                    <option value="">Select client...</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                                </select>
                                <div v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Product *</label>
                                <select v-model="form.product_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border rounded-xl">
                                    <option value="">Select product...</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }} ({{ product.sku }})</option>
                                </select>
                                <div v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</div>
                            </div>
                        </div>

                        <!-- Yarn, Dye, Weave -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Yarn Type *</label>
                                <input v-model="form.yarn_type" type="text" placeholder="e.g. Cotton 20s" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Dye Color *</label>
                                <input v-model="form.dye_color" type="text" placeholder="e.g. Navy Blue" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Weave Design *</label>
                                <input v-model="form.weave_design" type="text" placeholder="e.g. Twill 2/1" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                        </div>

                        <!-- Materials JSON Editor -->
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider mb-2 block">Materials (Material ID → Quantity)</label>
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border">
                                <!-- Add new material row -->
                                <div class="flex gap-2 mb-4">
                                    <select v-model="selectedMaterialId" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-slate-900 border rounded-lg">
                                        <option value="">Select material...</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">{{ mat.mat_id }} – {{ mat.name }} ({{ mat.unit }})</option>
                                    </select>
                                    <input v-model.number="materialQuantity" type="number" min="0.01" step="0.01" placeholder="Qty" class="w-28 px-3 py-2 text-sm bg-white dark:bg-slate-900 border rounded-lg" />
                                    <button @click="addMaterialToBom" :disabled="!selectedMaterialId || !materialQuantity" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold disabled:opacity-50">Add</button>
                                </div>

                                <!-- List of added materials -->
                                <div v-if="materialEntries.length === 0" class="text-center text-slate-400 py-4 text-sm">No materials added yet.</div>
                                <div v-for="entry in materialEntries" :key="entry.material_id" class="flex items-center justify-between gap-3 py-2 border-b last:border-0">
                                    <div class="flex-1">
                                        <span class="font-mono text-sm">{{ getMaterialName(entry.material_id) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="0.01" :value="entry.quantity" @input="updateMaterialQty(entry.material_id, $event.target.value)" class="w-24 px-2 py-1 text-sm bg-white border rounded-lg" />
                                        <button @click="removeMaterialFromBom(entry.material_id)" class="text-red-500 hover:text-red-700"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t flex gap-3 bg-slate-50 dark:bg-slate-800">
                        <button @click="showForm = false; resetForm()" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 text-slate-600">Cancel</button>
                        <button @click="submitForm" :disabled="processing" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                            {{ processing ? 'Saving...' : (editingId ? 'Update BOM' : 'Create BOM') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>