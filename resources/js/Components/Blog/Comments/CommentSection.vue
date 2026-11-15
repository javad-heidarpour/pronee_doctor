<template>

<section class="mt-16 space-y-8">


    <CommentStats
        :average="stats.average"
        :count="stats.count"
    />


    <CommentForm
        :post="post"
    />



    <div>

        <h2
            class="text-3xl font-black text-gray-800 mb-8"
        >
            نظرات کاربران
        </h2>


        <CommentList
            v-if="comments.length"
            :comments="comments"
        />


        <EmptyComments
            v-else
        />


    </div>


</section>

</template>



<script setup>

import CommentStats from "./CommentStats.vue";
import CommentForm from "./CommentForm.vue";
import CommentList from "./CommentList.vue";
import EmptyComments from "./EmptyComments.vue";


defineProps({

    post:{
        type:Object,
        required:true
    },


    comments:{
        type:Array,
        default:()=>[]
    },


    stats:{
        type:Object,
        default:()=>({
            average:0,
            count:0
        })
    }

});


</script>