<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Edit } from 'lucide-vue-next';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_active: boolean;
    decimal_places: number;
}

interface Props {
    mainSecVal: string;
    mainSecLabel: string;
    subSecP1Val: string;
    subSecP1Label: string;
    subSecP2Val: string;
    subSecP2Label: string;
    subSecP3Val: string;
    subSecP3Label: string;
    showStatus?: boolean;
    statusVal?: string;
    showEditButton?: boolean;
    showCurrencySelector?: boolean;
    currencies?: Currency[];
    selectedCurrency?: Currency;
}

const props = withDefaults(defineProps<Props>(), {
    showStatus: false,
    statusVal: 'Active',
    showEditButton: false,
    showCurrencySelector: false,
    currencies: () => [],
});

const emit = defineEmits<{
    edit: [];
    currencyChange: [currency: Currency];
}>();

const { t } = useI18n();

const isNegativeBalance = computed(() => {
    const numericValue = parseFloat(props.mainSecVal.replace(/[^-\d.]/g, ''));
    return numericValue < 0;
});
</script>

<template>
    <div class="gradient-brand rounded-[20px] p-5 sm:p-7 relative overflow-hidden text-white">
        <!-- Glow orbs -->
        <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/10 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-blue-400/20 blur-3xl rounded-full pointer-events-none"></div>

        <!-- Dot-grid overlay -->
        <div class="absolute inset-0 opacity-[0.04] pointer-events-none"
             style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;">
        </div>

        <!-- Edit Button -->
        <div v-if="props.showEditButton" class="absolute top-4 left-4 z-20">
            <Button
                variant="ghost"
                size="sm"
                type="button"
                @click="emit('edit')"
                class="h-9 w-9 p-0 text-white/70 hover:text-white hover:bg-white/10"
                :title="t('accounts.edit')"
            >
                <Edit class="w-4 h-4" />
            </Button>
        </div>

        <!-- Status Indicator -->
        <div v-if="props.showStatus" class="absolute top-4 right-4 z-10">
            <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 text-white/90 backdrop-blur-sm"
            >
                <div
                    class="w-1.5 h-1.5 rounded-full"
                    :class="props.statusVal === 'Active' ? 'bg-green-400' : 'bg-white/40'"
                ></div>
                {{ props.statusVal }}
            </div>
        </div>

        <div class="relative z-10">
            <div class="mb-5">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <div class="text-xs font-medium text-white/70 uppercase tracking-wider">{{ props.mainSecLabel }}</div>
                        <div v-if="props.showCurrencySelector && props.currencies && props.currencies.length > 1" class="flex items-center">
                            <span class="text-xs text-white/70 me-2">in</span>
                            <Select
                                :model-value="props.selectedCurrency?.id?.toString()"
                                @update:model-value="(value) => {
                                    const currency = props.currencies?.find(c => c.id.toString() === value);
                                    if (currency) emit('currencyChange', currency);
                                }"
                            >
                                <SelectTrigger class="w-auto h-7 px-2 py-1 text-xs bg-white/10 border-white/20 text-white">
                                    <SelectValue :placeholder="props.selectedCurrency?.code || 'Select'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="currency in props.currencies"
                                        :key="currency.id"
                                        :value="currency.id.toString()"
                                    >
                                        {{ currency.code }} ({{ currency.symbol }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="text-4xl sm:text-5xl font-extrabold tracking-tight stat-value"
                         :class="isNegativeBalance ? 'text-red-300' : 'text-white'">
                        {{ props.mainSecVal }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 pt-5 border-t border-white/10">
                <div class="text-center">
                    <div class="text-xs text-white/60 mb-1">{{ props.subSecP1Label }}</div>
                    <div class="text-sm font-semibold text-white">{{ props.subSecP1Val }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-white/60 mb-1">{{ props.subSecP2Label }}</div>
                    <div class="text-sm font-semibold text-white">{{ props.subSecP2Val }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-white/60 mb-1">{{ props.subSecP3Label }}</div>
                    <div class="text-sm font-semibold text-white">{{ props.subSecP3Val }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
