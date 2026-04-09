<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

// Omitted modules array as they were not actively being used in the template, 
// keeping the script clean and focused.
</script>

<template>

    <Head title="Monti ERP | Industrial Textile Management" />

    <div class="relative min-h-screen flex flex-col font-sans bg-cover bg-center bg-no-repeat bg-fixed"
        style="background-image: url('/images/threads.jpg');">

        <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col flex-grow">

            <header class="flex flex-wrap items-center justify-between gap-4 py-6 border-b border-white/10">
                <div class="flex items-center gap-3 group">
                    <div
                        class="p-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 shadow-lg group-hover:scale-105 transition-transform duration-300">
                        <img src="/images/applogo.png" alt="Logo" class="h-8 w-8 sm:h-9 sm:w-9 object-contain" />
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight leading-none text-white uppercase drop-shadow-md">
                            MONTI<span class="text-blue-400">TEXTILE</span>
                        </span>
                        <span
                            class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.2em] text-slate-300 mt-0.5">
                            Manufacturing ERP
                        </span>
                    </div>
                </div>

                <nav v-if="canLogin" class="flex flex-wrap items-center gap-3 sm:gap-6">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')"
                        class="text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-blue-600/30 active:scale-95">
                        Enterprise Dashboard
                    </Link>
                    <Link v-else-if="$page.props.auth?.supplier" :href="route('supplier.dashboard')"
                        class="text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-emerald-600/30 active:scale-95">
                        Supplier Hub
                    </Link>

                    <template v-else>
                        <Link :href="route('client.login')"
                            class="text-xs sm:text-sm font-bold text-slate-200 hover:text-white transition-colors drop-shadow">
                            Business Login
                        </Link>
                        <Link v-if="canRegister" :href="route('client.register')"
                            class="text-xs sm:text-sm font-bold bg-white/15 backdrop-blur-md border border-white/30 text-white px-5 py-2.5 rounded-xl hover:bg-white/25 transition-all shadow-lg active:scale-95">
                            Be our Partner
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="flex-grow flex flex-col justify-center py-12 sm:py-20">

                <section class="text-center max-w-4xl mx-auto mb-16 sm:mb-20">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-blue-300 text-[10px] sm:text-xs font-bold uppercase tracking-widest mb-6 sm:mb-8 shadow-lg">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                        </span>
                        Welcome to Monti Textile
                    </div>
                    <h1
                        class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.1] mb-6 drop-shadow-xl">
                        Let's <span
                            class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">Weave</span><br />
                        The Future.
                    </h1>
                    <p
                        class="text-base sm:text-lg md:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed drop-shadow-md">
                        Precision management for Monti Textile's manufacturing lifecycle. From raw weaving to global
                        distribution, keep every thread accounted for.
                    </p>
                </section>

                <div v-if="canRegister && !$page.props.auth?.user && !$page.props.auth?.supplier"
                    class="grid md:grid-cols-2 gap-6 sm:gap-8 max-w-5xl mx-auto w-full">

                    <div
                        class="group relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-[2rem] p-6 sm:p-8 md:p-10 flex flex-col justify-between overflow-hidden hover:border-blue-400/50 transition-all duration-500 hover:bg-white/15 hover:shadow-2xl hover:shadow-blue-500/20">
                        <div
                            class="absolute top-0 right-0 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl -mr-20 -mt-20 transition-transform duration-700 group-hover:scale-150 pointer-events-none">
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-6">
                                <div
                                    class="h-14 w-14 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20 text-blue-300 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-widest text-blue-300 bg-white/10 border border-white/20 rounded-full">
                                    Careers
                                </span>
                            </div>

                            <h4 class="text-2xl sm:text-3xl font-black text-white tracking-tight drop-shadow-md">
                                Join Our Team
                            </h4>
                            <p class="text-slate-300 mt-3 text-sm leading-relaxed">
                                Become part of the Monti Textile family. Bring your expertise to a team that is
                                innovating the textile manufacturing industry.
                            </p>

                            <ul class="mt-6 space-y-3">
                                <li class="flex items-center text-xs font-bold text-slate-200">
                                    <div class="p-1 rounded-full bg-white/10 border border-white/20 mr-3">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    Competitive Benefits & Packages
                                </li>
                                <li class="flex items-center text-xs font-bold text-slate-200">
                                    <div class="p-1 rounded-full bg-white/10 border border-white/20 mr-3">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    Accelerated Career Growth
                                </li>
                            </ul>
                        </div>

                        <Link :href="route('apply')"
                            class="relative z-10 mt-8 flex items-center justify-center w-full bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/30 active:scale-[0.98] group/btn">
                            <span class="tracking-widest uppercase text-xs">Send Application Now</span>
                            <svg class="w-4 h-4 ml-2 transform group-hover/btn:translate-x-1 transition-transform"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>

                    <div
                        class="group relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-[2rem] p-6 sm:p-8 md:p-10 flex flex-col justify-between overflow-hidden hover:border-emerald-400/50 transition-all duration-500 hover:bg-white/15 hover:shadow-2xl hover:shadow-emerald-500/20">
                        <div
                            class="absolute bottom-0 left-0 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl -ml-20 -mb-20 transition-transform duration-700 group-hover:scale-150 pointer-events-none">
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-6">
                                <div
                                    class="h-14 w-14 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20 text-emerald-300 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span
                                    class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-widest text-emerald-300 bg-white/10 border border-white/20 rounded-full">
                                    Partnerships
                                </span>
                            </div>

                            <h4 class="text-2xl sm:text-3xl font-black text-white tracking-tight drop-shadow-md">
                                Become a Supplier
                            </h4>
                            <p class="text-slate-300 mt-3 text-sm leading-relaxed">
                                Supply us with high-quality raw materials. Access requests for quotation, manage
                                purchase orders,
                                and scale your business with our B2B network.
                            </p>

                            <ul class="mt-6 space-y-3">
                                <li class="flex items-center text-xs font-bold text-slate-200">
                                    <div class="p-1 rounded-full bg-white/10 border border-white/20 mr-3">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    Direct B2B Integration Portal
                                </li>
                                <li class="flex items-center text-xs font-bold text-slate-200">
                                    <div class="p-1 rounded-full bg-white/10 border border-white/20 mr-3">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    Transparent Quotation Bidding
                                </li>
                            </ul>
                        </div>

                        <Link :href="route('supplier.register')"
                            class="relative z-10 mt-8 flex items-center justify-center w-full bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-4 rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/30 active:scale-[0.98] group/btn">
                            <span class="tracking-widest uppercase text-xs">Register Business</span>
                            <svg class="w-4 h-4 ml-2 transform group-hover/btn:translate-x-1 transition-transform"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </main>

            <footer
                class="py-8 border-t border-white/10 mt-auto flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-xs text-slate-400 font-medium text-center md:text-left drop-shadow">
                    © 2026 Monti Textile Manufacturing <span class="mx-2 text-slate-500">|</span>
                    <span class="font-mono text-blue-400">Enterprise Resource Planning System</span>
                </div>

                <div class="flex flex-wrap gap-6 justify-center">
                    <template v-if="!$page.props.auth?.user && !$page.props.auth?.supplier">
                        <Link :href="route('login')"
                            class="text-[10px] font-bold text-slate-300 hover:text-white transition-colors uppercase tracking-widest">
                            Employee Portal
                        </Link>
                        <Link :href="route('supplier.login')"
                            class="text-[10px] font-bold text-slate-300 hover:text-white transition-colors uppercase tracking-widest">
                            Supplier Portal
                        </Link>
                    </template>
                </div>
            </footer>

        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&display=swap');

.font-mono {
    font-family: 'JetBrains Mono', monospace;
}
</style>