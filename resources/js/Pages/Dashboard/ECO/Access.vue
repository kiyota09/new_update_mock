<template>
    <Head title="ECO Access Control" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Shield class="h-3.5 w-3.5" />
                        Permission Management
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        ECO <span class="text-indigo-600">Access</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Grant or revoke access to the E‑Commerce module for Secretaries and General Managers.
                    </p>
                </div>
                <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Info Banner -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4 flex items-center gap-3">
                <Info class="h-5 w-5 text-blue-600" />
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <span class="font-bold">CEO</span> has full access by default. Assign access below to other high‑rank users.
                </p>
            </div>

            <!-- Users Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">User</th>
                                <th class="px-8 py-5">Role</th>
                                <th class="px-8 py-5 text-center">Access Status</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="user in users" :key="user.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <User class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ user.name }}</p>
                                            <p class="text-[10px] font-bold text-gray-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase"
                                        :class="user.role === 'CEO' ? 'bg-purple-100 text-purple-700' : user.role === 'Secretary' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span v-if="user.role === 'CEO'" class="text-emerald-600 font-bold text-sm">
                                        <CheckCircle class="inline h-4 w-4 mr-1" /> Full Access
                                    </span>
                                    <span v-else-if="user.can_access_eco" class="text-emerald-600 font-bold text-sm">
                                        <CheckCircle class="inline h-4 w-4 mr-1" /> Granted
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">
                                        <XCircle class="inline h-4 w-4 mr-1" /> Revoked
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button v-if="user.role !== 'CEO'" @click="toggleAccess(user)" :disabled="processing[user.id]"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                                        :class="user.can_access_eco ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'">
                                        <span :class="user.can_access_eco ? 'translate-x-6' : 'translate-x-1'"
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                                    </button>
                                    <span v-else class="text-xs text-gray-400">N/A</span>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="4" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No eligible users found (Secretaries or General Managers).
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Help Card -->
            <div class="bg-gray-50 dark:bg-gray-800/30 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-black uppercase tracking-widest mb-2">How it works</h3>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li>Only the <span class="font-bold text-indigo-600">CEO</span> can access this page.</li>
                    <li>Toggle the switch to grant or revoke ECO module access.</li>
                    <li>Granted users will see the ECO module in their dashboard sidebar.</li>
                    <li>Revoked users lose all ECO module access immediately.</li>
                </ul>
            </div>

            <!-- Toast Notification -->
            <Transition name="toast">
                <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                    :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                    {{ toast.message }}
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Shield, RefreshCw, User, CheckCircle, XCircle, Info } from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    accesses: {
        type: Object,
        default: () => ({})
    }
});

const processing = ref({});
const toast = ref({ show: false, type: 'success', message: '' });

// Merge access status into user objects for easier rendering
const usersWithAccess = props.users.map(user => ({
    ...user,
    can_access_eco: props.accesses[user.id] || false
}));

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const toggleAccess = async (user) => {
    processing.value[user.id] = true;
    try {
        await router.post(route('eco.access.update'), {
            user_id: user.id,
            can_access: !user.can_access_eco
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', `${user.name} access ${!user.can_access_eco ? 'granted' : 'revoked'}.`);
            },
            onError: (errors) => {
                showToast('error', Object.values(errors)[0] || 'Update failed.');
            }
        });
    } catch (e) {
        showToast('error', 'An error occurred.');
    } finally {
        processing.value[user.id] = false;
    }
};

const refreshData = () => {
    router.reload({ only: ['users', 'accesses'] });
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>