import{a as x,V as v}from"./VRow-Iyy2LECc.js";import{V as b}from"./VCheckbox-ChoGBbws.js";import{V as g}from"./VTextField-CYlm0PWQ.js";import{r as p,o as h,c as f,b as l,e as i,aG as u,l as c,F as C,f as V,d as n,s as d,p as S,aN as O,t as I,i as U}from"./main-9VHNyDsO.js";import{V as z}from"./VTooltip-BSA0pDeS.js";import{_ as D}from"./AppCardCode-4m9iiMTg.js";/* empty css              */import"./VCheckboxBtn--WsElWdy.js";import"./VSelectionControl-BNhHD4v2.js";import"./form-GqIJ9qUn.js";import"./VInput-DsRpG4Kk.js";/* empty css                   */import"./VCounter-Dk7sH6p6.js";import"./VField-f91T7v1X.js";import"./VImg-C5N84Ab5.js";import"./vue3-perfect-scrollbar-BVWkj8dc.js";import"./VCard-K71c2zrh.js";import"./createSimpleFunctional-CSfWJ7IQ.js";import"./VAvatar-d_aoF7qa.js";import"./VCardText-CFuY7g1K.js";import"./VDivider-Co2ASRmY.js";const $={__name:"DemoCheckboxInlineTextField",setup(k){const t=p(!0),e=p(!1);return(a,o)=>(h(),f(C,null,[l(v,null,{default:i(()=>[l(x,{sm:"1",cols:"2"},{default:i(()=>[l(b,{modelValue:c(t),"onUpdate:modelValue":o[0]||(o[0]=s=>u(t)?t.value=s:null)},null,8,["modelValue"])]),_:1}),l(x,{sm:"11",cols:"10"},{default:i(()=>[l(g,{label:"Include files",placeholder:"Placeholder Text"})]),_:1})]),_:1}),l(v,null,{default:i(()=>[l(x,{cols:"2",sm:"1"},{default:i(()=>[l(b,{modelValue:c(e),"onUpdate:modelValue":o[1]||(o[1]=s=>u(e)?e.value=s:null)},null,8,["modelValue"])]),_:1}),l(x,{cols:"10",sm:"11"},{default:i(()=>[l(g,{disabled:!c(e),label:"I only work if you check the box",placeholder:"Placeholder Text"},null,8,["disabled"])]),_:1})]),_:1})],64))}},J={__name:"DemoCheckboxLabelSlot",setup(k){const t=p(!1);return(e,a)=>(h(),V(b,{modelValue:c(t),"onUpdate:modelValue":a[1]||(a[1]=o=>u(t)?t.value=o:null)},{label:i(()=>[n("div",null,[a[3]||(a[3]=d(" I agree that ")),l(z,{location:"bottom"},{activator:i(({props:o})=>[n("a",S({href:"https://vuetifyjs.com/",target:"_blank",rel:"noopener noreferrer"},o,{onClick:a[0]||(a[0]=O(()=>{},["stop"]))})," Vuetify ",16)]),default:i(()=>[a[2]||(a[2]=d(" Opens in new window "))]),_:1,__:[2]}),a[4]||(a[4]=d(" is awesome "))])]),_:1},8,["modelValue"]))}},A={class:"demo-space-x"},F={__name:"DemoCheckboxStates",setup(k){const t=p(!0),e=p(!0),a=p(!0),o=p(!1);return(s,r)=>(h(),f("div",A,[l(b,{modelValue:c(t),"onUpdate:modelValue":r[0]||(r[0]=m=>u(t)?t.value=m:null),label:"On"},null,8,["modelValue"]),l(b,{modelValue:c(o),"onUpdate:modelValue":r[1]||(r[1]=m=>u(o)?o.value=m:null),label:"Off"},null,8,["modelValue"]),l(b,{indeterminate:c(e),"onUpdate:indeterminate":r[2]||(r[2]=m=>u(e)?e.value=m:null),modelValue:c(e),"onUpdate:modelValue":r[3]||(r[3]=m=>u(e)?e.value=m:null),label:"Indeterminate"},null,8,["indeterminate","modelValue"]),l(b,{"model-value":c(a),disabled:"",label:"On disabled"},null,8,["model-value"]),l(b,{disabled:"",label:"Off disabled"})]))}},j={class:"demo-space-x"},E={__name:"DemoCheckboxCheckboxValue",setup(k){const t=p(1),e=p("Show");return(a,o)=>(h(),f("div",j,[l(b,{modelValue:c(t),"onUpdate:modelValue":o[0]||(o[0]=s=>u(t)?t.value=s:null),"true-value":1,"false-value":0,label:`${c(t).toString()}`},null,8,["modelValue","label"]),l(b,{modelValue:c(e),"onUpdate:modelValue":o[1]||(o[1]=s=>u(e)?e.value=s:null),"true-value":"Show","false-value":"Hide",color:"success",label:`${c(e).toString()}`},null,8,["modelValue","label"])]))}},P={class:"demo-space-x"},R={__name:"DemoCheckboxIcon",setup(k){const t=p(!0),e=p(!0),a=p(!0),o=s=>{const r=s.toString();return r.charAt(0).toUpperCase()+r.slice(1)};return(s,r)=>(h(),f("div",P,[l(b,{modelValue:c(t),"onUpdate:modelValue":r[0]||(r[0]=m=>u(t)?t.value=m:null),label:o(c(t)),"true-icon":"ri-check-line","false-icon":"ri-close-line"},null,8,["modelValue","label"]),l(b,{modelValue:c(e),"onUpdate:modelValue":r[1]||(r[1]=m=>u(e)?e.value=m:null),label:o(c(e)),"true-icon":"ri-alarm-line","false-icon":"ri-alarm-line",color:"success"},null,8,["modelValue","label"]),l(b,{modelValue:c(a),"onUpdate:modelValue":r[2]||(r[2]=m=>u(a)?a.value=m:null),label:o(c(a)),"true-icon":"ri-checkbox-circle-fill","false-icon":"ri-close-circle-fill",color:"error"},null,8,["modelValue","label"])]))}},B={class:"demo-space-x"},M={class:"mt-1"},H={__name:"DemoCheckboxModelAsArray",setup(k){const t=p(["John"]);return(e,a)=>(h(),f(C,null,[n("div",B,[l(b,{modelValue:c(t),"onUpdate:modelValue":a[0]||(a[0]=o=>u(t)?t.value=o:null),label:"John",value:"John"},null,8,["modelValue"]),l(b,{modelValue:c(t),"onUpdate:modelValue":a[1]||(a[1]=o=>u(t)?t.value=o:null),label:"Jacob",color:"success",value:"Jacob"},null,8,["modelValue"]),l(b,{modelValue:c(t),"onUpdate:modelValue":a[2]||(a[2]=o=>u(t)?t.value=o:null),label:"Johnson",color:"info",value:"Johnson"},null,8,["modelValue"])]),n("p",M,I(c(t)),1)],64))}},N={class:"demo-space-x"},W={__name:"DemoCheckboxColors",setup(k){const t=p(["Primary","Secondary","Success","Info","Warning","Error"]),e=p([]);return(a,o)=>(h(),f("div",N,[(h(!0),f(C,null,U(c(t),s=>(h(),V(b,{key:s,modelValue:c(e),"onUpdate:modelValue":o[0]||(o[0]=r=>u(e)?e.value=r:null),label:s,color:s.toLowerCase(),value:s},null,8,["modelValue","label","color","value"]))),128))]))}},G={class:"demo-space-x"},Y={__name:"DemoCheckboxDensity",setup(k){const t=p(!0),e=p(!1),a=o=>{const s=o.toString();return s.charAt(0).toUpperCase()+s.slice(1)};return(o,s)=>(h(),f("div",G,[l(b,{modelValue:c(t),"onUpdate:modelValue":s[0]||(s[0]=r=>u(t)?t.value=r:null),density:"compact",label:a(c(t))},null,8,["modelValue","label"]),l(b,{modelValue:c(e),"onUpdate:modelValue":s[1]||(s[1]=r=>u(e)?e.value=r:null),density:"compact",label:a(c(e))},null,8,["modelValue","label"])]))}},q={class:"demo-space-x"},K={__name:"DemoCheckboxBasic",setup(k){const t=p(!0),e=p(!1),a=o=>{const s=o.toString();return s.charAt(0).toUpperCase()+s.slice(1)};return(o,s)=>(h(),f("div",q,[l(b,{modelValue:c(t),"onUpdate:modelValue":s[0]||(s[0]=r=>u(t)?t.value=r:null),label:a(c(t))},null,8,["modelValue","label"]),l(b,{modelValue:c(e),"onUpdate:modelValue":s[1]||(s[1]=r=>u(e)?e.value=r:null),label:a(c(e))},null,8,["modelValue","label"])]))}},Q={ts:`<script lang="ts" setup>
const checkboxOne = ref(true)
const checkboxTwo = ref(false)

const capitalizedLabel = (label: boolean) => {
  const convertLabelText = label.toString()

  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkboxOne"
      :label="capitalizedLabel(checkboxOne)"
    />

    <VCheckbox
      v-model="checkboxTwo"
      :label="capitalizedLabel(checkboxTwo)"
    />
  </div>
</template>
`,js:`<script setup>
const checkboxOne = ref(true)
const checkboxTwo = ref(false)

const capitalizedLabel = label => {
  const convertLabelText = label.toString()
  
  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkboxOne"
      :label="capitalizedLabel(checkboxOne)"
    />

    <VCheckbox
      v-model="checkboxTwo"
      :label="capitalizedLabel(checkboxTwo)"
    />
  </div>
</template>
`},X={ts:`<script lang="ts" setup>
const checkbox = ref(1)
const checkboxString = ref('Show')
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkbox"
      :true-value="1"
      :false-value="0"
      :label="\`\${checkbox.toString()}\`"
    />

    <VCheckbox
      v-model="checkboxString"
      true-value="Show"
      false-value="Hide"
      color="success"
      :label="\`\${checkboxString.toString()}\`"
    />
  </div>
</template>
`,js:`<script setup>
const checkbox = ref(1)
const checkboxString = ref('Show')
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkbox"
      :true-value="1"
      :false-value="0"
      :label="\`\${checkbox.toString()}\`"
    />

    <VCheckbox
      v-model="checkboxString"
      true-value="Show"
      false-value="Hide"
      color="success"
      :label="\`\${checkboxString.toString()}\`"
    />
  </div>
</template>
`},Z={ts:`<script lang="ts" setup>
const colorCheckbox = ref(['Primary', 'Secondary', 'Success', 'Info', 'Warning', 'Error'])
const selectedCheckbox = ref([])
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-for="color in colorCheckbox"
      :key="color"
      v-model="selectedCheckbox"
      :label="color"
      :color="color.toLowerCase()"
      :value="color"
    />
  </div>
</template>
`,js:`<script setup>
const colorCheckbox = ref([
  'Primary',
  'Secondary',
  'Success',
  'Info',
  'Warning',
  'Error',
])

const selectedCheckbox = ref([])
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-for="color in colorCheckbox"
      :key="color"
      v-model="selectedCheckbox"
      :label="color"
      :color="color.toLowerCase()"
      :value="color"
    />
  </div>
</template>
`},ee={ts:`<script lang="ts" setup>
const checkboxOne = ref(true)
const checkboxTwo = ref(false)

const capitalizedLabel = (label: boolean) => {
  const convertLabelText = label.toString()

  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkboxOne"
      density="compact"
      :label="capitalizedLabel(checkboxOne)"
    />

    <VCheckbox
      v-model="checkboxTwo"
      density="compact"
      :label="capitalizedLabel(checkboxTwo)"
    />
  </div>
</template>
`,js:`<script setup>
const checkboxOne = ref(true)
const checkboxTwo = ref(false)

const capitalizedLabel = label => {
  const convertLabelText = label.toString()
  
  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="checkboxOne"
      density="compact"
      :label="capitalizedLabel(checkboxOne)"
    />

    <VCheckbox
      v-model="checkboxTwo"
      density="compact"
      :label="capitalizedLabel(checkboxTwo)"
    />
  </div>
</template>
`},le={ts:`<script lang="ts" setup>
const toggleCheckboxOne = ref(true)
const toggleCheckboxTwo = ref(true)
const toggleCheckboxThree = ref(true)

const capitalizedLabel = (label: boolean) => {
  const convertLabelText = label.toString()

  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="toggleCheckboxOne"
      :label="capitalizedLabel(toggleCheckboxOne)"
      true-icon="ri-check-line"
      false-icon="ri-close-line"
    />

    <VCheckbox
      v-model="toggleCheckboxTwo"
      :label="capitalizedLabel(toggleCheckboxTwo)"
      true-icon="ri-alarm-line"
      false-icon="ri-alarm-line"
      color="success"
    />

    <VCheckbox
      v-model="toggleCheckboxThree"
      :label="capitalizedLabel(toggleCheckboxThree)"
      true-icon="ri-checkbox-circle-fill"
      false-icon="ri-close-circle-fill"
      color="error"
    />
  </div>
</template>
`,js:`<script setup>
const toggleCheckboxOne = ref(true)
const toggleCheckboxTwo = ref(true)
const toggleCheckboxThree = ref(true)

const capitalizedLabel = label => {
  const convertLabelText = label.toString()
  
  return convertLabelText.charAt(0).toUpperCase() + convertLabelText.slice(1)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="toggleCheckboxOne"
      :label="capitalizedLabel(toggleCheckboxOne)"
      true-icon="ri-check-line"
      false-icon="ri-close-line"
    />

    <VCheckbox
      v-model="toggleCheckboxTwo"
      :label="capitalizedLabel(toggleCheckboxTwo)"
      true-icon="ri-alarm-line"
      false-icon="ri-alarm-line"
      color="success"
    />

    <VCheckbox
      v-model="toggleCheckboxThree"
      :label="capitalizedLabel(toggleCheckboxThree)"
      true-icon="ri-checkbox-circle-fill"
      false-icon="ri-close-circle-fill"
      color="error"
    />
  </div>
</template>
`},oe={ts:`<script lang="ts" setup>
const includeFiles = ref(true)
const isInputEnabled = ref(false)
<\/script>

<template>
  <VRow>
    <VCol
      sm="1"
      cols="2"
    >
      <VCheckbox v-model="includeFiles" />
    </VCol>

    <VCol
      sm="11"
      cols="10"
    >
      <VTextField
        label="Include files"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>

  <VRow>
    <VCol
      cols="2"
      sm="1"
    >
      <VCheckbox v-model="isInputEnabled" />
    </VCol>

    <VCol
      cols="10"
      sm="11"
    >
      <VTextField
        :disabled="!isInputEnabled"
        label="I only work if you check the box"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`,js:`<script setup>
const includeFiles = ref(true)
const isInputEnabled = ref(false)
<\/script>

<template>
  <VRow>
    <VCol
      sm="1"
      cols="2"
    >
      <VCheckbox v-model="includeFiles" />
    </VCol>

    <VCol
      sm="11"
      cols="10"
    >
      <VTextField
        label="Include files"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>

  <VRow>
    <VCol
      cols="2"
      sm="1"
    >
      <VCheckbox v-model="isInputEnabled" />
    </VCol>

    <VCol
      cols="10"
      sm="11"
    >
      <VTextField
        :disabled="!isInputEnabled"
        label="I only work if you check the box"
        placeholder="Placeholder Text"
      />
    </VCol>
  </VRow>
</template>
`},te={ts:`<script lang="ts" setup>
const checkbox = ref(false)
<\/script>

<template>
  <VCheckbox v-model="checkbox">
    <template #label>
      <div>
        I agree that
        <VTooltip location="bottom">
          <template #activator="{ props }">
            <a
              href="https://vuetifyjs.com/"
              target="_blank"
              rel="noopener noreferrer"
              v-bind="props"
              @click.stop
            >
              Vuetify
            </a>
          </template>
          Opens in new window
        </VTooltip>
        is awesome
      </div>
    </template>
  </VCheckbox>
</template>
`,js:`<script setup>
const checkbox = ref(false)
<\/script>

<template>
  <VCheckbox v-model="checkbox">
    <template #label>
      <div>
        I agree that
        <VTooltip location="bottom">
          <template #activator="{ props }">
            <a
              href="https://vuetifyjs.com/"
              target="_blank"
              rel="noopener noreferrer"
              v-bind="props"
              @click.stop
            >
              Vuetify
            </a>
          </template>
          Opens in new window
        </VTooltip>
        is awesome
      </div>
    </template>
  </VCheckbox>
</template>
`},ce={ts:`<script lang="ts" setup>
const selected = ref(['John'])
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="selected"
      label="John"
      value="John"
    />

    <VCheckbox
      v-model="selected"
      label="Jacob"
      color="success"
      value="Jacob"
    />

    <VCheckbox
      v-model="selected"
      label="Johnson"
      color="info"
      value="Johnson"
    />
  </div>

  <p class="mt-1">
    {{ selected }}
  </p>
</template>
`,js:`<script setup>
const selected = ref(['John'])
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="selected"
      label="John"
      value="John"
    />

    <VCheckbox
      v-model="selected"
      label="Jacob"
      color="success"
      value="Jacob"
    />

    <VCheckbox
      v-model="selected"
      label="Johnson"
      color="info"
      value="Johnson"
    />
  </div>

  <p class="mt-1">
    {{ selected }}
  </p>
</template>
`},ae={ts:`<script setup lang="ts">
const toggleCheckbox = ref(true)
const toggleIndeterminateCheckbox = ref(true)
const disabledCheckbox = ref(true)
const toggleOffCheckbox = ref(false)
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="toggleCheckbox"
      label="On"
    />

    <VCheckbox
      v-model="toggleOffCheckbox"
      label="Off"
    />

    <VCheckbox
      v-model:indeterminate="toggleIndeterminateCheckbox"
      v-model="toggleIndeterminateCheckbox"
      label="Indeterminate"
    />

    <VCheckbox
      :model-value="disabledCheckbox"
      disabled
      label="On disabled"
    />

    <VCheckbox
      disabled
      label="Off disabled"
    />
  </div>
</template>
`,js:`<script setup>
const toggleCheckbox = ref(true)
const toggleIndeterminateCheckbox = ref(true)
const disabledCheckbox = ref(true)
const toggleOffCheckbox = ref(false)
<\/script>

<template>
  <div class="demo-space-x">
    <VCheckbox
      v-model="toggleCheckbox"
      label="On"
    />

    <VCheckbox
      v-model="toggleOffCheckbox"
      label="Off"
    />

    <VCheckbox
      v-model:indeterminate="toggleIndeterminateCheckbox"
      v-model="toggleIndeterminateCheckbox"
      label="Indeterminate"
    />

    <VCheckbox
      :model-value="disabledCheckbox"
      disabled
      label="On disabled"
    />

    <VCheckbox
      disabled
      label="Off disabled"
    />
  </div>
</template>
`},ye={__name:"checkbox",setup(k){return(t,e)=>{const a=K,o=D,s=Y,r=W,m=H,_=R,T=E,w=F,L=J,y=$;return h(),V(v,{class:"match-height"},{default:i(()=>[l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Basic",code:c(Q)},{default:i(()=>[e[0]||(e[0]=n("p",null,[n("code",null,"v-checkbox"),d(" in its simplest form provides a toggle between 2 values.")],-1)),l(a)]),_:1,__:[0]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Density",code:c(ee)},{default:i(()=>[e[1]||(e[1]=n("p",null,[d("Use "),n("code",null,"density"),d(" prop to reduces the input height. Available options are: "),n("code",null,"default"),d(", "),n("code",null,"comfortable"),d(", and "),n("code",null,"compact"),d(".")],-1)),l(s)]),_:1,__:[1]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Colors",code:c(Z)},{default:i(()=>[e[2]||(e[2]=n("p",null,[d("Checkboxes can be colored by using any of the builtin colors and contextual names using the "),n("code",null,"color"),d(" prop.")],-1)),l(r)]),_:1,__:[2]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Model as array",code:c(ce)},{default:i(()=>[e[3]||(e[3]=n("p",null,[d("Multiple "),n("code",null,"v-checkbox"),d("'s can share the same "),n("code",null,"v-model"),d(" by using an array.")],-1)),l(m)]),_:1,__:[3]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Icon",code:c(le)},{default:i(()=>[e[4]||(e[4]=n("p",null,[d("Use "),n("code",null,"false-icon"),d(" and "),n("code",null,"true-icon"),d(" prop to change the icon on the checkbox.")],-1)),l(_)]),_:1,__:[4]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Checkbox Value",code:c(X)},{default:i(()=>[e[5]||(e[5]=n("p",null,[d("Use "),n("code",null,"false-value"),d(" and "),n("code",null,"true-value"),d(" prop to sets value for truthy and falsy state")],-1)),l(T)]),_:1,__:[5]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"States",code:c(ae)},{default:i(()=>[e[6]||(e[6]=n("p",null,[n("code",null,"v-checkbox"),d(" can have different states such as "),n("code",null,"default"),d(", "),n("code",null,"disabled"),d(", and "),n("code",null,"indeterminate"),d(".")],-1)),l(w)]),_:1,__:[6]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Label Slot",code:c(te)},{default:i(()=>[e[7]||(e[7]=n("p",null,[d("Checkbox labels can be defined in "),n("code",null,"label"),d(" slot - that will allow to use HTML content.")],-1)),l(L)]),_:1,__:[7]},8,["code"])]),_:1}),l(x,{cols:"12",md:"6"},{default:i(()=>[l(o,{title:"Inline text-field",code:c(oe)},{default:i(()=>[e[8]||(e[8]=n("p",null,[d("You can place "),n("code",null,"v-checkbox"),d(" in line with other components such as "),n("code",null,"v-text-field"),d(".")],-1)),l(y)]),_:1,__:[8]},8,["code"])]),_:1})]),_:1})}}};export{ye as default};
