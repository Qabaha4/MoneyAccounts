<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
</script>

<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card v-for="i in 4" :key="i">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <Skeleton class="h-4 w-24" />
          <Skeleton class="w-4 h-4" />
        </CardHeader>
        <CardContent>
          <Skeleton class="h-8 w-32 mb-1" />
          <Skeleton class="h-3 w-20" />
        </CardContent>
      </Card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Recent Transactions -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <Skeleton class="h-6 w-40" />
            <Skeleton class="h-4 w-16" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="i in 5" :key="i" class="flex items-center space-x-3">
              <Skeleton class="w-10 h-10 rounded-full" />
              <div class="flex-1 space-y-1">
                <div class="flex items-center justify-between">
                  <Skeleton class="h-4 w-32" />
                  <Skeleton class="h-4 w-16" />
                </div>
                <Skeleton class="h-3 w-24" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Accounts Overview -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <Skeleton class="h-6 w-36" />
            <Skeleton class="h-4 w-20" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="i in 4" :key="i" class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <Skeleton class="w-8 h-8 rounded" />
                <div class="space-y-1">
                  <Skeleton class="h-4 w-24" />
                  <Skeleton class="h-3 w-16" />
                </div>
              </div>
              <Skeleton class="h-5 w-20" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Quick Actions -->
    <Card>
      <CardHeader>
        <Skeleton class="h-6 w-32" />
        <Skeleton class="h-4 w-48" />
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <div v-for="i in 4" :key="i" class="flex flex-col items-center space-y-2 p-4 border rounded-lg">
            <Skeleton class="w-12 h-12 rounded-full" />
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-3 w-16" />
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>