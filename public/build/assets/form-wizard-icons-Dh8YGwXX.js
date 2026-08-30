import{_ as ee}from"./AppCardCode-BlK2sY5I.js";import{_ as j}from"./AppStepper-B_CPwqGY.js";import{o as k,c as U,d as i,a1 as le,r as f,f as D,e as o,b as e,aG as B,l,t as p,ai as w,a0 as h,s as C,aN as R,v as oe}from"./main-BaEGrDAQ.js";import{V as N}from"./VCardText-C1Ij5jP7.js";import{V as A}from"./VDivider-DXB8nnF_.js";import{V as T}from"./VForm-wHYhEd0D.js";import{V as $,a as y}from"./VWindowItem-aY8r0kPb.js";import{V as F,a as d}from"./VRow-BVuxKxZe.js";import{V as c}from"./VTextField-CaIJlpl2.js";import{V as z}from"./VSelect-DZJYg8Bu.js";import{V as E}from"./VCard-BeQYVVVh.js";import{r as v,e as ae,p as te,d as se,u as _}from"./validators-BTYlBg-y.js";import"./vue3-perfect-scrollbar-EehmiHvb.js";import"./VSlideGroupItem-or_7I8gi.js";import"./VSlideGroup-Tz_6M7np.js";import"./form-CaqpxLq3.js";import"./ssrBoot-CyQbTewg.js";/* empty css              *//* empty css                   */import"./VCounter-CxVvccye.js";import"./VField-V3KZtSO_.js";import"./VInput-DvQefsTH.js";import"./VImg-CkbqcFju.js";import"./VList-OPu-hxp0.js";import"./createSimpleFunctional-CWpvwpSS.js";import"./VAvatar-RrDvHgSg.js";import"./dialog-transition-OmMCQVtn.js";import"./VMenu-BnlFJ5vU.js";import"./VCheckboxBtn-C-Ll-Oe8.js";import"./VSelectionControl-DJlo6I7t.js";import"./VChip-Ch60EB2d.js";import"./helpers-DK5QwNv0.js";const re={ts:`<script setup lang="ts">
import customWizardAccount from '@images/svg/wizard-account.svg'
import customWizardAddress from '@images/svg/wizard-address.svg'
import customWizardPersonal from '@images/svg/wizard-personal.svg'
import customWizardSocialLink from '@images/svg/wizard-social-link.svg'
import customWizardSubmit from '@images/svg/wizard-submit.svg'

const iconsSteps = [
  {
    title: 'Account Details',
    icon: customWizardAccount,
  },
  {
    title: 'Personal Info',
    icon: customWizardPersonal,
  },
  {
    title: 'Address',
    icon: customWizardAddress,
  },
  {
    title: 'Social Links',
    icon: customWizardSocialLink,
  },
  {
    title: 'Review & Submit',
    icon: customWizardSubmit,
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)

const formData = ref({
  username: 'johndoe',
  email: 'john.doe@email.com',
  password: 'johndoe@J2',
  cPassword: 'johndoe@J2',
  firstName: 'John',
  lastName: 'Doe',
  country: 'UK',
  language: 'English',
  address: '98 Borough bridge Road, Birmingham',
  landmark: 'Borough bridge',
  pincode: '658921',
  city: 'Birmingham',
  twitter: 'https://twitter.com/abc',
  facebook: 'https://facebook.com/abc',
  googlePlus: 'https://plus.google.com/abc',
  LinkedIn: 'https://linkedin.com/abc',
})

const onSubmit = () => {
  console.log(formData.value)
}
<\/script>

<template>
  <VCard>
    <VCardText>
      <!-- 👉 Stepper -->
      <AppStepper
        v-model:current-step="currentStep"
        :items="iconsSteps"
        align="center"
      />
    </VCardText>

    <VDivider />

    <VCardText>
      <!-- 👉 stepper content -->
      <VForm>
        <VWindow
          v-model="currentStep"
          class="disable-tab-transition"
        >
          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Account Details
                </h6>
                <p class="text-sm mb-0">
                  Enter your Account Details
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.username"
                  placeholder="CarterLeonardo"
                  label="Username"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.email"
                  placeholder="carterleonardo@gmail.com"
                  label="Email"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.cPassword"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  placeholder="············"
                  :type="isCPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Personal Info
                </h6>
                <p class="text-sm mb-0">
                  Setup Information
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.firstName"
                  label="First Name"
                  placeholder="Leonard"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.lastName"
                  label="Last Name"
                  placeholder="Carter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="formData.country"
                  label="Country"
                  placeholder="Select Country"
                  :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="formData.language"
                  label="Language"
                  placeholder="Select Language"
                  :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Address
                </h6>
                <p class="text-sm mb-0">
                  Enter Your Address.
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.address"
                  placeholder="98 Borough bridge Road, Birmingham"
                  label="Address"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.landmark"
                  placeholder="Borough bridge"
                  label="Landmark"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.pincode"
                  placeholder="658921"
                  label="Pincode"
                  type="number"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.city"
                  placeholder="New York"
                  label="City"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Social Links
                </h6>
                <p class="text-sm mb-0">
                  Add Social Links
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.twitter"
                  placeholder="https://twitter.com/abc"
                  label="Twitter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.facebook"
                  placeholder="https://facebook.com/abc"
                  label="Facebook"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.googlePlus"
                  placeholder="https://plus.google.com/abc"
                  label="Google+"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.LinkedIn"
                  placeholder="https://linkedin.com/abc"
                  label="LinkedIn"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <div class="text-base">
              <h6 class="text-base font-weight-medium mb-2">
                Account
              </h6>

              <p class="mb-1">
                {{ formData.username }}
              </p>
              <p class="mb-1">
                {{ formData.email }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Personal Info
              </h6>

              <p class="mb-1">
                {{ formData.firstName }}
              </p>
              <p class="mb-1">
                {{ formData.lastName }}
              </p>
              <p class="mb-1">
                {{ formData.country }}
              </p>
              <p class="mb-1">
                {{ formData.language }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Address
              </h6>

              <p class="mb-1">
                {{ formData.address }}
              </p>
              <p class="mb-1">
                {{ formData.landmark }}
              </p>
              <p class="mb-1">
                {{ formData.pincode }}
              </p>
              <p class="mb-1">
                {{ formData.city }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Social Links
              </h6>

              <p class="mb-1">
                {{ formData.twitter }}
              </p>
              <p class="mb-1">
                {{ formData.facebook }}
              </p>
              <p class="mb-1">
                {{ formData.googlePlus }}
              </p>
              <p class="mb-1">
                {{ formData.LinkedIn }}
              </p>
            </div>
          </VWindowItem>
        </VWindow>

        <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
          <VBtn
            color="secondary"
            variant="outlined"
            :disabled="currentStep === 0"
            @click="currentStep--"
          >
            <VIcon
              icon="ri-arrow-left-line"
              start
              class="flip-in-rtl"
            />
            Previous
          </VBtn>

          <VBtn
            v-if="iconsSteps.length - 1 === currentStep"
            color="success"
            append-icon="ri-check-line"
            @click="onSubmit"
          >
            submit
          </VBtn>

          <VBtn
            v-else
            @click="currentStep++"
          >
            Next

            <VIcon
              icon="ri-arrow-right-line"
              end
              class="flip-in-rtl"
            />
          </VBtn>
        </div>
      </VForm>
    </VCardText>
  </VCard>
</template>
`,js:`<script setup>
import customWizardAccount from '@images/svg/wizard-account.svg'
import customWizardAddress from '@images/svg/wizard-address.svg'
import customWizardPersonal from '@images/svg/wizard-personal.svg'
import customWizardSocialLink from '@images/svg/wizard-social-link.svg'
import customWizardSubmit from '@images/svg/wizard-submit.svg'

const iconsSteps = [
  {
    title: 'Account Details',
    icon: customWizardAccount,
  },
  {
    title: 'Personal Info',
    icon: customWizardPersonal,
  },
  {
    title: 'Address',
    icon: customWizardAddress,
  },
  {
    title: 'Social Links',
    icon: customWizardSocialLink,
  },
  {
    title: 'Review & Submit',
    icon: customWizardSubmit,
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)

const formData = ref({
  username: 'johndoe',
  email: 'john.doe@email.com',
  password: 'johndoe@J2',
  cPassword: 'johndoe@J2',
  firstName: 'John',
  lastName: 'Doe',
  country: 'UK',
  language: 'English',
  address: '98 Borough bridge Road, Birmingham',
  landmark: 'Borough bridge',
  pincode: '658921',
  city: 'Birmingham',
  twitter: 'https://twitter.com/abc',
  facebook: 'https://facebook.com/abc',
  googlePlus: 'https://plus.google.com/abc',
  LinkedIn: 'https://linkedin.com/abc',
})

const onSubmit = () => {
  console.log(formData.value)
}
<\/script>

<template>
  <VCard>
    <VCardText>
      <!-- 👉 Stepper -->
      <AppStepper
        v-model:current-step="currentStep"
        :items="iconsSteps"
        align="center"
      />
    </VCardText>

    <VDivider />

    <VCardText>
      <!-- 👉 stepper content -->
      <VForm>
        <VWindow
          v-model="currentStep"
          class="disable-tab-transition"
        >
          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Account Details
                </h6>
                <p class="text-sm mb-0">
                  Enter your Account Details
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.username"
                  placeholder="CarterLeonardo"
                  label="Username"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.email"
                  placeholder="carterleonardo@gmail.com"
                  label="Email"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.cPassword"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  placeholder="············"
                  :type="isCPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Personal Info
                </h6>
                <p class="text-sm mb-0">
                  Setup Information
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.firstName"
                  label="First Name"
                  placeholder="Leonard"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.lastName"
                  label="Last Name"
                  placeholder="Carter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="formData.country"
                  label="Country"
                  placeholder="Select Country"
                  :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="formData.language"
                  label="Language"
                  placeholder="Select Language"
                  :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Address
                </h6>
                <p class="text-sm mb-0">
                  Enter Your Address.
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.address"
                  placeholder="98 Borough bridge Road, Birmingham"
                  label="Address"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.landmark"
                  placeholder="Borough bridge"
                  label="Landmark"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.pincode"
                  placeholder="658921"
                  label="Pincode"
                  type="number"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.city"
                  placeholder="New York"
                  label="City"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Social Links
                </h6>
                <p class="text-sm mb-0">
                  Add Social Links
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.twitter"
                  placeholder="https://twitter.com/abc"
                  label="Twitter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.facebook"
                  placeholder="https://facebook.com/abc"
                  label="Facebook"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.googlePlus"
                  placeholder="https://plus.google.com/abc"
                  label="Google+"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="formData.LinkedIn"
                  placeholder="https://linkedin.com/abc"
                  label="LinkedIn"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem>
            <div class="text-base">
              <h6 class="text-base font-weight-medium mb-2">
                Account
              </h6>

              <p class="mb-1">
                {{ formData.username }}
              </p>
              <p class="mb-1">
                {{ formData.email }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Personal Info
              </h6>

              <p class="mb-1">
                {{ formData.firstName }}
              </p>
              <p class="mb-1">
                {{ formData.lastName }}
              </p>
              <p class="mb-1">
                {{ formData.country }}
              </p>
              <p class="mb-1">
                {{ formData.language }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Address
              </h6>

              <p class="mb-1">
                {{ formData.address }}
              </p>
              <p class="mb-1">
                {{ formData.landmark }}
              </p>
              <p class="mb-1">
                {{ formData.pincode }}
              </p>
              <p class="mb-1">
                {{ formData.city }}
              </p>

              <VDivider class="my-4" />

              <h6 class="text-base font-weight-medium mb-2">
                Social Links
              </h6>

              <p class="mb-1">
                {{ formData.twitter }}
              </p>
              <p class="mb-1">
                {{ formData.facebook }}
              </p>
              <p class="mb-1">
                {{ formData.googlePlus }}
              </p>
              <p class="mb-1">
                {{ formData.LinkedIn }}
              </p>
            </div>
          </VWindowItem>
        </VWindow>

        <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
          <VBtn
            color="secondary"
            variant="outlined"
            :disabled="currentStep === 0"
            @click="currentStep--"
          >
            <VIcon
              icon="ri-arrow-left-line"
              start
              class="flip-in-rtl"
            />
            Previous
          </VBtn>

          <VBtn
            v-if="iconsSteps.length - 1 === currentStep"
            color="success"
            append-icon="ri-check-line"
            @click="onSubmit"
          >
            submit
          </VBtn>

          <VBtn
            v-else
            @click="currentStep++"
          >
            Next

            <VIcon
              icon="ri-arrow-right-line"
              end
              class="flip-in-rtl"
            />
          </VBtn>
        </div>
      </VForm>
    </VCardText>
  </VCard>
</template>
`},ie={ts:`<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import customWizardAccount from '@images/svg/wizard-account.svg'
import customWizardAddress from '@images/svg/wizard-address.svg'
import customWizardPersonal from '@images/svg/wizard-personal.svg'
import customWizardSocialLink from '@images/svg/wizard-social-link.svg'
import customWizardSubmit from '@images/svg/wizard-submit.svg'

const iconsSteps = [
  {
    title: 'Account Details',
    icon: customWizardAccount,
  },
  {
    title: 'Personal Info',
    icon: customWizardPersonal,
  },
  {
    title: 'Address',
    icon: customWizardAddress,
  },
  {
    title: 'Social Links',
    icon: customWizardSocialLink,
  },
  {
    title: 'Review & Submit',
    icon: customWizardSubmit,
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)
const isCurrentStepValid = ref(true)
const refAccountForm = ref<VForm>()
const refPersonalForm = ref<VForm>()
const refSocialLinkForm = ref<VForm>()
const refAddressForm = ref<VForm>()

const accountForm = ref({
  username: '',
  email: '',
  password: '',
  cPassword: '',
})

const personalForm = ref({
  firstName: '',
  lastName: '',
  country: undefined,
  language: undefined,
})

const socialForm = ref({
  twitter: '',
  facebook: '',
  googlePlus: '',
  LinkedIn: '',
})

const addressForm = ref({
  address: '',
  landmark: '',
  city: '',
  pincode: '',
})

const validateAccountForm = () => {
  refAccountForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    }
    else { isCurrentStepValid.value = false }
  })
}

const validatePersonalForm = () => {
  refPersonalForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    }
    else { isCurrentStepValid.value = false }
  })
}

const validateAddressForm = () => {
  refAddressForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    }
    else { isCurrentStepValid.value = false }
  })
}

const validateSocialLinkForm = () => {
  refSocialLinkForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    }
    else { isCurrentStepValid.value = false }
  })
}
<\/script>

<template>
  <VCard>
    <VCardText>
      <!-- 👉 Stepper -->
      <AppStepper
        v-model:current-step="currentStep"
        :items="iconsSteps"
        :is-active-step-valid="isCurrentStepValid"
        align="center"
      />
    </VCardText>

    <VDivider />

    <VCardText>
      <!-- 👉 stepper content -->

      <VWindow
        v-model="currentStep"
        class="disable-tab-transition"
      >
        <VWindowItem>
          <VForm
            ref="refAccountForm"
            @submit.prevent="validateAccountForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Account Details
                </h6>
                <p class="text-sm mb-0">
                  Enter your Account Details
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.username"
                  placeholder="CarterLeonardo"
                  :rules="[requiredValidator]"
                  label="Username"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.email"
                  placeholder="carterleonardo@gmail.com"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.password"
                  placeholder="············"
                  label="Password"
                  :rules="[requiredValidator, passwordValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.cPassword"
                  placeholder="············"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  :rules="[requiredValidator, confirmedValidator(accountForm.cPassword, accountForm.password)]"
                  :type="isCPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    disabled
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refPersonalForm"
            @submit.prevent="validatePersonalForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Personal Info
                </h6>
                <p class="text-sm mb-0">
                  Setup Information
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="personalForm.firstName"
                  label="First Name"
                  :rules="[requiredValidator]"
                  placeholder="Leonard"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="personalForm.lastName"
                  label="Last Name"
                  :rules="[requiredValidator]"
                  placeholder="Carter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="personalForm.country"
                  label="Country"
                  :rules="[requiredValidator]"
                  placeholder="Select Country"
                  :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="personalForm.language"
                  label="Language"
                  :rules="[requiredValidator]"
                  placeholder="Select Language"
                  :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refAddressForm"
            @submit.prevent="validateAddressForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Address
                </h6>
                <p class="text-sm mb-0">
                  Enter Your Address
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.address"
                  :rules="[requiredValidator]"
                  placeholder="98 Borough bridge Road, Birmingham"
                  label="Address"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.landmark"
                  :rules="[requiredValidator]"
                  placeholder="Borough bridge"
                  label="Landmark"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.pincode"
                  :rules="[requiredValidator]"
                  placeholder="658921"
                  label="Pincode"
                  type="number"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.city"
                  :rules="[requiredValidator]"
                  placeholder="New York"
                  label="City"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refSocialLinkForm"
            @submit.prevent="validateSocialLinkForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Social Links
                </h6>
                <p class="text-sm mb-0">
                  Add Social Links
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.twitter"
                  placeholder="https://twitter.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Twitter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.facebook"
                  placeholder="https://facebook.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Facebook"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.googlePlus"
                  placeholder="https://plus.google.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Google+"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.LinkedIn"
                  placeholder="https://linkedin.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="LinkedIn"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <div class="text-base">
            <h6 class="text-base font-weight-medium mb-2">
              Account
            </h6>

            <p class="mb-1">
              {{ accountForm.username }}
            </p>
            <p class="mb-1">
              {{ accountForm.email }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Personal Info
            </h6>

            <p class="mb-1">
              {{ personalForm.firstName }}
            </p>
            <p class="mb-1">
              {{ personalForm.lastName }}
            </p>
            <p class="mb-1">
              {{ personalForm.country }}
            </p>
            <p class="mb-1">
              {{ personalForm.language }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Address
            </h6>

            <p class="mb-1">
              {{ addressForm.address }}
            </p>
            <p class="mb-1">
              {{ addressForm.landmark }}
            </p>
            <p class="mb-1">
              {{ addressForm.pincode }}
            </p>
            <p class="mb-1">
              {{ addressForm.city }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Social Links
            </h6>

            <p class="mb-1">
              {{ socialForm.twitter }}
            </p>
            <p class="mb-1">
              {{ socialForm.facebook }}
            </p>
            <p class="mb-1">
              {{ socialForm.googlePlus }}
            </p>
            <p class="mb-1">
              {{ socialForm.LinkedIn }}
            </p>
          </div>

          <div>
            <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
              <VBtn
                color="secondary"
                variant="outlined"
                @click="currentStep--"
              >
                <VIcon
                  icon="ri-arrow-left-line"
                  start
                  class="flip-in-rtl"
                />
                Previous
              </VBtn>

              <VBtn
                color="success"
                type="submit"
              >
                Submit
                <VIcon
                  icon="ri-check-line"
                  end
                  class="flip-in-rtl"
                />
              </VBtn>
            </div>
          </div>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>
`,js:`<script setup>
import { VForm } from 'vuetify/components/VForm'
import customWizardAccount from '@images/svg/wizard-account.svg'
import customWizardAddress from '@images/svg/wizard-address.svg'
import customWizardPersonal from '@images/svg/wizard-personal.svg'
import customWizardSocialLink from '@images/svg/wizard-social-link.svg'
import customWizardSubmit from '@images/svg/wizard-submit.svg'

const iconsSteps = [
  {
    title: 'Account Details',
    icon: customWizardAccount,
  },
  {
    title: 'Personal Info',
    icon: customWizardPersonal,
  },
  {
    title: 'Address',
    icon: customWizardAddress,
  },
  {
    title: 'Social Links',
    icon: customWizardSocialLink,
  },
  {
    title: 'Review & Submit',
    icon: customWizardSubmit,
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)
const isCurrentStepValid = ref(true)
const refAccountForm = ref()
const refPersonalForm = ref()
const refSocialLinkForm = ref()
const refAddressForm = ref()

const accountForm = ref({
  username: '',
  email: '',
  password: '',
  cPassword: '',
})

const personalForm = ref({
  firstName: '',
  lastName: '',
  country: undefined,
  language: undefined,
})

const socialForm = ref({
  twitter: '',
  facebook: '',
  googlePlus: '',
  LinkedIn: '',
})

const addressForm = ref({
  address: '',
  landmark: '',
  city: '',
  pincode: '',
})

const validateAccountForm = () => {
  refAccountForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    } else {
      isCurrentStepValid.value = false
    }
  })
}

const validatePersonalForm = () => {
  refPersonalForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    } else {
      isCurrentStepValid.value = false
    }
  })
}

const validateAddressForm = () => {
  refAddressForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    } else {
      isCurrentStepValid.value = false
    }
  })
}

const validateSocialLinkForm = () => {
  refSocialLinkForm.value?.validate().then(valid => {
    if (valid.valid) {
      currentStep.value++
      isCurrentStepValid.value = true
    } else {
      isCurrentStepValid.value = false
    }
  })
}
<\/script>

<template>
  <VCard>
    <VCardText>
      <!-- 👉 Stepper -->
      <AppStepper
        v-model:current-step="currentStep"
        :items="iconsSteps"
        :is-active-step-valid="isCurrentStepValid"
        align="center"
      />
    </VCardText>

    <VDivider />

    <VCardText>
      <!-- 👉 stepper content -->

      <VWindow
        v-model="currentStep"
        class="disable-tab-transition"
      >
        <VWindowItem>
          <VForm
            ref="refAccountForm"
            @submit.prevent="validateAccountForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Account Details
                </h6>
                <p class="text-sm mb-0">
                  Enter your Account Details
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.username"
                  placeholder="CarterLeonardo"
                  :rules="[requiredValidator]"
                  label="Username"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.email"
                  placeholder="carterleonardo@gmail.com"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.password"
                  placeholder="············"
                  label="Password"
                  :rules="[requiredValidator, passwordValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="accountForm.cPassword"
                  placeholder="············"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  :rules="[requiredValidator, confirmedValidator(accountForm.cPassword, accountForm.password)]"
                  :type="isCPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    disabled
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refPersonalForm"
            @submit.prevent="validatePersonalForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Personal Info
                </h6>
                <p class="text-sm mb-0">
                  Setup Information
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="personalForm.firstName"
                  label="First Name"
                  :rules="[requiredValidator]"
                  placeholder="Leonard"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="personalForm.lastName"
                  label="Last Name"
                  :rules="[requiredValidator]"
                  placeholder="Carter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="personalForm.country"
                  label="Country"
                  :rules="[requiredValidator]"
                  placeholder="Select Country"
                  :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="personalForm.language"
                  label="Language"
                  :rules="[requiredValidator]"
                  placeholder="Select Language"
                  :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refAddressForm"
            @submit.prevent="validateAddressForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Address
                </h6>
                <p class="text-sm mb-0">
                  Enter Your Address
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.address"
                  :rules="[requiredValidator]"
                  placeholder="98 Borough bridge Road, Birmingham"
                  label="Address"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.landmark"
                  :rules="[requiredValidator]"
                  placeholder="Borough bridge"
                  label="Landmark"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.pincode"
                  :rules="[requiredValidator]"
                  placeholder="658921"
                  label="Pincode"
                  type="number"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="addressForm.city"
                  :rules="[requiredValidator]"
                  placeholder="New York"
                  label="City"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <VForm
            ref="refSocialLinkForm"
            @submit.prevent="validateSocialLinkForm"
          >
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6">
                  Social Links
                </h6>
                <p class="text-sm mb-0">
                  Add Social Links
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.twitter"
                  placeholder="https://twitter.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Twitter"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.facebook"
                  placeholder="https://facebook.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Facebook"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.googlePlus"
                  placeholder="https://plus.google.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="Google+"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="socialForm.LinkedIn"
                  placeholder="https://linkedin.com/abc"
                  :rules="[requiredValidator, urlValidator]"
                  label="LinkedIn"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="currentStep--"
                  >
                    <VIcon
                      icon="ri-arrow-left-line"
                      start
                      class="flip-in-rtl"
                    />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon
                      icon="ri-arrow-right-line"
                      end
                      class="flip-in-rtl"
                    />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <VWindowItem>
          <div class="text-base">
            <h6 class="text-base font-weight-medium mb-2">
              Account
            </h6>

            <p class="mb-1">
              {{ accountForm.username }}
            </p>
            <p class="mb-1">
              {{ accountForm.email }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Personal Info
            </h6>

            <p class="mb-1">
              {{ personalForm.firstName }}
            </p>
            <p class="mb-1">
              {{ personalForm.lastName }}
            </p>
            <p class="mb-1">
              {{ personalForm.country }}
            </p>
            <p class="mb-1">
              {{ personalForm.language }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Address
            </h6>

            <p class="mb-1">
              {{ addressForm.address }}
            </p>
            <p class="mb-1">
              {{ addressForm.landmark }}
            </p>
            <p class="mb-1">
              {{ addressForm.pincode }}
            </p>
            <p class="mb-1">
              {{ addressForm.city }}
            </p>

            <VDivider class="my-4" />

            <h6 class="text-base font-weight-medium mb-2">
              Social Links
            </h6>

            <p class="mb-1">
              {{ socialForm.twitter }}
            </p>
            <p class="mb-1">
              {{ socialForm.facebook }}
            </p>
            <p class="mb-1">
              {{ socialForm.googlePlus }}
            </p>
            <p class="mb-1">
              {{ socialForm.LinkedIn }}
            </p>
          </div>

          <div>
            <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
              <VBtn
                color="secondary"
                variant="outlined"
                @click="currentStep--"
              >
                <VIcon
                  icon="ri-arrow-left-line"
                  start
                  class="flip-in-rtl"
                />
                Previous
              </VBtn>

              <VBtn
                color="success"
                type="submit"
              >
                Submit
                <VIcon
                  icon="ri-check-line"
                  end
                  class="flip-in-rtl"
                />
              </VBtn>
            </div>
          </div>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>
`},de={ts:`<script setup lang="ts">
const numberedSteps = [
  {
    title: 'Account Details',
    icon: 'ri-article-line',
  },
  {
    title: 'Personal Info',
    icon: 'ri-user-line',
  },
  {
    title: 'Social Links',
    icon: 'ri-links-line',
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)

const formData = ref({
  username: '',
  email: '',
  password: '',
  cPassword: '',
  firstName: '',
  lastName: '',
  country: undefined,
  language: undefined,
  twitter: '',
  facebook: '',
  googlePlus: '',
  LinkedIn: '',

})

const onSubmit = () => {
  console.log(formData.value)
}
<\/script>

<template>
  <VCard>
    <VRow>
      <VCol
        cols="12"
        md="4"
        :class="$vuetify.display.smAndDown ? 'border-b' : 'border-e'"
      >
        <VCardText>
          <!-- 👉 Stepper -->
          <AppStepper
            v-model:current-step="currentStep"
            direction="vertical"
            :items="numberedSteps"
            icon-size="24"
            class="stepper-icon-step-bg"
          />
        </VCardText>
      </VCol>
      <!-- 👉 stepper content -->
      <VCol
        cols="12"
        md="8"
      >
        <VCardText>
          <VForm>
            <VWindow
              v-model="currentStep"
              class="disable-tab-transition"
            >
              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Account Details
                    </h6>
                    <p class="text-sm mb-0">
                      Enter your Account Details
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.username"
                      placeholder="CarterLeonardo"
                      label="Username"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.email"
                      placeholder="carterleonardo@gmail.com"
                      label="Email"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.password"
                      label="Password"
                      placeholder="············"
                      :type="isPasswordVisible ? 'text' : 'password'"
                      autocomplete="password"
                      :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                      @click:append-inner="isPasswordVisible = !isPasswordVisible"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.cPassword"
                      label="Confirm Password"
                      autocomplete="confirm-password"
                      placeholder="············"
                      :type="isCPasswordVisible ? 'text' : 'password'"
                      :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                      @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>

              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Personal Info
                    </h6>
                    <p class="text-sm mb-0">
                      Setup Information
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.firstName"
                      label="First Name"
                      placeholder="Leonard"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.lastName"
                      label="Last Name"
                      placeholder="Carter"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="formData.country"
                      label="Country"
                      placeholder="Select Country"
                      :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="formData.language"
                      label="Language"
                      placeholder="Select Language"
                      :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>

              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Social Links
                    </h6>
                    <p class="text-sm mb-0">
                      Add Social Links
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.twitter"
                      placeholder="https://twitter.com/abc"
                      label="Twitter"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.facebook"
                      placeholder="https://facebook.com/abc"
                      label="Facebook"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.googlePlus"
                      placeholder="https://plus.google.com/abc"
                      label="Google+"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.LinkedIn"
                      placeholder="https://linkedin.com/abc"
                      label="LinkedIn"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>
            </VWindow>

            <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
              <VBtn
                color="secondary"
                variant="outlined"
                :disabled="currentStep === 0"
                @click="currentStep--"
              >
                <VIcon
                  icon="ri-arrow-left-line"
                  start
                  class="flip-in-rtl"
                />
                Previous
              </VBtn>

              <VBtn
                v-if="numberedSteps.length - 1 === currentStep"
                color="success"
                append-icon="ri-check-line"
                @click="onSubmit"
              >
                submit
              </VBtn>

              <VBtn
                v-else
                @click="currentStep++"
              >
                Next

                <VIcon
                  icon="ri-arrow-right-line"
                  end
                  class="flip-in-rtl"
                />
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCol>
    </VRow>
  </VCard>
</template>
`,js:`<script setup>
const numberedSteps = [
  {
    title: 'Account Details',
    icon: 'ri-article-line',
  },
  {
    title: 'Personal Info',
    icon: 'ri-user-line',
  },
  {
    title: 'Social Links',
    icon: 'ri-links-line',
  },
]

const currentStep = ref(0)
const isPasswordVisible = ref(false)
const isCPasswordVisible = ref(false)

const formData = ref({
  username: '',
  email: '',
  password: '',
  cPassword: '',
  firstName: '',
  lastName: '',
  country: undefined,
  language: undefined,
  twitter: '',
  facebook: '',
  googlePlus: '',
  LinkedIn: '',
})

const onSubmit = () => {
  console.log(formData.value)
}
<\/script>

<template>
  <VCard>
    <VRow>
      <VCol
        cols="12"
        md="4"
        :class="$vuetify.display.smAndDown ? 'border-b' : 'border-e'"
      >
        <VCardText>
          <!-- 👉 Stepper -->
          <AppStepper
            v-model:current-step="currentStep"
            direction="vertical"
            :items="numberedSteps"
            icon-size="24"
            class="stepper-icon-step-bg"
          />
        </VCardText>
      </VCol>
      <!-- 👉 stepper content -->
      <VCol
        cols="12"
        md="8"
      >
        <VCardText>
          <VForm>
            <VWindow
              v-model="currentStep"
              class="disable-tab-transition"
            >
              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Account Details
                    </h6>
                    <p class="text-sm mb-0">
                      Enter your Account Details
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.username"
                      placeholder="CarterLeonardo"
                      label="Username"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.email"
                      placeholder="carterleonardo@gmail.com"
                      label="Email"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.password"
                      label="Password"
                      placeholder="············"
                      :type="isPasswordVisible ? 'text' : 'password'"
                      autocomplete="password"
                      :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                      @click:append-inner="isPasswordVisible = !isPasswordVisible"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.cPassword"
                      label="Confirm Password"
                      autocomplete="confirm-password"
                      placeholder="············"
                      :type="isCPasswordVisible ? 'text' : 'password'"
                      :append-inner-icon="isCPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                      @click:append-inner="isCPasswordVisible = !isCPasswordVisible"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>

              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Personal Info
                    </h6>
                    <p class="text-sm mb-0">
                      Setup Information
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.firstName"
                      label="First Name"
                      placeholder="Leonard"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.lastName"
                      label="Last Name"
                      placeholder="Carter"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="formData.country"
                      label="Country"
                      placeholder="Select Country"
                      :items="['UK', 'USA', 'Canada', 'Australia', 'Germany']"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="formData.language"
                      label="Language"
                      placeholder="Select Language"
                      :items="['English', 'Spanish', 'French', 'Russian', 'German']"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>

              <VWindowItem>
                <VRow>
                  <VCol cols="12">
                    <h6 class="text-h6">
                      Social Links
                    </h6>
                    <p class="text-sm mb-0">
                      Add Social Links
                    </p>
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.twitter"
                      placeholder="https://twitter.com/abc"
                      label="Twitter"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.facebook"
                      placeholder="https://facebook.com/abc"
                      label="Facebook"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.googlePlus"
                      placeholder="https://plus.google.com/abc"
                      label="Google+"
                    />
                  </VCol>

                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="formData.LinkedIn"
                      placeholder="https://linkedin.com/abc"
                      label="LinkedIn"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>
            </VWindow>

            <div class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8">
              <VBtn
                color="secondary"
                variant="outlined"
                :disabled="currentStep === 0"
                @click="currentStep--"
              >
                <VIcon
                  icon="ri-arrow-left-line"
                  start
                  class="flip-in-rtl"
                />
                Previous
              </VBtn>

              <VBtn
                v-if="numberedSteps.length - 1 === currentStep"
                color="success"
                append-icon="ri-check-line"
                @click="onSubmit"
              >
                submit
              </VBtn>

              <VBtn
                v-else
                @click="currentStep++"
              >
                Next

                <VIcon
                  icon="ri-arrow-right-line"
                  end
                  class="flip-in-rtl"
                />
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCol>
    </VRow>
  </VCard>
</template>
`},ne={xmlns:"http://www.w3.org/2000/svg",width:"54",height:"54"};function me(I,V){return k(),U("svg",ne,V[0]||(V[0]=[i("g",{fill:"currentColor"},[i("path",{d:"M54 7.2V4a4 4 0 0 0-4-4H4a4 4 0 0 0-4 4v3.2h1.8v36H.9a.9.9 0 1 0 0 1.8h25.2v1.8c0 .042.019.08.024.12A3.596 3.596 0 0 0 23.4 50.4c0 1.985 1.615 3.6 3.6 3.6s3.6-1.615 3.6-3.6a3.596 3.596 0 0 0-2.724-3.48c.005-.04.024-.078.024-.12V45h25.2a.9.9 0 1 0 0-1.8h-.9v-36zM28.8 50.4c0 .993-.807 1.8-1.8 1.8s-1.8-.807-1.8-1.8.807-1.8 1.8-1.8 1.8.807 1.8 1.8M5.4 1.8h43.2a3.6 3.6 0 0 1 3.6 3.6H1.8a3.6 3.6 0 0 1 3.6-3.6m43 41.4H5.6a2 2 0 0 1-2-2v-32a2 2 0 0 1 2-2h42.8a2 2 0 0 1 2 2v32a2 2 0 0 1-2 2"}),i("path",{d:"M45 36.9H31.5a.9.9 0 1 0 0 1.8H45a.9.9 0 1 0 0-1.8M9 32.4h9a.9.9 0 1 0 0-1.8H9a.9.9 0 1 0 0 1.8m18 0h13.5a.9.9 0 1 0 0-1.8H27a.9.9 0 1 0 0 1.8m-5.139-1.539a.93.93 0 0 0-.261.639c0 .234.099.468.261.639a.95.95 0 0 0 .639.261.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.95.95 0 0 0-.261-.639c-.333-.333-.945-.333-1.278 0M27 36.9H13.5a.9.9 0 1 0 0 1.8H27a.9.9 0 1 0 0-1.8M9 38.7a.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.9.9 0 0 0-.261-.63c-.333-.342-.936-.342-1.278-.009a.95.95 0 0 0-.261.639c0 .234.099.468.261.639A.95.95 0 0 0 9 38.7m35.361-7.839a.93.93 0 0 0-.261.639c0 .234.099.468.261.639A.95.95 0 0 0 45 32.4a.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.95.95 0 0 0-.261-.639c-.333-.333-.936-.333-1.278 0M45 18H31.5a.9.9 0 1 0 0 1.8H45a.9.9 0 1 0 0-1.8m0 6.3h-9a.9.9 0 1 0 0 1.8h9a.9.9 0 1 0 0-1.8m-18 1.8h1.8a.9.9 0 1 0 0-1.8H27a.9.9 0 1 0 0 1.8m0-12.6h13.5a.9.9 0 1 0 0-1.8H27a.9.9 0 1 0 0 1.8m18 0a.95.95 0 0 0 .639-.261.9.9 0 0 0 .261-.639.9.9 0 0 0-.261-.639c-.342-.333-.945-.333-1.278 0a.95.95 0 0 0-.261.639c0 .234.099.468.261.639A.95.95 0 0 0 45 13.5m-17.739 4.761A.93.93 0 0 0 27 18.9c0 .234.099.468.261.639a.95.95 0 0 0 .639.261.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.93.93 0 0 0-.261-.639.94.94 0 0 0-1.278 0m4.5 6.3a.95.95 0 0 0-.261.639c0 .234.099.468.261.639a.95.95 0 0 0 .639.261.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.95.95 0 0 0-.261-.639c-.333-.333-.945-.333-1.278 0M19.5 11.7h-8.4a3 3 0 0 0-3 3v8.4a3 3 0 0 0 3 3h8.4a3 3 0 0 0 3-3v-8.4a3 3 0 0 0-3-3m-1.8 12.6h-4.8a3 3 0 0 1-3-3v-4.8a3 3 0 0 1 3-3h4.8a3 3 0 0 1 3 3v4.8a3 3 0 0 1-3 3"})],-1)]))}const H={render:me},ce={xmlns:"http://www.w3.org/2000/svg",width:"54",height:"54"};function ue(I,V){return k(),U("svg",ce,V[0]||(V[0]=[le('<g fill="currentColor"><path d="M54 3.864c0-1.986-1.615-3.6-3.6-3.6H3.6a3.604 3.604 0 0 0-3.6 3.6c0 1.673 1.152 3.07 2.7 3.472v43.7l2.7-2.7 5.4 5.4 5.4-5.4 5.4 5.4 5.4-5.4 5.4 5.4 5.4-5.4 5.4 5.4 5.4-5.4 2.7 2.7v-43.7c1.548-.402 2.7-1.8 2.7-3.472M49.5 46.69l-.9-.9-5.4 5.4-5.4-5.4-5.4 5.4-5.4-5.4-5.4 5.4-5.4-5.4-5.4 5.4-5.4-5.4-.9.9V4.764h45zm1.8-41.27V2.965H2.7v2.458a1.8 1.8 0 0 1 .9-3.358h46.8a1.8 1.8 0 0 1 .9 3.358z"></path><path d="M39.6 36.264H26.1a.9.9 0 1 0 0 1.8h13.5a.9.9 0 1 0 0-1.8m3.861.261a.93.93 0 0 0-.261.639c0 .234.099.468.261.639a.95.95 0 0 0 .639.26.95.95 0 0 0 .639-.26.95.95 0 0 0 .261-.64.93.93 0 0 0-.261-.638.94.94 0 0 0-1.278 0m.639-12.861H30.6a.9.9 0 1 0 0 1.8h13.5a.9.9 0 1 0 0-1.8m0 6.3h-9a.9.9 0 1 0 0 1.8h9a.9.9 0 1 0 0-1.8m-18.9.9a.9.9 0 0 0 .9.9h1.8a.9.9 0 1 0 0-1.8h-1.8a.9.9 0 0 0-.9.9m15.3-12.6a.9.9 0 0 0-.9-.9H26.1a.9.9 0 1 0 0 1.8h13.5a.9.9 0 0 0 .9-.9m2.961-.639a.93.93 0 0 0-.261.639c0 .234.099.468.261.639a.95.95 0 0 0 .639.26.93.93 0 0 0 .639-.26.95.95 0 0 0 .261-.64.95.95 0 0 0-.261-.638c-.333-.333-.945-.333-1.278 0m-15.822 7.578a.93.93 0 0 0 .261-.64.93.93 0 0 0-.261-.638c-.333-.333-.936-.333-1.278 0a.93.93 0 0 0-.261.639.93.93 0 0 0 .261.639.95.95 0 0 0 .639.26.95.95 0 0 0 .639-.26m4.5 6.3a.91.91 0 0 0 0-1.278c-.333-.333-.936-.333-1.278 0a.95.95 0 0 0-.261.639c0 .234.099.468.261.639a.95.95 0 0 0 .639.26.95.95 0 0 0 .639-.26M15.3 19.255v-.991a.9.9 0 1 0-1.8 0v.993c-.982.2-1.877.721-2.536 1.502a3.1 3.1 0 0 0-.715 2.342 3.28 3.28 0 0 0 1.245 2.242l2.006 1.56v4.895a2.7 2.7 0 0 1-1.8-2.534.9.9 0 1 0-1.8 0 4.51 4.51 0 0 0 3.6 4.409v.79a.9.9 0 1 0 1.8 0v-.794c.982-.2 1.877-.72 2.537-1.501a3.1 3.1 0 0 0 .714-2.345 3.28 3.28 0 0 0-1.245-2.239l-2.006-1.56V21.13a2.7 2.7 0 0 1 1.8 2.535.9.9 0 1 0 1.8 0 4.51 4.51 0 0 0-3.6-4.41m.9 9.75c.315.244.52.612.56 1.006.04.37-.067.723-.297.995a2.7 2.7 0 0 1-1.163.787v-3.49zm-3.6-5.082a1.48 1.48 0 0 1-.56-1.008 1.32 1.32 0 0 1 .297-.994 2.7 2.7 0 0 1 1.163-.787v3.49z"></path><circle cx="36" cy="10.164" r="1"></circle><circle cx="32.4" cy="10.164" r="1"></circle><circle cx="39.6" cy="10.164" r="1"></circle><circle cx="28.8" cy="10.164" r="1"></circle><circle cx="43.2" cy="10.164" r="1"></circle><circle cx="18" cy="10.164" r="1"></circle><circle cx="21.6" cy="10.164" r="1"></circle><circle cx="25.2" cy="10.164" r="1"></circle><circle cx="46.8" cy="10.164" r="1"></circle><circle cx="14.4" cy="10.164" r="1"></circle><circle cx="10.8" cy="10.164" r="1"></circle><circle cx="7.2" cy="10.164" r="1"></circle></g>',1)]))}const G={render:ue},pe={xmlns:"http://www.w3.org/2000/svg",width:"58",height:"54"};function Ve(I,V){return k(),U("svg",pe,V[0]||(V[0]=[i("g",{fill:"currentColor"},[i("path",{d:"M53.857.283H4a4 4 0 0 0-4 4V40.64a4 4 0 0 0 4 4h32.643v9.077l6.75-6.75 6.75 6.75V44.64h3.714a4 4 0 0 0 4-4V4.283a4 4 0 0 0-4-4m-5.643 48.779-4.821-4.822-4.822 4.822V38.517q.02.01.037.019a9.6 9.6 0 0 0 4.785 1.282 9.6 9.6 0 0 0 4.785-1.282c.011-.007.025-.01.036-.019zm.283-13.118q-.228.2-.467.38a8 8 0 0 1-.58.4q-.058.034-.114.07a7 7 0 0 1-.496.272l-.145.07q-.242.117-.492.216-.096.036-.193.07-.233.086-.472.157-.128.036-.259.067a7 7 0 0 1-.43.1c-.123.024-.248.04-.373.057-.114.016-.227.037-.343.047a8 8 0 0 1-.741.038 8 8 0 0 1-.742-.038c-.114-.011-.227-.03-.342-.047q-.187-.024-.373-.057c-.145-.028-.288-.065-.43-.1q-.13-.032-.26-.067a7 7 0 0 1-.471-.157q-.098-.034-.193-.07a8 8 0 0 1-1.133-.559 8 8 0 0 1-.602-.4l-.092-.068a7.7 7.7 0 0 1-3.075-6.15c0-4.253 3.46-7.714 7.714-7.714 4.253 0 7.714 3.46 7.714 7.714a7.69 7.69 0 0 1-2.61 5.769m3.432 6.767h-1.786V37.05a9.6 9.6 0 0 0 2.893-6.875c0-5.317-4.326-9.642-9.643-9.642s-9.643 4.325-9.643 9.642a9.6 9.6 0 0 0 2.893 6.875v5.661H5.929a4 4 0 0 1-4-4v-32.5a4 4 0 0 1 4-4h46a4 4 0 0 1 4 4v32.5a4 4 0 0 1-4 4"}),i("path",{d:"M11.747 12.598a3.32 3.32 0 0 0-.766 2.51c.099.944.585 1.82 1.334 2.402l2.15 1.673v5.243a2.89 2.89 0 0 1-1.93-2.715.964.964 0 1 0-1.928 0 4.83 4.83 0 0 0 3.857 4.724v.847a.964.964 0 1 0 1.929 0v-.85a4.8 4.8 0 0 0 2.718-1.61c.59-.697.861-1.588.765-2.511a3.52 3.52 0 0 0-1.334-2.4l-2.15-1.67v-5.244a2.89 2.89 0 0 1 1.93 2.715.964.964 0 1 0 1.928 0 4.83 4.83 0 0 0-3.857-4.724V9.926a.964.964 0 1 0-1.929 0v1.065a4.8 4.8 0 0 0-2.717 1.607m5.611 8.836c.337.261.556.656.6 1.078.041.396-.072.775-.32 1.066a2.9 2.9 0 0 1-1.245.844v-3.74zM13.5 15.99a1.58 1.58 0 0 1-.6-1.08 1.4 1.4 0 0 1 .32-1.065 2.87 2.87 0 0 1 1.245-.843v3.738zm10.607-1.243h22.179a.964.964 0 1 0 0-1.929H24.107a.964.964 0 1 0 0 1.929m0 5.786h10.607a.964.964 0 1 0 0-1.929H24.107a.964.964 0 1 0 0 1.929m0 5.785h7.714a.964.964 0 1 0 0-1.928h-7.714a.964.964 0 1 0 0 1.928m-13.5 6.75h20.25a.964.964 0 1 0 0-1.928h-20.25a.964.964 0 1 0 0 1.928"})],-1)]))}const M={render:Ve},fe={xmlns:"http://www.w3.org/2000/svg",width:"54",height:"54"};function be(I,V){return k(),U("svg",fe,V[0]||(V[0]=[i("g",{fill:"currentColor"},[i("path",{d:"M50 3.6H27.9V.9a.9.9 0 1 0-1.8 0v2.7H4a4 4 0 0 0-4 4v29.8a4 4 0 0 0 4 4h20.827L13.764 52.464a.899.899 0 1 0 1.272 1.272L26.1 42.673V51.3a.9.9 0 1 0 1.8 0v-8.627l11.064 11.063a.9.9 0 0 0 1.272 0 .9.9 0 0 0 0-1.272L29.173 41.4H50a4 4 0 0 0 4-4V7.6a4 4 0 0 0-4-4m-1.8 36H5.8a4 4 0 0 1-4-4V9.4a4 4 0 0 1 4-4h42.4a4 4 0 0 1 4 4v26.2a4 4 0 0 1-4 4"}),i("path",{d:"M32.4 37.8a.9.9 0 0 0 .9-.9v-2.7a.9.9 0 1 0-1.8 0v2.7a.9.9 0 0 0 .9.9m3.6-8.1a.9.9 0 0 0-.9.9v6.3a.9.9 0 1 0 1.8 0v-6.3a.9.9 0 0 0-.9-.9m3.6 1.8a.9.9 0 0 0-.9.9v4.5a.9.9 0 1 0 1.8 0v-4.5a.9.9 0 0 0-.9-.9m3.6-6.3a.9.9 0 0 0-.9.9v10.8a.9.9 0 1 0 1.8 0V26.1a.9.9 0 0 0-.9-.9m3.6 3.6a.9.9 0 0 0-.9.9v7.2a.9.9 0 1 0 1.8 0v-7.2a.9.9 0 0 0-.9-.9M7.2 34.2h9a.9.9 0 1 0 0-1.8h-9a.9.9 0 1 0 0 1.8M20.7 36H7.2a.9.9 0 1 0 0 1.8h13.5a.9.9 0 1 0 0-1.8m6.26-19.254a10 10 0 0 0-.073-.637q-.025-.192-.058-.382a11 11 0 0 0-.153-.7q-.037-.156-.08-.311a10 10 0 0 0-.232-.704q-.052-.15-.11-.297a10 10 0 0 0-.285-.636c-.053-.112-.104-.225-.162-.335a10 10 0 0 0-.296-.513c-.082-.137-.16-.275-.249-.409q-.122-.174-.25-.344c-.124-.171-.245-.344-.38-.508l-.002-.002c-.21-.254-.428-.502-.663-.737a10 10 0 0 0-.706-.634l-.035-.032c-.155-.128-.32-.242-.481-.36-.123-.092-.245-.187-.375-.273-.122-.08-.25-.152-.375-.228a10 10 0 0 0-.55-.318c-.099-.051-.2-.096-.3-.145a10 10 0 0 0-.674-.301q-.134-.05-.266-.097a11 11 0 0 0-.732-.242q-.146-.039-.294-.075a10 10 0 0 0-.71-.156q-.191-.033-.384-.058a11 11 0 0 0-.63-.072 10 10 0 0 0-.487-.024c-.107-.003-.21-.016-.318-.016-.081 0-.16.01-.241.013-.08.001-.158-.006-.238-.002l-.355.021-.01.01C10.495 7.674 6.3 12.127 6.3 17.55c0 5.707 4.643 10.35 10.35 10.35 2.591 0 4.957-.964 6.775-2.544.025-.02.056-.03.08-.053.014-.014.018-.033.032-.048a10.32 10.32 0 0 0 3.419-6.817l.017-.014.018-.396c.004-.08-.004-.157-.002-.236 0-.08.011-.16.011-.242 0-.11-.014-.217-.016-.326q-.007-.239-.025-.478zM17.1 9.023q.136.004.268.013a9 9 0 0 1 .886.12q.09.015.18.034.369.077.724.186l.044.013a8.59 8.59 0 0 1 5.61 5.609q.005.022.012.044.109.355.186.723.019.09.034.181a9 9 0 0 1 .12.886q.009.133.013.268H17.1zm-9 8.527c0-4.254 3.127-7.782 7.2-8.433v8.878l.002.012.031.893h.494l5.68 5.679A8.5 8.5 0 0 1 16.65 26.1c-4.714 0-8.55-3.836-8.55-8.55m14.77 5.847L18.373 18.9h6.71a8.5 8.5 0 0 1-2.213 4.497M35.1 13.5h7.2a.9.9 0 1 0 0-1.8h-7.2a.9.9 0 1 0 0 1.8m11.07-1.539a.88.88 0 0 0-.27.639c0 .243.098.477.261.63.17.171.395.27.639.27a.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.95.95 0 0 0-.261-.639c-.342-.333-.955-.324-1.269 0M44.1 9h2.7a.9.9 0 1 0 0-1.8h-2.7a.9.9 0 1 0 0 1.8m-9 0h1.8a.9.9 0 1 0 0-1.8h-1.8a.9.9 0 1 0 0 1.8m5.4 0a.93.93 0 0 0 .639-.261.93.93 0 0 0 .261-.639.93.93 0 0 0-.261-.639c-.333-.333-.955-.333-1.278 0a.92.92 0 0 0-.261.639c0 .243.098.468.261.639.17.162.395.261.639.261"})],-1)]))}const K={render:be},ve={xmlns:"http://www.w3.org/2000/svg",width:"54",height:"54"};function we(I,V){return k(),U("svg",ve,V[0]||(V[0]=[i("g",{fill:"currentColor"},[i("path",{d:"M50.4 13.5V2.687A2.69 2.69 0 0 0 47.714 0h-3.627A2.69 2.69 0 0 0 41.4 2.687V13.5H4a4 4 0 0 0-4 4v21.7a4 4 0 0 0 4 4h37.4v2.04l3.6 6.3v1.56a.9.9 0 1 0 1.8 0v-1.56l3.6-6.3V43.2a3.6 3.6 0 0 0 3.6-3.6V17.1a3.6 3.6 0 0 0-3.6-3.6m-1.8-3.6h-5.4V8.1h5.4zm-5.4-7.213c0-.49.398-.887.886-.887h3.628c.488 0 .886.398.886.887V6.3h-5.4zM1.8 38.4V18.3a3 3 0 0 1 3-3h33.6a3 3 0 0 1 3 3v20.1a3 3 0 0 1-3 3H4.8a3 3 0 0 1-3-3m44.1 11.086L43.85 45.9h4.1zm2.7-5.386h-5.4V11.7h5.4zm2.7-2.7a.9.9 0 0 1-.9-.9V16.2a.9.9 0 1 1 1.8 0v24.3a.9.9 0 0 1-.9.9"}),i("path",{d:"M35.1 23.4h-2.7a.9.9 0 1 0 0 1.8h2.7a.9.9 0 1 0 0-1.8m-16.2-1.8h2.7a.9.9 0 1 0 0-1.8h-2.7a.9.9 0 1 0 0 1.8m16.2-1.8h-6.3a.9.9 0 1 0 0 1.8h6.3a.9.9 0 1 0 0-1.8m-9.9 1.8a.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.95.95 0 0 0-.261-.639c-.342-.333-.945-.333-1.278 0a.95.95 0 0 0-.261.639c0 .234.099.468.261.639a.93.93 0 0 0 .639.261m-3.6 2.7a.9.9 0 0 0 .9.9h6.3a.9.9 0 1 0 0-1.8h-6.3a.9.9 0 0 0-.9.9m-2.7.9a.95.95 0 0 0 .639-.261.95.95 0 0 0 .261-.639.93.93 0 0 0-.261-.639c-.342-.333-.945-.333-1.278 0A.93.93 0 0 0 18 24.3c0 .234.099.468.261.639a.93.93 0 0 0 .639.261m-9-4.41v-.99a.9.9 0 1 0-1.8 0v.994c-.982.2-1.877.721-2.536 1.502a3.1 3.1 0 0 0-.715 2.342 3.28 3.28 0 0 0 1.245 2.242L8.1 28.44v4.894A2.7 2.7 0 0 1 6.3 30.8a.9.9 0 1 0-1.8 0 4.51 4.51 0 0 0 3.6 4.409V36a.9.9 0 1 0 1.8 0v-.794c.982-.2 1.877-.72 2.537-1.501a3.1 3.1 0 0 0 .714-2.345 3.28 3.28 0 0 0-1.245-2.239L9.9 27.56v-4.894a2.7 2.7 0 0 1 1.8 2.534.9.9 0 1 0 1.8 0 4.51 4.51 0 0 0-3.6-4.41m.9 9.752c.315.243.52.611.56 1.006.04.369-.067.722-.297.994-.313.37-.718.63-1.163.788v-3.49l.9.701zm-3.6-5.083a1.48 1.48 0 0 1-.56-1.008c-.038-.368.067-.72.297-.993.313-.37.718-.63 1.163-.788v3.49l-.9-.7zm14.53 4.601c-1.97 1.026-2.635 2.989-2.876 5.024l-1.117-.936c-.882-.74-2.162.527-1.272 1.272l2.525 2.116c.592.497 1.51.183 1.536-.636.066-1.95.158-4.268 2.114-5.286 1.026-.534.117-2.089-.91-1.554m5.985 3.59c-1.246.289-2.664 1.875-3.542.096-.512-1.04-2.065-.128-1.554.908.465.944 1.35 1.604 2.402 1.737a3 3 0 0 0 1.52-.203c.367-.143 1.742-1.176 2.04-.645.565 1.011 2.12.104 1.554-.908-.484-.865-1.462-1.207-2.42-.985"})],-1)]))}const Y={render:we},Ce={class:"text-base"},ge={class:"mb-1"},he={class:"mb-1"},ye={class:"mb-1"},Fe={class:"mb-1"},ke={class:"mb-1"},xe={class:"mb-1"},Se={class:"mb-1"},Pe={class:"mb-1"},Ie={class:"mb-1"},Ae={class:"mb-1"},De={class:"mb-1"},Le={class:"mb-1"},We={class:"mb-1"},Te={class:"mb-1"},Be={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},Ne={__name:"DemoFormWizardIconsBasic",setup(I){const V=[{title:"Account Details",icon:H},{title:"Personal Info",icon:M},{title:"Address",icon:G},{title:"Social Links",icon:K},{title:"Review & Submit",icon:Y}],u=f(0),b=f(!1),g=f(!1),r=f({username:"johndoe",email:"john.doe@email.com",password:"johndoe@J2",cPassword:"johndoe@J2",firstName:"John",lastName:"Doe",country:"UK",language:"English",address:"98 Borough bridge Road, Birmingham",landmark:"Borough bridge",pincode:"658921",city:"Birmingham",twitter:"https://twitter.com/abc",facebook:"https://facebook.com/abc",googlePlus:"https://plus.google.com/abc",linkedIn:"https://linkedin.com/abc"}),L=()=>{console.log(r.value)};return(q,a)=>{const W=j;return k(),D(E,null,{default:o(()=>[e(N,null,{default:o(()=>[e(W,{"current-step":l(u),"onUpdate:currentStep":a[0]||(a[0]=s=>B(u)?u.value=s:null),items:V,align:"center"},null,8,["current-step"])]),_:1}),e(A),e(N,null,{default:o(()=>[e(T,null,{default:o(()=>[e($,{modelValue:l(u),"onUpdate:modelValue":a[19]||(a[19]=s=>B(u)?u.value=s:null),class:"disable-tab-transition"},{default:o(()=>[e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[22]||(a[22]=[i("h6",{class:"text-h6"}," Account Details ",-1),i("p",{class:"text-sm mb-0"}," Enter your Account Details ",-1)])),_:1,__:[22]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).username,"onUpdate:modelValue":a[1]||(a[1]=s=>l(r).username=s),placeholder:"CarterLeonardo",label:"Username"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).email,"onUpdate:modelValue":a[2]||(a[2]=s=>l(r).email=s),placeholder:"carterleonardo@gmail.com",label:"Email"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).password,"onUpdate:modelValue":a[3]||(a[3]=s=>l(r).password=s),label:"Password",placeholder:"············",type:l(b)?"text":"password",autocomplete:"password","append-inner-icon":l(b)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":a[4]||(a[4]=s=>b.value=!l(b))},null,8,["modelValue","type","append-inner-icon"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).cPassword,"onUpdate:modelValue":a[5]||(a[5]=s=>l(r).cPassword=s),label:"Confirm Password",autocomplete:"confirm-password",placeholder:"············",type:l(g)?"text":"password","append-inner-icon":l(g)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":a[6]||(a[6]=s=>g.value=!l(g))},null,8,["modelValue","type","append-inner-icon"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[23]||(a[23]=[i("h6",{class:"text-h6"}," Personal Info ",-1),i("p",{class:"text-sm mb-0"}," Setup Information ",-1)])),_:1,__:[23]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).firstName,"onUpdate:modelValue":a[7]||(a[7]=s=>l(r).firstName=s),label:"First Name",placeholder:"Leonard"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).lastName,"onUpdate:modelValue":a[8]||(a[8]=s=>l(r).lastName=s),label:"Last Name",placeholder:"Carter"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(r).country,"onUpdate:modelValue":a[9]||(a[9]=s=>l(r).country=s),label:"Country",placeholder:"Select Country",items:["UK","USA","Canada","Australia","Germany"]},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(r).language,"onUpdate:modelValue":a[10]||(a[10]=s=>l(r).language=s),label:"Language",placeholder:"Select Language",items:["English","Spanish","French","Russian","German"]},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[24]||(a[24]=[i("h6",{class:"text-h6"}," Address ",-1),i("p",{class:"text-sm mb-0"}," Enter Your Address. ",-1)])),_:1,__:[24]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).address,"onUpdate:modelValue":a[11]||(a[11]=s=>l(r).address=s),placeholder:"98 Borough bridge Road, Birmingham",label:"Address"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).landmark,"onUpdate:modelValue":a[12]||(a[12]=s=>l(r).landmark=s),placeholder:"Borough bridge",label:"Landmark"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).pincode,"onUpdate:modelValue":a[13]||(a[13]=s=>l(r).pincode=s),placeholder:"658921",label:"Pincode",type:"number"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).city,"onUpdate:modelValue":a[14]||(a[14]=s=>l(r).city=s),placeholder:"New York",label:"City"},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[25]||(a[25]=[i("h6",{class:"text-h6"}," Social Links ",-1),i("p",{class:"text-sm mb-0"}," Add Social Links ",-1)])),_:1,__:[25]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).twitter,"onUpdate:modelValue":a[15]||(a[15]=s=>l(r).twitter=s),placeholder:"https://twitter.com/abc",label:"Twitter"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).facebook,"onUpdate:modelValue":a[16]||(a[16]=s=>l(r).facebook=s),placeholder:"https://facebook.com/abc",label:"Facebook"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).googlePlus,"onUpdate:modelValue":a[17]||(a[17]=s=>l(r).googlePlus=s),placeholder:"https://plus.google.com/abc",label:"Google+"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).linkedIn,"onUpdate:modelValue":a[18]||(a[18]=s=>l(r).linkedIn=s),placeholder:"https://linkedin.com/abc",label:"LinkedIn"},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[i("div",Ce,[a[26]||(a[26]=i("h6",{class:"text-base font-weight-medium mb-2"}," Account ",-1)),i("p",ge,p(l(r).username),1),i("p",he,p(l(r).email),1),e(A,{class:"my-4"}),a[27]||(a[27]=i("h6",{class:"text-base font-weight-medium mb-2"}," Personal Info ",-1)),i("p",ye,p(l(r).firstName),1),i("p",Fe,p(l(r).lastName),1),i("p",ke,p(l(r).country),1),i("p",xe,p(l(r).language),1),e(A,{class:"my-4"}),a[28]||(a[28]=i("h6",{class:"text-base font-weight-medium mb-2"}," Address ",-1)),i("p",Se,p(l(r).address),1),i("p",Pe,p(l(r).landmark),1),i("p",Ie,p(l(r).pincode),1),i("p",Ae,p(l(r).city),1),e(A,{class:"my-4"}),a[29]||(a[29]=i("h6",{class:"text-base font-weight-medium mb-2"}," Social Links ",-1)),i("p",De,p(l(r).twitter),1),i("p",Le,p(l(r).facebook),1),i("p",We,p(l(r).googlePlus),1),i("p",Te,p(l(r).linkedIn),1)])]),_:1})]),_:1},8,["modelValue"]),i("div",Be,[e(w,{color:"secondary",variant:"outlined",disabled:l(u)===0,onClick:a[20]||(a[20]=s=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),a[30]||(a[30]=C(" Previous "))]),_:1,__:[30]},8,["disabled"]),V.length-1===l(u)?(k(),D(w,{key:0,color:"success","append-icon":"ri-check-line",onClick:L},{default:o(()=>a[31]||(a[31]=[C(" submit ")])),_:1,__:[31]})):(k(),D(w,{key:1,onClick:a[21]||(a[21]=s=>u.value++)},{default:o(()=>[a[32]||(a[32]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[32]}))])]),_:1})]),_:1})]),_:1})}}},ze={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},qe={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},Ue={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},Re={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},_e={class:"text-base"},je={class:"mb-1"},$e={class:"mb-1"},Ee={class:"mb-1"},He={class:"mb-1"},Ge={class:"mb-1"},Me={class:"mb-1"},Ke={class:"mb-1"},Ye={class:"mb-1"},Je={class:"mb-1"},Oe={class:"mb-1"},Qe={class:"mb-1"},Xe={class:"mb-1"},Ze={class:"mb-1"},el={class:"mb-1"},ll={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},ol={__name:"DemoFormWizardIconsValidation",setup(I){const V=[{title:"Account Details",icon:H},{title:"Personal Info",icon:M},{title:"Address",icon:G},{title:"Social Links",icon:K},{title:"Review & Submit",icon:Y}],u=f(0),b=f(!1),g=f(!1),r=f(!0),L=f(),q=f(),a=f(),W=f(),s=f({username:"",email:"",password:"",cPassword:""}),x=f({firstName:"",lastName:"",country:void 0,language:void 0}),S=f({twitter:"",facebook:"",googlePlus:"",linkedIn:""}),P=f({address:"",landmark:"",city:"",pincode:""}),J=()=>{var n;(n=L.value)==null||n.validate().then(t=>{t.valid?(u.value++,r.value=!0):r.value=!1})},O=()=>{var n;(n=q.value)==null||n.validate().then(t=>{t.valid?(u.value++,r.value=!0):r.value=!1})},Q=()=>{var n;(n=W.value)==null||n.validate().then(t=>{t.valid?(u.value++,r.value=!0):r.value=!1})},X=()=>{var n;(n=a.value)==null||n.validate().then(t=>{t.valid?(u.value++,r.value=!0):r.value=!1})};return(n,t)=>{const Z=j;return k(),D(E,null,{default:o(()=>[e(N,null,{default:o(()=>[e(Z,{"current-step":l(u),"onUpdate:currentStep":t[0]||(t[0]=m=>B(u)?u.value=m:null),items:V,"is-active-step-valid":l(r),align:"center"},null,8,["current-step","is-active-step-valid"])]),_:1}),e(A),e(N,null,{default:o(()=>[e($,{modelValue:l(u),"onUpdate:modelValue":t[23]||(t[23]=m=>B(u)?u.value=m:null),class:"disable-tab-transition"},{default:o(()=>[e(y,null,{default:o(()=>[e(l(T),{ref_key:"refAccountForm",ref:L,onSubmit:R(J,["prevent"])},{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>t[24]||(t[24]=[i("h6",{class:"text-h6"}," Account Details ",-1),i("p",{class:"text-sm mb-0"}," Enter your Account Details ",-1)])),_:1,__:[24]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(s).username,"onUpdate:modelValue":t[1]||(t[1]=m=>l(s).username=m),placeholder:"CarterLeonardo",rules:["requiredValidator"in n?n.requiredValidator:l(v)],label:"Username"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(s).email,"onUpdate:modelValue":t[2]||(t[2]=m=>l(s).email=m),placeholder:"carterleonardo@gmail.com",rules:["requiredValidator"in n?n.requiredValidator:l(v),"emailValidator"in n?n.emailValidator:l(ae)],label:"Email"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(s).password,"onUpdate:modelValue":t[3]||(t[3]=m=>l(s).password=m),placeholder:"············",label:"Password",rules:["requiredValidator"in n?n.requiredValidator:l(v),"passwordValidator"in n?n.passwordValidator:l(te)],type:l(b)?"text":"password",autocomplete:"password","append-inner-icon":l(b)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":t[4]||(t[4]=m=>b.value=!l(b))},null,8,["modelValue","rules","type","append-inner-icon"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(s).cPassword,"onUpdate:modelValue":t[5]||(t[5]=m=>l(s).cPassword=m),placeholder:"············",label:"Confirm Password",autocomplete:"confirm-password",rules:["requiredValidator"in n?n.requiredValidator:l(v),("confirmedValidator"in n?n.confirmedValidator:l(se))(l(s).cPassword,l(s).password)],type:l(g)?"text":"password","append-inner-icon":l(g)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":t[6]||(t[6]=m=>g.value=!l(g))},null,8,["modelValue","rules","type","append-inner-icon"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[i("div",ze,[e(w,{color:"secondary",variant:"outlined",disabled:""},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),t[25]||(t[25]=C(" Previous "))]),_:1,__:[25]}),e(w,{type:"submit"},{default:o(()=>[t[26]||(t[26]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[26]})])]),_:1})]),_:1})]),_:1},512)]),_:1}),e(y,null,{default:o(()=>[e(l(T),{ref_key:"refPersonalForm",ref:q,onSubmit:R(O,["prevent"])},{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>t[27]||(t[27]=[i("h6",{class:"text-h6"}," Personal Info ",-1),i("p",{class:"text-sm mb-0"}," Setup Information ",-1)])),_:1,__:[27]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(x).firstName,"onUpdate:modelValue":t[7]||(t[7]=m=>l(x).firstName=m),label:"First Name",rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"Leonard"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(x).lastName,"onUpdate:modelValue":t[8]||(t[8]=m=>l(x).lastName=m),label:"Last Name",rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"Carter"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(x).country,"onUpdate:modelValue":t[9]||(t[9]=m=>l(x).country=m),label:"Country",rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"Select Country",items:["UK","USA","Canada","Australia","Germany"]},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(x).language,"onUpdate:modelValue":t[10]||(t[10]=m=>l(x).language=m),label:"Language",rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"Select Language",items:["English","Spanish","French","Russian","German"]},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[i("div",qe,[e(w,{color:"secondary",variant:"outlined",onClick:t[11]||(t[11]=m=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),t[28]||(t[28]=C(" Previous "))]),_:1,__:[28]}),e(w,{type:"submit"},{default:o(()=>[t[29]||(t[29]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[29]})])]),_:1})]),_:1})]),_:1},512)]),_:1}),e(y,null,{default:o(()=>[e(l(T),{ref_key:"refAddressForm",ref:W,onSubmit:R(Q,["prevent"])},{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>t[30]||(t[30]=[i("h6",{class:"text-h6"}," Address ",-1),i("p",{class:"text-sm mb-0"}," Enter Your Address ",-1)])),_:1,__:[30]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(P).address,"onUpdate:modelValue":t[12]||(t[12]=m=>l(P).address=m),rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"98 Borough bridge Road, Birmingham",label:"Address"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(P).landmark,"onUpdate:modelValue":t[13]||(t[13]=m=>l(P).landmark=m),rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"Borough bridge",label:"Landmark"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(P).pincode,"onUpdate:modelValue":t[14]||(t[14]=m=>l(P).pincode=m),rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"658921",label:"Pincode",type:"number"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(P).city,"onUpdate:modelValue":t[15]||(t[15]=m=>l(P).city=m),rules:["requiredValidator"in n?n.requiredValidator:l(v)],placeholder:"New York",label:"City"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[i("div",Ue,[e(w,{color:"secondary",variant:"outlined",onClick:t[16]||(t[16]=m=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),t[31]||(t[31]=C(" Previous "))]),_:1,__:[31]}),e(w,{type:"submit"},{default:o(()=>[t[32]||(t[32]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[32]})])]),_:1})]),_:1})]),_:1},512)]),_:1}),e(y,null,{default:o(()=>[e(l(T),{ref_key:"refSocialLinkForm",ref:a,onSubmit:R(X,["prevent"])},{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>t[33]||(t[33]=[i("h6",{class:"text-h6"}," Social Links ",-1),i("p",{class:"text-sm mb-0"}," Add Social Links ",-1)])),_:1,__:[33]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(S).twitter,"onUpdate:modelValue":t[17]||(t[17]=m=>l(S).twitter=m),placeholder:"https://twitter.com/abc",rules:["requiredValidator"in n?n.requiredValidator:l(v),"urlValidator"in n?n.urlValidator:l(_)],label:"Twitter"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(S).facebook,"onUpdate:modelValue":t[18]||(t[18]=m=>l(S).facebook=m),placeholder:"https://facebook.com/abc",rules:["requiredValidator"in n?n.requiredValidator:l(v),"urlValidator"in n?n.urlValidator:l(_)],label:"Facebook"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(S).googlePlus,"onUpdate:modelValue":t[19]||(t[19]=m=>l(S).googlePlus=m),placeholder:"https://plus.google.com/abc",rules:["requiredValidator"in n?n.requiredValidator:l(v),"urlValidator"in n?n.urlValidator:l(_)],label:"Google+"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(S).linkedIn,"onUpdate:modelValue":t[20]||(t[20]=m=>l(S).linkedIn=m),placeholder:"https://linkedin.com/abc",rules:["requiredValidator"in n?n.requiredValidator:l(v),"urlValidator"in n?n.urlValidator:l(_)],label:"LinkedIn"},null,8,["modelValue","rules"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[i("div",Re,[e(w,{color:"secondary",variant:"outlined",onClick:t[21]||(t[21]=m=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),t[34]||(t[34]=C(" Previous "))]),_:1,__:[34]}),e(w,{type:"submit"},{default:o(()=>[t[35]||(t[35]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[35]})])]),_:1})]),_:1})]),_:1},512)]),_:1}),e(y,null,{default:o(()=>[i("div",_e,[t[36]||(t[36]=i("h6",{class:"text-base font-weight-medium mb-2"}," Account ",-1)),i("p",je,p(l(s).username),1),i("p",$e,p(l(s).email),1),e(A,{class:"my-4"}),t[37]||(t[37]=i("h6",{class:"text-base font-weight-medium mb-2"}," Personal Info ",-1)),i("p",Ee,p(l(x).firstName),1),i("p",He,p(l(x).lastName),1),i("p",Ge,p(l(x).country),1),i("p",Me,p(l(x).language),1),e(A,{class:"my-4"}),t[38]||(t[38]=i("h6",{class:"text-base font-weight-medium mb-2"}," Address ",-1)),i("p",Ke,p(l(P).address),1),i("p",Ye,p(l(P).landmark),1),i("p",Je,p(l(P).pincode),1),i("p",Oe,p(l(P).city),1),e(A,{class:"my-4"}),t[39]||(t[39]=i("h6",{class:"text-base font-weight-medium mb-2"}," Social Links ",-1)),i("p",Qe,p(l(S).twitter),1),i("p",Xe,p(l(S).facebook),1),i("p",Ze,p(l(S).googlePlus),1),i("p",el,p(l(S).linkedIn),1)]),i("div",null,[i("div",ll,[e(w,{color:"secondary",variant:"outlined",onClick:t[22]||(t[22]=m=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),t[40]||(t[40]=C(" Previous "))]),_:1,__:[40]}),e(w,{color:"success",type:"submit"},{default:o(()=>[t[41]||(t[41]=C(" Submit ")),e(h,{icon:"ri-check-line",end:"",class:"flip-in-rtl"})]),_:1,__:[41]})])])]),_:1})]),_:1},8,["modelValue"])]),_:1})]),_:1})}}},al={class:"d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"},tl={__name:"DemoFormWizardIconsVertical",setup(I){const V=[{title:"Account Details",icon:"ri-article-line"},{title:"Personal Info",icon:"ri-user-line"},{title:"Social Links",icon:"ri-links-line"}],u=f(0),b=f(!1),g=f(!1),r=f({username:"",email:"",password:"",cPassword:"",firstName:"",lastName:"",country:void 0,language:void 0,twitter:"",facebook:"",googlePlus:"",linkedIn:""}),L=()=>{console.log(r.value)};return(q,a)=>{const W=j;return k(),D(E,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12",md:"4",class:oe(q.$vuetify.display.smAndDown?"border-b":"border-e")},{default:o(()=>[e(N,null,{default:o(()=>[e(W,{"current-step":l(u),"onUpdate:currentStep":a[0]||(a[0]=s=>B(u)?u.value=s:null),direction:"vertical",items:V,"icon-size":"24",class:"stepper-icon-step-bg"},null,8,["current-step"])]),_:1})]),_:1},8,["class"]),e(d,{cols:"12",md:"8"},{default:o(()=>[e(N,null,{default:o(()=>[e(T,null,{default:o(()=>[e($,{modelValue:l(u),"onUpdate:modelValue":a[15]||(a[15]=s=>B(u)?u.value=s:null),class:"disable-tab-transition"},{default:o(()=>[e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[18]||(a[18]=[i("h6",{class:"text-h6"}," Account Details ",-1),i("p",{class:"text-sm mb-0"}," Enter your Account Details ",-1)])),_:1,__:[18]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).username,"onUpdate:modelValue":a[1]||(a[1]=s=>l(r).username=s),placeholder:"CarterLeonardo",label:"Username"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).email,"onUpdate:modelValue":a[2]||(a[2]=s=>l(r).email=s),placeholder:"carterleonardo@gmail.com",label:"Email"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).password,"onUpdate:modelValue":a[3]||(a[3]=s=>l(r).password=s),label:"Password",placeholder:"············",type:l(b)?"text":"password",autocomplete:"password","append-inner-icon":l(b)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":a[4]||(a[4]=s=>b.value=!l(b))},null,8,["modelValue","type","append-inner-icon"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).cPassword,"onUpdate:modelValue":a[5]||(a[5]=s=>l(r).cPassword=s),label:"Confirm Password",autocomplete:"confirm-password",placeholder:"············",type:l(g)?"text":"password","append-inner-icon":l(g)?"ri-eye-off-line":"ri-eye-line","onClick:appendInner":a[6]||(a[6]=s=>g.value=!l(g))},null,8,["modelValue","type","append-inner-icon"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[19]||(a[19]=[i("h6",{class:"text-h6"}," Personal Info ",-1),i("p",{class:"text-sm mb-0"}," Setup Information ",-1)])),_:1,__:[19]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).firstName,"onUpdate:modelValue":a[7]||(a[7]=s=>l(r).firstName=s),label:"First Name",placeholder:"Leonard"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).lastName,"onUpdate:modelValue":a[8]||(a[8]=s=>l(r).lastName=s),label:"Last Name",placeholder:"Carter"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(r).country,"onUpdate:modelValue":a[9]||(a[9]=s=>l(r).country=s),label:"Country",placeholder:"Select Country",items:["UK","USA","Canada","Australia","Germany"]},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(z,{modelValue:l(r).language,"onUpdate:modelValue":a[10]||(a[10]=s=>l(r).language=s),label:"Language",placeholder:"Select Language",items:["English","Spanish","French","Russian","German"]},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(y,null,{default:o(()=>[e(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>a[20]||(a[20]=[i("h6",{class:"text-h6"}," Social Links ",-1),i("p",{class:"text-sm mb-0"}," Add Social Links ",-1)])),_:1,__:[20]}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).twitter,"onUpdate:modelValue":a[11]||(a[11]=s=>l(r).twitter=s),placeholder:"https://twitter.com/abc",label:"Twitter"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).facebook,"onUpdate:modelValue":a[12]||(a[12]=s=>l(r).facebook=s),placeholder:"https://facebook.com/abc",label:"Facebook"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).googlePlus,"onUpdate:modelValue":a[13]||(a[13]=s=>l(r).googlePlus=s),placeholder:"https://plus.google.com/abc",label:"Google+"},null,8,["modelValue"])]),_:1}),e(d,{cols:"12",md:"6"},{default:o(()=>[e(c,{modelValue:l(r).linkedIn,"onUpdate:modelValue":a[14]||(a[14]=s=>l(r).linkedIn=s),placeholder:"https://linkedin.com/abc",label:"LinkedIn"},null,8,["modelValue"])]),_:1})]),_:1})]),_:1})]),_:1},8,["modelValue"]),i("div",al,[e(w,{color:"secondary",variant:"outlined",disabled:l(u)===0,onClick:a[16]||(a[16]=s=>u.value--)},{default:o(()=>[e(h,{icon:"ri-arrow-left-line",start:"",class:"flip-in-rtl"}),a[21]||(a[21]=C(" Previous "))]),_:1,__:[21]},8,["disabled"]),V.length-1===l(u)?(k(),D(w,{key:0,color:"success","append-icon":"ri-check-line",onClick:L},{default:o(()=>a[22]||(a[22]=[C(" submit ")])),_:1,__:[22]})):(k(),D(w,{key:1,onClick:a[17]||(a[17]=s=>u.value++)},{default:o(()=>[a[23]||(a[23]=C(" Next ")),e(h,{icon:"ri-arrow-right-line",end:"",class:"flip-in-rtl"})]),_:1,__:[23]}))])]),_:1})]),_:1})]),_:1})]),_:1})]),_:1})}}},ql={__name:"form-wizard-icons",setup(I){return(V,u)=>{const b=ee;return k(),D(F,null,{default:o(()=>[e(d,{cols:"12"},{default:o(()=>[e(b,{variant:"outlined",title:"Basic",code:l(re)},{default:o(()=>[e(Ne)]),_:1},8,["code"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[e(b,{variant:"outlined",title:"Validation",code:l(ie)},{default:o(()=>[e(ol)]),_:1},8,["code"])]),_:1}),e(d,{cols:"12"},{default:o(()=>[e(b,{variant:"outlined",title:"Vertical",code:l(de)},{default:o(()=>[e(tl)]),_:1},8,["code"])]),_:1})]),_:1})}}};export{ql as default};
