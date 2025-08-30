<template>
    <VerticalLayout>

  <PageTitle title="Gestion des Enseignants" subtitle="Enseignants" />
  
  <BRow>
    <BCol lg="12">
      <BCard no-body>
        <BCardBody>
          <!-- Search and Filters -->
          <BRow class="mb-3">
            <BCol md="4">
              <div class="search-box position-relative">
                <BFormInput
                  v-model="filters.search"
                  placeholder="Rechercher par nom, email..."
                  class="form-control search"
                  @input="handleSearch"
                />
                <i class="ri-search-line search-icon"></i>
              </div>
            </BCol>
            <BCol md="2">
              <BFormSelect
                v-model="filters.type"
                :options="typeOptions"
                @change="handleFilterChange"
              />
            </BCol>
            <BCol md="2">
              <BFormInput
                v-model="filters.specialty"
                placeholder="Spécialité principale"
                @input="handleFilterChange"
              />
            </BCol>
            <BCol md="2">
              <BFormSelect
                v-model="filters.active"
                :options="activeOptions"
                @change="handleFilterChange"
              />
            </BCol>
            <BCol md="2" class="text-end">
              <BButton variant="primary" @click="addTeacher">
                <i class="ri-add-line align-bottom me-1"></i> Ajouter
              </BButton>
            </BCol>
          </BRow>

          <!-- Teachers Table -->
          <div class="table-responsive">
            <BTable
              :items="teachers"
              :fields="tableFields"
              :busy="loading"
              class="align-middle table-nowrap"
              responsive
            >
              <template #cell(photo)="{ item }">
                <div class="d-flex align-items-center">
                  <img
                    :src="item.photo_url || '/assets/images/users/dummy-avatar.jpg'"
                    alt=""
                    class="avatar-sm rounded-circle me-2"
                  />
                </div>
              </template>

              <template #cell(name)="{ item }">
                <div>
                  <h6 class="mb-0">{{ item.full_name }}</h6>
                  <small class="text-muted">{{ item.email }}</small>
                </div>
              </template>

              <template #cell(type)="{ item }">
                <BBadge :variant="item.type === 'permanent' ? 'success' : 'warning'">
                  {{ item.type_display }}
                </BBadge>
              </template>

              <template #cell(main_subject)="{ item }">
                <span v-if="item.main_subject">
                  {{ item.main_subject.name }}
                </span>
                <span v-else class="text-muted">-</span>
              </template>

              <template #cell(subjects)="{ item }">
                <div class="d-flex flex-wrap gap-1">
                  <BBadge
                    v-for="subject in item.subjects"
                    :key="subject.id"
                    variant="info"
                    pill
                  >
                    {{ subject.name }}
                  </BBadge>
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
                    variant="soft-primary"
                    size="sm"
                    @click="viewTeacher(item.id)"
                  >
                    <i class="ri-eye-line"></i>
                  </BButton>
                  <BButton
                    variant="soft-success"
                    size="sm"
                    @click="editTeacher(item.id)"
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
  <TeacherFormModal
    v-model:show="showCreateModal"
    :teacher-id="editingTeacherId"
    @saved="handleTeacherSaved"
  />

  <!-- View Modal -->
  <TeacherViewModal
    v-model:show="showViewModal"
    :teacher-id="viewingTeacherId"
  />
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import TeacherFormModal from './components/TeacherFormModal.vue'
import TeacherViewModal from './components/TeacherViewModal.vue'
import axios from 'axios'

// State
const teachers = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const showViewModal = ref(false)
const editingTeacherId = ref(null)
const viewingTeacherId = ref(null)

const filters = reactive({
  search: '',
  type: '',
  specialty: '',
  active: ''
})

const pagination = reactive({
  current_page: 1,
  total: 0,
  per_page: 15,
  last_page: 1
})

// Computed
const typeOptions = computed(() => [
  { value: '', text: 'Tous les types' },
  { value: 'permanent', text: 'Permanent' },
  { value: 'vacataire', text: 'Vacataire' }
])

const activeOptions = computed(() => [
  { value: '', text: 'Tous' },
  { value: '1', text: 'Actifs' },
  { value: '0', text: 'Inactifs' }
])

const tableFields = [
  { key: 'photo', label: '', sortable: false, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'name', label: 'Nom & Email', sortable: true },
  { key: 'type', label: 'Type', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'main_subject', label: 'Spécialité principale', sortable: true },
  { key: 'subjects', label: 'Matières', sortable: false },
  { key: 'experience_years', label: 'Expérience', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'status', label: 'Statut', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Methods
const fetchTeachers = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      ...filters
    }
    
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    const response = await axios.get('/api/teachers', { params })
    teachers.value = response.data.teachers.data
    
    // Update pagination
    Object.assign(pagination, {
      current_page: response.data.teachers.current_page,
      total: response.data.teachers.total,
      per_page: response.data.teachers.per_page,
      last_page: response.data.teachers.last_page
    })
  } catch (error) {
    useToast().error('Erreur lors du chargement des enseignants')
    console.error('Error fetching teachers:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = debounce(() => {
  pagination.current_page = 1
  fetchTeachers()
}, 300)

const handleFilterChange = () => {
  pagination.current_page = 1
  fetchTeachers()
}

const handlePageChange = (page: number) => {
  pagination.current_page = page
  fetchTeachers()
}

const viewTeacher = (id: number) => {
  viewingTeacherId.value = id
  showViewModal.value = true
}

const addTeacher = () => {
  editingTeacherId.value = null
  showCreateModal.value = true
}

const editTeacher = (id: number) => {
  editingTeacherId.value = id
  showCreateModal.value = true
}

const confirmDelete = async (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet enseignant ?')) {
    try {
      await axios.delete(`/api/teachers/${id}`)
      useToast().success('Enseignant supprimé avec succès')
      fetchTeachers()
    } catch (error) {
      useToast().error('Erreur lors de la suppression')
      console.error('Error deleting teacher:', error)
    }
  }
}

const handleTeacherSaved = () => {
  showCreateModal.value = false
  editingTeacherId.value = null
  fetchTeachers()
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
onMounted(() => {
  fetchTeachers()
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

.avatar-sm {
  height: 2rem;
  width: 2rem;
}
</style>