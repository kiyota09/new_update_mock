<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Layers, TrendingUp, CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Quality Checker Dashboard" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Quality Control Dashboard</h1>
                <Link :href="route('man.staff.checker-quality.production')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                    Go to Production Control
                </Link>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Orders</p>
                            <p class="text-3xl font-bold">{{ stats.pending_orders }}</p>
                        </div>
                        <Package class="w-8 h-8 text-blue-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Fabrics Pending</p>
                            <p class="text-3xl font-bold">{{ stats.fabrics_pending }}</p>
                        </div>
                        <Layers class="w-8 h-8 text-purple-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Dye Pending</p>
                            <p class="text-3xl font-bold">{{ stats.dye_pending }}</p>
                        </div>
                        <TrendingUp class="w-8 h-8 text-amber-500" />
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Softener/Squeezer Pending</p>
                            <p class="text-3xl font-bold">{{ stats.softener_pending + stats.squeezer_pending }}</p>
                        </div>
                        <CheckCircle class="w-8 h-8 text-green-500" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold mb-2">Iron Pending</h3>
                    <p class="text-3xl font-bold">{{ stats.iron_pending }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold mb-2">Form Pending</h3>
                    <p class="text-3xl font-bold">{{ stats.form_pending }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold mb-2">Packages Pending</h3>
                    <p class="text-3xl font-bold">{{ stats.packages_pending }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold mb-2">Quality Rejections</h3>
                    <p class="text-3xl font-bold">0</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>