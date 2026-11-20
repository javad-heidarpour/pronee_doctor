<script setup>
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object,
  organizations: Array,
  staffOptions: Array,
  reviews: Array,
  canManage: Boolean,
  currentUserId: Number,
})

const form = useForm({
  organization_id: props.organizations?.find(item => item.can_manage)?.id ?? '',
  clinic_id: '',
  staff_user_id: '',
  period_start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  period_end: new Date().toISOString().slice(0, 10),
  reliability_score: 3,
  task_completion_score: 3,
  teamwork_score: 3,
  communication_score: 3,
  learning_score: 3,
  strengths: '',
  improvement_areas: '',
  goals: '',
  reviewer_notes: '',
})

const activeOrganization = computed(() => props.organizations?.find(item => String(item.id) === String(form.organization_id)))
const submit = () => form.post(route('portal.staff-performance.store'), { preserveScroll: true, onSuccess: () => form.reset('staff_user_id','strengths','improvement_areas','goals','reviewer_notes') })
const publish = review => router.patch(route('portal.staff-performance.publish', review.id), {}, { preserveScroll: true })
const acknowledge = review => {
  const response = window.prompt('اگر توضیحی درباره ارزیابی دارید بنویسید (اختیاری):', review.employee_response || '')
  if (response === null) return
  router.patch(route('portal.staff-performance.acknowledge', review.id), { employee_response: response }, { preserveScroll: true })
}
const scoreFields = [
  ['reliability_score','قابلیت اتکا و مسئولیت‌پذیری'],
  ['task_completion_score','انجام وظایف محوله'],
  ['teamwork_score','همکاری تیمی'],
  ['communication_score','ارتباط حرفه‌ای'],
  ['learning_score','یادگیری و توسعه مهارت'],
]
const scoreLabel = value => ({1:'نیازمند بهبود جدی',2:'نیازمند بهبود',3:'مطلوب',4:'بسیار خوب',5:'عالی'})[Number(value)] || '—'
const date = value => value ? new Date(value + 'T00:00:00').toLocaleDateString('fa-IR') : '—'
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 to-indigo-800 p-6 text-white">
        <p class="text-xs font-bold text-indigo-200">PRONEEX · HUMAN RESOURCES</p>
        <h1 class="mt-2 text-2xl font-black">ارزیابی عملکرد کارکنان</h1>
        <p class="mt-2 text-sm leading-7 text-white/80">ثبت بازخورد ساختاریافته، هدف‌گذاری و تأیید دریافت ارزیابی توسط کارمند.</p>
      </header>

      <div class="flex flex-wrap gap-2">
        <Link :href="route('portal.staff-attendance.index')" class="rounded-xl border bg-white px-4 py-2 text-sm font-bold text-slate-700">بازگشت به حضور و غیاب</Link>
      </div>

      <section v-if="canManage" class="rounded-2xl border bg-white p-4 sm:p-5">
        <h2 class="font-black">ثبت ارزیابی جدید</h2>
        <form class="mt-4 space-y-4" @submit.prevent="submit">
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm">مجموعه
              <select v-model="form.organization_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                <option value="">انتخاب مجموعه</option>
                <option v-for="item in organizations.filter(o=>o.can_manage)" :key="item.id" :value="item.id">{{item.name}}</option>
              </select>
            </label>
            <label class="text-sm">شعبه
              <select v-model="form.clinic_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                <option value="">انتخاب شعبه</option>
                <option v-for="item in activeOrganization?.clinics || []" :key="item.id" :value="item.id">{{item.name}}</option>
              </select>
            </label>
            <label class="text-sm">کارمند
              <select v-model="form.staff_user_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                <option value="">انتخاب کارمند</option>
                <option v-for="item in staffOptions.filter(s=>String(s.organization_id)===String(form.organization_id))" :key="item.id" :value="item.id">{{item.label}}</option>
              </select>
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label class="text-sm">از تاریخ<input v-model="form.period_start" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
              <label class="text-sm">تا تاریخ<input v-model="form.period_end" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
            </div>
          </div>

          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <label v-for="[key,label] in scoreFields" :key="key" class="rounded-xl border p-3 text-sm">
              <span class="font-bold">{{label}}</span>
              <div class="mt-2 flex items-center gap-2">
                <select v-model.number="form[key]" class="flex-1 rounded-lg border-slate-300 text-sm">
                  <option v-for="n in [1,2,3,4,5]" :key="n" :value="n">{{n}} · {{scoreLabel(n)}}</option>
                </select>
              </div>
            </label>
          </div>

          <div class="grid gap-3 md:grid-cols-2">
            <label class="text-sm">نقاط قوت<textarea v-model="form.strengths" rows="3" maxlength="2000" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="نمونه‌های رفتاری و کاری قابل استناد"></textarea></label>
            <label class="text-sm">زمینه‌های قابل بهبود<textarea v-model="form.improvement_areas" rows="3" maxlength="2000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
            <label class="text-sm">اهداف دوره بعد<textarea v-model="form.goals" rows="3" maxlength="2000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
            <label class="text-sm">یادداشت ارزیاب<textarea v-model="form.reviewer_notes" rows="3" maxlength="2000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
          </div>
          <p v-if="Object.keys(form.errors).length" class="text-sm text-rose-700">{{Object.values(form.errors).join(' · ')}}</p>
          <button :disabled="form.processing" class="rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">ذخیره پیش‌نویس ارزیابی</button>
        </form>
      </section>

      <section class="space-y-3">
        <h2 class="font-black">سوابق ارزیابی</h2>
        <article v-for="review in reviews" :key="review.id" class="rounded-2xl border bg-white p-4 sm:p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="font-black">{{review.staff_name}} {{review.staff_family || ''}}</h3>
              <p class="mt-1 text-xs text-slate-500">{{review.clinic_name}} · {{date(review.period_start)}} تا {{date(review.period_end)}}</p>
              <p class="mt-1 text-xs text-slate-500">ارزیاب: {{review.reviewer_name || '—'}} {{review.reviewer_family || ''}}</p>
            </div>
            <span class="rounded-lg px-3 py-1 text-xs font-bold" :class="review.status==='published'?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700'">{{review.status==='published'?'منتشرشده':'پیش‌نویس'}}</span>
          </div>
          <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <div v-for="[key,label] in scoreFields" :key="key" class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">{{label}}</p><strong class="mt-1 block">{{review[key]}} از ۵</strong><p class="text-[11px] text-slate-500">{{scoreLabel(review[key])}}</p></div>
          </div>
          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <div v-if="review.strengths"><strong class="text-xs">نقاط قوت</strong><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{review.strengths}}</p></div>
            <div v-if="review.improvement_areas"><strong class="text-xs">زمینه‌های بهبود</strong><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{review.improvement_areas}}</p></div>
            <div v-if="review.goals"><strong class="text-xs">اهداف بعدی</strong><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{review.goals}}</p></div>
            <div v-if="review.reviewer_notes"><strong class="text-xs">یادداشت ارزیاب</strong><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{review.reviewer_notes}}</p></div>
          </div>
          <div class="mt-4 flex flex-wrap items-center gap-2">
            <button v-if="canManage && review.status==='draft'" @click="publish(review)" class="rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white">انتشار برای کارمند</button>
            <button v-if="Number(review.staff_user_id)===Number(currentUserId) && review.status==='published'" @click="acknowledge(review)" class="rounded-xl border border-indigo-200 px-4 py-2 text-xs font-bold text-indigo-700">ثبت دریافت و پاسخ</button>
            <span v-if="review.acknowledged_at" class="text-xs text-emerald-700">دریافت‌شده در {{new Date(review.acknowledged_at).toLocaleDateString('fa-IR')}}</span>
          </div>
          <p v-if="review.employee_response" class="mt-3 rounded-xl bg-indigo-50 p-3 text-sm leading-6">پاسخ کارمند: {{review.employee_response}}</p>
        </article>
        <div v-if="!reviews?.length" class="rounded-2xl border border-dashed p-8 text-center text-sm text-slate-500">هنوز ارزیابی‌ای برای این محدوده ثبت نشده است.</div>
      </section>

      <p class="rounded-xl bg-slate-50 p-3 text-xs leading-6 text-slate-600">این فرم برای بازخورد شغلی و توسعه مهارت است. از درج اطلاعات بیماران، تشخیص، نسخه یا سایر داده‌های محرمانه سلامت در ارزیابی کارکنان خودداری کنید. ثبت دریافت توسط کارمند به معنی موافقت با ارزیابی نیست.</p>
    </main>
  </PortalLayout>
</template>
