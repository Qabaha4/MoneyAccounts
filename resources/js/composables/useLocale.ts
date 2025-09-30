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
      router.post('/locale', { locale: newLocale }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update frontend locale
          locale.value = newLocale
          
          // Update document direction and lang
          document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr'
          document.documentElement.lang = newLocale
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