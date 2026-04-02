<template>
  <div class="payment-wrapper">
    <!-- Spinner general -->
    <a-spin :spinning="loading" tip="Processing...">
      <div id="myPaymentForm" class="payment-form">
        <div class="kr-embedded">
          <div class="kr-pan"></div>
          <div class="kr-expiry"></div>
          <div class="kr-security-code"></div>
          <div class="kr-form-error"></div>
          <button class="kr-payment-button"></button>
        </div>
      </div>
    </a-spin>

    <!-- Modal con mensaje de éxito o error + resumen -->
    <a-modal
      v-model:open="modalVisible"
      title="Payment Confirmation"
      :footer="null"
      :zIndex="9999"
      :maskClosable="false"
    >
      <a-result :status="resultStatus" :title="resultTitle" class="payment-result">
        <template #subTitle>
          <span>{{ message }}</span>
        </template>
      </a-result>

      <a-card v-if="orderStatus" size="small" class="payment-summary">
        <a-descriptions :column="1" size="small">
          <a-descriptions-item label="Email">
            {{ PaidData.email }}
          </a-descriptions-item>
          <a-descriptions-item label="Order ID">
            {{ PaidData.orderId }}
          </a-descriptions-item>
          <a-descriptions-item label="Order Total">
            ${{ PaidData.amount }}
          </a-descriptions-item>
          <a-descriptions-item label="Status">
            <a-tag color="green">{{ PaidData.orderStatus }}</a-tag>
          </a-descriptions-item>
        </a-descriptions>
      </a-card>

      <div class="payment-actions">
        <a-button type="primary" href="/" block>
          Go Home
        </a-button>
      </div>
    </a-modal>
  </div>
</template>


<script setup>
import { ref, onMounted, toRefs, computed } from 'vue'
import KRGlue from '@lyracom/embedded-form-glue'
import axios from 'axios'

// Props
const props = defineProps({
  amount: [Number, String],
  name: String,
  email: String,
  subject: String,
  title: String,
  publicKey: String,
  datapay: Object,
  devmode:String
})
const { datapay, subject, name, email, title, amount, devmode, publicKey } = toRefs(props)

// States
const message = ref('')
const modalVisible = ref(false)
const orderStatus = ref(false)
const loading = ref(false)
const settings = (window.PEARPAY && window.PEARPAY.settings) ? window.PEARPAY.settings : {}
const successMessage = settings.confirm_message || 'Payment successful!'
const resultStatus = computed(() => (orderStatus.value ? 'success' : 'error'))
const resultTitle = computed(() => (orderStatus.value ? successMessage : 'Payment not completed'))

const formdata = ref({
  totalpay: amount.value,
  name: name.value,
  email: email.value,
  subject: subject.value,
  title: title.value,
  typePay: 'Izi pay'
})

const PaidData = ref({
  orderStatus: null,
  name: null,
  email: null,
  orderId: null,
  amount: null,
  date: null
})

const hookajax = ref()

//*************************** Validation Page IZipay */
const validatePayment = async (paymentData) => {
  loading.value = true
  formdata.value.typePay = 'IZIPAY'

  const DBS = new FormData()
  DBS.append('paydata', JSON.stringify(paymentData))
  DBS.append('peardata', JSON.stringify(formdata.value))
  DBS.append('nonce', hookajax.value.nonce)
  DBS.append('action', hookajax.value.action_validate)

  const isPaid = paymentData?.clientAnswer?.orderStatus === 'PAID'
  if (isPaid) {
    orderStatus.value = true
    PaidData.value = {
      orderStatus: paymentData.clientAnswer.orderStatus,
      email: paymentData.clientAnswer.customer.email,
      orderId: paymentData.clientAnswer.orderDetails.orderId,
      amount: paymentData.clientAnswer.orderDetails.orderTotalAmount / 100
    }
    message.value = successMessage
    modalVisible.value = true
  } else {
    message.value = 'Payment not completed or validation failed.'
    modalVisible.value = true
  }

  try {
    const response = await axios.post(hookajax.value.ajax_url, DBS)
    console.log(response.data.success)
    if (response.data.success) {
      message.value = successMessage;
      modalVisible.value = true
    }
    else{
      message.value = 'There was an error validating the payment.'
      modalVisible.value = true
    }
  } catch (error) {
    console.error(error)
    message.value = 'There was an error validating the payment.'
    modalVisible.value = true
  } finally {
    loading.value = false
  }

  return false // prevenir redirección
}

/************************************  CARGAR FORMULARIO  */
onMounted(() => {
  loading.value = true
  hookajax.value = PEARPAY
  const endpoint = 'https://static.micuentaweb.pe'
  
  const pubKey = (publicKey.value || '').trim()
  if (!pubKey) {
    loading.value = false
    message.value = 'Public key not configured. Please check PEARPAY_IZIPAY_ENV and public key settings.'
    modalVisible.value = true
    return
  }

  
  let formToken = ''

  const DB = new FormData()
  DB.append('peardata', JSON.stringify(formdata.value))
  DB.append('nonce', hookajax.value.nonce)
  DB.append('action', hookajax.value.action_token)

  axios
    .post(hookajax.value.ajax_url, DB)
    .then((resp) => {
      formToken = resp.data.data.answer.formToken
      return KRGlue.loadLibrary(endpoint, pubKey)
    })
    .then(({ KR }) =>
      KR.setFormConfig({
        formToken: formToken,
        'kr-language': 'en-US'
      })
    )
    .then(({ KR }) => KR.onSubmit(validatePayment))
    .then(({ KR }) => KR.renderElements('#myPaymentForm'))
    .then(({ KR, result }) => KR.showForm(result.formId))
    .catch((error) => {
      console.error(error)
      message.value = 'Error initializing payment form.'
      modalVisible.value = true
    })
    .finally(() => {
      loading.value = false
    })
})
</script>

<style scoped>
.payment-wrapper {
  max-width: 600px;
  margin: 0 auto;
  padding: 1rem;
}

.payment-form {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  margin-bottom: 1.5rem;
}

.payment-summary {
  margin-top: 8px;
  border-radius: 10px;
}

.payment-result {
  margin-bottom: 12px;
}

.payment-actions {
  margin-top: 16px;
}
</style>


