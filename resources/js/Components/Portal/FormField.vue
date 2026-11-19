<script setup>
import { useId } from 'vue';
const props = defineProps({ modelValue: [String, Number], label: { type: String, default: '' }, type: { type: String, default: 'text' }, required: Boolean, error: { type: String, default: '' }, hint: { type: String, default: '' }, disabled: Boolean, autocomplete: { type: String, default: undefined } });
defineEmits(['update:modelValue']);
const inputId = 'form-field-' + useId();
const errorId = inputId + '-error';
const hintId = inputId + '-hint';
</script>

<template>
  <div class="min-w-0">
    <label :for="inputId" class="mb-2 block text-sm font-bold text-gray-700">
      {{ label }} <span v-if="required" class="text-red-500" aria-hidden="true">*</span><span v-if="required" class="sr-only"> (الزامی)</span>
    </label>
    <input :id="inputId" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" :type="type" :required="required" :disabled="disabled" :autocomplete="autocomplete" :aria-required="required ? 'true' : undefined" :aria-invalid="error ? 'true' : undefined" :aria-describedby="[hint ? hintId : null, error ? errorId : null].filter(Boolean).join(' ') || undefined" class="w-full min-w-0 rounded-xl border border-gray-200 px-4 py-3 transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500 disabled:cursor-not-allowed disabled:bg-gray-100" />
    <p v-if="hint" :id="hintId" class="mt-1 text-xs leading-6 text-gray-500">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="mt-1 text-xs leading-6 text-red-600" role="alert">{{ error }}</p>
  </div>
</template>
