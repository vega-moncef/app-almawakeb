<template>
  <BModal
    v-model="localShow"
    :title="isEditing ? 'Modifier le Créneau Horaire' : 'Ajouter un Créneau Horaire'"
    size="lg"
    no-close-on-backdrop
    @hide="resetForm"
  >
    <form @submit.prevent="handleSubmit">
      <BRow>
        <BCol md="6">
          <div class="mb-3">
            <label for="name" class="form-label">
              Nom du Créneau <span class="text-danger">*</span>
            </label>
            <BFormInput
              id="name"
              v-model="form.name"
              type="text"
              placeholder="ex: 8h30-9h25"
              :class="{ 'is-invalid': errors.name }"
              required
            />
            <div v-if="errors.name" class="invalid-feedback">
              {{ errors.name }}
            </div>
          </div>
        </BCol>
        
        <BCol md="3">
          <div class="mb-3">
            <label for="start_time" class="form-label">
              Heure de Début <span class="text-danger">*</span>
            </label>
            <BFormInput
              id="start_time"
              v-model="form.start_time"
              type="time"
              :class="{ 'is-invalid': errors.start_time }"
              required
            />
            <div v-if="errors.start_time" class="invalid-feedback">
              {{ errors.start_time }}
            </div>
          </div>
        </BCol>
        
        <BCol md="3">
          <div class="mb-3">
            <label for="end_time" class="form-label">
              Heure de Fin <span class="text-danger">*</span>
            </label>
            <BFormInput
              id="end_time"
              v-model="form.end_time"
              type="time"
              :class="{ 'is-invalid': errors.end_time }"
              required
            />
            <div v-if="errors.end_time" class="invalid-feedback">
              {{ errors.end_time }}
            </div>
          </div>
        </BCol>
      </BRow>

      <BRow>
        <BCol md="4">
          <div class="mb-3">
            <label for="order" class="form-label">
              Ordre <span class="text-danger">*</span>
            </label>
            <BFormInput
              id="order"
              v-model="form.order"
              type="number"
              min="1"
              placeholder="1, 2, 3..."
              :class="{ 'is-invalid': errors.order }"
            />
            <div v-if="errors.order" class="invalid-feedback">
              {{ errors.order }}
            </div>
            <small class="text-muted">Ordre d'affichage dans les emplois du temps</small>
          </div>
        </BCol>

        <BCol md="4">
          <div class="mb-3">
            <label class="form-label">Type de Créneau</label>
            <div class="mt-2">
              <BFormRadioGroup
                v-model="form.is_break"
                :options="typeOptions"
                buttons
                button-variant="outline-primary"
                class="w-100"
              />
            </div>
          </div>
        </BCol>

        <BCol md="4">
          <div class="mb-3">
            <label class="form-label">Statut</label>
            <div class="mt-2">
              <BFormCheckbox
                v-model="form.is_active"
                switch
                size="lg"
              >
                {{ form.is_active ? 'Actif' : 'Inactif' }}
              </BFormCheckbox>
            </div>
            <small class="text-muted">Les créneaux inactifs n'apparaissent pas dans les emplois du temps</small>
          </div>
        </BCol>
      </BRow>

      <!-- Break Type Selection (only if is_break is true) -->
      <BRow v-if="form.is_break">
        <BCol md="12">
          <div class="mb-3">
            <label for="break_type" class="form-label">
              Type de Pause
            </label>
            <BFormSelect
              id="break_type"
              v-model="form.break_type"
              :options="breakTypeOptions"
            />
            <small class="text-muted">Sélectionnez le type de pause pour un meilleur affichage</small>
          </div>
        </BCol>
      </BRow>

      <!-- Scope Selection -->
      <BRow>
        <BCol md="12">
          <div class="mb-3">
            <label class="form-label">
              <i class="ri-focus-3-line me-1"></i>
              Portée du Créneau
            </label>
            <div class="mt-2">
              <BFormRadioGroup
                v-model="scopeType"
                :options="scopeOptions"
                buttons
                button-variant="outline-secondary"
                class="w-100"
                @change="onScopeTypeChange"
              />
            </div>
            <small class="text-muted">Définissez si ce créneau s'applique globalement ou à un contexte spécifique</small>
          </div>
        </BCol>
      </BRow>

      <!-- Specific Scope Selection -->
      <BRow v-if="scopeType !== 'global'">
        <BCol v-if="scopeType === 'school'" md="12">
          <div class="mb-3">
            <label for="school_id" class="form-label">
              École <span class="text-danger">*</span>
            </label>
            <BFormSelect
              id="school_id"
              v-model="form.school_id"
              :options="schoolOptions"
              :class="{ 'is-invalid': errors.school_id }"
              required
            />
            <div v-if="errors.school_id" class="invalid-feedback">
              {{ errors.school_id }}
            </div>
          </div>
        </BCol>

        <BCol v-if="scopeType === 'level'" md="12">
          <div class="mb-3">
            <label for="level_id" class="form-label">
              Niveau <span class="text-danger">*</span>
            </label>
            <BFormSelect
              id="level_id"
              v-model="form.level_id"
              :options="levelOptions"
              :class="{ 'is-invalid': errors.level_id }"
              required
            />
            <div v-if="errors.level_id" class="invalid-feedback">
              {{ errors.level_id }}
            </div>
          </div>
        </BCol>

        <BCol v-if="scopeType === 'class'" md="12">
          <div class="mb-3">
            <label for="class_id" class="form-label">
              Classe <span class="text-danger">*</span>
            </label>
            <BFormSelect
              id="class_id"
              v-model="form.class_id"
              :options="classOptions"
              :class="{ 'is-invalid': errors.class_id }"
              required
            />
            <div v-if="errors.class_id" class="invalid-feedback">
              {{ errors.class_id }}
            </div>
          </div>
        </BCol>
      </BRow>

      <!-- Duration Display -->
      <BRow v-if="computedDuration">
        <BCol md="12">
          <BAlert variant="info" class="mb-3">
            <i class="ri-time-line me-2"></i>
            <strong>Durée calculée:</strong> {{ computedDuration }} minutes
            <span v-if="form.is_break" class="ms-2">
              <i class="ri-coffee-line"></i> Pause
            </span>
          </BAlert>
        </BCol>
      </BRow>
    </form>

    <template #footer>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="secondary" @click="localShow = false">
          Annuler
        </BButton>
        <BButton 
          variant="primary" 
          @click="handleSubmit"
          :disabled="loading"
        >
          <BSpinner v-if="loading" small class="me-1" />
          {{ isEditing ? 'Mettre à jour' : 'Créer' }}
        </BButton>
      </div>
    </template>
  </BModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from '@/helpers/toast'
import axios from 'axios'

// Props
interface Props {
  show: boolean
  timeSlot?: any
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  timeSlot: null
})

// Emits
const emit = defineEmits(['update:show', 'saved'])

// State
const loading = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  start_time: '',
  end_time: '',
  order: null,
  is_active: true,
  is_break: false,
  break_type: 'recreation',
  school_id: null,
  level_id: null,
  class_id: null
})

// Additional state for scope management
const scopeType = ref('global')
const schools = ref([])
const levels = ref([])
const classes = ref([])

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const isEditing = computed(() => !!props.timeSlot)

const typeOptions = [
  { text: 'Cours', value: false },
  { text: 'Pause', value: true }
]

const breakTypeOptions = [
  { value: 'recreation', text: 'Récréation' },
  { value: 'lunch', text: 'Pause déjeuner' },
  { value: 'morning_break', text: 'Pause matinale' },
  { value: 'afternoon_break', text: 'Pause après-midi' }
]

const scopeOptions = [
  { text: 'Global', value: 'global' },
  { text: 'École', value: 'school' },
  { text: 'Niveau', value: 'level' },
  { text: 'Classe', value: 'class' }
]

const schoolOptions = computed(() => [
  { value: null, text: 'Sélectionnez une école' },
  ...(Array.isArray(schools.value) ? schools.value.map(school => ({
    value: school.id,
    text: school.name
  })) : [])
])

const levelOptions = computed(() => [
  { value: null, text: 'Sélectionnez un niveau' },
  ...(Array.isArray(levels.value) ? levels.value.map(level => ({
    value: level.id,
    text: level.name
  })) : [])
])

const classOptions = computed(() => [
  { value: null, text: 'Sélectionnez une classe' },
  ...(Array.isArray(classes.value) ? classes.value.map(cls => ({
    value: cls.id,
    text: cls.name
  })) : [])
])

const computedDuration = computed(() => {
  if (form.start_time && form.end_time) {
    const start = new Date(`1970-01-01T${form.start_time}:00`)
    const end = new Date(`1970-01-01T${form.end_time}:00`)
    
    if (end > start) {
      return Math.round((end.getTime() - start.getTime()) / (1000 * 60))
    }
  }
  return null
})

// Watchers
watch(() => props.timeSlot, (newTimeSlot) => {
  if (newTimeSlot) {
    populateForm(newTimeSlot)
  }
}, { immediate: true })

watch(() => [form.start_time, form.end_time], () => {
  // Auto-generate name if both times are set
  if (form.start_time && form.end_time && !isEditing.value) {
    const startFormatted = form.start_time.replace(':', 'h')
    const endFormatted = form.end_time.replace(':', 'h')
    form.name = `${startFormatted}-${endFormatted}`
  }
}, { deep: true })

// Methods
const populateForm = (timeSlot: any) => {
  form.name = timeSlot.name || ''
  form.start_time = timeSlot.start_time || ''
  form.end_time = timeSlot.end_time || ''
  form.order = timeSlot.order || null
  form.is_active = timeSlot.is_active ?? true
  form.is_break = timeSlot.is_break || false
  form.break_type = timeSlot.break_type || 'recreation'
  form.school_id = timeSlot.school_id || null
  form.level_id = timeSlot.level_id || null
  form.class_id = timeSlot.class_id || null

  // Determine scope type based on filled fields
  if (timeSlot.class_id) {
    scopeType.value = 'class'
  } else if (timeSlot.level_id) {
    scopeType.value = 'level'
  } else if (timeSlot.school_id) {
    scopeType.value = 'school'
  } else {
    scopeType.value = 'global'
  }
}

const resetForm = () => {
  form.name = ''
  form.start_time = ''
  form.end_time = ''
  form.order = null
  form.is_active = true
  form.is_break = false
  form.break_type = 'recreation'
  form.school_id = null
  form.level_id = null
  form.class_id = null
  scopeType.value = 'global'
  errors.value = {}
}

const onScopeTypeChange = () => {
  // Clear scope-related fields when scope type changes
  form.school_id = null
  form.level_id = null
  form.class_id = null
}

// Load dropdown data
const fetchSchools = async () => {
  try {
    const response = await axios.get('/api/schools')
    schools.value = Array.isArray(response.data.data) ? response.data.data : []
  } catch (error) {
    console.error('Error fetching schools:', error)
    schools.value = []
  }
}

const fetchLevels = async () => {
  try {
    const response = await axios.get('/api/levels')
    levels.value = Array.isArray(response.data.data) ? response.data.data : []
  } catch (error) {
    console.error('Error fetching levels:', error)
    levels.value = []
  }
}

const fetchClasses = async () => {
  try {
    const response = await axios.get('/api/classes')
    classes.value = Array.isArray(response.data.data) ? response.data.data : []
  } catch (error) {
    console.error('Error fetching classes:', error)
    classes.value = []
  }
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const url = isEditing.value 
      ? `/api/time-slots/${props.timeSlot.id}`
      : '/api/time-slots'
    
    const method = isEditing.value ? 'put' : 'post'
    
    await axios[method](url, form)
    
    useToast().success(
      isEditing.value 
        ? 'Créneau horaire modifié avec succès' 
        : 'Créneau horaire créé avec succès'
    )
    
    emit('saved')
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      if (error.response.data.message) {
        useToast().error(error.response.data.message)
      }
    } else {
      useToast().error('Erreur lors de la sauvegarde')
    }
    console.error('Error saving time slot:', error)
  } finally {
    loading.value = false
  }
}

// Load dropdown data on component mount
onMounted(() => {
  fetchSchools()
  fetchLevels()
  fetchClasses()
})
</script>

<style scoped>
.form-label {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.btn-group-toggle .btn {
  flex: 1;
}
</style>