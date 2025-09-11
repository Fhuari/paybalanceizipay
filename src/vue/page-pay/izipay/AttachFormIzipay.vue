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
    >
      <p class="text-center mb-3">{{ message }}</p>

      <!-- Mostrar resumen dentro del modal -->
      <div v-if="orderStatus" class="payment-summary">
        <table class="table table-borderless">
          <tbody>
            <tr>
              <td class="fw-bold">Email:</td>
              <td>{{ PaidData.email }}</td>
            </tr>
            <tr>
              <td class="fw-bold">Order ID:</td>
              <td>{{ PaidData.orderId }}</td>
            </tr>
            <tr>
              <td class="fw-bold">Order Total:</td>
              <td>${{ PaidData.amount }}</td>
            </tr>
            <tr>
              <td class="fw-bold">Status:</td>
              <td class="text-success fw-bold">{{ PaidData.orderStatus }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Botón para ir a la página de inicio -->
      <div class="text-center mt-4">
        <a href="/" class="btn btn-success rounded-0 text-white me-2">Go Home</a>
        <!-- <button class="btn btn-secondary rounded-0" @click="modalVisible = false">Close</button> -->
      </div>
    </a-modal>
  </div>
</template>


<script setup>
import { ref, onMounted, toRefs } from 'vue'
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
const { datapay, subject, name, email, title, amount,devmode} = toRefs(props)

// States
const message = ref('')
const modalVisible = ref(false)
const orderStatus = ref(false)
const loading = ref(false)

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
const validatePayment = (paymentData) => {
  console.log(paymentData)
  loading.value = true
  formdata.value.typePay = 'IZIPAY'

  const DBS = new FormData()
  DBS.append('paydata', JSON.stringify(paymentData))
  DBS.append('peardata', JSON.stringify(formdata.value))
  DBS.append('nonce', hookajax.value.nonce)
  DBS.append('action', hookajax.value.action_validate)

  axios
    .post(hookajax.value.ajax_url, DBS)
    .then((response) => {
      console.log(response.data)
      if (response.status === 200) {
        message.value = 'Payment successful!'
        modalVisible.value = true
      } else {
        message.value = 'There was an error validating the payment.'
        modalVisible.value = true
      }
    })
    .catch((error) => {
      console.error(error)
      message.value = 'There was an error validating the payment.'
      modalVisible.value = true
    })
    .finally(() => {
      loading.value = false
    })

  if (paymentData.clientAnswer.orderStatus === 'PAID') {
    orderStatus.value = true
    PaidData.value = {
      orderStatus: paymentData.clientAnswer.orderStatus,
      email: paymentData.clientAnswer.customer.email,
      orderId: paymentData.clientAnswer.orderDetails.orderId,
      amount: paymentData.clientAnswer.orderDetails.orderTotalAmount / 100
    }
  }

  return false // prevenir redirección
}

/************************************  CARGAR FORMULARIO  */
onMounted(() => {
  loading.value = true
  hookajax.value = PEARPAY
  console.log(hookajax.value) 

  const endpoint = 'https://static.micuentaweb.pe'
  
  const pubKey =devmode.value==='dev'?'93641145:testpublickey_rdVNd0OLHoCwgSjZMRhwrDA2i3JpNToT2evDswak87pRz':'93641145:publickey_lddMVVe8SvNwDlv6xNoYohGMJ3eZOha7TU9L53f3VGkH5';

  
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
  margin: auto;
  padding: 1rem;
}

.payment-form {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.payment-summary {
  max-width: 600px;
  margin: auto;
  border-radius: 10px;
}

.card-header {
  background-color: #28a745;
  font-size: 1.5rem;
  font-weight: bold;
}
</style>
