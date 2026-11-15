<template>
  <PortalLayout :doctor="doctor">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-black text-gray-900">مدیریت نظرات مقالات</h2>

      <div class="text-sm text-gray-500">
        تعداد:
        {{ comments.total }}
      </div>
    </div>

    <!-- Search -->

    <div class="bg-white rounded-2xl shadow-sm border p-5 mb-6">
      <input
        v-model="search"
        @keyup.enter="doSearch"
        type="text"
        placeholder="جستجو در نظرات..."
        class="w-full rounded-xl border-gray-200 px-4 py-3"
      />
    </div>

    <!-- Comments -->

    <div v-if="comments.data.length" class="space-y-6">
      <div
        v-for="comment in comments.data"
        :key="comment.id"
        class="bg-white rounded-3xl shadow-sm border p-6"
      >
        <!-- Header -->

        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-black text-gray-900">
              {{ comment.name }}
            </h3>

            <p class="text-sm text-gray-500 mt-1">
              مقاله:

              {{ comment.post?.title }}
            </p>
          </div>

          <div>
            <span
              v-if="comment.is_approved"
              class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
            >
              تایید شده
            </span>

            <span
              v-else
              class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm"
            >
              در انتظار تایید
            </span>
          </div>
        </div>

        <!-- Comment -->

        <div class="bg-gray-50 rounded-2xl p-4 text-gray-700 leading-8">
          {{ comment.comment }}
        </div>

        <!-- Rating -->

        <div v-if="comment.rating" class="mt-3 text-yellow-500">
          {{ "⭐".repeat(comment.rating) }}
        </div>

        <!-- Actions -->

        <div class="flex flex-wrap gap-3 mt-5">
          <button
            v-if="!comment.is_approved"
            @click="approve(comment.id)"
            class="bg-green-600 text-white px-5 py-2 rounded-xl font-bold"
          >
            تایید نظر
          </button>

          <button
            v-if="comment.is_approved"
            @click="reject(comment.id)"
            class="bg-yellow-500 text-white px-5 py-2 rounded-xl font-bold"
          >
            لغو تایید
          </button>

          <button
            @click="deleteComment(comment.id)"
            class="bg-red-600 text-white px-5 py-2 rounded-xl font-bold"
          >
            حذف
          </button>
        </div>

        <!-- Reply -->

        <div class="mt-6 border-t pt-5">
          <h4 class="font-black mb-3">پاسخ پزشک</h4>

          <textarea
            v-model="replyText[comment.id]"
            rows="3"
            class="w-full rounded-xl border-gray-200 p-4"
            placeholder="پاسخ خود را بنویسید..."
          />

          <button
            @click="reply(comment.id)"
            class="mt-3 bg-teal-600 text-white px-6 py-2 rounded-xl font-bold"
          >
            ارسال پاسخ
          </button>
        </div>

        <!-- Replies -->

        <div v-if="comment.replies?.length" class="mt-6 space-y-3 pr-6">
          <div
            v-for="reply in comment.replies"
            :key="reply.id"
            class="bg-teal-50 rounded-2xl p-4"
          >
            <div class="font-bold text-teal-700 mb-2">
              {{ reply.name }}
            </div>

            <div class="text-gray-700">
              {{ reply.comment }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white rounded-3xl p-10 text-center text-gray-500">
      نظری وجود ندارد.
    </div>

    <!-- Pagination -->

    <div class="flex justify-center gap-2 mt-8">
      <button
        v-for="page in comments.links"
        :key="page.label"
        v-html="page.label"
        @click="go(page.url)"
        :disabled="!page.url"
        class="px-4 py-2 rounded-xl bg-white border"
      />
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, reactive } from "vue";

import { router } from "@inertiajs/vue3";

import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
   doctor: {
    type: Object,
    required: true,
  },
  comments: Object,

  filters: Object,
});

const search = ref(props.filters?.search ?? "");

const replyText = reactive({});

const approve = (id) => {
  router.put(route("portal.post-comments.approve", id));
};

const reject = (id) => {
  router.put(route("portal.post-comments.reject", id));
};

const reply = (id) => {
  if (!replyText[id]) return;

  router.post(
    route("portal.post-comments.reply", id),

    {
      comment: replyText[id],
    },

    {
      preserveScroll: true,

      onSuccess() {
        replyText[id] = "";
      },
    }
  );
};

const deleteComment = (id) => {
  if (confirm("آیا از حذف این نظر مطمئن هستید؟")) {
    router.delete(route("portal.post-comments.destroy", id));
  }
};

const doSearch = () => {
  router.get(
    route("portal.post-comments.index"),

    {
      search: search.value,
    },

    {
      preserveState: true,

      replace: true,
    }
  );
};

const go = (url) => {
  if (url) {
    router.visit(url);
  }
};
</script>
