<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const servicesOpen = ref(false);
const bookingOpen = ref(false);

const activeItem = ref("booking");

const subItems = [
  {
    title: "خدمات قابل ارائه در مطب",
    href: "services",
  },
  {
    title: "عمل‌های جراحی",
    href: "Surgeries",
  },
];

function select(id) {
  activeItem.value = id;
}
</script>
<template>
  <div class="menu-column">
    <nav class="menu" aria-label="منوی اصلی">
      <!-- Booking -->
      <div class="menu__group">
        <Link
          :href="route('booking', { slug: props.doctor.slug })"
          class="menu__item menu__item--primary"
          :aria-expanded="bookingOpen"
        >
          <span class="chev" :class="{ 'chev--down': bookingOpen }">
            <svg viewBox="0 0 24 24">
              <path d="M15 6l-6 6 6 6" />
            </svg>
          </span>

          <span class="menu__label">دریافت نوبت</span>

          <svg class="menu__icon" viewBox="0 0 24 24">
            <rect x="3" y="4.5" width="18" height="16" rx="3" fill="currentColor" />
            <path
              d="M8 2.5v4M16 2.5v4"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <g fill="#fff">
              <rect x="6.5" y="10" width="2.6" height="2.4" rx=".5" />
              <rect x="10.7" y="10" width="2.6" height="2.4" rx=".5" />
              <rect x="14.9" y="10" width="2.6" height="2.4" rx=".5" />
              <rect x="6.5" y="14.2" width="2.6" height="2.4" rx=".5" />
              <rect x="10.7" y="14.2" width="2.6" height="2.4" rx=".5" />
            </g>
          </svg>
        </Link>
      </div>

      <!-- Services -->
      <div class="menu__group">
        <a
          class="menu__item"
          :aria-expanded="servicesOpen"
          @click="servicesOpen = !servicesOpen"
        >
          <span class="chev" :class="{ 'chev--down': servicesOpen }">
            <svg viewBox="0 0 24 24">
              <path d="M15 6l-6 6 6 6" />
            </svg>
          </span>

          <span class="menu__label">خدمات ما</span>

          <svg
            class="menu__icon menu__icon--blue"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M6 3v6a4 4 0 0 0 8 0V3" />
            <path d="M10 13v2a5 5 0 0 0 10 0v-2" />
            <circle cx="20" cy="10.5" r="2.3" />
          </svg>
        </a>

        <transition name="slide">
          <ul v-show="servicesOpen" class="submenu">
            <li v-for="(item, index) in subItems" :key="index">
              <Link :href="route(item.href, { slug: props.doctor.slug })" class="submenu__link">
                <span class="submenu__text">
                  {{ item.title }}
                </span>

                <span class="plus">
                  <svg viewBox="0 0 24 24">
                    <path d="M12 6v12M6 12h12" />
                  </svg>
                </span>
              </Link>
            </li>
          </ul>
        </transition>
      </div>

      <!-- Contact -->
      <Link
          :href="route('contact', { slug: props.doctor.slug })"
        class="menu__item"
        :class="{ 'is-active': activeItem === 'contact' }"
        @click="select('contact')"
      >
        <span class="chev">
          <svg viewBox="0 0 24 24">
            <path d="M15 6l-6 6 6 6" />
          </svg>
        </span>

        <span class="menu__label">تماس با ما</span>

        <svg class="menu__icon menu__icon--blue" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.6a1 1 0 0 1-.25 1z"
          />
        </svg>
      </Link>

      <!-- Tips -->
       <Link
          :href="route('tips', { slug: props.doctor.slug })"
       
        class="menu__item menu__item--two"
        :class="{ 'is-active': activeItem === 'tips' }"
        @click="select('tips')"
      >
        <span class="menu__label">
          دانستنی‌های قبل<br />
          و بعد از عمل
        </span>

        <svg
          class="menu__icon menu__icon--blue"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M9 18h6M10 21h4" />
          <path
            d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"
            fill="currentColor"
            fill-opacity=".15"
          />
          <path d="M2 8h2M20 8h2M4.2 2.8l1.4 1.4M19.8 2.8l-1.4 1.4" />
        </svg>
      </Link>

      <!-- Gallery -->
       <Link
          :href="route('galleries', { slug: props.doctor.slug })"
        class="menu__item"
        :class="{ 'is-active': activeItem === 'gallery' }"
        @click="select('gallery')"
      >
        <span class="chev">
          <svg viewBox="0 0 24 24">
            <path d="M15 6l-6 6 6 6" />
          </svg>
        </span>

        <span class="menu__label">گالری عکس</span>

        <svg
          class="menu__icon menu__icon--blue"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linejoin="round"
          stroke-linecap="round"
        >
          <rect x="3" y="4" width="18" height="16" rx="3" />
          <circle cx="9" cy="10" r="1.8" fill="currentColor" />
          <path d="M4 18l5-5 4 4 3-3 4 4" />
        </svg>
      </Link>
    </nav>
  </div>
</template>

<style scoped>
.menu-column {
  margin-top: 0px;
  order: 1;
  flex: 0 0 300px;
  /* margin-inline-end: clamp(16px, 5vw, 80px); */
  padding-bottom: 45px;
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 2;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: -10px;
}

.menu__item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 14px 18px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 16px;

  /* حالت شیشه‌ای عادی */
  background: rgba(255, 255, 255, 0.38);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);

  color: #1f2d3a;
  font: inherit;
  font-weight: 700;
  font-size: 15px;
  text-decoration: none;
  cursor: pointer;

  box-shadow: 0 6px 20px rgba(16, 60, 90, 0.12);

  transition: background 0.25s ease, backdrop-filter 0.25s ease, transform 0.2s ease,
    box-shadow 0.25s ease;
}

.menu__item:hover {
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(0px);
  -webkit-backdrop-filter: blur(0px);

  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(16, 60, 90, 0.2);
}

.menu__item:focus-visible,
.submenu__link:focus-visible {
  outline: 3px solid #1fa463;
  outline-offset: 2px;
}

.menu__label {
  flex: 1;
  text-align: right;
  line-height: 1.7;
}

.menu__icon {
  width: 30px;
  height: 30px;
  flex: none;
  color: #0b5d3f;
}

.menu__icon--blue {
  color: #0e7a4f;
}

.menu__item--primary,
.menu__item--primary.is-active {
  background: rgba(11, 100, 66, 0.55);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.35);
}
.menu__item--primary:hover,
.menu__item--primary.is-active:hover {
  background: #0e7a4f;
  color: #fff;
}
.menu__item--primary .menu__icon {
  color: #fff;
}

.menu__item.is-active:not(.menu__item--primary) {
  background: #e1f3e8;
  color: #0b5d3f;
}

.menu__item--two {
  font-size: 13.5px;
}

.menu__item--two .menu__label {
  padding-inline-start: 22px;
}

.chev {
  width: 22px;
  height: 22px;
  flex: none;
  display: grid;
  place-items: center;
  transition: transform 0.25s;
}

.chev svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.chev--down {
  transform: rotate(-90deg);
}

.submenu {
  list-style: none;
  /* margin: 6px 14px 0; */
  padding: 8px 14px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 6px 18px rgba(16, 60, 90, 0.12);
  overflow: hidden;
}

.submenu__link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  color: #1f2d3a;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.submenu__text {
  flex: 1;
  text-align: right;
}

.plus {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #dff3e7;
  color: #1fa463;
  display: grid;
  place-items: center;
  flex: none;
}

.plus svg {
  width: 14px;
  height: 14px;
  fill: none;
  stroke: currentColor;
  stroke-width: 3;
  stroke-linecap: round;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
  max-height: 140px;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
  padding-block: 0;
  /* margin-top: 0; */
}

/* MOBILE */
@media (max-width: 767px) {
  .menu-column {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 380px;
    margin: 0 auto;
    padding: 0;
    transform: translateY(15px);
  }

  .menu {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .menu__item {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.7);
  }

  .menu__item--primary,
  .menu__item--primary.is-active {
    background: rgba(11, 100, 66, 0.88);
    color: #fff;
  }

  .submenu {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(8px);
  }
}

/* SMALL MOBILE */
@media (max-width: 380px) {
  .menu__item {
    padding: 12px 13px;
    font-size: 13px;
  }

  .menu__icon {
    width: 27px;
    height: 27px;
  }

  .menu-column {
    transform: translateY(15px);
  }
}

/* MEDIUM SCREEN */
@media (min-width: 768px) and (max-width: 1200px) {
  .menu-column {
    flex: 0 0 270px;
    /* margin-inline-start: 15px; */
    margin-inline-end: 15px;
    padding-bottom: 65px;
  }

  .menu__item {
    padding: 12px 14px;
    font-size: 14px;
  }

  .menu__icon {
    width: 27px;
    height: 27px;
  }
}
</style>
