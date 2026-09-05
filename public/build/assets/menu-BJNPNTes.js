import{V as l,a as L}from"./VList-CmvhbWEn.js";import{V as x}from"./VTooltip-DgMb52fM.js";import{o as v,f as B,e,b as t,d as p,ai as r,aT as u,aU as c,p as b,s as n,r as y,l as d,b1 as k,aG as h,c as O,F as C,i as D,t as P}from"./main-CpEWi7t7.js";import{V as m}from"./VMenu-DfS-eTwh.js";import{V as S,d as A}from"./VCard-7kt40PTk.js";import{V as $}from"./VDivider-B1WHoDe_.js";import{V as j}from"./VCardText-DAhlWA7A.js";import{_ as I}from"./AppCardCode-CVJYWXhS.js";import{V as F,a as f}from"./VRow-Bj-ZZsxW.js";import"./ssrBoot-B8TuDyQb.js";import"./createSimpleFunctional-Bo5HVQLI.js";import"./VAvatar-CWb8Zxgk.js";import"./VImg-DZ8cyhoo.js";import"./dialog-transition-CmWmfSjI.js";import"./vue3-perfect-scrollbar-DaTv5vJD.js";/* empty css              */const E={__name:"DemoMenuActivatorAndTooltip",setup(_){const a=[{title:"Option 1",value:"Option 1"},{title:"Option 2",value:"Option 2"},{title:"Option 3",value:"Option 3"}];return(s,o)=>(v(),B(m,{location:"top"},{activator:e(({props:i})=>[t(x,{location:"top"},{activator:e(({props:V})=>[t(r,u(c(b(i,V))),{default:e(()=>o[0]||(o[0]=[n(" Dropdown w/ Tooltip ")])),_:2,__:[0]},1040)]),default:e(()=>[o[1]||(o[1]=p("span",null,"I am a Tooltip",-1))]),_:2,__:[1]},1024)]),default:e(()=>[t(l,{items:a})]),_:1}))}},G={__name:"DemoMenuPopover",setup(_){const a=y(!1);return(s,o)=>(v(),B(m,{modelValue:d(a),"onUpdate:modelValue":o[0]||(o[0]=i=>h(a)?a.value=i:null),location:"top"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[1]||(o[1]=[n(" Menu as Popover ")])),_:2,__:[1]},1040)]),default:e(()=>[t(S,{"max-width":"300"},{default:e(()=>[t(l,null,{default:e(()=>[t(L,{"prepend-avatar":d(k),title:"John Leider",subtitle:"Founder of Vuetify"},null,8,["prepend-avatar"])]),_:1}),t($),t(j,null,{default:e(()=>o[2]||(o[2]=[n(" Gingerbread bear claw cake. Soufflé candy sesame snaps chocolate ice cream cake. Dessert candy canes oat cake pudding cupcake. Bear claw sweet wafer bonbon dragée toffee. ")])),_:1,__:[2]}),t(A,null,{default:e(()=>[t(r,{icon:"ri-heart-line"}),t(r,{icon:"ri-bookmark-line"}),t(r,{icon:"ri-thumb-down-line"})]),_:1})]),_:1})]),_:1},8,["modelValue"]))}},R={__name:"DemoMenuOpenOnHover",setup(_){const a=[{title:"Option 1",value:"Option 1"},{title:"Option 2",value:"Option 2"},{title:"Option 3",value:"Option 3"}];return(s,o)=>(v(),B(m,{"open-on-hover":""},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[0]||(o[0]=[n(" On hover ")])),_:2,__:[0]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}))}},H={class:"demo-space-x"},J={__name:"DemoMenuLocation",setup(_){const a=[{title:"Option 1",value:"Option 1"},{title:"Option 2",value:"Option 2"},{title:"Option 3",value:"Option 3"}];return(s,o)=>(v(),O("div",H,[t(m,{location:"top"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[0]||(o[0]=[n(" Top ")])),_:2,__:[0]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}),t(m,{location:"bottom"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[1]||(o[1]=[n(" Bottom ")])),_:2,__:[1]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}),t(m,{location:"start"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[2]||(o[2]=[n(" Start ")])),_:2,__:[2]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}),t(m,{location:"end"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[3]||(o[3]=[n(" End ")])),_:2,__:[3]},1040)]),default:e(()=>[t(l,{items:a})]),_:1})]))}},N={class:"demo-space-x"},U={__name:"DemoMenuCustomTransitions",setup(_){const a=[{title:"Option 1",value:"Option 1"},{title:"Option 2",value:"Option 2"},{title:"Option 3",value:"Option 3"}];return(s,o)=>(v(),O("div",N,[t(m,{transition:"scale-transition"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[0]||(o[0]=[n(" Scale Transition ")])),_:2,__:[0]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}),t(m,{transition:"slide-x-transition"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[1]||(o[1]=[n(" Slide X Transition ")])),_:2,__:[1]},1040)]),default:e(()=>[t(l,{items:a})]),_:1}),t(m,{transition:"slide-y-transition"},{activator:e(({props:i})=>[t(r,u(c(i)),{default:e(()=>o[2]||(o[2]=[n(" Slide Y Transition ")])),_:2,__:[2]},1040)]),default:e(()=>[t(l,{items:a})]),_:1})]))}},X={class:"demo-space-x"},Y={__name:"DemoMenuBasic",setup(_){const a=["primary","secondary","success","info","warning","error"],s=[{title:"Option 1",value:"Option 1"},{title:"Option 2",value:"Option 2"},{title:"Option 3",value:"Option 3"}];return(o,i)=>(v(),O("div",X,[(v(),O(C,null,D(a,V=>t(m,{key:V},{activator:e(({props:M})=>[t(r,b({color:V,ref_for:!0},M),{default:e(()=>[n(P(V),1)]),_:2},1040,["color"])]),default:e(()=>[t(l,{items:s})]),_:2},1024)),64))]))}},z={ts:`<script lang="ts" setup>
import { mergeProps } from 'vue'

const items = [{ title: 'Option 1', value: 'Option 1' }, { title: 'Option 2', value: 'Option 2' }, { title: 'Option 3', value: 'Option 3' }]
<\/script>

<template>
  <VMenu location="top">
    <template #activator="{ props: menuProps }">
      <VTooltip location="top">
        <template #activator="{ props: tooltipProps }">
          <VBtn v-bind="mergeProps(menuProps, tooltipProps)">
            Dropdown w/ Tooltip
          </VBtn>
        </template>
        <span>I am a Tooltip</span>
      </VTooltip>
    </template>

    <VList :items="items" />
  </VMenu>
</template>
`,js:`<script setup>
import { mergeProps } from 'vue'

const items = [
  {
    title: 'Option 1',
    value: 'Option 1',
  },
  {
    title: 'Option 2',
    value: 'Option 2',
  },
  {
    title: 'Option 3',
    value: 'Option 3',
  },
]
<\/script>

<template>
  <VMenu location="top">
    <template #activator="{ props: menuProps }">
      <VTooltip location="top">
        <template #activator="{ props: tooltipProps }">
          <VBtn v-bind="mergeProps(menuProps, tooltipProps)">
            Dropdown w/ Tooltip
          </VBtn>
        </template>
        <span>I am a Tooltip</span>
      </VTooltip>
    </template>

    <VList :items="items" />
  </VMenu>
</template>
`},W={ts:`<script lang="ts" setup>
const menusVariant = ['primary', 'secondary', 'success', 'info', 'warning', 'error']
const items = [{ title: 'Option 1', value: 'Option 1' }, { title: 'Option 2', value: 'Option 2' }, { title: 'Option 3', value: 'Option 3' }]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu
      v-for="menu in menusVariant"
      :key="menu"
    >
      <template #activator="{ props }">
        <VBtn
          :color="menu"
          v-bind="props"
        >
          {{ menu }}
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`,js:`<script setup>
const menusVariant = [
  'primary',
  'secondary',
  'success',
  'info',
  'warning',
  'error',
]

const items = [
  {
    title: 'Option 1',
    value: 'Option 1',
  },
  {
    title: 'Option 2',
    value: 'Option 2',
  },
  {
    title: 'Option 3',
    value: 'Option 3',
  },
]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu
      v-for="menu in menusVariant"
      :key="menu"
    >
      <template #activator="{ props }">
        <VBtn
          :color="menu"
          v-bind="props"
        >
          {{ menu }}
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`},q={ts:`<script lang="ts" setup>
const items = [{ title: 'Option 1', value: 'Option 1' }, { title: 'Option 2', value: 'Option 2' }, { title: 'Option 3', value: 'Option 3' }]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu transition="scale-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Scale Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu transition="slide-x-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Slide X Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu transition="slide-y-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Slide Y Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`,js:`<script setup>
const items = [
  {
    title: 'Option 1',
    value: 'Option 1',
  },
  {
    title: 'Option 2',
    value: 'Option 2',
  },
  {
    title: 'Option 3',
    value: 'Option 3',
  },
]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu transition="scale-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Scale Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu transition="slide-x-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Slide X Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu transition="slide-y-transition">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Slide Y Transition
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`},K={ts:`<script lang="ts" setup>
const items = [{ title: 'Option 1', value: 'Option 1' }, { title: 'Option 2', value: 'Option 2' }, { title: 'Option 3', value: 'Option 3' }]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu location="top">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Top
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="bottom">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Bottom
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="start">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Start
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="end">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          End
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`,js:`<script setup>
const items = [
  {
    title: 'Option 1',
    value: 'Option 1',
  },
  {
    title: 'Option 2',
    value: 'Option 2',
  },
  {
    title: 'Option 3',
    value: 'Option 3',
  },
]
<\/script>

<template>
  <div class="demo-space-x">
    <VMenu location="top">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Top
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="bottom">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Bottom
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="start">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          Start
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>

    <VMenu location="end">
      <template #activator="{ props }">
        <VBtn v-bind="props">
          End
        </VBtn>
      </template>

      <VList :items="items" />
    </VMenu>
  </div>
</template>
`},Q={ts:`<script lang="ts" setup>
const items = [{ title: 'Option 1', value: 'Option 1' }, { title: 'Option 2', value: 'Option 2' }, { title: 'Option 3', value: 'Option 3' }]
<\/script>

<template>
  <VMenu open-on-hover>
    <template #activator="{ props }">
      <VBtn v-bind="props">
        On hover
      </VBtn>
    </template>

    <VList :items="items" />
  </VMenu>
</template>
`,js:`<script setup>
const items = [
  {
    title: 'Option 1',
    value: 'Option 1',
  },
  {
    title: 'Option 2',
    value: 'Option 2',
  },
  {
    title: 'Option 3',
    value: 'Option 3',
  },
]
<\/script>

<template>
  <VMenu open-on-hover>
    <template #activator="{ props }">
      <VBtn v-bind="props">
        On hover
      </VBtn>
    </template>

    <VList :items="items" />
  </VMenu>
</template>
`},Z={ts:`<script lang="ts" setup>
import avatar1 from '@images/avatars/avatar-1.png'

const menu = ref(false)
<\/script>

<template>
  <VMenu
    v-model="menu"
    location="top"
  >
    <template #activator="{ props }">
      <VBtn v-bind="props">
        Menu as Popover
      </VBtn>
    </template>

    <VCard max-width="300">
      <VList>
        <VListItem
          :prepend-avatar="avatar1"
          title="John Leider"
          subtitle="Founder of Vuetify"
        />
      </VList>

      <VDivider />

      <VCardText>
        Gingerbread bear claw cake. Soufflé candy sesame snaps chocolate ice cream cake.
        Dessert candy canes oat cake pudding cupcake. Bear claw sweet wafer bonbon dragée toffee.
      </VCardText>

      <VCardActions>
        <VBtn icon="ri-heart-line" />
        <VBtn icon="ri-bookmark-line" />
        <VBtn icon="ri-thumb-down-line" />
      </VCardActions>
    </VCard>
  </VMenu>
</template>
`,js:`<script setup>
import avatar1 from '@images/avatars/avatar-1.png'

const menu = ref(false)
<\/script>

<template>
  <VMenu
    v-model="menu"
    location="top"
  >
    <template #activator="{ props }">
      <VBtn v-bind="props">
        Menu as Popover
      </VBtn>
    </template>

    <VCard max-width="300">
      <VList>
        <VListItem
          :prepend-avatar="avatar1"
          title="John Leider"
          subtitle="Founder of Vuetify"
        />
      </VList>

      <VDivider />

      <VCardText>
        Gingerbread bear claw cake. Soufflé candy sesame snaps chocolate ice cream cake.
        Dessert candy canes oat cake pudding cupcake. Bear claw sweet wafer bonbon dragée toffee.
      </VCardText>

      <VCardActions>
        <VBtn icon="ri-heart-line" />
        <VBtn icon="ri-bookmark-line" />
        <VBtn icon="ri-thumb-down-line" />
      </VCardActions>
    </VCard>
  </VMenu>
</template>
`},_t={__name:"menu",setup(_){return(a,s)=>{const o=Y,i=I,V=U,M=J,w=R,T=G,g=E;return v(),B(F,{class:"match-height"},{default:e(()=>[t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Basic",code:d(W)},{default:e(()=>[s[0]||(s[0]=p("p",null," Remember to put the element that activates the menu in the activator slot. ",-1)),t(o)]),_:1,__:[0]},8,["code"])]),_:1}),t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Custom transitions",code:d(q)},{default:e(()=>[s[1]||(s[1]=p("p",null,[n("Vuetify comes with 3 standard transitions, "),p("code",null,"scale"),n(", "),p("code",null,"slide-x"),n(" and "),p("code",null,"slide-y"),n(". Use "),p("code",null,"transition"),n(" prop to add transition to a menu.")],-1)),t(V)]),_:1,__:[1]},8,["code"])]),_:1}),t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Location",code:d(K)},{default:e(()=>[s[2]||(s[2]=p("p",null,[n("Menu can be offset relative to the activator by using the "),p("code",null,"location"),n(" prop.")],-1)),t(M)]),_:1,__:[2]},8,["code"])]),_:1}),t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Open on hover",code:d(Q)},{default:e(()=>[s[3]||(s[3]=p("p",null,[n("Menus can be accessed using hover instead of clicking with the "),p("code",null,"open-on-hover"),n(" prop.")],-1)),t(w)]),_:1,__:[3]},8,["code"])]),_:1}),t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Popover",code:d(Z)},{default:e(()=>[s[4]||(s[4]=p("p",null,"A menu can be configured to be static when opened, allowing it to function as a popover. This can be useful when there are multiple interactive items within the menu contents.",-1)),t(T)]),_:1,__:[4]},8,["code"])]),_:1}),t(f,{cols:"12",md:"6"},{default:e(()=>[t(i,{title:"Activator and tooltip",code:d(z)},{default:e(()=>[s[5]||(s[5]=p("p",null,[n("With the new "),p("code",null,"v-slot"),n(" syntax, nested activators such as those seen with a "),p("code",null,"v-menu"),n(" and "),p("code",null,"v-tooltip"),n(" attached to the same activator button, need a particular setup in order to function correctly")],-1)),t(g)]),_:1,__:[5]},8,["code"])]),_:1})]),_:1})}}};export{_t as default};
