<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Calendar, Clock, Video, MapPin, XCircle, CheckCircle, ExternalLink,
    PlayCircle, CalendarClock, Eye, Edit3, User, Mail, Phone, Briefcase,
    Send, AlertTriangle, X, UserCheck, UserMinus, ArrowRight, MessageSquare
} from 'lucide-vue-next';

const props = defineProps({
    applicants: {
        type: Array,
        default: () => []
    }
});

// Toast notification
const showToast = ref(false);
const toastMessage = ref('');
const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 4000);
};

// Flash messages from server
const page = usePage();
if (page.props.flash?.message) {
    triggerToast(page.props.flash.message);
}

// Modal states
const isScheduleModalOpen = ref(false);
const isPassModalOpen = ref(false);
const isFailModalOpen = ref(false);
const isPassToOtherModalOpen = ref(false);
const selectedApplicant = ref(null);

// Form for scheduling
const scheduleForm = ref({
    scheduled_at: '',
    interview_type: '',
    duration: 45,
    interviewer: '',
    location: '',
    notes: ''
});

// Form for failing
const failReason = ref('');

// Form for passing to other module
const otherModule = ref('');

// Module options for "pass to other"
const modules = [
    { value: 'HRM', label: 'Human Resource' },
    { value: 'ECO', label: 'E-Commerce' },
    { value: 'SCM', label: 'Supply Chain' },
    { value: 'MAN', label: 'Manufacturing' },
    { value: 'PROJ', label: 'Project Management' },
    { value: 'FIN', label: 'Finance' },
    { value: 'LOG', label: 'Logistics' },
    { value: 'IT', label: 'Information Technology' }
];

// Helper functions
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'N/A';
const formatDateTime = (date) => date ? new Date(date).toLocaleString() : 'N/A';
const getInterviewTypeIcon = (type) => {
    const t = type?.toLowerCase();
    if (t === 'phone') return Phone;
    if (t === 'video') return Video;
    if (t === 'onsite') return MapPin;
    return Calendar;
};

// Open modals
const openScheduleModal = (applicant) => {
    selectedApplicant.value = applicant;
    // Pre-fill with existing interview data if any
    scheduleForm.value = {
        scheduled_at: applicant.scheduled_at ? new Date(applicant.scheduled_at).toISOString().slice(0, 16) : '',
        interview_type: applicant.interview_type || '',
        duration: applicant.duration || 45,
        interviewer: applicant.interviewer || '',
        location: applicant.location || '',
        notes: applicant.notes || ''
    };
    isScheduleModalOpen.value = true;
};

const openPassModal = (applicant) => {
    selectedApplicant.value = applicant;
    isPassModalOpen.value = true;
};

const openFailModal = (applicant) => {
    selectedApplicant.value = applicant;
    failReason.value = '';
    isFailModalOpen.value = true;
};

const openPassToOtherModal = (applicant) => {
    selectedApplicant.value = applicant;
    otherModule.value = '';
    isPassToOtherModalOpen.value = true;
};

const closeModals = () => {
    isScheduleModalOpen.value = false;
    isPassModalOpen.value = false;
    isFailModalOpen.value = false;
    isPassToOtherModalOpen.value = false;
    selectedApplicant.value = null;
    scheduleForm.value = { scheduled_at: '', interview_type: '', duration: 45, interviewer: '', location: '', notes: '' };
    failReason.value = '';
    otherModule.value = '';
};

// API calls
const scheduleInterview = () => {
    if (!scheduleForm.value.scheduled_at || !scheduleForm.value.interview_type) {
        triggerToast('Please fill in all required fields.');
        return;
    }
    router.post(route('crm.interview.schedule', selectedApplicant.value.id), scheduleForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast('Interview scheduled successfully.');
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to schedule interview.');
        }
    });
};

const passApplicant = () => {
    router.post(route('crm.interview.pass', selectedApplicant.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedApplicant.value.name} passed interview and became trainee.`);
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to process.');
        }
    });
};

const failApplicant = () => {
    if (!failReason.value.trim()) {
        triggerToast('Please provide a reason for failure.');
        return;
    }
    router.post(route('crm.interview.fail', selectedApplicant.value.id), {
        reason: failReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedApplicant.value.name} failed interview.`);
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to process.');
        }
    });
};

const passToOtherModule = () => {
    if (!otherModule.value) {
        triggerToast('Please select a module to pass to.');
        return;
    }
    router.post(route('crm.interview.pass-to-other', selectedApplicant.value.id), {
        module: otherModule.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`Applicant passed to ${otherModule.value} for interview.`);
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to pass to other module.');
        }
    });
};
</script>

<template>
    <Head title="CRM Interview Management" />

    <AuthenticatedLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="showToast" class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400 dark:text-emerald-600" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    CRM Interview <span class="text-blue-600">Management</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Manage candidates assigned to CRM department for initial interview.
                </p>
            </div>

            <!-- Applicants List -->
            <div v-if="applicants.length === 0" class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 p-12 text-center">
                <div class="inline-flex p-6 bg-slate-100 dark:bg-slate-700 rounded-full mb-4">
                    <Calendar class="h-10 w-10 text-slate-400" />
                </div>
                <h3 class="text-lg font-bold text-slate-600 dark:text-slate-400">No applicants assigned for interview</h3>
                <p class="text-sm text-slate-500 mt-2">When HR accepts an applicant and assigns them to CRM department, they will appear here.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div v-for="applicant in applicants" :key="applicant.id" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden hover:shadow-lg transition-all">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="h-14 w-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center text-xl font-black">
                                    {{ getInitials(applicant.name) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ applicant.name }}</h3>
                                    <p class="text-xs text-slate-500">{{ applicant.email }}</p>
                                    <p class="text-xs font-bold text-blue-600 mt-1">{{ applicant.position_applied }}</p>
                                </div>
                            </div>
                            <div v-if="applicant.scheduled_at" class="text-right">
                                <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">Scheduled</span>
                            </div>
                        </div>

                        <!-- Interview details if scheduled -->
                        <div v-if="applicant.scheduled_at" class="mt-4 p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4 text-blue-500" />
                                <span class="font-medium">{{ formatDateTime(applicant.scheduled_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <component :is="getInterviewTypeIcon(applicant.interview_type)" class="h-4 w-4 text-purple-500" />
                                <span class="capitalize">{{ applicant.interview_type }}</span>
                                <span v-if="applicant.duration" class="text-slate-400">({{ applicant.duration }} min)</span>
                            </div>
                            <div v-if="applicant.location" class="flex items-center gap-2 text-sm">
                                <MapPin class="h-4 w-4 text-orange-500" />
                                <span>{{ applicant.location }}</span>
                            </div>
                            <div v-if="applicant.interviewer" class="flex items-center gap-2 text-sm">
                                <User class="h-4 w-4 text-emerald-500" />
                                <span>Interviewer: {{ applicant.interviewer }}</span>
                            </div>
                            <div v-if="applicant.notes" class="text-sm text-slate-500 mt-2 border-t border-slate-200 pt-2">
                                <p class="font-bold text-xs uppercase tracking-wider">Notes:</p>
                                <p>{{ applicant.notes }}</p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-6 flex flex-wrap gap-2">
                            <button @click="openScheduleModal(applicant)" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-blue-700 transition-all">
                                <Calendar class="h-4 w-4" /> Schedule
                            </button>
                            <button @click="openPassModal(applicant)" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 transition-all">
                                <CheckCircle class="h-4 w-4" /> Pass
                            </button>
                            <button @click="openFailModal(applicant)" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-red-700 transition-all">
                                <XCircle class="h-4 w-4" /> Fail
                            </button>
                            <button @click="openPassToOtherModal(applicant)" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-purple-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-purple-700 transition-all">
                                <ArrowRight class="h-4 w-4" /> Pass to Other
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Interview Modal -->
        <div v-if="isScheduleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-blue-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Schedule Interview</h2>
                    <p class="text-blue-200 text-xs mt-1">For {{ selectedApplicant?.name }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Date & Time *</label>
                        <input type="datetime-local" v-model="scheduleForm.scheduled_at" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-500" required />
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Interview Type *</label>
                        <select v-model="scheduleForm.interview_type" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select type</option>
                            <option value="phone">Phone Screen</option>
                            <option value="technical">Technical Interview</option>
                            <option value="behavioral">Behavioral Interview</option>
                            <option value="onsite">On-site Interview</option>
                            <option value="video">Video Conference</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Duration (min)</label>
                            <select v-model="scheduleForm.duration" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                                <option value="60">60</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Location</label>
                            <input type="text" v-model="scheduleForm.location" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700" placeholder="Office/Meeting link" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Interviewer(s)</label>
                        <input type="text" v-model="scheduleForm.interviewer" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700" placeholder="Name of interviewer(s)" />
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Notes</label>
                        <textarea v-model="scheduleForm.notes" rows="2" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700" placeholder="Additional instructions..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="scheduleInterview" class="flex-1 py-3 bg-blue-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-blue-700 transition-all">Confirm Schedule</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pass Modal -->
        <div v-if="isPassModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden text-center">
                <div class="bg-emerald-600 p-8 text-white">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                        <UserCheck class="h-8 w-8" />
                    </div>
                    <h2 class="text-xl font-black uppercase">Pass Candidate</h2>
                </div>
                <div class="p-6">
                    <p class="text-slate-600 dark:text-slate-300 mb-6">Are you sure you want to pass <strong>{{ selectedApplicant?.name }}</strong>? This will convert them into a trainee of CRM department.</p>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="passApplicant" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-emerald-700 transition-all">Confirm Pass</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fail Modal -->
        <div v-if="isFailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-red-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Fail Candidate</h2>
                    <p class="text-red-200 text-xs mt-1">Provide reason for failure</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Reason *</label>
                        <textarea v-model="failReason" rows="3" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-red-500" placeholder="e.g., Insufficient technical skills..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="failApplicant" :disabled="!failReason.trim()" class="flex-1 py-3 bg-red-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-red-700 transition-all disabled:opacity-50">Confirm Fail</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pass to Other Module Modal -->
        <div v-if="isPassToOtherModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-purple-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Pass to Another Module</h2>
                    <p class="text-purple-200 text-xs mt-1">Select department for further interview</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Select Module *</label>
                        <select v-model="otherModule" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-purple-500">
                            <option value="" disabled>Choose department</option>
                            <option v-for="mod in modules" :key="mod.value" :value="mod.value">{{ mod.label }}</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="passToOtherModule" :disabled="!otherModule" class="flex-1 py-3 bg-purple-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-purple-700 transition-all disabled:opacity-50">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}
</style>