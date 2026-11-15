<template>

    <div
        class="bg-white rounded-3xl shadow p-6"
    >

        <div class="flex justify-between items-start">

            <div class="flex items-center gap-4">

                <div
                    class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center text-xl"
                >
                    👤
                </div>


                <div>

                    <h4 class="font-black text-gray-800">
                        {{ comment.display_name }}
                    </h4>

                    <p class="text-sm text-gray-400">
                        {{ comment.persian_date }}
                    </p>

                </div>

            </div>


            <div
                v-if="comment.rating"
                class="text-yellow-500 text-lg"
            >
                {{ comment.stars }}
            </div>


        </div>



        <p
            class="mt-5 text-gray-700 leading-9"
        >
            {{ comment.comment }}
        </p>



        <!-- پاسخ ها -->

        <div
            v-if="comment.replies?.length"
            class="mt-6 mr-8 space-y-4 border-r-4 border-teal-100 pr-5"
        >

            <CommentItem
                v-for="reply in comment.replies"
                :key="reply.id"
                :comment="reply"
            />

        </div>


    </div>

</template>


<script setup>

defineProps({

    comment:{
        type:Object,
        required:true
    }

});


</script>