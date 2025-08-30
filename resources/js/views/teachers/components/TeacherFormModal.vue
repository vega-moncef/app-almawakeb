<template>
  <BModal
    v-model="localShow"
    :title="isEditing ? 'Modifier l\'enseignant' : 'Ajouter un enseignant'"
    size="lg"
    hide-footer
    @hidden="resetForm"
  >
    <BForm @submit.prevent="handleSubmit">
      <!-- Photo Upload -->
      <BRow class="mb-4">
        <BCol md="12" class="text-center">
          <div class="position-relative d-inline-block">
            <img
              :src="form.photo_preview || '/assets/images/users/dummy-avatar.jpg'"
              alt="Profile"
              class="avatar-lg rounded-circle"
            />
            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
              <BFormFile
                id="profile-img-file-input"
                ref="fileInput"
                v-model="photoFile"
                accept="image/*"
                class="profile-img-file-input"
                @change="handleFileChange"
              />
              <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                <span class="avatar-title rounded-circle bg-light text-body">
                  <i class="ri-camera-fill"></i>
                </span>
              </label>
            </div>
          </div>
        </BCol>
      </BRow>

      <!-- 1. Informations personnelles -->
      <BRow class="mb-4">
        <BCol md="12">
          <h5 class="text-primary mb-3">
            <i class="ri-user-line me-2"></i>1. Informations personnelles
          </h5>
        </BCol>
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Prénom *</label>
            <BFormInput
              v-model="form.first_name"
              :state="getFieldState('first_name')"
              required
            />
            <BFormInvalidFeedback>{{ errors.first_name?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Nom *</label>
            <BFormInput
              v-model="form.last_name"
              :state="getFieldState('last_name')"
              required
            />
            <BFormInvalidFeedback>{{ errors.last_name?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <BFormInput
              v-model="form.email"
              type="email"
              :state="getFieldState('email')"
            />
            <BFormInvalidFeedback>{{ errors.email?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Téléphone</label>
            <BFormInput
              v-model="form.phone"
              :state="getFieldState('phone')"
            />
            <BFormInvalidFeedback>{{ errors.phone?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

         <BCol md="12">
          <div class="mb-3">
            <label class="form-label">Date de naissance</label>
            <BFormInput
              v-model="form.date_of_birth"
              type="date"
              :state="getFieldState('date_of_birth')"
            />
            <BFormInvalidFeedback>{{ errors.date_of_birth?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol cols="12">
          <div class="mb-3">
            <label class="form-label">Adresse</label>
            <BFormTextarea
              v-model="form.address"
              rows="2"
              :state="getFieldState('address')"
            />
            <BFormInvalidFeedback>{{ errors.address?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

      </BRow>

      <!-- 2. Statut et informations professionnelles -->
      <BRow class="mb-4">
        <BCol md="12">
          <h5 class="text-primary mb-3">
            <i class="ri-briefcase-line me-2"></i>2. Statut et informations professionnelles
          </h5>
        </BCol>
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Type d'enseignant *</label>
            <BFormSelect
              v-model="form.type"
              :options="teacherTypes"
              :state="getFieldState('type')"
              required
            />
            <BFormInvalidFeedback>{{ errors.type?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

       

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Date d'embauche</label>
            <BFormInput
              v-model="form.hire_date"
              type="date"
              :state="getFieldState('hire_date')"
            />
            <BFormInvalidFeedback>{{ errors.hire_date?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Années d'expérience</label>
            <BFormInput
              v-model="form.experience_years"
              type="number"
              min="0"
              :state="getFieldState('experience_years')"
            />
            <BFormInvalidFeedback>{{ errors.experience_years?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <BFormCheckbox v-model="form.is_active" class="mt-4">
              <strong>Enseignant actif</strong>
            </BFormCheckbox>
          </div>
        </BCol>
      </BRow>

      <!-- 3. Qualifications -->
      <BRow class="mb-4">
        <BCol md="12">
          <h5 class="text-primary mb-3">
            <i class="ri-graduation-cap-line me-2"></i>3. Qualifications
          </h5>
        </BCol>

        <BCol cols="12">
          <div class="mb-3">
            <label class="form-label">Qualifications</label>
            <BFormTextarea
              v-model="form.qualification"
              rows="3"
              :state="getFieldState('qualification')"
            />
            <BFormInvalidFeedback>{{ errors.qualification?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Employment Information -->
        <BCol cols="12" class="mt-4">
          <h5 class="text-primary mb-3">
            <i class="ri-school-line me-2"></i>4. Affectation scolaire
          </h5>
        </BCol>

        <BCol md="12">
          <div class="mb-3">
            <label class="form-label">ID Employé</label>
            <BFormInput
              v-model="form.employee_id"
              :state="getFieldState('employee_id')"
            />
            <BFormInvalidFeedback>{{ errors.employee_id?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">École</label>
            <BFormSelect
              v-model="form.school_id"
              :options="schoolOptions"
              :state="getFieldState('school_id')"
              @change="onSchoolChange"
            />
            <BFormInvalidFeedback>{{ errors.school_id?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

      </BRow>

      <BRow>
        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Spécialité principale</label>
            <BFormSelect
              v-model="form.main_subject_id"
              :options="specialtyOptions"
              :state="getFieldState('main_subject_id')"
            />
            <BFormInvalidFeedback>{{ errors.main_subject_id?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>


        <!-- Contract Information -->
        <BCol cols="12" class="mt-4">
          <h5 class="text-primary mb-3">
            <i class="ri-file-text-line me-2"></i>5. Informations contractuelles
          </h5>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Type de contrat</label>
            <BFormSelect
              v-model="form.contract_type"
              :options="contractTypeOptions"
              :state="getFieldState('contract_type')"
            />
            <BFormInvalidFeedback>{{ errors.contract_type?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Heures max par semaine</label>
            <BFormInput
              v-model="form.max_hours_per_week"
              type="number"
              min="1"
              max="60"
              :state="getFieldState('max_hours_per_week')"
            />
            <BFormInvalidFeedback>{{ errors.max_hours_per_week?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Date début contrat</label>
            <BFormInput
              v-model="form.contract_start_date"
              type="date"
              :state="getFieldState('contract_start_date')"
            />
            <BFormInvalidFeedback>{{ errors.contract_start_date?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Date fin contrat</label>
            <BFormInput
              v-model="form.contract_end_date"
              type="date"
              :state="getFieldState('contract_end_date')"
            />
            <BFormInvalidFeedback>{{ errors.contract_end_date?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Salary Information -->
        <BCol cols="12" class="mt-4">
          <h5 class="text-primary mb-3">
            <i class="ri-money-dollar-circle-line me-2"></i>6. Informations salariales
          </h5>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <label class="form-label">Type de salaire</label>
            <BFormSelect
              v-model="form.salary_type"
              :options="salaryTypeOptions"
              :state="getFieldState('salary_type')"
            />
            <BFormInvalidFeedback>{{ errors.salary_type?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6" v-if="form.salary_type === 'monthly'">
          <div class="mb-3">
            <label class="form-label">Salaire mensuel (DH)</label>
            <BFormInput
              v-model="form.monthly_salary"
              type="number"
              step="0.01"
              min="0"
              :state="getFieldState('monthly_salary')"
            />
            <BFormInvalidFeedback>{{ errors.monthly_salary?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <BCol md="6" v-if="form.salary_type === 'hourly'">
          <div class="mb-3">
            <label class="form-label">Taux horaire (DH)</label>
            <BFormInput
              v-model="form.hourly_rate"
              type="number"
              step="0.01"
              min="0"
              :state="getFieldState('hourly_rate')"
            />
            <BFormInvalidFeedback>{{ errors.hourly_rate?.[0] }}</BFormInvalidFeedback>
          </div>
        </BCol>

        <!-- Account Information -->
      </BRow>

      <!-- 7. Compte utilisateur -->
      <BRow class="mb-4">
        <BCol md="12">
          <h5 class="text-primary mb-3">
            <i class="ri-account-circle-line me-2"></i>7. Compte utilisateur
          </h5>
        </BCol>

        <BCol md="6">
          <div class="mb-3">
            <BFormCheckbox v-model="form.has_account">
              <strong>Possède un compte utilisateur</strong>
            </BFormCheckbox>
          </div>
        </BCol>
      </BRow>

      <!-- 8. Matières enseignées -->
      <BRow class="mb-4">
        <BCol md="12">
          <h5 class="text-primary mb-3">
            <i class="ri-book-open-line me-2"></i>8. Matières enseignées
          </h5>
        </BCol>
        
        <BCol 
          v-for="subject in subjects" 
          :key="subject.id" 
          md="3" 
          sm="6" 
          class="mb-2"
        >
          <BFormCheckbox
            :value="subject.id"
            v-model="form.subject_ids"
          >
            {{ subject.name }}
          </BFormCheckbox>
        </BCol>

        <BCol md="12" v-if="errors.subject_ids">
          <div class="text-danger small">
            {{ errors.subject_ids[0] }}
          </div>
        </BCol>
      </BRow>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <BButton variant="light" @click="localShow = false">
          Annuler
        </BButton>
        <BButton 
          type="submit" 
          variant="primary"
          :disabled="loading"
        >
          <BSpinner v-if="loading" small class="me-1" />
          {{ isEditing ? 'Modifier' : 'Ajouter' }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
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
  (e: 'saved'): void
}

const emit = defineEmits<Emits>()

// State
const loading = ref(false)
const subjects = ref([])
const schools = ref([])
const levels = ref([])
const teacherTypes = ref([])
const contractTypes = ref([])
const salaryTypes = ref([])
const photoFile = ref(null)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
  date_of_birth: '',
  hire_date: '',
  type: 'permanent',
  qualification: '',
  experience_years: 0,
  is_active: true,
  photo_preview: '',
  subject_ids: [],
  // New fields
  user_id: null,
  employee_id: '',
  school_id: null,
  main_subject_id: null,
  contract_type: null,
  contract_start_date: '',
  contract_end_date: '',
  max_hours_per_week: 40,
  salary_type: 'monthly',
  hourly_rate: null,
  monthly_salary: null,
  has_account: false
})

const errors = reactive({})

// Computed
const localShow = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value)
})

const isEditing = computed(() => props.teacherId !== null)

const schoolOptions = computed(() => [
  { value: null, text: 'Sélectionner une école' },
  ...schools.value.map(school => ({ value: school.id, text: school.name }))
])

const levelOptions = computed(() => [
  { value: null, text: 'Sélectionner un niveau' },
  ...levels.value.map(level => ({ value: level.id, text: level.name }))
])

const specialtyOptions = computed(() => [
  { value: null, text: 'Sélectionner une spécialité' },
  ...subjects.value.map(subject => ({ value: subject.id, text: subject.name }))
])

const contractTypeOptions = computed(() => [
  { value: null, text: 'Sélectionner un type' },
  ...contractTypes.value.map(([key, value]) => ({ value: key, text: value }))
])

const salaryTypeOptions = computed(() => [
  ...salaryTypes.value.map(([key, value]) => ({ value: key, text: value }))
])

// Methods
const fetchFormData = async () => {
  try {
    const response = await axios.get('/api/teachers/create')
    console.log('API Response:', response.data) // Debug log
    subjects.value = response.data.subjects || []
    schools.value = response.data.schools || []
    teacherTypes.value = Object.entries(response.data.types || {}).map(([key, value]) => ({
      value: key,
      text: value
    }))
    contractTypes.value = Object.entries(response.data.contractTypes || {})
    salaryTypes.value = Object.entries(response.data.salaryTypes || {})
    console.log('Subjects:', subjects.value) // Debug log
    console.log('Schools:', schools.value) // Debug log
  } catch (error) {
    useToast().error('Erreur lors du chargement des données')
    console.error('Error fetching form data:', error)
  }
}

const fetchLevelsForSchool = async (schoolId) => {
  if (!schoolId) {
    levels.value = []
    return
  }
  
  try {
    const response = await axios.get(`/api/schools/${schoolId}/levels`)
    levels.value = response.data || []
  } catch (error) {
    useToast().error('Erreur lors du chargement des niveaux')
    console.error('Error fetching levels:', error)
    levels.value = []
  }
}

const onSchoolChange = async () => {
  // School change handler - no longer needed for level selection
}

const fetchTeacher = async () => {
  if (!props.teacherId) return
  
  try {
    const response = await axios.get(`/api/teachers/${props.teacherId}/edit`)
    const teacher = response.data.teacher
    
    // Update form data and also get subjects/types from the same response
    subjects.value = response.data.subjects || subjects.value
    schools.value = response.data.schools || schools.value
    teacherTypes.value = Object.entries(response.data.types || {}).map(([key, value]) => ({
      value: key,
      text: value
    }))
    contractTypes.value = Object.entries(response.data.contractTypes || {})
    salaryTypes.value = Object.entries(response.data.salaryTypes || {})
    
    // Load levels if teacher has a school assigned
    if (teacher.school_id) {
      await fetchLevelsForSchool(teacher.school_id)
    }
    
    Object.keys(form).forEach(key => {
      if (key === 'subject_ids') {
        form[key] = teacher.subjects.map(s => s.id)
      } else if (key === 'photo_preview') {
        form[key] = teacher.photo_url
      } else {
        form[key] = teacher[key] || form[key]
      }
    })
  } catch (error) {
    useToast().error('Erreur lors du chargement de l\'enseignant')
    console.error('Error fetching teacher:', error)
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    // Check file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
      useToast().error('La taille de la photo ne doit pas dépasser 2MB')
      photoFile.value = null
      return
    }
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      form.photo_preview = e.target?.result as string
    }
    reader.readAsDataURL(file)
  } else {
    form.photo_preview = null
  }
}

const handleSubmit = async () => {
  loading.value = true
  Object.keys(errors).forEach(key => delete errors[key])
  
  try {
    const formData = new FormData()
    
    Object.keys(form).forEach(key => {
      if (key === 'subject_ids') {
        form.subject_ids.forEach(id => {
          formData.append('subject_ids[]', id.toString())
        })
      } else if (key === 'is_active' || key === 'has_account') {
        formData.append(key, form[key] ? '1' : '0')
      } else if (key !== 'photo_preview' && form[key] !== null && form[key] !== '') {
        formData.append(key, form[key].toString())
      }
    })

    // Add photo file if selected (separate from form data)
    if (photoFile.value && photoFile.value instanceof File) {
      formData.append('photo', photoFile.value)
    }

    let response
    if (isEditing.value) {
      formData.append('_method', 'PUT')
      response = await axios.post(`/api/teachers/${props.teacherId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      response = await axios.post('/api/teachers', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }

    useToast().success(
      isEditing.value 
        ? 'Enseignant modifié avec succès' 
        : 'Enseignant ajouté avec succès'
    )
    
    emit('saved')
  } catch (error: any) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors)
      console.log('Validation errors:', error.response.data.errors)
      console.log('Photo file:', photoFile.value)
      console.log('Form data:', form)
    } else {
      useToast().error('Erreur lors de l\'enregistrement')
    }
    console.error('Error saving teacher:', error)
  } finally {
    loading.value = false
  }
}

const getFieldState = (field: string) => {
  return errors[field] ? false : null
}

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'subject_ids') {
      form[key] = []
    } else if (key === 'is_active' || key === 'has_account') {
      form[key] = key === 'is_active' ? true : false
    } else if (key === 'type') {
      form[key] = 'permanent'
    } else if (key === 'salary_type') {
      form[key] = 'monthly'
    } else if (key === 'experience_years' || key === 'max_hours_per_week') {
      form[key] = key === 'max_hours_per_week' ? 40 : 0
    } else if (key.includes('_id') || key === 'user_id' || key === 'school_id' || key === 'main_subject_id' || key === 'contract_type') {
      form[key] = null
    } else if (key === 'hourly_rate' || key === 'monthly_salary') {
      form[key] = null
    } else {
      form[key] = ''
    }
  })
  Object.keys(errors).forEach(key => delete errors[key])
  
  // Clear photo file and preview
  photoFile.value = null
}

// Watchers
watch(() => props.show, (show) => {
  if (show) {
    if (isEditing.value) {
      fetchTeacher()
    } else {
      // Reset form for add mode
      resetForm()
    }
  }
})

// Lifecycle
onMounted(() => {
  fetchFormData()
})
</script>

<style scoped>
.avatar-lg {
  height: 4rem;
  width: 4rem;
}

.profile-photo-edit {
  position: absolute;
  bottom: 0;
  right: 0;
}

.profile-img-file-input {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.avatar-title {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
}
</style>