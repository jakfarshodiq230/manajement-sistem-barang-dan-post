import{r as V,b as j,i as D,a as O,c as Y,l as L,p as $,d as A,f as I,e as N,u as x}from"./validators-BTYlBg-y.js";import{r as s,o as y,f as g,e as o,b as l,l as e,aG as p,ai as U,s as q,aN as B,d as S}from"./main-BdxVNj0W.js";import{V as P,a as d}from"./VRow-vrTmU0A8.js";import{V as c}from"./VTextField-fBFfJByD.js";import{V as E}from"./VForm-BBCMO0CC.js";import{_ as J}from"./AppCardCode-50yCyEpV.js";import"./helpers-DK5QwNv0.js";/* empty css              *//* empty css                   */import"./VCounter-CgQz9E3-.js";import"./VField-K8nsQEDh.js";import"./form-Bqx4gi5N.js";import"./VInput-B5qWrE6y.js";import"./VImg-CJH8Rt0U.js";import"./vue3-perfect-scrollbar-64k2zWlB.js";import"./VCard-XkorCH1G.js";import"./createSimpleFunctional-DX9efoFk.js";import"./VAvatar-C-Xdvx7y.js";import"./VCardText-B2uwaTKx.js";import"./VDivider-iqf11oAx.js";const G={__name:"DemoFormValidationValidationTypes",setup(T){const w=s(""),h=s(""),C=s(""),n=s(""),m=s(""),f=s(""),F=s(""),u=s(""),t=s(""),b=s(""),v=s(""),R=s(""),k=s();return(r,a)=>(y(),g(e(E),{ref_key:"refForm",ref:k,onSubmit:a[13]||(a[13]=B(()=>{},["prevent"]))},{default:o(()=>[l(P,null,{default:o(()=>[l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(w),"onUpdate:modelValue":a[0]||(a[0]=i=>p(w)?w.value=i:null),"persistent-placeholder":"",placeholder:"This field is required",rules:["requiredValidator"in r?r.requiredValidator:e(V)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(h),"onUpdate:modelValue":a[1]||(a[1]=i=>p(h)?h.value=i:null),"persistent-placeholder":"",placeholder:"Enter Number between 10 & 20",rules:["requiredValidator"in r?r.requiredValidator:e(V),("betweenValidator"in r?r.betweenValidator:e(j))(e(h),10,20)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(C),"onUpdate:modelValue":a[2]||(a[2]=i=>p(C)?C.value=i:null),"persistent-placeholder":"",placeholder:"Must only consist of numbers",rules:["requiredValidator"in r?r.requiredValidator:e(V),"integerValidator"in r?r.integerValidator:e(D)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(n),"onUpdate:modelValue":a[3]||(a[3]=i=>p(n)?n.value=i:null),"persistent-placeholder":"",placeholder:"Must match the specified regular expression : ^([0-9]+)$ - numbers only",rules:["requiredValidator"in r?r.requiredValidator:e(V),("regexValidator"in r?r.regexValidator:e(O))(e(n),"^([0-9]+)$")]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(m),"onUpdate:modelValue":a[4]||(a[4]=i=>p(m)?m.value=i:null),"persistent-placeholder":"",placeholder:"Only alphabetic characters",rules:["requiredValidator"in r?r.requiredValidator:e(V),"alphaValidator"in r?r.alphaValidator:e(Y)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(f),"onUpdate:modelValue":a[5]||(a[5]=i=>p(f)?f.value=i:null),"persistent-placeholder":"",placeholder:"Length must be exactly 3 characters",rules:["requiredValidator"in r?r.requiredValidator:e(V),("lengthValidator"in r?r.lengthValidator:e(L))(e(f),3)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(F),"onUpdate:modelValue":a[6]||(a[6]=i=>p(F)?F.value=i:null),"persistent-placeholder":"",placeholder:"Password Input Field",type:"password",rules:["requiredValidator"in r?r.requiredValidator:e(V),"passwordValidator"in r?r.passwordValidator:e($)],autocomplete:"on"},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(u),"onUpdate:modelValue":a[7]||(a[7]=i=>p(u)?u.value=i:null),"persistent-placeholder":"",placeholder:"The digits field must be numeric and exactly contain 3 digits",rules:["requiredValidator"in r?r.requiredValidator:e(V),("lengthValidator"in r?r.lengthValidator:e(L))(e(u),3),"integerValidator"in r?r.integerValidator:e(D)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(t),"onUpdate:modelValue":a[8]||(a[8]=i=>p(t)?t.value=i:null),"persistent-placeholder":"",placeholder:"Repeat password must match",type:"password",rules:["requiredValidator"in r?r.requiredValidator:e(V),("confirmedValidator"in r?r.confirmedValidator:e(A))(e(t),e(F))],autocomplete:"on"},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(b),"onUpdate:modelValue":a[9]||(a[9]=i=>p(b)?b.value=i:null),"persistent-placeholder":"",placeholder:"Only alphabetic characters, numbers, dashes or underscores",rules:["requiredValidator"in r?r.requiredValidator:e(V),"alphaDashValidator"in r?r.alphaDashValidator:e(I)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(v),"onUpdate:modelValue":a[10]||(a[10]=i=>p(v)?v.value=i:null),"persistent-placeholder":"",placeholder:"Must be a valid email",rules:["requiredValidator"in r?r.requiredValidator:e(V),"emailValidator"in r?r.emailValidator:e(N)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(R),"onUpdate:modelValue":a[11]||(a[11]=i=>p(R)?R.value=i:null),"persistent-placeholder":"",placeholder:"Must be a valid url",rules:["requiredValidator"in r?r.requiredValidator:e(V),"urlValidator"in r?r.urlValidator:e(x)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12"},{default:o(()=>[l(U,{type:"submit",onClick:a[12]||(a[12]=i=>{var M;return(M=e(k))==null?void 0:M.validate()})},{default:o(()=>a[14]||(a[14]=[q(" Submit ")])),_:1,__:[14]})]),_:1})]),_:1})]),_:1},512))}},z={__name:"DemoFormValidationValidatingMultipleRules",setup(T){const w=s(""),h=s(""),C=s(),n=s(""),m=s(""),f=s(!1),F=s(!1);return(u,t)=>(y(),g(e(E),{ref_key:"refForm",ref:C,onSubmit:B(()=>{},["prevent"])},{default:o(()=>[l(P,null,{default:o(()=>[l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(w),"onUpdate:modelValue":t[0]||(t[0]=b=>p(w)?w.value=b:null),label:"Name",placeholder:"Your Name",rules:["requiredValidator"in u?u.requiredValidator:e(V)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(h),"onUpdate:modelValue":t[1]||(t[1]=b=>p(h)?h.value=b:null),label:"Email",placeholder:"Your Email",rules:["requiredValidator"in u?u.requiredValidator:e(V),"emailValidator"in u?u.emailValidator:e(N)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(n),"onUpdate:modelValue":t[2]||(t[2]=b=>p(n)?n.value=b:null),label:"Password",type:e(f)?"text":"password","append-inner-icon":e(f)?"ri-eye-off-line":"ri-eye-line",placeholder:"Enter Password",rules:["requiredValidator"in u?u.requiredValidator:e(V),"passwordValidator"in u?u.passwordValidator:e($)],autocomplete:"on","onClick:appendInner":t[3]||(t[3]=b=>f.value=!e(f))},null,8,["modelValue","type","append-inner-icon","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(m),"onUpdate:modelValue":t[4]||(t[4]=b=>p(m)?m.value=b:null),label:"Confirm Password",autocomplete:"confirm-password",type:e(F)?"text":"password",placeholder:"Confirm Password","append-inner-icon":e(m)?"ri-eye-off-line":"ri-eye-line",rules:["requiredValidator"in u?u.requiredValidator:e(V),("confirmedValidator"in u?u.confirmedValidator:e(A))(e(m),e(n))],"onClick:appendInner":t[5]||(t[5]=b=>F.value=!e(F))},null,8,["modelValue","type","append-inner-icon","rules"])]),_:1}),l(d,{cols:"12"},{default:o(()=>[l(U,{type:"submit",onClick:t[6]||(t[6]=b=>{var v;return(v=e(C))==null?void 0:v.validate()})},{default:o(()=>t[7]||(t[7]=[q(" Submit ")])),_:1,__:[7]})]),_:1})]),_:1})]),_:1},512))}},H={__name:"DemoFormValidationSimpleFormValidation",setup(T){const w=s(""),h=s(""),C=s();return(n,m)=>(y(),g(e(E),{ref_key:"refForm",ref:C,onSubmit:B(()=>{},["prevent"])},{default:o(()=>[l(P,null,{default:o(()=>[l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(w),"onUpdate:modelValue":m[0]||(m[0]=f=>p(w)?w.value=f:null),label:"First Name",placeholder:"John",rules:["requiredValidator"in n?n.requiredValidator:e(V)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12",md:"6"},{default:o(()=>[l(c,{modelValue:e(h),"onUpdate:modelValue":m[1]||(m[1]=f=>p(h)?h.value=f:null),label:"Email",placeholder:"john@email.com",rules:["requiredValidator"in n?n.requiredValidator:e(V),"emailValidator"in n?n.emailValidator:e(N)]},null,8,["modelValue","rules"])]),_:1}),l(d,{cols:"12"},{default:o(()=>[l(U,{type:"submit",onClick:m[2]||(m[2]=f=>{var F;return(F=e(C))==null?void 0:F.validate()})},{default:o(()=>m[3]||(m[3]=[q(" Submit ")])),_:1,__:[3]})]),_:1})]),_:1})]),_:1},512))}},K={ts:`<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'

const firstName = ref('')
const email = ref('')

const refForm = ref<VForm>()
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent="() => {}"
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="firstName"
          label="First Name"
          placeholder="John"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          label="Email"
          placeholder="john@email.com"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`,js:`<script setup>
import { VForm } from 'vuetify/components/VForm'

const firstName = ref('')
const email = ref('')
const refForm = ref()
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent="() => {}"
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="firstName"
          label="First Name"
          placeholder="John"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          label="Email"
          placeholder="john@email.com"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`},Q={ts:`<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'

const name = ref('')
const email = ref('')
const refForm = ref<VForm>()
const password = ref('')
const confirmPassword = ref('')
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent="() => {}"
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="name"
          label="Name"
          placeholder="Your Name"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          label="Email"
          placeholder="Your Email"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="password"
          label="Password"
          :type="isPasswordVisible ? 'text' : 'password'"
          :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
          placeholder="Enter Password"
          :rules="[requiredValidator, passwordValidator]"
          autocomplete="on"
          @click:append-inner="isPasswordVisible = !isPasswordVisible"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="confirmPassword"
          label="Confirm Password"
          autocomplete="confirm-password"
          :type="isConfirmPasswordVisible ? 'text' : 'password'"
          placeholder="Confirm Password"
          :append-inner-icon="confirmPassword ? 'ri-eye-off-line' : 'ri-eye-line'"
          :rules="[requiredValidator, confirmedValidator(confirmPassword, password)]"
          @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`,js:`<script setup>
import { VForm } from 'vuetify/components/VForm'

const name = ref('')
const email = ref('')
const refForm = ref()
const password = ref('')
const confirmPassword = ref('')
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent="() => {}"
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="name"
          label="Name"
          placeholder="Your Name"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          label="Email"
          placeholder="Your Email"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="password"
          label="Password"
          :type="isPasswordVisible ? 'text' : 'password'"
          :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
          placeholder="Enter Password"
          :rules="[requiredValidator, passwordValidator]"
          autocomplete="on"
          @click:append-inner="isPasswordVisible = !isPasswordVisible"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="confirmPassword"
          label="Confirm Password"
          autocomplete="confirm-password"
          :type="isConfirmPasswordVisible ? 'text' : 'password'"
          placeholder="Confirm Password"
          :append-inner-icon="confirmPassword ? 'ri-eye-off-line' : 'ri-eye-line'"
          :rules="[requiredValidator, confirmedValidator(confirmPassword, password)]"
          @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`},W={ts:`<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'

const requiredField = ref('')
const numberBetween10to20 = ref('')
const onlyConsistNumber = ref('')
const matchRegularEx = ref('')
const onlyAlphabeticCharacters = ref('')
const specifiedLength = ref('')
const password = ref('')
const digits = ref('')
const repeatPassword = ref('')
const onlyAlphabeticNumbersDashesUnderscores = ref('')
const email = ref('')
const validURL = ref('')
const refForm = ref<VForm>()
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="requiredField"
          persistent-placeholder
          placeholder="This field is required"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="numberBetween10to20"
          persistent-placeholder
          placeholder="Enter Number between 10 & 20"
          :rules="[requiredValidator, betweenValidator(numberBetween10to20, 10, 20)]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyConsistNumber"
          persistent-placeholder
          placeholder="Must only consist of numbers"
          :rules="[requiredValidator, integerValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="matchRegularEx"
          persistent-placeholder
          placeholder="Must match the specified regular expression : ^([0-9]+)$ - numbers only"
          :rules="[requiredValidator, regexValidator(matchRegularEx, '^([0-9]+)$')]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyAlphabeticCharacters"
          persistent-placeholder
          placeholder="Only alphabetic characters"
          :rules="[requiredValidator, alphaValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="specifiedLength"
          persistent-placeholder
          placeholder="Length must be exactly 3 characters"
          :rules="[requiredValidator, lengthValidator(specifiedLength, 3)]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="password"
          persistent-placeholder
          placeholder="Password Input Field"
          type="password"
          :rules="[requiredValidator, passwordValidator]"
          autocomplete="on"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="digits"
          persistent-placeholder
          placeholder="The digits field must be numeric and exactly contain 3 digits"
          :rules="[requiredValidator, lengthValidator(digits, 3), integerValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="repeatPassword"
          persistent-placeholder
          placeholder="Repeat password must match"
          type="password"
          :rules="[requiredValidator, confirmedValidator(repeatPassword, password)]"
          autocomplete="on"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyAlphabeticNumbersDashesUnderscores"
          persistent-placeholder
          placeholder="Only alphabetic characters, numbers, dashes or underscores"
          :rules="[requiredValidator, alphaDashValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          persistent-placeholder
          placeholder="Must be a valid email"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="validURL"
          persistent-placeholder
          placeholder="Must be a valid url"
          :rules="[requiredValidator, urlValidator]"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`,js:`<script setup>
import { VForm } from 'vuetify/components/VForm'

const requiredField = ref('')
const numberBetween10to20 = ref('')
const onlyConsistNumber = ref('')
const matchRegularEx = ref('')
const onlyAlphabeticCharacters = ref('')
const specifiedLength = ref('')
const password = ref('')
const digits = ref('')
const repeatPassword = ref('')
const onlyAlphabeticNumbersDashesUnderscores = ref('')
const email = ref('')
const validURL = ref('')
const refForm = ref()
<\/script>

<template>
  <VForm
    ref="refForm"
    @submit.prevent
  >
    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="requiredField"
          persistent-placeholder
          placeholder="This field is required"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="numberBetween10to20"
          persistent-placeholder
          placeholder="Enter Number between 10 & 20"
          :rules="[requiredValidator, betweenValidator(numberBetween10to20, 10, 20)]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyConsistNumber"
          persistent-placeholder
          placeholder="Must only consist of numbers"
          :rules="[requiredValidator, integerValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="matchRegularEx"
          persistent-placeholder
          placeholder="Must match the specified regular expression : ^([0-9]+)$ - numbers only"
          :rules="[requiredValidator, regexValidator(matchRegularEx, '^([0-9]+)$')]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyAlphabeticCharacters"
          persistent-placeholder
          placeholder="Only alphabetic characters"
          :rules="[requiredValidator, alphaValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="specifiedLength"
          persistent-placeholder
          placeholder="Length must be exactly 3 characters"
          :rules="[requiredValidator, lengthValidator(specifiedLength, 3)]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="password"
          persistent-placeholder
          placeholder="Password Input Field"
          type="password"
          :rules="[requiredValidator, passwordValidator]"
          autocomplete="on"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="digits"
          persistent-placeholder
          placeholder="The digits field must be numeric and exactly contain 3 digits"
          :rules="[requiredValidator, lengthValidator(digits, 3), integerValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="repeatPassword"
          persistent-placeholder
          placeholder="Repeat password must match"
          type="password"
          :rules="[requiredValidator, confirmedValidator(repeatPassword, password)]"
          autocomplete="on"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="onlyAlphabeticNumbersDashesUnderscores"
          persistent-placeholder
          placeholder="Only alphabetic characters, numbers, dashes or underscores"
          :rules="[requiredValidator, alphaDashValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="email"
          persistent-placeholder
          placeholder="Must be a valid email"
          :rules="[requiredValidator, emailValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VTextField
          v-model="validURL"
          persistent-placeholder
          placeholder="Must be a valid url"
          :rules="[requiredValidator, urlValidator]"
        />
      </VCol>

      <VCol cols="12">
        <VBtn
          type="submit"
          @click="refForm?.validate()"
        >
          Submit
        </VBtn>
      </VCol>
    </VRow>
  </VForm>
</template>
`},be={__name:"form-validation",setup(T){return(w,h)=>{const C=H,n=J,m=z,f=G;return y(),g(P,null,{default:o(()=>[l(d,{cols:"12"},{default:o(()=>[l(n,{title:"Simple Form Validation",code:e(K)},{default:o(()=>[h[0]||(h[0]=S("p",null,[q("Use "),S("code",null,"Rules"),q(" prop to validate the input.")],-1)),l(C)]),_:1,__:[0]},8,["code"])]),_:1}),l(d,{cols:"12"},{default:o(()=>[l(n,{title:"Validating Multiple Rules",code:e(Q)},{default:o(()=>[l(m)]),_:1},8,["code"])]),_:1}),l(d,{cols:"12"},{default:o(()=>[l(n,{title:"Validation Types",code:e(W)},{default:o(()=>[l(f)]),_:1},8,["code"])]),_:1})]),_:1})}}};export{be as default};
