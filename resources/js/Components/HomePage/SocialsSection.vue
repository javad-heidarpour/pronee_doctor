<template>
  <div class="flex flex-wrap gap-2">
    <a
      v-for="item in doctor.socials"
      :key="item.id"
      :href="item.platform.base_url + item.username"
      target="_blank"
      :title="item.platform.title"
      class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white transition-all duration-200 hover:-translate-y-1 hover:border-primary hover:shadow-lg"
    >
      <img
        v-if="isSvg(iconMap[item.platform.name])"
        :src="iconMap[item.platform.name]"
        class="h-5 w-5 object-contain"
      />

      <component
        v-else
        :is="iconMap[item.platform.name] || Globe"
        class="h-5 w-5 text-gray-700 transition group-hover:text-primary"
      />
    </a>
  </div>
</template>

<script setup>
import { computed } from "vue";
import {
  Send,
  Instagram,
  Linkedin,
  Globe,
  MessageCircle,
} from "lucide-vue-next";

import Bale from "@/assets/socials/Bale.svg";
import Eitta from "@/assets/socials/eitta.svg";
import roubika from "@/assets/socials/roubika.svg";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const iconMap = {
  telegram: Send,
  instagram: Instagram,
  linkedin: Linkedin,
  x: Globe,

  bale: Bale,
  eitta: Eitta,
  roubika: roubika,

  gap: MessageCircle,
  igap: MessageCircle,
  virasty: MessageCircle,
};

const isSvg = (icon) => typeof icon === "string";
</script>