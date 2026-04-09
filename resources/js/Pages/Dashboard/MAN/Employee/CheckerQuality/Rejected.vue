<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { XCircle, ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    rejectedItems: Array, // Each item: { id, code, product_name, quantity, rejected_by, reason, rejected_at }
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Rejected Items" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('man.staff.checker-quality.production')"
                        class="flex items-center gap-1 text-gray-600 hover:text-gray-900">
                        <ArrowLeft class="w-4 h-4" /> Back to Production
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rejected Items</h1>
                </div>
                <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-bold">
                    {{ rejectedItems?.length || 0 }} items rejected
                </div>
            </div>

            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800/50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Code</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Rejected By</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Reason</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            <tr v-for="item in rejectedItems" :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition">
                                <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ item.code }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.product_name }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ item.rejected_by }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300 max-w-md truncate"
                                    :title="item.reason">{{ item.reason }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">{{ new
                                    Date(item.rejected_at).toLocaleString() }}</td>
                            </tr>
                            <tr v-if="!rejectedItems || rejectedItems.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <XCircle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    <p>No rejected items found.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>