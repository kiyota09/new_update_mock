<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';

const props = defineProps({ users: Array });

const toggleAccess = (userId, currentAccess) => {
    router.post(route('scm.access.update'), { user_id: userId, can_access_scm: !currentAccess }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Access Control" />
        <div class="max-w-4xl mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">SCM Module Access Control</h1>
            <div class="bg-white dark:bg-gray-800 rounded-lg border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="p-4 text-left">Name</th><th class="p-4 text-left">Position</th><th class="p-4 text-center">Access</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-t">
                            <td class="p-4">{{ user.name }}<br><span class="text-xs text-gray-500">{{ user.email }}</span></td>
                            <td class="p-4">{{ user.position }}</td>
                            <td class="p-4 text-center">
                                <button @click="toggleAccess(user.id, user.can_access_scm)" :class="user.can_access_scm ? 'bg-green-600' : 'bg-gray-300'" class="relative inline-flex h-6 w-11 items-center rounded-full transition">
                                    <span :class="user.can_access_scm ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>