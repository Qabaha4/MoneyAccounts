<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const { t } = useI18n();

const props = withDefaults(defineProps<{
  open: boolean;
  title?: string;
  description?: string;
}>(), {
  title: '',
  description: '',
});

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const passcode = ref('');
const error = ref('');
const submitting = ref(false);
const retryAfter = ref(0);
let countdownTimer: number | null = null;

const stopCountdown = () => {
  if (countdownTimer !== null) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
};

const formatTimeLeft = (seconds: number) => {
  const minutes = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${minutes}m ${secs}s`;
};

const onKeydown = (e: KeyboardEvent) => {
  if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !/\d/.test(e.key)) {
    e.preventDefault();
  }
};

const onInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const sanitized = target.value.replace(/\D/g, '').slice(0, 6);
  if (target.value !== sanitized) {
    target.value = sanitized;
  }
  passcode.value = sanitized;
};

watch(() => props.open, (val) => {
  stopCountdown();
  if (val) {
    passcode.value = '';
    error.value = '';
    retryAfter.value = 0;
    submitting.value = false;
  }
});

onUnmounted(stopCountdown);

const verify = () => {
  if (!passcode.value.trim()) return;
  submitting.value = true;
  error.value = '';

  router.post('/passcode/verify', {
    passcode: passcode.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:open', false);
    },
    onError: (errs) => {
      const message = (errs as any).passcode;
      const retry = parseInt((errs as any).passcode_retry_after, 10);

      stopCountdown();
      if (message === 'too_many_attempts' && !Number.isNaN(retry) && retry > 0) {
        retryAfter.value = retry;
        error.value = t('passcode.too_many_attempts', { time: formatTimeLeft(retryAfter.value) });
        countdownTimer = window.setInterval(() => {
          retryAfter.value -= 1;
          if (retryAfter.value <= 0) {
            stopCountdown();
            error.value = '';
          } else {
            error.value = t('passcode.too_many_attempts', { time: formatTimeLeft(retryAfter.value) });
          }
        }, 1000);
      } else {
        error.value = message || t('passcode.invalid');
      }
      submitting.value = false;
    },
    onFinish: () => {
      submitting.value = false;
    },
  });
};

const close = () => {
  emit('update:open', false);
};
</script>

<template>
  <Dialog :open="props.open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[400px]">
      <DialogHeader>
        <DialogTitle>{{ props.title || t('passcode.verify_title') }}</DialogTitle>
        <DialogDescription>
          {{ props.description || t('passcode.verify_description') }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="verify" class="space-y-4">
        <div class="space-y-2">
          <Label for="passcode-input">{{ t('passcode.edit_passcode_label') }}</Label>
          <Input
            id="passcode-input"
            v-model="passcode"
            type="password"
            inputmode="numeric"
            maxlength="6"
            autocomplete="off"
            :placeholder="t('passcode.verify_placeholder')"
            @keydown="onKeydown"
            @input="onInput"
          />
          <InputError :message="error || ''" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="close">
            {{ t('common.cancel') }}
          </Button>
          <Button type="submit" :disabled="submitting || !passcode.trim()">
            {{ submitting ? t('passcode.verifying') : t('passcode.unlock') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
