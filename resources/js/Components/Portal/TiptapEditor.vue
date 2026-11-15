<script setup>
import { ref, onBeforeUnmount } from "vue";
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Image from "@tiptap/extension-image";
import Link from "@tiptap/extension-link";
import Placeholder from "@tiptap/extension-placeholder";
import TextAlign from "@tiptap/extension-text-align";
import Underline from "@tiptap/extension-underline";

const props = defineProps({
  modelValue: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:modelValue"]);
const isUploading = ref(false);
const fileInput = ref(null);

// ⚠️ نکته مهم: غیرفعال کردن Link و Underline در StarterKit
// برای جلوگیری از خطای "Duplicate extension names"
const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      link: false,
      underline: false,
    }),
    Underline,
    Image.configure({
      inline: false,
      allowBase64: false,
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: "text-blue-600 hover:underline cursor-pointer",
      },
    }),
    Placeholder.configure({
      placeholder: "محتوای مقاله خود را اینجا بنویسید...",
    }),
    TextAlign.configure({
      types: ["heading", "paragraph"],
    }),
  ],
  onUpdate: ({ editor }) => {
    emit("update:modelValue", editor.getHTML());
  },
});

// پاک‌سازی ادیتور هنگام نابودی کامپوننت (جلوگیری از نشت حافظه)
onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy();
  }
});

// تابع آپلود تصویر
const triggerImageUpload = () => {
  fileInput.value.click();
};

const handleImageUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  isUploading.value = true;

  try {
    const formData = new FormData();
    formData.append("image", file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const response = await fetch(route("portal.posts.upload-image"), {
      method: "POST",
      body: formData,
      headers: {
        "X-CSRF-TOKEN": csrfToken,
        Accept: "application/json",
      },
    });

    const data = await response.json();

    if (response.ok && data.url) {
      editor.value.chain().focus().setImage({ src: data.url }).run();
    } else {
      alert(data.error || "خطا در آپلود تصویر");
    }
  } catch (error) {
    console.error("Image upload error:", error);
    alert("خطا در برقراری ارتباط با سرور");
  } finally {
    isUploading.value = false;
    // پاک کردن مقدار input برای امکان آپلود مجدد همان فایل
    event.target.value = "";
  }
};

// تابع افزودن لینک
const setLink = () => {
  const previousUrl = editor.value.getAttributes("link").href;
  const url = window.prompt("آدرس لینک را وارد کنید:", previousUrl);

  if (url === null) return; // کاربر کنسل کرد

  if (url === "") {
    editor.value.chain().focus().extendMarkRange("link").unsetLink().run();
    return;
  }

  editor.value.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
};

// توابع کمکی برای کلاس‌های دکمه‌ها
const getBtnClass = (isActive) => {
  const base =
    "p-2 rounded-lg transition-all duration-200 flex items-center justify-center";
  const active = "bg-blue-600 text-white shadow-sm";
  const inactive =
    "text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700";
  return `${base} ${isActive ? active : inactive}`;
};
</script>

<template>
  <div
    v-if="editor"
    class="border border-gray-300 dark:border-gray-600 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm transition-colors"
  >
    <!-- نوار ابزار (Toolbar) -->
    <div
      class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 flex flex-wrap gap-1 items-center"
    >
      <!-- گروه ۱: قالب‌بندی متن -->
      <div class="flex gap-1">
        <button
          @click="editor.chain().focus().toggleBold().run()"
          :class="getBtnClass(editor.isActive('bold'))"
          title="ضخیم (Bold)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M4 4h7a4 4 0 014 4 4 4 0 01-4 4H4V4zm0 8h8a4 4 0 014 4 4 4 0 01-4 4H4v-8z"
            />
          </svg>
        </button>
        <button
          @click="editor.chain().focus().toggleItalic().run()"
          :class="getBtnClass(editor.isActive('italic'))"
          title="کج (Italic)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M8 3h8v2H8V3zm4 14H4v-2h8v2zM9 5l2 10H9L7 5h2z" />
          </svg>
        </button>
        <button
          @click="editor.chain().focus().toggleUnderline().run()"
          :class="getBtnClass(editor.isActive('underline'))"
          title="زیرخط (Underline)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M4 17h12v2H4v-2zm3-14h2v8a3 3 0 006 0V3h2v8a5 5 0 01-10 0V3z" />
          </svg>
        </button>
      </div>

      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-2"></div>

      <!-- گروه ۲: تیترها -->
      <div class="flex gap-1">
        <button
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="getBtnClass(editor.isActive('heading', { level: 1 }))"
          title="عنوان ۱"
        >
          <span class="text-sm font-bold">H1</span>
        </button>
        <button
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="getBtnClass(editor.isActive('heading', { level: 2 }))"
          title="عنوان ۲"
        >
          <span class="text-sm font-bold">H2</span>
        </button>
        <button
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="getBtnClass(editor.isActive('heading', { level: 3 }))"
          title="عنوان ۳"
        >
          <span class="text-sm font-bold">H3</span>
        </button>
      </div>

      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-2"></div>

      <!-- گروه ۳: لیست‌ها -->
      <div class="flex gap-1">
        <button
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="getBtnClass(editor.isActive('bulletList'))"
          title="لیست نقطه‌ای"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M3 4a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2zm-4 6a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2zm-4 6a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2z"
            />
          </svg>
        </button>
        <button
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="getBtnClass(editor.isActive('orderedList'))"
          title="لیست عددی"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M3 4a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2zm-4 6a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2zm-4 6a1 1 0 100 2 1 1 0 000-2zm4 0h10a1 1 0 110 2H7a1 1 0 010-2z"
            />
          </svg>
        </button>
      </div>

      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-2"></div>

      <!-- گروه ۴: تراز بندی -->
      <div class="flex gap-1">
        <button
          @click="editor.chain().focus().setTextAlign('right').run()"
          :class="getBtnClass(editor.isActive({ textAlign: 'right' }))"
          title="راست‌چین"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 4h16v2H2V4zm6 4h10v2H8V8zm-6 4h16v2H2v-2zm6 4h10v2H8v-2z" />
          </svg>
        </button>
        <button
          @click="editor.chain().focus().setTextAlign('center').run()"
          :class="getBtnClass(editor.isActive({ textAlign: 'center' }))"
          title="وسط‌چین"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 4h16v2H2V4zm3 4h10v2H5V8zm-3 4h16v2H2v-2zm3 4h10v2H5v-2z" />
          </svg>
        </button>
        <button
          @click="editor.chain().focus().setTextAlign('left').run()"
          :class="getBtnClass(editor.isActive({ textAlign: 'left' }))"
          title="چپ‌چین"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 4h16v2H2V4zm0 4h10v2H2V8zm0 4h16v2H2v-2zm0 4h10v2H2v-2z" />
          </svg>
        </button>
      </div>

      <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-2"></div>

      <!-- گروه ۵: رسانه و سایر -->
      <div class="flex gap-1">
        <button
          @click="setLink"
          :class="getBtnClass(editor.isActive('link'))"
          title="افزودن لینک"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"
            />
            <path
              d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"
            />
          </svg>
        </button>

        <button
          @click="triggerImageUpload"
          :disabled="isUploading"
          :class="
            getBtnClass(false) + (isUploading ? ' opacity-50 cursor-not-allowed' : '')
          "
          title="افزودن تصویر"
        >
          <svg
            v-if="!isUploading"
            class="w-4 h-4"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
            />
          </svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </button>

        <button
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="getBtnClass(editor.isActive('blockquote'))"
          title="نقل قول"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M3 5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm10 0a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V5z"
            />
          </svg>
        </button>

        <button
          @click="editor.chain().focus().setHorizontalRule().run()"
          :class="getBtnClass(false)"
          title="خط افقی"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 10h16v2H2v-2z" />
          </svg>
        </button>
      </div>

      <!-- Input مخفی برای انتخاب فایل -->
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleImageUpload"
        class="hidden"
      />
    </div>

    <!-- محتوای قابل ویرایش -->
    <EditorContent :editor="editor" class="tiptap-content" />
  </div>
</template>

<style>
/* استایل‌های اصلی ادیتور */
.tiptap-content {
  min-height: 400px;
  max-height: 600px;
  overflow-y: auto;
  padding: 1.5rem;
  outline: none;
  font-size: 16px;
  line-height: 1.8;
  color: #1f2937; /* gray-800 */
  direction: rtl;
  text-align: right;
}

.dark .tiptap-content {
  color: #f3f4f6; /* gray-100 */
}

/* Placeholder */
.tiptap-content p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: right;
  color: #9ca3af; /* gray-400 */
  pointer-events: none;
  height: 0;
  font-style: italic;
}

/* تصاویر */
.tiptap-content img {
  max-width: 100%;
  height: auto;
  border-radius: 0.75rem;
  margin: 1.5rem 0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  display: block;
}

/* نقل قول */
.tiptap-content blockquote {
  border-right: 4px solid #3b82f6; /* blue-500 */
  padding-right: 1.5rem;
  margin-right: 0;
  margin-left: 0;
  font-style: italic;
  color: #4b5563; /* gray-600 */
  background: #f9fafb; /* gray-50 */
  padding: 1rem 1.5rem;
  border-radius: 0.5rem;
}

.dark .tiptap-content blockquote {
  color: #d1d5db; /* gray-300 */
  background: #374151; /* gray-700 */
}

/* تیترها */
.tiptap-content h1 {
  font-size: 2rem;
  font-weight: 800;
  margin: 2rem 0 1rem;
  color: #111827;
}
.dark .tiptap-content h1 {
  color: #f9fafb;
}

.tiptap-content h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 1.75rem 0 0.875rem;
  color: #1f2937;
}
.dark .tiptap-content h2 {
  color: #f3f4f6;
}

.tiptap-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem;
  color: #374151;
}
.dark .tiptap-content h3 {
  color: #e5e7eb;
}

/* لیست‌ها */
.tiptap-content ul,
.tiptap-content ol {
  padding-right: 1.5rem;
  padding-left: 0;
  margin: 1rem 0;
}

.tiptap-content li {
  margin: 0.5rem 0;
}

/* لینک‌ها */
.tiptap-content a {
  color: #2563eb; /* blue-600 */
  text-decoration: underline;
  transition: color 0.2s;
}
.tiptap-content a:hover {
  color: #1d4ed8; /* blue-700 */
}

/* خط افقی */
.tiptap-content hr {
  border: none;
  border-top: 2px solid #e5e7eb;
  margin: 2.5rem 0;
}
.dark .tiptap-content hr {
  border-color: #4b5563;
}
</style>
