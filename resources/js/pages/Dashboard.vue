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
import { Plus, TrendingUp, TrendingDown, ArrowUpDown, Eye, ArrowRight, Wallet, Receipt, ChevronDown } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TransactionDetailModal from '@/components/TransactionDetailModal.vue';
import HeroSection from '@/components/HeroSection.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import DashboardSkeleton from '@/components/DashboardSkeleton.vue';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_active: boolean;
    decimal_places: number;
}

interface Account {
    id: number;
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
    id: number;
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
}

const props = defineProps<Props>();

const { t } = useI18n();

// Mobile detection and collapse state
const isMobile = ref(false);
const isTransactionsOpen = ref(true);

// Transaction detail modal state
const isTransactionDetailModalOpen = ref(false);
const selectedTransaction = ref<Transaction | null>(null);

// Loading states
const isLoading = ref(false);
const isRefreshing = ref(false);

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

// Computed property to get primary currency symbol
const primaryCurrencySymbol = computed(() => {
    // Use the currency symbol from the first account, or default to $ if no accounts
    return props.accounts.length > 0 ? props.accounts[0].currency.symbol : '$';
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

const getTransactionBgColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'bg-emerald-100 dark:bg-emerald-900/30';
        case 'expense':
            return 'bg-rose-100 dark:bg-rose-900/30';
        case 'transfer':
            return 'bg-blue-100 dark:bg-blue-900/30';
        default:
            return 'bg-slate-100 dark:bg-slate-900/30';
    }
};

const getTransactionVariant = (type: string) => {
    switch (type) {
        case 'income':
            return 'default';
        case 'expense':
            return 'destructive';
        case 'transfer':
            return 'secondary';
        default:
            return 'outline';
    }
};

// Transaction modal functions
const openTransactionDetail = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    isTransactionDetailModalOpen.value = true;
};

const handleNavigateToAccount = (accountId: number) => {
    window.location.href = accounts.show({ account: accountId }).url;
};

// Helper methods for enhanced transaction display
const getTransactionAmountColor = (transaction: Transaction) => {
    if (transaction.type === 'transfer' && transaction.is_incoming_transfer) {
        return 'text-emerald-600 dark:text-emerald-400';
    }
    return getTransactionColor(transaction.type);
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
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between gap-2 py-0.5">
                <h2 class="font-semibold text-base sm:text-lg text-gray-900 dark:text-gray-100 truncate">
                    {{ t('dashboard.title') }}
                </h2>
                <div class="flex items-center gap-1">
                    <Button size="sm" variant="ghost" class="h-7 w-7 p-0 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20" as-child>
                        <Link :href="accounts.index().url">
                            <Wallet class="w-4 h-4" />
                        </Link>
                    </Button>
                    <Button size="sm" variant="ghost" class="h-7 w-7 p-0 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20" as-child>
                        <Link :href="transactions.index().url">
                            <Receipt class="w-4 h-4" />
                        </Link>
                    </Button>
                    <Button size="sm" class="h-7 px-2.5 text-xs bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-sm" as-child>
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
                <!-- Loading State -->
                <div v-if="isLoading || isRefreshing">
                    <DashboardSkeleton />
                </div>
                
                <!-- Dashboard Content -->
                <div class="space-y-4" v-else>
                    <!-- Dashboard Balance Hero -->
                    <HeroSection 
                        :main-sec-val="`${Number(props.totalBalance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${primaryCurrencySymbol}`"
                        :main-sec-label="'Total Balance'"
                        :sub-sec-p1-val="props.accounts.length.toString()"
                        :sub-sec-p1-label="'Total Wallets'"
                        :sub-sec-p2-val="props.accounts.filter(account => account.is_active).length.toString()"
                        :sub-sec-p2-label="'Active Wallets'"
                        :sub-sec-p3-val="props.recentTransactions.length.toString()"
                        :sub-sec-p3-label="'Latest Activity'"
                    />

                    <!-- Account Overview and Recent Transactions -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Account Overview -->
                    <Card>
                        <CardContent class="p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                    {{ t('dashboard.accounts_overview') }}
                                </h3>
                                <Button variant="ghost" size="sm" class="h-8" as-child>
                                    <Link :href="accounts.index().url">
                                        <ArrowRight class="w-4 h-4" />
                                    </Link>
                                </Button>
                            </div>

                            <div v-if="props.accounts.length > 0" class="space-y-3">
                                <div 
                                    v-for="account in props.accounts.slice(0, 5)" 
                                    :key="account.id"
                                    class="group hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden rounded-xl p-3 border"
                                    @click="$inertia.visit(accounts.show({ account: account.id }).url)"
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Icon -->
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                                            <Wallet class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                                        </div>
                                        
                                        <!-- Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate mb-1">
                                                {{ account.name }}
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <Badge :variant="account.is_active ? 'default' : 'secondary'" class="text-xs px-2 py-0.5">
                                                    {{ account.is_active ? t('common.active') : t('common.inactive') }}
                                                </Badge>
                                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ account.currency.code }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Balance -->
                                        <div class="text-right flex-shrink-0">
                                            <div 
                                                class="text-sm font-bold"
                                                :class="account.balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                            >
                                                {{ account.currency.symbol }}{{ Number(account.balance).toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8">
                                <div class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                    <Wallet class="w-8 h-8 text-slate-400" />
                                </div>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2">
                                    {{ t('dashboard.no_accounts') }}
                                </h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
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
                                        class="flex items-center gap-2 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                        :class="{ 'cursor-pointer': isMobile, 'cursor-default': !isMobile }"
                                    >
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                            {{ t('dashboard.recent_transactions') }}
                                        </h3>
                                        <ChevronDown 
                                            v-if="isMobile"
                                            class="w-4 h-4 transition-transform duration-200"
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
                                            class="group hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden border-l-4 rounded-xl p-3"
                                            :class="{
                                                'border-l-emerald-500': transaction.type === 'income',
                                                'border-l-rose-500': transaction.type === 'expense',
                                                'border-l-blue-500': transaction.type === 'transfer'
                                            }"
                                            @click="openTransactionDetail(transaction)"
                                        >
                                            <div class="flex items-center gap-3">
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
                                                    <h4 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate mb-1">
                                                        {{ transaction.description || t('dashboard.no_description') }}
                                                    </h4>
                                                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                                        <template v-if="transaction.type === 'transfer'">
                                                            <template v-if="transaction.is_incoming_transfer">
                                                                <span class="font-medium cursor-pointer hover:text-blue-600 dark:hover:text-blue-400" 
                                                                       @click="handleNavigateToAccount(transaction.account.id)">
                                                                     {{ transaction.account.name }}
                                                                 </span>
                                                                 <span>→</span>
                                                                 <span v-if="transaction.transfer_to_account" class="font-medium cursor-pointer hover:text-blue-600 dark:hover:text-blue-400" 
                                                                       @click="handleNavigateToAccount(transaction.transfer_to_account.id)">
                                                                     {{ transaction.transfer_to_account.name }}
                                                                 </span>
                                                                <span class="text-emerald-600 dark:text-emerald-400 text-xs">
                                                                    ({{ t('transfer_in') }})
                                                                </span>
                                                            </template>
                                                            <template v-else>
                                                                <span class="font-medium cursor-pointer hover:text-blue-600 dark:hover:text-blue-400" 
                                                                       @click="handleNavigateToAccount(transaction.account.id)">
                                                                     {{ transaction.account.name }}
                                                                 </span>
                                                                 <span>→</span>
                                                                 <span v-if="transaction.transfer_to_account" class="font-medium cursor-pointer hover:text-blue-600 dark:hover:text-blue-400" 
                                                                       @click="handleNavigateToAccount(transaction.transfer_to_account.id)">
                                                                     {{ transaction.transfer_to_account.name }}
                                                                 </span>
                                                                <span class="text-rose-600 dark:text-rose-400 text-xs">
                                                                    ({{ t('transfer_out') }})
                                                                </span>
                                                            </template>
                                                        </template>
                                                        <template v-else>
                                                            <span class="font-medium cursor-pointer hover:text-blue-600 dark:hover:text-blue-400" 
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
                                                        {{ getTransactionAmountPrefix(transaction) }}{{ getTransactionCurrency(transaction).symbol }}{{ Number(transaction.amount).toLocaleString() }}
                                                    </div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                                        {{ new Date(transaction.transaction_date).toLocaleDateString() }} {{ new Date(transaction.transaction_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="text-center py-8">
                                        <div class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                            <Receipt class="w-8 h-8 text-slate-400" />
                                        </div>
                                        <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2">
                                            {{ t('dashboard.no_transactions') }}
                                        </h4>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
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
