<template>
  <v-dialog v-model="openModel" max-width="560">
    <v-card rounded="xl" class="pa-6">
      <div class="tracking-setup-head mb-4">
        <v-avatar color="primary" variant="tonal" size="42">
          <v-icon>mdi-tune-variant</v-icon>
        </v-avatar>
        <div>
          <h2 class="text-h5 font-weight-bold mb-1">{{ $t('dashboard.trackingSetupTitle') }}</h2>
          <p class="text-body-2 text-medium-emphasis ma-0">{{ $t('dashboard.trackingSetupSubtitle') }}</p>
        </div>
      </div>

      <v-alert
        v-if="trackError"
        type="error"
        variant="tonal"
        rounded="lg"
        class="mb-4"
        closable
        @click:close="trackError = null"
      >
        {{ trackError }}
      </v-alert>

      <div v-if="asset" class="tracking-asset-meta mb-4">
        <div class="text-body-1 font-weight-bold">
          {{ asset.title }}
          <span class="text-medium-emphasis">({{ asset.symbol }})</span>
        </div>
        <div class="text-body-2 text-medium-emphasis">
          {{ $t('dashboard.currentMarketPrice') }}:
          <span class="font-weight-bold text-high-emphasis">{{ formatPrice(asset.current_price, asset.currency) }}</span>
        </div>
      </div>

      <v-form @submit.prevent="submitTracking">
        <v-text-field
          v-model="trackForm.targetPrice"
          @update:model-value="normalizeTrackTargetInput"
          @keydown="preventPriceInputKeydown"
          @paste="handlePricePaste"
          :label="$t('dashboard.targetPrice')"
          type="text"
          inputmode="decimal"
          maxlength="18"
          counter="18"
          min="0"
          step="0.01"
          rounded="lg"
          variant="outlined"
          prepend-inner-icon="mdi-target"
        />

        <div v-if="trackForm.targetPrice && formattedTargetPrice" class="text-caption text-medium-emphasis mt-1 mb-3">
          {{ $t('dashboard.autoNotifyHint', {
            direction: trackForm.notifyWhen === 'above'
              ? $t('dashboard.notifyDirectionUp')
              : $t('dashboard.notifyDirectionDown'),
            price: formattedTargetPrice,
          }) }}
        </div>

        <div class="quick-adjust mb-4">
          <div class="text-caption text-medium-emphasis mb-2">{{ $t('dashboard.quickAdjust') }}</div>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex align-center quick-adjust-row">
              <span class="quick-adjust-direction">▲</span>
              <v-btn
                v-for="percent in quickAdjustValues"
                :key="`up_${percent}`"
                size="small"
                :variant="selectedQuickAdjust === `up_${percent}` ? 'tonal' : 'outlined'"
                :color="selectedQuickAdjust === `up_${percent}` ? 'primary' : undefined"
                rounded
                class="quick-adjust-btn quick-adjust-percent-btn"
                @click="applyTargetPercent(percent, 'up')"
              >
                +{{ percent }}%
              </v-btn>
            </div>

            <div class="d-flex align-center quick-adjust-row">
              <span class="quick-adjust-direction">▼</span>
              <v-btn
                v-for="percent in quickAdjustValues"
                :key="`down_${percent}`"
                size="small"
                :variant="selectedQuickAdjust === `down_${percent}` ? 'tonal' : 'outlined'"
                :color="selectedQuickAdjust === `down_${percent}` ? 'error' : undefined"
                rounded
                class="quick-adjust-btn quick-adjust-percent-btn"
                @click="applyTargetPercent(percent, 'down')"
              >
                -{{ percent }}%
              </v-btn>
            </div>

            <div>
              <v-btn
                size="small"
                variant="text"
                color="primary"
                rounded
                class="quick-adjust-btn use-current-price-btn"
                @click="setTargetToCurrent"
              >
                {{ $t('dashboard.useCurrentPrice') }}
              </v-btn>
            </div>
          </div>
        </div>

        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn variant="text" rounded="xl" :disabled="trackLoading" @click="openModel = false">{{ $t('form.cancel') }}</v-btn>
          <v-btn type="submit" color="primary" rounded="xl" :loading="trackLoading" :disabled="trackLoading" prepend-icon="mdi-bell-ring-outline">
            {{ $t('dashboard.saveTracking') }}
          </v-btn>
        </div>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { trackAsset } from '@/api'
import { formatCurrencyPrice, roundToTwo, sanitizePriceInput, toPriceInput } from '@/utils/price'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  asset: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'tracked'])

const { t } = useI18n()

const openModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const trackLoading = ref(false)
const trackError = ref(null)

const trackForm = ref({
  targetPrice: '',
  notifyWhen: 'above',
})

const quickAdjustValues = [1, 2, 5, 10, 15]
const selectedQuickAdjust = ref(null)

const parsedTargetPrice = computed(() => roundToTwo(trackForm.value.targetPrice))
const formattedTargetPrice = computed(() => {
  if (parsedTargetPrice.value === null) return ''
  const currency = props.asset?.currency || 'USD'
  return formatPrice(parsedTargetPrice.value, currency)
})

function formatPrice(price, currency = 'USD') {
  return formatCurrencyPrice(price, currency)
}

function resetDialogState() {
  selectedQuickAdjust.value = null
  trackError.value = null
  trackForm.value = {
    targetPrice: toPriceInput(props.asset?.current_price),
    notifyWhen: 'above',
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    resetDialogState()
  }
})

watch(() => props.asset, () => {
  if (props.modelValue) {
    resetDialogState()
  }
})

function normalizeTrackTargetInput(value) {
  trackForm.value.targetPrice = sanitizePriceInput(value, { maxLength: 18, decimals: 2 })
}

function preventPriceInputKeydown(event) {
  const { key, ctrlKey, metaKey } = event

  if (ctrlKey || metaKey) return

  const allowedControlKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End']
  if (allowedControlKeys.includes(key)) return

  const isDigit = /^\d$/.test(key)
  const isDecimalSeparator = key === '.' || key === ','

  if (isDigit) return

  if (isDecimalSeparator) {
    const current = String(trackForm.value.targetPrice || '')
    if (!current.includes('.') && !current.includes(',')) return
  }

  event.preventDefault()
}

function handlePricePaste(event) {
  const pasted = event.clipboardData?.getData('text') || ''
  const sanitized = sanitizePriceInput(pasted, { maxLength: 18, decimals: 2 })

  event.preventDefault()
  trackForm.value.targetPrice = sanitized
}

function applyTargetPercent(percent, direction) {
  if (!props.asset) return

  const current = Number(props.asset.current_price)
  if (Number.isNaN(current) || current <= 0) return

  const multiplier = direction === 'up' ? (1 + percent / 100) : (1 - percent / 100)
  const adjusted = roundToTwo(current * multiplier)
  if (adjusted === null || adjusted <= 0) return

  selectedQuickAdjust.value = `${direction}_${percent}`
  trackForm.value.targetPrice = toPriceInput(adjusted)
  trackForm.value.notifyWhen = direction === 'up' ? 'above' : 'below'
}

function setTargetToCurrent() {
  if (!props.asset) return
  selectedQuickAdjust.value = null
  trackForm.value.targetPrice = toPriceInput(props.asset.current_price)
}

watch(() => trackForm.value.targetPrice, (value) => {
  if (!props.asset) return

  const target = roundToTwo(value)
  const current = roundToTwo(props.asset.current_price)

  if (target === null || current === null) return

  if (target > current) {
    trackForm.value.notifyWhen = 'above'
  } else if (target < current) {
    trackForm.value.notifyWhen = 'below'
  }
})

async function submitTracking() {
  if (!props.asset || trackLoading.value) return

  const targetPrice = roundToTwo(trackForm.value.targetPrice)

  if (targetPrice === null || targetPrice <= 0) {
    trackError.value = t('dashboard.invalidTargetPrice')
    return
  }

  trackLoading.value = true
  trackError.value = null

  try {
    await trackAsset({
      symbol: props.asset.symbol,
      target_price: targetPrice,
      notify_when: trackForm.value.notifyWhen,
    })

    openModel.value = false
    emit('tracked')
  } catch (e) {
    trackError.value = e.response?.data?.message || t('dashboard.failedSaveTracking')
  } finally {
    trackLoading.value = false
  }
}
</script>

<style scoped>
.tracking-setup-head {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tracking-asset-meta {
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(var(--v-theme-on-surface), 0.035);
}

.quick-adjust-btn {
  text-transform: none;
}

.quick-adjust-row {
  gap: 8px;
}

.quick-adjust-direction {
  font-size: 12px;
  width: 16px;
  text-align: center;
  align-self: center;
}

.quick-adjust-percent-btn {
  width: 56px;
}

.use-current-price-btn {
  font-weight: 600;
}
</style>
