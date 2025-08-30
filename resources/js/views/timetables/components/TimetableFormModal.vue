<template>
  <BModal
    v-model="localShow"
    :title="isEditing ? 'Modifier l\'entrée' : 'Ajouter une entrée d\'emploi du temps'"
    size="lg"
    hide-footer
    @hidden="resetForm"
  >
    <BForm @submit.prevent="handleSubmit">
      <BRow>
        <!-- Class Selection -->
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Classe *</label>
            <BFormSelect
              v-model="form.class_id"
              :options="classOptions"
              :state="getFieldState('class_id')"
              @change="onClassChange"
              required
            />
            <BFormInvalidFeedback>{{ errors.class_id?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Subject Selection (filtered by class) -->
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Matière *</label>
            <BFormSelect
              v-model="form.subject_id"
              :options="subjectOptions"
              :state="getFieldState('subject_id')"
              :disabled="!form.class_id || loadingSubjects"
              @change="onSubjectChange"
              required
            />
            <BFormInvalidFeedback>{{ errors.subject_id?.[0] }}</BFormInvalidFeedback>
            <div v-if="loadingSubjects" class="small text-muted mt-1">
              <BSpinner small /> Chargement des matières...
            </div>
          </div>
        </BCol>

        <!-- Day Selection -->
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Jour *</label>
            <BFormSelect
              v-model="form.day_of_week"
              :options="dayOptions"
              :state="getFieldState('day_of_week')"
              @change="onScheduleChange"
              required
            />
            <BFormInvalidFeedback>{{ errors.day_of_week?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Time Slot Selection -->
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Créneau horaire *</label>
            <BFormSelect
              v-model="form.time_slot_id"
              :options="timeSlotOptions"
              :state="getFieldState('time_slot_id')"
              @change="onScheduleChange"
              required
            />
            <BFormInvalidFeedback>{{ errors.time_slot_id?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Teacher Selection (filtered by subject and availability) -->
        <BCol md="12">
          <div class="mb-3">
            <label class="form-label">Enseignant *</label>
            <BFormSelect
              v-model="form.teacher_id"
              :options="teacherOptions"
              :state="getFieldState('teacher_id')"
              :disabled="!canLoadTeachers || loadingTeachers"
              required
            />
            <BFormInvalidFeedback>{{ errors.teacher_id?.[0] }}</BFormInvalidFeedback>
            <div v-if="loadingTeachers" class="small text-muted mt-1">
              <BSpinner small /> Chargement des enseignants disponibles...
            </div>
            <div v-if="availableTeachers.length === 0 && !loadingTeachers && canLoadTeachers" class="small text-warning mt-1">
              <i class="ri-warning-line"></i> Aucun enseignant disponible pour ce créneau et cette matière
            </div>
          </div>
        </BCol>

        <!-- Active Status -->
        <BCol md="6">
          <div class="mb-3">
            <BFormCheckbox v-model="form.is_active">
              <strong>Entrée active</strong>
            </BFormCheckbox>
          </div>
        </BCol>

        <!-- Conflict Warning -->
        <BCol md="12" v-if="conflictMessage">
          <BAlert variant="warning" show>
            <i class="ri-warning-line me-1"></i>
            {{ conflictMessage }}
          </BAlert>
        </BCol>
      </BRow>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <BButton variant="light" @click="localShow = false">
          Annuler
        </BButton>
        <BButton 
          type="submit" 
          variant="primary"
          :disabled="loading || !!conflictMessage"
        >
          <BSpinner v-if="loading" small class="me-1" />
          {{ isEditing ? 'Modifier' : 'Ajouter' }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from '@/helpers/toast'
import axios from 'axios'

// Props
interface Props {
  show: boolean
  timetableId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  timetableId: null
})

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'saved'): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const loadingSubjects = ref(false)
const loadingTeachers = ref(false)
const classes = ref([])
const subjects = ref([])
const availableTeachers = ref([])
const timeSlots = ref([])
const days = ref({})
const conflictMessage = ref('')

const form = reactive({
  class_id: null,
  subject_id: null,
  teacher_id: null,
  time_slot_id: null,
  day_of_week: null,
  is_active: true
})

const errors = reactive({})

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const isEditing = computed(() => props.timetableId !== null)

const classOptions = computed(() => [
  { value: null, text: 'Sélectionner une classe' },
  ...classes.value.map(cls => ({ value: cls.id, text: `${cls.name} - ${cls.level?.name}` }))
])

const subjectOptions = computed(() => [
  { value: null, text: 'Sélectionner une matière' },
  ...subjects.value.map(subject => ({ value: subject.id, text: subject.name }))
])

const teacherOptions = computed(() => [
  { value: null, text: 'Sélectionner un enseignant' },
  ...availableTeachers.value.map(teacher => ({ value: teacher.id, text: teacher.full_name }))
])

const timeSlotOptions = computed(() => [
  { value: null, text: 'Sélectionner un créneau' },
  ...timeSlots.value.map(slot => ({ value: slot.id, text: slot.name }))
])

const dayOptions = computed(() => [
  { value: null, text: 'Sélectionner un jour' },
  ...Object.entries(days.value).map(([key, value]) => ({ value: parseInt(key), text: value }))
])

const canLoadTeachers = computed(() => {
  return form.subject_id && form.day_of_week && form.time_slot_id
})

// Methods
const fetchFormData = async () => {
  try {
    const response = await axios.get('/api/timetables/create')
    classes.value = response.data.classes || []
    timeSlots.value = response.data.timeSlots || []
    days.value = response.data.days || {}
  } catch (error) {
    useToast().error('Erreur lors du chargement des données')
    console.error('Error fetching form data:', error)
  }
}

const fetchClassSubjects = async (classId) => {
  if (!classId) {
    subjects.value = []
    return
  }
  
  loadingSubjects.value = true
  try {
    const response = await axios.get(`/api/timetables/class/${classId}/subjects`)
    subjects.value = response.data.subjects || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des matières')
    console.error('Error fetching class subjects:', error)
    subjects.value = []
  } finally {
    loadingSubjects.value = false
  }
}

const fetchAvailableTeachers = async () => {
  if (!canLoadTeachers.value) {
    availableTeachers.value = []
    return
  }
  
  loadingTeachers.value = true
  try {
    const params = {
      subject_id: form.subject_id,
      time_slot_id: form.time_slot_id,
      day_of_week: form.day_of_week
    }
    
    if (isEditing.value) {
      params.exclude_timetable_id = props.timetableId
    }
    
    const response = await axios.get('/api/timetables/available-teachers', { params })
    availableTeachers.value = response.data.teachers || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des enseignants')
    console.error('Error fetching available teachers:', error)
    availableTeachers.value = []
  } finally {
    loadingTeachers.value = false
  }
}

const checkConflicts = async () => {
  conflictMessage.value = ''
  
  if (!form.teacher_id || !form.class_id || !form.time_slot_id || !form.day_of_week) {
    return
  }
  
  try {
    const params = {
      teacher_id: form.teacher_id,
      class_id: form.class_id,
      time_slot_id: form.time_slot_id,
      day_of_week: form.day_of_week
    }
    
    if (isEditing.value) {
      params.exclude_timetable_id = props.timetableId
    }
    
    const response = await axios.post('/api/timetables/check-conflicts', params)
    
    if (response.data.has_conflict) {
      conflictMessage.value = 'Conflit détecté : L\'enseignant ou la classe est déjà assigné(e) à ce créneau.'
    }
  } catch (error) {
    console.error('Error checking conflicts:', error)
  }
}

const fetchTimetable = async () => {
  if (!props.timetableId) return
  
  try {
    const response = await axios.get(`/api/timetables/${props.timetableId}/edit`)
    const timetable = response.data.timetable
    
    // Update form data
    classes.value = response.data.classes || classes.value
    timeSlots.value = response.data.timeSlots || timeSlots.value
    days.value = response.data.days || days.value
    
    // Load class subjects
    await fetchClassSubjects(timetable.class_id)
    
    // Fill form
    Object.keys(form).forEach(key => {
      form[key] = timetable[key] || form[key]
    })
    
    // Load available teachers after form is filled
    await fetchAvailableTeachers()
  } catch (error) {
    useToast().error('Erreur lors du chargement de l\'entrée')
    console.error('Error fetching timetable:', error)
  }
}

const onClassChange = async () => {
  // Reset dependent fields
  form.subject_id = null
  form.teacher_id = null
  subjects.value = []
  availableTeachers.value = []
  conflictMessage.value = ''
  
  // Fetch subjects for the selected class
  if (form.class_id) {
    await fetchClassSubjects(form.class_id)
  }
}

const onSubjectChange = async () => {
  // Reset teacher selection
  form.teacher_id = null
  availableTeachers.value = []
  conflictMessage.value = ''
  
  // Fetch available teachers if schedule is selected
  if (canLoadTeachers.value) {
    await fetchAvailableTeachers()
  }
}

const onScheduleChange = async () => {
  // Reset teacher selection
  form.teacher_id = null
  availableTeachers.value = []
  conflictMessage.value = ''
  
  // Fetch available teachers
  if (canLoadTeachers.value) {
    await fetchAvailableTeachers()
  }
}

// Watch for teacher selection to check conflicts
watch(() => form.teacher_id, () => {
  if (form.teacher_id) {
    checkConflicts()
  } else {
    conflictMessage.value = ''
  }
})

const handleSubmit = async () => {
  loading.value = true
  Object.keys(errors).forEach(key => delete errors[key])
  conflictMessage.value = ''
  
  try {
    let response
    if (isEditing.value) {
      response = await axios.put(`/api/timetables/${props.timetableId}`, form)
    } else {
      response = await axios.post('/api/timetables', form)
    }

    useToast().success(
      isEditing.value 
        ? 'Entrée modifiée avec succès' 
        : 'Entrée ajoutée avec succès'
    )
    
    emit('saved')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
      if (error.response.data.message) {
        conflictMessage.value = error.response.data.message
      }
    } else {
      useToast().error('Erreur lors de l\'enregistrement')
    }
    console.error('Error saving timetable:', error)
  } finally {
    loading.value = false
  }
}

const getFieldState = (field: string) => {
  return errors[field] ? false : null
}

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'is_active') {
      form[key] = true
    } else {
      form[key] = null
    }
  })
  Object.keys(errors).forEach(key => delete errors[key])
  subjects.value = []
  availableTeachers.value = []
  conflictMessage.value = ''
}

// Watchers
watch(() => props.show, (show) => {
  if (show) {
    if (isEditing.value) {
      fetchTimetable()
    } else {
      resetForm()
    }
  }
})

// Lifecycle
onMounted(() => {
  fetchFormData()
})
</script>

<style scoped>
.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 1rem;
  margin-top: 1rem;
}
</style>