<template>
<VerticalLayout>
  <div class="container-fluid">
    <!-- Page Title -->
    <PageTitle :title="'Décisions d\'Admission'" :sub-title="'Gestion des décisions d\'admission après les tests'" />
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Décisions Pendantes',
            statistic: stats.pending_decisions || 0,
            prefix: '',
            icon: 'fas fa-clock'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Étudiants Acceptés',
            statistic: stats.accepted_students || 0,
            prefix: '',
            icon: 'fas fa-user-check'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Étudiants Refusés',
            statistic: stats.rejected_students || 0,
            prefix: '',
            icon: 'fas fa-user-times'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Réussis',
            statistic: stats.passed_students || 0,
            prefix: '',
            icon: 'fas fa-trophy'
          }"
        />
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Actions Rapides</h5>
          </div>
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
              <button 
                type="button" 
                class="btn btn-success"
                @click="acceptAllPassed"
                :disabled="!stats.pending_decisions || loading"
              >
                <i class="fas fa-user-check"></i> Accepter Tous les Réussis
              </button>
              <button 
                type="button" 
                class="btn btn-outline-primary"
                @click="exportDecisions"
                :disabled="loading"
              >
                <i class="fas fa-download"></i> Exporter les Décisions
              </button>
              <button 
                type="button" 
                class="btn btn-outline-info"
                @click="generateAdmissionLetters"
                :disabled="!stats.accepted_students || loading"
              >
                <i class="fas fa-file-pdf"></i> Générer Lettres d'Admission
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Decisions Management -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="card-title">Décisions d'Admission</h4>
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
                  @change="loadDecisions"
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
                <label class="form-label">Résultat</label>
                <select 
                  class="form-select" 
                  v-model="filters.passed"
                  @change="loadDecisions"
                >
                  <option value="">Tous les résultats</option>
                  <option value="true">Réussi</option>
                  <option value="false">Échoué</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Décision</label>
                <select 
                  class="form-select" 
                  v-model="filters.admission_decision"
                  @change="loadDecisions"
                >
                  <option value="">Toutes les décisions</option>
                  <option value="pending">En attente</option>
                  <option value="accepted">Accepté</option>
                  <option value="rejected">Refusé</option>
                </select>
              </div>
            </div>

            <!-- Decisions Table -->
            <div class="table-responsive">
              <table class="table table-hover table-nowrap">
                <thead class="table-light">
                  <tr>
                    <th>
                      <input 
                        type="checkbox" 
                        class="form-check-input"
                        :checked="selectedDecisions.length === decisions.length && decisions.length > 0"
                        @change="toggleSelectAll"
                      >
                    </th>
                    <th>Étudiant</th>
                    <th>Test</th>
                    <th>Score</th>
                    <th>Résultat</th>
                    <th>Décision Actuelle</th>
                    <th>Date Test</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingDecisions">
                    <td colspan="8" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Chargement...</span>
                      </div>
                      Chargement des décisions...
                    </td>
                  </tr>
                  <tr v-else-if="decisions.length === 0">
                    <td colspan="8" class="text-center py-4 text-muted">
                      <i class="fas fa-gavel fa-2x mb-3 d-block"></i>
                      Aucune décision trouvée
                    </td>
                  </tr>
                  <tr v-else v-for="decision in decisions" :key="decision.id">
                    <td>
                      <input 
                        type="checkbox" 
                        class="form-check-input"
                        :value="decision.id"
                        v-model="selectedDecisions"
                      >
                    </td>
                    <td>
                      <div>
                        <h6 class="mb-1">
                          {{ decision.student_visit?.student_first_name }} 
                          {{ decision.student_visit?.student_last_name }}
                        </h6>
                        <small class="text-muted">
                          {{ decision.student_visit?.student_gender }} - 
                          {{ decision.student_visit?.requested_school?.name }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <div>
                        <h6 class="mb-1">{{ decision.test?.title }}</h6>
                        <small class="text-muted">
                          <span 
                            class="badge badge-sm"
                            :class="`bg-${decision.test?.type === 'ORAL' ? 'primary' : 'success'}`"
                          >
                            {{ decision.test?.type }}
                          </span>
                          {{ decision.test?.level?.name }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <div v-if="decision.total_score !== null">
                        <strong>{{ decision.total_score }}/{{ decision.test?.total_marks }}</strong>
                        <br>
                        <small class="text-muted">{{ decision.percentage }}%</small>
                      </div>
                      <small v-else class="text-muted">Non noté</small>
                    </td>
                    <td>
                      <span 
                        v-if="decision.passed !== null"
                        class="badge"
                        :class="`bg-${decision.passed ? 'success' : 'danger'}`"
                      >
                        {{ decision.passed ? 'RÉUSSI' : 'ÉCHOUÉ' }}
                      </span>
                      <small v-else class="text-muted">-</small>
                    </td>
                    <td>
                      <span 
                        class="badge"
                        :class="`bg-${getDecisionColor(decision.admission_decision)}`"
                      >
                        {{ getDecisionLabel(decision.admission_decision) }}
                      </span>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ formatDate(decision.completed_at) }}
                      </small>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button 
                          type="button" 
                          class="btn btn-outline-success"
                          @click="acceptStudent(decision)"
                          :disabled="decision.admission_decision === 'accepted'"
                          title="Accepter"
                        >
                          <Icon icon="solar:check-circle-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-danger"
                          @click="rejectStudent(decision)"
                          :disabled="decision.admission_decision === 'rejected'"
                          title="Refuser"
                        >
                          <Icon icon="solar:close-circle-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-info"
                          @click="viewDetails(decision)"
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

            <!-- Bulk Actions -->
            <div class="row mt-3" v-if="selectedDecisions.length > 0">
              <div class="col-12">
                <div class="alert alert-info">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <i class="fas fa-info-circle"></i>
                      <strong>{{ selectedDecisions.length }}</strong> étudiant(s) sélectionné(s)
                    </div>
                    <div class="d-flex gap-2">
                      <button 
                        type="button" 
                        class="btn btn-success btn-sm"
                        @click="bulkAccept"
                        :disabled="loading"
                      >
                        <i class="fas fa-check"></i> Accepter Sélection
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-danger btn-sm"
                        @click="bulkReject"
                        :disabled="loading"
                      >
                        <i class="fas fa-times"></i> Refuser Sélection
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
              <div class="text-muted">
                Affichage {{ pagination.from }} à {{ pagination.to }} 
                sur {{ pagination.total }} décisions
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
  loadDecisions();
  loadTests();
  loadStats();
});

// Reactive data
const loading = ref(false);
const loadingDecisions = ref(false);
const decisions = ref([]);
const tests = ref([]);
const stats = ref({});
const pagination = ref({});
const selectedDecisions = ref([]);

const filters = reactive({
  search: '',
  test_id: '',
  passed: '',
  admission_decision: 'pending', // Default to pending decisions
  page: 1,
  per_page: 15
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
    loadDecisions();
  }, 500);
};

// Lifecycle
onMounted(() => {
  loadDecisions();
  loadTests();
  loadStats();
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', () => {
    console.log('Academic year changed, reloading decisions...');
    loadDecisions();
    loadTests();
    loadStats();
  });
});

// Methods
const loadDecisions = async () => {
  loadingDecisions.value = true;
  try {
    const params = addAcademicYearFilter({
      search: filters.search,
      test_id: filters.test_id,
      admission_decision: filters.admission_decision,
      page: filters.page,
      per_page: filters.per_page,
      status: 'completed' // Only show completed tests
    });

    if (filters.passed !== '') {
      params.passed = filters.passed === 'true';
    }

    const response = await axios.get('/api/student-tests', { params });
    
    if (response.data.success) {
      decisions.value = response.data.data.data || [];
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
    console.error('Error loading decisions:', error);
    alert('Erreur lors du chargement des décisions');
  } finally {
    loadingDecisions.value = false;
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
  loadDecisions();
};

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedDecisions.value = decisions.value.map(d => d.id);
  } else {
    selectedDecisions.value = [];
  }
};

const acceptStudent = async (decision) => {
  if (!confirm(`Accepter ${decision.student_visit?.student_first_name} ${decision.student_visit?.student_last_name} ?`)) {
    return;
  }

  await updateDecision(decision.id, 'accepted', 'Accepté par décision individuelle');
};

const rejectStudent = async (decision) => {
  if (!confirm(`Refuser ${decision.student_visit?.student_first_name} ${decision.student_visit?.student_last_name} ?`)) {
    return;
  }

  await updateDecision(decision.id, 'rejected', 'Refusé par décision individuelle');
};

const bulkAccept = async () => {
  if (!confirm(`Accepter ${selectedDecisions.value.length} étudiant(s) sélectionné(s) ?`)) {
    return;
  }

  await bulkUpdateDecisions('accepted', 'Accepté en lot');
};

const bulkReject = async () => {
  if (!confirm(`Refuser ${selectedDecisions.value.length} étudiant(s) sélectionné(s) ?`)) {
    return;
  }

  await bulkUpdateDecisions('rejected', 'Refusé en lot');
};

const acceptAllPassed = async () => {
  if (!confirm('Accepter automatiquement tous les étudiants qui ont réussi leur test ?')) {
    return;
  }

  loading.value = true;
  try {
    // Get all passed students with pending decisions
    const params = addAcademicYearFilter({
      admission_decision: 'pending',
      passed: true,
      status: 'completed',
      per_page: 1000 // Get all
    });

    const response = await axios.get('/api/student-tests', { params });
    
    if (response.data.success) {
      const passedStudents = response.data.data.data || [];
      
      for (const student of passedStudents) {
        await updateDecision(student.id, 'accepted', 'Accepté automatiquement (réussite du test)');
      }
      
      alert(`${passedStudents.length} étudiant(s) accepté(s) automatiquement!`);
      loadDecisions();
      loadStats();
    }
  } catch (error) {
    console.error('Error in bulk accept:', error);
    alert('Erreur lors de l\'acceptation automatique');
  } finally {
    loading.value = false;
  }
};

const updateDecision = async (studentTestId, decision, notes) => {
  try {
    const response = await axios.patch(`/api/student-tests/${studentTestId}/admission-decision`, {
      admission_decision: decision,
      notes: notes
    });
    
    if (response.data.success) {
      // Update local data
      const index = decisions.value.findIndex(d => d.id === studentTestId);
      if (index !== -1) {
        decisions.value[index].admission_decision = decision;
      }
      
      loadStats();
      return true;
    }
  } catch (error) {
    console.error('Error updating decision:', error);
    alert('Erreur lors de la mise à jour de la décision');
    return false;
  }
};

const bulkUpdateDecisions = async (decision, notes) => {
  loading.value = true;
  try {
    let successCount = 0;
    
    for (const studentTestId of selectedDecisions.value) {
      const success = await updateDecision(studentTestId, decision, notes);
      if (success) successCount++;
    }
    
    alert(`${successCount} décision(s) mise(s) à jour avec succès!`);
    selectedDecisions.value = [];
    loadDecisions();
  } catch (error) {
    console.error('Error in bulk update:', error);
  } finally {
    loading.value = false;
  }
};

const viewDetails = (decision) => {
  router.push({ 
    name: 'admissions.test-results.edit', 
    params: { id: decision.id } 
  });
};

const exportDecisions = () => {
  alert('Fonctionnalité d\'export à implémenter');
  // TODO: Implement CSV/PDF export
};

const generateAdmissionLetters = () => {
  alert('Génération de lettres d\'admission à implémenter');
  // TODO: Implement PDF letter generation
};

// Utility functions
const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR');
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

.form-check-input {
  cursor: pointer;
}

.alert {
  border-radius: 0.375rem;
}
</style>