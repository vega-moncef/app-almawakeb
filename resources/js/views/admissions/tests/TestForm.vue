<template>
<VerticalLayout>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">{{ isEditMode ? 'Modifier Test' : 'Nouveau Test' }}</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <!-- Test Basic Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Informations Générales</h5>
                  <hr>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label class="form-label">Titre du test *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.title"
                    :class="{ 'is-invalid': errors.title }"
                    placeholder="Ex: Test d'admission 1ère année"
                    required
                  >
                  <div v-if="errors.title" class="invalid-feedback">
                    {{ errors.title[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Niveau *</label>
                  <select 
                    class="form-select" 
                    v-model="form.level_id"
                    :class="{ 'is-invalid': errors.level_id }"
                    required
                  >
                    <option value="">Sélectionner un niveau</option>
                    <option 
                      v-for="level in levels" 
                      :key="level.id" 
                      :value="level.id"
                    >
                      {{ level.school ? level.school.name + ' - ' : '' }}{{ level.name }}
                    </option>
                  </select>
                  <div v-if="errors.level_id" class="invalid-feedback">
                    {{ errors.level_id[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Type de test *</label>
                  <select 
                    class="form-select" 
                    v-model="form.type"
                    :class="{ 'is-invalid': errors.type }"
                    required
                  >
                    <option value="">Sélectionner le type</option>
                    <option value="ORAL">Oral</option>
                    <option value="ECRIT">Écrit</option>
                  </select>
                  <div v-if="errors.type" class="invalid-feedback">
                    {{ errors.type[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Date du test *</label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="form.test_date"
                    :class="{ 'is-invalid': errors.test_date }"
                    required
                  >
                  <div v-if="errors.test_date" class="invalid-feedback">
                    {{ errors.test_date[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Durée (minutes) *</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="form.duration_minutes"
                    :class="{ 'is-invalid': errors.duration_minutes }"
                    min="1"
                    placeholder="120"
                    required
                  >
                  <div v-if="errors.duration_minutes" class="invalid-feedback">
                    {{ errors.duration_minutes[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Heure de début *</label>
                  <input 
                    type="time" 
                    class="form-control" 
                    v-model="form.start_time"
                    :class="{ 'is-invalid': errors.start_time }"
                    required
                  >
                  <div v-if="errors.start_time" class="invalid-feedback">
                    {{ errors.start_time[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Heure de fin *</label>
                  <input 
                    type="time" 
                    class="form-control" 
                    v-model="form.end_time"
                    :class="{ 'is-invalid': errors.end_time }"
                    required
                  >
                  <div v-if="errors.end_time" class="invalid-feedback">
                    {{ errors.end_time[0] }}
                  </div>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control" 
                    rows="3" 
                    v-model="form.description"
                    placeholder="Description du test..."
                  ></textarea>
                </div>
              </div>

              <!-- Test Scoring -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Barème</h5>
                  <hr>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label class="form-label">Note totale *</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="form.total_marks"
                    :class="{ 'is-invalid': errors.total_marks }"
                    min="0"
                    step="0.5"
                    placeholder="20"
                    required
                  >
                  <div v-if="errors.total_marks" class="invalid-feedback">
                    {{ errors.total_marks[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Note de passage *</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="form.passing_marks"
                    :class="{ 'is-invalid': errors.passing_marks }"
                    min="0"
                    step="0.5"
                    placeholder="10"
                    :max="form.total_marks"
                    required
                  >
                  <div v-if="errors.passing_marks" class="invalid-feedback">
                    {{ errors.passing_marks[0] }}
                  </div>
                </div>
              </div>

              <!-- Test File Upload -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Fichier du Test</h5>
                  <hr>
                </div>
                
                <div class="col-12 mb-3">
                  <label class="form-label">Document du test</label>
                  <input 
                    type="file" 
                    class="form-control" 
                    ref="fileInput"
                    @change="handleFileUpload"
                    accept=".pdf,.doc,.docx"
                  >
                  <div class="form-text">
                    Formats acceptés: PDF, DOC, DOCX (Max: 10MB)
                  </div>
                  <div v-if="form.test_file && typeof form.test_file === 'string'" class="mt-2">
                    <small class="text-success">
                      <i class="fas fa-file"></i> Fichier actuel: {{ getFileName(form.test_file) }}
                    </small>
                  </div>
                  <div v-if="errors.test_file" class="invalid-feedback d-block">
                    {{ errors.test_file[0] }}
                  </div>
                </div>
              </div>

              <!-- Subjects Selection -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Matières du Test</h5>
                  <hr>
                </div>
                
                <div class="col-12">
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="table-light">
                        <tr>
                          <th>Matière</th>
                          <th style="width: 150px;">Note (/{{ form.total_marks || 20 }})</th>
                          <th style="width: 80px;">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(subject, index) in form.subjects" :key="index">
                          <td>
                            <select 
                              class="form-select" 
                              v-model="subject.id"
                              :class="{ 'is-invalid': errors[`subjects.${index}.id`] }"
                              required
                            >
                              <option value="">Sélectionner une matière</option>
                              <option 
                                v-for="availableSubject in availableSubjects(index)" 
                                :key="availableSubject.id" 
                                :value="availableSubject.id"
                              >
                                {{ availableSubject.name }}
                              </option>
                            </select>
                          </td>
                          <td>
                            <input 
                              type="number" 
                              class="form-control" 
                              v-model.number="subject.marks"
                              :class="{ 'is-invalid': errors[`subjects.${index}.marks`] }"
                              min="0"
                              step="0.5"
                              :max="form.total_marks"
                              placeholder="0"
                              required
                            >
                          </td>
                          <td>
                            <button 
                              type="button" 
                              class="btn btn-danger btn-sm"
                              @click="removeSubject(index)"
                              :disabled="form.subjects.length === 1"
                            >
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  
                  <button 
                    type="button" 
                    class="btn btn-outline-primary btn-sm"
                    @click="addSubject"
                    :disabled="form.subjects.length >= availableSubjectsCount"
                  >
                    <i class="fas fa-plus"></i> Ajouter une matière
                  </button>
                  
                  <div v-if="parseFloat(totalSubjectMarks) !== parseFloat(form.total_marks)" class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    La somme des notes des matières ({{ totalSubjectMarks }}) 
                    ne correspond pas à la note totale ({{ form.total_marks }}).
                  </div>
                  
                  <div v-if="errors.subjects" class="invalid-feedback d-block">
                    {{ errors.subjects[0] }}
                  </div>
                </div>
              </div>

              <!-- Instructions -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Instructions</h5>
                  <hr>
                </div>
                
                <div class="col-12 mb-3">
                  <label class="form-label">Instructions pour les candidats</label>
                  <textarea 
                    class="form-control" 
                    rows="4" 
                    v-model="form.instructions"
                    placeholder="Instructions et consignes pour les candidats..."
                  ></textarea>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="row">
                <div class="col-12">
                  <hr>
                  <div class="d-flex justify-content-between">
                    <button 
                      type="button" 
                      class="btn btn-secondary"
                      @click="goBack"
                    >
                      <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    
                    <button 
                      type="submit" 
                      class="btn btn-primary"
                      :disabled="loading || !isFormValid"
                    >
                      <i class="fas fa-save"></i>
                      <span v-if="loading">Enregistrement...</span>
                      <span v-else>{{ isEditMode ? 'Mettre à jour' : 'Créer le Test' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</VerticalLayout>
</template>

<script setup>
import VerticalLayout from "@/layouts/VerticalLayout.vue";
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

// Router and route
const route = useRoute()
const router = useRouter()

// Form state
const loading = ref(false)
const levels = ref([])
const subjects = ref([])
const errors = ref({})
const fileInput = ref(null)

// Edit mode detection
const testId = computed(() => route.params.id)
const isEditMode = computed(() => !!testId.value)

const form = reactive({
  title: '',
  description: '',
  level_id: '',
  type: '',
  test_date: '',
  start_time: '08:00',
  end_time: '10:00',
  duration_minutes: 120,
  total_marks: 20,
  passing_marks: 10,
  test_file: null,
  instructions: '',
  subjects: [
    { id: '', marks: 0 }
  ]
})

// Computed properties
const availableSubjectsCount = computed(() => subjects.value.length)

const availableSubjects = (currentIndex) => {
  const selectedIds = form.subjects
    .map((s, index) => index !== currentIndex ? s.id : null)
    .filter(id => id)
  
  return subjects.value.filter(subject => !selectedIds.includes(subject.id))
}

const totalSubjectMarks = computed(() => {
  return form.subjects.reduce((total, subject) => {
    const marks = parseFloat(subject.marks) || 0
    return total + marks
  }, 0)
})

const isFormValid = computed(() => {
  return form.title && 
         form.level_id && 
         form.type && 
         form.test_date && 
         form.start_time && 
         form.end_time && 
         form.total_marks > 0 && 
         form.passing_marks >= 0 && 
         form.passing_marks <= form.total_marks &&
         form.subjects.length > 0 &&
         form.subjects.every(s => s.id && s.marks > 0) &&
         parseFloat(totalSubjectMarks.value) === parseFloat(form.total_marks)
})

onMounted(() => {
  loadFormData()
  
  // Load test data if in edit mode
  if (isEditMode.value) {
    loadTestData()
  }
})

const loadFormData = async () => {
  try {
    const response = await axios.get('/api/tests/form-data')
    
    if (response.data.success) {
      levels.value = response.data.data.levels || []
      subjects.value = response.data.data.subjects || []
    }
  } catch (error) {
    console.error('Error loading form data:', error)
    alert('Erreur lors du chargement des données')
  }
}

const loadTestData = async () => {
  try {
    const response = await axios.get(`/api/tests/${testId.value}`)
    
    if (response.data.success) {
      const test = response.data.data
      
      // Populate form with test data
      Object.assign(form, {
        title: test.title,
        description: test.description,
        level_id: test.level_id,
        type: test.type,
        test_date: test.test_date,
        start_time: test.start_time,
        end_time: test.end_time,
        duration_minutes: test.duration_minutes,
        total_marks: test.total_marks,
        passing_marks: test.passing_marks,
        test_file: test.test_file,
        instructions: test.instructions,
        subjects: test.subjects?.map(subject => ({
          id: subject.id,
          marks: parseFloat(subject.pivot.marks) || 0
        })) || [{ id: '', marks: 0 }]
      })
    }
  } catch (error) {
    console.error('Error loading test data:', error)
    alert('Erreur lors du chargement du test')
    router.push({ name: 'admissions.tests.list' })
  }
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
      alert('Le fichier est trop volumineux. Taille maximale: 10MB')
      fileInput.value.value = ''
      return
    }
    
    form.test_file = file
  }
}

const addSubject = () => {
  if (form.subjects.length < availableSubjectsCount.value) {
    form.subjects.push({ id: '', marks: 0 })
  }
}

const removeSubject = (index) => {
  if (form.subjects.length > 1) {
    form.subjects.splice(index, 1)
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    
    // Add form fields
    Object.keys(form).forEach(key => {
      if (key === 'test_file') {
        if (form.test_file && typeof form.test_file !== 'string') {
          formData.append('test_file', form.test_file)
        }
      } else if (key === 'subjects') {
        form.subjects.forEach((subject, index) => {
          formData.append(`subjects[${index}][id]`, subject.id)
          formData.append(`subjects[${index}][marks]`, subject.marks)
        })
      } else {
        formData.append(key, form[key])
      }
    })

    let response
    
    if (isEditMode.value) {
      // Laravel doesn't support PUT with FormData, so we use POST with _method
      formData.append('_method', 'PUT')
      response = await axios.post(`/api/tests/${testId.value}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    } else {
      response = await axios.post('/api/tests', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }
    
    if (response.data.success) {
      const message = isEditMode.value 
        ? 'Test mis à jour avec succès!' 
        : 'Test créé avec succès!'
      alert(message)
      router.push({ name: 'admissions.tests.list' })
    }
    
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      alert('Veuillez corriger les erreurs dans le formulaire')
    } else {
      const action = isEditMode.value ? 'mise à jour' : 'création'
      alert(`Erreur lors de la ${action}: ` + (error.response?.data?.message || error.message))
      console.error('Submission error:', error)
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'admissions.tests.list' })
}

const getFileName = (path) => {
  if (!path) return ''
  return path.split('/').pop()
}
</script>

<style scoped>
.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.text-primary {
  color: #0d6efd !important;
}

hr {
  margin: 1rem 0;
  opacity: 0.25;
}

.btn {
  border-radius: 0.375rem;
  font-weight: 500;
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}

.alert {
  border-radius: 0.375rem;
}
</style>