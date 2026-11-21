<template>
  <PortalLayout :doctor="doctor">
    <div class="space-y-6" dir="rtl">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div><h1 class="text-xl font-bold text-gray-900">تحلیل ظرفیت و پیشنهاد زمان‌بندی</h1>
          <p class="mt-1 text-sm text-gray-500">تحلیل اسلات‌های ثبت‌شده از {{ period.from }} تا {{ period.to }}؛ بدون تغییر خودکار برنامه</p></div>
        <div class="flex flex-wrap gap-2">
          <select v-model="localClinic" class="rounded-lg border-gray-300 text-sm">
            <option value="">همه مطب‌های مجاز</option>
            <option v-for="clinic in clinics" :key="clinic.id" :value="String(clinic.id)">{{ clinic.name }}</option>
          </select>
          <select v-model="localDays" class="rounded-lg border-gray-300 text-sm">
            <option value="28">۲۸ روز</option><option value="56">۵۶ روز</option><option value="90">۹۰ روز</option>
          </select>
          <button @click="applyFilters" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">اعمال</button>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div v-for="card in cards" :key="card.label" class="rounded-xl border border-gray-200 bg-white p-4">
          <p class="text-sm text-gray-500">{{ card.label }}</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ card.value }}</p>
        </div>
      </div>
      <section class="rounded-xl border border-gray-200 bg-white p-4">
        <h2 class="mb-3 font-bold text-gray-900">پیشنهاد بر اساس روز هفته</h2>
        <p v-if="!weekday_recommendations.length" class="text-sm text-gray-500">در این بازه اسلات کافی برای تحلیل وجود ندارد.</p>
        <div v-else class="overflow-x-auto"><table class="w-full min-w-[640px] text-sm">
          <thead class="bg-gray-50 text-right text-gray-600"><tr><th class="p-3">مطب</th><th class="p-3">روز هفته</th><th class="p-3">ظرفیت</th><th class="p-3">رزروشده</th><th class="p-3">روزهای مشاهده</th><th class="p-3">اشغال</th><th class="p-3">پیشنهاد</th></tr></thead>
          <tbody class="divide-y divide-gray-100"><tr v-for="item in weekday_recommendations" :key="item.clinic_id + '-' + item.day_of_week">
            <td class="p-3">{{ item.clinic_name }}</td><td class="p-3">{{ weekdayNames[item.day_of_week] }}</td>
            <td class="p-3">{{ item.capacity }}</td><td class="p-3">{{ item.occupied }}</td><td class="p-3">{{ item.observed_days }}</td><td class="p-3 font-semibold">{{ item.utilization_percent }}٪</td><td class="p-3 leading-6">{{ item.recommendation }}</td>
          </tr></tbody></table></div>
      </section>
      <section class="rounded-xl border border-gray-200 bg-white p-4">
        <h2 class="mb-3 font-bold text-gray-900">جزئیات روزانه</h2>
        <p v-if="!daily.length" class="text-sm text-gray-500">داده‌ای برای نمایش وجود ندارد.</p>
        <div v-else class="overflow-x-auto"><table class="w-full min-w-[560px] text-sm">
          <thead class="bg-gray-50 text-right text-gray-600"><tr><th class="p-3">تاریخ</th><th class="p-3">مطب</th><th class="p-3">ظرفیت</th><th class="p-3">رزروشده</th><th class="p-3">آزاد</th><th class="p-3">اشغال</th></tr></thead>
          <tbody class="divide-y divide-gray-100"><tr v-for="day in daily" :key="day.clinic_id + '-' + day.date">
            <td class="p-3">{{ day.date }}</td><td class="p-3">{{ day.clinic_name }}</td><td class="p-3">{{ day.capacity }}</td>
            <td class="p-3">{{ day.occupied }}</td><td class="p-3">{{ day.available }}</td><td class="p-3">{{ day.utilization_percent }}٪</td>
          </tr></tbody></table></div>
      </section>
      <p class="text-xs leading-6 text-gray-500">این پیشنهادها توصیفی و مبتنی بر اسلات‌های تولیدشده در بازه انتخابی هستند؛ ظرفیت خالیِ تولیدنشده را نمی‌بینند و جایگزین تصمیم پزشک یا مدیر مطب نیستند. هیچ ساعت کاری یا نوبتی خودکار ایجاد، حذف یا جابه‌جا نمی‌شود.</p>
    </div>
  </PortalLayout>
</template>
<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props = defineProps({
  doctor:{type:Object,required:true}, clinics:{type:Array,default:()=>[]},
  filters:{type:Object,required:true}, period:{type:Object,required:true},
  summary:{type:Object,required:true}, daily:{type:Array,default:()=>[]},
  weekday_recommendations:{type:Array,default:()=>[]},
})
const localClinic=ref(props.filters.clinic_id?String(props.filters.clinic_id):'')
const localDays=ref(String(props.filters.days||56))
const weekdayNames=['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه']
const cards=computed(()=>[
 {label:'کل ظرفیت ثبت‌شده',value:props.summary.capacity},
 {label:'نوبت‌های اشغال‌شده',value:props.summary.occupied},
 {label:'نوبت‌های آزاد',value:props.summary.available},
 {label:'درصد اشغال',value:props.summary.utilization_percent+'٪'},
])
function applyFilters(){
 router.get(route('portal.shifts.capacity'),{clinic_id:localClinic.value||undefined,days:localDays.value},
 {preserveState:true,preserveScroll:true,replace:true})
}
</script>
