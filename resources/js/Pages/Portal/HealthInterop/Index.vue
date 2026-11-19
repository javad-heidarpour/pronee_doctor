<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
  messages: { type: Array, default: () => [] },
  insuranceChecks: { type: Array, default: () => [] },
  destinations: { type: Object, default: () => ({}) },
})

const statusLabels = {
  pending: 'در صف ارسال',
  processing: 'در حال ارسال',
  sent: 'پذیرفته‌شده توسط مقصد',
  failed: 'خطا؛ نیازمند تلاش مجدد',
  blocked: 'اتصال غیرفعال',
  not_configured: 'اتصال پیکربندی نشده',
  awaiting_integration: 'در انتظار اتصال رسمی',
}
</script>

<template>
  <Head title="تبادل سلامت و بیمه‌گری" />
  <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6" dir="rtl">
    <header>
      <h1 class="text-2xl font-bold text-slate-800">تبادل سلامت و بیمه‌گری</h1>
      <p class="mt-2 text-sm text-slate-500">مدیریت وضعیت اتصال، استعلام بیمه و رویدادهای ارسالی به سامانه‌های ملی</p>
    </header>

    <div class="grid gap-3 sm:grid-cols-3">
      <section v-for="(item, key) in destinations" :key="key" class="rounded-xl border bg-white p-4">
        <div class="font-semibold text-slate-800">{{ key.toUpperCase() }}</div>
        <div class="mt-2 text-sm" :class="item.enabled ? 'text-emerald-700' : 'text-amber-700'">
          {{ item.enabled ? 'تنظیمات اتصال موجود است؛ قرارداد API باید تأیید شود' : 'غیرفعال؛ نیازمند مجوز و تنظیمات' }}
        </div>
      </section>
    </div>

    <section class="rounded-xl border bg-white p-4">
      <h2 class="mb-3 text-lg font-semibold">صف تبادل نوبت، ویزیت و نسخه</h2>
      <div v-if="!messages.length" class="py-8 text-center text-sm text-slate-500">هنوز رویدادی در صف تبادل ثبت نشده است.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-right text-sm">
          <thead><tr class="border-b text-slate-500"><th class="p-2">شناسه</th><th class="p-2">رویداد</th><th class="p-2">مقصد</th><th class="p-2">وضعیت</th><th class="p-2">شناسه پیگیری</th><th class="p-2">تلاش</th></tr></thead>
          <tbody><tr v-for="row in messages" :key="row.id" class="border-b last:border-0">
            <td class="p-2">{{ row.id }}</td><td class="p-2">{{ row.event_type }}</td><td class="p-2">{{ row.destination }}</td>
            <td class="p-2">{{ statusLabels[row.status] || row.status }}</td><td class="p-2">{{ row.remote_tracking_id || '—' }}</td><td class="p-2">{{ row.attempts }}</td>
          </tr></tbody>
        </table>
      </div>
    </section>

    <section class="rounded-xl border bg-white p-4">
      <h2 class="mb-3 text-lg font-semibold">استعلام‌های بیمه</h2>
      <p class="mb-3 text-xs text-amber-700">ثبت اطلاعات بیمه به معنی تأیید استحقاق یا تعهد بیمه‌گر نیست؛ تأیید فقط با پاسخ معتبر سرویس رسمی انجام می‌شود.</p>
      <div v-if="!insuranceChecks.length" class="py-6 text-center text-sm text-slate-500">استعلامی ثبت نشده است.</div>
      <div v-else class="space-y-2">
        <div v-for="row in insuranceChecks" :key="row.id" class="flex flex-wrap items-center justify-between gap-2 border-b py-2 text-sm">
          <span>{{ row.payer_name || row.payer_code }} · بیمار #{{ row.patient_id }}</span>
          <span class="text-amber-700">{{ statusLabels[row.status] || row.status }}</span>
        </div>
      </div>
    </section>
  </div>
</template>
