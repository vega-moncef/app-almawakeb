<template>
  <BModal
    v-model="localShow"
    title="Assigner des Matières à la Classe"
    size="lg"
    hide-footer
    @hidden="resetForm"
  >
    <div v-if="loading" class="text-center my-4">
      <BSpinner class="align-middle"></BSpinner>
      <p class="mt-2">Chargement des matières disponibles...</p>
    </div>

    <BForm @submit.prevent="handleSubmit" v-else>
      <!-- Available Subjects -->
      <div class="mb-3">
        <label class="form-label">Matières disponibles</label>
        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
          <div v-if="availableSubjects.length === 0" class="text-center text-muted">
            <p>Toutes les matières sont déjà assignées à cette classe.</p>
          </div>
          <div v-else>
            <BRow>
              <BCol 
                v-for="subject in availableSubjects" 
                :key="subject.unique_id"
                md="6"
                class="mb-2"
              >
                <BFormCheckbox
                  :value="subject.unique_id"
                  v-model="selectedSubjects"
                  @change="onSubjectToggle(subject.unique_id)"
                >
                  <strong>{{ subject.display_name }}</strong>
                  <br>
                  <small class="text-muted">{{ subject.code }}</small>
                </BFormCheckbox>
              </BCol>
            </BRow>
          </div>
        </div>
      </div>

      <!-- Selected Subjects Configuration -->
      <div v-if="selectedSubjects.length > 0" class="mt-4">
        <h6>Configuration des matières sélectionnées ({{ selectedSubjects.length }})</h6>
        <div class="border rounded p-3">
          <div 
            v-for="subjectUniqueId in selectedSubjects" 
            :key="`config-${subjectUniqueId}`"
            class="mb-3 p-3 bg-light rounded"
          >
            <div class="row align-items-center">
              <div class="col-md-4">
                <strong>{{ getSubjectByUniqueId(subjectUniqueId)?.display_name }}</strong>
              </div>
              <div class="col-md-4">
                <label class="form-label mb-1">Heures/semaine</label>
                <BFormInput
                  :value="getSubjectConfig(subjectUniqueId, 'hours_per_week')"
                  @input="updateSubjectConfig(subjectUniqueId, 'hours_per_week', $event.target.value)"
                  type="number"
                  min="1"
                  max="20"
                  placeholder="Ex: 4"
                />
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                  <BFormCheckbox
                    :model-value="getSubjectConfig(subjectUniqueId, 'is_active')"
                    @update:model-value="updateSubjectConfig(subjectUniqueId, 'is_active', $event)"
                    switch
                  >
                    Actif
                  </BFormCheckbox>
                </div>
              </div>
            </div>
          </div>
        </div>
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
          :disabled="submitting || selectedSubjects.length === 0"
        >
          <BSpinner v-if="submitting" small class="me-1" />
          Assigner {{ selectedSubjects.length }} matière(s)
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick } from 'vue'
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
  (e: 'saved'): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const submitting = ref(false)
const availableSubjects = ref([])
const selectedSubjects = ref([])
const subjectConfigs = reactive({})
const errors = reactive({})

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

// Ensure all selected subjects have configurations
const ensureSubjectConfigurations = () => {
  selectedSubjects.value.forEach(uniqueId => {
    if (!subjectConfigs[uniqueId]) {
      subjectConfigs[uniqueId] = {
        hours_per_week: 4,
        is_active: true
      }
    }
  })
}

// Methods
const fetchAvailableSubjects = async () => {
  if (!props.classId) return
  
  loading.value = true
  try {
    const response = await axios.get('/api/subjects/available', {
      params: { exclude_class_id: props.classId }
    })
    
    availableSubjects.value = response.data.subjects || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des matières disponibles')
    console.error('Error fetching available subjects:', error)
  } finally {
    loading.value = false
  }
}

const getSubjectById = (id: number) => {
  return availableSubjects.value.find(s => s.id === id)
}

const getSubjectByUniqueId = (uniqueId: string) => {
  return availableSubjects.value.find(s => s.unique_id === uniqueId)
}

const getSubjectConfig = (uniqueId: string, field: string) => {
  if (!subjectConfigs[uniqueId]) {
    subjectConfigs[uniqueId] = {
      hours_per_week: 4,
      is_active: true
    }
  }
  return subjectConfigs[uniqueId][field]
}

const updateSubjectConfig = (uniqueId: string, field: string, value: any) => {
  if (!subjectConfigs[uniqueId]) {
    subjectConfigs[uniqueId] = {
      hours_per_week: 4,
      is_active: true
    }
  }
  
  // Convert to number for hours_per_week field
  if (field === 'hours_per_week') {
    value = Number(value) || 0
  }
  
  subjectConfigs[uniqueId][field] = value
}

const onSubjectToggle = async (uniqueId: string) => {
  // Wait for the next tick to ensure v-model has updated selectedSubjects
  await nextTick()
  
  // Ensure all selected subjects have configurations
  ensureSubjectConfigurations()
  
  // Clean up configs for unselected subjects
  Object.keys(subjectConfigs).forEach(key => {
    if (!selectedSubjects.value.includes(key)) {
      delete subjectConfigs[key]
    }
  })
}

const handleSubmit = async () => {
  submitting.value = true
  Object.keys(errors).forEach(key => delete errors[key])
  
  try {
    const subjects = selectedSubjects.value.map(uniqueId => {
      const subject = getSubjectByUniqueId(uniqueId)
      return {
        id: subject?.id,
        hours_per_week: subjectConfigs[uniqueId]?.hours_per_week || null,
        is_active: subjectConfigs[uniqueId]?.is_active ?? true
      }
    })
    
    await axios.put(`/api/class-subjects/${props.classId}/subjects`, {
      subjects
    })
    
    useToast().success(`${selectedSubjects.value.length} matière(s) assignée(s) avec succès`)
    emit('saved')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
    } else {
      useToast().error('Erreur lors de l\'assignation')
    }
    console.error('Error assigning subjects:', error)
  } finally {
    submitting.value = false
  }
}

const resetForm = () => {
  selectedSubjects.value = []
  Object.keys(subjectConfigs).forEach(key => delete subjectConfigs[key])
  Object.keys(errors).forEach(key => delete errors[key])
  availableSubjects.value = []
}

// Watchers
watch(() => props.show, (show) => {
  if (show && props.classId) {
    fetchAvailableSubjects()
  }
})
</script>

<style scoped>
.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 1rem;
  margin-top: 1rem;
}

.form-check-input:checked {
  background-color: #28a745;
  border-color: #28a745;
}
</style>