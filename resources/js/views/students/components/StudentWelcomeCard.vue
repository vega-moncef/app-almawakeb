<template>
  <BCol xl="6" lg="12">
    <BCard no-body>
      <BCardBody>
        <BRow class="align-items-center">
          <BCol lg="7">
            <h4 class="text-dark mb-1">Tableau de Bord Élèves</h4>
            <p class="fs-14">Aperçu des inscriptions pour l'année {{ academicYear }}</p>
            <BRow class="align-items-center text-center mb-2">
              <BCol lg="7" class="border-end border-light">
                <BRow class="align-items-center">
                  <BCol lg="6">
                    <div class="text-center">
                      <div class="avatar-lg mx-auto bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ri-graduation-cap-line fs-24 text-primary"></i>
                      </div>
                    </div>
                  </BCol>
                  <BCol lg="6">
                    <h5>Élèves</h5>
                    <h2 class="fw-semibold text-dark">{{ totalStudents }}</h2>
                  </BCol>
                </BRow>
              </BCol>
              <BCol lg="5">
                <div class="ps-2">
                  <p class="d-flex align-items-center mb-2 gap-2">
                    <i class='ri-circle-fill text-success'></i>{{ activeStudents }} Actifs
                  </p>
                  <p class="d-flex align-items-center mb-2 gap-2">
                    <i class='ri-circle-fill text-warning'></i>{{ suspendedStudents }} Suspendus
                  </p>
                  <p class="d-flex align-items-center gap-2 mb-0">
                    <i class='ri-circle-fill text-info'></i>{{ transferredStudents }} Transférés
                  </p>
                </div>
              </BCol>
            </BRow>
            <p class="text-muted mb-0 d-flex align-items-center gap-1">
              Dernière mise à jour <span>:</span> 
              <span class="text-dark">{{ lastUpdated }}</span>
            </p>
          </BCol>
          <BCol lg="5" class="text-end">
            <img :src="studentImage" alt="Students" class="img-fluid">
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>
  </BCol>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAcademicYearStore } from '@/stores/academicYear'

// Import a student-related image (you can add this to assets/images)
import studentImage from '@/assets/images/home-2.png' // Using the same image for now

const academicYearStore = useAcademicYearStore()

// Reactive data
const stats = ref({
  total: 0,
  active: 0,
  suspended: 0,
  transferred: 0
})

// Computed properties
const academicYear = computed(() => academicYearStore.currentAcademicYear?.name || 'N/A')
const totalStudents = computed(() => stats.value.total)
const activeStudents = computed(() => stats.value.by_status?.active || 0)
const suspendedStudents = computed(() => stats.value.by_status?.suspended || 0)
const transferredStudents = computed(() => stats.value.by_status?.transferred || 0)
const lastUpdated = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('fr-FR')
})

// Load stats from props or API
const props = defineProps({
  studentStats: {
    type: Object,
    default: () => ({
      total: 0,
      by_status: {
        active: 0,
        suspended: 0,
        transferred: 0
      }
    })
  }
})

onMounted(() => {
  stats.value = { ...props.studentStats }
})

// Watch for props changes
import { watch } from 'vue'
watch(() => props.studentStats, (newStats) => {
  stats.value = { ...newStats }
}, { deep: true })
</script>