import{V as A}from"./VNodeRenderer-D7X6Qudy.js";import{r as h,o as f,f as V,e as l,b as e,ai as D,a0 as y,c as U,h as q,cJ as S,l as i,b0 as j,G as H,s as a,p as N,aG as _,d as t}from"./main-F9ZL2TPx.js";import{V as L}from"./VTooltip-C_x9_whu.js";import{V as d}from"./VTextField-CK05XwX6.js";import{a as s,V as g}from"./VRow-BoGg4xmS.js";import{_ as T}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{r as B,e as E}from"./validators-BTYlBg-y.js";import{V as W}from"./VForm-DF9R0J6M.js";import{_ as z}from"./AppCardCode-BFVlziTE.js";/* empty css                   */import"./VCounter-2-2sOJcR.js";import"./VField-qr9iHhSk.js";import"./form-BljyjHOr.js";import"./VInput-hkpFgLTA.js";import"./VImg-BtaznfkU.js";/* empty css              */import"./helpers-DK5QwNv0.js";import"./vue3-perfect-scrollbar-CXBI4KRu.js";import"./VCard-Dy2kFIHd.js";import"./createSimpleFunctional-HGcrSysB.js";import"./VAvatar-9SCeATur.js";import"./VCardText-C2oimcBb.js";import"./VDivider-CQB59P2F.js";const Y={key:0,class:"ms-3"},O={__name:"DemoTextfieldIconSlots",setup(x){const n=h("Hey!"),o=h(!1),u=()=>{o.value=!0,n.value="Wait for it...",setTimeout(()=>{o.value=!1,n.value="You've clicked me!"},2e3)};return(r,p)=>(f(),V(d,{modelValue:i(n),"onUpdate:modelValue":p[0]||(p[0]=c=>_(n)?n.value=c:null),density:"compact",clearable:"","clear-icon":"ri-close-line",label:"Message",placeholder:"Hey!!",type:"text"},{prepend:l(()=>[e(L,{location:"bottom"},{activator:l(({props:c})=>[e(y,N(c,{icon:"ri-question-line"}),null,16)]),default:l(()=>[p[1]||(p[1]=a(" I'm a tooltip "))]),_:1,__:[1]})]),"append-inner":l(()=>[e(S,{"leave-absolute":""},{default:l(()=>[i(o)?(f(),V(j,{key:0,size:"24",color:"info",indeterminate:""})):(f(),V(i(A),{key:1,nodes:i(H).app.logo},null,8,["nodes"]))]),_:1})]),append:l(()=>[e(D,{size:r.$vuetify.display.smAndDown?"small":"large",icon:r.$vuetify.display.smAndDown,onClick:u},{default:l(()=>[e(y,{icon:"ri-focus-3-line"}),r.$vuetify.display.mdAndUp?(f(),U("span",Y,"Click me")):q("",!0)]),_:1},8,["size","icon"])]),_:1},8,["modelValue"]))}},G={__name:"DemoTextfieldPasswordInput",setup(x){const n=h(!1),o=h(!0),u=h("Password"),r=h("wqfasds"),p={required:c=>!!c||"Required.",min:c=>c.length>=8||"Min 8 characters"};return(c,m)=>(f(),V(g,null,{default:l(()=>[e(s,{cols:"12",sm:"6"},{default:l(()=>[e(d,{modelValue:i(u),"onUpdate:modelValue":m[0]||(m[0]=b=>_(u)?u.value=b:null),"append-inner-icon":i(n)?"ri-eye-off-line":"ri-eye-line",rules:[p.required,p.min],type:i(n)?"text":"password",name:"input-10-1",label:"Normal with hint text",hint:"At least 8 characters",placeholder:"············",counter:"","onClick:appendInner":m[1]||(m[1]=b=>n.value=!i(n))},null,8,["modelValue","append-inner-icon","rules","type"])]),_:1}),e(s,{cols:"12",sm:"6"},{default:l(()=>[e(d,{modelValue:i(r),"onUpdate:modelValue":m[2]||(m[2]=b=>_(r)?r.value=b:null),rules:[p.required,p.min],"append-inner-icon":i(o)?"ri-eye-off-line":"ri-eye-line",type:i(o)?"text":"password",name:"input-10-2",placeholder:"············",label:"Visible",hint:"At least 8 characters","onClick:appendInner":m[3]||(m[3]=b=>o.value=!i(o))},null,8,["modelValue","rules","append-inner-icon","type"])]),_:1})]),_:1}))}},J={};function K(x,n){return f(),V(d,{placeholder:"Placeholder Text"},{label:l(()=>[n[0]||(n[0]=a(" What about  ")),n[1]||(n[1]=t("strong",null,"icon",-1)),n[2]||(n[2]=a(" here? ")),e(y,{icon:"ri-file-search-line"})]),_:1})}const Q=T(J,[["render",K]]),X={__name:"DemoTextfieldIconEvents",setup(x){const n=h("Hey!"),o=h(!0),u=h(0),r=()=>{o.value=!o.value},p=()=>{n.value=""},c=()=>{u.value=0},m=()=>{c(),p()};return(b,v)=>(f(),V(d,{modelValue:i(n),"onUpdate:modelValue":v[0]||(v[0]=C=>_(n)?n.value=C:null),clearable:"",type:"text",label:"Message",color:"primary",placeholder:"Hey!!","clear-icon":"ri-close-line","append-icon":i(n)?"ri-send-plane-2-line":"ri-mic-line","append-inner-icon":i(o)?"ri-pushpin-line":"ri-unpin-line","onClick:appendInner":r,"onClick:append":m,"onClick:clear":p},null,8,["modelValue","append-icon","append-inner-icon"]))}},Z={__name:"DemoTextfieldValidation",setup(x){const n=h("");return(o,u)=>(f(),V(W,null,{default:l(()=>[e(d,{modelValue:i(n),"onUpdate:modelValue":u[0]||(u[0]=r=>_(n)?n.value=r:null),rules:["requiredValidator"in o?o.requiredValidator:i(B),"emailValidator"in o?o.emailValidator:i(E)],placeholder:"johnedoe@email.com",label:"E-mail"},null,8,["modelValue","rules"])]),_:1}))}},ee={};function le(x,n){return f(),V(d,{label:"Regular",placeholder:"Placeholder Text","single-line":""})}const oe=T(ee,[["render",le]]),te={__name:"DemoTextfieldPrefixesAndSuffixes",setup(x){const n=h(10.05),o=h(28.02),u=h("example"),r=h("04:56");return(p,c)=>(f(),V(g,null,{default:l(()=>[e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(n),"onUpdate:modelValue":c[0]||(c[0]=m=>_(n)?n.value=m:null),label:"Amount",prefix:"$",type:"number",placeholder:"10.05"},null,8,["modelValue"])]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(o),"onUpdate:modelValue":c[1]||(c[1]=m=>_(o)?o.value=m:null),label:"Weight",suffix:"lbs",type:"number",placeholder:"28.02"},null,8,["modelValue"])]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(u),"onUpdate:modelValue":c[2]||(c[2]=m=>_(u)?u.value=m:null),label:"Email address",suffix:"@gmail.com",placeholder:"example"},null,8,["modelValue"])]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(r),"onUpdate:modelValue":c[3]||(c[3]=m=>_(r)?r.value=m:null),label:"Label Text",type:"time",suffix:"PST",placeholder:"04:56"},null,8,["modelValue"])]),_:1})]),_:1}))}},ne={};function ae(x,n){return f(),V(g,null,{default:l(()=>[e(s,{cols:"12"},{default:l(()=>[e(d,{label:"Prepend","prepend-icon":"ri-map-pin-line",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{label:"Prepend Inner","prepend-inner-icon":"ri-map-pin-line",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{label:"Append","append-icon":"ri-map-pin-line",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{label:"Append Inner","append-inner-icon":"ri-map-pin-line",placeholder:"Placeholder Text"})]),_:1})]),_:1})}const ie=T(ne,[["render",ae]]),re={};function se(x,n){return f(),V(d,{color:"success",label:"First name",placeholder:"Placeholder Text"})}const de=T(re,[["render",se]]),ce={};function pe(x,n){return f(),V(d,{placeholder:"Placeholder Text",label:"Regular",clearable:""})}const me=T(ce,[["render",pe]]),ue={__name:"DemoTextfieldCounter",setup(x){const n=h("Preliminary report"),o=h("California is a state in the western United States"),u=[r=>r.length<=25||"Max 25 characters"];return(r,p)=>(f(),V(g,null,{default:l(()=>[e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(n),"onUpdate:modelValue":p[0]||(p[0]=c=>_(n)?n.value=c:null),rules:u,counter:"25",placeholder:"Placeholder Text",hint:"This field uses counter prop",label:"Regular"},null,8,["modelValue"])]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{modelValue:i(o),"onUpdate:modelValue":p[1]||(p[1]=c=>_(o)?o.value=c:null),rules:u,counter:"",maxlength:"25",placeholder:"Placeholder Text",hint:"This field uses maxlength attribute",label:"Limit exceeded"},null,8,["modelValue"])]),_:1})]),_:1}))}},fe={};function Ve(x,n){return f(),V(g,null,{default:l(()=>[e(s,null,{default:l(()=>[e(d,{label:"Disabled",placeholder:"Placeholder Text",disabled:""})]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(d,{placeholder:"Placeholder Text",label:"Readonly",readonly:""})]),_:1})]),_:1})}const he=T(fe,[["render",Ve]]),xe={};function _e(x,n){return f(),V(g,null,{default:l(()=>[e(s,{cols:"12",md:"6"},{default:l(()=>[e(d,{label:"Outlined",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(d,{label:"Filled",variant:"filled",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(d,{label:"Solo",variant:"solo",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(d,{label:"Plain",variant:"plain",placeholder:"Placeholder Text"})]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(d,{label:"Underlined",variant:"underlined",placeholder:"Placeholder Text"})]),_:1})]),_:1})}const Te=T(xe,[["render",_e]]),be={};function ge(x,n){return f(),V(d,{label:"Compact",density:"compact",placeholder:"Placeholder Text"})}const ve=T(be,[["render",ge]]),Ce={};function ye(x,n){return f(),V(d,{label:"Regular",placeholder:"Placeholder Text"})}const we=T(Ce,[["render",ye]]),Pe={ts:`<template>
  <VTextField
    label="Regular"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<template>
  <VTextField
    label="Regular"
    placeholder="Placeholder Text"
  />
</template>
`},Fe={ts:`<template>
  <VTextField
    placeholder="Placeholder Text"
    label="Regular"
    clearable
  />
</template>
`,js:`<template>
  <VTextField
    placeholder="Placeholder Text"
    label="Regular"
    clearable
  />
</template>
`},ke={ts:`<script lang="ts" setup>
const title = ref('Preliminary report')
const description = ref('California is a state in the western United States')
const rules = [(v: string) => v.length <= 25 || 'Max 25 characters']
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        v-model="title"
        :rules="rules"
        counter="25"
        placeholder="Placeholder Text"
        hint="This field uses counter prop"
        label="Regular"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="description"
        :rules="rules"
        counter
        maxlength="25"
        placeholder="Placeholder Text"
        hint="This field uses maxlength attribute"
        label="Limit exceeded"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const title = ref('Preliminary report')
const description = ref('California is a state in the western United States')
const rules = [v => v.length <= 25 || 'Max 25 characters']
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        v-model="title"
        :rules="rules"
        counter="25"
        placeholder="Placeholder Text"
        hint="This field uses counter prop"
        label="Regular"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="description"
        :rules="rules"
        counter
        maxlength="25"
        placeholder="Placeholder Text"
        hint="This field uses maxlength attribute"
        label="Limit exceeded"
      />
    </VCol>
  </VRow>
</template>
`},Re={ts:`<template>
  <VTextField
    color="success"
    label="First name"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<template>
  <VTextField
    color="success"
    label="First name"
    placeholder="Placeholder Text"
  />
</template>
`},Ie={ts:`<template>
  <VTextField
    label="Compact"
    density="compact"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<template>
  <VTextField
    label="Compact"
    density="compact"
    placeholder="Placeholder Text"
  />
</template>
`},$e={ts:`<script lang="ts" setup>
const message = ref('Hey!')
const marker = ref(true)
const iconIndex = ref(0)

const toggleMarker = () => {
  marker.value = !marker.value
}

const clearMessage = () => {
  message.value = ''
}

const resetIcon = () => {
  iconIndex.value = 0
}

const sendMessage = () => {
  resetIcon()
  clearMessage()
}
<\/script>

<template>
  <VTextField
    v-model="message"
    clearable
    type="text"
    label="Message"
    color="primary"
    placeholder="Hey!!"
    clear-icon="ri-close-line"
    :append-icon="message ? 'ri-send-plane-2-line' : 'ri-mic-line'"
    :append-inner-icon="marker ? 'ri-pushpin-line' : 'ri-unpin-line'"
    @click:append-inner="toggleMarker"
    @click:append="sendMessage"
    @click:clear="clearMessage"
  />
</template>
`,js:`<script setup>
const message = ref('Hey!')
const marker = ref(true)
const iconIndex = ref(0)

const toggleMarker = () => {
  marker.value = !marker.value
}

const clearMessage = () => {
  message.value = ''
}

const resetIcon = () => {
  iconIndex.value = 0
}

const sendMessage = () => {
  resetIcon()
  clearMessage()
}
<\/script>

<template>
  <VTextField
    v-model="message"
    clearable
    type="text"
    label="Message"
    color="primary"
    placeholder="Hey!!"
    clear-icon="ri-close-line"
    :append-icon="message ? 'ri-send-plane-2-line' : 'ri-mic-line'"
    :append-inner-icon="marker ? 'ri-pushpin-line' : 'ri-unpin-line'"
    @click:append-inner="toggleMarker"
    @click:append="sendMessage"
    @click:clear="clearMessage"
  />
</template>
`},Me={ts:`<script lang="ts" setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const message = ref('Hey!')
const loading = ref(false)

const clickMe = () => {
  loading.value = true
  message.value = 'Wait for it...'

  setTimeout(() => {
    loading.value = false
    message.value = 'You've clicked me!'
  }, 2000)
}
<\/script>

<template>
  <VTextField
    v-model="message"
    density="compact"
    clearable
    clear-icon="ri-close-line"
    label="Message"
    placeholder="Hey!!"
    type="text"
  >
    <!-- Prepend -->
    <template #prepend>
      <VTooltip location="bottom">
        <template #activator="{ props }">
          <VIcon
            v-bind="props"
            icon="ri-question-line"
          />
        </template>
        I'm a tooltip
      </VTooltip>
    </template>

    <!-- AppendInner -->
    <template #append-inner>
      <VFadeTransition leave-absolute>
        <VProgressCircular
          v-if="loading"
          size="24"
          color="info"
          indeterminate
        />

        <VNodeRenderer
          v-else
          :nodes="themeConfig.app.logo"
        />
      </VFadeTransition>
    </template>

    <!-- Append -->
    <template #append>
      <VBtn
        :size="$vuetify.display.smAndDown ? 'small' : 'large'"
        :icon="$vuetify.display.smAndDown"
        @click="clickMe"
      >
        <VIcon icon="ri-focus-3-line" />
        <span
          v-if="$vuetify.display.mdAndUp"
          class="ms-3"
        >Click me</span>
      </VBtn>
    </template>
  </VTextField>
</template>
`,js:`<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const message = ref('Hey!')
const loading = ref(false)

const clickMe = () => {
  loading.value = true
  message.value = 'Wait for it...'
  setTimeout(() => {
    loading.value = false
    message.value = 'You've clicked me!'
  }, 2000)
}
<\/script>

<template>
  <VTextField
    v-model="message"
    density="compact"
    clearable
    clear-icon="ri-close-line"
    label="Message"
    placeholder="Hey!!"
    type="text"
  >
    <!-- Prepend -->
    <template #prepend>
      <VTooltip location="bottom">
        <template #activator="{ props }">
          <VIcon
            v-bind="props"
            icon="ri-question-line"
          />
        </template>
        I'm a tooltip
      </VTooltip>
    </template>

    <!-- AppendInner -->
    <template #append-inner>
      <VFadeTransition leave-absolute>
        <VProgressCircular
          v-if="loading"
          size="24"
          color="info"
          indeterminate
        />

        <VNodeRenderer
          v-else
          :nodes="themeConfig.app.logo"
        />
      </VFadeTransition>
    </template>

    <!-- Append -->
    <template #append>
      <VBtn
        :size="$vuetify.display.smAndDown ? 'small' : 'large'"
        :icon="$vuetify.display.smAndDown"
        @click="clickMe"
      >
        <VIcon icon="ri-focus-3-line" />
        <span
          v-if="$vuetify.display.mdAndUp"
          class="ms-3"
        >Click me</span>
      </VBtn>
    </template>
  </VTextField>
</template>
`},Ae={ts:`<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        label="Prepend"
        prepend-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Prepend Inner"
        prepend-inner-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Append"
        append-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Append Inner"
        append-inner-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        label="Prepend"
        prepend-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Prepend Inner"
        prepend-inner-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Append"
        append-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        label="Append Inner"
        append-inner-icon="ri-map-pin-line"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`},De={ts:`<template>
  <VTextField placeholder="Placeholder Text">
    <template #label>
      What about &nbsp;<strong>icon</strong>&nbsp;here?
      <VIcon icon="ri-file-search-line" />
    </template>
  </VTextField>
</template>
`,js:`<template>
  <VTextField placeholder="Placeholder Text">
    <template #label>
      What about &nbsp;<strong>icon</strong>&nbsp;here?
      <VIcon icon="ri-file-search-line" />
    </template>
  </VTextField>
</template>
`},Ue={ts:`<script lang="ts" setup>
const show1 = ref(false)
const show2 = ref(true)
const password = ref('Password')
const confirmPassword = ref('wqfasds')

const rules = {
  required: (value: string) => !!value || 'Required.',
  min: (v: string) => v.length >= 8 || 'Min 8 characters',
}
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextField
        v-model="password"
        :append-inner-icon="show1 ? 'ri-eye-off-line' : 'ri-eye-line'"
        :rules="[rules.required, rules.min]"
        :type="show1 ? 'text' : 'password'"
        name="input-10-1"
        label="Normal with hint text"
        hint="At least 8 characters"
        placeholder="············"
        counter
        @click:append-inner="show1 = !show1"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextField
        v-model="confirmPassword"
        :rules="[rules.required, rules.min]"
        :append-inner-icon="show2 ? 'ri-eye-off-line' : 'ri-eye-line'"
        :type="show2 ? 'text' : 'password'"
        name="input-10-2"
        placeholder="············"
        label="Visible"
        hint="At least 8 characters"
        @click:append-inner="show2 = !show2"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const show1 = ref(false)
const show2 = ref(true)
const password = ref('Password')
const confirmPassword = ref('wqfasds')

const rules = {
  required: value => !!value || 'Required.',
  min: v => v.length >= 8 || 'Min 8 characters',
}
<\/script>

<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextField
        v-model="password"
        :append-inner-icon="show1 ? 'ri-eye-off-line' : 'ri-eye-line'"
        :rules="[rules.required, rules.min]"
        :type="show1 ? 'text' : 'password'"
        name="input-10-1"
        label="Normal with hint text"
        hint="At least 8 characters"
        placeholder="············"
        counter
        @click:append-inner="show1 = !show1"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextField
        v-model="confirmPassword"
        :rules="[rules.required, rules.min]"
        :append-inner-icon="show2 ? 'ri-eye-off-line' : 'ri-eye-line'"
        :type="show2 ? 'text' : 'password'"
        name="input-10-2"
        placeholder="············"
        label="Visible"
        hint="At least 8 characters"
        @click:append-inner="show2 = !show2"
      />
    </VCol>
  </VRow>
</template>
`},qe={ts:`<script setup lang="ts">
const amount = ref(10.05)
const weight = ref(28.02)
const email = ref('example')
const time = ref('04:56')
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        v-model="amount"
        label="Amount"
        prefix="$"
        type="number"
        placeholder="10.05"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="weight"
        label="Weight"
        suffix="lbs"
        type="number"
        placeholder="28.02"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="email"
        label="Email address"
        suffix="@gmail.com"
        placeholder="example"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="time"
        label="Label Text"
        type="time"
        suffix="PST"
        placeholder="04:56"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const amount = ref(10.05)
const weight = ref(28.02)
const email = ref('example')
const time = ref('04:56')
<\/script>

<template>
  <VRow>
    <VCol cols="12">
      <VTextField
        v-model="amount"
        label="Amount"
        prefix="$"
        type="number"
        placeholder="10.05"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="weight"
        label="Weight"
        suffix="lbs"
        type="number"
        placeholder="28.02"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="email"
        label="Email address"
        suffix="@gmail.com"
        placeholder="example"
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        v-model="time"
        label="Label Text"
        type="time"
        suffix="PST"
        placeholder="04:56"
      />
    </VCol>
  </VRow>
</template>
`},Se={ts:`<template>
  <VTextField
    label="Regular"
    placeholder="Placeholder Text"
    single-line
  />
</template>
`,js:`<template>
  <VTextField
    label="Regular"
    placeholder="Placeholder Text"
    single-line
  />
</template>
`},je={ts:`<template>
  <VRow>
    <VCol>
      <VTextField
        label="Disabled"
        placeholder="Placeholder Text"
        disabled
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        placeholder="Placeholder Text"
        label="Readonly"
        readonly
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol>
      <VTextField
        label="Disabled"
        placeholder="Placeholder Text"
        disabled
      />
    </VCol>

    <VCol cols="12">
      <VTextField
        placeholder="Placeholder Text"
        label="Readonly"
        readonly
      />
    </VCol>
  </VRow>
</template>
`},He={ts:`<script lang="ts" setup>
const email = ref('')
<\/script>

<template>
  <VForm>
    <VTextField
      v-model="email"
      :rules="[requiredValidator, emailValidator]"
      placeholder="johnedoe@email.com"
      label="E-mail"
    />
  </VForm>
</template>
`,js:`<script setup>
const email = ref('')
<\/script>

<template>
  <VForm>
    <VTextField
      v-model="email"
      :rules="[requiredValidator, emailValidator]"
      placeholder="johnedoe@email.com"
      label="E-mail"
    />
  </VForm>
</template>
`},Ne={ts:`<template>
  <VRow>
    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Outlined"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Filled"
        variant="filled"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Solo"
        variant="solo"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Plain"
        variant="plain"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Underlined"
        variant="underlined"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Outlined"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Filled"
        variant="filled"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Solo"
        variant="solo"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Plain"
        variant="plain"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VTextField
        label="Underlined"
        variant="underlined"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`},cl={__name:"textfield",setup(x){return(n,o)=>{const u=we,r=z,p=ve,c=Te,m=he,b=ue,v=me,C=de,w=ie,P=te,F=oe,k=Z,R=X,I=Q,$=G,M=O;return f(),V(g,{class:"match-height"},{default:l(()=>[e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Basic",code:i(Pe)},{default:l(()=>[o[0]||(o[0]=t("p",null,"Text fields components are used for collecting user provided information.",-1)),e(u)]),_:1,__:[0]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Density",code:i(Ie)},{default:l(()=>[o[1]||(o[1]=t("p",null,[a("The "),t("code",null,"density"),a(" prop decreases the height of the text field based upon 1 of 3 levels of density; "),t("code",null,"default"),a(", "),t("code",null,"comfortable"),a(", and "),t("code",null,"compact"),a(".")],-1)),e(p)]),_:1,__:[1]},8,["code"])]),_:1}),e(s,{cols:"12"},{default:l(()=>[e(r,{title:"Variant",code:i(Ne)},{default:l(()=>[o[2]||(o[2]=t("p",null,[a("Use "),t("code",null,"solo"),a(", "),t("code",null,"filled"),a(", "),t("code",null,"outlined"),a(", "),t("code",null,"plain"),a(" and "),t("code",null,"underlined"),a(" option of "),t("code",null,"variant"),a(" prop to change the look of the textfield. ")],-1)),e(c)]),_:1,__:[2]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"State",code:i(je)},{default:l(()=>[o[3]||(o[3]=t("p",null,"Text fields can be disabled or readonly.",-1)),e(m)]),_:1,__:[3]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Counter",code:i(ke)},{default:l(()=>[o[4]||(o[4]=t("p",null,[a("Use a "),t("code",null,"counter"),a(" prop to inform a user of the character limit.")],-1)),e(b)]),_:1,__:[4]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Clearable",code:i(Fe)},{default:l(()=>[o[5]||(o[5]=t("p",null,"When clearable, you can customize the clear icon with clear-icon.",-1)),e(v)]),_:1,__:[5]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Custom Colors",code:i(Re)},{default:l(()=>[o[6]||(o[6]=t("p",null,[a("Use "),t("code",null,"color"),a(" prop to change the input border color.")],-1)),e(C)]),_:1,__:[6]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Icons",code:i(Ae)},{default:l(()=>[o[7]||(o[7]=t("p",null,[a("You can add icons to the text field with "),t("code",null,"prepend-icon"),a(", "),t("code",null,"append-icon"),a(" and "),t("code",null,"append-inner-icon"),a(" and "),t("code",null,"prepend-inner-icon"),a(" props.")],-1)),e(w)]),_:1,__:[7]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Prefixes and suffixes",code:i(qe)},{default:l(()=>[o[8]||(o[8]=t("p",null,[a("The "),t("code",null,"prefix"),a(" and "),t("code",null,"suffix"),a(" properties allows you to prepend and append inline non-modifiable text next to the text field.")],-1)),e(P)]),_:1,__:[8]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Single line",code:i(Se)},{default:l(()=>[o[9]||(o[9]=t("p",null,[t("code",null,"single-line"),a(" text fields do not float their label on focus or with data.")],-1)),e(F)]),_:1,__:[9]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Validation",code:i(He)},{default:l(()=>[o[10]||(o[10]=t("p",null,[a("Vuetify includes simple validation through the "),t("code",null,"rules"),a(" prop.")],-1)),e(k)]),_:1,__:[10]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Icon events",code:i($e)},{default:l(()=>[o[11]||(o[11]=t("p",null,[t("code",null,"click:prepend"),a(", "),t("code",null,"click:append"),a(", "),t("code",null,"click:append-inner"),a(", and "),t("code",null,"click:clear"),a(" will be emitted when you click on the respective icon")],-1)),e(R)]),_:1,__:[11]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Label Slot",code:i(De)},{default:l(()=>[o[12]||(o[12]=t("p",null,[a("Text field label can be defined in "),t("code",null,"label"),a(" slot - that will allow to use HTML content.")],-1)),e(I)]),_:1,__:[12]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Password input",code:i(Ue)},{default:l(()=>[o[13]||(o[13]=t("p",null,[a("Using the HTML input "),t("code",null,"type"),a(" password can be used with an appended icon and callback to control the visibility.")],-1)),e($)]),_:1,__:[13]},8,["code"])]),_:1}),e(s,{cols:"12",md:"6"},{default:l(()=>[e(r,{title:"Icon slots",code:i(Me)},{default:l(()=>[o[14]||(o[14]=t("p",null,[a("Instead of using "),t("code",null,"prepend"),a("/"),t("code",null,"append"),a("/"),t("code",null,"append-inner"),a(" icons you can use slots to extend input's functionality.")],-1)),e(M)]),_:1,__:[14]},8,["code"])]),_:1})]),_:1})}}};export{cl as default};
