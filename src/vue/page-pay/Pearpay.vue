<script setup>
import { ref, toRefs, watch } from 'vue'
import {
  HomeOutlined,
  ShoppingOutlined,
  FlagOutlined,
  DollarOutlined,
  CreditCardOutlined,
} from '@ant-design/icons-vue'

import PayPaypal from './components/PayPaypal.vue'
import PriceBreakdown from './components/PayBreakDow.vue'
import PaymentButton from './components/PayIzipay.vue'
import TranslateText from './components/TranslateText.vue'

const props = defineProps({
  lang: String,
  devmode:String
})
const { lang,devmode } = toRefs(props)

const selectedPaymentMethod = ref('izipay')
const balanceAmount = ref(null)
const IGV_RATE = 0.055

const paymentMethods = [
  {
    id: 'izipay',
    name: 'Card',
    icon: CreditCardOutlined,
    color: '#1677ff'
  }
]

/** Travelers counters */
const numTravelers = ref({
  room: 1,
  dayTour: 1,
  incaTrail: 1,
})

/** Configuración dinámica de las tarjetas */
const depositCards = [
  // {
  //   id: 'room',
  //   icon: HomeOutlined,
  //   title: { en: 'Room Deposit', es: 'Depósito de la habitación' },
  //   subtitle: {
  //     en: 'Reserve your room with a $20.00 deposit per night',
  //     es: 'Reserva tu habitación con un depósito de $20.00 por noche'
  //   },
  //   label: { en: 'Per night', es: 'Por noche' },
  //   price: 20,
  //   showPaypal: false
  // },
  // {
  //   id: 'dayTour',
  //   icon: ShoppingOutlined,
  //   title: { en: 'Day Trip Deposit', es: 'Depósito para tours de un día' },
  //   subtitle: {
  //     en: 'Reserve your tour with a $50.00 deposit per group',
  //     es: 'Reserva tu tour con un depósito de $50.00 por grupo'
  //   },
  //   label: { en: 'Number of Travelers', es: 'Número de viajeros' },
  //   price: 50,
  //   showPaypal: false
  // },
  // {
  //   id: 'incaTrail',
  //   icon: FlagOutlined,
  //   title: {
  //     en: 'Deposit for any Tour & trek to Machu Picchu',
  //     es: 'Depósito para cualquier Tour y caminata a Machu Picchu'
  //   },
  //   subtitle: {
  //     en: 'Reserve your trips with a $200.00 deposit per traveler',
  //     es: 'Reserva tus viajes con un depósito de $200.00 por viajero'
  //   },
  //   label: { en: 'Number of Travelers', es: 'Número de viajeros' },
  //   price: 200,
  //   showPaypal: true
  // }
]

/** lógica */
const calculateTotal = (amount) => {
  const igv = amount * IGV_RATE
  const total = amount + igv
  return { base: amount, igv: parseFloat(igv.toFixed(2)), total: parseFloat(total.toFixed(2)) }
}

const handlePayment = async (amount, type, method) => {
  const { total } = calculateTotal(amount)
  setTimeout(() => {
    alert(`Payment of $${total} (including 5.5% IGV) for ${type} via ${method} would be processed here.`)
  }, 2000)
}

const decreaseTraveler = (id) => {
  if (numTravelers.value[id] > 1) numTravelers.value[id]--
}
const increaseTraveler = (id) => {
  numTravelers.value[id]++
}
watch(numTravelers, (val) => {
  for (const key in val) {
    if (!val[key] || val[key] < 1) numTravelers.value[key] = 1
  }
}, { deep: true })
</script>

<template>
  <div class="pear-row">
    <!-- Tarjetas dinámicas -->
    <div class=" pear-col"
      v-for="card in depositCards"
      :key="card.id"
     
    >
      <a-card  :bordered="true">
        <a-space align="start">
          <div class="icon-box">
            <component :is="card.icon" style="font-size: 24px; color:#1677ff" />
          </div>
          <div>
            <a-typography-title :level="5" class="mb-1">
              <TranslateText :lang="lang" :en="card.title.en" :es="card.title.es" />
            </a-typography-title>
            <a-typography-text type="secondary">
              <TranslateText :lang="lang" :en="card.subtitle.en" :es="card.subtitle.es" />
            </a-typography-text>
          </div>
        </a-space>

        <div style="margin-top: 16px;">
          <a-typography-text strong>
            <TranslateText :lang="lang" :en="card.label.en" :es="card.label.es" />
          </a-typography-text>

          <a-space style="margin-top: 8px;">
            <a-button @click="decreaseTraveler(card.id)" size="small">-</a-button>
            <a-input-number
              v-model:value="numTravelers[card.id]"
              :min="1"
              size="small"
              style="width: 70px; text-align: center;"
              disabled
            />
            <a-button @click="increaseTraveler(card.id)" size="small">+</a-button>
          </a-space>
        </div>

        <div style="margin-top: 16px; text-align: right;">
          <PriceBreakdown :amount="card.price * numTravelers[card.id]" />
        </div>

        <div style="margin-top: 16px;">
          <PaymentButton
            v-for="method in paymentMethods"
            :key="method.id"
            :method="method"
            :amount="card.price * numTravelers[card.id]"
            :type="card.title.en"
            @payment="handlePayment"
          />
        </div>

        <PayPaypal
          v-if="card.showPaypal"
          :amount="card.price * numTravelers[card.id]"
          title="Trips to Machu Picchu Deposit"
          containerId="pay3"
        />
      </a-card>
    </div>

    <!-- Balance Payment -->
    <div class="pear-col">
      <a-card  :bordered="true">
        <a-space align="start">
          <div class="icon-box">
            <DollarOutlined style="font-size: 24px; color:#52c41a" />
          </div>
          <div>
            <a-typography-title :level="5" class="mb-1">
              <TranslateText :lang="lang" en="Pay your balance or make a deposit" es="Pague su saldo o realice un depósito" />
            </a-typography-title>
            <a-typography-text type="secondary">
              <TranslateText :lang="lang" en="Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay." es="Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar." />
            </a-typography-text>
          </div>
        </a-space>

        <div style="margin-top: 16px;">
          <a-form layout="vertical">
            <a-form-item>
              <template #label>
                <a-typography-text strong type="secondary">
                  <TranslateText :lang="lang" en="Enter the payment amount" es="Introduzca el importe del pago" />
                </a-typography-text>
              </template>
              <a-input-number
                v-model:value="balanceAmount"
                :min="0.01"
                :step="0.01"
                :precision="2"
                placeholder="0.00"
                size="large"
                style="width: 100%;"
                addon-before="$"
              />
            </a-form-item>
          </a-form>
        </div>

        <PaymentButton
          v-for="method in paymentMethods"
          :key="`balance-${method.id}`"
          v-show="selectedPaymentMethod === method.id"
          :method="method"
          :amount="balanceAmount"
          :devmode="devmode"
          type="Balance Payment"
          @payment="handlePayment"
          :balance="true"
        />
      </a-card>
    </div>
  </div>
</template>

<style scoped lang="scss">
.icon-box {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  background: #f5f5f5;
  display: flex;
  justify-content: center;
  align-items: center;
}
/* Fila */
.pear-row {
  display: flex;
  flex-wrap: wrap;       /* ✅ Permite que los elementos salten de línea */
  gap: 1rem;             /* Espaciado entre columnas/filas */
  width: 100%;
}

.pear-col {
  flex: 1 1 250px;       /* ✅ Se ajusta, con mínimo de 250px */
  max-width: 100%;       /* ✅ No se pasa del ancho del padre */
  box-sizing: border-box;
}
.ant-card-body{
 background-color: #fbfbfb !important;
}

</style>
