<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 w-8 px-0">
                <Globe class="h-4 w-4" />
                <span class="sr-only">{{ $t('common.language') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem 
                @click="setLocale('en')"
                :class="{ 'bg-accent': currentLocale === 'en' }"
            >
                <span class="me-2">🇺🇸</span>
                English
            </DropdownMenuItem>
            <DropdownMenuItem 
                @click="setLocale('ar')"
                :class="{ 'bg-accent': currentLocale === 'ar' }"
            >
                <span class="me-2">🇸🇦</span>
                العربية
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup lang="ts">
import { Globe } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useLocale } from '@/composables/useLocale'

const { currentLocale, setLocale } = useLocale()
</script>