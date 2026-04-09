<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Wrench, CheckCircle, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Maintenance Checker" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Maintenance Dashboard</h1>
                <div class="flex gap-3">
                    <Link :href="route('man.staff.maintenance-checker.page')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        Manage Machines
                    </Link>
                    <Link :href="route('man.staff.maintenance-checker.reports')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                        View Reports
                    </Link>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Reports</p>
                            <p class="text-3xl font-bold">{{ stats.pending_reports }}</p>
                        </div>
                        <AlertTriangle class="w-8 h-8 text-yellow-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Resolved Today</p>
                            <p class="text-3xl font-bold">{{ stats.resolved_today }}</p>
                        </div>
                        <CheckCircle class="w-8 h-8 text-green-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Machines</p>
                            <p class="text-3xl font-bold">{{ stats.total_machines }}</p>
                        </div>
                        <Wrench class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>