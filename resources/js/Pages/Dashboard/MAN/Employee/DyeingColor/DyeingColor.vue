<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Droplet, AlertCircle, X } from 'lucide-vue-next';

const props = defineProps({
    fabrics: Array,
    machines: Array,
});

const showDyeModal = ref(false);
const currentFabric = ref(null);

const form = useForm({
    fabric_id: null,
    machine_id: '',
    dye_type: '',
    chemical_no: '',
    remarks: '',
});

const openDyeModal = (fabric) => {
    currentFabric.value = fabric;
    form.fabric_id = fabric.id;
    form.machine_id = '';
    form.dye_type = '';
    form.chemical_no = '';
    form.remarks = '';
    showDyeModal.value = true;
};

const closeModal = () => {
    showDyeModal.value = false;
    form.reset();
    currentFabric.value = null;
};

const submitDye = () => {
    form.post(route('man.staff.dyeing-color.store-dye'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            router.reload({ only: ['fabrics'] });
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Dyeing Color">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dyeing Color Workspace</h1>
                <Link :href="route('man.staff.dyeing-color.reports')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                View Reports
                </Link>
            </div>

            <div v-if="fabrics && fabrics.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="fabric in fabrics" :key="fabric.id"
                    class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden hover:shadow-md transition">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400">{{ fabric.code
                                    }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ fabric.yarn_type }} | Roll:
                                    {{ fabric.roll_no }}</p>
                            </div>
                            <span
                                class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-bold">Pending</span>
                        </div>

                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <p><span class="font-medium">Machine:</span> {{ fabric.machine?.machine_no || 'N/A' }}</p>
                            <p><span class="font-medium">Weight:</span> {{ fabric.weight }} kg</p>
                            <p><span class="font-medium">Operator:</span> {{ fabric.operator?.name }}</p>
                            <p><span class="font-medium">Shift:</span> {{ fabric.shift }}</p>
                            <p><span class="font-medium">Date:</span> {{ new Date(fabric.processed_at).toLocaleString()
                                }}</p>
                            <p v-if="fabric.remarks"><span class="font-medium">Remarks:</span> {{ fabric.remarks }}</p>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                            <button @click="openDyeModal(fabric)"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-2">
                                <Droplet class="w-4 h-4" /> Dye Fabric
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-12 text-center">
                <AlertCircle class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No fabrics pending dyeing at the moment.</p>
            </div>
        </div>

        <!-- Dye Modal -->
        <div v-if="showDyeModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="closeModal">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-zinc-800">
                <div
                    class="flex justify-between items-center p-6 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Record Dyeing Process</h3>
                    <button @click="closeModal" class="hover:opacity-70">
                        <X class="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <form @submit.prevent="submitDye" class="p-6 space-y-4">
                    <div class="bg-gray-50 dark:bg-zinc-800 p-3 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Fabric: <span
                                class="font-mono">{{ currentFabric?.code }}</span></p>
                        <p class="text-sm text-gray-500">{{ currentFabric?.yarn_type }} ({{ currentFabric?.weight }} kg)
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Machine
                            No.</label>
                        <select v-model="form.machine_id" required
                            class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800">
                            <option value="">Select Machine</option>
                            <option v-for="machine in machines" :key="machine.id" :value="machine.id">
                                {{ machine.machine_no }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dye Type</label>
                        <input type="text" v-model="form.dye_type" required
                            class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                            placeholder="e.g., Reactive Blue" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chemical
                            No.</label>
                        <input type="text" v-model="form.chemical_no" required
                            class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                            placeholder="e.g., CH-2026-001" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                        <textarea v-model="form.remarks" rows="2"
                            class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                            placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-bold transition">
                            {{ form.processing ? 'Processing...' : 'Submit Dye Job' }}
                        </button>
                        <button type="button" @click="closeModal"
                            class="flex-1 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-800 dark:text-gray-200 py-2 rounded-lg font-bold transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>