import{V as s}from"./VTextarea-kJ_mRWCk.js";import{r as x,o as p,f as u,aG as V,l as i,e as l,b as e,d as o,s as a}from"./main-DFGL-DHh.js";import{_ as m}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{a as r,V as _}from"./VRow-DgmMvkvy.js";import{_ as y}from"./AppCardCode-G3WCRu5H.js";/* empty css                   */import"./VCounter--BzzbLVW.js";import"./VField-7rQGswgK.js";import"./form-IV8XNcXQ.js";import"./VInput-BvXUAhUT.js";import"./VImg-Bz2dHrbD.js";/* empty css              */import"./vue3-perfect-scrollbar-BHFlqzjo.js";import"./VCard-kiOy51Z4.js";import"./createSimpleFunctional-CcX9AWLD.js";import"./VAvatar-DbbkY1ge.js";import"./VCardText-BPMc_i1m.js";import"./VDivider-BE7Iio7K.js";const k={__name:"DemoTextareaValidation",setup(h){const n=x("Hello!"),t=[d=>d.length<=25||"Max 25 characters"];return(d,c)=>(p(),u(s,{modelValue:i(n),"onUpdate:modelValue":c[0]||(c[0]=T=>V(n)?n.value=T:null),label:"Validation",rules:t,rows:"2",placeholder:"Placeholder Text"},null,8,["modelValue"]))}},j={__name:"DemoTextareaNoResize",setup(h){const n=x("Marshmallow tiramisu pie dessert gingerbread tart caramels marzipan oat cake. Muffin sesame snaps cupcake bonbon cookie tiramisu. Pudding biscuit gingerbread halvah lollipop jelly-o cookie.");return(t,d)=>(p(),u(s,{modelValue:i(n),"onUpdate:modelValue":d[0]||(d[0]=c=>V(n)?n.value=c:null),label:"Text","no-resize":"",rows:"2",placeholder:"Placeholder Text"},null,8,["modelValue"]))}},z={};function $(h,n){return p(),u(_,null,{default:l(()=>[e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"One row","auto-grow":"",rows:"1","row-height":"15",placeholder:"Placeholder Text"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{"auto-grow":"",label:"Two rows",rows:"2",placeholder:"Placeholder Text","row-height":"20"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Three rows","auto-grow":"",rows:"3",placeholder:"Placeholder Text","row-height":"25"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{"auto-grow":"",label:"Four rows",placeholder:"Placeholder Text",rows:"4","row-height":"30"})]),_:1})]),_:1})}const A=m(z,[["render",$]]),H={};function U(h,n){return p(),u(_,null,{default:l(()=>[e(r,{cols:"12"},{default:l(()=>[e(s,{label:"Prepend-icon",rows:"1",placeholder:"Placeholder Text","prepend-icon":"ri-chat-2-line"})]),_:1}),e(r,{cols:"12"},{default:l(()=>[e(s,{"append-icon":"ri-chat-2-line",placeholder:"Placeholder Text",label:"Append-icon",rows:"1"})]),_:1}),e(r,{cols:"12"},{default:l(()=>[e(s,{"prepend-inner-icon":"ri-chat-2-line",label:"Prepend-inner-icon",placeholder:"Placeholder Text",rows:"1"})]),_:1}),e(r,{cols:"12"},{default:l(()=>[e(s,{"append-inner-icon":"ri-chat-2-line",label:"Append-inner-icon",placeholder:"Placeholder Text",rows:"1"})]),_:1})]),_:1})}const M=m(H,[["render",U]]),G={__name:"DemoTextareaCounter",setup(h){const n=x("Hello!");return(t,d)=>(p(),u(s,{modelValue:i(n),"onUpdate:modelValue":d[0]||(d[0]=c=>V(n)?n.value=c:null),counter:"",label:"Text",placeholder:"Placeholder Text"},null,8,["modelValue"]))}},B={__name:"DemoTextareaClearable",setup(h){const n=x("This is clearable text.");return(t,d)=>(p(),u(s,{modelValue:i(n),"onUpdate:modelValue":d[0]||(d[0]=c=>V(n)?n.value=c:null),clearable:"","clear-icon":"ri-close-line",label:"Text",placeholder:"Placeholder Text"},null,8,["modelValue"]))}},F={};function N(h,n){return p(),u(s,{autocomplete:"email",label:"Email",placeholder:"johndoe@email.com"})}const O=m(F,[["render",N]]),S={};function W(h,n){return p(),u(_,null,{default:l(()=>[e(r,{cols:"12"},{default:l(()=>[e(s,{disabled:"",label:"Disabled",hint:"Hint text",placeholder:"Placeholder Text",rows:"2"})]),_:1}),e(r,{cols:"12"},{default:l(()=>[e(s,{readonly:"",rows:"2",label:"Readonly",placeholder:"Placeholder Text",hint:"Hint text"})]),_:1})]),_:1})}const E=m(S,[["render",W]]),I={};function Y(h,n){return p(),u(_,null,{default:l(()=>[e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Default",rows:"2",placeholder:"Placeholder Text"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Solo",placeholder:"Placeholder Text",rows:"2",variant:"solo"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Filled",rows:"2",placeholder:"Placeholder Text",variant:"filled"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Outlined",rows:"2",placeholder:"Placeholder Text",variant:"outlined"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Underlined",rows:"2",placeholder:"Placeholder Text",variant:"underlined"})]),_:1}),e(r,{cols:"12",sm:"6"},{default:l(()=>[e(s,{label:"Plain",rows:"2",placeholder:"Placeholder Text",variant:"plain"})]),_:1})]),_:1})}const q=m(I,[["render",Y]]),J={__name:"DemoTextareaAutoGrow",setup(h){const n=x("The Woodman set to work at once, and so sharp was his axe that the tree was soon chopped nearly through.");return(t,d)=>(p(),u(s,{modelValue:i(n),"onUpdate:modelValue":d[0]||(d[0]=c=>V(n)?n.value=c:null),label:"Auto Grow",placeholder:"Placeholder Text","auto-grow":""},null,8,["modelValue"]))}},K={};function L(h,n){return p(),u(s,{label:"Default",placeholder:"Placeholder Text"})}const Q=m(K,[["render",L]]),X={ts:`<script setup lang="ts">
const textareaValue = ref('The Woodman set to work at once, and so sharp was his axe that the tree was soon chopped nearly through.')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    label="Auto Grow"
    placeholder="Placeholder Text"
    auto-grow
  />
</template>
`,js:`<script setup>
const textareaValue = ref('The Woodman set to work at once, and so sharp was his axe that the tree was soon chopped nearly through.')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    label="Auto Grow"
    placeholder="Placeholder Text"
    auto-grow
  />
</template>
`},Z={ts:`<template>
  <VTextarea
    label="Default"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<template>
  <VTextarea
    label="Default"
    placeholder="Placeholder Text"
  />
</template>
`},ee={ts:`<template>
  <VTextarea
    autocomplete="email"
    label="Email"
    placeholder="johndoe@email.com"
  />
</template>
`,js:`<template>
  <VTextarea
    autocomplete="email"
    label="Email"
    placeholder="johndoe@email.com"
  />
</template>
`},le={ts:`<script setup lang="ts">
const textareaValue = ref('This is clearable text.')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    clearable
    clear-icon="ri-close-line"
    label="Text"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<script setup>
const textareaValue = ref('This is clearable text.')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    clearable
    clear-icon="ri-close-line"
    label="Text"
    placeholder="Placeholder Text"
  />
</template>
`},oe={ts:`<script lang="ts" setup>
const textareaValue = ref('Hello!')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    counter
    label="Text"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<script setup>
const textareaValue = ref('Hello!')
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    counter
    label="Text"
    placeholder="Placeholder Text"
  />
</template>
`},ae={ts:`<template>
  <VRow>
    <VCol cols="12">
      <VTextarea
        label="Prepend-icon"
        rows="1"
        placeholder="Placeholder Text"
        prepend-icon="ri-chat-2-line"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        append-icon="ri-chat-2-line"
        placeholder="Placeholder Text"
        label="Append-icon"
        rows="1"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        prepend-inner-icon="ri-chat-2-line"
        label="Prepend-inner-icon"
        placeholder="Placeholder Text"
        rows="1"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        append-inner-icon="ri-chat-2-line"
        label="Append-inner-icon"
        placeholder="Placeholder Text"
        rows="1"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol cols="12">
      <VTextarea
        label="Prepend-icon"
        rows="1"
        placeholder="Placeholder Text"
        prepend-icon="ri-chat-2-line"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        append-icon="ri-chat-2-line"
        placeholder="Placeholder Text"
        label="Append-icon"
        rows="1"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        prepend-inner-icon="ri-chat-2-line"
        label="Prepend-inner-icon"
        placeholder="Placeholder Text"
        rows="1"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        append-inner-icon="ri-chat-2-line"
        label="Append-inner-icon"
        placeholder="Placeholder Text"
        rows="1"
      />
    </VCol>
  </VRow>
</template>
`},te={ts:`<script lang="ts" setup>
const value = ref('Marshmallow tiramisu pie dessert gingerbread tart caramels marzipan oat cake. Muffin sesame snaps cupcake bonbon cookie tiramisu. Pudding biscuit gingerbread halvah lollipop jelly-o cookie.')
<\/script>

<template>
  <VTextarea
    v-model="value"
    label="Text"
    no-resize
    rows="2"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<script setup>
const value = ref('Marshmallow tiramisu pie dessert gingerbread tart caramels marzipan oat cake. Muffin sesame snaps cupcake bonbon cookie tiramisu. Pudding biscuit gingerbread halvah lollipop jelly-o cookie.')
<\/script>

<template>
  <VTextarea
    v-model="value"
    label="Text"
    no-resize
    rows="2"
    placeholder="Placeholder Text"
  />
</template>
`},re={ts:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="One row"
        auto-grow
        rows="1"
        row-height="15"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        auto-grow
        label="Two rows"
        rows="2"
        placeholder="Placeholder Text"
        row-height="20"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Three rows"
        auto-grow
        rows="3"
        placeholder="Placeholder Text"
        row-height="25"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        auto-grow
        label="Four rows"
        placeholder="Placeholder Text"
        rows="4"
        row-height="30"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="One row"
        auto-grow
        rows="1"
        row-height="15"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        auto-grow
        label="Two rows"
        rows="2"
        placeholder="Placeholder Text"
        row-height="20"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Three rows"
        auto-grow
        rows="3"
        placeholder="Placeholder Text"
        row-height="25"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        auto-grow
        label="Four rows"
        placeholder="Placeholder Text"
        rows="4"
        row-height="30"
      />
    </VCol>
  </VRow>
</template>
`},ne={ts:`<template>
  <VRow>
    <VCol cols="12">
      <VTextarea
        disabled
        label="Disabled"
        hint="Hint text"
        placeholder="Placeholder Text"
        rows="2"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        readonly
        rows="2"
        label="Readonly"
        placeholder="Placeholder Text"
        hint="Hint text"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol cols="12">
      <VTextarea
        disabled
        label="Disabled"
        hint="Hint text"
        placeholder="Placeholder Text"
        rows="2"
      />
    </VCol>

    <VCol cols="12">
      <VTextarea
        readonly
        rows="2"
        label="Readonly"
        placeholder="Placeholder Text"
        hint="Hint text"
      />
    </VCol>
  </VRow>
</template>
`},se={ts:`<script lang="ts" setup>
const textareaValue = ref('Hello!')
const rules = [(v: string) => v.length <= 25 || 'Max 25 characters']
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    label="Validation"
    :rules="rules"
    rows="2"
    placeholder="Placeholder Text"
  />
</template>
`,js:`<script setup>
const textareaValue = ref('Hello!')
const rules = [v => v.length <= 25 || 'Max 25 characters']
<\/script>

<template>
  <VTextarea
    v-model="textareaValue"
    label="Validation"
    :rules="rules"
    rows="2"
    placeholder="Placeholder Text"
  />
</template>
`},ce={ts:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Default"
        rows="2"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Solo"
        placeholder="Placeholder Text"
        rows="2"
        variant="solo"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Filled"
        rows="2"
        placeholder="Placeholder Text"
        variant="filled"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Outlined"
        rows="2"
        placeholder="Placeholder Text"
        variant="outlined"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Underlined"
        rows="2"
        placeholder="Placeholder Text"
        variant="underlined"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Plain"
        rows="2"
        placeholder="Placeholder Text"
        variant="plain"
      />
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Default"
        rows="2"
        placeholder="Placeholder Text"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Solo"
        placeholder="Placeholder Text"
        rows="2"
        variant="solo"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Filled"
        rows="2"
        placeholder="Placeholder Text"
        variant="filled"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Outlined"
        rows="2"
        placeholder="Placeholder Text"
        variant="outlined"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Underlined"
        rows="2"
        placeholder="Placeholder Text"
        variant="underlined"
      />
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VTextarea
        label="Plain"
        rows="2"
        placeholder="Placeholder Text"
        variant="plain"
      />
    </VCol>
  </VRow>
</template>
`},De={__name:"textarea",setup(h){return(n,t)=>{const d=Q,c=y,T=J,w=q,f=E,b=O,P=B,C=G,g=M,v=A,R=j,D=k;return p(),u(_,{class:"match-height"},{default:l(()=>[e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Basic",code:i(Z)},{default:l(()=>[t[0]||(t[0]=o("p",null," v-textarea in its simplest form is a multi-line text-field, useful for larger amounts of text. ",-1)),e(d)]),_:1,__:[0]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Auto Grow",code:i(X)},{default:l(()=>[t[1]||(t[1]=o("p",null,[a("When using the "),o("code",null,"auto-grow"),a(" prop, textarea's will automatically increase in size when the contained text exceeds its size.")],-1)),e(T)]),_:1,__:[1]},8,["code"])]),_:1}),e(r,{cols:"12"},{default:l(()=>[e(c,{title:"Variant",code:i(ce)},{default:l(()=>[t[2]||(t[2]=o("p",null,[a("Use "),o("code",null,"filled"),a(", "),o("code",null,"plain"),a(", "),o("code",null,"outlined"),a(", "),o("code",null,"solo"),a(" and "),o("code",null,"underlined"),a(" option of "),o("code",null,"variant"),a(" prop to change the look of file input.")],-1)),e(w)]),_:1,__:[2]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"States",code:i(ne)},{default:l(()=>[t[3]||(t[3]=o("p",null,[a("Use "),o("code",null,"disabled"),a(" and "),o("code",null,"readonly"),a(" prop to change the state of textarea.")],-1)),e(f)]),_:1,__:[3]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Browser autocomplete",code:i(ee)},{default:l(()=>[t[4]||(t[4]=o("p",null,[a(" The "),o("code",null,"autocomplete"),a(" prop gives you the option to enable the browser to predict user input. ")],-1)),e(b)]),_:1,__:[4]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Clearable",code:i(le)},{default:l(()=>[t[5]||(t[5]=o("p",null,[a("You can clear the text from a "),o("code",null,"v-textarea"),a(" by using the "),o("code",null,"clearable"),a(" prop, and customize the icon used with the "),o("code",null,"clearable-icon"),a(" prop.")],-1)),e(P)]),_:1,__:[5]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Counter",code:i(oe)},{default:l(()=>[t[6]||(t[6]=o("p",null,[a(" The "),o("code",null,"counter"),a(" prop informs the user of a character limit for the "),o("code",null,"v-textarea"),a(". ")],-1)),e(C)]),_:1,__:[6]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Icons",code:i(ae)},{default:l(()=>[t[7]||(t[7]=o("p",null,[a("The "),o("code",null,"append-icon"),a(", "),o("code",null,"prepend-icon"),a(", "),o("code",null,"append-inner-icon"),a(" and "),o("code",null,"prepend-inner-icon"),a(" props help add context to v-textarea.")],-1)),e(g)]),_:1,__:[7]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Rows",code:i(re)},{default:l(()=>[t[8]||(t[8]=o("p",null,[a("The "),o("code",null,"rows"),a(" prop allows you to define how many rows the textarea has, when combined with the "),o("code",null,"row-height"),a(" prop you can further customize your rows by defining their height.")],-1)),e(v)]),_:1,__:[8]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"No resize",code:i(te)},{default:l(()=>[t[9]||(t[9]=o("p",null,[o("code",null,"v-textarea"),a("'s have the option to remain the same size regardless of their content's size, using the "),o("code",null,"no-resize"),a(" prop.")],-1)),e(R)]),_:1,__:[9]},8,["code"])]),_:1}),e(r,{cols:"12",md:"6"},{default:l(()=>[e(c,{title:"Validation",code:i(se)},{default:l(()=>[t[10]||(t[10]=o("p",null,[a("Use "),o("code",null,"rules"),a(" prop to validate the textarea.")],-1)),e(D)]),_:1,__:[10]},8,["code"])]),_:1})]),_:1})}}};export{De as default};
