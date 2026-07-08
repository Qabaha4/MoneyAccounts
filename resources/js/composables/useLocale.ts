import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'

export function useLocale() {
  const { locale, availableLocales } = useI18n()

  const currentLocale = computed(() => locale.value)
  
  const isRTL = computed(() => locale.value === 'ar')
  
  const setLocale = (newLocale: string) => {
    if (availableLocales.includes(newLocale)) {
      // Send request to Laravel to update session
      // Save locale on server, then do a full page refresh to
      // ensure all translations from both server and client are reloaded
      router.post('/locale', { locale: newLocale }, {
        preserveScroll: true,
        onSuccess: () => {
          window.location.reload()
        }
      })
    }
  }
  
  const toggleLocale = () => {
    const newLocale = locale.value === 'en' ? 'ar' : 'en'
    setLocale(newLocale)
  }
  
  // Watch for locale changes and update document
  watch(locale, (newLocale) => {
    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr'
    document.documentElement.lang = newLocale
  }, { immediate: true })
  
  return {
    currentLocale,
    isRTL,
    setLocale,
    toggleLocale,
    availableLocales: computed(() => availableLocales)
  }
}