<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as transactionsIndex } from '@/routes/transactions';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Wallet, Receipt, Globe } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import AppLogo from './AppLogo.vue';

const { t } = useI18n();

const mainNavItems: NavItem[] = [
    {
        title: t('app.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Accounts',
        href: accountsIndex().url,
        icon: Wallet,
    },
    {
        title: 'Transactions',
        href: transactionsIndex().url,
        icon: Receipt,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: t('app.github_repo'),
        href: 'https://github.com/Qabaha4/MoneyAccounts',
        icon: Folder,
    },
    {
        title: t('app.developer'),
        href: 'https://qabaha.net/',
        icon: Globe,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
