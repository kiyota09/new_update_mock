<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ShieldCheck, UserCog, UserCog2 } from 'lucide-vue-next';

const props = defineProps({
    staff: Array,
});

const isProcessing = ref(false);

const promoteSupervisor = (user) => {
    if (!user.manufacturing_role) {
        alert('This staff member does not have a manufacturing role assigned yet.');
        return;
    }
    
    if (confirm(`Promote ${user.name} to supervisor of the ${getDepartmentLabel(user.possible_department)}?`)) {
        isProcessing.value = true;
        router.post(route('man.access.assign-supervisor'), {
            user_id: user.id,
            is_supervisor: true,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }
};

const demoteSupervisor = (user) => {
    if (confirm(`Demote ${user.name} from supervisor position?`)) {
        isProcessing.value = true;
        router.post(route('man.access.assign-supervisor'), {
            user_id: user.id,
            is_supervisor: false,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }
};

const formatRoleLabel = (role) => {
    if (!role) return 'Not set';
    return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getDepartmentLabel = (dept) => {
    const labels = {
        knitting: 'Knitting Department',
        dyeing: 'Dyeing Department',
        maintenance: 'Maintenance Department',
    };
    return labels[dept] || dept;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Manufacturing Access Control</h1>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Staff</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Current Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Supervisor Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Assigned Supervisor Roles</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="user in staff" :key="user.id">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                {{ formatRoleLabel(user.manufacturing_role) }}
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="user.is_manufacturing_supervisor" class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Supervisor
                                </span>
                                <span v-else class="text-gray-400 text-sm">Staff</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="user.supervisor_department" class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ getDepartmentLabel(user.supervisor_department) }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="user.is_manufacturing_supervisor" class="flex flex-wrap gap-2">
                                    <span v-for="role in user.supervisor_roles" :key="role.id"
                                        class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                        {{ formatRoleLabel(role.manufacturing_role) }}
                                    </span>
                                </div>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button v-if="!user.is_manufacturing_supervisor && user.manufacturing_role"
                                    @click="promoteSupervisor(user)"
                                    :disabled="isProcessing"
                                    class="px-3 py-1 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 flex items-center gap-1">
                                    <UserCog2 class="w-4 h-4" />
                                    Promote
                                </button>
                                <button v-else-if="user.is_manufacturing_supervisor"
                                    @click="demoteSupervisor(user)"
                                    :disabled="isProcessing"
                                    class="px-3 py-1 rounded-lg text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 flex items-center gap-1">
                                    <UserCog class="w-4 h-4" />
                                    Demote
                                </button>
                                <span v-else class="text-gray-400 text-sm italic">No role</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>