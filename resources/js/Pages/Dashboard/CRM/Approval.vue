<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Calendar, X, CheckCircle, XCircle, Plus, MessageSquare, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    pendingClients: {
        type: Array,
        default: () => []
    }
});

const meetingForm = useForm({ scheduled_at: '', meeting_type: '', location: '', notes: '' });
const noteForm    = useForm({ note: '' });
const rejectForm  = useForm({ reason: '' });

const selectedClient  = ref(null);
const showMeetingModal = ref(false);
const showNoteModal    = ref(false);   // ← was missing
const showRejectModal  = ref(false);

// ── Meeting ────────────────────────────────────────────────────────────────────
const openMeetingModal = (client) => {
    selectedClient.value = client;
    meetingForm.reset();
    showMeetingModal.value = true;
};
const scheduleMeeting = () => {
    meetingForm.post(route('crm.approval.meeting', selectedClient.value.id), {
        preserveScroll: true,
        onSuccess: () => { showMeetingModal.value = false; }
    });
};

// ── Note ───────────────────────────────────────────────────────────────────────
const openNoteModal = (client) => {       // ← was missing; button was submitting instantly
    selectedClient.value = client;
    noteForm.reset();
    showNoteModal.value = true;
};
const submitNote = () => {
    noteForm.post(route('crm.approval.note', selectedClient.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showNoteModal.value = false;
            noteForm.reset();
        }
    });
};

// ── Accept / Reject ────────────────────────────────────────────────────────────
const acceptClient = (client) => {
    if (confirm(`Accept ${client.company_name} as active partner?`)) {
        router.post(route('crm.approval.accept', client.id));
    }
};
const openRejectModal = (client) => {
    selectedClient.value = client;
    rejectForm.reset();
    showRejectModal.value = true;
};
const rejectClient = () => {
    rejectForm.post(route('crm.approval.reject', selectedClient.value.id), {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; }
    });
};

// ── Meeting status ─────────────────────────────────────────────────────────────
const updateMeetingStatus = (meetingId, status) => {
    router.patch(route('crm.approval.meeting.update', meetingId), { status });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6">
            <h1 class="text-2xl font-black mb-6">Pending Client Approvals</h1>

            <div v-if="pendingClients.length === 0" class="text-center py-10 text-gray-500">
                <AlertCircle class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p>No pending client registrations.</p>
            </div>

            <div
                v-for="client in pendingClients"
                :key="client.id"
                class="bg-white dark:bg-zinc-900 rounded-2xl p-6 mb-4 shadow-sm border"
            >
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold">{{ client.company_name }}</h2>
                        <p class="text-sm text-gray-500">{{ client.contact_person }} | {{ client.email }}</p>
                        <p class="text-sm">TIN: {{ client.tin_number }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="acceptClient(client)" class="bg-green-600 text-white px-4 py-2 rounded-lg">Accept</button>
                        <button @click="openRejectModal(client)" class="bg-red-600 text-white px-4 py-2 rounded-lg">Reject</button>
                    </div>
                </div>

                <!-- Notes display -->
                <div v-if="client.notes" class="mt-3 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">
                    {{ client.notes }}
                </div>

                <div class="mt-4 border-t pt-4">
                    <div class="flex gap-2">
                        <button
                            @click="openMeetingModal(client)"
                            class="flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1 rounded-lg"
                        >
                            <Plus class="w-4 h-4" /> Schedule Meeting
                        </button>
                        <!-- FIXED: now opens a modal instead of submitting with an empty note -->
                        <button
                            @click="openNoteModal(client)"
                            class="flex items-center gap-1 bg-gray-100 dark:bg-zinc-700 px-3 py-1 rounded-lg"
                        >
                            <MessageSquare class="w-4 h-4" /> Add Note
                        </button>
                    </div>

                    <!-- Meetings list -->
                    <div v-if="client.meetings && client.meetings.length" class="mt-3">
                        <h3 class="font-semibold">Meetings</h3>
                        <div
                            v-for="meeting in client.meetings"
                            :key="meeting.id"
                            class="text-sm p-2 bg-gray-50 dark:bg-zinc-800 rounded mt-1"
                        >
                            <div>{{ new Date(meeting.scheduled_at).toLocaleString() }} – {{ meeting.meeting_type }}</div>
                            <div>{{ meeting.location }}</div>
                            <div class="text-gray-500">{{ meeting.notes }}</div>
                            <div class="flex gap-2 mt-1">
                                <button
                                    v-if="meeting.status !== 'done'"
                                    @click="updateMeetingStatus(meeting.id, 'done')"
                                    class="text-green-600 text-xs"
                                >Mark Done</button>
                                <button
                                    v-if="meeting.status !== 'cancelled'"
                                    @click="updateMeetingStatus(meeting.id, 'cancelled')"
                                    class="text-red-600 text-xs"
                                >Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Meeting Modal ─────────────────────────────────────────────────── -->
        <div
            v-if="showMeetingModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="showMeetingModal = false"
        >
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                    <h2 class="font-bold">Schedule Meeting</h2>
                    <button @click="showMeetingModal = false"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="scheduleMeeting" class="p-6 space-y-4">
                    <input
                        type="datetime-local"
                        v-model="meetingForm.scheduled_at"
                        required
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600"
                    />
                    <select
                        v-model="meetingForm.meeting_type"
                        required
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600"
                    >
                        <option value="">Select type</option>
                        <option value="phone">Phone Call</option>
                        <option value="video">Video Call</option>
                        <option value="onsite">On‑site Meeting</option>
                    </select>
                    <input
                        type="text"
                        v-model="meetingForm.location"
                        placeholder="Location"
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600"
                    />
                    <textarea
                        v-model="meetingForm.notes"
                        placeholder="Notes"
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600"
                    ></textarea>
                    <div class="flex gap-2">
                        <button type="button" @click="showMeetingModal = false" class="flex-1 p-2 bg-gray-200 dark:bg-zinc-700 rounded">Cancel</button>
                        <button type="submit" :disabled="meetingForm.processing" class="flex-1 p-2 bg-blue-600 text-white rounded disabled:opacity-50">Schedule</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Add Note Modal (NEW) ──────────────────────────────────────────── -->
        <div
            v-if="showNoteModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="showNoteModal = false"
        >
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-gray-700 p-4 text-white flex justify-between items-center">
                    <h2 class="font-bold flex items-center gap-2">
                        <MessageSquare class="w-4 h-4" />
                        Add Note — {{ selectedClient?.company_name }}
                    </h2>
                    <button @click="showNoteModal = false"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="submitNote" class="p-6 space-y-4">
                    <textarea
                        v-model="noteForm.note"
                        placeholder="Write your note here..."
                        required
                        rows="4"
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600 resize-none focus:outline-none focus:ring-2 focus:ring-gray-400"
                    ></textarea>
                    <p v-if="noteForm.errors.note" class="text-red-500 text-xs">{{ noteForm.errors.note }}</p>
                    <div class="flex gap-2">
                        <button type="button" @click="showNoteModal = false" class="flex-1 p-2 bg-gray-200 dark:bg-zinc-700 rounded">Cancel</button>
                        <button type="submit" :disabled="noteForm.processing" class="flex-1 p-2 bg-gray-700 text-white rounded disabled:opacity-50">Save Note</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Reject Modal ──────────────────────────────────────────────────── -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="showRejectModal = false"
        >
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-red-600 p-4 text-white flex justify-between items-center">
                    <h2 class="font-bold">Reject Registration</h2>
                    <button @click="showRejectModal = false"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="rejectClient" class="p-6 space-y-4">
                    <textarea
                        v-model="rejectForm.reason"
                        placeholder="Reason for rejection"
                        required
                        rows="3"
                        class="w-full border rounded p-2 dark:bg-zinc-800 dark:border-zinc-600 resize-none"
                    ></textarea>
                    <p v-if="rejectForm.errors.reason" class="text-red-500 text-xs">{{ rejectForm.errors.reason }}</p>
                    <div class="flex gap-2">
                        <button type="button" @click="showRejectModal = false" class="flex-1 p-2 bg-gray-200 dark:bg-zinc-700 rounded">Cancel</button>
                        <button type="submit" :disabled="rejectForm.processing" class="flex-1 p-2 bg-red-600 text-white rounded disabled:opacity-50">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>