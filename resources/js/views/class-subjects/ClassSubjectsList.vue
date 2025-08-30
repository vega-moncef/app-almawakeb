<template>
  <VerticalLayout>
    <PageTitle title="Matières par Classe" subtitle="Attribution des Matières" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Class Selection -->
            <BRow class="mb-3">
              <BCol md="4">
                <label class="form-label">Sélectionner une classe</label>
                <BFormSelect
                  v-model="selectedClassId"
                  :options="classOptions"
                  @change="loadClassSubjects"
                />
              </BCol>
              <BCol md="8" class="d-flex align-items-end">
                <BButton 
                  variant="primary" 
                  @click="openAssignModal"
                  :disabled="!selectedClassId"
                  class="me-2"
                >
                  <i class="ri-add-line me-1"></i> Assigner Matière
                </BButton>
                <BButton 
                  variant="success" 
                  @click="bulkAssignModal"
                  :disabled="!selectedClassId"
                >
                  <i class="ri-stack-line me-1"></i> Assignment en Lot
                </BButton>
              </BCol>
            </BRow>

            <div v-if="loading" class="text-center my-4">
              <BSpinner class="align-middle"></BSpinner>
              <p class="mt-2">Chargement...</p>
            </div>

            <div v-else-if="selectedClassId">
              <!-- Class Info -->
              <div class="mb-4 p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5 class="mb-1">{{ classInfo?.name }}</h5>
                    <p class="mb-0 text-muted">{{ classInfo?.level?.name }} - {{ classInfo?.academic_year?.name }}</p>
                  </div>
                  <div class="text-end">
                    <div class="class-stats">
                      <div><strong>{{ assignedSubjects.length }}</strong> matières assignées</div>
                      <div><strong>{{ totalHoursPerWeek }}</strong> heures/semaine</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Assigned Subjects Table -->
              <div class="table-responsive">
                <BTable
                  :items="assignedSubjects"
                  :fields="tableFields"
                  class="align-middle table-nowrap"
                  responsive
                >
                  <template #cell(subject)="{ item }">
                    <div>
                      <h6 class="mb-0">{{ item.name }}</h6>
                      <small class="text-muted">{{ item.code }}</small>
                    </div>
                  </template>

                  <template #cell(hours_per_week)="{ item }">
                    <BBadge 
                      :variant="item.pivot?.hours_per_week ? 'info' : 'secondary'"
                      pill
                    >
                      {{ item.pivot?.hours_per_week || 'Non défini' }}{{ item.pivot?.hours_per_week ? 'h/sem' : '' }}
                    </BBadge>
                  </template>

                  <template #cell(status)="{ item }">
                    <BBadge 
                      :variant="item.pivot?.is_active ? 'success' : 'danger'"
                    >
                      {{ item.pivot?.is_active ? 'Active' : 'Inactive' }}
                    </BBadge>
                  </template>

                  <template #cell(actions)="{ item }">
                    <div class="d-flex gap-2">
                      <BButton
                        variant="soft-success"
                        size="sm"
                        @click="editSubjectDetails(item)"
                      >
                        <i class="ri-pencil-line"></i>
                      </BButton>
                      <BButton
                        variant="soft-danger"
                        size="sm"
                        @click="confirmRemoveSubject(item.id)"
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

              <div v-if="assignedSubjects.length === 0" class="text-center my-5">
                <i class="ri-book-line" style="font-size: 4rem; color: #dee2e6;"></i>
                <h5 class="mt-3 text-muted">Aucune matière assignée</h5>
                <p class="text-muted">Commencez par assigner des matières à cette classe</p>
              </div>
            </div>

            <div v-else class="text-center my-5">
              <i class="ri-school-line" style="font-size: 4rem; color: #dee2e6;"></i>
              <h5 class="mt-3 text-muted">Sélectionnez une classe</h5>
              <p class="text-muted">Choisissez une classe pour voir ses matières assignées</p>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Assign Subject Modal -->
    <SubjectAssignModal
      v-model:show="showAssignModal"
      :class-id="selectedClassId"
      @saved="loadClassSubjects"
    />

    <!-- Edit Subject Details Modal -->
    <SubjectDetailsModal
      v-model:show="showDetailsModal"
      :class-id="selectedClassId"
      :subject="editingSubject"
      @saved="loadClassSubjects"
    />
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import SubjectAssignModal from './components/SubjectAssignModal.vue'
import SubjectDetailsModal from './components/SubjectDetailsModal.vue'
import axios from 'axios'

// State
const loading = ref(false)
const selectedClassId = ref(null)
const classInfo = ref(null)
const assignedSubjects = ref([])
const classes = ref([])
const showAssignModal = ref(false)
const showDetailsModal = ref(false)
const editingSubject = ref(null)

// Computed
const classOptions = computed(() => [
  { value: null, text: 'Sélectionner une classe' },
  ...classes.value.map(cls => ({ 
    value: cls.id, 
    text: `${cls.name} - ${cls.level?.name}` 
  }))
])

const totalHoursPerWeek = computed(() => {
  return assignedSubjects.value.reduce((total, subject) => {
    return total + (subject.pivot?.hours_per_week || 0)
  }, 0)
})

const tableFields = [
  { key: 'subject', label: 'Matière', sortable: true },
  { key: 'hours_per_week', label: 'Heures/Semaine', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'status', label: 'Statut', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Methods
const fetchClasses = async () => {
  try {
    const response = await axios.get('/api/class-subjects')
    classes.value = response.data.classes?.data || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des classes')
    console.error('Error fetching classes:', error)
  }
}

const loadClassSubjects = async () => {
  if (!selectedClassId.value) {
    classInfo.value = null
    assignedSubjects.value = []
    return
  }
  
  loading.value = true
  try {
    const response = await axios.get(`/api/class-subjects/${selectedClassId.value}`)
    
    classInfo.value = response.data.class
    assignedSubjects.value = response.data.class.subjects || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des matières')
    console.error('Error fetching class subjects:', error)
  } finally {
    loading.value = false
  }
}

const openAssignModal = () => {
  showAssignModal.value = true
}

const bulkAssignModal = () => {
  useToast().info('Fonction d\'assignment en lot en cours de développement')
}

const editSubjectDetails = (subject) => {
  editingSubject.value = subject
  showDetailsModal.value = true
}

const confirmRemoveSubject = async (subjectId: number) => {
  if (confirm('Êtes-vous sûr de vouloir retirer cette matière de la classe ?')) {
    try {
      await axios.delete('/api/class-subjects/remove', {
        data: {
          class_id: selectedClassId.value,
          subject_id: subjectId
        }
      })
      
      useToast().success('Matière retirée avec succès')
      await loadClassSubjects()
    } catch (error: any) {
      if (error.response?.status === 422) {
        useToast().error(error.response.data.message)
      } else {
        useToast().error('Erreur lors de la suppression')
      }
      console.error('Error removing subject:', error)
    }
  }
}

// Lifecycle
onMounted(() => {
  fetchClasses()
})
</script>

<style scoped>
.class-stats {
  text-align: center;
  font-size: 0.9rem;
}

.class-stats div {
  margin-bottom: 5px;
}
</style>