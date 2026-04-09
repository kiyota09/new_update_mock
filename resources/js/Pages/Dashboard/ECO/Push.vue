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
                    <button @click="showPOModal = true" 
                        class="flex items-center gap-2 px-6 py-4 bg-gray-900 text-white rounded-2xl text-[11px] font-black uppercase hover:bg-black transition-all shadow-xl shadow-gray-200 dark:shadow-none">
                        <FilePlus class="h-4 w-4" />
                        Create P.O
                    </button>
                    <button @click="showJOModal = true" 
                        class="flex items-center gap-2 px-6 py-4 bg-white text-indigo-600 border-2 border-indigo-50 rounded-2xl text-[11px] font-black uppercase hover:border-indigo-200 transition-all shadow-xl shadow-indigo-50 dark:shadow-none">
                        <Wrench class="h-4 w-4" />
                        Create J.O
                    </button>
                    <button @click="refreshData" class="p-4 rounded-2xl hover:bg-gray-100 dark:hover:bg-gray-700 transition ml-2">
                        <RefreshCw class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
            </div>

            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button @click="activeTab = 'created'"
                    :class="activeTab === 'created' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-6 font-black uppercase text-sm border-b-2 transition">
                    Created P.O. ({{ filteredCreatedPOs.length }})
                </button>
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

            <div v-if="searchQuery && isResultEmpty" class="py-20 text-center space-y-4">
                <div class="h-20 w-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto text-gray-400">
                    <SearchX class="h-10 w-10" />
                </div>
                <h3 class="text-xl font-black uppercase italic text-gray-400 tracking-tighter">No results matching "{{ searchQuery }}"</h3>
            </div>

            <div v-if="activeTab === 'created' && filteredCreatedPOs.length > 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden animate-in fade-in duration-500">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-indigo-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-indigo-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-10 py-5">PO Number</th>
                                <th class="px-10 py-5">Client Name</th>
                                <th class="px-10 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="po in filteredCreatedPOs" :key="po.id" class="group hover:bg-indigo-50/20 transition-all">
                                <td class="px-10 py-8 font-mono text-lg font-black text-indigo-600">{{ po.po_number }}</td>
                                <td class="px-10 py-8 text-lg font-black text-gray-900 dark:text-white">{{ po.client?.company_name }}</td>
                                <td class="px-10 py-8 text-center">
                                    <div class="flex gap-4 justify-center">
                                        <a v-if="po.attachment_path" :href="'/storage/' + po.attachment_path" target="_blank" class="flex items-center gap-1.5 text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase">
                                            <FileText class="h-4 w-4" /> View File
                                        </a>
                                        <button @click="pushToModule(po, 'scm')" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase shadow-sm">Push SCM</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                                <th class="px-6 py-5">Description</th>
                                <th class="px-6 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="jo in filteredPendingOrders" :key="jo.id" class="group hover:bg-gray-50/50 transition-all text-[11px]">
                                <td class="px-6 py-6 font-black text-indigo-600">{{ jo.jo_number }}</td>
                                <td class="px-6 py-6 font-mono font-bold">{{ jo.purchase_order?.po_number }}</td>
                                <td class="px-6 py-6 font-bold">{{ jo.purchase_order?.client?.company_name }}</td>
                                <td class="px-6 py-6 uppercase">{{ jo.yarn_type }}</td>
                                <td class="px-6 py-6 font-black uppercase text-indigo-500">{{ jo.color }}</td>
                                <td class="px-6 py-6 uppercase">{{ jo.design }}</td>
                                <td class="px-6 py-6 font-mono text-amber-600 font-bold">{{ jo.control_number }}</td>
                                <td class="px-6 py-6 text-center font-black">{{ jo.quantity }}kg</td>
                                <td class="px-6 py-6 max-w-[150px] truncate italic text-gray-500">{{ jo.description || '—' }}</td>
                                <td class="px-6 py-6 text-center">
                                    <button @click="pushToModule(jo, 'scm')" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase shadow-sm hover:bg-blue-700 transition">Push SCM</button>
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
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-[9px] font-black uppercase">{{ order.pushed_to }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Teleport to="body">
                <div v-if="showPOModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showPOModal = false">
                    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in duration-200">
                        <div class="px-8 py-6 bg-gray-900 text-white flex justify-between items-center">
                            <h3 class="font-black text-xl uppercase tracking-tighter">Manual P.O. Creation</h3>
                            <button @click="showPOModal = false" class="p-2 hover:bg-white/10 rounded-xl transition"><X class="h-6 w-6" /></button>
                        </div>
                        <form @submit.prevent="submitManualPO" class="p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Select Client *</label>
                                <select v-model="poForm.client_id" required class="w-full rounded-2xl border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 p-4 text-sm font-bold">
                                    <option value="" disabled>Choose a client...</option>
                                    <option v-for="client in clientList" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Upload P.O. File *</label>
                                <div @click="$refs.poFileInput.click()" class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl p-8 text-center cursor-pointer hover:border-indigo-400 transition-all">
                                    <input type="file" ref="poFileInput" class="hidden" @change="handleFileSelect" accept="image/*,.pdf,.doc,.docx">
                                    <div v-if="!filePreview" class="space-y-2 text-gray-400">
                                        <Paperclip class="h-6 w-6 mx-auto" />
                                        <p class="text-xs font-bold">Click to upload document</p>
                                    </div>
                                    <div v-else class="flex items-center gap-4 text-left">
                                        <div class="h-14 w-14 bg-indigo-100 rounded-xl flex items-center justify-center overflow-hidden">
                                            <img v-if="isImage" :src="filePreview" class="h-full w-full object-cover" />
                                            <FileText v-else class="h-8 w-8 text-indigo-600" />
                                        </div>
                                        <div class="flex-1 overflow-hidden">
                                            <p class="text-sm font-black truncate">{{ poForm.file?.name }}</p>
                                            <button type="button" @click.stop="filePreview = null; poForm.file = null" class="text-[10px] font-black text-red-500 uppercase">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" :disabled="submittingPO" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase hover:bg-indigo-700 transition">
                                <Loader2 v-if="submittingPO" class="h-4 w-4 animate-spin mr-2" /> Generate P.O.
                            </button>
                        </form>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showJOModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showJOModal = false">
                    <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-in zoom-in duration-200">
                        <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center shrink-0">
                            <div>
                                <h3 class="font-black text-xl uppercase tracking-tighter">Create Job Order (J.O.)</h3>
                                <p class="text-[10px] text-indigo-100 font-bold uppercase tracking-widest italic">Multi-Color Production Batch</p>
                            </div>
                            <button @click="showJOModal = false" class="p-2 hover:bg-white/20 rounded-xl transition"><X class="h-6 w-6" /></button>
                        </div>
                        
                        <form @submit.prevent="submitJO" class="p-8 space-y-8 overflow-y-auto flex-1">
                            <div class="grid grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-[2rem] border border-gray-100 dark:border-gray-700">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Select Purchase Order *</label>
                                    <select v-model="joForm.purchase_order_id" required class="w-full rounded-2xl border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-sm font-bold">
                                        <option value="" disabled>Search P.O. Number...</option>
                                        <option v-for="po in allPOList" :key="po.id" :value="po.id">{{ po.po_number }}</option>
                                    </select>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Client Name</label>
                                    <input :value="selectedClientName" type="text" readonly class="w-full rounded-2xl border-none bg-indigo-50 dark:bg-indigo-900/20 p-4 text-sm font-black text-indigo-600 cursor-not-allowed">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Yarn Type</label>
                                    <input v-model="joForm.yarn_type" type="text" placeholder="e.g. Cotton 30s" class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Design Style</label>
                                    <input v-model="joForm.design" type="text" placeholder="e.g. Plain / Stripe" class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between px-2">
                                    <label class="block text-[11px] font-black uppercase text-gray-500">Color-Specific Details</label>
                                    <button type="button" @click="addColorRow" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 dark:shadow-none">
                                        <Plus class="h-4 w-4" /> Add Color
                                    </button>
                                </div>

                                <div v-for="(row, index) in joForm.items" :key="index" class="grid grid-cols-12 gap-4 p-5 bg-white dark:bg-gray-800 rounded-[2rem] border-2 border-indigo-50 dark:border-gray-700 items-end shadow-sm animate-in slide-in-from-right-4 duration-300">
                                    <div class="col-span-3">
                                        <label class="text-[9px] uppercase font-black text-indigo-400 ml-1">Color Name</label>
                                        <input v-model="row.color" required class="w-full rounded-xl border-gray-100 dark:border-gray-700 p-3 text-xs font-bold uppercase" placeholder="e.g. Navy Blue">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-[9px] uppercase font-black text-indigo-400 ml-1">Quantity (KG)</label>
                                        <input v-model="row.quantity" type="number" required class="w-full rounded-xl border-gray-100 dark:border-gray-700 p-3 text-xs font-bold">
                                    </div>
                                    <div class="col-span-4">
                                        <label class="text-[9px] uppercase font-black text-indigo-400 ml-1">Item Description</label>
                                        <input v-model="row.description" class="w-full rounded-xl border-gray-100 dark:border-gray-700 p-3 text-xs font-bold">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-[9px] uppercase font-black text-indigo-400 ml-1">Control No.</label>
                                        <div class="p-3 text-[10px] font-mono font-black text-amber-700 bg-amber-50 dark:bg-amber-900/20 rounded-xl text-center border border-amber-100 dark:border-amber-800">
                                            {{ generatePreviewControlNo(index) }}
                                        </div>
                                    </div>
                                    <div class="col-span-1 text-center pb-1">
                                        <button v-if="joForm.items.length > 1" type="button" @click="joForm.items.splice(index, 1)" class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition">
                                            <Trash2 class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" :disabled="submittingJO" class="w-full py-5 bg-indigo-600 text-white rounded-[1.5rem] font-black text-[12px] uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all flex justify-center items-center gap-3">
                                <Loader2 v-if="submittingJO" class="h-5 w-5 animate-spin" />
                                Confirm Production Batch & Dispatch
                            </button>
                        </form>
                    </div>
                </div>
            </Teleport>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Send, RefreshCw, FilePlus, Wrench, X, 
    Paperclip, FileText, Loader2, Plus, Trash2, Search, SearchX
} from 'lucide-vue-next';

const props = defineProps({
    salesOrders: { type: Array, default: () => [] },
    pushedOrders: { type: Array, default: () => [] },
    createdPOs: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    allPOs: { type: Array, default: () => [] }
});

// View State & Search
const activeTab = ref('created');
const searchQuery = ref('');
const pushing = ref({});

// P.O. Modal State
const showPOModal = ref(false);
const submittingPO = ref(false);
const filePreview = ref(null);
const isImage = ref(false);
const poForm = ref({ client_id: '', file: null });

// J.O. Modal State
const showJOModal = ref(false);
const submittingJO = ref(false);
const joForm = ref({ 
    purchase_order_id: '', 
    yarn_type: '', 
    design: '', 
    items: [{ color: '', quantity: '', description: '' }] 
});

// RAW PROPS DATA
const pendingOrders = computed(() => props.salesOrders ?? []);
const createdPOList = computed(() => props.createdPOs ?? []);
const pushedOrders = computed(() => props.pushedOrders ?? []);
const clientList = computed(() => props.clients ?? []);
const allPOList = computed(() => props.allPOs ?? []);

// SEARCH FILTER LOGIC
const filteredCreatedPOs = computed(() => {
    if (!searchQuery.value) return createdPOList.value;
    const s = searchQuery.value.toLowerCase();
    return createdPOList.value.filter(po => 
        po.po_number?.toLowerCase().includes(s) || 
        po.client?.company_name?.toLowerCase().includes(s)
    );
});

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
    if (activeTab.value === 'created') return filteredCreatedPOs.value.length === 0;
    if (activeTab.value === 'pending') return filteredPendingOrders.value.length === 0;
    if (activeTab.value === 'pushed') return filteredPushedOrders.value.length === 0;
    return false;
});

// Reactive Client Name based on JO P.O. Selection
const selectedClientName = computed(() => {
    if (!joForm.value.purchase_order_id) return '';
    const po = allPOList.value.find(p => p.id === joForm.value.purchase_order_id);
    return po?.client?.company_name || 'N/A';
});

const generatePreviewControlNo = (index) => {
    const year = new Date().getFullYear().toString().substr(-2);
    const po = allPOList.value.find(p => p.id === joForm.value.purchase_order_id);
    const poSuffix = po ? po.po_number.substr(-3) : 'XXX';
    return `CTL-${year}${poSuffix}-${index + 1}`;
};

const addColorRow = () => {
    joForm.value.items.push({ color: '', quantity: '', description: '' });
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    poForm.value.file = file;
    isImage.value = file.type.startsWith('image/');
    filePreview.value = isImage.value ? URL.createObjectURL(file) : 'doc';
};

const submitManualPO = () => {
    if (!poForm.value.file || !poForm.value.client_id) return;
    submittingPO.value = true;
    const formData = new FormData();
    formData.append('client_id', poForm.value.client_id);
    formData.append('attachment', poForm.value.file);

    router.post(route('eco.po.manual_store'), formData, {
        forceFormData: true,
        onSuccess: () => {
            showPOModal.value = false;
            poForm.value = { client_id: '', file: null };
            filePreview.value = null;
            activeTab.value = 'created';
        },
        onFinish: () => submittingPO.value = false
    });
};

const submitJO = () => {
    submittingJO.value = true;
    router.post(route('eco.jo.manual_store'), joForm.value, {
        onSuccess: () => {
            showJOModal.value = false;
            joForm.value = { 
                purchase_order_id: '', 
                yarn_type: '', 
                design: '', 
                items: [{ color: '', quantity: '', description: '' }] 
            };
            activeTab.value = 'pending';
        },
        onFinish: () => submittingJO.value = false
    });
};

const pushToModule = async (order, module) => {
    pushing.value[order.id] = true;
    router.post(route(module === 'scm' ? 'eco.push.scm' : 'eco.push.ordermgmt', order.id), {}, {
        onSuccess: () => router.reload(),
        onFinish: () => pushing.value[order.id] = false
    });
};

const refreshData = () => router.reload();
</script>

<style scoped>
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>