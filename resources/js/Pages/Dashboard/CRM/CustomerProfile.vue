<script setup>
import { ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Building2, User, Mail, Phone, MapPin, CreditCard, Calendar,
    Clock, MessageSquare, AlertCircle, CheckCircle, XCircle,
    Plus, Edit, Trash2, Eye, FileText, TrendingUp, ShieldCheck,
    ChevronLeft
} from 'lucide-vue-next';

const props = defineProps({
    client: {
        type: Object,
        required: true
    },
    meetings: {
        type: Array,
        default: () => []
    },
    feedback: {
        type: Array,
        default: () => []
    }
});

// Feedback form with client_id pre-filled
const feedbackForm = useForm({
    client_id: props.client.id,   // <-- initialize here
    type: 'feedback',
    subject: '',
    message: ''
});

const showFeedbackModal = ref(false);

const openFeedbackModal = () => {
    feedbackForm.reset();
    // Re-assign client_id after reset (since reset clears all fields)
    feedbackForm.client_id = props.client.id;
    showFeedbackModal.value = true;
};

const submitFeedback = () => {
    feedbackForm.post(route('crm.investigation.feedback.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showFeedbackModal.value = false;
            feedbackForm.reset();
            // Reset client_id again after reset for next usage
            feedbackForm.client_id = props.client.id;
        }
    });
};

// Helper functions
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'N/A';
const formatDateTime = (date) => date ? new Date(date).toLocaleString() : 'N/A';
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0);
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-700';
        case 'pending': return 'bg-amber-100 text-amber-700';
        case 'suspended': return 'bg-red-100 text-red-700';
        case 'rejected': return 'bg-gray-100 text-gray-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};

const getFeedbackIcon = (type) => type === 'complaint' ? AlertCircle : MessageSquare;
const getFeedbackClass = (type) => type === 'complaint' ? 'text-red-600' : 'text-blue-600';
</script>

<template>
    <Head :title="client.company_name + ' - Profile'" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header with back button -->
            <div class="flex items-center gap-4">
                <Link :href="route('crm.investigation.index')" class="p-2 rounded-xl bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 transition">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        {{ client.company_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Client Profile & History</p>
                </div>
                <div class="ml-auto">
                    <span :class="['px-3 py-1 rounded-full text-xs font-bold uppercase', getStatusBadgeClass(client.status)]">
                        {{ client.status }}
                    </span>
                </div>
            </div>

            <!-- Client Information Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-blue-50 to-transparent dark:from-blue-900/10">
                    <h2 class="text-lg font-black uppercase tracking-wider flex items-center gap-2">
                        <Building2 class="w-5 h-5 text-blue-600" /> Company Information
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Building2 class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Company Name</p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">{{ client.company_name }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <User class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Contact Person</p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">{{ client.contact_person }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Mail class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Email Address</p>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ client.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Phone class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Phone Number</p>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ client.phone }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <MapPin class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Business Address</p>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ client.company_address }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <FileText class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">TIN Number</p>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ client.tin_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Credit Limit</p>
                                <p class="text-base font-bold text-emerald-600">{{ formatCurrency(client.credit_limit) }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Calendar class="w-5 h-5 text-gray-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Registered On</p>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ formatDate(client.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="client.rejection_reason" class="p-6 border-t border-gray-100 dark:border-zinc-800 bg-red-50 dark:bg-red-900/10">
                    <p class="text-xs font-bold text-red-600 uppercase">Rejection Reason</p>
                    <p class="text-sm text-red-700 dark:text-red-300">{{ client.rejection_reason }}</p>
                </div>
            </div>

            <!-- Meetings History -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h2 class="text-lg font-black uppercase tracking-wider flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-blue-600" /> Meeting History
                    </h2>
                </div>
                <div class="p-6">
                    <div v-if="meetings.length === 0" class="text-center py-8 text-gray-500 italic">
                        No meetings scheduled for this client.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="meeting in meetings" :key="meeting.id" class="border border-gray-100 dark:border-zinc-800 rounded-xl p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ meeting.meeting_type }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ formatDateTime(meeting.scheduled_at) }}</p>
                                    <p v-if="meeting.location" class="text-sm text-gray-500">📍 {{ meeting.location }}</p>
                                    <p v-if="meeting.notes" class="text-sm text-gray-500 mt-1">📝 {{ meeting.notes }}</p>
                                </div>
                                <span :class="{
                                    'bg-green-100 text-green-700': meeting.status === 'done',
                                    'bg-yellow-100 text-yellow-700': meeting.status === 'scheduled',
                                    'bg-red-100 text-red-700': meeting.status === 'cancelled',
                                    'bg-blue-100 text-blue-700': meeting.status === 'rescheduled'
                                }" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
                                    {{ meeting.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback & Complaints -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h2 class="text-lg font-black uppercase tracking-wider flex items-center gap-2">
                        <MessageSquare class="w-5 h-5 text-blue-600" /> Feedback & Complaints
                    </h2>
                    <button @click="openFeedbackModal" class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition">
                        <Plus class="w-4 h-4" /> Add Feedback
                    </button>
                </div>
                <div class="p-6">
                    <div v-if="feedback.length === 0" class="text-center py-8 text-gray-500 italic">
                        No feedback or complaints recorded.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="fb in feedback" :key="fb.id" class="border-l-4 rounded-r-xl p-4" :class="fb.type === 'complaint' ? 'border-l-red-500 bg-red-50 dark:bg-red-900/10' : 'border-l-blue-500 bg-blue-50 dark:bg-blue-900/10'">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <component :is="getFeedbackIcon(fb.type)" :class="getFeedbackClass(fb.type)" class="w-5 h-5" />
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ fb.subject }}</h3>
                                </div>
                                <span :class="{
                                    'bg-red-100 text-red-700': fb.status === 'open',
                                    'bg-yellow-100 text-yellow-700': fb.status === 'in_progress',
                                    'bg-green-100 text-green-700': fb.status === 'resolved'
                                }" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
                                    {{ fb.status }}
                                </span>
                            </div>
                            <p class="mt-2 text-gray-700 dark:text-gray-300">{{ fb.message }}</p>
                            <div class="mt-3 flex justify-between items-center text-xs text-gray-400">
                                <span>Assigned to: {{ fb.assignee?.name || 'Unassigned' }}</span>
                                <span>{{ formatDate(fb.created_at) }}</span>
                            </div>
                            <div v-if="fb.resolution_notes" class="mt-2 p-2 bg-white dark:bg-zinc-800 rounded text-sm">
                                <span class="font-bold">Resolution:</span> {{ fb.resolution_notes }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Feedback Modal -->
        <div v-if="showFeedbackModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showFeedbackModal = false">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-5 flex items-center justify-between">
                    <h2 class="text-white font-extrabold text-lg">Add Feedback / Complaint</h2>
                    <button @click="showFeedbackModal = false" class="text-white/70 hover:text-white">&times;</button>
                </div>
                <form @submit.prevent="submitFeedback" class="p-6 space-y-4">
                    <!-- Removed :value binding, v-model is enough and client_id is already initialized -->
                    <input type="hidden" v-model="feedbackForm.client_id" />
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase block mb-1">Type</label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2">
                                <input type="radio" value="feedback" v-model="feedbackForm.type" class="text-blue-600" />
                                <span>Feedback</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" value="complaint" v-model="feedbackForm.type" class="text-red-600" />
                                <span>Complaint</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase block mb-1">Subject</label>
                        <input type="text" v-model="feedbackForm.subject" class="w-full px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 border-none" required />
                    </div>
                    <div>
                        <label class="text-xs font-black text-gray-500 uppercase block mb-1">Message</label>
                        <textarea v-model="feedbackForm.message" rows="3" class="w-full px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 border-none" required></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showFeedbackModal = false" class="flex-1 py-2 text-gray-500 font-bold">Cancel</button>
                        <button type="submit" :disabled="feedbackForm.processing" class="flex-1 py-2 bg-blue-600 text-white rounded-xl font-bold">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Optional scrollbar */
</style>