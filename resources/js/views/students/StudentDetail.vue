<template>
  <VerticalLayout>
    <PageTitle 
      :title="student ? `${student.first_name} ${student.last_name}` : 'Détails Élève'" 
      :subtitle="student ? `Code: ${student.student_code}` : 'Chargement...'" 
    />

    <div v-if="loading" class="text-center my-5">
      <BSpinner class="me-2"></BSpinner>
      <span>Chargement des détails de l'élève...</span>
    </div>

    <div v-else-if="student">
      <BRow>
        <!-- Student Profile Card -->
        <BCol xl="4">
          <BCard class="student-profile-card">
            <BCardBody class="text-center">
              <!-- Student Photo -->
              <div class="student-photo-container mb-3">
                <img 
                  v-if="student.student_photo" 
                  :src="student.student_photo" 
                  :alt="student.full_name"
                  class="student-photo"
                />
                <div v-else class="student-photo-placeholder">
                  <span class="photo-initials">
                    {{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}
                  </span>
                </div>
              </div>

              <h4 class="mb-1">{{ student.first_name }} {{ student.last_name }}</h4>
              <p class="text-muted mb-2">{{ student.student_code }}</p>
              
              <div class="mb-3">
                <BBadge :variant="student.status_color" class="text-uppercase fs-6">
                  {{ student.status_label }}
                </BBadge>
              </div>

              <!-- Quick Actions -->
              <div class="d-flex justify-content-center gap-2">
                <BButton variant="primary" @click="editStudent">
                  <i class="ri-edit-line me-1"></i>
                  Modifier
                </BButton>
                <BButton variant="outline-secondary" @click="goBack">
                  <i class="ri-arrow-left-line me-1"></i>
                  Retour
                </BButton>
              </div>
            </BCardBody>
          </BCard>

          <!-- Quick Stats -->
          <BCard class="mt-3">
            <BCardHeader>
              <BCardTitle class="mb-0">Informations Rapides</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <div class="quick-stat-item">
                <i class="ri-calendar-line text-primary me-2"></i>
                <span class="text-muted">Âge:</span>
                <span class="ms-auto">{{ student.age || 'N/A' }} ans</span>
              </div>
              <div class="quick-stat-item">
                <i class="ri-user-line text-primary me-2"></i>
                <span class="text-muted">Genre:</span>
                <span class="ms-auto">{{ student.gender === 'male' ? 'Garçon' : 'Fille' }}</span>
              </div>
              <div class="quick-stat-item">
                <i class="ri-school-line text-primary me-2"></i>
                <span class="text-muted">Classe:</span>
                <span class="ms-auto">{{ student.class?.full_name || 'Non assigné' }}</span>
              </div>
              <div class="quick-stat-item">
                <i class="ri-calendar-event-line text-primary me-2"></i>
                <span class="text-muted">Inscription:</span>
                <span class="ms-auto">{{ formatDate(student.enrollment_date) }}</span>
              </div>
              <div v-if="student.repeat_count > 0" class="quick-stat-item">
                <i class="ri-repeat-line text-warning me-2"></i>
                <span class="text-muted">Redoublement:</span>
                <span class="ms-auto">{{ student.repeat_count }} fois</span>
              </div>
            </BCardBody>
          </BCard>
        </BCol>

        <!-- Student Details Tabs -->
        <BCol xl="8">
          <BTabs v-model="activeTab" class="nav-tabs-custom">
            <!-- Personal Information -->
            <BTab title="Informations Personnelles" active>
              <BRow>
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Informations Générales</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Prénom:</label>
                        <span>{{ student.first_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Nom:</label>
                        <span>{{ student.last_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Date de naissance:</label>
                        <span>{{ formatDate(student.birth_date) }}</span>
                      </div>
                      <div class="info-item">
                        <label>Lieu de naissance:</label>
                        <span>{{ student.birth_place }}</span>
                      </div>
                      <div class="info-item">
                        <label>Ville:</label>
                        <span>{{ student.city }}</span>
                      </div>
                      <div class="info-item">
                        <label>Nationalité:</label>
                        <span>{{ student.nationality || 'Moroccan' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
                
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Identification Officielle</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Code MASSAR:</label>
                        <span>{{ student.massar_code || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>CIN:</label>
                        <span>{{ student.national_id || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Passeport:</label>
                        <span>{{ student.passport_number || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>École précédente:</label>
                        <span>{{ student.previous_school || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Niveau précédent:</label>
                        <span>{{ student.previous_level || 'Non renseigné' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
              </BRow>
            </BTab>

            <!-- Family Information -->
            <BTab title="Informations Familiales">
              <BRow>
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Père</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Nom complet:</label>
                        <span>{{ student.father_first_name }} {{ student.father_last_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Téléphone:</label>
                        <span>{{ student.father_phone }}</span>
                      </div>
                      <div class="info-item">
                        <label>Email:</label>
                        <span>{{ student.father_email || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Profession:</label>
                        <span>{{ student.father_profession || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>CIN:</label>
                        <span>{{ student.father_national_id || 'Non renseigné' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
                
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Mère</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Nom complet:</label>
                        <span>{{ student.mother_first_name }} {{ student.mother_last_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Téléphone:</label>
                        <span>{{ student.mother_phone || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Email:</label>
                        <span>{{ student.mother_email || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Profession:</label>
                        <span>{{ student.mother_profession || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>CIN:</label>
                        <span>{{ student.mother_national_id || 'Non renseigné' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
              </BRow>
              
              <!-- Address & Emergency Contact -->
              <BRow class="mt-3">
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Adresse</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Adresse:</label>
                        <span>{{ student.home_address }}</span>
                      </div>
                      <div class="info-item">
                        <label>Code postal:</label>
                        <span>{{ student.postal_code || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Quartier:</label>
                        <span>{{ student.neighborhood || 'Non renseigné' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
                
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Contact d'urgence</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Nom:</label>
                        <span>{{ student.emergency_contact_name || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Téléphone:</label>
                        <span>{{ student.emergency_contact_phone || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Relation:</label>
                        <span>{{ student.emergency_contact_relationship || 'Non renseigné' }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
              </BRow>
            </BTab>

            <!-- Academic & Medical -->
            <BTab title="Académique & Médical">
              <BRow>
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Informations Académiques</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Année académique:</label>
                        <span>{{ student.academic_year?.name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Classe:</label>
                        <span>{{ student.class?.full_name || 'Non assigné' }}</span>
                      </div>
                      <div v-if="student.class" class="info-item">
                        <label>École:</label>
                        <span>{{ student.class.level.school.name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Score d'admission:</label>
                        <span>{{ student.admission_score ? `${student.admission_score}/20` : 'Non évalué' }}</span>
                      </div>
                      <div v-if="student.student_visit" class="info-item">
                        <label>Visite d'origine:</label>
                        <span>Visite #{{ student.student_visit_id }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
                
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Informations Médicales</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Groupe sanguin:</label>
                        <span>{{ student.blood_type || 'Non renseigné' }}</span>
                      </div>
                      <div class="info-item">
                        <label>Besoins spéciaux:</label>
                        <span>{{ student.has_special_needs ? 'Oui' : 'Non' }}</span>
                      </div>
                      <div v-if="student.special_needs_details" class="info-item">
                        <label>Détails besoins spéciaux:</label>
                        <span>{{ student.special_needs_details }}</span>
                      </div>
                      <div v-if="student.medical_conditions" class="info-item">
                        <label>Conditions médicales:</label>
                        <span>{{ student.medical_conditions }}</span>
                      </div>
                      <div v-if="student.medications" class="info-item">
                        <label>Médicaments:</label>
                        <span>{{ student.medications }}</span>
                      </div>
                      <div v-if="student.dietary_restrictions" class="info-item">
                        <label>Restrictions alimentaires:</label>
                        <span>{{ student.dietary_restrictions }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
              </BRow>
            </BTab>

            <!-- Services & Notes -->
            <BTab title="Services & Notes">
              <BRow>
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Services Scolaires</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div class="info-item">
                        <label>Transport:</label>
                        <span>{{ getTransportLabel(student.transport_method) }}</span>
                      </div>
                      <div v-if="student.bus_route" class="info-item">
                        <label>Ligne de bus:</label>
                        <span>{{ student.bus_route }}</span>
                      </div>
                      <div class="info-item">
                        <label>Restauration:</label>
                        <span>{{ getMealPlanLabel(student.meal_plan) }}</span>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
                
                <BCol md="6">
                  <BCard>
                    <BCardHeader>
                      <BCardTitle class="mb-0">Observations</BCardTitle>
                    </BCardHeader>
                    <BCardBody>
                      <div v-if="student.observations">
                        <p class="mb-0">{{ student.observations }}</p>
                      </div>
                      <div v-else class="text-muted">
                        <em>Aucune observation</em>
                      </div>
                    </BCardBody>
                  </BCard>
                </BCol>
              </BRow>
            </BTab>
          </BTabs>
        </BCol>
      </BRow>
    </div>

    <div v-else class="text-center py-5">
      <div class="avatar-md mx-auto mb-4">
        <div class="avatar-title bg-light text-danger rounded-circle fs-2">
          <i class="ri-user-unfollow-line"></i>
        </div>
      </div>
      <h5 class="mb-1">Élève introuvable</h5>
      <p class="text-muted mb-3">L'élève demandé n'existe pas ou n'est pas accessible.</p>
      <BButton variant="primary" @click="goBack">
        <i class="ri-arrow-left-line me-1"></i>
        Retour à la liste
      </BButton>
    </div>

    <!-- Edit Student Modal -->
    <StudentForm
      v-if="editingStudent"
      :visible="!!editingStudent"
      :student="editingStudent"
      @close="editingStudent = null"
      @saved="handleStudentSaved"
    />
  </VerticalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue'
import StudentForm from './components/StudentForm.vue'
// Remove import - we'll define formatDate locally
import { showToast } from '@/helpers/toast'

const route = useRoute()
const router = useRouter()

// Reactive data
const student = ref(null)
const loading = ref(false)
const activeTab = ref(0)
const editingStudent = ref(null)

// Methods
const loadStudent = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/students/${route.params.id}`)
    student.value = data
  } catch (error) {
    console.error('Error loading student:', error)
    if (error.response?.status === 404) {
      student.value = null
    } else {
      showToast('Erreur lors du chargement des détails de l\'élève', 'error')
    }
  } finally {
    loading.value = false
  }
}

const editStudent = () => {
  editingStudent.value = { ...student.value }
}

const handleStudentSaved = () => {
  editingStudent.value = null
  loadStudent()
  showToast('Élève modifié avec succès', 'success')
}

const goBack = () => {
  router.go(-1)
}

const getTransportLabel = (method) => {
  const labels = {
    'none': 'Aucun',
    'school_bus': 'Bus scolaire',
    'private': 'Transport privé',
    'walking': 'À pied'
  }
  return labels[method] || 'Non renseigné'
}

const getMealPlanLabel = (plan) => {
  const labels = {
    'none': 'Aucun',
    'lunch_only': 'Déjeuner uniquement',
    'full_meals': 'Tous les repas'
  }
  return labels[plan] || 'Non renseigné'
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
onMounted(() => {
  loadStudent()
})
</script>

<style scoped>
.student-profile-card {
  position: sticky;
  top: 2rem;
}

.student-photo-container {
  width: 120px;
  height: 120px;
  margin: 0 auto;
  position: relative;
}

.student-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  border: 4px solid #f0f0f0;
}

.student-photo-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #405189, #0ab39c);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 4px solid #f0f0f0;
}

.photo-initials {
  color: white;
  font-weight: 600;
  font-size: 2rem;
}

.quick-stat-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.quick-stat-item:last-child {
  border-bottom: none;
}

.info-item {
  display: flex;
  margin-bottom: 1rem;
  align-items: flex-start;
}

.info-item label {
  font-weight: 600;
  min-width: 140px;
  margin-right: 1rem;
  color: #495057;
  margin-bottom: 0;
}

.info-item span {
  flex: 1;
  color: #6c757d;
}

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