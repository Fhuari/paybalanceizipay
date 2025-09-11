<template>
  <div class="payment-summary ">
    <div class="summary-card card-body  border-0  shadow-0">
      <ul class="list-group list-group-flush mb-2">
        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0 bg-transparent">
          <span class="label">
            <i class="bi bi-cash-stack me-1"></i>Base amount
          </span>
          <span class="value">${{ breakdown.base }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0 bg-transparent">
          <span class="label">
            <i class="bi bi-percent me-1"></i>Payment fee (5.5%)
          </span>
          <span class="value">${{ breakdown.igv }}</span>
        </li>
      </ul>

      <div class="total-row ">
        <span class="total-label">
          <i class="bi bi-wallet2 me-1"></i>Total to pay
        </span>
        <span class="total-value">${{ breakdown.total }}</span>
      </div>
    </div>
  </div>

</template>

<script>
import { computed } from 'vue'

export default {
  name: 'PriceBreakdown',
  props: {
    amount: {
      type: [Number, String],
      default: 0,
      required: true
    }
  },
  setup(props) {
    const IGV_RATE = 0.055

    const breakdown = computed(() => {
      const igv = props.amount * IGV_RATE
      const total = props.amount + igv
      return {
        base: props.amount,
        igv: parseFloat(igv.toFixed(2)),
        total: parseFloat(total.toFixed(2))
      }
    })

    return {
      breakdown
    }
  }
}
</script>
<style lang="scss">
.payment-summary {
  font-size: 0.875rem; // más pequeño
  line-height: 1.3;


  .label {
    color: #6c757d;
    font-weight: 500;
    display: flex;
    align-items: center;
    font-size: 0.83rem;
  }

  .value {
    color: #212529;
    font-weight: 500;
    font-size: 0.85rem;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 0;
    .total-label {
      font-weight: 600;
      font-size: 0.9rem;
      color: #343a40;
      display: flex;
      align-items: center;
    }

    .total-value {
      font-size: 1.1rem;
      font-weight: 700;

    }
  }
}
.list-group{
  list-style: none !important;
  padding-left: 0 !important;
li{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px;
    border-bottom: 1px solid #dadada;
}
}
</style>
