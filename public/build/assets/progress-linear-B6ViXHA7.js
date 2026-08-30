import{_}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{o as m,c as g,b as e,aV as a,r as f,aG as V,l as n,e as l,d as r,t as P,f as L,E as k,b8 as B,w as $,s}from"./main-iFawPKEr.js";import{_ as M}from"./AppCardCode-C7-irx7I.js";import{V as x,a as v}from"./VRow-BVuve61r.js";import"./vue3-perfect-scrollbar-BDGziK_M.js";import"./VCard-Dfbw-epG.js";import"./createSimpleFunctional-VuQ2hYMi.js";import"./VAvatar-BSFUsqz7.js";import"./VImg-CGou3SRb.js";import"./VCardText-CoumzfAn.js";import"./VDivider-r2T-apzM.js";/* empty css              */const I={},D={class:"demo-space-y"};function U(p,t){return m(),g("div",D,[e(a,{"model-value":"75",striped:""}),e(a,{color:"success","model-value":"55",striped:""}),e(a,{color:"warning","model-value":"35",striped:""})])}const j=_(I,[["render",U]]),R={class:"demo-space-y"},T={__name:"DemoProgressLinearSlots",setup(p){const t=f(20),o=f(33),i=f(78);return(u,c)=>(m(),g("div",R,[e(a,{modelValue:n(i),"onUpdate:modelValue":c[0]||(c[0]=d=>V(i)?i.value=d:null),height:"8"},null,8,["modelValue"]),e(a,{modelValue:n(t),"onUpdate:modelValue":c[1]||(c[1]=d=>V(t)?t.value=d:null),height:"20"},{default:l(({value:d})=>[r("span",null,P(Math.ceil(d))+"%",1)]),_:1},8,["modelValue"]),e(a,{modelValue:n(o),"onUpdate:modelValue":c[2]||(c[2]=d=>V(o)?o.value=d:null),height:"20"},{default:l(()=>[r("span",null,P(Math.ceil(n(o)))+"%",1)]),_:1},8,["modelValue"])]))}},C={},S={class:"demo-space-y"};function N(p,t){return m(),g("div",S,[e(a,{"model-value":"78",height:"8",rounded:""}),e(a,{"model-value":"20",height:"20",rounded:""}),e(a,{"model-value":"33",height:"20",rounded:""})])}const E=_(C,[["render",N]]),A={};function G(p,t){return m(),L(a,{indeterminate:"",reverse:""})}const Y=_(A,[["render",G]]),q={};function z(p,t){return m(),L(a,{indeterminate:""})}const F=_(q,[["render",z]]),H={class:"demo-space-y"},J={__name:"DemoProgressLinearBuffering",setup(p){const t=f(10),o=f(20),i=f(),u=()=>{clearInterval(i.value),i.value=setInterval(()=>{t.value+=Math.random()*10+5,o.value+=Math.random()*10+6},2e3)};return k(u),B(()=>{clearInterval(i.value)}),$(t,()=>{if(t.value<100)return!1;t.value=0,o.value=10,u()}),(c,d)=>(m(),g("div",H,[e(a,{modelValue:n(t),"onUpdate:modelValue":d[0]||(d[0]=h=>V(t)?t.value=h:null),height:"8","buffer-value":n(o)},null,8,["modelValue","buffer-value"])]))}},K={},O={class:"demo-space-y"};function Q(p,t){return m(),g("div",O,[e(a,{"model-value":"15"}),e(a,{"model-value":"30",color:"secondary"}),e(a,{"model-value":"45",color:"success"})])}const W=_(K,[["render",Q]]),X={ts:`<script setup lang="ts">
const modelValue = ref(10)
const bufferValue = ref(20)
const interval = ref()

const startBuffer = () => {
  clearInterval(interval.value)

  interval.value = setInterval(() => {
    modelValue.value += Math.random() * (15 - 5) + 5
    bufferValue.value += Math.random() * (15 - 5) + 6
  }, 2000)
}

onMounted(startBuffer)

onBeforeUnmount(() => {
  clearInterval(interval.value)
})

watch(modelValue, () => {
  if (modelValue.value < 100)
    return false

  modelValue.value = 0
  bufferValue.value = 10
  startBuffer()
})
<\/script>

<template>
  <div class="demo-space-y">
    <VProgressLinear
      v-model="modelValue"
      height="8"
      :buffer-value="bufferValue"
    />
  </div>
</template>
`,js:`<script setup>
const modelValue = ref(10)
const bufferValue = ref(20)
const interval = ref()

const startBuffer = () => {
  clearInterval(interval.value)
  interval.value = setInterval(() => {
    modelValue.value += Math.random() * (15 - 5) + 5
    bufferValue.value += Math.random() * (15 - 5) + 6
  }, 2000)
}

onMounted(startBuffer)
onBeforeUnmount(() => {
  clearInterval(interval.value)
})
watch(modelValue, () => {
  if (modelValue.value < 100)
    return false
  modelValue.value = 0
  bufferValue.value = 10
  startBuffer()
})
<\/script>

<template>
  <div class="demo-space-y">
    <VProgressLinear
      v-model="modelValue"
      height="8"
      :buffer-value="bufferValue"
    />
  </div>
</template>
`},Z={ts:`<template>
  <div class="demo-space-y">
    <VProgressLinear model-value="15" />

    <VProgressLinear
      model-value="30"
      color="secondary"
    />

    <VProgressLinear
      model-value="45"
      color="success"
    />
  </div>
</template>
`,js:`<template>
  <div class="demo-space-y">
    <VProgressLinear model-value="15" />

    <VProgressLinear
      model-value="30"
      color="secondary"
    />

    <VProgressLinear
      model-value="45"
      color="success"
    />
  </div>
</template>
`},ee={ts:`<template>
  <VProgressLinear indeterminate />
</template>
`,js:`<template>
  <VProgressLinear indeterminate />
</template>
`},oe={ts:`<template>
  <VProgressLinear
    indeterminate
    reverse
  />
</template>
`,js:`<template>
  <VProgressLinear
    indeterminate
    reverse
  />
</template>
`},re={ts:`<template>
  <div class="demo-space-y">
    <VProgressLinear
      model-value="78"
      height="8"
      rounded
    />

    <VProgressLinear
      model-value="20"
      height="20"
      rounded
    />

    <VProgressLinear
      model-value="33"
      height="20"
      rounded
    />
  </div>
</template>
`,js:`<template>
  <div class="demo-space-y">
    <VProgressLinear
      model-value="78"
      height="8"
      rounded
    />

    <VProgressLinear
      model-value="20"
      height="20"
      rounded
    />

    <VProgressLinear
      model-value="33"
      height="20"
      rounded
    />
  </div>
</template>
`},te={ts:`<script setup lang="ts">
const skill = ref(20)
const knowledge = ref(33)
const power = ref(78)
<\/script>

<template>
  <div class="demo-space-y">
    <VProgressLinear
      v-model="power"
      height="8"
    />

    <VProgressLinear
      v-model="skill"
      height="20"
    >
      <template #default="{ value }">
        <span>{{ Math.ceil(value) }}%</span>
      </template>
    </VProgressLinear>

    <VProgressLinear
      v-model="knowledge"
      height="20"
    >
      <span>{{ Math.ceil(knowledge) }}%</span>
    </VProgressLinear>
  </div>
</template>
`,js:`<script setup>
const skill = ref(20)
const knowledge = ref(33)
const power = ref(78)
<\/script>

<template>
  <div class="demo-space-y">
    <VProgressLinear
      v-model="power"
      height="8"
    />

    <VProgressLinear
      v-model="skill"
      height="20"
    >
      <template #default="{ value }">
        <span>{{ Math.ceil(value) }}%</span>
      </template>
    </VProgressLinear>

    <VProgressLinear
      v-model="knowledge"
      height="20"
    >
      <span>{{ Math.ceil(knowledge) }}%</span>
    </VProgressLinear>
  </div>
</template>
`},le={ts:`<template>
  <div class="demo-space-y">
    <VProgressLinear
      model-value="75"
      striped
    />

    <VProgressLinear
      color="success"
      model-value="55"
      striped
    />

    <VProgressLinear
      color="warning"
      model-value="35"
      striped
    />
  </div>
</template>
`,js:`<template>
  <div class="demo-space-y">
    <VProgressLinear
      model-value="75"
      striped
    />

    <VProgressLinear
      color="success"
      model-value="55"
      striped
    />

    <VProgressLinear
      color="warning"
      model-value="35"
      striped
    />
  </div>
</template>
`},ge={__name:"progress-linear",setup(p){return(t,o)=>{const i=W,u=M,c=J,d=F,h=Y,y=E,b=T,w=j;return m(),L(x,{class:"match-height"},{default:l(()=>[e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Color",code:n(Z)},{default:l(()=>[o[0]||(o[0]=r("p",null,[s("Use the props "),r("code",null,"color"),s(" and "),r("code",null,"background-color"),s(" to set colors.")],-1)),e(i)]),_:1,__:[0]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Buffering",code:n(X)},{default:l(()=>[o[1]||(o[1]=r("p",null,[s("The primary value is controlled by "),r("code",null,"v-model"),s(", whereas the buffer is controlled by the "),r("code",null,"buffer-value"),s(" prop.")],-1)),e(c)]),_:1,__:[1]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Indeterminate",code:n(ee)},{default:l(()=>[o[2]||(o[2]=r("p",null,[s("for continuously animating progress bar,use prop "),r("code",null,"indeterminate"),s(". This indicates continuous process. ")],-1)),e(d)]),_:1,__:[2]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Reversed",code:n(oe)},{default:l(()=>[o[3]||(o[3]=r("p",null,[s("Use prop "),r("code",null,"reverse"),s(" to animate continuously in reverse direction. The component also has RTL support.")],-1)),e(h)]),_:1,__:[3]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Rounded",code:n(re)},{default:l(()=>[o[4]||(o[4]=r("p",null,[s("The "),r("code",null," rounded "),s(" prop is used to apply a border radius to the v-progress-linear component.")],-1)),e(y)]),_:1,__:[4]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Slots",code:n(te)},{default:l(()=>[o[5]||(o[5]=r("p",null,[s("we can bind user input using "),r("code",null,"v-model"),s(".You can also use the default slot for the same.")],-1)),e(b)]),_:1,__:[5]},8,["code"])]),_:1}),e(v,{cols:"12",md:"6"},{default:l(()=>[e(u,{title:"Striped",code:n(le)},{default:l(()=>[o[6]||(o[6]=r("p",null,[s(" The "),r("code",null,"striped"),s(" prop is used to apply striped background.")],-1)),e(w)]),_:1,__:[6]},8,["code"])]),_:1})]),_:1})}}};export{ge as default};
