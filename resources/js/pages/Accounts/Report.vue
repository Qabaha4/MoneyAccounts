<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      <!-- Print-Only Report Header -->
      <div class="print-only-report-header">
        <table class="w-full">
          <thead>
            <tr>
            <td class="w-1/2">
              <div class="flex items-center gap-3">
                <img src="/favicon-16x16.svg" width="32" height="32" alt=""/>
                <div>
                  <div class="text-lg font-bold text-gray-900">Money Accounts</div>
                  <div class="text-xs text-gray-500">Financial Report</div>
                </div>
              </div>
            </td>
            <td class="w-1/2 text-right">
              <div class="text-sm text-gray-700 font-semibold">{{ account.name }}</div>
              <!-- <div class="text-xs text-gray-500">{{ user.name }} · {{ user.email }}</div> -->
              <div class="text-xs text-gray-400">{{ formatDate(startDate) }} – {{ formatDate(endDate) }}</div>
            </td>
          </tr>
          </thead>
        </table>
        <div class="h-px bg-gray-300 my-4"></div>
      </div>

      <!-- Compact Header with Stats -->
      <Card>
        <CardContent class="p-4">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h1 class="text-lg font-bold text-foreground">{{ t('report.title') }}</h1>
              <p class="text-xs text-muted-foreground">{{ account.name }}</p>
            </div>
            <div class="text-start">
              <p class="text-xs text-muted-foreground">{{ formatDate(startDate) }} - {{ formatDate(endDate) }}</p>
            </div>
          </div>
          
          <!-- Compact Stats in Header -->
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
            <Card>
              <CardContent class="p-3">
                <p class="text-muted-foreground text-xs mb-0.5">{{ t('report.opening_balance') }}</p>
                <p class="font-bold text-foreground">{{ account.currency.symbol }}{{ formatAmount(statistics.opening_balance) }}</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="p-3">
                <p class="text-muted-foreground text-xs mb-0.5">{{ t('report.total_income') }}</p>
                <p class="font-bold text-success">+{{ account.currency.symbol }}{{ formatAmount(statistics.total_income) }}</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="p-3">
                <p class="text-muted-foreground text-xs mb-0.5">{{ t('report.total_expenses') }}</p>
                <p class="font-bold text-destructive">-{{ account.currency.symbol }}{{ formatAmount(statistics.total_expense) }}</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="p-3">
                <p class="text-muted-foreground text-xs mb-0.5">{{ t('report.closing_balance') }}</p>
                <p class="font-bold text-foreground">{{ account.currency.symbol }}{{ formatAmount(statistics.closing_balance) }}</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="p-3">
                <p class="text-muted-foreground text-xs mb-0.5">{{ t('report.net_change') }}</p>
                <p class="font-bold" :class="statistics.net_change >= 0 ? 'text-success' : 'text-destructive'">
                  {{ statistics.net_change >= 0 ? '+' : '' }}{{ account.currency.symbol }}{{ formatAmount(statistics.net_change) }}
                </p>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>

      <!-- Controls -->
      <Card class="print:hidden">
        <CardContent class="p-4">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <div class="flex flex-col gap-1.5">
              <Label for="start_date" class="text-xs text-muted-foreground">{{ t('report.start_date') }}</Label>
              <DatePicker
                v-model="localStartDate"
                :placeholder="t('report.start_date')"
                class="w-40 h-8 text-xs"
              />
            </div>
            <div class="flex flex-col gap-1.5">
              <Label for="end_date" class="text-xs text-muted-foreground">{{ t('report.end_date') }}</Label>
              <DatePicker
                v-model="localEndDate"
                :placeholder="t('report.end_date')"
                class="w-40 h-8 text-xs"
              />
            </div>
            <Button @click="updateReport" variant="outline" size="sm" class="h-8 mt-5">
              <RefreshCw class="w-3 h-3 me-1" />
              {{ t('report.update') }}
            </Button>
            <Button @click="printReport" size="sm" class="h-8 mt-5">
              <Printer class="w-3 h-3 me-1" />
              {{ t('report.print') }}
            </Button>
            <Button @click="goBack" variant="ghost" size="sm" class="h-8 mt-5 text-muted-foreground">
              <ArrowLeft class="w-3 h-3 me-1" />
              {{ t('report.back') }}
            </Button>
          </div>

          <!-- Column Selection -->
          <Card class="bg-card border-border">
            <CardContent class="p-3">
              <p class="text-xs font-semibold text-muted-foreground mb-2">{{ t('report.show_columns') }}:</p>
              <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.date" class="w-3 h-3 rounded border-border" />
                  <span class="text-xs text-muted-foreground">{{ t('report.date') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.type" class="w-3 h-3 rounded border-border" />
                  <span class="text-xs text-muted-foreground">{{ t('report.type') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.description" class="w-3 h-3 rounded border-border" />
                  <span class="text-xs text-muted-foreground">{{ t('report.description') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.amount" class="w-3 h-3 rounded border-border" />
                  <span class="text-xs text-muted-foreground">{{ t('report.amount') }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" v-model="visibleColumns.balance" class="w-3 h-3 rounded border-border" />
                  <span class="text-xs text-muted-foreground">{{ t('report.balance') }}</span>
                </label>
              </div>
            </CardContent>
          </Card>
        </CardContent>
      </Card>

      <!-- Transaction Details -->
      <Card class="border-border">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-bold text-foreground">{{ t('report.transaction_details') }}</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="transactions.length === 0" class="text-center py-8 px-4">
            <p class="text-sm text-muted-foreground">{{ t('report.no_transactions') }}</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr class="bg-muted/50 border-b-2 border-border">
                  <th v-if="visibleColumns.date" class="text-start p-2 font-semibold text-foreground text-xs">{{ t('report.date') }}</th>
                  <th v-if="visibleColumns.type" class="text-start p-2 font-semibold text-foreground text-xs">{{ t('report.type') }}</th>
                  <th v-if="visibleColumns.description" class="text-start p-2 font-semibold text-foreground text-xs">{{ t('report.description') }}</th>
                  <th v-if="visibleColumns.amount" class="text-start p-2 font-semibold text-foreground text-xs">{{ t('report.amount') }}</th>
                  <th v-if="visibleColumns.balance" class="text-start p-2 font-semibold text-foreground text-xs">{{ t('report.balance') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(transaction, index) in transactions" 
                  :key="transaction.id"
                  class="border-b border-border hover:bg-accent/50"
                >
                  <td v-if="visibleColumns.date" class="p-2 text-xs text-foreground">
                    {{ formatDate(transaction.transaction_date) }}
                  </td>
                  <td v-if="visibleColumns.type" class="p-2">
                    <Badge 
                      variant="outline"
                      class="text-xs font-semibold bg-secondary/50 text-secondary-foreground"
                    >
                      {{ getTransactionTypeLabel(transaction) }}
                    </Badge>
                  </td>
                  <td v-if="visibleColumns.description" class="p-2 text-xs text-foreground">
                    {{ transaction.description || t('report.no_description') }}
                    <span v-if="transaction.type === 'transfer'" class="text-muted-foreground text-xs ms-1">
                      ({{ transaction.is_incoming_transfer ? t('report.from') : t('report.to') }}: {{ transaction.is_incoming_transfer ? transaction.account.name : transaction.transfer_to_account?.name }})
                    </span>
                  </td>
                  <td v-if="visibleColumns.amount" class="p-2 text-start font-semibold text-xs" :class="getAmountColor(transaction)">
                    {{ getAmountPrefix(transaction) }}{{ account.currency.symbol }}{{ formatAmount(getEffectiveAmount(transaction)) }}
                  </td>
                  <td v-if="visibleColumns.balance" class="p-2 text-start text-xs text-foreground">
                    {{ account.currency.symbol }}{{ formatAmount(calculateRunningBalance(index)) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Print-Only Summary -->
      <div class="print-only-summary">
        <div class="h-px bg-gray-300 my-3"></div>
        <div class="flex justify-between text-xs text-gray-600">
          <div>
            <span class="font-semibold">{{ t('report.total_transactions') }}:</span> {{ statistics.transaction_count }}
          </div>
          <div>
            <span class="font-semibold">{{ t('report.opening') }}:</span> {{ account.currency.symbol }}{{ formatAmount(statistics.opening_balance) }}
            <span class="mx-2">|</span>
            <span class="font-semibold">{{ t('report.closing') }}:</span> {{ account.currency.symbol }}{{ formatAmount(statistics.closing_balance) }}
          </div>
          <div>
            <span class="font-semibold">{{ t('report.net_change') }}:</span>
            {{ statistics.net_change >= 0 ? '+' : '' }}{{ account.currency.symbol }}{{ formatAmount(statistics.net_change) }}
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="pt-3 border-t border-border text-center text-xs text-muted-foreground">
        <p>{{ t('report.generated_on') }}: {{ formatDateTime(generatedAt) }}</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import { Printer, ArrowLeft, RefreshCw } from 'lucide-vue-next'
import { report as reportRoute, show as showAccount } from '@/routes/accounts'
import type { BreadcrumbItem } from '@/types'

const { t, locale } = useI18n()
const { formatAmount } = useFormatting()

const breadcrumbs: BreadcrumbItem[] = computed(() => [
  {
    title: t('app.report'),
    href: '/report',
  },
  {
    title: props.account.name,
    href: reportRoute({ account: props.account.id }).url,
  },
])

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

// Column visibility state
const visibleColumns = ref({
  date: true,
  type: true,
  description: true,
  amount: true,
  balance: true
})

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
  
  const isAM = hours < 12
  const period = locale.value === 'ar' ? (isAM ? 'ص' : 'م') : (isAM ? 'AM' : 'PM')
  
  hours = hours % 12
  hours = hours ? hours : 12
  const hoursStr = String(hours).padStart(2, '0')
  
  return `${day}/${month}/${year} ${hoursStr}:${minutes}${period}`
}

const getTransactionTypeLabel = (transaction: Transaction) => {
  if (transaction.type === 'transfer') {
    return transaction.is_incoming_transfer ? t('report.transfer_in') : t('report.transfer_out')
  }
  return t(`report.${transaction.type}`)
}

const getAmountColor = (transaction: Transaction) => {
  if (transaction.type === 'income') return 'text-success'
  if (transaction.type === 'expense') return 'text-destructive'
  if (transaction.type === 'transfer') {
    return transaction.is_incoming_transfer ? 'text-success' : 'text-muted-foreground'
  }
  return 'text-foreground'
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
  router.visit(showAccount({ account: props.account.id }).url)
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
/* ── Print-Only Elements ── */
.print-only-report-header,
.print-only-summary {
  display: none;
}

@media print {
  /* ── Reset & Hide UI ── */
  .print\:hidden,
  nav,
  [data-slot="sidebar"],
  [data-slot="sidebar-content"],
  .app-header,
  aside,
  footer,
  input,
  [role="dialog"],
  button:not(.print-only-summary button),
  select,
  .lucide-search,
  [class*="Search"],
  [data-slot="sidebar-trigger"],
  .sidebar-trigger,
  [class*="burger"],
  [class*="hamburger"],
  [class*="menu-icon"] {
    display: none !important;
  }

  /* ── Hide duplicate visible header info ── */
  .flex.justify-between.items-start.mb-3 {
    display: none !important;
  }

  /* ── Hide app name from top ── */
  /* .print-only-report-header .flex.items-center.gap-3 > div {
    display: none !important;
  } */

  /* ── Make banner SVG bg transparent ── */
  /* .print-only-report-header svg rect { */
    /* fill: transparent !important; */
  /* } */

  /* ── Hide generated-on footer in print ── */
  /* .pt-3.border-t.border-border.text-center { */
    /* display: none !important; */
  /* } */

  /* ── Page Setup ── */
  @page {
    margin: 1.2cm 0.8cm;
    size: A4;
    @top-center { content: ""; }
    @bottom-left { content: ""; }
    @bottom-right { content: ""; }
    @bottom-center { content: counter(page); font-size: 9pt; color: #6b7280; }
  }

  @page :first {
    margin-top: 1.2cm;
  }

  /* ── Body Reset ── */
  body,
  html,
  .dark body,
  .dark [class*="bg-"],
  [class*="bg-background"],
  [class*="bg-card"] {
    print-color-adjust: exact !important;
    -webkit-print-color-adjust: exact !important;
    background: white !important;
    color: #111 !important;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    font-size: 10pt;
    line-height: 1.5;
  }

  .dark .bg-background,
  .dark .bg-card,
  .dark .bg-secondary,
  .dark .bg-muted,
  .dark [class*="bg-"] {
    background: white !important;
  }

  /* ── Show Print Header ── */
  .print-only-report-header {
    display: block !important;
  }

  .print-only-summary {
    display: block !important;
  }

  /* ── Card / Container Reset ── */
  .overflow-x-auto {
    overflow: visible !important;
  }

  [class*="Card"],
  [class*="card"] {
    box-shadow: none !important;
    background: white !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0.5rem 0 !important;
  }

  .max-w-6xl,
  .max-w-7xl {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .px-4, .sm\:px-6, .lg\:px-8,
  .px-2, .sm\:px-4, .lg\:px-6 {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }

  .py-6, .py-4, .sm\:py-6 {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }

  .space-y-6 > :not([hidden]) ~ :not([hidden]) {
    margin-top: 0.75rem !important;
  }

  /* ── Stats Summary Grid ── */
  .grid-cols-2.sm\:grid-cols-5 {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important;
    gap: 0.5rem !important;
  }

  .grid-cols-2.sm\:grid-cols-5 > [class*="Card"] {
    border: 1px solid #e5e7eb !important;
    border-radius: 4px !important;
    padding: 0.5rem !important;
    background: #f9fafb !important;
  }

  .grid-cols-2.sm\:grid-cols-5 .text-success {
    color: #059669 !important;
  }

  .grid-cols-2.sm\:grid-cols-5 .text-destructive {
    color: #dc2626 !important;
  }

  /* ── Transaction Table ── */
  table {
    width: 100% !important;
    border-collapse: collapse !important;
    page-break-inside: auto;
    font-size: 9pt;
    border: 1px solid #d1d5db !important;
    border-radius: 6px !important;
    overflow: hidden !important;
  }

  thead {
    display: table-header-group;
  }

  thead tr {
    background: #1e3a5f !important;
  }

  thead th {
    color: white !important;
    font-weight: 600 !important;
    font-size: 8pt !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 0.5rem 0.4rem !important;
    border-bottom: 1px solid #1e3a5f !important;
    border-right: 1px solid rgba(255,255,255,0.15) !important;
  }

  thead th:last-child {
    border-right: none !important;
  }

  tbody tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }

  tbody tr:nth-child(even) {
    background: #f3f4f6 !important;
  }

  tbody td {
    padding: 0.35rem 0.4rem !important;
    border-bottom: 1px solid #e5e7eb !important;
    border-right: 1px solid #e5e7eb !important;
    color: #1f2937 !important;
    font-size: 9pt !important;
  }

  tbody td:last-child {
    border-right: none !important;
  }

  tbody tr:last-child td {
    border-bottom: none !important;
  }

  /* ── Badge style in print ── */
  [class*="Badge"],
  [class*="badge"] {
    background: #e5e7eb !important;
    color: #374151 !important;
    padding: 0.1rem 0.4rem !important;
    border-radius: 2px !important;
    font-size: 7pt !important;
    font-weight: 500 !important;
    border: none !important;
  }

  /* ── Color overrides for print ── */
  .text-success {
    color: #059669 !important;
  }

  .text-destructive {
    color: #dc2626 !important;
  }

  .text-muted-foreground {
    color: #6b7280 !important;
  }

  .text-foreground {
    color: #111827 !important;
  }

  .bg-muted\/50 {
    background: #f3f4f6 !important;
  }

  /* ── Card Title ── */
  h3, [class*="CardTitle"] {
    font-size: 11pt !important;
    font-weight: 700 !important;
    color: #111 !important;
    margin-bottom: 0.5rem !important;
  }

  /* ── Avoid page breaks inside cards ── */
  [class*="Card"] {
    page-break-inside: avoid;
  }


    thead tr {
    background: transparent !important;
  }

  thead th {
    /* color: white !important;
    font-weight: 600 !important;
    font-size: 8pt !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 0.5rem 0.4rem !important; */
    border-bottom: 0px!important;
    /* border-right: 1px solid rgba(255,255,255,0.15) !important; */
  }
}
</style>
