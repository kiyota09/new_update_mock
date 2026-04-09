<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    assignedRoles: Array,
    activeRole: String,
});

const selectedRole = ref(props.activeRole || '');

const switchRole = () => {
    if (!selectedRole.value) return;
    router.post(route('man.supervisor.switch'), { role: selectedRole.value });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto py-12">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl p-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Manufacturing Supervisor
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    You have been assigned multiple roles. Select which role you want to work as.
                </p>

                <select v-model="selectedRole"
                    class="w-full max-w-xs mx-auto border border-gray-300 dark:border-zinc-700 rounded-lg p-3 mb-6">
                    <option value="">-- Choose a role --</option>
                    <option v-for="role in assignedRoles" :key="role" :value="role">
                        {{ role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                    </option>
                </select>

                <button @click="switchRole" :disabled="!selectedRole"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold disabled:opacity-50">
                    Switch to this role
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>