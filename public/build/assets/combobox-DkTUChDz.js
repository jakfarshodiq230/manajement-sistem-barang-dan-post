import{V as p}from"./VCombobox-_hKV0Qa5.js";import{r as c,o as V,f as b,aG as d,l as a,w as _,e as s,b as t,s as n,d as i,t as C,aL as I}from"./main-NUGZQBXg.js";import{a as P,b as D}from"./VList-LquT_LMq.js";import{a as r,V as y}from"./VRow-BCAM7yo-.js";import{V as w}from"./VChip-DsKEM71b.js";import{V as U}from"./VAvatar-CVj9uwcK.js";import{_ as L}from"./AppCardCode-Bol9coa1.js";import"./VSelect-Ifd_pS_X.js";import"./VTextField-DlXfb9fV.js";/* empty css                   */import"./VCounter-iJ7LBs-z.js";import"./VField-D08HnQ-H.js";import"./form-BNfurfNN.js";import"./VInput-BhYqadj7.js";import"./VImg-mK_LHBel.js";import"./dialog-transition-Gyarbe2G.js";import"./VMenu-CaoAGUEz.js";import"./VCheckboxBtn-Cx7rhzub.js";import"./VSelectionControl-DE6q6nGu.js";import"./filter-DFKsPxUZ.js";import"./ssrBoot-BV9A_QZU.js";import"./createSimpleFunctional-1CHwcg7L.js";import"./VDivider-4rmxI4vC.js";/* empty css              */import"./VSlideGroup-Bym2RZHs.js";import"./vue3-perfect-scrollbar-CCKaLpXL.js";import"./VCard-Dv2GqkjI.js";import"./VCardText-CWNMbA2o.js";const k={__name:"DemoComboboxClearable",setup(g){const e=c(["Vuetify","Programming"]),m=["Programming","Design","Vue","Vuetify"];return(u,o)=>(V(),b(p,{modelValue:a(e),"onUpdate:modelValue":o[0]||(o[0]=l=>d(e)?e.value=l:null),items:m,label:"Combobox",multiple:"",placeholder:"deployment",clearable:""},null,8,["modelValue"]))}},A={__name:"DemoComboboxNoDataWithChips",setup(g){const e=["Gaming","Programming","Vue","Vuetify"],m=c(["Vuetify"]),u=c(null);return _(m,o=>{o.length>5&&I(()=>m.value.pop())}),(o,l)=>(V(),b(p,{modelValue:a(m),"onUpdate:modelValue":l[0]||(l[0]=f=>d(m)?m.value=f:null),"search-input":a(u),"onUpdate:searchInput":l[1]||(l[1]=f=>d(u)?u.value=f:null),items:e,"hide-selected":"","hide-no-data":!1,placeholder:"deployment",hint:"Maximum of 5 tags",label:"Add some tags",multiple:"","persistent-hint":""},{"no-data":s(()=>[t(P,null,{default:s(()=>[t(D,null,{default:s(()=>[l[2]||(l[2]=n(' No results matching "')),i("strong",null,C(a(u)),1),l[3]||(l[3]=n('". Press ')),l[4]||(l[4]=i("kbd",null,"enter",-1)),l[5]||(l[5]=n(" to create a new one "))]),_:1,__:[2,3,4,5]})]),_:1})]),_:1},8,["modelValue","search-input"]))}},R={__name:"DemoComboboxMultiple",setup(g){const e=c(["Vuetify","Programming"]),m=["Programming","Design","Vue","Vuetify"];return(u,o)=>(V(),b(y,null,{default:s(()=>[t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[0]||(o[0]=l=>d(e)?e.value=l:null),items:m,placeholder:"deployment",label:"Select a favorite activity or create a new one",multiple:""},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[1]||(o[1]=l=>d(e)?e.value=l:null),items:m,placeholder:"deployment",label:"I use chips",multiple:"",chips:""},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[2]||(o[2]=l=>d(e)?e.value=l:null),placeholder:"deployment",label:"I'm readonly",chips:"",multiple:"",readonly:""},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[3]||(o[3]=l=>d(e)?e.value=l:null),items:m,placeholder:"deployment",label:"I use selection slot",multiple:""},{selection:s(({item:l})=>[t(w,{size:"small"},{prepend:s(()=>[t(U,{start:"",color:"primary"},{default:s(()=>[n(C(String(l.title).charAt(0).toUpperCase()),1)]),_:2},1024)]),default:s(()=>[n(" "+C(l.title),1)]),_:2},1024)]),_:1},8,["modelValue"])]),_:1})]),_:1}))}},N={__name:"DemoComboboxVariant",setup(g){const e=c(["Programming"]),m=["Programming","Design","Vue","Vuetify"];return(u,o)=>(V(),b(y,null,{default:s(()=>[t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[0]||(o[0]=l=>d(e)?e.value=l:null),items:m,multiple:"",placeholder:"deployment",variant:"solo",label:"solo"},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[1]||(o[1]=l=>d(e)?e.value=l:null),multiple:"",items:m,placeholder:"deployment",variant:"outlined",label:"Outlined"},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[2]||(o[2]=l=>d(e)?e.value=l:null),multiple:"",items:m,placeholder:"deployment",variant:"underlined",label:"Underlined"},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[3]||(o[3]=l=>d(e)?e.value=l:null),multiple:"",items:m,placeholder:"deployment",variant:"filled",label:"Filled"},null,8,["modelValue"])]),_:1}),t(r,{cols:"12"},{default:s(()=>[t(p,{modelValue:a(e),"onUpdate:modelValue":o[4]||(o[4]=l=>d(e)?e.value=l:null),multiple:"",items:m,variant:"plain",placeholder:"deployment",label:"Plain"},null,8,["modelValue"])]),_:1})]),_:1}))}},T={__name:"DemoComboboxDensity",setup(g){const e=c(["Vuetify","Programming"]),m=["Programming","Design","Vue","Vuetify"];return(u,o)=>(V(),b(p,{modelValue:a(e),"onUpdate:modelValue":o[0]||(o[0]=l=>d(e)?e.value=l:null),items:m,label:"Combobox",density:"compact",placeholder:"deployment",multiple:""},null,8,["modelValue"]))}},S={__name:"DemoComboboxBasic",setup(g){const e=c("Programming"),m=["Programming","Design","Vue","Vuetify"];return(u,o)=>(V(),b(p,{modelValue:a(e),"onUpdate:modelValue":o[0]||(o[0]=l=>d(e)?e.value=l:null),items:m,placeholder:"deployment"},null,8,["modelValue"]))}},$={ts:`<script lang="ts" setup>
const selectedItem = ref('Programming')
const items = ['Programming', 'Design', 'Vue', 'Vuetify']
<\/script>

<template>
  <VCombobox
    v-model="selectedItem"
    :items="items"
    placeholder="deployment"
  />
</template>
`,js:`<script setup>
const selectedItem = ref('Programming')

const items = [
  'Programming',
  'Design',
  'Vue',
  'Vuetify',
]
<\/script>

<template>
  <VCombobox
    v-model="selectedItem"
    :items="items"
    placeholder="deployment"
  />
</template>
`},j={ts:`<script lang="ts" setup>
const select = ref(['Vuetify', 'Programming'])
const items = ['Programming', 'Design', 'Vue', 'Vuetify']
<\/script>

<template>
  <VCombobox
    v-model="select"
    :items="items"
    label="Combobox"
    multiple
    placeholder="deployment"
    clearable
  />
</template>
`,js:`<script setup>
const select = ref([
  'Vuetify',
  'Programming',
])

const items = [
  'Programming',
  'Design',
  'Vue',
  'Vuetify',
]
<\/script>

<template>
  <VCombobox
    v-model="select"
    :items="items"
    label="Combobox"
    multiple
    placeholder="deployment"
    clearable
  />
</template>
`},B={ts:`<script lang="ts" setup>
const select = ref(['Vuetify', 'Programming'])
const items = ['Programming', 'Design', 'Vue', 'Vuetify']
<\/script>

<template>
  <VCombobox
    v-model="select"
    :items="items"
    label="Combobox"
    density="compact"
    placeholder="deployment"
    multiple
  />
</template>
`,js:`<script setup>
const select = ref([
  'Vuetify',
  'Programming',
])

const items = [
  'Programming',
  'Design',
  'Vue',
  'Vuetify',
]
<\/script>

<template>
  <VCombobox
    v-model="select"
    :items="items"
    label="Combobox"
    density="compact"
    placeholder="deployment"
    multiple
  />
</template>
`},M={ts:`<script lang="ts" setup>
const selectedItem = ref(['Vuetify', 'Programming'])
const items = ['Programming', 'Design', 'Vue', 'Vuetify']
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="Select a favorite activity or create a new one"
        multiple
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="I use chips"
        multiple
        chips
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        placeholder="deployment"
        label="I'm readonly"
        chips
        multiple
        readonly
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="I use selection slot"
        multiple
      >
        <template #selection="{ item }">
          <VChip size="small">
            <template #prepend>
              <VAvatar
                start
                color="primary"
              >
                {{ String(item.title).charAt(0).toUpperCase() }}
              </VAvatar>
            </template>

            {{ item.title }}
          </VChip>
        </template>
      </VCombobox>
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const selectedItem = ref([
  'Vuetify',
  'Programming',
])

const items = [
  'Programming',
  'Design',
  'Vue',
  'Vuetify',
]
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="Select a favorite activity or create a new one"
        multiple
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="I use chips"
        multiple
        chips
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        placeholder="deployment"
        label="I'm readonly"
        chips
        multiple
        readonly
      />
    </VCol>

    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        placeholder="deployment"
        label="I use selection slot"
        multiple
      >
        <template #selection="{ item }">
          <VChip size="small">
            <template #prepend>
              <VAvatar
                start
                color="primary"
              >
                {{ String(item.title).charAt(0).toUpperCase() }}
              </VAvatar>
            </template>

            {{ item.title }}
          </VChip>
        </template>
      </VCombobox>
    </VCol>
  </VRow>
</template>
`},z={ts:`<script lang="ts" setup>
const items = ['Gaming', 'Programming', 'Vue', 'Vuetify']
const selectedList = ref(['Vuetify'])
const search = ref(null)

watch(selectedList, value => {
  if (value.length > 5)
    nextTick(() => selectedList.value.pop())
})
<\/script>

<template>
  <VCombobox
    v-model="selectedList"
    v-model:search-input="search"
    :items="items"
    hide-selected
    :hide-no-data="false"
    placeholder="deployment"
    hint="Maximum of 5 tags"
    label="Add some tags"
    multiple
    persistent-hint
  >
    <template #no-data>
      <VListItem>
        <VListItemTitle>
          No results matching "<strong>{{ search }}</strong>". Press <kbd>enter</kbd> to create a new one
        </VListItemTitle>
      </VListItem>
    </template>
  </VCombobox>
</template>
`,js:`<script setup>
const items = [
  'Gaming',
  'Programming',
  'Vue',
  'Vuetify',
]

const selectedList = ref(['Vuetify'])
const search = ref(null)

watch(selectedList, value => {
  if (value.length > 5)
    nextTick(() => selectedList.value.pop())
})
<\/script>

<template>
  <VCombobox
    v-model="selectedList"
    v-model:search-input="search"
    :items="items"
    hide-selected
    :hide-no-data="false"
    placeholder="deployment"
    hint="Maximum of 5 tags"
    label="Add some tags"
    multiple
    persistent-hint
  >
    <template #no-data>
      <VListItem>
        <VListItemTitle>
          No results matching "<strong>{{ search }}</strong>". Press <kbd>enter</kbd> to create a new one
        </VListItemTitle>
      </VListItem>
    </template>
  </VCombobox>
</template>
`},G={ts:`<script lang="ts" setup>
const selectedItem = ref(['Programming'])
const items = ['Programming', 'Design', 'Vue', 'Vuetify']
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        multiple
        placeholder="deployment"
        variant="solo"
        label="solo"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="outlined"
        label="Outlined"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="underlined"
        label="Underlined"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="filled"
        label="Filled"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        variant="plain"
        placeholder="deployment"
        label="Plain"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const selectedItem = ref(['Programming'])

const items = [
  'Programming',
  'Design',
  'Vue',
  'Vuetify',
]
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        :items="items"
        multiple
        placeholder="deployment"
        variant="solo"
        label="solo"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="outlined"
        label="Outlined"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="underlined"
        label="Underlined"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        placeholder="deployment"
        variant="filled"
        label="Filled"
      />
    </VCol>
    <VCol cols="12">
      <VCombobox
        v-model="selectedItem"
        multiple
        :items="items"
        variant="plain"
        placeholder="deployment"
        label="Plain"
      />
    </VCol>
  </VRow>
</template>
`},ge={__name:"combobox",setup(g){return(e,m)=>{const u=S,o=L,l=T,f=N,v=R,x=A,h=k;return V(),b(y,{class:"match-height"},{default:s(()=>[t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"Basic",code:a($)},{default:s(()=>[m[0]||(m[0]=i("p",null,"With Combobox, you can allow a user to create new values that may not be present in a provided items list.",-1)),t(u)]),_:1,__:[0]},8,["code"])]),_:1}),t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"Density",code:a(B)},{default:s(()=>[m[1]||(m[1]=i("p",null,[n(" You can use "),i("code",null,"Density"),n(" prop to reduce combobox height and lower max height of list items. Available options are: "),i("code",null,"default"),n(", "),i("code",null,"comfortable"),n(", and "),i("code",null,"compact"),n(". ")],-1)),t(l)]),_:1,__:[1]},8,["code"])]),_:1}),t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"Variant",code:a(G)},{default:s(()=>[m[2]||(m[2]=i("p",null,[n("Use "),i("code",null,"solo"),n(", "),i("code",null,"outlined"),n(", "),i("code",null,"underlined"),n(", "),i("code",null,"filled"),n(" and "),i("code",null,"plain"),n(" options of z"),i("code",null,"variant"),n(" prop to change the look of combobox. ")],-1)),t(f)]),_:1,__:[2]},8,["code"])]),_:1}),t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"Multiple",code:a(M)},{default:s(()=>[m[3]||(m[3]=i("p",null,"Previously known as tags - user is allowed to enter more than 1 value",-1)),t(v)]),_:1,__:[3]},8,["code"])]),_:1}),t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"No data with chips",code:a(z)},{default:s(()=>[m[4]||(m[4]=i("p",null,"Previously known as tags - user is allowed to enter more than 1 value",-1)),t(x)]),_:1,__:[4]},8,["code"])]),_:1}),t(r,{cols:"12",md:"6"},{default:s(()=>[t(o,{title:"Clearable",code:a(j)},{default:s(()=>[m[5]||(m[5]=i("p",null,[n("Use "),i("code",null,"clearable"),n(" prop to clear combobox.")],-1)),t(h)]),_:1,__:[5]},8,["code"])]),_:1})]),_:1})}}};export{ge as default};
