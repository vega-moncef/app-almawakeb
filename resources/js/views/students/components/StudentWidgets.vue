<template>
  <BCol xl="3" lg="6">
    <BCard no-body>
      <BCardHeader class="d-flex align-items-center border-bottom border-dashed">
        <BCardTitle class="mb-0">Année Académique</BCardTitle>
        <div class="ms-auto">
          <BDropdown variant="link" toggle-class="p-0 m-0" menu-class="dropdown-menu-end" no-caret>
            <template v-slot:button-content>
              <a href="#" class="dropdown-toggle drop-arrow-none card-drop p-0">
                <i class="ti ti-dots-vertical"></i>
              </a>
            </template>
            <BDropdownItem>Exporter</BDropdownItem>
            <BDropdownItem>Partager</BDropdownItem>
          </BDropdown>
        </div>
      </BCardHeader>
      <BCardBody>
        <BRow>
          <BCol lg="5">
            <h5 class="text-dark fw-medium mb-1">{{ totalEnrollments }}</h5>
            <p class="text-muted mb-0">Inscriptions totales</p>
          </BCol>
          <BCol lg="4" cols="3" class="text-center">
            <h5 class="text-dark fw-medium mb-1">{{ pendingApplications }}</h5>
            <p class="text-muted mb-0">En attente</p>
          </BCol>
          <BCol xl="3" cols="3" class="text-end">
            <h5 class="text-dark fw-medium mb-1">{{ daysLeftInAcademicYear }}</h5>
            <p class="text-muted mb-0">Jours restants</p>
          </BCol>
        </BRow>
        <BProgress class="progress-lg bg-light-subtle rounded-0 gap-1 overflow-visible mt-2" style="height: 10px;">
          <BProgressBar variant="success" class="rounded-pill" :value="enrollmentProgress" />
          <BProgressBar variant="warning" class="rounded-pill" :value="pendingProgress" />
          <BProgressBar variant="info" class="rounded-pill" :value="remainingProgress" />
        </BProgress>
        <p class="mb-0 mt-3">
          <span class="text-success fw-medium mb-0">
            <i class="ri-arrow-up-line"></i>{{ growthPercentage }}%
          </span>
          vs année précédente
        </p>
      </BCardBody>
      <BCardFooter class="d-flex justify-content-between py-2">
        <p class="text-muted mb-0 d-flex align-items-center gap-1">
          Mis à jour <span>:</span> 
          <span class="text-dark">{{ lastUpdated }}</span>
        </p>
        <a href="#!" class="link-primary fw-medium">Voir plus</a>
      </BCardFooter>
    </BCard>
  </BCol>
  
  <BCol xl="3" lg="6">
    <BCard no-body class="bg-primary bg-gradient">
      <BCardBody>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <BCardTitle class="mb-2 text-white">Classes Actives</BCardTitle>
            <p class="text-white fw-medium fs-24 mb-0">{{ totalClasses }}</p>
          </div>
          <div>
            <div class="avatar-md bg-light rounded">
              <div class="avatar-title">
                <i class="ri-school-line fs-32 text-primary"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="text-white-50 mb-2">
          <div class="d-flex justify-content-between mb-1">
            <span>Occupées: {{ occupiedClasses }}</span>
            <span>{{ Math.round((occupiedClasses / totalClasses) * 100) }}%</span>
          </div>
          <BProgress class="bg-white bg-opacity-25" style="height: 4px;">
            <BProgressBar variant="light" :value="(occupiedClasses / totalClasses) * 100" />
          </BProgress>
        </div>
      </BCardBody>
    </BCard>
  </BCol>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAcademicYearStore } from '@/stores/academicYear'

const academicYearStore = useAcademicYearStore()

// Reactive data
const enrollmentStats = ref({
  totalEnrollments: 0,
  pendingApplications: 0,
  totalClasses: 0,
  occupiedClasses: 0,
  growthPercentage: 0
})

// Props
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalEnrollments: 25,
      pendingApplicationions: 8,
      totalClasses: 12,
      occupiedClasses: 10,
      growthPercentage: 15.4
    })
  }
})

// Computed properties
const totalEnrollments = computed(() => enrollmentStats.value.totalEnrollments)
const pendingApplications = computed(() => enrollmentStats.value.pendingApplications)
const totalClasses = computed(() => enrollmentStats.value.totalClasses)
const occupiedClasses = computed(() => enrollmentStats.value.occupiedClasses)
const growthPercentage = computed(() => enrollmentStats.value.growthPercentage)

const daysLeftInAcademicYear = computed(() => {
  const now = new Date()
  const currentAcademicYear = academicYearStore.currentAcademicYear
  
  if (!currentAcademicYear?.end_date) {
    // Fallback if no academic year data
    return 'N/A'
  }
  
  const endDate = new Date(currentAcademicYear.end_date)
  const diffTime = endDate.getTime() - now.getTime()
  
  if (diffTime <= 0) {
    // Academic year has ended
    return 'Terminée'
  }
  
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const enrollmentProgress = computed(() => {
  const total = totalEnrollments.value + pendingApplications.value
  return total > 0 ? (totalEnrollments.value / total) * 100 : 0
})

const pendingProgress = computed(() => {
  const total = totalEnrollments.value + pendingApplications.value  
  return total > 0 ? (pendingApplications.value / total) * 100 : 0
})

const remainingProgress = computed(() => {
  return 100 - enrollmentProgress.value - pendingProgress.value
})

const lastUpdated = computed(() => {
  const now = new Date()
  return now.toLocaleDateString('fr-FR')
})

onMounted(() => {
  enrollmentStats.value = { ...props.stats }
})

// Watch for props changes
import { watch } from 'vue'
watch(() => props.stats, (newStats) => {
  enrollmentStats.value = { ...newStats }
}, { deep: true })
</script>