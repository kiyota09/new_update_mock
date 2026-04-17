<template>
    <Head title="Push Center - ECO" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-8 bg-white dark:bg-gray-900 p-8 rounded-[3rem] shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Send class="h-3.5 w-3.5" />
                        Dispatch Unit
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Push <span class="text-indigo-600">Center</span>
                    </h1>
                </div>

                <div class="flex-1 max-w-2xl relative group">
                    <Search class="absolute left-6 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search by PO#, Client, Color, or Control Number..." 
                        class="w-full pl-14 pr-6 py-5 bg-gray-50 dark:bg-gray-800 border-none rounded-[2rem] text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder:text-gray-400"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <button @click="refreshData" class="p-4 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-700 transition ml-2">
                        <RefreshCw class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
            </div>

            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button @click="activeTab = 'pending'"
                    :class="activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-6 font-black uppercase text-sm border-b-2 transition">
                    Pending Push ({{ filteredPendingOrders.length }})
                </button>
                <button @click="activeTab = 'pushed'"
                    :class="activeTab === 'pushed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-6 font-black uppercase text-sm border-b-2 transition">
                    Already Pushed ({{ filteredPushedOrders.length }})
                </button>
            </div>

            <div v-if="activeTab === 'pending' && filteredPendingOrders.length > 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden animate-in fade-in duration-500">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[1500px]">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[9px] font-black uppercase text-gray-400 tracking-[0.1em]">
                            <tr>
                                <th class="px-6 py-5">JO Number</th>
                                <th class="px-6 py-5">PO Number</th>
                                <th class="px-6 py-5">Client</th>
                                <th class="px-6 py-5">Yarn</th>
                                <th class="px-6 py-5">Color</th>
                                <th class="px-6 py-5">Design</th>
                                <th class="px-6 py-5">Control No.</th>
                                <th class="px-6 py-5 text-center">Quantity</th>
                                <th class="px-6 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="jo in filteredPendingOrders" :key="jo.id" class="group hover:bg-gray-50/50 transition-all text-[11px]">
                                <td class="px-6 py-6 font-black text-indigo-600 uppercase">{{ jo.jo_number }}</td>
                                <td class="px-6 py-6 font-mono font-bold">{{ jo.purchase_order_id }}</td>
                                <td class="px-6 py-6 font-bold">{{ jo.purchase_order?.client?.company_name }}</td>
                                <td class="px-6 py-6 uppercase">{{ jo.yarn_type }}</td>
                                <td class="px-6 py-6 font-black uppercase text-indigo-500">{{ jo.color }}</td>
                                <td class="px-6 py-6 uppercase">{{ jo.design }}</td>
                                <td class="px-6 py-6 font-mono text-amber-600 font-bold tracking-tighter">{{ jo.control_number }}</td>
                                <td class="px-6 py-6 text-center font-black">{{ jo.quantity }}kg</td>
                                <td class="px-6 py-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openSummary(jo)" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors" title="View Summary">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <button @click="pushToModule(jo, 'scm')" 
                                            :disabled="pushing[jo.id]"
                                            class="px-5 py-2 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase shadow-sm hover:bg-blue-700 transition disabled:opacity-50">
                                            <Loader2 v-if="pushing[jo.id]" class="h-3 w-3 animate-spin inline-block mr-1" />
                                            Push SCM
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="activeTab === 'pushed' && filteredPushedOrders.length > 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden animate-in fade-in duration-500">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">PO Number</th>
                                <th class="px-8 py-5">Client</th>
                                <th class="px-8 py-5 text-center">Pushed To</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="order in filteredPushedOrders" :key="order.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6 font-mono text-sm font-black">{{ order.po_number }}</td>
                                <td class="px-8 py-6 text-sm font-bold">{{ order.client?.company_name }}</td>
                                <td class="px-8 py-6 text-center">
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-[9px] font-black uppercase">
                                        {{ order.pushed_to }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="summaryModal.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="summaryModal.show = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in duration-200">
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-xl uppercase tracking-tighter">Quotation Summary</h3>
                            <p class="text-[10px] text-indigo-100 font-bold uppercase tracking-widest">{{ summaryModal.data.jo_number }}</p>
                        </div>
                        <button @click="summaryModal.show = false" class="p-2 hover:bg-white/10 rounded-xl transition"><X class="h-6 w-6" /></button>
                    </div>
                    
                    <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl">
                                <p class="text-[9px] font-black text-gray-400 uppercase">Client</p>
                                <p class="font-bold text-gray-900 dark:text-white">{{ summaryModal.data.purchase_order?.client?.company_name }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl">
                                <p class="text-[9px] font-black text-gray-400 uppercase">PO Reference</p>
                                <p class="font-bold text-gray-900 dark:text-white">{{ summaryModal.data.purchase_order?.po_number }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-indigo-500 tracking-widest">Active Batch Items</label>
                            <div class="border border-gray-100 dark:border-gray-800 rounded-3xl divide-y divide-gray-50 dark:divide-gray-800 overflow-hidden">
                                <div class="p-5 flex justify-between items-center bg-indigo-50/30">
                                    <div class="flex flex-col">
                                        <span class="text-[12px] font-black text-gray-900 dark:text-white uppercase">{{ summaryModal.data.color }}</span>
                                        <span class="text-[9px] font-mono text-indigo-500 font-bold">CONTROL: {{ summaryModal.data.control_number }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-lg font-black text-indigo-600">{{ summaryModal.data.quantity }}kg</span>
                                        <span class="text-[9px] font-bold text-gray-400 uppercase">{{ summaryModal.data.yarn_type }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Send, RefreshCw, Search, SearchX, FileText, Loader2, X, Eye 
} from 'lucide-vue-next';

const props = defineProps({
    salesOrders: { type: Array, default: () => [] },
    pushedOrders: { type: Array, default: () => [] }
});

const activeTab = ref('pending');
const searchQuery = ref('');
const pushing = ref({});
const summaryModal = ref({ show: false, data: {} });

const pendingOrders = computed(() => props.salesOrders ?? []);
const pushedOrders = computed(() => props.pushedOrders ?? []);

const filteredPendingOrders = computed(() => {
    if (!searchQuery.value) return pendingOrders.value;
    const s = searchQuery.value.toLowerCase();
    return pendingOrders.value.filter(jo => 
        jo.jo_number?.toLowerCase().includes(s) || 
        jo.purchase_order?.po_number?.toLowerCase().includes(s) ||
        jo.purchase_order?.client?.company_name?.toLowerCase().includes(s) ||
        jo.color?.toLowerCase().includes(s) ||
        jo.control_number?.toLowerCase().includes(s)
    );
});

const filteredPushedOrders = computed(() => {
    if (!searchQuery.value) return pushedOrders.value;
    const s = searchQuery.value.toLowerCase();
    return pushedOrders.value.filter(po => 
        po.po_number?.toLowerCase().includes(s) || 
        po.client?.company_name?.toLowerCase().includes(s) ||
        po.pushed_to?.toLowerCase().includes(s)
    );
});

const isResultEmpty = computed(() => {
    if (activeTab.value === 'pending') return filteredPendingOrders.value.length === 0;
    if (activeTab.value === 'pushed') return filteredPushedOrders.value.length === 0;
    return false;
});

const openSummary = (jo) => {
    summaryModal.value = { show: true, data: jo };
};

const pushToModule = async (order, module) => {
    pushing.value[order.id] = true;
    router.post(route(module === 'scm' ? 'eco.push.scm' : 'eco.push.ordermgmt', order.id), {}, {
        onSuccess: () => router.reload(),
        onFinish: () => pushing.value[order.id] = false
    });
};

const formatPrice = (v) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const refreshData = () => router.reload();
</script>

<style scoped>
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>