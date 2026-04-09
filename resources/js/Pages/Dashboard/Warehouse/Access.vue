<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ShieldCheck,
    UserCog,
    Warehouse,
    CheckCircle,
    XCircle,
    Save,
    RefreshCw,
    Search,
    ChevronDown,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    userWarehouseAccess: {
        type: Object,
        default: () => ({}),
    },
    auth: Object,
});

// State
const searchQuery = ref('');
const saving = ref(false);
const savingUserId = ref(null);

// Filtered users (only secretary, general_manager, manager, supervisor)
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

// Local copy of access grants (warehouse ids per user)
const userAccess = ref({});
const initAccess = () => {
    const access = {};
    eligibleUsers.value.forEach(user => {
        access[user.id] = {
            granted: !!props.userWarehouseAccess[user.id]?.can_access,
            warehouse_ids: props.userWarehouseAccess[user.id]?.warehouse_ids || [],
        };
    });
    userAccess.value = access;
};
initAccess();

// Toggle grant for a user
const toggleGrant = (userId, granted) => {
    userAccess.value[userId].granted = granted;
    if (!granted) {
        userAccess.value[userId].warehouse_ids = [];
    }
};

// Update warehouse selection (multiselect)
const updateWarehouses = (userId, warehouseId, event) => {
    const current = userAccess.value[userId].warehouse_ids;
    if (event.target.checked) {
        if (!current.includes(warehouseId)) {
            current.push(warehouseId);
        }
    } else {
        const index = current.indexOf(warehouseId);
        if (index !== -1) current.splice(index, 1);
    }
};

// Save permissions for a specific user
const saveUserAccess = (userId) => {
    const data = userAccess.value[userId];
    savingUserId.value = userId;
    saving.value = true;
    router.post(route('warehouse.access.update'), {
        user_id: userId,
        grant: data.granted,
        warehouse_ids: data.warehouse_ids,
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            savingUserId.value = null;
        },
    });
};

// Get warehouse name by id
const getWarehouseName = (id) => {
    const wh = props.warehouses.find(w => w.id === id);
    return wh ? wh.name : 'Unknown';
};
</script>

<template>
    <Head title="Warehouse Access Control | Monti Textile" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Warehouse Access Control</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Grant warehouse access to secretaries, general managers, managers, and supervisors.</p>
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
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">User</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Position</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Access</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Assigned Warehouses</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="eligibleUsers.length === 0">
                                    <td colspan="5" class="px-5 py-16 text-center text-slate-400">
                                        <UserCog class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold text-slate-500">No eligible users found.</p>
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
                                    <td class="px-5 py-4">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="userAccess[user.id]?.granted || false"
                                                @change="toggleGrant(user.id, $event.target.checked)"
                                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                {{ userAccess[user.id]?.granted ? 'Granted' : 'Denied' }}
                                            </span>
                                        </label>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div v-if="userAccess[user.id]?.granted" class="space-y-1">
                                            <div v-for="wh in warehouses" :key="wh.id" class="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    :id="`wh_${user.id}_${wh.id}`"
                                                    :value="wh.id"
                                                    :checked="userAccess[user.id]?.warehouse_ids.includes(wh.id)"
                                                    @change="updateWarehouses(user.id, wh.id, $event)"
                                                    class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600"
                                                />
                                                <label :for="`wh_${user.id}_${wh.id}`" class="text-xs text-slate-600 dark:text-slate-400">
                                                    {{ wh.name }} ({{ wh.location }})
                                                </label>
                                            </div>
                                            <div v-if="warehouses.length === 0" class="text-xs text-slate-400 italic">No warehouses available.</div>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">Access denied – no warehouses assigned.</div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            @click="saveUserAccess(user.id)"
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

                    <!-- Footer note -->
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 flex items-center gap-2">
                        <ShieldCheck class="w-3.5 h-3.5" />
                        Only users with granted access can view and manage the selected warehouses.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>