<template>
  <VerticalLayout>
    <PageTitle title="Assignation des Classes" subtitle="Gestion des assignations élèves-classes" />
    
    <!-- Statistics Cards -->
    <BRow class="mb-4">
      <BCol xl="3" lg="6">
        <BCard no-body class="bg-primary bg-gradient">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <BCardTitle class="mb-2 text-white">Élèves Assignés</BCardTitle>
                <p class="text-white fw-medium fs-24 mb-0">{{ stats.assigned_students }}</p>
                <p class="text-white-50 mb-0">{{ stats.assignment_percentage }}% du total</p>
              </div>
              <div>
                <div class="avatar-md bg-light rounded">
                  <div class="avatar-title">
                    <i class="ri-user-check-line fs-32 text-primary"></i>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      
      <BCol xl="3" lg="6">
        <BCard no-body class="bg-warning bg-gradient">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <BCardTitle class="mb-2 text-white">Non Assignés</BCardTitle>
                <p class="text-white fw-medium fs-24 mb-0">{{ stats.unassigned_students }}</p>
                <p class="text-white-50 mb-0">{{ stats.total_students - stats.assigned_students }} restants</p>
              </div>
              <div>
                <div class="avatar-md bg-light rounded">
                  <div class="avatar-title">
                    <i class="ri-user-unfollow-line fs-32 text-warning"></i>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      
      <BCol xl="3" lg="6">
        <BCard no-body class="bg-success bg-gradient">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <BCardTitle class="mb-2 text-white">Classes Utilisées</BCardTitle>
                <p class="text-white fw-medium fs-24 mb-0">{{ stats.occupied_classes }}</p>
                <p class="text-white-50 mb-0">{{ stats.total_classes }} disponibles</p>
              </div>
              <div>
                <div class="avatar-md bg-light rounded">
                  <div class="avatar-title">
                    <i class="ri-school-line fs-32 text-success"></i>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      
      <BCol xl="3" lg="6">
        <BCard no-body class="bg-info bg-gradient">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <BCardTitle class="mb-2 text-white">Taux d'Occupation</BCardTitle>
                <p class="text-white fw-medium fs-24 mb-0">{{ stats.capacity_utilization }}%</p>
                <p class="text-white-50 mb-0">{{ stats.total_capacity }} places totales</p>
              </div>
              <div>
                <div class="avatar-md bg-light rounded">
                  <div class="avatar-title">
                    <i class="ri-pie-chart-line fs-32 text-info"></i>
                  </div>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Loading State -->
    <div v-if="loading" class="text-center my-5">
      <BSpinner class="me-2"></BSpinner>
      <span>Chargement des données...</span>
    </div>

    <!-- Levels and Classes -->
    <div v-else>
      <BRow v-for="level in levels" :key="level.id" class="mb-4">
        <BCol cols="12">
          <BCard>
            <BCardHeader class="bg-light">
              <BRow class="align-items-center">
                <BCol>
                  <h5 class="mb-0">
                    <i class="ri-graduation-cap-line me-2"></i>
                    {{ level.school.name }} - {{ level.name }}
                  </h5>
                  <p class="text-muted mb-0">
                    {{ getTotalStudentsInLevel(level) }} élèves • 
                    {{ level.classes.length }} classe(s) • 
                    {{ getUnassignedCount(level) }} non assigné(s)
                  </p>
                </BCol>
                <BCol cols="auto">
                  <BBadge 
                    :variant="getUnassignedCount(level) === 0 ? 'success' : 'warning'" 
                    class="fs-6"
                  >
                    {{ getUnassignedCount(level) === 0 ? 'Complet' : getUnassignedCount(level) + ' à assigner' }}
                  </BBadge>
                </BCol>
              </BRow>
            </BCardHeader>
            
            <BCardBody>
              <BRow>
                <!-- Classes -->
                <BCol lg="8">
                  <h6 class="text-primary mb-3">Classes Disponibles</h6>
                  <BRow>
                    <BCol 
                      v-for="classRoom in level.classes" 
                      :key="classRoom.id" 
                      md="6" 
                      class="mb-3"
                    >
                      <BCard no-body>
                        <BCardBody class="p-3">
                          <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">{{ classRoom.full_name }}</h6>
                            <BBadge 
                              :variant="getClassStatusColor(classRoom)" 
                              class="text-uppercase"
                            >
                              {{ classRoom.students_count }}/{{ classRoom.capacity }}
                            </BBadge>
                          </div>
                          
                          <div class="mb-2">
                            <BProgress 
                              :value="getClassOccupancyPercentage(classRoom)" 
                              :variant="getClassProgressColor(classRoom)"
                              style="height: 6px"
                            />
                          </div>
                          
                          <div class="d-flex justify-content-between text-muted small">
                            <span>{{ classRoom.students_count }} élèves</span>
                            <span>{{ classRoom.capacity - classRoom.students_count }} places libres</span>
                          </div>
                          
                          <!-- Students in this class -->
                          <div v-if="classRoom.students && classRoom.students.length > 0" class="mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                              <small class="text-muted">Élèves:</small>
                              <BButton 
                                variant="outline-secondary" 
                                size="sm"
                                @click.stop="toggleStudentsList(classRoom.id)"
                              >
                                <i :class="expandedClasses.includes(classRoom.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                              </BButton>
                            </div>
                            
                            <!-- Class students search -->
                            <div v-if="expandedClasses.includes(classRoom.id)" class="mb-2">
                              <BInputGroup size="sm">
                                <BFormInput
                                  v-model="classSearchFilters[classRoom.id]"
                                  placeholder="Rechercher dans la classe..."
                                  class="form-control-sm"
                                />
                                <BInputGroupText>
                                  <i class="ri-search-line"></i>
                                </BInputGroupText>
                              </BInputGroup>
                            </div>
                            
                            <!-- Collapsed view -->
                            <div v-if="!expandedClasses.includes(classRoom.id)" class="d-flex flex-wrap gap-1">
                              <BBadge 
                                v-for="student in classRoom.students.slice(0, 3)" 
                                :key="student.id"
                                variant="light"
                                class="text-dark"
                              >
                                {{ student.first_name }} {{ student.last_name.charAt(0) }}.
                              </BBadge>
                              <BBadge 
                                v-if="classRoom.students.length > 3"
                                variant="outline-secondary"
                              >
                                +{{ classRoom.students.length - 3 }}
                              </BBadge>
                            </div>
                            
                            <!-- Expanded view -->
                            <div v-else class="mt-1" style="max-height: 200px; overflow-y: auto;">
                              <div 
                                v-for="student in getFilteredClassStudents(classRoom)" 
                                :key="student.id"
                                class="d-flex justify-content-between align-items-center p-1 border-bottom"
                              >
                                <small class="text-dark">
                                  <strong>{{ student.first_name }} {{ student.last_name }}</strong>
                                  <br>
                                  <span class="text-muted"># {{ student.student_code }}</span>
                                </small>
                                <BButton 
                                  variant="outline-danger" 
                                  size="sm"
                                  title="Retirer de la classe"
                                  @click.stop="removeStudentFromClass(student.id)"
                                >
                                  <i class="ri-close-line"></i>
                                </BButton>
                              </div>
                              
                              <!-- No search results message -->
                              <div v-if="getFilteredClassStudents(classRoom).length === 0 && classSearchFilters[classRoom.id]" 
                                   class="text-center text-muted py-2">
                                <i class="ri-search-line"></i>
                                <small>Aucun élève trouvé pour "{{ classSearchFilters[classRoom.id] }}"</small>
                              </div>
                            </div>
                          </div>
                        </BCardBody>
                      </BCard>
                    </BCol>
                  </BRow>
                </BCol>
                
                <!-- Unassigned Students -->
                <BCol lg="4">
                  <h6 class="text-warning mb-3">Élèves Non Assignés ({{ getFilteredUnassignedStudents(level).length }})</h6>
                  
                  <!-- Search Filter -->
                  <div v-if="level.unassigned_students && level.unassigned_students.length > 0" class="mb-3">
                    <BInputGroup size="sm">
                      <BFormInput
                        v-model="searchFilters[level.id]"
                        placeholder="Rechercher un élève..."
                        class="form-control-sm"
                      />
                      <BInputGroupText>
                        <i class="ri-search-line"></i>
                      </BInputGroupText>
                    </BInputGroup>
                  </div>
                  
                  <div v-if="getFilteredUnassignedStudents(level).length > 0">
                    
                    <!-- Select All / Deselect All -->
                    <div class="mb-2">
                      <BFormCheckbox 
                        :checked="areAllFilteredStudentsSelected(level)"
                        :indeterminate="areSomeFilteredStudentsSelected(level) && !areAllFilteredStudentsSelected(level)"
                        @change="toggleAllFilteredStudents(level)"
                      >
                        <small class="text-muted">
                          {{ getSelectedStudentsInLevel(level).length > 0 ? `${getSelectedStudentsInLevel(level).length} sélectionné(s)` : 'Tout sélectionner' }}
                        </small>
                      </BFormCheckbox>
                    </div>
                    
                    <!-- Compact Student List -->
                    <div class="student-list-compact mb-3" style="max-height: 300px; overflow-y: auto;">
                      <div class="row g-1">
                        <div 
                          v-for="student in getFilteredUnassignedStudents(level)" 
                          :key="student.id"
                          class="col-12"
                        >
                          <div 
                            :class="['student-item-compact p-2 border rounded', selectedStudents.includes(student.id) ? 'border-primary bg-primary bg-opacity-10' : 'border-light']"
                            style="cursor: pointer"
                            @click="toggleStudentSelection(student.id)"
                          >
                            <div class="d-flex align-items-center">
                              <BFormCheckbox 
                                :checked="selectedStudents.includes(student.id)"
                                @click.stop
                                @change="toggleStudentSelection(student.id)"
                                class="me-2"
                                size="sm"
                              />
                              <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                  <div>
                                    <strong class="fs-6">{{ student.first_name }} {{ student.last_name }}</strong>
                                  </div>
                                  <small class="text-muted"># {{ student.student_code }}</small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Assignment Actions -->
                    <div v-if="selectedStudents.length > 0" class="mt-3">
                      <BFormGroup label="Assigner à la classe:" label-for="class-select" class="mb-2">
                        <BFormSelect
                          id="class-select"
                          v-model="selectedClassForAssignment"
                          :options="getClassOptionsForLevel(level)"
                          size="sm"
                        />
                      </BFormGroup>
                      
                      <BAlert v-if="selectedClassForAssignment" variant="info" show class="p-2 mb-2">
                        <small>
                          <strong>{{ selectedStudents.length }}</strong> élève(s) sélectionné(s) pour 
                          <strong>{{ getClassById(selectedClassForAssignment)?.full_name }}</strong>
                        </small>
                      </BAlert>
                      
                      <BButton 
                        variant="primary" 
                        size="sm" 
                        class="w-100"
                        @click="assignSelectedStudents"
                        :disabled="assigning || !selectedClassForAssignment"
                      >
                        <BSpinner v-if="assigning" small class="me-1" />
                        <i v-else class="ri-user-add-line me-1"></i>
                        Assigner à la Classe
                      </BButton>
                    </div>
                  </div>
                  <div v-else-if="level.unassigned_students && level.unassigned_students.length > 0" class="text-center text-muted py-4">
                    <i class="ri-search-line fs-2"></i>
                    <p class="mt-2">Aucun élève trouvé pour "{{ searchFilters[level.id] }}"</p>
                  </div>
                  <div v-else class="text-center text-muted py-4">
                    <i class="ri-user-check-line fs-2"></i>
                    <p class="mt-2">Tous les élèves sont assignés</p>
                  </div>
                </BCol>
              </BRow>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && levels.length === 0" class="text-center py-5">
      <div class="avatar-md mx-auto mb-4">
        <div class="avatar-title bg-light text-primary rounded-circle fs-2">
          <i class="ri-school-line"></i>
        </div>
      </div>
      <h5 class="mb-1">Aucun niveau trouvé</h5>
      <p class="text-muted mb-3">Aucun niveau n'est disponible pour cette année académique.</p>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue'
import { useAcademicYearStore } from '@/stores/academicYear'
import { showToast } from '@/helpers/toast'

const academicYearStore = useAcademicYearStore()

// Reactive data
const levels = ref([])
const stats = ref({
  total_students: 0,
  assigned_students: 0,
  unassigned_students: 0,
  assignment_percentage: 0,
  total_classes: 0,
  occupied_classes: 0,
  total_capacity: 0,
  capacity_utilization: 0
})
const loading = ref(false)
const assigning = ref(false)
const selectedClass = ref(null)
const selectedStudents = ref([])
const selectedClassForAssignment = ref('')
const expandedClasses = ref([])
const searchFilters = ref({})
const classSearchFilters = ref({})

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const [levelsResponse, statsResponse] = await Promise.all([
      axios.get('/api/class-assignments/levels', {
        params: { academic_year_id: academicYearStore.currentAcademicYear?.id }
      }),
      axios.get('/api/class-assignments/stats', {
        params: { academic_year_id: academicYearStore.currentAcademicYear?.id }
      })
    ])

    levels.value = levelsResponse.data.data || []
    stats.value = statsResponse.data.data || stats.value

  } catch (error) {
    console.error('Error loading data:', error)
    showToast('Erreur lors du chargement des données', 'error')
  } finally {
    loading.value = false
  }
}


const toggleStudentSelection = (studentId) => {
  const index = selectedStudents.value.indexOf(studentId)
  if (index > -1) {
    selectedStudents.value.splice(index, 1)
  } else {
    selectedStudents.value.push(studentId)
  }
}

const assignSelectedStudents = async () => {
  if (!selectedClassForAssignment.value || selectedStudents.value.length === 0) {
    showToast('Veuillez sélectionner une classe et des élèves', 'warning')
    return
  }

  assigning.value = true
  try {
    const response = await axios.post('/api/class-assignments/assign', {
      class_id: selectedClassForAssignment.value,
      student_ids: selectedStudents.value
    })

    if (response.data.success) {
      showToast(response.data.message, 'success')
      selectedStudents.value = []
      selectedClassForAssignment.value = ''
      await loadData() // Reload data to reflect changes
    }

  } catch (error) {
    console.error('Error assigning students:', error)
    const message = error.response?.data?.message || 'Erreur lors de l\'assignation'
    showToast(message, 'error')
  } finally {
    assigning.value = false
  }
}

const toggleAllStudents = (level) => {
  const allStudentIds = level.unassigned_students.map(s => s.id)
  const allSelected = areAllStudentsSelected(level)
  
  if (allSelected) {
    // Deselect all students from this level
    selectedStudents.value = selectedStudents.value.filter(id => !allStudentIds.includes(id))
  } else {
    // Select all students from this level
    allStudentIds.forEach(id => {
      if (!selectedStudents.value.includes(id)) {
        selectedStudents.value.push(id)
      }
    })
  }
}

// Search and filtering methods
const getFilteredUnassignedStudents = (level) => {
  if (!level.unassigned_students || level.unassigned_students.length === 0) return []
  
  const searchTerm = searchFilters.value[level.id]?.toLowerCase().trim()
  if (!searchTerm) return level.unassigned_students
  
  return level.unassigned_students.filter(student => {
    return student.first_name.toLowerCase().includes(searchTerm) ||
           student.last_name.toLowerCase().includes(searchTerm) ||
           student.student_code.toLowerCase().includes(searchTerm)
  })
}

const getSelectedStudentsInLevel = (level) => {
  const filteredStudents = getFilteredUnassignedStudents(level)
  return filteredStudents.filter(student => selectedStudents.value.includes(student.id))
}

// Updated selection methods for filtered students
const areAllFilteredStudentsSelected = (level) => {
  const filteredStudents = getFilteredUnassignedStudents(level)
  if (filteredStudents.length === 0) return false
  return filteredStudents.every(student => selectedStudents.value.includes(student.id))
}

const areSomeFilteredStudentsSelected = (level) => {
  const filteredStudents = getFilteredUnassignedStudents(level)
  if (filteredStudents.length === 0) return false
  return filteredStudents.some(student => selectedStudents.value.includes(student.id))
}

const toggleAllFilteredStudents = (level) => {
  const filteredStudents = getFilteredUnassignedStudents(level)
  const filteredStudentIds = filteredStudents.map(s => s.id)
  const allSelected = areAllFilteredStudentsSelected(level)
  
  if (allSelected) {
    // Deselect all filtered students
    selectedStudents.value = selectedStudents.value.filter(id => !filteredStudentIds.includes(id))
  } else {
    // Select all filtered students
    filteredStudentIds.forEach(id => {
      if (!selectedStudents.value.includes(id)) {
        selectedStudents.value.push(id)
      }
    })
  }
}

// Class students filtering method
const getFilteredClassStudents = (classRoom) => {
  if (!classRoom.students || classRoom.students.length === 0) return []
  
  const searchTerm = classSearchFilters.value[classRoom.id]?.toLowerCase().trim()
  if (!searchTerm) return classRoom.students
  
  return classRoom.students.filter(student => {
    return student.first_name.toLowerCase().includes(searchTerm) ||
           student.last_name.toLowerCase().includes(searchTerm) ||
           student.student_code.toLowerCase().includes(searchTerm)
  })
}

// Legacy methods for backward compatibility
const areAllStudentsSelected = (level) => {
  return areAllFilteredStudentsSelected(level)
}

const areSomeStudentsSelected = (level) => {
  return areSomeFilteredStudentsSelected(level)
}

const getClassOptionsForLevel = (level) => {
  const options = [{ value: '', text: 'Choisir une classe...' }]
  level.classes.forEach(cls => {
    if (cls.capacity > cls.students_count) { // Only show classes with available capacity
      options.push({
        value: cls.id,
        text: `${cls.full_name} (${cls.capacity - cls.students_count} places libres)`
      })
    }
  })
  return options
}

const getClassById = (classId) => {
  for (const level of levels.value) {
    const foundClass = level.classes.find(cls => cls.id == classId)
    if (foundClass) return foundClass
  }
  return null
}

const toggleStudentsList = (classId) => {
  const index = expandedClasses.value.indexOf(classId)
  if (index > -1) {
    expandedClasses.value.splice(index, 1)
  } else {
    expandedClasses.value.push(classId)
  }
}

const removeStudentFromClass = async (studentId) => {
  if (!confirm('Êtes-vous sûr de vouloir retirer cet élève de sa classe ?')) {
    return
  }

  try {
    const response = await axios.delete(`/api/class-assignments/student/${studentId}`)

    if (response.data.success) {
      showToast(response.data.message, 'success')
      await loadData() // Reload data to reflect changes
    }

  } catch (error) {
    console.error('Error removing student:', error)
    const message = error.response?.data?.message || 'Erreur lors du retrait de l\'élève'
    showToast(message, 'error')
  }
}

// Helper methods
const getTotalStudentsInLevel = (level) => {
  const assignedCount = level.classes.reduce((total, cls) => total + (cls.students_count || 0), 0)
  const unassignedCount = level.unassigned_students?.length || 0
  return assignedCount + unassignedCount
}

const getUnassignedCount = (level) => {
  return getFilteredUnassignedStudents(level).length
}

const getClassOccupancyPercentage = (classRoom) => {
  return classRoom.capacity > 0 ? (classRoom.students_count / classRoom.capacity) * 100 : 0
}

const getClassStatusColor = (classRoom) => {
  const percentage = getClassOccupancyPercentage(classRoom)
  if (percentage >= 100) return 'danger'
  if (percentage >= 80) return 'warning'
  if (percentage >= 50) return 'info'
  return 'success'
}

const getClassProgressColor = (classRoom) => {
  const percentage = getClassOccupancyPercentage(classRoom)
  if (percentage >= 100) return 'danger'
  if (percentage >= 80) return 'warning'
  return 'success'
}

// Lifecycle
onMounted(async () => {
  if (!academicYearStore.currentAcademicYear) {
    await academicYearStore.init()
  }
  await loadData()
})
</script>

<style scoped>
.list-group-item.active {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.card:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease-in-out;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>