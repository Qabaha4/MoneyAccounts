<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-xl text-foreground leading-tight">
          {{ t('app.report') }}
        </h2>
      </div>
    </template>

    <div class="py-4 sm:py-6 lg:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Summary Cards -->
        <!-- Mobile: Horizontal Scroll -->
        <div class="flex gap-3 overflow-x-auto pb-2 snap-x snap-mandatory scrollbar-hide md:hidden">
          <div class="flex-shrink-0 w-40 snap-start">
            <Card>
              <CardContent class="p-4">
                <Wallet class="w-5 h-5 mb-2 text-muted-foreground" />
                <p class="text-xs font-medium text-muted-foreground mb-1">{{ t('dashboard.total_accounts') }}</p>
                <p class="text-xl font-bold text-foreground">{{ accounts.length }}</p>
              </CardContent>
            </Card>
          </div>
          <div class="flex-shrink-0 w-40 snap-start">
            <Card>
              <CardContent class="p-4">
                <DollarSign class="w-5 h-5 mb-2 text-success" />
                <p class="text-xs font-medium text-muted-foreground mb-1">{{ t('dashboard.total_balance') }}</p>
                <p class="text-xl font-bold text-success">{{ totalBalance }}</p>
              </CardContent>
            </Card>
          </div>
          <div class="flex-shrink-0 w-40 snap-start">
            <Card>
              <CardContent class="p-4">
                <TrendingUp class="w-5 h-5 mb-2 text-green-600" />
                <p class="text-xs font-medium text-muted-foreground mb-1">{{ t('accounts.active') }}</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ activeCount }}</p>
              </CardContent>
            </Card>
          </div>
          <div class="flex-shrink-0 w-40 snap-start">
            <Card>
              <CardContent class="p-4">
                <CreditCard class="w-5 h-5 mb-2 text-muted-foreground" />
                <p class="text-xs font-medium text-muted-foreground mb-1">{{ t('dashboard.total_wallets') }}</p>
                <p class="text-xl font-bold text-foreground">{{ currenciesCount }}</p>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Desktop: Grid -->
        <div class="hidden md:grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">
                {{ t('dashboard.total_accounts') }}
              </p>
              <p class="text-2xl font-bold text-foreground">
                {{ accounts.length }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">
                {{ t('dashboard.total_balance') }}
              </p>
              <p class="text-2xl font-bold text-foreground">
                {{ totalBalance }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">
                {{ t('accounts.active') }}
              </p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ activeCount }}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">
                {{ t('dashboard.total_wallets') }}
              </p>
              <p class="text-2xl font-bold text-foreground">
                {{ currenciesCount }}
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Accounts List -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg font-bold text-foreground">
                {{ t('app.accounts') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="accounts.length === 0" class="text-center py-12">
              <Receipt class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
              <h3 class="text-lg font-bold text-foreground mb-2">
                {{ t('dashboard.no_accounts') }}
              </h3>
              <p class="text-sm text-muted-foreground">
                {{ t('accounts.create_first') || t('accounts.no_accounts_found') }}
              </p>
            </div>

            <div v-else class="divide-y divide-border">
              <div
                v-for="account in accounts"
                :key="account.id"
                class="flex items-center gap-4 p-4 hover:bg-accent/50 transition-colors"
              >
                <!-- Account Icon -->
                <div class="w-10 h-10 rounded-xl bg-secondary/50 flex items-center justify-center flex-shrink-0">
                  <Wallet class="w-5 h-5 text-muted-foreground" />
                </div>

                <!-- Account Info -->
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-sm text-foreground truncate">
                    {{ account.name }}
                  </h4>
                  <p class="text-xs text-muted-foreground">
                    {{ account.type }} · {{ account.currency.code }}
                  </p>
                </div>

                <!-- Balance -->
                <div class="text-right">
                  <p class="text-sm font-bold text-foreground">
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
import { Receipt, Wallet, FileText, DollarSign, TrendingUp, CreditCard } from 'lucide-vue-next'
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
  id: string
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

const viewReport = (accountId: string) => {
  router.visit(reportRoute({ account: accountId }).url)
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
