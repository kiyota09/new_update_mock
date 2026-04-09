<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Order Management – Access Control</h1>
            <p class="text-gray-500 mb-6">Grant or revoke access to the ORD module for secretaries, general managers,
                and SCM managers.</p>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role / Position
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Access</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="user in users" :key="user.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ user.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ user.role }} · {{ user.position }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" v-model="user.has_access"
                                        @change="updateAccess(user.id, $event.target.checked)">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3'

const props = defineProps({
    users: Array
})

const updateAccess = (userId, canAccess) => {
    router.post(route('ord.access.update'), {
        user_id: userId,
        can_access: canAccess
    }, {
        preserveScroll: true,
        onError: (err) => console.error(err)
    })
}
</script>