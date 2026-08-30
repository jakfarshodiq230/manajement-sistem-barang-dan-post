import{r as f,o as c,f as _,e as o,b as e,a0 as $,p as U,aG as u,l as s,c as V,d as a,t as S,F as y,i as j,s as r}from"./main-CrHLQfV8.js";import{V as m}from"./VRating-AaQjWmQf.js";import{_ as v}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{V as k}from"./VSlider-C22F2R_D.js";import{_ as F}from"./AppCardCode-DvVcanpZ.js";import{V as I,a as p}from"./VRow-B7QHx5BP.js";import"./VSliderTrack-GEOEvRpg.js";import"./VInput-DPYQxEgm.js";import"./form-BLlc5Gb_.js";import"./vue3-perfect-scrollbar-DLzc-8Wd.js";import"./VCard-BCHwnJXd.js";import"./createSimpleFunctional-PWyTj9HX.js";import"./VAvatar-BKgmGdw4.js";import"./VImg-B5tbpIyO.js";import"./VCardText-BdD2bCIU.js";import"./VDivider-C5vNIyMQ.js";/* empty css              */const B={__name:"DemoRatingItemSlot",setup(d){const t=f(4.5);return(l,i)=>(c(),_(m,{modelValue:s(t),"onUpdate:modelValue":i[0]||(i[0]=n=>u(t)?t.value=n:null)},{item:o(n=>[e($,U(n,{size:25,color:n.isFilled?"success":"secondary",class:"me-3",icon:n.isFilled?"ri-emotion-happy-line":"ri-emotion-unhappy-line"}),null,16,["color","icon"])]),_:1},8,["modelValue"]))}},T={__name:"DemoRatingIncremented",setup(d){const t=f(4.5);return(l,i)=>(c(),_(m,{modelValue:s(t),"onUpdate:modelValue":i[0]||(i[0]=n=>u(t)?t.value=n:null),"half-increments":"",hover:"",color:"secondary"},null,8,["modelValue"]))}},L={};function M(d,t){return c(),_(m,{hover:""})}const N=v(L,[["render",M]]),H={};function P(d,t){return c(),_(m,{readonly:"","model-value":4})}const A=v(H,[["render",P]]),E={};function G(d,t){return c(),_(m,{clearable:""})}const q=v(E,[["render",G]]),J={class:"font-weight-medium mb-0"},K={__name:"DemoRatingLength",setup(d){const t=f(5),l=f(2);return(i,n)=>(c(),V(y,null,[n[2]||(n[2]=a("div",{class:"text-caption"}," Custom length ",-1)),e(k,{modelValue:s(t),"onUpdate:modelValue":n[0]||(n[0]=g=>u(t)?t.value=g:null),min:1,max:7},null,8,["modelValue"]),e(m,{modelValue:s(l),"onUpdate:modelValue":n[1]||(n[1]=g=>u(l)?l.value=g:null),length:s(t)},null,8,["modelValue","length"]),a("p",J," Model: "+S(s(l)),1)],64))}},O={class:"d-flex flex-column"},Q={__name:"DemoRatingSize",setup(d){const t=f(4);return(l,i)=>(c(),V("div",O,[e(m,{modelValue:s(t),"onUpdate:modelValue":i[0]||(i[0]=n=>u(t)?t.value=n:null),size:"x-small"},null,8,["modelValue"]),e(m,{modelValue:s(t),"onUpdate:modelValue":i[1]||(i[1]=n=>u(t)?t.value=n:null),size:"small"},null,8,["modelValue"]),e(m,{modelValue:s(t),"onUpdate:modelValue":i[2]||(i[2]=n=>u(t)?t.value=n:null)},null,8,["modelValue"]),e(m,{modelValue:s(t),"onUpdate:modelValue":i[3]||(i[3]=n=>u(t)?t.value=n:null),size:"large"},null,8,["modelValue"]),e(m,{modelValue:s(t),"onUpdate:modelValue":i[4]||(i[4]=n=>u(t)?t.value=n:null),size:"x-large"},null,8,["modelValue"])]))}},W={class:"d-flex flex-column"},X={__name:"DemoRatingColors",setup(d){const t=f(4),l=["primary","secondary","success","info","warning","error"];return(i,n)=>(c(),V("div",W,[(c(),V(y,null,j(l,g=>e(m,{key:g,modelValue:s(t),"onUpdate:modelValue":n[0]||(n[0]=R=>u(t)?t.value=R:null),color:g},null,8,["modelValue","color"])),64))]))}},Y={};function Z(d,t){return c(),_(m,{density:"compact"})}const ee=v(Y,[["render",Z]]),te={};function ne(d,t){return c(),_(m)}const le=v(te,[["render",ne]]),oe={ts:`<template>
  <VRating />
</template>
`,js:`<template>
  <VRating />
</template>
`},ae={ts:`<template>
  <VRating clearable />
</template>
`,js:`<template>
  <VRating clearable />
</template>
`},se={ts:`<script lang="ts" setup>
const rating = ref(4)
const ratingColors = ['primary', 'secondary', 'success', 'info', 'warning', 'error']
<\/script>

<template>
  <div class="d-flex flex-column">
    <VRating
      v-for="color in ratingColors"
      :key="color"
      v-model="rating"
      :color="color"
    />
  </div>
</template>
`,js:`<script setup>
const rating = ref(4)

const ratingColors = [
  'primary',
  'secondary',
  'success',
  'info',
  'warning',
  'error',
]
<\/script>

<template>
  <div class="d-flex flex-column">
    <VRating
      v-for="color in ratingColors"
      :key="color"
      v-model="rating"
      :color="color"
    />
  </div>
</template>
`},ie={ts:`<template>
  <VRating density="compact" />
</template>
`,js:`<template>
  <VRating density="compact" />
</template>
`},re={ts:`<template>
  <VRating hover />
</template>
`,js:`<template>
  <VRating hover />
</template>
`},me={ts:`<script lang="ts" setup>
const rating = ref(4.5)
<\/script>

<template>
  <VRating
    v-model="rating"
    half-increments
    hover
    color="secondary"
  />
</template>
`,js:`<script setup>
const rating = ref(4.5)
<\/script>

<template>
  <VRating
    v-model="rating"
    half-increments
    hover
    color="secondary"
  />
</template>
`},ce={ts:`<script lang="ts" setup>
const rating = ref(4.5)
<\/script>

<template>
  <VRating v-model="rating">
    <template #item="props">
      <VIcon
        v-bind="props"
        :size="25"
        :color="props.isFilled ? 'success' : 'secondary'"
        class="me-3"
        :icon="props.isFilled ? 'ri-emotion-happy-line' : 'ri-emotion-unhappy-line'"
      />
    </template>
  </VRating>
</template>
`,js:`<script setup>
const rating = ref(4.5)
<\/script>

<template>
  <VRating v-model="rating">
    <template #item="props">
      <VIcon
        v-bind="props"
        :size="25"
        :color="props.isFilled ? 'success' : 'secondary'"
        class="me-3"
        :icon="props.isFilled ? 'ri-emotion-happy-line' : 'ri-emotion-unhappy-line'"
      />
    </template>
  </VRating>
</template>
`},de={ts:`<script lang="ts" setup>
const length = ref(5)
const rating = ref(2)
<\/script>

<template>
  <div class="text-caption">
    Custom length
  </div>

  <VSlider
    v-model="length"
    :min="1"
    :max="7"
  />

  <VRating
    v-model="rating"
    :length="length"
  />
  <p class="font-weight-medium mb-0">
    Model: {{ rating }}
  </p>
</template>
`,js:`<script setup>
const length = ref(5)
const rating = ref(2)
<\/script>

<template>
  <div class="text-caption">
    Custom length
  </div>

  <VSlider
    v-model="length"
    :min="1"
    :max="7"
  />

  <VRating
    v-model="rating"
    :length="length"
  />
  <p class="font-weight-medium mb-0">
    Model: {{ rating }}
  </p>
</template>
`},pe={ts:`<template>
  <VRating
    readonly
    :model-value="4"
  />
</template>
`,js:`<template>
  <VRating
    readonly
    :model-value="4"
  />
</template>
`},ue={ts:`<script lang="ts" setup>
const rating = ref(4)
<\/script>

<template>
  <div class="d-flex flex-column">
    <VRating
      v-model="rating"
      size="x-small"
    />

    <VRating
      v-model="rating"
      size="small"
    />

    <VRating v-model="rating" />

    <VRating
      v-model="rating"
      size="large"
    />

    <VRating
      v-model="rating"
      size="x-large"
    />
  </div>
</template>
`,js:`<script setup>
const rating = ref(4)
<\/script>

<template>
  <div class="d-flex flex-column">
    <VRating
      v-model="rating"
      size="x-small"
    />

    <VRating
      v-model="rating"
      size="small"
    />

    <VRating v-model="rating" />

    <VRating
      v-model="rating"
      size="large"
    />

    <VRating
      v-model="rating"
      size="x-large"
    />
  </div>
</template>
`},je={__name:"rating",setup(d){return(t,l)=>{const i=le,n=F,g=ee,R=X,h=Q,x=K,b=q,z=A,C=N,w=T,D=B;return c(),_(I,{class:"match-height"},{default:o(()=>[e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Basic",code:s(oe)},{default:o(()=>[l[0]||(l[0]=a("p",null,[r("The "),a("code",null,"v-rating"),r(" component provides a simple interface for gathering user feedback.")],-1)),e(i)]),_:1,__:[0]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Density",code:s(ie)},{default:o(()=>[l[1]||(l[1]=a("p",null,[r("Control the space occupied by "),a("code",null,"v-rating"),r(" items using the "),a("code",null,"density"),r(" prop.")],-1)),e(g)]),_:1,__:[1]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Colors",code:s(se)},{default:o(()=>[l[2]||(l[2]=a("p",null,[r("The "),a("code",null,"v-rating"),r(" component can be colored as you want, you can set both selected and not selected colors.")],-1)),e(R)]),_:1,__:[2]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Size",code:s(ue)},{default:o(()=>[l[3]||(l[3]=a("p",null,[r("Utilize the same sizing classes available in "),a("code",null,"v-icon"),r(" or provide your own with the "),a("code",null,"size"),r(" prop.")],-1)),e(h)]),_:1,__:[3]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Length",code:s(de)},{default:o(()=>[l[4]||(l[4]=a("p",null,[r("Change the number of items by modifying the the "),a("code",null,"length"),r(" prop.")],-1)),e(x)]),_:1,__:[4]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Clearable",code:s(ae)},{default:o(()=>[l[5]||(l[5]=a("p",null,[r("Use "),a("code",null,"clearable"),r(" prop to allows for the component to be cleared. Triggers when the icon containing the current value is clicked.")],-1)),e(b)]),_:1,__:[5]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Readonly",code:s(pe)},{default:o(()=>[l[6]||(l[6]=a("p",null,[r("For ratings that are not meant to be changed you can use "),a("code",null,"readonly"),r(" prop.")],-1)),e(z)]),_:1,__:[6]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Hover",code:s(re)},{default:o(()=>[l[7]||(l[7]=a("p",null,"Provides visual feedback when hovering over icons",-1)),e(C)]),_:1,__:[7]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Incremented",code:s(me)},{default:o(()=>[l[8]||(l[8]=a("p",null,[r("The "),a("code",null,"half-increments"),r(" prop increases the granularity of the ratings, allow for .5 values as well.")],-1)),e(w)]),_:1,__:[8]},8,["code"])]),_:1}),e(p,{cols:"12",md:"6"},{default:o(()=>[e(n,{title:"Item slot",code:s(ce)},{default:o(()=>[l[9]||(l[9]=a("p",null,"Slots enable advanced customization possibilities and provide you with more freedom in how you display the rating.",-1)),e(D)]),_:1,__:[9]},8,["code"])]),_:1})]),_:1})}}};export{je as default};
