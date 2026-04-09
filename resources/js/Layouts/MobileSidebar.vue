<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import { computed, ref } from 'vue'
import {
    Menu,
    X,
    LayoutDashboard,
    BarChart3,
    Package,
    LogOut,
    ChevronRight,
    CreditCard,
    UserPlus,
    Spool,
    ClipboardList,
    ChartNoAxesCombined,
    ShoppingBasket,
    HandCoins,
    FileUser,
    DoorOpen,
    BicepsFlexed,
    Truck,
    Wallet,
    Factory,
    Book,
    Boxes,
    ShoppingCart,
    Warehouse,
    Globe,
    Clock,
    CalendarDays,
    History,
    Users,
    Settings,
    Receipt,
    HelpCircle,
    ShieldCheck,
    Building2,
    RefreshCw,
    ClipboardCheck,
    FileText,
    Send,
    ShoppingBag,
    User,
    TrendingUp,
    XCircle
} from 'lucide-vue-next'

const page = usePage()

// UI State
const isOpen = ref(false)
const isWorkforceOpen = ref(false)
const showLogoutModal = ref(false)

const toggleWorkforce = () => {
    isWorkforceOpen.value = !isWorkforceOpen.value
}

// Bulletproof authentication object parsing (Synced from Sidebar.vue)
const user = computed(() => page.props.auth.user)
const client = computed(() => page.props.auth.client)
const supplier = computed(() => page.props.auth.supplier || (page.props.auth.user?.business_name ? page.props.auth.user : null))
const currentUrl = computed(() => page.url)

const isEmployeePortal = computed(() => currentUrl.value.startsWith('/dashboard/employee-ui'))
const isClient = computed(() => !!client.value)
const isSupplier = computed(() => !!supplier.value || currentUrl.value.startsWith('/supplier'))

const navItems = computed(() => {
    // --- Supplier Navigation ---
    if (isSupplier.value) {
        return [
            { label: 'Vendor Hub', href: route('supplier.dashboard'), icon: LayoutDashboard },
            { label: 'Purchase Orders', href: route('supplier.orders'), icon: ShoppingCart },
        ]
    }

    // --- Client Navigation (B2B) ---
    if (isClient.value) {
        return [
            { label: 'Dashboard', href: route('client.dashboard'), icon: LayoutDashboard },
            { label: 'Products', href: route('client.products'), icon: ShoppingBag },
            { label: 'Orders', href: route('client.orders'), icon: ShoppingCart },
            { label: 'Invoices', href: route('client.invoices'), icon: Receipt },
            { label: 'Profile', href: route('client.profile.edit'), icon: User },
            { label: 'Support', href: route('client.support'), icon: HelpCircle },
        ]
    }

    // --- Employee ID Portal Navigation ---
    if (isEmployeePortal.value) {
        return [
            { label: 'Employee Dashboard', href: route('employee.ui.dashboard'), icon: Clock },
            { label: 'My Attendance', href: route('employee.ui.clock'), icon: CalendarDays },
            { label: 'Leave Request', href: route('employee.ui.leave'), icon: History },
            { label: 'Payslip', href: route('employee.ui.payslip'), icon: HandCoins },
        ]
    }

    // --- Standard ERP Navigation ---
    const items = [
        { label: 'Main Dashboard', href: route('dashboard'), icon: LayoutDashboard },
    ]

    const userRole = user.value?.role?.toUpperCase()
    const userPosition = user.value?.position?.toLowerCase()
    const manufacturingRole = user.value?.manufacturing_role

    // --- Trainee Navigation ---
    if (userPosition === 'trainee') {
        items.push(
            { label: 'Time In/Out', href: route('trainee.timekeeping'), icon: Clock },
            { label: 'Attendance', href: route('trainee.attendance'), icon: CalendarDays },
            { label: 'Payslips', href: route('trainee.payslip'), icon: HandCoins }
        );
        return items;
    }

    // --- Human Resources (HRM) ---
    if (userRole === 'HRM') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Employee Management', href: route('hrm.manager.employee'), icon: Users },
                { label: 'Onboarding', href: route('hrm.manager.onboarding'), icon: BarChart3 },
                {
                    label: 'Workforce Management',
                    icon: Users,
                    isDropdown: true,
                    children: [
                        { label: 'Attendance', href: route('hrm.employee.attendance'), icon: FileUser },
                        { label: 'Leave MGMT', href: route('hrm.employee.leave'), icon: DoorOpen },
                    ]
                },
                { label: 'Payroll', href: route('hrm.manager.payroll'), icon: HandCoins },
                { label: 'Analytics', href: route('hrm.manager.analytics'), icon: ChartNoAxesCombined }
            )
        } else if (userPosition === 'staff') {
            items.push(
                { label: 'Recruitment', href: route('hrm.applicants.index'), icon: UserPlus },
                { label: 'Interview', href: route('hrm.employee.interview'), icon: ClipboardList },
                { label: 'Training', href: route('hrm.employee.training'), icon: BicepsFlexed },
                {
                    label: 'Workforce Management',
                    icon: Users,
                    isDropdown: true,
                    children: [
                        { label: 'Attendance', href: route('hrm.employee.attendance'), icon: FileUser },
                        { label: 'Leave MGMT', href: route('hrm.employee.leave'), icon: DoorOpen },
                    ]
                },
                { label: 'Payroll', href: route('hrm.employee.hrmstaffpayroll'), icon: HandCoins }
            )
        }
    }

    // --- Supply Chain Management (SCM) ---
    if (userRole === 'SCM') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Operations', href: route('scm.manager.operations'), icon: LayoutDashboard },
                { label: 'Sales Orders', href: route('scm.manager.sales-orders'), icon: ShoppingCart },
                { label: 'Payment Approval', href: route('scm.manager.payments'), icon: HandCoins },
                { label: 'Vendor Management', href: route('scm.manager.vendor'), icon: ChartNoAxesCombined },
                { label: 'Close', href: route('scm.manager.close'), icon: DoorOpen }
            )
        } else if (userPosition === 'staff') {
            items.push(
                { label: 'Inbound', href: route('scm.employee.inbound'), icon: Truck },
                { label: 'Receiving', href: route('scm.employee.recieving'), icon: Truck },
                { label: 'Inventory', href: route('scm.employee.inventory'), icon: Package },
                { label: 'Verifications', href: route('scm.employee.verification'), icon: HandCoins }
            )
        }
    }

    // --- Financial Operations (FIN) ---
    if (userRole === 'FIN') {
        items.push({ label: 'Finance', href: userPosition === 'manager' ? route('fin.manager.dashboard') : route('fin.employee.dashboard'), icon: Wallet })
    }

    // --- Manufacturing Plant (MAN) ---
    if (userRole === 'MAN') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Manufacturing Dashboard', href: route('man.manager.dashboard'), icon: Factory },
                { label: 'Production Orders', href: route('man.manager.production'), icon: ClipboardList },
                { label: 'Rejected Items', href: route('man.manager.rejected'), icon: XCircle }
            );
        } else {
            const roleToRoutes = {
                knitting_yarn: {
                    dashboard: 'man.staff.knitting-yarn.dashboard',
                    work: 'man.staff.knitting-yarn.page',
                    reports: 'man.staff.knitting-yarn.reports'
                },
                dyeing_color: {
                    dashboard: 'man.staff.dyeing-color.dashboard',
                    work: 'man.staff.dyeing-color.page',
                    reports: 'man.staff.dyeing-color.reports'
                },
                dyeing_fabric_softener: {
                    dashboard: 'man.staff.dyeing-fabric-softener.dashboard',
                    work: 'man.staff.dyeing-fabric-softener.page',
                    reports: 'man.staff.dyeing-fabric-softener.reports'
                },
                dyeing_squeezer: {
                    dashboard: 'man.staff.dyeing-squeezer.dashboard',
                    work: 'man.staff.dyeing-squeezer.page',
                    reports: 'man.staff.dyeing-squeezer.reports'
                },
                dyeing_ironing: {
                    dashboard: 'man.staff.dyeing-ironing.dashboard',
                    work: 'man.staff.dyeing-ironing.page',
                    reports: 'man.staff.dyeing-ironing.reports'
                },
                dyeing_forming: {
                    dashboard: 'man.staff.dyeing-forming.dashboard',
                    work: 'man.staff.dyeing-forming.page',
                    reports: 'man.staff.dyeing-forming.reports'
                },
                dyeing_packaging: {
                    dashboard: 'man.staff.dyeing-packaging.dashboard',
                    work: 'man.staff.dyeing-packaging.page',
                    reports: null
                },
                maintenance_checker: {
                    dashboard: 'man.staff.maintenance-checker.dashboard',
                    work: 'man.staff.maintenance-checker.page',
                    reports: 'man.staff.maintenance-checker.reports'
                },
                checker_quality: {
                    dashboard: 'man.staff.checker-quality.dashboard',
                    work: 'man.staff.checker-quality.production',
                    reports: null
                }
            };

            const routes = roleToRoutes[manufacturingRole];
            if (routes) {
                items.push({ label: 'Manufacturing Dashboard', href: route(routes.dashboard), icon: Factory });
                if (routes.work) {
                    let workLabel = 'My Work';
                    if (manufacturingRole === 'dyeing_packaging') workLabel = 'Packaging';
                    if (manufacturingRole === 'checker_quality') workLabel = 'Production Control';
                    items.push({ label: workLabel, href: route(routes.work), icon: ClipboardList });
                }
                if (routes.reports) {
                    items.push({ label: 'Reports', href: route(routes.reports), icon: FileText });
                }
            }
        }
    }

    // --- Inventory & Logistics (INV) ---
    if (userRole === 'INV') {
        items.push({ label: 'Inventory', href: userPosition === 'manager' ? route('inv.manager.inventory') : route('inv.employee.dashboard'), icon: Boxes })
        if (userPosition === 'manager') {
            items.push(
                { label: 'Production Planning', href: route('inv.manager.production-planning'), icon: TrendingUp },
                { label: 'Master Materials', href: route('inv.manager.material'), icon: Spool },
                { label: 'Master Products', href: route('inv.manager.product'), icon: Building2 }
            )
        }
    }

    // --- Order Fulfillment (ORD) ---
    if (userRole === 'ORD') {
        items.push({ label: 'Orders', href: userPosition === 'manager' ? route('ord.manager.dashboard') : route('ord.employee.dashboard'), icon: ShoppingCart })
    }

    // --- Warehouse Management (WAR) ---
    if (userRole === 'WAR') {
        items.push({ label: 'Warehouse', href: userPosition === 'manager' ? route('war.manager.dashboard') : route('war.employee.dashboard'), icon: Warehouse })
    }

    // --- Customer Relationship Management (CRM) ---
    if (userRole === 'CRM') {
        items.push(
            { label: 'Lead & Deals', href: route('crm.lead'), icon: FileUser },
            { label: 'Customer Profiles', href: route('crm.customerprofile'), icon: Users }
        );
        if (userPosition === 'manager') {
            items.push({ label: 'Approval Queue', href: route('crm.approval.queue'), icon: ClipboardCheck });
        }
    }

    // --- E-Commerce & B2B Portal (ECO) ---
    if (userRole === 'ECO') {
        if (userPosition === 'manager') {
            items.push(
                { label: 'Store', href: route('eco.manager.store'), icon: ShoppingBag },
                { label: 'Quotations', href: route('eco.manager.quotations'), icon: FileText },
                { label: 'Orders', href: route('eco.manager.orders'), icon: ShoppingCart },
                { label: 'Credit MGMT', href: route('eco.manager.credit'), icon: CreditCard },
                { label: 'Book MGMT', href: route('eco.manager.book'), icon: Book }
            )
        } else {
            items.push(
                { label: 'Online Store', href: route('eco.employee.products'), icon: Globe },
                { label: 'Order Management', href: route('eco.employee.ordermng'), icon: ShoppingBasket }
            )
        }
    }

    // --- Procurement Module (PRO) ---
    if (userRole === 'PRO') {
        if (userPosition === 'manager') {
            items.push(
                // { label: 'Material Requests', href: route('pro.manager.material-requests'), icon: ClipboardList },
                { label: 'Supplier Quotations', href: route('pro.manager.supplier-quotations'), icon: FileText },
                { label: 'Receipt', href: route('pro.manager.receipt'), icon: Send }
            )
        } else if (userPosition === 'staff') {
            items.push({ label: 'Procurement Staff', href: route('pro.employee.dashboard'), icon: ShoppingCart })
        }
    }

    // --- Project Automation (PROJ) ---
    if (userRole === 'PROJ') {
        items.push({ label: 'Projects', href: userPosition === 'manager' ? route('proj.manager.dashboard') : route('proj.employee.dashboard'), icon: LayoutDashboard })
    }

    // --- IT & Systems Admin (IT) ---
    if (userRole === 'IT') {
        items.push({ label: 'IT & Systems', href: userPosition === 'manager' ? route('it.manager.dashboard') : route('it.employee.dashboard'), icon: Settings })
    }

    // --- CEO Super Admin Access ---
    if (userRole === 'CEO') {
        items.push(
            { label: 'Human Resource', href: route('hrm.manager.dashboard'), icon: Users },
            { label: 'Supply Chain', href: route('scm.manager.dashboard'), icon: Truck },
            { label: 'Manufacturing', href: route('man.manager.dashboard'), icon: Factory },
            { label: 'Inventory', href: route('inv.manager.dashboard'), icon: Boxes },
            { label: 'Customer Relationship', href: route('crm.dashboard'), icon: Users },
            { label: 'E‑Commerce', href: route('eco.manager.dashboard'), icon: ShoppingBag },
            { label: 'Procurement', href: route('pro.manager.dashboard'), icon: ShoppingCart }
            // Logistics module is commented out until its routes are defined
            // { label: 'Logistics', href: route('log.staff.dashboard'), icon: Truck },
        );
    }

    return items
})

const isActive = (href) => {
    if (href === '#') return false
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

const displayName = computed(() => {
    if (isSupplier.value) return supplier.value?.representative_name
    if (isClient.value) return client.value?.company_name
    return user.value?.name
})

const displayInitial = computed(() => displayName.value?.charAt(0) ?? '?')

const userPhotoUrl = computed(() => {
    if (user.value?.profile_photo_path) return `/storage/${user.value.profile_photo_path}`;
    if (supplier.value?.profile_photo_path) return `/storage/${supplier.value.profile_photo_path}`;
    if (client.value?.profile_photo_path) return `/storage/${client.value.profile_photo_path}`;
    return null;
})

const displayDepartment = computed(() => {
    if (isSupplier.value) return 'Supplier'
    if (isClient.value) return client.value?.business_type
    return user.value?.role
})

const displayPosition = computed(() => {
    if (isSupplier.value) return supplier.value?.business_name ?? 'Vendor'
    if (isEmployeePortal.value) return user.value?.employee_id ?? 'Staff'
    if (isClient.value) return 'Partner'
    return user.value?.position
})

const sidebarLabel = computed(() => {
    if (isSupplier.value) return 'Vendor'
    if (isClient.value) return 'Partner'
    if (isEmployeePortal.value) return 'Employee'
    return 'System'
})

const logoutRoute = computed(() => {
    if (isClient.value) return route('client.logout')
    if (isSupplier.value) return route('supplier.logout')
    return route('logout')
})

const handleNavClick = () => {
    isOpen.value = false;
}
</script>

<template>
    <div class="md:hidden">
        <nav
            class="fixed top-0 left-0 right-0 z-[60] bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-b border-gray-200/50 dark:border-gray-800/50 shadow-sm h-16 flex items-center justify-between px-4 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <div :class="isSupplier ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-blue-600 shadow-blue-500/20'"
                    class="h-8 w-8 rounded-lg flex items-center justify-center shadow-lg">
                    <img src="/images/applogo.png" alt="Logo" class="h-4.5 w-4.5 object-contain brightness-0 invert" />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-[14px] font-black tracking-tight text-gray-900 dark:text-white uppercase leading-none">
                        Monti <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'">Textile</span>
                    </span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                        {{ sidebarLabel }}
                    </span>
                </div>
            </div>

            <button @click.stop="isOpen = !isOpen"
                class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <Menu v-if="!isOpen" class="h-6 w-6" />
                <X v-else class="h-6 w-6" />
            </button>
        </nav>

        <transition enter-active-class="transition-opacity duration-300 ease-in-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200 ease-in-out"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-[50] bg-black/40 backdrop-blur-sm"
                aria-hidden="true"></div>
        </transition>

        <transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
            <aside v-if="isOpen"
                class="fixed inset-y-0 left-0 z-[55] w-[80vw] max-w-sm bg-white/90 dark:bg-gray-950/90 backdrop-blur-xl flex flex-col shadow-2xl h-full pt-16 border-r border-gray-200/50 dark:border-gray-800/50">

                <div class="flex-1 flex flex-col overflow-y-auto px-3 py-6 custom-scrollbar">
                    <div class="mb-4 px-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Main Menu</p>
                    </div>
                    <nav class="space-y-1">
                        <template v-for="item in navItems" :key="item.label">
                            <div v-if="item.isDropdown" class="space-y-1">
                                <button @click="toggleWorkforce" :class="[
                                    isWorkforceOpen ? 'text-blue-600 bg-white/50 dark:bg-gray-900/50' : 'text-gray-500 dark:text-gray-400',
                                    'w-full flex items-center justify-between px-3 py-3.5 text-[14px] font-bold rounded-xl hover:bg-white/50 dark:hover:bg-gray-900/50 transition-all duration-300'
                                ]">
                                    <div class="flex items-center">
                                        <div :class="[isWorkforceOpen ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600' : 'text-gray-400']"
                                            class="p-2 rounded-lg mr-3 transition-colors duration-300">
                                            <component :is="item.icon" class="h-5 w-5" />
                                        </div>
                                        <span class="truncate tracking-tight">{{ item.label }}</span>
                                    </div>
                                    <ChevronRight
                                        :class="['h-4 w-4 transition-transform duration-300', isWorkforceOpen ? 'rotate-90' : 'text-gray-400']" />
                                </button>

                                <div v-show="isWorkforceOpen" class="pl-12 space-y-1 mt-1 transition-all">
                                    <Link v-for="subItem in item.children" :key="subItem.label" :href="subItem.href"
                                        @click="handleNavClick" :class="[
                                            isActive(subItem.href) ? 'text-blue-600' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                                        ]" class="flex items-center py-2.5 text-[13px] font-bold transition-colors">
                                        <component :is="subItem.icon" class="h-4 w-4 mr-3" />
                                        {{ subItem.label }}
                                    </Link>
                                </div>
                            </div>

                            <Link v-else :href="item.href" @click="handleNavClick" :class="[
                                isActive(item.href)
                                    ? isSupplier
                                        ? 'bg-white dark:bg-gray-900 text-emerald-600 shadow-sm ring-1 ring-gray-200/50 dark:ring-gray-800'
                                        : 'bg-white dark:bg-gray-900 text-blue-600 shadow-sm ring-1 ring-gray-200/50 dark:ring-gray-800'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-900/50 hover:text-gray-900 dark:hover:text-white'
                            ]"
                                class="group relative flex items-center justify-between px-3 py-3 text-[14px] font-bold rounded-xl transition-all duration-300">
                                <div v-if="isActive(item.href)" :class="isSupplier ? 'bg-emerald-600' : 'bg-blue-600'"
                                    class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-r-full"></div>
                                <div class="flex items-center relative z-10">
                                    <div :class="[
                                        isActive(item.href)
                                            ? isSupplier
                                                ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600'
                                                : 'bg-blue-50 dark:bg-blue-900/30 text-blue-600'
                                            : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'
                                    ]" class="p-2 rounded-lg transition-colors duration-300 mr-3">
                                        <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                                    </div>
                                    <span class="truncate tracking-tight">{{ item.label }}</span>
                                </div>
                            </Link>
                        </template>
                    </nav>
                </div>

                <div class="p-4 mt-auto border-t border-gray-100 dark:border-gray-800">
                    <div
                        class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md rounded-2xl p-3 border border-gray-100/50 dark:border-gray-800/50 shadow-lg">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="relative flex-shrink-0">
                                <img v-if="userPhotoUrl" :src="userPhotoUrl" alt="Profile"
                                    class="h-10 w-10 rounded-xl object-cover shadow-lg"
                                    :class="isSupplier ? 'shadow-emerald-500/30' : 'shadow-blue-500/30'" />
                                <div v-else :class="isSupplier
                                    ? 'from-emerald-600 to-teal-700 shadow-emerald-500/30'
                                    : 'from-blue-600 to-indigo-700 shadow-blue-500/30'"
                                    class="h-10 w-10 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-sm font-black shadow-lg uppercase">
                                    {{ displayInitial }}
                                </div>
                                <div
                                    class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full">
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-xs font-black text-gray-900 dark:text-white truncate uppercase tracking-tighter">
                                    {{ displayName }}
                                </p>
                                <div class="flex items-center gap-1 mt-0.5 mb-1">
                                    <Building2 class="h-3 w-3 text-gray-400" />
                                    <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'"
                                        class="text-[9px] font-black uppercase truncate">
                                        {{ displayDepartment }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <ShieldCheck :class="isSupplier ? 'text-emerald-500' : 'text-blue-500'"
                                        class="h-3 w-3" />
                                    <span class="text-[9px] font-black text-gray-400 uppercase truncate">
                                        {{ displayPosition }}
                                    </span>
                                </div>
                            </div>

                            <button @click.stop="showLogoutModal = true; isOpen = false"
                                class="p-2.5 rounded-xl bg-gray-100/80 dark:bg-gray-800/80 text-gray-400 hover:text-red-500 hover:bg-red-50/80 dark:hover:bg-red-900/20 transition-all duration-300 backdrop-blur-sm">
                                <LogOut class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </transition>

        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="showLogoutModal"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="showLogoutModal = false">
                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-sm p-6 flex flex-col items-center text-center transform transition-all duration-300 scale-100">
                        <div
                            class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                            <LogOut class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Sign Out</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 px-2">
                            Are you sure you want to sign out of your account?
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 w-full">
                            <button @click="showLogoutModal = false"
                                class="w-full sm:flex-1 py-3 text-sm font-bold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                Cancel
                            </button>
                            <Link :href="logoutRoute" method="post" as="button"
                                class="w-full sm:flex-1 py-3 text-sm font-bold rounded-xl bg-red-600 text-white hover:bg-red-700 transition shadow-lg shadow-red-500/20">
                                Confirm Sign Out
                            </Link>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.2);
    border-radius: 10px;
}

/* Modal Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
    transition: transform 0.2s ease;
}

.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
    transform: scale(0.95) translateY(10px);
}
</style>