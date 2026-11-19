<script setup>
import { onBeforeUnmount, ref } from 'vue'
const props=defineProps({modelValue:{type:String,default:''},language:{type:String,default:'fa-IR'}})
const emit=defineEmits(['update:modelValue'])
const listening=ref(false),error=ref('')
let recognition=null
const start=()=>{
 error.value=''
 const API=window.SpeechRecognition||window.webkitSpeechRecognition
 if(!API){error.value='مرورگر شما از تشخیص گفتار پشتیبانی نمی‌کند.';return}
 try{
  recognition=new API();recognition.lang=props.language;recognition.continuous=true;recognition.interimResults=true
  recognition.onstart=()=>{listening.value=true}
  recognition.onresult=e=>{let text='';for(let i=e.resultIndex;i<e.results.length;i++)if(e.results[i].isFinal)text+=e.results[i][0].transcript
   if(text.trim())emit('update:modelValue',[String(props.modelValue||'').trim(),text.trim()].filter(Boolean).join(' '))}
  recognition.onerror=e=>{error.value=e.error==='not-allowed'?'دسترسی میکروفون مجاز نیست.':'تشخیص گفتار با خطا روبه‌رو شد؛ متن را بازبینی کنید.'}
  recognition.onend=()=>{listening.value=false};recognition.start()
 }catch{listening.value=false;error.value='شروع میکروفون ممکن نشد. مجوز مرورگر را بررسی کنید.'}
}
const stop=()=>{if(recognition)recognition.stop();listening.value=false}
onBeforeUnmount(stop)
</script>
<template><div class="my-2 flex flex-wrap items-center gap-2">
<button v-if="!listening" type="button" @click="start" class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">🎙 دیکته صوتی فارسی</button>
<button v-else type="button" @click="stop" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-800">توقف دیکته</button>
<span v-if="listening" class="text-xs text-rose-700">در حال شنیدن؛ متن را بازبینی کنید.</span><span v-if="error" role="status" class="text-xs text-rose-700">{{error}}</span>
<p class="basis-full text-[10px] leading-5 text-slate-500">صدا در پرونیکس ذخیره یا بارگذاری نمی‌شود؛ سرویس تشخیص گفتار مرورگر ممکن است صدا را طبق سیاست ارائه‌دهنده خود پردازش کند. فقط با رعایت رضایت بیمار و ضوابط محرمانگی استفاده کنید. متن پس از بازبینی پزشک ذخیره می‌شود.</p>
</div></template>