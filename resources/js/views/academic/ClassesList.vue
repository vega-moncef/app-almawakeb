<template>
  <VerticalLayout>
    <PageTitle title="Classes & Sections" subtitle="Gestion Académique" />
    
    <!-- Add Class Button -->
    <b-row class="mb-3">
      <b-col>
        <b-button variant="primary" @click="showAddForm = true">
          <Icon icon="solar:add-circle-broken" class="me-1" />
          Ajouter une Classe
        </b-button>
      </b-col>
    </b-row>

    <!-- Filters Section -->
    <b-row class="mb-3">
      <b-col>
        <b-card no-body>
          <b-card-body>
            <b-row class="g-3">
              <b-col md="3">
                <b-form-select v-model="filters.school_id" @change="onSchoolChange">
                  <option value="">Toutes les écoles</option>
                  <option v-for="school in schools" :key="school.id" :value="school.id">
                    {{ school.name }}
                  </option>
                </b-form-select>
              </b-col>
              <b-col md="3">
                <b-form-select v-model="filters.level_id" @change="loadClasses">
                  <option value="">Tous les niveaux</option>
                  <option v-for="level in filteredLevels" :key="level.id" :value="level.id">
                    {{ level.name }}
                  </option>
                </b-form-select>
              </b-col>
              <b-col md="4">
                <b-input-group>
                  <b-form-input 
                    v-model="filters.search" 
                    placeholder="Rechercher par nom de classe..."
                    @input="debounceSearch"
                  />
                  <template #append>
                    <b-button variant="outline-secondary">
                      <Icon icon="solar:magnifer-broken" />
                    </b-button>
                  </template>
                </b-input-group>
              </b-col>
              <b-col md="2">
                <b-button variant="primary" @click="loadClasses" :disabled="loading">
                  <Icon icon="solar:refresh-broken" class="me-1" />
                  Actualiser
                </b-button>
              </b-col>
            </b-row>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Classes Grid -->
    <b-row>
      <b-col xl="12">
        <b-card no-body>
          <b-card-header class="d-flex justify-content-between align-items-center border-bottom">
            <div>
              <b-card-title class="mb-0">Liste des Classes</b-card-title>
              <small class="text-muted">{{ totalClasses }} classe(s) au total</small>
            </div>
          </b-card-header>
          
          <div v-if="loading" class="text-center p-4">
            <b-spinner class="me-2"></b-spinner>
            Chargement des classes...
          </div>
          
          <div v-else-if="filteredClasses.length === 0" class="text-center p-4">
            <Icon icon="solar:file-text-broken" class="fs-1 text-muted mb-3" />
            <p class="text-muted mb-0">Aucune classe trouvée</p>
          </div>
          
          <b-table-simple v-else responsive hover class="align-middle text-nowrap table-centered mb-0">
            <b-thead class="bg-light-subtle">
              <b-tr>
                <b-th>École</b-th>
                <b-th>Niveau</b-th>
                <b-th>Nom de la Classe</b-th>
                <b-th>Capacité</b-th>
                <b-th>Étudiants Actuels</b-th>
                <b-th>Occupation</b-th>
                <b-th>Statut</b-th>
                <b-th>Actions</b-th>
              </b-tr>
            </b-thead>
            <b-tbody>
              <b-tr v-for="classRoom in filteredClasses" :key="classRoom.id">
                <b-td>
                  <span class="badge bg-info-subtle text-info">
                    {{ classRoom.level.school.name }}
                  </span>
                </b-td>
                <b-td>{{ classRoom.level.name }}</b-td>
                <b-td>
                  <strong>{{ classRoom.full_name }}</strong>
                </b-td>
                <b-td>{{ classRoom.capacity }}</b-td>
                <b-td>{{ classRoom.current_students }}</b-td>
                <b-td>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                      <b-progress 
                        :value="classRoom.occupancy_percentage" 
                        :variant="getOccupancyColor(classRoom.occupancy_percentage)"
                        height="8px"
                      />
                    </div>
                    <span class="ms-2 small">{{ classRoom.occupancy_percentage }}%</span>
                  </div>
                </b-td>
                <b-td>
                  <b-badge 
                    :variant="getStatusBadgeVariant(classRoom.status)" 
                    class="badge-outline"
                  >
                    {{ getStatusLabel(classRoom.status) }}
                  </b-badge>
                </b-td>
                <b-td>
                  <div class="d-flex gap-2">
                    <button 
                      type="button" 
                      class="btn btn-outline-primary btn-sm"
                      @click="editClass(classRoom)"
                      title="Modifier"
                    >
                      <Icon icon="solar:pen-broken" />
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-danger btn-sm"
                      @click="deleteClass(classRoom)"
                      :disabled="classRoom.current_students > 0"
                      title="Supprimer"
                    >
                        <Icon icon="solar:trash-bin-minimalistic-2-broken" />
                    </button>
                  </div>
                </b-td>
              </b-tr>
            </b-tbody>
          </b-table-simple>
        </b-card>
      </b-col>
    </b-row>

    <!-- Add/Edit Class Modal -->
    <b-modal 
      v-model="showAddForm" 
      :title="editingClass ? 'Modifier la Classe' : 'Ajouter une Classe'"
      size="md"
      hide-footer
      @hide="resetForm"
    >
      <b-form @submit.prevent="handleSaveClick">
        <b-row>
          <b-col md="12">
            <b-form-group label="École" label-for="school-select">
              <b-form-select 
                id="school-select"
                v-model="classForm.school_id" 
                :options="schoolOptions"
                @change="onFormSchoolChange"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>
        
        <b-row>
          <b-col md="12">
            <b-form-group label="Niveau" label-for="level-select">
              <b-form-select 
                id="level-select"
                v-model="classForm.level_id" 
                :options="levelOptions"
                :disabled="!classForm.school_id"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <b-row>
          <b-col md="6">
            <b-form-group label="Nom de la Section" label-for="class-name">
              <b-form-input 
                id="class-name"
                v-model="classForm.name" 
                placeholder="A, B, C, etc."
                required
              />
              <small class="text-muted">Ex: A, B, C pour les sections</small>
            </b-form-group>
          </b-col>
          <b-col md="6">
            <b-form-group label="Capacité" label-for="class-capacity">
              <b-form-input 
                id="class-capacity"
                v-model.number="classForm.capacity" 
                type="number"
                min="1"
                max="50"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <b-row v-if="editingClass">
          <b-col md="12">
            <b-form-group>
              <b-form-checkbox 
                v-model="classForm.is_active"
                switch
                size="lg"
              >
                Classe active
              </b-form-checkbox>
            </b-form-group>
          </b-col>
        </b-row>


        <!-- Submit button inside form -->
        <b-row class="mt-3">
          <b-col md="12" class="d-flex justify-content-end gap-2">
            <b-button variant="secondary" @click="showAddForm = false">Annuler</b-button>
            <b-button 
              variant="primary" 
              type="submit"
              :disabled="saving"
            >
              <b-spinner v-if="saving" small class="me-1" />
              {{ editingClass ? 'Modifier' : 'Ajouter' }}
            </b-button>
          </b-col>
        </b-row>
      </b-form>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import PageTitle from '@/components/PageTitle.vue';
import axios from 'axios';

// Reactive data
const classes = ref([]);
const schools = ref([]);
const levels = ref([]);
const currentAcademicYear = ref(null);
const loading = ref(false);
const saving = ref(false);
const showAddForm = ref(false);
const editingClass = ref(null);

// Filters
const filters = ref({
  school_id: '',
  level_id: '',
  search: ''
});

// Form data
const classForm = ref({
  school_id: '',
  level_id: '',
  name: '',
  capacity: 30,
  academic_year_id: '',
  is_active: true
});

// Computed properties
const totalClasses = computed(() => classes.value.length);

const filteredClasses = computed(() => {
  let result = classes.value;
  
  if (filters.value.school_id) {
    result = result.filter(c => c.level.school.id == filters.value.school_id);
  }
  
  if (filters.value.level_id) {
    result = result.filter(c => c.level.id == filters.value.level_id);
  }
  
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(c => 
      c.full_name.toLowerCase().includes(search) ||
      c.name.toLowerCase().includes(search)
    );
  }
  
  return result;
});

const filteredLevels = computed(() => {
  if (!filters.value.school_id) return levels.value;
  return levels.value.filter(level => level.school.id == filters.value.school_id);
});

const schoolOptions = computed(() => [
  { value: '', text: 'Sélectionnez une école' },
  ...schools.value.map(school => ({ value: school.id, text: school.name }))
]);

const levelOptions = computed(() => {
  if (!classForm.value.school_id) return [{ value: '', text: 'Sélectionnez d\'abord une école' }];
  
  const schoolLevels = levels.value.filter(level => level.school.id == classForm.value.school_id);
  return [
    { value: '', text: 'Sélectionnez un niveau' },
    ...schoolLevels.map(level => ({ value: level.id, text: level.name }))
  ];
});

// Methods
const loadClasses = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/classes');
    
    if (response.data.success) {
      classes.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading classes:', error);
  } finally {
    loading.value = false;
  }
};

const loadSchools = async () => {
  try {
    const response = await axios.get('/api/schools');
    
    if (response.data.success) {
      schools.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading schools:', error);
  }
};

const loadLevels = async () => {
  try {
    const response = await axios.get('/api/levels');
    
    if (response.data.success) {
      levels.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading levels:', error);
  }
};

const loadCurrentAcademicYear = async () => {
  try {
    const response = await axios.get('/api/academic-years/current');
    
    if (response.data.success) {
      currentAcademicYear.value = response.data.data;
      // Set the academic year ID in the form
      classForm.value.academic_year_id = response.data.data.id;
    }
  } catch (error) {
    console.error('Error loading current academic year:', error);
  }
};

const onSchoolChange = () => {
  filters.value.level_id = '';
  loadClasses();
};

const onFormSchoolChange = () => {
  classForm.value.level_id = '';
};

const handleSaveClick = async () => {
  // Basic form validation
  if (!classForm.value.school_id) {
    alert('Veuillez sélectionner une école');
    return;
  }
  
  if (!classForm.value.level_id) {
    alert('Veuillez sélectionner un niveau');
    return;
  }
  
  if (!classForm.value.name) {
    alert('Veuillez entrer un nom de section');
    return;
  }
  
  if (!classForm.value.academic_year_id) {
    alert('Année académique manquante - veuillez recharger la page');
    return;
  }
  
  // Call the actual save function
  await saveClass();
};

const saveClass = async () => {
  try {
    saving.value = true;
    
    let response;
    if (editingClass.value) {
      // Update existing class
      response = await axios.put(`/api/classes/${editingClass.value.id}`, classForm.value);
    } else {
      // Create new class
      response = await axios.post('/api/classes', classForm.value);
    }
    
    if (response.data.success) {
      showAddForm.value = false;
      resetForm();
      await loadClasses();
    } else {
      console.error('API Error:', response.data);
      alert(response.data.message || 'Erreur lors de la sauvegarde');
    }
  } catch (error) {
    console.error('Error saving class:', error);
    if (error.response) {
      // Server responded with error status
      console.error('Server error:', error.response.data);
      alert(error.response.data.message || 'Erreur du serveur');
    } else {
      // Network or other error
      alert('Erreur lors de la sauvegarde: ' + error.message);
    }
  } finally {
    saving.value = false;
  }
};

const editClass = (classRoom) => {
  editingClass.value = classRoom;
  classForm.value = {
    school_id: classRoom.level.school.id,
    level_id: classRoom.level.id,
    name: classRoom.name,
    capacity: classRoom.capacity,
    academic_year_id: classRoom.academic_year_id,
    is_active: classRoom.is_active
  };
  showAddForm.value = true;
};

const deleteClass = async (classRoom) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer la classe ${classRoom.full_name} ?`)) {
    return;
  }
  
  try {
    const response = await axios.delete(`/api/classes/${classRoom.id}`);
    
    if (response.data.success) {
      await loadClasses();
    } else {
      alert(response.data.message || 'Erreur lors de la suppression');
    }
  } catch (error) {
    console.error('Error deleting class:', error);
    if (error.response) {
      alert(error.response.data.message || 'Erreur du serveur');
    } else {
      alert('Erreur lors de la suppression: ' + error.message);
    }
  }
};

const resetForm = () => {
  editingClass.value = null;
  classForm.value = {
    school_id: '',
    level_id: '',
    name: '',
    capacity: 30,
    academic_year_id: currentAcademicYear.value?.id || '',
    is_active: true
  };
};

const getOccupancyColor = (percentage) => {
  if (percentage >= 100) return 'danger';
  if (percentage >= 80) return 'warning';
  if (percentage >= 50) return 'info';
  return 'success';
};

const getStatusBadgeVariant = (status) => {
  const variants = {
    'full': 'danger',
    'almost_full': 'warning',
    'half_full': 'info',
    'available': 'success'
  };
  return variants[status] || 'secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    'full': 'Complète',
    'almost_full': 'Presque pleine',
    'half_full': 'À moitié pleine',
    'available': 'Disponible'
  };
  return labels[status] || status;
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    // The filtering happens automatically via computed property
  }, 300);
};

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadClasses(),
    loadSchools(),
    loadLevels(),
    loadCurrentAcademicYear()
  ]);
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', handleAcademicYearChange);
});

// Cleanup on unmount
onUnmounted(() => {
  window.removeEventListener('academic-year-changed', handleAcademicYearChange);
});

// Handle academic year change event
const handleAcademicYearChange = async (event) => {
  console.log('📅 Academic year changed, reloading classes...', event.detail);
  // Update the current academic year in the form
  await loadCurrentAcademicYear();
  // Reload the classes list for the new academic year
  await loadClasses();
};
</script>

<style scoped>
.badge-outline {
  border: 1px solid currentColor;
}
</style>