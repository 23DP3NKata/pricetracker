function normalizeParams(raw) {
  if (!raw) return {}
  if (typeof raw === 'object') return raw

  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      if (parsed && typeof parsed === 'object') return parsed
    } catch {
        // ignore
    }
  }

  return {}
}

function formatPriceValue(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '0.00'
  return num.toFixed(2)
}

export function isTrackingStoppedNotification(notification) {
  if (notification?.message_key === 'tracking_stopped') return true

  const msg = String(notification?.message || '').toLowerCase()
  return msg.includes('tracking stopped')
}

export function getNotificationText(notification, t) {
  if (!notification) return t('ux.notificationFallback')

  if (notification.message_key === 'target_reached') {
    const params = normalizeParams(notification.message_params)
    return t('notificationsPage.messageTargetReached', {
      symbol: params.symbol || notification?.product?.symbol || notification?.product?.title || '?',
      currentPrice: formatPriceValue(params.new_price ?? notification.new_price),
      targetPrice: formatPriceValue(params.target_price),
      currency: (params.currency || 'USD').toUpperCase(),
    })
  }

  if (notification.message_key === 'tracking_stopped') {
    return t('notificationsPage.messageTrackingStopped')
  }

  return notification.message || notification.title || t('ux.notificationFallback')
}
