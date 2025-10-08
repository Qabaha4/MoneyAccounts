<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import {
    Search,
    CreditCard,
    ArrowUpDown,
    Calendar,
    DollarSign,
    X,
} from 'lucide-vue-next';

interface SearchResult {
    id: number;
    type: 'account' | 'transaction';
    title: string;
    description?: string;
    balance?: number;
    amount?: number;
    transaction_type?: string;
    currency: string;
    account_name?: string;
    date?: string;
    url: string;
}

interface SearchResponse {
    accounts: SearchResult[];
    transactions: SearchResult[];
    total_results: number;
}

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const searchQuery = ref('');
const searchResults = ref<SearchResponse | null>(null);
const isLoading = ref(false);
const selectedIndex = ref(-1);
const searchInput = ref<HTMLInputElement>();

let debounceTimeout: number;

const isDialogOpen = computed({
    get: () => props.isOpen,
    set: (value) => emit('update:isOpen', value),
});

const allResults = computed(() => {
    if (!searchResults.value) return [];
    return [
        ...searchResults.value.accounts,
        ...searchResults.value.transactions,
    ];
});

const hasResults = computed(() => {
    return searchResults.value && searchResults.value.total_results > 0;
});

const performSearch = async (query: string) => {
    if (!query.trim()) {
        searchResults.value = null;
        return;
    }

    isLoading.value = true;
    
    try {
        const response = await fetch(`/search?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data: SearchResponse = await response.json();
            searchResults.value = data;
        } else {
            console.error('Search failed:', response.statusText);
            searchResults.value = null;
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.value = null;
    } finally {
        isLoading.value = false;
    }
};

const debouncedSearch = (query: string) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
};

watch(searchQuery, (newQuery) => {
    selectedIndex.value = -1;
    if (newQuery.trim()) {
        debouncedSearch(newQuery);
    } else {
        searchResults.value = null;
    }
});

const handleKeydown = (event: KeyboardEvent) => {
    if (!hasResults.value) return;

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            selectedIndex.value = Math.min(
                selectedIndex.value + 1,
                allResults.value.length - 1
            );
            break;
        case 'ArrowUp':
            event.preventDefault();
            selectedIndex.value = Math.max(selectedIndex.value - 1, -1);
            break;
        case 'Enter':
            event.preventDefault();
            if (selectedIndex.value >= 0) {
                selectResult(allResults.value[selectedIndex.value]);
            }
            break;
        case 'Escape':
            event.preventDefault();
            closeDialog();
            break;
    }
};

const selectResult = (result: SearchResult) => {
    router.visit(result.url);
    closeDialog();
};

const closeDialog = () => {
    isDialogOpen.value = false;
    searchQuery.value = '';
    searchResults.value = null;
    selectedIndex.value = -1;
};

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getTransactionTypeColor = (type: string) => {
    switch (type) {
        case 'income':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'expense':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'transfer':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    clearTimeout(debounceTimeout);
});

watch(isDialogOpen, (isOpen) => {
    if (isOpen) {
        setTimeout(() => {
            searchInput.value?.focus();
        }, 100);
    }
});
</script>

<template>
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="max-w-2xl max-h-[80vh] overflow-hidden p-0">
            <DialogHeader class="px-6 py-4 border-b">
                <DialogTitle class="flex items-center gap-2">
                    <Search class="h-5 w-5" />
                    Search Accounts & Transactions
                </DialogTitle>
            </DialogHeader>

            <div class="px-6 py-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        placeholder="Search accounts, transactions, descriptions..."
                        class="pl-10 pr-10"
                        @keydown="handleKeydown"
                    />
                    <Button
                        v-if="searchQuery"
                        variant="ghost"
                        size="icon"
                        class="absolute right-1 top-1/2 h-6 w-6 -translate-y-1/2"
                        @click="searchQuery = ''"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto max-h-96">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <LoadingSpinner class="h-6 w-6" />
                    <span class="ml-2 text-sm text-muted-foreground">Searching...</span>
                </div>

                <!-- No Results -->
                <div
                    v-else-if="searchQuery && !hasResults && !isLoading"
                    class="flex flex-col items-center justify-center py-8 text-center"
                >
                    <Search class="h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-sm text-muted-foreground">
                        No results found for "{{ searchQuery }}"
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Try searching for account names or transaction descriptions
                    </p>
                </div>

                <!-- Search Results -->
                <div v-else-if="hasResults" class="space-y-4 px-6 pb-6">
                    <!-- Accounts Section -->
                    <div v-if="searchResults!.accounts.length > 0">
                        <h3 class="flex items-center gap-2 text-sm font-medium text-muted-foreground mb-3">
                            <CreditCard class="h-4 w-4" />
                            Accounts ({{ searchResults!.accounts.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="(account, index) in searchResults!.accounts"
                                :key="`account-${account.id}`"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-lg border cursor-pointer transition-colors',
                                    selectedIndex === index
                                        ? 'bg-accent border-accent-foreground/20'
                                        : 'hover:bg-accent/50',
                                ]"
                                @click="selectResult(account)"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium truncate">{{ account.title }}</h4>
                                        <Badge variant="outline" class="text-xs">
                                            {{ account.currency }}
                                        </Badge>
                                    </div>
                                    <p v-if="account.description" class="text-sm text-muted-foreground truncate">
                                        {{ account.description }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">
                                        {{ formatCurrency(account.balance!, account.currency) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Separator -->
                    <Separator v-if="searchResults!.accounts.length > 0 && searchResults!.transactions.length > 0" />

                    <!-- Transactions Section -->
                    <div v-if="searchResults!.transactions.length > 0">
                        <h3 class="flex items-center gap-2 text-sm font-medium text-muted-foreground mb-3">
                            <ArrowUpDown class="h-4 w-4" />
                            Transactions ({{ searchResults!.transactions.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="(transaction, index) in searchResults!.transactions"
                                :key="`transaction-${transaction.id}`"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-lg border cursor-pointer transition-colors',
                                    selectedIndex === (searchResults!.accounts.length + index)
                                        ? 'bg-accent border-accent-foreground/20'
                                        : 'hover:bg-accent/50',
                                ]"
                                @click="selectResult(transaction)"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-medium truncate">{{ transaction.title }}</h4>
                                        <Badge
                                            :class="getTransactionTypeColor(transaction.transaction_type!)"
                                            class="text-xs"
                                        >
                                            {{ transaction.transaction_type }}
                                        </Badge>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <span class="truncate">{{ transaction.account_name }}</span>
                                        <span>•</span>
                                        <div class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            <span>{{ formatDate(transaction.date!) }}</span>
                                        </div>
                                    </div>
                                    <p v-if="transaction.description" class="text-sm text-muted-foreground truncate mt-1">
                                        {{ transaction.description }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium flex items-center gap-1">
                                        <DollarSign class="h-4 w-4" />
                                        {{ formatCurrency(transaction.amount!, transaction.currency) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="!searchQuery"
                    class="flex flex-col items-center justify-center py-8 text-center"
                >
                    <Search class="h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-sm text-muted-foreground">
                        Start typing to search accounts and transactions
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Use ↑↓ to navigate, Enter to select, Esc to close
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>