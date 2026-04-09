<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Scissors, AlertCircle, X } from 'lucide-vue-next';

const props = defineProps({
    machines: Array, // List of available knitting machines
});

const showRecordForm = ref(true); // Show form by default
const form = useForm({
    machine_id: '',
    yarn_type: '',
    roll_no: '',
    weight: '',
    remarks: '',
});

const submitFabric = () => {
    form.post(route('man.staff.knitting-yarn.store-fabric'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Knitting Yarn">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knitting Yarn Workspace</h1>
                <Link :href="route('man.staff.knitting-yarn.reports')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                View Reports
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Section -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Record New Fabric</h2>
                            <p class="text-sm text-gray-500">Enter details from the knitting machine</p>
                        </div>

                        <form @submit.prevent="submitFabric" class="p-6 space-y-4">
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yarn
                                    Type</label>
                                <input type="text" v-model="form.yarn_type" required
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="e.g., Polyester, Cotton, Nylon" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Roll
                                    No.</label>
                                <input type="text" v-model="form.roll_no" required
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="e.g., ROLL-001, BATCH-23" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weight
                                    (kg)</label>
                                <input type="number" step="0.01" v-model="form.weight" required
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="0.00" />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                                <textarea v-model="form.remarks" rows="3"
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="Optional notes..."></textarea>
                            </div>

                            <div class="pt-2">
                                <button type="submit" :disabled="form.processing"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-bold transition">
                                    {{ form.processing ? 'Recording...' : 'Record Fabric' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 p-6">
                        <Scissors class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-3" />
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Knitting Instructions</h3>
                        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                            <li>✓ Select the machine currently in use</li>
                            <li>✓ Record the yarn type and roll number</li>
                            <li>✓ Enter the exact weight of the produced fabric</li>
                            <li>✓ Add remarks if any issues or special notes</li>
                        </ul>
                        <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                            <p class="text-xs text-gray-500">
                                Each recorded fabric will be assigned a unique code (e.g., FABRIC-2026-03-00001) and
                                automatically go to the checker for quality evaluation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>