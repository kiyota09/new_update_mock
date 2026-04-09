<template>
    <Head title="Support & Feedback" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <HelpCircle class="h-3.5 w-3.5" />
                        Client Care
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Support & <span class="text-indigo-600">Feedback</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Submit complaints or share your feedback with our CRM team.
                    </p>
                </div>
            </div>

            <!-- Contact Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
                    <Phone class="h-8 w-8 mb-3 opacity-80" />
                    <h3 class="text-lg font-black">Hotline</h3>
                    <p class="text-2xl font-bold mt-1">+63 (2) 8888-MONTI</p>
                    <p class="text-xs opacity-80 mt-2">Mon-Fri, 8AM – 6PM</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white shadow-lg">
                    <Mail class="h-8 w-8 mb-3 opacity-80" />
                    <h3 class="text-lg font-black">Email Support</h3>
                    <p class="text-xl font-bold mt-1">support@montitextile.ph</p>
                    <p class="text-xs opacity-80 mt-2">Response within 24 hours</p>
                </div>
            </div>

            <!-- Complaint/Feedback Form -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <form @submit.prevent="submitComplaint" class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Type *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.type" value="complaint" class="text-indigo-600">
                                <span class="text-sm font-bold">Complaint</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.type" value="feedback" class="text-indigo-600">
                                <span class="text-sm font-bold">Feedback / Suggestion</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Subject *</label>
                        <input v-model="form.subject" type="text" required
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Brief summary of your issue or suggestion">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Message *</label>
                        <textarea v-model="form.message" rows="6" required
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Please provide as much detail as possible..."></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Attachment (Optional)</label>
                        <div class="flex items-center gap-3">
                            <input type="file" @change="handleFileUpload" accept="image/*,application/pdf"
                                class="text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <span v-if="form.attachment" class="text-xs text-green-600">✓ File selected</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Max 5MB. Accepted: JPG, PNG, PDF</p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800">
                        <p class="text-xs font-medium text-blue-800 dark:text-blue-200 flex items-start gap-2">
                            <Info class="h-4 w-4 mt-0.5 flex-shrink-0" />
                            Your message will be sent directly to our Customer Relationship Management (CRM) team. 
                            A representative will respond within 2 business days.
                        </p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full py-4 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        {{ form.processing ? 'Sending...' : 'Submit to CRM' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                {{ toast.message }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { HelpCircle, Phone, Mail, Info, Loader2, Send } from 'lucide-vue-next';

const form = useForm({
    type: 'complaint',
    subject: '',
    message: '',
    attachment: null
});

const toast = ref({ show: false, type: 'success', message: '' });

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 4000);
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file && file.size > 5 * 1024 * 1024) {
        showToast('error', 'File size exceeds 5MB limit.');
        event.target.value = '';
        form.attachment = null;
        return;
    }
    form.attachment = file;
};

const submitComplaint = () => {
    if (!form.subject.trim() || !form.message.trim()) {
        showToast('error', 'Please fill in all required fields.');
        return;
    }
    
    form.post(route('client.support.complaint'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Your message has been sent to our CRM team.');
            form.reset('subject', 'message', 'attachment');
            form.type = 'complaint';
            // Clear file input
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = '';
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Failed to send. Please try again.';
            showToast('error', errorMsg);
        }
    });
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>