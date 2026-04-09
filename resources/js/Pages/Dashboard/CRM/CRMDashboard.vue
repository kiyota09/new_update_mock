<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Users, Briefcase, TrendingUp, Clock, Calendar, MessageSquare,
    CheckCircle, XCircle, AlertCircle, Building2, UserCheck,
    PieChart, BarChart3, ArrowRight, Eye
} from 'lucide-vue-next';

const page = usePage();
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_clients: 0,
            pending_clients: 0,
            total_leads: 0,
            open_feedback: 0,
        }),
    },
    recentFeedback: {
        type: Array,
        default: () => [],
    },
});

const user = computed(() => page.props.auth.user);
const isStaff = computed(() => user.value?.position === 'staff');
const isManager = computed(() => user.value?.position === 'manager');
const isCeo = computed(() => user.value?.role === 'CEO');

// Helper for greeting
const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
};

// Quick access links (filtered by permissions – we'll use route names, actual access will be blocked by backend)
const quickLinks = computed(() => {
    const links = [];
    if (!isStaff.value || user.value?.crmPagePermissions?.includes('lead')) {
        links.push({ label: 'Lead Pipeline', href: route('crm.lead'), icon: Briefcase });
    }
    if (!isStaff.value || user.value?.crmPagePermissions?.includes('approval')) {
        links.push({ label: 'Pending Approvals', href: route('crm.approval.index'), icon: Clock });
    }
    if (!isStaff.value || user.value?.crmPagePermissions?.includes('investigation')) {
        links.push({ label: 'Investigation', href: route('crm.investigation.index'), icon: AlertCircle });
    }
    if (!isStaff.value || user.value?.crmPagePermissions?.includes('customerprofile')) {
        links.push({ label: 'Customer Profiles', href: route('crm.customerprofile.show', 1), icon: Building2 }); // route expects id, but we'll redirect to list? We'll use a placeholder.
    }
    return links;
});

const getFeedbackIcon = (type) => {
    return type === 'complaint' ? XCircle : MessageSquare;
};

const getFeedbackClass = (type) => {
    return type === 'complaint' ? 'text-red-600 bg-red-50' : 'text-blue-600 bg-blue-50';
};
</script>

<template>
    <Head title="CRM Dashboard" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Customer Relationship <span class="text-blue-600">Dashboard</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ getGreeting() }}, {{ user?.name }}</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <Calendar class="w-4 h-4" />
                    <span>{{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Clients</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total_clients }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <Users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                        <TrendingUp class="w-3 h-3" /> +8% from last month
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Approvals</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.pending_clients }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                            <Clock class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-amber-600 dark:text-amber-400">Awaiting review</div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Leads</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total_leads }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <Briefcase class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-purple-600 dark:text-purple-400">In pipeline</div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Open Feedback</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.open_feedback }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-red-600 dark:text-red-400">Requires attention</div>
                </div>
            </div>

            <!-- Quick Access Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Link v-for="link in quickLinks" :key="link.label" :href="link.href"
                    class="group flex flex-col items-center p-4 bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 hover:border-blue-400 hover:shadow-lg transition-all">
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-zinc-800 group-hover:bg-blue-50 transition-colors">
                        <component :is="link.icon" class="w-6 h-6 text-gray-500 group-hover:text-blue-600" />
                    </div>
                    <span class="mt-2 text-xs font-bold text-gray-700 dark:text-gray-300">{{ link.label }}</span>
                    <ArrowRight class="w-3 h-3 text-gray-400 group-hover:text-blue-500 mt-1" />
                </Link>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Lead Conversion Progress (Placeholder) -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2 mb-4">
                        <PieChart class="w-4 h-4 text-blue-500" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Lead Conversion Rate</h3>
                    </div>
                    <div class="relative pt-1">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Conversion</span>
                            <span>32%</span>
                        </div>
                        <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full transition-all duration-1000" style="width: 32%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>Leads: {{ stats.total_leads }}</span>
                            <span>Won: 8</span>
                        </div>
                    </div>
                </div>

                <!-- Feedback Status Distribution -->
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2 mb-4">
                        <BarChart3 class="w-4 h-4 text-emerald-500" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Feedback Status</h3>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span>Open</span>
                                <span>{{ stats.open_feedback }}</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full" :style="{ width: `${(stats.open_feedback / (stats.open_feedback + 5)) * 100}%` }"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span>In Progress</span>
                                <span>3</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-500 rounded-full" style="width: 25%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span>Resolved</span>
                                <span>12</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: 60%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Feedback / Complaints -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h3 class="text-sm font-bold uppercase flex items-center gap-2">
                        <MessageSquare class="w-4 h-4" /> Recent Feedback & Complaints
                    </h3>
                    <Link v-if="!isStaff" :href="route('crm.investigation.index')" class="text-xs text-blue-600 hover:underline">View All</Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                    <div v-for="fb in recentFeedback" :key="fb.id" class="p-5 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                        <div class="flex items-start gap-3">
                            <div :class="['p-2 rounded-xl', getFeedbackClass(fb.type)]">
                                <component :is="getFeedbackIcon(fb.type)" class="w-4 h-4" />
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap justify-between items-start gap-2">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ fb.subject }}</p>
                                    <span :class="fb.status === 'open' ? 'bg-red-100 text-red-700' : fb.status === 'in_progress' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'" class="text-[10px] px-2 py-0.5 rounded-full font-black uppercase">{{ fb.status }}</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ fb.message }}</p>
                                <div class="flex justify-between items-center mt-2 text-xs text-gray-400">
                                    <span>{{ fb.client?.company_name || 'Client' }}</span>
                                    <span>{{ new Date(fb.created_at).toLocaleDateString() }}</span>
                                </div>
                            </div>
                            <Link v-if="!isStaff" :href="route('crm.investigation.index')" class="text-blue-600 hover:text-blue-700">
                                <Eye class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                    <div v-if="recentFeedback.length === 0" class="p-10 text-center text-gray-500 italic">
                        No recent feedback or complaints.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth transitions */
.group:hover {
    transform: translateY(-2px);
}
</style>