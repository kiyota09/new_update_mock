<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Award, Star, CheckCircle, XCircle, User, Mail, Calendar, TrendingUp,
    Eye, Edit, RotateCcw, UserCheck, UserMinus, X, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    trainees: {
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
if (page.props.flash?.error) {
    triggerToast(page.props.flash.error);
}

// Modal states
const isGradeModalOpen = ref(false);
const isPassModalOpen = ref(false);
const isFailModalOpen = ref(false);
const selectedTrainee = ref(null);
const failReason = ref('');

// Grade form
const gradeForm = ref({
    skills_performance: 0,
    behaviour: 0,
    technicals: 0,
    safety_awareness: 0,
    productivity: 0
});

// Criteria list for grading
const criteria = [
    { id: 'skills_performance', label: 'Skills Performance' },
    { id: 'behaviour', label: 'Behaviour' },
    { id: 'technicals', label: 'Technicals' },
    { id: 'safety_awareness', label: 'Safety Awareness' },
    { id: 'productivity', label: 'Productivity' }
];

// Computed grade total
const gradeTotal = computed(() => {
    if (!selectedTrainee.value) return 0;
    const sum = criteria.reduce((acc, c) => acc + (gradeForm.value[c.id] || 0), 0);
    return (sum / 25) * 100;
});
const isPassing = computed(() => gradeTotal.value >= 80);

// Helper functions
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'N/A';
const getGradeColor = (percentage) => {
    if (percentage >= 80) return 'text-emerald-600';
    if (percentage >= 60) return 'text-blue-600';
    if (percentage >= 40) return 'text-amber-600';
    return 'text-red-600';
};

// Open modals
const openGradeModal = (trainee) => {
    selectedTrainee.value = trainee;
    // Pre-fill existing grade if any
    if (trainee.trainee_grade) {
        gradeForm.value = {
            skills_performance: trainee.trainee_grade.skills_performance || 0,
            behaviour: trainee.trainee_grade.behaviour || 0,
            technicals: trainee.trainee_grade.technicals || 0,
            safety_awareness: trainee.trainee_grade.safety_awareness || 0,
            productivity: trainee.trainee_grade.productivity || 0
        };
    } else {
        gradeForm.value = { skills_performance: 0, behaviour: 0, technicals: 0, safety_awareness: 0, productivity: 0 };
    }
    isGradeModalOpen.value = true;
};

const openPassModal = (trainee) => {
    selectedTrainee.value = trainee;
    isPassModalOpen.value = true;
};

const openFailModal = (trainee) => {
    selectedTrainee.value = trainee;
    failReason.value = '';
    isFailModalOpen.value = true;
};

const closeModals = () => {
    isGradeModalOpen.value = false;
    isPassModalOpen.value = false;
    isFailModalOpen.value = false;
    selectedTrainee.value = null;
    gradeForm.value = { skills_performance: 0, behaviour: 0, technicals: 0, safety_awareness: 0, productivity: 0 };
    failReason.value = '';
};

// Actions
const submitGrade = () => {
    if (criteria.some(c => gradeForm.value[c.id] === 0)) {
        triggerToast('Please rate all criteria before submitting.');
        return;
    }
    router.post(route('hrm.trainee.grade', selectedTrainee.value.id), gradeForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to save grade.');
        }
    });
};

const passTrainee = () => {
    router.post(route('hrm.trainee.pass', selectedTrainee.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to approve trainee.');
        }
    });
};

const failTrainee = () => {
    if (!failReason.value.trim()) {
        triggerToast('Please provide a reason for failure.');
        return;
    }
    router.post(route('hrm.trainee.fail', selectedTrainee.value.id), {
        reason: failReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to process failure.');
        }
    });
};
</script>

<template>

    <Head title="Trainee Management" />

    <AuthenticatedLayout>
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400 dark:text-emerald-600" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Trainee <span class="text-blue-600">Development</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Grade your department's trainees and approve them for HR conversion.
                </p>
            </div>

            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Active Trainees</p>
                        <p class="text-3xl font-black">{{ trainees.length }}</p>
                    </div>
                    <Award class="h-12 w-12 opacity-50" />
                </div>
            </div>

            <div v-if="trainees.length === 0"
                class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 p-12 text-center">
                <div class="inline-flex p-6 bg-slate-100 dark:bg-slate-700 rounded-full mb-4">
                    <User class="h-10 w-10 text-slate-400" />
                </div>
                <h3 class="text-lg font-bold text-slate-600 dark:text-slate-400">No trainees assigned</h3>
                <p class="text-sm text-slate-500 mt-2">When applicants pass the interview for your department, they will
                    appear here.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="trainee in trainees" :key="trainee.id"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden hover:shadow-lg transition-all">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 flex items-center justify-center text-xl font-black">
                                    {{ getInitials(trainee.name) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ trainee.name }}</h3>
                                    <p class="text-xs text-slate-500">{{ trainee.email }}</p>
                                    <p class="text-xs font-bold text-blue-600 mt-1">{{ trainee.role }} Department</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Joined</p>
                                <p class="text-sm font-semibold">{{ formatDate(trainee.join_date) }}</p>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Overall Grade</p>
                                <p
                                    :class="['text-sm font-black', getGradeColor(trainee.trainee_grade?.total_percentage || 0)]">
                                    {{ trainee.trainee_grade?.total_percentage || 0 }}%
                                </p>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" :class="[
                                    (trainee.trainee_grade?.total_percentage || 0) >= 80 ? 'bg-emerald-500' :
                                        (trainee.trainee_grade?.total_percentage || 0) >= 60 ? 'bg-blue-500' :
                                            (trainee.trainee_grade?.total_percentage || 0) >= 40 ? 'bg-amber-500' : 'bg-red-500'
                                ]" :style="{ width: `${trainee.trainee_grade?.total_percentage || 0}%` }"></div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <button @click="openGradeModal(trainee)"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-amber-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-amber-700 transition-all">
                                <Star class="h-4 w-4" /> Grade
                            </button>
                            <button v-if="trainee.trainee_grade?.total_percentage >= 80" @click="openPassModal(trainee)"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 transition-all">
                                <UserCheck class="h-4 w-4" /> Approve & Notify HR
                            </button>
                            <button v-else @click="openFailModal(trainee)"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-red-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-red-700 transition-all">
                                <UserMinus class="h-4 w-4" /> Fail
                            </button>
                        </div>
                        <p v-if="trainee.trainee_grade?.total_percentage < 80"
                            class="mt-3 text-xs text-center text-amber-600 font-medium">
                            Needs at least 80% to be eligible for HR conversion.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isGradeModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
            @click.self="closeModals">
            <div
                class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="bg-amber-600 p-6 text-white sticky top-0">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-black uppercase">Coursework Grading</h2>
                        <button @click="closeModals" class="text-white/70 hover:text-white">&times;</button>
                    </div>
                    <p class="text-amber-200 text-sm mt-1">Rate {{ selectedTrainee?.name }} on each criterion</p>
                </div>
                <div class="p-6 space-y-6">
                    <div v-for="criterion in criteria" :key="criterion.id">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">{{ criterion.label }}
                        </p>
                        <div class="flex gap-2">
                            <button v-for="star in 5" :key="star" type="button" @click="gradeForm[criterion.id] = star"
                                class="focus:outline-none transition-transform active:scale-110">
                                <Star :class="[
                                    gradeForm[criterion.id] >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-200 dark:text-slate-600',
                                    'h-7 w-7 transition-colors cursor-pointer'
                                ]" />
                            </button>
                        </div>
                    </div>

                    <div v-if="gradeTotal > 0" class="mt-6 p-4 rounded-2xl border-2 transition-all"
                        :class="isPassing ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-200 bg-red-50 dark:bg-red-900/20'">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Average
                                </p>
                                <p
                                    :class="['text-3xl font-black mt-0.5', isPassing ? 'text-emerald-600' : 'text-red-500']">
                                    {{ gradeTotal.toFixed(1) }}%
                                </p>
                            </div>
                            <div v-if="isPassing"
                                class="flex items-center gap-2 px-3 py-2 bg-emerald-100 text-emerald-700 rounded-xl">
                                <CheckCircle class="h-4 w-4" />
                                <span class="text-xs font-black uppercase">Ready for HR</span>
                            </div>
                            <div v-else class="flex items-center gap-2 px-3 py-2 bg-red-100 text-red-600 rounded-xl">
                                <XCircle class="h-4 w-4" />
                                <span class="text-xs font-black uppercase">Below threshold</span>
                            </div>
                        </div>
                        <p v-if="isPassing" class="text-xs text-emerald-600 mt-2 font-medium">✓ This trainee is now
                            eligible to be approved and sent to HR.</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button @click="closeModals"
                            class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="submitGrade" :disabled="!gradeTotal"
                            class="flex-1 py-3 bg-amber-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-amber-700 transition-all disabled:opacity-50">
                            Save Grades
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isPassModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
            @click.self="closeModals">
            <div
                class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden text-center">
                <div class="bg-emerald-600 p-8 text-white">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                        <UserCheck class="h-8 w-8" />
                    </div>
                    <h2 class="text-xl font-black uppercase">Approve Trainee</h2>
                </div>
                <div class="p-6">
                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        Approve <strong>{{ selectedTrainee?.name }}'s</strong> departmental training? <br /><br />
                        HR will be notified to officially convert them to Staff. They will remain on your list until HR
                        completes the conversion.
                    </p>
                    <div class="flex gap-3">
                        <button @click="closeModals"
                            class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="passTrainee"
                            class="flex-1 py-3 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-emerald-700 transition-all">Confirm
                            Approval</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isFailModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
            @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-red-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Fail Trainee</h2>
                    <p class="text-red-200 text-xs mt-1">Provide reason for failure</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Reason
                            *</label>
                        <textarea v-model="failReason" rows="3"
                            class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-red-500"
                            placeholder="e.g., Failed to meet performance standards..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModals"
                            class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="failTrainee" :disabled="!failReason.trim()"
                            class="flex-1 py-3 bg-red-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-red-700 transition-all disabled:opacity-50">Confirm
                            Fail</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}
</style>