<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { XCircle, ArrowLeft, Search } from 'lucide-vue-next';

const props = defineProps({
    rejectedItems: Array,
});

const searchTerm = ref('');

const filteredItems = computed(() => {
    if (!searchTerm.value) return props.rejectedItems;
    return props.rejectedItems.filter(item =>
        item.code.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        item.product_name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        (item.reason && item.reason.toLowerCase().includes(searchTerm.value.toLowerCase()))
    );
});

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Rejected Items" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('man.manager.dashboard')"
                        class="flex items-center gap-1 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        <ArrowLeft class="w-4 h-4" /> Back to Dashboard
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rejected Items</h1>
                </div>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Search by code, product, or reason..."
                        class="pl-9 pr-4 py-2 border border-gray-300 dark:border-zinc-700 rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rejected By</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reason</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="item in filteredItems" :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ item.code }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.product_name }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.rejected_by }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300 max-w-md truncate"
                                    :title="item.reason">{{ item.reason }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">{{
                                    formatDate(item.rejected_at) }}</td>
                            </tr>
                            <tr v-if="filteredItems.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <XCircle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    No rejected items found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="mt-6 bg-red-50 dark:bg-red-900/10 rounded-2xl p-4 border border-red-100 dark:border-red-800">
                <div class="flex items-center gap-3">
                    <XCircle class="w-6 h-6 text-red-600" />
                    <div>
                        <p class="font-medium text-red-800 dark:text-red-300">Total Rejected Items</p>
                        <p class="text-2xl font-bold text-red-600">{{ rejectedItems?.length || 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>