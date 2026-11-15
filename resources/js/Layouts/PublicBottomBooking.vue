<template>
  <!-- CTA -->
  <section class="py-16 px-5">
    <div
      class="max-w-6xl mx-auto relative overflow-hidden rounded-[2rem] bg-[#164e3e] p-8 md:p-14 text-white"
    >
      <div
        class="absolute -left-20 -top-20 w-64 h-64 rounded-full border border-white/10 pointer-events-none"
      ></div>

      <div
        class="absolute -left-10 -top-10 w-44 h-44 rounded-full border border-white/10 pointer-events-none"
      ></div>

      <div class="relative flex flex-col md:flex-row items-center justify-between gap-8">
        <div>
          <span class="text-emerald-200 text-sm font-bold"> YOUR HEALTH MATTERS </span>

          <h2 class="text-2xl md:text-3xl font-black mt-4 leading-relaxed">
            برای سلامت خود یک قدم بردارید
          </h2>

          <p class="text-emerald-100/80 mt-4 leading-8">
            برای دریافت نوبت و مراجعه به مطب اقدام کنید.
          </p>
        </div>

        <Link
          :href="route('booking')"
          class="shrink-0 inline-flex items-center gap-3 bg-white text-emerald-900 px-8 py-4 rounded-2xl font-black hover:bg-emerald-50 transition"
        >
          دریافت نوبت آنلاین
          <span>←</span>
        </Link>

        <SocialsSection :doctor="doctor" />
      </div>
    </div>
        <PorneexWidget />
  </section>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";
import SocialsSection from "@/Components/HomePage/SocialsSection.vue";
import PorneexWidget from "@/Components/PorneexWidget.vue";

const props = defineProps({
  showBooking: {
    type: Boolean,
    default: true,
  },
  doctor: {
    type: Boolean,
    default: true,
  },
});
</script>
<style scoped>
/* =========================================
   BOTTOM CTA
========================================= */

.public-bottom-cta {
  padding: 25px;

  background: #f1f8f3;
}

.public-bottom-cta__inner {
  width: min(100%, 1150px);

  margin: 0 auto;

  padding: 27px 30px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 25px;

  border-radius: 24px;

  background: linear-gradient(120deg, #0b5d3f, #137d51);

  color: white;

  box-shadow: 0 15px 35px rgba(11, 93, 63, 0.14);
}

.public-bottom-cta__badge {
  display: inline-block;

  padding: 5px 12px;

  background: rgba(255, 255, 255, 0.13);

  border-radius: 20px;

  font-size: 11px;
  font-weight: 700;
}

.public-bottom-cta__text h3 {
  margin: 9px 0 4px;

  font-size: 21px;
  font-weight: 900;
}

.public-bottom-cta__text p {
  margin: 0;

  color: rgba(255, 255, 255, 0.75);

  font-size: 12px;
  line-height: 2;
}

.public-bottom-cta__actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.public-bottom-cta__actions a {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  padding: 12px 18px;

  border-radius: 12px;

  background: white;

  color: var(--green-900);

  font-size: 12px;
  font-weight: 900;

  transition: 0.25s ease;
}

.public-bottom-cta__actions a:hover {
  transform: translateY(-3px);

  background: #e8f5ec;
}
</style>
