<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ShieldCheck,
    UserCog,
    Database,
    Package,
    Boxes,
    ClipboardList,
    Search,
    Save,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({}), // { user_id: { dashboard: true, materials: true, products: true, bom: true, checker: true } }
    },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const saving = ref(false);
const savingUserId = ref(null);

// Local copy of permissions
const userPerms = ref({});
const initPerms = () => {
    const perms = {};
    props.users.forEach(user => {
        perms[user.id] = {
            dashboard: props.permissions[user.id]?.dashboard || false,
            materials: props.permissions[user.id]?.materials || false,
            products: props.permissions[user.id]?.products || false,
            bom: props.permissions[user.id]?.bom || false,
            checker: props.permissions[user.id]?.checker || false,
        };
    });
    userPerms.value = perms;
};
initPerms();

// Filter users (only secretary, general_manager, manager, supervisor)
const eligibleUsers = computed(() => {
    let users = props.users.filter(u =>
        ['secretary', 'general_manager', 'manager', 'supervisor'].includes(u.position)
    );
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        users = users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    }
    return users;
});

// Toggle a specific permission for a user
const togglePermission = (userId, permKey) => {
    userPerms.value[userId][permKey] = !userPerms.value[userId][permKey];
};

// Save all permissions for a specific user
const saveUserPermissions = (userId) => {
    savingUserId.value = userId;
    saving.value = true;
    router.post(route('inv.access.update'), {
        user_id: userId,
        permissions: userPerms.value[userId],
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            savingUserId.value = null;
        },
    });
};

// Permission labels and icons
const permissionList = [
    { key: 'dashboard', label: 'Dashboard', icon: Database },
    { key: 'materials', label: 'Materials', icon: Package },
    { key: 'products', label: 'Products', icon: Boxes },
    { key: 'bom', label: 'Bill of Materials', icon: ClipboardList },
    { key: 'checker', label: 'Stock Checker', icon: ShieldCheck },
];
</script>

<template>
    <Head title="Inventory Access Control | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Inventory Access Control</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Grant access to Inventory module pages for secretaries, general managers, managers, and supervisors.</p>
                    </div>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search users..."
                            class="pl-9 pr-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 w-64"
                        />
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">User</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Position</th>
                                    <th v-for="perm in permissionList" :key="perm.key" class="px-3 py-3.5 text-[10px] font-black uppercase tracking-wider text-center">
                                        {{ perm.label }}
                                    </th>
                                    <th class="px-5 py-3.5 text-center w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="eligibleUsers.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <UserCog class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold">No eligible users found.</p>
                                    </td>
                                </tr>
                                <tr v-for="user in eligibleUsers" :key="user.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ user.name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="capitalize text-xs font-bold text-slate-600 dark:text-slate-300">{{ user.position?.replace('_', ' ') }}</span>
                                    </td>
                                    <td v-for="perm in permissionList" :key="perm.key" class="px-3 py-4 text-center">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                :checked="userPerms[user.id]?.[perm.key] || false"
                                                @change="togglePermission(user.id, perm.key)"
                                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            />
                                        </label>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            @click="saveUserPermissions(user.id)"
                                            :disabled="saving && savingUserId === user.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-50"
                                        >
                                            <Save class="w-3.5 h-3.5" />
                                            {{ saving && savingUserId === user.id ? 'Saving...' : 'Save' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2">
                        <ShieldCheck class="w-3.5 h-3.5" />
                        Users without any permission cannot access the Inventory module.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>