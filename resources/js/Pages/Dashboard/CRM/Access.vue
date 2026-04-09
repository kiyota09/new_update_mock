<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Array,
    pages: Array
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const isCEO = computed(() => currentUser.value?.role === 'CEO');
const isCRMmanager = computed(() => currentUser.value?.role === 'CRM' && currentUser.value?.position === 'manager');
const canEdit = computed(() => isCEO.value || isCRMmanager.value);

const permissions = ref({});
props.users.forEach(user => {
    permissions.value[user.id] = [...(user.permissions || [])];
});

const togglePermission = (userId, page) => {
    const idx = permissions.value[userId].indexOf(page);
    if (idx === -1) permissions.value[userId].push(page);
    else permissions.value[userId].splice(idx, 1);
};

const savePermissions = (userId) => {
    router.post(route('crm.access.update'), {
        user_id: userId,
        pages: permissions.value[userId]
    });
};

const isOwnRow = (userId) => userId === currentUser.value?.id;
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">CRM Access Control</h1>
                <p class="text-sm text-gray-500">Manage page permissions for CRM staff and managers.</p>
            </div>

            <div v-if="users.length === 0" class="text-center py-12 text-gray-500">
                No CRM users found.
            </div>

            <div v-for="user in users" :key="user.id" class="bg-white dark:bg-zinc-900 rounded-2xl p-6 mb-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                <div class="flex flex-wrap justify-between items-start gap-4">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ user.name }}</h2>
                        <p class="text-xs text-gray-500">
                            {{ user.role }} · {{ user.position }}
                            <span v-if="user.position === 'manager'" class="ml-2 text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Manager</span>
                        </p>
                    </div>
                    <button 
                        v-if="canEdit && !isOwnRow(user.id)" 
                        @click="savePermissions(user.id)" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition"
                    >
                        Save Permissions
                    </button>
                    <span v-else-if="isOwnRow(user.id) && !isCEO" class="text-xs text-gray-400 italic">(Your own permissions – ask a manager or CEO to change)</span>
                    <span v-else-if="!canEdit" class="text-xs text-gray-400 italic">(Read only)</span>
                </div>

                <div class="mt-4 flex flex-wrap gap-4">
                    <label v-for="page in pages" :key="page" class="flex items-center gap-2 text-sm">
                        <input 
                            type="checkbox" 
                            :checked="permissions[user.id]?.includes(page)" 
                            @change="togglePermission(user.id, page)"
                            :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="capitalize text-gray-700 dark:text-gray-300">{{ page }}</span>
                    </label>
                </div>

                <div v-if="!canEdit && user.position === 'staff'" class="mt-3 text-xs text-amber-600">
                    ⚠️ Only CRM managers or the CEO can change permissions.
                </div>
                <div v-else-if="isOwnRow(user.id) && !isCEO && canEdit" class="mt-3 text-xs text-amber-600">
                    ⚠️ You cannot edit your own permissions. Ask another manager or the CEO.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>