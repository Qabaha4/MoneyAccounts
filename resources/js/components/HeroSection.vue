<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-vue-next';

interface Account {
    id: number;
    name: string;
    description: string | null;
    type: string;
    balance: number;
    initial_balance: number;
    is_active: boolean;
    currency: {
        id: number;
        code: string;
        name: string;
        symbol: string;
        is_active: boolean;
        decimal_places: number;
    };
}

interface Transaction {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    amount: string;
    description: string;
    transaction_date: string;
    account: Account;
    transfer_to_account?: Account;
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
}

const props = withDefaults(defineProps<Props>(), {
    showStatus: false,
    statusVal: 'Active',
    showEditButton: false
});

const emit = defineEmits<{
    edit: []
}>();

const { t } = useI18n();

// Computed property to check if the main value is negative
const isNegativeBalance = computed(() => {
    // Extract numeric value from the formatted string
    const numericValue = parseFloat(props.mainSecVal.replace(/[^-\d.]/g, ''));
    return numericValue < 0;
});
</script>

<template>
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-lg sm:rounded-xl lg:rounded-2xl p-3 sm:p-5 lg:p-7 text-white shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:20px_20px]"></div>
        
        <!-- Edit Button -->
        <div v-if="props.showEditButton" class="absolute top-3 left-3 sm:top-4 sm:left-4 z-10">
            <Button
                variant="ghost"
                size="sm"
                @click="emit('edit')"
                class="h-8 w-8 p-0 text-slate-300 hover:text-white hover:bg-white/10 transition-colors relative z-10"
                title="Edit Account"
            >
                <Edit class="w-4 h-4" />
            </Button>
        </div>
        
        <!-- Status Indicator -->
        <div v-if="props.showStatus" class="absolute top-3 right-3 sm:top-4 sm:right-4">
            <div 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shadow-sm"
                :class="props.statusVal === 'Active' 
                    ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' 
                    : 'bg-rose-500/20 text-rose-300 border-rose-500/30'"
            >
                <div 
                    class="w-1.5 h-1.5 rounded-full"
                    :class="props.statusVal === 'Active' ? 'bg-emerald-400' : 'bg-rose-400'"
                ></div>
                {{ props.statusVal }}
            </div>
        </div>
        
        <div class="relative">
            <div class="mb-3 sm:mb-5">
                <div class="text-center">
                    <div class="text-xs sm:text-sm font-medium text-slate-400 mb-1 sm:mb-1.5">{{ props.mainSecLabel.toUpperCase() }}</div>
                    <div class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" 
                         :class="isNegativeBalance ? 'text-red-400' : 'text-emerald-400'">
                        {{ props.mainSecVal }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 sm:gap-3 pt-2.5 sm:pt-3 border-t border-slate-700">
                <div class="text-center">
                    <div class="text-xs text-slate-400 mb-0.5">{{ props.subSecP1Label }}</div>
                    <div class="text-sm font-semibold">{{ props.subSecP1Val }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-slate-400 mb-0.5">{{ props.subSecP2Label }}</div>
                    <div class="text-sm font-semibold">{{ props.subSecP2Val }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-slate-400 mb-0.5">{{ props.subSecP3Label }}</div>
                    <div class="text-sm font-semibold">{{ props.subSecP3Val }}</div>
                </div>
            </div>
        </div>
    </div>
</template>