<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { 
    ArrowLeft, Shield, Calendar, Paperclip, Send, 
    Loader2, FileText, X, Download, Eye, Edit2
} from 'lucide-vue-next';

const props = defineProps({
    inquiry: Object,
    quotations: Array,
    allProducts: Array
});

const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const scheduling = ref(false);
const isTyping = ref(false);
const showQuotationModal = ref(false);
const showPreview = ref(false); 
const submitting = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]);

const meetingData = ref({
    scheduled_at: '',
    location: '',
    type: 'video'
});

// START: Updated Quotation Logic
const quotationItems = ref([
    { fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 }
]);

const quotationForm = ref({
    payment_terms: '',
    notes: ''
});

// Updated: Just sum the price field directly
const computedTotal = computed(() =>
    quotationItems.value.reduce((sum, item) => sum + (Number(item.price) || 0), 0)
);
// END: Updated Quotation Logic

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    });
};

const triggerFileUpload = () => fileInput.value.click();

const onFilesSelected = (e) => {
    selectedFiles.value.push(...Array.from(e.target.files));
    e.target.value = '';
};

const previewUrls = new Map();
const getFilePreview = (file) => {
    if (!previewUrls.has(file)) previewUrls.set(file, URL.createObjectURL(file));
    return previewUrls.get(file);
};
const revokePreview = (file) => {
    if (previewUrls.has(file)) { URL.revokeObjectURL(previewUrls.get(file)); previewUrls.delete(file); }
};
const removeFile = (i) => { revokePreview(selectedFiles.value[i]); selectedFiles.value.splice(i, 1); };

const openInNewTab = (url) => window.open(url, '_blank');

const handleSend = async () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;
    const formData = new FormData();
    formData.append('message', newMessage.value || '');
    selectedFiles.value.forEach((file, index) => formData.append(`files[${index}]`, file));
    router.post(route('eco.inquiry.message', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            selectedFiles.value.forEach(revokePreview);
            newMessage.value = '';
            selectedFiles.value = [];
            scrollToBottom();
        },
        onFinish: () => sending.value = false
    });
};

const deleteAttachment = (id) => {
    if (confirm('Permanently delete this attachment?')) {
        router.delete(route('client.attachment.destroy', id), { preserveScroll: true });
    }
};

const scheduleMeeting = async () => {
    scheduling.value = true;
    await router.post(route('eco.inquiry.meeting', props.inquiry.id), meetingData.value);
    meetingData.value = { scheduled_at: '', location: '', type: 'video' };
    scheduling.value = false;
    scrollToBottom();
};

const checkCredit = async () => {
    try {
        const res = await axios.get(route('eco.credit.check', props.inquiry.client_id));
        alert(`Credit Status: ${res.data.is_good_payer ? 'Good Payer' : 'High Risk'}\nOutstanding Balance: ₱${res.data.outstanding}`);
    } catch (e) {
        alert('Could not retrieve credit data.');
    }
};

const openQuotationModal = () => {
    showQuotationModal.value = true;
    showPreview.value = false;
};

const addItem = () => {
    quotationItems.value.push({ fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 });
};

const removeItem = (idx) => { quotationItems.value.splice(idx, 1); };

const goToPreview = () => {
    for (let item of quotationItems.value) {
        if (!item.fabric || !item.color || item.kilos <= 0 || item.price <= 0) {
            alert('Please fill out Fabric, Color, Kilos, and Price for all items.');
            return;
        }
    }
    if (!quotationForm.value.payment_terms) {
        alert('Please fill out the Payment Terms.');
        return;
    }
    showPreview.value = true;
};

const submitQuotation = async () => {
    submitting.value = true;
    await router.post(route('eco.inquiry.quotation', props.inquiry.id), {
        items: quotationItems.value,
        payment_terms: quotationForm.value.payment_terms,
        notes: quotationForm.value.notes
    });
    submitting.value = false;
    showQuotationModal.value = false;
    showPreview.value = false;
    quotationItems.value = [{ fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 }];
    quotationForm.value = { payment_terms: '', notes: '' };
    scrollToBottom();
};

const formatPrice = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDateTime = (date) => new Date(date).toLocaleString('en-PH');
const formatTime = (date) => new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const statusBadge = (status) => {
    const map = {
        open: 'bg-blue-100 text-blue-700',
        quotation_sent: 'bg-indigo-100 text-indigo-700',
        converted: 'bg-emerald-100 text-emerald-700'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

watch(() => props.inquiry.messages, () => scrollToBottom(), { deep: true });
onMounted(() => scrollToBottom());
</script>

<template>
    <Head :title="`Inquiry: ${inquiry.client?.company_name} - ${inquiry.product?.name}`" />
    <AuthenticatedLayout>
        <div class="w-full h-[calc(100vh-64px)] flex flex-col overflow-hidden">

            <div class="flex items-center gap-4 px-6 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
                <Link :href="route('eco.inquiries')" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3">
                        <h1 class="text-lg font-black text-gray-900 dark:text-white leading-tight truncate">
                            Conversation with <span class="text-indigo-600">{{ inquiry.client?.company_name }}</span>
                        </h1>
                        <span :class="statusBadge(inquiry.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase flex-shrink-0">
                            {{ inquiry.status }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 truncate">Product: {{ inquiry.product?.name }} (SKU: {{ inquiry.product?.sku }})</p>
                </div>
                <button @click="checkCredit"
                    class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[10px] font-black uppercase hover:bg-amber-100 transition">
                    <Shield class="h-4 w-4" /> Credit Check
                </button>
            </div>

            <div class="flex flex-1 overflow-hidden">

                <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 min-w-0">

                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/30 dark:bg-gray-950/30">
                        <div v-for="msg in inquiry.messages" :key="msg.id" class="flex flex-col"
                            :class="msg.sender_type === 'eco' ? 'items-end' : 'items-start'">

                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-4">
                                <div class="bg-gray-200/50 dark:bg-gray-800 px-4 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                                    <p class="text-[9px] font-black uppercase text-gray-500 tracking-widest">{{ msg.message }}</p>
                                </div>
                            </div>

                            <div v-else
                                :class="msg.sender_type === 'eco'
                                    ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none'
                                    : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-tl-none'"
                                class="max-w-[60%] px-4 py-3 shadow-sm relative group">

                                <p v-if="msg.message" class="text-sm whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>

                                <div v-if="msg.meeting_data" class="mt-2 text-xs border-t border-white/20 pt-1">
                                    <Calendar class="inline h-3 w-3 mr-1" />
                                    Meeting: {{ msg.meeting_data.type }} at {{ msg.meeting_data.location }} on {{ formatDateTime(msg.meeting_data.scheduled_at) }}
                                </div>

                                <div v-if="msg.attachments && msg.attachments.length" class="mt-3 space-y-2">
                                    <div v-for="file in msg.attachments" :key="file.id" class="relative group/file">
                                        <div v-if="file.file_type.startsWith('image/')" class="rounded-lg overflow-hidden border border-black/10 bg-white/10 p-1">
                                            <img :src="file.url" class="max-h-64 w-full object-cover rounded cursor-pointer hover:opacity-95 transition" @click="openInNewTab(file.url)" />
                                        </div>
                                        <div v-else class="flex items-center gap-3 p-3 rounded-xl bg-black/5 dark:bg-white/5 border border-black/10">
                                            <div class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                                <FileText class="h-5 w-5 text-indigo-600" />
                                            </div>
                                            <div class="flex-1 overflow-hidden">
                                                <a :href="file.url" target="_blank" download class="text-[10px] font-black truncate uppercase block hover:underline">{{ file.file_name }}</a>
                                                <p class="text-[8px] opacity-60 uppercase font-black">Click to download</p>
                                            </div>
                                            <a :href="file.url" download class="p-1.5 hover:bg-black/5 rounded-lg"><Download class="h-4 w-4" /></a>
                                        </div>
                                        <button v-if="msg.sender_type === 'eco'" @click="deleteAttachment(file.id)"
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover/file:opacity-100 transition shadow-lg z-10">
                                            <X class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>

                                <p class="text-[10px] opacity-70 mt-1 text-right">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="isTyping" class="flex justify-start">
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl px-4 py-2 text-gray-500 text-sm italic">ECO is typing...</div>
                        </div>
                    </div>

                    <div class="flex-shrink-0 border-t border-gray-100 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-2">
                            <div v-for="(file, i) in selectedFiles" :key="i"
                                class="relative h-16 w-16 flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                                <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center"><FileText class="h-6 w-6 text-gray-400" /></div>
                                <button @click="removeFile(i)" class="absolute top-0.5 right-0.5 bg-red-500 text-white rounded-full p-0.5 shadow-md"><X class="h-3 w-3" /></button>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1 flex items-center gap-2 bg-gray-50 dark:bg-gray-800 rounded-xl px-4 border border-gray-200 dark:border-gray-700">
                                <input v-model="newMessage" type="text" placeholder="Type your message..."
                                    class="flex-1 border-none bg-transparent py-2 text-sm focus:ring-0 text-gray-800 dark:text-gray-200 placeholder-gray-400"
                                    @keydown.enter.prevent="handleSend" />
                                <button type="button" @click="triggerFileUpload" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition">
                                    <Paperclip class="h-5 w-5 text-gray-400 hover:text-indigo-600" />
                                </button>
                                <input ref="fileInput" type="file" class="hidden" multiple @change="onFilesSelected" />
                            </div>
                            <button type="button" @click="handleSend"
                                :disabled="sending || (!newMessage.trim() && !selectedFiles.length)"
                                class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2 shadow-md">
                                <Send v-if="!sending" class="h-5 w-5" />
                                <Loader2 v-else class="h-5 w-5 animate-spin" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="w-80 xl:w-96 flex-shrink-0 overflow-y-auto bg-gray-50 dark:bg-gray-950 p-4 space-y-4">

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                        <h3 class="text-xs font-black uppercase tracking-widest mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                            <Calendar class="h-4 w-4 text-indigo-600" /> Schedule Meeting
                        </h3>
                        <form @submit.prevent="scheduleMeeting" class="space-y-3">
                            <input v-model="meetingData.scheduled_at" type="datetime-local" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800" />
                            <input v-model="meetingData.location" type="text" placeholder="Venue or Meeting Link" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800" />
                            <select v-model="meetingData.type" required
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800">
                                <option value="onsite">On-site</option>
                                <option value="video">Video Call</option>
                                <option value="phone">Phone Call</option>
                            </select>
                            <button type="submit" :disabled="scheduling"
                                class="w-full py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl font-black text-[10px] uppercase hover:bg-indigo-100 transition shadow-sm disabled:opacity-60">
                                Send Meeting Invite
                            </button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                        <h3 class="text-xs font-black uppercase tracking-widest mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                            <FileText class="h-4 w-4 text-indigo-600" /> Issue Quotation
                        </h3>
                        <button @click="openQuotationModal"
                            class="w-full py-2 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">
                            Create New Quotation
                        </button>
                    </div>

                    <div v-if="quotations.length > 0" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
                        <h3 class="text-xs font-black uppercase tracking-widest mb-3 text-gray-900 dark:text-white">Sent Quotations</h3>
                        <div class="space-y-4">
                            <div v-for="q in quotations" :key="q.id"
                                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-mono text-[10px] font-bold text-gray-600 dark:text-gray-400">{{ q.quotation_number }}</span>
                                    <span :class="{
                                        'text-emerald-600': q.status?.toLowerCase() === 'accepted' || q.status?.toLowerCase() === 'converted',
                                        'text-red-600': q.status?.toLowerCase() === 'rejected',
                                        'text-amber-600': q.status?.toLowerCase() === 'quotation_sent' || q.status?.toLowerCase() === 'open'
                                    }" class="text-[9px] font-black uppercase tracking-tighter">
                                        {{ q.status }}
                                    </span>
                                </div>

                                <div class="space-y-2 mb-3">
                                    <div v-for="(item, i) in q.items" :key="i" class="text-[10px] border-l-2 border-indigo-500 pl-2 py-1 bg-white/50 dark:bg-black/20 rounded-r-md">
                                        <p class="font-black text-gray-800 dark:text-gray-200 truncate">{{ item.fabric_name || item.fabric }}</p>
                                        <div class="flex flex-wrap gap-x-2 opacity-70 text-[9px]">
                                            <span>{{ item.color }}</span>
                                            <span>•</span>
                                            <span>{{ item.kilos }}kg</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between items-center text-[9px]">
                                        <span class="font-black text-gray-400 uppercase">Terms:</span>
                                        <span class="font-bold text-gray-600 dark:text-gray-300">{{ q.payment_terms }}</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-[9px] font-black text-gray-400 uppercase">Total:</span>
                                        <span class="text-xs font-black text-indigo-600">₱{{ formatPrice(q.grand_total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showQuotationModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showQuotationModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="font-black text-xl uppercase tracking-tighter">
                                {{ showPreview ? 'Review Quotation' : 'Issue Quotation' }}
                            </h3>
                            <p class="text-xs text-indigo-100 font-bold uppercase tracking-widest mt-1">Ref: {{ inquiry.client?.company_name }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button v-if="showPreview" @click="showPreview = false"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-xl text-[10px] font-black uppercase tracking-widest transition">
                                <Edit2 class="h-3.5 w-3.5" /> Edit
                            </button>
                            <button @click="showQuotationModal = false" class="p-2 hover:bg-white/20 rounded-xl transition">
                                <X class="h-6 w-6" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!showPreview" class="p-8 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900/50">
                        <div class="space-y-6">
                            <div class="space-y-4">
                                <div v-for="(item, idx) in quotationItems" :key="idx"
                                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-3xl shadow-sm space-y-4">
                                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <span class="font-black text-indigo-600 text-xs uppercase tracking-[0.2em]">Item #{{ idx + 1 }}</span>
                                        <button type="button" @click="removeItem(idx)" v-if="quotationItems.length > 1"
                                            class="text-red-500 hover:text-red-700 text-[10px] font-black uppercase tracking-widest transition">Remove</button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Fabric Name</label>
                                            <input v-model="item.fabric" type="text" readonly class="w-full rounded-2xl border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 text-sm text-gray-500 cursor-not-allowed" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Design Style</label>
                                            <input v-model="item.design" type="text" placeholder="Plain, Striped, etc." class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Colorway *</label>
                                            <input v-model="item.color" type="text" required placeholder="e.g. Midnight Blue" class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Kilos *</label>
                                            <input v-model.number="item.kilos" type="number" step="0.01" required class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Item Total Price (₱) *</label>
                                            <input v-model.number="item.price" type="number" step="0.01" required class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="addItem"
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-black uppercase tracking-[0.2em] ml-2 hover:underline">
                                    + Add Fabric Item
                                </button>
                            </div>

                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-3xl shadow-sm space-y-6">
                                <h4 class="font-black text-gray-900 dark:text-white text-xs uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 pb-3">Quotation Metadata</h4>
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Payment Terms *</label>
                                        <select v-model="quotationForm.payment_terms" required class="w-full rounded-2xl border-gray-200 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                            <option value="" disabled>Select Term</option>
                                            <option value="50% Downpayment - 50% COD">50% Downpayment - 50% COD</option>
                                            <option value="Cash on Delivery">Cash on Delivery</option>
                                            <option value="30 Days">30 Days</option>
                                            <option value="60 Days">60 Days</option>
                                            <option value="150 Days">150 Days</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Remarks</label>
                                        <textarea v-model="quotationForm.notes" rows="3" placeholder="Notes to client..." class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-indigo-600 p-6 rounded-3xl text-white shadow-xl shadow-indigo-200 dark:shadow-none flex justify-between items-center">
                                <span class="font-black uppercase tracking-widest text-xs opacity-80">Total Investment</span>
                                <h3 class="text-3xl font-black italic tracking-tighter">₱{{ formatPrice(computedTotal) }}</h3>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-8 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900/50">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

                            <div class="bg-indigo-600 px-4 py-1.5 text-center">
                                <span class="text-[8px] font-black uppercase text-white tracking-widest">Draft — Pending Send</span>
                            </div>

                            <div class="p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="text-[7px] text-gray-400 font-black uppercase">Client</span>
                                        <p class="text-[11px] font-black text-gray-900 dark:text-white">{{ inquiry.client?.company_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[7px] text-gray-400 font-black uppercase">Product</span>
                                        <p class="text-[11px] font-black text-gray-900 dark:text-white">{{ inquiry.product?.name }}</p>
                                    </div>
                                </div>

                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2">
                                    <div v-for="(item, idx) in quotationItems" :key="idx"
                                        class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-between mb-1.5">
                                            <div>
                                                <span class="text-[7px] text-gray-500 font-black uppercase dark:text-gray-400">Product Name:</span>
                                                <p class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 uppercase">{{ item.fabric }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-[7px] text-gray-500 font-black uppercase dark:text-gray-400">Line Total:</span>
                                                <p class="text-[10px] font-black text-gray-900 dark:text-gray-100">₱{{ formatPrice(item.price) }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[9px] pt-1.5 border-t border-gray-200 dark:border-gray-600">
                                            <div>
                                                <span class="text-[7px] text-gray-500 font-black uppercase dark:text-gray-400">Colorway:</span>
                                                <p class="font-semibold text-gray-700 dark:text-gray-300">{{ item.color }}</p>
                                            </div>
                                            <div>
                                                <span class="text-[7px] text-gray-500 font-black uppercase dark:text-gray-400">Weight:</span>
                                                <p class="font-semibold text-gray-700 dark:text-gray-300">{{ item.kilos }}kg</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-2.5 border border-indigo-100 dark:border-indigo-800">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[7px] text-gray-500 font-black uppercase dark:text-gray-400">Payment Terms:</span>
                                        <span class="font-bold text-indigo-700 dark:text-indigo-300 uppercase text-[10px]">{{ quotationForm.payment_terms }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-1">
                                    <span class="text-[9px] text-gray-600 font-black uppercase dark:text-gray-300">Grand Total:</span>
                                    <span class="text-lg font-black text-indigo-600">₱{{ formatPrice(computedTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex gap-4 shrink-0">
                        <button @click="showQuotationModal = false" type="button"
                            class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 bg-gray-50 dark:bg-gray-900 rounded-2xl hover:bg-gray-100 transition">
                            Cancel
                        </button>

                        <button v-if="!showPreview" @click="goToPreview" type="button"
                            class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition shadow-xl shadow-indigo-100 flex justify-center items-center gap-2">
                            <Eye class="h-4 w-4" /> Preview Quotation
                        </button>

                        <button v-else @click="submitQuotation" type="button" :disabled="submitting"
                            class="flex-[2] py-4 bg-emerald-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-700 transition shadow-xl shadow-emerald-100 flex justify-center items-center gap-2 disabled:opacity-60">
                            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else class="flex items-center gap-2"><Send class="h-4 w-4" /> Confirm & Send</span>
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.dark ::-webkit-scrollbar-thumb { background: #334155; }
</style>