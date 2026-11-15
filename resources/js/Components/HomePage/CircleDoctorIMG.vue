<template>
  <!-- Floating Doctor Image -->
  <Transition name="doctor-float">
    <div class="fixed z-[100] doctor-floating-image">
      <div
        class="relative w-[60px] h-[60px] rounded-full overflow-hidden border-2 border-white shadow-xl"
      >
        <img
          :src="doctor.photo"
          :alt="`عکس ${doctor.caption} ${doctor.name} ${doctor.family}`"
          class="w-full h-full object-cover"
          loading="eager"
        />
      </div>

      <div
        class="absolute bottom-1 right-2 w-3 h-3 bg-green-400 border-2 border-white rounded-full shadow-sm"
      ></div>
    </div>
  </Transition>
</template>

<script setup>
const props = defineProps({
  doctor: {
    type: Object,
    required: true,
    default: () => ({}),
  },
  ImgPath: String,
});
</script>
