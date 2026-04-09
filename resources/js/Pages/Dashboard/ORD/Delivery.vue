<template>
     <AuthenticatedLayout>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">In-Transit Deliveries</h1>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="delivery in deliveries" :key="delivery.id" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-bold">{{ delivery.po_number }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.client_name }}</p>
          </div>
          <span :class="statusBadge(delivery.status)" class="px-2 py-1 text-xs rounded-full">
            {{ delivery.status }}
          </span>
        </div>
        <div class="mt-3 space-y-1 text-sm">
          <p><span class="font-medium">Total:</span> ₱{{ formatNumber(delivery.total_amount) }}</p>
          <p><span class="font-medium">Expected:</span> {{ delivery.delivery_date || 'TBD' }}</p>
          <p><span class="font-medium">Tracking #:</span> {{ delivery.tracking_number }}</p>
        </div>
        <div class="mt-4 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-center text-xs text-gray-500">
          🚚 Live tracking will appear here (Logistics module integration)
        </div>
      </div>
      <div v-if="deliveries.length === 0" class="col-span-full text-center py-12 text-gray-500">
        No deliveries in transit.
      </div>
    </div>
  </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({
  deliveries: Array
})

const formatNumber = (val) => {
  return new Intl.NumberFormat().format(val)
}

const statusBadge = (status) => {
  const map = {
    shipped: 'bg-purple-100 text-purple-800',
    delivery: 'bg-orange-100 text-orange-800',
    in_transit: 'bg-blue-100 text-blue-800',
  }
  return map[status] || 'bg-gray-100 text-gray-800'
}
</script>