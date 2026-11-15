<template>

    <div class="space-y-6">


        <CommentItem
            v-for="comment in comments"
            :key="comment.id"
            :comment="comment"
        />


    </div>

</template>


<script setup>

import CommentItem from "./CommentItem.vue";


defineProps({

    comments:{
        type:Array,
        default:()=>[]
    }

});


</script>