<script setup>
import Sidebar from './Sidebar.vue'
import MobileSidebar from './MobileSidebar.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

// User data from Inertia shared props
const user = computed(() => page.props.auth.user)
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-950 transition-colors duration-300">
        <Sidebar />

        <div class="md:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
            <div class="px-4 py-4 flex items-center justify-between h-16">
                <div class="flex items-center">
                    <MobileSidebar />
                    <div class="ml-4">
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Monti <span class="text-blue-600">Textile</span>
                        </h1>
                    </div>
                </div>
                <div
                    class="h-8 w-8 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                    {{ user?.name?.charAt(0) }}
                </div>
            </div>
        </div>

        <div class="md:pl-64 flex flex-col flex-1">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="animate-in fade-in duration-500">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>