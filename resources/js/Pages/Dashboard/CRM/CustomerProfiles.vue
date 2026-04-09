<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';
import { Building2, Phone, Mail, MapPin, ChevronRight, Users } from 'lucide-vue-next';

defineProps({
    clients: {
        type: Array,
        default: () => []
    }
});

const getStatusClass = (status) => {
    switch (status) {
        case 'active':   return 'bg-green-100 text-green-700';
        case 'pending':  return 'bg-amber-100 text-amber-700';
        case 'inactive': return 'bg-red-100 text-red-700';
        default:         return 'bg-gray-100 text-gray-600';
    }
};
</script>

<template>
    <Head title="Customer Profiles" />
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">

            <!-- Page Header -->
            <div class="mb-6 flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                    <Users class="h-5 w-5 text-blue-600" />
                </div>
                <div>
                    <h1 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Customer Profiles</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ clients.length }} registered client{{ clients.length !== 1 ? 's' : '' }}</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="clients.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                    <Users class="h-8 w-8 text-gray-400" />
                </div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No clients found</p>
                <p class="text-xs text-gray-400 mt-1">Converted leads will appear here.</p>
            </div>

            <!-- Client Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="client in clients"
                    :key="client.id"
                    :href="route('crm.customerprofile.show', client.id)"
                    class="group relative flex flex-col bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800 transition-all duration-200 p-5 overflow-hidden"
                >
                    <!-- Accent bar -->
                    <div class="absolute left-0 top-4 bottom-4 w-0.5 bg-blue-500 rounded-r-full opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>

                    <!-- Header row -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-sm font-black shadow-md shadow-blue-500/20 uppercase flex-shrink-0">
                                {{ (client.company_name ?? client.name ?? '?').charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13px] font-black text-gray-900 dark:text-white truncate tracking-tight">
                                    {{ client.company_name ?? client.name }}
                                </p>
                                <p v-if="client.business_type" class="text-[10px] text-blue-600 font-bold uppercase truncate">
                                    {{ client.business_type }}
                                </p>
                            </div>
                        </div>
                        <span v-if="client.status"
                            :class="getStatusClass(client.status)"
                            class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full flex-shrink-0 ml-2">
                            {{ client.status }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="space-y-1.5 mb-4">
                        <div v-if="client.email" class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                            <Mail class="h-3 w-3 flex-shrink-0" />
                            <span class="truncate">{{ client.email }}</span>
                        </div>
                        <div v-if="client.phone" class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                            <Phone class="h-3 w-3 flex-shrink-0" />
                            <span class="truncate">{{ client.phone }}</span>
                        </div>
                        <div v-if="client.address" class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                            <MapPin class="h-3 w-3 flex-shrink-0" />
                            <span class="truncate">{{ client.address }}</span>
                        </div>
                    </div>

                    <!-- Footer stats -->
                    <div class="mt-auto flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex gap-3">
                            <div class="text-center">
                                <p class="text-[14px] font-black text-gray-900 dark:text-white">{{ client.meetings?.length ?? 0 }}</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase">Meetings</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[14px] font-black text-gray-900 dark:text-white">{{ client.feedback?.length ?? 0 }}</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase">Feedback</p>
                            </div>
                        </div>
                        <ChevronRight class="h-4 w-4 text-gray-300 group-hover:text-blue-500 transition-colors duration-200" />
                    </div>
                </Link>
            </div>

        </div>
    </AuthenticatedLayout>
</template>