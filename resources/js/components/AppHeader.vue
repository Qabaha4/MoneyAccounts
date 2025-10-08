<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { getInitials } from '@/composables/useInitials';
import { toUrl, urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';
import { InertiaLinkProps, Link, usePage, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Menu, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

interface SearchAccount {
    id: number;
    name: string;
    description?: string;
}

interface SearchTransaction {
    id: number;
    description: string;
    amount: number;
    account_id: number;
    account?: {
        name: string;
    };
    currency?: {
        code: string;
    };
}

interface SearchResults {
    accounts: SearchAccount[];
    transactions: SearchTransaction[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

// Search functionality
const searchQuery = ref('');
const isSearchFocused = ref(false);
const isSearching = ref(false);
const searchResults = ref<SearchResults>({
    accounts: [],
    transactions: []
});

let debounceTimeout: number = 0;

const handleSearch = () => {
    clearTimeout(debounceTimeout);
    
    if (!searchQuery.value.trim()) {
        searchResults.value = { accounts: [], transactions: [] };
        isSearching.value = false;
        return;
    }
    
    isSearching.value = true;
    
    debounceTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/search?query=${encodeURIComponent(searchQuery.value)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
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
    }, 300);
};

const handleSearchBlur = () => {
    // Delay hiding results to allow for clicks
    setTimeout(() => {
        isSearchFocused.value = false;
    }, 200);
};

const navigateToAccount = (accountId: number) => {
    router.visit(`/accounts/${accountId}`);
    isSearchFocused.value = false;
    searchQuery.value = '';
};

const navigateToTransaction = (transactionId: number) => {
    // Navigate to the account that contains this transaction
    const transaction = searchResults.value.transactions.find(t => t.id === transactionId);
    if (transaction?.account_id) {
        router.visit(`/accounts/${transaction.account_id}`);
    }
    isSearchFocused.value = false;
    searchQuery.value = '';
};

const formatCurrency = (amount: number, currencyCode: string = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode
    }).format(amount);
};

const isSearchOpen = ref(false);

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        urlIsActive(url, page.url),
);

const activeItemStyles = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        isCurrentRoute.value(toUrl(url))
            ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
            : '',
);



const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/money-accounts-management/m.a.m.',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: '#',
        icon: BookOpen,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="me-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Navigation Menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="activeItemStyles(item.href)"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ms-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        activeItemStyles(item.href),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="me-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentRoute(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ms-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search accounts & transactions..."
                                class="pl-9 pr-4 h-9 bg-background/50 border-border/50 focus:bg-background focus:border-border"
                                @input="handleSearch"
                                @focus="isSearchFocused = true"
                                @blur="handleSearchBlur"
                            />
                            
                            <!-- Search Results Dropdown -->
                            <div
                                v-if="isSearchFocused && (searchResults.accounts.length > 0 || searchResults.transactions.length > 0 || isSearching)"
                                class="absolute top-full left-0 right-0 mt-1 bg-popover border border-border rounded-md shadow-lg z-50 max-h-96 overflow-y-auto"
                            >
                                <div v-if="isSearching" class="p-4 text-center text-muted-foreground">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                                        Searching...
                                    </div>
                                </div>
                                
                                <div v-else-if="searchResults.accounts.length === 0 && searchResults.transactions.length === 0 && searchQuery.trim()" class="p-4 text-center text-muted-foreground">
                                    No results found
                                </div>
                                
                                <div v-else>
                                    <!-- Accounts Results -->
                                    <div v-if="searchResults.accounts.length > 0" class="p-2">
                                        <h3 class="text-sm font-medium text-muted-foreground mb-2 px-2">Accounts</h3>
                                        <div
                                            v-for="account in searchResults.accounts"
                                            :key="`account-${account.id}`"
                                            class="flex items-center gap-3 p-2 rounded-sm hover:bg-accent cursor-pointer"
                                            @click="navigateToAccount(account.id)"
                                        >
                                            <Folder class="h-4 w-4 text-blue-500" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">{{ account.name }}</p>
                                                <p v-if="account.description" class="text-xs text-muted-foreground truncate">{{ account.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Transactions Results -->
                                    <div v-if="searchResults.transactions.length > 0" class="p-2">
                                        <h3 class="text-sm font-medium text-muted-foreground mb-2 px-2">Transactions</h3>
                                        <div
                                            v-for="transaction in searchResults.transactions"
                                            :key="`transaction-${transaction.id}`"
                                            class="flex items-center gap-3 p-2 rounded-sm hover:bg-accent cursor-pointer"
                                            @click="navigateToTransaction(transaction.id)"
                                        >
                                            <BookOpen class="h-4 w-4 text-green-500" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">{{ transaction.description }}</p>
                                                <p class="text-xs text-muted-foreground truncate">
                                                    {{ transaction.account?.name }} • {{ formatCurrency(transaction.amount, transaction.currency?.code) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <LanguageSwitcher />

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>


    </div>
</template>
