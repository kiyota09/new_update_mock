<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Package, Receipt, Send, Eye, Printer } from 'lucide-vue-next';

const props = defineProps({
    purchaseOrders: Array,
    invoices: Array,
});

const sendPO = (id) => {
    router.post(route('pro.manager.purchase-orders.send', id), {}, {
        preserveScroll: true,
    });
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const statusBadge = (status) => {
    const map = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        production: 'bg-amber-100 text-amber-700',
        shipping: 'bg-violet-100 text-violet-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        completed: 'bg-green-100 text-green-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>

    <Head title="PRO - Receipt" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Purchase Orders & Invoices</h2>
            <p class="text-sm text-gray-500">Manage POs and track invoices from suppliers.</p>
        </div>

        <!-- Purchase Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-8">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold">Purchase Orders</h3>
            </div>
            <div class="p-6">
                <div v-if="purchaseOrders.length === 0" class="text-center py-8 text-gray-400">No POs yet.</div>
                <div class="space-y-4">
                    <div v-for="po in purchaseOrders" :key="po.id" class="border rounded-xl p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex gap-2 items-center">
                                    <span class="font-mono text-sm font-bold">{{ po.po_number }}</span>
                                    <span :class="statusBadge(po.status)" class="text-xs px-2 py-0.5 rounded-full">{{
                                        po.status }}</span>
                                </div>
                                <p class="font-bold">{{ po.supplier_name }}</p>
                                <p class="text-xs text-gray-500">Issued: {{ po.issued_date }} | Expected: {{
                                    po.expected_delivery }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-emerald-600">{{ formatCurrency(po.grand_total) }}</p>
                                <button v-if="po.status === 'draft'" @click="sendPO(po.id)"
                                    class="mt-2 px-3 py-1 bg-blue-600 text-white rounded-lg text-xs">Send PO</button>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-500">
                            <p v-for="item in po.items" :key="item.id">{{ item.material_name }}: {{ item.qty }} {{
                                item.unit }} @ {{ formatCurrency(item.unit_price) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold">Invoices from Suppliers</h3>
            </div>
            <div class="p-6">
                <div v-if="invoices.length === 0" class="text-center py-8 text-gray-400">No invoices recorded.</div>
                <div class="space-y-3">
                    <div v-for="inv in invoices" :key="inv.id"
                        class="flex justify-between items-center p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="font-mono text-sm font-bold">{{ inv.invoice_number }}</p>
                            <p class="text-sm">{{ inv.supplier_name }}</p>
                            <p class="text-xs text-gray-500">Date: {{ inv.invoice_date }} | Due: {{ inv.due_date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-amber-600">{{ formatCurrency(inv.amount) }}</p>
                            <span :class="inv.status === 'paid' ? 'text-emerald-600' : 'text-red-600'"
                                class="text-xs font-bold uppercase">{{ inv.status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>