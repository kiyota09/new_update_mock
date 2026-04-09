<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Users, UserCheck, FileText, Calendar, Clock, TrendingUp, Building2, Briefcase, Award,
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_employees: 0,
            active_trainees: 0,
            pending_applications: 0,
            pending_interviews: 0,
            pending_onboarding: 0,
        }),
    },
    departmentCounts: {
        type: Object,
        default: () => ({}),
    },
    attendanceTrend: {
        type: Object,
        default: () => ({ months: [], values: [] }),
    },
});

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
};
</script>

<template>

    <Head title="HRM Dashboard" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Human Resource Management
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ getGreeting() }}, Monti Team</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <Calendar class="w-4 h-4" />
                    <span>{{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
                    }}</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6">
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Employees</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                stats.total_employees }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <Users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Trainees</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                stats.active_trainees }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <UserCheck class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Applications</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                stats.pending_applications }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                            <FileText class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Interviews</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                stats.pending_interviews }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                            <Calendar class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-lg transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Onboarding</p>
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1">{{
                                stats.pending_onboarding }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <Briefcase class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section (Placeholders) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Department Distribution -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2 mb-4">
                        <Building2 class="w-4 h-4 text-blue-500" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Department Distribution</h3>
                    </div>
                    <div v-if="Object.keys(departmentCounts).length === 0" class="text-center py-8 text-gray-500">
                        No department data available
                    </div>
                    <div v-else class="space-y-3 max-h-80 overflow-y-auto pr-2">
                        <div v-for="(count, dept) in departmentCounts" :key="dept" class="flex items-center gap-3">
                            <span class="w-16 text-xs font-medium text-gray-600 dark:text-gray-400">{{ dept }}</span>
                            <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-700"
                                    :style="{ width: `${(count / Math.max(...Object.values(departmentCounts))) * 100}%` }">
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Attendance Trend (Placeholder) -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2 mb-4">
                        <TrendingUp class="w-4 h-4 text-emerald-500" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Attendance Trend (Last 6 Months)
                        </h3>
                    </div>
                    <div v-if="!attendanceTrend.values || attendanceTrend.values.length === 0"
                        class="text-center py-8 text-gray-500">
                        No attendance data available
                    </div>
                    <div v-else class="h-48 flex items-end gap-2">
                        <div v-for="(value, idx) in attendanceTrend.values" :key="idx"
                            class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-gradient-to-t from-emerald-400 to-emerald-500 rounded-t-lg transition-all duration-500"
                                :style="{ height: `${value}%`, minHeight: '4px' }"></div>
                            <span class="text-[10px] sm:text-xs mt-2 text-gray-500">{{ attendanceTrend.months[idx]
                            }}</span>
                            <span class="text-[9px] font-bold text-gray-600 dark:text-gray-400">{{ value }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (Placeholder) -->
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-4">
                    <Clock class="w-4 h-4 text-gray-500" />
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Recent Activity</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-blue-50 rounded-lg mt-0.5">
                            <UserCheck class="w-3 h-3 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">New employee hired: Sarah Johnson</p>
                            <p class="text-xs text-gray-400">2 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-green-50 rounded-lg mt-0.5">
                            <Award class="w-3 h-3 text-green-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">Trainee Mark Williams scored 85%</p>
                            <p class="text-xs text-gray-400">Yesterday</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-orange-50 rounded-lg mt-0.5">
                            <Calendar class="w-3 h-3 text-orange-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">Leave request approved for 3 days</p>
                            <p class="text-xs text-gray-400">2 days ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>