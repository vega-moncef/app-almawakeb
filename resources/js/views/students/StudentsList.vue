<template>
  <VerticalLayout>
    <PageTitle title="Élèves Inscrits" subtitle="Gestion des élèves inscrits" />
    
    <BRow>
      <BCol xxl="12">
        <BCard no-body>
          <BCardHeader class="align-items-center d-flex">
            <BCardTitle class="mb-0 flex-grow-1">Liste des Élèves</BCardTitle>
            <div class="flex-shrink-0">
              <div class="d-flex flex-wrap align-items-start justify-content-end gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                  <BButton variant="primary" @click="showCreateModal = true">
                    <i class="ri-add-line align-bottom me-1"></i>
                    Nouvel Élève
                  </BButton>
                  <BButton variant="success" @click="showImportModal = true">
                    <i class="ri-download-line align-bottom me-1"></i>
                    Importer des Visites
                  </BButton>
                </div>
              </div>
            </div>
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
              <BCol lg="3">
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
              <BCol lg="3">
                <BFormGroup label="Actions" label-for="actions">
                  <div class="d-flex gap-2">
                    <BButton variant="outline-secondary" @click="resetFilters">
                      <i class="ri-refresh-line"></i>
                      Réinitialiser
                    </BButton>
                  </div>
                </BFormGroup>
              </BCol>
            </BRow>

            <!-- Students Table -->
            <div class="table-responsive">
              <BTable
                :items="students"
                :fields="tableFields"
                :busy="loading"
                responsive
                class="table-nowrap align-middle"
                :per-page="0"
              >
                <template #table-busy>
                  <div class="text-center my-2">
                    <BSpinner class="align-middle me-2"></BSpinner>
                    <strong>Chargement...</strong>
                  </div>
                </template>

                <template #cell(student_code)="{ item }">
                  <strong class="text-primary">{{ item.student_code }}</strong>
                </template>

                <template #cell(full_name)="{ item }">
                  <div class="d-flex align-items-center">
                    <div class="avatar-xs me-2">
                      <img
                        v-if="item.student_photo"
                        :src="`/assets/images/students/${item.student_photo}`"
                        :alt="`${item.first_name} ${item.last_name}`"
                        class="avatar-title rounded-circle"
                        style="width: 100%; height: 100%; object-fit: cover;"
                      />
                      <div v-else class="avatar-title rounded-circle bg-light text-primary">
                        {{ item.first_name.charAt(0) }}{{ item.last_name.charAt(0) }}
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-0">{{ item.first_name }} {{ item.last_name }}</h6>
                      <small class="text-muted">{{ item.gender === 'male' ? 'Garçon' : 'Fille' }}</small>
                    </div>
                  </div>
                </template>

                <template #cell(age)="{ item }">
                  <span v-if="item.age">{{ item.age }} ans</span>
                  <span v-else class="text-muted">-</span>
                </template>

                <template #cell(class)="{ item }">
                  <div v-if="item.class">
                    <BBadge variant="info">{{ item.class.full_name }}</BBadge>
                    <div class="text-muted small">{{ item.class.level.school.name }}</div>
                  </div>
                  <span v-else class="text-muted">Non assigné</span>
                </template>

                <template #cell(father_contact)="{ item }">
                  <div>
                    <div>{{ item.father_first_name }} {{ item.father_last_name }}</div>
                    <small class="text-muted">{{ item.father_phone }}</small>
                  </div>
                </template>

                <template #cell(status)="{ item }">
                  <BBadge :variant="item.status_color" class="text-uppercase">
                    {{ item.status_label }}
                  </BBadge>
                </template>

                <template #cell(enrollment_date)="{ item }">
                  {{ formatDate(item.enrollment_date) }}
                </template>

                <template #cell(actions)="{ item }">
                  <div class="d-flex gap-1">
                    <BButton 
                      size="sm" 
                      variant="outline-primary"
                      @click="viewStudent(item)"
                      v-b-tooltip.hover 
                      title="Voir les détails"
                    >
                      <i class="ri-eye-line"></i>
                    </BButton>
                    <BButton 
                      size="sm" 
                      variant="outline-secondary"
                      @click="editStudent(item)"
                      v-b-tooltip.hover 
                      title="Modifier l'élève"
                    >
                      <i class="ri-edit-line"></i>
                    </BButton>
                    <BButton 
                      size="sm" 
                      variant="outline-danger"
                      @click="deleteStudent(item)"
                      v-b-tooltip.hover 
                      title="Supprimer l'élève"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </BButton>
                  </div>
                </template>
              </BTable>
            </div>

            <!-- Pagination -->
            <BRow class="mt-3" v-if="pagination.last_page > 1">
              <BCol cols="12">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="text-muted">
                    Affichage de {{ pagination.from }} à {{ pagination.to }} 
                    sur {{ pagination.total }} élèves
                  </div>
                  <BPagination
                    v-model="pagination.current_page"
                    :total-rows="pagination.total"
                    :per-page="pagination.per_page"
                    @page-click="loadStudents"
                    class="mb-0"
                  />
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

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
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

// Table configuration
const tableFields = [
  { key: 'student_code', label: 'Code Élève', sortable: true },
  { key: 'full_name', label: 'Élève', sortable: true },
  { key: 'age', label: 'Âge', sortable: true },
  { key: 'class', label: 'Classe', sortable: false },
  { key: 'father_contact', label: 'Contact Père', sortable: false },
  { key: 'status', label: 'Statut', sortable: true },
  { key: 'enrollment_date', label: 'Date Inscription', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Computed
const statusOptions = computed(() => [
  { value: '', text: 'Tous les statuts' },
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

// Methods
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
  } catch (error) {
    console.error('❌ Error loading students:', error)
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