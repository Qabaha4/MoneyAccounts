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
import { Plus, TrendingUp, TrendingDown, ArrowUpDown, Eye } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

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
    amount: number;
    description: string;
    transaction_date: string;
    account: Account;
    transfer_to_account?: Account;
}

interface Props {
    accounts: Account[];
    totalBalance: number;
    recentTransactions: Transaction[];
}

const props = defineProps<Props>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: dashboard().url,
    },
];

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
            return 'text-green-600';
        case 'expense':
            return 'text-red-600';
        case 'transfer':
            return 'text-blue-600';
        default:
            return 'text-gray-600';
    }
};
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Account Summary Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Total Balance Card -->
                <Card class="relative overflow-hidden">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.total_balance') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="props.totalBalance >= 0 ? 'text-green-600' : 'text-red-600'">
                            ${{ Number(props.totalBalance).toLocaleString() }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Across {{ props.accounts.length }} account{{ props.accounts.length !== 1 ? 's' : '' }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Active Accounts Card -->
                <Card class="relative overflow-hidden">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.active_accounts') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.accounts.filter(account => account.is_active).length }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ props.accounts.filter(account => !account.is_active).length }} {{ t('dashboard.inactive') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Quick Actions Card -->
                <Card class="relative overflow-hidden">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.quick_actions') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button size="sm" class="w-full" as-child>
                            <Link :href="transactions.create().url">
                                <Plus class="w-4 h-4 mr-2" />
                                {{ t('dashboard.add_transaction') }}
                            </Link>
                        </Button>
                        <Button size="sm" variant="outline" class="w-full" as-child>
                            <Link :href="accounts.create().url">
                                <Plus class="w-4 h-4 mr-2" />
                                {{ t('dashboard.add_account') }}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Transactions and Account Details -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Recent Transactions -->
                <Card class="relative flex-1">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ t('dashboard.recent_transactions') }}</CardTitle>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="transactions.index().url">
                                    {{ t('dashboard.view_all') }}
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.recentTransactions.length > 0" class="space-y-3">
                            <div 
                                v-for="transaction in props.recentTransactions" 
                                :key="transaction.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex items-center space-x-3">
                                    <div :class="['p-2 rounded-full', getTransactionColor(transaction.type)]">
                                        <component :is="getTransactionIcon(transaction.type)" class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ transaction.description }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ transaction.account.name }}
                                            <span v-if="transaction.transfer_to_account">
                                                → {{ transaction.transfer_to_account.name }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium" :class="getTransactionColor(transaction.type)">
                                        {{ transaction.account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ new Date(transaction.transaction_date).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            <p>{{ t('dashboard.no_transactions') }}</p>
                            <Button size="sm" class="mt-2" as-child>
                                <Link :href="transactions.create().url">
                                    <Plus class="w-4 h-4 mr-2" />
                                    Add Your First Transaction
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Account Overview -->
                <Card class="relative flex-1">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ t('dashboard.accounts_overview') }}</CardTitle>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="accounts.index().url">
                                    {{ t('dashboard.view_all') }}
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.accounts.length > 0" class="space-y-3">
                            <div 
                                v-for="account in props.accounts.slice(0, 5)" 
                                :key="account.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="flex flex-col">
                                        <p class="font-medium">{{ account.name }}</p>
                                        <div class="flex items-center space-x-2">
                                            <Badge :variant="account.is_active ? 'default' : 'secondary'" class="text-xs">
                                                {{ account.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                            <span class="text-sm text-muted-foreground">{{ account.currency.code }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium" :class="account.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ account.currency.symbol }}{{ Number(account.balance).toLocaleString() }}
                                    </p>
                                    <Button size="sm" variant="ghost" as-child>
                                        <Link :href="accounts.show({ account: account.id }).url">
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            <p>{{ t('dashboard.no_accounts') }}</p>
                            <Button size="sm" class="mt-2" as-child>
                                <Link :href="accounts.create().url">
                                    <Plus class="w-4 h-4 mr-2" />
                                    Create Your First Account
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
