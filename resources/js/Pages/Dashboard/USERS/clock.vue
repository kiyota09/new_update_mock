<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import {
    Clock,
    Calendar as CalendarIcon,
    CheckCircle2,
    Power,
    BellRing,
    Sunrise,
    Sunset,
    Moon,
    AlertCircle,
    Lock,
    X,
    Info,
    CheckCircle,
    Timer
} from 'lucide-vue-next';

const props = defineProps({
    today_log: Object,
    assigned_shift: Object,
    history: Array,
});

const page = usePage();

// --- UI STATE ---
const isConfirmModalOpen = ref(false);
const showSuccessAlert = ref(false);
const successMessage = ref('');
const liveWorkDuration = ref('00:00:00');

// --- CLOCK LOGIC ---
const isClockedIn = computed(() => !!props.today_log?.clock_in && !props.today_log?.clock_out);

/**
 * IMPLEMENTED RULES:
 * 1. Disable if no shift is assigned.
 * 2. Prevent clock-in more than 30 minutes before start.
 * 3. Allow exact time and late clock-ins.
 */
const isTooEarlyToClockIn = computed(() => {
    // If user is already clocked in or no shift is assigned, this rule doesn't apply here
    if (!props.assigned_shift?.shift_type || props.today_log?.clock_in) return false;

    // Shift Start Times defined in your ERP logic
    const shiftTimes = {
        'Morning': '08:00:00',
        'Afternoon': '16:00:00',
        'Graveyard': '00:00:00'
    };

    const startTimeStr = shiftTimes[props.assigned_shift.shift_type] || '08:00:00';

    try {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        // Create date object for the shift start today
        const shiftStart = new Date(`${year}-${month}-${day}T${startTimeStr}`);
        // Calculate the 30-minute early window
        const earliestAllowed = new Date(shiftStart.getTime() - 30 * 60000);

        // If 'now' is before that 30-minute window, it's too early
        return now < earliestAllowed;
    } catch (e) {
        return false;
    }
});

const shiftScheduleDisplay = computed(() => {
    return props.assigned_shift?.schedule_range || 'Not Assigned';
});

const clockButtonText = computed(() => {
    if (props.today_log?.clock_out) return 'Log Finished';
    if (isClockedIn.value) return 'Clock Out';
    if (!props.assigned_shift) return 'No Shift';
    if (isTooEarlyToClockIn.value) return 'Locked';
    return 'Clock In';
});

// --- ACTION HANDLERS ---
const openConfirmModal = () => {
    isConfirmModalOpen.value = true;
};

const handleClockToggle = () => {
    const wasClockedIn = isClockedIn.value;
    isConfirmModalOpen.value = false;

    router.post(route('employee.attendance.toggle'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = wasClockedIn ? 'Successfully Clocked Out' : 'Successfully Clocked In';
            showSuccessAlert.value = true;
            setTimeout(() => showSuccessAlert.value = false, 5000);
        }
    });
};

// --- LIVE TIME & TIMER DISPLAY ---
const currentTime = ref('');
let timerInterval = null;

const updateWorkDuration = () => {
    if (!isClockedIn.value || !props.today_log?.clock_in) {
        liveWorkDuration.value = '00:00:00';
        return;
    }

    try {
        const now = new Date();
        const [time, modifier] = props.today_log.clock_in.split(' ');
        let [hours, minutes] = time.split(':');

        if (hours === '12') hours = '00';
        if (modifier === 'PM') hours = parseInt(hours, 10) + 12;

        const clockInDate = new Date();
        clockInDate.setHours(hours, minutes, 0, 0);

        if (now < clockInDate) clockInDate.setDate(clockInDate.getDate() - 1);

        const diffMs = now - clockInDate;
        const diffHrs = Math.floor(diffMs / 3600000);
        const diffMins = Math.floor((diffMs % 3600000) / 60000);
        const diffSecs = Math.floor((diffMs % 60000) / 1000);

        liveWorkDuration.value = `${String(diffHrs).padStart(2, '0')}:${String(diffMins).padStart(2, '0')}:${String(diffSecs).padStart(2, '0')}`;
    } catch (e) {
        liveWorkDuration.value = '00:00:00';
    }
};

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    updateWorkDuration();
};

const getShiftIcon = (type) => {
    if (type === 'Afternoon') return Sunset;
    if (type === 'Graveyard') return Moon;
    return Sunrise;
};

const getStatusStyles = (status) => {
    if (!status) return 'bg-slate-100 text-slate-700 border-slate-200';
    const s = String(status);
    if (s.includes('Late') || s.includes('Early Out')) return 'bg-amber-100 text-amber-700 border-amber-200';
    if (s === 'On-Time') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    return 'bg-rose-100 text-rose-700 border-rose-200';
};

onMounted(() => {
    updateTime();
    timerInterval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>

    <Head title="Attendance Portal" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 bg-[#f8faff] min-h-screen relative overflow-hidden">
            <Transition enter-active-class="transform transition duration-500 ease-out"
                enter-from-class="translate-y-[-100%] opacity-0" enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-300 ease-in" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showSuccessAlert"
                    class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-[90%] max-w-md bg-emerald-500 text-white p-4 rounded-3xl shadow-2xl flex items-center justify-between border border-white/20 backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <CheckCircle class="size-6 text-white" />
                        <span class="font-black uppercase text-[11px] tracking-widest">{{ successMessage }}</span>
                    </div>
                    <button @click="showSuccessAlert = false"
                        class="p-1 hover:bg-white/20 rounded-full transition-colors">
                        <X class="size-4" />
                    </button>
                </div>
            </Transition>

            <div class="max-w-[1400px] mx-auto">
                <div v-if="!assigned_shift"
                    class="mb-6 p-5 bg-amber-50 border border-amber-100 rounded-[2rem] flex items-center gap-4 shadow-sm animate-pulse">
                    <div class="p-3 bg-amber-500 rounded-2xl text-white shadow-lg shadow-amber-100">
                        <AlertCircle class="size-5" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase text-amber-600 tracking-widest leading-none">Shift
                            Not Detected</p>
                        <p class="text-[9px] font-bold text-amber-500 uppercase tracking-widest mt-1">You have no
                            assigned schedule for today. Access Restricted.</p>
                    </div>
                </div>

                <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 -translate-y-4">
                    <div v-if="page.props.flash?.error"
                        class="mb-6 p-5 bg-rose-50 border border-rose-100 rounded-[2rem] flex items-center gap-4 text-rose-600 shadow-sm shadow-rose-100">
                        <div class="p-3 bg-rose-500 rounded-2xl text-white shadow-lg shadow-rose-100">
                            <Lock class="size-5" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest leading-none">Access Locked</p>
                            <span class="text-[9px] font-bold uppercase tracking-widest mt-1">{{ page.props.flash.error
                                }}</span>
                        </div>
                    </div>
                </Transition>

                <div v-if="assigned_shift"
                    class="mb-8 p-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[2rem] shadow-xl shadow-blue-200 overflow-hidden">
                    <div
                        class="bg-white/10 backdrop-blur-md px-8 py-4 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 text-white">
                            <div class="p-3 bg-white/20 rounded-2xl">
                                <component :is="getShiftIcon(assigned_shift.shift_type)" class="size-6" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Assigned Shift
                                </p>
                                <h2 class="text-xl font-black italic uppercase tracking-tight">
                                    {{ assigned_shift.shift_type }} Duty
                                    <span class="font-light opacity-60 ml-2">({{ shiftScheduleDisplay }})</span>
                                </h2>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-2 bg-black/20 px-4 py-2 rounded-xl text-white border border-white/10">
                            <BellRing class="size-4 animate-bounce" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Active Schedule</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                    <div class="lg:col-span-8 space-y-6 lg:space-y-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="w-full md:w-auto">
                                <h1
                                    class="text-2xl sm:text-3xl font-black text-slate-900 uppercase tracking-tight italic">
                                    Duty <span class="text-blue-600 font-light">Command</span></h1>
                                <p
                                    class="text-[10px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                                    Status: {{ isClockedIn ? 'Active Shift' : 'Off Duty' }}</p>
                            </div>

                            <div
                                class="w-full md:w-auto flex items-center p-1.5 bg-white rounded-2xl sm:rounded-[2rem] shadow-sm border border-slate-100">
                                <div class="px-4 sm:px-6 py-2 text-right border-r border-slate-100 mr-2">
                                    <p
                                        class="text-[8px] sm:text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                        System Time</p>
                                    <p class="text-sm sm:text-lg font-mono font-black text-slate-800 italic">{{
                                        currentTime }}</p>
                                </div>

                                <button @click="openConfirmModal"
                                    :disabled="!!today_log?.clock_out || isTooEarlyToClockIn || !assigned_shift" :class="[
                                        'group flex-1 md:flex-none flex items-center justify-center gap-2 sm:gap-3 px-4 sm:px-8 py-3 sm:py-4 rounded-xl sm:rounded-[1.5rem] transition-all duration-500 font-black uppercase text-[10px] sm:text-[11px] tracking-[0.15em]',
                                        isClockedIn ? 'bg-rose-500 text-white hover:bg-rose-600' : (isTooEarlyToClockIn || !assigned_shift ? 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200' : 'bg-emerald-500 text-white hover:bg-emerald-600'),
                                        today_log?.clock_out ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'shadow-lg hover:scale-[1.02]'
                                    ]">
                                    <component :is="isTooEarlyToClockIn && !isClockedIn ? Lock : Power"
                                        class="size-3 sm:size-4" />
                                    <span>{{ clockButtonText }}</span>
                                </button>
                            </div>
                        </div>

                        <Transition enter-active-class="duration-500 ease-out"
                            enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0">
                            <div v-if="isClockedIn"
                                class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center justify-between overflow-hidden relative group">
                                <div
                                    class="absolute right-0 top-0 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                                    <Timer class="size-48 -translate-y-8 translate-x-8" />
                                </div>
                                <div class="z-10">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">
                                        Current Work Session</p>
                                    <h2 class="text-5xl font-mono font-black text-slate-800 tracking-tighter italic">{{
                                        liveWorkDuration }}</h2>
                                </div>
                                <div class="z-10 flex flex-col items-end">
                                    <div
                                        class="flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100">
                                        <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Active
                                            Session</span>
                                    </div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-3">
                                        Clocked in at {{ today_log.clock_in }}</p>
                                </div>
                            </div>
                        </Transition>

                        <div v-if="isTooEarlyToClockIn && !isClockedIn"
                            class="p-5 bg-blue-50 border border-blue-100 rounded-[1.5rem] flex items-center gap-4">
                            <div class="p-2 bg-blue-500 rounded-lg text-white shadow-lg shadow-blue-100">
                                <Clock class="size-4" />
                            </div>
                            <p class="text-[10px] font-black uppercase text-blue-600 tracking-widest">Notice: Earliest
                                clock-in allowed is 30 minutes before your start time.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                class="bg-blue-600 rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-8 shadow-xl shadow-blue-200 text-white flex flex-col justify-between overflow-hidden relative min-h-[140px]">
                                <div class="z-10">
                                    <p
                                        class="text-blue-100 text-[9px] sm:text-[10px] font-black uppercase tracking-widest">
                                        Session Summary</p>
                                    <div class="mt-6 flex gap-4">
                                        <div class="bg-white/10 px-4 py-2 rounded-xl">
                                            <p class="text-[8px] uppercase opacity-60">In</p>
                                            <p class="text-xs font-bold">{{ today_log?.clock_in || '--:--' }}</p>
                                        </div>
                                        <div class="bg-white/10 px-4 py-2 rounded-xl">
                                            <p class="text-[8px] uppercase opacity-60">Out</p>
                                            <p class="text-xs font-bold">{{ today_log?.clock_out || '--:--' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="today_log?.status"
                                class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col justify-center min-h-[140px]">
                                <p class="text-slate-400 text-[9px] font-black uppercase tracking-widest mb-2">Cycle
                                    Analysis</p>
                                <div :class="getStatusStyles(today_log.status)"
                                    class="w-fit px-4 py-2 rounded-xl border text-xs font-black uppercase tracking-widest shadow-sm">
                                    {{ today_log.status }}</div>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                            <div class="p-6 sm:p-8 border-b border-slate-50 bg-slate-50/20">
                                <h3 class="text-[10px] sm:text-xs font-black text-slate-800 uppercase tracking-[0.2em]">
                                    Cycle History</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left min-w-[500px]">
                                    <thead>
                                        <tr
                                            class="text-[8px] sm:text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">
                                            <th class="px-6 py-5">Date</th>
                                            <th class="px-6 py-5 text-center">Logs</th>
                                            <th class="px-6 py-5 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="log in history" :key="log.id"
                                            class="hover:bg-slate-50/50 transition-colors group">
                                            <td
                                                class="px-6 py-5 font-black text-slate-700 italic text-xs group-hover:text-blue-600 transition-colors">
                                                {{ log.date }}</td>
                                            <td class="px-6 py-5 text-center font-mono text-[11px] text-slate-500">{{
                                                log.clock_in }} — {{ log.clock_out || '---' }}</td>
                                            <td class="px-6 py-5 text-right">
                                                <span :class="getStatusStyles(log.status)"
                                                    class="px-3 py-1.5 rounded-lg text-[8px] font-black uppercase border whitespace-nowrap">{{
                                                    log.status }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div
                            class="bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-sm border border-slate-100 p-6 sm:p-8 sticky top-6">
                            <h2 class="text-lg font-black text-slate-800 uppercase italic">Duty Tracking</h2>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-2">March 2026</p>
                            <div class="grid grid-cols-7 gap-1.5 mt-6 text-center text-slate-600">
                                <span v-for="d in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="d"
                                    class="text-[8px] font-black text-slate-300 uppercase pb-2">{{ d }}</span>
                                <div v-for="i in 31" :key="i"
                                    class="aspect-square flex items-center justify-center text-[10px] font-black rounded-lg border border-transparent hover:bg-blue-50 cursor-default transition-all duration-200">
                                    {{ i }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isConfirmModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="isConfirmModalOpen = false">
                </div>
                <div
                    class="relative bg-white w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 overflow-hidden transform transition-all scale-100">
                    <div :class="isClockedIn ? 'bg-rose-100 text-rose-500 shadow-rose-100' : 'bg-emerald-100 text-emerald-500 shadow-emerald-100'"
                        class="size-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-2xl transition-all duration-500">
                        <Power class="size-8 stroke-[3]" />
                    </div>
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight italic mb-2">Duty <span
                            class="text-blue-600 font-light italic">Confirmation</span></h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-4">Are you ready to {{
                        isClockedIn ? 'end your shift' : 'start your schedule' }}?</p>
                    <div class="mt-8 flex flex-col gap-3">
                        <button @click="handleClockToggle" :class="isClockedIn ? 'bg-rose-500' : 'bg-emerald-500'"
                            class="w-full py-4 rounded-2xl text-white font-black uppercase text-[11px] tracking-[0.2em] shadow-xl hover:scale-105 transition-all">Confirm
                            Action</button>
                        <button @click="isConfirmModalOpen = false"
                            class="w-full py-4 rounded-2xl bg-slate-50 text-slate-400 font-black uppercase text-[11px] tracking-[0.2em] hover:bg-slate-100 transition-all">Cancel</button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

button:active:not(:disabled) {
    transform: scale(0.96);
}
</style>