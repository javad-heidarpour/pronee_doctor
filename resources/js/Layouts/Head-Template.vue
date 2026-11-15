<template>
  <Head>
    <title>{{ pageTitle }}</title>

    <meta head-key="description" name="description" :content="pageDescription" />

    <meta
      head-key="robots"
      name="robots"
      content="index, follow, max-image-preview:large"
    />

    <meta head-key="og-title" property="og:title" :content="pageTitle" />

    <meta
      head-key="og-description"
      property="og:description"
      :content="pageDescription"
    />

    <meta head-key="og:type" property="og:type" content="website" />

    <meta head-key="twitter-card" name="twitter:card" content="summary" />
  </Head>
</template>

<script setup>
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
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
</script>
