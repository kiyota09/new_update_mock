<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Building2, CheckCircle, XCircle, Eye, X } from 'lucide-vue-next';

const props = defineProps({ registrations: Array });

const selectedVendor = ref(null);
const showModal = ref(false);
const modalMode = ref('view'); // view, approve, reject
const rejectionReason = ref('');
const requirementLines = ref([{ requirement_name: '', description: '', value: '' }]);

const openModal = (vendor, mode) => {
    selectedVendor.value = vendor;
    modalMode.value = mode;
    rejectionReason.value = '';
    requirementLines.value = vendor.requirements?.length ? vendor.requirements.map(r => ({ ...r })) : [{ requirement_name: '', description: '', value: '' }];
    showModal.value = true;
};

const submitApprove = () => {
    const validReqs = requirementLines.value.filter(r => r.requirement_name.trim());
    router.post(route('scm.vendors.approve', selectedVendor.value.id), { requirements: validReqs }, {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const submitReject = () => {
    router.post(route('scm.vendors.reject', selectedVendor.value.id), { rejection_reason: rejectionReason.value }, {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const addReqLine = () => requirementLines.value.push({ requirement_name: '', description: '', value: '' });
const removeReqLine = (i) => requirementLines.value.splice(i, 1);

const statusBadge = (s) => ({
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
}[s] || 'bg-gray-100');
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Vendors" />
        <div class="max-w-7xl mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">Vendor Registrations</h1>
            <div class="bg-white dark:bg-gray-800 rounded-lg border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="p-4 text-left">Business Name</th><th class="p-4 text-left">Contact</th><th class="p-4 text-center">Status</th><th class="p-4 text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="vendor in registrations" :key="vendor.id" class="border-t">
                            <td class="p-4">{{ vendor.business_name }}</td>
                            <td class="p-4">{{ vendor.representative_name }}<br><span class="text-xs text-gray-500">{{ vendor.email }}</span></td>
                            <td class="p-4 text-center"><span :class="statusBadge(vendor.status)" class="px-2 py-1 rounded-full text-xs">{{ vendor.status }}</span></td>
                            <td class="p-4 text-right space-x-2">
                                <button @click="openModal(vendor, 'view')" class="p-1"><Eye class="w-4 h-4" /></button>
                                <button v-if="vendor.status === 'pending'" @click="openModal(vendor, 'approve')" class="px-3 py-1 bg-green-600 text-white rounded text-xs">Approve</button>
                                <button v-if="vendor.status === 'pending'" @click="openModal(vendor, 'reject')" class="px-3 py-1 bg-red-600 text-white rounded text-xs">Reject</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full">
                    <div class="p-6 border-b flex justify-between">
                        <h3 class="text-xl font-bold">{{ modalMode === 'approve' ? 'Approve Vendor' : modalMode === 'reject' ? 'Reject Vendor' : 'Vendor Details' }}</h3>
                        <button @click="showModal = false"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6">
                        <div v-if="modalMode === 'view'">
                            <p><strong>Business:</strong> {{ selectedVendor.business_name }}</p>
                            <p><strong>Representative:</strong> {{ selectedVendor.representative_name }}</p>
                            <p><strong>Email:</strong> {{ selectedVendor.email }}</p>
                            <p><strong>Phone:</strong> {{ selectedVendor.phone_number }}</p>
                            <p><strong>Address:</strong> {{ selectedVendor.address }}</p>
                            <p><strong>Status:</strong> {{ selectedVendor.status }}</p>
                            <p v-if="selectedVendor.rejection_reason"><strong>Rejection reason:</strong> {{ selectedVendor.rejection_reason }}</p>
                        </div>
                        <div v-if="modalMode === 'reject'">
                            <label class="block text-sm font-medium">Reason for rejection *</label>
                            <textarea v-model="rejectionReason" rows="3" class="w-full border rounded-lg p-2 mt-1"></textarea>
                        </div>
                        <div v-if="modalMode === 'approve'">
                            <p class="mb-4">Set compliance requirements for <strong>{{ selectedVendor.business_name }}</strong></p>
                            <div v-for="(req, i) in requirementLines" :key="i" class="grid grid-cols-3 gap-2 mb-2">
                                <input v-model="req.requirement_name" placeholder="Requirement name" class="border rounded p-1">
                                <input v-model="req.description" placeholder="Description" class="border rounded p-1">
                                <div class="flex gap-1"><input v-model="req.value" placeholder="Value" class="border rounded p-1 flex-1"><button @click="removeReqLine(i)" class="text-red-500">✖</button></div>
                            </div>
                            <button @click="addReqLine" class="text-sm text-blue-600">+ Add requirement</button>
                        </div>
                    </div>
                    <div class="p-6 border-t flex justify-end gap-2">
                        <button @click="showModal = false" class="px-4 py-2 border rounded">Cancel</button>
                        <button v-if="modalMode === 'reject'" @click="submitReject" class="px-4 py-2 bg-red-600 text-white rounded">Confirm Reject</button>
                        <button v-if="modalMode === 'approve'" @click="submitApprove" class="px-4 py-2 bg-green-600 text-white rounded">Approve Vendor</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>