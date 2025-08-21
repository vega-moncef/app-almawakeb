<template>
<VerticalLayout>
  <div class="container-fluid">
    <!-- Page Title -->
    <PageTitle :title="'Résultats des Tests'" :sub-title="'Gestion des résultats et corrections'" />
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Total Assignations',
            statistic: stats.total_assignments || 0,
            prefix: '',
            icon: 'fas fa-clipboard-list'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Terminés',
            statistic: stats.completed_tests || 0,
            prefix: '',
            icon: 'fas fa-check-circle'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'En Attente',
            statistic: stats.pending_tests || 0,
            prefix: '',
            icon: 'fas fa-clock'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Étudiants Réussis',
            statistic: stats.passed_students || 0,
            prefix: '',
            icon: 'fas fa-user-check'
          }"
        />
      </div>
    </div>

    <!-- Filters & Results -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="card-title">Résultats des Tests</h4>
            </div>
          </div>
          
          <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
              <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="filters.search"
                  placeholder="Nom de l'étudiant..."
                  @input="debouncedSearch"
                >
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Test</label>
                <select 
                  class="form-select" 
                  v-model="filters.test_id"
                  @change="loadTestResults"
                >
                  <option value="">Tous les tests</option>
                  <option 
                    v-for="test in tests" 
                    :key="test.id" 
                    :value="test.id"
                  >
                    {{ test.title }}
                  </option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select 
                  class="form-select" 
                  v-model="filters.status"
                  @change="loadTestResults"
                >
                  <option value="">Tous les statuts</option>
                  <option value="assigned">Assigné</option>
                  <option value="in_progress">En cours</option>
                  <option value="completed">Terminé</option>
                  <option value="absent">Absent</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Décision</label>
                <select 
                  class="form-select" 
                  v-model="filters.admission_decision"
                  @change="loadTestResults"
                >
                  <option value="">Toutes les décisions</option>
                  <option value="pending">En attente</option>
                  <option value="accepted">Accepté</option>
                  <option value="rejected">Refusé</option>
                </select>
              </div>
            </div>

            <!-- Results Table -->
            <div class="table-responsive">
              <table class="table table-hover table-nowrap">
                <thead class="table-light">
                  <tr>
                    <th>Étudiant</th>
                    <th>Test</th>
                    <th>Date d'assignation</th>
                    <th>Heure de début</th>
                    <th>Statut</th>
                    <th>Score</th>
                    <th>Résultat</th>
                    <th>Décision</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="9" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Chargement...</span>
                      </div>
                      Chargement des résultats...
                    </td>
                  </tr>
                  <tr v-else-if="testResults.length === 0">
                    <td colspan="9" class="text-center py-4 text-muted">
                      <i class="fas fa-clipboard-list fa-2x mb-3 d-block"></i>
                      Aucun résultat trouvé
                    </td>
                  </tr>
                  <tr v-else v-for="result in testResults" :key="result.id">
                    <td>
                      <div>
                        <h6 class="mb-1">
                          {{ result.student_visit?.student_first_name }} 
                          {{ result.student_visit?.student_last_name }}
                        </h6>
                        <small class="text-muted">
                          {{ result.student_visit?.student_gender }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <div>
                        <h6 class="mb-1">{{ result.test?.title }}</h6>
                        <small class="text-muted">
                          <span 
                            class="badge badge-sm"
                            :class="`bg-${result.test?.type === 'ORAL' ? 'primary' : 'success'}`"
                          >
                            {{ result.test?.type }}
                          </span>
                          {{ result.test?.level?.name }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ formatDateTime(result.assigned_at) }}
                      </small>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ result.started_at ? formatDateTime(result.started_at) : '-' }}
                      </small>
                    </td>
                    <td>
                      <span 
                        class="badge"
                        :class="`bg-${getStatusColor(result.status)}`"
                      >
                        {{ getStatusLabel(result.status) }}
                      </span>
                    </td>
                    <td>
                      <div v-if="result.total_score !== null">
                        <strong>{{ result.total_score }}/{{ result.test?.total_marks }}</strong>
                        <br>
                        <small class="text-muted">{{ result.percentage }}%</small>
                      </div>
                      <small v-else class="text-muted">-</small>
                    </td>
                    <td>
                      <span 
                        v-if="result.passed !== null"
                        class="badge"
                        :class="`bg-${result.passed ? 'success' : 'danger'}`"
                      >
                        {{ result.passed ? 'Réussi' : 'Échoué' }}
                      </span>
                      <small v-else class="text-muted">-</small>
                    </td>
                    <td>
                      <span 
                        class="badge"
                        :class="`bg-${getDecisionColor(result.admission_decision)}`"
                      >
                        {{ getDecisionLabel(result.admission_decision) }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button 
                          type="button" 
                          class="btn btn-outline-primary"
                          @click="editResults(result)"
                          title="Saisir/Modifier Résultats"
                        >
                          <Icon icon="solar:pen-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-success"
                          @click="viewDetails(result)"
                          title="Voir Détails"
                        >
                          <Icon icon="solar:eye-broken" class="me-2" />
                        </button>
                        
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
              <div class="text-muted">
                Affichage {{ pagination.from }} à {{ pagination.to }} 
                sur {{ pagination.total }} résultats
              </div>
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                    <button 
                      class="page-link" 
                      @click="loadPage(pagination.current_page - 1)"
                      :disabled="!pagination.prev_page_url"
                    >
                      Précédent
                    </button>
                  </li>
                  
                  <li 
                    v-for="page in visiblePages" 
                    :key="page"
                    class="page-item" 
                    :class="{ active: page === pagination.current_page }"
                  >
                    <button 
                      class="page-link" 
                      @click="loadPage(page)"
                    >
                      {{ page }}
                    </button>
                  </li>
                  
                  <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                    <button 
                      class="page-link" 
                      @click="loadPage(pagination.current_page + 1)"
                      :disabled="!pagination.next_page_url"
                    >
                      Suivant
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Decision Modal -->
    <div class="modal fade" id="decisionModal" tabindex="-1" v-if="selectedResult">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Décision d'Admission</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <strong>Étudiant:</strong> 
              {{ selectedResult.student_visit?.student_first_name }} 
              {{ selectedResult.student_visit?.student_last_name }}
            </div>
            <div class="mb-3">
              <strong>Test:</strong> {{ selectedResult.test?.title }}
            </div>
            <div class="mb-3">
              <strong>Score:</strong> 
              {{ selectedResult.total_score }}/{{ selectedResult.test?.total_marks }} 
              ({{ selectedResult.percentage }}%)
            </div>
            <div class="mb-3">
              <strong>Résultat:</strong> 
              <span 
                class="badge"
                :class="`bg-${selectedResult.passed ? 'success' : 'danger'}`"
              >
                {{ selectedResult.passed ? 'Réussi' : 'Échoué' }}
              </span>
            </div>
            
            <form @submit.prevent="submitDecision">
              <div class="mb-3">
                <label class="form-label">Décision *</label>
                <select 
                  class="form-select" 
                  v-model="decisionForm.admission_decision"
                  required
                >
                  <option value="">Sélectionner une décision</option>
                  <option value="accepted">Accepté</option>
                  <option value="rejected">Refusé</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="decisionForm.notes"
                  placeholder="Commentaires sur la décision..."
                ></textarea>
              </div>
              
              <div class="d-flex justify-content-end gap-2">
                <button 
                  type="button" 
                  class="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Annuler
                </button>
                <button 
                  type="submit" 
                  class="btn"
                  :class="decisionForm.admission_decision === 'accepted' ? 'btn-success' : 'btn-danger'"
                  :disabled="!decisionForm.admission_decision"
                >
                  {{ decisionForm.admission_decision === 'accepted' ? 'Accepter' : 'Refuser' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</VerticalLayout>
</template>

<script setup>
import VerticalLayout from "@/layouts/VerticalLayout.vue";
import PageTitle from "@/components/PageTitle.vue";
import StatisticsCard from "@/views/property/list/components/StatisticsCard.vue";
import { Icon } from "@iconify/vue";
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter';

// Use Vue Router
const router = useRouter();

// Use academic year filtering composable
const { addAcademicYearFilter } = useAcademicYearFilter(() => {
  loadTestResults();
  loadTests();
  loadStats();
});

// Reactive data
const loading = ref(false);
const testResults = ref([]);
const tests = ref([]);
const stats = ref({});
const pagination = ref({});
const selectedResult = ref(null);

const filters = reactive({
  search: '',
  test_id: '',
  status: '',
  admission_decision: '',
  page: 1,
  per_page: 15
});

const decisionForm = reactive({
  admission_decision: '',
  notes: ''
});

// Computed properties
const visiblePages = computed(() => {
  const current = pagination.value.current_page || 1;
  const last = pagination.value.last_page || 1;
  const pages = [];
  
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadTestResults();
  }, 500);
};

// Lifecycle
onMounted(() => {
  loadTestResults();
  loadTests();
  loadStats();
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', () => {
    console.log('Academic year changed, reloading test results...');
    loadTestResults();
    loadTests();
    loadStats();
  });
});

// Methods
const loadTestResults = async () => {
  loading.value = true;
  try {
    const params = addAcademicYearFilter({
      search: filters.search,
      test_id: filters.test_id,
      status: filters.status,
      admission_decision: filters.admission_decision,
      page: filters.page,
      per_page: filters.per_page
    });

    const response = await axios.get('/api/student-tests', { params });
    
    if (response.data.success) {
      testResults.value = response.data.data.data || [];
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total,
        from: response.data.data.from,
        to: response.data.data.to,
        prev_page_url: response.data.data.prev_page_url,
        next_page_url: response.data.data.next_page_url
      };
    }
  } catch (error) {
    console.error('Error loading test results:', error);
    alert('Erreur lors du chargement des résultats');
  } finally {
    loading.value = false;
  }
};

const loadTests = async () => {
  try {
    const params = addAcademicYearFilter({});
    const response = await axios.get('/api/tests', { params });
    
    if (response.data.success) {
      tests.value = response.data.data.data || [];
    }
  } catch (error) {
    console.error('Error loading tests:', error);
  }
};

const loadStats = async () => {
  try {
    const response = await axios.get('/api/student-tests/stats');
    
    if (response.data.success) {
      stats.value = response.data.data || {};
    }
  } catch (error) {
    console.error('Error loading stats:', error);
  }
};

const loadPage = (page) => {
  filters.page = page;
  loadTestResults();
};

const editResults = (result) => {
  router.push({ 
    name: 'admissions.test-results.edit', 
    params: { id: result.id } 
  });
};

const viewDetails = (result) => {
  // You can implement a detail view or modal here
  console.log('View details for:', result);
  alert('Fonctionnalité de détail à implémenter');
};

const makeDecision = (result) => {
  selectedResult.value = result;
  decisionForm.admission_decision = '';
  decisionForm.notes = '';
  
  // Show modal (you might need to use Bootstrap's modal API)
  const modal = new bootstrap.Modal(document.getElementById('decisionModal'));
  modal.show();
};

const submitDecision = async () => {
  try {
    const response = await axios.patch(
      `/api/student-tests/${selectedResult.value.id}/admission-decision`, 
      decisionForm
    );
    
    if (response.data.success) {
      alert('Décision enregistrée avec succès!');
      
      // Close modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('decisionModal'));
      modal.hide();
      
      // Reload data
      loadTestResults();
      loadStats();
    }
  } catch (error) {
    console.error('Error updating decision:', error);
    alert('Erreur lors de l\'enregistrement de la décision');
  }
};

// Utility functions
const formatDateTime = (datetime) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleDateString('fr-FR') + ' ' + 
         new Date(datetime).toLocaleTimeString('fr-FR', { 
           hour: '2-digit', 
           minute: '2-digit' 
         });
};

const getStatusColor = (status) => {
  const colors = {
    'assigned': 'info',
    'in_progress': 'warning',
    'completed': 'success',
    'absent': 'danger'
  };
  return colors[status] || 'secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    'assigned': 'Assigné',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'absent': 'Absent'
  };
  return labels[status] || status;
};

const getDecisionColor = (decision) => {
  const colors = {
    'pending': 'warning',
    'accepted': 'success',
    'rejected': 'danger'
  };
  return colors[decision] || 'secondary';
};

const getDecisionLabel = (decision) => {
  const labels = {
    'pending': 'En attente',
    'accepted': 'Accepté',
    'rejected': 'Refusé'
  };
  return labels[decision] || decision;
};
</script>

<style scoped>
.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.table td {
  vertical-align: middle;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.badge {
  font-size: 0.75em;
}

.badge-sm {
  font-size: 0.7em;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.modal-content {
  border-radius: 0.5rem;
}
</style>