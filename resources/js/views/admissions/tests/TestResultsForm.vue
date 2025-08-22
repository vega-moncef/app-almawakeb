<template>
<VerticalLayout>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title">Saisie des Résultats</h4>
                <p class="text-muted mb-0" v-if="studentTest">
                  {{ studentTest.student_visit?.student_first_name }} 
                  {{ studentTest.student_visit?.student_last_name }} - 
                  {{ studentTest.test?.title }}
                </p>
              </div>
              <button 
                type="button" 
                class="btn btn-secondary"
                @click="goBack"
              >
                <i class="fas fa-arrow-left"></i> Retour
              </button>
            </div>
          </div>
          
          <div class="card-body">
            <!-- Student and Test Information -->
            <div class="row mb-4" v-if="studentTest">
              <div class="col-12">
                <div class="alert alert-info">
                  <div class="row">
                    <div class="col-md-3">
                      <strong>Étudiant:</strong><br>
                      {{ studentTest.student_visit?.student_first_name }} 
                      {{ studentTest.student_visit?.student_last_name }}
                      <br>
                      <small class="text-muted">
                        {{ studentTest.student_visit?.student_gender }}
                      </small>
                    </div>
                    <div class="col-md-3">
                      <strong>Test:</strong><br>
                      {{ studentTest.test?.title }}
                      <br>
                      <small class="text-muted">
                        <span 
                          class="badge badge-sm"
                          :class="`bg-${studentTest.test?.type === 'ORAL' ? 'primary' : 'success'}`"
                        >
                          {{ studentTest.test?.type }}
                        </span>
                      </small>
                    </div>
                    <div class="col-md-3">
                      <strong>Assigné le:</strong><br>
                      {{ formatDateTime(studentTest.assigned_at) }}
                    </div>
                    <div class="col-md-3">
                      <strong>Statut actuel:</strong><br>
                      <span 
                        class="badge"
                        :class="`bg-${getStatusColor(studentTest.status)}`"
                      >
                        {{ getStatusLabel(studentTest.status) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="submitResults">
              <!-- Test Status -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Statut du Test</h5>
                  <hr>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label class="form-label">Statut *</label>
                  <select 
                    class="form-select" 
                    v-model="form.status"
                    :class="{ 'is-invalid': errors.status }"
                    required
                  >
                    <option value="assigned">Assigné</option>
                    <option value="in_progress">En cours</option>
                    <option value="completed">Terminé</option>
                    <option value="absent">Absent</option>
                  </select>
                  <div v-if="errors.status" class="invalid-feedback">
                    {{ errors.status[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Date du test</label>
                  <input 
                    type="datetime-local" 
                    class="form-control" 
                    v-model="form.test_date"
                    :class="{ 'is-invalid': errors.test_date }"
                    :placeholder="studentTest?.student_visit?.test_date ? 'Date programmée' : 'Sélectionner la date du test'"
                  >
                  <small class="text-muted">
                    {{ studentTest?.student_visit?.test_date ? 'Date programmée lors de l\'assignation' : 'Aucune date programmée - veuillez saisir la date du test' }}
                  </small>
                  <div v-if="errors.test_date" class="invalid-feedback">
                    {{ errors.test_date[0] }}
                  </div>
                </div>

              </div>

              <!-- Subject Results -->
              <div class="row mb-4" v-if="form.status === 'completed' || form.status === 'in_progress'">
                <div class="col-12">
                  <h5 class="text-primary">Résultats par Matière</h5>
                  <hr>
                </div>
                
                <div class="col-12">
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="table-light">
                        <tr>
                          <th>Matière</th>
                          <th style="width: 150px;">Note obtenue</th>
                          <th style="width: 150px;">Note maximale</th>
                          <th style="width: 100px;">Pourcentage</th>
                          <th>Remarques</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(result, index) in form.results" :key="result.subject_id">
                          <td>
                            <strong>{{ getSubjectName(result.subject_id) }}</strong>
                          </td>
                          <td>
                            <input 
                              type="number" 
                              class="form-control" 
                              v-model.number="result.score"
                              :class="{ 'is-invalid': errors[`results.${index}.score`] }"
                              min="0"
                              step="0.5"
                              :max="result.max_score"
                              placeholder="0"
                              required
                            >
                            <div v-if="errors[`results.${index}.score`]" class="invalid-feedback">
                              {{ errors[`results.${index}.score`][0] }}
                            </div>
                          </td>
                          <td>
                            <input 
                              type="number" 
                              class="form-control" 
                              v-model.number="result.max_score"
                              :class="{ 'is-invalid': errors[`results.${index}.max_score`] }"
                              min="0"
                              step="0.5"
                              placeholder="20"
                              required
                              readonly
                            >
                          </td>
                          <td>
                            <div class="text-center">
                              <span 
                                class="badge"
                                :class="`bg-${getGradeColor(getPercentage(result.score, result.max_score))}`"
                              >
                                {{ getPercentage(result.score, result.max_score) }}%
                              </span>
                            </div>
                          </td>
                          <td>
                            <textarea 
                              class="form-control" 
                              rows="2" 
                              v-model="result.remarks"
                              placeholder="Remarques sur cette matière..."
                            ></textarea>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="table-secondary">
                        <tr>
                          <th>Total</th>
                          <th>
                            <strong>{{ totalScore }}/{{ totalMaxScore }}</strong>
                          </th>
                          <th>
                            <strong>{{ totalMaxScore }}</strong>
                          </th>
                          <th>
                            <div class="text-center">
                              <span 
                                class="badge"
                                :class="`bg-${getGradeColor(totalPercentage)}`"
                              >
                                {{ totalPercentage }}%
                              </span>
                            </div>
                          </th>
                          <th></th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                  
                  <!-- Overall Result -->
                  <div class="row mt-3">
                    <div class="col-md-6">
                      <div 
                        class="alert"
                        :class="totalScore >= (studentTest?.test?.passing_marks || 10) ? 'alert-success' : 'alert-danger'"
                      >
                        <div class="d-flex align-items-center">
                          <i 
                            class="fas fa-2x me-3"
                            :class="totalScore >= (studentTest?.test?.passing_marks || 10) ? 'fa-check-circle' : 'fa-times-circle'"
                          ></i>
                          <div>
                            <h5 class="mb-1">
                              {{ totalScore >= (studentTest?.test?.passing_marks || 10) ? 'RÉUSSI' : 'ÉCHOUÉ' }}
                            </h5>
                            <p class="mb-0">
                              Note de passage: {{ studentTest?.test?.passing_marks }}/{{ studentTest?.test?.total_marks }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="card bg-light">
                        <div class="card-body text-center">
                          <h3 class="mb-1">{{ totalScore }}/{{ totalMaxScore }}</h3>
                          <p class="text-muted mb-0">Score Total</p>
                          <h4 class="text-primary">{{ totalPercentage }}%</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary">Notes et Observations</h5>
                  <hr>
                </div>
                
                <div class="col-12 mb-3">
                  <label class="form-label">Notes générales</label>
                  <textarea 
                    class="form-control" 
                    rows="4" 
                    v-model="form.notes"
                    placeholder="Observations générales sur le test, comportement de l'étudiant, difficultés rencontrées..."
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
                    
                    <div class="d-flex gap-2">
                      <button 
                        v-if="form.status === 'completed' && totalScore >= (studentTest?.test?.passing_marks || 10)"
                        type="button" 
                        class="btn btn-success"
                        @click="quickAccept"
                      >
                        <i class="fas fa-check"></i> Accepter Directement
                      </button>
                      
                      <button 
                        type="submit" 
                        class="btn btn-primary"
                        :disabled="loading"
                      >
                        <i class="fas fa-save"></i>
                        <span v-if="loading">Enregistrement...</span>
                        <span v-else>Enregistrer les Résultats</span>
                      </button>
                    </div>
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

// Data
const loading = ref(false)
const studentTest = ref(null)
const errors = ref({})

const studentTestId = computed(() => route.params.id)

const form = reactive({
  status: 'assigned',
  test_date: '',
  notes: '',
  results: []
})

// Computed properties
const totalScore = computed(() => {
  return form.results.reduce((total, result) => {
    const score = parseFloat(result.score) || 0
    return total + score
  }, 0)
})

const totalMaxScore = computed(() => {
  return form.results.reduce((total, result) => {
    const maxScore = parseFloat(result.max_score) || 0
    return total + maxScore
  }, 0)
})

const totalPercentage = computed(() => {
  if (totalMaxScore.value === 0) return 0
  return Math.round((totalScore.value / totalMaxScore.value) * 100)
})

// Lifecycle
onMounted(() => {
  loadStudentTest()
})

// Methods
const loadStudentTest = async () => {
  try {
    // First get the student test details
    const studentTestResponse = await axios.get(`/api/student-tests?id=${studentTestId.value}`)
    
    if (studentTestResponse.data.success && studentTestResponse.data.data.data.length > 0) {
      const testData = studentTestResponse.data.data.data[0]
      studentTest.value = testData
      
      // Populate form with existing data
      form.status = testData.status || 'assigned'
      
      // Use programmed test date from studentVisit.test_date if available
      form.test_date = testData.student_visit?.test_date 
        ? formatDateTimeForInput(testData.student_visit.test_date)
        : ''
      
      form.notes = testData.notes || ''
      
      // Load test subjects to initialize results
      if (testData.test && testData.test.subjects) {
        form.results = testData.test.subjects.map(subject => {
          const existingResult = testData.results?.find(r => r.subject_id === subject.id)
          return {
            subject_id: subject.id,
            score: parseFloat(existingResult?.score) || 0,
            max_score: parseFloat(subject.pivot?.marks) || 0,
            remarks: existingResult?.remarks || ''
          }
        })
      }
    } else {
      alert('Test non trouvé')
      router.push({ name: 'admissions.test-results.list' })
    }
  } catch (error) {
    console.error('Error loading student test:', error)
    alert('Erreur lors du chargement du test')
    router.push({ name: 'admissions.test-results.list' })
  }
}

const submitResults = async () => {
  loading.value = true
  errors.value = {}

  try {
    const payload = {
      status: form.status,
      test_date: form.test_date || null,
      notes: form.notes,
      results: form.status === 'completed' ? form.results : []
    }

    const response = await axios.put(`/api/student-tests/${studentTestId.value}/results`, payload)
    
    if (response.data.success) {
      alert('Résultats enregistrés avec succès!')
      router.push({ name: 'admissions.test-results.list' })
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

const quickAccept = async () => {
  if (!confirm('Voulez-vous accepter cet étudiant directement ?')) {
    return
  }

  try {
    // First save the results
    await submitResults()
    
    // Then accept the student
    await axios.patch(`/api/student-tests/${studentTestId.value}/admission-decision`, {
      admission_decision: 'accepted',
      notes: 'Accepté automatiquement après réussite du test'
    })
    
    alert('Étudiant accepté avec succès!')
    router.push({ name: 'admissions.test-results.list' })
  } catch (error) {
    console.error('Error in quick accept:', error)
    alert('Erreur lors de l\'acceptation automatique')
  }
}

const goBack = () => {
  router.push({ name: 'admissions.test-results.list' })
}

// Utility functions
const formatDateTimeForInput = (datetime) => {
  if (!datetime) return ''
  const date = new Date(datetime)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

const formatDateTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleDateString('fr-FR') + ' ' + 
         new Date(datetime).toLocaleTimeString('fr-FR', { 
           hour: '2-digit', 
           minute: '2-digit' 
         })
}

const getSubjectName = (subjectId) => {
  const subject = studentTest.value?.test?.subjects?.find(s => s.id === subjectId)
  return subject?.name || 'Matière inconnue'
}

const getPercentage = (score, maxScore) => {
  const numScore = parseFloat(score) || 0
  const numMaxScore = parseFloat(maxScore) || 0
  if (numMaxScore === 0) return 0
  return Math.round((numScore / numMaxScore) * 100)
}

const getGradeColor = (percentage) => {
  if (percentage >= 90) return 'success'
  if (percentage >= 80) return 'primary'
  if (percentage >= 60) return 'warning'
  if (percentage >= 50) return 'info'
  return 'danger'
}

const getStatusColor = (status) => {
  const colors = {
    'assigned': 'info',
    'in_progress': 'warning',
    'completed': 'success',
    'absent': 'danger'
  }
  return colors[status] || 'secondary'
}

const getStatusLabel = (status) => {
  const labels = {
    'assigned': 'Assigné',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'absent': 'Absent'
  }
  return labels[status] || status
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

.badge {
  font-size: 0.75em;
}

.badge-sm {
  font-size: 0.7em;
}
</style>