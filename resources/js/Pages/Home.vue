<template>
  <Head title="پزشکان پرونیکس | سامانه هوشمند سلامت" />

  <div dir="rtl" class="min-h-screen overflow-hidden bg-[#f6f9f7] text-slate-800">
    <!-- Header -->
    <header class="sticky top-0 z-40 border-b border-emerald-900/5 bg-white/90 backdrop-blur-xl">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        <Link href="/" class="group flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-lg shadow-emerald-900/15 transition group-hover:rotate-3">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16M4 12h16" />
              <circle cx="12" cy="12" r="9" />
            </svg>
          </span>
          <span>
            <span class="block text-lg font-black tracking-tight text-slate-900">پرونیکس</span>
            <span class="block text-[11px] font-medium text-slate-500">سامانه هوشمند سلامت</span>
          </span>
        </Link>

        <div class="hidden items-center gap-2 text-sm text-slate-500 sm:flex">
          <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
          همراه شما در مسیر سلامت
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 sm:hidden">پزشکان پرونیکس</span>
      </div>
    </header>

    <main class="relative mx-auto max-w-7xl px-4 pb-20 pt-10 sm:px-6 sm:pt-16 lg:px-8">
      <!-- subtle background decoration -->
      <div class="pointer-events-none absolute -right-40 top-0 h-80 w-80 rounded-full bg-emerald-100/60 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-40 top-40 h-72 w-72 rounded-full bg-teal-100/50 blur-3xl"></div>

      <!-- Intro -->
      <section class="relative mx-auto mb-10 max-w-3xl text-center sm:mb-14">
        <span class="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-white/80 px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm">
          <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
          پزشکان عضو پرونیکس
        </span>
        <h1 class="text-3xl font-black leading-[1.65] tracking-tight text-slate-900 sm:text-4xl md:text-5xl">
          مراقبت بهتر، با
          <span class="relative whitespace-nowrap text-emerald-700">
            انتخاب آگاهانه
            <span class="absolute -bottom-1 right-0 h-2 w-full rounded-full bg-emerald-200/70"></span>
          </span>
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-sm leading-8 text-slate-500 sm:text-base">
          پزشک مورد نظرتان را پیدا کنید، با خدمات و اطلاعات ایشان آشنا شوید و به صفحه اختصاصی پزشک دسترسی داشته باشید.
        </p>
      </section>

      <!-- Search and count -->
      <section class="relative mb-7 flex flex-col gap-4 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_12px_45px_rgba(15,80,55,0.06)] sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="relative w-full sm:max-w-md">
          <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="10.8" cy="10.8" r="6.8" />
            <path stroke-linecap="round" d="m16 16 4.5 4.5" />
          </svg>
          <input
            v-model="search"
            type="search"
            placeholder="جستجو بر اساس نام یا تخصص پزشک..."
            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pr-12 pl-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10"
            aria-label="جستجوی پزشکان"
          />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
          <label class="flex items-center gap-2 text-xs text-slate-500">
            <span class="shrink-0">مرتب‌سازی</span>
            <select
              v-model="sortMode"
              aria-label="مرتب‌سازی نتایج پزشکان"
              class="min-w-44 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-700 outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
            >
              <option value="nearest">نزدیک‌ترین نوبت آزاد</option>
              <option value="recent">تازه‌ترین نوبت آزادشده</option>
            </select>
          </label>

          <div class="flex items-center justify-between gap-3 px-1 sm:justify-end">
            <span class="text-xs text-slate-500">پزشکان قابل مشاهده</span>
            <span class="inline-flex min-w-10 items-center justify-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-black tabular-nums text-emerald-800">
              {{ filteredDoctors.length }}
            </span>
          </div>
        </div>
      </section>

      <!-- Doctor cards -->
      <section class="relative">
        <div class="mb-5 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-slate-900 sm:text-xl">پزشکان ما</h2>
            <p class="mt-1 text-xs text-slate-500">برای مشاهده اطلاعات و خدمات، پزشک را انتخاب کنید.</p>
          </div>
          <span class="hidden rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-500 sm:inline-flex">فهرست پزشکان فعال</span>
        </div>

        <div v-if="filteredDoctors.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <Link
            v-for="doctor in filteredDoctors"
            :key="doctor.id"
            :href="`/${doctor.slug}`"
            class="group relative overflow-hidden rounded-[1.6rem] border border-slate-200/80 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_45px_rgba(15,80,55,0.12)] focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
          >
            <div class="relative flex h-52 items-center justify-center overflow-hidden rounded-[1.2rem] bg-gradient-to-br from-emerald-50 via-teal-50 to-slate-100">
              <div class="absolute -right-8 -top-10 h-36 w-36 rounded-full border-[18px] border-white/40"></div>
              <div class="absolute -bottom-14 -left-8 h-40 w-40 rounded-full bg-emerald-100/60 blur-xl"></div>

              <img
                v-if="doctor.photo"
                :src="doctor.photo.startsWith('http') ? doctor.photo : (doctor.photo.startsWith('/') ? doctor.photo : `/storage/${doctor.photo}`)"
                :alt="doctorFullName(doctor)"
                loading="lazy"
                class="relative z-10 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
              />
              <div v-else class="relative z-10 flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-white text-3xl font-black text-emerald-700 shadow-lg shadow-emerald-900/10">
                {{ doctorInitials(doctor) }}
              </div>

              <span class="absolute right-3 top-3 z-20 inline-flex items-center gap-1.5 rounded-full border border-white/80 bg-white/90 px-2.5 py-1.5 text-[10px] font-bold text-emerald-800 shadow-sm backdrop-blur">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                نوبت آزاد
              </span>
            </div>

            <div class="px-2 pb-2 pt-4">
              <h3 class="truncate text-base font-black text-slate-900 transition group-hover:text-emerald-800">
                {{ doctor.caption || doctorFullName(doctor) }}
              </h3>
              <p class="mt-1.5 min-h-6 truncate text-sm text-slate-500">
                {{ doctor.specialty_title || "پزشک عضو پرونیکس" }}
              </p>

              <div
                v-if="displayedSlot(doctor) && displayedClinic(doctor)"
                class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3"
              >
                <p class="text-[11px] font-bold text-emerald-800">
                  {{ sortMode === "recent" ? "تازه‌ترین فرصت آزادشده" : "نزدیک‌ترین نوبت آزاد" }}
                </p>
                <p class="mt-1 text-sm font-black text-slate-900">
                  {{ formatPersianDate(displayedSlot(doctor).date) }}
                  · ساعت {{ displayedSlot(doctor).start_time }}
                </p>
                <p class="mt-1 truncate text-xs text-slate-600">
                  {{ displayedClinic(doctor).name }}
                  <span v-if="displayedClinic(doctor).city_name"> · {{ displayedClinic(doctor).city_name }}</span>
                </p>
                <p v-if="doctor.clinics?.length > 1" class="mt-1 text-[11px] text-slate-500">
                  دارای نوبت در {{ doctor.clinics.length }} شعبه
                </p>
              </div>

              <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-xs font-bold text-emerald-700">مشاهده صفحه پزشک</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 transition duration-300 group-hover:bg-emerald-700 group-hover:text-white">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                  </svg>
                </span>
              </div>
            </div>
          </Link>
        </div>

        <!-- No search result -->
        <div v-else-if="doctors.length" class="rounded-3xl border border-dashed border-slate-300 bg-white/80 px-5 py-14 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
              <circle cx="10.8" cy="10.8" r="6.8" />
              <path stroke-linecap="round" d="m16 16 4.5 4.5M8 10.8h5.6" />
            </svg>
          </div>
          <h3 class="mt-4 text-base font-black text-slate-800">پزشکی پیدا نشد</h3>
          <p class="mt-2 text-sm text-slate-500">عبارت دیگری را برای جستجو امتحان کنید.</p>
          <button @click="search = ''" class="mt-5 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800">پاک کردن جستجو</button>
        </div>

        <!-- Empty state -->
        <div v-else class="rounded-3xl border border-dashed border-emerald-200 bg-white/80 px-5 py-14 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0" />
            </svg>
          </div>
          <h3 class="mt-4 text-base font-black text-slate-800">در حال حاضر نوبت آزادی برای رزرو ثبت نشده است</h3>
          <p class="mt-2 text-sm text-slate-500">لطفاً کمی بعد دوباره بررسی کنید؛ فقط زمان‌های آزاد و قابل رزرو نمایش داده می‌شوند.</p>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200/80 bg-white">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-7 text-center sm:flex-row sm:px-6 lg:px-8">
        <span class="text-sm font-black text-emerald-800">پرونیکس</span>
        <p class="text-xs text-slate-400">هوش مصنوعی پرونیکس © ۲۰۲۶ · سامانه هوشمند خدمات پزشکی</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  doctors: {
    type: Array,
    default: () => [],
  },
});

const search = ref("");
const sortMode = ref("nearest");

const filteredDoctors = computed(() => {
  const term = search.value.trim().toLocaleLowerCase();
  const matches = term
    ? props.doctors.filter((doctor) => {
        const searchable = [
          doctor.caption,
          doctor.name,
          doctor.family,
          doctor.specialty_title,
        ]
          .filter(Boolean)
          .join(" ")
          .toLocaleLowerCase();

        return searchable.includes(term);
      })
    : [...props.doctors];

  return matches.sort((left, right) => {
    if (sortMode.value === "recent") {
      const leftTime = left.latest_available?.available_at
        ? new Date(left.latest_available.available_at).getTime()
        : 0;
      const rightTime = right.latest_available?.available_at
        ? new Date(right.latest_available.available_at).getTime()
        : 0;

      return rightTime - leftTime;
    }

    const leftTime = left.next_available
      ? new Date(`${left.next_available.date}T${left.next_available.start_time}:00`).getTime()
      : Number.MAX_SAFE_INTEGER;
    const rightTime = right.next_available
      ? new Date(`${right.next_available.date}T${right.next_available.start_time}:00`).getTime()
      : Number.MAX_SAFE_INTEGER;

    return leftTime - rightTime;
  });
});

function displayedSlot(doctor) {
  return sortMode.value === "recent"
    ? doctor.latest_available
    : doctor.next_available;
}

function displayedClinic(doctor) {
  return sortMode.value === "recent"
    ? doctor.latest_clinic
    : doctor.next_clinic;
}

function doctorFullName(doctor) {
  return [doctor.name, doctor.family].filter(Boolean).join(" ").trim() || "پزشک پرونیکس";
}

function doctorInitials(doctor) {
  const first = doctor.name?.trim()?.charAt(0) || "";
  const last = doctor.family?.trim()?.charAt(0) || "";
  return first + last || "پ";
}

function formatPersianDate(date) {
  if (!date) return "";

  const [year, month, day] = date.split("-").map(Number);
  const value = new Date(year, month - 1, day, 12);

  return new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(value);
}
</script>
