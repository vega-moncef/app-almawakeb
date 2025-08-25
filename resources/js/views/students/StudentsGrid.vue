<template>
  <VerticalLayout>
    <PageTitle title="Élèves Inscrits" subtitle="Vue grille des élèves inscrits" />
    
    <BRow>
      <StudentWelcomeCard :student-stats="studentStats" />
      <StudentWidgets :stats="widgetStats" />
    </BRow>

    <BRow>
      <BCol lg="12">
        <BCard no-body class="bg-body shadow-none">
          <BCardHeader class="border-0">
            <BRow class="justify-content-between align-items-center">
              <BCol lg="6">
                <p class="mb-0 text-muted">Affichage de <span class="text-dark fw-semibold">{{ pagination.total }}</span> Élèves</p>
              </BCol>
              <BCol lg="6">
                <div class="text-md-end mt-3 mt-md-0">
                  <BButton type="button" variant="outline-primary" class="me-1" @click="$router.push('/students')">
                    <i class="ri-list-unordered me-1"></i>Vue Liste
                  </BButton>
                  <BButton type="button" variant="outline-primary" class="me-1" @click="resetFilters">
                    <i class="ri-filter-line me-1"></i> Réinitialiser
                  </BButton>
                  <BButton type="button" variant="outline-success" class="me-1" @click="showImportModal = true">
                    <i class="ri-download-line"></i> Importer
                  </BButton>
                  <BButton type="button" variant="success" class="me-1" @click="showCreateModal = true">
                    <i class="ri-add-line"></i> Nouvel Élève
                  </BButton>
                </div>
              </BCol>
            </BRow>
          </BCardHeader>
          
          <BCardBody>
            <!-- Filters -->
            <BRow class="mb-4">
              <BCol lg="3">
                <BFormGroup label="Rechercher" label-for="search-input">
                  <BInputGroup>
                    <BFormInput
                      id="search-input"
                      v-model="filters.search"
                      placeholder="Nom, code élève..."
                      @keyup.enter="loadStudents"
                    />
                    <template #append>
                      <BButton variant="outline-primary" @click="loadStudents">
                        <i class="ri-search-line"></i>
                      </BButton>
                    </template>
                  </BInputGroup>
                </BFormGroup>
              </BCol>
              <BCol lg="2">
                <BFormGroup label="Statut" label-for="status-filter">
                  <BFormSelect
                    id="status-filter"
                    v-model="filters.status"
                    :options="statusOptions"
                    @change="loadStudents"
                  />
                </BFormGroup>
              </BCol>
              <BCol lg="3">
                <BFormGroup label="Classe" label-for="class-filter">
                  <BFormSelect
                    id="class-filter"
                    v-model="filters.class_id"
                    :options="classOptions"
                    @change="loadStudents"
                  />
                </BFormGroup>
              </BCol>
              <BCol lg="2">
                <BFormGroup label="Par page" label-for="per-page">
                  <BFormSelect
                    id="per-page"
                    v-model="pagination.per_page"
                    :options="perPageOptions"
                    @change="handlePerPageChange"
                  />
                </BFormGroup>
              </BCol>
              <BCol lg="2">
                <BFormGroup label="Actions" label-for="actions">
                  <BButton variant="outline-secondary" @click="resetFilters" class="w-100">
                    <i class="ri-refresh-line"></i>
                    Réinitialiser
                  </BButton>
                </BFormGroup>
              </BCol>
            </BRow>

            <!-- Loading State -->
            <div v-if="loading" class="text-center my-5">
              <BSpinner class="me-2"></BSpinner>
              <span>Chargement des élèves...</span>
            </div>
            
            <!-- Students Grid -->
            <BRow v-else>
              <BCol 
                v-for="student in students" 
                :key="student.id" 
                xl="4" 
                lg="6" 
                class="mb-4"
              >
                <BCard no-body>
                  <BCardBody>
                    <div class="d-flex flex-wrap align-items-center gap-2 border-bottom pb-3">
                      <img 
                        v-if="student.student_photo" 
                        :src="`/assets/images/students/${student.student_photo}`" 
                        :alt="`${student.first_name} ${student.last_name}`"
                        class="avatar-lg rounded-3 border border-light border-3"
                      />
                      <div 
                        v-else 
                        class="avatar-lg rounded-3 border border-light border-3 d-flex align-items-center justify-content-center bg-light"
                      >
                        <span class="fs-18 fw-semibold text-primary">
                          {{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}
                        </span>
                      </div>
                      <div class="d-block">
                        <a href="#!" class="text-dark fw-medium fs-16">{{ student.first_name }} {{ student.last_name }}</a>
                        <p class="mb-0">
                          <span v-if="student.class?.level?.school" class="text-muted">
                            {{ student.class.level.school.name }} - {{ student.class.level.name }} - {{ student.class.name }}
                          </span>
                          <span v-else class="text-muted">Non assigné à une classe</span>
                        </p>
                        <p class="mb-0 text-primary"># {{ student.student_code }}</p>
                      </div>
                      <div class="ms-auto">
                        <BBadge :variant="student.status_color" class="text-uppercase">
                          {{ student.status_label }}
                        </BBadge>
                      </div>
                    </div>
                    
                    <p class="mt-3 d-flex align-items-center gap-2 mb-2">
                      <i class="ri-calendar-line fs-18 text-primary"></i>{{ student.age || 'N/A' }} ans
                    </p>
                    
                    <p class="d-flex align-items-center gap-2 mt-2 mb-2">
                      <i class="ri-user-line fs-18 text-primary"></i>{{ student.gender === 'male' ? 'Garçon' : 'Fille' }}
                    </p>
                    
                    <p  class="d-flex align-items-center gap-2 mt-2 mb-2">
                      <i class="ri-school-line fs-18 text-primary"></i>Massar : {{ student.massar_code }}
                    </p>
                    
                    <p class="d-flex align-items-center gap-2 mt-2">
                      <i class="ri-calendar-event-line fs-18 text-primary"></i>Naissance : {{ formatDate(student.birth_date) }}  <span>{{ student.birth_place }}</span>
                    </p>
                    
                    <p class="d-flex align-items-center gap-2 mt-2 mb-2">
                      <i class="ri-bus-line fs-18 text-primary"></i>Transport : 
                      <span :class="student.transport_method && student.transport_method !== 'none' ? 'text-success fw-medium' : 'text-muted'">
                        {{ student.transport_method && student.transport_method !== 'none' ? 'Oui' : 'Non' }}
                      </span>
                    </p>
                    
                    <p class="d-flex align-items-center gap-2 mt-2 mb-2">
                      <i class="ri-restaurant-line fs-18 text-primary"></i>Restaurant : 
                      <span :class="student.meal_plan && student.meal_plan !== 'none' ? 'text-success fw-medium' : 'text-muted'">
                        {{ student.meal_plan && student.meal_plan !== 'none' ? 'Oui' : 'Non' }}
                      </span>
                    </p>
                    
                    <h5 class="my-3">Contact Parent :</h5>
                    <div class="d-flex align-items-center gap-2">
                      <i class="ri-parent-line fs-18 text-muted"></i>
                      <span class="text-muted">Père :</span>
                      <span>{{ student.father_first_name }} {{ student.father_last_name }}</span><br>
                      <span>{{ student.father_phone }}</span>
                    </div>
                  </BCardBody>
                  
                  <BCardFooter class="border-top">
                    <BRow class="g-2">
                      <BCol lg="4">
                        <BButton 
                          variant="primary" 
                          size="sm" 
                          class="w-100"
                          @click="viewStudent(student)"
                        >
                          <i class="ri-eye-line align-middle fs-16"></i> Voir
                        </BButton>
                      </BCol>
                      <BCol lg="4">
                        <BButton 
                          variant="outline-secondary" 
                          size="sm" 
                          class="w-100"
                          @click="editStudent(student)"
                        >
                          <i class="ri-edit-line align-middle fs-16"></i> Modifier
                        </BButton>
                      </BCol>
                      <BCol lg="4">
                        <BButton 
                          variant="outline-danger" 
                          size="sm" 
                          class="w-100"
                          @click="deleteStudent(student)"
                        >
                          <i class="ri-delete-bin-line align-middle fs-16"></i> Supprimer
                        </BButton>
                      </BCol>
                    </BRow>
                  </BCardFooter>
                </BCard>
              </BCol>
              
              <!-- No Data State -->
              <BCol v-if="!students.length && !loading" cols="12">
                <div class="text-center py-5">
                  <div class="avatar-md mx-auto mb-4">
                    <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                      <i class="ri-user-search-line"></i>
                    </div>
                  </div>
                  <h5 class="mb-1">Aucun élève trouvé</h5>
                  <p class="text-muted mb-3">
                    {{ Object.values(filters).some(f => f) ? 
                       'Aucun élève ne correspond aux critères de recherche.' : 
                       'Aucun élève n\'est inscrit pour cette année académique.' }}
                  </p>
                  <BButton variant="primary" @click="showCreateModal = true">
                    <i class="ri-add-line me-1"></i>
                    Ajouter un Élève
                  </BButton>
                </div>
              </BCol>
            </BRow>

          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
    
    <div class="p-3 border-top" v-if="pagination.last_page > 1">
      <BPagination 
        v-model="pagination.current_page"
        :total-rows="pagination.total" 
        :per-page="pagination.per_page" 
        prev-text="Précédent" 
        next-text="Suivant" 
        class="justify-content-end mb-0" 
        @page-click="loadStudents"
      />
    </div>

    <!-- Create/Edit Student Modal -->
    <StudentForm
      v-if="showCreateModal || editingStudent"
      :visible="showCreateModal || !!editingStudent"
      :student="editingStudent"
      @close="closeModal"
      @saved="handleStudentSaved"
    />

    <!-- Import from Visits Modal -->
    <ImportVisitsModal
      v-if="showImportModal"
      :visible="showImportModal"
      @close="showImportModal = false"
      @imported="handleStudentImported"
    />

  </VerticalLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue'
import StudentForm from './components/StudentForm.vue'
import ImportVisitsModal from './components/ImportVisitsModal.vue'
import StudentWelcomeCard from './components/StudentWelcomeCard.vue'
import StudentWidgets from './components/StudentWidgets.vue'
import { useAcademicYearStore } from '@/stores/academicYear'
// Remove import - we'll define formatDate locally
import { showToast } from '@/helpers/toast'

const router = useRouter()
const academicYearStore = useAcademicYearStore()

// Reactive data
const students = ref([])
const classes = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const showImportModal = ref(false)
const editingStudent = ref(null)

// Stats for the top cards
const studentStats = ref({
  total: 0,
  by_status: {
    active: 0,
    suspended: 0,
    transferred: 0
  },
  by_gender: {},
  recent_enrollments: 0
})

const widgetStats = ref({
  totalEnrollments: 25,
  pendingApplications: 8,
  totalClasses: 12,
  occupiedClasses: 10,
  growthPercentage: 15.4
})

// Filters
const filters = ref({
  search: '',
  status: '',
  class_id: ''
})

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: 0,
  to: 0
})

// Computed
const statusOptions = computed(() => [
  { value: '', text: 'Tous' },
  { value: 'active', text: 'Actif' },
  { value: 'suspended', text: 'Suspendu' },
  { value: 'graduated', text: 'Diplômé' },
  { value: 'transferred', text: 'Transféré' },
  { value: 'dropped_out', text: 'Abandonné' }
])

const classOptions = computed(() => [
  { value: '', text: 'Toutes les classes' },
  ...classes.value.map(cls => ({
    value: cls.id,
    text: cls.full_name
  }))
])

const perPageOptions = computed(() => [
  { value: 6, text: '6' },
  { value: 12, text: '12' },
  { value: 18, text: '18' },
  { value: 24, text: '24' }
])

// Methods
const loadStats = async () => {
  if (!academicYearStore.currentAcademicYear?.id) return
  
  try {
    // Load student statistics
    const { data } = await axios.get('/api/students/stats', {
      params: { academic_year_id: academicYearStore.currentAcademicYear.id }
    })
    
    studentStats.value = {
      total: data.total || 0,
      by_status: data.by_status || {},
      by_gender: data.by_gender || {},
      recent_enrollments: data.recent_enrollments || 0
    }
    
    // Update widget stats based on actual data
    widgetStats.value.totalEnrollments = data.recent_enrollments || 0
    
    // Calculate class statistics
    await loadClassStats()
  } catch (error) {
    console.error('Error loading stats:', error)
    // Use default values if API fails
  }
}

const loadClassStats = async () => {
  try {
    // Get total classes count
    widgetStats.value.totalClasses = classes.value.length || 0
    
    // Calculate occupied classes by checking which classes have students
    const occupiedClassIds = new Set()
    students.value.forEach(student => {
      if (student.class_id) {
        occupiedClassIds.add(student.class_id)
      }
    })
    
    // If we don't have students data yet, get it from API
    if (students.value.length === 0) {
      try {
        const { data: studentsData } = await axios.get('/api/students', {
          params: { 
            academic_year_id: academicYearStore.currentAcademicYear.id,
            per_page: 1000 // Get all students to count occupied classes accurately
          }
        })
        
        const allStudents = studentsData.data || []
        allStudents.forEach(student => {
          if (student.class_id) {
            occupiedClassIds.add(student.class_id)
          }
        })
      } catch (error) {
        console.error('Error loading students for class stats:', error)
      }
    }
    
    widgetStats.value.occupiedClasses = occupiedClassIds.size
  } catch (error) {
    console.error('Error calculating class stats:', error)
  }
}

const loadStudents = async () => {
  if (!academicYearStore.currentAcademicYear?.id) {
    await academicYearStore.init()
  }
  
  if (!academicYearStore.currentAcademicYear?.id) {
    console.error('No current academic year available')
    return
  }
  
  loading.value = true
  try {
    const params = {
      academic_year_id: academicYearStore.currentAcademicYear.id,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    }
    
    // Only add non-empty filter values
    if (filters.value.search && filters.value.search.trim() !== '') {
      params.search = filters.value.search.trim()
    }
    if (filters.value.status && filters.value.status !== '') {
      params.status = filters.value.status
    }
    if (filters.value.class_id && filters.value.class_id !== '') {
      params.class_id = filters.value.class_id
    }

    const { data } = await axios.get('/api/students', { params })
    
    students.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
      from: data.from,
      to: data.to
    }
    
    // Update total count in stats
    studentStats.value.total = data.total
    
    // Calculate status counts from current page data
    if (data.data) {
      const statusCounts = data.data.reduce((acc, student) => {
        acc[student.status] = (acc[student.status] || 0) + 1
        return acc
      }, {})
      
      // These are estimates based on current page, real stats should come from API
      studentStats.value.active = statusCounts.active || 0
      studentStats.value.suspended = statusCounts.suspended || 0
      studentStats.value.transferred = statusCounts.transferred || 0
    }
  } catch (error) {
    console.error('Error loading students:', error)
    showToast('Erreur lors du chargement des élèves', 'error')
  } finally {
    loading.value = false
  }
}

const loadClasses = async () => {
  if (!academicYearStore.currentAcademicYear?.id) return
  
  try {
    const { data } = await axios.get('/api/students/classes', {
      params: { academic_year_id: academicYearStore.currentAcademicYear.id }
    })
    classes.value = data
  } catch (error) {
    console.error('Error loading classes:', error)
  }
}

const viewStudent = (student) => {
  router.push({ name: 'student-detail', params: { id: student.id } })
}

const editStudent = (student) => {
  editingStudent.value = student
}

const deleteStudent = async (student) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'élève ${student.first_name} ${student.last_name} ?`)) {
    return
  }

  try {
    await axios.delete(`/api/students/${student.id}`)
    showToast('Élève supprimé avec succès', 'success')
    loadStudents()
  } catch (error) {
    console.error('Error deleting student:', error)
    showToast('Erreur lors de la suppression de l\'élève', 'error')
  }
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    class_id: ''
  }
  pagination.value.current_page = 1
  loadStudents()
}

const handlePerPageChange = () => {
  pagination.value.current_page = 1
  loadStudents()
}

const closeModal = () => {
  showCreateModal.value = false
  editingStudent.value = null
}

const handleStudentSaved = () => {
  closeModal()
  loadStudents()
  showToast('Élève sauvegardé avec succès', 'success')
}

const handleStudentImported = () => {
  showImportModal.value = false
  loadStudents()
  showToast('Élèves importés avec succès', 'success')
}

const handleAcademicYearChange = async () => {
  await loadClasses()
  await loadStats()
  await loadStudents()
}

// Date formatting helper
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Lifecycle
onMounted(async () => {
  if (!academicYearStore.currentAcademicYear) {
    await academicYearStore.init()
  }
  
  await loadClasses()
  await loadStats()
  await loadStudents()
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', handleAcademicYearChange)
})

// Cleanup
import { onBeforeUnmount } from 'vue'
onBeforeUnmount(() => {
  window.removeEventListener('academic-year-changed', handleAcademicYearChange)
})
</script>

