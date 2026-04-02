<script setup>
import { ref, computed, toRefs } from 'vue'
import FromIzipay from '../izipay/AttachFormIzipay.vue'

const props = defineProps({
  method: Object,
  balance: Boolean,
  amount: {
    type: Number,
    default: 0
  },
  title: String,
  type: String,
  lang: String,
  devmode: String
})
const { method, amount, title, type, lang, devmode } = toRefs(props)
const IGV_RATE = 0
const isModalVisible = ref(false)
const showIzipay = ref(false)
const settings = (window.PEARPAY && window.PEARPAY.settings) ? window.PEARPAY.settings : {}
const publicKey = settings.izipay_public_key || ''
const themeColor = settings.theme_color || '#1d2327'
const commissionType = settings.commission_type || 'percent'
const commissionValue = Number(String(settings.commission_value || 0).replace(',', '.'))
const currency = settings.currency || 'USD'
const currencySymbol = currency === 'PEN' ? 'S/' : '$'
const isEn = computed(() => (lang?.value || 'es').toLowerCase().startsWith('en'))
const t = computed(() => (isEn.value ? {
  payNow: 'Pay now',
  modalTitle: 'Secure payment',
  modalDesc: 'Please confirm your information to continue.',
  totalTitle: 'Trip total',
  alertSecure: 'Your payment is processed securely by Izipay.',
  nameLabel: 'Full name (booking holder)',
  namePlaceholder: 'E.g. John Smith',
  subjectLabel: 'Tour / Travel or Balance Payment',
  subjectPlaceholder: 'E.g. City Tour Lima - Booking 124',
  emailLabel: 'Email',
  emailPlaceholder: 'E.g. traveler@email.com',
  continue: 'Continue to payment',
  nameRequired: 'Name is required',
  emailRequired: 'Email is required',
  emailInvalid: 'Email is invalid',
} : {
  payNow: 'Pagar',
  modalTitle: 'Pago seguro',
  modalDesc: 'Confirma tus datos para continuar.',
  totalTitle: 'Total',
  alertSecure: 'Tu pago se procesa de forma segura con Izipay.',
  nameLabel: 'Nombre completo ',
  namePlaceholder: 'Ej. Juan Perez',
  subjectLabel: 'Pago del tour/ o del saldo',
  subjectPlaceholder: 'Ej. City Tour Lima ',
  emailLabel: 'Correo Electronico',
  emailPlaceholder: 'Ej. viajero@correo.com',
  continue: 'Continuar al pago',
  nameRequired: 'El nombre es obligatorio',
  emailRequired: 'El correo es obligatorio',
  emailInvalid: 'El correo no es valido',
}))

const name = ref('')
const email = ref('')
const subject = ref('');

const nameError = ref('')
const emailError = ref('')

const total = computed(() => {
  let amount = Number(props.amount) || 0
  if (props.balance || amount <= 0) {
    return amount.toFixed(2)
  }
  const igv = amount * IGV_RATE
  const baseTotal = amount + igv
  const withCommission = applyCommission(baseTotal)
  return withCommission.toFixed(2)
})

const applyCommission = (baseAmount) => {
  if (!commissionValue || commissionValue <= 0) {
    return baseAmount
  }
  if (commissionType === 'fixed') {
    return baseAmount + commissionValue
  }
  // Percent: 1 -> 1%, 4.5 -> 4.5%
  return baseAmount + (baseAmount * (commissionValue / 100))
}

const validateFields = () => {
  let valid = true

  if (!name.value.trim()) {
    nameError.value = t.value.nameRequired
    valid = false
  } else {
    nameError.value = ''
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value.trim()) {
    emailError.value = t.value.emailRequired
    valid = false
  } else if (!emailPattern.test(email.value)) {
    emailError.value = t.value.emailInvalid
    valid = false
  } else {
    emailError.value = ''
  }

  return valid
}

const handleClick = () => {
  isModalVisible.value = true
}

const handleContinue = () => {
  if (validateFields()) {
    showIzipay.value = true
  }
}

const handleCancel = () => {
  isModalVisible.value = false
  showIzipay.value = false
  name.value = ''
  email.value = ''
  subject.value = ''
  nameError.value = ''
  emailError.value = ''
}
</script>

<template>
  <div>
    <!-- Botón de pago (100% Ant Design) -->
    <a-button type="primary" block size="large" :disabled="amount <= 0" @click="handleClick">

      {{ t.payNow }} {{ currencySymbol }} {{ total }} {{ currency }}
    </a-button>

    <!-- Modal con formulario -->
    <a-modal v-model:open="isModalVisible" :footer="null" :maskClosable="false" @cancel="handleCancel" :zIndex="9999">
      <template #title>
        {{ t.modalTitle }}
      </template>

      <a-typography-paragraph type="secondary" style="margin-bottom: 12px;">
        {{ t.modalDesc }}
      </a-typography-paragraph>

      <a-card size="small" style="margin-bottom: 12px;">
        <a-statistic :title="t.totalTitle" :value="`${currencySymbol} ${total} ${currency}`"
          :value-style="{ fontWeight: 700 }" />
      </a-card>

      <a-alert type="info" show-icon :message="t.alertSecure"
        style="margin-bottom: 16px;" />

      <a-form v-if="!showIzipay" layout="vertical">
        <a-form-item :label="t.nameLabel" :validateStatus="nameError ? 'error' : ''" :help="nameError">
          <a-input v-model:value="name" :placeholder="t.namePlaceholder" />
        </a-form-item>

        <a-form-item :label="t.subjectLabel" :validateStatus="nameError ? 'error' : ''" :help="nameError">
          <a-input v-model:value="subject" :placeholder="t.subjectPlaceholder" />
        </a-form-item>

        <a-form-item :label="t.emailLabel" :validateStatus="emailError ? 'error' : ''" :help="emailError">
          <a-input v-model:value="email" :placeholder="t.emailPlaceholder" />
        </a-form-item>

        <a-form-item>
          <a-button type="primary" size="large" block @click="handleContinue">

            {{ t.continue }}
          </a-button>
        </a-form-item>
      </a-form>
      <FromIzipay
        v-if="showIzipay"
        :amount="total"
        :name="name"
        :email="email"
        :subject="subject"
        :title="props.title"
        :devmode="devmode"
        :publicKey="publicKey"
      />
    </a-modal>
  </div>
</template>
