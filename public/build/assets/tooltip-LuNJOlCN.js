import{o as d,c as u,b as t,e as n,s as a,ai as e,l as c,b4 as S,d as l,a0 as T,p as _,r as H,f}from"./main-CUuNRo4T.js";import{V as p}from"./VTooltip-jVHs6M9H.js";import{V as b}from"./VImg-r7X4LxmQ.js";import{V as D}from"./VAvatar-Byel16vD.js";import{_ as m}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{_ as k}from"./AppCardCode-CmGHI9oP.js";import{V as $,a as V}from"./VRow-iz7EcXng.js";import"./vue3-perfect-scrollbar-Coo2UxDp.js";import"./VCard-D5ODRNnh.js";import"./createSimpleFunctional-CF6hf7al.js";import"./VCardText-C4dena62.js";import"./VDivider-Comr9yVz.js";/* empty css              */const E={class:"demo-space-x"},I={__name:"DemoTooltipTooltipOnVariousElements",setup(v){return(o,i)=>(d(),u("div",E,[t(e,null,{default:n(()=>[i[1]||(i[1]=a(" Button ")),t(p,{location:"top",activator:"parent"},{default:n(()=>i[0]||(i[0]=[a(" Tooltip ")])),_:1,__:[0]})]),_:1,__:[1]}),t(D,{color:"info"},{default:n(()=>[t(b,{src:c(S)},null,8,["src"]),t(p,{location:"top",activator:"parent"},{default:n(()=>i[2]||(i[2]=[a(" Tooltip on Avatar ")])),_:1,__:[2]})]),_:1}),t(p,{location:"top"},{activator:n(({props:r})=>[t(T,_(r,{size:"30",icon:"ri-user-line"}),null,16)]),default:n(()=>[i[3]||(i[3]=l("span",null,"Tooltip on Icon",-1))]),_:1,__:[3]})]))}},A={},w={class:"demo-space-x"};function j(v,o){return d(),u("div",w,[t(e,null,{default:n(()=>[o[1]||(o[1]=a(" scale transition ")),t(p,{location:"top",transition:"scale-transition",activator:"parent"},{default:n(()=>o[0]||(o[0]=[l("span",null,"Scale Transition",-1)])),_:1,__:[0]})]),_:1,__:[1]}),t(e,null,{default:n(()=>[o[3]||(o[3]=a(" scroll X transition ")),t(p,{location:"top",activator:"parent",transition:"scroll-x-transition"},{default:n(()=>o[2]||(o[2]=[l("span",null,"Scroll X Transition",-1)])),_:1,__:[2]})]),_:1,__:[3]}),t(e,null,{default:n(()=>[o[5]||(o[5]=a(" scroll y transition ")),t(p,{location:"top",activator:"parent",transition:"scroll-y-transition"},{default:n(()=>o[4]||(o[4]=[l("span",null,"Scroll Y Transition",-1)])),_:1,__:[4]})]),_:1,__:[5]})])}const F=m(A,[["render",j]]),X={class:"demo-space-x"},C={__name:"DemoTooltipVModelSupport",setup(v){const o=H(!1);return(i,r)=>(d(),u("div",X,[t(e,{onClick:r[0]||(r[0]=s=>o.value=!c(o))},{default:n(()=>r[1]||(r[1]=[a(" toggle tooltip ")])),_:1,__:[1]}),t(p,{"model-value":c(o),location:"top"},{activator:n(({props:s})=>[t(T,_(s,{icon:"ri-instagram-line"}),null,16)]),default:n(()=>[r[2]||(r[2]=l("span",null,"Programmatic tooltip",-1))]),_:1,__:[2]},8,["model-value"])]))}},M={};function P(v,o){return d(),f(e,{variant:"outlined"},{default:n(()=>[o[1]||(o[1]=l("span",null,"Open Delay On Hover",-1)),o[2]||(o[2]=a()),t(p,{"open-delay":"500",location:"top",activator:"parent"},{default:n(()=>o[0]||(o[0]=[l("span",null,"Open Delay On Hover",-1)])),_:1,__:[0]})]),_:1,__:[1,2]})}const z=m(M,[["render",P]]),N={},Y={class:"demo-space-x"};function L(v,o){return d(),u("div",Y,[t(e,{variant:"outlined"},{default:n(()=>[o[1]||(o[1]=l("span",null,"Open On Hover",-1)),t(p,{activator:"parent",location:"top"},{default:n(()=>o[0]||(o[0]=[a(" Open On Hover ")])),_:1,__:[0]})]),_:1,__:[1]}),t(e,{variant:"outlined",color:"primary"},{default:n(()=>[o[3]||(o[3]=l("span",null,"Open On click",-1)),t(p,{"open-on-click":"","open-on-hover":!1,location:"top",activator:"parent"},{default:n(()=>o[2]||(o[2]=[a(" Open On click ")])),_:1,__:[2]})]),_:1,__:[3]}),t(e,{variant:"outlined"},{default:n(()=>[o[5]||(o[5]=l("span",null,"Open On Hover + Focus",-1)),t(p,{"open-on-focus":"",location:"top",activator:"parent"},{default:n(()=>o[4]||(o[4]=[a(" Open On Hover + Focus ")])),_:1,__:[4]})]),_:1,__:[5]})])}const U=m(N,[["render",L]]),R={},q={class:"demo-space-x"};function G(v,o){return d(),u("div",q,[t(e,{variant:"tonal"},{default:n(()=>[o[1]||(o[1]=a(" Tooltip on End ")),t(p,{activator:"parent",location:"end"},{default:n(()=>o[0]||(o[0]=[a(" End Tooltip ")])),_:1,__:[0]})]),_:1,__:[1]}),t(e,{variant:"tonal"},{default:n(()=>[o[3]||(o[3]=a(" Tooltip on Start ")),t(p,{activator:"parent",location:"start"},{default:n(()=>o[2]||(o[2]=[a(" Start Tooltip ")])),_:1,__:[2]})]),_:1,__:[3]}),t(e,{variant:"tonal"},{default:n(()=>[o[5]||(o[5]=a(" Tooltip on Top ")),t(p,{activator:"parent",location:"top"},{default:n(()=>o[4]||(o[4]=[a(" Top Tooltip ")])),_:1,__:[4]})]),_:1,__:[5]}),t(e,{variant:"tonal"},{default:n(()=>[o[7]||(o[7]=a(" Tooltip on Bottom ")),t(p,{activator:"parent",location:"bottom"},{default:n(()=>o[6]||(o[6]=[a(" Bottom Tooltip ")])),_:1,__:[6]})]),_:1,__:[7]})])}const J=m(R,[["render",G]]),K={ts:`<template>
  <!-- Delay on open -->
  <VBtn variant="outlined">
    <span>Open Delay On Hover</span> <VTooltip
      open-delay="500"
      location="top"
      activator="parent"
    >
      <span>Open Delay On Hover</span>
    </VTooltip>
  </VBtn>
</template>
`,js:`<template>
  <!-- Delay on open -->
  <VBtn variant="outlined">
    <span>Open Delay On Hover</span> <VTooltip
      open-delay="500"
      location="top"
      activator="parent"
    >
      <span>Open Delay On Hover</span>
    </VTooltip>
  </VBtn>
</template>
`},Q={ts:`<template>
  <div class="demo-space-x">
    <!-- open on hover -->
    <VBtn variant="outlined">
      <span>Open On Hover</span>
      <VTooltip
        activator="parent"
        location="top"
      >
        Open On Hover
      </VTooltip>
    </VBtn>

    <!-- open on click -->
    <VBtn
      variant="outlined"
      color="primary"
    >
      <span>Open On click</span>

      <VTooltip
        open-on-click
        :open-on-hover="false"
        location="top"
        activator="parent"
      >
        Open On click
      </VTooltip>
    </VBtn>

    <!-- open on focus -->
    <VBtn variant="outlined">
      <span>Open On Hover + Focus</span>
      <VTooltip
        open-on-focus
        location="top"
        activator="parent"
      >
        Open On Hover + Focus
      </VTooltip>
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <!-- open on hover -->
    <VBtn variant="outlined">
      <span>Open On Hover</span>
      <VTooltip
        activator="parent"
        location="top"
      >
        Open On Hover
      </VTooltip>
    </VBtn>

    <!-- open on click -->
    <VBtn
      variant="outlined"
      color="primary"
    >
      <span>Open On click</span>

      <VTooltip
        open-on-click
        :open-on-hover="false"
        location="top"
        activator="parent"
      >
        Open On click
      </VTooltip>
    </VBtn>

    <!-- open on focus -->
    <VBtn variant="outlined">
      <span>Open On Hover + Focus</span>
      <VTooltip
        open-on-focus
        location="top"
        activator="parent"
      >
        Open On Hover + Focus
      </VTooltip>
    </VBtn>
  </div>
</template>
`},W={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="tonal">
      Tooltip on End
      <VTooltip
        activator="parent"
        location="end"
      >
        End Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Start
      <VTooltip
        activator="parent"
        location="start"
      >
        Start Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Top
      <VTooltip
        activator="parent"
        location="top"
      >
        Top Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Bottom
      <VTooltip
        activator="parent"
        location="bottom"
      >
        Bottom Tooltip
      </VTooltip>
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="tonal">
      Tooltip on End
      <VTooltip
        activator="parent"
        location="end"
      >
        End Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Start
      <VTooltip
        activator="parent"
        location="start"
      >
        Start Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Top
      <VTooltip
        activator="parent"
        location="top"
      >
        Top Tooltip
      </VTooltip>
    </VBtn>

    <VBtn variant="tonal">
      Tooltip on Bottom
      <VTooltip
        activator="parent"
        location="bottom"
      >
        Bottom Tooltip
      </VTooltip>
    </VBtn>
  </div>
</template>
`},Z={ts:`<script setup lang="ts">
import avatar4 from '@images/avatars/avatar-4.png'
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn>
      Button
      <VTooltip
        location="top"
        activator="parent"
      >
        Tooltip
      </VTooltip>
    </VBtn>

    <VAvatar color="info">
      <VImg :src="avatar4" />
      <VTooltip
        location="top"
        activator="parent"
      >
        Tooltip on Avatar
      </VTooltip>
    </VAvatar>

    <VTooltip location="top">
      <template #activator="{ props }">
        <VIcon
          v-bind="props"
          size="30"
          icon="ri-user-line"
        />
      </template>
      <span>Tooltip on Icon</span>
    </VTooltip>
  </div>
</template>
`,js:`<script setup>
import avatar4 from '@images/avatars/avatar-4.png'
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn>
      Button
      <VTooltip
        location="top"
        activator="parent"
      >
        Tooltip
      </VTooltip>
    </VBtn>

    <VAvatar color="info">
      <VImg :src="avatar4" />
      <VTooltip
        location="top"
        activator="parent"
      >
        Tooltip on Avatar
      </VTooltip>
    </VAvatar>

    <VTooltip location="top">
      <template #activator="{ props }">
        <VIcon
          v-bind="props"
          size="30"
          icon="ri-user-line"
        />
      </template>
      <span>Tooltip on Icon</span>
    </VTooltip>
  </div>
</template>
`},h={ts:`<template>
  <div class="demo-space-x">
    <!-- Scale transition -->
    <VBtn>
      scale transition
      <VTooltip
        location="top"
        transition="scale-transition"
        activator="parent"
      >
        <span>Scale Transition</span>
      </VTooltip>
    </VBtn>

    <!-- Scroll x transition -->
    <VBtn>
      scroll X transition
      <VTooltip
        location="top"
        activator="parent"
        transition="scroll-x-transition"
      >
        <span>Scroll X Transition</span>
      </VTooltip>
    </VBtn>

    <!-- Scroll y transition -->
    <VBtn>
      scroll y transition
      <VTooltip
        location="top"
        activator="parent"
        transition="scroll-y-transition"
      >
        <span>Scroll Y Transition</span>
      </VTooltip>
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <!-- Scale transition -->
    <VBtn>
      scale transition
      <VTooltip
        location="top"
        transition="scale-transition"
        activator="parent"
      >
        <span>Scale Transition</span>
      </VTooltip>
    </VBtn>

    <!-- Scroll x transition -->
    <VBtn>
      scroll X transition
      <VTooltip
        location="top"
        activator="parent"
        transition="scroll-x-transition"
      >
        <span>Scroll X Transition</span>
      </VTooltip>
    </VBtn>

    <!-- Scroll y transition -->
    <VBtn>
      scroll y transition
      <VTooltip
        location="top"
        activator="parent"
        transition="scroll-y-transition"
      >
        <span>Scroll Y Transition</span>
      </VTooltip>
    </VBtn>
  </div>
</template>
`},oo={ts:`<script lang="ts" setup>
const isTooltipVisible = ref(false)
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn @click="isTooltipVisible = !isTooltipVisible">
      toggle tooltip
    </VBtn>

    <VTooltip
      :model-value="isTooltipVisible"
      location="top"
    >
      <template #activator="{ props }">
        <VIcon
          v-bind="props"
          icon="ri-instagram-line"
        />
      </template>
      <span>Programmatic tooltip</span>
    </VTooltip>
  </div>
</template>
`,js:`<script setup>
const isTooltipVisible = ref(false)
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn @click="isTooltipVisible = !isTooltipVisible">
      toggle tooltip
    </VBtn>

    <VTooltip
      :model-value="isTooltipVisible"
      location="top"
    >
      <template #activator="{ props }">
        <VIcon
          v-bind="props"
          icon="ri-instagram-line"
        />
      </template>
      <span>Programmatic tooltip</span>
    </VTooltip>
  </div>
</template>
`},mo={__name:"tooltip",setup(v){return(o,i)=>{const r=J,s=k,B=U,O=z,x=C,y=F,g=I;return d(),f($,null,{default:n(()=>[t(V,{cols:"12"},{default:n(()=>[t(s,{title:"Location",code:c(W)},{default:n(()=>[i[0]||(i[0]=l("p",null,[a("Use the "),l("code",null,"location"),a(" prop to specify on which side of the element the tooltip should show")],-1)),t(r)]),_:1,__:[0]},8,["code"])]),_:1}),t(V,{cols:"12"},{default:n(()=>[t(s,{title:"Events",code:c(Q)},{default:n(()=>[t(B)]),_:1},8,["code"])]),_:1}),t(V,{cols:"12"},{default:n(()=>[t(s,{title:"Delay On Hover",code:c(K)},{default:n(()=>[i[1]||(i[1]=l("p",null,[a("Delay (in ms) after which tooltip opens (when "),l("code",null,"open-on-hover"),a(" prop is set to true)")],-1)),t(O)]),_:1,__:[1]},8,["code"])]),_:1}),t(V,{cols:"12"},{default:n(()=>[t(s,{title:"V-Model Support",code:c(oo)},{default:n(()=>[i[2]||(i[2]=l("p",null,[a("Tooltip visibility can be programmatically changed using "),l("code",null,"v-model"),a(".")],-1)),t(x)]),_:1,__:[2]},8,["code"])]),_:1}),t(V,{cols:"12"},{default:n(()=>[t(s,{title:"Transition",code:c(h)},{default:n(()=>[i[3]||(i[3]=l("p",null,[a("Use "),l("code",null,"transition"),a(" prop to sets the component transition.")],-1)),t(y)]),_:1,__:[3]},8,["code"])]),_:1}),t(V,{cols:"12"},{default:n(()=>[t(s,{title:"Tooltip on Various Elements",code:c(Z)},{default:n(()=>[i[4]||(i[4]=l("p",null,"Tooltips can wrap any element.",-1)),t(g)]),_:1,__:[4]},8,["code"])]),_:1})]),_:1})}}};export{mo as default};
