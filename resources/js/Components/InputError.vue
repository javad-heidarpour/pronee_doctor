<script setup>
defineProps({
    message: {
        type: [String, Array],
        default: '',
    },
});
</script>

<template>
    <p v-if="message" class="mt-1 text-sm leading-6 text-red-600" role="alert" aria-live="polite">
        {{ Array.isArray(message) ? message[0] : message }}
    </p>
</template>
