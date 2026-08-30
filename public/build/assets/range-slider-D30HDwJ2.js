import{u as Z,a as ee,g as Y,m as le,V as ae,b as q}from"./VSliderTrack-D7M-NtQF.js";import{V as E,m as te}from"./VInput-pvgRigJa.js";import{u as se,m as oe,V as ue}from"./form-BJ4mZuov.js";import{aq as ne,r as g,au as re,at as ie,Y as G,a7 as de,b as t,p as ce,a9 as me,F as pe,o as h,f as R,aG as $,l as f,e as c,a0 as ve,d,s as v}from"./main-TWTCmj-z.js";import{_ as fe}from"./AppCardCode-Bk2whRwG.js";import{V as Ve,a as k}from"./VRow-Dzm07-65.js";import"./vue3-perfect-scrollbar--QdeCOqy.js";import"./VCard-DsKBMUtk.js";import"./createSimpleFunctional-j1QBDy8G.js";import"./VAvatar-c5yPRIz6.js";import"./VImg-DQYTfSmA.js";import"./VCardText-Db9-KNRP.js";import"./VDivider-B7KoFdnM.js";/* empty css              */const be=me({...oe(),...te(),...le(),strict:Boolean,modelValue:{type:Array,default:()=>[0,0]}},"VRangeSlider"),y=ne()({name:"VRangeSlider",props:be(),emits:{"update:focused":e=>!0,"update:modelValue":e=>!0,end:e=>!0,start:e=>!0},setup(e,o){let{slots:l,emit:u}=o;const a=g(),i=g(),S=g(),{rtlClasses:j}=re();function I(m){if(!a.value||!i.value)return;const p=Y(m,a.value.$el,e.direction),r=Y(m,i.value.$el,e.direction),n=Math.abs(p),V=Math.abs(r);return n<V||n===V&&p<0?a.value.$el:i.value.$el}const U=Z(e),s=ie(e,"modelValue",void 0,m=>m!=null&&m.length?m.map(p=>U.roundValue(p)):[0,0]),{activeThumbRef:b,hasLabels:H,max:B,min:x,mousePressed:J,onSliderMousedown:K,onSliderTouchstart:Q,position:z,trackContainerRef:X,readonly:L}=ee({props:e,steps:U,onSliderStart:()=>{u("start",s.value)},onSliderEnd:m=>{var n;let{value:p}=m;const r=b.value===((n=a.value)==null?void 0:n.$el)?[p,s.value[1]]:[s.value[0],p];!e.strict&&r[0]<r[1]&&(s.value=r),u("end",s.value)},onSliderMove:m=>{var V,w,D,_;let{value:p}=m;const[r,n]=s.value;!e.strict&&r===n&&r!==x.value&&(b.value=p>r?(V=i.value)==null?void 0:V.$el:(w=a.value)==null?void 0:w.$el,(D=b.value)==null||D.focus()),b.value===((_=a.value)==null?void 0:_.$el)?s.value=[Math.min(p,n),n]:s.value=[r,Math.max(r,p)]},getActiveThumb:I}),{isFocused:M,focus:A,blur:N}=se(e),O=G(()=>z(s.value[0])),W=G(()=>z(s.value[1]));return de(()=>{const m=E.filterProps(e),p=!!(e.label||l.label||l.prepend);return t(E,ce({class:["v-slider","v-range-slider",{"v-slider--has-labels":!!l["tick-label"]||H.value,"v-slider--focused":M.value,"v-slider--pressed":J.value,"v-slider--disabled":e.disabled},j.value,e.class],style:e.style,ref:S},m,{focused:M.value}),{...l,prepend:p?r=>{var n,V;return t(pe,null,[((n=l.label)==null?void 0:n.call(l,r))??(e.label?t(ue,{class:"v-slider__label",text:e.label},null):void 0),(V=l.prepend)==null?void 0:V.call(l,r)])}:void 0,default:r=>{var w,D;let{id:n,messagesId:V}=r;return t("div",{class:"v-slider__container",onMousedown:L.value?void 0:K,onTouchstartPassive:L.value?void 0:Q},[t("input",{id:`${n.value}_start`,name:e.name||n.value,disabled:!!e.disabled,readonly:!!e.readonly,tabindex:"-1",value:s.value[0]},null),t("input",{id:`${n.value}_stop`,name:e.name||n.value,disabled:!!e.disabled,readonly:!!e.readonly,tabindex:"-1",value:s.value[1]},null),t(ae,{ref:X,start:O.value,stop:W.value},{"tick-label":l["tick-label"]}),t(q,{ref:a,"aria-describedby":V.value,focused:M&&b.value===((w=a.value)==null?void 0:w.$el),modelValue:s.value[0],"onUpdate:modelValue":_=>s.value=[_,s.value[1]],onFocus:_=>{var T,F,C,P;A(),b.value=(T=a.value)==null?void 0:T.$el,B.value!==x.value&&s.value[0]===s.value[1]&&s.value[1]===x.value&&_.relatedTarget!==((F=i.value)==null?void 0:F.$el)&&((C=a.value)==null||C.$el.blur(),(P=i.value)==null||P.$el.focus())},onBlur:()=>{N(),b.value=void 0},min:x.value,max:s.value[1],position:O.value,ripple:e.ripple},{"thumb-label":l["thumb-label"]}),t(q,{ref:i,"aria-describedby":V.value,focused:M&&b.value===((D=i.value)==null?void 0:D.$el),modelValue:s.value[1],"onUpdate:modelValue":_=>s.value=[s.value[0],_],onFocus:_=>{var T,F,C,P;A(),b.value=(T=i.value)==null?void 0:T.$el,B.value!==x.value&&s.value[0]===s.value[1]&&s.value[0]===B.value&&_.relatedTarget!==((F=a.value)==null?void 0:F.$el)&&((C=i.value)==null||C.$el.blur(),(P=a.value)==null||P.$el.focus())},onBlur:()=>{N(),b.value=void 0},min:s.value[0],max:B.value,position:W.value,ripple:e.ripple},{"thumb-label":l["thumb-label"]})])}})}),{}}}),_e={__name:"DemoRangeSliderVertical",setup(e){const o=g([20,40]);return(l,u)=>(h(),R(y,{modelValue:f(o),"onUpdate:modelValue":u[0]||(u[0]=a=>$(o)?o.value=a:null),direction:"vertical"},null,8,["modelValue"]))}},ge={__name:"DemoRangeSliderThumbLabel",setup(e){const o=["Winter","Spring","Summer","Fall"],l=["ri-snowy-line","ri-leaf-line","ri-fire-line","ri-drop-line"],u=g([1,2]);return(a,i)=>(h(),R(y,{modelValue:f(u),"onUpdate:modelValue":i[0]||(i[0]=S=>$(u)?u.value=S:null),tick:o,min:"0",max:"3",step:1,"show-ticks":"always","thumb-label":"","tick-size":"4"},{"thumb-label":c(({modelValue:S})=>[t(ve,{icon:l[S]},null,8,["icon"])]),_:1},8,["modelValue"]))}},Se={__name:"DemoRangeSliderStep",setup(e){const o=g([20,40]);return(l,u)=>(h(),R(y,{modelValue:f(o),"onUpdate:modelValue":u[0]||(u[0]=a=>$(o)?o.value=a:null),step:"10"},null,8,["modelValue"]))}},he={__name:"DemoRangeSliderColor",setup(e){const o=g([10,60]);return(l,u)=>(h(),R(y,{modelValue:f(o),"onUpdate:modelValue":u[0]||(u[0]=a=>$(o)?o.value=a:null),color:"success","track-color":"secondary"},null,8,["modelValue"]))}},Re={__name:"DemoRangeSliderDisabled",setup(e){const o=g([30,60]);return(l,u)=>(h(),R(y,{modelValue:f(o),"onUpdate:modelValue":u[0]||(u[0]=a=>$(o)?o.value=a:null),disabled:"",label:"Disabled"},null,8,["modelValue"]))}},ke={__name:"DemoRangeSliderBasic",setup(e){const o=g([10,60]);return(l,u)=>(h(),R(y,{modelValue:f(o),"onUpdate:modelValue":u[0]||(u[0]=a=>$(o)?o.value=a:null)},null,8,["modelValue"]))}},$e={ts:`<script setup lang="ts">
const sliderValues = ref([10, 60])
<\/script>

<template>
  <VRangeSlider v-model="sliderValues" />
</template>
`,js:`<script setup>
const sliderValues = ref([
  10,
  60,
])
<\/script>

<template>
  <VRangeSlider v-model="sliderValues" />
</template>
`},ye={ts:`<script lang="ts" setup>
const sliderValues = ref([10, 60])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    color="success"
    track-color="secondary"
  />
</template>
`,js:`<script setup>
const sliderValues = ref([
  10,
  60,
])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    color="success"
    track-color="secondary"
  />
</template>
`},xe={ts:`<script lang="ts" setup>
const slidersValues = ref([30, 60])
<\/script>

<template>
  <VRangeSlider
    v-model="slidersValues"
    disabled
    label="Disabled"
  />
</template>
`,js:`<script setup>
const slidersValues = ref([
  30,
  60,
])
<\/script>

<template>
  <VRangeSlider
    v-model="slidersValues"
    disabled
    label="Disabled"
  />
</template>
`},we={ts:`<script lang="ts" setup>
const sliderValues = ref([20, 40])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    step="10"
  />
</template>
`,js:`<script setup>
const sliderValues = ref([
  20,
  40,
])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    step="10"
  />
</template>
`},De={ts:`<script lang="ts" setup>
const seasons = ['Winter', 'Spring', 'Summer', 'Fall']
const icons = ['ri-snowy-line', 'ri-leaf-line', 'ri-fire-line', 'ri-drop-line']
const sliderValues = ref([1, 2])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    :tick="seasons"
    min="0"
    max="3"
    :step="1"
    show-ticks="always"
    thumb-label
    tick-size="4"
  >
    <template #thumb-label="{ modelValue }">
      <VIcon :icon="icons[modelValue]" />
    </template>
  </VRangeSlider>
</template>
`,js:`<script setup>
const seasons = [
  'Winter',
  'Spring',
  'Summer',
  'Fall',
]

const icons = [
  'ri-snowy-line',
  'ri-leaf-line',
  'ri-fire-line',
  'ri-drop-line',
]

const sliderValues = ref([
  1,
  2,
])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    :tick="seasons"
    min="0"
    max="3"
    :step="1"
    show-ticks="always"
    thumb-label
    tick-size="4"
  >
    <template #thumb-label="{ modelValue }">
      <VIcon :icon="icons[modelValue]" />
    </template>
  </VRangeSlider>
</template>
`},Te={ts:`<script lang="ts" setup>
const sliderValues = ref([20, 40])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    direction="vertical"
  />
</template>
`,js:`<script setup>
const sliderValues = ref([
  20,
  40,
])
<\/script>

<template>
  <VRangeSlider
    v-model="sliderValues"
    direction="vertical"
  />
</template>
`},Ye={__name:"range-slider",setup(e){return(o,l)=>{const u=ke,a=fe,i=Re,S=he,j=Se,I=ge,U=_e;return h(),R(Ve,null,{default:c(()=>[t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Basic",code:f($e)},{default:c(()=>[l[0]||(l[0]=d("p",null,[v("The "),d("code",null,"v-slider"),v(" component is a better visualization of the number input.")],-1)),t(u)]),_:1,__:[0]},8,["code"])]),_:1}),t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Disabled",code:f(xe)},{default:c(()=>[l[1]||(l[1]=d("p",null,[v("You cannot interact with "),d("code",null,"disabled"),v(" sliders.")],-1)),t(i)]),_:1,__:[1]},8,["code"])]),_:1}),t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Color",code:f(ye)},{default:c(()=>[l[2]||(l[2]=d("p",null,[v("Use "),d("code",null,"color"),v(" prop to the sets the slider color. "),d("code",null,"track-color"),v(" prop to sets the color of slider's unfilled track.")],-1)),t(S)]),_:1,__:[2]},8,["code"])]),_:1}),t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Step",code:f(we)},{default:c(()=>[l[3]||(l[3]=d("p",null,[d("code",null,"v-range-slider"),v(" can have steps other than 1. This can be helpful for some applications where you need to adjust values with more or less accuracy.")],-1)),t(j)]),_:1,__:[3]},8,["code"])]),_:1}),t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Thumb label",code:f(De)},{default:c(()=>[l[4]||(l[4]=d("p",null,[v(" Using the "),d("code",null,"tick-labels"),v(" prop along with the "),d("code",null,"thumb-label"),v(" slot, you can create a very customized solution. ")],-1)),t(I)]),_:1,__:[4]},8,["code"])]),_:1}),t(k,{cols:"12",md:"6"},{default:c(()=>[t(a,{title:"Vertical",code:f(Te)},{default:c(()=>[l[5]||(l[5]=d("p",null,[v("You can use the "),d("code",null,"vertical"),v(" prop to switch sliders to a vertical orientation. If you need to change the height of the slider, use css.")],-1)),t(U)]),_:1,__:[5]},8,["code"])]),_:1})]),_:1})}}};export{Ye as default};
