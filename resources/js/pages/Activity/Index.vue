<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-xl text-foreground leading-tight">
          {{ t('activity.title') }}
        </h2>
      </div>
    </template>

    <div class="py-4 sm:py-6 lg:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filters -->
        <Card class="mb-6 overflow-hidden">
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-3 cursor-pointer" @click="toggleFilters">
              <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-muted-foreground" />
                <span class="text-sm font-semibold text-foreground">
                  {{ t('activity.filter') }}
                  <Badge v-if="activeFiltersCount > 0" variant="secondary" class="ms-2 text-xs px-2 py-0.5">
                    {{ activeFiltersCount }}
                  </Badge>
                </span>
              </div>
              <Button variant="ghost" size="sm" class="h-7 w-7 p-0">
                <component :is="filtersExpanded ? ChevronUp : ChevronDown" class="w-4 h-4" />
              </Button>
            </div>

            <div v-if="filtersExpanded" class="space-y-3 pt-3 border-t">
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('activity.type_label') }}</Label>
                  <Select
                    :key="`type-${resetKey}`"
                    v-model="filterForm.subject_type"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="t('activity.all_types')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{{ t('activity.all_types') }}</SelectItem>
                      <SelectItem
                        v-for="type in subjectTypes"
                        :key="type.value"
                        :value="type.value"
                      >
                        {{ type.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('activity.action_label') }}</Label>
                  <Select
                    :key="`action-${resetKey}`"
                    v-model="filterForm.action"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="t('activity.all_actions')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{{ t('activity.all_actions') }}</SelectItem>
                      <SelectItem value="created">{{ t('activity.created') }}</SelectItem>
                      <SelectItem value="updated">{{ t('activity.updated') }}</SelectItem>
                      <SelectItem value="deleted">{{ t('activity.deleted') }}</SelectItem>
                      <SelectItem value="exported">{{ t('activity.exported') }}</SelectItem>
                      <SelectItem value="printed">{{ t('activity.printed') }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('activity.date_from') }}</Label>
                  <DatePicker
                    v-model="filterForm.date_from"
                    @update:model-value="applyFilters"
                    :placeholder="t('activity.date_placeholder')"
                    class="h-9 text-sm"
                  />
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('activity.date_to') }}</Label>
                  <DatePicker
                    v-model="filterForm.date_to"
                    @update:model-value="applyFilters"
                    :placeholder="t('activity.date_placeholder')"
                    class="h-9 text-sm"
                  />
                </div>
              </div>

              <div class="flex gap-3 items-end">
                <div class="flex-1 relative">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                  <Input
                    v-model="filterForm.search"
                    :placeholder="t('activity.search_placeholder')"
                    class="pl-9 h-9 text-sm"
                    @input="debounceSearch"
                  />
                </div>
                <Button variant="outline" @click="clearFilters" class="h-9 text-sm shrink-0">
                  <X class="w-4 h-4 me-1.5" />
                  {{ t('activity.clear_filters') }}
                </Button>
              </div>

              <div v-if="activeFiltersCount > 0" class="flex flex-wrap gap-2 pt-2 border-t">
                <Badge
                  v-if="filterForm.subject_type !== 'all'"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-accent/50 transition-colors"
                  @click="clearSubjectTypeFilter"
                >
                  {{ getSubjectTypeLabel(filterForm.subject_type) }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge
                  v-if="filterForm.action !== 'all'"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-accent/50 transition-colors"
                  @click="clearActionFilter"
                >
                  {{ getActionLabel(filterForm.action) }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge
                  v-if="filterForm.search.trim()"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-accent/50 transition-colors"
                  @click="clearSearchFilter"
                >
                  "{{ filterForm.search.trim() }}"
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Activity List -->
        <div v-if="activities.data?.length > 0">
          <Card>
            <div class="divide-y divide-border">
              <div
                v-for="activity in activities.data"
                :key="activity.id"
                class="flex items-start gap-4 p-4 hover:bg-accent/30 transition-colors cursor-pointer"
                @click="openDetailModal(activity)"
              >
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                  :class="getActionIcon(activity.action).bgClass">
                  <component :is="getActionIcon(activity.action).icon" class="w-4 h-4" :class="getActionIcon(activity.action).colorClass" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-foreground">{{ activity.description }}</p>
                  <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                    <Badge variant="secondary" class="text-xs px-1.5 py-0">
                      {{ getSubjectTypeLabel(activity.subject_type) }}
                    </Badge>
                    <Badge variant="secondary" class="text-xs px-1.5 py-0">
                      {{ getActionLabel(activity.action) }}
                    </Badge>
                    <span>•</span>
                    <span>{{ formatDate(activity.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Pagination -->
          <div v-if="activities.last_page > 1" class="flex justify-center mt-6">
            <div class="flex items-center gap-1 sm:gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="goToPage(activities.current_page - 1)"
                :disabled="activities.current_page === 1"
                class="h-9"
              >
                <ChevronLeft class="w-4 h-4" />
                <span class="hidden sm:inline ms-1">{{ t('activity.previous') }}</span>
              </Button>

              <div class="flex gap-1">
                <Button
                  v-for="page in paginationPages"
                  :key="page"
                  :variant="page === activities.current_page ? 'default' : 'outline'"
                  size="sm"
                  @click="goToPage(page)"
                  class="min-w-[36px] h-9 text-sm"
                  :class="{'bg-primary text-primary-foreground': page === activities.current_page}"
                >
                  <span>{{ page }}</span>
                </Button>
              </div>

              <Button
                variant="outline"
                size="sm"
                @click="goToPage(activities.current_page + 1)"
                :disabled="activities.current_page === activities.last_page"
                class="h-9"
              >
                <span class="hidden sm:inline me-1">{{ t('activity.next') }}</span>
                <ChevronRight class="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <Card v-else class="text-center py-12 sm:py-16">
          <CardContent>
            <div class="max-w-md mx-auto">
              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-secondary/50 flex items-center justify-center mx-auto mb-4 sm:mb-6">
                <ActivityIcon class="w-8 h-8 sm:w-10 sm:h-10 text-muted-foreground" />
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-foreground mb-2 sm:mb-3">
                {{ hasActiveFilters ? t('activity.no_matching') : t('activity.no_activity') }}
              </h3>
              <p class="text-sm text-muted-foreground mb-4 sm:mb-6">
                {{ hasActiveFilters ? t('activity.no_matching_description') : t('activity.empty_description') }}
              </p>
              <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters" class="h-10">
                  <X class="w-4 h-4 me-2" />
                  {{ t('activity.clear_filters') }}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Detail Modal -->
    <Dialog :open="detailModalOpen" @update:open="detailModalOpen = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogTitle class="sr-only">{{ selectedActivity?.description }}</DialogTitle>
        <DialogDescription class="sr-only">Activity detail view</DialogDescription>
        <div class="space-y-4 py-2">
          <p class="text-sm text-foreground font-medium">{{ selectedActivity?.description }}</p>

          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between border-b pb-1.5">
              <span class="text-muted-foreground">{{ t('activity.type_label') }}</span>
              <Badge variant="secondary" class="text-xs">
                {{ getSubjectTypeLabel(selectedActivity?.subject_type || '') }}
              </Badge>
            </div>
            <div class="flex items-center justify-between border-b pb-1.5">
              <span class="text-muted-foreground">{{ t('activity.action_label') }}</span>
              <Badge variant="secondary" class="text-xs">
                {{ getActionLabel(selectedActivity?.action || '') }}
              </Badge>
            </div>
            <div class="flex items-center justify-between border-b pb-1.5">
              <span class="text-muted-foreground">{{ t('activity.date') }}</span>
              <span>{{ formatDate(selectedActivity?.created_at || '') }}</span>
            </div>
          </div>

          <div v-if="changeEntries.length > 0" class="pt-2 border-t">
            <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
              {{ t('activity.changes') }}
            </div>
            <div class="rounded-md border overflow-hidden">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-muted/50 text-muted-foreground">
                    <th class="text-start font-medium px-3 py-2 w-1/3">{{ t('activity.field') }}</th>
                    <th class="text-start font-medium px-3 py-2">{{ t('activity.from') }}</th>
                    <th class="text-start font-medium px-3 py-2 w-8"></th>
                    <th class="text-start font-medium px-3 py-2">{{ t('activity.to') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(entry, i) in changeEntries" :key="entry.field"
                    :class="i % 2 === 1 ? 'bg-muted/30' : ''"
                  >
                    <td class="px-3 py-2 text-muted-foreground capitalize font-medium">{{ formatFieldName(entry.field) }}</td>
                    <td class="px-3 py-2 font-mono text-rose-600 dark:text-rose-400">{{ entry.from }}</td>
                    <td class="px-0 py-2 text-muted-foreground text-center">
                      <ArrowRight class="w-3 h-3" />
                    </td>
                    <td class="px-3 py-2 font-mono text-emerald-600 dark:text-emerald-400">{{ entry.to }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex gap-2 pt-3 border-t">
            <template v-if="isTransactionSubject">
              <Button variant="default" class="flex-1 h-10" @click="openTransactionModal">
                <Eye class="w-4 h-4 me-2" />
                {{ t('activity.view_transaction') }}
              </Button>
              <Button variant="outline" class="flex-1 h-10" @click="router.visit(navigateUrl)">
                <ExternalLink class="w-4 h-4 me-2" />
                {{ t('activity.go_to_account') }}
              </Button>
            </template>
            <Button v-else-if="navigateUrl" variant="outline" class="w-full h-10" @click="router.visit(navigateUrl)">
              <ExternalLink class="w-4 h-4 me-2" />
              {{ t('activity.view_entity') }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <TransactionDetailModal
      :is-open="transactionModalOpen"
      :transaction="selectedActivity?.subject ?? null"
      hide-actions
      show-in-account
      @update:is-open="transactionModalOpen = $event"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DatePicker } from '@/components/ui/date-picker'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import TransactionDetailModal from '@/components/TransactionDetailModal.vue'
import {
  ActivityIcon, Search, Filter, X, Eye,
  ChevronLeft, ChevronRight, ChevronUp, ChevronDown,
  CirclePlus, Pencil, Trash2, Download, Printer, ExternalLink, ArrowRight
} from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const { t } = useI18n()

interface Activity {
  id: string
  subject_type: string
  subject_id: string | null
  action: string
  description: string
  metadata: Record<string, any> | null
  created_at: string
  subject?: Record<string, any> | null
}

interface PaginatedActivities {
  data: Activity[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Filters {
  subject_type?: string
  action?: string
  date_from?: string
  date_to?: string
  search?: string
}

const props = defineProps<{
  activities: PaginatedActivities
  filters?: Filters
}>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: t('dashboard.title'),
    href: dashboard().url,
  },
  {
    title: t('activity.title'),
    href: '/activity',
  },
]

const subjectTypes = [
  { value: 'App\\Models\\Account', label: t('activity.account') },
  { value: 'App\\Models\\Transaction', label: t('activity.transaction') },
  { value: 'report', label: t('activity.report') },
]

const filtersExpanded = ref(false)
const searchTimeout = ref<number | null>(null)
const resetKey = ref(0)

const filterForm = reactive({
  subject_type: props.filters?.subject_type || 'all',
  action: props.filters?.action || 'all',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  search: props.filters?.search || '',
})

const hasActiveFilters = computed(() => {
  return filterForm.subject_type !== 'all' ||
    filterForm.action !== 'all' ||
    filterForm.search.trim() !== '' ||
    filterForm.date_from !== '' ||
    filterForm.date_to !== ''
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (filterForm.subject_type !== 'all') count++
  if (filterForm.action !== 'all') count++
  if (filterForm.search.trim()) count++
  if (filterForm.date_from) count++
  if (filterForm.date_to) count++
  return count
})

const paginationPages = computed(() => {
  const pages: (number | string)[] = []
  const maxPages = 7
  const current = props.activities.current_page
  const last = props.activities.last_page

  if (last <= maxPages) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  return pages
})

const getSubjectTypeLabel = (type: string) => {
  const map: Record<string, string> = {
    'App\\Models\\Account': t('activity.account'),
    'App\\Models\\Transaction': t('activity.transaction'),
    'report': t('activity.report'),
  }
  return map[type] || type.split('\\').pop() || type
}

const getActionLabel = (action: string) => {
  const map: Record<string, string> = {
    'created': t('activity.created'),
    'updated': t('activity.updated'),
    'deleted': t('activity.deleted'),
    'exported': t('activity.exported'),
    'printed': t('activity.printed'),
  }
  return map[action] || action
}

const getActionIcon = (action: string) => {
  switch (action) {
    case 'created':
      return { icon: CirclePlus, bgClass: 'bg-emerald-500/10', colorClass: 'text-emerald-600 dark:text-emerald-400' }
    case 'updated':
      return { icon: Pencil, bgClass: 'bg-blue-500/10', colorClass: 'text-blue-600 dark:text-blue-400' }
    case 'deleted':
      return { icon: Trash2, bgClass: 'bg-rose-500/10', colorClass: 'text-rose-600 dark:text-rose-400' }
    case 'exported':
      return { icon: Download, bgClass: 'bg-purple-500/10', colorClass: 'text-purple-600 dark:text-purple-400' }
    case 'printed':
      return { icon: Printer, bgClass: 'bg-orange-500/10', colorClass: 'text-orange-600 dark:text-orange-400' }
    default:
      return { icon: ActivityIcon, bgClass: 'bg-secondary/50', colorClass: 'text-muted-foreground' }
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const toggleFilters = () => {
  filtersExpanded.value = !filtersExpanded.value
}

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    applyFilters()
  }, 500)
}

const buildFilters = () => {
  const filters: Record<string, string> = {}
  if (filterForm.subject_type !== 'all') filters.subject_type = filterForm.subject_type
  if (filterForm.action !== 'all') filters.action = filterForm.action
  if (filterForm.date_from) filters.date_from = filterForm.date_from
  if (filterForm.date_to) filters.date_to = filterForm.date_to
  if (filterForm.search.trim()) filters.search = filterForm.search.trim()
  return filters
}

const applyFilters = () => {
  router.get('/activity', buildFilters(), {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  filterForm.subject_type = 'all'
  filterForm.action = 'all'
  filterForm.date_from = ''
  filterForm.date_to = ''
  filterForm.search = ''
  resetKey.value++

  router.get('/activity', {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const goToPage = (page: number | string) => {
  if (typeof page === 'string') return
  const filters: Record<string, string | number> = { ...buildFilters(), page }
  router.get('/activity', filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearSubjectTypeFilter = () => {
  filterForm.subject_type = 'all'
  applyFilters()
}

const clearActionFilter = () => {
  filterForm.action = 'all'
  applyFilters()
}

const clearSearchFilter = () => {
  filterForm.search = ''
  applyFilters()
}

const detailModalOpen = ref(false)
const transactionModalOpen = ref(false)
const selectedActivity = ref<Activity | null>(null)

const openDetailModal = (activity: Activity) => {
  selectedActivity.value = activity
  detailModalOpen.value = true
}

const isTransactionSubject = computed(() =>
  selectedActivity.value?.subject_type === 'App\\Models\\Transaction'
)

const openTransactionModal = () => {
  detailModalOpen.value = false
  transactionModalOpen.value = true
}

const formatFieldName = (field: string): string => {
  return field.replace(/_id$/, '').replace(/_/g, ' ')
}

const changeEntries = computed(() => {
  const changes = selectedActivity.value?.metadata?.changes
  if (!changes || typeof changes !== 'object') return []
  return Object.entries(changes)
    .filter(([field]) => field !== 'updated_at')
    .map(([field, value]) => {
      const v = value as { from?: unknown; to?: unknown }
      return {
        field,
        from: v.from ?? '-',
        to: v.to ?? '-',
      }
    })
})

const navigateUrl = computed(() => {
  const a = selectedActivity.value
  if (!a) return null

  if (a.subject_type === 'App\\Models\\Account') {
    return `/accounts/${a.subject_id}`
  }
  if (a.subject_type === 'App\\Models\\Transaction') {
    const accountId = a.metadata?.account_id
    return accountId ? `/accounts/${accountId}` : null
  }
  if (a.subject_type === 'report') {
    const accountId = a.metadata?.account_id
    return accountId ? `/accounts/${accountId}/report` : null
  }
  return null
})
</script>
