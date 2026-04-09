<template>
    <Head :title="`Conversation with ${supplier?.business_name}`" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-8">

            <!-- Header with back button -->
            <div class="flex items-center gap-4">
                <Link :href="route('eco.suppliers')" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                            Conversation with <span class="text-indigo-600">{{ supplier?.business_name }}</span>
                        </h1>
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-100 text-green-700">
                            {{ supplier?.status || 'Active' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ supplier?.representative_name }} · {{ supplier?.email }}</p>
                </div>
                <div class="ml-auto">
                    <button @click="checkSupplierCredit" class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[10px] font-black uppercase hover:bg-amber-100 transition">
                        <Shield class="h-4 w-4" /> Credit Check
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main conversation area -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-250px)]">
                    <!-- Messages -->
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div v-for="msg in messages" :key="msg.id" class="flex" :class="msg.sender_type === 'supplier' ? 'justify-start' : 'justify-end'">
                            <div :class="msg.sender_type === 'supplier' 
                                ? 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' 
                                : 'bg-indigo-600 text-white'"
                                class="max-w-[70%] rounded-2xl px-4 py-2 shadow-sm">
                                <p class="text-sm">{{ msg.message }}</p>
                                <div v-if="msg.meeting_data" class="mt-2 text-xs border-t border-white/20 pt-1">
                                    <Calendar class="inline h-3 w-3 mr-1" />
                                    Meeting: {{ msg.meeting_data.type }} at {{ msg.meeting_data.location }} on {{ formatDateTime(msg.meeting_data.scheduled_at) }}
                                </div>
                                <div v-if="msg.attachment" class="mt-1">
                                    <a :href="msg.attachment" target="_blank" class="text-xs underline flex items-center gap-1">
                                        <Paperclip class="h-3 w-3" /> Attachment
                                    </a>
                                </div>
                                <p class="text-[10px] opacity-70 mt-1 text-right">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                        <div v-if="isTyping" class="flex justify-start">
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl px-4 py-2 text-gray-500 text-sm">
                                Supplier is typing...
                            </div>
                        </div>
                    </div>

                    <!-- Message input -->
                    <div class="border-t border-gray-100 dark:border-gray-800 p-4">
                        <form @submit.prevent="sendMessage" class="flex gap-3">
                            <input v-model="newMessage" type="text" placeholder="Type your message..." 
                                class="flex-1 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="triggerFileUpload" class="p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200">
                                <Paperclip class="h-5 w-5 text-gray-500" />
                            </button>
                            <input ref="fileInput" type="file" class="hidden" @change="uploadAttachment">
                            <button type="submit" :disabled="sending" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition">
                                <Send v-if="!sending" class="h-5 w-5" />
                                <Loader2 v-else class="h-5 w-5 animate-spin" />
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar actions -->
                <div class="space-y-6">
                    <!-- Set Meeting Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-indigo-600" /> Schedule Meeting
                        </h3>
                        <form @submit.prevent="scheduleMeeting">
                            <div class="space-y-3">
                                <input v-model="meetingData.scheduled_at" type="datetime-local" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm">
                                <input v-model="meetingData.location" type="text" placeholder="Location (e.g., Zoom, Office)" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm">
                                <select v-model="meetingData.type" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 p-2 text-sm">
                                    <option value="onsite">On-site</option>
                                    <option value="video">Video Call</option>
                                    <option value="phone">Phone Call</option>
                                </select>
                                <button type="submit" :disabled="scheduling" class="w-full py-2 bg-indigo-100 text-indigo-700 rounded-xl font-bold text-sm hover:bg-indigo-200 transition">
                                    {{ scheduling ? 'Scheduling...' : 'Send Meeting Invite' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Request Quotation / Purchase Order Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                            <FileText class="h-4 w-4 text-indigo-600" /> Request Quotation
                        </h3>
                        <button @click="openRequestModal" class="w-full py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition">
                            Request Quotation / PO
                        </button>
                    </div>

                    <!-- Previous requests -->
                    <div v-if="requests.length > 0" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-3">Sent Requests</h3>
                        <div class="space-y-2">
                            <div v-for="req in requests" :key="req.id" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="flex justify-between items-center">
                                    <span class="font-mono text-xs">{{ req.request_number }}</span>
                                    <span :class="req.status === 'accepted' ? 'text-emerald-600' : req.status === 'rejected' ? 'text-red-600' : 'text-amber-600'" class="text-[9px] font-black uppercase">
                                        {{ req.status }}
                                    </span>
                                </div>
                                <p class="text-sm font-black mt-1">₱{{ formatPrice(req.grand_total) }}</p>
                                <p class="text-[10px] text-gray-500">{{ req.items_count }} items</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quotation / Purchase Request Modal -->
        <Teleport to="body">
            <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showRequestModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Request Quotation / Purchase Order</h3>
                        <button @click="showRequestModal = false" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <form @submit.prevent="submitRequest" class="space-y-4">
                            <div v-for="(item, idx) in requestItems" :key="idx" class="border p-3 rounded-xl space-y-2">
                                <div class="flex justify-between">
                                    <span class="font-bold text-sm">Item {{ idx+1 }}</span>
                                    <button type="button" @click="removeItem(idx)" v-if="requestItems.length > 1" class="text-red-500 text-xs">Remove</button>
                                </div>
                                <input v-model="item.material_name" type="text" placeholder="Material/Product name" required class="w-full rounded-lg border p-2 text-sm">
                                <input v-model.number="item.quantity" type="number" placeholder="Quantity" required class="w-full rounded-lg border p-2 text-sm">
                                <input v-model="item.unit" type="text" placeholder="Unit (e.g., kg, rolls)" required class="w-full rounded-lg border p-2 text-sm">
                                <input v-model.number="item.unit_price" type="number" step="0.01" placeholder="Target unit price (optional)" class="w-full rounded-lg border p-2 text-sm">
                                <textarea v-model="item.specs" placeholder="Specifications / requirements" rows="2" class="w-full rounded-lg border p-2 text-sm"></textarea>
                            </div>
                            <button type="button" @click="addItem" class="text-sm text-indigo-600 font-bold">+ Add another item</button>
                            
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div><label class="block text-xs font-bold">Required Delivery Date</label><input v-model="requestForm.delivery_date" type="date" required class="w-full rounded-lg border p-2"></div>
                                <div><label class="block text-xs font-bold">Payment Terms</label><input v-model="requestForm.payment_terms" required class="w-full rounded-lg border p-2" placeholder="e.g., Net 30"></div>
                                <div class="col-span-2"><label class="block text-xs font-bold">Notes / Remarks</label><textarea v-model="requestForm.notes" rows="2" class="w-full rounded-lg border p-2"></textarea></div>
                            </div>
                            
                            <button type="submit" :disabled="submitting" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition">
                                {{ submitting ? 'Sending...' : 'Send Request to Supplier' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, watch } from 'vue';
import { ArrowLeft, Shield, Calendar, Paperclip, Send, Loader2, FileText, X } from 'lucide-vue-next';

const props = defineProps({
    supplier: {
        type: Object,
        required: true
    },
    messages: {
        type: Array,
        default: () => []
    },
    requests: {
        type: Array,
        default: () => []
    }
});

const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const scheduling = ref(false);
const isTyping = ref(false);
const showRequestModal = ref(false);
const submitting = ref(false);
const fileInput = ref(null);

// Meeting form
const meetingData = ref({
    scheduled_at: '',
    location: '',
    type: 'video'
});

// Request form (quotation/purchase order)
const requestItems = ref([{ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' }]);
const requestForm = ref({
    delivery_date: '',
    payment_terms: '',
    notes: ''
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    sending.value = true;
    try {
        await router.post(route('eco.supplier.message', props.supplier.id), { message: newMessage.value });
        newMessage.value = '';
        scrollToBottom();
    } finally {
        sending.value = false;
    }
};

const triggerFileUpload = () => {
    fileInput.value.click();
};

const uploadAttachment = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('attachment', file);
    formData.append('message', 'Sent an attachment');
    await router.post(route('eco.supplier.message', props.supplier.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    fileInput.value.value = '';
    scrollToBottom();
};

const scheduleMeeting = async () => {
    scheduling.value = true;
    await router.post(route('eco.supplier.meeting', props.supplier.id), meetingData.value);
    meetingData.value = { scheduled_at: '', location: '', type: 'video' };
    scheduling.value = false;
    scrollToBottom();
};

const checkSupplierCredit = async () => {
    const res = await axios.get(route('eco.supplier.credit-check', props.supplier.id));
    alert(`Credit Status: ${res.data.is_good_payer ? 'Good Payer' : 'High Risk'}\nOutstanding Balance: ₱${res.data.outstanding}`);
};

const openRequestModal = () => {
    showRequestModal.value = true;
};

const addItem = () => {
    requestItems.value.push({ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' });
};

const removeItem = (idx) => {
    requestItems.value.splice(idx, 1);
};

const submitRequest = async () => {
    // Validate each item
    for (let item of requestItems.value) {
        if (!item.material_name || !item.quantity || !item.unit) {
            alert('Please fill all required fields for each item.');
            return;
        }
    }
    if (!requestForm.value.delivery_date || !requestForm.value.payment_terms) {
        alert('Please fill delivery date and payment terms.');
        return;
    }
    submitting.value = true;
    await router.post(route('eco.supplier.request', props.supplier.id), {
        items: requestItems.value,
        delivery_date: requestForm.value.delivery_date,
        payment_terms: requestForm.value.payment_terms,
        notes: requestForm.value.notes
    });
    submitting.value = false;
    showRequestModal.value = false;
    // reset form
    requestItems.value = [{ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' }];
    requestForm.value = { delivery_date: '', payment_terms: '', notes: '' };
    scrollToBottom();
};

const formatPrice = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDateTime = (date) => new Date(date).toLocaleString('en-PH');
const formatTime = (date) => new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

watch(() => props.messages, () => {
    scrollToBottom();
}, { deep: true });

onMounted(() => {
    scrollToBottom();
});
</script>