<template>

    <div
        class="bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-3xl p-8 flex flex-col md:flex-row justify-between items-center gap-6"
    >

        <div class="text-center">

            <div class="text-5xl font-black">
                {{ average }}
            </div>

            <div class="text-yellow-300 text-3xl mt-2">
                {{ stars }}
            </div>

            <p class="mt-2 text-cyan-100">
                امتیاز کاربران
            </p>

        </div>


        <div class="text-center">

            <div class="text-4xl font-black">
                {{ count }}
            </div>

            <p class="mt-2 text-cyan-100">
                نظر ثبت شده
            </p>

        </div>


    </div>

</template>


<script setup>


import { computed } from "vue";


const props = defineProps({

    average:{
        type:Number,
        default:0
    },


    count:{
        type:Number,
        default:0
    }

});



const stars = computed(()=>{

    return "⭐".repeat(
        Math.round(props.average)
    );

});


</script>