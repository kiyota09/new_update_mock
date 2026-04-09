<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Search, UserPlus, MessageSquare, AlertCircle, CheckCircle, Clock,
    Eye, Edit, X, Plus, Trash2, ShieldCheck, UserCheck, ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
    clients: {
        type: Array,
        default: () => []
    },
    staff: {
        type: Array,
        default: () => []
    },
    message: {
        type: String,
        default: null
    }
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isManager = computed(() => user.value?.position === 'manager' || user.value?.role === 'CEO');

// Search
const searchQuery = ref('');

const filteredClients = computed(() => {
    if (!searchQuery.value) return props.clients;
    const q = searchQuery.value.toLowerCase();
    return props.clients.filter(c =>
        c.company_name?.toLowerCase().includes(q) ||
        c.contact_person?.toLowerCase().includes(q)
    );
});

// Modals
const showFeedbackModal = ref(false);
const showAssignModal = ref(false);
const selectedClient = ref(null);

// Feedback form
const feedbackForm = useForm({
    client_id: null,
    type: 'feedback',
    subject: '',
    message: ''
});

// Assign form
const assignForm = useForm({
    client_id: null,
    staff_id: null
});

// Open feedback modal
const openFeedbackModal = (client) => {
    selectedClient.value = client;
    feedbackForm.reset();
    feedbackForm.client_id = client.id;
    showFeedbackModal.value = true;
};

// Submit feedback
const submitFeedback = () => {
    feedbackForm.post(route('crm.investigation.feedback.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showFeedbackModal.value = false;
            feedbackForm.reset();
        }
    });
};

// Open assign modal
const openAssignModal = (client) => {
    selectedClient.value = client;
    assignForm.reset();
    assignForm.client_id = client.id;
    showAssignModal.value = true;
};

// Submit assignment
const assignStaff = () => {
    assignForm.post(route('crm.investigation.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
        }
    });
};

// Update feedback status
const updateStatus = (feedbackId, newStatus) => {
    router.patch(route('crm.investigation.feedback.status', feedbackId), { status: newStatus, resolution_notes: null }, {
        preserveScroll: true
    });
};

// Helper to get status badge class
const getStatusClass = (status) => {
    switch (status) {
        case 'open': return 'bg-red-100 text-red-700';
        case 'in_progress': return 'bg-yellow-100 text-yellow-700';
        case 'resolved': return 'bg-green-100 text-green-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <Head title="Investigation & Feedback" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Investigation <span class="text-blue-600">Center</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Monitor client feedback, complaints, and assign staff.
                    </p>
                </div>
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search clients..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <!-- Message (e.g., no assigned clients for staff) -->
            <div v-if="message" class="bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-yellow-800 dark:text-yellow-300">
                {{ message }}
            </div>

            <!-- Clients List -->
            <div v-if="filteredClients.length === 0" class="text-center py-20 text-gray-500">
                <AlertCircle class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-lg font-medium">No clients found.</p>
                <p class="text-sm">Try adjusting your search or check back later.</p>
            </div>

            <div v-for="client in filteredClients" :key="client.id" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                <!-- Client Header with clickable name -->
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex flex-wrap justify-between items-center gap-4">
                    <Link :href="route('crm.customerprofile.show', client.id)" class="group cursor-pointer">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition">
                                {{ client.company_name }}
                            </h2>
                            <ExternalLink class="h-4 w-4 text-gray-400 group-hover:text-blue-500" />
                        </div>
                        <p class="text-sm text-gray-500">{{ client.contact_person }} | {{ client.email }}</p>
                    </Link>
                    <div class="flex gap-2">
                        <button @click="openFeedbackModal(client)" class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700">
                            <MessageSquare class="w-4 h-4" /> Add Feedback
                        </button>
                        <button v-if="isManager" @click="openAssignModal(client)" class="flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-bold hover:bg-purple-700">
                            <UserPlus class="w-4 h-4" /> Assign Staff
                        </button>
                    </div>
                </div>

                <!-- Feedback/Complaints Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-if="!client.feedback || client.feedback.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No feedback or complaints recorded.</td>
                            </tr>
                            <tr v-for="fb in client.feedback" :key="fb.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4">
                                    <span :class="fb.type === 'complaint' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        {{ fb.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ fb.subject }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400 max-w-md truncate">{{ fb.message }}</td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(fb.status)" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                        {{ fb.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ fb.assignee?.name || 'Unassigned' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button v-if="fb.status !== 'resolved'" @click="updateStatus(fb.id, 'resolved')" class="p-1.5 rounded-lg bg-green-100 text-green-700 hover:bg-green-200" title="Mark Resolved">
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button v-if="fb.status === 'open'" @click="updateStatus(fb.id, 'in_progress')" class="p-1.5 rounded-lg bg-yellow-100 text-yellow-700 hover:bg-yellow-200" title="Mark In Progress">
                                            <Clock class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Feedback Modal -->
        <div v-if="showFeedbackModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showFeedbackModal = false">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                    <h2 class="text-lg font-black">Add Feedback / Complaint</h2>
                    <button @click="showFeedbackModal = false" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <form @submit.prevent="submitFeedback" class="p-6 space-y-4">
                    <input type="hidden" v-model="feedbackForm.client_id" />
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase">Type</label>
                        <div class="flex gap-4 mt-1">
                            <label class="flex items-center gap-1"><input type="radio" value="feedback" v-model="feedbackForm.type" /> Feedback</label>
                            <label class="flex items-center gap-1"><input type="radio" value="complaint" v-model="feedbackForm.type" /> Complaint</label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase">Subject</label>
                        <input type="text" v-model="feedbackForm.subject" required class="w-full mt-1 px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 border-none" />
                    </div>
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase">Message</label>
                        <textarea v-model="feedbackForm.message" rows="3" required class="w-full mt-1 px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 border-none"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showFeedbackModal = false" class="flex-1 py-2 text-gray-500 font-bold">Cancel</button>
                        <button type="submit" :disabled="feedbackForm.processing" class="flex-1 py-2 bg-blue-600 text-white rounded-xl font-bold">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assign Staff Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showAssignModal = false">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-purple-600 p-4 text-white flex justify-between items-center">
                    <h2 class="text-lg font-black">Assign Staff to Client</h2>
                    <button @click="showAssignModal = false" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <form @submit.prevent="assignStaff" class="p-6 space-y-4">
                    <input type="hidden" v-model="assignForm.client_id" />
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase">Select Staff</label>
                        <select v-model="assignForm.staff_id" required class="w-full mt-1 px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 border-none">
                            <option value="">-- Choose staff --</option>
                            <option v-for="staffMember in staff" :key="staffMember.id" :value="staffMember.id">
                                {{ staffMember.name }} ({{ staffMember.email }})
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showAssignModal = false" class="flex-1 py-2 text-gray-500 font-bold">Cancel</button>
                        <button type="submit" :disabled="assignForm.processing" class="flex-1 py-2 bg-purple-600 text-white rounded-xl font-bold">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>