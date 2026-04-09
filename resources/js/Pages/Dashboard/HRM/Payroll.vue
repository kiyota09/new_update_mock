<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import {
    Search,
    Filter,
    MoreHorizontal,
    Download,
    DollarSign,
    CreditCard,
    TrendingUp,
    Clock,
    FileText,
    CheckCircle2,
    Calendar,
    ArrowUpRight,
    UserCheck,
    AlertCircle,
    Plus,
    History,
    Banknote
} from 'lucide-vue-next'

const props = defineProps({
    payrollData: {
        type: Array,
        default: () => [
            { id: 'PAY-001', name: 'Alexander Wright', role: 'Senior Developer', amount: 4500.00, method: 'Direct Deposit', status: 'Processed', date: 'Feb 15, 2026' },
            { id: 'PAY-002', name: 'Sarah Jenkins', role: 'UI Designer', amount: 3850.50, method: 'Direct Deposit', status: 'Pending', date: 'Feb 15, 2026' },
            { id: 'PAY-003', name: 'Michael Chen', role: 'Project Manager', amount: 5200.00, method: 'Wire Transfer', status: 'Processed', date: 'Feb 15, 2026' },
            { id: 'PAY-004', name: 'Emma Rodriguez', role: 'HR Specialist', amount: 4100.00, method: 'Direct Deposit', status: 'Failed', date: 'Feb 14, 2026' },
        ]
    }
})

const searchQuery = ref('')

const filteredPayroll = computed(() => {
    return props.payrollData.filter(item =>
        item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        item.id.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const getStatusClass = (status) => {
    switch (status.toLowerCase()) {
        case 'processed': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
        case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
        case 'failed': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
    }
}

const stats = computed(() => [
    { label: 'Total Payroll', value: '$13,550.50', sub: '+$420 vs last month', icon: DollarSign, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Tax Withholding', value: '$3,140.00', sub: '12% Avg. Rate', icon: Banknote, color: 'text-purple-600', bg: 'bg-purple-50' },
    { label: 'Pending Approval', value: '4 Requests', sub: 'Action required', icon: AlertCircle, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Avg. Net Pay', value: '$4,230.00', sub: 'Per employee', icon: TrendingUp, color: 'text-emerald-600', bg: 'bg-emerald-50' },
])
</script>

<template>

    <Head title="HR Payroll Console" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <span
                            class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-widest">HR
                            Admin</span>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Payroll <span class="text-blue-600 font-light">Console</span>
                        </h1>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium italic">
                        Managing Q1 2026 Disbursement Cycle • <span class="text-emerald-500">System Online</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm text-slate-600 dark:text-slate-300 hover:shadow-md transition-all">
                        <History class="h-4 w-4" />
                        Audit Logs
                    </button>
                    <button
                        class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm text-slate-600 dark:text-slate-300 hover:shadow-md transition-all">
                        <Download class="h-4 w-4" />
                        Export
                    </button>
                    <button
                        class="flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-blue-600 hover:bg-black dark:hover:bg-blue-700 text-white rounded-2xl font-bold text-sm transition-all shadow-xl active:scale-95">
                        <Plus class="h-4 w-4" />
                        New Run
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="stat in stats" :key="stat.label"
                    class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:border-blue-200 transition-colors group">
                    <div class="flex items-center justify-between mb-4">
                        <div :class="[stat.bg, 'p-3 rounded-2xl group-hover:scale-110 transition-transform']">
                            <component :is="stat.icon" :class="[stat.color, 'h-6 w-6']" />
                        </div>
                        <span
                            class="text-[10px] font-black text-slate-300 group-hover:text-blue-500 transition-colors">DETAILS</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stat.value }}</p>
                        <p class="mt-2 text-[11px] font-medium text-slate-400 flex items-center gap-1">
                            <CheckCircle2 class="h-3 w-3 text-emerald-500" />
                            {{ stat.sub }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div
                    class="p-8 border-b border-slate-50 dark:border-slate-700 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="relative flex-1 max-w-xl">
                        <Search class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search by name, ID, or position..."
                            class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" />
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            class="flex items-center gap-2 px-5 py-4 bg-slate-50 dark:bg-slate-900 rounded-2xl font-bold text-xs text-slate-500 uppercase tracking-widest border border-transparent hover:border-slate-200 transition-all">
                            <Calendar class="h-4 w-4" />
                            Feb 2026
                        </button>
                        <button
                            class="flex items-center gap-2 px-5 py-4 bg-slate-50 dark:bg-slate-900 rounded-2xl font-bold text-xs text-slate-500 uppercase tracking-widest border border-transparent hover:border-slate-200 transition-all">
                            <Filter class="h-4 w-4" />
                            Filters
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                                <th
                                    class="pl-8 pr-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Transaction</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Employee Details</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Disbursement</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Method</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Status</th>
                                <th
                                    class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right pr-8">
                                    Management</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="pay in filteredPayroll" :key="pay.id"
                                class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all cursor-default">
                                <td class="pl-8 pr-4 py-6">
                                    <span
                                        class="text-xs font-black text-slate-400 group-hover:text-blue-600 transition-colors">{{
                                        pay.id }}</span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center">
                                        <div
                                            class="h-12 w-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-black text-sm mr-4 shadow-inner">
                                            {{pay.name.split(' ').map(n => n[0]).join('')}}
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-black text-slate-900 dark:text-white leading-tight">{{
                                                pay.name }}</span>
                                            <span
                                                class="text-[11px] font-bold text-blue-500 uppercase tracking-tighter">{{
                                                pay.role }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900 dark:text-white">${{
                                            pay.amount.toLocaleString() }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{
                                            pay.date }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                        <CreditCard class="h-3.5 w-3.5" />
                                        {{ pay.method }}
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        :class="[getStatusClass(pay.status), 'px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-[0.1em] inline-flex items-center gap-2 shadow-sm']">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        {{ pay.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-right pr-8">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"
                                            title="Manage Payslip">
                                            <FileText class="h-4.5 w-4.5" />
                                        </button>
                                        <button
                                            class="p-2.5 text-slate-400 hover:text-emerald-600 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"
                                            title="Quick Approve">
                                            <CheckCircle2 class="h-4.5 w-4.5" />
                                        </button>
                                        <div class="h-6 w-[1px] bg-slate-100 dark:bg-slate-700 mx-1"></div>
                                        <button
                                            class="p-2.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl">
                                            <MoreHorizontal class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="px-8 py-6 bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div
                        class="flex items-center gap-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">
                        <span>Page 1 of 12</span>
                        <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                        <span class="text-blue-500">{{ filteredPayroll.length }} Records Found</span>
                    </div>
                    <div class="flex gap-3">
                        <button
                            class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-black text-slate-500 uppercase hover:bg-slate-100 transition-all active:scale-95 disabled:opacity-50"
                            disabled>
                            Prev
                        </button>
                        <button
                            class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-black text-slate-900 dark:text-white uppercase hover:bg-slate-100 transition-all active:scale-95 shadow-sm">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Optional: Custom scrollbar for the table area */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>