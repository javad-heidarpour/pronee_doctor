<script setup>
defineProps({ value: { type: String, default: '' }, for: { type: String, default: undefined }, required: { type: Boolean, default: false } });
</script>

<template>
    <label :for="for" class="mb-1 block text-sm font-medium text-gray-700">
        <span v-if="value">{{ value }}</span><span v-else><slot /></span>
        <span v-if="required" class="ms-1 text-red-600" aria-hidden="true">*</span>
        <span v-if="required" class="sr-only"> (الزامی)</span>
    </label>
</template>
