<template>
  <VerticalLayout>
    <PageTitle title="Gestion des Créneaux Horaires" subtitle="Configuration des Heures de Cours" />
    
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardBody>
            <!-- Header Controls -->
            <BRow class="mb-4">
              <BCol md="2">
                <BFormSelect
                  v-model="filters.type"
                  :options="typeOptions"
                  @change="fetchTimeSlots"
                />
              </BCol>
              <BCol md="2">
                <BFormSelect
                  v-model="filters.status"
                  :options="statusOptions"
                  @change="fetchTimeSlots"
                />
              </BCol>
              <BCol md="2">
                <BFormSelect
                  v-model="filters.scope"
                  :options="scopeFilterOptions"
                  @change="fetchTimeSlots"
                />
              </BCol>
              <BCol md="6" class="text-end">
                <BButton variant="success" class="me-2" @click="addTimeSlot">
                  <i class="ri-add-line align-bottom me-1"></i>
                  Ajouter Créneau
                </BButton>
                <BButton variant="info" @click="toggleReorderMode">
                  <i class="ri-drag-move-line align-bottom me-1"></i>
                  {{ reorderMode ? 'Terminer' : 'Réorganiser' }}
                </BButton>
              </BCol>
            </BRow>

            <!-- Time Slots Table -->
            <div class="table-responsive">
              <BTable
                :items="timeSlots"
                :fields="tableFields"
                :busy="loading"
                class="align-middle table-nowrap"
                responsive
              >
                <template #cell(order)="{ item, index }">
                  <div v-if="reorderMode" class="d-flex align-items-center">
                    <BButton 
                      variant="soft-secondary" 
                      size="sm" 
                      class="me-2"
                      @click="moveUp(index)"
                      :disabled="index === 0"
                    >
                      <i class="ri-arrow-up-line"></i>
                    </BButton>
                    <span class="fw-bold">{{ item.order }}</span>
                    <BButton 
                      variant="soft-secondary" 
                      size="sm" 
                      class="ms-2"
                      @click="moveDown(index)"
                      :disabled="index === timeSlots.length - 1"
                    >
                      <i class="ri-arrow-down-line"></i>
                    </BButton>
                  </div>
                  <span v-else class="fw-bold">{{ item.order }}</span>
                </template>

                <template #cell(name)="{ item }">
                  <div>
                    <h6 class="mb-1">{{ item.name }}</h6>
                    <small class="text-muted">{{ item.display_time }}</small>
                  </div>
                </template>

                <template #cell(duration)="{ item }">
                  <BBadge variant="info" pill>
                    {{ item.duration_in_minutes }} min
                  </BBadge>
                </template>

                <template #cell(type)="{ item }">
                  <BBadge :variant="item.is_break ? 'warning' : 'primary'" pill>
                    <i :class="item.is_break ? 'ri-time-line' : 'ri-book-line'" class="me-1"></i>
                    {{ item.is_break ? 'Pause' : 'Cours' }}
                  </BBadge>
                </template>

                <template #cell(scope)="{ item }">
                  <div>
                    <BBadge 
                      :variant="getScopeVariant(item)" 
                      pill
                      class="d-block mb-1"
                    >
                      <i :class="getScopeIcon(item)" class="me-1"></i>
                      {{ getScopeText(item) }}
                    </BBadge>
                    <small v-if="getScopeDetail(item)" class="text-muted">
                      {{ getScopeDetail(item) }}
                    </small>
                  </div>
                </template>

                <template #cell(status)="{ item }">
                  <BBadge :variant="item.is_active ? 'success' : 'danger'">
                    {{ item.is_active ? 'Actif' : 'Inactif' }}
                  </BBadge>
                </template>

                <template #cell(usage)="{ item }">
                  <span class="text-muted">
                    {{ item.timetables_count || 0 }} emplois du temps
                  </span>
                </template>

                <template #cell(actions)="{ item }">
                  <div class="d-flex gap-2">
                    <BButton
                      variant="soft-success"
                      size="sm"
                      @click="editTimeSlot(item)"
                      :disabled="reorderMode"
                    >
                      <i class="ri-pencil-line"></i>
                    </BButton>
                    <BButton
                      :variant="item.is_active ? 'soft-warning' : 'soft-primary'"
                      size="sm"
                      @click="toggleStatus(item)"
                      :disabled="reorderMode"
                    >
                      <i :class="item.is_active ? 'ri-pause-line' : 'ri-play-line'"></i>
                    </BButton>
                    <BButton
                      variant="soft-danger"
                      size="sm"
                      @click="confirmDelete(item.id)"
                      :disabled="reorderMode"
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
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Create/Edit Modal -->
    <TimeSlotFormModal
      v-model:show="showFormModal"
      :time-slot="editingTimeSlot"
      @saved="handleTimeSlotSaved"
    />
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from '@/helpers/toast'
import PageTitle from '@/components/PageTitle.vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import TimeSlotFormModal from './components/TimeSlotFormModal.vue'
import axios from 'axios'

// State
const timeSlots = ref([])
const loading = ref(false)
const showFormModal = ref(false)
const editingTimeSlot = ref(null)
const reorderMode = ref(false)

const filters = reactive({
  type: '',
  status: 'active',
  scope: ''
})

// Computed
const typeOptions = [
  { value: '', text: 'Tous les types' },
  { value: 'regular', text: 'Cours uniquement' },
  { value: 'break', text: 'Pauses uniquement' }
]

const statusOptions = [
  { value: '', text: 'Tous les statuts' },
  { value: 'active', text: 'Actifs uniquement' },
  { value: 'inactive', text: 'Inactifs uniquement' }
]

const scopeFilterOptions = [
  { value: '', text: 'Toutes les portées' },
  { value: 'global', text: 'Globaux uniquement' },
  { value: 'school', text: 'École uniquement' },
  { value: 'level', text: 'Niveau uniquement' },
  { value: 'class', text: 'Classe uniquement' }
]

const tableFields = [
  { key: 'order', label: 'Ordre', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'name', label: 'Nom & Horaire', sortable: true },
  { key: 'duration', label: 'Durée', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'type', label: 'Type', sortable: false, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'scope', label: 'Portée', sortable: false, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'status', label: 'Statut', sortable: true, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'usage', label: 'Utilisation', sortable: false, thClass: 'text-center', tdClass: 'text-center' },
  { key: 'actions', label: 'Actions', sortable: false, thClass: 'text-center', tdClass: 'text-center' }
]

// Methods
const fetchTimeSlots = async () => {
  loading.value = true
  try {
    const params = {
      type: filters.type || undefined,
      status: filters.status || undefined,
      scope: filters.scope || undefined
    }
    
    // Remove undefined values
    Object.keys(params).forEach(key => {
      if (params[key] === undefined) {
        delete params[key]
      }
    })

    const response = await axios.get('/api/time-slots', { params })
    timeSlots.value = response.data.timeSlots
  } catch (error) {
    useToast().error('Erreur lors du chargement des créneaux horaires')
    console.error('Error fetching time slots:', error)
  } finally {
    loading.value = false
  }
}

const addTimeSlot = () => {
  editingTimeSlot.value = null
  showFormModal.value = true
}

const editTimeSlot = (timeSlot: any) => {
  editingTimeSlot.value = { ...timeSlot }
  showFormModal.value = true
}

const toggleStatus = async (timeSlot: any) => {
  try {
    await axios.post(`/api/time-slots/${timeSlot.id}/toggle`)
    useToast().success(`Créneau ${timeSlot.is_active ? 'désactivé' : 'activé'} avec succès`)
    fetchTimeSlots()
  } catch (error) {
    useToast().error('Erreur lors du changement de statut')
    console.error('Error toggling time slot:', error)
  }
}

const confirmDelete = async (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce créneau horaire ?')) {
    try {
      await axios.delete(`/api/time-slots/${id}`)
      useToast().success('Créneau supprimé avec succès')
      fetchTimeSlots()
    } catch (error: any) {
      const message = error.response?.data?.message || 'Erreur lors de la suppression'
      useToast().error(message)
      console.error('Error deleting time slot:', error)
    }
  }
}

const handleTimeSlotSaved = () => {
  showFormModal.value = false
  editingTimeSlot.value = null
  fetchTimeSlots()
}

const toggleReorderMode = () => {
  reorderMode.value = !reorderMode.value
  if (!reorderMode.value) {
    // Save the new order when exiting reorder mode
    saveNewOrder()
  }
}

const moveUp = (index: number) => {
  if (index > 0) {
    const temp = timeSlots.value[index]
    timeSlots.value[index] = timeSlots.value[index - 1]
    timeSlots.value[index - 1] = temp
    updateOrderValues()
  }
}

const moveDown = (index: number) => {
  if (index < timeSlots.value.length - 1) {
    const temp = timeSlots.value[index]
    timeSlots.value[index] = timeSlots.value[index + 1]
    timeSlots.value[index + 1] = temp
    updateOrderValues()
  }
}

const updateOrderValues = () => {
  timeSlots.value.forEach((slot, index) => {
    slot.order = index + 1
  })
}

const saveNewOrder = async () => {
  try {
    const reorderData = timeSlots.value.map(slot => ({
      id: slot.id,
      order: slot.order
    }))

    await axios.post('/api/time-slots/reorder', { timeSlots: reorderData })
    useToast().success('Ordre des créneaux mis à jour avec succès')
  } catch (error) {
    useToast().error('Erreur lors de la mise à jour de l\'ordre')
    console.error('Error reordering time slots:', error)
    fetchTimeSlots() // Reload to get original order
  }
}

// Scope display methods
const getScopeText = (item: any) => {
  const classRoom = item.class_room || item.classRoom
  
  if (classRoom) {
    return 'Classe'
  } else if (item.level_id) {
    return 'Niveau'
  } else if (item.school_id) {
    return 'École'
  } else {
    return 'Global'
  }
}

const getScopeDetail = (item: any) => {
  // Check both snake_case and camelCase for relationships
  const classRoom = item.class_room || item.classRoom
  const level = item.level
  const school = item.school
  
  if (classRoom && classRoom.name) {
    const levelName = classRoom.level?.name || ''
    return levelName ? `${levelName} ${classRoom.name}` : classRoom.name
  } else if (item.level_id) {
    if (level && level.name) {
      return level.name
    } else {
      return `Niveau ${item.level_id}`
    }
  } else if (item.school_id) {
    if (school && school.name) {
      return school.name
    } else {
      return `École ${item.school_id}`
    }
  }
  
  return null
}

const getScopeVariant = (item: any) => {
  const classRoom = item.class_room || item.classRoom
  
  if (classRoom) {
    return 'info'
  } else if (item.level_id) {
    return 'secondary'
  } else if (item.school_id) {
    return 'dark'
  } else {
    return 'light'
  }
}

const getScopeIcon = (item: any) => {
  const classRoom = item.class_room || item.classRoom
  
  if (classRoom) {
    return 'ri-group-line'
  } else if (item.level_id) {
    return 'ri-stack-line'
  } else if (item.school_id) {
    return 'ri-building-line'
  } else {
    return 'ri-global-line'
  }
}

// Lifecycle
onMounted(() => {
  fetchTimeSlots()
})
</script>

<style scoped>
.table-responsive {
  min-height: 400px;
}

.reorder-handle {
  cursor: grab;
}

.reorder-handle:active {
  cursor: grabbing;
}

.reorder-highlight {
  background-color: #f8f9fa;
  border: 2px dashed #007bff;
}
</style>