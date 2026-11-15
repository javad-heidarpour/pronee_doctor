<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    doctor: Object,
    faqs: Array,
    currentUrl: String
});

// ساخت عنوان و توضیحات داینامیک برای سئو
const pageTitle = `${props.doctor.full_name} | ${props.doctor.specialty.name} در ${props.doctor.city.name} - نوبت‌دهی آنلاین طب‌آوا`;
const pageDescription = `رزرو نوبت اینترنتی از ${props.doctor.full_name}، ${props.doctor.specialty.name} در ${props.doctor.city.name}. مشاهده آدرس مطب، شماره تماس، نظرات بیماران و رزرو آنلاین با تخفیف بیمه در سامانه طب‌آوا.`;

// ساخت داده‌های اسکیما (Schema.org) برای درک بهتر گوگل از صفحه
const jsonLd = {
  "@context": "https://schema.org",
  "@type": "Physician",
  "name": props.doctor.full_name,
  "image": "https://tebava.ir/placeholder-doctor.jpg", // در پروژه واقعی لینک عکس پزشک
  "description": pageDescription,
  "medicalSpecialty": props.doctor.specialty.name,
  "address": {
    "@type": "PostalAddress",
    "streetAddress": props.doctor.clinic_address,
    "addressLocality": props.doctor.city.name,
    "addressCountry": "IR"
  },
  "priceRange": "$$",
  "telephone": "021-00000000", // در پروژه واقعی از دیتابیس خوانده شود
  "url": props.currentUrl
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans" dir="rtl">
    
    <!-- ========================================== -->
    <!-- بخش ۱: موتور سئو (Meta Tags & Schema) -->
    <!-- ========================================== -->
    <Head>
      <title>{{ pageTitle }}</title>
      <meta name="description" :content="pageDescription" />
      <meta property="og:title" :content="pageTitle" />
      <meta property="og:description" :content="pageDescription" />
      <meta property="og:url" :content="currentUrl" />
      <meta property="og:type" content="profile" />
      <!-- اسکیما مارک‌آپ برای گوگل -->
      <!-- <script type="application/ld+json" v-html="JSON.stringify(jsonLd)" /> -->
    </Head>

    <!-- نوار مسیر (Breadcrumb) - عالی برای سئو و تجربه کاربری -->
    <nav class="bg-white border-b border-slate-200 py-3">
      <div class="max-w-6xl mx-auto px-4 text-sm text-slate-500 flex items-center gap-2">
        <a href="/" class="hover:text-blue-600 transition-colors">خانه</a>
        <span>/</span>
        <a :href="`/?specialty=${doctor.specialty.id}`" class="hover:text-blue-600 transition-colors">{{ doctor.specialty.name }}</a>
        <span>/</span>
        <span class="text-slate-800 font-medium">{{ doctor.name }}</span>
      </div>
    </nav>
    <div class="max-w-6xl mx-auto py-8 px-4 grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- ========================================== -->
      <!-- بخش ۲: محتوای اصلی (ستون ۸ از ۱۲) -->
      <!-- ========================================== -->
      <main class="lg:col-span-8 space-y-6">
        
        <!-- کارت معرفی اصلی با طراحی خلاقانه -->
        <article class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
          <!-- بنر گرادیانتی با پترن -->
          <div class="h-40 bg-gradient-to-l from-blue-700 via-blue-600 to-teal-500 relative">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
          </div>
          
          <div class="px-6 pb-8 md:px-10 relative">
            <!-- آواتار با افکت شناور و بج تایید -->
            <div class="absolute -top-16 right-6 md:right-10">
              <div class="w-32 h-32 md:w-40 md:h-40 bg-white rounded-3xl shadow-xl p-1.5 relative z-10">
                <div class="w-full h-full bg-slate-100 rounded-2xl flex items-center justify-center text-blue-600 text-5xl md:text-6xl font-bold overflow-hidden">
                  <!-- اگر عکس واقعی بود: <img :src="doctor.image" class="w-full h-full object-cover"> -->
                  {{ doctor.full_name.charAt(0) }}
                </div>
              </div>
              <!-- بج تایید هویت (Trust Signal) -->
              <div class="absolute -bottom-3 -left-3 bg-green-500 text-white p-2 rounded-full border-4 border-white shadow-md z-20" title="هویت پزشک توسط طب‌آوا تایید شده است">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
              </div>
            </div>

            <div class="mt-20 md:mt-4 md:mr-48">
              <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-2">{{ doctor.full_name }}</h1>
              <p class="text-xl text-blue-600 font-bold mb-4">{{ doctor.specialty.name }}</p>
              
              <!-- نشان‌های اعتماد (Trust Badges) -->
              <div class="flex flex-wrap gap-3 mb-6">
                <span class="flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-xl text-sm font-medium border border-blue-100">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  نظام پزشکی: {{ doctor.medical_council_number }}
                </span>
                <span class="flex items-center gap-2 bg-amber-50 text-amber-700 px-4 py-2 rounded-xl text-sm font-medium border border-amber-100">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                  ۴.۸ از ۵ (بر اساس ۱۲۴ نظر)
                </span>
              </div>

              <section class="prose prose-slate max-w-none">
                <h2 class="text-xl font-bold text-slate-800 mb-3 flex items-center gap-2">
                  <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                  درباره پزشک
                </h2>
                <p class="text-slate-600 leading-8 text-justify">
                  {{ doctor.bio || 'این پزشک هنوز توضیحات تکمیلی درباره سوابق تحصیلی، دوره‌های تخصصی و حرفه‌ای خود در سامانه طب‌آوا ثبت نکرده است.' }}
                </p>
              </section>
            </div>
          </div>
        </article>

        <!-- بخش ویدیو معرفی (Creative Feature) -->
        <section v-if="doctor.profile_video_url" class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
          <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path></svg>
            ویدیو معرفی و پیام پزشک
          </h2>
          <div class="aspect-video bg-slate-900 rounded-2xl flex flex-col items-center justify-center text-white relative overflow-hidden group cursor-pointer shadow-inner">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
            <!-- دکمه پلی انیمیشنی -->
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center group-hover:scale-110 group-hover:bg-blue-600 transition-all duration-300 border border-white/30 shadow-2xl z-10">
              <svg class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path></svg>
            </div>
            <p class="absolute bottom-6 text-sm md:text-base text-slate-200 font-medium z-10">برای تماشای ویدیو معرفی کلیک کنید</p>
          </div>
        </section>

        <!-- بخش سوالات متداول (FAQ) - بمب سئو! -->
        <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
          <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            سوالات متداول درباره {{ doctor.full_name }}
          </h2>
          <div class="space-y-4">
            <div v-for="(faq, index) in faqs" :key="index" class="border border-slate-200 rounded-2xl p-5 hover:border-blue-300 transition-colors bg-slate-50/50">
              <h3 class="font-bold text-slate-800 mb-2 flex items-start gap-2">
                <span class="text-blue-600 mt-1">❓</span>
                {{ faq.question }}
              </h3>
              <p class="text-slate-600 leading-7 pr-7">{{ faq.answer }}</p>
            </div>
          </div>
        </section>

      </main>

      <!-- ========================================== -->
      <!-- بخش ۳: سایدبار چسبنده رزرو (ستون ۴ از ۱۲) -->
      <!-- ========================================== -->
      <aside class="lg:col-span-4">
        <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-blue-100 p-6 sticky top-24 transition-all duration-300 hover:shadow-2xl">
          <div class="text-center mb-6 pb-6 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 mb-1">دریافت نوبت ویزیت</h3>
            <p class="text-sm text-slate-500">نوبت خود را به صورت آنلاین رزرو کنید</p>
          </div>
          
          <div class="space-y-4 mb-8">
            <div class="flex justify-between items-center py-2">
              <span class="text-slate-600 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                هزینه ویزیت:
              </span>
              <span class="font-extrabold text-slate-900 text-xl">۲۰۰,۰۰۰ <span class="text-sm font-normal text-slate-500">تومان</span></span>
            </div>
            <div class="flex justify-between items-center py-2">
              <span class="text-slate-600 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                پوشش بیمه:
              </span>
              <span class="font-bold text-green-700 text-sm bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">تأمین اجتماعی و تکمیلی</span>
            </div>
          </div>

          <button class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 rounded-xl text-lg transition-all duration-300 shadow-lg shadow-blue-500/30 transform hover:-translate-y-1 flex items-center justify-center gap-2 group">
            <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            انتخاب روز و ساعت نوبت
          </button>
          
          <div class="mt-6 space-y-3 pt-6 border-t border-slate-100">
            <div class="flex items-center gap-2 text-xs text-slate-500 justify-center">
              <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <span>تضمین بازگشت وجه در صورت لغو تا ۲۴ ساعت قبل</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500 justify-center">
              <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              <span>پرداخت امن از درگاه بانکی معتبر (شاپرک)</span>
            </div>
          </div>
        </div>
      </aside>

    </div>
  </div>
</template>