<script setup>
import teamImg from "../../../assets/team.png";
import plantImg from "../../../assets/plant.jpg";

import SiteDrawer from "./SiteDrawer.vue";
import Features from "./Features.vue";
import MainMenu from "./MainMenu.vue";

defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="page" dir="rtl">
    <section class="hero">
      <!-- Background -->
      <div
        class="hero__bg"
        :style="{ backgroundImage: `url(${teamImg})` }"
        aria-hidden="true"
      ></div>

      <!-- Team Image -->
      <div class="team" aria-hidden="true">
        <img :src="teamImg" class="team__img" alt="" />
      </div>

      <!-- Hamburger -->
      <SiteDrawer :doctor="doctor" />

      <!-- Doctor Heading -->
      <div class="doctor-heading">
        <!-- Snow Background -->
        <div class="doctor-heading__snow"></div>

        <h1 class="doctor-heading__name">
          {{ doctor.caption || "" }}
          {{ doctor.name || "" }}
          {{ doctor.family || "" }}
        </h1>

        <div class="doctor-heading__specialty">
          <span class="doctor-heading__line"></span>

          <span>
            {{ doctor.specialty_title || "متخصص" }}
          </span>

          <span class="doctor-heading__line"></span>
        </div>
      </div>

      <!-- Main Content -->
      <div class="hero__inner">
        <MainMenu :doctor="doctor" />
      </div>

      <!-- Wave -->
      <svg
        class="hero__wave"
        viewBox="0 0 1440 170"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <defs>
          <linearGradient id="waveblue" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#0b5d3f" />
            <stop offset=".55" stop-color="#2fae6f" />
            <stop offset="1" stop-color="#bfe8cf" />
          </linearGradient>
        </defs>

        <path
          d="M0 70 C 260 150, 520 120, 800 95 S 1250 40, 1440 70 L1440 170 L0 170 Z"
          fill="url(#waveblue)"
          opacity=".9"
        />

        <path
          d="M0 100 C 300 170, 600 140, 900 118 S 1300 80, 1440 100 L1440 170 L0 170 Z"
          fill="#f1f8f3"
        />
      </svg>
    </section>

    <!-- FEATURES -->
    <Features :plantImg="plantImg" />
  </div>
</template>

<style scoped>
/* =========================================
   PAGE
========================================= */

.page {
  --blue-900: #0b5d3f;
  --blue-700: #0e7a4f;
  --blue-500: #1fa463;

  --ink: #1f2d3a;
  --muted: #6b7a88;
  --card: #ffffff;
  --bg-soft: #eef7f1;

  font-family: "Vazirmatn", "IRANSans", Tahoma, sans-serif;
  color: var(--ink);

  background: linear-gradient(180deg, #fff 0%, var(--bg-soft) 60%, #e3f2e8 100%);

  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

.page * {
  box-sizing: border-box;
}

/* =========================================
   HERO
========================================= */

.hero {
  position: relative;
  isolation: isolate;
  overflow: hidden;

  background: #074881;

  height: 100vh;
  min-height: 650px;
  padding: 0;
}

/* =========================================
   BACKGROUND
========================================= */

.hero__bg {
  position: absolute;
  top: 0;
  left: 0;

  width: 100%;
  height: 100vh;

  z-index: -2;

  background-position: center center;
  background-size: cover;
  background-repeat: no-repeat;

  filter: blur(10px) saturate(1.1);

  pointer-events: none;
}

.hero::before {
  content: "";

  position: absolute;
  inset: 0;

  z-index: -1;

  background: linear-gradient(180deg, rgba(10, 60, 110, 0.12), rgba(10, 60, 110, 0.3));
}

/* =========================================
   TEAM IMAGE
========================================= */

.team {
  position: absolute;
  top: 0;
  left: 0;

  width: 75%;
  height: 100vh;

  overflow: hidden;

  z-index: 1;
  pointer-events: none;
}

.team__img {
  position: absolute;
  inset: 0;

  display: block;

  width: 100%;
  height: 100%;

  object-fit: cover;
  object-position: center top;

  /*
    تصویر در لبه راست و پایین
    به تدریج محو می‌شود.
  */

  -webkit-mask-image: linear-gradient(
      to right,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    ),
    linear-gradient(
      to bottom,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    );

  -webkit-mask-composite: source-in;

  mask-image: linear-gradient(
      to right,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    ),
    linear-gradient(
      to bottom,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    );

  mask-composite: intersect;
}

/* =========================================
   DOCTOR HEADING
========================================= */

.doctor-heading {
  position: absolute;
  top: 24px;
  left: 50%;

  transform: translateX(-50%);

  z-index: 5;

  width: min(80%, 500px);

  align-items: center;
  text-align: center;

  isolation: isolate;
}

/* Snow Background */

.doctor-heading__snow {
  position: absolute;

  z-index: -1;

  left: -2px;
  right: -2px;

  top: -5px;
  bottom: -15px;

  background: radial-gradient(
    ellipse at 50% 55%,
    rgba(255, 255, 255, 0.98) 0%,
    rgba(255, 255, 255, 0.88) 35%,
    rgba(255, 255, 255, 0.58) 62%,
    rgba(255, 255, 255, 0) 100%
  );

  filter: blur(25px);

  pointer-events: none;
}

/* Doctor Name */

.doctor-heading__name {
  position: relative;

  margin: 0;

  color: #0b5d3f;

  font-size: clamp(23px, 2.2vw, 31px);
  font-weight: 900;
  line-height: 1.7;

  text-shadow: 0 1px 3px rgba(255, 255, 255, 0.95);
}

/* Specialty */

.doctor-heading__specialty {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 12px;
  margin-top: 0;

  color: #0e7a4f;

  font-size: 15px;
  font-weight: 700;
}

.doctor-heading__line {
  width: 30px;
  height: 1px;

  background: #1fa463;
  opacity: 0.65;
}

/* =========================================
   MAIN CONTENT
========================================= */

.hero__inner {
  position: relative;

  z-index: 10;

  display: flex;
  flex-direction: row;

  align-items: flex-start;
  justify-content: space-between;

  gap: 16px;

  padding: 120px 35px 0;

  min-height: 0;
}

.hero__inner > :first-child {
  position: relative;
  z-index: 20;
}

/* =========================================
   HERO WAVE
========================================= */

.hero__wave {
  position: absolute;

  left: 0;
  bottom: 0;

  width: 100%;
  height: 130px;

  display: block;

  z-index: 5;
  pointer-events: none;
}

/* =========================================
   LARGE DESKTOP
========================================= */

@media (min-width: 1201px) {
  .hero__inner {
    padding-top: 140px;
    padding-right: 45px;
    padding-left: 35px;
  }

  .hero__inner > :first-child {
    margin-top: 20px;
  }

  .team {
    width: 75%;
    height: 100vh;
  }

  /*
    محوشدن بیشتر در نمایشگر بزرگ
    تا خط مرزی تصویر دیده نشود.
  */

  .team__img {
    -webkit-mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      );

    -webkit-mask-composite: source-in;

    mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      );

    mask-composite: intersect;
  }

  .hero__wave {
    height: 160px;
    bottom: 0;
    top: auto;
  }
}

/* =========================================
   TABLET / MEDIUM
========================================= */

@media (min-width: 768px) and (max-width: 1200px) {
  .hero {
    height: 100vh;
    min-height: 650px;
    padding: 0;
  }

  .doctor-heading {
    top: 15px;
    width: 100%;
  }

  .doctor-heading__name {
    font-size: 25px;
  }

  .hero__inner {
    padding: 110px 25px 0;

    align-items: flex-start;
    gap: 15px;
  }

  .team {
    width: 75%;
    height: 100vh;
  }

  .team__img {
    object-position: center top;

    -webkit-mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      );

    -webkit-mask-composite: source-in;

    mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      );

    mask-composite: intersect;
  }

  .hero__wave {
    top: auto;
    bottom: 0;
    height: 100px;
  }
}

/* =========================================
   MOBILE
========================================= */

@media (max-width: 767px) {
  .hero {
    height: auto;
    min-height: 650px;
    padding: 0;
  }

  .hero__bg {
    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    background-position: center;
    background-size: cover;

    filter: blur(2px) saturate(1.1);
    transform: scale(1.04);
  }

  .hero::before {
    background: linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.15),
      rgba(10, 60, 50, 0.15)
    );
  }

  .doctor-heading {
    top: 24px;
    width: calc(100% - 110px);
  }

  .doctor-heading__name {
    font-size: 25px;
    color: #0b5d3f;

    text-shadow: 0 2px 12px rgba(255, 255, 255, 0.85);
  }

  .doctor-heading__specialty {
    font-size: 14px;
  }

  .doctor-heading__snow {
    left: -20px;
    right: -20px;

    top: -10px;
    bottom: -18px;

    filter: blur(10px);
  }

  .hero__inner {
    display: block;

    padding: 100px 18px 100px;

    min-height: 0;
  }

  .team {
    display: none;
  }

  .hero__wave {
    top: auto;
    bottom: 0;

    height: 70px;
  }
}

/* =========================================
   SMALL MOBILE
========================================= */

/* =========================================
   SMALL MOBILE - iPhone SE
========================================= */

@media (max-width: 380px) {
  .hero__inner {
    padding-inline: 13px;
    padding-top: 200px;
    padding-bottom: 100px;
  }

  .doctor-heading {
    top: 25px;
    width: calc(100% - 70px);
  }

  .doctor-heading__name {
    font-size: 21px;
    line-height: 1.6;
  }

  .doctor-heading__specialty {
    font-size: 13px;
    margin-top: 2px;
  }

  .doctor-heading__snow {
    left: -15px;
    right: -15px;
    top: -8px;
    bottom: -12px;
  }
}
</style>
