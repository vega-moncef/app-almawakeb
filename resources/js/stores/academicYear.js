import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAcademicYearStore = defineStore('academicYear', () => {
  // State
  const currentAcademicYear = ref(null)
  const academicYears = ref([])
  const loading = ref(false)

  // Getters
  const currentYearId = computed(() => currentAcademicYear.value?.id || null)
  const currentYearName = computed(() => currentAcademicYear.value?.name || '')
  const availableYears = computed(() => academicYears.value.filter(year => year.is_active))

  // Actions
  const fetchAcademicYears = async () => {
    console.log('🔄 Fetching academic years...')
    loading.value = true
    try {
      const response = await axios.get('/api/academic-years')
      console.log('📥 Academic years response:', response.data)
      
      if (response.data.success) {
        academicYears.value = response.data.data
        console.log('📊 Academic years loaded:', academicYears.value.length)
        
        // Set current academic year if not already set
        if (!currentAcademicYear.value) {
          const currentYear = academicYears.value.find(year => year.is_current)
          console.log('📅 Found current year:', currentYear)
          if (currentYear) {
            currentAcademicYear.value = currentYear
            console.log('✅ Current academic year set to:', currentYear.name)
          }
        }
      }
    } catch (error) {
      console.error('❌ Error fetching academic years:', error)
    } finally {
      loading.value = false
    }
  }

  const setCurrentAcademicYear = async (yearId) => {
    console.log('🔄 Setting current academic year to ID:', yearId)
    try {
      // Update backend to set new current year
      const response = await axios.patch(`/api/academic-years/${yearId}/set-current`)
      console.log('📥 Set current year response:', response.data)
      
      if (response.data.success) {
        // Update local state
        academicYears.value.forEach(year => {
          year.is_current = year.id == yearId
        })
        
        const newCurrentYear = academicYears.value.find(year => year.id == yearId)
        console.log('📅 New current year:', newCurrentYear)
        
        if (newCurrentYear) {
          currentAcademicYear.value = newCurrentYear
          console.log('✅ Current academic year updated to:', newCurrentYear.name)
        }
        
        // Emit event to refresh data across the app
        console.log('📢 Emitting academic-year-changed event')
        window.dispatchEvent(new CustomEvent('academic-year-changed', { 
          detail: { yearId, yearName: newCurrentYear?.name } 
        }))
        
        return true
      }
    } catch (error) {
      console.error('❌ Error setting current academic year:', error)
      throw error
    }
  }

  let initPromise = null
  
  const init = async () => {
    // Prevent multiple simultaneous init calls
    if (initPromise) {
      return initPromise
    }
    
    if (academicYears.value.length === 0) {
      initPromise = fetchAcademicYears()
      await initPromise
      initPromise = null
    }
  }

  // Helper method to get current academic year ID for API calls
  const getCurrentYearIdForApi = () => {
    return currentYearId.value
  }

  // Helper method to add academic year filter to API params
  const addYearFilter = (params = {}) => {
    const yearId = getCurrentYearIdForApi()
    if (yearId) {
      params.academic_year_id = yearId
    }
    return params
  }

  return {
    // State
    currentAcademicYear,
    academicYears,
    loading,
    
    // Getters
    currentYearId,
    currentYearName,
    availableYears,
    
    // Actions
    fetchAcademicYears,
    setCurrentAcademicYear,
    init,
    
    // Helpers
    getCurrentYearIdForApi,
    addYearFilter
  }
})