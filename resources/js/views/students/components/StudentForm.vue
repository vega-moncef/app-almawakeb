<template>
  <BModal
    :model-value="visible"
    @update:model-value="$emit('close')"
    :title="isEditing ? 'Modifier l\'élève' : 'Nouvel élève'"
    size="xl"
    hide-footer
    scrollable
  >
    <BForm @submit.prevent="submitForm" class="needs-validation" novalidate>
      <BTabs v-model="activeTab" class="nav-tabs-custom mb-3">
        <!-- Basic Information -->
        <BTab title="Informations de base" active>
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Prénom *"
                label-for="first-name"
                :invalid-feedback="errors.first_name"
                :state="getFieldState('first_name')"
              >
                <BFormInput
                  id="first-name"
                  v-model="form.first_name"
                  type="text"
                  placeholder="Prénom de l'élève"
                  :state="getFieldState('first_name')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Nom *"
                label-for="last-name"
                :invalid-feedback="errors.last_name"
                :state="getFieldState('last_name')"
              >
                <BFormInput
                  id="last-name"
                  v-model="form.last_name"
                  type="text"
                  placeholder="Nom de famille"
                  :state="getFieldState('last_name')"
                  required
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Genre *"
                label-for="gender"
                :invalid-feedback="errors.gender"
                :state="getFieldState('gender')"
              >
                <BFormSelect
                  id="gender"
                  v-model="form.gender"
                  :options="genderOptions"
                  :state="getFieldState('gender')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Date de naissance *"
                label-for="birth-date"
                :invalid-feedback="errors.birth_date"
                :state="getFieldState('birth_date')"
              >
                <BFormInput
                  id="birth-date"
                  v-model="form.birth_date"
                  type="date"
                  :state="getFieldState('birth_date')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Lieu de naissance *"
                label-for="birth-place"
                :invalid-feedback="errors.birth_place"
                :state="getFieldState('birth_place')"
              >
                <BFormInput
                  id="birth-place"
                  v-model="form.birth_place"
                  type="text"
                  placeholder="Lieu de naissance"
                  :state="getFieldState('birth_place')"
                  required
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Ville *"
                label-for="city"
                :invalid-feedback="errors.city"
                :state="getFieldState('city')"
              >
                <BFormInput
                  id="city"
                  v-model="form.city"
                  type="text"
                  placeholder="Ville de résidence"
                  :state="getFieldState('city')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Nationalité"
                label-for="nationality"
              >
                <BFormInput
                  id="nationality"
                  v-model="form.nationality"
                  type="text"
                  placeholder="Nationalité"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Nombre de redoublements"
                label-for="repeat-count"
              >
                <BFormInput
                  id="repeat-count"
                  v-model="form.repeat_count"
                  type="number"
                  min="0"
                  placeholder="0"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol md="6">
              <BFormGroup
                label="École précédente"
                label-for="previous-school"
              >
                <BFormInput
                  id="previous-school"
                  v-model="form.previous_school"
                  type="text"
                  placeholder="École précédente"
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Niveau précédent"
                label-for="previous-level"
              >
                <BFormInput
                  id="previous-level"
                  v-model="form.previous_level"
                  type="text"
                  placeholder="Niveau précédent"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Photo de l'élève"
                label-for="student-photo"
              >
                <BFormFile
                  id="student-photo"
                  v-model="studentPhotoFile"
                  accept="image/*"
                  placeholder="Choisir une photo..."
                  drop-placeholder="Déposer le fichier ici..."
                  @change="handlePhotoChange"
                />
                <small class="text-muted">Formats acceptés: JPG, PNG, GIF. Taille max: 2MB</small>
              </BFormGroup>
            </BCol>
            <BCol md="6" v-if="photoPreview">
              <BFormGroup label="Aperçu">
                <img 
                  :src="photoPreview" 
                  alt="Aperçu de la photo" 
                  class="img-thumbnail"
                  style="max-width: 150px; max-height: 150px; object-fit: cover;"
                />
              </BFormGroup>
            </BCol>
          </BRow>
        </BTab>

        <!-- Official Information -->
        <BTab title="Identification officielle">
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Code MASSAR"
                label-for="massar-code"
                :invalid-feedback="errors.massar_code"
                :state="getFieldState('massar_code')"
              >
                <BFormInput
                  id="massar-code"
                  v-model="form.massar_code"
                  type="text"
                  placeholder="Code MASSAR"
                  :state="getFieldState('massar_code')"
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="CIN"
                label-for="national-id"
              >
                <BFormInput
                  id="national-id"
                  v-model="form.national_id"
                  type="text"
                  placeholder="Numéro de CIN"
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Numéro de passeport"
                label-for="passport-number"
              >
                <BFormInput
                  id="passport-number"
                  v-model="form.passport_number"
                  type="text"
                  placeholder="Numéro de passeport"
                />
              </BFormGroup>
            </BCol>
          </BRow>
        </BTab>

        <!-- Family Information -->
        <BTab title="Informations familiales">
          <!-- Father Information -->
          <h6 class="text-primary mb-3">Informations du Père</h6>
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Prénom du père *"
                label-for="father-first-name"
                :invalid-feedback="errors.father_first_name"
                :state="getFieldState('father_first_name')"
              >
                <BFormInput
                  id="father-first-name"
                  v-model="form.father_first_name"
                  type="text"
                  placeholder="Prénom du père"
                  :state="getFieldState('father_first_name')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Nom du père *"
                label-for="father-last-name"
                :invalid-feedback="errors.father_last_name"
                :state="getFieldState('father_last_name')"
              >
                <BFormInput
                  id="father-last-name"
                  v-model="form.father_last_name"
                  type="text"
                  placeholder="Nom du père"
                  :state="getFieldState('father_last_name')"
                  required
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Téléphone père *"
                label-for="father-phone"
                :invalid-feedback="errors.father_phone"
                :state="getFieldState('father_phone')"
              >
                <BFormInput
                  id="father-phone"
                  v-model="form.father_phone"
                  type="tel"
                  placeholder="Téléphone"
                  :state="getFieldState('father_phone')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Email père"
                label-for="father-email"
              >
                <BFormInput
                  id="father-email"
                  v-model="form.father_email"
                  type="email"
                  placeholder="Email"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Profession père"
                label-for="father-profession"
              >
                <BFormInput
                  id="father-profession"
                  v-model="form.father_profession"
                  type="text"
                  placeholder="Profession"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol md="6">
              <BFormGroup
                label="CIN père"
                label-for="father-national-id"
              >
                <BFormInput
                  id="father-national-id"
                  v-model="form.father_national_id"
                  type="text"
                  placeholder="CIN du père"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <!-- Mother Information -->
          <h6 class="text-primary mb-3 mt-4">Informations de la Mère</h6>
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Prénom de la mère *"
                label-for="mother-first-name"
                :invalid-feedback="errors.mother_first_name"
                :state="getFieldState('mother_first_name')"
              >
                <BFormInput
                  id="mother-first-name"
                  v-model="form.mother_first_name"
                  type="text"
                  placeholder="Prénom de la mère"
                  :state="getFieldState('mother_first_name')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Nom de la mère *"
                label-for="mother-last-name"
                :invalid-feedback="errors.mother_last_name"
                :state="getFieldState('mother_last_name')"
              >
                <BFormInput
                  id="mother-last-name"
                  v-model="form.mother_last_name"
                  type="text"
                  placeholder="Nom de la mère"
                  :state="getFieldState('mother_last_name')"
                  required
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Téléphone mère"
                label-for="mother-phone"
              >
                <BFormInput
                  id="mother-phone"
                  v-model="form.mother_phone"
                  type="tel"
                  placeholder="Téléphone"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Email mère"
                label-for="mother-email"
              >
                <BFormInput
                  id="mother-email"
                  v-model="form.mother_email"
                  type="email"
                  placeholder="Email"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Profession mère"
                label-for="mother-profession"
              >
                <BFormInput
                  id="mother-profession"
                  v-model="form.mother_profession"
                  type="text"
                  placeholder="Profession"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol md="6">
              <BFormGroup
                label="CIN mère"
                label-for="mother-national-id"
              >
                <BFormInput
                  id="mother-national-id"
                  v-model="form.mother_national_id"
                  type="text"
                  placeholder="CIN de la mère"
                />
              </BFormGroup>
            </BCol>
          </BRow>
        </BTab>

        <!-- Address & Emergency -->
        <BTab title="Adresse & Contact d'urgence">
          <h6 class="text-primary mb-3">Adresse</h6>
          <BRow>
            <BCol cols="12">
              <BFormGroup
                label="Adresse domicile *"
                label-for="home-address"
                :invalid-feedback="errors.home_address"
                :state="getFieldState('home_address')"
              >
                <BFormTextarea
                  id="home-address"
                  v-model="form.home_address"
                  rows="3"
                  placeholder="Adresse complète"
                  :state="getFieldState('home_address')"
                  required
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Code postal"
                label-for="postal-code"
              >
                <BFormInput
                  id="postal-code"
                  v-model="form.postal_code"
                  type="text"
                  placeholder="Code postal"
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Quartier"
                label-for="neighborhood"
              >
                <BFormInput
                  id="neighborhood"
                  v-model="form.neighborhood"
                  type="text"
                  placeholder="Quartier"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <!-- Emergency Contact -->
          <h6 class="text-primary mb-3 mt-4">Contact d'urgence</h6>
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Nom du contact"
                label-for="emergency-contact-name"
              >
                <BFormInput
                  id="emergency-contact-name"
                  v-model="form.emergency_contact_name"
                  type="text"
                  placeholder="Nom complet"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Téléphone"
                label-for="emergency-contact-phone"
              >
                <BFormInput
                  id="emergency-contact-phone"
                  v-model="form.emergency_contact_phone"
                  type="tel"
                  placeholder="Téléphone"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Relation"
                label-for="emergency-contact-relationship"
              >
                <BFormInput
                  id="emergency-contact-relationship"
                  v-model="form.emergency_contact_relationship"
                  type="text"
                  placeholder="Relation (oncle, tante, etc.)"
                />
              </BFormGroup>
            </BCol>
          </BRow>
        </BTab>

        <!-- Academic & Services -->
        <BTab title="Académique & Services">
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Date d'inscription *"
                label-for="enrollment-date"
                :invalid-feedback="errors.enrollment_date"
                :state="getFieldState('enrollment_date')"
              >
                <BFormInput
                  id="enrollment-date"
                  v-model="form.enrollment_date"
                  type="date"
                  :state="getFieldState('enrollment_date')"
                  required
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Classe"
                label-for="class-id"
              >
                <BFormSelect
                  id="class-id"
                  v-model="form.class_id"
                  :options="classOptions"
                />
              </BFormGroup>
            </BCol>
          </BRow>
          
          <BRow>
            <BCol md="6">
              <BFormGroup
                label="Score d'admission"
                label-for="admission-score"
              >
                <BFormInput
                  id="admission-score"
                  v-model="form.admission_score"
                  type="number"
                  min="0"
                  max="20"
                  step="0.01"
                  placeholder="Score sur 20"
                />
              </BFormGroup>
            </BCol>
            <BCol md="6">
              <BFormGroup
                label="Statut"
                label-for="status"
              >
                <BFormSelect
                  id="status"
                  v-model="form.status"
                  :options="statusOptions"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <!-- Services -->
          <h6 class="text-primary mb-3 mt-4">Services Scolaires</h6>
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Transport"
                label-for="transport-method"
              >
                <BFormSelect
                  id="transport-method"
                  v-model="form.transport_method"
                  :options="transportOptions"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Ligne de bus"
                label-for="bus-route"
              >
                <BFormInput
                  id="bus-route"
                  v-model="form.bus_route"
                  type="text"
                  placeholder="Ligne de bus"
                  :disabled="form.transport_method !== 'school_bus'"
                />
              </BFormGroup>
            </BCol>
            <BCol md="4">
              <BFormGroup
                label="Restauration"
                label-for="meal-plan"
              >
                <BFormSelect
                  id="meal-plan"
                  v-model="form.meal_plan"
                  :options="mealPlanOptions"
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <!-- Medical & Special Needs -->
          <h6 class="text-primary mb-3 mt-4">Informations Médicales</h6>
          <BRow>
            <BCol md="4">
              <BFormGroup
                label="Groupe sanguin"
                label-for="blood-type"
              >
                <BFormSelect
                  id="blood-type"
                  v-model="form.blood_type"
                  :options="bloodTypeOptions"
                />
              </BFormGroup>
            </BCol>
            <BCol md="8">
              <BFormGroup>
                <BFormCheckbox
                  v-model="form.has_special_needs"
                  name="has-special-needs"
                >
                  L'élève a des besoins spéciaux
                </BFormCheckbox>
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow v-if="form.has_special_needs">
            <BCol cols="12">
              <BFormGroup
                label="Détails des besoins spéciaux"
                label-for="special-needs-details"
              >
                <BFormTextarea
                  id="special-needs-details"
                  v-model="form.special_needs_details"
                  rows="3"
                  placeholder="Décrivez les besoins spéciaux de l'élève..."
                />
              </BFormGroup>
            </BCol>
          </BRow>

          <BRow>
            <BCol cols="12">
              <BFormGroup
                label="Observations"
                label-for="observations"
              >
                <BFormTextarea
                  id="observations"
                  v-model="form.observations"
                  rows="3"
                  placeholder="Observations générales sur l'élève..."
                />
              </BFormGroup>
            </BCol>
          </BRow>
        </BTab>
      </BTabs>

      <!-- Form Actions -->
      <div class="d-flex justify-content-end gap-2 mt-3">
        <BButton type="button" variant="secondary" @click="$emit('close')">
          Annuler
        </BButton>
        <BButton type="submit" variant="primary" :disabled="isSubmitting">
          <BSpinner v-if="isSubmitting" small class="me-2" />
          {{ isEditing ? 'Modifier' : 'Ajouter' }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAcademicYearStore } from '@/stores/academicYear'
import { showToast } from '@/helpers/toast'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  student: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const academicYearStore = useAcademicYearStore()

// Reactive data
const activeTab = ref(0)
const isSubmitting = ref(false)
const errors = ref({})
const classes = ref([])
const studentPhotoFile = ref(null)
const photoPreview = ref('')

// Form data
const form = ref({
  first_name: '',
  last_name: '',
  gender: '',
  birth_date: '',
  birth_place: '',
  city: '',
  nationality: 'Moroccan',
  repeat_count: 0,
  previous_school: '',
  previous_level: '',
  massar_code: '',
  national_id: '',
  passport_number: '',
  father_first_name: '',
  father_last_name: '',
  father_phone: '',
  father_email: '',
  father_profession: '',
  father_national_id: '',
  mother_first_name: '',
  mother_last_name: '',
  mother_phone: '',
  mother_email: '',
  mother_profession: '',
  mother_national_id: '',
  home_address: '',
  postal_code: '',
  neighborhood: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  emergency_contact_relationship: '',
  enrollment_date: new Date().toISOString().split('T')[0],
  class_id: '',
  admission_score: '',
  status: 'active',
  transport_method: 'none',
  bus_route: '',
  meal_plan: 'none',
  blood_type: '',
  has_special_needs: false,
  special_needs_details: '',
  observations: '',
  student_photo: '',
  academic_year_id: null
})

// Computed
const isEditing = computed(() => !!props.student)

const genderOptions = [
  { value: '', text: 'Sélectionner le genre' },
  { value: 'male', text: 'Garçon' },
  { value: 'female', text: 'Fille' }
]

const classOptions = computed(() => [
  { value: '', text: 'Sélectionner une classe' },
  ...classes.value.map(cls => ({
    value: cls.id,
    text: cls.full_name
  }))
])

const statusOptions = [
  { value: 'active', text: 'Actif' },
  { value: 'suspended', text: 'Suspendu' },
  { value: 'graduated', text: 'Diplômé' },
  { value: 'transferred', text: 'Transféré' },
  { value: 'dropped_out', text: 'Abandonné' }
]

const transportOptions = [
  { value: 'none', text: 'Aucun' },
  { value: 'school_bus', text: 'Bus scolaire' },
  { value: 'private', text: 'Transport privé' },
  { value: 'walking', text: 'À pied' }
]

const mealPlanOptions = [
  { value: 'none', text: 'Aucun' },
  { value: 'lunch_only', text: 'Déjeuner uniquement' },
  { value: 'full_meals', text: 'Tous les repas' }
]

const bloodTypeOptions = [
  { value: '', text: 'Non renseigné' },
  { value: 'A+', text: 'A+' },
  { value: 'A-', text: 'A-' },
  { value: 'B+', text: 'B+' },
  { value: 'B-', text: 'B-' },
  { value: 'AB+', text: 'AB+' },
  { value: 'AB-', text: 'AB-' },
  { value: 'O+', text: 'O+' },
  { value: 'O-', text: 'O-' }
]

// Methods
const getFieldState = (fieldName) => {
  if (errors.value[fieldName]) return false
  return null
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

const handlePhotoChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Check file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
      showToast('La taille de la photo ne doit pas dépasser 2MB', 'error')
      studentPhotoFile.value = null
      return
    }
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    photoPreview.value = ''
  }
}

const resetForm = () => {
  form.value = {
    first_name: '',
    last_name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    city: '',
    nationality: 'Moroccan',
    repeat_count: 0,
    previous_school: '',
    previous_level: '',
    massar_code: '',
    national_id: '',
    passport_number: '',
    father_first_name: '',
    father_last_name: '',
    father_phone: '',
    father_email: '',
    father_profession: '',
    father_national_id: '',
    mother_first_name: '',
    mother_last_name: '',
    mother_phone: '',
    mother_email: '',
    mother_profession: '',
    mother_national_id: '',
    home_address: '',
    postal_code: '',
    neighborhood: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
    enrollment_date: new Date().toISOString().split('T')[0],
    class_id: '',
    admission_score: '',
    status: 'active',
    transport_method: 'none',
    bus_route: '',
    meal_plan: 'none',
    blood_type: '',
    has_special_needs: false,
    special_needs_details: '',
    observations: '',
    student_photo: '',
    academic_year_id: academicYearStore.currentAcademicYear?.id
  }
  errors.value = {}
  activeTab.value = 0
  studentPhotoFile.value = null
  photoPreview.value = ''
}

const loadStudent = () => {
  if (props.student) {
    Object.keys(form.value).forEach(key => {
      if (props.student[key] !== undefined) {
        form.value[key] = props.student[key]
      }
    })
    // Handle date formatting
    if (props.student.birth_date) {
      form.value.birth_date = props.student.birth_date.split('T')[0]
    }
    if (props.student.enrollment_date) {
      form.value.enrollment_date = props.student.enrollment_date.split('T')[0]
    }
    // Handle existing photo
    if (props.student.student_photo) {
      photoPreview.value = `/assets/images/students/${props.student.student_photo}`
    }
  }
}

const submitForm = async () => {
  if (isSubmitting.value) return
  
  isSubmitting.value = true
  errors.value = {}

  try {
    // Set academic year
    form.value.academic_year_id = academicYearStore.currentAcademicYear?.id

    // Prepare form data for submission
    const formData = new FormData()
    
    // Add all form fields
    Object.keys(form.value).forEach(key => {
      // Always append the field, even if empty (but not null)
      if (form.value[key] !== null && form.value[key] !== undefined) {
        // Handle boolean values properly for FormData
        if (typeof form.value[key] === 'boolean') {
          formData.append(key, form.value[key] ? '1' : '0')
        } else {
          formData.append(key, form.value[key] || '')
        }
      }
    })
    
    // Add photo if selected
    if (studentPhotoFile.value) {
      formData.append('student_photo_file', studentPhotoFile.value)
    }

    let response
    if (isEditing.value) {
      // For update, use POST with _method field for file uploads
      // Append _method after all other fields to ensure it's processed correctly
      formData.append('_method', 'PUT')
      response = await axios.post(`/api/students/${props.student.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    } else {
      response = await axios.post('/api/students', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    }

    emit('saved', response.data)
    resetForm()
    showToast(isEditing.value ? 'Élève modifié avec succès' : 'Élève ajouté avec succès', 'success')
  } catch (error) {
    console.error('Error saving student:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      console.log('Validation errors:', errors.value)
      showToast('Veuillez corriger les erreurs dans le formulaire', 'error')
    } else {
      console.log('Full error response:', error.response)
      showToast('Erreur lors de la sauvegarde de l\'élève', 'error')
    }
  } finally {
    isSubmitting.value = false
  }
}

// Watchers
watch(() => props.visible, (visible) => {
  if (visible) {
    if (props.student) {
      loadStudent()
    } else {
      resetForm()
    }
    loadClasses()
  }
})

watch(() => form.value.transport_method, (method) => {
  if (method !== 'school_bus') {
    form.value.bus_route = ''
  }
})

// Lifecycle
onMounted(async () => {
  if (!academicYearStore.currentAcademicYear) {
    await academicYearStore.init()
  }
  
  if (props.visible) {
    await loadClasses()
    if (props.student) {
      loadStudent()
    } else {
      resetForm()
    }
  }
})
</script>

<style scoped>
.nav-tabs-custom .nav-link {
  border: 1px solid transparent;
  border-radius: 0.375rem 0.375rem 0 0;
}

.nav-tabs-custom .nav-link.active {
  color: #405189;
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff;
}
</style>