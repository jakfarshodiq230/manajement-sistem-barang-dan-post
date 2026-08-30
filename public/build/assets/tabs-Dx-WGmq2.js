import{a as u,V as T}from"./VTabs-BFpZvXKb.js";import{r as I,w as $,o as c,c as d,b as e,e as t,F as p,i as g,l as r,f as y,s as n,t as k,aG as b,d as l,ai as W,a0 as q}from"./main-BaEGrDAQ.js";import{a as _,V as x}from"./VWindowItem-aY8r0kPb.js";import{_ as C}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{_ as h}from"./AppCardCode-BlK2sY5I.js";import{V as z,a as f}from"./VRow-BVuxKxZe.js";import"./VSlideGroup-Tz_6M7np.js";import"./ssrBoot-CyQbTewg.js";import"./vue3-perfect-scrollbar-EehmiHvb.js";import"./VCard-BeQYVVVh.js";import"./createSimpleFunctional-CWpvwpSS.js";import"./VAvatar-RrDvHgSg.js";import"./VImg-CkbqcFju.js";import"./VCardText-C1Ij5jP7.js";import"./VDivider-DXB8nnF_.js";/* empty css              */const O={class:"text-center mt-9"},U={__name:"DemoTabsDynamic",setup(V){const a=I(3),o=I(0);return $(a,s=>{o.value=s-1}),(s,i)=>(c(),d(p,null,[e(T,{modelValue:r(o),"onUpdate:modelValue":i[0]||(i[0]=m=>b(o)?o.value=m:null)},{default:t(()=>[(c(!0),d(p,null,g(r(a),m=>(c(),y(u,{key:m,value:m},{default:t(()=>[n(" Tab "+k(m),1)]),_:2},1032,["value"]))),128))]),_:1},8,["modelValue"]),l("div",O,[e(W,{disabled:!r(a),variant:"text",onClick:i[1]||(i[1]=m=>a.value--)},{default:t(()=>i[3]||(i[3]=[n(" Remove Tab ")])),_:1,__:[3]},8,["disabled"]),e(W,{variant:"text",onClick:i[2]||(i[2]=m=>a.value++)},{default:t(()=>i[4]||(i[4]=[n(" Add Tab ")])),_:1,__:[4]})])],64))}},E={class:"text-center"},M="Chocolate cake marshmallow toffee sweet caramels tootsie roll chocolate bar. Chocolate candy lemon drops cupcake macaroon liquorice. Icing tiramisu cake pastry jujubes lollipop gummies sugar plum pie.",H={__name:"DemoTabsProgrammaticNavigation",setup(V){const a=I(1),o=["Appetizers","Entrees","Deserts","Cocktails"],s=o.length,i=()=>{a.value!==1&&(a.value-=1)},m=()=>{a.value!==s&&(a.value+=1)};return(P,w)=>(c(),d(p,null,[e(T,{modelValue:r(a),"onUpdate:modelValue":w[0]||(w[0]=v=>b(a)?a.value=v:null),grow:""},{default:t(()=>[(c(!0),d(p,null,g(o.length,v=>(c(),y(u,{key:v,value:v},{default:t(()=>[n(k(o[v-1]),1)]),_:2},1032,["value"]))),128))]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":w[1]||(w[1]=v=>b(a)?a.value=v:null),class:"mt-5"},{default:t(()=>[(c(!0),d(p,null,g(o.length,v=>(c(),y(_,{key:v,value:v},{default:t(()=>[n(k(M))]),_:2},1032,["value"]))),128))]),_:1},8,["modelValue"]),l("div",E,[e(W,{variant:"text",disabled:r(a)===1,onClick:i},{default:t(()=>w[2]||(w[2]=[n(" Previous ")])),_:1,__:[2]},8,["disabled"]),e(W,{variant:"text",disabled:r(a)===r(s),onClick:m},{default:t(()=>w[3]||(w[3]=[n(" Next ")])),_:1,__:[3]},8,["disabled"])])],64))}},R="hortbread chocolate bar marshmallow bear claw tiramisu chocolate cookie wafer. Gummies sweet brownie brownie marshmallow chocolate cake pastry. Topping macaroon shortbread liquorice dragée macaroon.",G={__name:"DemoTabsGrow",setup(V){const a=I("Appetizers"),o=["Appetizers","Entrees","Deserts","Cocktails"];return(s,i)=>(c(),d(p,null,[e(T,{modelValue:r(a),"onUpdate:modelValue":i[0]||(i[0]=m=>b(a)?a.value=m:null),grow:""},{default:t(()=>[(c(),d(p,null,g(o,m=>e(u,{key:m,value:m},{default:t(()=>[n(k(m),1)]),_:2},1032,["value"])),64))]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":i[1]||(i[1]=m=>b(a)?a.value=m:null),class:"mt-6"},{default:t(()=>[(c(),d(p,null,g(o,m=>e(_,{key:m,value:m},{default:t(()=>[n(k(R))]),_:2},1032,["value"])),64))]),_:1},8,["modelValue"])],64))}},L={};function J(V,a){return c(),y(T,{"fixed-tabs":""},{default:t(()=>[e(u,null,{default:t(()=>a[0]||(a[0]=[n(" Fixed Tab 1 ")])),_:1,__:[0]}),e(u,null,{default:t(()=>a[1]||(a[1]=[n(" Fixed Tab 2 ")])),_:1,__:[1]}),e(u,null,{default:t(()=>a[2]||(a[2]=[n(" Fixed Tab 3 ")])),_:1,__:[2]}),e(u,null,{default:t(()=>a[3]||(a[3]=[n(" Fixed Tab 4 ")])),_:1,__:[3]})]),_:1})}const K=C(L,[["render",J]]),Q={};function X(V,a){return c(),y(T,{"next-icon":"ri-arrow-right-line","prev-icon":"ri-arrow-left-line","show-arrows":""},{default:t(()=>[(c(),d(p,null,g(10,o=>e(u,{key:o},{default:t(()=>[n(" Item "+k(o),1)]),_:2},1024)),64))]),_:1})}const Y=C(Q,[["render",X]]),Z={};function ee(V,a){return c(),y(T,{"show-arrows":""},{default:t(()=>[(c(),d(p,null,g(10,o=>e(u,{key:o,value:o},{default:t(()=>[n(" Item "+k(o),1)]),_:2},1032,["value"])),64))]),_:1})}const te=C(Z,[["render",ee]]),ae={};function se(V,a){return c(),d(p,null,[e(T,null,{default:t(()=>[e(u,null,{default:t(()=>a[0]||(a[0]=[n("Home")])),_:1,__:[0]}),e(u,null,{default:t(()=>a[1]||(a[1]=[n("Service")])),_:1,__:[1]}),e(u,null,{default:t(()=>a[2]||(a[2]=[n("Account")])),_:1,__:[2]})]),_:1}),e(T,{"align-tabs":"center"},{default:t(()=>[e(u,null,{default:t(()=>a[3]||(a[3]=[n("Home")])),_:1,__:[3]}),e(u,null,{default:t(()=>a[4]||(a[4]=[n("Service")])),_:1,__:[4]}),e(u,null,{default:t(()=>a[5]||(a[5]=[n("Account")])),_:1,__:[5]})]),_:1}),e(T,{"align-tabs":"end"},{default:t(()=>[e(u,null,{default:t(()=>a[6]||(a[6]=[n("Home")])),_:1,__:[6]}),e(u,null,{default:t(()=>a[7]||(a[7]=[n("Service")])),_:1,__:[7]}),e(u,null,{default:t(()=>a[8]||(a[8]=[n("Account")])),_:1,__:[8]})]),_:1})],64)}const ne=C(ae,[["render",se]]),ie={class:"d-flex gap-6"},oe={__name:"DemoTabsVerticalPill",setup(V){const a=I("window-1");return(o,s)=>(c(),d("div",ie,[l("div",null,[e(T,{modelValue:r(a),"onUpdate:modelValue":s[0]||(s[0]=i=>b(a)?a.value=i:null),direction:"vertical",class:"v-tabs-pill"},{default:t(()=>[e(u,null,{default:t(()=>[e(q,{start:"",icon:"ri-user-line"}),s[2]||(s[2]=n(" Option 1 "))]),_:1,__:[2]}),e(u,null,{default:t(()=>[e(q,{start:"",icon:"ri-lock-line"}),s[3]||(s[3]=n(" Option 2 "))]),_:1,__:[3]}),e(u,null,{default:t(()=>[e(q,{start:"",icon:"ri-rfid-line"}),s[4]||(s[4]=n(" Option 3 "))]),_:1,__:[4]})]),_:1},8,["modelValue"])]),e(x,{modelValue:r(a),"onUpdate:modelValue":s[1]||(s[1]=i=>b(a)?a.value=i:null)},{default:t(()=>[e(_,{value:"window-1"},{default:t(()=>s[5]||(s[5]=[l("p",null," Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero. ",-1),l("p",{class:"mb-0"}," Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et. ",-1)])),_:1,__:[5]}),e(_,{value:"window-2"},{default:t(()=>s[6]||(s[6]=[l("p",{class:"mb-0"}," Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis. ",-1)])),_:1,__:[6]}),e(_,{value:"window-3"},{default:t(()=>s[7]||(s[7]=[l("p",{class:"mb-0"}," Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. ",-1)])),_:1,__:[7]})]),_:1},8,["modelValue"])]))}},le={class:"d-flex"},ue={__name:"DemoTabsVertical",setup(V){const a=I(0);return(o,s)=>(c(),d("div",le,[e(T,{modelValue:r(a),"onUpdate:modelValue":s[0]||(s[0]=i=>b(a)?a.value=i:null),direction:"vertical"},{default:t(()=>[e(u,{"prepend-icon":"ri-user-line"},{default:t(()=>s[2]||(s[2]=[n(" Option 1 ")])),_:1,__:[2]}),e(u,{"prepend-icon":"ri-lock-line"},{default:t(()=>s[3]||(s[3]=[n(" Option 2 ")])),_:1,__:[3]}),e(u,{"prepend-icon":"ri-rfid-line"},{default:t(()=>s[4]||(s[4]=[n(" Option 3 ")])),_:1,__:[4]})]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":s[1]||(s[1]=i=>b(a)?a.value=i:null),class:"ms-3"},{default:t(()=>[e(_,null,{default:t(()=>s[5]||(s[5]=[l("p",null," Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero. ",-1),l("p",{class:"mb-0"}," Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et, tincidunt eget, semper nec, quam. Phasellus blandit leo ut odio. ",-1)])),_:1,__:[5]}),e(_,null,{default:t(()=>s[6]||(s[6]=[l("p",null," Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis. ",-1),l("p",{class:"mb-0"}," Donec venenatis vulputate lorem. Aenean viverra rhoncus pede. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. Fusce commodo aliquam arcu. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. ",-1)])),_:1,__:[6]}),e(_,null,{default:t(()=>s[7]||(s[7]=[l("p",null," Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. ",-1),l("p",{class:"mb-0"}," Cras sagittis. Phasellus nec sem in justo pellentesque facilisis. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nam at tortor in tellus interdum sagittis. ",-1)])),_:1,__:[7]})]),_:1},8,["modelValue"])]))}},re="Biscuit cheesecake gingerbread oat cake tiramisu. Marzipan tiramisu jelly-o muffin biscuit jelly cake pie. Chocolate cookie candy croissant brownie cupcake powder cheesecake. Biscuit sesame snaps biscuit topping tiramisu croissant.",ce={__name:"DemoTabsStacked",setup(V){const a=I("tab-1");return(o,s)=>(c(),d(p,null,[e(T,{modelValue:r(a),"onUpdate:modelValue":s[0]||(s[0]=i=>b(a)?a.value=i:null),grow:"",stacked:""},{default:t(()=>[e(u,{value:"tab-1"},{default:t(()=>[e(q,{icon:"ri-phone-line",class:"mb-2"}),s[2]||(s[2]=l("span",null,"Recent",-1))]),_:1,__:[2]}),e(u,{value:"tab-2"},{default:t(()=>[e(q,{icon:"ri-heart-line",class:"mb-2"}),s[3]||(s[3]=l("span",null,"Favorites",-1))]),_:1,__:[3]}),e(u,{value:"tab-3"},{default:t(()=>[e(q,{icon:"ri-account-box-line",class:"mb-2"}),s[4]||(s[4]=l("span",null,"Nearby",-1))]),_:1,__:[4]})]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":s[1]||(s[1]=i=>b(a)?a.value=i:null),class:"mt-5"},{default:t(()=>[(c(),d(p,null,g(3,i=>e(_,{key:i,value:`tab-${i}`},{default:t(()=>[n(k(re))]),_:2},1032,["value"])),64))]),_:1},8,["modelValue"])],64))}},me="Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.",de={__name:"DemoTabsBasicPill",setup(V){const a=I(0);return(o,s)=>(c(),d(p,null,[e(T,{modelValue:r(a),"onUpdate:modelValue":s[0]||(s[0]=i=>b(a)?a.value=i:null),class:"v-tabs-pill"},{default:t(()=>[e(u,null,{default:t(()=>s[2]||(s[2]=[n("Tab One")])),_:1,__:[2]}),e(u,null,{default:t(()=>s[3]||(s[3]=[n("Tab Two")])),_:1,__:[3]}),e(u,null,{default:t(()=>s[4]||(s[4]=[n("Tab Three")])),_:1,__:[4]})]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":s[1]||(s[1]=i=>b(a)?a.value=i:null),class:"mt-5"},{default:t(()=>[(c(),d(p,null,g(3,i=>e(_,{key:i},{default:t(()=>[n(k(me))]),_:2},1024)),64))]),_:1},8,["modelValue"])],64))}},pe="Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.",be={__name:"DemoTabsBasic",setup(V){const a=I(0);return(o,s)=>(c(),d(p,null,[e(T,{modelValue:r(a),"onUpdate:modelValue":s[0]||(s[0]=i=>b(a)?a.value=i:null)},{default:t(()=>[e(u,null,{default:t(()=>s[2]||(s[2]=[n("Tab One")])),_:1,__:[2]}),e(u,null,{default:t(()=>s[3]||(s[3]=[n("Tab Two")])),_:1,__:[3]}),e(u,null,{default:t(()=>s[4]||(s[4]=[n("Tab Three")])),_:1,__:[4]})]),_:1},8,["modelValue"]),e(x,{modelValue:r(a),"onUpdate:modelValue":s[1]||(s[1]=i=>b(a)?a.value=i:null),class:"mt-5"},{default:t(()=>[(c(),d(p,null,g(3,i=>e(_,{key:i},{default:t(()=>[n(k(pe))]),_:2},1024)),64))]),_:1},8,["modelValue"])],64))}},Te={ts:`<template>
  <!-- Default -->
  <VTabs>
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>

  <!-- Center -->
  <VTabs align-tabs="center">
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>

  <!-- End -->
  <VTabs align-tabs="end">
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>
</template>
`,js:`<template>
  <!-- Default -->
  <VTabs>
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>

  <!-- Center -->
  <VTabs align-tabs="center">
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>

  <!-- End -->
  <VTabs align-tabs="end">
    <VTab>Home</VTab>
    <VTab>Service</VTab>
    <VTab>Account</VTab>
  </VTabs>
</template>
`},Ve={ts:`<script lang="ts" setup>
const currentTab = ref(0)
const tabItemContent = 'Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.'
<\/script>

<template>
  <VTabs v-model="currentTab">
    <VTab>Tab One</VTab>
    <VTab>Tab Two</VTab>
    <VTab>Tab Three</VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in 3"
      :key="item"
    >
      {{ tabItemContent }}
    </VWindowItem>
  </VWindow>
</template>
`,js:`<script setup>
const currentTab = ref(0)
const tabItemContent = 'Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.'
<\/script>

<template>
  <VTabs v-model="currentTab">
    <VTab>Tab One</VTab>
    <VTab>Tab Two</VTab>
    <VTab>Tab Three</VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in 3"
      :key="item"
    >
      {{ tabItemContent }}
    </VWindowItem>
  </VWindow>
</template>
`},ve={ts:`<script lang="ts" setup>
const currentTab = ref(0)
const tabItemContent = 'Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    class="v-tabs-pill"
  >
    <VTab>Tab One</VTab>
    <VTab>Tab Two</VTab>
    <VTab>Tab Three</VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in 3"
      :key="item"
    >
      {{ tabItemContent }}
    </VWindowItem>
  </VWindow>
</template>
`,js:`<script setup>
const currentTab = ref(0)
const tabItemContent = 'Candy canes donut chupa chups candy canes lemon drops oat cake wafer. Cotton candy candy canes marzipan carrot cake. Sesame snaps lemon drops candy marzipan donut brownie tootsie roll. Icing croissant bonbon biscuit gummi bears. Pudding candy canes sugar plum cookie chocolate cake powder croissant.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    class="v-tabs-pill"
  >
    <VTab>Tab One</VTab>
    <VTab>Tab Two</VTab>
    <VTab>Tab Three</VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in 3"
      :key="item"
    >
      {{ tabItemContent }}
    </VWindowItem>
  </VWindow>
</template>
`},fe={ts:`<template>
  <VTabs
    next-icon="ri-arrow-right-line"
    prev-icon="ri-arrow-left-line"
    show-arrows
  >
    <VTab
      v-for="i in 10"
      :key="i"
    >
      Item {{ i }}
    </VTab>
  </VTabs>
</template>
`,js:`<template>
  <VTabs
    next-icon="ri-arrow-right-line"
    prev-icon="ri-arrow-left-line"
    show-arrows
  >
    <VTab
      v-for="i in 10"
      :key="i"
    >
      Item {{ i }}
    </VTab>
  </VTabs>
</template>
`},_e={ts:`<script lang="ts" setup>
const totalTabs = ref(3)
const currentTab = ref(0)

watch(totalTabs, newValue => {
  currentTab.value = newValue - 1
})
<\/script>

<template>
  <VTabs v-model="currentTab">
    <VTab
      v-for="n in totalTabs"
      :key="n"
      :value="n"
    >
      Tab {{ n }}
    </VTab>
  </VTabs>

  <!-- buttons -->
  <div class="text-center mt-9">
    <VBtn
      :disabled="!totalTabs"
      variant="text"
      @click="totalTabs--"
    >
      Remove Tab
    </VBtn>

    <VBtn
      variant="text"
      @click="totalTabs++"
    >
      Add Tab
    </VBtn>
  </div>
</template>
`,js:`<script setup>
const totalTabs = ref(3)
const currentTab = ref(0)

watch(totalTabs, newValue => {
  currentTab.value = newValue - 1
})
<\/script>

<template>
  <VTabs v-model="currentTab">
    <VTab
      v-for="n in totalTabs"
      :key="n"
      :value="n"
    >
      Tab {{ n }}
    </VTab>
  </VTabs>

  <!-- buttons -->
  <div class="text-center mt-9">
    <VBtn
      :disabled="!totalTabs"
      variant="text"
      @click="totalTabs--"
    >
      Remove Tab
    </VBtn>

    <VBtn
      variant="text"
      @click="totalTabs++"
    >
      Add Tab
    </VBtn>
  </div>
</template>
`},we={ts:`<template>
  <VTabs fixed-tabs>
    <VTab>
      Fixed Tab 1
    </VTab>
    <VTab>
      Fixed Tab 2
    </VTab>
    <VTab>
      Fixed Tab 3
    </VTab>
    <VTab>
      Fixed Tab 4
    </VTab>
  </VTabs>
</template>
`,js:`<template>
  <VTabs fixed-tabs>
    <VTab>
      Fixed Tab 1
    </VTab>
    <VTab>
      Fixed Tab 2
    </VTab>
    <VTab>
      Fixed Tab 3
    </VTab>
    <VTab>
      Fixed Tab 4
    </VTab>
  </VTabs>
</template>
`},ge={ts:`<script lang="ts" setup>
const currentTab = ref('Appetizers')
const items = ['Appetizers', 'Entrees', 'Deserts', 'Cocktails']
const tabItemText = 'hortbread chocolate bar marshmallow bear claw tiramisu chocolate cookie wafer. Gummies sweet brownie brownie marshmallow chocolate cake pastry. Topping macaroon shortbread liquorice dragée macaroon.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
  >
    <VTab
      v-for="item in items"
      :key="item"
      :value="item"
    >
      {{ item }}
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-6"
  >
    <VWindowItem
      v-for="item in items"
      :key="item"
      :value="item"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>
</template>
`,js:`<script setup>
const currentTab = ref('Appetizers')

const items = [
  'Appetizers',
  'Entrees',
  'Deserts',
  'Cocktails',
]

const tabItemText = 'hortbread chocolate bar marshmallow bear claw tiramisu chocolate cookie wafer. Gummies sweet brownie brownie marshmallow chocolate cake pastry. Topping macaroon shortbread liquorice dragée macaroon.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
  >
    <VTab
      v-for="item in items"
      :key="item"
      :value="item"
    >
      {{ item }}
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-6"
  >
    <VWindowItem
      v-for="item in items"
      :key="item"
      :value="item"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>
</template>
`},ke={ts:`<template>
  <VTabs show-arrows>
    <VTab
      v-for="i in 10"
      :key="i"
      :value="i"
    >
      Item {{ i }}
    </VTab>
  </VTabs>
</template>
`,js:`<template>
  <VTabs show-arrows>
    <VTab
      v-for="i in 10"
      :key="i"
      :value="i"
    >
      Item {{ i }}
    </VTab>
  </VTabs>
</template>
`},Ie={ts:`<script lang="ts" setup>
const currentTab = ref(1)
const items = ['Appetizers', 'Entrees', 'Deserts', 'Cocktails']
const tabItemText = 'Chocolate cake marshmallow toffee sweet caramels tootsie roll chocolate bar. Chocolate candy lemon drops cupcake macaroon liquorice. Icing tiramisu cake pastry jujubes lollipop gummies sugar plum pie.'
const totalTabs = items.length

const preTab = () => {
  if (currentTab.value !== 1)
    currentTab.value -= 1
}

const nextTab = () => {
  if (currentTab.value !== totalTabs)
    currentTab.value += 1
}
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
  >
    <VTab
      v-for="item in items.length"
      :key="item"
      :value="item"
    >
      {{ items[item - 1] }}
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in items.length"
      :key="item"
      :value="item"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>

  <div class="text-center">
    <VBtn
      variant="text"
      :disabled="currentTab === 1"
      @click="preTab"
    >
      Previous
    </VBtn>

    <VBtn
      variant="text"
      :disabled="currentTab === totalTabs"
      @click="nextTab"
    >
      Next
    </VBtn>
  </div>
</template>
`,js:`<script setup>
const currentTab = ref(1)

const items = [
  'Appetizers',
  'Entrees',
  'Deserts',
  'Cocktails',
]

const tabItemText = 'Chocolate cake marshmallow toffee sweet caramels tootsie roll chocolate bar. Chocolate candy lemon drops cupcake macaroon liquorice. Icing tiramisu cake pastry jujubes lollipop gummies sugar plum pie.'
const totalTabs = items.length

const preTab = () => {
  if (currentTab.value !== 1)
    currentTab.value -= 1
}

const nextTab = () => {
  if (currentTab.value !== totalTabs)
    currentTab.value += 1
}
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
  >
    <VTab
      v-for="item in items.length"
      :key="item"
      :value="item"
    >
      {{ items[item - 1] }}
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="item in items.length"
      :key="item"
      :value="item"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>

  <div class="text-center">
    <VBtn
      variant="text"
      :disabled="currentTab === 1"
      @click="preTab"
    >
      Previous
    </VBtn>

    <VBtn
      variant="text"
      :disabled="currentTab === totalTabs"
      @click="nextTab"
    >
      Next
    </VBtn>
  </div>
</template>
`},ye={ts:`<script lang="ts" setup>
const currentTab = ref('tab-1')
const tabItemText = 'Biscuit cheesecake gingerbread oat cake tiramisu. Marzipan tiramisu jelly-o muffin biscuit jelly cake pie. Chocolate cookie candy croissant brownie cupcake powder cheesecake. Biscuit sesame snaps biscuit topping tiramisu croissant.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
    stacked
  >
    <VTab value="tab-1">
      <VIcon
        icon="ri-phone-line"
        class="mb-2"
      />
      <span>Recent</span>
    </VTab>

    <VTab value="tab-2">
      <VIcon
        icon="ri-heart-line"
        class="mb-2"
      />
      <span>Favorites</span>
    </VTab>

    <VTab value="tab-3">
      <VIcon
        icon="ri-account-box-line"
        class="mb-2"
      />
      <span>Nearby</span>
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="i in 3"
      :key="i"
      :value="\`tab-\${i}\`"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>
</template>
`,js:`<script setup>
const currentTab = ref('tab-1')
const tabItemText = 'Biscuit cheesecake gingerbread oat cake tiramisu. Marzipan tiramisu jelly-o muffin biscuit jelly cake pie. Chocolate cookie candy croissant brownie cupcake powder cheesecake. Biscuit sesame snaps biscuit topping tiramisu croissant.'
<\/script>

<template>
  <VTabs
    v-model="currentTab"
    grow
    stacked
  >
    <VTab value="tab-1">
      <VIcon
        icon="ri-phone-line"
        class="mb-2"
      />
      <span>Recent</span>
    </VTab>

    <VTab value="tab-2">
      <VIcon
        icon="ri-heart-line"
        class="mb-2"
      />
      <span>Favorites</span>
    </VTab>

    <VTab value="tab-3">
      <VIcon
        icon="ri-account-box-line"
        class="mb-2"
      />
      <span>Nearby</span>
    </VTab>
  </VTabs>

  <VWindow
    v-model="currentTab"
    class="mt-5"
  >
    <VWindowItem
      v-for="i in 3"
      :key="i"
      :value="\`tab-\${i}\`"
    >
      {{ tabItemText }}
    </VWindowItem>
  </VWindow>
</template>
`},xe={ts:`<script setup lang="ts">
const currentTab = ref(0)
<\/script>

<template>
  <div class="d-flex">
    <VTabs
      v-model="currentTab"
      direction="vertical"
    >
      <VTab prepend-icon="ri-user-line">
        Option 1
      </VTab>
      <VTab prepend-icon="ri-lock-line">
        Option 2
      </VTab>
      <VTab prepend-icon="ri-rfid-line">
        Option 3
      </VTab>
    </VTabs>
    <VWindow
      v-model="currentTab"
      class="ms-3"
    >
      <VWindowItem>
        <p>
          Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero.
        </p>
        <p class="mb-0">
          Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et, tincidunt eget, semper nec, quam. Phasellus blandit leo ut odio.
        </p>
      </VWindowItem>
      <VWindowItem>
        <p>
          Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis.
        </p>
        <p class="mb-0">
          Donec venenatis vulputate lorem. Aenean viverra rhoncus pede. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. Fusce commodo aliquam arcu. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi.
        </p>
      </VWindowItem>
      <VWindowItem>
        <p>
          Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo.
        </p>
        <p class="mb-0">
          Cras sagittis. Phasellus nec sem in justo pellentesque facilisis. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nam at tortor in tellus interdum sagittis.
        </p>
      </VWindowItem>
    </VWindow>
  </div>
</template>
`,js:`<script setup>
const currentTab = ref(0)
<\/script>

<template>
  <div class="d-flex">
    <VTabs
      v-model="currentTab"
      direction="vertical"
    >
      <VTab prepend-icon="ri-user-line">
        Option 1
      </VTab>
      <VTab prepend-icon="ri-lock-line">
        Option 2
      </VTab>
      <VTab prepend-icon="ri-rfid-line">
        Option 3
      </VTab>
    </VTabs>
    <VWindow
      v-model="currentTab"
      class="ms-3"
    >
      <VWindowItem>
        <p>
          Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero.
        </p>
        <p class="mb-0">
          Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et, tincidunt eget, semper nec, quam. Phasellus blandit leo ut odio.
        </p>
      </VWindowItem>
      <VWindowItem>
        <p>
          Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis.
        </p>
        <p class="mb-0">
          Donec venenatis vulputate lorem. Aenean viverra rhoncus pede. In dui magna, posuere eget, vestibulum et, tempor auctor, justo. Fusce commodo aliquam arcu. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi.
        </p>
      </VWindowItem>
      <VWindowItem>
        <p>
          Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo.
        </p>
        <p class="mb-0">
          Cras sagittis. Phasellus nec sem in justo pellentesque facilisis. Proin sapien ipsum, porta a, auctor quis, euismod ut, mi. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nam at tortor in tellus interdum sagittis.
        </p>
      </VWindowItem>
    </VWindow>
  </div>
</template>
`},qe={ts:`<script setup lang="ts">
const currentTab = ref('window-1')
<\/script>

<template>
  <div class="d-flex gap-6">
    <div>
      <VTabs
        v-model="currentTab"
        direction="vertical"
        class="v-tabs-pill"
      >
        <VTab>
          <VIcon
            start
            icon="ri-user-line"
          />
          Option 1
        </VTab>

        <VTab>
          <VIcon
            start
            icon="ri-lock-line"
          />
          Option 2
        </VTab>

        <VTab>
          <VIcon
            start
            icon="ri-rfid-line"
          />
          Option 3
        </VTab>
      </VTabs>
    </div>

    <VWindow v-model="currentTab">
      <VWindowItem value="window-1">
        <p>
          Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero.
        </p>

        <p class="mb-0">
          Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et.
        </p>
      </VWindowItem>

      <VWindowItem value="window-2">
        <p class="mb-0">
          Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis.
        </p>
      </VWindowItem>

      <VWindowItem value="window-3">
        <p class="mb-0">
          Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo.
        </p>
      </VWindowItem>
    </VWindow>
  </div>
</template>
`,js:`<script setup>
const currentTab = ref('window-1')
<\/script>

<template>
  <div class="d-flex gap-6">
    <div>
      <VTabs
        v-model="currentTab"
        direction="vertical"
        class="v-tabs-pill"
      >
        <VTab>
          <VIcon
            start
            icon="ri-user-line"
          />
          Option 1
        </VTab>

        <VTab>
          <VIcon
            start
            icon="ri-lock-line"
          />
          Option 2
        </VTab>

        <VTab>
          <VIcon
            start
            icon="ri-rfid-line"
          />
          Option 3
        </VTab>
      </VTabs>
    </div>

    <VWindow v-model="currentTab">
      <VWindowItem value="window-1">
        <p>
          Sed aliquam ultrices mauris. Donec posuere vulputate arcu. Morbi ac felis. Etiam feugiat lorem non metus. Sed a libero.
        </p>

        <p class="mb-0">
          Phasellus dolor. Fusce neque. Fusce fermentum odio nec arcu. Pellentesque libero tortor, tincidunt et.
        </p>
      </VWindowItem>

      <VWindowItem value="window-2">
        <p class="mb-0">
          Morbi nec metus. Suspendisse faucibus, nunc et pellentesque egestas, lacus ante convallis tellus, vitae iaculis lacus elit id tortor. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a orci. Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Nunc sed turpis.
        </p>
      </VWindowItem>

      <VWindowItem value="window-3">
        <p class="mb-0">
          Fusce a quam. Phasellus nec sem in justo pellentesque facilisis. Nam eget dui. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. In dui magna, posuere eget, vestibulum et, tempor auctor, justo.
        </p>
      </VWindowItem>
    </VWindow>
  </div>
</template>
`},Me={__name:"tabs",setup(V){return(a,o)=>{const s=be,i=h,m=de,P=ce,w=ue,v=oe,S=ne,D=te,j=Y,F=K,B=G,N=H,A=U;return c(),y(z,{class:"match-height"},{default:t(()=>[e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Basic",code:r(Ve)},{default:t(()=>[o[0]||(o[0]=l("p",null,[n("The "),l("code",null,"v-tabs"),n(" component is used for hiding content behind a selectable item.")],-1)),e(s)]),_:1,__:[0]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Basic Pill",code:r(ve)},{default:t(()=>[o[1]||(o[1]=l("p",null,[n("Use our custom class "),l("code",null,".v-tabs-pill"),n(" along with "),l("code",null,"v-tabs"),n(" component to style pill tabs.")],-1)),e(m)]),_:1,__:[1]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Stacked",code:r(ye)},{default:t(()=>[o[2]||(o[2]=l("p",null,[n("Using "),l("code",null,"stacked"),n(" prop you can have buttons that use both icons and text.")],-1)),e(P)]),_:1,__:[2]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Vertical",code:r(xe)},{default:t(()=>[o[3]||(o[3]=l("p",null,[n("The "),l("code",null,"vertical"),n(" prop allows for "),l("code",null,"v-tab"),n(" components to stack vertically.")],-1)),e(w)]),_:1,__:[3]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Vertical Pill",code:r(qe)},{default:t(()=>[o[4]||(o[4]=l("p",null,"Use our custom class .v-tabs-pill along with v-tabs component to style pill tabs.",-1)),e(v)]),_:1,__:[4]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Alignment",code:r(Te)},{default:t(()=>[o[5]||(o[5]=l("p",null,[n("Use "),l("code",null,"align-tabs"),n(" prop to change the tabs alignment.")],-1)),e(S)]),_:1,__:[5]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Pagination",code:r(ke)},{default:t(()=>[o[6]||(o[6]=l("p",null,"If the tab items overflow their container, pagination controls will appear on desktop.",-1)),e(D)]),_:1,__:[6]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Custom Icons",code:r(fe)},{default:t(()=>[o[7]||(o[7]=l("p",null,[l("code",null,"prev-icon"),n(" and "),l("code",null,"next-icon"),n(" props can be used for applying custom pagination icons.")],-1)),e(j)]),_:1,__:[7]},8,["code"])]),_:1}),e(f,{cols:"12"},{default:t(()=>[e(i,{title:"Fixed",code:r(we)},{default:t(()=>[o[8]||(o[8]=l("p",null,[n("The "),l("code",null,"fixed-tabs"),n(" prop forces "),l("code",null,"v-tab"),n(" to take up all available space up to the maximum width (300px).")],-1)),e(F)]),_:1,__:[8]},8,["code"])]),_:1}),e(f,{cols:"12"},{default:t(()=>[e(i,{title:"Grow",code:r(ge)},{default:t(()=>[o[9]||(o[9]=l("p",null,[n("The "),l("code",null,"grow"),n(" prop will make the tab items take up all available space with no limit.")],-1)),e(B)]),_:1,__:[9]},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Programmatic Navigation",code:r(Ie)},{default:t(()=>[e(N)]),_:1},8,["code"])]),_:1}),e(f,{cols:"12",md:"6"},{default:t(()=>[e(i,{title:"Dynamic",code:r(_e)},{default:t(()=>[o[10]||(o[10]=l("p",null,[n("Tabs can be dynamically added and removed. This allows you to update to any number and the "),l("code",null,"v-tabs"),n(" component will react.")],-1)),e(A)]),_:1,__:[10]},8,["code"])]),_:1})]),_:1})}}};export{Me as default};
