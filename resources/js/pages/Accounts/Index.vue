<template>
  <AppLayout title="Accounts">
    <template #header>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ t('accounts.title') }}
        </h2>
        <Button
          @click="openCreateModal"
          class="inline-flex items-center w-full sm:w-auto justify-center"
        >
          <PlusIcon class="w-4 h-4 me-2" />
          {{ t('accounts.create') }}
        </Button>
      </div>
    </template>

    <div class="py-4 sm:py-6 lg:py-8 h-screen overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
        <div class="bg-white dark:bg-black/90 overflow-hidden shadow-sm sm:rounded-lg border dark:border-gray-800 h-full flex flex-col">
          <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100 flex-shrink-0">
            <!-- Compact Header with Create Button -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
              <h3 class="text-base font-medium">{{ t('accounts.title') }}</h3>
              <Button @click="openCreateModal" class="inline-flex items-center w-full sm:w-auto justify-center text-sm h-8">
                <PlusIcon class="w-3 h-3 me-2" />
                {{ t('accounts.create_account') }}
              </Button>
            </div>
            
            <!-- Search Bar -->
            <div class="relative mb-4">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
              <Input
                v-model="searchQuery"
                placeholder="Search accounts by name, type, or currency..."
                class="pl-9 h-9 text-sm"
              />
            </div>
          </div>

          <!-- Scrollable Accounts Container -->
          <div class="flex-1 overflow-y-auto px-4 sm:px-6 pb-4 sm:pb-6">
            <!-- Loading State -->
            <div v-if="isLoading || isRefreshing">
              <AccountSkeleton :count="6" />
            </div>
            
            <!-- Accounts Grid -->
            <div v-else-if="filteredAccounts.length > 0" class="grid gap-3 sm:gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3 auto-rows-max">
              <Card 
                v-for="account in filteredAccounts" 
                :key="account.id" 
                class="overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02] group h-fit max-h-[350px] flex flex-col cursor-pointer"
                @click="$inertia.visit(accountRoutes.show(account.id).url)"
              >
                <!-- Header with Type and Currency -->
                <div class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-3 py-1.5 flex-shrink-0">
                  <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:12px_12px]"></div>
                  
                  <div class="relative flex items-center justify-between">
                    <!-- Account Type -->
                    <div class="flex items-center gap-1 text-slate-300">
                      <Wallet class="w-3 h-3 flex-shrink-0" />
                      <span class="text-xs capitalize truncate">
                        {{ account.type || 'General' }}
                      </span>
                    </div>

                    <!-- Currency and Edit Button -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                      <div class="text-xs text-slate-400 font-mono">
                        {{ account.currency.symbol }}
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        @click.stop="openEditModal(account)"
                        class="h-6 w-6 p-0 text-slate-300 hover:text-white hover:bg-white/10"
                      >
                        <Edit class="w-3 h-3" />
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- Body with Account Name and Balance -->
                <div class="px-3 py-4 flex-1 flex flex-col justify-center">
                  <!-- Account Name and Balance on Same Line -->
                  <div class="flex items-center justify-between gap-4">
                    <!-- Account Name -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-1">
                        <div class="w-0.5 h-3 bg-green-500 rounded-full flex-shrink-0"></div>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                          Account Name
                        </span>
                      </div>
                      <div class="pl-2 flex items-center">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                          <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-white">A</span>
                          </div>
                          <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                              {{ account.name }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Balance -->
                    <div class="flex-shrink-0">
                      <div class="flex items-center gap-2 mb-1 justify-end">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                          Balance
                        </span>
                        <div class="w-0.5 h-3 bg-blue-500 rounded-full flex-shrink-0"></div>
                      </div>
                      <div class="pe-2 flex items-center justify-end">
                        <div class="flex items-center gap-2">
                          <div class="min-w-0">
                            <div 
                              class="text-sm font-semibold text-right"
                              :class="{
                                'text-emerald-600 dark:text-emerald-400': account.balance >= 0,
                                'text-red-600 dark:text-red-400': account.balance < 0
                              }"
                            >
                              {{ account.balance < 0 ? '-' : '' }}{{ account.currency.symbol }}{{ Number(Math.abs(account.balance)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </div>
                            <!-- <div class="text-xs text-slate-500 dark:text-slate-400 text-right">
                              {{ account.currency.code }}
                            </div> -->
                          </div>
                          <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-white">{{ account.currency.symbol }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                
              </Card>
            </div>

            <!-- Empty State -->
            <div v-else-if="!isLoading && !isRefreshing" class="flex items-center justify-center h-full">
              <div class="text-center py-8 px-4">
                <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 dark:bg-gray-800/80 rounded-full flex items-center justify-center mb-4 dark:border dark:border-gray-700">
                  <component :is="searchQuery.trim() ? Search : Wallet" class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                  {{ searchQuery.trim() ? 'No accounts found' : t('dashboard.no_accounts') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto text-sm">
                  {{ searchQuery.trim() ? `No accounts match "${searchQuery.trim()}". Try a different search term.` : 'Get started by creating your first account.' }}
                </p>
                <Button v-if="searchQuery.trim()" @click="searchQuery = ''" variant="outline" class="w-full sm:w-auto me-2">
                  Clear Search
                </Button>
                <Button @click="openCreateModal" class="w-full sm:w-auto">
                  <PlusIcon class="w-4 h-4 me-2" />
                  {{ t('accounts.create_account') }}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Form Modal -->
     <AccountFormModal
       v-model:open="isModalOpen"
       :account="editingAccount"
       :currencies="props.currencies"
       @success="handleModalSuccess"
     />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Plus as PlusIcon, Eye, Edit, Wallet, Search } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import accountRoutes from '@/routes/accounts'
import AccountFormModal from '@/components/AccountFormModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import AccountSkeleton from '@/components/AccountSkeleton.vue'

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
  type: string
  currency_id: number
  currency: Currency
  description?: string | null
  created_at: string
  updated_at: string
}

const props = defineProps<{
  accounts: Account[]
  currencies: Currency[]
}>()

// Modal state
const isModalOpen = ref(false)
const editingAccount = ref<Account | null>(null)

// Loading states
const isLoading = ref(false)
const isRefreshing = ref(false)

// Search functionality
const searchQuery = ref('')

// Computed filtered accounts
const filteredAccounts = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.accounts
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return props.accounts.filter(account => 
    account.name.toLowerCase().includes(query) ||
    account.type.toLowerCase().includes(query) ||
    account.currency.code.toLowerCase().includes(query) ||
    account.currency.name.toLowerCase().includes(query) ||
    (account.description && account.description.toLowerCase().includes(query))
  )
})

const openCreateModal = () => {
  editingAccount.value = null
  isModalOpen.value = true
}

const openEditModal = (account: Account) => {
  editingAccount.value = account
  isModalOpen.value = true
}

const handleModalSuccess = () => {
  // Refresh the page to show updated data
  isRefreshing.value = true
  router.reload({
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}
</script>

<style scoped>
.bg-grid-white\/\[0\.02\] {
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}
</style>