<script setup>
import { ref, toRefs, watch, computed } from 'vue'
import {
  HomeOutlined,
  ShoppingOutlined,
  DollarOutlined,
  CreditCardOutlined,
} from '@ant-design/icons-vue'
import PriceBreakdown from './components/PayBreakDow.vue'
import PaymentButton from './components/PayIzipay.vue'
import TranslateText from './components/TranslateText.vue'

const props = defineProps({
  lang: String,
  devmode: String,
  mode: {
    type: String,
    default: 'all'
  }
})
const { lang, devmode, mode } = toRefs(props)
const settings = (window.PEARPAY && window.PEARPAY.settings) ? window.PEARPAY.settings : {}

const showPaymentOptions = settings.show_payment_options !== false
const currency = settings.currency || 'USD'
const currencySymbol = currency === 'PEN' ? 'S/' : '$'
const commissionType = settings.commission_type || 'percent'
const commissionValue = settings.commission_value || 0
const balanceTexts = settings.balance_texts || {}

const showOptions = mode.value === 'all' || mode.value === 'options'
const showBalance = mode.value === 'all' || mode.value === 'balance'

const selectedPaymentMethod = ref('izipay')
const balanceAmount = ref(null)
const IGV_RATE = 0

const paymentMethods = [
  {
    id: 'izipay',
    name: 'Card',
    icon: CreditCardOutlined,
    color: '#1677ff'
  }
]

const numTravelers = ref({})

const rawOptions = (window.PEARPAY && window.PEARPAY.options) ? window.PEARPAY.options : []
const depositCards = rawOptions.map((item, index) => {
  const icon = index % 2 === 0 ? HomeOutlined : ShoppingOutlined
  const labelByType = item.price_type === 'group'
    ? { en: 'Per group', es: 'Por grupo' }
    : { en: 'Per person', es: 'Por persona' }

  return {
    id: item.id,
    icon,
    title: { en: item.title_en, es: item.title_es },
    subtitle: { en: item.description_en, es: item.description_es },
    label: labelByType,
    price: Number(item.price || 0),
    priceType: item.price_type || 'person'
  }
})

depositCards.forEach((card) => {
  if (!numTravelers.value[card.id]) {
    numTravelers.value[card.id] = 1
  }
})

const calculateTotal = (amount) => {
  const igv = amount * IGV_RATE
  const total = amount + igv
  return { base: amount, total: parseFloat(total.toFixed(2)) }
}

const normalizeCommission = () => {
  const raw = String(commissionValue || 0).replace(',', '.')
  return Number(raw) || 0
}

const applyCommission = (amount) => {
  const value = normalizeCommission()
  if (!value || value <= 0) return amount
  return commissionType === 'fixed'
    ? amount + value
    : amount + (amount * (value / 100))
}

const balanceAmountNumber = computed(() => Number(balanceAmount.value || 0))
const balanceTotalWithCommission = computed(() => {
  const base = balanceAmountNumber.value
  if (base <= 0) return 0
  const { total } = calculateTotal(base)
  return parseFloat(applyCommission(total).toFixed(2))
})

const handlePayment = async (amount, type, method) => {
  const { total } = calculateTotal(amount)
  setTimeout(() => {
    alert(`Payment of $${total} for ${type} via ${method} would be processed here.`)
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
  <a-config-provider
    :theme="{
      token: {
        colorPrimary: settings.theme_color,
        colorSuccess: '#52c41a',
        borderRadius: Number(settings.theme_border),
      },
    }"
  >
    <div class="pearpay-shell" v-if="showPaymentOptions">
      <div class="pearpay-head" v-if="showOptions">
        <div class="pearpay-title">Payment options</div>
        <div class="pearpay-subtitle">Choose an option and pay securely</div>
      </div>

      <div class="pearpay-grid" v-if="showOptions">
        <div class="pearpay-col" v-for="card in depositCards" :key="card.id">
          <a-card :bordered="false" class="pearpay-card">
            <div class="pearpay-card-head">
              <div class="pearpay-icon">
                <component :is="card.icon" style="font-size: 22px" />
              </div>
              <div>
                <a-typography-title :level="5" class="mb-1">
                  <TranslateText :lang="lang" :en="card.title.en" :es="card.title.es" />
                </a-typography-title>
                <a-typography-text type="secondary">
                  <TranslateText :lang="lang" :en="card.subtitle.en" :es="card.subtitle.es" />
                </a-typography-text>
              </div>
            </div>

            <div class="pearpay-counter">
              <a-typography-text strong>
                <TranslateText :lang="lang" :en="card.label.en" :es="card.label.es" />
              </a-typography-text>

              <a-space v-if="card.priceType === 'person'" class="pearpay-counter-controls">
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
              <div v-else class="pearpay-fixed">1</div>
            </div>

            <div class="pearpay-breakdown">
              <PriceBreakdown
                :amount="card.price * numTravelers[card.id]"
                :currencySymbol="currencySymbol"
                :commissionType="commissionType"
                :commissionValue="commissionValue"
              />
            </div>

            <div class="pearpay-actions">
              <PaymentButton
                v-for="method in paymentMethods"
                :key="method.id"
                :method="method"
                :amount="card.price * numTravelers[card.id]"
                :type="card.title.en"
                :devmode="devmode"
                @payment="handlePayment"
              />
            </div>
          </a-card>
        </div>
      </div>

      <div class="pearpay-balance" v-if="showBalance">
        <a-card :bordered="false" class="pearpay-card pearpay-card--balance">
          <div class="pearpay-card-head">
            <div class="pearpay-icon pearpay-icon--green">
              <DollarOutlined style="font-size: 22px" />
            </div>
            <div>
              <a-typography-title :level="5" class="mb-1">
                <TranslateText
                  :lang="lang"
                  :en="balanceTexts.title_en || 'Pay your balance or make a deposit'"
                  :es="balanceTexts.title_es || 'Pague su saldo o realice un deposito'"
                />
              </a-typography-title>
              <a-typography-text type="secondary">
                <TranslateText
                  :lang="lang"
                  :en="balanceTexts.desc_en || 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.'"
                  :es="balanceTexts.desc_es || 'Pague cualquier saldo restante o realice un deposito personalizado. Ingrese el monto que desea pagar.'"
                />
              </a-typography-text>
            </div>
          </div>

          <div class="pearpay-form">
            <a-form layout="vertical">
              <a-form-item>
                <template #label>
                  <a-typography-text strong type="secondary">
                    <TranslateText
                      :lang="lang"
                      :en="balanceTexts.label_en || 'Enter the payment amount'"
                      :es="balanceTexts.label_es || 'Introduzca el importe del pago'"
                    />
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
                  :addon-before="currencySymbol"
                />
              </a-form-item>
            </a-form>
          </div>

          <div class="pearpay-breakdown" v-if="balanceAmountNumber > 0">
            <PriceBreakdown
              :amount="balanceAmountNumber"
              :currencySymbol="currencySymbol"
              :commissionType="commissionType"
              :commissionValue="commissionValue"
            />
          </div>

          <div class="pearpay-actions">
            <PaymentButton
              v-for="method in paymentMethods"
              :key="`balance-${method.id}`"
              v-show="selectedPaymentMethod === method.id"
              :lang="lang"
              :method="method"
              :amount="balanceTotalWithCommission"
              :devmode="devmode"
              type="Balance Payment"
              @payment="handlePayment"
              :balance="true"
            />
          </div>
        </a-card>
      </div>
    </div>
  </a-config-provider>
</template>

<style scoped lang="scss">
.pearpay-shell {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 16px 40px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.pearpay-head {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pearpay-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #121826;
}

.pearpay-subtitle {
  color: #5b6475;
  font-size: 0.95rem;
}

.pearpay-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

.pearpay-card {
  border: 1px solid #e6e8ee;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
}

.pearpay-card--balance {
  background: linear-gradient(135deg, #ffffff 0%, #f7f9ff 100%);
}

.pearpay-card-head {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 12px;
}

.pearpay-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #eef2ff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pearpay-icon--green {
  background: #e7f6ee;
  color: #2f855a;
}

.pearpay-counter {
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.pearpay-counter-controls {
  gap: 8px;
}

.pearpay-fixed {
  font-weight: 600;
  color: #1f2937;
}

.pearpay-breakdown {
  margin-top: 12px;
}

.pearpay-actions {
  margin-top: 16px;
}

.pearpay-balance {
  margin-top: 12px;
}

.pearpay-section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 12px;
}

.pearpay-form {
  margin-top: 12px;
}
</style>
