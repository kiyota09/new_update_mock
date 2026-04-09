<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Plus, Wrench, Calendar, X, CheckCircle, AlertTriangle, Eye } from 'lucide-vue-next';

const props = defineProps({
    machines: Array,
});

// State for modals
const showAddModal = ref(false);
const showStatusModal = ref(false);
const currentMachine = ref(null);

// Form for adding new machine
const addForm = useForm({
    machine_no: '',
    type: '',
    remarks: '',
});

// Form for updating machine status
const statusForm = useForm({
    status: '',
    remarks: '',
});

// Machine types
const machineTypes = [
    { value: 'knitting', label: 'Knitting Machine' },
    { value: 'dyeing', label: 'Dyeing Machine' },
    { value: 'softening', label: 'Softening Machine' },
    { value: 'squeezer', label: 'Squeezer Machine' },
    { value: 'forming', label: 'Forming Machine' },
];

// Status options
const statusOptions = [
    { value: 'available', label: 'Available', color: 'bg-green-100 text-green-800' },
    { value: 'under_maintenance', label: 'Under Maintenance', color: 'bg-yellow-100 text-yellow-800' },
    { value: 'retired', label: 'Retired', color: 'bg-red-100 text-red-800' },
];

const getStatusBadgeClass = (status) => {
    const option = statusOptions.find(opt => opt.value === status);
    return option?.color || 'bg-gray-100 text-gray-800';
};

const openAddModal = () => {
    addForm.reset();
    showAddModal.value = true;
};

const openStatusModal = (machine) => {
    currentMachine.value = machine;
    statusForm.status = machine.status;
    statusForm.remarks = machine.remarks || '';
    showStatusModal.value = true;
};

const submitAddMachine = () => {
    addForm.post(route('man.staff.maintenance-checker.store-machine'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
            router.reload({ only: ['machines'] });
        },
    });
};

const submitUpdateStatus = () => {
    statusForm.patch(route('man.staff.maintenance-checker.update-machine', currentMachine.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showStatusModal.value = false;
            statusForm.reset();
            router.reload({ only: ['machines'] });
        },
    });
};

const closeModal = () => {
    showAddModal.value = false;
    showStatusModal.value = false;
    currentMachine.value = null;
};

// Group machines by type for display
const groupedMachines = computed(() => {
    const groups = {};
    props.machines.forEach(machine => {
        if (!groups[machine.type]) groups[machine.type] = [];
        groups[machine.type].push(machine);
    });
    return groups;
});
</script>

<template>
    <AuthenticatedLayout title="Maintenance Management">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Machine Management</h1>
                <button @click="openAddModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <Plus class="w-4 h-4" /> Add Machine
                </button>
            </div>

            <!-- Calendar Section (Placeholder) -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 mb-8">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <Calendar class="w-5 h-5" /> Maintenance Schedule
                    </h2>
                </div>
                <div class="p-6 text-center text-gray-500">
                    <Calendar class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                    <p>Schedule preview will be implemented soon</p>
                    <p class="text-sm">Here you'll be able to view upcoming maintenance tasks and set reminders.</p>
                </div>
            </div>

            <!-- Machines by Type -->
            <div class="space-y-8">
                <div v-for="(machines, type) in groupedMachines" :key="type"
                    class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                        <h2 class="text-lg font-bold capitalize">{{ type }} Machines</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine
                                        No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="machine in machines" :key="machine.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm">{{ machine.machine_no }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="getStatusBadgeClass(machine.status)"
                                            class="px-2 py-1 rounded text-xs font-bold">
                                            {{ machine.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ machine.remarks || '—' }}</td>
                                    <td class="px-6 py-4">
                                        <button @click="openStatusModal(machine)"
                                            class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                            <Eye class="w-4 h-4" /> Update Status
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="Object.keys(groupedMachines).length === 0" class="text-center py-12 text-gray-500">
                    No machines found. Click "Add Machine" to get started.
                </div>
            </div>
        </div>

        <!-- Add Machine Modal -->
        <div v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="closeModal">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                    <h3 class="text-lg font-bold">Add New Machine</h3>
                    <button @click="closeModal" class="hover:opacity-70">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitAddMachine" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Machine No.</label>
                        <input type="text" v-model="addForm.machine_no" required class="w-full border rounded-lg p-2"
                            placeholder="e.g., KN-001, DY-023" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Machine Type</label>
                        <select v-model="addForm.type" required class="w-full border rounded-lg p-2">
                            <option value="">Select Type</option>
                            <option v-for="type in machineTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Remarks</label>
                        <textarea v-model="addForm.remarks" rows="2" class="w-full border rounded-lg p-2"
                            placeholder="Optional notes..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="addForm.processing"
                            class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold">
                            {{ addForm.processing ? 'Adding...' : 'Add Machine' }}
                        </button>
                        <button type="button" @click="closeModal"
                            class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-bold">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div v-if="showStatusModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="closeModal">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                    <h3 class="text-lg font-bold">Update Machine Status</h3>
                    <button @click="closeModal" class="hover:opacity-70">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitUpdateStatus" class="p-6 space-y-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-sm font-medium">Machine: <span class="font-mono">{{ currentMachine?.machine_no
                                }}</span></p>
                        <p class="text-sm text-gray-500">Type: {{ currentMachine?.type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select v-model="statusForm.status" required class="w-full border rounded-lg p-2">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Remarks</label>
                        <textarea v-model="statusForm.remarks" rows="2" class="w-full border rounded-lg p-2"
                            placeholder="Notes about status change..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="statusForm.processing"
                            class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold">
                            {{ statusForm.processing ? 'Updating...' : 'Update Status' }}
                        </button>
                        <button type="button" @click="closeModal"
                            class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-bold">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>