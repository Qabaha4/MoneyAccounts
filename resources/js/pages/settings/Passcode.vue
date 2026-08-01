<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';
import { show as showPasscode, update, destroy, dashboardBalance } from '@/routes/passcode';
import PasscodeModal from '@/components/PasscodeModal.vue';
import { Switch } from '@/components/ui/switch';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{
  hasPasscode: boolean;
  hideDashboardBalance: boolean;
}>();

const isRevealModalOpen = ref(false);
const isHidden = ref(props.hideDashboardBalance);
const saving = ref(false);
const recentlySaved = ref(false);

watch(
  () => props.hideDashboardBalance,
  (value) => {
    isHidden.value = value;
  },
);

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: t('passcode.title'),
    href: showPasscode().url,
  },
];

const setForm = useForm({
  current_password: '',
  passcode: '',
  passcode_confirmation: '',
});

const disableForm = useForm({
  current_password: '',
});

const set = () => {
  setForm.post(update().url, {
    preserveScroll: true,
    onSuccess: () => {
      setForm.reset();
    },
  });
};

const disable = () => {
  disableForm.delete(destroy().url, {
    preserveScroll: true,
    onSuccess: () => {
      disableForm.reset();
    },
  });
};

const persistBalanceSetting = (hidden: boolean) => {
  saving.value = true;
  recentlySaved.value = false;

  router.post(dashboardBalance().url, { hidden }, {
    preserveScroll: true,
    onSuccess: () => {
      recentlySaved.value = true;
      setTimeout(() => {
        recentlySaved.value = false;
      }, 2000);
    },
    onFinish: () => {
      saving.value = false;
    },
  });
};

const saveBalanceSetting = () => {
  if (isHidden.value) {
    persistBalanceSetting(true);
  } else if (props.hasPasscode) {
    isRevealModalOpen.value = true;
  } else {
    persistBalanceSetting(false);
  }
};

const handleRevealModalChange = (open: boolean) => {
  isRevealModalOpen.value = open;
  if (!open) {
    isHidden.value = props.hideDashboardBalance;
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head :title="t('passcode.title')" />

    <SettingsLayout>
      <div class="space-y-6">
        <HeadingSmall
          :title="t('passcode.heading')"
          :description="t('passcode.description')"
        />

        <!-- Set/Change Passcode -->
        <form @submit.prevent="set" class="space-y-6">
          <div class="grid gap-2">
            <Label for="current_password">{{ t('password.current_password') }}</Label>
            <Input
              id="current_password"
              v-model="setForm.current_password"
              type="password"
              class="mt-1 block w-full"
              autocomplete="current-password"
              :placeholder="t('password.current_password_placeholder')"
            />
            <InputError :message="setForm.errors.current_password" />
          </div>

          <div class="grid gap-2">
            <Label for="passcode">{{ t('passcode.new_passcode') }}</Label>
            <Input
              id="passcode"
              v-model="setForm.passcode"
              type="password"
              inputmode="numeric"
              maxlength="6"
              class="mt-1 block w-full"
              autocomplete="off"
              :placeholder="t('passcode.new_passcode_placeholder')"
            />
            <InputError :message="setForm.errors.passcode" />
          </div>

          <div class="grid gap-2">
            <Label for="passcode_confirmation">{{ t('passcode.confirm_passcode') }}</Label>
            <Input
              id="passcode_confirmation"
              v-model="setForm.passcode_confirmation"
              type="password"
              inputmode="numeric"
              maxlength="6"
              class="mt-1 block w-full"
              autocomplete="off"
              :placeholder="t('passcode.confirm_passcode_placeholder')"
            />
            <InputError :message="setForm.errors.passcode_confirmation" />
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="setForm.processing">
              {{ props.hasPasscode ? t('passcode.change') : t('passcode.set') }}
            </Button>
            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p v-show="setForm.recentlySuccessful" class="text-sm text-neutral-600">
                {{ t('passcode.saved') }}
              </p>
            </Transition>
          </div>
        </form>

        <!-- Disable Passcode -->
        <form v-if="props.hasPasscode" @submit.prevent="disable" class="space-y-6 pt-6 border-t">
          <HeadingSmall
            :title="t('passcode.disable_heading')"
            :description="t('passcode.disable_description')"
          />

          <div class="grid gap-2">
            <Label for="disable_current_password">{{ t('password.current_password') }}</Label>
            <Input
              id="disable_current_password"
              v-model="disableForm.current_password"
              type="password"
              class="mt-1 block w-full"
              autocomplete="current-password"
              :placeholder="t('password.current_password_placeholder')"
            />
            <InputError :message="disableForm.errors.current_password" />
          </div>

          <Button variant="destructive" :disabled="disableForm.processing">
            {{ t('passcode.disable') }}
          </Button>
        </form>

        <!-- Hide/Show Dashboard Balance -->
        <div class="space-y-6 pt-6 border-t">
          <HeadingSmall
            :title="t('passcode.dashboard_hide_heading')"
            :description="t('passcode.dashboard_hide_description')"
          />

          <div class="flex items-center justify-between gap-4">
            <Label for="hide-dashboard-balance">{{ t('passcode.hide_dashboard') }}</Label>
            <Switch
              id="hide-dashboard-balance"
              :model-value="isHidden"
              @update:model-value="isHidden = $event"
            />
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="saving" @click="saveBalanceSetting">
              {{ saving ? t('common.saving') : t('common.save') }}
            </Button>
            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p v-show="recentlySaved" class="text-sm text-neutral-600">
                {{ t('common.saved') }}
              </p>
            </Transition>
          </div>
        </div>
      </div>

      <PasscodeModal
        v-model:open="isRevealModalOpen"
        :title="t('passcode.verify_title')"
        :description="t('passcode.dashboard_hide_verify_description')"
        :action-url="dashboardBalance().url"
        :extra-data="{ hidden: false }"
        @update:open="handleRevealModalChange"
      />
    </SettingsLayout>
  </AppLayout>
</template>
