<template>
    <AuthenticatedLayout>
  <div class="p-6">
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order Calendar</h1>
      <div class="flex gap-2">
        <button @click="prevMonth" class="px-3 py-1 bg-gray-200 rounded">◀ Prev</button>
        <span class="px-3 py-1 font-medium">{{ currentMonthName }} {{ currentYear }}</span>
        <button @click="nextMonth" class="px-3 py-1 bg-gray-200 rounded">Next ▶</button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
      <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 text-center text-sm font-semibold">
        <div v-for="day in weekDays" :key="day" class="py-2 bg-gray-50 dark:bg-gray-900">
          {{ day }}
        </div>
      </div>
      <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
        <div
          v-for="(day, idx) in calendarDays"
          :key="idx"
          class="min-h-32 p-2 bg-white dark:bg-gray-800"
          :class="day.isCurrentMonth ? '' : 'bg-gray-50 dark:bg-gray-900'"
        >
          <div class="font-medium text-sm">{{ day.date }}</div>
          <div class="mt-1 space-y-1">
            <div
              v-for="order in day.orders"
              :key="order.id"
              class="text-xs p-1 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 truncate"
              :title="`${order.po_number} - ${order.client_name}`"
            >
              {{ order.po_number }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  orders: Array,
  currentYear: Number,
  currentMonth: Number,
})

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const currentYear = ref(props.currentYear)
const currentMonth = ref(props.currentMonth)

const currentMonthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value - 1).toLocaleString('default', { month: 'long' })
})

const calendarDays = computed(() => {
  const firstDayOfMonth = new Date(currentYear.value, currentMonth.value - 1, 1)
  const startDay = firstDayOfMonth.getDay()
  const daysInMonth = new Date(currentYear.value, currentMonth.value, 0).getDate()

  const days = []
  const ordersByDate = {}
  props.orders.forEach(order => {
    const d = order.delivery_date
    if (d) ordersByDate[d] = ordersByDate[d] || []
    ordersByDate[d].push(order)
  })

  // Previous month tail
  const prevMonthDays = startDay
  for (let i = prevMonthDays - 1; i >= 0; i--) {
    const date = new Date(currentYear.value, currentMonth.value - 1, -i)
    days.push({
      date: date.getDate(),
      isCurrentMonth: false,
      orders: []
    })
  }

  // Current month
  for (let i = 1; i <= daysInMonth; i++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value).padStart(2,'0')}-${String(i).padStart(2,'0')}`
    days.push({
      date: i,
      isCurrentMonth: true,
      orders: ordersByDate[dateStr] || []
    })
  }

  // Next month fill to complete 6 rows
  const totalCells = Math.ceil(days.length / 7) * 7
  while (days.length < totalCells) {
    days.push({ date: '', isCurrentMonth: false, orders: [] })
  }
  return days
})

const prevMonth = () => {
  if (currentMonth.value === 1) {
    currentMonth.value = 12
    currentYear.value--
  } else {
    currentMonth.value--
  }
  refresh()
}

const nextMonth = () => {
  if (currentMonth.value === 12) {
    currentMonth.value = 1
    currentYear.value++
  } else {
    currentMonth.value++
  }
  refresh()
}

const refresh = () => {
  window.location.href = route('ord.orders', { year: currentYear.value, month: currentMonth.value })
}
</script>