<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Shield, CheckCircle, X, Save, User, Mail, Lock, Unlock
} from 'lucide-vue-next';

const props = defineProps({
    staff: {
        type: Array,
        default: () => []
    },
    pages: {
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

// Local state to track permissions per staff (checkboxes)
const staffPermissions = ref({});
// Track which staff are being saved to show loading state
const savingStaff = ref({});

// Initialize permissions from props
const initPermissions = () => {
    props.staff.forEach(staff => {
        staffPermissions.value[staff.id] = [...(staff.permissions || [])];
    });
};
initPermissions();

// Helper to check if a staff has a specific page permission
const hasPermission = (staffId, page) => {
    return staffPermissions.value[staffId]?.includes(page) || false;
};

// Toggle permission for a staff
const togglePermission = (staffId, page) => {
    const current = staffPermissions.value[staffId] || [];
    if (current.includes(page)) {
        staffPermissions.value[staffId] = current.filter(p => p !== page);
    } else {
        staffPermissions.value[staffId] = [...current, page];
    }
};

// Save permissions for a staff
const savePermissions = (staffId) => {
    savingStaff.value[staffId] = true;
    router.post(route('hrm.access.update'), {
        user_id: staffId,
        pages: staffPermissions.value[staffId] || []
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast('Permissions updated successfully.');
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to update permissions.');
        },
        onFinish: () => {
            savingStaff.value[staffId] = false;
        }
    });
};

// Get badge color for pages (optional)
const getPageIcon = (page) => {
    const icons = {
        dashboard: '📊',
        employee: '👥',
        application: '📝',
        interview: '🎤',
        trainee: '🎓',
        onboarding: '🚀',
        reject: '❌',
        payroll: '💰',
        analytics: '📈',
        access: '🔒'
    };
    return icons[page] || '📄';
};
</script>

<template>

    <Head title="Access Control" />

    <AuthenticatedLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-2xl border border-white/10">
                <CheckCircle class="h-5 w-5 text-emerald-400 dark:text-emerald-600" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Access <span class="text-blue-600">Control</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    Assign page-level permissions to staff members.
                </p>
            </div>

            <!-- Info Card -->
            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 flex items-start gap-3">
                <Shield class="h-5 w-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-bold text-blue-800 dark:text-blue-300">Module Manager Access</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">As a manager, you have full access to all
                        pages in this module. Use this panel to grant specific pages to staff members. Staff will only
                        see the pages you enable here.</p>
                </div>
            </div>

            <!-- Staff List -->
            <div v-if="staff.length === 0"
                class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 p-12 text-center">
                <div class="inline-flex p-6 bg-slate-100 dark:bg-slate-700 rounded-full mb-4">
                    <User class="h-10 w-10 text-slate-400" />
                </div>
                <h3 class="text-lg font-bold text-slate-600 dark:text-slate-400">No staff found</h3>
                <p class="text-sm text-slate-500 mt-2">Staff members with the role of your module will appear here once
                    hired.</p>
            </div>

            <div v-else class="space-y-8">
                <div v-for="staffMember in staff" :key="staffMember.id"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div
                        class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-12 w-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-black text-lg">
                                {{ staffMember.name.charAt(0) }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ staffMember.name }}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <Mail class="h-3 w-3" /> {{ staffMember.email }}
                                    </span>
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <User class="h-3 w-3" /> {{ staffMember.role }}
                                    </span>
                                </div>
                            </div>
                            <button @click="savePermissions(staffMember.id)" :disabled="savingStaff[staffMember.id]"
                                class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-black uppercase shadow-md hover:bg-blue-700 transition-all disabled:opacity-50 flex items-center gap-2">
                                <Save class="h-4 w-4" />
                                {{ savingStaff[staffMember.id] ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <div v-for="page in pages" :key="page" class="flex items-center gap-2">
                                <input type="checkbox" :id="`${staffMember.id}_${page}`"
                                    :checked="hasPermission(staffMember.id, page)"
                                    @change="togglePermission(staffMember.id, page)"
                                    class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <label :for="`${staffMember.id}_${page}`"
                                    class="text-sm font-medium text-slate-700 dark:text-slate-300 capitalize flex items-center gap-1">
                                    <span class="text-base">{{ getPageIcon(page) }}</span>
                                    {{ page }}
                                </label>
                            </div>
                        </div>
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