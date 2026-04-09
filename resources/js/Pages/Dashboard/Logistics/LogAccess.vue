<template>
    <Head title="Logistics Access Control" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Security & Permissions
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Logistics <span class="text-indigo-600">Access Control</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Grant or revoke access to the Logistics module for Secretary and General Managers.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-5 rounded-2xl border border-blue-100 dark:border-blue-800 flex items-start gap-3">
                <Info class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-bold text-blue-800 dark:text-blue-200">CEO‑Only Section</p>
                    <p class="text-xs text-blue-600 dark:text-blue-300">
                        Only the CEO can manage Logistics access. Secretary and General Managers need explicit permission to view the Logistics module.
                    </p>
                </div>
            </div>

            <!-- Access Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white">User Permissions</h2>
                    <p class="text-xs text-gray-500">Toggle access for the Logistics Dashboard, Load, Dispatch, Fleet, and all sub‑pages.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">User</th>
                                <th class="px-8 py-5">Role / Position</th>
                                <th class="px-8 py-5 text-center">Logistics Access</th>
                                <th class="px-8 py-5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="user in users" :key="user.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-black">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-gray-900 dark:text-white">{{ user.name }}</p>
                                            <p class="text-[10px] text-gray-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-[9px] font-black uppercase">
                                        {{ user.role }} · {{ user.position }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                :checked="accessState[user.id] || false"
                                                @change="toggleAccess(user)"
                                                class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                            <span class="ml-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                {{ accessState[user.id] ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div v-if="updatingUserId === user.id" class="flex justify-end">
                                        <Loader2 class="h-4 w-4 animate-spin text-indigo-600" />
                                    </div>
                                </td>
                             </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="4" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    <ShieldCheck class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    No eligible users found.
                                    <br>
                                    <span class="text-xs">Only Secretary and General Manager accounts appear here.</span>
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
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
import { ref, reactive } from 'vue';
import { ShieldCheck, RefreshCw, Info, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    access: {
        type: Object, // { user_id: boolean }
        default: () => ({})
    }
});

// Local copy of access states
const accessState = reactive({ ...props.access });
const updatingUserId = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['users', 'access'] });
};

const toggleAccess = async (user) => {
    const newValue = !accessState[user.id];
    updatingUserId.value = user.id;
    
    try {
        await router.post(route('logistics.access.update'), {
            user_id: user.id,
            can_access: newValue
        }, {
            preserveScroll: true,
            onSuccess: () => {
                accessState[user.id] = newValue;
                showToast('success', `${user.name} access ${newValue ? 'granted' : 'revoked'}.`);
            },
            onError: (errors) => {
                showToast('error', Object.values(errors)[0] || 'Update failed.');
                // revert local state
                accessState[user.id] = !newValue;
            },
            onFinish: () => {
                updatingUserId.value = null;
            }
        });
    } catch (error) {
        showToast('error', 'Network error. Please try again.');
        accessState[user.id] = !newValue;
        updatingUserId.value = null;
    }
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>