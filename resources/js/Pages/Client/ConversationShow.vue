<template>
    <Head :title="`Conversation: ${inquiry.product?.name}`" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-4 p-4 lg:p-6">

            <div class="flex items-center gap-3">
                <Link :href="route('client.conversations')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <h1 class="text-lg font-black text-gray-900 dark:text-white leading-none">
                        {{ inquiry.product?.name }}
                    </h1>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tight">
                        SKU: {{ inquiry.product?.sku }} | Status: {{ formatStatus(inquiry.status) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-220px)]">
                    
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/30">
                        <div v-for="msg in inquiry.messages" :key="msg.id" class="flex flex-col" :class="msg.sender_type === 'client' ? 'items-end' : 'items-start'">
                            
                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-2">
                                <div class="bg-gray-200/50 dark:bg-gray-800 px-4 py-1 rounded-full border border-gray-200">
                                    <p class="text-[9px] font-black uppercase text-gray-500">{{ msg.message }}</p>
                                </div>
                            </div>

                            <div v-else :class="msg.sender_type === 'client' 
                                ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' 
                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-tl-none'"
                                class="max-w-[75%] px-4 py-2.5 shadow-sm group relative">
                                
                                <p class="text-xs whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>
                                
                                <div v-if="msg.attachments && msg.attachments.length" class="mt-2 space-y-2">
                                    <div v-for="file in msg.attachments" :key="file.id" class="relative group/file">
                                        <div v-if="file.file_type.startsWith('image/')" class="rounded-lg overflow-hidden border border-black/10">
                                            <img :src="file.url" class="max-w-full h-auto cursor-pointer" @click="window.open(file.url)" />
                                        </div>
                                        <div v-else class="flex items-center gap-2 p-2 bg-black/10 dark:bg-white/10 rounded-lg text-[10px] font-bold">
                                            <FileText class="h-3.5 w-3.5" />
                                            <a :href="file.url" target="_blank" class="truncate hover:underline uppercase">{{ file.file_name }}</a>
                                        </div>
                                        <button v-if="msg.sender_type === 'client'" @click="deleteFile(file.id)"
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover/file:opacity-100 transition shadow-lg">
                                            <X class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                                
                                <p class="text-[8px] opacity-60 mt-1 font-bold text-right uppercase">
                                    {{ formatTime(msg.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-2">
                            <div v-for="(file, i) in selectedFiles" :key="i" class="relative h-14 w-14 flex-shrink-0 bg-gray-100 rounded-xl border">
                                <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" class="h-full w-full object-cover rounded-xl" />
                                <div v-else class="h-full w-full flex items-center justify-center"><FileText class="h-6 w-6 text-gray-400" /></div>
                                <button @click="selectedFiles.splice(i, 1)" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full p-0.5 shadow-md">
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="handleSend" class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-2xl px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <input v-model="newMessage" type="text" placeholder="Type a message..." class="flex-1 bg-transparent border-none focus:ring-0 text-sm">
                            
                            <div class="flex items-center gap-1 border-l border-gray-200 pl-2">
                                <button type="button" @click="triggerFileUpload" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition">
                                    <Paperclip class="h-5 w-5 text-gray-400" />
                                </button>
                                <input ref="fileInput" type="file" class="hidden" multiple @change="onFilesSelected">

                                <button type="submit" :disabled="sending || (!newMessage.trim() && !selectedFiles.length)" 
                                    class="p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-md">
                                    <Send v-if="!sending" class="h-5 w-5" />
                                    <Loader2 v-else class="h-5 w-5 animate-spin" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="space-y-4 overflow-y-auto h-[calc(100vh-220px)] pr-1">
                    <div v-if="quotations.length === 0" class="bg-gray-50 rounded-2xl p-6 text-center border-2 border-dashed border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase">No active proposals yet</p>
                    </div>

                    <div v-for="quotation in quotations" :key="quotation.id" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                        <div :class="quotation.status === 'accepted' ? 'bg-emerald-600' : quotation.status === 'rejected' ? 'bg-red-600' : 'bg-indigo-600'" class="px-4 py-1 text-center">
                            <span class="text-[8px] font-black uppercase text-white">{{ quotation.status }}</span>
                        </div>

                        <div class="p-3">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-mono text-[10px] font-bold text-gray-900 dark:text-white">{{ quotation.quotation_number }}</h4>
                                <div class="text-right">
                                    <span class="text-[7px] text-black font-black uppercase dark:text-white">Delivery:</span>
                                    <p class="text-[10px] font-bold text-indigo-600">{{ formatDate(quotation.delivery_date) }}</p>
                                </div>
                            </div>

                            <div v-for="item in quotation.items" :key="item.id" class="p-2.5 rounded-xl bg-gray-50 mb-2 border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                                <div class="flex justify-between mb-1.5">
                                    <div class="flex flex-col">
                                        <span class="text-[7px] text-black font-black uppercase dark:text-white">Product Name:</span>
                                        <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 uppercase">{{ item.fabric }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[7px] text-black font-black uppercase dark:text-white">Line Total:</span>
                                        <p class="text-[10px] font-black text-gray-900 dark:text-gray-100">₱{{ formatPrice(item.price) }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 text-[9px] pt-1.5 border-t border-gray-200 dark:border-gray-600">
                                    <p><span class="text-black font-black uppercase text-[7px] dark:text-white">Colorway:</span><br>{{ item.color }}</p>
                                    <p><span class="text-black font-black uppercase text-[7px] dark:text-white">Weight:</span><br>{{ item.kilos }}kg</p>
                                </div>
                            </div>

                            <div class="bg-indigo-50 rounded-xl p-2 mb-3 text-[9px] border border-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800">
                                <div class="flex justify-between">
                                    <span class="text-black font-black uppercase text-[7px] dark:text-white">Payment Terms:</span>
                                    <span class="font-bold text-indigo-700 uppercase dark:text-indigo-300">{{ quotation.payment_terms }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[8px] text-black font-black uppercase dark:text-white">Grand Total:</span>
                                <span class="text-sm font-black text-indigo-600">₱{{ formatPrice(quotation.grand_total) }}</span>
                            </div>

                            <div v-if="quotation.status === 'sent'" class="flex gap-2">
                                <button @click="openRejectModal(quotation)" class="flex-1 py-1.5 border-2 border-red-100 text-red-600 rounded-lg text-[9px] font-black uppercase">Reject</button>
                                <button @click="acceptQuotation(quotation)" class="flex-1 py-1.5 bg-indigo-600 text-white rounded-lg text-[9px] font-black uppercase shadow-md">Accept Order</button>
                            </div>

                            <div v-if="quotation.status === 'rejected'" class="p-2 bg-red-50 rounded-lg border border-red-100 mt-2">
                                <p class="text-[7px] text-black font-black uppercase">Rejection Reason:</p>
                                <p class="text-[9px] text-red-600 italic leading-tight mb-1">{{ quotation.reject_reason }}</p>
                                <div v-if="quotation.request_new_quote" class="mt-1 flex items-center gap-1">
                                    <div class="w-1 h-1 bg-red-600 rounded-full"></div>
                                    <p class="text-[8px] font-black uppercase text-red-700 tracking-tighter">Revised Quote Requested</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="rejectModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="rejectModal.show = false">
                <div class="bg-white dark:bg-gray-900 w-full max-sm:max-w-sm rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 py-4 bg-red-600 text-white"><h3 class="font-black text-sm uppercase">Reject Quotation</h3></div>
                    <form @submit.prevent="submitReject" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Reason for rejection *</label>
                            <textarea v-model="rejectModal.reason" rows="3" required class="w-full rounded-xl border-gray-200 text-xs p-3" placeholder="Explain why..."></textarea>
                        </div>
                        
                        <label class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl cursor-pointer">
                            <input type="checkbox" v-model="rejectModal.requestNew" class="mt-0.5 rounded border-gray-300 text-red-600">
                            <div>
                                <p class="text-[10px] font-black uppercase text-gray-700 dark:text-gray-200">Request Revised Version</p>
                                <p class="text-[9px] text-gray-500">Check this if you want the agent to send a new quote.</p>
                            </div>
                        </label>

                        <button type="submit" :disabled="rejectModal.submitting" class="w-full py-2 bg-red-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest flex justify-center items-center gap-2">
                            <span v-if="rejectModal.submitting" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            Confirm
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, watch } from 'vue';
import { ArrowLeft, Paperclip, Send, Loader2, FileText, X } from 'lucide-vue-next';

const props = defineProps({ inquiry: Object, quotations: Array });
const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]);
const rejectModal = ref({ show: false, quotation: null, reason: '', requestNew: false, submitting: false });

const scrollToBottom = () => {
    nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; });
};

const triggerFileUpload = () => fileInput.value.click();

const onFilesSelected = (e) => {
    const files = Array.from(e.target.files);
    selectedFiles.value.push(...files);
};

const getFilePreview = (file) => URL.createObjectURL(file);

const handleSend = async () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('message', newMessage.value || '');
    selectedFiles.value.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });

    router.post(route('client.conversation.message', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            newMessage.value = '';
            selectedFiles.value = [];
            scrollToBottom();
        },
        onFinish: () => sending.value = false
    });
};

const deleteFile = (fileId) => {
    if(confirm('Delete this attachment?')) {
        router.delete(route('client.attachment.destroy', fileId));
    }
};

const acceptQuotation = (q) => {
    if (!confirm('Accept and create purchase order?')) return;
    router.post(route('client.quotation.accept', q.id));
};

const openRejectModal = (quotation) => {
    rejectModal.value = { show: true, quotation, reason: '', requestNew: false, submitting: false };
};

const submitReject = () => {
    if (!rejectModal.value.reason.trim()) return;
    rejectModal.value.submitting = true;
    router.post(route('client.quotation.reject', rejectModal.value.quotation.id), {
        reason: rejectModal.value.reason,
        request_new: rejectModal.value.requestNew
    }, {
        onSuccess: () => { rejectModal.value.show = false; },
        onFinish: () => { rejectModal.value.submitting = false; }
    });
};

const formatPrice = (v) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDate = (d) => new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
const formatTime = (d) => new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const formatStatus = (s) => s ? s.replace(/_/g, ' ').toUpperCase() : 'OPEN';

watch(() => props.inquiry.messages, () => scrollToBottom(), { deep: true });
onMounted(() => scrollToBottom());
</script>