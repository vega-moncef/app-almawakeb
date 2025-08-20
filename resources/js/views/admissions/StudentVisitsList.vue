<template>
  <VerticalLayout>
    <PageTitle title="Liste des Visites" subtitle="Admissions" />
    
    <!-- Status Statistics Cards -->
    <b-row>
      <b-col md="6" xl="3" v-for="(stat, idx) in statusStatistics" :key="idx">
        <StatisticsCard :item="stat" />
      </b-col>
    </b-row>
    
    <!-- Filters Section -->
    <b-row class="mb-3">
      <b-col>
        <b-card no-body>
          <b-card-body>
            <b-row class="g-3">
              <b-col md="3">
                <b-form-select v-model="filters.status" @change="loadVisits">
                  <option value="">Tous les statuts</option>
                  <option value="pending">En attente</option>
                  <option value="test_scheduled">Test programmé</option>
                  <option value="tested">Testé</option>
                  <option value="accepted">Accepté</option>
                  <option value="rejected">Refusé</option>
                </b-form-select>
              </b-col>
              <b-col md="3">
                <b-form-select v-model="filters.school_id" @change="loadVisits">
                  <option value="">Toutes les écoles</option>
                  <option v-for="school in schools" :key="school.id" :value="school.id">
                    {{ school.name }}
                  </option>
                </b-form-select>
              </b-col>
              <b-col md="4">
                <b-input-group>
                  <b-form-input 
                    v-model="filters.search" 
                    placeholder="Rechercher par nom d'élève ou parent..."
                    @input="debounceSearch"
                  />
                  <b-input-group-append>
                    <b-button variant="outline-secondary">
                      <Icon icon="solar:magnifer-broken" />
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </b-col>
              <b-col md="2">
                <b-button variant="primary" @click="loadVisits" :disabled="loading">
                  <Icon icon="solar:refresh-broken" class="me-1" />
                  Actualiser
                </b-button>
              </b-col>
            </b-row>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Visits Table -->
    <b-row>
      <b-col xl="12">
        <b-card no-body>
          <b-card-header class="d-flex justify-content-between align-items-center border-bottom">
            <div>
              <b-card-title class="mb-0">Liste des Visites Étudiants</b-card-title>
              <small class="text-muted">{{ totalVisits }} visite(s) au total</small>
            </div>
            <b-dropdown variant="link" toggle-class="p-0 m-0" menu-class="dropdown-menu-end" no-caret>
              <template v-slot:button-content>
                <b-button size="sm" variant="outline-light" class="dropdown-toggle">
                  Actions
                </b-button>
              </template>
              <b-dropdown-item @click="exportVisits">
                <Icon icon="solar:download-broken" class="me-2" />
                Exporter
              </b-dropdown-item>
              <b-dropdown-item @click="refreshData">
                <Icon icon="solar:refresh-broken" class="me-2" />
                Actualiser
              </b-dropdown-item>
            </b-dropdown>
          </b-card-header>
          
          <div v-if="loading" class="text-center p-4">
            <b-spinner class="me-2"></b-spinner>
            Chargement des visites...
          </div>
          
          <b-table-simple v-else responsive hover class="align-middle text-nowrap table-centered mb-0">
            <b-thead class="bg-light-subtle">
              <b-tr>
                <b-th style="width: 20px;">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectAll" v-model="selectAll" @change="toggleSelectAll">
                    <label class="form-check-label" for="selectAll"></label>
                  </div>
                </b-th>
                <b-th>Élève</b-th>
                <b-th>École/Niveau</b-th>
                <b-th>Date de Visite</b-th>
                <b-th>Parents</b-th>
                <b-th>Statut</b-th>
                <b-th>Date de Test</b-th>
                <b-th>Action</b-th>
              </b-tr>
            </b-thead>
            <b-tbody>
              <b-tr v-if="visits.length === 0">
                <b-td colspan="8" class="text-center text-muted py-4">
                  <Icon icon="solar:inbox-broken" class="fs-48 mb-2 d-block" />
                  Aucune visite trouvée
                </b-td>
              </b-tr>
              <b-tr v-for="(visit, idx) in visits" :key="visit.id">
                <b-td>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" :id="`check${visit.id}`" v-model="selectedVisits" :value="visit.id">
                    <label class="form-check-label" :for="`check${visit.id}`"></label>
                  </div>
                </b-td>
                <b-td>
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                      <Icon :icon="visit.student_gender === 'MASCULIN' ? 'solar:user-bold' : 'solar:user-bold'" class="fs-18 text-primary" />
                    </div>
                    <div>
                      <h6 class="mb-0 fw-semibold">{{ visit.student_first_name }} {{ visit.student_last_name }}</h6>
                      <small class="text-muted">
                        {{ visit.student_gender }} • {{ calculateAge(visit.birth_date) }} ans
                        <span v-if="visit.repeat_count > 0" class="ms-1">
                          <span class="badge bg-warning-subtle text-warning">{{ visit.repeat_count }} redoublement(s)</span>
                        </span>
                      </small>
                    </div>
                  </div>
                </b-td>
                <b-td>
                  <div>
                    <h6 class="mb-1 fw-medium">{{ visit.requested_school?.name }}</h6>
                    <small class="text-muted">{{ visit.requested_level?.name || 'Non spécifié' }}</small>
                  </div>
                </b-td>
                <b-td>
                  <div>
                    <span class="fw-medium">{{ formatDate(visit.visit_date) }}</span>
                    <br>
                    <small class="text-muted">{{ formatTime(visit.visit_date) }}</small>
                  </div>
                </b-td>
                <b-td>
                  <div>
                    <div class="mb-1">
                      <small class="text-muted">Père:</small> {{ visit.father_first_name }} {{ visit.father_last_name }}
                      <br><small class="text-muted">{{ visit.father_phone }}</small>
                    </div>
                    <div>
                      <small class="text-muted">Mère:</small> {{ visit.mother_first_name }} {{ visit.mother_last_name }}
                      <br><small class="text-muted">{{ visit.mother_phone }}</small>
                    </div>
                  </div>
                </b-td>
                <b-td>
                  <span class="badge py-2 px-3 fs-12 fw-medium" :class="getStatusBadgeClass(visit.status)">
                    {{ getStatusLabel(visit.status) }}
                  </span>
                </b-td>
                <b-td>
                  <div v-if="visit.test_date">
                    <span class="fw-medium">{{ formatDate(visit.test_date) }}</span>
                    <br>
                    <small class="text-muted">{{ formatTime(visit.test_date) }}</small>
                  </div>
                  <span v-else class="text-muted">Non programmé</span>
                </b-td>
                <b-td>
                  <div class="d-flex gap-2">
                    <b-button size="sm" variant="light" @click="viewVisit(visit)" title="Voir les détails">
                      <Icon icon="solar:eye-broken" class="fs-16" />
                    </b-button>
                    <b-button size="sm" variant="soft-primary" @click="editVisit(visit)" title="Modifier">
                      <Icon icon="solar:pen-2-broken" class="fs-16" />
                    </b-button>
                    <b-dropdown size="sm" variant="soft-info" no-caret toggle-class="p-1">
                      <template v-slot:button-content>
                        <Icon icon="solar:settings-broken" class="fs-16" />
                      </template>
                      <b-dropdown-item @click="updateStatus(visit, 'test_scheduled')">
                        <Icon icon="solar:calendar-broken" class="me-2" />
                        Programmer Test
                      </b-dropdown-item>
                      <b-dropdown-item @click="updateStatus(visit, 'accepted')">
                        <Icon icon="solar:check-circle-broken" class="me-2" />
                        Accepter
                      </b-dropdown-item>
                      <b-dropdown-item @click="updateStatus(visit, 'rejected')">
                        <Icon icon="solar:close-circle-broken" class="me-2" />
                        Refuser
                      </b-dropdown-item>
                      <b-dropdown-divider />
                      <b-dropdown-item @click="deleteVisit(visit)" class="text-danger">
                        <Icon icon="solar:trash-bin-minimalistic-2-broken" class="me-2" />
                        Supprimer
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                </b-td>
              </b-tr>
            </b-tbody>
          </b-table-simple>
          
          <b-card-footer v-if="pagination.total > 0">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted">
                Affichage {{ (pagination.current_page - 1) * pagination.per_page + 1 }} à 
                {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
                de {{ pagination.total }} résultats
              </div>
              <b-pagination
                v-model="pagination.current_page"
                :total-rows="pagination.total"
                :per-page="pagination.per_page"
                prev-text="Précédent"
                next-text="Suivant"
                class="mb-0"
                @change="loadVisits"
              />
            </div>
          </b-card-footer>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup lang="ts">
import VerticalLayout from "@/layouts/VerticalLayout.vue";
import PageTitle from "@/components/PageTitle.vue";
import StatisticsCard from "@/views/property/list/components/StatisticsCard.vue";
import { Icon } from "@iconify/vue";
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter';

// Use academic year filtering composable
const { addAcademicYearFilter } = useAcademicYearFilter(() => {
  loadSchools();
  loadVisits();
});

// Reactive data
const loading = ref(false);
const visits = ref([]);
const schools = ref([]);
const selectedVisits = ref([]);
const selectAll = ref(false);
const totalVisits = ref(0);

// Filters
const filters = reactive({
  status: '',
  school_id: '',
  search: '',
  academic_year_id: ''
});

// Pagination
const pagination = reactive({
  current_page: 1,
  per_page: 15,
  total: 0
});

// Status counts for statistics
const statusCounts = ref({
  pending: 0,
  test_scheduled: 0,
  tested: 0,
  accepted: 0,
  rejected: 0
});

// Computed statistics cards
const statusStatistics = computed(() => [
  {
    title: 'En Attente',
    statistic: statusCounts.value.pending,
    icon: 'solar:clock-circle-broken',
    prefix: '',
    suffix: '',
    growth: null
  },
  {
    title: 'Test Programmé',
    statistic: statusCounts.value.test_scheduled,
    icon: 'solar:calendar-broken',
    prefix: '',
    suffix: '',
    growth: null
  },
  {
    title: 'Testé',
    statistic: statusCounts.value.tested,
    icon: 'solar:document-text-broken',
    prefix: '',
    suffix: '',
    growth: null
  },
  {
    title: 'Accepté',
    statistic: statusCounts.value.accepted,
    icon: 'solar:check-circle-broken',
    prefix: '',
    suffix: '',
    growth: null
  }
]);

// Load data on mount
onMounted(() => {
  loadSchools();
  loadVisits();
});

// Load schools for filter
const loadSchools = async () => {
  try {
    const response = await axios.get('/api/student-visits/form-data');
    if (response.data.success) {
      schools.value = response.data.data.schools;
    }
  } catch (error) {
    console.error('Error loading schools:', error);
  }
};

// Load visits with filters and pagination
const loadVisits = async () => {
  loading.value = true;
  try {
    let apiParams = {};
    
    // Add filters
    if (filters.status) apiParams.status = filters.status;
    if (filters.school_id) apiParams.school_id = filters.school_id;
    if (filters.search) apiParams.search = filters.search;
    apiParams.page = pagination.current_page.toString();
    apiParams.per_page = pagination.per_page.toString();
    
    // Add academic year filter automatically
    apiParams = addAcademicYearFilter(apiParams);
    
    const params = new URLSearchParams(apiParams);

    const response = await axios.get(`/api/student-visits?${params.toString()}`);
    
    if (response.data.success) {
      visits.value = response.data.data.data;
      pagination.total = response.data.data.total;
      pagination.current_page = response.data.data.current_page;
      totalVisits.value = response.data.data.total;
      
      // Update status counts
      updateStatusCounts();
    }
  } catch (error) {
    console.error('Error loading visits:', error);
    alert('Erreur lors du chargement des visites');
  } finally {
    loading.value = false;
  }
};

// Update status counts from loaded visits
const updateStatusCounts = () => {
  const counts = {
    pending: 0,
    test_scheduled: 0,
    tested: 0,
    accepted: 0,
    rejected: 0
  };

  visits.value.forEach(visit => {
    if (counts.hasOwnProperty(visit.status)) {
      counts[visit.status]++;
    }
  });

  statusCounts.value = counts;
};

// Debounced search
let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.current_page = 1;
    loadVisits();
  }, 300);
};

// Select all functionality
const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedVisits.value = visits.value.map(visit => visit.id);
  } else {
    selectedVisits.value = [];
  }
};

// Watch selectedVisits to update selectAll
watch(selectedVisits, (newVal) => {
  selectAll.value = newVal.length === visits.value.length && visits.value.length > 0;
}, { deep: true });

// Status badge classes
const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'bg-warning-subtle text-warning',
    'test_scheduled': 'bg-info-subtle text-info',
    'tested': 'bg-secondary-subtle text-secondary',
    'accepted': 'bg-success-subtle text-success',
    'rejected': 'bg-danger-subtle text-danger'
  };
  return classes[status] || 'bg-secondary-subtle text-secondary';
};

// Status labels
const getStatusLabel = (status) => {
  const labels = {
    'pending': 'En attente',
    'test_scheduled': 'Test programmé',
    'tested': 'Testé',
    'accepted': 'Accepté',
    'rejected': 'Refusé'
  };
  return labels[status] || status;
};

// Date formatting
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const formatTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Calculate age
const calculateAge = (birthDate) => {
  const today = new Date();
  const birth = new Date(birthDate);
  let age = today.getFullYear() - birth.getFullYear();
  const monthDiff = today.getMonth() - birth.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--;
  }
  
  return age;
};

// Action handlers
const viewVisit = (visit) => {
  // TODO: Implement view visit modal or navigation
  console.log('View visit:', visit);
  alert(`Voir les détails de ${visit.student_first_name} ${visit.student_last_name}`);
};

const editVisit = (visit) => {
  // TODO: Implement edit visit navigation
  console.log('Edit visit:', visit);
  alert(`Modifier la visite de ${visit.student_first_name} ${visit.student_last_name}`);
};

const updateStatus = async (visit, newStatus) => {
  try {
    const response = await axios.patch(`/api/student-visits/${visit.id}/status`, {
      status: newStatus
    });
    
    if (response.data.success) {
      // Update local visit status
      const index = visits.value.findIndex(v => v.id === visit.id);
      if (index !== -1) {
        visits.value[index].status = newStatus;
      }
      updateStatusCounts();
      alert('Statut mis à jour avec succès');
    }
  } catch (error) {
    console.error('Error updating status:', error);
    alert('Erreur lors de la mise à jour du statut');
  }
};

const deleteVisit = async (visit) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer la visite de ${visit.student_first_name} ${visit.student_last_name} ?`)) {
    try {
      const response = await axios.delete(`/api/student-visits/${visit.id}`);
      
      if (response.data.success) {
        loadVisits(); // Reload the list
        alert('Visite supprimée avec succès');
      }
    } catch (error) {
      console.error('Error deleting visit:', error);
      alert('Erreur lors de la suppression');
    }
  }
};

const exportVisits = () => {
  // TODO: Implement export functionality
  alert('Fonctionnalité d\'export à implémenter');
};

const refreshData = () => {
  loadVisits();
};
</script>