<script setup>
import { ref, computed,toRefs } from 'vue'
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
  devmode:String
})
const { method, amount,title,type,lang,devmode } = toRefs(props)
const IGV_RATE = 0.055
const isModalVisible = ref(false)
const showIzipay = ref(false)

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
  return (amount + igv).toFixed(2)
})

const validateFields = () => {
  let valid = true

  if (!name.value.trim()) {
    nameError.value = 'Name is required'
    valid = false
  } else {
    nameError.value = ''
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value.trim()) {
    emailError.value = 'Email is required'
    valid = false
  } else if (!emailPattern.test(email.value)) {
    emailError.value = 'Email is invalid'
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
    <a-button type="primary" block class="btn-pay" size="large" :disabled="amount <= 0" @click="handleClick">
      Pay ${{ total }} with {{ method.name }}
    </a-button>

    <!-- Modal con formulario -->
    <a-modal v-model:open="isModalVisible" title="Complete your details" :footer="null" :maskClosable="false"
      @cancel="handleCancel">
      <a-form layout="vertical">
        <a-form-item label="Full Name" :validateStatus="nameError ? 'error' : ''" :help="nameError">
          <a-input v-model:value="name" placeholder="Enter your name" />
        </a-form-item>

        <a-form-item label="subject" :validateStatus="nameError ? 'error' : ''" :help="nameError">
          <a-input v-model:value="subject" placeholder="Enter  subject" />
        </a-form-item>

        <a-form-item label="Email" :validateStatus="emailError ? 'error' : ''" :help="emailError">
          <a-input v-model:value="email" placeholder="Enter your email" />
        </a-form-item>

        <a-form-item style="text-align: center;">
          <button class="btn-pay-izipay" style="padding: 5px"  @click="handleContinue">
            Continue to payment
          </button>
        </a-form-item>
      </a-form>

      <FromIzipay v-if="showIzipay" :amount="total" :name="name" :email="email" :subject="subject"
        :title="props.title" :devmode="devmode" />
    </a-modal>
  </div>
</template>
<style>
.btn-pay {
  background-color: rgb(29 35 39);
  color: #FFC107;
  border-radius: 02px !important;
  font-weight: 600;
  margin-bottom: 10px;
}
.btn-pay:not(:disabled):hover {
  background-color: #383838;
  color: #FFC107 !important;
  border-radius: 02px !important;
  font-weight: 600;
}
.btn-pay-izipay{
    background-color: #3F51B5;
    color: #ffffff;
    border-radius: 02px !important;
    font-weight: 600;
    width: 100%;
    border: none;
    font-weight: 500;
    font-size: 1.05rem;
}
</style>
