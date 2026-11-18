<script setup>
import { useForm, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ organizations: Array, doctors: Array, clinics: Array, isAdmin: Boolean, currentDoctorId: Number })
const form = useForm({ name: '', legal_name: '', registration_number: '', phone: '', email: '', clinic_ids: [] })
const inviteForms = {}
const create = () => form.post(route('portal.organizations.store'))
const inviteDoctor = (organization, event) => { const data = Object.fromEntries(new FormData(event.target).entries()); router.post(route('portal.organizations.doctors.invite', organization.id), { doctor_id: Number(data.doctor_id), role: data.role, revenue_share_basis_points: Math.round(Number(data.share) * 100) }) }
const attachClinic = (organization, event) => { const data = Object.fromEntries(new FormData(event.target).entries()); router.post(route('portal.organizations.clinics.attach', organization.id), { clinic_id: Number(data.clinic_id), branch_code: data.branch_code || null }) }
const acceptDoctor = organization => router.post(route('portal.organizations.doctors.accept', organization.id))
</script>

<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto max-w-6xl space-y-6 p-4">
      <header><h1 class="text-xl font-black text-slate-900">مجموعه‌های درمانی</h1><p class="mt-1 text-sm text-slate-500">مدیریت چند پزشک، چند شعبه و دسترسی کارکنان در یک ساختار سازمانی</p></header>
      <section v-if="!isAdmin" class="rounded-2xl border bg-white p-5">
        <h2 class="mb-4 font-bold">ایجاد مجموعه درمانی</h2>
        <form @submit.prevent="create" class="grid gap-3 md:grid-cols-2">
          <input v-model="form.name" required maxlength="180" placeholder="نام مجموعه درمانی" class="rounded-xl border-slate-300" />
          <input v-model="form.legal_name" placeholder="نام حقوقی (اختیاری)" class="rounded-xl border-slate-300" />
          <input v-model="form.registration_number" placeholder="شناسه / شماره ثبت" class="rounded-xl border-slate-300" />
          <input v-model="form.phone" placeholder="تلفن مجموعه" class="rounded-xl border-slate-300" />
          <input v-model="form.email" type="email" placeholder="ایمیل مجموعه" class="rounded-xl border-slate-300" />
          <div class="md:col-span-2"><p class="mb-2 text-sm font-semibold">شعبه‌های فعلی که می‌خواهید زیرمجموعه این مجموعه باشند</p><div class="grid gap-2 sm:grid-cols-2"><label v-for="clinic in clinics" :key="clinic.id" class="flex items-center gap-2 rounded-lg border p-3 text-sm"><input v-model="form.clinic_ids" type="checkbox" :value="clinic.id" />{{ clinic.name }} <span class="text-xs text-slate-400">{{ clinic.full_address }}</span></label></div></div>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-3 font-bold text-white disabled:opacity-50 md:col-span-2">ایجاد مجموعه</button>
        </form>
      </section>
      <section v-for="organization in organizations" :key="organization.id" class="space-y-4 rounded-2xl border bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-extrabold">{{ organization.name }}</h2><p class="text-xs text-slate-500">{{ organization.legal_name || 'مجموعه درمانی' }} · {{ organization.doctors_count }} پزشک · {{ organization.clinics_count }} شعبه · {{ organization.members_count }} عضو</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700">{{ organization.status }}</span></div>
        <div><h3 class="mb-2 text-sm font-bold">پزشکان مجموعه</h3><button v-if="organization.doctors.some(d => d.id === currentDoctorId && d.pivot.status === 'pending')" @click="acceptDoctor(organization)" class="mb-2 rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">تأیید عضویت من در این مجموعه</button><div class="grid gap-2 md:grid-cols-2"><div v-for="doctor in organization.doctors" :key="doctor.id" class="rounded-xl bg-slate-50 p-3 text-sm"><b>{{ doctor.name }} {{ doctor.family }}</b><p class="text-xs text-slate-500">{{ doctor.specialty_title || 'پزشک' }} · {{ doctor.pivot.role }} · {{ doctor.pivot.status === 'active' ? 'فعال' : 'در انتظار تأیید' }} · سهم توافقی {{ (doctor.pivot.revenue_share_basis_points/100).toFixed(0) }}٪</p></div></div></div>
        <div><h3 class="mb-2 text-sm font-bold">شعبه‌ها</h3><div class="flex flex-wrap gap-2"><span v-for="clinic in organization.clinics" :key="clinic.id" class="rounded-lg border px-3 py-2 text-xs">{{ clinic.name }} — {{ clinic.full_address || 'نشانی ثبت نشده' }}</span></div></div>
        <div v-if="!isAdmin" class="grid gap-3 border-t pt-4 md:grid-cols-2">
          <form @submit.prevent="inviteDoctor(organization, $event)" class="space-y-2 rounded-xl bg-slate-50 p-3">
            <h3 class="text-sm font-bold">دعوت پزشک</h3>
            <select name="doctor_id" required class="w-full rounded-lg border-slate-300 text-sm"><option value="">انتخاب پزشک</option><option v-for="doctor in doctors.filter(d => !organization.doctors.some(member => member.id === d.id))" :key="doctor.id" :value="doctor.id">{{ doctor.name }} {{ doctor.family }} — {{ doctor.specialty_title || 'پزشک' }}</option></select>
            <div class="grid grid-cols-2 gap-2"><select name="role" class="rounded-lg border-slate-300 text-sm"><option value="practitioner">پزشک همکار</option><option value="clinical_lead">مسئول بالینی</option><option value="manager">مدیر</option></select><input name="share" type="number" min="0" max="100" value="70" class="rounded-lg border-slate-300 text-sm" placeholder="سهم پزشک ٪" /></div>
            <button class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">ارسال دعوت</button>
          </form>
          <form @submit.prevent="attachClinic(organization, $event)" class="space-y-2 rounded-xl bg-slate-50 p-3">
            <h3 class="text-sm font-bold">اتصال شعبه پزشک عضو</h3>
            <select name="clinic_id" required class="w-full rounded-lg border-slate-300 text-sm"><option value="">انتخاب شعبه پزشک فعال مجموعه</option><option v-for="clinic in clinics.filter(c => organization.doctors.some(member => member.id === c.doctor_id && member.pivot.status === 'active') && !organization.clinics.some(branch => branch.id === c.id))" :key="clinic.id" :value="clinic.id">{{ clinic.name }} — {{ clinic.full_address || 'نشانی ثبت نشده' }}</option></select>
            <input name="branch_code" placeholder="کد شعبه (اختیاری)" class="w-full rounded-lg border-slate-300 text-sm" />
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold">اتصال شعبه</button>
          </form>
        </div>

        <p class="text-xs leading-6 text-amber-800">افزودن پزشک جدید از مسیر دعوت و تأیید خود پزشک انجام می‌شود. در این نسخه، برای حفظ سازگاری با نوبت‌دهی موجود، هر پزشک همچنان برنامه و پرونده عملیاتی مستقل خود را دارد؛ گزارش تجمیعی مجموعه بر اساس پیوند شعبه‌ها و پزشکان توسعه می‌یابد.</p>
      </section>
      <div v-if="!organizations.length" class="rounded-2xl border border-dashed p-8 text-center text-sm text-slate-500">هنوز مجموعه درمانی ثبت نشده است.</div>
    </main>
  </PortalLayout>
</template>
