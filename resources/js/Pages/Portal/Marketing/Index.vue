<script setup>
import { computed, ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ doctor: Object, clinics: Array, campaigns: Array, totals: Object, canManage: Boolean })
const form = useForm({ name: '', clinic_id: '', channel: 'social', budget_rial: '', starts_at: '', ends_at: '' })
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const channelName = value => ({ social: 'شبکه‌های اجتماعی', sms: 'پیامک', poster: 'پوستر و QR', website: 'وب‌سایت', partner: 'همکار حرفه‌ای', other: 'سایر' })[value] || value
const activeCampaigns = computed(() => props.campaigns.filter(c => c.status === 'active').length)
const copyMessage = ref('')
const copyLink = async url => {
  copyMessage.value = ''
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(url)
      copyMessage.value = 'لینک کمپین کپی شد.'
      return
    }
  } catch {}
  const field = document.createElement('textarea')
  field.value = url
  field.setAttribute('readonly', '')
  field.style.position = 'fixed'
  field.style.opacity = '0'
  document.body.appendChild(field)
  field.select()
  const copied = document.execCommand('copy')
  field.remove()
  copyMessage.value = copied ? 'لینک کمپین کپی شد.' : 'کپی خودکار ممکن نشد؛ نشانی لینک را انتخاب و کپی کنید.'
}
const submit = () => form.post(route('portal.marketing.store'), { onSuccess: () => form.reset('name', 'budget_rial', 'starts_at', 'ends_at') })
</script>

<template>
  <PortalLayout :doctor="doctor" title="رشد و بازاریابی">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-indigo-950 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold tracking-wider text-teal-200">PRONEEX · GROWTH NETWORK</p>
        <h1 class="mt-2 text-2xl font-black">مرکز رشد و بازاریابی سلامت</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-white/80">برای هر کانال تبلیغاتی یک لینک اختصاصی بسازید، عملکرد آن را بسنجید و بفهمید کدام مسیر واقعاً به نوبت منتهی می‌شود؛ بدون فروش اطلاعات بیمار یا تبلیغ درمان غیرضروری.</p>
        <Link :href="route('portal.marketing.discounts.index')" class="mt-4 inline-flex rounded-xl border border-white/30 px-4 py-2 text-sm font-bold text-white">مدیریت کدهای تخفیف</Link>
      </header>

      <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">کل کمپین‌ها</span><b class="mt-2 block text-2xl">{{ totals.campaigns }}</b></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">کمپین فعال</span><b class="mt-2 block text-2xl text-emerald-700">{{ activeCampaigns }}</b></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">بازدید لینک‌ها</span><b class="mt-2 block text-2xl">{{ totals.clicks }}</b></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">نوبت منتسب</span><b class="mt-2 block text-2xl text-indigo-700">{{ totals.bookings }}</b></article>
      </section>

      <section v-if="canManage" class="rounded-2xl border bg-white p-4 sm:p-6">
        <h2 class="text-lg font-black">ساخت کمپین جدید</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">برای اینستاگرام، پیامک، پوستر، وب‌سایت یا معرفی همکار، لینک مستقل ساخته می‌شود. آمار فقط به‌صورت تعداد کلیک و نوبت ثبت می‌شود.</p>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 md:grid-cols-2">
          <input v-model="form.name" required maxlength="160" placeholder="نام کمپین؛ مثلاً معرفی خدمات کلینیک" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="form.channel" class="rounded-xl border-slate-300 text-sm">
            <option value="social">شبکه‌های اجتماعی</option><option value="sms">پیامک</option><option value="poster">پوستر و QR</option><option value="website">وب‌سایت</option><option value="partner">همکار حرفه‌ای</option><option value="other">سایر</option>
          </select>
          <select v-model="form.clinic_id" class="rounded-xl border-slate-300 text-sm"><option value="">کل مطب‌ها / مجموعه</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select>
          <input v-model.number="form.budget_rial" type="number" min="0" placeholder="بودجه کمپین به ریال (اختیاری)" class="rounded-xl border-slate-300 text-sm" />
          <label class="text-xs text-slate-500">شروع کمپین<input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">پایان کمپین<input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50 md:col-span-2">ساخت کمپین و تولید لینک اختصاصی</button>
        </form>
      </section>

      <section class="space-y-3 rounded-2xl border bg-white p-4 sm:p-6">
        <div><h2 class="text-lg font-black">عملکرد کمپین‌ها</h2><p class="mt-1 text-xs text-slate-500">نوبت منتسب زمانی ثبت می‌شود که بیمار از لینک کمپین وارد صفحه پزشک شده و رزرو عمومی را تکمیل کند.</p></div>
        <article v-for="campaign in campaigns" :key="campaign.id" class="rounded-2xl border p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black">{{ campaign.name }}</h3><span class="rounded-full px-2 py-1 text-[11px]" :class="campaign.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ campaign.status === 'active' ? 'فعال' : 'متوقف' }}</span></div><p class="mt-1 text-xs text-slate-500">{{ channelName(campaign.channel) }} · {{ campaign.clinic_name || 'همه شعب' }} · کد {{ campaign.code }}</p></div>
            <button v-if="canManage" @click="router.post(route('portal.marketing.toggle', campaign.id))" class="rounded-lg border px-3 py-2 text-xs font-bold">{{ campaign.status === 'active' ? 'توقف کمپین' : 'فعال‌سازی' }}</button>
          </div>
          <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-slate-50 p-3"><b class="block text-lg">{{ campaign.clicks }}</b><span class="text-[11px] text-slate-500">کلیک</span></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="block text-lg">{{ campaign.bookings }}</b><span class="text-[11px] text-slate-500">نوبت</span></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="block text-lg">{{ campaign.conversion_rate }}٪</b><span class="text-[11px] text-slate-500">تبدیل</span></div>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-2">
            <a :href="campaign.share_url" target="_blank" rel="noopener" class="break-all text-xs font-semibold text-teal-700 underline">{{ campaign.share_url }}</a>
            <button @click="copyLink(campaign.share_url)" class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">کپی لینک</button>
          </div>
          <p v-if="campaign.budget_rial" class="mt-2 text-xs text-slate-500">بودجه ثبت‌شده: {{ money(campaign.budget_rial) }} <span v-if="campaign.cost_per_booking !== null">· هزینه هر نوبت منتسب: {{ money(campaign.cost_per_booking) }}</span></p>
        </article>
        <div v-if="!campaigns.length" class="rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500">هنوز کمپینی ثبت نشده است. اولین لینک بازاریابی خود را بسازید.</div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-900">
        <b>اصول بازاریابی مسئولانه پرونیکس:</b> تبلیغات باید اطلاع‌رسانی دقیق و غیرگمراه‌کننده باشند. از وعده نتیجه درمان، ترساندن بیمار، انتشار اطلاعات سلامت یا خریدوفروش داده بیماران استفاده نکنید. این ماژول هیچ امتیاز یا پورسانتی برای ارجاع درمانی ایجاد نمی‌کند.
      </section>
    </main>
  </PortalLayout>
</template>
