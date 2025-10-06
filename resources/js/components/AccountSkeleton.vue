<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'

interface AccountSkeletonProps {
  count?: number
}

const props = withDefaults(defineProps<AccountSkeletonProps>(), {
  count: 6
})
</script>

<template>
  <div class="grid gap-3 sm:gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3 auto-rows-max">
    <Card v-for="i in count" :key="i" class="overflow-hidden h-fit max-h-[350px] flex flex-col">
      <!-- Header with gradient background -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-3 py-1.5 flex-shrink-0">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1">
            <Skeleton class="w-3 h-3 bg-slate-600" />
            <Skeleton class="h-3 w-16 bg-slate-600" />
          </div>
          <Skeleton class="h-4 w-8 bg-slate-600" />
        </div>
      </div>

      <CardContent class="p-3 flex-1 flex flex-col">
        <!-- Account name and balance -->
        <div class="space-y-2 mb-3">
          <Skeleton class="h-5 w-3/4" />
          <Skeleton class="h-8 w-24" />
        </div>

        <!-- Description -->
        <div class="space-y-1 mb-3">
          <Skeleton class="h-3 w-full" />
          <Skeleton class="h-3 w-2/3" />
        </div>

        <!-- Status and actions -->
        <div class="mt-auto pt-3 border-t border-border">
          <div class="flex items-center justify-between">
            <Skeleton class="h-5 w-12" />
            <div class="flex gap-1">
              <Skeleton class="w-6 h-6 rounded" />
              <Skeleton class="w-6 h-6 rounded" />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>