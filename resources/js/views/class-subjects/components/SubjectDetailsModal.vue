<template>
  <BModal
    v-model="localShow"
    title="Modifier les Détails de la Matière"
    size="md"
    hide-footer
    @hidden="resetForm"
  >
    <BForm @submit.prevent="handleSubmit" v-if="subject">
      <!-- Subject Info -->
      <div class="mb-3 p-3 bg-light rounded">
        <h6 class="mb-1">{{ subject.name }}</h6>
        <small class="text-muted">{{ subject.code }}</small>
      </div>

      <!-- Hours per week -->
      <div class="mb-3">
        <label class="form-label">Heures par semaine *</label>
        <BFormInput
          v-model.number="form.hours_per_week"
          type="number"
          min="1"
          max="20"
          :state="getFieldState('hours_per_week')"
          required
        />
        <BFormInvalidFeedback>{{ errors.hours_per_week?.[0] }}</BFormInvalidFeedback>
        <div class="form-text">Nombre d'heures allouées à cette matière par semaine</div>
      </div>

      <!-- Active Status -->
      <div class="mb-3">
        <BFormCheckbox v-model="form.is_active">
          <strong>Matière active</strong>
        </BFormCheckbox>
        <div class="form-text">Les matières inactives n'apparaîtront pas dans la création d'emploi du temps</div>
      </div>

      <!-- Form Validation Errors -->
      <div v-if="Object.keys(errors).length > 0" class="mt-3">
        <BAlert variant="danger" show>
          <ul class="mb-0">
            <li v-for="(messages, field) in errors" :key="field">
              {{ messages[0] }}
            </li>
          </ul>
        </BAlert>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <BButton variant="light" @click="localShow = false">
          Annuler
        </BButton>
        <BButton 
          type="submit" 
          variant="primary"
          :disabled="loading"
        >
          <BSpinner v-if="loading" small class="me-1" />
          Enregistrer
        </BButton>
      </div>
    </BForm>
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
  subject?: any
}

const props = withDefaults(defineProps<Props>(), {
  classId: null,
  subject: null
})

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'saved'): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)

const form = reactive({
  hours_per_week: null,
  is_active: true
})

const errors = reactive({})

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

// Methods
const loadSubjectData = () => {
  if (props.subject) {
    form.hours_per_week = props.subject.pivot?.hours_per_week || null
    form.is_active = props.subject.pivot?.is_active ?? true
  }
}

const handleSubmit = async () => {
  loading.value = true
  Object.keys(errors).forEach(key => delete errors[key])
  
  try {
    await axios.put('/api/class-subjects/update-details', {
      class_id: props.classId,
      subject_id: props.subject.id,
      hours_per_week: form.hours_per_week,
      is_active: form.is_active
    })
    
    useToast().success('Détails de la matière mis à jour avec succès')
    emit('saved')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
    } else {
      useToast().error('Erreur lors de la mise à jour')
    }
    console.error('Error updating subject details:', error)
  } finally {
    loading.value = false
  }
}

const getFieldState = (field: string) => {
  return errors[field] ? false : null
}

const resetForm = () => {
  form.hours_per_week = null
  form.is_active = true
  Object.keys(errors).forEach(key => delete errors[key])
}

// Watchers
watch(() => props.show, (show) => {
  if (show && props.subject) {
    loadSubjectData()
  }
})

watch(() => props.subject, (newSubject) => {
  if (newSubject && props.show) {
    loadSubjectData()
  }
})
</script>

<style scoped>
.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 1rem;
  margin-top: 1rem;
}
</style>