<template>
  <section class="info">
    <img class="info__plant" :src="plantImg" alt="" aria-hidden="true" />

    <!-- <ul class="features">
      <li v-for="f in features" :key="f.id" class="feature">
        <svg class="feature__icon" viewBox="0 0 24 24" fill="currentColor">
          <template v-if="f.icon === 'shield'">
            <path d="M12 2l8 3v6c0 5-3.4 9.3-8 11-4.6-1.7-8-6-8-11V5z" />
            <path
              d="M8.3 12.2l2.7 2.7 4.7-5"
              fill="none"
              stroke="#fff"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </template>

          <template v-else-if="f.icon === 'people'">
            <circle cx="12" cy="7" r="3.2" />
            <circle cx="5" cy="9" r="2.4" />
            <circle cx="19" cy="9" r="2.4" />
            <path d="M6.5 20v-2.5A4.5 4.5 0 0 1 11 13h2a4.5 4.5 0 0 1 4.5 4.5V20z" />
            <path
              d="M1.5 17.5c0-2 1.4-3.5 3.3-3.5 .8 0 1.4.2 2 .6-.9.9-1.4 2-1.4 3.4V19H1.5zM22.5 17.5c0-2-1.4-3.5-3.3-3.5-.8 0-1.4.2-2 .6.9.9 1.4 2 1.4 3.4V19h3.9z"
            />
          </template>

          <template v-else-if="f.icon === 'monitor'">
            <rect x="2.5" y="3.5" width="19" height="13" rx="2.2" />
            <path
              d="M9 21h6M12 16.5V21"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <rect
              x="5"
              y="6"
              width="14"
              height="8"
              rx="1"
              fill="#fff"
              fill-opacity=".9"
            />
          </template>

          <template v-else>
            <path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7z" />
            <circle cx="12" cy="9" r="2.6" fill="#fff" />
          </template>
        </svg>

        <h3 class="feature__title">
          {{ f.title }}
        </h3>

        <p class="feature__desc">
          {{ f.desc }}
        </p>
      </li>
    </ul> -->
    <PorneexWidget />
  </section>
</template>
<script setup>
import PorneexWidget from "@/Components/PorneexWidget.vue";
defineProps({
  plantImg: {
    type: String,
    required: true,
  },
});
const features = [
  {
    id: 1,
    title: "تخصص و تجربه بالا",
    desc: "ارائه خدمات تخصصی پزشکی",
    icon: "shield",
  },
  {
    id: 2,
    title: "رضایت بیماران",
    desc: "توجه به آسایش و اعتماد بیمار",
    icon: "people",
  },
  {
    id: 3,
    title: "تجهیزات پیشرفته",
    desc: "استفاده از روش‌های نوین درمانی",
    icon: "monitor",
  },
  {
    id: 4,
    title: "مطب مجهز و دسترس‌پذیر",
    desc: "امکان دریافت نوبت آنلاین",
    icon: "pin",
  },
];
</script>
<style scoped>
/* FEATURES */

.features {
  list-style: none;

  margin: 0 auto;
  padding: 24px 12px;

  max-width: 1000px;

  display: grid;
  grid-template-columns: repeat(4, 1fr);

  background: var(--card);
  border-radius: 26px;

  box-shadow: 0 10px 40px rgba(16, 60, 90, 0.1);

  position: relative;
  z-index: 1;
}

.feature {
  text-align: center;
  padding: 6px 14px;

  border-inline-start: 1px solid #e6eef0;
}

.feature:first-child {
  border-inline-start: 0;
}

.feature__icon {
  width: 38px;
  height: 38px;

  color: var(--blue-900);
  margin-bottom: 6px;
}

.feature__title {
  margin: 0 0 6px;

  font-size: 14.5px;
  font-weight: 800;

  color: var(--blue-900);
}

.feature__desc {
  margin: 0;

  font-size: 12px;
  color: var(--muted);
  line-height: 1.8;
}

/* TABLET */

@media (min-width: 768px) and (max-width: 1050px) {
  .menu-column {
    flex-basis: 280px;
    margin-inline-end: 25px;
  }

  .hero__inner {
    gap: 10px;
    padding-top: 105px;
  }

  .menu__item {
    padding: 12px 14px;
    font-size: 14px;
  }

  .doctor-heading__name {
    font-size: 25px;
  }
}

/* INFO */

.info {
  position: relative;

  padding: 8px 20px 70px;

  background: radial-gradient(
      520px 300px at 8% 90%,
      rgba(120, 200, 150, 0.28),
      transparent 70%
    ),
    linear-gradient(180deg, #f1f8f3 0%, #e4f2e9 100%);
}

.info__plant {
  position: absolute;
  left: 0;
  bottom: 0;

  width: 120px;

  pointer-events: none;
  mix-blend-mode: multiply;

  -webkit-mask-image: radial-gradient(
    ellipse 62% 62% at 18% 82%,
    #000 40%,
    transparent 100%
  );

  mask-image: radial-gradient(ellipse 62% 62% at 18% 82%, #000 40%, transparent 100%);
}
</style>
