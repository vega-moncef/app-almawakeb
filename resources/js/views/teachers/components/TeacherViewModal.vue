<template>
  <BModal
    v-model="localShow"
    title="Détails de l'enseignant"
    size="lg"
    hide-footer
  >
    <div v-if="loading" class="text-center py-4">
      <BSpinner class="me-2" />
      <span>Chargement...</span>
    </div>

    <div v-else-if="teacher" class="teacher-details">
      <!-- Header with photo and basic info -->
      <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
        <img
          :src="teacher.photo_url || '/assets/images/users/dummy-avatar.jpg'"
          alt="Profile"
          class="avatar-lg rounded-circle me-3"
        />
        <div>
          <h4 class="mb-1">{{ teacher.full_name }}</h4>
          <p class="text-muted mb-1">{{ teacher.email }}</p>
          <div class="d-flex gap-2">
            <BBadge :variant="teacher.type === 'permanent' ? 'success' : 'warning'">
              {{ teacher.type_display }}
            </BBadge>
            <BBadge :variant="teacher.is_active ? 'success' : 'danger'">
              {{ teacher.is_active ? 'Actif' : 'Inactif' }}
            </BBadge>
          </div>
        </div>
      </div>

      <!-- Personal Information -->
      <div class="mb-4">
        <h5 class="mb-3">
          <i class="ri-user-line me-2"></i>
          Informations personnelles
        </h5>
        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label text-muted">Téléphone</label>
              <p class="mb-0">{{ teacher.phone || '-' }}</p>
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label text-muted">Date de naissance</label>
              <p class="mb-0">{{ formatDate(teacher.date_of_birth) || '-' }}</p>
            </div>
          </BCol>
          <BCol cols="12">
            <div class="mb-3">
              <label class="form-label text-muted">Adresse</label>
              <p class="mb-0">{{ teacher.address || '-' }}</p>
            </div>
          </BCol>
        </BRow>
      </div>

      <!-- Professional Information -->
      <div class="mb-4">
        <h5 class="mb-3">
          <i class="ri-briefcase-line me-2"></i>
          Informations professionnelles
        </h5>
        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label text-muted">Spécialité</label>
              <p class="mb-0">{{ teacher.specialty || '-' }}</p>
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label text-muted">Date d'embauche</label>
              <p class="mb-0">{{ formatDate(teacher.hire_date) || '-' }}</p>
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label text-muted">Années d'expérience</label>
              <p class="mb-0">{{ teacher.experience_years || 0 }} ans</p>
            </div>
          </BCol>
          <BCol cols="12">
            <div class="mb-3">
              <label class="form-label text-muted">Qualifications</label>
              <p class="mb-0">{{ teacher.qualification || '-' }}</p>
            </div>
          </BCol>
        </BRow>
      </div>

      <!-- Subjects -->
      <div class="mb-4">
        <h5 class="mb-3">
          <i class="ri-book-line me-2"></i>
          Matières enseignées
        </h5>
        <div v-if="teacher.subjects && teacher.subjects.length > 0" class="d-flex flex-wrap gap-2">
          <BBadge
            v-for="subject in teacher.subjects"
            :key="subject.id"
            variant="info"
            pill
          >
            {{ subject.name }}
          </BBadge>
        </div>
        <p v-else class="text-muted">Aucune matière assignée</p>
      </div>

      <!-- Timetable -->
      <div class="mb-4" v-if="teacher.timetables && teacher.timetables.length > 0">
        <h5 class="mb-3">
          <i class="ri-calendar-line me-2"></i>
          Emploi du temps
        </h5>
        <div class="table-responsive">
          <BTable
            :items="teacher.timetables"
            :fields="timetableFields"
            small
            class="mb-0"
          >
            <template #cell(day_name)="{ item }">
              <strong>{{ item.day_name }}</strong>
            </template>
            
            <template #cell(time_slot)="{ item }">
              {{ item.time_slot?.display_time || '-' }}
            </template>
            
            <template #cell(subject)="{ item }">
              <BBadge variant="primary">{{ item.subject?.name || '-' }}</BBadge>
            </template>
            
            <template #cell(class)="{ item }">
              {{ item.class_room?.name || '-' }}
            </template>
          </BTable>
        </div>
      </div>
      
      <div v-else class="mb-4">
        <h5 class="mb-3">
          <i class="ri-calendar-line me-2"></i>
          Emploi du temps
        </h5>
        <p class="text-muted">Aucun cours programmé</p>
      </div>
    </div>

    <template #modal-footer>
      <BButton variant="secondary" @click="localShow = false">
        Fermer
      </BButton>
    </template>
  </BModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useToast } from '@/helpers/toast'
import axios from 'axios'

// Props
interface Props {
  show: boolean
  teacherId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  teacherId: null
})

// Emits
interface Emits {
  (e: 'update:show', value: boolean): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const teacher = ref(null)

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const timetableFields = [
  { key: 'day_name', label: 'Jour' },
  { key: 'time_slot', label: 'Horaire' },
  { key: 'subject', label: 'Matière' },
  { key: 'class', label: 'Classe' }
]

// Methods
const fetchTeacher = async () => {
  if (!props.teacherId) return
  
  loading.value = true
  try {
    const response = await axios.get(`/api/teachers/${props.teacherId}`)
    teacher.value = response.data.teacher
  } catch (error) {
    useToast().error('Erreur lors du chargement des détails')
    console.error('Error fetching teacher:', error)
    localShow.value = false
  } finally {
    loading.value = false
  }
}

const formatDate = (date: string | null) => {
  if (!date) return null
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Watchers
watch(() => props.show, (show) => {
  if (show && props.teacherId) {
    fetchTeacher()
  }
})

watch(() => props.teacherId, (newId) => {
  if (newId && props.show) {
    fetchTeacher()
  }
})
</script>

<style scoped>
.avatar-lg {
  height: 4rem;
  width: 4rem;
}

.teacher-details .form-label {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.teacher-details p {
  font-size: 0.9rem;
}
</style>