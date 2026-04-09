<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Search, X, ChevronRight, ChevronLeft, Package, Layers,
    Tag, Ruler, Weight, Palette, Clock,
    AlertTriangle, Boxes,
    ChevronDown, Info, Zap, Plus, Trash2,
    Pencil, Upload, ImageIcon, Check, ImageMinus // Added ImageMinus for delete image modal
} from 'lucide-vue-next';

// alias so template can use ChevronRightIcon without conflicting with the card's chevron
const ChevronRightIcon = ChevronRight;

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    products: { type: Array, default: () => [] },
    masterMaterials: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const products = ref(props.products);
watch(() => props.products, v => (products.value = v), { deep: true });

// ── UI State ──────────────────────────────────────────────────────────────────
const isLoaded = ref(false);
const searchQuery = ref('');
const catFilter = ref('All');
const selectedProduct = ref(null);
const activeTab = ref('bom');
const expandedMat = ref(null);
const showAddProduct = ref(false);
const showEditProduct = ref(false);
const processing = ref(false);

// ── Confirmation States ───────────────────────────────────────────────────────
const showEditConfirm = ref(false);
const productToDelete = ref(null); 
const colorToDeleteInfo = ref(null); // NEW: Holds { index, isEdit }
const imageToDelete = ref(null); // NEW: Holds image ID

// Per-card image slider index keyed by product.id
const cardSlide = ref({});
const slideIdx = (productId) => cardSlide.value[productId] ?? 0;

// ── Auto-slide ────────────────────────────────────────────────────────────────
const autoSlideIntervals = {};

const startAutoSlide = (productId, total) => {
    if (autoSlideIntervals[productId]) return;
    autoSlideIntervals[productId] = setInterval(() => {
        cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) + 1) % total;
    }, 3000);
};

const stopAutoSlide = (productId) => {
    if (autoSlideIntervals[productId]) {
        clearInterval(autoSlideIntervals[productId]);
        delete autoSlideIntervals[productId];
    }
};

const resetAutoSlide = (productId, total) => {
    stopAutoSlide(productId);
    startAutoSlide(productId, total);
};

const initAutoSlide = () => {
    products.value.forEach(p => {
        if (p.images && p.images.length > 1) {
            startAutoSlide(p.id, p.images.length);
        }
    });
};

const slideNext = (productId, total, e) => {
    e.stopPropagation();
    cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) + 1) % total;
    resetAutoSlide(productId, total);
};
const slidePrev = (productId, total, e) => {
    e.stopPropagation();
    cardSlide.value[productId] = ((cardSlide.value[productId] ?? 0) - 1 + total) % total;
    resetAutoSlide(productId, total);
};

onMounted(() => {
    setTimeout(() => (isLoaded.value = true), 60);
    initAutoSlide();
});

onUnmounted(() => {
    Object.values(autoSlideIntervals).forEach(id => clearInterval(id));
});

watch(() => products.value, () => initAutoSlide(), { deep: true });

// ── Color Management System (Specific to Product) ─────────────────────────────
const newColorForm = ref({
    name: '',
    hex: '#3b82f6'
});

const addColor = () => {
    if (newColorForm.value.name && newColorForm.value.hex) {
        const addedColor = { 
            name: newColorForm.value.name, 
            hex: newColorForm.value.hex 
        };
        
        // Add directly to the active form's color array
        if (showAddProduct.value) {
            newProduct.value.colors.push(addedColor);
        } else if (showEditProduct.value) {
            editForm.value.colors.push(addedColor);
        }
        
        // Reset form
        newColorForm.value.name = '';
        newColorForm.value.hex = '#3b82f6';
    }
};

// ── NEW: Trigger Color Delete Confirmation ──
const triggerDeleteColor = (index, isEdit = false) => {
    colorToDeleteInfo.value = { index, isEdit };
};

const confirmDeleteColor = () => {
    if (!colorToDeleteInfo.value) return;
    
    const { index, isEdit } = colorToDeleteInfo.value;
    if (isEdit) {
        editForm.value.colors.splice(index, 1);
    } else {
        newProduct.value.colors.splice(index, 1);
    }
    
    colorToDeleteInfo.value = null; // Close confirmation modal
};

const isDarkColor = (hex) => {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness < 128;
};

// ── Add form ──────────────────────────────────────────────────────────────────
const blankForm = () => ({
    name: '',
    colors: [] 
});

const newProduct = ref(blankForm());

const addImageInput = ref(null);
const addImageFiles = ref([]);
const addImagePreviews = ref([]);

const onAddImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(f => {
        addImageFiles.value.push(f);
        const reader = new FileReader();
        reader.onload = ev => addImagePreviews.value.push(ev.target.result);
        reader.readAsDataURL(f);
    });
    e.target.value = '';
};

const removeAddPreview = (i) => {
    addImageFiles.value.splice(i, 1);
    addImagePreviews.value.splice(i, 1);
};

const resetAddForm = () => {
    newProduct.value = blankForm();
    addImageFiles.value = [];
    addImagePreviews.value = [];
};

const submitProduct = () => {
    if (!newProduct.value.name || newProduct.value.colors.length === 0) return;
    processing.value = true;
    
    router.post(route('inv.manager.product.store'), {
        name: newProduct.value.name,
        colors: newProduct.value.colors,
        category: 'Uncategorized', 
        status: 'Active',
        images: addImageFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { resetAddForm(); showAddProduct.value = false; },
        onFinish: () => (processing.value = false),
    });
};

// ── Edit form ─────────────────────────────────────────────────────────────────
const editForm = ref(blankForm());
const editProductId = ref(null);
const editExistingImages = ref([]); 
const editImageInput = ref(null);
const editImageFiles = ref([]);
const editImagePreviews = ref([]);

const onEditImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(f => {
        editImageFiles.value.push(f);
        const reader = new FileReader();
        reader.onload = ev => editImagePreviews.value.push(ev.target.result);
        reader.readAsDataURL(f);
    });
    e.target.value = '';
};

const removeEditPreview = (i) => {
    editImageFiles.value.splice(i, 1);
    editImagePreviews.value.splice(i, 1);
};

// ── NEW: Trigger Image Delete Confirmation ──
const triggerDeleteImage = (imageId) => {
    imageToDelete.value = imageId;
};

const confirmDeleteImage = () => {
    if (!imageToDelete.value) return;
    processing.value = true;
    
    router.delete(route('inv.manager.product.image.destroy', { imageId: imageToDelete.value }), {
        preserveScroll: true,
        onSuccess: () => {
            editExistingImages.value = editExistingImages.value.filter(img => img.id !== imageToDelete.value);
            imageToDelete.value = null; // Close confirmation modal
        },
        onFinish: () => (processing.value = false),
    });
};

const openEditModal = (product, e) => {
    e?.stopPropagation();
    editProductId.value = product.id;
    editExistingImages.value = [...(product.images ?? [])];
    editImageFiles.value = [];
    editImagePreviews.value = [];
    
    let existingColors = [];
    
    if (product.colors) {
        if (typeof product.colors === 'string') {
            try {
                existingColors = JSON.parse(product.colors);
            } catch(err) {
                console.error("Could not parse product colors:", err);
            }
        } 
        else if (Array.isArray(product.colors)) {
            existingColors = [...product.colors];
        }
        else if (typeof product.colors === 'object') {
            existingColors = Object.values(product.colors);
        }
    } 
    
    if (existingColors.length === 0 && (product.color_hex || product.colorHex)) {
        existingColors = [{ 
            name: product.color_name || product.colorName || 'Default Color', 
            hex: product.color_hex || product.colorHex 
        }];
    }

    editForm.value = {
        name: product.name,
        colors: existingColors
    };
    showEditProduct.value = true;
};

const triggerEditConfirm = () => {
    if (!editForm.value.name || editForm.value.colors.length === 0) return;
    showEditConfirm.value = true; 
};

const submitEdit = () => {
    processing.value = true;
    router.post(route('inv.manager.product.update', { id: editProductId.value }), {
        name: editForm.value.name,
        colors: editForm.value.colors, 
        new_images: editImageFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showEditConfirm.value = false;
            showEditProduct.value = false;
            editProductId.value = null;
        },
        onFinish: () => (processing.value = false),
    });
};

// ── Delete product ────────────────────────────────────────────────────────────
const triggerDelete = (id, e) => {
    e?.stopPropagation();
    productToDelete.value = id; 
};

const confirmDeleteProduct = () => {
    if (!productToDelete.value) return;
    
    processing.value = true;
    if (selectedProduct.value?.id === productToDelete.value) {
        selectedProduct.value = null; 
    }
    
    router.delete(route('inv.manager.product.destroy', { id: productToDelete.value }), { 
        preserveScroll: true,
        onSuccess: () => {
            productToDelete.value = null; 
        },
        onFinish: () => (processing.value = false),
    });
};

// ── Computed ──────────────────────────────────────────────────────────────────
const categories = computed(() => ['All', ...new Set(products.value.map(p => p.category).filter(Boolean))]);

const filtered = computed(() => {
    let list = products.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku?.toLowerCase().includes(q) ||
            p.product_id?.toLowerCase().includes(q)
        );
    }
    if (catFilter.value !== 'All') list = list.filter(p => p.category === catFilter.value);
    return [...list].sort((a, b) => {
        const aHas = a.images && a.images.length > 0 ? 0 : 1;
        const bHas = b.images && b.images.length > 0 ? 0 : 1;
        return aHas - bHas;
    });
});

const bomCost = (product) => product.materials?.reduce((s, m) => s + m.cost * m.qty, 0) || 0;
const margin = (product) => {
    if (!product.sellingPrice) return '0.0';
    return (((product.sellingPrice - product.unitCost) / product.sellingPrice) * 100).toFixed(1);
};
const bomHasAlert = (product) => product.materials?.some(m => m.stockStatus !== 'In Stock') || false;

const fmt = (n) => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const openProduct = (product) => { selectedProduct.value = product; activeTab.value = 'bom'; expandedMat.value = null; };
const closeModal = () => { selectedProduct.value = null; };
</script>

<template>
    <Head title="Product Catalog | Monti Textile" />
    <AuthenticatedLayout>

        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4"
            :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
            style="transition: opacity .45s ease, transform .45s ease;">
            <div>
                <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-1">Monti Textile ERP</p>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Product Catalog</h1>
                <p class="text-slate-500 text-sm mt-0.5">Bill of materials, specifications, and raw material breakdown
                    for every product.</p>
            </div>
            <div class="flex items-center gap-3 text-sm flex-shrink-0">
                <span
                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold text-slate-600 dark:text-slate-300">
                    {{ products.length }} Products
                </span>
                <button @click="showAddProduct = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-80 transition shadow-sm">
                    <Plus class="w-4 h-4" /> Add Product
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-6 items-center" :class="isLoaded ? 'opacity-100' : 'opacity-0'"
            style="transition: opacity .5s ease .1s;">
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                <input v-model="searchQuery" type="text" placeholder="Search product, SKU..."
                    class="pl-9 pr-4 py-2.5 w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 placeholder-slate-400" />
            </div>
            <div class="relative">
                <select v-model="catFilter"
                    class="appearance-none pl-3 pr-8 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 font-semibold">
                    <option v-for="c in categories" :key="c">{{ c }}</option>
                </select>
                <ChevronDown
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
            </div>
            <div v-if="searchQuery || catFilter !== 'All'" class="flex items-center gap-1.5">
                <span class="text-xs text-slate-400 font-medium">{{ filtered.length }} results</span>
                <button @click="searchQuery = ''; catFilter = 'All'"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div v-for="(product, i) in filtered" :key="product.id"
                class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-300 cursor-pointer flex flex-col"
                :style="`transition-delay: ${i * 50}ms`"
                :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'"
                @click="openProduct(product)">

                <div class="relative overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-800">

                    <template v-if="product.images && product.images.length > 1">
                        <div class="relative w-full aspect-square overflow-hidden"
                            @mouseenter="stopAutoSlide(product.id)"
                            @mouseleave="startAutoSlide(product.id, product.images.length)">
                            <div class="flex h-full transition-transform duration-300 ease-in-out"
                                :style="`transform: translateX(-${slideIdx(product.id) * 100}%)`">
                                <img v-for="img in product.images" :key="img.id" :src="img.url" :alt="product.name"
                                    class="h-full w-full object-cover flex-shrink-0" />
                            </div>

                            <button @click="slidePrev(product.id, product.images.length, $event)"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </button>
                            <button @click="slideNext(product.id, product.images.length, $event)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition z-10">
                                <ChevronRightIcon class="w-3.5 h-3.5" />
                            </button>

                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-10">
                                <button v-for="(_, di) in product.images" :key="di"
                                    @click.stop="cardSlide[product.id] = di"
                                    :class="['w-1.5 h-1.5 rounded-full transition-all', di === slideIdx(product.id) ? 'bg-white scale-125' : 'bg-white/50']" />
                            </div>
                        </div>
                    </template>

                    <template v-else-if="product.images && product.images.length === 1">
                        <img :src="product.images[0].url" :alt="product.name"
                            class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-500" />
                    </template>

                    <template v-else>
                        <div class="h-2 w-full" :style="`background-color: ${(product.colors && product.colors.length > 0) ? (Array.isArray(product.colors) ? product.colors[0].hex : (JSON.parse(product.colors)[0]?.hex || '#64748b')) : (product.colorHex || '#64748b')}`" />
                    </template>

                    <div
                        class="absolute top-2 left-2 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                        <button @click="openEditModal(product, $event)"
                            class="w-7 h-7 rounded-lg bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-blue-600 hover:text-white transition-all"
                            title="Edit product">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        
                        <button @click="triggerDelete(product.id, $event)"
                            class="w-7 h-7 rounded-lg bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-red-600 hover:text-white transition-all"
                            title="Delete product">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <div class="p-5 flex flex-col gap-4 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span
                                    class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                                    {{ product.product_id }}
                                </span>
                                <span v-if="product.subcategory || product.category"
                                    class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                    {{ product.subcategory || product.category }}
                                </span>
                            </div>
                            <h3
                                class="font-black text-slate-900 dark:text-white text-base leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ product.name }}
                            </h3>
                            <p class="font-mono text-[11px] text-slate-400 mt-0.5">{{ product.sku }}</p>
                        </div>
                        
                        <div class="flex -space-x-2 flex-shrink-0">
                            <template v-if="product.colors && product.colors.length > 0">
                                <div v-for="c in (Array.isArray(product.colors) ? product.colors : JSON.parse(product.colors)).slice(0, 3)" :key="c.hex" 
                                     class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 shadow-sm"
                                     :style="`background-color: ${c.hex}`" :title="c.name" />
                                <div v-if="(Array.isArray(product.colors) ? product.colors : JSON.parse(product.colors)).length > 3" class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 dark:bg-slate-800 text-[9px] font-bold text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                    +{{ (Array.isArray(product.colors) ? product.colors : JSON.parse(product.colors)).length - 3 }}
                                </div>
                            </template>
                            <template v-else>
                                <div class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 shadow-sm"
                                     :style="`background-color: ${product.colorHex || '#64748b'}`" />
                            </template>
                        </div>
                    </div>

                    <div
                        class="pt-3 mt-auto border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div v-if="product.materials?.length">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">BOM Items</p>
                                <p class="text-sm font-black text-slate-800 dark:text-white">{{ product.materials.length }} materials</p>
                            </div>
                            <div v-if="product.sellingPrice">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Margin</p>
                                <p class="text-sm font-black text-emerald-600">{{ margin(product) }}%</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-right" v-if="product.sellingPrice">
                                <p class="text-[10px] text-slate-400 font-bold">Selling Price</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white">₱{{ fmt(product.sellingPrice) }}</p>
                            </div>
                            <div
                                class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover:bg-blue-600 transition-all">
                                <ChevronRight class="w-4 h-4 text-slate-400 group-hover:text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filtered.length === 0"
                class="col-span-full flex flex-col items-center justify-center py-24 text-slate-400">
                <Package class="w-12 h-12 mb-4 opacity-30" />
                <p class="font-bold text-slate-500">No products match your filters.</p>
                <button @click="searchQuery = ''; catFilter = 'All'"
                    class="mt-3 text-sm text-blue-600 font-bold hover:underline">Clear filters</button>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="closeModal">
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-4xl">

                    <div class="h-1.5 w-full rounded-t-2xl"
                        :style="`background-color: ${(selectedProduct.colors && selectedProduct.colors.length > 0) ? (Array.isArray(selectedProduct.colors) ? selectedProduct.colors[0].hex : (JSON.parse(selectedProduct.colors)[0]?.hex || '#64748b')) : (selectedProduct.colorHex || '#64748b')}`" />

                    <div v-if="selectedProduct.images && selectedProduct.images.length"
                        class="flex gap-2 p-4 border-b border-slate-100 dark:border-slate-800 overflow-x-auto">
                        <img v-for="img in selectedProduct.images" :key="img.id" :src="img.url"
                            :alt="selectedProduct.name"
                            class="h-24 w-24 object-cover rounded-xl flex-shrink-0 border border-slate-200 dark:border-slate-700" />
                    </div>

                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span
                                    class="font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-md">{{
                                        selectedProduct.product_id }}</span>
                                <span v-if="selectedProduct.category"
                                    class="text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-full">{{
                                        selectedProduct.category }}</span>
                            </div>
                            <h2 class="text-xl font-black text-slate-900 dark:text-white leading-tight">{{
                                selectedProduct.name }}</h2>
                            <p class="font-mono text-xs text-slate-400 mt-0.5">{{ selectedProduct.sku }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="openEditModal(selectedProduct); closeModal()"
                                class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                title="Edit product">
                                <Pencil class="w-4 h-4" />
                            </button>
                            
                            <button @click="triggerDelete(selectedProduct.id, $event)"
                                class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <Trash2 class="w-4 h-4" />
                            </button>
                            
                            <button @click="closeModal"
                                class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-start gap-3">
                            <Palette class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2">Available Colorways</p>
                                <div class="flex flex-wrap gap-2">
                                    <template v-if="selectedProduct.colors && selectedProduct.colors.length > 0">
                                        <div v-for="c in (Array.isArray(selectedProduct.colors) ? selectedProduct.colors : JSON.parse(selectedProduct.colors))" :key="c.hex" class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 pr-3 rounded-full border border-slate-200 dark:border-slate-700">
                                            <span class="w-5 h-5 rounded-full border border-black/10 dark:border-white/10" :style="`background-color: ${c.hex}`" />
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ c.name }}</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800 pr-3 rounded-full border border-slate-200 dark:border-slate-700">
                                            <span class="w-5 h-5 rounded-full" :style="`background-color: ${selectedProduct.colorHex || '#000000'}`" />
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ selectedProduct.colorName || 'No Color' }}</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showAddProduct"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="showAddProduct = false; resetAddForm()">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md my-auto">

                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Add New Product</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Define product details and available colors.</p>
                        </div>
                        <button @click="showAddProduct = false; resetAddForm()"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Product Name *</label>
                            <input v-model="newProduct.name" type="text"
                                placeholder="e.g. Premium Cotton Shirt"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Colors assigned *</p>
                                <span class="text-xs font-bold text-slate-400">
                                    {{ newProduct.colors.length }} added
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-4 mb-6 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 min-h-[80px] items-center">
                                <template v-if="newProduct.colors.length > 0">
                                    <div v-for="(color, index) in newProduct.colors" :key="index"
                                        class="relative group flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full border-2 border-slate-300 dark:border-slate-600 shadow-sm flex items-center justify-center transition-transform hover:scale-105"
                                            :style="{ backgroundColor: color.hex || '#CCCCCC' }" :title="color.name">
                                        </div>
                                        <span class="text-[9px] mt-1.5 font-bold text-slate-600 dark:text-slate-300 max-w-[50px] text-center truncate" :title="color.name">
                                            {{ color.name || 'Unnamed' }}
                                        </span>
                                        <button @click="triggerDeleteColor(index, false)" 
                                            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 shadow-lg hover:bg-red-600 transition-all z-10 scale-90 group-hover:scale-100">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </template>
                                <div v-else class="w-full text-center text-slate-400 text-xs italic py-2">
                                    No colors added yet.
                                </div>
                            </div>

                            <div class="bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-xl border border-blue-100 dark:border-blue-900/30 flex flex-col gap-3">
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-tighter">Add New Color</p>
                                <div class="flex items-end gap-2">
                                    <div class="flex-1">
                                        <input v-model="newColorForm.name" type="text" placeholder="Color Name"
                                            class="w-full px-2 py-2 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/30 text-slate-700 dark:text-slate-200" />
                                    </div>
                                    <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <input v-model="newColorForm.hex" type="color"
                                            class="w-7 h-7 rounded border-none cursor-pointer bg-transparent p-0" />
                                        <input v-model="newColorForm.hex" type="text" placeholder="#000000"
                                            class="w-16 px-1 py-1 text-xs bg-transparent border-none focus:ring-0 text-slate-700 dark:text-slate-200 font-mono uppercase" />
                                    </div>
                                </div>
                                <button @click="addColor" :disabled="!newColorForm.name || !newColorForm.hex"
                                    class="w-full py-2 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    Add Color
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Product Images</p>
                            <div v-if="addImagePreviews.length > 0" class="flex flex-wrap gap-2 mb-3">
                                <div v-for="(src, i) in addImagePreviews" :key="i" class="relative group">
                                    <img :src="src" class="w-16 h-16 object-cover rounded-lg border-2 border-white dark:border-slate-800 shadow-sm" />
                                    <button @click="removeAddPreview(i)"
                                        class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-500 transition">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <input ref="addImageInput" type="file" multiple accept="image/*" class="hidden" @change="onAddImageChange" />
                            <button @click="$refs.addImageInput.click()"
                                class="flex flex-col items-center gap-1.5 py-6 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 hover:border-blue-400 hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all w-full">
                                <Upload class="w-5 h-5" />
                                <span class="text-[10px] font-bold">Upload Media</span>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex gap-3 rounded-b-2xl">
                        <button @click="showAddProduct = false; resetAddForm()"
                            class="flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-white transition">
                            Cancel
                        </button>
                        <button @click="submitProduct"
                            :disabled="processing || !newProduct.name || newProduct.colors.length === 0"
                            class="flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-lg shadow-blue-500/20 disabled:opacity-30">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showEditProduct"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm overflow-y-auto"
                @click.self="showEditProduct = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md my-auto">

                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Edit Product</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Manage details and color availability.</p>
                        </div>
                        <button @click="showEditProduct = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Product Name *</label>
                            <input v-model="editForm.name" type="text"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200" />
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Colors assigned *</p>
                                <span class="text-xs font-bold text-slate-400">
                                    {{ editForm.colors?.length || 0 }} added
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-4 mb-6 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 min-h-[80px] items-center">
                                
                                <template v-if="editForm.colors && editForm.colors.length > 0">
                                    <div v-for="(color, index) in editForm.colors" :key="index"
                                        class="relative group flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full border-2 border-slate-300 dark:border-slate-600 shadow-sm flex items-center justify-center transition-transform hover:scale-105"
                                            :style="{ backgroundColor: color.hex || '#CCCCCC' }" :title="color.name">
                                        </div>
                                        <span class="text-[9px] mt-1.5 font-bold text-slate-600 dark:text-slate-300 max-w-[50px] text-center truncate" :title="color.name">
                                            {{ color.name || 'Unnamed' }}
                                        </span>

                                        <button @click="triggerDeleteColor(index, true)" 
                                            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 shadow-lg hover:bg-red-600 transition-all z-10 scale-90 group-hover:scale-100">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </template>

                                <div v-else class="w-full text-center text-slate-400 text-xs italic py-2">
                                    No colors currently assigned.
                                </div>
                            </div>

                            <div class="bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-xl border border-blue-100 dark:border-blue-900/30 flex flex-col gap-3">
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-tighter">Add New Color</p>
                                <div class="flex items-end gap-2">
                                    <div class="flex-1">
                                        <input v-model="newColorForm.name" type="text" placeholder="Color Name"
                                            class="w-full px-2 py-2 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/30 text-slate-700 dark:text-slate-200" />
                                    </div>
                                    <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <input v-model="newColorForm.hex" type="color"
                                            class="w-7 h-7 rounded border-none cursor-pointer bg-transparent p-0" />
                                        <input v-model="newColorForm.hex" type="text" placeholder="#000000"
                                            class="w-16 px-1 py-1 text-xs bg-transparent border-none focus:ring-0 text-slate-700 dark:text-slate-200 font-mono uppercase" />
                                    </div>
                                </div>
                                <button @click="addColor" :disabled="!newColorForm.name || !newColorForm.hex"
                                    class="w-full py-2 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    Add Color
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                Product Images
                            </p>

                            <div v-if="editExistingImages.length > 0" class="mb-4">
                                <p class="text-[10px] text-slate-400 font-bold mb-2">Saved Images</p>
                                <div class="flex flex-wrap gap-2">
                                    <div v-for="img in editExistingImages" :key="img.id" class="relative">
                                        <img :src="img.url" class="w-16 h-16 object-cover rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm" />
                                        <button @click="triggerDeleteImage(img.id)"
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition z-10"
                                            title="Remove image">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="editImagePreviews.length > 0" class="flex flex-wrap gap-2 mb-3">
                                <div v-for="(src, i) in editImagePreviews" :key="i" class="relative">
                                    <img :src="src" class="w-16 h-16 object-cover rounded-lg border-2 border-blue-400 dark:border-blue-500 shadow-sm" />
                                    <span class="absolute bottom-0.5 left-0.5 text-[8px] font-black bg-blue-600 text-white px-1 py-0.5 rounded shadow-sm">NEW</span>
                                    <button @click="removeEditPreview(i)"
                                        class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition z-10"
                                        title="Remove">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <input ref="editImageInput" type="file" multiple accept="image/*" class="hidden" @change="onEditImageChange" />
                            <button @click="$refs.editImageInput.click()"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-all w-full justify-center">
                                <Upload class="w-4 h-4" />
                                Upload additional images
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex gap-3 bg-slate-50 dark:bg-slate-800/30 rounded-b-2xl">
                        <button @click="showEditProduct = false"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        
                        <button @click="triggerEditConfirm" :disabled="processing || !editForm.name || !editForm.colors || editForm.colors.length === 0"
                            class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm shadow-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showEditConfirm" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" @click.self="showEditConfirm = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-4">
                            <Info class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">Save Changes?</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Are you sure you want to update this product's details and inventory data?</p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 flex gap-3">
                        <button @click="showEditConfirm = false" class="flex-1 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition">
                            Back
                        </button>
                        <button @click="submitEdit" :disabled="processing" class="flex-1 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else>Confirm Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="productToDelete" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="productToDelete = null">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/20 text-red-500 flex items-center justify-center mx-auto mb-4 border-4 border-white dark:border-slate-900 shadow-sm">
                            <AlertTriangle class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Delete Product</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            This action cannot be undone. This will permanently delete the product, its images, and remove it from all warehouse inventories.
                        </p>
                    </div>
                    <div class="p-4 bg-red-50/50 dark:bg-slate-800/50 flex gap-3 border-t border-red-100 dark:border-slate-800">
                        <button @click="productToDelete = null" :disabled="processing" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition">
                            Cancel
                        </button>
                        <button @click="confirmDeleteProduct" :disabled="processing" class="flex-1 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm shadow-red-500/20 disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else>Yes, Delete It</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="colorToDeleteInfo" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="colorToDeleteInfo = null">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/20 text-red-500 flex items-center justify-center mx-auto mb-4 border-4 border-white dark:border-slate-900 shadow-sm">
                            <Palette class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Remove Color</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Are you sure you want to remove this color from the available options?
                        </p>
                    </div>
                    <div class="p-4 bg-red-50/50 dark:bg-slate-800/50 flex gap-3 border-t border-red-100 dark:border-slate-800">
                        <button @click="colorToDeleteInfo = null" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition">
                            Cancel
                        </button>
                        <button @click="confirmDeleteColor" class="flex-1 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm shadow-red-500/20">
                            Remove Color
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="imageToDelete" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="imageToDelete = null">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/20 text-red-500 flex items-center justify-center mx-auto mb-4 border-4 border-white dark:border-slate-900 shadow-sm">
                            <ImageMinus class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Delete Image</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Are you sure you want to permanently delete this image from the server? This action cannot be undone.
                        </p>
                    </div>
                    <div class="p-4 bg-red-50/50 dark:bg-slate-800/50 flex gap-3 border-t border-red-100 dark:border-slate-800">
                        <button @click="imageToDelete = null" :disabled="processing" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition">
                            Cancel
                        </button>
                        <button @click="confirmDeleteImage" :disabled="processing" class="flex-1 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm shadow-red-500/20 disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else>Delete Image</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>