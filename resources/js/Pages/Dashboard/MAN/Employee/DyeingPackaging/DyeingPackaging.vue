<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, AlertCircle, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    availableForms: Array,    // { id, code, product_name, total_quantity, remaining_quantity }
    recentPackages: Array,    // { id, code, items, packaged_at, status }
});

// Selected items for the new package
const selectedItems = ref([]);

// Form for creating package
const form = useForm({
    items: [],
});

const addItem = () => {
    selectedItems.value.push({
        form_job_id: '',
        quantity: 1,
        max_quantity: 0,
    });
};

const removeItem = (index) => {
    selectedItems.value.splice(index, 1);
};

// Update max quantity when form job changes
const updateMaxQuantity = (index) => {
    const formJob = props.availableForms.find(f => f.id === selectedItems.value[index].form_job_id);
    if (formJob) {
        selectedItems.value[index].max_quantity = formJob.remaining_quantity;
        if (selectedItems.value[index].quantity > formJob.remaining_quantity) {
            selectedItems.value[index].quantity = formJob.remaining_quantity;
        }
    }
};

const submitPackage = () => {
    // Filter out items without a selected form job
    const validItems = selectedItems.value.filter(item => item.form_job_id);
    if (validItems.length === 0) {
        alert('Please select at least one product to package.');
        return;
    }
    form.items = validItems.map(item => ({
        form_job_id: item.form_job_id,
        quantity: item.quantity,
    }));
    form.post(route('man.staff.dyeing-packaging.store-package'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedItems.value = [];
            form.reset();
            router.reload({ only: ['availableForms', 'recentPackages'] });
        },
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};
</script>

<template>
    <AuthenticatedLayout title="Packaging">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Packaging Workspace</h1>
                <Link :href="route('man.staff.dyeing-packaging.dashboard')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                Back to Dashboard
                </Link>
            </div>

            <!-- Available Products Section -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 mb-8">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Available Products</h2>
                    <p class="text-sm text-gray-500">Products ready for packaging</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total
                                    Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="form in availableForms" :key="form.id">
                                <td class="px-6 py-4 font-mono text-sm">{{ form.code }}</td>
                                <td class="px-6 py-4">{{ form.product_name }}</td>
                                <td class="px-6 py-4">{{ form.total_quantity }}</td>
                                <td class="px-6 py-4 font-bold text-green-600">{{ form.remaining_quantity }}</td>
                            </tr>
                            <tr v-if="availableForms.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    No products available for packaging.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Package Section -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 mb-8">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Create New Package</h2>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(item, index) in selectedItems" :key="index"
                            class="flex flex-wrap gap-4 items-end border-b border-gray-100 pb-4">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select
                                    Product</label>
                                <select v-model="item.form_job_id" @change="updateMaxQuantity(index)"
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800">
                                    <option value="">Choose a product</option>
                                    <option v-for="form in availableForms" :key="form.id" :value="form.id">
                                        {{ form.code }} - {{ form.product_name }} ({{ form.remaining_quantity }} left)
                                    </option>
                                </select>
                            </div>
                            <div class="w-32">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                <input type="number" v-model.number="item.quantity" :max="item.max_quantity" min="1"
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    :disabled="!item.form_job_id" />
                            </div>
                            <button @click="removeItem(index)" class="mt-5 text-red-600 hover:text-red-800">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>

                        <button @click="addItem"
                            class="flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <Plus class="w-4 h-4" /> Add Product
                        </button>

                        <div class="pt-4">
                            <button @click="submitPackage" :disabled="form.processing || selectedItems.length === 0"
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2 disabled:opacity-50">
                                <Package class="w-4 h-4" />
                                {{ form.processing ? 'Creating Package...' : 'Create Package' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Packages Section -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Packages</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="pkg in recentPackages" :key="pkg.id">
                                <td class="px-6 py-4 font-mono text-sm">{{ pkg.code }}</td>
                                <td class="px-6 py-4">
                                    <div v-for="item in pkg.items" :key="item.id" class="text-sm">
                                        {{ item.quantity }}x {{ item.form_job?.product?.name || 'Product' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ formatDate(pkg.packaged_at) }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="pkg.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                        class="px-2 py-1 rounded text-xs font-bold">
                                        {{ pkg.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="recentPackages.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    No packages created yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>