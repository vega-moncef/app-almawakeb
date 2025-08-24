// Simple toast notification helper
export const showToast = (message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') => {
  // For now, use browser alerts - can be enhanced with a proper toast library later
  if (type === 'success') {
    console.log('✅ Success:', message)
  } else if (type === 'error') {
    console.error('❌ Error:', message)
  } else if (type === 'warning') {
    console.warn('⚠️ Warning:', message)
  } else {
    console.info('ℹ️ Info:', message)
  }
  
  // Simple browser notification for now
  alert(`${type.toUpperCase()}: ${message}`)
}

export default showToast