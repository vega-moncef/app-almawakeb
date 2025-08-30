<template>
  <VerticalLayout>
    <PageTitle title="Planning Enseignants" subtitle="Emploi du Temps des Enseignants" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Filters and Controls -->
            <BRow class="mb-4">
              <BCol md="3">
                <BFormSelect
                  v-model="selectedSubject"
                  :options="subjectOptions"
                  @change="filterTeachers"
                />
              </BCol>
              <BCol md="3">
                <BFormSelect
                  v-model="selectedLevel"
                  :options="levelOptions"  
                  @change="filterTeachers"
                />
              </BCol>
              <BCol md="3">
                <BFormSelect
                  v-model="selectedAcademicYear"
                  :options="academicYearOptions"
                  @change="loadAllSchedules"
                />
              </BCol>
              <BCol md="3" class="text-end">
                <BButton variant="primary" @click="printAllSchedules" v-if="teacherSchedules.length">
                  <i class="ri-printer-line me-1"></i> Imprimer Tout
                </BButton>
              </BCol>
            </BRow>

            <!-- Loading State -->
            <div v-if="loading" class="text-center p-4">
              <BSpinner class="me-2"></BSpinner>
              <span>Chargement des plannings...</span>
            </div>

            <!-- Teacher Schedules Grid -->
            <div v-else-if="teacherSchedules.length" class="teacher-schedules-container">
              <BRow>
                <BCol 
                  v-for="teacherSchedule in teacherSchedules" 
                  :key="teacherSchedule.teacher.id"
                  xs="12" sm="6" lg="6" xl="6"
                  class="mb-4"
                >
                  <div class="teacher-schedule-card">
                    <!-- Teacher Header -->
                    <div class="schedule-header">
                      <div class="d-flex align-items-center mb-2">
                        <div class="school-logo me-2">
                          <div class="logo-placeholder" style="height: 40px; width: 40px; border: 2px solid #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                            <i class="ri-school-line text-primary"></i>
                          </div>
                        </div>
                        <div class="flex-grow-1 text-center">
                          <h6 class="mb-0 fw-bold text-primary">EMPLOI DU TEMPS</h6>
                        </div>
                      </div>
                      <div class="teacher-info text-center border border-dark p-2 mb-2">
                        <div class="fw-bold">Prof: {{ teacherSchedule.teacher.first_name }} {{ teacherSchedule.teacher.last_name }}</div>
                        <div class="fw-bold text-primary">Matière : {{ getTeacherMainSubject(teacherSchedule.teacher) }}</div>
                      </div>
                    </div>

                    <!-- Mini Timetable -->
                    <div class="mini-schedule">
                      <table class="table table-bordered mini-schedule-table">
                        <thead>
                          <tr>
                            <th class="time-header">Horaires</th>
                            <th v-for="timeSlot in compactTimeSlots" :key="timeSlot.id" class="time-slot-header">
                              <div class="time-slot-text">{{ formatCompactTime(timeSlot.start_time) }}</div>
                              <div class="time-slot-text">{{ formatCompactTime(timeSlot.end_time) }}</div>
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="day in weekDays" :key="day.value" class="day-row">
                            <td class="day-header">{{ day.shortLabel }}</td>
                            <td v-for="timeSlot in compactTimeSlots" :key="`${teacherSchedule.teacher.id}-${day.value}-${timeSlot.id}`" 
                                class="schedule-cell mini-cell"
                                :class="timeSlot.is_break ? 'break-time-cell' : ''">
                              <div v-if="getTeacherScheduleEntry(teacherSchedule, day.value, timeSlot.id)" 
                                   class="mini-subject-cell">
                                <div class="cell-content">
                                  <div class="class-name">{{ getClassDisplayName(getTeacherScheduleEntry(teacherSchedule, day.value, timeSlot.id)) }}</div>
                                  <div class="subject-name">{{ getSubjectDisplayName(getTeacherScheduleEntry(teacherSchedule, day.value, timeSlot.id)) }}</div>
                                </div>
                              </div>
                              <div v-else-if="timeSlot.is_break" class="mini-break-cell">
                                <div class="break-content">
                                  <span class="break-indicator">{{ getBreakLabel(timeSlot) }}</span>
                                  <small class="break-duration">{{ timeSlot.duration_in_minutes }}min</small>
                                </div>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- Break Time Indicators -->
                    <div class="break-indicators mt-2" v-if="getBreakSlots(compactTimeSlots).length">
                      <div class="row">
                        <div class="col text-center">
                          <small class="text-muted">
                            <div v-for="breakSlot in getBreakSlots(compactTimeSlots)" :key="breakSlot.id" class="break-legend">
                              <strong>{{ getBreakLabel(breakSlot) }}</strong>: {{ formatCompactTime(breakSlot.start_time) }} - {{ formatCompactTime(breakSlot.end_time) }} ({{ breakSlot.duration_in_minutes }}min)
                            </div>
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </BCol>
              </BRow>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center p-5">
              <i class="ri-user-line display-4 text-muted"></i>
              <h5 class="mt-3">Aucun planning d'enseignant trouvé</h5>
              <p class="text-muted">Vérifiez vos filtres ou ajoutez des emplois du temps</p>
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
const selectedSubject = ref('')
const selectedLevel = ref('')
const selectedAcademicYear = ref('')
const teacherSchedules = ref([])
const teachers = ref([])
const subjects = ref([])
const levels = ref([])
const academicYears = ref([])
const compactTimeSlots = ref([])
const currentAcademicYear = ref('')

// Computed
const subjectOptions = computed(() => [
  { value: '', text: 'Toutes les matières' },
  ...subjects.value.map(subject => ({ 
    value: subject.id.toString(), 
    text: subject.name 
  }))
])

const levelOptions = computed(() => [
  { value: '', text: 'Tous les niveaux' },
  ...levels.value.map(level => ({ 
    value: level.id.toString(), 
    text: level.name 
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
  { value: 1, label: 'Lundi', shortLabel: 'L' },
  { value: 2, label: 'Mardi', shortLabel: 'Ma' },
  { value: 3, label: 'Mercredi', shortLabel: 'Me' },
  { value: 4, label: 'Jeudi', shortLabel: 'J' },
  { value: 5, label: 'Vendredi', shortLabel: 'V' }
]

// Methods
const fetchFormData = async () => {
  try {
    const response = await axios.get('/api/timetables/create')
    teachers.value = response.data.teachers || []
    subjects.value = response.data.subjects || []
    levels.value = response.data.levels || []
    academicYears.value = response.data.academicYears || []
    compactTimeSlots.value = response.data.timeSlots || []
    currentAcademicYear.value = response.data.academicYear?.name || '2024-2025'
    
  } catch (error) {
    console.error('Error fetching form data:', error)
  }
}

const loadAllSchedules = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (selectedAcademicYear.value) {
      params.academic_year_id = selectedAcademicYear.value
    }

    // Get filtered teachers
    let filteredTeachers = teachers.value
    
    if (selectedSubject.value) {
      filteredTeachers = teachers.value.filter(teacher => {
        // Check if teacher has the selected subject
        return teacher.subjects?.some(subject => subject.id.toString() === selectedSubject.value)
      })
    }

    if (selectedLevel.value) {
      filteredTeachers = filteredTeachers.filter(teacher => 
        teacher.level_id?.toString() === selectedLevel.value
      )
    }

    // Load schedule for each teacher
    const schedulePromises = filteredTeachers.map(async (teacher) => {
      try {
        const response = await axios.get(`/api/timetables/teacher/${teacher.id}/schedule`, { params })
        return {
          teacher: teacher,
          schedule: response.data.schedule,
          timeSlots: response.data.timeSlots
        }
      } catch (error) {
        console.error(`Error loading schedule for teacher ${teacher.id}:`, error)
        return {
          teacher: teacher,
          schedule: {},
          timeSlots: []
        }
      }
    })

    teacherSchedules.value = await Promise.all(schedulePromises)
    
  } catch (error) {
    useToast().error('Erreur lors du chargement des plannings')
    console.error('Error loading teacher schedules:', error)
  } finally {
    loading.value = false
  }
}

const filterTeachers = () => {
  loadAllSchedules()
}

const getTeacherScheduleEntry = (teacherSchedule: any, dayOfWeek: number, timeSlotId: number) => {
  return teacherSchedule.schedule[dayOfWeek]?.slots?.[timeSlotId] || null
}

const getTeacherMainSubject = (teacher: any) => {
  return teacher.subjects?.[0]?.name || teacher.main_subject || 'N/A'
}

const getClassDisplayName = (scheduleEntry: any) => {
  if (!scheduleEntry) return ''
  const className = scheduleEntry.class_room?.name || ''
  const levelName = scheduleEntry.class_room?.level?.name || ''
  
  // Combine level and class name (e.g., "1APIC A")
  if (levelName && className) {
    return `${levelName} ${className}`
  } else if (className) {
    return className
  } else if (levelName) {
    return levelName
  }
  return ''
}

const getSubjectDisplayName = (scheduleEntry: any) => {
  if (!scheduleEntry) return ''
  const subjectName = scheduleEntry.subject?.name || ''
  return subjectName
}

const formatCompactTime = (time: string) => {
  if (!time) return ''
  return time.substring(0, 5) // Format HH:MM
}

const printAllSchedules = () => {
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
  
  return breakLabels[timeSlot.break_type] || 'Pause'
}

const getBreakSlots = (timeSlots: any[]) => {
  return timeSlots.filter(slot => slot.is_break)
}

// Lifecycle
onMounted(async () => {
  await fetchFormData()
  await loadAllSchedules()
})

// Watch for filter changes
watch([selectedSubject, selectedLevel], () => {
  loadAllSchedules()
})
</script>

<style scoped>
.teacher-schedules-container {
  font-family: 'Arial', sans-serif;
}

.teacher-schedule-card {
  border: 2px solid #000;
  padding: 15px;
  background: white;
  border-radius: 8px;
  height: fit-content;
}

.schedule-header {
  border-bottom: 1px solid #ddd;
  padding-bottom: 10px;
  margin-bottom: 15px;
}

.teacher-info {
  font-size: 12px;
  background: #f8f9fa;
}

.mini-schedule-table {
  font-size: 10px;
  border: 1px solid #000;
  margin: 0;
}

.mini-schedule-table th,
.mini-schedule-table td {
  border: 1px solid #000;
  padding: 4px 2px;
  text-align: center;
  vertical-align: middle;
}

.time-header {
  background-color: #f8f9fa;
  font-weight: bold;
  min-width: 50px;
  font-size: 9px;
}

.time-slot-header {
  background-color: #f8f9fa;
  font-weight: bold;
  min-width: 40px;
}

.time-slot-text {
  font-size: 8px;
  line-height: 1.1;
}

.day-header {
  background-color: #f8f9fa;
  font-weight: bold;
  font-size: 9px;
}

.mini-cell {
  height: 25px;
  padding: 2px;
  font-size: 8px;
}

.mini-subject-cell {
  background-color: #e3f2fd;
  border-radius: 2px;
  padding: 1px;
  font-weight: bold;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.cell-content {
  text-align: center;
}

.class-name {
  font-size: 8px;
  font-weight: bold;
  line-height: 1;
}

.subject-name {
  font-size: 6px;
  line-height: 1;
  margin-top: 1px;
}

.break-time-cell {
  background-color: #fff3cd !important;
  border: 1px solid #ffeaa7;
}

.mini-break-cell {
  background-color: #fff3cd;
  color: #856404;
  font-size: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  flex-direction: column;
}

.break-content {
  text-align: center;
  line-height: 1.1;
}

.break-indicator {
  font-size: 6px;
  font-weight: bold;
  display: block;
}

.break-duration {
  font-size: 5px;
  opacity: 0.8;
  display: block;
  margin-top: 1px;
}

.break-indicators {
  border-top: 1px solid #ddd;
  padding-top: 8px;
  font-size: 9px;
}

.break-legend {
  margin-bottom: 2px;
  font-size: 8px;
}

/* Print Styles */
@media print {
  .teacher-schedule-card {
    page-break-inside: avoid;
    margin-bottom: 20px;
    border: 2px solid #000;
  }
  
  .no-print {
    display: none !important;
  }
  
  .mini-schedule-table {
    width: 100%;
  }
  
  .teacher-schedules-container .col-6 {
    width: 48%;
    float: left;
    margin-right: 2%;
  }
  
  .teacher-schedules-container .col-6:nth-child(2n) {
    margin-right: 0;
  }
}

/* Responsive Design */
@media (max-width: 1400px) {
  .teacher-schedule-card {
    padding: 10px;
  }
  
  .mini-schedule-table {
    font-size: 9px;
  }
  
  .time-slot-text {
    font-size: 7px;
  }
}

@media (max-width: 768px) {
  .teacher-schedule-card {
    margin-bottom: 20px;
  }
  
  .mini-schedule-table {
    font-size: 8px;
  }
  
  .mini-cell {
    height: 20px;
    font-size: 7px;
  }
  
  .time-slot-text {
    font-size: 6px;
  }
}

/* Subject-specific colors for mini cells */
.mini-subject-cell.french {
  background-color: #c8e6c9;
}

.mini-subject-cell.math {
  background-color: #c8e6c9;
}

.mini-subject-cell.english {
  background-color: #c8e6c9;
}

.mini-subject-cell.arabic {
  background-color: #fff9c4;
}

.mini-subject-cell.science {
  background-color: #c8e6c9;
}

.mini-subject-cell.physics {
  background-color: #b3e5fc;
}

.mini-subject-cell.history {
  background-color: #c8e6c9;
}

.mini-subject-cell.arts {
  background-color: #e0e0e0;
}

.mini-subject-cell.sports {
  background-color: #c8e6c9;
}

.mini-subject-cell.islamic {
  background-color: #fff9c4;
}
</style>