<template>
  <VerticalLayout>
    <PageTitle title="Planning Enseignant" subtitle="Emplois du Temps" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Teacher and Academic Year Selection -->
            <BRow class="mb-4">
              <BCol md="4">
                <label class="form-label">Sélectionner un enseignant</label>
                <BFormSelect
                  v-model="selectedTeacherId"
                  :options="teacherOptions"
                  @change="loadTeacherSchedule"
                />
              </BCol>
              <BCol md="3">
                <label class="form-label">Année académique</label>
                <BFormSelect
                  v-model="selectedAcademicYear"
                  :options="academicYearOptions"
                  @change="loadTeacherSchedule"
                />
              </BCol>
              <BCol md="5" class="d-flex align-items-end">
                <BButton 
                  variant="primary" 
                  @click="printSchedule"
                  :disabled="!selectedTeacherId || loading"
                  class="me-2"
                >
                  <i class="ri-printer-line me-1"></i> Imprimer
                </BButton>
                <BButton 
                  variant="success" 
                  @click="exportPDF"
                  :disabled="!selectedTeacherId || loading"
                >
                  <i class="ri-download-line me-1"></i> PDF
                </BButton>
              </BCol>
            </BRow>

            <div v-if="loading" class="text-center my-4">
              <BSpinner class="align-middle"></BSpinner>
              <p class="mt-2">Chargement de l'emploi du temps...</p>
            </div>

            <div v-else-if="selectedTeacherId">
              <!-- Teacher Info Header -->
              <div class="mb-4 p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5 class="mb-1">{{ teacherInfo?.full_name }}</h5>
                    <p class="mb-0 text-muted">{{ teacherInfo?.email }} - {{ teacherInfo?.type_display }}</p>
                    <div class="mt-2">
                      <BBadge 
                        v-for="subject in teacherInfo?.subjects" 
                        :key="subject.id"
                        variant="info" 
                        pill 
                        class="me-1"
                      >
                        {{ subject.name }}
                      </BBadge>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="teacher-stats">
                      <div><strong>{{ totalHours }}</strong> heures/semaine</div>
                      <div><strong>{{ uniqueClasses.length }}</strong> classes</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Timetable Grid -->
              <div class="timetable-container">
                <div class="table-responsive">
                  <table class="table table-bordered timetable-table">
                    <thead class="table-dark">
                      <tr>
                        <th class="time-column">Horaire</th>
                        <th v-for="(day, dayNum) in days" :key="dayNum" class="day-column">
                          {{ day.day_name }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="timeSlot in timeSlots" :key="timeSlot.id">
                        <td class="time-cell">
                          <div class="time-content">
                            <strong>{{ formatTimeRange(timeSlot.start_time, timeSlot.end_time) }}</strong>
                          </div>
                        </td>
                        <td 
                          v-for="(day, dayNum) in days" 
                          :key="`${timeSlot.id}-${dayNum}`" 
                          class="schedule-cell"
                          :class="getCellClass(dayNum, timeSlot.id)"
                        >
                          <div 
                            v-if="getScheduleEntry(dayNum, timeSlot.id)"
                            class="schedule-entry"
                            :class="getEntryClass(getScheduleEntry(dayNum, timeSlot.id))"
                          >
                            <div class="class-name">
                              {{ getScheduleEntry(dayNum, timeSlot.id)?.class_room?.name }}
                            </div>
                            <div class="subject-name">
                              {{ getScheduleEntry(dayNum, timeSlot.id)?.subject?.name }}
                            </div>
                          </div>
                          <div v-else class="empty-cell">
                            <!-- Empty cell -->
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Statistics -->
              <div class="mt-4">
                <BRow>
                  <BCol md="6">
                    <h6>Classes enseignées :</h6>
                    <div class="class-list">
                      <BBadge 
                        v-for="cls in uniqueClasses" 
                        :key="cls.id"
                        variant="primary" 
                        class="me-2 mb-1"
                      >
                        {{ cls.name }} - {{ cls.level?.name }}
                      </BBadge>
                    </div>
                  </BCol>
                  <BCol md="6">
                    <h6>Répartition des matières :</h6>
                    <div class="subject-distribution">
                      <div 
                        v-for="subject in subjectDistribution" 
                        :key="subject.id"
                        class="mb-1"
                      >
                        <strong>{{ subject.name }}:</strong> {{ subject.hours }} heures/semaine
                      </div>
                    </div>
                  </BCol>
                </BRow>
              </div>
            </div>

            <div v-else class="text-center my-5">
              <i class="ri-calendar-line" style="font-size: 4rem; color: #dee2e6;"></i>
              <h5 class="mt-3 text-muted">Sélectionnez un enseignant</h5>
              <p class="text-muted">Choisissez un enseignant pour voir son emploi du temps</p>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import axios from 'axios'

// State
const loading = ref(false)
const selectedTeacherId = ref(null)
const selectedAcademicYear = ref('')
const teacherInfo = ref(null)
const teachers = ref([])
const schedule = ref({})
const timeSlots = ref([])
const days = ref({})
const academicYears = ref([])
const currentAcademicYear = ref('')

// Computed
const teacherOptions = computed(() => [
  { value: null, text: 'Sélectionner un enseignant' },
  ...teachers.value.map(teacher => ({ value: teacher.id, text: teacher.full_name }))
])

const academicYearOptions = computed(() => [
  { value: '', text: 'Année courante' },
  ...academicYears.value.map(year => ({ 
    value: year.id.toString(), 
    text: year.name 
  }))
])

const uniqueClasses = computed(() => {
  const classes = new Map()
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry && entry.class_room) {
        classes.set(entry.class_room.id, entry.class_room)
      }
    })
  })
  
  return Array.from(classes.values())
})

const totalHours = computed(() => {
  let total = 0
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry) {
        total += 1 // Assume each slot is 1 hour
      }
    })
  })
  
  return total
})

const subjectDistribution = computed(() => {
  const distribution = new Map()
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry && entry.subject) {
        const current = distribution.get(entry.subject.id) || {
          id: entry.subject.id,
          name: entry.subject.name,
          hours: 0
        }
        current.hours += 1
        distribution.set(entry.subject.id, current)
      }
    })
  })
  
  return Array.from(distribution.values()).sort((a, b) => b.hours - a.hours)
})

// Methods
const fetchTeachers = async () => {
  try {
    const response = await axios.get('/api/timetables/create')
    teachers.value = response.data.teachers || []
    timeSlots.value = response.data.timeSlots || []
    days.value = response.data.days || {}
    academicYears.value = response.data.academicYears || []
    currentAcademicYear.value = response.data.academicYear?.name || '2024-2025'
  } catch (error) {
    useToast().error('Erreur lors du chargement des enseignants')
    console.error('Error fetching teachers:', error)
  }
}

const loadTeacherSchedule = async () => {
  if (!selectedTeacherId.value) {
    teacherInfo.value = null
    schedule.value = {}
    return
  }
  
  loading.value = true
  try {
    const params = {}
    if (selectedAcademicYear.value) {
      params.academic_year_id = selectedAcademicYear.value
    }
    
    const response = await axios.get(`/api/timetables/teacher/${selectedTeacherId.value}/schedule`, { params })
    
    teacherInfo.value = response.data.teacher
    schedule.value = response.data.schedule
    timeSlots.value = response.data.timeSlots
    days.value = response.data.days
  } catch (error) {
    useToast().error('Erreur lors du chargement de l\'emploi du temps')
    console.error('Error fetching teacher schedule:', error)
  } finally {
    loading.value = false
  }
}

const getScheduleEntry = (dayNum: number, timeSlotId: number) => {
  return schedule.value[dayNum]?.slots[timeSlotId] || null
}

const getCellClass = (dayNum: number, timeSlotId: number) => {
  const entry = getScheduleEntry(dayNum, timeSlotId)
  return entry ? 'has-entry' : 'empty-entry'
}

const getEntryClass = (entry: any) => {
  if (!entry) return ''
  
  // Color based on class level or subject
  const className = entry.class_room?.name?.toLowerCase() || ''
  
  if (className.includes('1')) return 'class-level-1'
  if (className.includes('2')) return 'class-level-2'  
  if (className.includes('3')) return 'class-level-3'
  
  return 'class-default'
}

const formatTimeRange = (startTime: string, endTime: string) => {
  const formatTime = (time: string) => {
    if (!time) return ''
    return time.substring(0, 5)
  }
  
  return `${formatTime(startTime)}-${formatTime(endTime)}`
}

const printSchedule = () => {
  window.print()
}

const exportPDF = () => {
  useToast().info('Fonction PDF en cours de développement')
}

// Lifecycle
onMounted(() => {
  fetchTeachers()
})
</script>

<style scoped>
.timetable-container {
  max-height: 600px;
  overflow: auto;
}

.timetable-table {
  min-width: 800px;
  font-size: 0.9rem;
}

.time-column {
  width: 120px;
  background-color: #f8f9fa !important;
}

.day-column {
  width: 150px;
  text-align: center;
}

.time-cell {
  background-color: #f8f9fa;
  font-weight: bold;
  vertical-align: middle;
  text-align: center;
}

.schedule-cell {
  height: 80px;
  vertical-align: middle;
  padding: 4px;
}

.schedule-entry {
  height: 100%;
  padding: 8px;
  border-radius: 4px;
  text-align: center;
  color: white;
  font-size: 0.85rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.class-name {
  font-weight: bold;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.subject-name {
  font-size: 0.75rem;
  opacity: 0.9;
}

.empty-cell {
  height: 100%;
  background-color: #f8f9fa;
  border-radius: 4px;
}

/* Class Level Colors */
.class-level-1 {
  background-color: #3498db;
}

.class-level-2 {
  background-color: #e74c3c;
}

.class-level-3 {
  background-color: #2ecc71;
}

.class-default {
  background-color: #95a5a6;
}

.teacher-stats {
  text-align: center;
  font-size: 0.9rem;
}

.teacher-stats div {
  margin-bottom: 5px;
}

.class-list {
  display: flex;
  flex-wrap: wrap;
}

.subject-distribution {
  font-size: 0.9rem;
}

/* Print Styles */
@media print {
  .btn,
  .form-control,
  .form-select {
    display: none !important;
  }
  
  .timetable-table {
    font-size: 0.8rem;
  }
  
  .schedule-entry {
    font-size: 0.75rem;
  }
  
  .class-name {
    font-size: 0.8rem;
  }
  
  .subject-name {
    font-size: 0.7rem;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .timetable-table {
    font-size: 0.8rem;
  }
  
  .day-column {
    width: 120px;
  }
  
  .time-column {
    width: 100px;
  }
  
  .schedule-entry {
    font-size: 0.75rem;
    padding: 4px;
  }
  
  .class-name {
    font-size: 0.8rem;
  }
  
  .subject-name {
    font-size: 0.7rem;
  }
}
</style>