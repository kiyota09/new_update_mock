<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Shirt, Droplet, Factory, Iron, Box, CheckCircle, XCircle, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
    fabrics: Array,
    dyeJobs: Array,
    softenerJobs: Array,
    squeezerJobs: Array,
    ironJobs: Array,
    formJobs: Array,
    packages: Array,
});

const activeTab = ref('orders');

const passFabric = (fabricId, destination) => {
    router.post(route('man.staff.checker-quality.pass-fabric', fabricId), { destination });
};

const passDye = (dyeId, action) => {
    router.post(route('man.staff.checker-quality.pass-dye', dyeId), { action });
};

// ... similar for other passes
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <!-- Tabs -->
            <div class="flex space-x-4 border-b mb-6">
                <button @click="activeTab = 'orders'"
                    :class="activeTab === 'orders' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Orders
                </button>
                <button @click="activeTab = 'fabrics'"
                    :class="activeTab === 'fabrics' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Fabrics
                </button>
                <button @click="activeTab = 'dye'" :class="activeTab === 'dye' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Dye
                </button>
                <button @click="activeTab = 'softener'"
                    :class="activeTab === 'softener' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Softener & Squeezer
                </button>
                <button @click="activeTab = 'iron'" :class="activeTab === 'iron' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Iron
                </button>
                <button @click="activeTab = 'form'" :class="activeTab === 'form' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Form
                </button>
                <button @click="activeTab = 'packed'"
                    :class="activeTab === 'packed' ? 'border-blue-600 text-blue-600' : ''"
                    class="py-2 px-4 border-b-2 font-medium">
                    Packed
                </button>
            </div>

            <!-- Orders Tab -->
            <div v-if="activeTab === 'orders'">
                <div v-for="order in orders" :key="order.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ order.po_number }}</p>
                            <p>{{ order.product_name }} – {{ order.quantity }} pcs</p>
                        </div>
                        <div class="space-x-2">
                            <button @click="router.post(route('man.staff.checker-quality.check-inventory', order.id))"
                                class="bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                Check Inventory
                            </button>
                            <button @click="router.post(route('man.staff.checker-quality.start-production', order.id))"
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                Start Production
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fabrics Tab -->
            <div v-if="activeTab === 'fabrics'">
                <div v-for="fabric in fabrics" :key="fabric.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ fabric.code }}</p>
                            <p>Weight: {{ fabric.weight }} kg</p>
                        </div>
                        <div>
                            <button @click="passFabric(fabric.id, 'dyeing')"
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm mr-2">
                                Pass to Dyeing
                            </button>
                            <button @click="passFabric(fabric.id, 'softener')"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                Pass to Softener
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dye Tab -->
            <div v-if="activeTab === 'dye'">
                <div v-for="dye in dyeJobs" :key="dye.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ dye.code }}</p>
                            <p>Fabric: {{ dye.fabric.code }}</p>
                        </div>
                        <div>
                            <button @click="passDye(dye.id, 'quality')"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm mr-2">
                                Quality Pass
                            </button>
                            <button @click="passDye(dye.id, 'reject')"
                                class="bg-red-600 text-white px-3 py-1 rounded text-sm">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Softener & Squeezer Tab -->
            <div v-if="activeTab === 'softener'">
                <!-- Display softener jobs with status: softener -> squeezer -> quality check -->
                <div v-for="job in softenerJobs" :key="job.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div>
                        <p class="font-bold">{{ job.code }}</p>
                        <p>Fabric: {{ job.fabric.code }}</p>
                        <p>Status: {{ job.status }}</p>
                        <div v-if="job.status === 'softened'">
                            <button @click="passSoftener(job.id, 'quality')"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                Quality Pass (to iron)
                            </button>
                            <button @click="passSoftener(job.id, 'resoften')"
                                class="bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                Resoften
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Iron Tab -->
            <div v-if="activeTab === 'iron'">
                <div v-for="iron in ironJobs" :key="iron.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ iron.code }}</p>
                            <p>Fabric: {{ iron.fabric.code }}</p>
                        </div>
                        <div>
                            <button @click="passIron(iron.id, 'form')"
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm mr-2">
                                Form
                            </button>
                            <button @click="passIron(iron.id, 'pack')"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                Pack
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Tab -->
            <div v-if="activeTab === 'form'">
                <div v-for="form in formJobs" :key="form.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ form.code }}</p>
                            <p>Quantity: {{ form.quantity }} pcs</p>
                            <p>Product: {{ form.product.name }}</p>
                        </div>
                        <div>
                            <button @click="packForm(form.id)"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm mr-2">
                                Pack
                            </button>
                            <button @click="rejectForm(form.id)"
                                class="bg-red-600 text-white px-3 py-1 rounded text-sm">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packed Tab -->
            <div v-if="activeTab === 'packed'">
                <div v-for="pkg in packages" :key="pkg.id" class="bg-white p-4 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ pkg.code }}</p>
                            <p>Items: {{ pkg.items.length }} products</p>
                        </div>
                        <div>
                            <button @click="assignToOrder(pkg.id)"
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                Assign to Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>