<template>
  <div class="paypal-checkout mb-0">
    <div :id="containerId"></div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { loadScript } from '@paypal/paypal-js'

const props = defineProps({
  amount: {
    type: Number,
    required: true
  },
  title: {
    type: String,
    default: 'Pay with PayPal'
  },
  containerId: {
    type: String,
    required: true
  }
})

// Calcula el monto con la comisión del 5.5%
const feeRate = 0.055
const totalWithFee = computed(() => (props.amount * (1 + feeRate)).toFixed(2))

const renderPayPalButton = async () => {
  const paypal = await loadScript({
    clientId: 'AWZMMsToyGWe6DL73sWsHMxZgjXn6sqFuagTf9mjAWAH4bkmtusX6GkOr51aEHf88rRPO7MXaNw_s3I9',
    currency: 'USD',
    disableFunding: "card,credit"
  })

  const container = document.getElementById(props.containerId)
  if (!paypal || !container) return

  // Asegura que el contenedor esté limpio
  container.innerHTML = ''

  // Renderiza el botón UNA SOLA VEZ
  paypal.Buttons({
    createOrder(data, actions) {
      return actions.order.create({
        purchase_units: [{
          amount: {
            value: totalWithFee.value
          },
          description: `${props.title} (incl. 5.5% fee)`
        }]
      })
    },
    onApprove(data, actions) {
      return actions.order.capture().then(details => {
        alert(`Payment completed by ${details.payer.name.given_name}`)
      })
    },
    onError(err) {
      console.error('Error en PayPal:', err)
    }
  }).render(`#${props.containerId}`)
}

onMounted(() => {
  renderPayPalButton()
})
</script>

<style scoped>
.paypal-checkout {
  margin-bottom: 1rem;
}
</style>
