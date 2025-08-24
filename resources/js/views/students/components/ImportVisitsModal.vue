<template>
  <BModal
    :model-value="visible"
    @update:model-value="$emit('close')"
    title="Importer des élèves depuis les visites acceptées"
    size="lg"
    hide-footer
    scrollable
  >
    <div v-if="loading" class="text-center py-4">
      <BSpinner class="me-2"></BSpinner>
      <span>Chargement des visites acceptées...</span>
    </div>

    <div v-else-if="!acceptedVisits.length" class="text-center py-5">
      <div class="avatar-md mx-auto mb-3">
        <div class="avatar-title bg-light text-muted rounded-circle fs-2">
          <i class="ri-user-search-line"></i>
        </div>
      </div>
      <h6 class="mb-1">Aucune visite acceptée</h6>
      <p class="text-muted mb-0">
        Il n'y a aucune visite acceptée à importer pour cette année académique.
      </p>
    </div>

    <div v-else>
      <!-- Selection Actions -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <span class="text-muted">
            {{ selectedVisits.length }} visite(s) sélectionnée(s) sur {{ acceptedVisits.length }}
          </span>
        </div>
        <div class="d-flex gap-2">
          <BButton 
            size="sm" 
            variant="outline-secondary"
            @click="selectAll"
            :disabled="selectedVisits.length === acceptedVisits.length"
          >
            Tout sélectionner
          </BButton>
          <BButton 
            size="sm" 
            variant="outline-secondary"
            @click="selectNone"
            :disabled="!selectedVisits.length"
          >
            Tout désélectionner
          </BButton>
        </div>
      </div>

      <!-- Visits List -->
      <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <BTable
          :items="acceptedVisits"
          :fields="tableFields"
          small
          hover
          class="mb-0"
        >
          <template #cell(select)="{ item }">
            <BFormCheckbox
              :model-value="selectedVisits.includes(item.id)"
              @update:model-value="toggleVisit(item.id)"
            />
          </template>

          <template #cell(student_name)="{ item }">
            <div>
              <h6 class="mb-0 fs-6">{{ item.student_first_name }} {{ item.student_last_name }}</h6>
              <small class="text-muted">{{ item.gender === 'male' ? 'Garçon' : 'Fille' }}</small>
            </div>
          </template>

          <template #cell(age)="{ item }">
            <span v-if="item.birth_date">
              {{ calculateAge(item.birth_date) }} ans
            </span>
            <span v-else class="text-muted">-</span>
          </template>

          <template #cell(father_contact)="{ item }">
            <div>
              <div class="fw-medium">{{ item.father_first_name }} {{ item.father_last_name }}</div>
              <small class="text-muted">{{ item.father_phone }}</small>
            </div>
          </template>

          <template #cell(requested_level)="{ item }">
            <div v-if="item.requested_level">
              <BBadge variant="info">{{ item.requested_level.name }}</BBadge>
              <div class="text-muted small">{{ item.requested_school.name }}</div>
            </div>
            <span v-else class="text-muted">-</span>
          </template>

          <template #cell(visit_date)="{ item }">
            {{ formatDate(item.visit_date) }}
          </template>
        </BTable>
      </div>

      <!-- Class Assignment -->
      <div v-if="selectedVisits.length" class="mt-4 pt-3 border-top">
        <BRow class="align-items-end">
          <BCol md="8">
            <BFormGroup
              label="Assigner à une classe (optionnel)"
              label-for="class-assignment"
              description="Si vous laissez vide, les élèves seront créés sans classe assignée"
            >
              <BFormSelect
                id="class-assignment"
                v-model="selectedClass"
                :options="classOptions"
              />
            </BFormGroup>
          </BCol>
          <BCol md="4">
            <BButton
              variant="primary"
              @click="importStudents"
              :disabled="isImporting"
              class="w-100"
            >
              <BSpinner v-if="isImporting" small class="me-2" />
              Importer {{ selectedVisits.length }} élève(s)
            </BButton>
          </BCol>
        </BRow>
      </div>
    </div>

    <!-- Modal Actions -->
    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
      <BButton variant="secondary" @click="$emit('close')">
        Fermer
      </BButton>
    </div>
  </BModal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAcademicYearStore } from '@/stores/academicYear'
// Remove import - we'll define formatDate locally
import { showToast } from '@/helpers/toast'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'imported'])

const academicYearStore = useAcademicYearStore()

// Reactive data
const loading = ref(false)
const isImporting = ref(false)
const acceptedVisits = ref([])
const selectedVisits = ref([])
const selectedClass = ref('')
const classes = ref([])

// Table configuration
const tableFields = [
  { key: 'select', label: '', thClass: 'text-center', tdClass: 'text-center' },
  { key: 'student_name', label: 'Élève', sortable: true },
  { key: 'age', label: 'Âge', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'father_contact', label: 'Contact Père', sortable: false },
  { key: 'requested_level', label: 'Niveau Demandé', sortable: false },
  { key: 'visit_date', label: 'Date Visite', sortable: true }
]

// Computed
const classOptions = computed(() => [
  { value: '', text: 'Aucune classe (à assigner plus tard)' },
  ...classes.value.map(cls => ({
    value: cls.id,
    text: cls.full_name
  }))
])

// Methods
const loadAcceptedVisits = async () => {
  if (!academicYearStore.currentAcademicYear?.id) return
  
  loading.value = true
  try {
    const { data } = await axios.get('/api/students/accepted-visits', {
      params: { academic_year_id: academicYearStore.currentAcademicYear.id }
    })
    acceptedVisits.value = data
  } catch (error) {
    console.error('Error loading accepted visits:', error)
    showToast('Erreur lors du chargement des visites acceptées', 'error')
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

const toggleVisit = (visitId) => {
  const index = selectedVisits.value.indexOf(visitId)
  if (index > -1) {
    selectedVisits.value.splice(index, 1)
  } else {
    selectedVisits.value.push(visitId)
  }
}

const selectAll = () => {
  selectedVisits.value = acceptedVisits.value.map(visit => visit.id)
}

const selectNone = () => {
  selectedVisits.value = []
}

const calculateAge = (birthDate) => {
  if (!birthDate) return null
  const today = new Date()
  const birth = new Date(birthDate)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  
  return age
}

const importStudents = async () => {
  if (!selectedVisits.value.length) return
  
  isImporting.value = true
  try {
    const promises = selectedVisits.value.map(visitId => 
      axios.post('/api/students/create-from-visit', {
        student_visit_id: visitId,
        class_id: selectedClass.value || null
      })
    )
    
    await Promise.all(promises)
    
    emit('imported')
    showToast(`${selectedVisits.value.length} élève(s) importé(s) avec succès`, 'success')
  } catch (error) {
    console.error('Error importing students:', error)
    
    if (error.response?.status === 422) {
      const message = error.response.data.message || 'Erreur lors de l\'importation'
      showToast(message, 'error')
    } else {
      showToast('Erreur lors de l\'importation des élèves', 'error')
    }
  } finally {
    isImporting.value = false
  }
}

const resetModal = () => {
  selectedVisits.value = []
  selectedClass.value = ''
  acceptedVisits.value = []
}

// Watchers
watch(() => props.visible, (visible) => {
  if (visible) {
    loadAcceptedVisits()
    loadClasses()
  } else {
    resetModal()
  }
})

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
  
  if (props.visible) {
    await loadAcceptedVisits()
    await loadClasses()
  }
})
</script>

<style scoped>
.table-responsive {
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
}

.table th {
  background-color: #f8f9fa;
  border-top: none;
  font-size: 0.875rem;
  font-weight: 600;
}

.table td {
  vertical-align: middle;
  font-size: 0.875rem;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
}
</style>