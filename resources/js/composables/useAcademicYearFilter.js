import { useAcademicYearStore } from '@/stores/academicYear'
import { onMounted, onUnmounted } from 'vue'

/**
 * Composable for easy academic year filtering in components
 * Provides reactive academic year state and automatic data reloading on year changes
 */
export function useAcademicYearFilter(reloadDataCallback) {
  const academicYearStore = useAcademicYearStore()

  // Academic year change handler
  const handleAcademicYearChange = (event) => {
    console.log('🔄 Academic year changed, reloading data...', event.detail)
    if (reloadDataCallback && typeof reloadDataCallback === 'function') {
      reloadDataCallback()
    }
  }

  // Setup and cleanup event listeners
  onMounted(() => {
    window.addEventListener('academic-year-changed', handleAcademicYearChange)
  })

  onUnmounted(() => {
    window.removeEventListener('academic-year-changed', handleAcademicYearChange)
  })

  /**
   * Add academic year filter to API parameters
   * @param {Object} params - Existing API parameters
   * @returns {Object} Parameters with academic year filter added
   */
  const addAcademicYearFilter = (params = {}) => {
    return academicYearStore.addYearFilter(params)
  }

  /**
   * Get current academic year ID for API calls
   * @returns {number|null} Current academic year ID
   */
  const getCurrentAcademicYearId = () => {
    return academicYearStore.getCurrentYearIdForApi()
  }

  return {
    // Store reference
    academicYearStore,
    
    // Helper methods
    addAcademicYearFilter,
    getCurrentAcademicYearId,
    
    // Reactive state
    currentAcademicYear: academicYearStore.currentAcademicYear,
    currentYearId: academicYearStore.currentYearId,
    currentYearName: academicYearStore.currentYearName,
    availableYears: academicYearStore.availableYears,
    loading: academicYearStore.loading
  }
}