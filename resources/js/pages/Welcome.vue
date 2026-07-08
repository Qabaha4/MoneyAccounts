<script setup lang="ts">
import { dashboard, login, register, home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, onUnmounted } from 'vue';

const currentYear = new Date().getFullYear();
const mouseX = ref(0);
const mouseY = ref(0);

const handleMouse = (e: MouseEvent) => {
  mouseX.value = e.clientX;
  mouseY.value = e.clientY;
};

onMounted(() => window.addEventListener('mousemove', handleMouse));
onUnmounted(() => window.removeEventListener('mousemove', handleMouse));
</script>

<template>
  <Head title="Money Accounts - Personal Finance Manager" />

  <div class="min-h-screen bg-[#060A12] text-white overflow-x-hidden">
    <!-- Grid overlay -->
    <div class="fixed inset-0 pointer-events-none"
      :style="{
        backgroundImage: 'radial-gradient(circle at ' + mouseX + 'px ' + mouseY + 'px, rgba(59,130,246,0.08) 0%, transparent 60%)',
        backgroundSize: '100% 100%'
      }"
    />
    <div class="fixed inset-0 bg-[url(&#x27;data:image/svg+xml,<svg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;><rect width=&quot;60&quot; height=&quot;60&quot; fill=&quot;none&quot;/><rect x=&quot;0&quot; y=&quot;0&quot; width=&quot;1&quot; height=&quot;1&quot; fill=&quot;rgba(255,255,255,0.02)&quot;/></svg>&#x27;)] opacity-30 pointer-events-none" />

    <!-- Nav -->
    <nav class="fixed top-6 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-6xl z-50">
      <div class="bg-[#0C1123]/90 backdrop-blur-xl border border-white/[0.06] rounded-2xl px-6 h-16 flex items-center justify-between shadow-2xl shadow-blue-500/5">
        <Link :href="home()" class="flex items-center gap-3 group">
          <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet-icon w-5 h-5 text-white"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>
          </div>
          <span class="text-lg font-bold tracking-tight">Money Accounts</span>
        </Link>
        <div class="flex items-center gap-3">
          <template v-if="$page.props.auth.user">
            <Link :href="dashboard()" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl font-medium text-sm transition-all shadow-lg shadow-blue-600/20 hover:shadow-blue-500/30">
              Dashboard
            </Link>
          </template>
          <template v-else>
            <Link :href="login()" class="text-gray-400 hover:text-white px-4 py-2.5 text-sm font-medium transition-colors">
              Sign In
            </Link>
            <Link :href="register()" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl font-medium text-sm transition-all shadow-lg shadow-blue-600/20 hover:shadow-blue-500/30">
              Get Started
            </Link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="relative min-h-screen flex items-center pt-24 pb-20">
      <div class="max-w-7xl mx-auto px-6 w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <div class="space-y-8 relative z-10">
            <div class="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-500/20 rounded-full px-4 py-1.5 text-sm text-blue-400">
              <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse" />
              Personal Finance Manager
            </div>
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold leading-[1.1] tracking-tight">
              Take Control of Your
              <br>
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400">
                Financial World
              </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-400 leading-relaxed max-w-xl">
              Manage multiple accounts, track every transaction, and gain real-time insight into your finances — in one beautiful, secure platform.
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
              <Link
                v-if="!$page.props.auth.user"
                :href="register()"
                class="group relative inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all shadow-2xl shadow-blue-600/25 hover:shadow-blue-500/40"
              >
                Start Managing Today
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </Link>
              <Link
                v-else
                :href="dashboard()"
                class="group relative inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all shadow-2xl shadow-blue-600/25 hover:shadow-blue-500/40"
              >
                Go to Dashboard
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </Link>
              <a href="#features" class="inline-flex items-center justify-center gap-2 border border-white/10 hover:border-white/25 text-gray-300 hover:text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all">
                Explore Features
              </a>
            </div>
            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Multi-currency</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Real-time analytics</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>End-to-end encrypted</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Arabic & English</span>
              </div>
            </div>
          </div>

          <!-- Hero Card -->
          <div class="relative">
            <div class="absolute -inset-8 bg-gradient-to-br from-blue-500/20 via-purple-500/10 to-pink-500/20 rounded-[3rem] blur-3xl" />
            <div class="relative bg-[#0C1123] border border-white/[0.06] rounded-3xl p-8 shadow-2xl">
              <div class="space-y-6">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold">Account Overview</h3>
                  <span class="text-sm text-gray-500">Live</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-gradient-to-br from-blue-500/10 to-purple-500/10 border border-blue-500/10 rounded-2xl p-5">
                    <div class="text-sm text-gray-400 mb-1">Total Balance</div>
                    <div class="text-3xl font-bold text-white tabular-nums">$12,450</div>
                  </div>
                  <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/10 rounded-2xl p-5">
                    <div class="text-sm text-gray-400 mb-1">This Month</div>
                    <div class="text-3xl font-bold text-emerald-400 tabular-nums">+$1,250</div>
                  </div>
                </div>
                <div class="space-y-3">
                  <div class="flex items-center justify-between p-4 bg-white/[0.03] hover:bg-white/[0.06] rounded-xl transition-colors">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                      </div>
                      <div>
                        <div class="font-medium">Checking Account</div>
                        <div class="text-sm text-gray-500">**** 1234</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold tabular-nums">$8,450</div>
                      <div class="text-xs text-emerald-400">+2.3%</div>
                    </div>
                  </div>
                  <div class="flex items-center justify-between p-4 bg-white/[0.03] hover:bg-white/[0.06] rounded-xl transition-colors">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-gradient-to-br from-emerald-600 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-600/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                      </div>
                      <div>
                        <div class="font-medium">Savings Account</div>
                        <div class="text-sm text-gray-500">**** 5678</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold tabular-nums">$4,000</div>
                      <div class="text-xs text-emerald-400">+5.1%</div>
                    </div>
                  </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-white/[0.06] text-sm">
                  <span class="text-gray-500">12 accounts • 4 currencies</span>
                  <span class="text-blue-400 hover:text-blue-300 transition-colors cursor-pointer">View all →</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Bar -->
    <section id="stats" class="border-y border-white/[0.06] bg-white/[0.02]">
      <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-3xl font-bold text-white tabular-nums">$128M+</div>
            <div class="text-sm text-gray-500 mt-1">Managed Assets</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white tabular-nums">52K+</div>
            <div class="text-sm text-gray-500 mt-1">Active Users</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white tabular-nums">98.5%</div>
            <div class="text-sm text-gray-500 mt-1">Uptime</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-white tabular-nums">24</div>
            <div class="text-sm text-gray-500 mt-1">Currencies Supported</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-24">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center space-y-4 mb-20">
          <div class="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-500/20 rounded-full px-4 py-1.5 text-sm text-blue-400">
            Features
          </div>
          <h2 class="text-3xl md:text-5xl font-bold tracking-tight">
            Everything You Need to
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Own Your Finances</span>
          </h2>
          <p class="text-lg text-gray-400 max-w-2xl mx-auto">
            From multi-account management to detailed reporting, get a complete picture of your financial health.
          </p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-600/20 to-cyan-600/20 border border-blue-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 20V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/><path d="M6 12h.01M6 16h.01M10 12h8M10 16h8"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Multi-Account Management</h3>
            <p class="text-gray-400 leading-relaxed">Create and manage checking, savings, credit, and investment accounts all in one place. Switch between them seamlessly.</p>
          </div>
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-600/20 to-pink-600/20 border border-purple-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Transaction Tracking</h3>
            <p class="text-gray-400 leading-relaxed">Record income, expenses, and transfers with full history. Every transaction updates your balance in real time.</p>
          </div>
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-600/20 to-teal-600/20 border border-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Multi-Currency Support</h3>
            <p class="text-gray-400 leading-relaxed">Handle multiple currencies with live conversion rates. Perfect for international accounts and travelers.</p>
          </div>
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-amber-600/20 to-orange-600/20 border border-amber-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 4-6"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Dashboard & Analytics</h3>
            <p class="text-gray-400 leading-relaxed">Visual dashboards show your financial health at a glance. Track trends, monitor balances, and get actionable insights.</p>
          </div>
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-red-600/20 to-rose-600/20 border border-red-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 1 1 17"/><path d="M9 1v8H1"/><path d="M23 7 7 23"/><path d="M15 23v-8h8"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Account Transfers</h3>
            <p class="text-gray-400 leading-relaxed">Transfer funds between accounts instantly. Automatic balance updates and a complete transfer history keep you in control.</p>
          </div>
          <div class="group bg-[#0C1123] border border-white/[0.06] hover:border-blue-500/30 rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/5">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-600/20 to-violet-600/20 border border-indigo-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10"/></svg>
            </div>
            <h3 class="text-xl font-semibold mb-3">Arabic & English RTL</h3>
            <p class="text-gray-400 leading-relaxed">Full support for both English and Arabic with proper RTL layout. Built for a global audience from day one.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="relative py-24 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20" />
      <div class="absolute inset-0 bg-[#060A12] [mask-image:radial-gradient(ellipse_at_center,transparent_30%,black_70%)]" />
      <div class="relative max-w-4xl mx-auto text-center px-6">
        <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight">
          Ready to Take Control?
        </h2>
        <p class="text-lg md:text-xl text-gray-400 mb-10 max-w-2xl mx-auto">
          Join thousands managing their money with confidence. Get started free in under a minute.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            v-if="!$page.props.auth.user"
            :href="register()"
            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all shadow-2xl shadow-blue-600/25 hover:shadow-blue-500/40"
          >
            Get Started Free
          </Link>
          <Link
            v-else
            :href="dashboard()"
            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all shadow-2xl shadow-blue-600/25 hover:shadow-blue-500/40"
          >
            Go to Dashboard
          </Link>
          <Link
            v-if="!$page.props.auth.user"
            :href="login()"
            class="border border-white/10 hover:border-white/25 text-gray-300 hover:text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all"
          >
            Sign In
          </Link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/[0.06] bg-[#060A12] py-16">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-12">
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet-icon w-5 h-5 text-white"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>
              </div>
              <span class="text-lg font-bold">Money Accounts</span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
              Personal finance management platform. Track, manage, and grow your money with confidence.
            </p>
          </div>
          <div>
            <h3 class="font-semibold text-sm text-gray-400 uppercase tracking-wider mb-4">Product</h3>
            <ul class="space-y-3">
              <li><a href="#features" class="text-sm text-gray-500 hover:text-white transition-colors">Features</a></li>
              <li><a href="#stats" class="text-sm text-gray-500 hover:text-white transition-colors">Statistics</a></li>
              <li><a href="#features" class="text-sm text-gray-500 hover:text-white transition-colors">Documentation</a></li>
            </ul>
          </div>
          <div>
            <h3 class="font-semibold text-sm text-gray-400 uppercase tracking-wider mb-4">Community</h3>
            <ul class="space-y-3">
              <li>
                <a href="https://github.com/Qabaha4/MoneyAccounts" target="_blank" rel="noopener noreferrer" class="text-sm text-gray-500 hover:text-white transition-colors inline-flex items-center gap-2">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.385-1.335-1.755-1.335-1.755-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12 24 5.37 18.63 0 12 0z"/></svg>
                  GitHub
                </a>
              </li>
              <li><a href="https://github.com/Qabaha4/MoneyAccounts/issues" target="_blank" rel="noopener noreferrer" class="text-sm text-gray-500 hover:text-white transition-colors">Report Issue</a></li>
              <li><a href="https://github.com/Qabaha4/MoneyAccounts/discussions" target="_blank" rel="noopener noreferrer" class="text-sm text-gray-500 hover:text-white transition-colors">Discussions</a></li>
            </ul>
          </div>
          <div>
            <h3 class="font-semibold text-sm text-gray-400 uppercase tracking-wider mb-4">Legal</h3>
            <ul class="space-y-3">
              <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Privacy Policy</a></li>
              <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Terms of Service</a></li>
              <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">License</a></li>
            </ul>
          </div>
        </div>
        <div class="border-t border-white/[0.06] mt-12 pt-8 text-center text-sm text-gray-500">
          &copy; {{ currentYear }} Money Accounts. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>
