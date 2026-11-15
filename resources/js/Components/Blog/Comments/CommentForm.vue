<template>
  <form
    @submit.prevent="submit"
    class="bg-white rounded-3xl shadow-xl p-8 space-y-6"
  >

    <!-- پیام موفقیت -->
    <div
      v-if="successMessage"
      class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 font-bold"
    >
      {{ successMessage }}
    </div>


    <!-- پیام خطا -->
    <div
      v-if="errorMessage"
      class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 font-bold"
    >
      {{ errorMessage }}
    </div>


    <div>
      <h3 class="text-2xl font-black text-gray-800">
        ثبت نظر شما
      </h3>

      <p class="text-gray-500 mt-2">
        نظر و تجربه خود را با دیگران به اشتراک بگذارید.
      </p>
    </div>


    <div class="grid md:grid-cols-2 gap-5">

      <div>
        <label class="block mb-2 font-bold">
          نام
        </label>

        <input
          v-model="form.name"
          type="text"
          class="w-full rounded-xl border-gray-200"
          placeholder="نام شما"
        />

        <div
          v-if="errors.name"
          class="text-red-600 text-sm mt-2"
        >
          {{ errors.name }}
        </div>

      </div>


      <div>
        <label class="block mb-2 font-bold">
          ایمیل
        </label>

        <input
          v-model="form.email"
          type="email"
          class="w-full rounded-xl border-gray-200"
          placeholder="example@email.com"
        />

        <div
          v-if="errors.email"
          class="text-red-600 text-sm mt-2"
        >
          {{ errors.email }}
        </div>

      </div>

    </div>


    <RatingInput v-model="form.rating" />


    <div
      v-if="errors.rating"
      class="text-red-600 text-sm"
    >
      {{ errors.rating }}
    </div>


    <div>

      <label class="block mb-2 font-bold">
        نظر شما
      </label>


      <textarea
        v-model="form.comment"
        rows="5"
        class="w-full rounded-xl border-gray-200"
        placeholder="متن نظر خود را بنویسید..."
      />


      <div
        v-if="errors.comment"
        class="text-red-600 text-sm mt-2"
      >
        {{ errors.comment }}
      </div>

    </div>



    <label class="flex items-center gap-3">

      <input
        v-model="form.is_anonymous"
        type="checkbox"
        class="rounded"
      />

      <span>
        ارسال به صورت ناشناس
      </span>

    </label>



    <button
      type="submit"
      :disabled="processing"
      class="w-full bg-teal-600 hover:bg-teal-700 disabled:bg-gray-400 text-white font-black py-4 rounded-2xl transition"
    >

      <span v-if="!processing">
        ارسال نظر
      </span>

      <span v-else>
        در حال ارسال...
      </span>

    </button>


  </form>
</template>



<script setup>

import { reactive, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import RatingInput from "./RatingInput.vue";



const props = defineProps({

  post: {

    type: Object,

    required: true,

  },

});



const page = usePage();



const processing = ref(false);



const successMessage = ref("");

const errorMessage = ref("");



const errors = ref({});




const form = reactive({

  name: "",

  email: "",

  comment: "",

  rating: null,

  is_anonymous: false,

});




const resetForm = () => {

  form.name = "";

  form.email = "";

  form.comment = "";

  form.rating = null;

  form.is_anonymous = false;

};





const submit = () => {


  processing.value = true;


  successMessage.value = "";

  errorMessage.value = "";

  errors.value = {};



  router.post(

    route(
      "blog.comment.store",
      {
        post: props.post.slug,
      }
    ),


    form,


    {


      preserveScroll: true,


      onSuccess(pageResponse) {


     

     


        if (
          pageResponse.props.flash?.success
        ) {

          successMessage.value =
            pageResponse.props.flash.success;

        }


        resetForm();

      },



      onError(responseErrors) {


 

        errors.value = responseErrors;



        if (
          responseErrors.comment
        ) {

          errorMessage.value =
            responseErrors.comment;

        }


      },



      onFinish() {

        processing.value = false;

      },


    }

  );


};



</script>