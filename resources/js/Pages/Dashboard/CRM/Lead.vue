<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Plus, DollarSign, Calendar, X, CheckCircle2, AlertCircle,
    HelpCircle, ArrowRight, UserCheck, Building2, FileText, Upload, Clock,
    MessageSquare, Video, MapPin, Download, Eye, Trash2, ChevronDown, ChevronUp,
    ArrowUpRight, Filter, Search, ChevronRight, ArrowRightCircle, MoveRight,
    Check, XCircle
} from 'lucide-vue-next';

const props = defineProps({
    leads: Array
});

// ─────────────────────────────────────────────────
// Tab State & Lead Grouping
// ─────────────────────────────────────────────────
const activeTab = ref('Inquiry');
const searchQuery = ref('');

const stages = [
    { status: 'Inquiry', label: 'New Inquiry', icon: MessageSquare },
    { status: 'Negotiation', label: 'Negotiation', icon: Video },
    { status: 'Approval Sent', label: 'Approval Sent', icon: FileText },
    { status: 'Closed-Won', label: 'Closed-Won', icon: CheckCircle2 }
];

// Group leads by status, filter by search, and sort oldest first (FIFO)
const leadsByStatus = computed(() => {
    const groups = {};
    stages.forEach(s => groups[s.status] = []);

    (props.leads || []).forEach(lead => {
        if (groups[lead.status] !== undefined) {
            groups[lead.status].push(lead);
        }
    });

    // Sort each group by created_at ascending (oldest first)
    for (const status in groups) {
        groups[status].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    }

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        for (const status in groups) {
            groups[status] = groups[status].filter(lead =>
                lead.company_name.toLowerCase().includes(query) ||
                lead.contact_person.toLowerCase().includes(query) ||
                lead.email.toLowerCase().includes(query)
            );
        }
    }

    return groups;
});

// Current leads for active tab
const currentLeads = computed(() => leadsByStatus.value[activeTab.value] || []);

// ─────────────────────────────────────────────────
// Modal & Form State
// ─────────────────────────────────────────────────
const showCreateModal = ref(false);
const showClientConversionModal = ref(false);
const showNoteModal = ref(false);
const showInterviewModal = ref(false);
const showFinalizeModal = ref(false);       // new modal for Approval Sent
const showMoveConfirmModal = ref(false);
const currentLead = ref(null);
const pendingMoveNextStage = ref(null);

// Finalization modal state
const finalizeFile = ref(null);
const finalizeAction = ref(null); // 'accept' or 'reject'
const rejectReason = ref('');
const isUploading = ref(false);
const isSubmitting = ref(false);

// Forms
const form = useForm({
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    interest_fabric: 'Cotton',
    estimated_value: '',
});

const conversionForm = useForm({
    lead_id: null,
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    business_type: 'wholesaler',
    tin_number: '',
    company_address: '',
    password: 'password123',
});

const noteForm = useForm({ note: '' });
const interviewForm = useForm({ scheduled_at: '', location: '', notes: '' });
const rejectForm = useForm({ reject_reason: '' });

// ─────────────────────────────────────────────────
// Helper: Move to next stage (for Inquiry/Negotiation)
// ─────────────────────────────────────────────────
const getNextStage = (currentStatus) => {
    const stagesOrder = ['Inquiry', 'Negotiation', 'Approval Sent', 'Closed-Won'];
    const idx = stagesOrder.indexOf(currentStatus);
    if (idx === -1 || idx === stagesOrder.length - 1) return null;
    return stagesOrder[idx + 1];
};

const openMoveConfirm = (lead) => {
    currentLead.value = lead;
    pendingMoveNextStage.value = getNextStage(lead.status);
    showMoveConfirmModal.value = true;
};

const confirmMove = () => {
    if (!currentLead.value || !pendingMoveNextStage.value) return;
    router.patch(route('crm.lead.status', currentLead.value.id), {
        status: pendingMoveNextStage.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showMoveConfirmModal.value = false;
            currentLead.value = null;
            pendingMoveNextStage.value = null;
            router.reload({ only: ['leads'] });
        }
    });
};

// ─────────────────────────────────────────────────
// Finalization Modal Logic
// ─────────────────────────────────────────────────
const openFinalizeModal = (lead) => {
    currentLead.value = lead;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
    showFinalizeModal.value = true;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            return;
        }
        finalizeFile.value = file;
    }
};

const uploadFinalizeFile = () => {
    if (!finalizeFile.value) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', finalizeFile.value);
    router.post(route('crm.lead.upload-file', currentLead.value.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            finalizeFile.value = null;
            isUploading.value = false;
            // Optionally show a success message
            router.reload({ only: ['leads'] });
        },
        onError: () => {
            isUploading.value = false;
            alert('Upload failed');
        }
    });
};

const submitFinalize = () => {
    if (!finalizeAction.value) return;

    if (finalizeAction.value === 'accept') {
        // Accept lead -> move to Closed-Won
        router.post(route('crm.lead.accept', currentLead.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showFinalizeModal.value = false;
                currentLead.value = null;
                router.reload({ only: ['leads'] });
            }
        });
    } else if (finalizeAction.value === 'reject') {
        if (!rejectReason.value.trim()) {
            alert('Please provide a reason for rejection');
            return;
        }
        rejectForm.reject_reason = rejectReason.value;
        rejectForm.post(route('crm.lead.reject', currentLead.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFinalizeModal.value = false;
                currentLead.value = null;
                rejectForm.reset();
                router.reload({ only: ['leads'] });
            }
        });
    }
};

// ─────────────────────────────────────────────────
// Modal Controls (other)
// ─────────────────────────────────────────────────
const openNoteModal = (lead) => {
    currentLead.value = lead;
    noteForm.note = '';
    showNoteModal.value = true;
};

const openInterviewModal = (lead) => {
    currentLead.value = lead;
    interviewForm.reset();
    showInterviewModal.value = true;
};

const closeAllModals = () => {
    showNoteModal.value = false;
    showInterviewModal.value = false;
    showFinalizeModal.value = false;
    showMoveConfirmModal.value = false;
    currentLead.value = null;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
};

// ─────────────────────────────────────────────────
// API Calls (others)
// ─────────────────────────────────────────────────
const addNote = () => {
    noteForm.post(route('crm.lead.add-note', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            noteForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

const scheduleInterview = () => {
    interviewForm.post(route('crm.lead.schedule-interview', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAllModals();
            interviewForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

// Conversion
const openConversionModal = (lead) => {
    conversionForm.lead_id = lead.id;
    conversionForm.company_name = lead.company_name;
    conversionForm.contact_person = lead.contact_person;
    conversionForm.email = lead.email;
    conversionForm.phone = lead.phone;
    showClientConversionModal.value = true;
};

const submitConversion = () => {
    conversionForm.post(route('crm.lead.convert'), {
        preserveScroll: true,
        onSuccess: () => {
            showClientConversionModal.value = false;
            conversionForm.reset();
            router.reload({ only: ['leads'] });
        },
    });
};

const submit = () => {
    form.post(route('crm.lead.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

// ─────────────────────────────────────────────────
// UI Helpers
// ─────────────────────────────────────────────────
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0);
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};

// Collapsible state for notes/interviews/files (but files now in modal)
const showNotes = ref({});
const showInterviews = ref({});

const toggleNotes = (leadId) => { showNotes.value[leadId] = !showNotes.value[leadId]; };
const toggleInterviews = (leadId) => { showInterviews.value[leadId] = !showInterviews.value[leadId]; };
</script>

<template>
    <AuthenticatedLayout title="Lead & Deal Workspace">
        <div
            class="p-4 md:p-6 space-y-6 bg-gradient-to-br from-slate-50 to-white dark:from-zinc-950 dark:to-zinc-900 min-h-screen">
            <!-- Header (unchanged) -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200/80 dark:border-zinc-800 pb-6">
                <div>
                    <h1
                        class="text-2xl md:text-3xl font-black tracking-tight bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                        Lead <span class="text-gray-900 dark:text-white">Pipeline</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage leads in a structured queue – oldest
                        first.</p>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search leads..."
                            class="pl-9 pr-4 py-2 border border-gray-200 dark:border-zinc-700 rounded-xl text-sm bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-blue-500 w-full md:w-64 transition-all" />
                    </div>
                    <button @click="showCreateModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-black uppercase shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all active:scale-95">
                        <Plus class="w-4 h-4" /> Create Deal
                    </button>
                </div>
            </div>

            <!-- Tabs (unchanged) -->
            <div class="border-b border-gray-200 dark:border-zinc-800 overflow-x-auto no-scrollbar">
                <div class="flex space-x-2 min-w-max">
                    <button v-for="stage in stages" :key="stage.status" @click="activeTab = stage.status" :class="[
                        activeTab === stage.status
                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-800',
                        'flex items-center gap-2 py-2 px-4 rounded-full text-sm font-medium transition-all'
                    ]">
                        <component :is="stage.icon" class="w-4 h-4" />
                        {{ stage.label }}
                        <span :class="[
                            'ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold',
                            activeTab === stage.status ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-300'
                        ]">
                            {{ leadsByStatus[stage.status]?.length || 0 }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Lead List (Queue) -->
            <div class="space-y-4">
                <div v-if="currentLeads.length === 0" class="text-center py-16 text-gray-500">
                    <AlertCircle class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <p class="text-base font-medium">No leads in this stage.</p>
                    <p class="text-sm">Create a new deal to start the pipeline.</p>
                </div>

                <div v-for="lead in currentLeads" :key="lead.id"
                    class="group bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-5 relative overflow-hidden">
                    <!-- Gradient top border -->
                    <div
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    </div>

                    <!-- Top row: company and move button (for Inquiry/Negotiation) -->
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ lead.company_name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                {{ lead.contact_person }} · {{ lead.email }}
                            </p>
                        </div>
                        <!-- For Inquiry & Negotiation: move button -->
                        <button v-if="lead.status === 'Inquiry' || lead.status === 'Negotiation'"
                            @click="openMoveConfirm(lead)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold uppercase shadow-md hover:bg-blue-700 transition-all active:scale-95 self-start">
                            <MoveRight class="w-4 h-4" /> Move to {{ getNextStage(lead.status) }}
                        </button>
                        <!-- For Approval Sent: Finalize button -->
                        <button v-if="lead.status === 'Approval Sent'" @click="openFinalizeModal(lead)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold uppercase shadow-md hover:bg-emerald-700 transition-all active:scale-95 self-start">
                            <CheckCircle2 class="w-4 h-4" /> Finalize
                        </button>
                    </div>

                    <!-- Middle: value & fabric -->
                    <div class="flex flex-wrap items-center justify-between mt-3 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900 dark:text-white">{{
                                formatCurrency(lead.estimated_value) }}</span>
                            <span class="text-xs text-gray-400 bg-gray-100 dark:bg-zinc-800 px-2 py-0.5 rounded-full">{{
                                lead.interest_fabric }}</span>
                        </div>
                        <span class="text-xs text-gray-400">#{{ lead.id }}</span>
                    </div>

                    <!-- Stage‑specific actions -->
                    <!-- New Inquiry: Add Note -->
                    <div v-if="lead.status === 'Inquiry'">
                        <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-zinc-800">
                            <button @click="openNoteModal(lead)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-100 transition">
                                <MessageSquare class="w-3.5 h-3.5" /> Add Note
                            </button>
                        </div>
                        <!-- Notes collapsible -->
                        <div v-if="lead.notes && lead.notes.length" class="mt-3">
                            <button @click="toggleNotes(lead.id)"
                                class="text-xs text-gray-500 flex items-center gap-1 hover:text-gray-700">
                                <ChevronDown v-if="!showNotes[lead.id]" class="w-3.5 h-3.5" />
                                <ChevronUp v-else class="w-3.5 h-3.5" />
                                {{ lead.notes.length }} note{{ lead.notes.length !== 1 ? 's' : '' }}
                            </button>
                            <div v-if="showNotes[lead.id]"
                                class="mt-2 space-y-2 pl-2 border-l-2 border-gray-200 dark:border-zinc-700">
                                <div v-for="note in lead.notes" :key="note.id"
                                    class="text-xs bg-gray-50 dark:bg-zinc-800 p-2 rounded-lg">
                                    <p class="text-gray-700 dark:text-gray-300">{{ note.note }}</p>
                                    <p class="text-gray-400 text-[10px] mt-1">{{ note.user.name }} - {{ new
                                        Date(note.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Negotiation: Schedule Interview -->
                    <div v-if="lead.status === 'Negotiation'">
                        <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-zinc-800">
                            <button @click="openInterviewModal(lead)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-100 transition">
                                <Video class="w-3.5 h-3.5" /> Schedule Interview
                            </button>
                        </div>
                        <!-- Interviews collapsible -->
                        <div v-if="lead.interviews && lead.interviews.length" class="mt-3">
                            <button @click="toggleInterviews(lead.id)"
                                class="text-xs text-gray-500 flex items-center gap-1 hover:text-gray-700">
                                <ChevronDown v-if="!showInterviews[lead.id]" class="w-3.5 h-3.5" />
                                <ChevronUp v-else class="w-3.5 h-3.5" />
                                {{ lead.interviews.length }} interview{{ lead.interviews.length !== 1 ? 's' : '' }}
                            </button>
                            <div v-if="showInterviews[lead.id]"
                                class="mt-2 space-y-2 pl-2 border-l-2 border-gray-200 dark:border-zinc-700">
                                <div v-for="iv in lead.interviews" :key="iv.id"
                                    class="text-xs bg-gray-50 dark:bg-zinc-800 p-2 rounded-lg">
                                    <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300">
                                        <Calendar class="w-3 h-3" /> {{ formatDateTime(iv.scheduled_at) }}
                                    </div>
                                    <div v-if="iv.location"
                                        class="flex items-center gap-1 text-gray-600 dark:text-gray-400 mt-1">
                                        <MapPin class="w-3 h-3" /> {{ iv.location }}
                                    </div>
                                    <div v-if="iv.notes" class="italic mt-1 text-gray-600 dark:text-gray-400">{{
                                        iv.notes }}</div>
                                    <p class="text-gray-400 text-[10px] mt-1">{{ iv.user.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Sent: no extra actions here (all inside Finalize modal) -->
                    <div v-if="lead.status === 'Approval Sent' && lead.approval_files && lead.approval_files.length"
                        class="mt-3">
                        <div class="text-xs text-gray-500">
                            {{ lead.approval_files.length }} file{{ lead.approval_files.length !== 1 ? 's' : '' }}
                            attached
                        </div>
                    </div>

                    <!-- Closed-Won -->
                    <div v-if="lead.status === 'Closed-Won'"
                        class="mt-4 pt-3 border-t border-gray-100 dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <div class="text-green-600 text-sm font-medium flex items-center gap-1">
                                <CheckCircle2 class="w-4 h-4" /> Qualified
                            </div>
                            <button @click="openConversionModal(lead)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                                <UserCheck class="w-3.5 h-3.5" /> Create Client Account
                            </button>
                        </div>
                    </div>

                    <!-- Footer: creation date -->
                    <div class="mt-3 text-[10px] text-gray-400 flex justify-end">
                        Created: {{ new Date(lead.created_at).toLocaleDateString() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== MODALS ========== -->
        <!-- Move Confirmation Modal (for Inquiry/Negotiation) -->
        <div v-if="showMoveConfirmModal"
            class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Confirm Stage Change</h3>
                    <button @click="closeAllModals" class="hover:rotate-90 transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-gray-600 dark:text-gray-300">
                        Move <span class="font-bold text-gray-900 dark:text-white">{{ currentLead?.company_name
                            }}</span> from
                        <span class="font-bold text-blue-600">{{ currentLead?.status }}</span> to
                        <span class="font-bold text-emerald-600">{{ pendingMoveNextStage }}</span>?
                    </p>
                    <div class="flex gap-3 pt-2">
                        <button @click="closeAllModals"
                            class="flex-1 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                            Cancel
                        </button>
                        <button @click="confirmMove"
                            class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                            Confirm Move
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note Modal (unchanged) -->
        <div v-if="showNoteModal"
            class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-black uppercase">Add Note</h3>
                    <button @click="closeAllModals" class="hover:rotate-90 transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="addNote" class="p-6 space-y-4">
                    <textarea v-model="noteForm.note" rows="4" placeholder="Write your notes here..." required
                        class="w-full border rounded-xl p-3 text-sm"></textarea>
                    <button type="submit" :disabled="noteForm.processing"
                        class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-black uppercase">
                        {{ noteForm.processing ? 'Saving...' : 'Save Note' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Interview Modal (unchanged) -->
        <div v-if="showInterviewModal"
            class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-black uppercase">Schedule Interview</h3>
                    <button @click="closeAllModals" class="hover:rotate-90 transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="scheduleInterview" class="p-6 space-y-4">
                    <input type="datetime-local" v-model="interviewForm.scheduled_at" required
                        class="w-full border rounded-xl p-3 text-sm" />
                    <input type="text" v-model="interviewForm.location" placeholder="Location (optional)"
                        class="w-full border rounded-xl p-3 text-sm" />
                    <textarea v-model="interviewForm.notes" rows="3" placeholder="Notes (optional)"
                        class="w-full border rounded-xl p-3 text-sm"></textarea>
                    <button type="submit" :disabled="interviewForm.processing"
                        class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-black uppercase">
                        {{ interviewForm.processing ? 'Scheduling...' : 'Schedule' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Finalize Modal (for Approval Sent) -->
        <div v-if="showFinalizeModal"
            class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Finalize Lead</h3>
                    <button @click="closeAllModals" class="hover:rotate-90 transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Complete the process for <span class="font-bold text-gray-900 dark:text-white">{{
                            currentLead?.company_name }}</span>.
                    </p>

                    <!-- File Upload Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Approval
                            File (optional)</label>
                        <div class="flex items-center gap-3">
                            <input type="file" @change="handleFileChange" accept="image/*,application/pdf"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            <button v-if="finalizeFile" @click="uploadFinalizeFile" :disabled="isUploading"
                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition disabled:opacity-50">
                                {{ isUploading ? 'Uploading...' : 'Upload' }}
                            </button>
                        </div>
                    </div>

                    <!-- Accept/Reject Actions -->
                    <div class="flex gap-3">
                        <button @click="finalizeAction = 'accept'" :class="[
                            'flex-1 py-2.5 rounded-xl text-sm font-bold transition',
                            finalizeAction === 'accept'
                                ? 'bg-green-600 text-white shadow-md'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-zinc-800 dark:text-gray-300'
                        ]">
                            <Check class="w-4 h-4 inline mr-1" /> Accept
                        </button>
                        <button @click="finalizeAction = 'reject'" :class="[
                            'flex-1 py-2.5 rounded-xl text-sm font-bold transition',
                            finalizeAction === 'reject'
                                ? 'bg-red-600 text-white shadow-md'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-zinc-800 dark:text-gray-300'
                        ]">
                            <XCircle class="w-4 h-4 inline mr-1" /> Reject
                        </button>
                    </div>

                    <!-- Reject Reason (only shown if reject selected) -->
                    <div v-if="finalizeAction === 'reject'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason for
                            Rejection *</label>
                        <textarea v-model="rejectReason" rows="2" placeholder="Please provide a reason..."
                            class="w-full border border-gray-200 dark:border-zinc-700 rounded-xl p-3 text-sm bg-white dark:bg-zinc-800 focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button @click="submitFinalize"
                        :disabled="!finalizeAction || (finalizeAction === 'reject' && !rejectReason.trim())"
                        class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-bold uppercase shadow-md hover:bg-blue-700 transition disabled:opacity-50">
                        {{ finalizeAction === 'accept' ? 'Confirm & Move to Closed-Won' : 'Confirm & Archive' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Conversion Modal (unchanged) -->
        <div v-if="showClientConversionModal"
            class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div
                class="bg-white dark:bg-zinc-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-200 dark:border-zinc-800">
                <div
                    class="p-8 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-green-50/50 dark:bg-green-900/10">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest italic text-green-700">Promote to
                            Business Client</h3>
                        <p class="text-[9px] font-bold text-gray-400 uppercase mt-1 italic">Finalizing Partnership for
                            {{ conversionForm.company_name }}</p>
                    </div>
                    <button @click="showClientConversionModal = false" class="hover:rotate-90 transition-transform">
                        <X class="w-6 h-6 text-gray-400" />
                    </button>
                </div>
                <form @submit.prevent="submitConversion" class="p-8 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-2">Business Type</label>
                            <select v-model="conversionForm.business_type"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4">
                                <option value="wholesaler">Wholesaler</option>
                                <option value="retailer">Retailer</option>
                                <option value="manufacturer">Manufacturer</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-2">TIN Number</label>
                            <input v-model="conversionForm.tin_number" placeholder="000-000-000" type="text"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black p-4"
                                required />
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-gray-400 uppercase ml-2">Official Company
                            Address</label>
                        <textarea v-model="conversionForm.company_address" rows="3"
                            placeholder="COMPLETE BUSINESS ADDRESS"
                            class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black p-4"
                            required></textarea>
                    </div>
                    <button type="submit" :disabled="conversionForm.processing"
                        class="w-full py-4 bg-green-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl hover:brightness-110 transition-all flex items-center justify-center gap-2">
                        <Building2 class="w-4 h-4" /> {{ conversionForm.processing ? 'Converting...' :
                            'Finalize Of ficialClient' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Create Deal Modal (unchanged) -->
        <div v-if="showCreateModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
            <div
                class="bg-white dark:bg-zinc-900 w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-200 dark:border-zinc-800">
                <div
                    class="p-8 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50 dark:bg-zinc-800/50">
                    <h3 class="text-sm font-black uppercase tracking-widest italic">Initiate New Textile Deal</h3>
                    <button @click="showCreateModal = false" class="hover:rotate-90 transition-transform">
                        <X class="w-6 h-6 text-gray-400" />
                    </button>
                </div>
                <form @submit.prevent="submit" class="p-8 space-y-5">
                    <div class="space-y-4">
                        <input v-model="form.company_name" placeholder="COMPANY NAME" type="text"
                            class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                            required />
                        <input v-model="form.contact_person" placeholder="CONTACT PERSON" type="text"
                            class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                            required />
                        <div class="grid grid-cols-2 gap-4">
                            <input v-model="form.email" placeholder="EMAIL" type="email"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4"
                                required />
                            <input v-model="form.phone" placeholder="PHONE" type="text"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-[10px] font-black uppercase p-4"
                                required />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <input v-model="form.estimated_value" placeholder="VALUE (₱)" type="number"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4"
                                required />
                            <select v-model="form.interest_fabric"
                                class="w-full bg-gray-50 dark:bg-zinc-800 border-none rounded-xl text-xs font-black uppercase p-4">
                                <option>Cotton</option>
                                <option>Wool</option>
                                <option>Nylon</option>
                                <option>Polyester</option>
                                <option>Silk</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-xl hover:brightness-110 transition-all">
                        {{ form.processing ? 'Syncing Pipeline...' : 'Confirm & Initiate Deal' }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Hide scrollbar for tab bar */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Smooth transitions */
.group:hover .absolute {
    transition: opacity 0.3s ease;
}
</style>