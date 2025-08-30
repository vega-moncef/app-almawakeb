<template>
  <VerticalLayout>
    <PageTitle title="Gestion des Emplois du Temps" subtitle="Emplois du Temps" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Filters -->
            <BRow class="mb-3">
              <BCol md="2">
                <div class="search-box position-relative">
                  <BFormInput
                    v-model="filters.search"
                    placeholder="Rechercher..."
                    class="form-control search"
                    @input="handleSearch"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>
              </BCol>
              <BCol md="2">
                <BFormSelect
                  v-model="selectedAcademicYear"
                  :options="academicYearOptions"
                  @change="handleFilterChange"
                />
              </BCol>
              <BCol md="2">
                <BFormSelect
                  v-model="filters.class_id"
                  :options="classOptions"
                  @change="handleFilterChange"
                />
              </BCol>
              <BCol md="2">
                <BFormSelect
                  v-model="filters.teacher_id"
                  :options="teacherOptions"
                  @change="handleFilterChange"
                />
              </BCol>
              <BCol md="1">
                <BFormSelect
                  v-model="filters.day_of_week"
                  :options="dayOptions"
                  @change="handleFilterChange"
                />
              </BCol>
              <BCol md="3" class="text-end">
                <BButton variant="success" @click="viewClassSchedule" class="me-2">
                  <i class="ri-calendar-line align-bottom me-1"></i> Planning Classe
                </BButton>
                <BButton variant="primary" @click="addTimetableEntry">
                  <i class="ri-add-line align-bottom me-1"></i> Ajouter
                </BButton>
              </BCol>
            </BRow>

            <!-- Timetable Entries Table -->
            <div class="table-responsive">
              <BTable
                :items="timetables"
                :fields="tableFields"
                :busy="loading"
                class="align-middle table-nowrap"
                responsive
              >
                <template #cell(class)="{ item }">
                  <div>
                    <h6 class="mb-0">{{ item.class_room?.name }}</h6>
                    <small class="text-muted">{{ item.class_room?.level?.name }}</small>
                  </div>
                </template>

                <template #cell(teacher)="{ item }">
                  <div>
                    <h6 class="mb-0">{{ item.teacher?.full_name }}</h6>
                    <small class="text-muted">{{ item.teacher?.email }}</small>
                  </div>
                </template>

                <template #cell(subject)="{ item }">
                  <BBadge variant="info" pill>
                    {{ item.subject?.name }}
                  </BBadge>
                </template>

                <template #cell(schedule)="{ item }">
                  <div>
                    <strong>{{ item.day_name }}</strong><br>
                    <small>{{ item.time_slot?.display_time }}</small>
                  </div>
                </template>

                <template #cell(status)="{ item }">
                  <BBadge :variant="item.is_active ? 'success' : 'danger'">
                    {{ item.is_active ? 'Actif' : 'Inactif' }}
                  </BBadge>
                </template>

                <template #cell(actions)="{ item }">
                  <div class="d-flex gap-2">
                    <BButton
                      variant="soft-success"
                      size="sm"
                      @click="editTimetableEntry(item.id)"
                    >
                      <i class="ri-pencil-line"></i>
                    </BButton>
                    <BButton
                      variant="soft-danger"
                      size="sm"
                      @click="confirmDelete(item.id)"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </BButton>
                  </div>
                </template>

                <template #table-busy>
                  <div class="text-center my-2">
                    <BSpinner class="align-middle"></BSpinner>
                    <strong>Chargement...</strong>
                  </div>
                </template>
              </BTable>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3" v-if="pagination.total > pagination.per_page">
              <nav>
                <BPagination
                  v-model="pagination.current_page"
                  :total-rows="pagination.total"
                  :per-page="pagination.per_page"
                  @change="handlePageChange"
                ></BPagination>
              </nav>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Create/Edit Modal -->
    <TimetableFormModal
      v-model:show="showCreateModal"
      :timetable-id="editingTimetableId"
      @saved="handleTimetableSaved"
    />

    <!-- Class Schedule Modal -->
    <ClassScheduleModal
      v-model:show="showClassScheduleModal"
      :class-id="selectedClassId"
    />
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import TimetableFormModal from './components/TimetableFormModal.vue'
import ClassScheduleModal from './components/ClassScheduleModal.vue'
import axios from 'axios'

// State
const timetables = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const showClassScheduleModal = ref(false)
const editingTimetableId = ref(null)
const selectedClassId = ref(null)

const selectedAcademicYear = ref('')

const filters = reactive({
  search: '',
  class_id: '',
  teacher_id: '',
  day_of_week: ''
})

const pagination = reactive({
  current_page: 1,
  total: 0,
  per_page: 50,
  last_page: 1
})

// Options for filters
const classes = ref([])
const teachers = ref([])
const days = ref({})
const academicYears = ref([])
const currentAcademicYear = ref('')

// Computed
const classOptions = computed(() => [
  { value: '', text: 'Toutes les classes' },
  ...classes.value.map(cls => ({ value: cls.id, text: `${cls.name} - ${cls.level?.name}` }))
])

const teacherOptions = computed(() => [
  { value: '', text: 'Tous les enseignants' },
  ...teachers.value.map(teacher => ({ value: teacher.id, text: teacher.full_name }))
])

const dayOptions = computed(() => [
  { value: '', text: 'Tous les jours' },
  ...Object.entries(days.value).map(([key, value]) => ({ value: key, text: value }))
])

const academicYearOptions = computed(() => [
  { value: '', text: 'Année courante' },
  ...academicYears.value.map(year => ({ 
    value: year.id.toString(), 
    text: year.name 
  }))
])

const tableFields = [
  { key: 'class', label: 'Classe', sortable: true },
  { key: 'teacher', label: 'Enseignant', sortable: true },
  { key: 'subject', label: 'Matière', sortable: true },
  { key: 'schedule', label: 'Horaire', sortable: false },
  { key: 'status', label: 'Statut', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Methods
const fetchTimetables = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      ...filters
    }

    if (selectedAcademicYear.value) {
      params.academic_year_id = selectedAcademicYear.value
    }
    
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    const response = await axios.get('/api/timetables', { params })
    timetables.value = response.data.timetables.data
    days.value = response.data.days
    
    // Update pagination
    Object.assign(pagination, {
      current_page: response.data.timetables.current_page,
      total: response.data.timetables.total,
      per_page: response.data.timetables.per_page,
      last_page: response.data.timetables.last_page
    })
  } catch (error) {
    useToast().error('Erreur lors du chargement des emplois du temps')
    console.error('Error fetching timetables:', error)
  } finally {
    loading.value = false
  }
}

const fetchFormData = async () => {
  try {
    const response = await axios.get('/api/timetables/create')
    classes.value = response.data.classes || []
    teachers.value = response.data.teachers || []
    days.value = response.data.days || {}
    academicYears.value = response.data.academicYears || []
    currentAcademicYear.value = response.data.academicYear?.name || '2024-2025'
  } catch (error) {
    console.error('Error fetching form data:', error)
  }
}

const handleSearch = debounce(() => {
  pagination.current_page = 1
  fetchTimetables()
}, 300)

const handleFilterChange = () => {
  pagination.current_page = 1
  fetchTimetables()
}

const handlePageChange = (page: number) => {
  pagination.current_page = page
  fetchTimetables()
}

const addTimetableEntry = () => {
  editingTimetableId.value = null
  showCreateModal.value = true
}

const editTimetableEntry = (id: number) => {
  editingTimetableId.value = id
  showCreateModal.value = true
}

const viewClassSchedule = () => {
  if (!filters.class_id) {
    useToast().warning('Veuillez sélectionner une classe d\'abord')
    return
  }
  selectedClassId.value = filters.class_id
  showClassScheduleModal.value = true
}

const confirmDelete = async (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette entrée d\'emploi du temps ?')) {
    try {
      await axios.delete(`/api/timetables/${id}`)
      useToast().success('Entrée supprimée avec succès')
      fetchTimetables()
    } catch (error) {
      useToast().error('Erreur lors de la suppression')
      console.error('Error deleting timetable:', error)
    }
  }
}

const handleTimetableSaved = () => {
  showCreateModal.value = false
  editingTimetableId.value = null
  fetchTimetables()
}

// Utils
function debounce(func: Function, wait: number) {
  let timeout: any
  return function executedFunction(...args: any[]) {
    const later = () => {
      clearTimeout(timeout)
      func.apply(this, args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Lifecycle
onMounted(async () => {
  await fetchFormData()
  await fetchTimetables()
})
</script>

<style scoped>
.search-box {
  position: relative;
}

.search-icon {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  color: #adb5bd;
}
</style>