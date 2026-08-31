import{V as c}from"./VPagination-BwOhXtso.js";import{r as d,o as g,c as v,b as n,aG as m,l as s,f as P,e as r,d as o,s as a}from"./main-B505sglD.js";import{_ as y}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{_ as w}from"./AppCardCode-DqUfaeYv.js";import{V as C,a as _}from"./VRow-CHajFKF9.js";import"./vue3-perfect-scrollbar-BdpqDEIr.js";import"./VCard-DpboESki.js";import"./createSimpleFunctional-DrJf4CdD.js";import"./VAvatar-Dr1UxUFV.js";import"./VImg-CdIuIYCs.js";import"./VCardText-CICCFvtq.js";import"./VDivider-CM5e32kb.js";/* empty css              */const z={class:"d-flex flex-column gap-6"},S={__name:"DemoPaginationSize",setup(f){const e=d(1),t=d(2),l=d(3);return(i,u)=>(g(),v("div",z,[n(c,{modelValue:s(e),"onUpdate:modelValue":u[0]||(u[0]=p=>m(e)?e.value=p:null),length:7,size:"small"},null,8,["modelValue"]),n(c,{modelValue:s(t),"onUpdate:modelValue":u[1]||(u[1]=p=>m(t)?t.value=p:null),length:7},null,8,["modelValue"]),n(c,{modelValue:s(l),"onUpdate:modelValue":u[2]||(u[2]=p=>m(l)?l.value=p:null),length:7,size:"large"},null,8,["modelValue"])]))}},j={class:"d-flex flex-column gap-6"},I={__name:"DemoPaginationColor",setup(f){const e=d(1),t=d(2),l=d(3);return(i,u)=>(g(),v("div",j,[n(c,{modelValue:s(e),"onUpdate:modelValue":u[0]||(u[0]=p=>m(e)?e.value=p:null),length:7,color:"success"},null,8,["modelValue"]),n(c,{modelValue:s(t),"onUpdate:modelValue":u[1]||(u[1]=p=>m(t)?t.value=p:null),length:7,color:"error"},null,8,["modelValue"]),n(c,{modelValue:s(l),"onUpdate:modelValue":u[2]||(u[2]=p=>m(l)?l.value=p:null),length:7,color:"info"},null,8,["modelValue"])]))}},T={__name:"DemoPaginationTotalVisible",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),length:15,"total-visible":t.$vuetify.display.mdAndUp?4:t.$vuetify.display.sm?2:1},null,8,["modelValue","total-visible"]))}},B={__name:"DemoPaginationLength",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),length:15},null,8,["modelValue"]))}},E={__name:"DemoPaginationIcons",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),length:5,"prev-icon":"ri-arrow-left-s-fill","next-icon":"ri-arrow-right-s-fill"},null,8,["modelValue"]))}},O={};function A(f,e){return g(),P(c,{length:5,disabled:""})}const k=y(O,[["render",A]]),L={__name:"DemoPaginationOutlineCircle",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),variant:"outlined",length:5,rounded:"circle"},null,8,["modelValue"]))}},N={__name:"DemoPaginationCircle",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),length:5,rounded:"circle"},null,8,["modelValue"]))}},R={__name:"DemoPaginationOutline",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),variant:"outlined",length:5},null,8,["modelValue"]))}},G={__name:"DemoPaginationBasic",setup(f){const e=d(1);return(t,l)=>(g(),P(c,{modelValue:s(e),"onUpdate:modelValue":l[0]||(l[0]=i=>m(e)?e.value=i:null),length:5},null,8,["modelValue"]))}},Y={ts:`<script lang="ts" setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
  />
</template>
`},q={ts:`<script lang="ts" setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
    rounded="circle"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
    rounded="circle"
  />
</template>
`},F={ts:`<script setup lang="ts">
const pageSuccess = ref(1)
const pageError = ref(2)
const pageInfo = ref(3)
<\/script>

<template>
  <div class="d-flex flex-column gap-6">
    <VPagination
      v-model="pageSuccess"
      :length="7"
      color="success"
    />
    <VPagination
      v-model="pageError"
      :length="7"
      color="error"
    />
    <VPagination
      v-model="pageInfo"
      :length="7"
      color="info"
    />
  </div>
</template>
`,js:`<script setup>
const pageSuccess = ref(1)
const pageError = ref(2)
const pageInfo = ref(3)
<\/script>

<template>
  <div class="d-flex flex-column gap-6">
    <VPagination
      v-model="pageSuccess"
      :length="7"
      color="success"
    />
    <VPagination
      v-model="pageError"
      :length="7"
      color="error"
    />
    <VPagination
      v-model="pageInfo"
      :length="7"
      color="info"
    />
  </div>
</template>
`},H={ts:`<template>
  <VPagination
    :length="5"
    disabled
  />
</template>
`,js:`<template>
  <VPagination
    :length="5"
    disabled
  />
</template>
`},J={ts:`<script lang="ts" setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
    prev-icon="ri-arrow-left-s-fill"
    next-icon="ri-arrow-right-s-fill"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="5"
    prev-icon="ri-arrow-left-s-fill"
    next-icon="ri-arrow-right-s-fill"
  />
</template>
`},K={ts:`<script lang="ts" setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="15"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="15"
  />
</template>
`},M={ts:`<script setup lang="ts">
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    variant="outlined"
    :length="5"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    variant="outlined"
    :length="5"
  />
</template>
`},Q={ts:`<script setup lang="ts">
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    variant="outlined"
    :length="5"
    rounded="circle"
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    variant="outlined"
    :length="5"
    rounded="circle"
  />
</template>
`},W={ts:`<script setup lang="ts">
const xSmallPagination = ref(1)
const smallPagination = ref(2)
const largePagination = ref(3)
<\/script>

<template>
  <div class="d-flex flex-column gap-6">
    <VPagination
      v-model="xSmallPagination"
      :length="7"
      size="small"
    />
    <VPagination
      v-model="smallPagination"
      :length="7"
    />
    <VPagination
      v-model="largePagination"
      :length="7"
      size="large"
    />
  </div>
</template>
`,js:`<script setup>
const xSmallPagination = ref(1)
const smallPagination = ref(2)
const largePagination = ref(3)
<\/script>

<template>
  <div class="d-flex flex-column gap-6">
    <VPagination
      v-model="xSmallPagination"
      :length="7"
      size="small"
    />
    <VPagination
      v-model="smallPagination"
      :length="7"
    />
    <VPagination
      v-model="largePagination"
      :length="7"
      size="large"
    />
  </div>
</template>
`},X={ts:`<script lang="ts" setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="15"
    :total-visible="$vuetify.display.mdAndUp ? 4 : $vuetify.display.sm ? 2 : 1 "
  />
</template>
`,js:`<script setup>
const currentPage = ref(1)
<\/script>

<template>
  <VPagination
    v-model="currentPage"
    :length="15"
    :total-visible="$vuetify.display.mdAndUp ? 4 : $vuetify.display.sm ? 2 : 1 "
  />
</template>
`},de={__name:"pagination",setup(f){return(e,t)=>{const l=G,i=w,u=R,p=N,V=L,h=k,x=E,b=B,D=T,U=I,$=S;return g(),P(C,{class:"match-height"},{default:r(()=>[n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Basic",code:s(Y)},{default:r(()=>[t[0]||(t[0]=o("p",null,[a("The "),o("code",null,"v-pagination"),a(" component is used to separate long sets of data.")],-1)),n(l)]),_:1,__:[0]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Outline",code:s(M)},{default:r(()=>[t[1]||(t[1]=o("p",null,[a("The "),o("code",null,"variant='outline'"),a(" prop is used to give outline to pagination item.")],-1)),n(u)]),_:1,__:[1]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Circle",code:s(q)},{default:r(()=>[t[2]||(t[2]=o("p",null,[a("The "),o("code",null,"rounded"),a(" prop allows you to render pagination buttons with alternative styles.")],-1)),n(p)]),_:1,__:[2]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Outline Circle",code:s(Q)},{default:r(()=>[t[3]||(t[3]=o("p",null,[a("The "),o("code",null,"variant='outline'"),a(" and "),o("code",null,"rounded"),a(" prop is used to give rounded outline to pagination item.")],-1)),n(V)]),_:1,__:[3]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Disabled",code:s(H)},{default:r(()=>[t[4]||(t[4]=o("p",null,[a("Pagination items can be manually deactivated using the "),o("code",null,"disabled"),a(" prop.")],-1)),n(h)]),_:1,__:[4]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Icons",code:s(J)},{default:r(()=>[t[5]||(t[5]=o("p",null,[a("Previous and next page icons can be customized with the "),o("code",null,"prev-icon"),a(" and "),o("code",null,"next-icon"),a(" props.")],-1)),n(x)]),_:1,__:[5]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Length",code:s(K)},{default:r(()=>[t[6]||(t[6]=o("p",null,[a("Using the "),o("code",null,"length"),a(" prop you can set the length of "),o("code",null,"v-pagination"),a(", if the number of page buttons exceeds the parent container, it will truncate the list.")],-1)),n(b)]),_:1,__:[6]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Total visible",code:s(X)},{default:r(()=>[t[7]||(t[7]=o("p",null,[a("You can also manually set the maximum number of visible page buttons with the "),o("code",null,"total-visible"),a(" prop.")],-1)),n(D)]),_:1,__:[7]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Color",code:s(F)},{default:r(()=>[t[8]||(t[8]=o("p",null,[a("Use "),o("code",null,"color"),a(" prop for create different color pagination.")],-1)),n(U)]),_:1,__:[8]},8,["code"])]),_:1}),n(_,{cols:"12",md:"6"},{default:r(()=>[n(i,{title:"Size",code:s(W)},{default:r(()=>[t[9]||(t[9]=o("p",null,[a("Use "),o("code",null,"size"),a(" prop to sets the height and width of the component. Default unit is px. Can also use the following predefined sizes: "),o("strong",null,"x-small"),a(", "),o("strong",null,"small"),a(", "),o("strong",null,"default"),a(", "),o("strong",null,"large"),a(", and "),o("strong",null,"x-large"),a(".")],-1)),n($)]),_:1,__:[9]},8,["code"])]),_:1})]),_:1})}}};export{de as default};
