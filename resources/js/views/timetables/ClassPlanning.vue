<template>
  <VerticalLayout>
    <PageTitle title="Planning Classe" subtitle="Emploi du Temps" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Class Selection -->
            <BRow class="mb-4">
              <BCol md="4">
                <BFormSelect
                  v-model="selectedClassId"
                  :options="classOptions"
                  @change="loadClassSchedule"
                />
              </BCol>
              <BCol md="4">
                <BFormSelect
                  v-model="selectedAcademicYear"
                  :options="academicYearOptions"
                  @change="loadClassSchedule"
                />
              </BCol>
              <BCol md="4" class="text-end">
                <BButton variant="primary" @click="printSchedule" v-if="classData">
                  <i class="ri-printer-line me-1"></i> Imprimer
                </BButton>
              </BCol>
            </BRow>

            <!-- Loading State -->
            <div v-if="loading" class="text-center p-4">
              <BSpinner class="me-2"></BSpinner>
              <span>Chargement du planning...</span>
            </div>

            <!-- Schedule Table -->
            <div v-else-if="classData" class="schedule-container">
              <div class="schedule-header text-center mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="school-logo">
                    <div class="logo-placeholder" style="height: 80px; width: 80px; border: 3px solid #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                      <i class="ri-school-line text-primary" style="font-size: 2rem;"></i>
                    </div>
                  </div>
                  <div class="schedule-title flex-grow-1">
                    <h2 class="text-danger fw-bold mb-1">EMPLOI DU TEMPS</h2>
                    <h4 class="text-danger fw-bold mb-1">{{ classData.name }} {{ classData.level?.name }}</h4>
                    <div class="academic-year border border-dark px-3 py-1 d-inline-block">
                      <strong>Année scolaire: {{ currentAcademicYear }}</strong>
                    </div>
                  </div>
                  <div style="width: 80px;"></div> <!-- Spacer for centering -->
                </div>
              </div>

              <!-- Timetable Grid -->
              <div class="table-responsive">
                <table class="table table-bordered schedule-table">
                  <thead>
                    <tr>
                      <th class="text-center align-middle" style="min-width: 100px;">Horaire</th>
                      <th v-for="timeSlot in timeSlots" :key="timeSlot.id" class="text-center time-slot-header">
                        <div>{{ formatTimeSlot(timeSlot.start_time) }}</div>
                        <div>{{ formatTimeSlot(timeSlot.end_time) }}</div>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="day in weekDays" :key="day.value" class="day-row">
                      <td class="day-header text-center fw-bold">{{ day.label }}</td>
                      <td v-for="timeSlot in timeSlots" :key="`${day.value}-${timeSlot.id}`" 
                          class="schedule-cell"
                          :class="getCellClass(day.value, timeSlot.id)">
                        <div v-if="getScheduleEntry(day.value, timeSlot.id)" 
                             class="subject-cell h-100 d-flex align-items-center justify-content-center">
                          <div class="text-center">
                            <div class="subject-name fw-bold">{{ getScheduleEntry(day.value, timeSlot.id)?.subject?.name }}</div>
                            <small class="teacher-name">{{ getScheduleEntry(day.value, timeSlot.id)?.teacher?.full_name }}</small>
                          </div>
                        </div>
                        <div v-else-if="timeSlot.is_break" class="break-cell h-100 d-flex align-items-center justify-content-center">
                          <div class="text-center">
                            <strong class="text-danger">{{ getBreakLabel(timeSlot) }}</strong><br>
                            <small>{{ timeSlot.duration_in_minutes }} min</small>
                            <div v-if="timeSlot.break_type && timeSlot.break_type !== 'recreation'" class="break-type">
                              <small class="text-muted">({{ formatBreakType(timeSlot.break_type) }})</small>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center p-5">
              <i class="ri-calendar-line display-4 text-muted"></i>
              <h5 class="mt-3">Sélectionnez une classe pour afficher son emploi du temps</h5>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import axios from 'axios'

// State
const loading = ref(false)
const selectedClassId = ref('')
const selectedAcademicYear = ref('')
const classData = ref(null)
const schedule = ref({})
const timeSlots = ref([])
const classes = ref([])
const academicYears = ref([])
const currentAcademicYear = ref('')

// Computed
const classOptions = computed(() => [
  { value: '', text: 'Sélectionner une classe' },
  ...classes.value.map(cls => ({ 
    value: cls.id.toString(), 
    text: `${cls.name} - ${cls.level?.name}` 
  }))
])

const academicYearOptions = computed(() => [
  { value: '', text: 'Année courante' },
  ...academicYears.value.map(year => ({ 
    value: year.id.toString(), 
    text: year.name 
  }))
])

const weekDays = [
  { value: 1, label: 'Lundi' },
  { value: 2, label: 'Mardi' },
  { value: 3, label: 'Mercredi' },
  { value: 4, label: 'Jeudi' },
  { value: 5, label: 'Vendredi' }
]

// Methods
const fetchFormData = async () => {
  try {
    const response = await axios.get('/api/timetables/create')
    classes.value = response.data.classes || []
    academicYears.value = response.data.academicYears || []
    currentAcademicYear.value = response.data.academicYear?.name || '2024-2025'
  } catch (error) {
    console.error('Error fetching form data:', error)
  }
}

const loadClassSchedule = async () => {
  if (!selectedClassId.value) {
    classData.value = null
    return
  }

  loading.value = true
  try {
    const params: any = {}
    if (selectedAcademicYear.value) {
      params.academic_year_id = selectedAcademicYear.value
    }

    const response = await axios.get(`/api/timetables/class/${selectedClassId.value}/schedule`, { params })
    
    classData.value = response.data.class
    schedule.value = response.data.schedule
    timeSlots.value = response.data.timeSlots
    
  } catch (error) {
    useToast().error('Erreur lors du chargement du planning')
    console.error('Error loading class schedule:', error)
  } finally {
    loading.value = false
  }
}

const getScheduleEntry = (dayOfWeek: number, timeSlotId: number) => {
  return schedule.value[dayOfWeek]?.slots?.[timeSlotId] || null
}

const getCellClass = (dayOfWeek: number, timeSlotId: number) => {
  const entry = getScheduleEntry(dayOfWeek, timeSlotId)
  const timeSlot = timeSlots.value.find(slot => slot.id === timeSlotId)
  
  if (timeSlot?.is_break) {
    return 'break-time-cell'
  }
  
  if (entry) {
    // Color coding based on subject
    const subjectColors = {
      'Français': 'french-subject',
      'Mathématiques': 'math-subject', 
      'Maths': 'math-subject',
      'Anglais': 'english-subject',
      'English': 'english-subject',
      'Arabe': 'arabic-subject',
      'عربية': 'arabic-subject',
      'Histoire': 'history-subject',
      'HG': 'history-subject',
      'SVT': 'science-subject',
      'PC': 'physics-subject',
      'Arts/Music': 'arts-subject',
      'Info': 'info-subject',
      'EPS': 'sports-subject',
      'تربية إسلامية': 'islamic-subject'
    }
    
    const subjectName = entry.subject?.name || ''
    for (const [subject, className] of Object.entries(subjectColors)) {
      if (subjectName.includes(subject)) {
        return className
      }
    }
    return 'default-subject'
  }
  
  return ''
}

const formatTimeSlot = (time: string) => {
  if (!time) return ''
  return time.substring(0, 5) // Format HH:MM
}

const printSchedule = () => {
  window.print()
}

const getBreakLabel = (timeSlot: any) => {
  if (!timeSlot.is_break) return ''
  
  const breakLabels = {
    'recreation': 'Récréation',
    'lunch': 'Pause déjeuner', 
    'morning_break': 'Pause matinale',
    'afternoon_break': 'Pause après-midi'
  }
  
  return breakLabels[timeSlot.break_type] || 'Récréation'
}

const formatBreakType = (breakType: string) => {
  const types = {
    'recreation': 'Récréation',
    'lunch': 'Déjeuner',
    'morning_break': 'Matinale',
    'afternoon_break': 'Après-midi'
  }
  
  return types[breakType] || breakType
}

// Lifecycle
onMounted(async () => {
  await fetchFormData()
})

// Watch for class selection changes
watch(selectedClassId, (newValue) => {
  if (newValue) {
    loadClassSchedule()
  }
})
</script>

<style scoped>
.schedule-container {
  font-family: 'Arial', sans-serif;
}

.schedule-header {
  page-break-inside: avoid;
}

.schedule-title h2, .schedule-title h4 {
  font-weight: bold;
  margin: 0;
}

.academic-year {
  font-size: 14px;
  border: 2px solid #000 !important;
  background: white;
}

.schedule-table {
  border: 2px solid #000;
  margin: 0;
}

.schedule-table th,
.schedule-table td {
  border: 1px solid #000;
  padding: 8px;
  vertical-align: middle;
  text-align: center;
}

.time-slot-header {
  background-color: #f8f9fa;
  font-weight: bold;
  min-width: 80px;
  font-size: 12px;
}

.day-header {
  background-color: #f8f9fa;
  font-weight: bold;
  writing-mode: horizontal-tb;
  min-width: 100px;
}

.schedule-cell {
  height: 60px;
  position: relative;
  padding: 4px;
}

.subject-cell {
  border-radius: 2px;
  color: #000;
  font-weight: bold;
  text-align: center;
  padding: 2px;
}

.subject-name {
  font-size: 14px;
  line-height: 1.2;
}

.teacher-name {
  font-size: 10px;
  opacity: 0.9;
}

/* Subject Colors - matching the exact image colors */
.french-subject .subject-cell {
  background-color: #90EE90; /* Light green for French */
}

.math-subject .subject-cell {
  background-color: #90EE90; /* Light green for Maths */
}

.english-subject .subject-cell {
  background-color: #90EE90; /* Light green for English */
}

.arabic-subject .subject-cell {
  background-color: #FFFF99; /* Light yellow for Arabic */
}

.history-subject .subject-cell {
  background-color: #90EE90; /* Light green for HG */
}

.science-subject .subject-cell {
  background-color: #90EE90; /* Light green for SVT */
}

.physics-subject .subject-cell {
  background-color: #87CEEB; /* Sky blue for PC */
}

.arts-subject .subject-cell {
  background-color: #D3D3D3; /* Light gray for Arts/Music */
}

.info-subject .subject-cell {
  background-color: #90EE90; /* Light green for Info */
}

.sports-subject .subject-cell {
  background-color: #90EE90; /* Light green for EPS */
}

.islamic-subject .subject-cell {
  background-color: #FFFF99; /* Light yellow for Islamic education */
}

.default-subject .subject-cell {
  background-color: #E6E6FA; /* Light lavender default */
}

.break-time-cell {
  background-color: #fff3cd;
  border: 2px solid #ffeaa7;
}

.break-cell {
  color: #856404;
  font-weight: bold;
}

.break-type {
  margin-top: 2px;
  font-size: 11px;
}


/* Print Styles */
@media print {
  .schedule-container {
    font-size: 12px;
  }
  
  .no-print {
    display: none !important;
  }
  
  .schedule-table {
    width: 100%;
    table-layout: fixed;
  }
  
  .schedule-cell {
    height: 50px;
  }
  
  .subject-name {
    font-size: 12px;
  }
  
  .teacher-name {
    font-size: 9px;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .schedule-table {
    font-size: 10px;
  }
  
  .schedule-cell {
    height: 50px;
    padding: 2px;
  }
  
  .subject-name {
    font-size: 11px;
  }
  
  .teacher-name {
    font-size: 8px;
  }
}
</style>