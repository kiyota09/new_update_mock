<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { 
    ArrowLeft, Shield, Calendar, Paperclip, Send, 
    Loader2, FileText, X, Download 
} from 'lucide-vue-next';

const props = defineProps({
    inquiry: Object,
    quotations: Array,
    allProducts: Array   // from inventory for dropdown
});

const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const scheduling = ref(false);
const isTyping = ref(false);
const showQuotationModal = ref(false);
const submitting = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]); // Store multiple selected files for upload

// Meeting form
const meetingData = ref({
    scheduled_at: '',
    location: '',
    type: 'video'
});

// Items array for the quotation modal
const quotationItems = ref([
    { fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 }
]);

// Quotation metadata (Delivery Target Removed)
const quotationForm = ref({
    payment_terms: '', 
    notes: ''
});

// Computed total calculates sum of all item prices
const computedTotal = computed(() => {
    return quotationItems.value.reduce((sum, item) => {
        return sum + (Number(item.price) || 0);
    }, 0);
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const triggerFileUpload = () => {
    fileInput.value.click();
};

const onFilesSelected = (e) => {
    const files = Array.from(e.target.files);
    selectedFiles.value.push(...files);
};

const getFilePreview = (file) => URL.createObjectURL(file);

const openInNewTab = (url) => {
    window.open(url, '_blank');
};

const handleSend = async () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('message', newMessage.value || '');
    
    selectedFiles.value.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });

    router.post(route('eco.inquiry.message', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            newMessage.value = '';
            selectedFiles.value = [];
            scrollToBottom();
        },
        onFinish: () => sending.value = false
    });
};

const deleteAttachment = (id) => {
    if(confirm('Permanently delete this attachment?')) {
        router.delete(route('client.attachment.destroy', id), {
            preserveScroll: true
        });
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
    // Assuming axios is globally available or imported if needed
    try {
        const res = await axios.get(route('eco.credit.check', props.inquiry.client_id));
        alert(`Credit Status: ${res.data.is_good_payer ? 'Good Payer' : 'High Risk'}\nOutstanding Balance: ₱${res.data.outstanding}`);
    } catch (e) {
        alert('Could not retrieve credit data.');
    }
};

const openQuotationModal = () => {
    showQuotationModal.value = true;
};

// ── Item Management ──
const addItem = () => {
    quotationItems.value.push({ fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 });
};

const removeItem = (idx) => {
    quotationItems.value.splice(idx, 1);
};

const submitQuotation = async () => {
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

    submitting.value = true;
    
    await router.post(route('eco.inquiry.quotation', props.inquiry.id), {
        items: quotationItems.value, 
        payment_terms: quotationForm.value.payment_terms,
        notes: quotationForm.value.notes
    });
    
    submitting.value = false;
    showQuotationModal.value = false;
    quotationItems.value = [{ fabric: props.inquiry?.product?.name || '', design: '', color: '', kilos: 0, price: 0 }];
    quotationForm.value = { payment_terms: '', notes: '' };
    scrollToBottom();
};

const formatPrice = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (date) => new Date(date).toLocaleDateString('en-PH');
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
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-8">

            <div class="flex items-center gap-4">
                <Link :href="route('eco.inquiries')" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white leading-tight">
                            Conversation with <span class="text-indigo-600">{{ inquiry.client?.company_name }}</span>
                        </h1>
                        <span :class="statusBadge(inquiry.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                            {{ inquiry.status }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">Product: {{ inquiry.product?.name }} (SKU: {{ inquiry.product?.sku }})</p>
                </div>
                <div class="ml-auto">
                    <button @click="checkCredit" class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[10px] font-black uppercase hover:bg-amber-100 transition">
                        <Shield class="h-4 w-4" /> Credit Check
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-250px)]">
                    
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/30">
                        <div v-for="msg in inquiry.messages" :key="msg.id" class="flex flex-col" :class="msg.sender_type === 'eco' ? 'items-end' : 'items-start'">
                            
                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-4">
                                <div class="bg-gray-200/50 dark:bg-gray-800 px-4 py-1 rounded-full border border-gray-200">
                                    <p class="text-[9px] font-black uppercase text-gray-500 tracking-widest">{{ msg.message }}</p>
                                </div>
                            </div>

                            <div v-else :class="msg.sender_type === 'eco' 
                                ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' 
                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-tl-none'"
                                class="max-w-[75%] px-4 py-3 shadow-sm relative group">
                                
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

                    <div class="border-t border-gray-100 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-2">
                            <div v-for="(file, i) in selectedFiles" :key="i" class="relative h-16 w-16 flex-shrink-0 bg-gray-100 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center"><FileText class="h-6 w-6 text-gray-400" /></div>
                                <button @click="selectedFiles.splice(i, 1)" class="absolute top-0.5 right-0.5 bg-red-500 text-white rounded-full p-0.5 shadow-md"><X class="h-3 w-3" /></button>
                            </div>
                        </div>

                        <form @submit.prevent="handleSend" class="flex gap-3">
                            <div class="flex-1 flex items-center gap-2 bg-gray-50 dark:bg-gray-800 rounded-xl px-4 border border-gray-200 dark:border-gray-700">
                                <input v-model="newMessage" type="text" placeholder="Type your message..." class="flex-1 border-none bg-transparent py-2 text-sm focus:ring-0">
                                <button type="button" @click="triggerFileUpload" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition">
                                    <Paperclip class="h-5 w-5 text-gray-400 hover:text-indigo-600" />
                                </button>
                                <input ref="fileInput" type="file" class="hidden" multiple @change="onFilesSelected">
                            </div>

                            <button type="submit" :disabled="sending || (!newMessage.trim() && !selectedFiles.length)" 
                                    class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition flex items-center gap-2 shadow-md">
                                <Send v-if="!sending" class="h-5 w-5" />
                                <Loader2 v-else class="h-5 w-5 animate-spin" />
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                            <Calendar class="h-4 w-4 text-indigo-600" /> Schedule Meeting
                        </h3>
                        <form @submit.prevent="scheduleMeeting" class="space-y-3">
                            <input v-model="meetingData.scheduled_at" type="datetime-local" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800">
                            <input v-model="meetingData.location" type="text" placeholder="Venue or Meeting Link" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800">
                            <select v-model="meetingData.type" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm bg-gray-50 dark:bg-gray-800">
                                <option value="onsite">On-site</option>
                                <option value="video">Video Call</option>
                                <option value="phone">Phone Call</option>
                            </select>
                            <button type="submit" :disabled="scheduling" class="w-full py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl font-black text-[10px] uppercase hover:bg-indigo-100 transition shadow-sm">
                                Send Meeting Invite
                            </button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                            <FileText class="h-4 w-4 text-indigo-600" /> Issue Quotation
                        </h3>
                        <button @click="openQuotationModal" class="w-full py-2 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">
                            Create New Quotation
                        </button>
                    </div>

                    <div v-if="quotations.length > 0" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-3 text-gray-900 dark:text-white">Sent Quotations</h3>
                        <div class="space-y-3">
                            <div v-for="q in quotations" :key="q.id" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="font-mono text-xs font-bold text-gray-600 dark:text-gray-400">{{ q.quotation_number }}</span>
                                    <span :class="q.status === 'accepted' ? 'text-emerald-600' : q.status === 'rejected' ? 'text-red-600' : 'text-amber-600'" class="text-[9px] font-black uppercase tracking-tighter">
                                        {{ q.status }}
                                    </span>
                                </div>
                                <p class="text-sm font-black mt-1 text-indigo-600">₱{{ formatPrice(q.grand_total) }}</p>
                                
                                <div v-if="q.status === 'rejected'" class="mt-3 p-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg">
                                    <p class="text-[9px] text-red-600 dark:text-red-400 font-black uppercase leading-tight">Reason:</p>
                                    <p class="text-[10px] text-red-700 dark:text-red-300 italic mb-1">"{{ q.reject_reason || 'No reason provided' }}"</p>
                                    
                                    <div v-if="q.request_new_quote" class="mt-2 flex items-center gap-1.5 p-1.5 bg-red-600 rounded-md shadow-sm">
                                        <Loader2 class="h-3 w-3 text-white animate-spin" />
                                        <p class="text-[8px] font-black uppercase text-white leading-none tracking-tight">Client requested revised quote</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showQuotationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showQuotationModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="font-black text-xl uppercase tracking-tighter">Issue Quotation</h3>
                            <p class="text-xs text-indigo-100 font-bold uppercase tracking-widest mt-1">Ref: {{ inquiry.client?.company_name }}</p>
                        </div>
                        <button @click="showQuotationModal = false" class="p-2 hover:bg-white/20 rounded-xl transition"><X class="h-6 w-6" /></button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900/50">
                        <form @submit.prevent="submitQuotation" class="space-y-6">
                            <div class="space-y-4">
                                <div v-for="(item, idx) in quotationItems" :key="idx" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-3xl shadow-sm space-y-4">
                                    <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <span class="font-black text-indigo-600 text-xs uppercase tracking-[0.2em]">Item #{{ idx+1 }}</span>
                                        <button type="button" @click="removeItem(idx)" v-if="quotationItems.length > 1" class="text-red-500 hover:text-red-700 text-[10px] font-black uppercase tracking-widest transition">Remove</button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Fabric Name</label>
                                            <input v-model="item.fabric" type="text" readonly class="w-full rounded-2xl border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 text-sm text-gray-500 cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Design Style</label>
                                            <input v-model="item.design" type="text" placeholder="Plain, Striped, etc." class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Colorway *</label>
                                            <input v-model="item.color" type="text" required placeholder="e.g. Midnight Blue" class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Kilos *</label>
                                            <input v-model.number="item.kilos" type="number" step="0.01" required class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Item Total Price (₱) *</label>
                                            <input v-model.number="item.price" type="number" step="0.01" required class="w-full rounded-2xl border-gray-100 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="addItem" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-black uppercase tracking-[0.2em] ml-2 hover:underline">+ Add Fabric Item</button>
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
                                    <div class="">
                                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-1.5 ml-1">Remarks</label>
                                        <textarea v-model="quotationForm.notes" rows="3" placeholder="Notes to client..." class="w-full rounded-2xl border-gray-200 dark:border-gray-700 p-3 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-indigo-600 p-6 rounded-3xl text-white shadow-xl shadow-indigo-200 dark:shadow-none flex justify-between items-center">
                                <span class="font-black uppercase tracking-widest text-xs opacity-80">Total Investment</span>
                                <h3 class="text-3xl font-black italic tracking-tighter">₱{{ formatPrice(computedTotal) }}</h3>
                            </div>
                        </form>
                    </div>

                    <div class="px-8 py-6 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex gap-4 shrink-0">
                        <button @click="showQuotationModal = false" type="button" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 bg-gray-50 dark:bg-gray-900 rounded-2xl hover:bg-gray-100 transition">Cancel</button>
                        <button @click="submitQuotation" type="button" :disabled="submitting" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition shadow-xl shadow-indigo-100 flex justify-center items-center gap-2">
                            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else>Confirm & Issue Quotation</span>
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