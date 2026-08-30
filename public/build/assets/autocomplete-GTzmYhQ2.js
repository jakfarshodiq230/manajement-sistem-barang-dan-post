import{V as u}from"./VAutocomplete-DShKNrLf.js";import{r as S,o as v,f as g,aG as _,l as s,e as o,b as a,dB as M,a0 as N,w as T,p as C,b1 as G,b2 as I,b3 as F,b4 as D,bc as W,be as R,bd as O,dy as H,d as i,s as r}from"./main-BdxVNj0W.js";import{a as L}from"./VList-B1zIe8dw.js";import{V as E}from"./VChip-BBy0MQja.js";import{a as p,V}from"./VRow-vrTmU0A8.js";import{_ as U}from"./AppCardCode-50yCyEpV.js";import"./VSelect-DxJzOT9N.js";import"./VTextField-fBFfJByD.js";/* empty css                   */import"./VCounter-CgQz9E3-.js";import"./VField-K8nsQEDh.js";import"./form-Bqx4gi5N.js";import"./VInput-B5qWrE6y.js";import"./VImg-CJH8Rt0U.js";import"./dialog-transition-n2jgi_y4.js";import"./VMenu-0DfAB6z2.js";import"./VCheckboxBtn-sA_-PWGR.js";import"./VSelectionControl-DxRC1VlK.js";import"./VAvatar-C-Xdvx7y.js";import"./filter-h4lMBIHE.js";import"./ssrBoot-BZoYsp_l.js";import"./createSimpleFunctional-DX9efoFk.js";import"./VDivider-iqf11oAx.js";import"./VSlideGroup-CxI0OGzF.js";/* empty css              */import"./vue3-perfect-scrollbar-64k2zWlB.js";import"./VCard-XkorCH1G.js";import"./VCardText-B2uwaTKx.js";const P={__name:"DemoAutocompleteValidation",setup(f){const t=["foo","bar","fizz","buzz"],e=S(["foo"]),c=[l=>!!l.length||"Select at least one option."];return(l,n)=>(v(),g(u,{modelValue:s(e),"onUpdate:modelValue":n[0]||(n[0]=m=>_(e)?e.value=m:null),items:t,rules:c,placeholder:"Select Option",multiple:""},null,8,["modelValue"]))}},q={__name:"DemoAutocompleteStateSelector",setup(f){const t=S(!1),e=S(null),c=["Alabama","Alaska","American Samoa","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District of Columbia","Federated States of Micronesia","Florida","Georgia","Guam","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Marshall Islands","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Northern Mariana Islands","Ohio","Oklahoma","Oregon","Palau","Pennsylvania","Puerto Rico","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virgin Island","Virginia","Washington","West Virginia","Wisconsin","Wyoming"];return(l,n)=>(v(),g(u,{modelValue:s(e),"onUpdate:modelValue":n[1]||(n[1]=m=>_(e)?e.value=m:null),hint:s(t)?"Click the icon to save":"Click the icon to edit",placeholder:"Select Your State",items:c,readonly:!s(t),label:`State — ${s(t)?"Editable":"Readonly"}`,"persistent-hint":"","prepend-icon":"ri-building-line","menu-props":{maxHeight:"200px"}},{append:o(()=>[a(M,{mode:"out-in"},{default:o(()=>[(v(),g(N,{key:`icon-${s(t)}`,color:s(t)?"success":"info",icon:s(t)?"ri-check-line":"ri-edit-circle-line",onClick:n[0]||(n[0]=m=>t.value=!s(t))},null,8,["color","icon"]))]),_:1})]),_:1},8,["modelValue","hint","readonly","label"]))}},z={__name:"DemoAutocompleteAsyncItems",setup(f){const t=S(!1),e=S(),c=S(null),l=["Alabama","Alaska","American Samoa","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District of Columbia","Federated States of Micronesia","Florida","Georgia","Guam","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Marshall Islands","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Northern Mariana Islands","Ohio","Oklahoma","Oregon","Palau","Pennsylvania","Puerto Rico","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virgin Island","Virginia","Washington","West Virginia","Wisconsin","Wyoming"],n=S(l),m=h=>{t.value=!0,setTimeout(()=>{n.value=l.filter(d=>(d||"").toLowerCase().includes((h||"").toLowerCase())),t.value=!1},500)};return T(e,h=>{h&&h!==c.value&&m(h)}),(h,d)=>(v(),g(u,{modelValue:s(c),"onUpdate:modelValue":d[0]||(d[0]=b=>_(c)?c.value=b:null),search:s(e),"onUpdate:search":d[1]||(d[1]=b=>_(e)?e.value=b:null),loading:s(t),items:s(n),placeholder:"Search for a state",label:"What state are you from?",variant:"underlined","menu-props":{maxHeight:"200px"}},null,8,["modelValue","search","loading","items"]))}},$={__name:"DemoAutocompleteSlots",setup(f){const t=S(["Sandra Adams","Britta Holt"]),e=[{name:"Sandra Adams",group:"Group 1",avatar:G},{name:"Ali Connors",group:"Group 1",avatar:I},{name:"Trevor Hansen",group:"Group 1",avatar:F},{name:"Tucker Smith",group:"Group 1",avatar:D},{name:"Britta Holt",group:"Group 2",avatar:W},{name:"Jane Smith ",group:"Group 2",avatar:R},{name:"John Smith",group:"Group 2",avatar:O},{name:"Sandra Williams",group:"Group 2",avatar:H}];return(c,l)=>(v(),g(u,{modelValue:s(t),"onUpdate:modelValue":l[0]||(l[0]=n=>_(t)?t.value=n:null),chips:"","closable-chips":"",multiple:"",items:e,"item-title":"name","item-value":"name",placeholder:"Select User",label:"Select"},{chip:o(({props:n,item:m})=>[a(E,C(n,{"prepend-avatar":m.raw.avatar,text:m.raw.name}),null,16,["prepend-avatar","text"])]),item:o(({props:n,item:m})=>{var h,d,b;return[a(L,C(n,{"prepend-avatar":(h=m==null?void 0:m.raw)==null?void 0:h.avatar,title:(d=m==null?void 0:m.raw)==null?void 0:d.name,subtitle:(b=m==null?void 0:m.raw)==null?void 0:b.group}),null,16,["prepend-avatar","title","subtitle"])]}),_:1},8,["modelValue"]))}},Y={__name:"DemoAutocompleteCustomFilter",setup(f){const t=[{name:"Florida",abbr:"FL",id:1},{name:"Georgia",abbr:"GA",id:2},{name:"Nebraska",abbr:"NE",id:3},{name:"California",abbr:"CA",id:4},{name:"New York",abbr:"NY",id:5}];function e(c,l,n){const m=n.raw.name.toLowerCase(),h=n.raw.abbr.toLowerCase(),d=l.toLowerCase();return m.includes(d)||h.includes(d)}return(c,l)=>(v(),g(u,{label:"States",items:t,"custom-filter":e,"item-title":"name","item-value":"abbr",placeholder:"Select State"}))}},j={__name:"DemoAutocompleteChips",setup(f){const t=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(e,c)=>(v(),g(u,{label:"States",items:t,placeholder:"Select State",chips:"",multiple:""}))}},B={__name:"DemoAutocompleteClearable",setup(f){const t=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(e,c)=>(v(),g(u,{label:"States",items:t,multiple:"",placeholder:"Select State",clearable:""}))}},J={__name:"DemoAutocompleteMultiple",setup(f){const t=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(e,c)=>(v(),g(u,{label:"States",items:t,placeholder:"Select State",multiple:""}))}},K={__name:"DemoAutocompleteVariant",setup(f){const t=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(e,c)=>(v(),g(V,null,{default:o(()=>[a(p,{cols:"12",md:"6"},{default:o(()=>[a(u,{variant:"solo",label:"Solo",items:t,placeholder:"Select State"})]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(u,{variant:"outlined",label:"Outlined",placeholder:"Select State",items:t})]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(u,{variant:"underlined",label:"Underlined",placeholder:"Select State",items:t})]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(u,{variant:"filled",label:"Filled",placeholder:"Select State",items:t})]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(u,{variant:"plain",label:"Plain",placeholder:"Select State",items:t})]),_:1})]),_:1}))}},X={__name:"DemoAutocompleteDensity",setup(f){const t=S("Florida"),e=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(c,l)=>(v(),g(u,{modelValue:s(t),"onUpdate:modelValue":l[0]||(l[0]=n=>_(t)?t.value=n:null),label:"States",density:"compact",placeholder:"Select State",items:e},null,8,["modelValue"]))}},Q={__name:"DemoAutocompleteBasic",setup(f){const t=["California","Colorado","Florida","Georgia","Texas","Wyoming"];return(e,c)=>(v(),g(u,{label:"States",items:t,placeholder:"Select State"}))}},Z={ts:`<script setup lang="ts">
const loading = ref(false)
const search = ref()
const select = ref(null)

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
  'Hawaii',
  'Idaho',
  'Illinois',
  'Indiana',
  'Iowa',
  'Kansas',
  'Kentucky',
  'Louisiana',
  'Maine',
  'Marshall Islands',
  'Maryland',
  'Massachusetts',
  'Michigan',
  'Minnesota',
  'Mississippi',
  'Missouri',
  'Montana',
  'Nebraska',
  'Nevada',
  'New Hampshire',
  'New Jersey',
  'New Mexico',
  'New York',
  'North Carolina',
  'North Dakota',
  'Northern Mariana Islands',
  'Ohio',
  'Oklahoma',
  'Oregon',
  'Palau',
  'Pennsylvania',
  'Puerto Rico',
  'Rhode Island',
  'South Carolina',
  'South Dakota',
  'Tennessee',
  'Texas',
  'Utah',
  'Vermont',
  'Virgin Island',
  'Virginia',
  'Washington',
  'West Virginia',
  'Wisconsin',
  'Wyoming',
]

const items = ref(states)

const querySelections = (query: string) => {
  loading.value = true

  // Simulated ajax query
  setTimeout(() => {
    items.value = states.filter(state => (state || '').toLowerCase().includes((query || '').toLowerCase()))
    loading.value = false
  }, 500)
}

watch(search, query => {
  query && query !== select.value && querySelections(query)
})
<\/script>

<template>
  <VAutocomplete
    v-model="select"
    v-model:search="search"
    :loading="loading"
    :items="items"
    placeholder="Search for a state"
    label="What state are you from?"
    variant="underlined"
    :menu-props="{ maxHeight: '200px' }"
  />
</template>
`,js:`<script setup>
const loading = ref(false)
const search = ref()
const select = ref(null)

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
  'Hawaii',
  'Idaho',
  'Illinois',
  'Indiana',
  'Iowa',
  'Kansas',
  'Kentucky',
  'Louisiana',
  'Maine',
  'Marshall Islands',
  'Maryland',
  'Massachusetts',
  'Michigan',
  'Minnesota',
  'Mississippi',
  'Missouri',
  'Montana',
  'Nebraska',
  'Nevada',
  'New Hampshire',
  'New Jersey',
  'New Mexico',
  'New York',
  'North Carolina',
  'North Dakota',
  'Northern Mariana Islands',
  'Ohio',
  'Oklahoma',
  'Oregon',
  'Palau',
  'Pennsylvania',
  'Puerto Rico',
  'Rhode Island',
  'South Carolina',
  'South Dakota',
  'Tennessee',
  'Texas',
  'Utah',
  'Vermont',
  'Virgin Island',
  'Virginia',
  'Washington',
  'West Virginia',
  'Wisconsin',
  'Wyoming',
]

const items = ref(states)

const querySelections = query => {
  loading.value = true

  // Simulated ajax query
  setTimeout(() => {
    items.value = states.filter(state => (state || '').toLowerCase().includes((query || '').toLowerCase()))
    loading.value = false
  }, 500)
}

watch(search, query => {
  query && query !== select.value && querySelections(query)
})
<\/script>

<template>
  <VAutocomplete
    v-model="select"
    v-model:search="search"
    :loading="loading"
    :items="items"
    placeholder="Search for a state"
    label="What state are you from?"
    variant="underlined"
    :menu-props="{ maxHeight: '200px' }"
  />
</template>
`},ee={ts:`<script setup lang="ts">
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
  />
</template>
`,js:`<script setup>
const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
  />
</template>
`},ae={ts:`<script setup lang="ts">
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
    chips
    multiple
  />
</template>
`,js:`<script setup>
const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
    chips
    multiple
  />
</template>
`},te={ts:`<script setup lang="ts">
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    multiple
    placeholder="Select State"
    clearable
  />
</template>
`,js:`<script setup>
const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    multiple
    placeholder="Select State"
    clearable
  />
</template>
`},oe={ts:`<script setup lang="ts">
const states = [
  { name: 'Florida', abbr: 'FL', id: 1 },
  { name: 'Georgia', abbr: 'GA', id: 2 },
  { name: 'Nebraska', abbr: 'NE', id: 3 },
  { name: 'California', abbr: 'CA', id: 4 },
  { name: 'New York', abbr: 'NY', id: 5 },
]

function customFilter(itemTitle: any, queryText: any, item: any) {
  const textOne = item.raw.name.toLowerCase()
  const textTwo = item.raw.abbr.toLowerCase()
  const searchText = queryText.toLowerCase()

  return textOne.includes(searchText) || textTwo.includes(searchText)
}
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="states"
    :custom-filter="customFilter"
    item-title="name"
    item-value="abbr"
    placeholder="Select State"
  />
</template>
`,js:`<script setup>
const states = [
  {
    name: 'Florida',
    abbr: 'FL',
    id: 1,
  },
  {
    name: 'Georgia',
    abbr: 'GA',
    id: 2,
  },
  {
    name: 'Nebraska',
    abbr: 'NE',
    id: 3,
  },
  {
    name: 'California',
    abbr: 'CA',
    id: 4,
  },
  {
    name: 'New York',
    abbr: 'NY',
    id: 5,
  },
]

function customFilter(itemTitle, queryText, item) {
  const textOne = item.raw.name.toLowerCase()
  const textTwo = item.raw.abbr.toLowerCase()
  const searchText = queryText.toLowerCase()
  
  return textOne.includes(searchText) || textTwo.includes(searchText)
}
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="states"
    :custom-filter="customFilter"
    item-title="name"
    item-value="abbr"
    placeholder="Select State"
  />
</template>
`},le={ts:`<script setup lang="ts">
const select = ref('Florida')
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VAutocomplete
    v-model="select"
    label="States"
    density="compact"
    placeholder="Select State"
    :items="items"
  />
</template>
`,js:`<script setup>
const select = ref('Florida')

const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    v-model="select"
    label="States"
    density="compact"
    placeholder="Select State"
    :items="items"
  />
</template>
`},ie={ts:`<script setup lang="ts">
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
    multiple
  />
</template>
`,js:`<script setup>
const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    label="States"
    :items="items"
    placeholder="Select State"
    multiple
  />
</template>
`},se={ts:`<script setup lang="ts">
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'
import avatar6 from '@images/avatars/avatar-6.png'
import avatar7 from '@images/avatars/avatar-7.png'
import avatar8 from '@images/avatars/avatar-8.png'

const friends = ref(['Sandra Adams', 'Britta Holt'])

const people = [
  { name: 'Sandra Adams', group: 'Group 1', avatar: avatar1 },
  { name: 'Ali Connors', group: 'Group 1', avatar: avatar2 },
  { name: 'Trevor Hansen', group: 'Group 1', avatar: avatar3 },
  { name: 'Tucker Smith', group: 'Group 1', avatar: avatar4 },
  { name: 'Britta Holt', group: 'Group 2', avatar: avatar5 },
  { name: 'Jane Smith ', group: 'Group 2', avatar: avatar6 },
  { name: 'John Smith', group: 'Group 2', avatar: avatar7 },
  { name: 'Sandra Williams', group: 'Group 2', avatar: avatar8 },
]
<\/script>

<template>
  <VAutocomplete
    v-model="friends"
    chips
    closable-chips
    multiple
    :items="people"
    item-title="name"
    item-value="name"
    placeholder="Select User"
    label="Select"
  >
    <template #chip="{ props, item }">
      <VChip
        v-bind="props"
        :prepend-avatar="item.raw.avatar"
        :text="item.raw.name"
      />
    </template>

    <template #item="{ props, item }">
      <VListItem
        v-bind="props"
        :prepend-avatar="item?.raw?.avatar"
        :title="item?.raw?.name"
        :subtitle="item?.raw?.group"
      />
    </template>
  </VAutocomplete>
</template>
`,js:`<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'
import avatar6 from '@images/avatars/avatar-6.png'
import avatar7 from '@images/avatars/avatar-7.png'
import avatar8 from '@images/avatars/avatar-8.png'

const friends = ref([
  'Sandra Adams',
  'Britta Holt',
])

const people = [
  {
    name: 'Sandra Adams',
    group: 'Group 1',
    avatar: avatar1,
  },
  {
    name: 'Ali Connors',
    group: 'Group 1',
    avatar: avatar2,
  },
  {
    name: 'Trevor Hansen',
    group: 'Group 1',
    avatar: avatar3,
  },
  {
    name: 'Tucker Smith',
    group: 'Group 1',
    avatar: avatar4,
  },
  {
    name: 'Britta Holt',
    group: 'Group 2',
    avatar: avatar5,
  },
  {
    name: 'Jane Smith ',
    group: 'Group 2',
    avatar: avatar6,
  },
  {
    name: 'John Smith',
    group: 'Group 2',
    avatar: avatar7,
  },
  {
    name: 'Sandra Williams',
    group: 'Group 2',
    avatar: avatar8,
  },
]
<\/script>

<template>
  <VAutocomplete
    v-model="friends"
    chips
    closable-chips
    multiple
    :items="people"
    item-title="name"
    item-value="name"
    placeholder="Select User"
    label="Select"
  >
    <template #chip="{ props, item }">
      <VChip
        v-bind="props"
        :prepend-avatar="item.raw.avatar"
        :text="item.raw.name"
      />
    </template>

    <template #item="{ props, item }">
      <VListItem
        v-bind="props"
        :prepend-avatar="item?.raw?.avatar"
        :title="item?.raw?.name"
        :subtitle="item?.raw?.group"
      />
    </template>
  </VAutocomplete>
</template>
`},re={ts:`<script setup lang="ts">
const isEditing = ref(false)
const selectedState = ref(null)

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
  'Hawaii',
  'Idaho',
  'Illinois',
  'Indiana',
  'Iowa',
  'Kansas',
  'Kentucky',
  'Louisiana',
  'Maine',
  'Marshall Islands',
  'Maryland',
  'Massachusetts',
  'Michigan',
  'Minnesota',
  'Mississippi',
  'Missouri',
  'Montana',
  'Nebraska',
  'Nevada',
  'New Hampshire',
  'New Jersey',
  'New Mexico',
  'New York',
  'North Carolina',
  'North Dakota',
  'Northern Mariana Islands',
  'Ohio',
  'Oklahoma',
  'Oregon',
  'Palau',
  'Pennsylvania',
  'Puerto Rico',
  'Rhode Island',
  'South Carolina',
  'South Dakota',
  'Tennessee',
  'Texas',
  'Utah',
  'Vermont',
  'Virgin Island',
  'Virginia',
  'Washington',
  'West Virginia',
  'Wisconsin',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    v-model="selectedState"
    :hint="!isEditing ? 'Click the icon to edit' : 'Click the icon to save'"
    placeholder="Select Your State"
    :items="states"
    :readonly="!isEditing"
    :label="\`State — \${isEditing ? 'Editable' : 'Readonly'}\`"
    persistent-hint
    prepend-icon="ri-building-line"
    :menu-props="{ maxHeight: '200px' }"
  >
    <template #append>
      <VSlideXReverseTransition mode="out-in">
        <VIcon
          :key="\`icon-\${isEditing}\`"
          :color="isEditing ? 'success' : 'info'"
          :icon="isEditing ? 'ri-check-line' : 'ri-edit-circle-line'"
          @click="isEditing = !isEditing"
        />
      </VSlideXReverseTransition>
    </template>
  </VAutocomplete>
</template>
`,js:`<script setup>
const isEditing = ref(false)
const selectedState = ref(null)

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
  'Hawaii',
  'Idaho',
  'Illinois',
  'Indiana',
  'Iowa',
  'Kansas',
  'Kentucky',
  'Louisiana',
  'Maine',
  'Marshall Islands',
  'Maryland',
  'Massachusetts',
  'Michigan',
  'Minnesota',
  'Mississippi',
  'Missouri',
  'Montana',
  'Nebraska',
  'Nevada',
  'New Hampshire',
  'New Jersey',
  'New Mexico',
  'New York',
  'North Carolina',
  'North Dakota',
  'Northern Mariana Islands',
  'Ohio',
  'Oklahoma',
  'Oregon',
  'Palau',
  'Pennsylvania',
  'Puerto Rico',
  'Rhode Island',
  'South Carolina',
  'South Dakota',
  'Tennessee',
  'Texas',
  'Utah',
  'Vermont',
  'Virgin Island',
  'Virginia',
  'Washington',
  'West Virginia',
  'Wisconsin',
  'Wyoming',
]
<\/script>

<template>
  <VAutocomplete
    v-model="selectedState"
    :hint="!isEditing ? 'Click the icon to edit' : 'Click the icon to save'"
    placeholder="Select Your State"
    :items="states"
    :readonly="!isEditing"
    :label="\`State — \${isEditing ? 'Editable' : 'Readonly'}\`"
    persistent-hint
    prepend-icon="ri-building-line"
    :menu-props="{ maxHeight: '200px' }"
  >
    <template #append>
      <VSlideXReverseTransition mode="out-in">
        <VIcon
          :key="\`icon-\${isEditing}\`"
          :color="isEditing ? 'success' : 'info'"
          :icon="isEditing ? 'ri-check-line' : 'ri-edit-circle-line'"
          @click="isEditing = !isEditing"
        />
      </VSlideXReverseTransition>
    </template>
  </VAutocomplete>
</template>
`},ne={ts:`<script setup lang="ts">
const items = ['foo', 'bar', 'fizz', 'buzz']
const values = ref(['foo'])
const nameRules = [(v: string) => !!v.length || 'Select at least one option.']
<\/script>

<template>
  <VAutocomplete
    v-model="values"
    :items="items"
    :rules="nameRules"
    placeholder="Select Option"
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

const values = ref(['foo'])
const nameRules = [v => !!v.length || 'Select at least one option.']
<\/script>

<template>
  <VAutocomplete
    v-model="values"
    :items="items"
    :rules="nameRules"
    placeholder="Select Option"
    multiple
  />
</template>
`},me={ts:`<script setup lang="ts">
const items = ['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 solo variant  -->
      <VAutocomplete
        variant="solo"
        label="Solo"
        :items="items"
        placeholder="Select State"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 outlined variant -->
      <VAutocomplete
        variant="outlined"
        label="Outlined"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 underlined variant -->
      <VAutocomplete
        variant="underlined"
        label="Underlined"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 filled variant  -->
      <VAutocomplete
        variant="filled"
        label="Filled"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!--  👉 plain variant -->
      <VAutocomplete
        variant="plain"
        label="Plain"
        placeholder="Select State"
        :items="items"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const items = [
  'California',
  'Colorado',
  'Florida',
  'Georgia',
  'Texas',
  'Wyoming',
]
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 solo variant  -->
      <VAutocomplete
        variant="solo"
        label="Solo"
        :items="items"
        placeholder="Select State"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 outlined variant -->
      <VAutocomplete
        variant="outlined"
        label="Outlined"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 underlined variant -->
      <VAutocomplete
        variant="underlined"
        label="Underlined"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!-- 👉 filled variant  -->
      <VAutocomplete
        variant="filled"
        label="Filled"
        placeholder="Select State"
        :items="items"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <!--  👉 plain variant -->
      <VAutocomplete
        variant="plain"
        label="Plain"
        placeholder="Select State"
        :items="items"
      />
    </VCol>
  </VRow>
</template>
`},He={__name:"autocomplete",setup(f){return(t,e)=>{const c=Q,l=U,n=X,m=K,h=J,d=B,b=j,y=Y,A=$,w=z,x=q,k=P;return v(),g(V,{class:"match-height"},{default:o(()=>[a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Basic",code:s(ee)},{default:o(()=>[e[0]||(e[0]=i("p",null,[r(" The "),i("code",null," v-autocomplete "),r(" component offers simple and flexible type-ahead functionality. This is useful when searching large sets of data or even dynamically fetching information from an API. ")],-1)),a(c)]),_:1,__:[0]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Density",code:s(le)},{default:o(()=>[e[1]||(e[1]=i("p",null,[r(" You can use "),i("code",null," density "),r(" prop to adjusts vertical spacing within the component. Available options are: "),i("code",null,"default"),r(", "),i("code",null,"comfortable"),r(", and "),i("code",null,"compact"),r(". ")],-1)),a(n)]),_:1,__:[1]},8,["code"])]),_:1}),a(p,{cols:"12",md:"12"},{default:o(()=>[a(l,{title:"Variant",code:s(me)},{default:o(()=>[e[2]||(e[2]=i("p",null,[r("Use "),i("code",null,"solo"),r(", "),i("code",null,"outlined"),r(", "),i("code",null,"underlined"),r(", "),i("code",null,"filled"),r(" and "),i("code",null,"plain"),r(" options of "),i("code",null,"variant"),r(" prop to change the look of Autocomplete. ")],-1)),a(m)]),_:1,__:[2]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Multiple",code:s(ie)},{default:o(()=>[e[3]||(e[3]=i("p",null,[r("Use "),i("code",null,"multiple"),r(" prop to select multiple. Accepts array for value")],-1)),a(h)]),_:1,__:[3]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Clearable",code:s(te)},{default:o(()=>[e[4]||(e[4]=i("p",null,[r("Use "),i("code",null,"clearable"),r(" prop to add input clear functionality.")],-1)),a(d)]),_:1,__:[4]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Chips",code:s(ae)},{default:o(()=>[e[5]||(e[5]=i("p",null,[r("Use "),i("code",null," chips "),r(" prop to use chips in select.")],-1)),a(b)]),_:1,__:[5]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Custom-Filter",code:s(oe)},{default:o(()=>[e[6]||(e[6]=i("p",null,[r("The "),i("code",null," custom-filter "),r(" prop can be used to filter each individual item with custom logic.In example we will filter state based on their name and abbreviations ")],-1)),a(y)]),_:1,__:[6]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Slots",code:s(se)},{default:o(()=>[e[7]||(e[7]=i("p",null,"With the power of slots, you can customize the visual output of the select. In this example we add a profile picture for both the chips and list items using their props. ",-1)),a(A)]),_:1,__:[7]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Async items",code:s(Z)},{default:o(()=>[e[8]||(e[8]=i("p",null,"Sometimes you need to load data externally based upon a search query. ",-1)),a(w)]),_:1,__:[8]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"State Selector",code:s(re)},{default:o(()=>[e[9]||(e[9]=i("p",null,"Using a combination of v-autocomplete slots and transitions, you can create a stylish toggle able autocomplete field such as below state selector.",-1)),a(x)]),_:1,__:[9]},8,["code"])]),_:1}),a(p,{cols:"12",md:"6"},{default:o(()=>[a(l,{title:"Validation",code:s(ne)},{default:o(()=>[e[10]||(e[10]=i("p",null,[r("Use "),i("code",null,"rules"),r(" prop to validate autocomplete. Accepts a mixed array of types function, boolean and string. Functions pass an input value as an argument and must return either true / false or a string containing an error message.")],-1)),a(k)]),_:1,__:[10]},8,["code"])]),_:1})]),_:1})}}};export{He as default};
