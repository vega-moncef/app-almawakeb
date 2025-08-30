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
            icon: 'solar:clipboard-text-broken'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Oraux',
            statistic: stats.oral_tests || 0,
            prefix: '',
            icon: 'solar:microphone-3-broken'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Écrits',
            statistic: stats.written_tests || 0,
            prefix: '',
            icon: 'solar:pen-new-square-broken'
          }"
        />
      </div>
      <div class="col-xl-3 col-md-6">
        <StatisticsCard
          :item="{
            title: 'Tests Actifs',
            statistic: stats.active_tests || 0,
            prefix: '',
            icon: 'solar:check-circle-broken'
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
              <div>
                <h4 class="card-title mb-0">Liste des Tests</h4>
                <small class="text-muted">{{ pagination.total }} test(s) au total • {{ pagination.per_page }} par page</small>
              </div>
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-primary"
                  @click="createTest"
                >
                  <Icon icon="solar:add-circle-broken" class="me-2" />Nouveau Test
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
                  @change="onFilterChange"
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
                  @change="onFilterChange"
                >
                  <option value="">Tous les niveaux</option>
                  <option 
                    v-for="level in sortedLevels" 
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
                  @change="onFilterChange"
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
                    <th>Durée</th>
                    <th>Matières</th>
                    <th>Notes</th>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="8" class="text-center py-4">
                      <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Chargement...</span>
                      </div>
                      Chargement des tests...
                    </td>
                  </tr>
                  <tr v-else-if="tests.length === 0">
                    <td colspan="8" class="text-center py-4 text-muted">
                      <Icon icon="solar:clipboard-text-broken" class="fs-48 mb-2 d-block" />
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
                first-text="Premier"
                last-text="Dernier"
                class="mb-0"
                limit="5"
                @update:model-value="onPageChange"
              />
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
const pagination = reactive({
  current_page: 1,
  per_page: 10,
  total: 0
});

const filters = reactive({
  search: '',
  type: '',
  level_id: '',
  status: '',
  page: 1,
  per_page: 10
});

// Computed properties
const sortedLevels = computed(() => {
  // The API already filters by current academic year and sorts properly
  return levels.value;
});

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.current_page = 1;
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
      page: pagination.current_page,
      per_page: pagination.per_page
    });

    // Add status filter logic
    if (filters.status === 'active') {
      params.is_active = true;
    } else if (filters.status === 'inactive') {
      params.is_active = false;
    }

    console.log('Loading tests with params:', params); // Debug log

    const response = await axios.get('/api/tests', { params });
    
    console.log('API Response:', response.data); // Debug log
    
    if (response.data.success) {
      tests.value = response.data.data.data || [];
      pagination.total = response.data.data.total;
      // Don't overwrite current_page if we just set it
      // pagination.current_page = response.data.data.current_page;
      
      console.log('Updated tests:', tests.value.length, 'Current page:', pagination.current_page); // Debug log
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

// Page change handler
const onPageChange = (page) => {
  console.log('Page change requested:', page); // Debug log
  pagination.current_page = page;
  loadTests();
};

// Filter change handler - resets to first page when filter changes
const onFilterChange = () => {
  pagination.current_page = 1;
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