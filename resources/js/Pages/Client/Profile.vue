<template>
    <Head title="Business Profile" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Building2 class="h-3.5 w-3.5" />
                        Account Management
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Company <span class="text-indigo-600">Profile</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Update your business information and delivery address.
                    </p>
                </div>
                <button @click="refresh" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Profile Form -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-8">
                    <!-- Company Information -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Briefcase class="h-4 w-4 text-indigo-500" /> Company Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Company Name *</label>
                                <input v-model="form.company_name" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Business Type *</label>
                                <input v-model="form.business_type" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">TIN Number</label>
                                <input v-model="form.tin_number" type="text"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Contact Person *</label>
                                <input v-model="form.contact_person" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Phone Number *</label>
                                <input v-model="form.phone" type="tel" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Email (read-only)</label>
                                <input :value="client?.email" type="email" disabled
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <MapPin class="h-4 w-4 text-indigo-500" /> Delivery Address
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Street Address *</label>
                                <textarea v-model="form.company_address" rows="2" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">City</label>
                                    <input v-model="form.city" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Province</label>
                                    <input v-model="form.province" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Postal Code</label>
                                    <input v-model="form.postal_code" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GPS Coordinates (for logistics) -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Navigation class="h-4 w-4 text-indigo-500" /> Precise Location (Optional)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Latitude</label>
                                <input v-model.number="form.latitude" type="number" step="any"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Longitude</label>
                                <input v-model.number="form.longitude" type="number" step="any"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                            </div>
                        </div>
                        <button type="button" @click="getCurrentLocation" class="mt-2 text-xs text-indigo-600 font-bold hover:underline">
                            Use my current location
                        </button>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" :disabled="form.processing"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin inline mr-2" />
                            Update Profile
                        </button>
                        <button type="button" @click="resetForm" class="px-8 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Toast Notification -->
            <Transition name="toast">
                <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                    :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                    {{ toast.message }}
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Building2, RefreshCw, Briefcase, MapPin, Navigation, Loader2 } from 'lucide-vue-next';

const page = usePage();
const client = ref(page.props.auth?.client);

const form = useForm({
    company_name: client.value?.company_name || '',
    business_type: client.value?.business_type || '',
    tin_number: client.value?.tin_number || '',
    contact_person: client.value?.contact_person || '',
    phone: client.value?.phone || '',
    company_address: client.value?.company_address || '',
    city: client.value?.city || '',
    province: client.value?.province || '',
    postal_code: client.value?.postal_code || '',
    latitude: client.value?.latitude || null,
    longitude: client.value?.longitude || null,
});

const toast = ref({ show: false, type: 'success', message: '' });

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const submit = () => {
    form.patch(route('client.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Profile updated successfully.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            showToast('error', firstError || 'Update failed.');
        }
    });
};

const resetForm = () => {
    form.company_name = client.value?.company_name || '';
    form.business_type = client.value?.business_type || '';
    form.tin_number = client.value?.tin_number || '';
    form.contact_person = client.value?.contact_person || '';
    form.phone = client.value?.phone || '';
    form.company_address = client.value?.company_address || '';
    form.city = client.value?.city || '';
    form.province = client.value?.province || '';
    form.postal_code = client.value?.postal_code || '';
    form.latitude = client.value?.latitude || null;
    form.longitude = client.value?.longitude || null;
};

const refresh = () => {
    router.reload({ only: ['auth'] });
};

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        showToast('error', 'Geolocation is not supported by your browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
            showToast('success', 'Location coordinates updated.');
        },
        (error) => {
            showToast('error', 'Unable to retrieve your location.');
        }
    );
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