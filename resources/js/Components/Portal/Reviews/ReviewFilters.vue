<template>
    <div
        class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6"
    >
        <div
            class="grid grid-cols-1 md:grid-cols-4 gap-4"
        >

            <!-- جستجو -->

            <div>

                <label
                    class="block text-sm font-bold text-gray-700 mb-2"
                >
                    جستجو
                </label>

                <input

                    :value="search"

                    @input="updateSearch"

                    type="text"

                    placeholder="نام بیمار یا متن نظر..."

                    class="w-full rounded-xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"

                />

            </div>

            <!-- وضعیت -->

            <div>

                <label
                    class="block text-sm font-bold text-gray-700 mb-2"
                >
                    وضعیت
                </label>

                <select

                    :value="status"

                    @change="updateStatus"

                    class="w-full rounded-xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"

                >

                    <option value="">
                        همه
                    </option>

                    <option value="approved">
                        تایید شده
                    </option>

                    <option value="pending">
                        در انتظار تایید
                    </option>

                </select>

            </div>

            <!-- امتیاز -->

            <div>

                <label
                    class="block text-sm font-bold text-gray-700 mb-2"
                >
                    امتیاز
                </label>

                <select

                    :value="rating"

                    @change="updateRating"

                    class="w-full rounded-xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"

                >

                    <option value="">
                        همه
                    </option>

                    <option
                        v-for="i in 5"
                        :key="i"
                        :value="i"
                    >
                        {{ i }} ستاره
                    </option>

                </select>

            </div>

            <!-- دکمه -->

            <div
                class="flex items-end"
            >

                <button

                    @click="$emit('filter')"

                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-xl font-bold"

                >

                    اعمال فیلتر

                </button>

            </div>

        </div>
    </div>
</template>

<script setup>

const props = defineProps({

    search: String,

    status: String,

    rating: [String, Number],

});

const emit = defineEmits([

    "update:search",

    "update:status",

    "update:rating",

    "filter",

]);

const updateSearch = (e) => {

    emit(

        "update:search",

        e.target.value

    );

};

const updateStatus = (e) => {

    emit(

        "update:status",

        e.target.value

    );

};

const updateRating = (e) => {

    emit(

        "update:rating",

        e.target.value

    );

};

</script>