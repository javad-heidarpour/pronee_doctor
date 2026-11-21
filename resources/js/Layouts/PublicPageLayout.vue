<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import LocaleSwitcher from "@/Components/LocaleSwitcher.vue";
import HeadTemplate from "./Head-Template.vue";
import PublicBottomBooking from "./PublicBottomBooking.vue";
import teamImg from "../assets/team.png";
import SiteDrawer from "@/Pages/HomePage/Home1/SiteDrawer.vue";

const page = usePage();
const localeDirection = computed(() => page.props.direction || "rtl");

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },

  title: {
    type: String,
    default: "خدمات پزشکی",
  },

  subtitle: {
    type: String,
    default: "ارائه خدمات تخصصی پزشکی با بالاترین کیفیت",
  },

  eyebrow: {
    type: String,
    default: "خدمات و اطلاعات پزشکی",
  },

  activeItem: {
    type: String,
    default: "",
  },

  showBooking: {
    type: Boolean,
    default: true,
  },
});
const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = route("home1", { doctor: props.doctor.slug });
  }
};
</script>

<template>
  <HeadTemplate
    :doctor="doctor"
    :title="title"
    :subtitle="subtitle"
    :eyebrow="eyebrow"
    :active-item="activeItem"
  />

  <div
    :dir="localeDirection"
    class="relative min-h-screen w-full overflow-x-clip bg-[#f1f8f3] font-[Vazirmatn,IRANSans,Tahoma,sans-serif] text-[#1d342b]"
  >
    <!-- ================= HEADER ================= -->

    <header
      class="relative z-[100] isolate min-h-[490px] overflow-visible border-0 shadow-none max-[767px]:min-h-[430px]"
    >
      <!-- Background Image -->
      <div
        class="pointer-events-none absolute inset-0 z-0 bg-cover bg-[position:center_35%] bg-no-repeat"
        :style="{ backgroundImage: `url(${teamImg})` }"
      ></div>

      <!-- Image Overlay -->
      <div
        class="pointer-events-none absolute inset-0 z-[1] bg-[linear-gradient(110deg,rgba(235,248,239,0.25),rgba(255,255,255,0.03)_48%,rgba(224,242,230,0.25))]"
      ></div>

      <!-- Hamburger Menu -->
      <SiteDrawer
        :doctor="doctor"
        class="!fixed !right-3.5 !top-4 min-[381px]:!right-3.5"
      />
      <!-- Back Button -->
      <button
        type="button"
        @click="goBack"
        aria-label="بازگشت به صفحه قبل"
        title="بازگشت"
        class="fixed right-10 top-4 z-[110] flex h-11 w-11 items-center justify-center rounded-full border border-white/80 bg-white/75 text-[#14764e] shadow-[0_4px_18px_rgba(20,100,65,0.13)] backdrop-blur-md transition-all duration-300 hover:-translate-x-1 hover:bg-[#14764e] hover:text-white active:scale-90 max-[380px]:right-3"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M15 18l-6-6 6-6" />
        </svg>
      </button>
      <!-- Reserved Doctor Identity Space -->
      <div
        aria-hidden="true"
        class="invisible pointer-events-none relative z-[3] mx-auto block w-[min(90%,550px)] pt-[27px] text-center md:pt-[22px]"
      ></div>

      <!-- ================= PAGE HEADING ================= -->

      <div
        class="relative z-[3] mx-auto mt-[2px] w-full max-w-[200px] px-0 pt-32 text-center min-[768px]:mt-[2px]"
      >
        <!-- Doctor Identity -->
        <Link
          :href="route('home1', { doctor: doctor.slug })"
          class="relative isolate mx-auto block w-[min(90%,550px)] pt-[27px] text-center max-[767px]:w-[calc(100%-75px)] max-[767px]:pt-5 max-[380px]:w-[calc(100%-65px)]"
        >
          <!-- Doctor Glow -->
          <div
            class="pointer-events-none absolute -inset-x-[25px] inset-y-0 -z-10 bg-[radial-gradient(ellipse,rgba(255,255,255,0.92),rgba(255,255,255,0.18)_55%,transparent_100%)] blur-[18px]"
          ></div>

          <!-- Doctor Name -->
          <div class="relative isolate">
            <h2
              class="whitespace-nowrap relative z-10 m-0 text-[clamp(23px,2.4vw,30px)] font-black leading-[1.7] text-[#0b5d3f] max-[767px]:text-[22px] max-[380px]:mt-[5px] max-[380px]:text-[19px]"
            >
              {{ doctor.caption || "" }}
              {{ doctor.name || "" }}
              {{ doctor.family || "" }}
            </h2>

            <!-- White Glow Under Name -->
            <div
              aria-hidden="true"
              class="pointer-events-none absolute -bottom-[3px] left-1/2 z-0 h-[30px] w-[190%] -translate-x-1/2 bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.95)_0%,rgba(255,255,255,0.75)_30%,rgba(255,255,255,0.15)_58%,rgba(255,255,255,0.08)_78%,transparent_100%)] blur-[2px]"
            ></div>
          </div>

          <!-- Doctor Specialty -->
          <div class="relative isolate mt-[2px]">
            <div
              class="relative z-10 flex w-full min-w-0 items-center justify-center gap-2 whitespace-nowrap text-xs font-bold text-[#0e7650] sm:text-sm"
            >
              <span class="h-px w-14 bg-[#1fa463]/70"></span>
              <span class="whitespace-nowrap">
                {{ doctor.specialty_title || "جراح" }}
              </span>

              <span class="h-px w-14 bg-[#1fa463]/70"></span>
            </div>

            <!-- White Glow Under Specialty -->
            <div
              aria-hidden="true"
              class="pointer-events-none absolute -bottom-[8px] left-1/2 z-0 h-[30px] w-[300%] -translate-x-1/2 bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.95)_0%,rgba(255,255,255,0.75)_30%,rgba(255,255,255,0.15)_58%,rgba(255,255,255,0.08)_78%,transparent_100%)] blur-[2px]"
            ></div>
          </div>
        </Link>

        <!-- Page Eyebrow -->
        <div
          class="whitespace-nowrap mt-3 inline-flex items-center gap-[10px] rounded-full border-2 border-white/90 bg-[rgba(58,159,530,0.86)] px-3 py-1 text-[23px] font-bold text-blue-800 shadow-[0_5px_20px_rgba(11,43,221,0.05)] max-[767px]:text-lg"
        >
          <span
            class="h-[9px] w-[9px] shrink-0 rounded-full bg-white shadow-[0_0_0_3px_rgba(255,255,255,0.25)] animate-pulse"
          ></span>
          <span class="z-1">
            {{ title }}
          </span>
        </div>

        <!-- Subtitle + Snow (currently disabled) -->
        <!--
        <div class="relative isolate">
          <p
            class="mx-auto mt-[5px] max-w-[650px]
                   text-[15px] leading-[2] text-[#65786d]"
          >
            {{ subtitle }}
          </p>

          <div
            aria-hidden="true"
            class="pointer-events-none absolute -bottom-[6px]
                   left-[53%] h-[37px] w-[120%]
                   -translate-x-1/2
                   bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.95),transparent)]
                   blur-[2px]"
          ></div>
        </div>
        -->
      </div>

      <!-- ================= BOTTOM WAVE ================= -->

      <svg
        class="pointer-events-none absolute bottom-[-1px] left-0 z-[4] block h-[95px] w-full max-[767px]:h-[65px] min-[768px]:max-[1200px]:h-[85px]"
        viewBox="0 0 1440 120"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <path
          d="M0 65 C280 125 480 20 760 65 S1200 110 1440 40 L1440 120 L0 120Z"
          fill="#f1f8f3"
        />
      </svg>
    </header>

    <!-- ================= MAIN CONTENT ================= -->

    <main
      class="relative z-[1] w-full min-w-0 bg-[#f1f8f3] px-[25px] pb-[55px] pt-[10px] max-[1200px]:px-5 max-[767px]:px-[14px] max-[767px]:pb-[35px] max-[767px]:pt-[5px]"
    >
      <div class="mx-auto w-full min-w-0 max-w-[1200px]">
        <slot />
      </div>
    </main>

    <!-- ================= BOTTOM BOOKING CTA ================= -->
    <PublicBottomBooking  :doctor="doctor" :showBooking="showBooking" />
  </div>
</template>
