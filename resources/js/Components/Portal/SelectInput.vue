<!-- resources/js/Components/Doctor/SelectInput.vue -->
<template>
  <div class="relative" ref="selectRef">
    <!-- Label -->
    <label v-if="label" class="block text-sm font-bold text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Trigger Button -->
    <button
      type="button"
      @click="toggleDropdown"
      :class="[
        'w-full px-4 py-3 text-right border rounded-xl transition-all bg-white flex items-center justify-between gap-2',
        hasError
          ? 'border-red-500 focus:ring-red-500'
          : 'border-gray-200 focus:ring-teal-500',
        'focus:ring-2 focus:border-transparent',
        isOpen ? 'ring-2 ring-teal-500 border-teal-500' : '',
        disabled
          ? 'bg-gray-100 cursor-not-allowed opacity-60'
          : 'cursor-pointer hover:border-gray-300',
      ]"
      :disabled="disabled"
    >
      <span
        :class="selectedLabel || searchQuery ? 'text-gray-900' : 'text-gray-400'"
        class="truncate"
      >
        {{ selectedLabel || searchQuery || placeholder }}
      </span>
      <svg
        class="w-5 h-5 text-gray-400 transition-transform"
        :class="isOpen ? 'rotate-180' : ''"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute z-50 mt-2 w-full bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden"
      >
        <!-- Search Input -->
        <div v-if="searchable" class="p-3 border-b border-gray-100">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full px-4 py-2 pr-10 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent"
              @click.stop
            />
            <svg
              class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
        </div>

        <!-- Options List -->
        <div class="max-h-60 overflow-y-auto py-2">
          <!-- Clear Button (if has value) -->
          <button
            v-if="clearable && modelValue"
            @click="clearSelection"
            class="w-full px-4 py-2 text-right text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            پاک کردن انتخاب
          </button>

          <!-- No Results -->
          <div
            v-if="filteredOptions.length === 0"
            class="px-4 py-3 text-sm text-gray-500 text-center"
          >
            {{ noResultsText }}
          </div>
          <!-- Create New Option -->
          <button
            v-if="
              allowCreate &&
              searchQuery.trim().length >= 2 &&
              !filteredOptions.some(
                (opt) =>
                  String(opt.label).trim().toLowerCase() ===
                  searchQuery.trim().toLowerCase()
              )
            "
            type="button"
            @click="createOption"
            class="w-full px-4 py-3 text-right text-sm text-teal-700 bg-teal-50 hover:bg-teal-100 border-b border-gray-100"
          >
            + {{ createLabel }} «{{ searchQuery.trim() }}»
          </button>
          <!-- Options -->
          <button
            v-for="option in filteredOptions"
            :key="getOptionValue(option)"
            type="button"
            @click="selectOption(option)"
            :class="[
              'w-full px-4 py-2.5 text-right text-sm transition-colors flex items-center gap-2',
              isSelected(option)
                ? 'bg-teal-50 text-teal-700 font-bold'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <!-- Check Icon -->
            <svg
              v-if="isSelected(option)"
              class="w-5 h-5 text-teal-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span v-else class="w-5"></span>

            <!-- Option Text -->
            <span class="flex-1">{{ getOptionLabel(option) }}</span>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Error Message -->
    <p v-if="errorMessage" class="text-red-500 text-xs mt-1">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
  allowCreate: {
    type: Boolean,
    default: false,
  },
  createLabel: {
    type: String,
    default: "افزودن مقدار جدید",
  },
  modelValue: {
    type: [String, Number],
    default: "",
  },
  options: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "انتخاب کنید",
  },
  searchable: {
    type: Boolean,
    default: false,
  },
  searchPlaceholder: {
    type: String,
    default: "جست‌وجو ...",
  },
  noResultsText: {
    type: String,
    default: "",
  },
  clearable: {
    type: Boolean,
    default: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  hasError: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: "",
  },
  optionValue: {
    type: String,
    default: "value",
  },
  optionLabel: {
    type: String,
    default: "label",
  },
});

const emit = defineEmits(["update:modelValue", "change", "create"]);

const isOpen = ref(false);
const searchQuery = ref("");
const selectRef = ref(null);

// تبدیل options به فرمت استاندارد
const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    if (typeof opt === "string" || typeof opt === "number") {
      return { value: opt, label: opt };
    }
    return opt;
  });
});
const createOption = () => {
  const label = searchQuery.value.trim();

  if (label.length < 2) return;

  emit("create", label);
  isOpen.value = false;
  searchQuery.value = "";
};
// فیلتر کردن options بر اساس جستجو
const filteredOptions = computed(() => {
  if (!searchQuery.value) return normalizedOptions.value;

  const query = searchQuery.value.toLowerCase();
  return normalizedOptions.value.filter((opt) =>
    String(opt.label).toLowerCase().includes(query)
  );
});

// برچسب انتخاب شده
const selectedLabel = computed(() => {
  const selected = normalizedOptions.value.find((opt) => opt.value === props.modelValue);
  return selected ? selected.label : "";
});

// بررسی انتخاب بودن یک گزینه
const isSelected = (option) => {
  return option.value === props.modelValue;
};

// دریافت مقدار گزینه
const getOptionValue = (option) => {
  return option.value;
};

// دریافت برچسب گزینه
const getOptionLabel = (option) => {
  return option.label;
};

// باز/بسته کردن dropdown
const toggleDropdown = () => {
  if (props.disabled) return;

  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    searchQuery.value = "";
  }
};

// انتخاب گزینه
const selectOption = (option) => {
  emit("update:modelValue", option.value);
  emit("change", option.value);
  isOpen.value = false;
  searchQuery.value = "";
};

// پاک کردن انتخاب
const clearSelection = () => {
  emit("update:modelValue", "");
  emit("change", "");
  isOpen.value = false;
};

// بستن با کلیک بیرون
const handleClickOutside = (event) => {
  if (selectRef.value && !selectRef.value.contains(event.target)) {
    isOpen.value = false;

    const query = searchQuery.value.trim();

    if (query) {
      const matchedOption = normalizedOptions.value.find(
        (option) =>
          String(option.label).trim().toLowerCase() === query.toLowerCase()
      );

      if (matchedOption) {
        emit("update:modelValue", matchedOption.value);
        emit("change", matchedOption.value);
        searchQuery.value = "";
      } else {
        emit("update:modelValue", query);
        emit("change", query);
      }
    }
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Custom Scrollbar */
.max-h-60::-webkit-scrollbar {
  width: 6px;
}

.max-h-60::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
