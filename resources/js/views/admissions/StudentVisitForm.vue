<template>
<VerticalLayout>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Fiche de Visite - Nouvel Élève</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <!-- Student Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Informations de l'Élève</h5>
                  <hr>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label class="form-label">Nom de l'élève *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.student_last_name"
                    :class="{ 'is-invalid': errors.student_last_name }"
                    required
                  >
                  <div v-if="errors.student_last_name" class="invalid-feedback">
                    {{ errors.student_last_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Prénom de l'élève *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.student_first_name"
                    :class="{ 'is-invalid': errors.student_first_name }"
                    required
                  >
                  <div v-if="errors.student_first_name" class="invalid-feedback">
                    {{ errors.student_first_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Sexe *</label>
                  <select 
                    class="form-select" 
                    v-model="form.student_gender"
                    :class="{ 'is-invalid': errors.student_gender }"
                    required
                  >
                    <option value="">Sélectionner</option>
                    <option value="FEMININ">FEMININ</option>
                    <option value="MASCULIN">MASCULIN</option>
                  </select>
                  <div v-if="errors.student_gender" class="invalid-feedback">
                    {{ errors.student_gender[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Date de naissance *</label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="form.birth_date"
                    :class="{ 'is-invalid': errors.birth_date }"
                    required
                  >
                  <div v-if="errors.birth_date" class="invalid-feedback">
                    {{ errors.birth_date[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Lieu de naissance</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.birth_place"
                    placeholder="MARRAKECH"
                  >
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Ville</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.city"
                    placeholder="MARRAKECH"
                  >
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">École fréquentée</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.current_school"
                    placeholder="Nom de l'école actuelle"
                  >
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Niveau scolaire</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.current_level"
                    placeholder="Ex: 1A BAC"
                  >
                </div>

                <div class="col-md-2 mb-3">
                  <label class="form-label">Nombre de redoublements</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="form.repeat_count"
                    min="0"
                    max="10"
                    placeholder="0"
                  >
                </div>
              </div>

              <!-- Father Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Informations du Père</h5>
                  <hr>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Nom du père *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.father_last_name"
                    :class="{ 'is-invalid': errors.father_last_name }"
                    required
                  >
                  <div v-if="errors.father_last_name" class="invalid-feedback">
                    {{ errors.father_last_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Prénom du père *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.father_first_name"
                    :class="{ 'is-invalid': errors.father_first_name }"
                    required
                  >
                  <div v-if="errors.father_first_name" class="invalid-feedback">
                    {{ errors.father_first_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Numéro de téléphone *</label>
                  <input 
                    type="tel" 
                    class="form-control" 
                    v-model="form.father_phone"
                    :class="{ 'is-invalid': errors.father_phone }"
                    placeholder="0661234567"
                    required
                  >
                  <div v-if="errors.father_phone" class="invalid-feedback">
                    {{ errors.father_phone[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">E-mail</label>
                  <input 
                    type="email" 
                    class="form-control" 
                    v-model="form.father_email"
                    :class="{ 'is-invalid': errors.father_email }"
                    placeholder="father@example.com"
                  >
                  <div v-if="errors.father_email" class="invalid-feedback">
                    {{ errors.father_email[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Profession</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.father_profession"
                    placeholder="Ex: PROF DE E.S"
                  >
                </div>
              </div>

              <!-- Mother Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Informations de la Mère</h5>
                  <hr>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Nom de la mère *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.mother_last_name"
                    :class="{ 'is-invalid': errors.mother_last_name }"
                    required
                  >
                  <div v-if="errors.mother_last_name" class="invalid-feedback">
                    {{ errors.mother_last_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Prénom de la mère *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.mother_first_name"
                    :class="{ 'is-invalid': errors.mother_first_name }"
                    required
                  >
                  <div v-if="errors.mother_first_name" class="invalid-feedback">
                    {{ errors.mother_first_name[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Numéro de téléphone *</label>
                  <input 
                    type="tel" 
                    class="form-control" 
                    v-model="form.mother_phone"
                    :class="{ 'is-invalid': errors.mother_phone }"
                    placeholder="0610234567"
                    required
                  >
                  <div v-if="errors.mother_phone" class="invalid-feedback">
                    {{ errors.mother_phone[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">E-mail</label>
                  <input 
                    type="email" 
                    class="form-control" 
                    v-model="form.mother_email"
                    :class="{ 'is-invalid': errors.mother_email }"
                    placeholder="mother@example.com"
                  >
                  <div v-if="errors.mother_email" class="invalid-feedback">
                    {{ errors.mother_email[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Profession</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.mother_profession"
                    placeholder="Ex: PROF E.P"
                  >
                </div>
              </div>

              <!-- School Request -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Demande d'Inscription</h5>
                  <hr>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">École demandée *</label>
                  <select 
                    class="form-select" 
                    v-model="form.requested_school_id"
                    @change="onSchoolChange"
                    :class="{ 'is-invalid': errors.requested_school_id }"
                    required
                  >
                    <option value="">Sélectionner une école</option>
                    <option 
                      v-for="school in schools" 
                      :key="school.id" 
                      :value="school.id"
                    >
                      {{ school.name }}
                    </option>
                  </select>
                  <small class="text-muted">Écoles disponibles: {{ schools.length }}</small>
                  <div v-if="errors.requested_school_id" class="invalid-feedback">
                    {{ errors.requested_school_id[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Niveau demandé</label>
                  <select 
                    class="form-select" 
                    v-model="form.requested_level_id"
                    :class="{ 'is-invalid': errors.requested_level_id }"
                    :disabled="!form.requested_school_id"
                  >
                    <option value="">Sélectionner un niveau</option>
                    <option 
                      v-for="level in filteredLevels" 
                      :key="level.id" 
                      :value="level.id"
                    >
                      {{ level.name }}
                    </option>
                  </select>
                  <div v-if="errors.requested_level_id" class="invalid-feedback">
                    {{ errors.requested_level_id[0] }}
                  </div>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Observations</label>
                  <textarea 
                    class="form-control" 
                    rows="3" 
                    v-model="form.observations"
                    placeholder="Notes et observations..."
                  ></textarea>
                </div>
              </div>

              <!-- Visit Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Informations de Visite</h5>
                  <hr>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Date de visite *</label>
                  <input 
                    type="datetime-local" 
                    class="form-control" 
                    v-model="form.visit_date"
                    :class="{ 'is-invalid': errors.visit_date }"
                    required
                  >
                  <div v-if="errors.visit_date" class="invalid-feedback">
                    {{ errors.visit_date[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Date du test (optionnel)</label>
                  <input 
                    type="datetime-local" 
                    class="form-control" 
                    v-model="form.test_date"
                  >
                  <small class="text-muted">Peut être programmé plus tard</small>
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
                      @click="resetForm"
                    >
                      <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                    
                    <button 
                      type="submit" 
                      class="btn btn-primary"
                      :disabled="loading"
                    >
                      <i class="fas fa-save"></i>
                      <span v-if="loading">Enregistrement...</span>
                      <span v-else>Enregistrer la Visite</span>
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
import axios from 'axios'

const loading = ref(false)
const schools = ref([])
const levels = ref([])
const errors = ref({})

const getCurrentDateTime = () => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
}

const form = reactive({
  // Student info
  student_first_name: '',
  student_last_name: '',
  student_gender: '',
  birth_date: '',
  birth_place: 'MARRAKECH',
  current_school: '',
  current_level: '',
  city: 'MARRAKECH',
  repeat_count: 0,
  
  // Father info
  father_first_name: '',
  father_last_name: '',
  father_phone: '',
  father_email: '',
  father_profession: '',
  
  // Mother info
  mother_first_name: '',
  mother_last_name: '',
  mother_phone: '',
  mother_email: '',
  mother_profession: '',
  
  // Request info
  requested_school_id: '',
  requested_level_id: '',
  observations: '',
  visit_date: getCurrentDateTime(),
  test_date: ''
})

const filteredLevels = computed(() => {
  if (!form.requested_school_id) return []
  return levels.value.filter(level => level.school_id == form.requested_school_id)
})

onMounted(() => {
  loadFormData()
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', (event) => {
    console.log('Academic year changed, reloading form data...')
    loadFormData()
  })
})

const loadFormData = async () => {
  console.log('🔄 Loading form data...')
  try {
    const response = await axios.get('/api/student-visits/form-data')
    console.log('📥 API Response:', response.data)
    
    if (response.data.success) {
      schools.value = response.data.data.schools || []
      console.log('🏫 Schools loaded:', schools.value)
      console.log('📊 Total schools:', schools.value.length)
      
      if (schools.value.length === 0) {
        console.warn('⚠️ No schools found in response')
      }
    } else {
      throw new Error('Failed to load form data')
    }
  } catch (error) {
    console.error('❌ Error loading form data:', error)
    console.error('❌ Error details:', error.response?.data)
    alert('Erreur lors du chargement des données: ' + (error.response?.data?.message || error.message))
  }
}

const onSchoolChange = async () => {
  form.requested_level_id = ''
  levels.value = []
  
  if (form.requested_school_id) {
    try {
      const response = await axios.get(`/api/schools/${form.requested_school_id}/levels`)
      if (response.data.success) {
        levels.value = response.data.data
      }
    } catch (error) {
      console.error('Error loading levels:', error)
      alert('Erreur lors du chargement des niveaux')
    }
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await axios.post('/api/student-visits', form)
    
    if (response.data.success) {
      alert('Fiche de visite enregistrée avec succès!')
      resetForm()
    } else {
      alert('Erreur lors de l\'enregistrement')
    }
    
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      alert('Veuillez corriger les erreurs dans le formulaire')
    } else {
      alert('Erreur lors de l\'enregistrement: ' + (error.response?.data?.message || error.message))
      console.error('Submission error:', error)
    }
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'birth_place' || key === 'city') {
      form[key] = 'MARRAKECH'
    } else if (key === 'repeat_count') {
      form[key] = 0
    } else if (key === 'visit_date') {
      form[key] = getCurrentDateTime()
    } else {
      form[key] = ''
    }
  })
  errors.value = {}
  levels.value = []
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
</style>