<template>
  <AppLayout :title="t('accounts.title')">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ t('accounts.title') }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium">{{ t('accounts.title') }}</h3>
              <Button as-child>
                <Link :href="accountRoutes.create().url" class="inline-flex items-center">
                  <Plus class="w-4 h-4 mr-2" />
                  {{ t('accounts.create_account') }}
                </Link>
              </Button>
            </div>

            <!-- Accounts Grid -->
            <div v-if="accounts.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <Card v-for="account in accounts" :key="account.id" class="hover:shadow-lg transition-shadow">
                <CardHeader class="pb-3">
                  <div class="flex items-center justify-between">
                    <CardTitle class="text-lg">{{ account.name }}</CardTitle>
                    <Badge :variant="account.is_active ? 'default' : 'secondary'">
                      {{ account.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('accounts.balance') }}</span>
                      <span class="text-lg font-semibold" :class="account.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ account.currency.symbol }}{{ Number(account.balance).toLocaleString() }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('accounts.currency') }}</span>
                      <span class="text-sm">{{ account.currency.code }}</span>
                    </div>
                  </div>
                </CardContent>
                <CardFooter class="pt-3">
                  <div class="flex gap-2 w-full">
                    <Button variant="outline" size="sm" as-child class="flex-1">
                      <Link :href="accountRoutes.show(account.id).url">
                        <Eye class="w-4 h-4 mr-1" />
                        {{ t('accounts.view') }}
                      </Link>
                    </Button>
                    <Button variant="outline" size="sm" as-child class="flex-1">
                      <Link :href="accountRoutes.edit(account.id).url">
                        <Edit class="w-4 h-4 mr-1" />
                        {{ t('accounts.edit') }}
                      </Link>
                    </Button>
                    <Button 
                      variant="destructive" 
                      size="sm" 
                      @click="deleteAccount(account)"
                      class="flex-1"
                    >
                      <Trash2 class="w-4 h-4 mr-1" />
                      {{ t('accounts.delete') }}
                    </Button>
                  </div>
                </CardFooter>
              </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <Wallet class="w-12 h-12 text-gray-400" />
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('dashboard.no_accounts') }}</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6">Get started by creating your first account.</p>
              <Button as-child>
                <Link :href="accountRoutes.create().url">
                  <Plus class="w-4 h-4 mr-2" />
                  {{ t('accounts.create_account') }}
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Plus, Eye, Edit, Trash2, Wallet } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import accountRoutes from '@/routes/accounts'

const { t } = useI18n()

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Account {
  id: number
  name: string
  balance: number
  initial_balance: number
  is_active: boolean
  currency: Currency
  created_at: string
  updated_at: string
}

defineProps<{
  accounts: Account[]
}>()

const deleteAccount = (account: Account) => {
  if (confirm(t('common.confirm_delete', { name: account.name }))) {
    router.delete(accountRoutes.destroy(account.id).url)
  }
}
</script>