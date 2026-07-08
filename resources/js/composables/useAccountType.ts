import { Landmark, PiggyBank, Wallet, TrendingUp, CreditCard, CircleDollarSign } from 'lucide-vue-next'

const ACCOUNT_TYPE_STYLES: Record<string, { gradientClass: string; icon: any }> = {
  checking:   { gradientClass: 'gradient-checking',   icon: Landmark },
  savings:    { gradientClass: 'gradient-savings',    icon: PiggyBank },
  cash:       { gradientClass: 'gradient-wallet',     icon: Wallet },
  investment: { gradientClass: 'gradient-investment', icon: TrendingUp },
  credit:     { gradientClass: 'gradient-credit',     icon: CreditCard },
  other:      { gradientClass: 'gradient-brand',      icon: CircleDollarSign },
}

export function useAccountType() {
  const getAccountTypeStyle = (type: string) =>
    ACCOUNT_TYPE_STYLES[type?.toLowerCase()] ?? ACCOUNT_TYPE_STYLES.other
  return { getAccountTypeStyle }
}
