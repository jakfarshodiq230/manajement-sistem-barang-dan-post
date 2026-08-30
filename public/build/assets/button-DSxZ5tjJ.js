import{r as g,o as c,f,e as o,b as n,ai as r,aG as j,l as s,dw as D,c as B,s as e,a0 as m,d as i}from"./main-9VHNyDsO.js";import{_ as p}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{a as d,V as x}from"./VRow-Iyy2LECc.js";import{_ as z}from"./AppCardCode-4m9iiMTg.js";import{V as v}from"./VAlert-C7RQ3Jt9.js";/* empty css              */import"./vue3-perfect-scrollbar-BVWkj8dc.js";import"./VCard-K71c2zrh.js";import"./createSimpleFunctional-CSfWJ7IQ.js";import"./VAvatar-d_aoF7qa.js";import"./VImg-C5N84Ab5.js";import"./VCardText-CFuY7g1K.js";import"./VDivider-Co2ASRmY.js";const N={__name:"DemoButtonGroup",setup(u){const t=g(1);return(l,V)=>(c(),f(D,{modelValue:s(t),"onUpdate:modelValue":V[0]||(V[0]=a=>j(t)?t.value=a:null),density:"compact"},{default:o(()=>[n(r,{icon:"ri-align-left"}),n(r,{icon:"ri-align-center"}),n(r,{icon:"ri-align-right"}),n(r,{icon:"ri-align-justify"})]),_:1},8,["modelValue"]))}},R={},U={class:"demo-space-x"};function O(u,t){return c(),B("div",U,[n(r,{href:"https://pixinvent.com/"},{default:o(()=>t[0]||(t[0]=[e(" String Literal ")])),_:1,__:[0]}),n(r,{href:"https://pixinvent.com/",target:"_blank",rel:"noopener noreferrer"},{default:o(()=>t[1]||(t[1]=[e(" Open New Tab ")])),_:1,__:[1]})])}const A=p(R,[["render",O]]),G={},q={class:"demo-space-x"};function Q(u,t){return c(),B("div",q,[n(r,{to:"/components/alert"},{default:o(()=>t[0]||(t[0]=[e(" String Literal ")])),_:1,__:[0]}),n(r,{color:"warning",to:{path:"/components/alert"}},{default:o(()=>t[1]||(t[1]=[e(" Object Path ")])),_:1,__:[1]}),n(r,{color:"success",to:{name:"components-alert"}},{default:o(()=>t[2]||(t[2]=[e(" Named Router ")])),_:1,__:[2]}),n(r,{color:"secondary",to:{path:"/components/alert",query:{plan:"private"}}},{default:o(()=>t[3]||(t[3]=[e(" With Query ")])),_:1,__:[3]})])}const Y=p(G,[["render",Q]]),F={class:"demo-space-x"},H={class:"custom-loader"},J={__name:"DemoButtonLoaders",setup(u){const t=g([]),l=V=>{t.value[V]=!0,setTimeout(()=>{t.value[V]=!1},3e3)};return(V,a)=>(c(),B("div",F,[n(r,{loading:s(t)[0],disabled:s(t)[0],color:"primary",onClick:a[0]||(a[0]=_=>l(0))},{default:o(()=>a[5]||(a[5]=[e(" Accept Terms ")])),_:1,__:[5]},8,["loading","disabled"]),n(r,{loading:s(t)[1],disabled:s(t)[1],color:"secondary",onClick:a[1]||(a[1]=_=>l(1))},{default:o(()=>[a[6]||(a[6]=e(" Upload ")),n(m,{end:"",icon:"ri-upload-cloud-line"})]),_:1,__:[6]},8,["loading","disabled"]),n(r,{loading:s(t)[2],disabled:s(t)[2],color:"success",onClick:a[2]||(a[2]=_=>l(2))},{loader:o(()=>a[7]||(a[7]=[i("span",null,"Loading...",-1)])),default:o(()=>[a[8]||(a[8]=e(" Loader slot "))]),_:1,__:[8]},8,["loading","disabled"]),n(r,{loading:s(t)[3],disabled:s(t)[3],color:"info",onClick:a[3]||(a[3]=_=>l(3))},{loader:o(()=>[i("span",H,[n(m,{icon:"ri-refresh-line"})])]),default:o(()=>[a[9]||(a[9]=e(" Icon Loader "))]),_:1,__:[9]},8,["loading","disabled"]),n(r,{loading:s(t)[4],disabled:s(t)[4],color:"warning",icon:"ri-upload-cloud-line",onClick:a[4]||(a[4]=_=>l(4))},null,8,["loading","disabled"])]))}},K=p(J,[["__scopeId","data-v-ac3efd74"]]),M={};function X(u,t){return c(),f(x,null,{default:o(()=>[n(d,{cols:"12",sm:"6"},{default:o(()=>[n(r,{block:""},{default:o(()=>t[0]||(t[0]=[e(" Block Button ")])),_:1,__:[0]})]),_:1}),n(d,{cols:"12",sm:"6"},{default:o(()=>[n(r,{variant:"outlined",block:""},{default:o(()=>t[1]||(t[1]=[e(" Block Button ")])),_:1,__:[1]})]),_:1})]),_:1})}const Z=p(M,[["render",X]]),h={},tt={class:"demo-space-x"};function nt(u,t){return c(),B("div",tt,[n(r,{size:"x-large"},{default:o(()=>t[0]||(t[0]=[e(" Extra large Button ")])),_:1,__:[0]}),n(r,{color:"success",size:"large"},{default:o(()=>t[1]||(t[1]=[e(" Large Button ")])),_:1,__:[1]}),n(r,{color:"info"},{default:o(()=>t[2]||(t[2]=[e(" Normal Button ")])),_:1,__:[2]}),n(r,{size:"small",color:"warning"},{default:o(()=>t[3]||(t[3]=[e(" Small Button ")])),_:1,__:[3]}),n(r,{size:"x-small",color:"error"},{default:o(()=>t[4]||(t[4]=[e(" Extra small Button ")])),_:1,__:[4]})])}const ot=p(h,[["render",nt]]),et={},rt={class:"demo-space-x"};function lt(u,t){return c(),B("div",rt,[n(r,{icon:"ri-suitcase-line",variant:"text"}),n(r,{variant:"text",icon:"ri-user-add-line",color:"secondary"}),n(r,{icon:"ri-search-line",variant:"text",color:"success"}),n(r,{icon:"ri-thumb-up-line",variant:"text",color:"info"}),n(r,{icon:"ri-star-line",variant:"text",color:"warning"}),n(r,{icon:"ri-heart-line",variant:"text",color:"error"})])}const at=p(et,[["render",lt]]),it={},st={class:"demo-space-x"};function dt(u,t){return c(),B("div",st,[n(r,null,{default:o(()=>[t[0]||(t[0]=e(" Accept ")),n(m,{end:"",icon:"ri-checkbox-circle-line"})]),_:1,__:[0]}),n(r,{color:"secondary"},{default:o(()=>[n(m,{start:"",icon:"ri-subtract-line"}),t[1]||(t[1]=e("Cancel "))]),_:1,__:[1]}),n(r,{color:"success"},{default:o(()=>[t[2]||(t[2]=e(" Upload ")),n(m,{end:"",icon:"ri-upload-cloud-line"})]),_:1,__:[2]}),n(r,{color:"info"},{default:o(()=>[n(m,{start:"",icon:"ri-arrow-left-line"}),t[3]||(t[3]=e(" Back "))]),_:1,__:[3]}),n(r,{color:"warning"},{default:o(()=>[n(m,{icon:"ri-hammer-line"})]),_:1}),n(r,{color:"error"},{default:o(()=>[n(m,{icon:"ri-prohibited-line"})]),_:1})])}const ct=p(it,[["render",dt]]),ut={},pt={class:"demo-space-x"};function Bt(u,t){return c(),B("div",pt,[n(r,{variant:"tonal"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{color:"secondary",variant:"tonal"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{color:"success",variant:"tonal"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{color:"info",variant:"tonal"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{color:"warning",variant:"tonal"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{color:"error",variant:"tonal"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const Vt=p(ut,[["render",Bt]]),mt={},_t={class:"demo-space-x"};function ft(u,t){return c(),B("div",_t,[n(r,{variant:"plain"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{color:"secondary",variant:"plain"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{color:"success",variant:"plain"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{color:"info",variant:"plain"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{color:"warning",variant:"plain"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{color:"error",variant:"plain"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const vt=p(mt,[["render",ft]]),gt={},xt={class:"demo-space-x"};function yt(u,t){return c(),B("div",xt,[n(r,{variant:"text"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{variant:"text",color:"secondary"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{variant:"text",color:"success"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{variant:"text",color:"info"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{variant:"text",color:"warning"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{variant:"text",color:"error"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const bt=p(gt,[["render",yt]]),wt={},kt={class:"demo-space-x"};function St(u,t){return c(),B("div",kt,[n(r,null,{default:o(()=>t[0]||(t[0]=[e(" Normal Button ")])),_:1,__:[0]}),n(r,{rounded:"",color:"secondary"},{default:o(()=>t[1]||(t[1]=[e(" Rounded Button ")])),_:1,__:[1]}),n(r,{rounded:0,color:"success"},{default:o(()=>t[2]||(t[2]=[e(" Tile Button ")])),_:1,__:[2]}),n(r,{rounded:"pill",color:"info"},{default:o(()=>t[3]||(t[3]=[e(" Pill Button ")])),_:1,__:[3]})])}const $t=p(wt,[["render",St]]),It={},Et={class:"demo-space-x"};function Tt(u,t){return c(),B("div",Et,[n(r,{variant:"flat"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{variant:"flat",color:"secondary"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{variant:"flat",color:"success"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{variant:"flat",color:"info"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{variant:"flat",color:"warning"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{variant:"flat",color:"error"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const Pt=p(It,[["render",Tt]]),Lt={},Ct={class:"demo-space-x"};function Wt(u,t){return c(),B("div",Ct,[n(r,{variant:"outlined"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{variant:"outlined",color:"secondary"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{variant:"outlined",color:"success"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{variant:"outlined",color:"info"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{variant:"outlined",color:"warning"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{variant:"outlined",color:"error"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const jt=p(Lt,[["render",Wt]]),Dt={},zt={class:"demo-space-x"};function Nt(u,t){return c(),B("div",zt,[n(r,{color:"primary"},{default:o(()=>t[0]||(t[0]=[e(" Primary ")])),_:1,__:[0]}),n(r,{color:"secondary"},{default:o(()=>t[1]||(t[1]=[e(" Secondary ")])),_:1,__:[1]}),n(r,{color:"success"},{default:o(()=>t[2]||(t[2]=[e(" Success ")])),_:1,__:[2]}),n(r,{color:"info"},{default:o(()=>t[3]||(t[3]=[e(" Info ")])),_:1,__:[3]}),n(r,{color:"warning"},{default:o(()=>t[4]||(t[4]=[e(" Warning ")])),_:1,__:[4]}),n(r,{color:"error"},{default:o(()=>t[5]||(t[5]=[e(" Error ")])),_:1,__:[5]})])}const Rt=p(Dt,[["render",Nt]]),Ut={ts:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VBtn block>
        Block Button
      </VBtn>
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VBtn
        variant="outlined"
        block
      >
        Block Button
      </VBtn>
    </VCol>
  </VRow>
</template>
`,js:`<template>
  <VRow>
    <VCol
      cols="12"
      sm="6"
    >
      <VBtn block>
        Block Button
      </VBtn>
    </VCol>

    <VCol
      cols="12"
      sm="6"
    >
      <VBtn
        variant="outlined"
        block
      >
        Block Button
      </VBtn>
    </VCol>
  </VRow>
</template>
`},Ot={ts:`<template>
  <div class="demo-space-x">
    <VBtn color="primary">
      Primary
    </VBtn>
    <VBtn color="secondary">
      Secondary
    </VBtn>
    <VBtn color="success">
      Success
    </VBtn>
    <VBtn color="info">
      Info
    </VBtn>
    <VBtn color="warning">
      Warning
    </VBtn>
    <VBtn color="error">
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn color="primary">
      Primary
    </VBtn>
    <VBtn color="secondary">
      Secondary
    </VBtn>
    <VBtn color="success">
      Success
    </VBtn>
    <VBtn color="info">
      Info
    </VBtn>
    <VBtn color="warning">
      Warning
    </VBtn>
    <VBtn color="error">
      Error
    </VBtn>
  </div>
</template>
`},At={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="flat">
      Primary
    </VBtn>

    <VBtn
      variant="flat"
      color="secondary"
    >
      Secondary
    </VBtn>

    <VBtn
      variant="flat"
      color="success"
    >
      Success
    </VBtn>

    <VBtn
      variant="flat"
      color="info"
    >
      Info
    </VBtn>

    <VBtn
      variant="flat"
      color="warning"
    >
      Warning
    </VBtn>

    <VBtn
      variant="flat"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="flat">
      Primary
    </VBtn>

    <VBtn
      variant="flat"
      color="secondary"
    >
      Secondary
    </VBtn>

    <VBtn
      variant="flat"
      color="success"
    >
      Success
    </VBtn>

    <VBtn
      variant="flat"
      color="info"
    >
      Info
    </VBtn>

    <VBtn
      variant="flat"
      color="warning"
    >
      Warning
    </VBtn>

    <VBtn
      variant="flat"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`},Gt={ts:`<script lang="ts" setup>
const toggleExclusive = ref(1)
<\/script>

<template>
  <VBtnToggle
    v-model="toggleExclusive"
    density="compact"
  >
    <VBtn icon="ri-align-left" />
    <VBtn icon="ri-align-center" />
    <VBtn icon="ri-align-right" />
    <VBtn icon="ri-align-justify" />
  </VBtnToggle>
</template>
`,js:`<script setup>
const toggleExclusive = ref(1)
<\/script>

<template>
  <VBtnToggle
    v-model="toggleExclusive"
    density="compact"
  >
    <VBtn icon="ri-align-left" />
    <VBtn icon="ri-align-center" />
    <VBtn icon="ri-align-right" />
    <VBtn icon="ri-align-justify" />
  </VBtnToggle>
</template>
`},qt={ts:`<template>
  <div class="demo-space-x">
    <VBtn>
      Accept
      <VIcon
        end
        icon="ri-checkbox-circle-line"
      />
    </VBtn>

    <VBtn color="secondary">
      <VIcon
        start
        icon="ri-subtract-line"
      />Cancel
    </VBtn>

    <VBtn color="success">
      Upload
      <VIcon
        end
        icon="ri-upload-cloud-line"
      />
    </VBtn>

    <VBtn color="info">
      <VIcon
        start
        icon="ri-arrow-left-line"
      />
      Back
    </VBtn>

    <VBtn color="warning">
      <VIcon icon="ri-hammer-line" />
    </VBtn>

    <VBtn color="error">
      <VIcon icon="ri-prohibited-line" />
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn>
      Accept
      <VIcon
        end
        icon="ri-checkbox-circle-line"
      />
    </VBtn>

    <VBtn color="secondary">
      <VIcon
        start
        icon="ri-subtract-line"
      />Cancel
    </VBtn>

    <VBtn color="success">
      Upload
      <VIcon
        end
        icon="ri-upload-cloud-line"
      />
    </VBtn>

    <VBtn color="info">
      <VIcon
        start
        icon="ri-arrow-left-line"
      />
      Back
    </VBtn>

    <VBtn color="warning">
      <VIcon icon="ri-hammer-line" />
    </VBtn>

    <VBtn color="error">
      <VIcon icon="ri-prohibited-line" />
    </VBtn>
  </div>
</template>
`},Qt={ts:`<template>
  <div class="demo-space-x">
    <VBtn
      icon="ri-suitcase-line"
      variant="text"
    />

    <VBtn
      variant="text"
      icon="ri-user-add-line"
      color="secondary"
    />

    <VBtn
      icon="ri-search-line"
      variant="text"
      color="success"
    />

    <VBtn
      icon="ri-thumb-up-line"
      variant="text"
      color="info"
    />

    <VBtn
      icon="ri-star-line"
      variant="text"
      color="warning"
    />

    <VBtn
      icon="ri-heart-line"
      variant="text"
      color="error"
    />
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn
      icon="ri-suitcase-line"
      variant="text"
    />

    <VBtn
      variant="text"
      icon="ri-user-add-line"
      color="secondary"
    />

    <VBtn
      icon="ri-search-line"
      variant="text"
      color="success"
    />

    <VBtn
      icon="ri-thumb-up-line"
      variant="text"
      color="info"
    />

    <VBtn
      icon="ri-star-line"
      variant="text"
      color="warning"
    />

    <VBtn
      icon="ri-heart-line"
      variant="text"
      color="error"
    />
  </div>
</template>
`},Yt={ts:`<template>
  <div class="demo-space-x">
    <VBtn href="https://pixinvent.com/">
      String Literal
    </VBtn>

    <VBtn
      href="https://pixinvent.com/"
      target="_blank"
      rel="noopener noreferrer"
    >
      Open New Tab
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn href="https://pixinvent.com/">
      String Literal
    </VBtn>

    <VBtn
      href="https://pixinvent.com/"
      target="_blank"
      rel="noopener noreferrer"
    >
      Open New Tab
    </VBtn>
  </div>
</template>
`},Ft={ts:`<script lang="ts" setup>
const loadings = ref<boolean[]>([])

const load = (i: number) => {
  loadings.value[i] = true
  setTimeout(() => {
    loadings.value[i] = false
  }, 3000)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn
      :loading="loadings[0]"
      :disabled="loadings[0]"
      color="primary"
      @click="load(0)"
    >
      Accept Terms
    </VBtn>

    <VBtn
      :loading="loadings[1]"
      :disabled="loadings[1]"
      color="secondary"
      @click="load(1)"
    >
      Upload
      <VIcon
        end
        icon="ri-upload-cloud-line"
      />
    </VBtn>

    <VBtn
      :loading="loadings[2]"
      :disabled="loadings[2]"
      color="success"
      @click="load(2)"
    >
      Loader slot
      <template #loader>
        <span>Loading...</span>
      </template>
    </VBtn>

    <VBtn
      :loading="loadings[3]"
      :disabled="loadings[3]"
      color="info"
      @click="load(3)"
    >
      Icon Loader
      <template #loader>
        <span class="custom-loader">
          <VIcon icon="ri-refresh-line" />
        </span>
      </template>
    </VBtn>

    <VBtn
      :loading="loadings[4]"
      :disabled="loadings[4]"
      color="warning"
      icon="ri-upload-cloud-line"
      @click="load(4)"
    />
  </div>
</template>

  <style lang="scss" scoped>
  .custom-loader {
    display: flex;
    animation: loader 1s infinite;
  }

  @keyframes loader {
    from {
      transform: rotate(0);
    }

    to {
      transform: rotate(360deg);
    }
  }
  </style>
`,js:`<script setup>
const loadings = ref([])

const load = i => {
  loadings.value[i] = true
  setTimeout(() => {
    loadings.value[i] = false
  }, 3000)
}
<\/script>

<template>
  <div class="demo-space-x">
    <VBtn
      :loading="loadings[0]"
      :disabled="loadings[0]"
      color="primary"
      @click="load(0)"
    >
      Accept Terms
    </VBtn>

    <VBtn
      :loading="loadings[1]"
      :disabled="loadings[1]"
      color="secondary"
      @click="load(1)"
    >
      Upload
      <VIcon
        end
        icon="ri-upload-cloud-line"
      />
    </VBtn>

    <VBtn
      :loading="loadings[2]"
      :disabled="loadings[2]"
      color="success"
      @click="load(2)"
    >
      Loader slot
      <template #loader>
        <span>Loading...</span>
      </template>
    </VBtn>

    <VBtn
      :loading="loadings[3]"
      :disabled="loadings[3]"
      color="info"
      @click="load(3)"
    >
      Icon Loader
      <template #loader>
        <span class="custom-loader">
          <VIcon icon="ri-refresh-line" />
        </span>
      </template>
    </VBtn>

    <VBtn
      :loading="loadings[4]"
      :disabled="loadings[4]"
      color="warning"
      icon="ri-upload-cloud-line"
      @click="load(4)"
    />
  </div>
</template>

  <style lang="scss" scoped>
  .custom-loader {
    display: flex;
    animation: loader 1s infinite;
  }

  @keyframes loader {
    from {
      transform: rotate(0);
    }

    to {
      transform: rotate(360deg);
    }
  }
  </style>
`},Ht={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="outlined">
      Primary
    </VBtn>
    <VBtn
      variant="outlined"
      color="secondary"
    >
      Secondary
    </VBtn>
    <VBtn
      variant="outlined"
      color="success"
    >
      Success
    </VBtn>
    <VBtn
      variant="outlined"
      color="info"
    >
      Info
    </VBtn>
    <VBtn
      variant="outlined"
      color="warning"
    >
      Warning
    </VBtn>
    <VBtn
      variant="outlined"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="outlined">
      Primary
    </VBtn>
    <VBtn
      variant="outlined"
      color="secondary"
    >
      Secondary
    </VBtn>
    <VBtn
      variant="outlined"
      color="success"
    >
      Success
    </VBtn>
    <VBtn
      variant="outlined"
      color="info"
    >
      Info
    </VBtn>
    <VBtn
      variant="outlined"
      color="warning"
    >
      Warning
    </VBtn>
    <VBtn
      variant="outlined"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`},Jt={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="plain">
      Primary
    </VBtn>

    <VBtn
      color="secondary"
      variant="plain"
    >
      Secondary
    </VBtn>

    <VBtn
      color="success"
      variant="plain"
    >
      Success
    </VBtn>

    <VBtn
      color="info"
      variant="plain"
    >
      Info
    </VBtn>

    <VBtn
      color="warning"
      variant="plain"
    >
      Warning
    </VBtn>

    <VBtn
      color="error"
      variant="plain"
    >
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="plain">
      Primary
    </VBtn>

    <VBtn
      color="secondary"
      variant="plain"
    >
      Secondary
    </VBtn>

    <VBtn
      color="success"
      variant="plain"
    >
      Success
    </VBtn>

    <VBtn
      color="info"
      variant="plain"
    >
      Info
    </VBtn>

    <VBtn
      color="warning"
      variant="plain"
    >
      Warning
    </VBtn>

    <VBtn
      color="error"
      variant="plain"
    >
      Error
    </VBtn>
  </div>
</template>
`},Kt={ts:`<template>
  <div class="demo-space-x">
    <VBtn>
      Normal Button
    </VBtn>
    <VBtn
      rounded
      color="secondary"
    >
      Rounded Button
    </VBtn>
    <VBtn
      :rounded="0"
      color="success"
    >
      Tile Button
    </VBtn>
    <VBtn
      rounded="pill"
      color="info"
    >
      Pill Button
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn>
      Normal Button
    </VBtn>
    <VBtn
      rounded
      color="secondary"
    >
      Rounded Button
    </VBtn>
    <VBtn
      :rounded="0"
      color="success"
    >
      Tile Button
    </VBtn>
    <VBtn
      rounded="pill"
      color="info"
    >
      Pill Button
    </VBtn>
  </div>
</template>
`},Mt={ts:`<template>
  <div class="demo-space-x">
    <VBtn to="/components/alert">
      String Literal
    </VBtn>

    <VBtn
      color="warning"
      :to="{ path: '/components/alert' }"
    >
      Object Path
    </VBtn>

    <VBtn
      color="success"
      :to="{ name: 'components-alert' }"
    >
      Named Router
    </VBtn>

    <VBtn
      color="secondary"
      :to="{ path: '/components/alert', query: { plan: 'private' } }"
    >
      With Query
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn to="/components/alert">
      String Literal
    </VBtn>

    <VBtn
      color="warning"
      :to="{ path: '/components/alert' }"
    >
      Object Path
    </VBtn>

    <VBtn
      color="success"
      :to="{ name: 'components-alert' }"
    >
      Named Router
    </VBtn>

    <VBtn
      color="secondary"
      :to="{ path: '/components/alert', query: { plan: 'private' } }"
    >
      With Query
    </VBtn>
  </div>
</template>
`},Xt={ts:`<template>
  <div class="demo-space-x">
    <VBtn size="x-large">
      Extra large Button
    </VBtn>

    <VBtn
      color="success"
      size="large"
    >
      Large Button
    </VBtn>

    <VBtn color="info">
      Normal Button
    </VBtn>

    <VBtn
      size="small"
      color="warning"
    >
      Small Button
    </VBtn>

    <VBtn
      size="x-small"
      color="error"
    >
      Extra small Button
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn size="x-large">
      Extra large Button
    </VBtn>

    <VBtn
      color="success"
      size="large"
    >
      Large Button
    </VBtn>

    <VBtn color="info">
      Normal Button
    </VBtn>

    <VBtn
      size="small"
      color="warning"
    >
      Small Button
    </VBtn>

    <VBtn
      size="x-small"
      color="error"
    >
      Extra small Button
    </VBtn>
  </div>
</template>
`},Zt={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="text">
      Primary
    </VBtn>

    <VBtn
      variant="text"
      color="secondary"
    >
      Secondary
    </VBtn>

    <VBtn
      variant="text"
      color="success"
    >
      Success
    </VBtn>

    <VBtn
      variant="text"
      color="info"
    >
      Info
    </VBtn>

    <VBtn
      variant="text"
      color="warning"
    >
      Warning
    </VBtn>

    <VBtn
      variant="text"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="text">
      Primary
    </VBtn>

    <VBtn
      variant="text"
      color="secondary"
    >
      Secondary
    </VBtn>

    <VBtn
      variant="text"
      color="success"
    >
      Success
    </VBtn>

    <VBtn
      variant="text"
      color="info"
    >
      Info
    </VBtn>

    <VBtn
      variant="text"
      color="warning"
    >
      Warning
    </VBtn>

    <VBtn
      variant="text"
      color="error"
    >
      Error
    </VBtn>
  </div>
</template>
`},ht={ts:`<template>
  <div class="demo-space-x">
    <VBtn variant="tonal">
      Primary
    </VBtn>

    <VBtn
      color="secondary"
      variant="tonal"
    >
      Secondary
    </VBtn>

    <VBtn
      color="success"
      variant="tonal"
    >
      Success
    </VBtn>

    <VBtn
      color="info"
      variant="tonal"
    >
      Info
    </VBtn>

    <VBtn
      color="warning"
      variant="tonal"
    >
      Warning
    </VBtn>

    <VBtn
      color="error"
      variant="tonal"
    >
      Error
    </VBtn>
  </div>
</template>
`,js:`<template>
  <div class="demo-space-x">
    <VBtn variant="tonal">
      Primary
    </VBtn>

    <VBtn
      color="secondary"
      variant="tonal"
    >
      Secondary
    </VBtn>

    <VBtn
      color="success"
      variant="tonal"
    >
      Success
    </VBtn>

    <VBtn
      color="info"
      variant="tonal"
    >
      Info
    </VBtn>

    <VBtn
      color="warning"
      variant="tonal"
    >
      Warning
    </VBtn>

    <VBtn
      color="error"
      variant="tonal"
    >
      Error
    </VBtn>
  </div>
</template>
`},Vn={__name:"button",setup(u){return(t,l)=>{const V=Rt,a=z,_=jt,y=Pt,b=$t,w=bt,k=vt,S=Vt,$=ct,I=at,E=ot,T=Z,P=K,L=Y,C=A,W=N;return c(),f(x,null,{default:o(()=>[n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Colors",code:s(Ot)},{default:o(()=>[l[0]||(l[0]=i("p",null,[e("The "),i("code",null,"color"),e(" prop is used to change the background color of the alert.")],-1)),n(V)]),_:1,__:[0]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Outlined",code:s(Ht)},{default:o(()=>[l[1]||(l[1]=i("p",null,[e("The "),i("code",null,"outlined"),e(" variant option is used to create outlined buttons.")],-1)),n(_)]),_:1,__:[1]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Flat",code:s(At)},{default:o(()=>[l[2]||(l[2]=i("p",null,[e("The "),i("code",null,"flat"),e(" buttons still maintain their background color, but have no box shadow.")],-1)),n(y)]),_:1,__:[2]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Rounded",code:s(Kt)},{default:o(()=>[l[3]||(l[3]=i("p",null,[e("Use the "),i("code",null,"rounded"),e(" prop to control the border radius of buttons.")],-1)),n(b)]),_:1,__:[3]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Text",code:s(Zt)},{default:o(()=>[l[4]||(l[4]=i("p",null,[e("Use "),i("code",null,"text"),e(" variant option to create text button. Text buttons have no box shadow and no background.")],-1)),n(w)]),_:1,__:[4]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Plain",code:s(Jt)},{default:o(()=>[l[5]||(l[5]=i("p",null,[e("Use "),i("code",null,"plain"),e(" variant option to a create a plain button. Plain buttons have a lower baseline opacity that reacts to hover and focus.")],-1)),n(k)]),_:1,__:[5]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Tonal",code:s(ht)},{default:o(()=>[l[6]||(l[6]=i("p",null,[e("Use "),i("code",null,"tonal"),e(" variant option to a create a light background button.")],-1)),n(S)]),_:1,__:[6]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Icon",code:s(qt)},{default:o(()=>[l[7]||(l[7]=i("p",null,"Icons can be used inside of buttons to add emphasis to the action.",-1)),n($)]),_:1,__:[7]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Icon Only",code:s(Qt)},{default:o(()=>[l[8]||(l[8]=i("p",null,[e("Use "),i("code",null,"icon"),e(" prop to create button with icon only. This property makes the button rounded and applies the text prop styles.")],-1)),n(I)]),_:1,__:[8]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Sizing",code:s(Xt)},{default:o(()=>[l[9]||(l[9]=i("p",null,"Buttons can be given different sizing options to fit a multitude of scenarios.",-1)),n(E)]),_:1,__:[9]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Block",code:s(Ut)},{default:o(()=>[l[10]||(l[10]=i("p",null,[e("The "),i("code",null,"block"),e(" prop allows buttons to extend the full available width.")],-1)),n(T)]),_:1,__:[10]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Loaders",code:s(Ft)},{default:o(()=>[l[11]||(l[11]=i("p",null,[e("Using the "),i("code",null,"loading"),e(" prop, you can notify a user that there is processing taking place. The default behavior is to use a "),i("code",null,"v-progress-circular"),e(" component but this can be customized.")],-1)),n(P)]),_:1,__:[11]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Router",code:s(Mt)},{default:o(()=>[l[13]||(l[13]=i("p",null,[e("Use "),i("code",null,"to"),e(" prop to create button with router support.")],-1)),n(v,{color:"warning",variant:"tonal",class:"mb-4"},{default:o(()=>l[12]||(l[12]=[e(" Note: On click of the link button, You will get redirected to another page. ")])),_:1,__:[12]}),n(L)]),_:1,__:[13]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Link",code:s(Yt)},{default:o(()=>[l[15]||(l[15]=i("p",null,[e("Designates that the component is a link. This is automatic when using the "),i("code",null,"href"),e(" or "),i("code",null,"to"),e(" prop.")],-1)),n(v,{color:"warning",variant:"tonal",class:"mb-4"},{default:o(()=>l[14]||(l[14]=[e(" Note: On click of the link button, You will get redirected to another page. ")])),_:1,__:[14]}),n(C)]),_:1,__:[15]},8,["code"])]),_:1}),n(d,{cols:"12"},{default:o(()=>[n(a,{title:"Group",code:s(Gt)},{default:o(()=>[l[16]||(l[16]=i("p",null,[e(" Wrap buttons with the "),i("code",null,"v-btn-toggle"),e(" component to create a group button. You can add a visual divider between buttons with the "),i("code",null,"divided"),e(" prop. ")],-1)),n(W)]),_:1,__:[16]},8,["code"])]),_:1})]),_:1})}}};export{Vn as default};
