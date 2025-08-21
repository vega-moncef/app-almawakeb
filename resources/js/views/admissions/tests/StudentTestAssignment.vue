<template>
<VerticalLayout>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title mb-1">Assigner des Étudiants au Test</h4>
                <p class="text-muted mb-0" v-if="test">
                  {{ test.title }} - {{ test.level?.school?.name }} {{ test.level?.name }}
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
            <!-- Test Information -->
            <div class="row mb-4" v-if="test">
              <div class="col-12">
                <div class="alert alert-info">
                  <div class="row">
                    <div class="col-md-3">
                      <strong>Type:</strong> 
                      <span class="badge ms-1" :class="`bg-${test.type === 'ORAL' ? 'primary' : 'success'}`">
                        {{ test.type }}
                      </span>
                    </div>
                    <div class="col-md-3">
                      <strong>Date:</strong> {{ formatDate(test.test_date) }}
                    </div>
                    <div class="col-md-3">
                      <strong>Horaire:</strong> {{ formatTime(test.start_time) }} - {{ formatTime(test.end_time) }}
                    </div>
                    <div class="col-md-3">
                      <strong>Durée:</strong> {{ test.duration_minutes }} min
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filters for Student Selection -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary">Sélection des Étudiants</h5>
                <hr>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Recherche</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="filters.search"
                  placeholder="Nom de l'étudiant..."
                  @input="debouncedSearch"
                >
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">École demandée</label>
                <select 
                  class="form-select" 
                  v-model="filters.school_id"
                  @change="loadStudentVisits"
                >
                  <option value="">Toutes les écoles</option>
                  <option 
                    v-for="school in schools" 
                    :key="school.id" 
                    :value="school.id"
                  >
                    {{ school.name }}
                  </option>
                </select>
              </div>
              
              <div class="col-md-4 mb-3">
                <label class="form-label">Statut de visite</label>
                <select 
                  class="form-select" 
                  v-model="filters.status"
                  @change="loadStudentVisits"
                >
                  <option value="">Tous les statuts</option>
                  <option value="pending">En attente</option>
                  <option value="test_scheduled">Test programmé</option>
                  <option value="tested">Testé</option>
                </select>
              </div>
            </div>

            <!-- Student Selection -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6>Étudiants Disponibles ({{ availableStudents.length }})</h6>
                  <div>
                    <button 
                      type="button" 
                      class="btn btn-outline-primary btn-sm me-2"
                      @click="selectAll"
                      :disabled="availableStudents.length === 0"
                    >
                      <i class="fas fa-check-square"></i> Tout sélectionner
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary btn-sm"
                      @click="clearSelection"
                    >
                      <i class="fas fa-square"></i> Tout désélectionner
                    </button>
                  </div>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 50px;">
                          <input 
                            type="checkbox" 
                            class="form-check-input"
                            :checked="selectedStudents.length === availableStudents.length && availableStudents.length > 0"
                            @change="toggleSelectAll"
                          >
                        </th>
                        <th>Étudiant</th>
                        <th>École demandée</th>
                        <th>Niveau demandé</th>
                        <th>Visite</th>
                        <th>Statut</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="loadingStudents">
                        <td colspan="6" class="text-center py-4">
                          <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Chargement...</span>
                          </div>
                          Chargement des étudiants...
                        </td>
                      </tr>
                      <tr v-else-if="availableStudents.length === 0">
                        <td colspan="6" class="text-center py-4 text-muted">
                          <i class="fas fa-users fa-2x mb-3 d-block"></i>
                          Aucun étudiant disponible pour ce test
                        </td>
                      </tr>
                      <tr v-else v-for="student in availableStudents" :key="student.id">
                        <td>
                          <input 
                            type="checkbox" 
                            class="form-check-input"
                            :value="student.id"
                            v-model="selectedStudents"
                          >
                        </td>
                        <td>
                          <div>
                            <h6 class="mb-1">{{ student.student_first_name }} {{ student.student_last_name }}</h6>
                            <small class="text-muted">
                              Né(e) le {{ formatDate(student.birth_date) }}
                            </small>
                          </div>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark">
                            {{ student.requested_school?.name }}
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-secondary">
                            {{ student.requested_level?.name }}
                          </span>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ formatDate(student.visit_date) }}
                          </small>
                        </td>
                        <td>
                          <span 
                            class="badge"
                            :class="`bg-${getStatusColor(student.status)}`"
                          >
                            {{ getStatusLabel(student.status) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Assignment Options -->
            <div class="row mb-4" v-if="selectedStudents.length > 0">
              <div class="col-12">
                <h5 class="text-primary">Options d'Assignation</h5>
                <hr>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Date d'assignation</label>
                <input 
                  type="datetime-local" 
                  class="form-control" 
                  v-model="assignmentOptions.assigned_at"
                >
                <small class="text-muted">Par défaut: maintenant</small>
              </div>
              
              <div class="col-12 mb-3">
                <label class="form-label">Notes d'assignation</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="assignmentOptions.notes"
                  placeholder="Notes ou instructions spéciales pour ces étudiants..."
                ></textarea>
              </div>
              
              <div class="col-12">
                <div class="alert alert-success">
                  <i class="fas fa-info-circle"></i>
                  <strong>{{ selectedStudents.length }}</strong> étudiant(s) sélectionné(s) pour le test
                  <strong>"{{ test?.title }}"</strong>
                </div>
              </div>
            </div>

            <!-- Already Assigned Students -->
            <div class="row mb-4" v-if="assignedStudents.length > 0">
              <div class="col-12">
                <h5 class="text-warning">Étudiants Déjà Assignés ({{ assignedStudents.length }})</h5>
                <hr>
              </div>
              
              <div class="col-12">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-light">
                      <tr>
                        <th>Étudiant</th>
                        <th>Date d'assignation</th>
                        <th>Statut</th>
                        <th>Score</th>
                        <th>Décision</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="assignment in assignedStudents" :key="assignment.id">
                        <td>
                          <small>
                            {{ assignment.student_visit?.student_first_name }} 
                            {{ assignment.student_visit?.student_last_name }}
                          </small>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ formatDateTime(assignment.assigned_at) }}
                          </small>
                        </td>
                        <td>
                          <span 
                            class="badge badge-sm"
                            :class="`bg-${getTestStatusColor(assignment.status)}`"
                          >
                            {{ getTestStatusLabel(assignment.status) }}
                          </span>
                        </td>
                        <td>
                          <small v-if="assignment.total_score">
                            {{ assignment.total_score }}/{{ test?.total_marks }}
                            ({{ assignment.percentage }}%)
                          </small>
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

                        
                          <button 
                            type="button" 
                            class="btn btn-outline-danger"
                            @click="unassignStudent(assignment)"
                            title="Désassigner"
                          >
                            <Icon icon="solar:trash-bin-trash-broken" class="me-2" />
                          </button>


                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
              <div class="col-12">
                <hr>
                <div class="d-flex justify-content-end gap-2">
                  <button 
                    type="button" 
                    class="btn btn-success"
                    @click="assignStudents"
                    :disabled="selectedStudents.length === 0 || loading"
                  >
                    <i class="fas fa-user-plus"></i>
                    <span v-if="loading">Assignation...</span>
                    <span v-else>Assigner {{ selectedStudents.length }} Étudiant(s)</span>
                  </button>
                </div>
              </div>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

// Router and route
const route = useRoute()
const router = useRouter()

// Data
const loading = ref(false)
const loadingStudents = ref(false)
const test = ref(null)
const studentVisits = ref([])
const assignedStudents = ref([])
const schools = ref([])
const selectedStudents = ref([])

const testId = computed(() => route.params.id)

const filters = reactive({
  search: '',
  school_id: '',
  status: ''
})

const assignmentOptions = reactive({
  assigned_at: getCurrentDateTime(),
  notes: ''
})

// Computed
const availableStudents = computed(() => {
  // Filter out students who are already assigned to this test
  const assignedIds = assignedStudents.value.map(a => a.student_visit_id)
  return studentVisits.value.filter(student => !assignedIds.includes(student.id))
})

// Lifecycle
onMounted(() => {
  loadTest()
  loadStudentVisits()
  loadAssignedStudents()
  loadSchools()
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

const loadStudentVisits = async () => {
  loadingStudents.value = true
  try {
    const params = {
      search: filters.search,
      school_id: filters.school_id,
      status: filters.status,
      per_page: 100 // Load more for assignment
    }

    const response = await axios.get('/api/student-visits', { params })
    
    if (response.data.success) {
      studentVisits.value = response.data.data.data || []
    }
  } catch (error) {
    console.error('Error loading student visits:', error)
  } finally {
    loadingStudents.value = false
  }
}

const loadAssignedStudents = async () => {
  try {
    const response = await axios.get(`/api/tests/${testId.value}/assigned-students`)
    
    if (response.data.success) {
      assignedStudents.value = response.data.data || []
    }
  } catch (error) {
    console.error('Error loading assigned students:', error)
  }
}

const loadSchools = async () => {
  try {
    const response = await axios.get('/api/schools')
    
    if (response.data.success) {
      schools.value = response.data.data || []
    }
  } catch (error) {
    console.error('Error loading schools:', error)
  }
}

// Debounced search
let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadStudentVisits()
  }, 500)
}

const selectAll = () => {
  selectedStudents.value = availableStudents.value.map(s => s.id)
}

const clearSelection = () => {
  selectedStudents.value = []
}

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectAll()
  } else {
    clearSelection()
  }
}

const assignStudents = async () => {
  if (selectedStudents.value.length === 0) return

  loading.value = true
  try {
    const payload = {
      student_visit_ids: selectedStudents.value,
      assigned_at: assignmentOptions.assigned_at || getCurrentDateTime(),
      notes: assignmentOptions.notes
    }

    const response = await axios.post(`/api/tests/${testId.value}/assign-students`, payload)
    
    if (response.data.success) {
      alert(`${selectedStudents.value.length} étudiant(s) assigné(s) avec succès!`)
      
      // Reload data
      await loadAssignedStudents()
      selectedStudents.value = []
      assignmentOptions.notes = ''
    }
  } catch (error) {
    console.error('Error assigning students:', error)
    alert('Erreur lors de l\'assignation des étudiants')
  } finally {
    loading.value = false
  }
}

const unassignStudent = async (assignment) => {
  if (!confirm('Êtes-vous sûr de vouloir désassigner cet étudiant ?')) {
    return
  }

  try {
    const response = await axios.delete(`/api/student-tests/${assignment.id}`)
    
    if (response.data.success) {
      alert('Étudiant désassigné avec succès')
      await loadAssignedStudents()
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
function getCurrentDateTime() {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
}

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

const getStatusColor = (status) => {
  const colors = {
    'pending': 'warning',
    'test_scheduled': 'info',
    'tested': 'secondary',
    'accepted': 'success',
    'rejected': 'danger'
  }
  return colors[status] || 'secondary'
}

const getStatusLabel = (status) => {
  const labels = {
    'pending': 'En attente',
    'test_scheduled': 'Test programmé',
    'tested': 'Testé',
    'accepted': 'Accepté',
    'rejected': 'Refusé'
  }
  return labels[status] || status
}

const getTestStatusColor = (status) => {
  const colors = {
    'assigned': 'info',
    'in_progress': 'warning',
    'completed': 'success',
    'absent': 'danger'
  }
  return colors[status] || 'secondary'
}

const getTestStatusLabel = (status) => {
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

.table td {
  vertical-align: middle;
}

.badge-sm {
  font-size: 0.7em;
}

.form-check-input {
  cursor: pointer;
}

.alert {
  border-radius: 0.375rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>