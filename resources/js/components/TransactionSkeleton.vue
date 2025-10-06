<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'

interface TransactionSkeletonProps {
  count?: number
  viewMode?: 'list' | 'grid'
}

const props = withDefaults(defineProps<TransactionSkeletonProps>(), {
  count: 5,
  viewMode: 'list'
})
</script>

<template>
  <div v-if="viewMode === 'list'" class="space-y-3">
    <Card v-for="i in count" :key="i" class="overflow-hidden">
      <CardContent class="p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3 flex-1">
            <!-- Icon placeholder -->
            <Skeleton class="w-10 h-10 rounded-full" />
            
            <!-- Content -->
            <div class="flex-1 space-y-2">
              <div class="flex items-center justify-between">
                <Skeleton class="h-4 w-32" />
                <Skeleton class="h-5 w-20" />
              </div>
              <div class="flex items-center space-x-4">
                <Skeleton class="h-3 w-24" />
                <Skeleton class="h-3 w-16" />
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>

  <div v-else class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
    <Card v-for="i in count" :key="i" class="overflow-hidden">
      <CardContent class="p-4">
        <div class="space-y-3">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <Skeleton class="w-8 h-8 rounded-full" />
            <Skeleton class="h-6 w-16" />
          </div>
          
          <!-- Amount -->
          <Skeleton class="h-8 w-24 mx-auto" />
          
          <!-- Details -->
          <div class="space-y-2">
            <Skeleton class="h-3 w-full" />
            <Skeleton class="h-3 w-3/4" />
            <Skeleton class="h-3 w-1/2" />
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>