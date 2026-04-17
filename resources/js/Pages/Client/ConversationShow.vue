<template>
    <Head :title="`Conversation: ${inquiry.product?.name}`" />
    <AuthenticatedLayout>
        <div class="w-full h-[calc(100vh-64px)] flex flex-col overflow-hidden">

            <div class="flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
                <Link :href="route('client.conversations')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-black text-gray-900 dark:text-white leading-none truncate">
                        {{ inquiry.product?.name }}
                    </h1>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tight">
                        SKU: {{ inquiry.product?.sku }} | Status: {{ formatStatus(inquiry.status) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 min-w-0">
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/30 dark:bg-gray-950/30">
                        <div v-for="msg in inquiry.messages" :key="msg.id" class="flex flex-col"
                            :class="msg.sender_type === 'client' ? 'items-end' : 'items-start'">

                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-2">
                                <div class="bg-gray-200/50 dark:bg-gray-800 px-4 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                                    <p class="text-[9px] font-black uppercase text-gray-500">{{ msg.message }}</p>
                                </div>
                            </div>

                            <div v-else
                                :class="msg.sender_type === 'client'
                                    ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none'
                                    : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-tl-none'"
                                class="max-w-[60%] px-4 py-2.5 shadow-sm group relative">

                                <p class="text-xs whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>

                                <div v-if="msg.attachments && msg.attachments.length" class="mt-2 space-y-2">
                                    <div v-for="file in msg.attachments" :key="file.id" class="relative group/file">
                                        <div v-if="file.file_type.startsWith('image/')" class="rounded-lg overflow-hidden border border-black/10">
                                            <img :src="file.url" class="max-w-full h-auto cursor-pointer" @click="openUrl(file.url)" />
                                        </div>
                                        <div v-else class="flex items-center gap-2 p-2 bg-black/10 dark:bg-white/10 rounded-lg text-[10px] font-bold">
                                            <FileText class="h-3.5 w-3.5" />
                                            <a :href="file.url" target="_blank" class="truncate hover:underline uppercase">{{ file.file_name }}</a>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[8px] opacity-60 mt-1 font-bold text-right uppercase">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-shrink-0 border-t border-gray-100 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-2">
                            <div v-for="(file, i) in selectedFiles" :key="i"
                                class="relative h-14 w-14 flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200">
                                <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" class="h-full w-full object-cover rounded-xl" />
                                <div v-else class="h-full w-full flex items-center justify-center"><FileText class="h-6 w-6 text-gray-400" /></div>
                                <button @click="confirmRemoveFile(i)" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full p-0.5 shadow-md"><X class="h-3 w-3" /></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-2xl px-3 py-2 border border-gray-200">
                            <input v-model="newMessage" type="text" placeholder="Type a message..." class="flex-1 bg-transparent border-none focus:ring-0 text-sm" @keydown.enter.prevent="handleSend" />
                            <button type="button" @click="triggerFileUpload" class="p-2 hover:bg-gray-200 rounded-xl transition"><Paperclip class="h-5 w-5 text-gray-400" /></button>
                            <input ref="fileInput" type="file" class="hidden" multiple @change="onFilesSelected" />
                            <button type="button" @click="handleSend" :disabled="sending || (!newMessage.trim() && !selectedFiles.length)" class="p-2 bg-indigo-600 text-white rounded-xl shadow-md transition">
                                <Send v-if="!sending" class="h-5 w-5" /><Loader2 v-else class="h-5 w-5 animate-spin" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="w-80 xl:w-96 flex-shrink-0 overflow-y-auto bg-gray-50 dark:bg-gray-950 p-4 space-y-4">
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Proposals</p>
                    <div v-for="quotation in quotations" :key="quotation.id" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                        <div :class="quotation.status === 'accepted' ? 'bg-emerald-600' : quotation.status === 'rejected' ? 'bg-red-600' : 'bg-indigo-600'" class="px-4 py-1 text-center">
                            <span class="text-[8px] font-black uppercase text-white tracking-widest">{{ quotation.status }}</span>
                        </div>
                        <div class="p-3">
                            <h4 class="font-mono text-[10px] font-bold text-gray-900 dark:text-white">{{ quotation.quotation_number }}</h4>
                            <div v-for="item in quotation.items" :key="item.id" class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 mb-2 border border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between mb-1.5">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 uppercase truncate">{{ item.fabric }}</span>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-[10px] font-black text-gray-900 dark:text-gray-100">₱{{ formatPrice(item.price) }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 text-[9px] pt-1.5 border-t border-gray-200 dark:border-gray-600">
                                    <p><span class="text-gray-500 font-black uppercase text-[7px] dark:text-gray-400">Colorway:</span><br>{{ item.color }}</p>
                                    <p><span class="text-gray-500 font-black uppercase text-[7px] dark:text-gray-400">Weight:</span><br>{{ item.kilos }}kg</p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[8px] text-gray-600 font-black uppercase dark:text-gray-300">Grand Total:</span>
                                <span class="text-sm font-black text-indigo-600">₱{{ formatPrice(quotation.grand_total) }}</span>
                            </div>
                            <div v-if="quotation.status === 'sent'" class="flex gap-2">
                                <button @click="openRejectModal(quotation)" class="flex-1 py-1.5 border-2 border-red-100 text-red-600 rounded-lg text-[9px] font-black uppercase hover:bg-red-50 transition">Reject</button>
                                <button @click="openAcceptModal(quotation)" class="flex-1 py-1.5 bg-indigo-600 text-white rounded-lg text-[9px] font-black uppercase shadow-md hover:bg-indigo-700 transition">Accept Order</button>
                            </div>
                            <div v-if="quotation.status === 'accepted'" class="mt-2">
                                <button @click="viewQuotationAttachments(quotation)" class="w-full py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-lg text-[9px] font-black uppercase flex items-center justify-center gap-2 hover:bg-emerald-100 transition">
                                    <FileText class="h-3 w-3" /> View Attachments
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="dialog.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-900 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
                    <div class="p-8 text-center">
                        <div :class="dialog.type === 'error' ? 'bg-red-100 text-red-600' : dialog.type === 'confirm' ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600'" 
                            class="h-16 w-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <X v-if="dialog.type === 'error'" class="h-8 w-8" />
                            <Send v-if="dialog.type === 'confirm'" class="h-8 w-8" />
                            <Loader2 v-if="dialog.type === 'info'" class="h-8 w-8 animate-spin" />
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-900 dark:text-white mb-2">{{ dialog.title }}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed font-bold uppercase">{{ dialog.message }}</p>
                    </div>
                    <div class="flex border-t border-gray-100 dark:border-gray-800">
                        <button v-if="dialog.type === 'confirm'" @click="dialog.show = false" class="flex-1 py-4 text-[10px] font-black uppercase text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition border-r border-gray-100 dark:border-gray-800">Cancel</button>
                        <button @click="handleDialogAction" class="flex-1 py-4 text-[10px] font-black uppercase" :class="dialog.type === 'error' ? 'text-red-600' : 'text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-800'">
                            {{ dialog.type === 'confirm' ? 'Confirm' : 'Got it' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="previewModal.show" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md" @click.self="previewModal.show = false">
                <button @click="previewModal.show = false" class="absolute top-6 right-6 text-white hover:text-red-500 transition-colors z-[80]"><X class="h-10 w-10"/></button>
                <div class="w-full max-w-6xl h-[90vh] flex flex-col gap-4">
                    <div class="flex justify-between items-center text-white px-2">
                        <h3 class="font-black uppercase tracking-widest text-sm">{{ previewModal.title }}</h3>
                    </div>
                    <div class="flex-1 bg-gray-900 rounded-3xl overflow-hidden relative border border-white/10 flex items-center justify-center">
                        <div v-if="previewModal.activeFile" class="w-full h-full flex items-center justify-center p-4">
                            <img v-if="previewModal.activeFile.file_type?.startsWith('image/') || previewModal.activeFile.type?.startsWith('image/')" :src="previewModal.activeFile.url || getFilePreview(previewModal.activeFile)" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" />
                            <div v-else class="bg-white dark:bg-gray-800 p-12 rounded-3xl flex flex-col items-center gap-6 shadow-2xl">
                                <FileText class="h-24 w-24 text-indigo-500" />
                                <p class="font-black text-gray-900 dark:text-white uppercase">{{ previewModal.activeFile.file_name || previewModal.activeFile.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="h-24 flex gap-3 overflow-x-auto py-2 px-1 scrollbar-hide">
                        <div v-for="(file, idx) in previewModal.files" :key="idx" @click="previewModal.activeFile = file" :class="previewModal.activeFile === file ? 'ring-4 ring-emerald-500 scale-105' : 'opacity-50 border-white/20'" class="h-full aspect-square flex-shrink-0 rounded-xl border-2 overflow-hidden cursor-pointer transition-all duration-200">
                            <img v-if="file.file_type?.startsWith('image/') || file.type?.startsWith('image/')" :src="file.url || getFilePreview(file)" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full bg-gray-800 flex items-center justify-center"><FileText class="h-6 w-6 text-white" /></div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="acceptModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="acceptModal.show = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border">
                    <div class="px-6 py-5 bg-emerald-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-sm uppercase tracking-widest">Accept Quotation</h3>
                        <button @click="acceptModal.show = false"><X class="h-4 w-4"/></button>
                    </div>
                    <form @submit.prevent="submitAccept" class="p-6 space-y-5">
                        <div v-if="acceptModal.quotation" class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[8px] font-black uppercase text-gray-400 mb-2">Review Summary</p>
                            <div class="space-y-2">
                                <div v-for="item in acceptModal.quotation.items" :key="item.id" class="flex justify-between text-[10px] font-bold">
                                    <span class="text-gray-600 uppercase">{{ item.fabric }}</span>
                                    <span class="text-indigo-600">₱{{ formatPrice(item.price) }}</span>
                                </div>
                                <div class="pt-2 border-t border-dashed border-gray-200 flex justify-between items-center text-sm font-black text-emerald-600 uppercase">
                                    <span>Total</span>
                                    <span>₱{{ formatPrice(acceptModal.quotation.grand_total) }}</span>
                                </div>
                            </div>
                        </div>
                        <textarea v-model="acceptModal.notes" rows="2" class="w-full rounded-2xl border-gray-100 text-xs p-3" placeholder="Notes (Optional)"></textarea>
                        <div>
                            <div class="flex items-center justify-between mb-2 px-1">
                                <label class="text-[10px] font-black uppercase text-gray-400">Attachments *</label>
                                <button type="button" @click="$refs.acceptFileInput.click()" class="text-[10px] font-black text-indigo-600 uppercase hover:underline">Add Files</button>
                            </div>
                            <div v-if="acceptModal.files.length" class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                                <div v-for="(file, i) in acceptModal.files" :key="i" class="relative group h-16 w-16 flex-shrink-0 bg-gray-100 rounded-xl overflow-hidden border border-gray-200">
                                    <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center"><FileText class="h-6 w-6 text-gray-400" /></div>
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-2 transition-opacity">
                                        <button type="button" @click="previewAcceptFile(file)" class="p-1 bg-white/20 rounded-lg"><Eye class="h-3 w-3 text-white"/></button>
                                        <button type="button" @click="confirmRemoveAcceptFile(i)" class="p-1 bg-red-500 rounded-lg"><Trash2 class="h-3 w-3 text-white"/></button>
                                    </div>
                                </div>
                            </div>
                            <div v-else @click="$refs.acceptFileInput.click()" class="border-2 border-dashed border-red-100 rounded-2xl p-6 text-center cursor-pointer hover:bg-red-50/30 transition">
                                <Paperclip class="h-5 w-5 text-red-300 mx-auto mb-2" />
                                <p class="text-[9px] text-red-500 font-black uppercase italic">Payment proof required.</p>
                            </div>
                        </div>
                        <input ref="acceptFileInput" type="file" class="hidden" multiple @change="onAcceptFilesSelected" />
                        <button type="submit" :disabled="acceptModal.submitting || acceptModal.files.length === 0" class="w-full py-3 bg-emerald-600 text-white rounded-2xl font-black text-[10px] uppercase flex justify-center items-center gap-2 disabled:opacity-50">
                            <Loader2 v-if="acceptModal.submitting" class="h-4 w-4 animate-spin" /> Confirm and Accept Order
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="rejectModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="rejectModal.show = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-sm rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 py-4 bg-red-600 text-white"><h3 class="font-black text-sm uppercase">Reject Quotation</h3></div>
                    <form @submit.prevent="submitReject" class="p-6 space-y-4">
                        <textarea v-model="rejectModal.reason" rows="3" required class="w-full rounded-xl border-gray-200 text-xs p-3" placeholder="Explain why..."></textarea>
                        <button type="submit" :disabled="rejectModal.submitting" class="w-full py-2 bg-red-600 text-white rounded-xl font-black text-[10px] uppercase">Confirm Reject</button>
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
import { ArrowLeft, Paperclip, Send, Loader2, FileText, X, Eye, Trash2 } from 'lucide-vue-next';

const props = defineProps({ inquiry: Object, quotations: Array });
const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]);

const rejectModal = ref({ show: false, quotation: null, reason: '', requestNew: false, submitting: false });
const acceptModal = ref({ show: false, quotation: null, notes: '', files: [], submitting: false });
const acceptFileInput = ref(null);
const previewModal = ref({ show: false, title: '', files: [], activeFile: null });

// Dialog Modal State
const dialog = ref({ show: false, type: 'info', title: '', message: '', onConfirm: null });
const showDialog = (type, title, message, onConfirm = null) => { dialog.value = { show: true, type, title, message, onConfirm }; };
const handleDialogAction = () => { if (dialog.value.onConfirm) dialog.value.onConfirm(); dialog.value.show = false; };

const scrollToBottom = () => { nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; }); };

const viewQuotationAttachments = (q) => {
    const acceptedMsg = props.inquiry.messages.find(m => m.is_system_event && m.message.includes(q.quotation_number) && m.message.includes('ACCEPTED') && m.attachments?.length > 0);
    if (acceptedMsg) { previewModal.value = { show: true, title: q.quotation_number, files: acceptedMsg.attachments, activeFile: acceptedMsg.attachments[0] }; } 
    else { showDialog('error', 'No Files', 'Could not find attachments for this quotation.'); }
};

const triggerFileUpload = () => fileInput.value.click();
const onFilesSelected = (e) => { selectedFiles.value.push(...Array.from(e.target.files)); e.target.value = ''; };
const getFilePreview = (file) => URL.createObjectURL(file);

// Confirmed delete for Main Input Selection
const confirmRemoveFile = (index) => {
    showDialog('confirm', 'Remove Attachment', 'Are you sure you want to remove this file from your message?', () => {
        selectedFiles.value.splice(index, 1);
    });
};

const handleSend = async () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;
    const formData = new FormData();
    formData.append('message', newMessage.value || '');
    selectedFiles.value.forEach((file, index) => formData.append(`files[${index}]`, file));
    router.post(route('client.conversation.message', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => { newMessage.value = ''; selectedFiles.value = []; scrollToBottom(); },
        onFinish: () => sending.value = false
    });
};

const openAcceptModal = (quotation) => { acceptModal.value = { show: true, quotation, notes: '', files: [], submitting: false }; };
const onAcceptFilesSelected = (e) => { acceptModal.value.files.push(...Array.from(e.target.files)); e.target.value = ''; };
const previewAcceptFile = (file) => { previewModal.value = { show: true, title: "Selected File", files: acceptModal.value.files, activeFile: file }; };

// Confirmed delete for Accept Modal Selection
const confirmRemoveAcceptFile = (index) => {
    showDialog('confirm', 'Remove File', 'Do you want to remove this attachment from the quotation acceptance?', () => {
        acceptModal.value.files.splice(index, 1);
    });
};

const submitAccept = () => {
    if (acceptModal.value.files.length === 0) { showDialog('error', 'Required', 'Please attach proof of payment.'); return; }
    showDialog('confirm', 'Accept Order', 'Proceed with accepting this quotation? This will generate the Purchase and Job Orders.', () => {
        acceptModal.value.submitting = true;
        const formData = new FormData();
        formData.append('notes', acceptModal.value.notes);
        acceptModal.value.files.forEach((file, index) => formData.append(`files[${index}]`, file));
        router.post(route('client.quotation.accept', acceptModal.value.quotation.id), formData, {
            forceFormData: true,
            onSuccess: () => { acceptModal.value.show = false; },
            onFinish: () => { acceptModal.value.submitting = false; }
        });
    });
};

const openRejectModal = (quotation) => { rejectModal.value = { show: true, quotation, reason: '', requestNew: false, submitting: false }; };
const submitReject = () => {
    rejectModal.value.submitting = true;
    router.post(route('client.quotation.reject', rejectModal.value.quotation.id), { reason: rejectModal.value.reason }, {
        onSuccess: () => { rejectModal.value.show = false; },
        onFinish: () => { rejectModal.value.submitting = false; }
    });
};

const formatPrice = (v) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatTime = (d) => new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const formatStatus = (s) => s ? s.replace(/_/g, ' ').toUpperCase() : 'OPEN';

watch(() => props.inquiry.messages, () => scrollToBottom(), { deep: true });
onMounted(() => scrollToBottom());
</script>