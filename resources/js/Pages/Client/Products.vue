<template>
    <Head title="Product Catalog" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Package class="h-3.5 w-3.5" />
                        B2B Catalog
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Premium <span class="text-indigo-600">Fabrics</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Browse our textile collection and request a quotation.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-3 flex-wrap">
                    <div class="relative min-w-[240px] group">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchQuery" @input="debouncedSearch" type="text"
                            placeholder="Search by name or SKU..."
                            class="pl-10 pr-4 py-3 w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>
                    <div class="relative">
                        <select v-model="selectedCategory" @change="filterProducts"
                            class="appearance-none pl-4 pr-10 py-3 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold">
                            <option value="All">All Categories</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>
                <div v-if="searchQuery || selectedCategory !== 'All'" class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">{{ filteredProducts.length }} results</span>
                    <button @click="clearFilters" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <X class="h-4 w-4 text-gray-400" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="product in filteredProducts" :key="product.id"
                    class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-xl hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 flex flex-col">

                    <div class="relative overflow-hidden bg-gray-100 dark:bg-gray-800 h-64">
                        <img v-if="product.images && product.images.length > 0" :src="product.images[0].url" :alt="product.name"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                            <Package class="h-16 w-16" />
                        </div>
                        <div class="absolute top-3 right-3">
                            <span v-if="product.stock_on_hand <= 0" class="bg-red-500/90 text-white text-[10px] font-black px-2 py-0.5 rounded-full">Out of Stock</span>
                            <span v-else-if="product.stock_on_hand < 100" class="bg-amber-500/90 text-white text-[10px] font-black px-2 py-0.5 rounded-full">Low Stock</span>
                        </div>
                    </div>

                    <div class="p-5 flex flex-col flex-1">
                        
                        <div class="mb-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Available Colors:</p>
                            
                            <template v-if="getParsedColors(product.colors).length > 0">
                                <div v-if="getParsedColors(product.colors).length <= 3" class="flex items-center gap-1.5 flex-wrap">
                                    <div v-for="c in getParsedColors(product.colors)" :key="c.hex" 
                                         class="w-5 h-5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm"
                                         :style="`background-color: ${c.hex}`" :title="c.name" />
                                </div>
                                
                                <div v-else class="relative w-full max-w-[200px]">
                                    <select class="w-full appearance-none pl-3 pr-8 py-1.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-gray-700 dark:text-gray-300 font-medium cursor-pointer">
                                        <option v-for="c in getParsedColors(product.colors)" :key="c.hex" :value="c.hex">
                                            {{ c.name }}
                                        </option>
                                    </select>
                                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3 w-3 text-gray-400 pointer-events-none" />
                                </div>
                            </template>
                            
                            <template v-else>
                                <div v-if="product.colorHex" class="flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm"
                                         :style="`background-color: ${product.colorHex}`" :title="product.colorName" />
                                    <span class="text-xs text-gray-500 font-medium">{{ product.colorName }}</span>
                                </div>
                                <p v-else class="text-[10px] text-gray-400 italic">No colors specified</p>
                            </template>
                        </div>

                        <div class="mb-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">{{ product.sku }}</span>
                                <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">{{ product.category }}</span>
                            </div>
                            <h3 class="font-black text-gray-900 dark:text-white text-base leading-tight">{{ product.name }}</h3>
                        </div>

                        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end">
                            <button @click="openInquiryModal(product)"
                                :disabled="product.stock_on_hand <= 0"
                                class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Inquire
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filteredProducts.length === 0" class="text-center py-20 bg-white dark:bg-gray-900 rounded-3xl border border-dashed border-gray-200">
                <Package class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500 font-bold">No products match your filters.</p>
                <button @click="clearFilters" class="mt-2 text-indigo-600 text-sm font-bold">Clear filters</button>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 flex items-center justify-center mx-auto mb-4 border-4 border-white dark:border-gray-900 shadow-sm">
                            <Package class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Confirm Inquiry</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            Would you like to request a formal quotation for <strong class="text-gray-700 dark:text-gray-300">{{ selectedProduct?.name }}</strong>? Our ECO team will reach out to you within 24 hours.
                        </p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 flex gap-3 border-t border-gray-100 dark:border-gray-800">
                        <button @click="showModal = false" :disabled="submitting" class="flex-1 py-3 text-sm font-bold text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 rounded-xl transition">
                            Cancel
                        </button>
                        <button @click="submitInquiry" :disabled="submitting" class="flex-1 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm shadow-indigo-500/20 disabled:opacity-50 flex justify-center items-center gap-2">
                            <span v-if="submitting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else>
                                <Check class="w-4 h-4" />
                                <span>Confirm</span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

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
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Package, Search, ChevronDown, X, Check } from 'lucide-vue-next'; 

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    }
});

// Helper function to safely parse the colors array
const getParsedColors = (colors) => {
    if (!colors) return [];
    if (Array.isArray(colors)) return colors;
    try {
        return JSON.parse(colors) || [];
    } catch (e) {
        return [];
    }
};

// Filter state
const searchQuery = ref('');
const selectedCategory = ref('All');

// Interactive states
const submitting = ref(false); 
const showModal = ref(false);
const selectedProduct = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

// Categories derived from products
const categories = computed(() => {
    const cats = new Set(props.products.map(p => p.category).filter(Boolean));
    return Array.from(cats);
});

// Filtered products
const filteredProducts = computed(() => {
    let list = props.products;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q));
    }
    if (selectedCategory.value !== 'All') {
        list = list.filter(p => p.category === selectedCategory.value);
    }
    return list;
});

let debounceTimer;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {}, 300);
};

const filterProducts = () => {};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'All';
};

const openInquiryModal = (product) => {
    selectedProduct.value = product;
    showModal.value = true;
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const submitInquiry = async () => {
    if (!selectedProduct.value) return;
    
    submitting.value = true;
    try {
        await router.post(route('client.products.inquire', selectedProduct.value.id), {
            message: `I would like to request more information and a formal quotation for ${selectedProduct.value.name}.`
        });
        showModal.value = false;
        showToast('success', 'Inquiry sent! You can continue the conversation in "My Conversations".');
    } catch (error) {
        showToast('error', 'Failed to send inquiry. Please try again.');
    } finally {
        submitting.value = false;
    }
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