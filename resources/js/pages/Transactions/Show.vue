<template>
  <AppLayout :title="t('transactions.transaction_details')">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ t('transactions.transaction_details') }}
        </h2>
        <div class="flex space-x-2">
          <Button variant="outline" as-child>
            <Link :href="transactionRoutes.edit({ transaction: transaction.id }).url">
              <Edit class="w-4 h-4 mr-2" />
              {{ t('common.edit') }}
            </Link>
          </Button>
          <Button variant="outline" as-child>
            <Link :href="transactionRoutes.index().url">
              <ArrowLeft class="w-4 h-4 mr-2" />
              {{ t('transactions.back_to_list') }}
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <!-- Transaction Header -->
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center space-x-3">
                <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-700">
                  <Receipt class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ transaction.description || 'Transaction' }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Transaction #{{ transaction.id }}
                  </p>
                </div>
              </div>
              <Badge 
                :variant="transaction.type === 'income' ? 'default' : transaction.type === 'expense' ? 'destructive' : 'secondary'"
                class="text-sm px-3 py-1"
              >
                {{ transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1) }}
              </Badge>
            </div>

            <!-- Transaction Details -->
            <div class="grid gap-6 md:grid-cols-2">
              <!-- Amount Card -->
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <DollarSign class="w-5 h-5" />
                    <span>{{ t('transactions.amount') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div 
                    class="text-3xl font-bold"
                    :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ transaction.type === 'income' ? '+' : '-' }}{{ transaction.account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ transaction.account.currency.name }} ({{ transaction.account.currency.code }})
                  </p>
                </CardContent>
              </Card>

              <!-- Date Card -->
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <Calendar class="w-5 h-5" />
                    <span>{{ t('transactions.date') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ new Date(transaction.transaction_date).toLocaleDateString('en-US', { 
                      weekday: 'long',
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    }) }}
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ new Date(transaction.transaction_date).toLocaleTimeString('en-US', {
                      hour: '2-digit',
                      minute: '2-digit'
                    }) }}
                  </p>
                </CardContent>
              </Card>

              <!-- Account Card -->
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <CreditCard class="w-5 h-5" />
                    <span>{{ t('transactions.account') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ transaction.account.name }}
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ t('transactions.primary_account') }}
                  </p>
                </CardContent>
              </Card>

              <!-- Transfer Account Card (if applicable) -->
              <Card v-if="transaction.transfer_to_account">
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <ArrowRightLeft class="w-5 h-5" />
                    <span>{{ t('transactions.transfer_to') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ transaction.transfer_to_account.name }}
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ t('transactions.destination_account') }}
                  </p>
                </CardContent>
              </Card>

              <!-- Description Card (if applicable) -->
              <Card v-if="transaction.description" :class="transaction.transfer_to_account ? 'md:col-span-2' : ''">
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <FileText class="w-5 h-5" />
                    <span>{{ t('transactions.description') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p class="text-gray-900 dark:text-gray-100 leading-relaxed">
                    {{ transaction.description }}
                  </p>
                </CardContent>
              </Card>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
              <Button variant="outline" as-child>
                <Link :href="transactionRoutes.index().url">
                  <ArrowLeft class="w-4 h-4 mr-2" />
                  {{ t('transactions.back_to_transactions') }}
                </Link>
              </Button>
              <Button variant="outline" as-child>
                <Link :href="transactionRoutes.edit({ transaction: transaction.id }).url">
                  <Edit class="w-4 h-4 mr-2" />
                  {{ t('transactions.edit_transaction') }}
                </Link>
              </Button>
              <Button 
                variant="destructive" 
                @click="deleteTransaction"
                :disabled="isDeleting"
              >
                <Trash2 class="w-4 h-4 mr-2" />
                {{ isDeleting ? t('transactions.deleting') : t('common.delete') }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { 
  Receipt, 
  DollarSign, 
  Calendar, 
  CreditCard, 
  ArrowRightLeft, 
  FileText, 
  Edit, 
  Trash2, 
  ArrowLeft 
} from 'lucide-vue-next'
import transactions from '@/routes/transactions'
import transactionRoutes from '@/routes/transactions'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Account {
  id: number
  name: string
  currency: Currency
}

interface Transaction {
  id: number
  type: string
  amount: number
  description: string
  transaction_date: string
  account: Account
  transfer_to_account?: Account
}

const props = defineProps<{
  transaction: Transaction
}>()

const isDeleting = ref(false)

const deleteTransaction = () => {
  if (confirm(t('common.confirm_delete', { name: props.transaction.description || 'this transaction' }))) {
    isDeleting.value = true
    router.delete(transactionRoutes.destroy({ transaction: props.transaction.id }).url, {
      onSuccess: () => {
        // Redirect to transactions index after successful deletion
      },
      onError: () => {
        isDeleting.value = false
      },
      onFinish: () => {
        isDeleting.value = false
      }
    })
  }
}
</script>