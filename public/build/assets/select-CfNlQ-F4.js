import{r as b,o as d,f as u,e as a,b as e,d as o,t as F,aG as f,l as n,b1 as D,b2 as I,b3 as B,b4 as w,bc as O,s as i}from"./main-B1QOifSU.js";import{V as x}from"./VChip-DwiGpai4.js";import{V as k}from"./VAvatar-DdWXk1nZ.js";import{V as m}from"./VSelect-DUyjUuZv.js";import{a as c,V as _}from"./VRow-DJDt7Tdn.js";import{_ as y}from"./AppCardCode-Bo0qHg_x.js";import"./VSlideGroup-DUwVsaBf.js";import"./VImg-5F4su6me.js";import"./VTextField-ChdaOKHE.js";/* empty css                   */import"./VCounter-Z7HHfeJZ.js";import"./VField-B-NjxCIk.js";import"./form-D1IVdaBk.js";import"./VInput-CEmVy47o.js";import"./VList-BEyKRJkv.js";import"./ssrBoot-BeySvEPC.js";import"./createSimpleFunctional-CL3gyP-m.js";import"./VDivider-SgM3IBzR.js";import"./dialog-transition-BHXfhDOt.js";import"./VMenu-BZ5PnqSj.js";import"./VCheckboxBtn-DO56aW7r.js";import"./VSelectionControl-wnX9S7dP.js";/* empty css              */import"./vue3-perfect-scrollbar-Bht0gmbX.js";import"./VCard-CrYBt13Q.js";import"./VCardText-GEOzdVgt.js";const G={__name:"DemoSelectSelectionSlot",setup(S){const l=[{name:"Sandra Adams",avatar:D},{name:"Ali Connors",avatar:I},{name:"Trevor Hansen",avatar:B},{name:"Tucker Smith",avatar:w},{name:"Britta Holt",avatar:O}],t=b(["Sandra Adams"]);return(p,s)=>(d(),u(m,{modelValue:n(t),"onUpdate:modelValue":s[0]||(s[0]=r=>f(t)?t.value=r:null),items:l,"item-title":"name","item-value":"name",label:"Select Item",placeholder:"Select Item",multiple:"",clearable:"","clear-icon":"ri-close-line"},{selection:a(({item:r})=>[e(x,null,{prepend:a(()=>[e(k,{start:"",image:r.raw.avatar},null,8,["image"])]),default:a(()=>[o("span",null,F(r.title),1)]),_:2},1024)]),_:1},8,["modelValue"]))}},$={__name:"DemoSelectMultiple",setup(S){const l=b(["Alabama"]),t=["Alabama","Alaska","American Samoa","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District of Columbia","Federated States of Micronesia","Florida","Georgia","Guam"];return(p,s)=>(d(),u(m,{modelValue:n(l),"onUpdate:modelValue":s[0]||(s[0]=r=>f(l)?l.value=r:null),items:t,"menu-props":{maxHeight:"400"},label:"Select",multiple:"","persistent-hint":"",placeholder:"Select State"},null,8,["modelValue"]))}},N={__name:"DemoSelectMenuProps",setup(S){const l=["Foo","Bar","Fizz","Buzz"];return(t,p)=>(d(),u(m,{items:l,"menu-props":{transition:"scroll-y-transition"},label:"Label",placeholder:"Select Item"}))}},j={__name:"DemoSelectChips",setup(S){const l=["foo","bar","fizz","buzz"],t=b(["foo","bar","fizz","buzz"]);return(p,s)=>(d(),u(m,{modelValue:n(t),"onUpdate:modelValue":s[0]||(s[0]=r=>f(t)?t.value=r:null),items:l,placeholder:"Select Item",label:"Chips",chips:"",multiple:""},null,8,["modelValue"]))}},T={__name:"DemoSelectIcons",setup(S){const l=b("Florida"),t=b("Texas"),p=["Alabama","Alaska","American Samoa","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District of Columbia","Federated States of Micronesia","Florida","Georgia","Guam"];return(s,r)=>(d(),u(_,null,{default:a(()=>[e(c,{cols:"12"},{default:a(()=>[e(m,{modelValue:n(l),"onUpdate:modelValue":r[0]||(r[0]=v=>f(l)?l.value=v:null),items:p,label:"Select","prepend-icon":"ri-map-2-line","single-line":"",variant:"filled",placeholder:"Select State"},null,8,["modelValue"])]),_:1}),e(c,{cols:"12"},{default:a(()=>[e(m,{modelValue:n(t),"onUpdate:modelValue":r[1]||(r[1]=v=>f(t)?t.value=v:null),items:p,"append-icon":"ri-map-2-line",label:"Select","single-line":"",variant:"filled",placeholder:"Select State"},null,8,["modelValue"])]),_:1})]),_:1}))}},U={__name:"DemoSelectCustomTextAndValue",setup(S){const l=b({state:"Florida",abbr:"FL"}),t=[{state:"Florida",abbr:"FL"},{state:"Georgia",abbr:"GA"},{state:"Nebraska",abbr:"NE"},{state:"California",abbr:"CA"},{state:"New York",abbr:"NY"}];return(p,s)=>(d(),u(m,{modelValue:n(l),"onUpdate:modelValue":s[0]||(s[0]=r=>f(l)?l.value=r:null),hint:`${n(l).state}, ${n(l).abbr}`,items:t,"item-title":"state","item-value":"abbr",label:"Select","persistent-hint":"","return-object":"","single-line":"",placeholder:"Select State"},null,8,["modelValue","hint"]))}},M={__name:"DemoSelectVariant",setup(S){const l=["Foo","Bar","Fizz","Buzz"];return(t,p)=>(d(),u(_,null,{default:a(()=>[e(c,{cols:"12",sm:"6"},{default:a(()=>[e(m,{items:l,label:"Outlined",placeholder:"Select Item"})]),_:1}),e(c,{cols:"12",sm:"6"},{default:a(()=>[e(m,{items:l,label:"Filled",placeholder:"Select Item",variant:"filled"})]),_:1}),e(c,{cols:"12",sm:"6"},{default:a(()=>[e(m,{items:l,label:"Solo",placeholder:"Select Item",variant:"solo"})]),_:1}),e(c,{cols:"12",sm:"6"},{default:a(()=>[e(m,{items:l,label:"Plain",placeholder:"Select Item",variant:"plain"})]),_:1}),e(c,{cols:"12",sm:"6"},{default:a(()=>[e(m,{items:l,label:"Underlined",variant:"underlined",placeholder:"Select Item"})]),_:1})]),_:1}))}},R={__name:"DemoSelectDensity",setup(S){const l=["Foo","Bar","Fizz","Buzz"];return(t,p)=>(d(),u(m,{items:l,label:"Density",density:"compact",placeholder:"Select Item"}))}},H={__name:"DemoSelectBasic",setup(S){const l=["Foo","Bar","Fizz","Buzz"];return(t,p)=>(d(),u(m,{items:l,label:"Standard",placeholder:"Select Item",eager:""}))}},L={ts:`<script lang="ts" setup>
const items = ['Foo', 'Bar', 'Fizz', 'Buzz']
<\/script>

<template>
  <VSelect
    :items="items"
    label="Standard"
    placeholder="Select Item"
    eager
  />
</template>
`,js:`<script setup>
const items = [
  'Foo',
  'Bar',
  'Fizz',
  'Buzz',
]
<\/script>

<template>
  <VSelect
    :items="items"
    label="Standard"
    placeholder="Select Item"
    eager
  />
</template>
`},P={ts:`<script lang="ts" setup>
const items = ['foo', 'bar', 'fizz', 'buzz']
const selected = ref(['foo', 'bar', 'fizz', 'buzz'])
<\/script>

<template>
  <VSelect
    v-model="selected"
    :items="items"
    placeholder="Select Item"
    label="Chips"
    chips
    multiple
  />
</template>
`,js:`<script setup>
const items = [
  'foo',
  'bar',
  'fizz',
  'buzz',
]

const selected = ref([
  'foo',
  'bar',
  'fizz',
  'buzz',
])
<\/script>

<template>
  <VSelect
    v-model="selected"
    :items="items"
    placeholder="Select Item"
    label="Chips"
    chips
    multiple
  />
</template>
`},Y={ts:`<script lang="ts" setup>
const selectedOption = ref({ state: 'Florida', abbr: 'FL' })

const items = [
  { state: 'Florida', abbr: 'FL' },
  { state: 'Georgia', abbr: 'GA' },
  { state: 'Nebraska', abbr: 'NE' },
  { state: 'California', abbr: 'CA' },
  { state: 'New York', abbr: 'NY' },
]
<\/script>

<template>
  <VSelect
    v-model="selectedOption"
    :hint="\`\${selectedOption.state}, \${selectedOption.abbr}\`"
    :items="items"
    item-title="state"
    item-value="abbr"
    label="Select"
    persistent-hint
    return-object
    single-line
    placeholder="Select State"
  />
</template>
`,js:`<script setup>
const selectedOption = ref({
  state: 'Florida',
  abbr: 'FL',
})

const items = [
  {
    state: 'Florida',
    abbr: 'FL',
  },
  {
    state: 'Georgia',
    abbr: 'GA',
  },
  {
    state: 'Nebraska',
    abbr: 'NE',
  },
  {
    state: 'California',
    abbr: 'CA',
  },
  {
    state: 'New York',
    abbr: 'NY',
  },
]
<\/script>

<template>
  <VSelect
    v-model="selectedOption"
    :hint="\`\${selectedOption.state}, \${selectedOption.abbr}\`"
    :items="items"
    item-title="state"
    item-value="abbr"
    label="Select"
    persistent-hint
    return-object
    single-line
    placeholder="Select State"
  />
</template>
`},E={ts:`<script lang="ts" setup>
const items = ['Foo', 'Bar', 'Fizz', 'Buzz']
<\/script>

<template>
  <VSelect
    :items="items"
    label="Density"
    density="compact"
    placeholder="Select Item"
  />
</template>
`,js:`<script setup>
const items = [
  'Foo',
  'Bar',
  'Fizz',
  'Buzz',
]
<\/script>

<template>
  <VSelect
    :items="items"
    label="Density"
    density="compact"
    placeholder="Select Item"
  />
</template>
`},q={ts:`<script lang="ts" setup>
const selectedOption1 = ref('Florida')
const selectedOption2 = ref('Texas')

const states = [
  'Alabama',
  'Alaska',
  'American Samoa',
  'Arizona',
  'Arkansas',
  'California',
  'Colorado',
  'Connecticut',
  'Delaware',
  'District of Columbia',
  'Federated States of Micronesia',
  'Florida',
  'Georgia',
  'Guam',
]
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VSelect
        v-model="selectedOption1"
        :items="states"
        label="Select"
        prepend-icon="ri-map-2-line"
        single-line
        variant="filled"
        placeholder="Select State"
      />
    </VCol>

    <VCol cols="12">
      <VSelect
        v-model="selectedOption2"
        :items="states"
        append-icon="ri-map-2-line"
        label="Select"
        single-line
        variant="filled"
        placeholder="Select State"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const selectedOption1 = ref('Florida')
const selectedOption2 = ref('Texas')

const states = [
  'Alabama',
  'Alaska',
  'American Samoa',
  'Arizona',
  'Arkansas',
  'California',
  'Colorado',
  'Connecticut',
  'Delaware',
  'District of Columbia',
  'Federated States of Micronesia',
  'Florida',
  'Georgia',
  'Guam',
]
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VSelect
        v-model="selectedOption1"
        :items="states"
        label="Select"
        prepend-icon="ri-map-2-line"
        single-line
        variant="filled"
        placeholder="Select State"
      />
    </VCol>

    <VCol cols="12">
      <VSelect
        v-model="selectedOption2"
        :items="states"
        append-icon="ri-map-2-line"
        label="Select"
        single-line
        variant="filled"
        placeholder="Select State"
      />
    </VCol>
  </VRow>
</template>
`},J={ts:`<script lang="ts" setup>
const items = ['Foo', 'Bar', 'Fizz', 'Buzz']
<\/script>

<template>
  <VSelect
    :items="items"
    :menu-props="{ transition: 'scroll-y-transition' }"
    label="Label"
    placeholder="Select Item"
  />
</template>
`,js:`<script setup>
const items = [
  'Foo',
  'Bar',
  'Fizz',
  'Buzz',
]
<\/script>

<template>
  <VSelect
    :items="items"
    :menu-props="{ transition: 'scroll-y-transition' }"
    label="Label"
    placeholder="Select Item"
  />
</template>
`},K={ts:`<script lang="ts" setup>
const selectedOptions = ref(['Alabama'])

const states = [
  'Alabama',
  'Alaska',
  'American Samoa',
  'Arizona',
  'Arkansas',
  'California',
  'Colorado',
  'Connecticut',
  'Delaware',
  'District of Columbia',
  'Federated States of Micronesia',
  'Florida',
  'Georgia',
  'Guam',
]
<\/script>

<template>
  <VSelect
    v-model="selectedOptions"
    :items="states"
    :menu-props="{ maxHeight: '400' }"
    label="Select"
    multiple
    persistent-hint
    placeholder="Select State"
  />
</template>
`,js:`<script setup>
const selectedOptions = ref(['Alabama'])

const states = [
  'Alabama',
  'Alaska',
  'American Samoa',
  'Arizona',
  'Arkansas',
  'California',
  'Colorado',
  'Connecticut',
  'Delaware',
  'District of Columbia',
  'Federated States of Micronesia',
  'Florida',
  'Georgia',
  'Guam',
]
<\/script>

<template>
  <VSelect
    v-model="selectedOptions"
    :items="states"
    :menu-props="{ maxHeight: '400' }"
    label="Select"
    multiple
    persistent-hint
    placeholder="Select State"
  />
</template>
`},Q={ts:`<script lang="ts" setup>
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'

const items: { name: string; avatar: string }[] = [
  { name: 'Sandra Adams', avatar: avatar1 },
  { name: 'Ali Connors', avatar: avatar2 },
  { name: 'Trevor Hansen', avatar: avatar3 },
  { name: 'Tucker Smith', avatar: avatar4 },
  { name: 'Britta Holt', avatar: avatar5 },
]

const value = ref(['Sandra Adams'])
<\/script>

<template>
  <VSelect
    v-model="value"
    :items="items"
    item-title="name"
    item-value="name"
    label="Select Item"
    placeholder="Select Item"
    multiple
    clearable
    clear-icon="ri-close-line"
  >
    <template #selection="{ item }">
      <VChip>
        <template #prepend>
          <VAvatar
            start
            :image="item.raw.avatar"
          />
        </template>

        <span>{{ item.title }}</span>
      </VChip>
    </template>
  </VSelect>
</template>
`,js:`<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'

const items = [
  {
    name: 'Sandra Adams',
    avatar: avatar1,
  },
  {
    name: 'Ali Connors',
    avatar: avatar2,
  },
  {
    name: 'Trevor Hansen',
    avatar: avatar3,
  },
  {
    name: 'Tucker Smith',
    avatar: avatar4,
  },
  {
    name: 'Britta Holt',
    avatar: avatar5,
  },
]

const value = ref(['Sandra Adams'])
<\/script>

<template>
  <VSelect
    v-model="value"
    :items="items"
    item-title="name"
    item-value="name"
    label="Select Item"
    placeholder="Select Item"
    multiple
    clearable
    clear-icon="ri-close-line"
  >
    <template #selection="{ item }">
      <VChip>
        <template #prepend>
          <VAvatar
            start
            :image="item.raw.avatar"
          />
        </template>

        <span>{{ item.title }}</span>
      </VChip>
    </template>
  </VSelect>
</template>
`},W={ts:`<script lang="ts" setup>
const items = ['Foo', 'Bar', 'Fizz', 'Buzz']
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Outlined"
        placeholder="Select Item"
      />
    </VCol>
    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Filled"
        placeholder="Select Item"
        variant="filled"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Solo"
        placeholder="Select Item"
        variant="solo"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Plain"
        placeholder="Select Item"
        variant="plain"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Underlined"
        variant="underlined"
        placeholder="Select Item"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const items = [
  'Foo',
  'Bar',
  'Fizz',
  'Buzz',
]
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Outlined"
        placeholder="Select Item"
      />
    </VCol>
    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Filled"
        placeholder="Select Item"
        variant="filled"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Solo"
        placeholder="Select Item"
        variant="solo"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Plain"
        placeholder="Select Item"
        variant="plain"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VSelect
        :items="items"
        label="Underlined"
        variant="underlined"
        placeholder="Select Item"
      />
    </VCol>
  </VRow>
</template>
`},Ae={__name:"select",setup(S){return(l,t)=>{const p=H,s=y,r=R,v=M,V=U,C=T,z=j,h=N,g=$,A=G;return d(),u(_,null,{default:a(()=>[e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Basic",code:n(L)},{default:a(()=>[t[0]||(t[0]=o("p",null,"Select fields components are used for collecting user provided information from a list of options.",-1)),e(p)]),_:1,__:[0]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Density",code:n(E)},{default:a(()=>[t[1]||(t[1]=o("p",null,[i("You can use "),o("code",null,"density"),i(" prop to reduce the field height and lower max height of list items.")],-1)),e(r)]),_:1,__:[1]},8,["code"])]),_:1}),e(c,{cols:"12"},{default:a(()=>[e(s,{title:"Variant",code:n(W)},{default:a(()=>[t[2]||(t[2]=o("p",null,[i(" Use "),o("code",null,"filled"),i(", "),o("code",null,"outlined"),i(", "),o("code",null,"solo"),i(", "),o("code",null,"underlined"),i(" and "),o("code",null,"plain"),i(" options of "),o("code",null,"variant"),i(" prop to change appearance of select. ")],-1)),e(v)]),_:1,__:[2]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Custom text and value",code:n(Y)},{default:a(()=>[t[3]||(t[3]=o("p",null,"You can specify the specific properties within your items array that correspond to the title and value fields. In this example we also use the return-object prop which will return the entire object of the selected item on selection.",-1)),e(V)]),_:1,__:[3]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Icons",code:n(q)},{default:a(()=>[t[4]||(t[4]=o("p",null,[i("Use a custom "),o("code",null,"prepend"),i(" or "),o("code",null,"appended"),i(" icon.")],-1)),e(C)]),_:1,__:[4]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Chips",code:n(P)},{default:a(()=>[t[5]||(t[5]=o("p",null,[i("Use "),o("code",null,"chips"),i(" prop to make selected option as chip.")],-1)),e(z)]),_:1,__:[5]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Menu Props",code:n(J)},{default:a(()=>[t[6]||(t[6]=o("p",null,[i("Custom props can be passed directly to "),o("code",null,"v-menu"),i(" using "),o("code",null,"menuProps"),i(" prop.")],-1)),e(h)]),_:1,__:[6]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Multiple",code:n(K)},{default:a(()=>[t[7]||(t[7]=o("p",null,[i("Use "),o("code",null,"multiple"),i(" prop to select multiple option.")],-1)),e(g)]),_:1,__:[7]},8,["code"])]),_:1}),e(c,{cols:"12",md:"6"},{default:a(()=>[e(s,{title:"Selection slot",code:n(Q)},{default:a(()=>[t[8]||(t[8]=o("p",null,[i("The "),o("code",null,"selection"),i(" slot can be used to customize the way selected values are shown in the input.")],-1)),e(A)]),_:1,__:[8]},8,["code"])]),_:1})]),_:1})}}};export{Ae as default};
