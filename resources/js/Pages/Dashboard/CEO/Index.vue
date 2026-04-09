<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Filter, Download, Plus, ChevronDown, ArrowDownRight, ArrowUpRight,
    Settings2, ArrowUpRightSquare, Clock, X, Wallet, TrendingUp, Users, 
    Factory, Package, ShoppingCart, DollarSign, Activity
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalOrders: 0,
            totalRevenue: 0,
            activeEmployees: 0,
            pendingLeads: 0
        })
    },
    revenueTrend: {
        type: Object,
        default: () => ({
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            values: [40, 75, 45, 90, 60, 50, 85],
            rawValues: [0, 0, 0, 0, 0, 0, 0]
        })
    },
    activityStats: {
        type: Object,
        default: () => ({
            receipts: { value: '0.00', trend: '+0%', isUp: true },
            contributions: { value: '0.00', trend: '+0%', isUp: true },
            owes: { value: '0.00', trend: '0%', isUp: false }
        })
    },
    transactions: {
        type: Array,
        default: () => []
    },
    modules: {
        type: Array,
        default: () => []
    }
});

const showBanner = ref(true);

// Format total revenue for display
const formattedTotalRevenue = computed(() => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(props.stats.totalRevenue);
});

// Format total orders
const formattedTotalOrders = computed(() => {
    return new Intl.NumberFormat('en-US').format(props.stats.totalOrders);
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="CEO Dashboard" />

        <div class="min-h-screen bg-gray-50 p-4 sm:p-8 font-sans text-gray-900">
            <div class="max-w-[1400px] mx-auto space-y-8">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                            Welcome back, Monti Leadership!
                        </h1>
                        <p class="text-gray-500 mt-2 font-medium">Control your investment, income, and expenses.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 rounded-full font-semibold shadow-sm border border-gray-200 hover:bg-gray-50 transition">
                            <Filter class="w-4 h-4" /> Filters
                        </button>
                        <button class="flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 rounded-full font-semibold shadow-sm border border-gray-200 hover:bg-gray-50 transition">
                            <Download class="w-4 h-4" /> Exports
                        </button>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

                    <!-- Left Column: Summary + Small Cards -->
                    <div class="xl:col-span-4 flex flex-col gap-6">

                        <!-- Summary Card -->
                        <div class="bg-white rounded-2xl p-7 shadow-sm border border-gray-100">
                            <div class="flex justify-between items-center mb-8">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Summary</h2>
                                    <p class="text-sm text-gray-400 font-medium">Track your performance.</p>
                                </div>
                                <button class="flex items-center gap-1 text-sm font-semibold px-4 py-2 border border-gray-200 rounded-full hover:bg-gray-50">
                                    Weekly
                                    <ChevronDown class="w-4 h-4 text-gray-400" />
                                </button>
                            </div>

                            <div class="flex gap-4 mb-10">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 font-medium mb-1">
                                        <div class="w-6 h-6 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center">
                                            <ArrowDownRight class="w-3 h-3 text-gray-600" />
                                        </div>
                                        Total income
                                    </div>
                                    <div class="text-2xl font-bold tracking-tight">₱{{ formattedTotalRevenue }}</div>
                                </div>
                                <div class="w-px bg-gray-100"></div>
                                <div class="flex-1 pl-2">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 font-medium mb-1">
                                        <div class="w-6 h-6 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center">
                                            <ArrowUpRight class="w-3 h-3 text-gray-600" />
                                        </div>
                                        Total orders
                                    </div>
                                    <div class="text-2xl font-bold tracking-tight">{{ formattedTotalOrders }}</div>
                                </div>
                            </div>

                            <!-- Bar Chart -->
                            <div class="h-40 flex items-end justify-between gap-3 mt-4">
                                <div v-for="(val, index) in revenueTrend.values" :key="index"
                                    class="w-full relative group flex flex-col justify-end h-full">
                                    <div class="w-full rounded-t-xl rounded-b-sm transition-all duration-500"
                                        :class="[val > 50 ? 'bg-blue-500' : 'bg-gray-200']"
                                        :style="{ height: `${val}%` }">
                                        <div class="opacity-0 group-hover:opacity-100 transition absolute -top-6 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                                            ₱{{ new Intl.NumberFormat().format(revenueTrend.rawValues[index]) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-3 text-xs text-gray-400">
                                <span v-for="(label, idx) in revenueTrend.labels" :key="idx">{{ label }}</span>
                            </div>
                        </div>

                        <!-- Two small stat cards -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                                <div class="flex items-center gap-2 text-sm text-gray-500 font-medium mb-4">
                                    <Clock class="w-4 h-4" /> Active Employees
                                </div>
                                <div class="flex items-center gap-1 text-sm font-bold text-gray-900 mb-1">
                                    <ArrowDownRight class="w-4 h-4 text-gray-900" /> Current
                                </div>
                                <div class="text-2xl font-bold">{{ stats.activeEmployees }}</div>
                            </div>
                            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                                <div class="flex items-center gap-2 text-sm text-gray-500 font-medium mb-4">
                                    <Activity class="w-4 h-4" /> Pending Leads
                                </div>
                                <div class="flex items-center gap-1 text-sm font-bold text-blue-600 mb-1">
                                    <ArrowUpRight class="w-4 h-4" /> Inquiry
                                </div>
                                <div class="text-2xl font-bold">{{ stats.pendingLeads }}</div>
                            </div>
                        </div>

                        <!-- Banner (tip) -->
                        <div v-if="showBanner" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center mt-2">
                            <h3 class="font-bold text-lg text-gray-900 leading-tight max-w-[200px]">
                                How is your business management going?
                            </h3>
                            <button @click="showBanner = false" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition">
                                <X class="w-5 h-5 text-gray-500" />
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Activity + Transactions -->
                    <div class="xl:col-span-8 flex flex-col gap-8">

                        <!-- Activity section -->
                        <div>
                            <div class="flex justify-between items-center mb-4 px-2">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Activity</h2>
                                    <p class="text-sm text-gray-400 font-medium">Track your activity.</p>
                                </div>
                                <div class="flex gap-2">
                                    <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm border border-gray-200 hover:bg-gray-50">
                                        <Settings2 class="w-4 h-4 text-gray-600" />
                                    </button>
                                    <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm border border-gray-200 hover:bg-gray-50">
                                        <ArrowUpRightSquare class="w-4 h-4 text-gray-600" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Receipts Card -->
                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col relative overflow-hidden group">
                                    <div class="flex items-center gap-3 mb-6 relative z-10">
                                        <div class="p-2 bg-gray-50 rounded-xl">
                                            <Wallet class="w-5 h-5 text-gray-700" />
                                        </div>
                                        <span class="font-semibold text-gray-900">Receipts</span>
                                    </div>
                                    <div class="relative z-10">
                                        <div class="text-blue-600 text-sm font-bold flex items-center gap-1 mb-1">
                                            <ArrowUpRight class="w-4 h-4" /> {{ activityStats.receipts.trend }}
                                        </div>
                                        <div class="text-3xl font-bold tracking-tight">₱{{ activityStats.receipts.value }}</div>
                                    </div>
                                    <div class="absolute bottom-0 left-0 w-full h-24 opacity-80 group-hover:opacity-100 transition duration-500">
                                        <svg viewBox="0 0 200 60" preserveAspectRatio="none" class="w-full h-full">
                                            <path d="M0,40 Q10,30 20,45 T40,35 T60,50 T80,20 T100,45 T120,25 T140,50 T160,15 T180,35 T200,20" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                                            <path d="M0,40 Q10,30 20,45 T40,35 T60,50 T80,20 T100,45 T120,25 T140,50 T160,15 T180,35 T200,20 L200,60 L0,60 Z" fill="url(#gradBlue)" opacity="0.15" />
                                            <defs>
                                                <linearGradient id="gradBlue" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#3B82F6" />
                                                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Contributions Card -->
                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col relative overflow-hidden group">
                                    <div class="flex items-center gap-3 mb-6 relative z-10">
                                        <div class="p-2 bg-gray-50 rounded-xl">
                                            <TrendingUp class="w-5 h-5 text-gray-700" />
                                        </div>
                                        <span class="font-semibold text-gray-900">Contributions</span>
                                    </div>
                                    <div class="relative z-10">
                                        <div class="text-blue-600 text-sm font-bold flex items-center gap-1 mb-1">
                                            <ArrowUpRight class="w-4 h-4" /> {{ activityStats.contributions.trend }}
                                        </div>
                                        <div class="text-3xl font-bold tracking-tight">₱{{ activityStats.contributions.value }}</div>
                                    </div>
                                    <div class="absolute bottom-0 left-0 w-full h-24 opacity-80 group-hover:opacity-100 transition duration-500">
                                        <svg viewBox="0 0 200 60" preserveAspectRatio="none" class="w-full h-full">
                                            <path d="M0,50 Q15,20 30,40 T60,25 T90,45 T120,30 T150,55 T180,25 T200,40" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                                            <path d="M0,50 Q15,20 30,40 T60,25 T90,45 T120,30 T150,55 T180,25 T200,40 L200,60 L0,60 Z" fill="url(#gradBlue2)" opacity="0.15" />
                                            <defs>
                                                <linearGradient id="gradBlue2" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#3B82F6" />
                                                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Owes Card -->
                                <div class="bg-blue-50 text-gray-900 rounded-2xl p-6 shadow-sm flex flex-col relative overflow-hidden group">
                                    <div class="flex items-center gap-3 mb-6 relative z-10">
                                        <div class="p-2 bg-blue-100 rounded-xl">
                                            <DollarSign class="w-5 h-5 text-blue-700" />
                                        </div>
                                        <span class="font-semibold text-gray-900">Owes</span>
                                    </div>
                                    <div class="relative z-10">
                                        <div class="text-red-500 text-sm font-medium flex items-center gap-1 mb-1">
                                            <ArrowDownRight class="w-4 h-4" /> {{ activityStats.owes.trend }}
                                        </div>
                                        <div class="text-3xl font-bold tracking-tight">₱{{ activityStats.owes.value }}</div>
                                    </div>
                                    <div class="absolute bottom-0 left-0 w-full h-24 opacity-90 group-hover:opacity-100 transition duration-500">
                                        <svg viewBox="0 0 200 60" preserveAspectRatio="none" class="w-full h-full">
                                            <path d="M0,35 Q20,15 40,45 T80,25 T120,50 T160,30 T200,45" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                                            <path d="M0,35 Q20,15 40,45 T80,25 T120,50 T160,30 T200,45 L200,60 L0,60 Z" fill="url(#gradLightBlue)" opacity="0.2" />
                                            <defs>
                                                <linearGradient id="gradLightBlue" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#3B82F6" />
                                                    <stop offset="100%" stop-color="#EFF6FF" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <div class="bg-white rounded-2xl p-7 shadow-sm border border-gray-100 flex-1">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Transactions history</h2>
                                    <p class="text-sm text-gray-400 font-medium">Track your history.</p>
                                </div>
                                <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm border border-gray-200 hover:bg-gray-50">
                                    <Settings2 class="w-4 h-4 text-gray-600" />
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                                            <th class="pb-4 font-semibold px-2">Name</th>
                                            <th class="pb-4 font-semibold px-2">ID</th>
                                            <th class="pb-4 font-semibold px-2">Status</th>
                                            <th class="pb-4 font-semibold px-2">Date</th>
                                            <th class="pb-4 font-semibold px-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        <tr v-for="tx in transactions" :key="tx.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                            <td class="py-4 px-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-600 text-sm overflow-hidden border border-gray-200">
                                                        {{ tx.avatar }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-gray-900">{{ tx.name }}</div>
                                                        <div class="text-xs text-gray-500">{{ tx.role }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-2 font-medium text-gray-900">{{ tx.id }}</td>
                                            <td class="py-4 px-2">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                                                    :class="tx.status === 'Completed' || tx.status === 'Approved' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="tx.status === 'Completed' || tx.status === 'Approved' ? 'bg-green-600' : 'bg-blue-600'"></span>
                                                    {{ tx.status }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-2 font-medium text-gray-900">{{ tx.date }}</td>
                                            <td class="py-4 px-2 text-right font-bold" :class="tx.isNegative ? 'text-red-500' : 'text-gray-900'">
                                                {{ tx.amount }}
                                            </td>
                                        </tr>
                                        <tr v-if="transactions.length === 0">
                                            <td colspan="5" class="py-8 text-center text-gray-400">
                                                No transactions found
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Modules -->
                <div class="pt-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 px-2">Department Overview</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="(mod, idx) in modules" :key="idx"
                            class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-3 mb-5">
                                <div :class="[mod.bg, mod.color, 'p-3 rounded-xl']">
                                    <component v-if="mod.icon === 'Users'" :is="Users" class="w-5 h-5" />
                                    <component v-else-if="mod.icon === 'ShoppingCart'" :is="ShoppingCart" class="w-5 h-5" />
                                    <component v-else-if="mod.icon === 'Factory'" :is="Factory" class="w-5 h-5" />
                                    <component v-else-if="mod.icon === 'Package'" :is="Package" class="w-5 h-5" />
                                    <component v-else :is="Users" class="w-5 h-5" />
                                </div>
                                <h3 class="font-bold text-gray-900">{{ mod.title }}</h3>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(val, key) in mod.stats" :key="key" class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 font-medium">{{ key }}</span>
                                    <span class="font-bold text-gray-900">{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Modern sans-serif font */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-sans {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* Subtle scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>