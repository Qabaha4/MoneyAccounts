<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { show as showPasscode, update, destroy } from '@/routes/passcode';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{
  hasPasscode: boolean;
}>();

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
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
