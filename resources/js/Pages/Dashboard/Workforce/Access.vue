<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Shield, Users, Plus, X, Save, Trash2, Edit, Search, UserCog
} from 'lucide-vue-next';

const props = defineProps({
    candidates: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] }
});

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

// Search filter
const searchQuery = ref('');
const filteredCandidates = computed(() => {
    if (!searchQuery.value) return props.candidates;
    const q = searchQuery.value.toLowerCase();
    return props.candidates.filter(c =>
        c.name.toLowerCase().includes(q) ||
        c.role.toLowerCase().includes(q) ||
        c.position.toLowerCase().includes(q)
    );
});

// Modal state
const isPermissionModalOpen = ref(false);
const selectedUser = ref(null);
const permissionsList = ref([]);

const openPermissionModal = (user) => {
    selectedUser.value = user;
    permissionsList.value = (user.permissions || []).map(p => ({
        module: p.module || '',
        department: p.department || '',
        access_level: p.access_level || 'view'
    }));
    if (permissionsList.value.length === 0) addPermissionRow();
    isPermissionModalOpen.value = true;
};

const closePermissionModal = () => {
    isPermissionModalOpen.value = false;
    selectedUser.value = null;
};

const addPermissionRow = () => {
    permissionsList.value.push({ module: '', department: '', access_level: 'view' });
};

const removePermissionRow = (index) => {
    permissionsList.value.splice(index, 1);
};

const removeAllPermissions = (userId) => {
    if (confirm('Are you sure you want to remove all workforce permissions for this user?')) {
        router.post(route('workforce.access.update'), {
            user_id: userId,
            permissions: [] // Sending empty array to clear
        }, { preserveScroll: true });
    }
};

const savePermissions = () => {
    const validPermissions = permissionsList.value.filter(p => p.module && p.access_level);
    router.post(route('workforce.access.update'), {
        user_id: selectedUser.value.id,
        permissions: validPermissions
    }, {
        preserveScroll: true,
        onSuccess: () => closePermissionModal(),
    });
};

const getAccessLevelClass = (level) => {
    switch (level) {
        case 'manage': return 'bg-purple-100 text-purple-700';
        // case 'schedule': return 'bg-blue-100 text-blue-700';
        // case 'view': return 'bg-green-100 text-green-700';
        default: return 'bg-slate-100 text-slate-600';
    }
};

const getInitials = (name) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
</script>

<template>
    <Head title="Workforce Access Control" />
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Workforce <span class="text-blue-600">Access</span>
                </h1>
            </div>

            <div class="relative w-80">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                <input v-model="searchQuery" type="text" placeholder="Search managers..."
                    class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-slate-800 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-blue-600 text-sm" />
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr v-for="user in filteredCandidates" :key="user.id">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-bold text-xs">{{ getInitials(user.name) }}</div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ user.name }}</p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span v-for="(perm, idx) in user.permissions" :key="idx" :class="['px-2 py-0.5 rounded text-[9px] font-bold', getAccessLevelClass(perm.access_level)]">
                                                {{ perm.module }} ({{ perm.access_level }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex gap-2 justify-end">
                                    <button @click="openPermissionModal(user)" class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition"><Edit class="h-4 w-4" /></button>
                                    <button v-if="user.permissions.length > 0" @click="removeAllPermissions(user.id)" class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition"><Trash2 class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="isPermissionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col">
                <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                    <h2 class="text-lg font-black uppercase">Edit Access: {{ selectedUser?.name }}</h2>
                    <button @click="closePermissionModal"><X class="h-5 w-5" /></button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div v-for="(perm, idx) in permissionsList" :key="idx" class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 relative flex flex-col gap-4">
                        <button @click="removePermissionRow(idx)" class="absolute top-2 right-2 text-red-500"><Trash2 class="h-4 w-4" /></button>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <select v-model="perm.module" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-900 border-none text-sm">
                                <option value="">Select Module</option>
                                <option v-for="mod in modules" :key="mod" :value="mod">{{ mod }}</option>
                            </select>
                            <select v-model="perm.access_level" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-900 border-none text-sm">
                                <!-- <option value="view">View</option>
                                <option value="schedule">Schedule</option> -->
                                <option value="manage">Manage</option>
                            </select>
                        </div>
                    </div>
                    <button @click="addPermissionRow" class="w-full py-2 border-2 border-dashed border-slate-200 text-slate-500 rounded-xl font-bold uppercase text-xs hover:bg-slate-50">+ Add Rule</button>
                </div>
                <div class="p-4 border-t border-slate-100 flex gap-3">
                    <button @click="closePermissionModal" class="flex-1 py-2 font-bold text-slate-500">Cancel</button>
                    <button @click="savePermissions" class="flex-1 py-2 bg-blue-600 text-white rounded-xl font-bold">Save Changes</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>