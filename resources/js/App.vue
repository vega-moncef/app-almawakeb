<template>
  <RouterView/>
</template>

<script setup lang="ts">
import {RouterView} from 'vue-router'
import {useLayoutStore} from '@/stores/layout'
import {useAcademicYearStore} from '@/stores/academicYear'
import {onMounted} from 'vue'
// import configureFakeBackend from "@/helpers/fake-backend";



onMounted(() => {
  useLayoutStore().init()
  useAcademicYearStore().init()
})

// Commenté pour permettre les vraies API calls
// configureFakeBackend()
</script>
