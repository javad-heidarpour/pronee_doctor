<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({ type: [String, Number], required: true });
const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) input.value.focus();
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        ref="input"
        v-model="model"
        class="block w-full min-w-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-70"
    />
</template>
