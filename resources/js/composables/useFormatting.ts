import { useI18n } from 'vue-i18n'
import { computed } from 'vue'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

export function useFormatting() {
  const { locale } = useI18n()

  const currentLocale = computed(() => locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US')

  function formatCurrency(amount: number, currency: Currency): string {
    return new Intl.NumberFormat(currentLocale.value, {
      style: 'currency',
      currency: currency.code,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)
  }

  function formatAmount(amount: number | string): string {
    return Number(amount).toLocaleString(currentLocale.value, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  function formatDate(dateString: string): string {
    const date = new Date(dateString)
    return date.toLocaleDateString(currentLocale.value, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  function formatDateTime(dateString: string): string {
    const date = new Date(dateString)
    const dateStr = date.toLocaleDateString(currentLocale.value, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
    const timeStr = date.toLocaleTimeString(currentLocale.value, {
      hour: '2-digit',
      minute: '2-digit',
    })
    return `${dateStr} ${timeStr}`
  }

  function formatTime(dateString: string): string {
    const date = new Date(dateString)
    return date.toLocaleTimeString(currentLocale.value, {
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatSimpleDate(dateString: string): string {
    const date = new Date(dateString)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  function formatDateFull(dateString: string): string {
    const date = new Date(dateString)
    return date.toLocaleDateString(currentLocale.value, {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  function formatNumber(value: number): string {
    return value.toLocaleString(currentLocale.value)
  }

  return {
    currentLocale,
    formatCurrency,
    formatAmount,
    formatDate,
    formatDateTime,
    formatTime,
    formatSimpleDate,
    formatDateFull,
    formatNumber,
  }
}
