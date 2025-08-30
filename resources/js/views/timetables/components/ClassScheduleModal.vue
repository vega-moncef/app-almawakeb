<template>
  <BModal
    v-model="localShow"
    :title="`Emploi du temps - ${classInfo?.name} (${classInfo?.level?.name})`"
    size="xl"
    hide-footer
  >
    <div v-if="loading" class="text-center my-4">
      <BSpinner class="align-middle"></BSpinner>
      <p class="mt-2">Chargement de l'emploi du temps...</p>
    </div>

    <div v-else>
      <!-- Class Info Header -->
      <div class="mb-4 p-3 bg-light rounded">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">{{ classInfo?.name }}</h5>
            <p class="mb-0 text-muted">{{ classInfo?.level?.name }} - Année {{ classInfo?.academic_year?.name }}</p>
          </div>
          <div class="text-end">
            <BButton variant="primary" size="sm" @click="printSchedule">
              <i class="ri-printer-line me-1"></i> Imprimer
            </BButton>
            <BButton variant="success" size="sm" @click="exportPDF" class="ms-2">
              <i class="ri-download-line me-1"></i> PDF
            </BButton>
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
                    <div class="subject-name">
                      {{ getScheduleEntry(dayNum, timeSlot.id)?.subject?.name }}
                    </div>
                    <div class="teacher-name">
                      {{ getScheduleEntry(dayNum, timeSlot.id)?.teacher?.full_name }}
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

      <!-- Legend -->
      <div class="mt-3">
        <div class="row">
          <div class="col-md-6">
            <h6>Légende des matières :</h6>
            <div class="subject-legend">
              <div 
                v-for="subject in uniqueSubjects" 
                :key="subject.id"
                class="legend-item"
              >
                <span 
                  class="legend-color"
                  :style="{ backgroundColor: getSubjectColor(subject.name) }"
                ></span>
                {{ subject.name }}
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6>Statistiques :</h6>
            <ul class="list-unstyled">
              <li><strong>Total heures/semaine :</strong> {{ totalHours }}</li>
              <li><strong>Matières enseignées :</strong> {{ uniqueSubjects.length }}</li>
              <li><strong>Enseignants :</strong> {{ uniqueTeachers.length }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </BModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from '@/helpers/toast'
import axios from 'axios'

// Props
interface Props {
  show: boolean
  classId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  classId: null
})

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const classInfo = ref(null)
const schedule = ref({})
const timeSlots = ref([])
const days = ref({})

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const uniqueSubjects = computed(() => {
  const subjects = new Map()
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry && entry.subject) {
        subjects.set(entry.subject.id, entry.subject)
      }
    })
  })
  
  return Array.from(subjects.values())
})

const uniqueTeachers = computed(() => {
  const teachers = new Map()
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry && entry.teacher) {
        teachers.set(entry.teacher.id, entry.teacher)
      }
    })
  })
  
  return Array.from(teachers.values())
})

const totalHours = computed(() => {
  let total = 0
  
  Object.values(schedule.value).forEach((day: any) => {
    Object.values(day.slots || {}).forEach((entry: any) => {
      if (entry) {
        // Assume each slot is 1 hour, adjust based on your time slot duration
        total += 1
      }
    })
  })
  
  return total
})

// Methods
const fetchClassSchedule = async () => {
  if (!props.classId) return
  
  loading.value = true
  try {
    const response = await axios.get(`/api/timetables/class/${props.classId}/schedule`)
    
    classInfo.value = response.data.class
    schedule.value = response.data.schedule
    timeSlots.value = response.data.timeSlots
    days.value = response.data.days
  } catch (error) {
    useToast().error('Erreur lors du chargement de l\'emploi du temps')
    console.error('Error fetching class schedule:', error)
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
  
  // Color based on subject
  const subjectName = entry.subject?.name?.toLowerCase() || ''
  
  if (subjectName.includes('math')) return 'subject-math'
  if (subjectName.includes('français') || subjectName.includes('french')) return 'subject-french'
  if (subjectName.includes('english') || subjectName.includes('anglais')) return 'subject-english'
  if (subjectName.includes('pc') || subjectName.includes('physique')) return 'subject-science'
  if (subjectName.includes('svt') || subjectName.includes('bio')) return 'subject-biology'
  if (subjectName.includes('histoire') || subjectName.includes('géo')) return 'subject-history'
  if (subjectName.includes('arabe') || subjectName.includes('تربية')) return 'subject-arabic'
  
  return 'subject-default'
}

const getSubjectColor = (subjectName: string) => {
  const name = subjectName?.toLowerCase() || ''
  
  if (name.includes('math')) return '#e74c3c'
  if (name.includes('français') || name.includes('french')) return '#3498db'
  if (name.includes('english') || name.includes('anglais')) return '#2ecc71'
  if (name.includes('pc') || name.includes('physique')) return '#f39c12'
  if (name.includes('svt') || name.includes('bio')) return '#27ae60'
  if (name.includes('histoire') || name.includes('géo')) return '#8e44ad'
  if (name.includes('arabe') || name.includes('تربية')) return '#16a085'
  
  return '#95a5a6'
}

const formatTimeRange = (startTime: string, endTime: string) => {
  // Format time from "HH:MM:SS" to "HH:MM"
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
  // Implementation for PDF export would go here
  useToast().info('Fonction PDF en cours de développement')
}

// Watchers
watch(() => props.show, (show) => {
  if (show && props.classId) {
    fetchClassSchedule()
  }
})

watch(() => props.classId, (newClassId) => {
  if (newClassId && props.show) {
    fetchClassSchedule()
  }
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

.subject-name {
  font-weight: bold;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.teacher-name {
  font-size: 0.75rem;
  opacity: 0.9;
}

.empty-cell {
  height: 100%;
  background-color: #f8f9fa;
  border-radius: 4px;
}

/* Subject Colors */
.subject-math {
  background-color: #e74c3c;
}

.subject-french {
  background-color: #3498db;
}

.subject-english {
  background-color: #2ecc71;
}

.subject-science {
  background-color: #f39c12;
}

.subject-biology {
  background-color: #27ae60;
}

.subject-history {
  background-color: #8e44ad;
}

.subject-arabic {
  background-color: #16a085;
}

.subject-default {
  background-color: #95a5a6;
}

/* Legend */
.subject-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.legend-item {
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.legend-color {
  width: 20px;
  height: 15px;
  border-radius: 3px;
  margin-right: 8px;
}

/* Print Styles */
@media print {
  .modal-header,
  .modal-footer,
  .btn,
  .legend-item:nth-child(n+6) {
    display: none !important;
  }
  
  .timetable-table {
    font-size: 0.8rem;
  }
  
  .schedule-entry {
    font-size: 0.75rem;
  }
  
  .subject-name {
    font-size: 0.8rem;
  }
  
  .teacher-name {
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
  
  .subject-name {
    font-size: 0.8rem;
  }
  
  .teacher-name {
    font-size: 0.7rem;
  }
}
</style>