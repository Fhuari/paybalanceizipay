<template>
  <span > {{ translated }}  </span>
</template>

<script setup>
import { computed, ref } from 'vue'

// Idioma actual: puedes reemplazar esta línea por una fuente global como i18n o Pinia
const currentLang = ref('es')// <-- Cambia esto dinámicamente si usas un store o configuración global

// Props individuales
const props = defineProps({
  lang:String,
  en: String,
  es: String,
  pt: String
})
currentLang.value=props.lang;

// Devuelve el texto según el idioma actual
const translated = computed(() => {
  return (
    (currentLang.value === 'es' && props.es) ||
    (currentLang.value === 'pt' && props.pt) ||
    props.en || '' // fallback en inglés
  )
})
</script>
<style>
.text-more{
  color: #383838;
}
</style>