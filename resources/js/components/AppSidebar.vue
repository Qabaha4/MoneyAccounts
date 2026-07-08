<script setup lang="ts">
import { computed } from 'vue';
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
import { index as reportIndex } from '@/routes/report';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Folder, LayoutGrid, Wallet, Receipt, Globe, Moon, Sun, Languages, FileText, Shield } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { useAppearance } from '@/composables/useAppearance';
import { useLocale } from '@/composables/useLocale';
import AppLogo from './AppLogo.vue';

const { t } = useI18n();
const { toggleLocale, isRTL } = useLocale();
const { appearance, updateAppearance } = useAppearance();

const user = computed(() => (usePage().props.auth as any)?.user);
const isAdmin = computed(() => user.value?.role === 'admin');

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('app.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('app.accounts'),
            href: accountsIndex().url,
            icon: Wallet,
        },
        {
            title: t('app.transactions'),
            href: transactionsIndex().url,
            icon: Receipt,
        },
        {
            title: t('app.report'),
            href: reportIndex().url,
            icon: FileText,
        },
    ];

    if (isAdmin.value) {
        items.push({
            title: t('app.admin'),
            href: '/admin',
            icon: Shield,
            external: true,
        });
    }

    return items;
});

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

function cycleAppearance() {
    const next: Record<string, string> = { light: 'dark', dark: 'system', system: 'light' };
    updateAppearance(next[appearance.value] as 'light' | 'dark' | 'system');
}
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

            <SidebarMenu>
                <SidebarMenuItem>
                    <div
                        class="group-data-[collapsible=icon]:flex-col flex items-center gap-1 px-3 py-2"
                    >
                        <button
                            @click="cycleAppearance"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-white/[0.04] hover:text-foreground"
                            :title="t('appearance.title')"
                        >
                            <Sun
                                v-if="appearance === 'light'"
                                class="h-4 w-4"
                            />
                            <Moon
                                v-else-if="appearance === 'dark'"
                                class="h-4 w-4"
                            />
                            <Sun
                                v-else
                                class="h-4 w-4"
                            />
                            <span class="group-data-[collapsible=icon]:hidden">{{
                                appearance === 'light'
                                    ? t('appearance.light')
                                    : appearance === 'dark'
                                      ? t('appearance.dark')
                                      : t('appearance.system')
                            }}</span>
                        </button>

                        <button
                            @click="toggleLocale"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-white/[0.04] hover:text-foreground"
                            :title="t('common.language')"
                        >
                            <Languages class="h-4 w-4" />
                            <span class="group-data-[collapsible=icon]:hidden">{{ isRTL ? 'AR' : 'EN' }}</span>
                        </button>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>

            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
