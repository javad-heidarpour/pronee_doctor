<script setup>
import { ref, watch, onUnmounted } from "vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const drawerOpen = ref(false);

const menuItems = [
  { title: "خانه", href: "home1", currentDoctor: true },
  { title: "دریافت نوبت", href: "booking" },
  { title: "تماس با ما", href: "contact" },
  { title: "ساعت کاری", href: "WorkingHours" },
  { title: "درباره پزشک", href: "About" },
  { title: "خدمات قابل ارائه در مطب", href: "services" },
  { title: "عمل‌های جراحی", href: "Surgeries" },
  { title: "دانستنی‌های قبل و بعد از عمل", href: "tips" },
  { title: "نظرات بیماران", href: "Reviews" },
  { title: "گالری عکس", href: "galleries" },
];

let previousBodyOverflow = "";

function closeDrawer() {
  drawerOpen.value = false;
}

function toggleDrawer() {
  drawerOpen.value = !drawerOpen.value;
}

function handleKeydown(event) {
  if (event.key === "Escape") {
    closeDrawer();
  }
}

watch(drawerOpen, (isOpen) => {
  if (isOpen) {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", handleKeydown);
  } else {
    document.body.style.overflow = previousBodyOverflow;
    document.removeEventListener("keydown", handleKeydown);
  }
});

onUnmounted(() => {
  document.body.style.overflow = previousBodyOverflow;
  document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
  <Teleport to="body">
    <div class="site-drawer" dir="rtl">
      <!-- Hamburger -->
      <button
        type="button"
        class="hamburger"
        aria-label="باز کردن منوی سایت"
        :aria-expanded="drawerOpen"
        aria-controls="site-drawer-panel"
        @click="toggleDrawer"
      >
        <span :class="{ 'hamburger__line--first': drawerOpen }"></span>
        <span :class="{ 'hamburger__line--second': drawerOpen }"></span>
        <span :class="{ 'hamburger__line--third': drawerOpen }"></span>
      </button>

      <!-- Overlay -->
      <Transition name="drawer-fade">
        <div v-if="drawerOpen" class="drawer-overlay" @click="closeDrawer"></div>
      </Transition>

      <!-- Drawer -->
      <Transition name="drawer-slide">
        <aside
          v-if="drawerOpen"
          id="site-drawer-panel"
          class="drawer"
          aria-label="منوی سایت"
        >
          <div class="drawer__header">
            <div class="drawer__doctor">
              <strong>
                {{ doctor.caption || "" }}
                {{ doctor.name || "" }}
                {{ doctor.family || "" }}
              </strong>

              <span>
                {{ doctor.specialty_title || "متخصص" }}
              </span>
            </div>

            <button
              type="button"
              class="drawer__close"
              aria-label="بستن منو"
              @click="closeDrawer"
            >
              ×
            </button>
          </div>

          <nav class="drawer__nav">
            <a
              v-for="item in menuItems"
              :key="item.href"
              :href="route(item.href, item.currentDoctor ? { doctor: props.doctor.slug } : { slug: props.doctor.slug })"
              @click="closeDrawer"
            >
              <span>{{ item.title }}</span>
              <span class="drawer__arrow">‹</span>
            </a>
          </nav>

          <div class="drawer__footer">همراه شما در مسیر سلامتی</div>
        </aside>
      </Transition>
    </div>
  </Teleport>
</template>

<style scoped>
.site-drawer {
  direction: rtl;
}

/* HAMBURGER */
.hamburger {
  position: fixed;
  top: 22px;
  left: 24px;
  z-index: 10002;

  width: 46px;
  height: 46px;

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 6px;

  border: 1px solid rgba(255, 255, 255, 0.75);
  border-radius: 13px;

  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 5px 18px rgba(16, 60, 90, 0.15);

  cursor: pointer;
  transition: 0.25s;
}

.hamburger:hover {
  background: #fff;
  transform: translateY(-2px);
}

.hamburger span {
  width: 21px;
  height: 2px;
  border-radius: 5px;
  background: #0b5d3f;
  transition: 0.25s;
}

.hamburger__line--first {
  transform: translateY(8px) rotate(45deg);
}

.hamburger__line--second {
  opacity: 0;
}

.hamburger__line--third {
  transform: translateY(-8px) rotate(-45deg);
}

/* OVERLAY */
.drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;

  background: rgba(13, 39, 31, 0.48);
  backdrop-filter: blur(3px);
}

/* DRAWER */
.drawer {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 10001;

  width: min(340px, 88vw);
  padding: 25px;

  display: flex;
  flex-direction: column;

  background: #fff;
  box-shadow: 10px 0 40px rgba(0, 0, 0, 0.14);

  overflow-y: auto;
}

.drawer__header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding-bottom: 22px;
  border-bottom: 1px solid #e8efeb;
}

.drawer__doctor {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.drawer__doctor strong {
  font-size: 16px;
  color: #0b5d3f;
}

.drawer__doctor span {
  font-size: 12px;
  color: #0e7a4f;
}

.drawer__close {
  width: 38px;
  height: 38px;

  border: 0;
  border-radius: 12px;

  background: #e4f2e9;
  color: #0b5d3f;

  font-size: 26px;
  cursor: pointer;
}

.drawer__nav {
  display: flex;
  flex-direction: column;
  gap: 7px;
  padding-top: 22px;
}

.drawer__nav a {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 14px 12px;
  border-radius: 12px;

  color: #1f2d3a;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;

  transition: 0.2s;
}

.drawer__nav a:hover {
  color: #0b5d3f;
  background: #eaf5ee;
}

.drawer__arrow {
  font-size: 22px;
  color: #1fa463;
}

.drawer__footer {
  margin-top: auto;
  padding-top: 18px;

  border-top: 1px solid #e8efeb;

  text-align: center;
  color: #0e7a4f;
  font-size: 12px;
}

/* ANIMATIONS */
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.25s ease;
}

.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}

.drawer-slide-enter-active,
.drawer-slide-leave-active {
  transition: transform 0.3s ease;
}

.drawer-slide-enter-from,
.drawer-slide-leave-to {
  transform: translateX(-100%);
}

/* RESPONSIVE */
@media (max-width: 767px) {
  .hamburger {
    top: 16px;
    left: 17px;
    width: 43px;
    height: 43px;
  }
}

@media (max-width: 380px) {
  .hamburger {
    top: 13px;
    left: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hamburger,
  .hamburger span,
  .drawer-fade-enter-active,
  .drawer-fade-leave-active,
  .drawer-slide-enter-active,
  .drawer-slide-leave-active {
    transition: none !important;
  }
}
</style>
