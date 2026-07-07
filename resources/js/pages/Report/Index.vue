<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ t('app.report') }}
        </h2>
      </div>
    </template>

    <div class="py-4 sm:py-6 lg:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ t('dashboard.total_accounts') }}
              </p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                {{ accounts.length }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ t('dashboard.total_balance') }}
              </p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                {{ totalBalance }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ t('accounts.active') }}
              </p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ activeCount }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ t('dashboard.total_wallets') }}
              </p>
              <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                {{ currenciesCount }}
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Accounts List -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">
                {{ t('app.accounts') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="accounts.length === 0" class="text-center py-12">
              <Receipt class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-4" />
              <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2">
                {{ t('dashboard.no_accounts') }}
              </h3>
              <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ t('accounts.create_first') || t('accounts.no_accounts_found') }}
              </p>
            </div>

            <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
              <div
                v-for="account in accounts"
                :key="account.id"
                class="flex items-center gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
              >
                <!-- Account Icon -->
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                  <Wallet class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>

                <!-- Account Info -->
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate">
                    {{ account.name }}
                  </h4>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ account.type }} · {{ account.currency.code }}
                  </p>
                </div>

                <!-- Balance -->
                <div class="text-right">
                  <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                    {{ account.currency.symbol }}{{ formatAmount(account.balance) }}
                  </p>
                  <Badge
                    :variant="account.is_active ? 'outline' : 'secondary'"
                    class="mt-1 text-xs"
                  >
                    {{ account.is_active ? t('accounts.active') : t('accounts.inactive') }}
                  </Badge>
                </div>

                <!-- Action -->
                <Button
                  variant="outline"
                  size="sm"
                  @click="viewReport(account.id)"
                  class="flex-shrink-0"
                >
                  <FileText class="w-4 h-4 me-1" />
                  {{ t('report.view_report') }}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Receipt, Wallet, FileText } from 'lucide-vue-next'
import { useFormatting } from '@/composables/useFormatting'
import { report as reportRoute } from '@/routes/accounts'
import type { BreadcrumbItem } from '@/types'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
  is_active: boolean
  decimal_places: number
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

const props = defineProps<{
  accounts: Account[]
  totalBalance: string
  activeCount: number
  currenciesCount: number
}>()

const { t } = useI18n()
const { formatAmount } = useFormatting()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: t('app.report'),
    href: '/report',
  },
]

const viewReport = (accountId: number) => {
  router.visit(reportRoute({ account: accountId }).url)
}
</script>
