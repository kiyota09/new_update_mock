<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    Factory, 
    ListChecks, 
    Clock, 
    CheckCircle2, 
    AlertCircle,
    TrendingUp
} from 'lucide-vue-next';

const props = defineProps({
    productions: Array,
    auth: Object
});
</script>

<template>
    <Head title="Production Tracking" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl text-gray-800 dark:text-white leading-tight uppercase tracking-tight">
                Order Production Status
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-zinc-800 p-6">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                <Factory class="w-6 h-6 text-orange-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Active Production Lines</h3>
                                <p class="text-sm text-gray-500">Monitor textile processing stages from Knitting to Packaging.</p>
                            </div>
                        </div>
                        <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-700">
                            <ListChecks class="w-4 h-4 text-zinc-400" />
                            <span class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Live Updates</span>
                        </div>
                    </div>

                    <div v-if="productions && productions.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <th class="py-4 px-2 text-[11px] font-black text-zinc-400 uppercase tracking-widest">Order Info</th>
                                    <th class="py-4 px-2 text-[11px] font-black text-zinc-400 uppercase tracking-widest">Progress</th>
                                    <th class="py-4 px-2 text-[11px] font-black text-zinc-400 uppercase tracking-widest">Status</th>
                                    <th class="py-4 px-2 text-[11px] font-black text-zinc-400 uppercase tracking-widest text-right">Start Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                                <tr v-for="item in productions" :key="item.id" class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="py-4 px-2">
                                        <div class="font-bold text-zinc-900 dark:text-white text-sm">{{ item.po_number }}</div>
                                        <div class="text-xs text-zinc-500 font-medium">{{ item.client_name }}</div>
                                    </td>
                                    <td class="py-4 px-2 w-1/3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full bg-orange-500 rounded-full transition-all duration-1000" 
                                                    :style="{ width: item.progress + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-xs font-black text-zinc-700 dark:text-zinc-300">{{ item.progress }}%</span>
                                        </div>
                                        <div class="text-[10px] text-zinc-400 mt-1 uppercase font-bold tracking-tighter">
                                            {{ item.completed_quantity }} / {{ item.total_quantity }} Units Done
                                        </div>
                                    </td>
                                    <td class="py-4 px-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                            <TrendingUp class="w-3 h-3 mr-1" />
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400">{{ item.created_at }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-20">
                        <Clock class="w-12 h-12 text-gray-300 mx-auto mb-4 animate-pulse" />
                        <p class="text-gray-500 italic font-medium">No active production batches found.</p>
                        <p class="text-xs text-gray-400 mt-1 uppercase tracking-widest">New orders will appear here once pushed from ECO center.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>