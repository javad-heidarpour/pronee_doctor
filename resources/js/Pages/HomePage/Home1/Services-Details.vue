<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },

  services: {
    type: Array,
    default: () => [],
  },
});

const bookingUrl = route("booking");

const categoryLabels = {
  general_surgery: "جراحی عمومی",
  cosmetic_surgery: "جراحی زیبایی",
  obesity_surgery: "جراحی چاقی",
};

const getCategoryLabel = (category) => {
  return categoryLabels[category] || category || "خدمات پزشکی";
};

const doctorName = props.doctor?.name || "پزشک";

const pageTitle = `خدمات پزشکی و درمانی ${doctorName}`;

const pageDescription =
  `آشنایی با خدمات قابل ارائه در مطب ${doctorName}، ` +
  "اطلاعات خدمات درمانی و دریافت نوبت آنلاین.";
</script>

<template>
  <main dir="rtl" class="bg-[#fafaf7] text-slate-800 overflow-hidden">
    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div
        class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-100/50 blur-3xl pointer-events-none"
      ></div>

      <div
        class="absolute bottom-0 -left-24 w-72 h-72 rounded-full bg-amber-100/40 blur-3xl pointer-events-none"
      ></div>

      <div class="relative max-w-7xl mx-auto px-5 pt-12 pb-16 lg:pt-20 lg:pb-24">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <!-- Hero Content -->
          <div>
            <div
              class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 text-emerald-800 text-sm mb-6"
            >
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              خدمات پزشکی و تخصصی
            </div>

            <h1 class="text-3xl md:text-5xl font-black leading-[1.7] text-slate-900">
              خدمات تخصصی
              <span class="text-emerald-700">
                {{ doctorName }}
              </span>
              در کنار شما
            </h1>

            <p class="mt-5 text-slate-500 leading-9 text-base md:text-lg max-w-xl">
              در این بخش با خدمات قابل ارائه در مطب آشنا شوید. اطلاعات هر خدمت به شما کمک
              می‌کند با آگاهی بیشتر درباره مسیر مراجعه و دریافت خدمات پزشکی تصمیم بگیرید.
            </p>

            <div class="flex flex-wrap gap-3 mt-8">
              <Link
                              :href="route('booking')"

                class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-emerald-700 text-white font-bold hover:bg-emerald-800 transition shadow-lg shadow-emerald-900/10"
              >
                دریافت نوبت آنلاین
                <span>←</span>
              </Link>

              <!-- <a
                href="#services"
                class="inline-flex items-center justify-center px-7 py-4 rounded-2xl border border-slate-200 bg-white text-slate-700 font-bold hover:border-emerald-500 transition"
              >
                مشاهده خدمات
              </a> -->
            </div>
          </div>

          <!-- Hero Visual -->
          <div class="relative">
            <div class="absolute inset-5 rounded-[3rem] bg-emerald-100 rotate-3"></div>

            <div
              class="relative overflow-hidden rounded-[3rem] bg-[#e9f0e9] aspect-[5/4] flex items-center justify-center"
            >
              <div class="text-center p-8">
                <div
                  class="w-28 h-28 mx-auto rounded-full bg-white flex items-center justify-center text-5xl shadow-sm"
                >
                  🩺
                </div>

                <h2 class="mt-6 text-2xl font-black text-emerald-950">
                  مراقبت حرفه‌ای، تجربه‌ای مطمئن
                </h2>

                <p class="mt-3 text-emerald-900/70 leading-8">
                  توجه به نیازهای بیمار در تمام مراحل مراجعه و درمان
                </p>
              </div>
            </div>

            <div
              class="absolute -bottom-5 right-5 md:right-10 bg-white shadow-xl rounded-2xl p-4 flex items-center gap-3"
            >
              <span class="text-2xl">💚</span>

              <div>
                <div class="font-bold text-slate-800 text-sm">
                  همراه شما در مسیر سلامت
                </div>

                <div class="text-xs text-slate-400 mt-1">مشاوره، مراقبت و پیگیری</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Intro -->
    <section class="py-12 md:py-16">
      <div class="max-w-5xl mx-auto px-5 text-center">
        <span class="text-emerald-700 font-bold text-sm"> خدمات مطب </span>

        <h2 class="text-2xl md:text-3xl font-black mt-4">
          انتخاب خدمات متناسب با نیاز شما
        </h2>

        <p class="text-slate-500 leading-9 mt-5 max-w-3xl mx-auto">
          در این بخش می‌توانید خدمات ارائه‌شده در مطب را بررسی کنید. برای آشنایی بیشتر با
          هر خدمت، توضیحات مربوط به آن را مطالعه کرده و در صورت نیاز برای دریافت نوبت
          اقدام کنید.
        </p>
      </div>
    </section>

    <!-- Services -->
    <section id="services" class="py-14 bg-white scroll-mt-24">
      <div class="max-w-7xl mx-auto px-5">
        <!-- Section Header -->
        <div class="flex flex-wrap justify-between items-end gap-5 mb-10">
          <div>
            <span class="text-emerald-700 text-sm font-bold"> MEDICAL SERVICES </span>

            <h2 class="text-2xl md:text-3xl font-black mt-3">خدمات قابل ارائه در مطب</h2>

            <p class="text-slate-500 mt-3 leading-8">
              برای آشنایی بیشتر، خدمات زیر را بررسی کنید.
            </p>
          </div>

          <span v-if="services.length" class="text-sm text-slate-400">
            {{ services.length }} خدمت
          </span>
        </div>

        <!-- Services Grid -->
        <div v-if="services.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <article
            v-for="(service, index) in services"
            :key="service.id"
            class="group relative flex flex-col bg-[#fafaf7] rounded-3xl p-7 border border-slate-100 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300"
          >
            <!-- Card Top -->
            <div class="flex items-center justify-between">
              <!-- HTML Icon -->
              <div
                class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-700 group-hover:bg-emerald-100 transition"
              >
                <span
                  v-if="service.icon_html"
                  class="flex items-center justify-center [&_svg]:w-8 [&_svg]:h-8 [&_svg]:block [&_svg]:fill-current"
                  v-html="service.icon_html"
                ></span>

                <span v-else class="text-3xl"> ✚ </span>
              </div>
              <span
                class="text-4xl font-black text-emerald-200/70 group-hover:text-emerald-600 transition-colors duration-300"
              >
                <span v-if="index < 10">0{{ index }}</span>
                <span v-else>{{ index }}</span>
              </span>
            </div>

            <!-- Card Content -->
            <div class="mt-7 flex-1">
              <span class="text-xs text-emerald-700 font-bold">
                {{ getCategoryLabel(service.category) }}
              </span>

              <h3 class="text-lg font-black mt-3 leading-8 text-slate-800">
                {{ service.title }}
              </h3>

              <p
                v-if="service.short_description"
                class="text-sm text-slate-500 leading-8 mt-3"
              >
                {{ service.short_description }}
              </p>
            </div>

            <!-- Card Footer -->
            <div class="mt-6 pt-5 border-t border-slate-100">
              <Link
                :href="bookingUrl"
                class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700 group-hover:gap-4 transition-all"
              >
                دریافت نوبت
                <span>←</span>
              </Link>
            </div>
          </article>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="bg-[#fafaf7] border border-dashed border-slate-200 rounded-3xl p-10 md:p-16 text-center"
        >
          <div
            class="w-20 h-20 mx-auto rounded-full bg-emerald-50 flex items-center justify-center text-4xl"
          >
            🩺
          </div>

          <h3 class="text-xl font-black mt-6">خدماتی برای نمایش ثبت نشده است</h3>

          <p class="text-sm text-slate-500 leading-8 mt-3 max-w-md mx-auto">
            در حال حاضر اطلاعات خدمات مطب در دسترس نیست. برای دریافت نوبت می‌توانید از
            طریق سامانه نوبت‌دهی اقدام کنید.
          </p>

          <Link
                         :href="route('booking')"

            class="inline-flex items-center gap-2 mt-6 px-7 py-3 rounded-xl bg-emerald-700 text-white font-bold hover:bg-emerald-800 transition"
          >
            دریافت نوبت آنلاین
            <span>←</span>
          </Link>
        </div>
      </div>
    </section>

    <!-- Appointment Process -->
    <section class="py-16 md:py-20">
      <div class="max-w-7xl mx-auto px-5">
        <div class="text-center mb-12">
          <span class="text-emerald-700 text-sm font-bold"> EASY APPOINTMENT </span>

          <h2 class="text-2xl md:text-3xl font-black mt-4">
            دریافت نوبت در چند مرحله ساده
          </h2>

          <p class="text-slate-500 mt-4 leading-8">
            برای مراجعه به مطب، مراحل زیر را دنبال کنید.
          </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
          <div class="text-center p-7">
            <div
              class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-black"
            >
              ۱
            </div>

            <h3 class="font-black mt-5">انتخاب خدمت</h3>

            <p class="text-sm text-slate-500 leading-8 mt-3">
              خدمت مورد نظر خود را بررسی کنید.
            </p>
          </div>

          <div class="text-center p-7">
            <div
              class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-black"
            >
              ۲
            </div>

            <h3 class="font-black mt-5">انتخاب زمان مراجعه</h3>

            <p class="text-sm text-slate-500 leading-8 mt-3">
              زمان مناسب را از میان نوبت‌های موجود انتخاب کنید.
            </p>
          </div>

          <div class="text-center p-7">
            <div
              class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-black"
            >
              ۳
            </div>

            <h3 class="font-black mt-5">ثبت نوبت</h3>

            <p class="text-sm text-slate-500 leading-8 mt-3">
              اطلاعات خود را ثبت کرده و نوبت دریافت کنید.
            </p>
          </div>
        </div>

        <div class="text-center mt-5">
          <Link
                          :href="route('booking')"

            class="inline-flex items-center gap-3 px-9 py-4 rounded-2xl bg-emerald-700 text-white font-bold hover:bg-emerald-800 transition shadow-lg"
          >
            رزرو نوبت آنلاین
            <span>←</span>
          </Link>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="bg-white py-16">
      <div class="max-w-4xl mx-auto px-5">
        <div class="text-center mb-10">
          <span class="text-emerald-700 text-sm font-bold"> FAQ </span>

          <h2 class="text-2xl md:text-3xl font-black mt-3">
            سؤالات متداول درباره خدمات مطب
          </h2>
        </div>

        <div class="space-y-4">
          <details class="group bg-[#fafaf7] rounded-2xl p-5">
            <summary
              class="cursor-pointer list-none flex items-center justify-between gap-4 font-bold"
            >
              چگونه می‌توانم از خدمات مطب استفاده کنم؟

              <span class="text-emerald-700 text-xl group-open:rotate-45 transition">
                +
              </span>
            </summary>

            <p class="text-sm text-slate-500 leading-8 mt-4">
              با مراجعه به بخش نوبت‌دهی آنلاین می‌توانید زمان مراجعه خود را انتخاب و
              درخواست نوبت را ثبت کنید.
            </p>
          </details>

          <details class="group bg-[#fafaf7] rounded-2xl p-5">
            <summary
              class="cursor-pointer list-none flex items-center justify-between gap-4 font-bold"
            >
              آیا برای مراجعه نیاز به رزرو نوبت دارم؟

              <span class="text-emerald-700 text-xl group-open:rotate-45 transition">
                +
              </span>
            </summary>

            <p class="text-sm text-slate-500 leading-8 mt-4">
              برای برنامه‌ریزی بهتر مراجعه، پیشنهاد می‌شود پیش از حضور در مطب نوبت خود را
              ثبت کنید.
            </p>
          </details>

          <details class="group bg-[#fafaf7] rounded-2xl p-5">
            <summary
              class="cursor-pointer list-none flex items-center justify-between gap-4 font-bold"
            >
              چگونه درباره خدمات بیشتر اطلاعات کسب کنم؟

              <span class="text-emerald-700 text-xl group-open:rotate-45 transition">
                +
              </span>
            </summary>

            <p class="text-sm text-slate-500 leading-8 mt-4">
              می‌توانید از طریق بخش تماس با مطب، اطلاعات مورد نیاز خود را دریافت کنید.
            </p>
          </details>
        </div>
      </div>
    </section>
  </main>
</template>
