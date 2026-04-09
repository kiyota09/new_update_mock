<template>
    <Head title="Product Catalog - ECO" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Package class="h-3.5 w-3.5" />
                        Inventory Feed
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Product <span class="text-indigo-600">Store</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Live product catalog synchronized with the Inventory module.
                    </p>
                </div>
                <button @click="exportCatalog"
                    class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-[11px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all flex items-center gap-2">
                    <Download class="h-4 w-4" /> Export Catalog
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Products</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ products.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Categories</p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ categories.length - 1 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Active SKUs</p>
                    <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ products.filter(p => p.status === 'Active').length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Low Stock Alert</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ lowStockCount }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-3 flex-wrap">
                    <div class="relative min-w-[240px] group">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-indigo-600" />
                        <input v-model="searchQuery" @input="debouncedSearch" type="text"
                            placeholder="Search by name, SKU, product ID..."
                            class="pl-10 pr-4 py-3 w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>
                    <div class="relative">
                        <select v-model="selectedCategory" @change="filterProducts"
                            class="appearance-none pl-4 pr-10 py-3 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold">
                            <option v-for="cat in categories" :key="cat">{{ cat }}</option>
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
                    class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-xl hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 flex flex-col cursor-pointer"
                    @click="openDetail(product)">

                    <div class="relative overflow-hidden bg-gray-100 dark:bg-gray-800 h-64">
                        <div v-if="product.images && product.images.length > 0" class="relative w-full h-full">
                            <img :src="product.images[currentImageIndex[product.id] || 0]" :alt="product.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            <div v-if="product.images.length > 1" class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                                <button v-for="(_, idx) in product.images" :key="idx"
                                    @click.stop="setImageIndex(product.id, idx)"
                                    :class="['w-1.5 h-1.5 rounded-full transition-all', ((currentImageIndex[product.id] || 0) === idx) ? 'bg-white scale-125' : 'bg-white/50']" />
                            </div>
                        </div>
                        <div v-else class="h-full w-full flex items-center justify-center text-gray-300">
                            <Package class="h-16 w-16" />
                        </div>

                        <div class="absolute top-3 right-3 z-10">
                            <span :class="product.status === 'Active' ? 'bg-emerald-500/90 text-white' : 'bg-slate-500/90 text-white'"
                                class="text-[10px] font-black px-2 py-0.5 rounded-full backdrop-blur-sm uppercase">
                                {{ product.status }}
                            </span>
                        </div>
                    </div>

                    <div class="p-5 flex flex-col gap-3 flex-1">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                    {{ product.sku }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                                    {{ product.category }}
                                </span>
                            </div>
                            <h3 class="font-black text-gray-900 dark:text-white text-base leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ product.name }}
                            </h3>
                        </div>

                        <!-- <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Weight class="w-3.5 h-3.5" />
                                <span>{{ product.weight || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Ruler class="w-3.5 h-3.5" />
                                <span class="truncate">{{ product.dimensions || '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Boxes class="w-3.5 h-3.5" />
                                <span>{{ product.stock_on_hand?.toLocaleString() || 0 }} units</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <Tag class="w-3.5 h-3.5" />
                                <span>MOQ {{ product.moq || '—' }}</span>
                            </div>
                        </div> -->

                        <!-- <div class="pt-3 mt-auto border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Unit Price</p>
                                <p class="text-lg font-black text-indigo-600 dark:text-indigo-400">₱{{ formatPrice(product.selling_price) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Margin</p>
                                <p class="text-sm font-black text-emerald-600">{{ marginPercent(product) }}%</p>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>

            <div v-if="filteredProducts.length === 0"
                class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700">
                <Package class="w-16 h-16 mb-4 opacity-30" />
                <p class="font-bold text-gray-500">No products match your filters.</p>
                <button @click="clearFilters" class="mt-3 text-sm text-indigo-600 font-bold hover:underline">
                    Clear filters
                </button>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showDetailModal && selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="showDetailModal = false">
                <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-8 py-6 bg-indigo-600 text-white flex justify-between items-center flex-shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center">
                                <Eye class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tighter italic">Product Details</h3>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">{{ selectedProduct.sku }}</p>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="p-2 bg-white/10 rounded-xl hover:bg-white/20 transition-all">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl overflow-hidden">
                                    <img v-if="selectedProduct.images && selectedProduct.images[0]" :src="selectedProduct.images[0]" :alt="selectedProduct.name"
                                        class="w-full object-cover max-h-80" />
                                    <div v-else class="h-64 flex items-center justify-center text-gray-400">
                                        <Package class="h-16 w-16" />
                                    </div>
                                </div>
                                <div v-if="selectedProduct.images && selectedProduct.images.length > 1" class="flex gap-2 overflow-x-auto pb-2">
                                    <img v-for="img in selectedProduct.images" :key="img" :src="img"
                                        class="h-20 w-20 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-700 cursor-pointer hover:border-indigo-500 transition-all" />
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ selectedProduct.name }}</h2>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="font-mono text-xs text-gray-500">{{ selectedProduct.product_id }}</span>
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-xs text-gray-500">{{ selectedProduct.category }}</span>
                                    </div>
                                </div>

                                <!-- <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-5 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Unit Price</span>
                                        <span class="text-2xl font-black text-indigo-600">₱{{ formatPrice(selectedProduct.selling_price) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Cost</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-300">₱{{ formatPrice(selectedProduct.unit_cost) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Stock On Hand</span>
                                        <span :class="selectedProduct.stock_on_hand < 50 ? 'text-red-600' : 'text-emerald-600'" class="font-bold">
                                            {{ selectedProduct.stock_on_hand?.toLocaleString() || 0 }} units
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">MOQ</span>
                                        <span class="font-bold">{{ selectedProduct.moq || '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Lead Time</span>
                                        <span class="font-bold">{{ selectedProduct.lead_time || '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Certification</span>
                                        <span class="font-bold">{{ selectedProduct.certification || '—' }}</span>
                                    </div>
                                </div> -->

                                <div v-if="selectedProduct.description" class="border-l-4 border-indigo-300 pl-4 italic text-gray-600 dark:text-gray-400">
                                    {{ selectedProduct.description }}
                                </div>

                                <div v-if="selectedProduct.sizes && selectedProduct.sizes.length">
                                    <h4 class="text-xs font-black uppercase text-gray-500 mb-2">Available Sizes</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="sz in selectedProduct.sizes" :key="sz"
                                            class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-xs font-bold">
                                            {{ sz }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="selectedProduct.specs && selectedProduct.specs.length">
                                    <h4 class="text-xs font-black uppercase text-gray-500 mb-2">Technical Specifications</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="spec in selectedProduct.specs" :key="spec.label" class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
                                            <span class="text-sm font-medium">{{ spec.label }}</span>
                                            <span class="text-sm font-bold">{{ spec.value }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex justify-end gap-3 flex-shrink-0">
                        <button @click="showDetailModal = false"
                            class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import {
    Package,
    Search,
    ChevronDown,
    X,
    Eye,
    Weight,
    Ruler,
    Boxes,
    Tag,
    Download
} from 'lucide-vue-next';

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    }
});

// State
const searchQuery = ref('');
const selectedCategory = ref('All');
const showDetailModal = ref(false);
const selectedProduct = ref(null);
const currentImageIndex = ref({});

// Derived categories
const categories = computed(() => {
    const cats = ['All', ...new Set(props.products.map(p => p.category).filter(Boolean))];
    return cats;
});

// Filtered products
const filteredProducts = computed(() => {
    let list = props.products;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            (p.product_id && p.product_id.toLowerCase().includes(q))
        );
    }
    if (selectedCategory.value !== 'All') {
        list = list.filter(p => p.category === selectedCategory.value);
    }
    return list;
});

// Low stock count
const lowStockCount = computed(() => {
    return props.products.filter(p => p.stock_on_hand < 100).length;
});

// Helper functions
const formatPrice = (value) => {
    return Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const marginPercent = (product) => {
    if (!product.selling_price || !product.unit_cost) return '0.0';
    const margin = ((product.selling_price - product.unit_cost) / product.selling_price) * 100;
    return margin.toFixed(1);
};

const setImageIndex = (id, idx) => {
    currentImageIndex.value = { ...currentImageIndex.value, [id]: idx };
};

const openDetail = (product) => {
    selectedProduct.value = product;
    showDetailModal.value = true;
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'All';
};

let debounceTimer;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        // Search logic is handled by the computed property
    }, 300);
};

const exportCatalog = () => {
    alert('Export functionality will be implemented.');
};

onMounted(() => {
    // Initialize image indices for all products
    props.products.forEach(p => {
        if (p.images && p.images.length) {
            currentImageIndex.value[p.id] = 0;
        }
    });
});
</script>

<style scoped>
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>