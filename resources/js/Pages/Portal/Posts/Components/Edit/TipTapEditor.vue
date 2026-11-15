<template>
  <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
    <!-- نوار ابزار -->
    <div
      v-if="editor"
      class="flex flex-wrap gap-1 p-2 border-b border-slate-200 bg-slate-50 sticky top-0 z-10"
    >
      <button
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="buttonClass(editor.isActive('heading', { level: 1 }))"
        type="button"
      >
        H1
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="buttonClass(editor.isActive('heading', { level: 2 }))"
        type="button"
      >
        H2
      </button>
      <button
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="buttonClass(editor.isActive('heading', { level: 3 }))"
        type="button"
      >
        H3
      </button>

      <div class="w-px bg-slate-300 mx-1"></div>

      <button
        @click="editor.chain().focus().toggleBold().run()"
        :class="buttonClass(editor.isActive('bold'))"
        type="button"
      >
        <strong>B</strong>
      </button>
      <button
        @click="editor.chain().focus().toggleItalic().run()"
        :class="buttonClass(editor.isActive('italic'))"
        type="button"
      >
        <em>I</em>
      </button>
      <button
        @click="editor.chain().focus().toggleUnderline().run()"
        :class="buttonClass(editor.isActive('underline'))"
        type="button"
      >
        <u>U</u>
      </button>

      <div class="w-px bg-slate-300 mx-1"></div>

      <button
        @click="editor.chain().focus().toggleBulletList().run()"
        :class="buttonClass(editor.isActive('bulletList'))"
        type="button"
      >
        • لیست
      </button>
      <button
        @click="editor.chain().focus().toggleOrderedList().run()"
        :class="buttonClass(editor.isActive('orderedList'))"
        type="button"
      >
        1. لیست
      </button>

      <div class="w-px bg-slate-300 mx-1"></div>

      <button
        @click="setLink"
        :class="buttonClass(editor.isActive('link'))"
        type="button"
      >
        🔗 لینک
      </button>
      <button
        @click="editor.chain().focus().unsetLink().run()"
        type="button"
        class="px-3 py-1 text-xs rounded hover:bg-slate-200"
      >
        حذف لینک
      </button>
    </div>

    <!-- ناحیه ادیتور -->
    <editor-content
      :editor="editor"
      class="prose max-w-none p-4 min-h-[400px] focus:outline-none"
      style="direction: rtl; text-align: right"
    />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import Link from "@tiptap/extension-link";
import Placeholder from "@tiptap/extension-placeholder";

const props = defineProps({
  modelValue: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:modelValue"]);

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    TextAlign.configure({
      types: ["heading", "paragraph"],
    }),
    Link.configure({
      openOnClick: false,
    }),
    Placeholder.configure({
      placeholder: "محتوای مقاله را اینجا بنویسید...",
    }),
  ],
  onUpdate: ({ editor }) => {
    emit("update:modelValue", editor.getHTML());
  },
});

const setLink = () => {
  const previousUrl = editor.value.getAttributes("link").href;
  const url = window.prompt("آدرس لینک:", previousUrl);

  if (url === null) return;

  if (url === "") {
    editor.value.chain().focus().extendMarkRange("link").unsetLink().run();
    return;
  }

  editor.value.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
};

const buttonClass = (isActive) => {
  return [
    "px-3 py-1 text-xs rounded transition",
    isActive ? "bg-indigo-600 text-white" : "hover:bg-slate-200 text-slate-700",
  ];
};
</script>

<style>
.tiptap {
  min-height: 400px;
}

.tiptap p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.tiptap h1 {
  font-size: 2em;
  font-weight: bold;
  margin: 1em 0;
}

.tiptap h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin: 0.8em 0;
}

.tiptap h3 {
  font-size: 1.25em;
  font-weight: bold;
  margin: 0.6em 0;
}

.tiptap ul {
  list-style-type: disc;
  padding-right: 1.5em;
  margin: 0.5em 0;
}

.tiptap ol {
  list-style-type: decimal;
  padding-right: 1.5em;
  margin: 0.5em 0;
}

.tiptap a {
  color: #4f46e5;
  text-decoration: underline;
}
</style>