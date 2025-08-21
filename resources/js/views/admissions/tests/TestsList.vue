<template>
<VerticalLayout>
  <div class="container-fluid">
    <!-- Page Title -->
    <PageTitle :title="'Gestion des Tests'" :sub-title="'Liste des tests d\'admission'" />
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Total Tests',
            statistic: stats.total_tests || 0,
            prefix: '',
            icon: 'fas fa-clipboard-list'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Oraux',
            statistic: stats.oral_tests || 0,
            prefix: '',
            icon: 'fas fa-microphone'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Écrits',
            statistic: stats.written_tests || 0,
            prefix: '',
            icon: 'fas fa-pen'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Actifs',
            statistic: stats.active_tests || 0,
            prefix: '',
            icon: 'fas fa-check-circle'
          }"
        />
      </div>
    </div>

    <!-- Filters & Actions -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h4 class="card-title">Liste des Tests</h4>
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-primary"
                  @click="createTest"
                >
                  <i class="fas fa-plus"></i> Nouveau Test
                </button>
              </div>
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
                  placeholder="Titre du test..."
                  @input="debouncedSearch"
                >
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Type</label>
                <select 
                  class="form-select" 
                  v-model="filters.type"
                  @change="loadTests"
                >
                  <option value="">Tous les types</option>
                  <option value="ORAL">Oral</option>
                  <option value="ECRIT">Écrit</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Niveau</label>
                <select 
                  class="form-select" 
                  v-model="filters.level_id"
                  @change="loadTests"
                >
                  <option value="">Tous les niveaux</option>
                  <option 
                    v-for="level in levels" 
                    :key="level.id" 
                    :value="level.id"
                  >
                    {{ level.school ? level.school.name + ' - ' : '' }}{{ level.name }}
                  </option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select 
                  class="form-select" 
                  v-model="filters.status"
                  @change="loadTests"
                >
                  <option value="">Tous les statuts</option>
                  <option value="active">Actif</option>
                  <option value="inactive">Inactif</option>
                </select>
              </div>
            </div>

            <!-- Tests Table -->
            <div class="table-responsive">
              <table class="table table-hover table-nowrap">
                <thead class="table-light">
                  <tr>
                    <th>Titre</th>
                    <th>Niveau</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Durée</th>
                    <th>Matières</th>
                    <th>Notes</th>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="9" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Chargement...</span>
                      </div>
                      Chargement des tests...
                    </td>
                  </tr>
                  <tr v-else-if="tests.length === 0">
                    <td colspan="9" class="text-center py-4 text-muted">
                      <i class="fas fa-clipboard-list fa-2x mb-3 d-block"></i>
                      Aucun test trouvé
                    </td>
                  </tr>
                  <tr v-else v-for="test in tests" :key="test.id">
                    <td>
                      <div>
                        <h6 class="mb-1">{{ test.title }}</h6>
                        <small class="text-muted" v-if="test.description">
                          {{ test.description }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">
                        {{ test.level?.school?.name }} - {{ test.level?.name }}
                      </span>
                    </td>
                    <td>
                      <span 
                        class="badge"
                        :class="`bg-${test.type === 'ORAL' ? 'primary' : 'success'}`"
                      >
                        {{ test.type }}
                      </span>
                    </td>
                    <td>
                      <div>
                        <div>{{ formatDate(test.test_date) }}</div>
                        <small class="text-muted">
                          {{ formatTime(test.start_time) }} - {{ formatTime(test.end_time) }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-info">
                        {{ test.duration_minutes }} min
                      </span>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        <span 
                          v-for="subject in test.subjects?.slice(0, 3)" 
                          :key="subject.id"
                          class="badge bg-secondary"
                          style="font-size: 0.7em;"
                        >
                          {{ subject.name }}
                        </span>
                        <span 
                          v-if="test.subjects?.length > 3"
                          class="badge bg-secondary"
                          style="font-size: 0.7em;"
                        >
                          +{{ test.subjects.length - 3 }}
                        </span>
                      </div>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ test.total_marks }} pts<br>
                        Seuil: {{ test.passing_marks }}
                      </small>
                    </td>
                    <td>
                      <span 
                        class="badge"
                        :class="`bg-${test.is_active ? 'success' : 'secondary'}`"
                      >
                        {{ test.is_active ? 'Actif' : 'Inactif' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button 
                          type="button" 
                          class="btn btn-outline-primary"
                          @click="viewTest(test)"
                          title="Voir"
                        >
                          <Icon icon="solar:eye-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-warning"
                          @click="editTest(test)"
                          title="Modifier"
                        >
                          <Icon icon="solar:pen-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-info"
                          @click="assignStudents(test)"
                          title="Assigner Étudiants"
                        >
                          <Icon icon="solar:user-plus-broken" class="me-2" />
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-danger"
                          @click="deleteTest(test)"
                          title="Supprimer"
                        >
                          <Icon icon="solar:trash-bin-trash-broken" class="me-2" />
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
                sur {{ pagination.total }} tests
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
  loadTests();
  loadLevels();
});

// Reactive data
const loading = ref(false);
const tests = ref([]);
const levels = ref([]);
const stats = ref({});
const pagination = ref({});

const filters = reactive({
  search: '',
  type: '',
  level_id: '',
  status: '',
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
    loadTests();
  }, 500);
};

// Lifecycle
onMounted(() => {
  loadTests();
  loadLevels();
  loadStats();
  
  // Listen for academic year changes
  window.addEventListener('academic-year-changed', () => {
    console.log('Academic year changed, reloading tests...');
    loadTests();
    loadLevels();
    loadStats();
  });
});

// Methods
const loadTests = async () => {
  loading.value = true;
  try {
    const params = addAcademicYearFilter({
      search: filters.search,
      type: filters.type,
      level_id: filters.level_id,
      page: filters.page,
      per_page: filters.per_page
    });

    // Add status filter logic
    if (filters.status === 'active') {
      params.is_active = true;
    } else if (filters.status === 'inactive') {
      params.is_active = false;
    }

    const response = await axios.get('/api/tests', { params });
    
    if (response.data.success) {
      tests.value = response.data.data.data || [];
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
    console.error('Error loading tests:', error);
    alert('Erreur lors du chargement des tests');
  } finally {
    loading.value = false;
  }
};

const loadLevels = async () => {
  try {
    const params = addAcademicYearFilter({});
    const response = await axios.get('/api/levels', { params });
    
    if (response.data.success) {
      levels.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Error loading levels:', error);
  }
};

const loadStats = async () => {
  try {
    const params = addAcademicYearFilter({});
    const response = await axios.get('/api/tests/stats', { params });
    
    if (response.data.success) {
      stats.value = response.data.data || {};
    }
  } catch (error) {
    console.error('Error loading stats:', error);
  }
};

const loadPage = (page) => {
  filters.page = page;
  loadTests();
};

const createTest = () => {
  router.push({ name: 'admissions.tests.create' });
};

const viewTest = (test) => {
  router.push({ 
    name: 'admissions.tests.view', 
    params: { id: test.id } 
  });
};

const editTest = (test) => {
  router.push({ 
    name: 'admissions.tests.edit', 
    params: { id: test.id } 
  });
};

const assignStudents = (test) => {
  router.push({ 
    name: 'admissions.tests.assign', 
    params: { id: test.id } 
  });
};

const deleteTest = async (test) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le test "${test.title}" ?`)) {
    return;
  }

  try {
    const response = await axios.delete(`/api/tests/${test.id}`);
    
    if (response.data.success) {
      alert('Test supprimé avec succès');
      loadTests();
      loadStats();
    }
  } catch (error) {
    console.error('Error deleting test:', error);
    alert('Erreur lors de la suppression du test');
  }
};

// Utility functions
const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR');
};

const formatTime = (time) => {
  if (!time) return '';
  return new Date(`2000-01-01 ${time}`).toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
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

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>