<template>
  <div>
    <div class="text-sm text-slate-700">{{ formatDate(dateStr) }}</div>
    <div class="text-xs text-slate-500">{{ formatTime(dateStr) }}</div>
  </div>
</template>

<script setup>
defineProps({ dateStr: { type: String, default: '' } })

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try { return new Intl.DateTimeFormat('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(dateStr)) } 
  catch { return '-' }
}
const formatTime = (dateStr) => {
  if (!dateStr) return ''
  try { return new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit' }).format(new Date(dateStr)) } 
  catch { return '' }
}
</script>