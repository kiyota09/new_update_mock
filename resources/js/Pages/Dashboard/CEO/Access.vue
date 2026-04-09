<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ShieldCheck, Save, UserCog, Info, CheckCircle, AlertCircle, Zap, Crown, UserCheck, Loader2, Lock, Factory } from 'lucide-vue-next';

const props = defineProps({
    users: Array,
    allModules: Array,
});

const selectedModules = ref({});
const savingUsers = ref(new Set());
const showSuccessModal = ref(false);
const successMessage = ref('');
const savedUserName = ref('');

const getRootModule = (user) => user.root_module || null;
const getAssignableModules = (user) => user.assignable_modules || [];

// Initialize selected modules for each user
props.users.forEach(user => {
    let modules = [...(user.granted_modules || [])];
    const isElevated = user.position === 'secretary' || user.position === 'general_manager' || user.is_manufacturing_supervisor;
    
    if (isElevated) {
        const rootModule = getRootModule(user);
        if (rootModule && !modules.includes(rootModule)) {
            modules.unshift(rootModule);
        }
    }
    selectedModules.value[user.id] = modules;
});

const updatePosition = (userId, newPosition) => {
    if (confirm(`Are you sure you want to change position to ${newPosition}?`)) {
        router.post(route('ceo.access.updatePosition'), {
            user_id: userId,
            position: newPosition,
        }, {
            preserveScroll: true,
            onSuccess: () => location.reload(),
        });
    }
};

const saveModules = (userId) => {
    if (savingUsers.value.has(userId)) return;
    
    const user = props.users.find(u => u.id === userId);
    const isElevated = user?.position === 'secretary' || user?.position === 'general_manager' || user?.is_manufacturing_supervisor;
    if (!isElevated) return;
    
    let modulesToSave = [...selectedModules.value[userId]];
    const rootModule = getRootModule(user);
    
    if (rootModule && !modulesToSave.includes(rootModule)) {
        modulesToSave.push(rootModule);
        selectedModules.value[userId] = modulesToSave;
    }
    
    savingUsers.value.add(userId);
    
    router.post(route('ceo.access.updateModules'), {
        user_id: userId,
        modules: modulesToSave,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const userObj = props.users.find(u => u.id === userId);
            savedUserName.value = userObj ? userObj.name : 'User';
            successMessage.value = `Module permissions saved successfully for ${savedUserName.value}!`;
            showSuccessModal.value = true;
            setTimeout(() => {
                if (showSuccessModal.value) closeSuccessModal();
            }, 3000);
        },
        onError: (errors) => {
            console.error('Save error:', errors);
            successMessage.value = 'Error saving module permissions. Please try again.';
            showSuccessModal.value = true;
            setTimeout(() => {
                if (showSuccessModal.value) closeSuccessModal();
            }, 3000);
        },
        onFinish: () => {
            savingUsers.value.delete(userId);
        }
    });
};

const closeSuccessModal = () => {
    showSuccessModal.value = false;
    successMessage.value = '';
    savedUserName.value = '';
};

const getModuleName = (key) => {
    const mod = props.allModules.find(m => m.key === key);
    return mod ? mod.name : key;
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
};

const getAvatarColor = (name) => {
    const colors = [
        'from-violet-500 to-purple-600',
        'from-blue-500 to-indigo-600',
        'from-emerald-500 to-teal-600',
        'from-rose-500 to-pink-600',
        'from-amber-500 to-orange-600',
        'from-cyan-500 to-blue-600',
    ];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
};

const isSaving = (userId) => savingUsers.value.has(userId);

const isRootModule = (user, moduleKey) => {
    if (!user) return false;
    const isElevated = user.position === 'secretary' || user.position === 'general_manager' || user.is_manufacturing_supervisor;
    if (!isElevated) return false;
    const root = getRootModule(user);
    return root === moduleKey;
};

const getDisplayModules = (user) => {
    const assignable = getAssignableModules(user);
    return props.allModules.filter(mod => assignable.includes(mod.key));
};

const getPositionDisplay = (user) => {
    if (user.display_position === 'manufacturing_supervisor') {
        return 'Manufacturing Supervisor';
    }
    if (user.position === 'general_manager') return 'General Manager';
    if (user.position === 'secretary') return 'Secretary';
    return 'Manager';
};

const getPositionIcon = (user) => {
    if (user.display_position === 'manufacturing_supervisor') return Factory;
    if (user.position === 'general_manager') return Crown;
    if (user.position === 'secretary') return UserCheck;
    return UserCog;
};

const canPromoteDemote = (user) => {
    // Manufacturing supervisors cannot be promoted/demoted via position buttons
    return !user.is_manufacturing_supervisor;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="CEO Access Control" />

        <div class="min-h-screen bg-[#f0f2f8] p-4 sm:p-8 font-['Sora',_sans-serif]">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-700 rounded-2xl p-6 sm:p-8 shadow-xl shadow-indigo-200">
                    <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/5 border border-white/10"></div>
                    <div class="absolute -bottom-14 -right-4 w-64 h-64 rounded-full bg-white/5 border border-white/10"></div>
                    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white/20 backdrop-blur-sm p-2 rounded-xl">
                                    <ShieldCheck class="w-6 h-6 text-white" />
                                </div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Promotion Manager</h1>
                            </div>
                            <p class="text-indigo-100 text-sm leading-relaxed max-w-xl">
                                Manage module permissions for Manufacturing Supervisors, Secretaries, and General Managers.
                                Core modules (🔒) are locked. Feature modules can be assigned.
                            </p>
                        </div>
                        <div class="bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl px-4 py-3 text-white text-sm font-medium whitespace-nowrap">
                            {{ users.length }} {{ users.length === 1 ? 'User' : 'Users' }} Listed
                        </div>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="flex items-start gap-3 bg-indigo-50 border border-indigo-200 rounded-xl px-5 py-4 text-indigo-800 text-sm">
                    <div class="bg-indigo-100 rounded-lg p-1.5 flex-shrink-0 mt-0.5">
                        <Info class="w-4 h-4 text-indigo-600" />
                    </div>
                    <div class="leading-relaxed">
                        <span class="font-semibold">Module Access Rules:</span> 
                        <strong>Manufacturing Supervisors</strong> automatically keep the <strong>MAN</strong> module (locked). They can be assigned any feature module.
                        <strong>Core managers</strong> (HRM, MAN, LOG) promoted to Secretary/GM retain their original core module and can be assigned feature modules.
                        <strong>CRM</strong> is exclusive to CEO.
                    </div>
                </div>

                <!-- User Cards -->
                <div class="space-y-4">
                    <div
                        v-for="user in users"
                        :key="user.id"
                        class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all duration-300 overflow-hidden"
                    >
                        <!-- Card Top -->
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4 px-5 py-5 border-b border-gray-100">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <div :class="`bg-gradient-to-br ${getAvatarColor(user.name)} flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md`">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500 truncate">{{ user.email }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">Role: <span class="font-medium text-gray-500">{{ user.role }}</span></div>
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1.5">Position</div>
                                <span :class="{
                                    'bg-blue-50 text-blue-700 border-blue-200 ring-blue-100': user.position === 'manager' && !user.is_manufacturing_supervisor,
                                    'bg-violet-50 text-violet-700 border-violet-200 ring-violet-100': user.position === 'secretary',
                                    'bg-indigo-50 text-indigo-700 border-indigo-200 ring-indigo-100': user.position === 'general_manager',
                                    'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-100': user.is_manufacturing_supervisor,
                                }" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border ring-1">
                                    <component :is="getPositionIcon(user)" class="w-3.5 h-3.5" />
                                    {{ getPositionDisplay(user) }}
                                </span>
                            </div>

                            <div class="flex-shrink-0" v-if="canPromoteDemote(user)">
                                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1.5">Promote / Demote</div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        @click="updatePosition(user.id, 'secretary')"
                                        :disabled="user.position === 'secretary'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-violet-200 text-violet-700 bg-violet-50 hover:bg-violet-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-150"
                                    >
                                        <UserCheck class="w-3.5 h-3.5" /> Secretary
                                    </button>
                                    <button
                                        @click="updatePosition(user.id, 'general_manager')"
                                        :disabled="user.position === 'general_manager'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-150"
                                    >
                                        <Crown class="w-3.5 h-3.5" /> General Manager
                                    </button>
                                    <button
                                        @click="updatePosition(user.id, 'manager')"
                                        :disabled="user.position === 'manager' || !user.position"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 bg-gray-50 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-150"
                                    >
                                        <UserCog class="w-3.5 h-3.5" /> Demote to Manager
                                    </button>
                                </div>
                            </div>
                            <div v-else class="flex-shrink-0 text-xs text-gray-400 italic">
                                Supervisor – position fixed
                            </div>
                        </div>

                        <!-- Card Bottom: Module Access -->
                        <div class="px-5 py-4">
                            <div v-if="user.position === 'secretary' || user.position === 'general_manager' || user.is_manufacturing_supervisor">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                        <ShieldCheck class="w-4 h-4 text-indigo-500" />
                                        Module Permissions
                                        <span v-if="getRootModule(user)" class="inline-flex items-center gap-1 text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                            <Lock class="w-3 h-3" /> Core: {{ getModuleName(getRootModule(user)) }}
                                        </span>
                                    </div>
                                    <div v-if="selectedModules[user.id].length === 0"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                        <Zap class="w-3.5 h-3.5" />
                                        Fallback: auto access to {{ user.role }}
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                        <CheckCircle class="w-3.5 h-3.5" />
                                        {{ selectedModules[user.id].length }} module{{ selectedModules[user.id].length > 1 ? 's' : '' }} selected
                                    </div>
                                </div>

                                <!-- Modules Grid: only show assignable modules -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-2 mb-4">
                                    <label
                                        v-for="mod in getDisplayModules(user)"
                                        :key="mod.key"
                                        class="relative flex items-center gap-2.5 px-3 py-2.5 rounded-xl border cursor-pointer transition-all duration-150 select-none"
                                        :class="[
                                            selectedModules[user.id].includes(mod.key)
                                                ? 'bg-indigo-50 border-indigo-300 text-indigo-800 shadow-sm'
                                                : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 hover:border-gray-300',
                                            isRootModule(user, mod.key) ? 'ring-2 ring-indigo-400 bg-indigo-100/50 cursor-not-allowed opacity-90' : ''
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="mod.key"
                                            v-model="selectedModules[user.id]"
                                            :disabled="isRootModule(user, mod.key)"
                                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 flex-shrink-0"
                                        />
                                        <span class="text-xs font-medium truncate">{{ mod.name }}</span>
                                        <Lock v-if="isRootModule(user, mod.key)" class="w-3 h-3 text-indigo-500 ml-auto flex-shrink-0" />
                                    </label>
                                </div>

                                <!-- Save Button -->
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="saveModules(user.id)"
                                        :disabled="isSaving(user.id)"
                                        class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition-all duration-150 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <Loader2 v-if="isSaving(user.id)" class="w-4 h-4 animate-spin" />
                                        <Save v-else class="w-4 h-4" />
                                        {{ isSaving(user.id) ? 'Saving...' : 'Save Modules' }}
                                    </button>
                                    <span class="text-xs text-gray-400">
                                        Changes apply immediately. Core module (🔒) is permanent.
                                    </span>
                                </div>
                            </div>

                            <div v-else class="flex items-center gap-2 text-sm text-gray-400 italic py-1">
                                <AlertCircle class="w-4 h-4 text-gray-300 flex-shrink-0" />
                                Module access is only configurable for Manufacturing Supervisors, Secretaries, or General Managers.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!users || users.length === 0" class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <UserCog class="w-7 h-7 text-gray-400" />
                    </div>
                    <p class="text-gray-500 font-medium">No users found.</p>
                    <p class="text-sm text-gray-400 mt-1">There are currently no managers, secretaries, or manufacturing supervisors available to configure.</p>
                </div>

                <!-- Success Modal -->
                <div v-if="showSuccessModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeSuccessModal"></div>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <CheckCircle class="h-6 w-6 text-emerald-600" />
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg leading-6 font-semibold text-gray-900">Success!</h3>
                                        <div class="mt-2"><p class="text-sm text-gray-500">{{ successMessage }}</p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button @click="closeSuccessModal" type="button" class="inline-flex justify-center w-full rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 text-base font-medium text-white hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Got it</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap');
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-spin { animation: spin 1s linear infinite; }
</style>