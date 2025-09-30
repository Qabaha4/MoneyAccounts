<template>
    <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Navigation Test Page</h1>
            
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Test Navigation Links</h2>
                <div class="space-y-4">
                    <div>
                        <Link 
                            :href="home().url" 
                            class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition-colors"
                            @click="logNavigation('Home')"
                        >
                            Go to Home
                        </Link>
                    </div>
                    
                    <div>
                        <Link 
                            :href="dashboard().url" 
                            class="inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-colors"
                            @click="logNavigation('Dashboard')"
                        >
                            Go to Dashboard
                        </Link>
                    </div>
                    
                    <div>
                        <Link 
                            :href="accountsIndex().url" 
                            class="inline-block bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition-colors"
                            @click="logNavigation('Accounts')"
                        >
                            Go to Accounts
                        </Link>
                    </div>
                    
                    <div>
                        <Link 
                            :href="transactionsIndex().url" 
                            class="inline-block bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded transition-colors"
                            @click="logNavigation('Transactions')"
                        >
                            Go to Transactions
                        </Link>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Navigation Log</h2>
                <div class="space-y-2">
                    <div v-for="(log, index) in navigationLogs" :key="index" class="text-sm text-gray-600">
                        {{ log }}
                    </div>
                    <div v-if="navigationLogs.length === 0" class="text-gray-500 italic">
                        No navigation attempts yet. Click a link above to test navigation.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { home, dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as transactionsIndex } from '@/routes/transactions';

const navigationLogs = ref<string[]>([]);

const logNavigation = (destination: string) => {
    const timestamp = new Date().toLocaleTimeString();
    navigationLogs.value.push(`${timestamp}: Attempting to navigate to ${destination}`);
};

// Log when component mounts
navigationLogs.value.push(`${new Date().toLocaleTimeString()}: Navigation test page loaded`);
</script>