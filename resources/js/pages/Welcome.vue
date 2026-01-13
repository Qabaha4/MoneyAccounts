<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Wallet, 
    CheckCircle2, 
    CreditCard, 
    Layers, 
    Activity, 
    Coins, 
    LineChart, 
    ArrowRightLeft, 
    Globe,
    ShieldCheck
} from 'lucide-vue-next';

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Welcome to M.A.M.">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    </Head>
    
    <!-- Navigation -->
    <nav class="fixed top-0 w-full bg-white/80 backdrop-blur-md border-b border-gray-200 z-50 dark:bg-gray-900/80 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <Wallet class="w-5 h-5 text-white" />
                        </div>
                        <span class="ml-2 text-xl font-semibold text-gray-900 dark:text-white">M.A.M.</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-gray-700 hover:text-blue-600 px-3 py-2 font-medium transition-colors dark:text-gray-300 dark:hover:text-blue-400"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="register()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-20 pb-16 bg-gradient-to-br from-blue-50 via-purple-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div class="space-y-4">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white leading-tight">
                            Take Control of Your
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                                Financial Future
                            </span>
                        </h1>
                        <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed">
                            Manage multiple accounts, track transactions, and monitor your financial health with our intuitive Money Accounts Management platform.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="register()"
                            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            Start Managing Today
                        </Link>
                        <Link
                            v-else
                            :href="dashboard()"
                            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            Go to Dashboard
                        </Link>
                        <a
                            href="#features"
                            class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all"
                        >
                            Learn More
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-8 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <CheckCircle2 class="w-5 h-5 text-blue-500" />
                            <span>Multi-Currency Support</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <CheckCircle2 class="w-5 h-5 text-blue-500" />
                            <span>Real-time Analytics</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <ShieldCheck class="w-5 h-5 text-blue-500" />
                            <span>Secure & Private</span>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-6 dark:bg-gray-800">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Account Overview</h3>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Today</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg dark:bg-blue-900/20">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">$12,450</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Balance</div>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg dark:bg-blue-900/20">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">+$1,250</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">This Month</div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center dark:bg-blue-900">
                                            <CreditCard class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Checking Account</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">****1234</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900 dark:text-white">$8,450</div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center dark:bg-blue-900">
                                            <CreditCard class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Savings Account</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">****5678</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900 dark:text-white">$4,000</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -top-4 -right-4 w-72 h-72 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full opacity-20 blur-3xl"></div>
                    <div class="absolute -bottom-8 -left-8 w-64 h-64 bg-gradient-to-br from-purple-400 to-blue-500 rounded-full opacity-20 blur-3xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center space-y-4 mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                    Everything You Need to Manage Your Money
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    From multi-account management to detailed transaction tracking, M.A.M. provides all the tools you need for complete financial control.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 dark:bg-gradient-to-br dark:from-blue-900 dark:to-purple-900">
                        <Layers class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Multi-Account Management</h3>
                    <p class="text-gray-600 dark:text-gray-300">Create and manage multiple financial accounts including checking, savings, credit, and investment accounts all in one place.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-6 dark:bg-blue-900">
                        <Activity class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Transaction Tracking</h3>
                    <p class="text-gray-600 dark:text-gray-300">Record and categorize income, expenses, and transfers with detailed transaction history and real-time balance updates.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-6 dark:bg-purple-900">
                        <Coins class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Multi-Currency Support</h3>
                    <p class="text-gray-600 dark:text-gray-300">Handle different currencies with automatic conversion rates and maintain accurate balances across all your international accounts.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-6 dark:bg-teal-900">
                        <LineChart class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Dashboard Overview</h3>
                    <p class="text-gray-600 dark:text-gray-300">Get real-time insights into your financial health with comprehensive dashboards showing balances, trends, and transaction summaries.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6 dark:bg-orange-900">
                        <ArrowRightLeft class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Account Transfers</h3>
                    <p class="text-gray-600 dark:text-gray-300">Easily transfer funds between your accounts with automatic balance updates and detailed transfer history tracking.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-lg transition-shadow dark:bg-gray-800">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-6 dark:bg-indigo-900">
                        <Globe class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 dark:text-white">Internationalization</h3>
                    <p class="text-gray-600 dark:text-gray-300">Full support for English and Arabic languages, making the platform accessible to users worldwide with localized interfaces.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Ready to Take Control of Your Finances?
            </h2>
            <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                Join thousands of users who trust M.A.M. to manage their financial accounts and track their money with confidence.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                    v-if="!$page.props.auth.user"
                    :href="register()"
                    class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg"
                >
                    Get Started Free
                </Link>
                <Link
                    v-else
                    :href="dashboard()"
                    class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg"
                >
                    Go to Dashboard
                </Link>
                <Link
                    v-if="!$page.props.auth.user"
                    :href="login()"
                    class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-4 rounded-xl font-semibold text-lg transition-all"
                >
                    Sign In
                </Link>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <Wallet class="w-5 h-5 text-white" />
                        </div>
                        <span class="ml-2 text-xl font-semibold">M.A.M.</span>
                    </div>
                    <p class="text-gray-400">
                        Money Accounts Management - Your trusted partner for personal finance management.
                    </p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Features</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Account Management</li>
                        <li>Transaction Tracking</li>
                        <li>Multi-Currency</li>
                        <li>Real-time Analytics</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Documentation</li>
                        <li>Help Center</li>
                        <li>Contact Us</li>
                        <li>Privacy Policy</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Company</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>About Us</li>
                        <li>Blog</li>
                        <li>Careers</li>
                        <li>Terms of Service</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; {{ currentYear }} Money Accounts Management. All rights reserved.</p>
            </div>
        </div>
    </footer>
</template>
