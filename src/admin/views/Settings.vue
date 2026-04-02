<script setup>
import { reactive, ref, onMounted } from 'vue'
import { message } from 'ant-design-vue'

const form = reactive({
  pearpay_mail: '',
  pearpay_commission_type: 'percent',
  pearpay_commission_value: 0,
  pearpay_show_payment_options: true,
  pearpay_currency: 'USD',
  pearpay_balance_title_en: 'Pay your balance or make a deposit',
  pearpay_balance_title_es: 'Pague su saldo o realice un depósito',
  pearpay_balance_desc_en: 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.',
  pearpay_balance_desc_es: 'Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar.',
  pearpay_balance_label_en: 'Enter the payment amount',
  pearpay_balance_label_es: 'Introduzca el importe del pago',
  pearpay_theme_color: '#1d2327',
  pearpay_theme_border: 5,
  pearpay_confirm_message: 'Payment successful!',
})

const loading = ref(true)
const saving = ref(false)

const apiBase = window.PEARPAY_ADMIN?.rest_url || ''
const nonce = window.PEARPAY_ADMIN?.nonce || ''

const loadSettings = async () => {
  loading.value = true
  try {
    const res = await fetch(`${apiBase}pearpay/v1/settings`, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    })
    if (!res.ok) throw new Error('Failed to load settings')
    const data = await res.json()
  console.log(data)
    form.pearpay_mail = data.pearpay_mail || ''
    form.pearpay_commission_type = data.pearpay_commission_type || 'percent'
    form.pearpay_commission_value = Number(data.pearpay_commission_value || 0)
    form.pearpay_show_payment_options = !!data.pearpay_show_payment_options
    form.pearpay_currency = data.pearpay_currency || 'USD'
    form.pearpay_balance_title_en = data.pearpay_balance_title_en || 'Pay your balance or make a deposit'
    form.pearpay_balance_title_es = data.pearpay_balance_title_es || 'Pague su saldo o realice un depósito'
    form.pearpay_balance_desc_en = data.pearpay_balance_desc_en || 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.'
    form.pearpay_balance_desc_es = data.pearpay_balance_desc_es || 'Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar.'
    form.pearpay_balance_label_en = data.pearpay_balance_label_en || 'Enter the payment amount'
    form.pearpay_balance_label_es = data.pearpay_balance_label_es || 'Introduzca el importe del pago'
    form.pearpay_theme_color = data.pearpay_theme_color || '#1d2327'
    form.pearpay_theme_border =data.pearpay_theme_border||2;
    form.pearpay_confirm_message = data.pearpay_confirm_message || 'Payment successful!'
  } catch (err) {
    message.error('No se pudieron cargar los settings.')
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    const res = await fetch(`${apiBase}pearpay/v1/settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify({
        pearpay_mail: form.pearpay_mail,
        pearpay_commission_type: form.pearpay_commission_type,
        pearpay_commission_value: form.pearpay_commission_value,
        pearpay_show_payment_options: form.pearpay_show_payment_options,
        pearpay_currency: form.pearpay_currency,
        pearpay_balance_title_en: form.pearpay_balance_title_en,
        pearpay_balance_title_es: form.pearpay_balance_title_es,
        pearpay_balance_desc_en: form.pearpay_balance_desc_en,
        pearpay_balance_desc_es: form.pearpay_balance_desc_es,
        pearpay_balance_label_en: form.pearpay_balance_label_en,
        pearpay_balance_label_es: form.pearpay_balance_label_es,
        pearpay_theme_color: form.pearpay_theme_color,
        pearpay_theme_border:form.pearpay_theme_border,
        pearpay_confirm_message: form.pearpay_confirm_message,
      }),
    })
    if (!res.ok) throw new Error('Failed to save settings')
    message.success('Settings guardados.')
  } catch (err) {
    message.error('No se pudieron guardar los settings.')
  } finally {
    saving.value = false
  }
}

onMounted(loadSettings)
</script>

<template>
  <a-page-header title="Settings" sub-title="Configuración general de Pear Pay" />

  <a-skeleton :loading="loading" active>
    <a-form layout="vertical">
      <a-form-item label="Correos para notificaciones">
        <a-input v-model:value="form.pearpay_mail" placeholder="correo@dominio.com" />
      </a-form-item>
      <a-form-item label="Comisión por tarjeta">
        <a-space>
          <a-select v-model:value="form.pearpay_commission_type" style="width: 140px;">
            <a-select-option value="percent">Percent (%)</a-select-option>
            <a-select-option value="fixed">Fixed ($)</a-select-option>
          </a-select>
          <a-input-number v-model:value="form.pearpay_commission_value" :min="0" :step="0.1" style="width: 160px;" />
        </a-space>
      </a-form-item>
      <a-form-item label="Mostrar opciones de pago">
        <a-switch v-model:checked="form.pearpay_show_payment_options" />
      </a-form-item>
      <a-form-item label="Moneda">
        <a-select v-model:value="form.pearpay_currency" style="width: 120px;">
          <a-select-option value="USD">USD</a-select-option>
          <a-select-option value="PEN">PEN</a-select-option>
        </a-select>
      </a-form-item>
      <a-divider>Textos Balance</a-divider>
      <a-row :gutter="[16, 16]">
        <a-col :span="12">
          <a-form-item label="Título Balance (EN)">
            <a-input v-model:value="form.pearpay_balance_title_en" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Título Balance (ES)">
            <a-input v-model:value="form.pearpay_balance_title_es" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Descripción Balance (EN)">
            <a-textarea v-model:value="form.pearpay_balance_desc_en" :rows="2" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Descripción Balance (ES)">
            <a-textarea v-model:value="form.pearpay_balance_desc_es" :rows="2" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Label Balance (EN)">
            <a-input v-model:value="form.pearpay_balance_label_en" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Label Balance (ES)">
            <a-input v-model:value="form.pearpay_balance_label_es" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="[10, 10]">
        <a-col :span="12">
          <a-form-item label="Color principal">
            <a-input v-model:value="form.pearpay_theme_color" type="color" style="width: 80px; padding: 0;" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="Border radius">
            <a-input-number v-model:value="form.pearpay_theme_border"  :min="0" :max="50" addon-after="px" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item label="Mensaje de confirmación">
        <a-textarea v-model:value="form.pearpay_confirm_message" :rows="3" />
      </a-form-item>

      <a-divider>Cómo usar</a-divider>
      <a-alert type="info" show-icon>
        <template #message>Shortcodes disponibles</template>
        <template #description>
          <div>
            Usa estos shortcodes en páginas o posts:
          </div>
          <ul style="margin: 8px 0 0 16px;">
            <li><code>[pear_pay]</code> — Todo (opciones + balance)</li>
            <li><code>[pear_pay_options]</code> — Solo opciones de pago</li>
            <li><code>[pear_pay_balance]</code> — Solo balance</li>
          </ul>
          <div style="margin-top: 8px;">
            Puedes usar <code>[pear_pay_options]</code> y <code>[pear_pay_balance]</code>.
          </div>
        </template>
      </a-alert>

      <a-form-item>
        <br>
        <a-button type="primary" :loading="saving" @click="saveSettings">
          Guardar cambios
        </a-button>
      </a-form-item>
    </a-form>
  </a-skeleton>
</template>
