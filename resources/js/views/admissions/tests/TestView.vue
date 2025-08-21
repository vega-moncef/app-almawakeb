<template>
<VerticalLayout>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title">{{ test?.title }}</h4>
                <p class="text-muted mb-0" v-if="test">
                  {{ test.level?.school?.name }} - {{ test.level?.name }} | 
                  <span 
                    class="badge"
                    :class="`bg-${test.type === 'ORAL' ? 'primary' : 'success'}`"
                  >
                    {{ test.type }}
                  </span>
                </p>
              </div>
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-outline-warning"
                  @click="editTest"
                  v-if="test"
                >
                  <i class="fas fa-edit"></i> Modifier
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-info"
                  @click="assignStudents"
                  v-if="test"
                >
                  <i class="fas fa-user-plus"></i> Assigner Étudiants
                </button>
                <button 
                  type="button" 
                  class="btn btn-secondary"
                  @click="goBack"
                >
                  <i class="fas fa-arrow-left"></i> Retour
                </button>
              </div>
            </div>
          </div>
          
          <div class="card-body" v-if="test">
            <!-- Test Information -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary">Informations du Test</h5>
                <hr>
              </div>
              
              <div class="col-md-6">
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Titre:</strong></div>
                  <div class="col-sm-8">{{ test.title }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Description:</strong></div>
                  <div class="col-sm-8">{{ test.description || 'Aucune description' }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>École:</strong></div>
                  <div class="col-sm-8">{{ test.level?.school?.name }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Niveau:</strong></div>
                  <div class="col-sm-8">{{ test.level?.name }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Type:</strong></div>
                  <div class="col-sm-8">
                    <span 
                      class="badge"
                      :class="`bg-${test.type === 'ORAL' ? 'primary' : 'success'}`"
                    >
                      {{ test.type }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Date:</strong></div>
                  <div class="col-sm-8">{{ formatDate(test.test_date) }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Horaire:</strong></div>
                  <div class="col-sm-8">{{ formatTime(test.start_time) }} - {{ formatTime(test.end_time) }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Durée:</strong></div>
                  <div class="col-sm-8">{{ test.duration_minutes }} minutes</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Note totale:</strong></div>
                  <div class="col-sm-8">{{ test.total_marks }} points</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4"><strong>Note de passage:</strong></div>
                  <div class="col-sm-8">{{ test.passing_marks }} points</div>
                </div>
              </div>
            </div>

            <!-- Test File -->
            <div class="row mb-4" v-if="test.test_file">
              <div class="col-12">
                <h5 class="text-primary">Document du Test</h5>
                <hr>
              </div>
              
              <div class="col-12">
                <div class="d-flex align-items-center">
                  <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                  <div>
                    <h6 class="mb-1">{{ getFileName(test.test_file) }}</h6>
                    <small class="text-muted">Fichier du test</small>
                  </div>
                  <a 
                    :href="`/storage/${test.test_file}`" 
                    target="_blank"
                    class="btn btn-outline-primary btn-sm ms-auto"
                  >
                    <i class="fas fa-download"></i> Télécharger
                  </a>
                </div>
              </div>
            </div>

            <!-- Subjects -->
            <div class="row mb-4" v-if="test.subjects && test.subjects.length > 0">
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
                        <th>Description</th>
                        <th>Note</th>
                        <th>Pourcentage</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="subject in test.subjects" :key="subject.id">
                        <td><strong>{{ subject.name }}</strong></td>
                        <td>{{ subject.description || '-' }}</td>
                        <td>{{ subject.pivot.marks }} pts</td>
                        <td>
                          <span class="badge bg-info">
                            {{ Math.round((subject.pivot.marks / test.total_marks) * 100) }}%
                          </span>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="table-secondary">
                      <tr>
                        <th colspan="2">Total</th>
                        <th>{{ test.total_marks }} pts</th>
                        <th>100%</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Instructions -->
            <div class="row mb-4" v-if="test.instructions">
              <div class="col-12">
                <h5 class="text-primary">Instructions</h5>
                <hr>
              </div>
              
              <div class="col-12">
                <div class="alert alert-light">
                  <pre class="mb-0">{{ test.instructions }}</pre>
                </div>
              </div>
            </div>

            <!-- Assigned Students -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="text-primary mb-0">Étudiants Assignés ({{ assignedStudents.length }})</h5>
                  <button 
                    type="button" 
                    class="btn btn-outline-primary btn-sm"
                    @click="assignStudents"
                  >
                    <i class="fas fa-user-plus"></i> Assigner plus d'étudiants
                  </button>
                </div>
                <hr>
              </div>
              
              <div class="col-12">
                <div v-if="loadingStudents" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Chargement...</span>
                  </div>
                  Chargement des étudiants...
                </div>
                
                <div v-else-if="assignedStudents.length === 0" class="text-center py-4 text-muted">
                  <i class="fas fa-users fa-2x mb-3 d-block"></i>
                  Aucun étudiant assigné à ce test
                </div>
                
                <div v-else class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Étudiant</th>
                        <th>École demandée</th>
                        <th>Assigné le</th>
                        <th>Statut</th>
                        <th>Score</th>
                        <th>Décision</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="assignment in assignedStudents" :key="assignment.id">
                        <td>
                          <div>
                            <h6 class="mb-1">
                              {{ assignment.student_visit?.student_first_name }} 
                              {{ assignment.student_visit?.student_last_name }}
                            </h6>
                            <small class="text-muted">
                              {{ assignment.student_visit?.student_gender }}
                            </small>
                          </div>
                        </td>
                        <td>
                          <small>{{ assignment.student_visit?.requested_school?.name }}</small>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ formatDateTime(assignment.assigned_at) }}
                          </small>
                        </td>
                        <td>
                          <span 
                            class="badge badge-sm"
                            :class="`bg-${getStatusColor(assignment.status)}`"
                          >
                            {{ getStatusLabel(assignment.status) }}
                          </span>
                        </td>
                        <td>
                          <div v-if="assignment.total_score !== null">
                            <small>
                              {{ assignment.total_score }}/{{ test.total_marks }}
                              ({{ assignment.percentage }}%)
                            </small>
                          </div>
                          <small v-else class="text-muted">-</small>
                        </td>
                        <td>
                          <span 
                            class="badge badge-sm"
                            :class="`bg-${getDecisionColor(assignment.admission_decision)}`"
                          >
                            {{ getDecisionLabel(assignment.admission_decision) }}
                          </span>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <button 
                              type="button" 
                              class="btn btn-outline-primary btn-sm"
                              @click="editResults(assignment)"
                              title="Modifier Résultats"
                            >
                              <Icon icon="solar:pen-broken" class="me-2" />
                            </button>
                            <button 
                              type="button" 
                              class="btn btn-outline-danger btn-sm"
                              @click="unassignStudent(assignment)"
                              title="Désassigner"
                            >
                              <Icon icon="solar:close-circle-broken" class="me-2" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Test Statistics -->
            <div class="row">
              <div class="col-12">
                <h5 class="text-primary">Statistiques</h5>
                <hr>
              </div>
              
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h4 class="text-primary">{{ assignedStudents.length }}</h4>
                    <p class="text-muted mb-0">Étudiants Assignés</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h4 class="text-success">{{ completedCount }}</h4>
                    <p class="text-muted mb-0">Tests Terminés</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h4 class="text-info">{{ passedCount }}</h4>
                    <p class="text-muted mb-0">Étudiants Réussis</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h4 class="text-warning">{{ acceptedCount }}</h4>
                    <p class="text-muted mb-0">Étudiants Acceptés</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Loading State -->
          <div v-else class="card-body">
            <div class="text-center py-5">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Chargement...</span>
              </div>
              <p class="mt-3">Chargement des détails du test...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</VerticalLayout>
</template>

<script setup>
import VerticalLayout from "@/layouts/VerticalLayout.vue";
import { Icon } from "@iconify/vue";
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

// Router and route
const route = useRoute()
const router = useRouter()

// Data
const test = ref(null)
const assignedStudents = ref([])
const loadingStudents = ref(false)

const testId = computed(() => route.params.id)

// Computed properties
const completedCount = computed(() => {
  return assignedStudents.value.filter(s => s.status === 'completed').length
})

const passedCount = computed(() => {
  return assignedStudents.value.filter(s => s.passed === true).length
})

const acceptedCount = computed(() => {
  return assignedStudents.value.filter(s => s.admission_decision === 'accepted').length
})

// Lifecycle
onMounted(() => {
  loadTest()
  loadAssignedStudents()
})

// Methods
const loadTest = async () => {
  try {
    const response = await axios.get(`/api/tests/${testId.value}`)
    
    if (response.data.success) {
      test.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading test:', error)
    alert('Erreur lors du chargement du test')
    router.push({ name: 'admissions.tests.list' })
  }
}

const loadAssignedStudents = async () => {
  loadingStudents.value = true
  try {
    const response = await axios.get(`/api/tests/${testId.value}/assigned-students`)
    
    if (response.data.success) {
      assignedStudents.value = response.data.data || []
    }
  } catch (error) {
    console.error('Error loading assigned students:', error)
  } finally {
    loadingStudents.value = false
  }
}

const editTest = () => {
  router.push({ 
    name: 'admissions.tests.edit', 
    params: { id: testId.value } 
  })
}

const assignStudents = () => {
  router.push({ 
    name: 'admissions.tests.assign', 
    params: { id: testId.value } 
  })
}

const editResults = (assignment) => {
  router.push({ 
    name: 'admissions.test-results.edit', 
    params: { id: assignment.id } 
  })
}

const unassignStudent = async (assignment) => {
  if (!confirm(`Désassigner ${assignment.student_visit?.student_first_name} ${assignment.student_visit?.student_last_name} ?`)) {
    return
  }

  try {
    const response = await axios.delete(`/api/student-tests/${assignment.id}`)
    
    if (response.data.success) {
      alert('Étudiant désassigné avec succès')
      loadAssignedStudents()
    }
  } catch (error) {
    console.error('Error unassigning student:', error)
    alert('Erreur lors de la désassignation')
  }
}

const goBack = () => {
  router.push({ name: 'admissions.tests.list' })
}

// Utility functions
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatTime = (time) => {
  if (!time) return ''
  return new Date(`2000-01-01 ${time}`).toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const formatDateTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleDateString('fr-FR') + ' ' + 
         new Date(datetime).toLocaleTimeString('fr-FR', { 
           hour: '2-digit', 
           minute: '2-digit' 
         })
}

const getFileName = (path) => {
  if (!path) return ''
  return path.split('/').pop()
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

const getDecisionColor = (decision) => {
  const colors = {
    'pending': 'warning',
    'accepted': 'success',
    'rejected': 'danger'
  }
  return colors[decision] || 'secondary'
}

const getDecisionLabel = (decision) => {
  const labels = {
    'pending': 'En attente',
    'accepted': 'Accepté',
    'rejected': 'Refusé'
  }
  return labels[decision] || decision
}
</script>

<style scoped>
.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.text-primary {
  color: #0d6efd !important;
}

hr {
  margin: 1rem 0;
  opacity: 0.25;
}

.badge {
  font-size: 0.75em;
}

.badge-sm {
  font-size: 0.7em;
}

.table td {
  vertical-align: middle;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

pre {
  white-space: pre-wrap;
  word-wrap: break-word;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>