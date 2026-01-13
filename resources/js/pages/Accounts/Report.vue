<template>
  <div class="min-h-screen bg-white dark:bg-black transition-colors">
    <div class="max-w-6xl mx-auto p-4 sm:p-6">
      <!-- Compact Header with Stats -->
      <Card class="mb-4 border-gray-200 dark:border-gray-800">
        <CardContent class="p-4">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ t('report.title') }}</h1>
              <p class="text-xs text-gray-600 dark:text-gray-400">{{ account.name }}</p>
            </div>
            <div class="text-start">
              <p class="text-xs text-gray-600 dark:text-gray-400">{{ formatDate(startDate) }} - {{ formatDate(endDate) }}</p>
              <div class="flex items-center gap-2 mt-1 print:hidden">
                <select 
                  v-model="currentLocale" 
                  @change="changeLocale"
                  class="text-xs px-2 py-1 rounded-md border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="en">English</option>
                  <option value="ar">العربية</option>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Compact Stats in Header -->
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
            <Card class="bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900">
              <CardContent class="p-2">
                <p class="text-gray-600 dark:text-gray-400 text-xs mb-0.5">{{ t('report.opening_balance') }}</p>
                <p class="font-bold text-gray-900 dark:text-gray-100">{{ account.currency.symbol }}{{ formatAmount(statistics.opening_balance) }}</p>
              </CardContent>
            </Card>
            <Card class="bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-900">
              <CardContent class="p-2">
                <p class="text-gray-600 dark:text-gray-400 text-xs mb-0.5">{{ t('report.total_income') }}</p>
                <p class="font-bold text-green-600 dark:text-green-400">+{{ account.currency.symbol }}{{ formatAmount(statistics.total_income) }}</p>
              </CardContent>
            </Card>
            <Card class="bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900">
              <CardContent class="p-2">
                <p class="text-gray-600 dark:text-gray-400 text-xs mb-0.5">{{ t('report.total_expenses') }}</p>
                <p class="font-bold text-red-600 dark:text-red-400">-{{ account.currency.symbol }}{{ formatAmount(statistics.total_expense) }}</p>
              </CardContent>
            </Card>
            <Card class="bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-900">
              <CardContent class="p-2">
                <p class="text-gray-600 dark:text-gray-400 text-xs mb-0.5">{{ t('report.closing_balance') }}</p>
                <p class="font-bold text-gray-900 dark:text-gray-100">{{ account.currency.symbol }}{{ formatAmount(statistics.closing_balance) }}</p>
              </CardContent>
            </Card>
            <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950/30 dark:to-purple-950/30 border-gray-200 dark:border-gray-800">
              <CardContent class="p-2">
                <p class="text-gray-600 dark:text-gray-400 text-xs mb-0.5">{{ t('report.net_change') }}</p>
                <p class="font-bold" :class="statistics.net_change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                  {{ statistics.net_change >= 0 ? '+' : '' }}{{ account.currency.symbol }}{{ formatAmount(statistics.net_change) }}
                </p>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>

      <!-- Controls -->
      <Card class="mb-4 print:hidden border-gray-200 dark:border-gray-800">
        <CardContent class="p-4">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <div class="flex flex-col gap-1.5">
              <Label for="start_date" class="text-xs dark:text-gray-300">{{ t('report.start_date') }}</Label>
              <DatePicker
                v-model="localStartDate"
                :placeholder="t('report.start_date')"
                class="w-40 h-8 text-xs"
              />
            </div>
            <div class="flex flex-col gap-1.5">
              <Label for="end_date" class="text-xs dark:text-gray-300">{{ t('report.end_date') }}</Label>
              <DatePicker
                v-model="localEndDate"
                :placeholder="t('report.end_date')"
                class="w-40 h-8 text-xs"
              />
            </div>
            <Button @click="updateReport" variant="outline" size="sm" class="h-8 mt-5 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
              <RefreshCw class="w-3 h-3 me-1" />
              {{ t('report.update') }}
            </Button>
            <Button @click="printReport" size="sm" class="h-8 mt-5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">
              <Printer class="w-3 h-3 me-1" />
              {{ t('report.print') }}
            </Button>
            <Button @click="goBack" variant="ghost" size="sm" class="h-8 mt-5 dark:text-gray-300 dark:hover:bg-gray-900">
              <ArrowLeft class="w-3 h-3 me-1" />
              {{ t('report.back') }}
            </Button>
          </div>

          <!-- Column Selection -->
          <Card class="bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800">
            <CardContent class="p-3">
              <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ t('report.show_columns') }}:</p>
              <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.date" class="w-3 h-3 rounded dark:bg-gray-800 dark:border-gray-700" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ t('report.date') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.type" class="w-3 h-3 rounded dark:bg-gray-800 dark:border-gray-700" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ t('report.type') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.description" class="w-3 h-3 rounded dark:bg-gray-800 dark:border-gray-700" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ t('report.description') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.amount" class="w-3 h-3 rounded dark:bg-gray-800 dark:border-gray-700" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ t('report.amount') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.balance" class="w-3 h-3 rounded dark:bg-gray-800 dark:border-gray-700" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ t('report.balance') }}</span>
                </label>
              </div>
            </CardContent>
          </Card>
        </CardContent>
      </Card>

      <!-- Transaction Details -->
      <Card class="mb-4 border-gray-200 dark:border-gray-800">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-bold text-gray-900 dark:text-gray-100">{{ t('report.transaction_details') }}</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="transactions.length === 0" class="text-center py-8 px-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('report.no_transactions') }}</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-800">
                  <th v-if="visibleColumns.date" class="text-start p-2 font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ t('report.date') }}</th>
                  <th v-if="visibleColumns.type" class="text-start p-2 font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ t('report.type') }}</th>
                  <th v-if="visibleColumns.description" class="text-start p-2 font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ t('report.description') }}</th>
                  <th v-if="visibleColumns.amount" class="text-start p-2 font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ t('report.amount') }}</th>
                  <th v-if="visibleColumns.balance" class="text-start p-2 font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ t('report.balance') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(transaction, index) in transactions" 
                  :key="transaction.id"
                  class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900"
                >
                  <td v-if="visibleColumns.date" class="p-2 text-xs text-gray-700 dark:text-gray-300">
                    {{ formatDate(transaction.transaction_date) }}
                  </td>
                  <td v-if="visibleColumns.type" class="p-2">
                    <Badge 
                      variant="outline"
                      class="text-xs font-semibold"
                      :class="{
                        'bg-green-100 text-green-800 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900': transaction.type === 'income',
                        'bg-red-100 text-red-800 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900': transaction.type === 'expense',
                        'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900': transaction.type === 'transfer'
                      }"
                    >
                      {{ getTransactionTypeLabel(transaction) }}
                    </Badge>
                  </td>
                  <td v-if="visibleColumns.description" class="p-2 text-xs text-gray-700 dark:text-gray-300">
                    {{ transaction.description || t('report.no_description') }}
                    <span v-if="transaction.type === 'transfer'" class="text-gray-500 dark:text-gray-500 text-xs ms-1">
                      ({{ transaction.is_incoming_transfer ? t('report.from') : t('report.to') }}: {{ transaction.is_incoming_transfer ? transaction.account.name : transaction.transfer_to_account?.name }})
                    </span>
                  </td>
                  <td v-if="visibleColumns.amount" class="p-2 text-start font-semibold text-xs" :class="getAmountColor(transaction)">
                    {{ getAmountPrefix(transaction) }}{{ account.currency.symbol }}{{ formatAmount(getEffectiveAmount(transaction)) }}
                  </td>
                  <td v-if="visibleColumns.balance" class="p-2 text-start text-xs text-gray-700 dark:text-gray-300">
                    {{ account.currency.symbol }}{{ formatAmount(calculateRunningBalance(index)) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Compact Footer -->
      <div class="mt-4 pt-3 border-t border-gray-300 dark:border-gray-800 text-center text-xs text-gray-600 dark:text-gray-400">
        <p>{{ t('report.generated_on') }}: {{ formatDateTime(generatedAt) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import { Printer, ArrowLeft, RefreshCw } from 'lucide-vue-next'

const { t } = useI18n()

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
  is_active: boolean
  decimal_places: number
}

interface Transaction {
  id: number
  type: 'income' | 'expense' | 'transfer'
  amount: string
  description: string
  transaction_date: string
  account: Account
  transfer_to_account?: Account
  is_incoming_transfer?: boolean
  exchange_rate?: string | null
  converted_amount?: string | null
  category?: string
}

interface Account {
  id: number
  name: string
  type: string
  balance: number
  initial_balance: number
  is_active: boolean
  currency: Currency
  currency_id: number
  description: string | null
}

interface Statistics {
  opening_balance: number
  closing_balance: number
  total_income: number
  total_expense: number
  transfers_in: number
  transfers_out: number
  net_change: number
  transaction_count: number
}

interface TransactionsByType {
  income: number
  expense: number
  transfer: number
}

interface User {
  name: string
  email: string
}

const props = defineProps<{
  account: Account
  transactions: Transaction[]
  statistics: Statistics
  transactionsByType: TransactionsByType
  startDate: string
  endDate: string
  generatedAt: string
  user: User
}>()

const localStartDate = ref(props.startDate)
const localEndDate = ref(props.endDate)

// Get i18n instance at the top level
const { locale } = useI18n()
const currentLocale = ref(locale.value)

// Column visibility state
const visibleColumns = ref({
  date: true,
  type: true,
  description: true,
  amount: true,
  balance: true
})

const changeLocale = () => {
  locale.value = currentLocale.value
  
  // Update HTML dir attribute for RTL support
  document.documentElement.dir = currentLocale.value === 'ar' ? 'rtl' : 'ltr'
  
  // Optionally persist to session
  router.post('/locale', {
    locale: currentLocale.value
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  
  return `${day}/${month}/${year}`
}

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  
  let hours = date.getHours()
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  // Determine AM/PM
  const isAM = hours < 12
  const period = currentLocale.value === 'ar' ? (isAM ? 'ص' : 'م') : (isAM ? 'AM' : 'PM')
  
  // Convert to 12-hour format
  hours = hours % 12
  hours = hours ? hours : 12 // 0 should be 12
  const hoursStr = String(hours).padStart(2, '0')
  
  return `${day}/${month}/${year} ${hoursStr}:${minutes}${period}`
}

const formatAmount = (amount: number | string) => {
  return Number(amount).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const getTransactionTypeLabel = (transaction: Transaction) => {
  if (transaction.type === 'transfer') {
    return transaction.is_incoming_transfer ? t('report.transfer_in') : t('report.transfer_out')
  }
  return t(`report.${transaction.type}`)
}

const getAmountColor = (transaction: Transaction) => {
  if (transaction.type === 'income') return 'text-green-600 dark:text-green-400'
  if (transaction.type === 'expense') return 'text-red-600 dark:text-red-400'
  if (transaction.type === 'transfer') {
    return transaction.is_incoming_transfer ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400'
  }
  return 'text-gray-900 dark:text-gray-100'
}

const getAmountPrefix = (transaction: Transaction) => {
  if (transaction.type === 'income') return '+'
  if (transaction.type === 'expense') return '-'
  if (transaction.type === 'transfer') {
    return transaction.is_incoming_transfer ? '+' : '-'
  }
  return ''
}

const getEffectiveAmount = (transaction: Transaction) => {
  if (transaction.type === 'transfer' && transaction.is_incoming_transfer && transaction.converted_amount) {
    return Number(transaction.converted_amount)
  }
  return Number(transaction.amount)
}

const calculateRunningBalance = (index: number) => {
  let balance = props.statistics.opening_balance
  
  for (let i = 0; i <= index; i++) {
    const transaction = props.transactions[i]
    const amount = getEffectiveAmount(transaction)
    
    if (transaction.type === 'income') {
      balance += amount
    } else if (transaction.type === 'expense') {
      balance -= amount
    } else if (transaction.type === 'transfer') {
      if (transaction.is_incoming_transfer) {
        balance += amount
      } else {
        balance -= amount
      }
    }
  }
  
  return balance
}

const printReport = () => {
  window.print()
}

const goBack = () => {
  router.visit(`/accounts/${props.account.id}`)
}

const updateReport = () => {
  router.visit(`/accounts/${props.account.id}/report`, {
    data: {
      start_date: localStartDate.value,
      end_date: localEndDate.value
    }
  })
}
</script>

<style>
@media print {
  /* Hide print controls */
  .print\:hidden {
    display: none !important;
  }
  
  /* Remove browser headers and footers */
  @page {
    margin: 0.5cm;
    size: auto;
  }
  
  /* Ensure colors are preserved */
  body {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
  
  /* Optimize table printing */
  table {
    page-break-inside: auto;
  }
  
  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }
  
  thead {
    display: table-header-group;
  }
  
  /* Ensure proper spacing */
  * {
    box-shadow: none !important;
  }
}
</style>
