<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { 
    Command, 
    CommandInput, 
    CommandList, 
    CommandEmpty, 
    CommandGroup, 
    CommandItem 
} from '@/components/ui/command';
import { Dialog, DialogContent, DialogTrigger, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Wallet, TrendingUp, TrendingDown, ArrowUpDown } from 'lucide-vue-next';
import TransactionDetailModal from '@/components/TransactionDetailModal.vue';
import type { BreadcrumbItemType } from '@/types';

// TypeScript interfaces for search results
interface Account {
    id: number;
    name: string;
    description?: string;
    is_active?: boolean;
    balance?: number;
    currency?: {
        code: string;
        symbol: string;
    };
}

interface SearchTransaction {
    id: number;
    account_id: number;
    description: string;
    amount: number;
    currency: string;
    type: 'income' | 'expense' | 'transfer';
    transaction_date?: string;
    account: {
        name: string;
    };
    transfer_to_account?: {
        name: string;
    };
}

interface SearchResults {
    accounts: Account[];
    transactions: SearchTransaction[];
}

const props = defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

// Reactive variables for search functionality
const spotlightOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<SearchResults>({ accounts: [], transactions: [] });
const isSearching = ref(false);

// Transaction modal state
const isTransactionModalOpen = ref(false);
const selectedTransaction = ref<any>(null);

let debounceTimeout: number | null = null;

// Watch for search query changes
watch(searchQuery, (newQuery: string) => {
    handleSearch(newQuery);
});

// Search function with debouncing
const handleSearch = async (query: string) => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout);
    }

    // Clear results immediately if query is empty
    if (query.trim().length === 0) {
        searchResults.value = { accounts: [], transactions: [] };
        isSearching.value = false;
        return;
    }

    // Show loading state immediately
    isSearching.value = true;

    // For very short queries (1-2 characters), use shorter debounce
    const debounceTime = query.trim().length <= 2 ? 100 : 150;

    debounceTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/search?query=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (response.ok) {
                const data = await response.json();
                searchResults.value = data;
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            isSearching.value = false;
        }
    }, debounceTime);
};

const selectItem = (type: 'account' | 'transaction', id: number) => {
    if (type === 'account') {
        router.visit(`/accounts/${id}`);
    } else {
        // Find the transaction in search results and open modal
        const transaction = searchResults.value.transactions.find(t => t.id === id);
        if (transaction) {
            // Convert the transaction data to match the expected format
            selectedTransaction.value = {
                id: transaction.id,
                type: transaction.type,
                amount: transaction.amount.toString(),
                description: transaction.description,
                transaction_date: transaction.transaction_date || new Date().toISOString(),
                account: {
                    id: transaction.account_id,
                    name: transaction.account.name,
                    currency: {
                        symbol: transaction.currency === 'USD' ? '$' : transaction.currency === 'EUR' ? '€' : transaction.currency,
                        code: transaction.currency
                    }
                },
                transfer_to_account: transaction.transfer_to_account ? {
                    id: 0, // We don't have the ID from search results
                    name: transaction.transfer_to_account.name,
                    currency: {
                        symbol: transaction.currency === 'USD' ? '$' : transaction.currency === 'EUR' ? '€' : transaction.currency,
                        code: transaction.currency
                    }
                } : null
            };
            isTransactionModalOpen.value = true;
        }
    }
    spotlightOpen.value = false;
    searchQuery.value = '';
    searchResults.value = { accounts: [], transactions: [] };
};

// Handle navigation to account from transaction modal
const handleNavigateToAccount = (accountId: number) => {
    isTransactionModalOpen.value = false;
    router.visit(`/accounts/${accountId}`);
};

// Keyboard shortcut handler
const handleKeydown = (event: KeyboardEvent) => {
    // Handle Ctrl+K or Cmd+K for search
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        event.stopPropagation();
        spotlightOpen.value = true;
        return;
    }
    
    // Handle Escape to close search
    if (event.key === 'Escape' && spotlightOpen.value) {
        event.preventDefault();
        event.stopPropagation();
        spotlightOpen.value = false;
        searchQuery.value = '';
        searchResults.value = { accounts: [], transactions: [] };
        return;
    }
};

// Lifecycle hooks for keyboard shortcuts
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(amount);
};

const formatTransactionAmount = (amount: number, type: string, currency: string) => {
    // For expenses, display as negative
    const displayAmount = type === 'expense' ? -Math.abs(amount) : amount;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(displayAmount);
};

// Helper functions for transaction styling
const getTransactionIcon = (type: string) => {
    switch (type) {
        case 'income':
            return TrendingUp;
        case 'expense':
            return TrendingDown;
        case 'transfer':
            return ArrowUpDown;
        default:
            return TrendingUp;
    }
};

const getTransactionBgColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'bg-emerald-100 dark:bg-emerald-900/30';
        case 'expense':
            return 'bg-rose-100 dark:bg-rose-900/30';
        case 'transfer':
            return 'bg-blue-100 dark:bg-blue-900/30';
        default:
            return 'bg-slate-100 dark:bg-slate-800';
    }
};

const getTransactionColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'expense':
            return 'text-rose-600 dark:text-rose-400';
        case 'transfer':
            return 'text-blue-600 dark:text-blue-400';
        default:
            return 'text-slate-600 dark:text-slate-400';
    }
};

const getTransactionBorderColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'border-l-emerald-500';
        case 'expense':
            return 'border-l-rose-500';
        case 'transfer':
            return 'border-l-blue-500';
        default:
            return 'border-l-slate-500';
    }
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ms-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Spotlight Search Trigger -->
        <div class="ml-auto">
            <Button
                variant="outline"
                @click="spotlightOpen = true"
                class="w-[200px] justify-start text-sm text-muted-foreground md:w-[300px] lg:w-[400px]"
            >
                <Search class="mr-2 h-4 w-4 shrink-0" />
                <span>Search...</span>
                <kbd class="pointer-events-none ml-auto inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100">
                    <span class="text-xs">⌘</span>K
                </kbd>
            </Button>
        </div>

        <!-- Spotlight Dialog -->
        <Dialog v-model:open="spotlightOpen">
            <DialogContent class="max-w-2xl p-0 shadow-2xl border-0">
                <!-- Accessibility components (visually hidden) -->
                <DialogTitle class="sr-only">
                    Search Accounts and Transactions
                </DialogTitle>
                <DialogDescription class="sr-only">
                    Search through your accounts and transactions. Use the search field to find specific items, then click on any result to view details.
                </DialogDescription>
                
                <Command class="[&_[cmdk-group-heading]]:px-4 [&_[cmdk-group-heading]]:py-2 [&_[cmdk-group-heading]]:font-semibold [&_[cmdk-group-heading]]:text-sm [&_[cmdk-group-heading]]:text-slate-600 [&_[cmdk-group-heading]]:dark:text-slate-400 [&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 [&_[cmdk-group]]:px-0 [&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5 [&_[cmdk-input]]:h-14 [&_[cmdk-input]]:text-base [&_[cmdk-item]]:px-0 [&_[cmdk-item]]:py-0 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5 rounded-xl overflow-hidden">
                    <CommandInput 
                        v-model="searchQuery"
                        placeholder="Search accounts and transactions..."
                        class="border-0 focus:ring-0"
                    />
                    <CommandList class="max-h-[400px] overflow-y-auto px-2 pb-2">
                        <CommandEmpty v-if="!isSearching">
                            {{ searchQuery ? `No results found for "${searchQuery}"` : 'Start typing to search...' }}
                        </CommandEmpty>
                        
                        <!-- Loading State -->
                        <CommandEmpty v-if="isSearching">
                            <div class="flex items-center justify-center py-6">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                                <span class="ml-2">Searching...</span>
                            </div>
                        </CommandEmpty>

                        <!-- Accounts Group -->
                        <CommandGroup v-if="searchResults.accounts.length > 0" heading="Accounts">
                            <CommandItem
                                v-for="account in searchResults.accounts"
                                :key="`account-${account.id}`"
                                :value="`account-${account.id}-${account.name}`"
                                @select="selectItem('account', account.id)"
                                class="cursor-pointer p-3 hover:bg-accent/50 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-3 w-full">
                                    <!-- Icon -->
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                                        <Wallet class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate mb-1">
                                            {{ account.name }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Badge 
                                                :variant="'outline'" 
                                                :class="account.is_active ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700'"
                                                class="text-xs px-2 py-0.5"
                                            >
                                                {{ account.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ account.currency?.code || 'USD' }}</span>
                                        </div>
                                        <div v-if="account.description" class="text-xs text-muted-foreground mt-1 truncate">
                                            {{ account.description }}
                                        </div>
                                    </div>
                                    
                                    <!-- Balance -->
                                    <div class="text-right flex-shrink-0">
                                        <div 
                                            class="text-sm font-bold"
                                            :class="(account.balance || 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                        >
                                            {{ account.currency?.symbol || '$' }}{{ Number(account.balance || 0).toLocaleString() }}
                                        </div>
                                    </div>
                                </div>
                            </CommandItem>
                        </CommandGroup>

                        <!-- Transactions Group -->
                        <CommandGroup v-if="searchResults.transactions.length > 0" heading="Transactions">
                            <CommandItem
                                v-for="transaction in searchResults.transactions"
                                :key="`transaction-${transaction.id}`"
                                :value="`transaction-${transaction.id}-${transaction.description}`"
                                @select="selectItem('transaction', transaction.id)"
                                class="cursor-pointer p-3 hover:bg-accent/50 transition-colors duration-200"
                            >
                                <div 
                                    class="flex items-center gap-3 w-full border-l-4 rounded-r-lg pl-3 -ml-3"
                                    :class="getTransactionBorderColor(transaction.type)"
                                >
                                    <!-- Icon -->
                                    <div 
                                        class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                        :class="getTransactionBgColor(transaction.type)"
                                    >
                                        <component 
                                            :is="getTransactionIcon(transaction.type)" 
                                            class="w-5 h-5"
                                            :class="getTransactionColor(transaction.type)"
                                        />
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate mb-1">
                                            {{ transaction.description || 'No description' }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span class="font-medium">{{ transaction.account.name }}</span>
                                            <span v-if="transaction.type === 'transfer' && transaction.transfer_to_account">
                                                → {{ transaction.transfer_to_account.name }}
                                            </span>
                                        </div>
                                        <div v-if="transaction.transaction_date" class="text-xs text-muted-foreground mt-1">
                                            {{ new Date(transaction.transaction_date).toLocaleString(undefined, { 
                                                year: 'numeric', 
                                                month: 'short', 
                                                day: 'numeric', 
                                                hour: '2-digit', 
                                                minute: '2-digit' 
                                            }) }}
                                        </div>
                                    </div>
                                    
                                    <!-- Amount -->
                                    <div class="text-right flex-shrink-0">
                                        <div 
                                            class="text-sm font-bold"
                                            :class="getTransactionColor(transaction.type)"
                                        >
                                            {{ formatTransactionAmount(transaction.amount, transaction.type, transaction.currency) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground capitalize">
                                            {{ transaction.type }}
                                        </div>
                                    </div>
                                </div>
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </DialogContent>
        </Dialog>
    </header>

    <!-- Transaction Detail Modal -->
    <TransactionDetailModal
        v-model:is-open="isTransactionModalOpen"
        :transaction="selectedTransaction"
        :hide-actions="true"
        @navigate-to-account="handleNavigateToAccount"
    />
</template>
