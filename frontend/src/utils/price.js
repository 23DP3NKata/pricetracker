export function roundToTwo(value) {
  if (value === null || value === undefined || value === '') return null

  const normalized = String(value).replace(',', '.')
  const numeric = Number(normalized)

  if (Number.isNaN(numeric)) return null

  return Math.round((numeric + Number.EPSILON) * 100) / 100
}

export function formatCurrencyPrice(price, currency = 'USD', locale) {
  const numeric = roundToTwo(price)
  if (numeric === null) return 'N/A'

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: String(currency || 'USD').toUpperCase(),
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numeric)
}

export function formatDecimalPrice(price, locale) {
  const numeric = roundToTwo(price)
  if (numeric === null) return 'N/A'

  return new Intl.NumberFormat(locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numeric)
}

export function toPriceInput(value) {
  const numeric = roundToTwo(value)
  return numeric === null ? '' : numeric.toFixed(2)
}

export function sanitizePriceInput(value, options = {}) {
  const { maxLength = 18, decimals = 2 } = options

  if (value === null || value === undefined) return ''

  const raw = String(value).replace(',', '.')
  const sanitized = raw.replace(/[^\d.]/g, '')
  const [integerPartRaw = '', ...decimalParts] = sanitized.split('.')

  let integerPart = integerPartRaw
  if (integerPart.length > 1) {
    integerPart = integerPart.replace(/^0+(?=\d)/, '')
  }

  const decimalPart = decimalParts.join('').slice(0, decimals)

  let normalized = integerPart
  if (sanitized.includes('.')) {
    normalized = `${integerPart || '0'}.${decimalPart}`
  }

  if (normalized.length > maxLength) {
    return normalized.slice(0, maxLength)
  }

  return normalized
}
