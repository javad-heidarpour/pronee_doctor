<template>
  <Head title="پزشکان پرونیکس | سامانه هوشمند سلامت" />

  <div
    dir="rtl"
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50"
  >
    <!-- Header -->
    <header class="bg-white/90 backdrop-blur border-b border-gray-100 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div
            class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xl"
          >
            P
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-800">پرونیکس</h1>
            <p class="text-xs text-gray-500">سامانه هوشمند سلامت</p>
          </div>
        </div>

        <span class="text-sm text-gray-500 hidden sm:block">
          همراه شما در مسیر سلامت
        </span>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-12">
      <!-- Hero -->
      <section class="text-center max-w-2xl mx-auto mb-12">
        <span
          class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-5"
        >
          پزشکان عضو پرونیکس
        </span>

        <h2 class="text-3xl md:text-5xl font-black text-gray-800 leading-tight mb-5">
          پزشک مورد نظر خود را
          <span class="text-blue-600">انتخاب کنید</span>
        </h2>

        <p class="text-gray-500 text-base md:text-lg leading-8">
          از میان پزشکان عضو سامانه پرونیکس، پزشک مورد نظر خود را انتخاب کنید و به صفحه
          اختصاصی ایشان دسترسی داشته باشید.
        </p>
      </section>

      <!-- Doctors -->
      <section>
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-800">فهرست پزشکان</h3>

          <span class="text-sm text-gray-500"> {{ doctors.length }} پزشک </span>
        </div>

        <div
          v-if="doctors.length"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
        >
          <Link
            v-for="doctor in doctors"
            :key="doctor.id"
            :href="`/${doctor.slug}`"
            class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden"
          >
            <!-- Doctor Image -->
            <div
              class="bg-gradient-to-br from-blue-50 to-slate-100 h-64 flex items-center justify-center overflow-hidden"
            >
              <img
                v-if="doctor.photo"
                :src="
                  doctor.photo.startsWith('http')
                    ? doctor.photo
                    : `/storage/${doctor.photo}`
                "
                :alt="`دکتر ${doctor.name} ${doctor.family}`"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />

              <div
                v-else
                class="w-28 h-28 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"
              >
                <svg
                  class="w-14 h-14"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0"
                  />
                </svg>
              </div>
            </div>

            <!-- Doctor Info -->
            <div class="p-5">
              <h4
                class="text-lg font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors"
              >
                {{ doctor.caption || `دکتر ${doctor.name} ${doctor.family}` }}
              </h4>

              <p class="text-sm text-gray-500 mb-5">
                {{ doctor.specialty_title || "پزشک عضو پرونیکس" }}
              </p>

              <div
                class="flex items-center justify-between border-t border-gray-100 pt-4"
              >
                <span class="text-sm font-semibold text-blue-600">
                  مشاهده صفحه پزشک
                </span>

                <span
                  class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 19l-7-7 7-7"
                    />
                  </svg>
                </span>
              </div>
            </div>
          </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
          <div
            class="w-20 h-20 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-5"
          >
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0"
              />
            </svg>
          </div>

          <h3 class="text-xl font-bold text-gray-800 mb-3">هنوز پزشکی ثبت نشده است</h3>

          <p class="text-gray-500">
            به‌زودی پزشکان بیشتری به خانواده پرونیکس اضافه خواهند شد.
          </p>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-100 bg-white mt-16">
      <div class="max-w-7xl mx-auto px-4 py-7 text-center">
        <p class="text-sm text-gray-500">هوش مصنوعی پرونیکس © ۲۰۲۶</p>
        <p class="text-xs text-gray-400 mt-2">سامانه هوشمند خدمات پزشکی</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
  doctors: {
    type: Array,
    default: () => [],
  },
});
</script>
