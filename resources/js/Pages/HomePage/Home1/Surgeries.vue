<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  procedures: {
    type: Array,
    default: () => [],
  },
});

const bookingUrl = route("booking");

const doctorName = computed(() => {
  return (
    props.doctor?.full_name ||
    props.doctor?.name ||
    [props.doctor?.first_name, props.doctor?.last_name].filter(Boolean).join(" ") ||
    "پزشک"
  );
});

const categoryLabels = {
  general_surgery: "جراحی عمومی",
  obesity_surgery: "جراحی چاقی",
  cosmetic_surgery: "جراحی زیبایی",
};

const groupedProcedures = computed(() => {
  const groups = [
    {
      key: "general_surgery",
      title: "جراحی‌های عمومی",
      description:
        "بررسی و درمان بیماری‌هایی که ممکن است به مداخله جراحی نیاز داشته باشند.",
      items: [],
    },
    {
      key: "obesity_surgery",
      title: "جراحی‌های چاقی",
      description: "روش‌های جراحی با هدف کمک به مدیریت چاقی و کاهش وزن.",
      items: [],
    },
    {
      key: "cosmetic_surgery",
      title: "جراحی‌های زیبایی",
      description: "روش‌های جراحی با هدف اصلاح فرم و تناسب اندام.",
      items: [],
    },
  ];

  props.procedures.forEach((procedure) => {
    const group = groups.find((item) => item.key === procedure.category);

    if (group) {
      group.items.push(procedure);
    }
  });

  return groups.filter((group) => group.items.length > 0);
});

const formatNumber = (number) => {
  return String(number + 1).padStart(2, "0");
};

const getProcedureDescription = (procedure) => {
  return (
    procedure.short_description ||
    procedure.intro_title ||
    "برای آشنایی بیشتر با این روش جراحی، اطلاعات تکمیلی را مطالعه کنید."
  );
};

const pageTitle = computed(() => {
  return `جراحی‌های قابل ارائه | ${doctorName.value}`;
});

const pageDescription = computed(() => {
  return `آشنایی با جراحی‌های قابل ارائه توسط ${doctorName.value}، اطلاعات اولیه درباره روش‌های جراحی و امکان دریافت نوبت مشاوره.`;
});
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    :title="pageTitle"
    :subtitle="pageDescription"
    active-item="Surgeries"
  >
    <main dir="rtl" class="min-h-screen  text-slate-800">
      <!-- Hero -->
      <section class="relative overflow-hidden bg-[#e9f1e9]">
        <div
          class="absolute -top-24 -left-20 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"
        ></div>
        <div
          class="absolute -bottom-32 right-0 h-80 w-80 rounded-full bg-white/60 blur-3xl"
        ></div>

        <div class="relative mx-auto max-w-7xl px-5 py-16 md:px-8 md:py-24">
          <div class="grid items-center gap-12 lg:grid-cols-[1.15fr_.85fr]">
            <div>
              <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/70 px-4 py-2 text-sm font-bold text-emerald-800"
              >
                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                خدمات تخصصی جراحی
              </div>

              <h1
                class="max-w-3xl text-3xl font-black leading-[1.7] text-slate-900 md:text-5xl"
              >
                آشنایی با
                <span class="text-emerald-800">عمل‌های جراحی</span>
                و روش‌های درمانی
              </h1>

              <p class="mt-6 max-w-2xl text-base leading-9 text-slate-600 md:text-lg">
                در این بخش می‌توانید با جراحی‌های قابل ارائه، کاربردهای هر روش و اطلاعات
                اولیه مربوط به آن‌ها آشنا شوید. انتخاب روش درمان پس از بررسی شرایط فردی و
                مشاوره با پزشک انجام می‌شود.
              </p>

              <div class="mt-9 flex flex-wrap gap-4">
                <Link
                  :href="bookingUrl"
                  class="inline-flex items-center justify-center gap-3 rounded-2xl bg-emerald-800 px-7 py-4 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-1 hover:bg-emerald-900"
                >
                  دریافت نوبت مشاوره
                  <span>←</span>
                </Link>

          
              </div>
            </div>

            <!-- Hero visual -->
            <div class="relative mx-auto w-full max-w-md">
              <div
                class="absolute inset-5 rounded-[3rem] bg-emerald-200/60 rotate-6"
              ></div>

              <div
                class="relative overflow-hidden rounded-[3rem] border border-white bg-white p-8 shadow-2xl shadow-emerald-900/10"
              >
                <div
                  class="flex aspect-[4/3] items-center justify-center rounded-[2.3rem] bg-gradient-to-br from-emerald-50 to-[#dcebe0]"
                >
                  <div class="text-center">
                    <div
                      class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-white text-emerald-800 shadow-lg"
                    >
                      <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.4"
                        class="h-16 w-16"
                      >
                        <path
                          d="M8 3v5M16 3v5M5 8h14M6 8v11h12V8"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                        />
                        <path d="M12 11v5M9.5 13.5h5" stroke-linecap="round" />
                      </svg>
                    </div>
                    <p class="mt-6 text-xl font-black text-emerald-950">مراقبت و درمان</p>
                    <p class="mt-2 text-sm text-slate-500">
                      آگاهی، مشاوره و انتخاب آگاهانه
                    </p>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                  <div class="rounded-2xl bg-[#f5f8f3] p-4">
                    <p class="text-2xl font-black text-emerald-800">
                      {{ procedures.length }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-slate-500">عنوان جراحی</p>
                  </div>

                  <div class="rounded-2xl bg-[#f5f8f3] p-4">
                    <p class="text-2xl font-black text-emerald-800">
                      {{ groupedProcedures.length }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-slate-500">گروه جراحی</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Introduction -->
      <section class="mx-auto max-w-7xl px-5 py-16 md:px-8">
        <div class="grid gap-8 md:grid-cols-[.8fr_1.2fr] md:items-center">
          <div>
            <span class="text-sm font-black tracking-wide text-emerald-700">
              SURGICAL SERVICES
            </span>

            <h2
              class="mt-3 text-2xl font-black leading-relaxed text-slate-900 md:text-3xl"
            >
              انتخاب آگاهانه،
              <span class="text-emerald-800">قدم اول درمان</span>
            </h2>
          </div>

          <p class="text-sm leading-9 text-slate-600 md:text-base">
            هر عمل جراحی با توجه به نوع بیماری، شرایط جسمانی، سوابق پزشکی و نظر پزشک متخصص
            بررسی می‌شود. اطلاعات این صفحه برای آشنایی اولیه است و جایگزین معاینه و مشاوره
            پزشکی نخواهد بود.
          </p>
        </div>
      </section>

      <!-- Procedures -->
      <section
        id="surgery-list"
        class="mx-auto max-w-7xl scroll-mt-24 px-5 pb-20 md:px-8"
      >
        <div class="mb-12 flex flex-wrap items-end justify-between gap-5">
          <div>
            <span class="text-sm font-black text-emerald-700"> PROCEDURE CATALOG </span>
            <h2 class="mt-3 text-3xl font-black text-slate-900">فهرست عمل‌های جراحی</h2>
            <p class="mt-3 text-sm leading-7 text-slate-500">
              برای آشنایی با هر روش، اطلاعات مربوط به آن را انتخاب کنید.
            </p>
          </div>

          <div
            class="rounded-2xl border border-emerald-100 bg-white px-5 py-3 text-sm font-bold text-emerald-800 shadow-sm"
          >
            {{ procedures.length }} مورد ثبت‌شده
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="!procedures.length"
          class="rounded-3xl border border-dashed border-emerald-200 bg-white p-12 text-center"
        >
          <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-3xl"
          >
            ✚
          </div>
          <h3 class="mt-5 text-xl font-black text-slate-800">
            در حال حاضر جراحی‌ای ثبت نشده است
          </h3>
          <p class="mt-3 text-sm text-slate-500">
            اطلاعات جراحی‌ها پس از ثبت در سامانه در این بخش نمایش داده می‌شوند.
          </p>
        </div>

        <!-- Category groups -->
        <div v-else class="space-y-16">
          <section v-for="group in groupedProcedures" :key="group.key">
            <div class="mb-7 flex items-start gap-4">
              <div class="mt-1 h-12 w-1.5 shrink-0 rounded-full bg-emerald-700"></div>

              <div>
                <h3 class="text-2xl font-black text-slate-900">
                  {{ group.title }}
                </h3>
                <p class="mt-2 text-sm leading-7 text-slate-500">
                  {{ group.description }}
                </p>
              </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              <article
                v-for="(procedure, index) in group.items"
                :key="procedure.id"
                class="group relative flex flex-col overflow-hidden rounded-[1.8rem] border border-emerald-900/5 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-2 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-900/10"
              >
                <div class="mb-6 flex items-center justify-between">
                  <span
                    class="text-4xl font-black text-emerald-800 group-hover:text-emerald-600 transition-colors duration-300"
                  >
                    {{ formatNumber(index) }}
                  </span>

                  <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800 transition duration-300 group-hover:bg-emerald-800 group-hover:text-white"
                  >
                    <span class="text-2xl" aria-hidden="true">✚</span>
                  </div>
                </div>

                <span
                  class="mb-3 w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800"
                >
                  {{ categoryLabels[procedure.category] || "جراحی" }}
                </span>

                <h4
                  class="text-xl font-black leading-9 text-slate-900 transition group-hover:text-emerald-800"
                >
                  {{ procedure.title }}
                </h4>

                <p class="mt-3 flex-1 text-sm leading-8 text-slate-500">
                  {{ getProcedureDescription(procedure) }}
                </p>

                <div class="mt-7 border-t border-slate-100 pt-5">
                  <Link
                    :href="bookingUrl"
                    class="flex items-center justify-between rounded-xl bg-[#f3f7f2] px-4 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-800 hover:text-white"
                  >
                    دریافت نوبت مشاوره
                    <span class="text-lg">←</span>
                  </Link>
                </div>
              </article>
            </div>
          </section>
        </div>
      </section>
    </main>
  </PublicPageLayout>
</template>
