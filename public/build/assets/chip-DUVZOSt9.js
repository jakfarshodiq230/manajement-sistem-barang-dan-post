import{V as P,a as E,b as w,d as W}from"./VList-B1zIe8dw.js";import{V as $}from"./VListItemAction-IcvMVWfE.js";import{r as C,a as L,o as c,f,e as r,b as e,s as o,a0 as M,aT as z,aU as A,aG as x,l as a,c as h,d as n,b1 as T,b2 as j,b3 as B,b4 as N,h as _}from"./main-BdxVNj0W.js";import{V as l}from"./VChip-BBy0MQja.js";import{V as J}from"./VMenu-0DfAB6z2.js";import{V as U}from"./VCombobox-BzRsCMIw.js";import{_ as b}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{_ as F}from"./AppCardCode-50yCyEpV.js";import{V as R,a as V}from"./VRow-vrTmU0A8.js";import"./ssrBoot-BZoYsp_l.js";import"./createSimpleFunctional-DX9efoFk.js";import"./VAvatar-C-Xdvx7y.js";import"./VImg-CJH8Rt0U.js";import"./VDivider-iqf11oAx.js";import"./VSlideGroup-CxI0OGzF.js";import"./dialog-transition-n2jgi_y4.js";import"./VSelect-DxJzOT9N.js";import"./VTextField-fBFfJByD.js";/* empty css                   */import"./VCounter-CgQz9E3-.js";import"./VField-K8nsQEDh.js";import"./form-Bqx4gi5N.js";import"./VInput-B5qWrE6y.js";import"./VCheckboxBtn-sA_-PWGR.js";import"./VSelectionControl-DxRC1VlK.js";import"./filter-h4lMBIHE.js";import"./vue3-perfect-scrollbar-64k2zWlB.js";import"./VCard-XkorCH1G.js";import"./VCardText-B2uwaTKx.js";/* empty css              */const Y={__name:"DemoChipExpandable",setup(m){const i=C(!1);return(t,d)=>{const p=L("IconBtn");return c(),f(J,{modelValue:a(i),"onUpdate:modelValue":d[1]||(d[1]=u=>x(i)?i.value=u:null),transition:"scale-transition"},{activator:r(({props:u})=>[e(l,z(A(u)),{default:r(()=>d[2]||(d[2]=[o(" VueJS ")])),_:2,__:[2]},1040)]),default:r(()=>[e(P,null,{default:r(()=>[e(E,null,{append:r(()=>[e($,{class:"ms-1"},{default:r(()=>[e(p,{onClick:d[0]||(d[0]=u=>i.value=!1)},{default:r(()=>[e(M,{size:"20",icon:"ri-close-line"})]),_:1})]),_:1})]),default:r(()=>[e(w,null,{default:r(()=>d[3]||(d[3]=[o("VueJS")])),_:1,__:[3]}),e(W,null,{default:r(()=>d[4]||(d[4]=[o("The Progressive JavaScript Framework")])),_:1,__:[4]})]),_:1})]),_:1})]),_:1},8,["modelValue"])}}},O={__name:"DemoChipInSelects",setup(m){const i=C(["Programming","Playing games","Sleeping"]),t=C(["Streaming","Eating","Programming","Playing games","Sleeping"]);return(d,p)=>(c(),f(U,{modelValue:a(i),"onUpdate:modelValue":p[0]||(p[0]=u=>x(i)?i.value=u:null),chips:"",clearable:"",multiple:"","closable-chips":"","clear-icon":"ri-close-circle-line",items:a(t),label:"Your favorite hobbies","prepend-icon":"ri-filter-3-line"},null,8,["modelValue","items"]))}},G={},q={class:"demo-space-x"};function H(m,i){return c(),h("div",q,[e(l,{size:"x-small"},{default:r(()=>i[0]||(i[0]=[o(" x-small chip ")])),_:1,__:[0]}),e(l,{size:"small"},{default:r(()=>i[1]||(i[1]=[o(" small chip ")])),_:1,__:[1]}),e(l,null,{default:r(()=>i[2]||(i[2]=[o("Default")])),_:1,__:[2]}),e(l,{size:"large"},{default:r(()=>i[3]||(i[3]=[o(" large chip ")])),_:1,__:[3]}),e(l,{size:"x-large"},{default:r(()=>i[4]||(i[4]=[o(" x-large chip ")])),_:1,__:[4]})])}const K=b(G,[["render",H]]),Q={class:"demo-space-x"},X={__name:"DemoChipWithAvatar",setup(m){return(i,t)=>(c(),h("div",Q,[e(l,{"prepend-avatar":a(T)},{default:r(()=>t[0]||(t[0]=[n("span",null,"Chip",-1)])),_:1,__:[0]},8,["prepend-avatar"]),e(l,{"prepend-avatar":a(j)},{default:r(()=>t[1]||(t[1]=[n("span",null,"Darcy Nooser",-1)])),_:1,__:[1]},8,["prepend-avatar"]),e(l,{"prepend-avatar":a(B)},{default:r(()=>t[2]||(t[2]=[n("span",null,"Felicia Risker",-1)])),_:1,__:[2]},8,["prepend-avatar"]),e(l,{"prepend-avatar":a(N)},{default:r(()=>t[3]||(t[3]=[n("span",null,"Minnie Mostly",-1)])),_:1,__:[3]},8,["prepend-avatar"])]))}},Z={},ii={class:"demo-space-x"};function ei(m,i){return c(),h("div",ii,[e(l,{"prepend-icon":"ri-user-line"},{default:r(()=>i[0]||(i[0]=[o(" Account ")])),_:1,__:[0]}),e(l,{color:"primary","prepend-icon":"ri-star-line"},{default:r(()=>i[1]||(i[1]=[o(" Premium ")])),_:1,__:[1]}),e(l,{color:"secondary","prepend-icon":"ri-cake-line"},{default:r(()=>i[2]||(i[2]=[o(" 1 Year ")])),_:1,__:[2]}),e(l,{color:"success","prepend-icon":"ri-notification-line"},{default:r(()=>i[3]||(i[3]=[o(" Notification ")])),_:1,__:[3]}),e(l,{color:"info","prepend-icon":"ri-message-line"},{default:r(()=>i[4]||(i[4]=[o(" Message ")])),_:1,__:[4]}),e(l,{color:"warning","prepend-icon":"ri-error-warning-line"},{default:r(()=>i[5]||(i[5]=[o(" Warning ")])),_:1,__:[5]}),e(l,{color:"error","prepend-icon":"ri-error-warning-line"},{default:r(()=>i[6]||(i[6]=[o(" Error ")])),_:1,__:[6]})])}const ri=b(Z,[["render",ei]]),oi={class:"demo-space-x"},li={__name:"DemoChipClosable",setup(m){const i=C(!0),t=C(!0),d=C(!0),p=C(!0),u=C(!0),g=C(!0),y=C(!0);return(S,s)=>(c(),h("div",oi,[a(i)?(c(),f(l,{key:0,closable:"","onClick:close":s[0]||(s[0]=v=>i.value=!a(i))},{default:r(()=>s[7]||(s[7]=[o(" Default ")])),_:1,__:[7]})):_("",!0),a(t)?(c(),f(l,{key:1,closable:"",color:"primary","onClick:close":s[1]||(s[1]=v=>t.value=!a(t))},{default:r(()=>s[8]||(s[8]=[o(" Primary ")])),_:1,__:[8]})):_("",!0),a(d)?(c(),f(l,{key:2,closable:"",color:"secondary","onClick:close":s[2]||(s[2]=v=>d.value=!a(d))},{default:r(()=>s[9]||(s[9]=[o(" Secondary ")])),_:1,__:[9]})):_("",!0),a(p)?(c(),f(l,{key:3,closable:"",color:"success","onClick:close":s[3]||(s[3]=v=>p.value=!a(p))},{default:r(()=>s[10]||(s[10]=[o(" Success ")])),_:1,__:[10]})):_("",!0),a(u)?(c(),f(l,{key:4,closable:"",color:"info","onClick:close":s[4]||(s[4]=v=>u.value=!a(u))},{default:r(()=>s[11]||(s[11]=[o(" Info ")])),_:1,__:[11]})):_("",!0),a(g)?(c(),f(l,{key:5,closable:"",color:"warning","onClick:close":s[5]||(s[5]=v=>g.value=!a(g))},{default:r(()=>s[12]||(s[12]=[o(" Warning ")])),_:1,__:[12]})):_("",!0),a(y)?(c(),f(l,{key:6,closable:"",color:"error","onClick:close":s[6]||(s[6]=v=>y.value=!a(y))},{default:r(()=>s[13]||(s[13]=[o(" Error ")])),_:1,__:[13]})):_("",!0)]))}},ti={},ai={class:"demo-space-x"};function si(m,i){return c(),h("div",ai,[e(l,{label:""},{default:r(()=>i[0]||(i[0]=[o(" Default ")])),_:1,__:[0]}),e(l,{label:"",color:"primary"},{default:r(()=>i[1]||(i[1]=[o(" Primary ")])),_:1,__:[1]}),e(l,{label:"",color:"secondary"},{default:r(()=>i[2]||(i[2]=[o(" Secondary ")])),_:1,__:[2]}),e(l,{label:"",color:"success"},{default:r(()=>i[3]||(i[3]=[o(" Success ")])),_:1,__:[3]}),e(l,{label:"",color:"info"},{default:r(()=>i[4]||(i[4]=[o(" Info ")])),_:1,__:[4]}),e(l,{label:"",color:"warning"},{default:r(()=>i[5]||(i[5]=[o(" Warning ")])),_:1,__:[5]}),e(l,{label:"",color:"error"},{default:r(()=>i[6]||(i[6]=[o(" Error ")])),_:1,__:[6]})])}const ni=b(ti,[["render",si]]),pi={},ci={class:"demo-space-x"};function di(m,i){return c(),h("div",ci,[e(l,{variant:"outlined"},{default:r(()=>i[0]||(i[0]=[o(" Default ")])),_:1,__:[0]}),e(l,{color:"primary",variant:"outlined"},{default:r(()=>i[1]||(i[1]=[o(" Primary ")])),_:1,__:[1]}),e(l,{color:"secondary",variant:"outlined"},{default:r(()=>i[2]||(i[2]=[o(" Secondary ")])),_:1,__:[2]}),e(l,{color:"success",variant:"outlined"},{default:r(()=>i[3]||(i[3]=[o(" Success ")])),_:1,__:[3]}),e(l,{color:"info",variant:"outlined"},{default:r(()=>i[4]||(i[4]=[o(" Info ")])),_:1,__:[4]}),e(l,{color:"warning",variant:"outlined"},{default:r(()=>i[5]||(i[5]=[o(" Warning ")])),_:1,__:[5]}),e(l,{color:"error",variant:"outlined"},{default:r(()=>i[6]||(i[6]=[o(" Error ")])),_:1,__:[6]})])}const ui=b(pi,[["render",di]]),mi={},Vi={class:"demo-space-x"};function Ci(m,i){return c(),h("div",Vi,[e(l,{variant:"elevated"},{default:r(()=>i[0]||(i[0]=[o(" Default ")])),_:1,__:[0]}),e(l,{color:"primary",variant:"elevated"},{default:r(()=>i[1]||(i[1]=[o(" Primary ")])),_:1,__:[1]}),e(l,{color:"secondary",variant:"elevated"},{default:r(()=>i[2]||(i[2]=[o(" Secondary ")])),_:1,__:[2]}),e(l,{color:"success",variant:"elevated"},{default:r(()=>i[3]||(i[3]=[o(" Success ")])),_:1,__:[3]}),e(l,{color:"info",variant:"elevated"},{default:r(()=>i[4]||(i[4]=[o(" Info ")])),_:1,__:[4]}),e(l,{color:"warning",variant:"elevated"},{default:r(()=>i[5]||(i[5]=[o(" Warning ")])),_:1,__:[5]}),e(l,{color:"error",variant:"elevated"},{default:r(()=>i[6]||(i[6]=[o(" Error ")])),_:1,__:[6]})])}const fi=b(mi,[["render",Ci]]),vi={},hi={class:"demo-space-x"};function _i(m,i){return c(),h("div",hi,[e(l,null,{default:r(()=>i[0]||(i[0]=[o(" Default ")])),_:1,__:[0]}),e(l,{color:"primary"},{default:r(()=>i[1]||(i[1]=[o(" Primary ")])),_:1,__:[1]}),e(l,{color:"secondary"},{default:r(()=>i[2]||(i[2]=[o(" Secondary ")])),_:1,__:[2]}),e(l,{color:"success"},{default:r(()=>i[3]||(i[3]=[o(" Success ")])),_:1,__:[3]}),e(l,{color:"info"},{default:r(()=>i[4]||(i[4]=[o(" Info ")])),_:1,__:[4]}),e(l,{color:"warning"},{default:r(()=>i[5]||(i[5]=[o(" Warning ")])),_:1,__:[5]}),e(l,{color:"error"},{default:r(()=>i[6]||(i[6]=[o(" Error ")])),_:1,__:[6]})])}const bi=b(vi,[["render",_i]]),gi={ts:`<script lang="ts" setup>
const isDefaultChipVisible = ref(true)
const isPrimaryChipVisible = ref(true)
const isSecondaryChipVisible = ref(true)
const isSuccessChipVisible = ref(true)
const isInfoChipVisible = ref(true)
const isWarningChipVisible = ref(true)
const isErrorChipVisible = ref(true)
<\/script>

<template>
  <div class="demo-space-x">
    <VChip
      v-if="isDefaultChipVisible"
      closable
      @click:close="isDefaultChipVisible = !isDefaultChipVisible"
    >
      Default
    </VChip>

    <VChip
      v-if="isPrimaryChipVisible"
      closable
      color="primary"
      @click:close="isPrimaryChipVisible = !isPrimaryChipVisible"
    >
      Primary
    </VChip>

    <VChip
      v-if="isSecondaryChipVisible"
      closable
      color="secondary"
      @click:close="isSecondaryChipVisible = !isSecondaryChipVisible"
    >
      Secondary
    </VChip>

    <VChip
      v-if="isSuccessChipVisible"
      closable
      color="success"
      @click:close="isSuccessChipVisible = !isSuccessChipVisible"
    >
      Success
    </VChip>

    <VChip
      v-if="isInfoChipVisible"
      closable
      color="info"
      @click:close="isInfoChipVisible = !isInfoChipVisible"
    >
      Info
    </VChip>

    <VChip
      v-if="isWarningChipVisible"
      closable
      color="warning"
      @click:close="isWarningChipVisible = !isWarningChipVisible"
    >
      Warning
    </VChip>

    <VChip
      v-if="isErrorChipVisible"
      closable
      color="error"
      @click:close="isErrorChipVisible = !isErrorChipVisible"
    >
      Error
    </VChip>
  </div>
</template>
`,js:`<script setup>
const isDefaultChipVisible = ref(true)
const isPrimaryChipVisible = ref(true)
const isSecondaryChipVisible = ref(true)
const isSuccessChipVisible = ref(true)
const isInfoChipVisible = ref(true)
const isWarningChipVisible = ref(true)
const isErrorChipVisible = ref(true)
<\/script>

<template>
  <div class="demo-space-x">
    <VChip
      v-if="isDefaultChipVisible"
      closable
      @click:close="isDefaultChipVisible = !isDefaultChipVisible"
    >
      Default
    </VChip>

    <VChip
      v-if="isPrimaryChipVisible"
      closable
      color="primary"
      @click:close="isPrimaryChipVisible = !isPrimaryChipVisible"
    >
      Primary
    </VChip>

    <VChip
      v-if="isSecondaryChipVisible"
      closable
      color="secondary"
      @click:close="isSecondaryChipVisible = !isSecondaryChipVisible"
    >
      Secondary
    </VChip>

    <VChip
      v-if="isSuccessChipVisible"
      closable
      color="success"
      @click:close="isSuccessChipVisible = !isSuccessChipVisible"
    >
      Success
    </VChip>

    <VChip
      v-if="isInfoChipVisible"
      closable
      color="info"
      @click:close="isInfoChipVisible = !isInfoChipVisible"
    >
      Info
    </VChip>

    <VChip
      v-if="isWarningChipVisible"
      closable
      color="warning"
      @click:close="isWarningChipVisible = !isWarningChipVisible"
    >
      Warning
    </VChip>

    <VChip
      v-if="isErrorChipVisible"
      closable
      color="error"
      @click:close="isErrorChipVisible = !isErrorChipVisible"
    >
      Error
    </VChip>
  </div>
</template>
`},yi={ts:`<template>
  <div class="demo-space-x">
    <VChip>
      Default
    </VChip>

    <VChip color="primary">
      Primary
    </VChip>

    <VChip color="secondary">
      Secondary
    </VChip>

    <VChip color="success">
      Success
    </VChip>

    <VChip color="info">
      Info
    </VChip>

    <VChip color="warning">
      Warning
    </VChip>

    <VChip color="error">
      Error
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip>
      Default
    </VChip>

    <VChip color="primary">
      Primary
    </VChip>

    <VChip color="secondary">
      Secondary
    </VChip>

    <VChip color="success">
      Success
    </VChip>

    <VChip color="info">
      Info
    </VChip>

    <VChip color="warning">
      Warning
    </VChip>

    <VChip color="error">
      Error
    </VChip>
  </div>
</template>
`},Si={ts:`<template>
  <div class="demo-space-x">
    <VChip variant="elevated">
      Default
    </VChip>

    <VChip
      color="primary"
      variant="elevated"
    >
      Primary
    </VChip>

    <VChip
      color="secondary"
      variant="elevated"
    >
      Secondary
    </VChip>

    <VChip
      color="success"
      variant="elevated"
    >
      Success
    </VChip>

    <VChip
      color="info"
      variant="elevated"
    >
      Info
    </VChip>

    <VChip
      color="warning"
      variant="elevated"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      variant="elevated"
    >
      Error
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip variant="elevated">
      Default
    </VChip>

    <VChip
      color="primary"
      variant="elevated"
    >
      Primary
    </VChip>

    <VChip
      color="secondary"
      variant="elevated"
    >
      Secondary
    </VChip>

    <VChip
      color="success"
      variant="elevated"
    >
      Success
    </VChip>

    <VChip
      color="info"
      variant="elevated"
    >
      Info
    </VChip>

    <VChip
      color="warning"
      variant="elevated"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      variant="elevated"
    >
      Error
    </VChip>
  </div>
</template>
`},xi={ts:`<script lang="ts" setup>
const isMenuVisible = ref(false)
<\/script>

<template>
  <VMenu
    v-model="isMenuVisible"
    transition="scale-transition"
  >
    <!-- v-menu activator -->
    <template #activator="{ props }">
      <VChip v-bind="props">
        VueJS
      </VChip>
    </template>

    <!-- v-menu list -->
    <VList>
      <VListItem>
        <VListItemTitle>VueJS</VListItemTitle>
        <VListItemSubtitle>The Progressive JavaScript Framework</VListItemSubtitle>

        <template #append>
          <VListItemAction class="ms-1">
            <IconBtn @click="isMenuVisible = false">
              <VIcon
                size="20"
                icon="ri-close-line"
              />
            </IconBtn>
          </VListItemAction>
        </template>
      </VListItem>
    </VList>
  </VMenu>
</template>
`,js:`<script setup>
const isMenuVisible = ref(false)
<\/script>

<template>
  <VMenu
    v-model="isMenuVisible"
    transition="scale-transition"
  >
    <!-- v-menu activator -->
    <template #activator="{ props }">
      <VChip v-bind="props">
        VueJS
      </VChip>
    </template>

    <!-- v-menu list -->
    <VList>
      <VListItem>
        <VListItemTitle>VueJS</VListItemTitle>
        <VListItemSubtitle>The Progressive JavaScript Framework</VListItemSubtitle>

        <template #append>
          <VListItemAction class="ms-1">
            <IconBtn @click="isMenuVisible = false">
              <VIcon
                size="20"
                icon="ri-close-line"
              />
            </IconBtn>
          </VListItemAction>
        </template>
      </VListItem>
    </VList>
  </VMenu>
</template>
`},Ii={ts:`<script lang="ts" setup>
const chips = ref(['Programming', 'Playing games', 'Sleeping'])
const items = ref(['Streaming', 'Eating', 'Programming', 'Playing games', 'Sleeping'])
<\/script>

<template>
  <VCombobox
    v-model="chips"
    chips
    clearable
    multiple
    closable-chips
    clear-icon="ri-close-circle-line"
    :items="items"
    label="Your favorite hobbies"
    prepend-icon="ri-filter-3-line"
  />
</template>
`,js:`<script setup>
const chips = ref([
  'Programming',
  'Playing games',
  'Sleeping',
])

const items = ref([
  'Streaming',
  'Eating',
  'Programming',
  'Playing games',
  'Sleeping',
])
<\/script>

<template>
  <VCombobox
    v-model="chips"
    chips
    clearable
    multiple
    closable-chips
    clear-icon="ri-close-circle-line"
    :items="items"
    label="Your favorite hobbies"
    prepend-icon="ri-filter-3-line"
  />
</template>
`},ki={ts:`<template>
  <div class="demo-space-x">
    <VChip label>
      Default
    </VChip>

    <VChip
      label
      color="primary"
    >
      Primary
    </VChip>

    <VChip
      label
      color="secondary"
    >
      Secondary
    </VChip>

    <VChip
      label
      color="success"
    >
      Success
    </VChip>

    <VChip
      label
      color="info"
    >
      Info
    </VChip>

    <VChip
      label
      color="warning"
    >
      Warning
    </VChip>

    <VChip
      label
      color="error"
    >
      Error
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip label>
      Default
    </VChip>

    <VChip
      label
      color="primary"
    >
      Primary
    </VChip>

    <VChip
      label
      color="secondary"
    >
      Secondary
    </VChip>

    <VChip
      label
      color="success"
    >
      Success
    </VChip>

    <VChip
      label
      color="info"
    >
      Info
    </VChip>

    <VChip
      label
      color="warning"
    >
      Warning
    </VChip>

    <VChip
      label
      color="error"
    >
      Error
    </VChip>
  </div>
</template>
`},Di={ts:`<template>
  <div class="demo-space-x">
    <VChip variant="outlined">
      Default
    </VChip>

    <VChip
      color="primary"
      variant="outlined"
    >
      Primary
    </VChip>

    <VChip
      color="secondary"
      variant="outlined"
    >
      Secondary
    </VChip>

    <VChip
      color="success"
      variant="outlined"
    >
      Success
    </VChip>

    <VChip
      color="info"
      variant="outlined"
    >
      Info
    </VChip>

    <VChip
      color="warning"
      variant="outlined"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      variant="outlined"
    >
      Error
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip variant="outlined">
      Default
    </VChip>

    <VChip
      color="primary"
      variant="outlined"
    >
      Primary
    </VChip>

    <VChip
      color="secondary"
      variant="outlined"
    >
      Secondary
    </VChip>

    <VChip
      color="success"
      variant="outlined"
    >
      Success
    </VChip>

    <VChip
      color="info"
      variant="outlined"
    >
      Info
    </VChip>

    <VChip
      color="warning"
      variant="outlined"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      variant="outlined"
    >
      Error
    </VChip>
  </div>
</template>
`},Pi={ts:`<template>
  <div class="demo-space-x">
    <VChip size="x-small">
      x-small chip
    </VChip>

    <VChip size="small">
      small chip
    </VChip>

    <VChip>Default</VChip>

    <VChip size="large">
      large chip
    </VChip>

    <VChip size="x-large">
      x-large chip
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip size="x-small">
      x-small chip
    </VChip>

    <VChip size="small">
      small chip
    </VChip>

    <VChip>Default</VChip>

    <VChip size="large">
      large chip
    </VChip>

    <VChip size="x-large">
      x-large chip
    </VChip>
  </div>
</template>
`},Ei={ts:`<script setup lang="ts">
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
<\/script>

<template>
  <div class="demo-space-x">
    <VChip :prepend-avatar="avatar1">
      <span>Chip</span>
    </VChip>

    <VChip :prepend-avatar="avatar2">
      <span>Darcy Nooser</span>
    </VChip>

    <VChip :prepend-avatar="avatar3">
      <span>Felicia Risker</span>
    </VChip>

    <VChip :prepend-avatar="avatar4">
      <span>Minnie Mostly</span>
    </VChip>
  </div>
</template>
`,js:`<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
<\/script>

<template>
  <div class="demo-space-x">
    <VChip :prepend-avatar="avatar1">
      <span>Chip</span>
    </VChip>

    <VChip :prepend-avatar="avatar2">
      <span>Darcy Nooser</span>
    </VChip>

    <VChip :prepend-avatar="avatar3">
      <span>Felicia Risker</span>
    </VChip>

    <VChip :prepend-avatar="avatar4">
      <span>Minnie Mostly</span>
    </VChip>
  </div>
</template>
`},wi={ts:`<template>
  <div class="demo-space-x">
    <VChip prepend-icon="ri-user-line">
      Account
    </VChip>

    <VChip
      color="primary"
      prepend-icon="ri-star-line"
    >
      Premium
    </VChip>

    <VChip
      color="secondary"
      prepend-icon="ri-cake-line"
    >
      1 Year
    </VChip>

    <VChip
      color="success"
      prepend-icon="ri-notification-line"
    >
      Notification
    </VChip>

    <VChip
      color="info"
      prepend-icon="ri-message-line"
    >
      Message
    </VChip>

    <VChip
      color="warning"
      prepend-icon="ri-error-warning-line"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      prepend-icon="ri-error-warning-line"
    >
      Error
    </VChip>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VChip prepend-icon="ri-user-line">
      Account
    </VChip>

    <VChip
      color="primary"
      prepend-icon="ri-star-line"
    >
      Premium
    </VChip>

    <VChip
      color="secondary"
      prepend-icon="ri-cake-line"
    >
      1 Year
    </VChip>

    <VChip
      color="success"
      prepend-icon="ri-notification-line"
    >
      Notification
    </VChip>

    <VChip
      color="info"
      prepend-icon="ri-message-line"
    >
      Message
    </VChip>

    <VChip
      color="warning"
      prepend-icon="ri-error-warning-line"
    >
      Warning
    </VChip>

    <VChip
      color="error"
      prepend-icon="ri-error-warning-line"
    >
      Error
    </VChip>
  </div>
</template>
`},se={__name:"chip",setup(m){return(i,t)=>{const d=bi,p=F,u=fi,g=ui,y=ni,S=li,s=ri,v=X,I=K,k=O,D=Y;return c(),f(R,{class:"match-height"},{default:r(()=>[e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Color",code:a(yi)},{default:r(()=>[t[0]||(t[0]=n("p",null,[o("Use "),n("code",null,"color"),o(" prop to change the background color of chips.")],-1)),e(d)]),_:1,__:[0]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Elevated",code:a(Si)},{default:r(()=>[t[1]||(t[1]=n("p",null,[o("Use "),n("code",null,"elevated"),o(" variant option to create filled chips.")],-1)),e(u)]),_:1,__:[1]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Outlined",code:a(Di)},{default:r(()=>[t[2]||(t[2]=n("p",null,[o("Use "),n("code",null,"outlined"),o(" variant option to create outline border chips.")],-1)),e(g)]),_:1,__:[2]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Label",code:a(ki)},{default:r(()=>[t[3]||(t[3]=n("p",null,[o("Label chips use the "),n("code",null,"v-card"),o(" border-radius. Use "),n("code",null,"label"),o(" prop to create label chips.")],-1)),e(y)]),_:1,__:[3]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Closable",code:a(gi)},{default:r(()=>[t[4]||(t[4]=n("p",null,[o("Closable chips can be controlled with a "),n("code",null,"v-model"),o(".")],-1)),e(S)]),_:1,__:[4]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"With Icon",code:a(wi)},{default:r(()=>[t[5]||(t[5]=n("p",null,"Chips can use text or any icon available in the Material Icons font library.",-1)),e(s)]),_:1,__:[5]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"With Avatar",code:a(Ei)},{default:r(()=>[t[6]||(t[6]=n("p",null,[o("Use "),n("code",null,"pill"),o(" prop to remove the "),n("code",null,"v-avatar"),o(" padding.")],-1)),e(v)]),_:1,__:[6]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Sizes",code:a(Pi)},{default:r(()=>[t[7]||(t[7]=n("p",null,[o("The "),n("code",null,"v-chip"),o(" component can have various sizes from "),n("code",null,"x-small"),o(" to "),n("code",null,"x-large"),o(".")],-1)),e(I)]),_:1,__:[7]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"In Selects",code:a(Ii)},{default:r(()=>[t[8]||(t[8]=n("p",null,[o("Selects can use "),n("code",null,"chips"),o(" to display the selected data. Try adding your own tags below.")],-1)),e(k)]),_:1,__:[8]},8,["code"])]),_:1}),e(V,{cols:"12",md:"6"},{default:r(()=>[e(p,{title:"Expandable",code:a(xi)},{default:r(()=>[t[9]||(t[9]=n("p",null,[o("Chips can be combined with "),n("code",null,"v-menu"),o(" to enable a specific set of actions for a chip.")],-1)),e(D)]),_:1,__:[9]},8,["code"])]),_:1})]),_:1})}}};export{se as default};
