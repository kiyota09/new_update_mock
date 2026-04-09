<template>
    <input :id="id" :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        :class="{ 'border-red-500': error }" />
</template>

<script setup>
defineProps({
    id: { type: String, default: '' },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' }, // Accept both String and Number
    error: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>