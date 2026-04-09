<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Search, Building2, User, Mail, ExternalLink, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    clients: Array,
    message: String
});

const searchQuery = ref('');

const filteredClients = computed(() => {
    if (!searchQuery.value) return props.clients;
    const q = searchQuery.value.toLowerCase();
    return props.clients.filter(c =>
        c.company_name?.toLowerCase().includes(q) ||
        c.contact_person?.toLowerCase().includes(q) ||
        c.email?.toLowerCase().includes(q)
    );
});
</script>

<template>
    <Head title="Customer Profiles" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Customer <span class="text-blue-600">Profiles</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        View and manage client details, meetings, and feedback.
                    </p>
                </div>
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search clients..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div v-if="message" class="bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-yellow-800 dark:text-yellow-300">
                {{ message }}
            </div>

            <div v-if="filteredClients.length === 0" class="text-center py-20 text-gray-500">
                <AlertCircle class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-lg font-medium">No clients found.</p>
                <p class="text-sm">Try adjusting your search or check back later.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="client in filteredClients" :key="client.id"
                      :href="route('crm.customerprofile.show', client.id)"
                      class="group bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-md transition-all overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition">
                                    {{ client.company_name }}
                                </h3>
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <User class="h-4 w-4" />
                                        <span>{{ client.contact_person }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <Mail class="h-4 w-4" />
                                        <span>{{ client.email }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <Building2 class="h-4 w-4" />
                                        <span>{{ client.business_type || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                            <ExternalLink class="h-5 w-5 text-gray-400 group-hover:text-blue-500 transition" />
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                            <span :class="client.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                  class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                {{ client.status }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>