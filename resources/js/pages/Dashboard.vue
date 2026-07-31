<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import accounts from '@/routes/accounts';
import transactions from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Plus, TrendingUp, TrendingDown, ArrowUpDown, Eye, ArrowRight, Wallet, Receipt, ChevronDown, Landmark, BarChart3, List, ArrowRightLeft } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TransactionDetailModal from '@/components/TransactionDetailModal.vue';
import HeroSection from '@/components/HeroSection.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';

import { useFormatting } from '@/composables/useFormatting';
import { useAccountType } from '@/composables/useAccountType';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_active: boolean;
    decimal_places: number;
}

interface Account {
    id: string;
    name: string;
    description: string | null;
    type: string;
    balance: number;
    initial_balance: number;
    is_active: boolean;
    currency: Currency;
    transactions: Transaction[];
}

interface Transaction {
    id: string;
    type: 'income' | 'expense' | 'transfer';
    amount: string;
    description: string;
    transaction_date: string;
    account: Account;
    transfer_to_account?: Account;
    is_incoming_transfer?: boolean;
}

interface Props {
    accounts: Account[];
    totalBalance: number;
    recentTransactions: Transaction[];
    userCurrencies: Currency[];
    balancesByCurrency: Record<number, number>;
}

const props = defineProps<Props>();

const { t } = useI18n();
const { formatDateTime: fmtDateTime, formatDate: fmtDate, formatAmount, formatCurrency } = useFormatting();
const { getAccountTypeStyle } = useAccountType();

// Mobile detection and collapse state
const isMobile = ref(false);
const isTransactionsOpen = ref(true);

// Transaction detail modal state
const isTransactionDetailModalOpen = ref(false);
const selectedTransaction = ref<Transaction | null>(null);



// Currency selector state
const selectedCurrency = ref<Currency | null>(null);

const checkMobile = () => {
    isMobile.value = window.innerWidth < 768; // md breakpoint
    // Set initial state based on screen size
    if (isMobile.value) {
        isTransactionsOpen.value = false; // Closed by default on mobile
    } else {
        isTransactionsOpen.value = true; // Always open on desktop
    }
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    // Initialize selected currency to the first available currency or primary currency
    if (props.userCurrencies.length > 0) {
        // Try to find the most common currency among accounts, or use the first one
        const currencyUsage = props.accounts.reduce((acc, account) => {
            acc[account.currency.id] = (acc[account.currency.id] || 0) + 1;
            return acc;
        }, {} as Record<number, number>);
        
        const mostUsedCurrencyId = Object.keys(currencyUsage).reduce((a, b) => 
            currencyUsage[Number(a)] > currencyUsage[Number(b)] ? a : b
        );
        
        selectedCurrency.value = props.userCurrencies.find(c => c.id === Number(mostUsedCurrencyId)) || props.userCurrencies[0];
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: dashboard().url,
    },
];

// Computed property for total balance in selected currency (sum of accounts in that currency only)
const convertedTotalBalance = computed(() => {
    if (!selectedCurrency.value) return props.totalBalance;
    
    return props.balancesByCurrency[selectedCurrency.value.id] || 0;
});

// Computed property for selected currency symbol
const selectedCurrencySymbol = computed(() => {
    return selectedCurrency.value?.symbol || '$';
});

const getTransactionIcon = (type: string) => {
    switch (type) {
        case 'income':
            return TrendingUp;
        case 'expense':
            return TrendingDown;
        case 'transfer':
            return ArrowUpDown;
        default:
            return ArrowUpDown;
    }
};

const getTransactionAmountColor = (transaction: Transaction) => {
    if (transaction.type === 'transfer' && transaction.is_incoming_transfer) {
        return 'text-success';
    }
    switch (transaction.type) {
        case 'income':
            return 'text-success';
        case 'expense':
            return 'text-destructive';
        case 'transfer':
            return 'text-foreground';
        default:
            return 'text-muted-foreground';
    }
};

// Transaction modal functions
const openTransactionDetail = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    isTransactionDetailModalOpen.value = true;
};

const handleNavigateToAccount = (accountId: string) => {
    window.location.href = accounts.show({ account: accountId }).url;
};

const getTransactionAmountPrefix = (transaction: Transaction) => {
    if (transaction.type === 'income') {
        return '+';
    } else if (transaction.type === 'expense') {
        return '-';
    } else if (transaction.type === 'transfer') {
        return transaction.is_incoming_transfer ? '+' : '-';
    }
    return '';
};

const getTransactionCurrency = (transaction: Transaction) => {
    if (transaction.type === 'transfer' && transaction.is_incoming_transfer && transaction.transfer_to_account) {
        return transaction.transfer_to_account.currency;
    }
    return transaction.account.currency;
};

// Currency change handler
const handleCurrencyChange = (currency: Currency) => {
    selectedCurrency.value = currency;
};
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between gap-2 py-0.5">
                <h2 class="font-semibold text-base sm:text-lg text-foreground truncate">
                    {{ t('dashboard.title') }}
                </h2>
                <div class="flex items-center gap-1">
                    <Button size="sm" variant="ghost" class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground hover:bg-accent/50" as-child>
                        <Link :href="accounts.index().url">
                            <Wallet class="w-4 h-4" />
                        </Link>
                    </Button>
                    <Button size="sm" variant="ghost" class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground hover:bg-accent/50" as-child>
                        <Link :href="transactions.index().url">
                            <Receipt class="w-4 h-4" />
                        </Link>
                    </Button>
                    <Button size="sm" class="h-7 px-2.5 text-xs" as-child>
                        <Link :href="transactions.index().url">
                            <Plus class="w-3 h-3 sm:me-1" />
                            <span class="hidden sm:inline font-medium">{{ t('dashboard.add_transaction') }}</span>
                        </Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-2 sm:py-4">
            <div class="max-w-6xl mx-auto px-2 sm:px-4 lg:px-6 space-y-3 sm:space-y-5">
                <!-- Dashboard Content -->
                <div class="space-y-4">
                    <!-- Dashboard Balance Hero -->
                    <HeroSection 
                        :main-sec-val="`${formatAmount(convertedTotalBalance)} ${selectedCurrencySymbol}`"
                    :main-sec-label="t('dashboard.total_balance')"
                    :sub-sec-p1-val="props.accounts.length.toString()"
                    :sub-sec-p1-label="t('dashboard.total_wallets')"
                    :sub-sec-p2-val="props.accounts.filter(account => account.is_active).length.toString()"
                    :sub-sec-p2-label="t('dashboard.active_wallets')"
                    :sub-sec-p3-val="props.recentTransactions.length.toString()"
                    :sub-sec-p3-label="t('dashboard.latest_activity')"
                        :show-currency-selector="true"
                        :currencies="props.userCurrencies"
                        :selected-currency="selectedCurrency || undefined"
                        @currency-change="handleCurrencyChange"
                    />

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <Link :href="accounts.index().url" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-blue-500/10 to-purple-500/10 border border-blue-500/10 hover:border-blue-500/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow-lg shadow-blue-600/20">
                                <Landmark class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-foreground group-hover:text-blue-400 transition-colors">{{ t('dashboard.all_accounts') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('dashboard.total_count', { count: props.accounts.length }) }}</div>
                            </div>
                        </Link>
                        <Link :href="transactions.index().url" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/10 hover:border-emerald-500/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-600/20">
                                <ArrowRightLeft class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-foreground group-hover:text-emerald-400 transition-colors">{{ t('transactions.title') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('dashboard.recent_count', { count: props.recentTransactions.length }) }}</div>
                            </div>
                        </Link>
                        <Link :href="accounts.index().url + '?sort_by=balance_desc'" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 border border-amber-500/10 hover:border-amber-500/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-600 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-600/20">
                                <BarChart3 class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-foreground group-hover:text-amber-400 transition-colors">{{ t('dashboard.top_balances') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('dashboard.highest_first') }}</div>
                            </div>
                        </Link>
                        <Link :href="accounts.index().url + '?sort_by=transacted_desc'" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-rose-500/10 to-pink-500/10 border border-rose-500/10 hover:border-rose-500/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-600 to-pink-500 flex items-center justify-center shadow-lg shadow-rose-600/20">
                                <List class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-foreground group-hover:text-rose-400 transition-colors">{{ t('dashboard.recent_activity') }}</div>
                                <div class="text-xs text-muted-foreground">{{ t('dashboard.last_transaction') }}</div>
                            </div>
                        </Link>
                    </div>

                    <!-- Account Overview and Recent Transactions -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Account Overview -->
                    <Card>
                        <CardContent class="p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-foreground">
                                    {{ t('dashboard.accounts_overview') }}
                                </h3>
                                <Button variant="ghost" size="sm" class="h-8" as-child>
                                    <Link :href="accounts.index().url">
                                        <ArrowRight class="w-4 h-4" />
                                    </Link>
                                </Button>
                            </div>

                            <div v-if="props.accounts.length > 0" class="space-y-2">
                                <div 
                                    v-for="account in props.accounts.slice(0, 5)" 
                                    :key="account.id"
                                    class="group hover:bg-accent/30 transition-all duration-200 cursor-pointer overflow-hidden rounded-xl p-3 border border-border/50"
                                    @click="$inertia.visit(accounts.show({ account: account.id }).url)"
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Icon -->
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                             :class="getAccountTypeStyle(account.type).gradientClass">
                                            <component :is="getAccountTypeStyle(account.type).icon" class="w-5 h-5 text-white" />
                                        </div>
                                        
                                        <!-- Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-sm text-foreground truncate mb-1">
                                                {{ account.name }}
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <Badge 
                                                    variant="secondary"
                                                    class="text-xs px-2 py-0.5"
                                                >
                                                    {{ account.is_active ? t('common.active') : t('common.inactive') }}
                                                </Badge>
                                                <span class="text-xs text-muted-foreground">{{ account.currency.code }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Balance -->
                                        <div class="text-right flex-shrink-0">
                                            <div 
                                                class="text-sm font-bold"
                                                :class="account.balance >= 0 ? 'text-success' : 'text-destructive'"
                                            >
                                                {{ formatCurrency(account.balance, account.currency) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8">
                                <div class="w-16 h-16 rounded-full bg-secondary/50 flex items-center justify-center mx-auto mb-4">
                                    <Wallet class="w-8 h-8 text-muted-foreground" />
                                </div>
                                <h4 class="text-lg font-semibold text-foreground mb-2">
                                    {{ t('dashboard.no_accounts') }}
                                </h4>
                                <p class="text-sm text-muted-foreground mb-4">
                                    {{ t('dashboard.get_started_account') }}
                                </p>
                                <Button size="sm" class="h-9" as-child>
                                    <Link :href="accounts.index().url">
                                        <Plus class="w-4 h-4 me-2" />
                                        {{ t('dashboard.create_account') }}
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Transactions -->
                    <Card>
                        <CardContent class="p-4 sm:p-6">
                            <Collapsible v-model:open="isTransactionsOpen" :disabled="!isMobile">
                                <div class="flex items-center justify-between mb-6">
                                    <CollapsibleTrigger 
                                        class="flex items-center gap-2 hover:text-foreground transition-colors"
                                        :class="{ 'cursor-pointer': isMobile, 'cursor-default': !isMobile }"
                                    >
                                        <h3 class="text-lg font-semibold text-foreground">
                                            {{ t('dashboard.recent_transactions') }}
                                        </h3>
                                        <ChevronDown 
                                            v-if="isMobile"
                                            class="w-4 h-4 transition-transform duration-200 text-muted-foreground"
                                            :class="{ 'rotate-180': isTransactionsOpen }"
                                        />
                                    </CollapsibleTrigger>
                                    <Button variant="ghost" size="sm" class="h-8" as-child>
                                        <Link :href="transactions.index().url">
                                            <ArrowRight class="w-4 h-4" />
                                        </Link>
                                    </Button>
                                </div>

                                <CollapsibleContent class="space-y-0">
                                    <div v-if="props.recentTransactions.length > 0" class="space-y-2">
                                        <div 
                                            v-for="transaction in props.recentTransactions.slice(0, 6)" 
                                            :key="transaction.id"
                                            class="group hover:bg-accent/30 transition-all duration-200 cursor-pointer overflow-hidden rounded-xl p-3 border border-border/50"
                                            @click="openTransactionDetail(transaction)"
                                        >
                                            <div class="flex items-center gap-3">
                                                <!-- Icon -->
                                                <div class="w-10 h-10 rounded-xl bg-secondary/50 flex items-center justify-center flex-shrink-0">
                                                    <component 
                                                        :is="getTransactionIcon(transaction.type)" 
                                                        class="w-5 h-5 text-muted-foreground"
                                                    />
                                                </div>
                                                
                                                <!-- Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-semibold text-sm text-foreground truncate mb-1">
                                                        {{ transaction.description || t('dashboard.no_description') }}
                                                    </h4>
                                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <template v-if="transaction.type === 'transfer'">
                                                            <template v-if="transaction.is_incoming_transfer">
                                                                <span class="font-medium cursor-pointer hover:text-foreground" 
                                                                       @click="handleNavigateToAccount(transaction.account.id)">
                                                                     {{ transaction.account.name }}
                                                                 </span>
                                                                 <span>→</span>
                                                                 <span v-if="transaction.transfer_to_account" class="font-medium cursor-pointer hover:text-foreground" 
                                                                       @click="handleNavigateToAccount(transaction.transfer_to_account.id)">
                                                                     {{ transaction.transfer_to_account.name }}
                                                                 </span>
                                                                <span class="text-success text-xs">
                                                                    ({{ t('transfer_in') }})
                                                                </span>
                                                            </template>
                                                            <template v-else>
                                                                <span class="font-medium cursor-pointer hover:text-foreground" 
                                                                       @click="handleNavigateToAccount(transaction.account.id)">
                                                                     {{ transaction.account.name }}
                                                                 </span>
                                                                 <span>→</span>
                                                                 <span v-if="transaction.transfer_to_account" class="font-medium cursor-pointer hover:text-foreground" 
                                                                       @click="handleNavigateToAccount(transaction.transfer_to_account.id)">
                                                                     {{ transaction.transfer_to_account.name }}
                                                                 </span>
                                                                <span class="text-destructive text-xs">
                                                                    ({{ t('transfer_out') }})
                                                                </span>
                                                            </template>
                                                        </template>
                                                        <template v-else>
                                                            <span class="font-medium cursor-pointer hover:text-foreground" 
                                                                  @click="handleNavigateToAccount(transaction.account.id)">
                                                                {{ transaction.account.name }}
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>
                                                
                                                <!-- Amount -->
                                                <div class="text-right flex-shrink-0">
                                                    <div 
                                                        class="text-sm font-bold"
                                                        :class="getTransactionAmountColor(transaction)"
                                                    >
                                                        {{ getTransactionAmountPrefix(transaction) }}{{ getTransactionCurrency(transaction).symbol }}{{ formatAmount(transaction.amount) }}
                                                    </div>
                                                    <div class="text-xs text-muted-foreground">
                                                        {{ fmtDateTime(transaction.transaction_date) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="text-center py-8">
                                        <div class="w-16 h-16 rounded-full bg-secondary/50 flex items-center justify-center mx-auto mb-4">
                                            <Receipt class="w-8 h-8 text-muted-foreground" />
                                        </div>
                                        <h4 class="text-lg font-semibold text-foreground mb-2">
                                            {{ t('dashboard.no_transactions') }}
                                        </h4>
                                        <p class="text-sm text-muted-foreground mb-4">
                                            {{ t('dashboard.get_started_transaction') }}
                                        </p>
                                        <Button size="sm" class="h-9" as-child>
                                            <Link :href="transactions.index().url">
                                                <Plus class="w-4 h-4 me-2" />
                                                {{ t('dashboard.add_transaction') }}
                                            </Link>
                                        </Button>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </CardContent>
                    </Card>
                </div>
                </div>
            </div>
        </div>

        <!-- Transaction Detail Modal -->
        <TransactionDetailModal
            v-model:is-open="isTransactionDetailModalOpen"
            :transaction="selectedTransaction"
            :hide-actions="true"
            @navigate-to-account="handleNavigateToAccount"
        />
    </AppLayout>
</template>
