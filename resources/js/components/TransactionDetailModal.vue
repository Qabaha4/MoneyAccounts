<template>
  <Dialog :open="isOpen" @update:open="$emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-2xl p-0 gap-0 overflow-hidden">
      <!-- Accessibility components (visually hidden) -->
      <DialogTitle class="sr-only">
        Transaction Details - {{ transaction?.type?.toUpperCase() }} #{{ transaction?.id }}
      </DialogTitle>
      <DialogDescription class="sr-only">
        View details for {{ transaction?.type }} transaction of {{ transaction?.account.currency.symbol }}{{ parseFloat(transaction?.amount || '0').toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} on {{ transaction?.transaction_date ? new Date(transaction.transaction_date).toLocaleDateString() : '' }}
      </DialogDescription>
      
      <!-- Hero Section with Amount -->
      <div class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-8 py-10">
        <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:20px_20px]"></div>
        
        <div class="relative">
          <!-- Type Badge -->
          <div class="flex items-center justify-between mb-6">
            <Badge 
              :variant="transaction?.type === 'income' ? 'default' : transaction?.type === 'expense' ? 'destructive' : 'secondary'"
              class="text-xs font-semibold px-3 py-1"
            >
              {{ transaction?.type?.toUpperCase() }}
            </Badge>
            <span class="text-xs text-slate-400 font-mono">
              #{{ transaction?.id }}
            </span>
          </div>

          <!-- Amount -->
          <div class="text-center mb-8">
            <div class="text-sm text-slate-400 mb-2 font-medium">TRANSACTION AMOUNT</div>
            <div 
              class="text-5xl font-bold tracking-tight mb-2"
              :class="{
                'text-emerald-400': transaction?.type === 'income',
                'text-red-400': transaction?.type === 'expense',
                'text-blue-400': transaction?.type === 'transfer'
              }"
            >
              {{ transaction?.type === 'income' ? '+' : transaction?.type === 'expense' ? '-' : '' }}{{ transaction?.account.currency.symbol }}{{ getEffectiveAmount(transaction).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </div>
            <div class="text-sm text-slate-400">
              {{ transaction?.account.currency.code }}
            </div>
          </div>

          <!-- Date -->
          <div class="flex items-center justify-center gap-2 text-slate-300">
            <Calendar class="w-4 h-4" />
            <span class="text-sm">
              {{ transaction?.transaction_date ? new Date(transaction.transaction_date).toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              }) : '' }}
            </span>
            <span class="text-slate-500">•</span>
            <span class="text-sm font-mono">
              {{ transaction?.transaction_date ? new Date(transaction.transaction_date).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
              }) : '' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Details Section -->
      <div class="p-8 space-y-6 bg-white dark:bg-slate-950">
        <!-- Description -->
        <div v-if="transaction?.description">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              Description
            </span>
          </div>
          <p class="text-base text-slate-900 dark:text-slate-100 leading-relaxed pl-3">
            {{ transaction.description }}
          </p>
        </div>

        <!-- Account Information -->
        <div class="space-y-4">
          <!-- From Account -->
          <div class="group">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
              <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                From Account
              </span>
            </div>
            <div 
              class="pl-3 flex items-center justify-between cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 rounded-lg p-2 -m-2 transition-colors"
              @click="transaction?.account.id && $emit('navigateToAccount', transaction.account.id)"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                  <CreditCard class="w-5 h-5 text-white" />
                </div>
                <div>
                  <div class="text-base font-semibold text-slate-900 dark:text-slate-100">
                    {{ transaction?.account.name }}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">
                    {{ transaction?.account.currency.code }} Account
                  </div>
                </div>
              </div>
              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <ExternalLink class="w-4 h-4 text-slate-400" />
              </div>
            </div>
          </div>

          <!-- To Account (if transfer) -->
          <div v-if="transaction?.transfer_to_account" class="group">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-1 h-5 bg-cyan-500 rounded-full"></div>
              <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                To Account
              </span>
            </div>
            <div 
              class="pl-3 flex items-center justify-between cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 rounded-lg p-2 -m-2 transition-colors"
              @click="transaction?.transfer_to_account?.id && $emit('navigateToAccount', transaction.transfer_to_account.id)"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-cyan-600 flex items-center justify-center">
                  <ArrowRightLeft class="w-5 h-5 text-white" />
                </div>
                <div>
                  <div class="text-base font-semibold text-slate-900 dark:text-slate-100">
                    {{ transaction.transfer_to_account.name }}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">
                    Destination Account
                  </div>
                </div>
              </div>
              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <ExternalLink class="w-4 h-4 text-slate-400" />
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="!hideActions" class="flex items-center gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
          <Button
            variant="outline"
            @click="transaction && $emit('edit', transaction)"
            class="flex-1 h-11"
          >
            <Edit class="w-4 h-4 me-2" />
            Edit Transaction
          </Button>
          <Button
            variant="destructive"
            @click="transaction && $emit('delete', transaction)"
            :disabled="isDeleting"
            class="flex-1 h-11"
          >
            <Trash2 class="w-4 h-4 me-2" />
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { 
  Dialog, 
  DialogContent,
  DialogTitle,
  DialogDescription
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { 
  Calendar, 
  CreditCard, 
  ArrowRightLeft, 
  Edit, 
  Trash2,
  ExternalLink 
} from 'lucide-vue-next'

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
  type: 'income' | 'expense' | 'transfer'
  amount: string
  description: string | null
  transaction_date: string
  account: Account
  transfer_to_account?: Account | null
  exchange_rate?: string | null
  converted_amount?: string | null
  exchange_rate_source?: string | null
}

interface Props {
  isOpen: boolean
  transaction: Transaction | null
  isDeleting?: boolean
  hideActions?: boolean
}

const { hideActions } = defineProps<Props>()

// Get the effective amount for display (converted amount for cross-currency transfers)
const getEffectiveAmount = (transaction: Transaction | null) => {
  if (!transaction) return 0
  
  if (transaction.type === 'transfer' && transaction.converted_amount) {
    return parseFloat(transaction.converted_amount)
  }
  
  return parseFloat(transaction.amount || '0')
}

defineEmits<{
  'update:isOpen': [value: boolean]
  'edit': [transaction: Transaction]
  'delete': [transaction: Transaction]
  'navigateToAccount': [accountId: number]
}>()
</script>

<style scoped>
.bg-grid-white\/\[0\.02\] {
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}
</style>