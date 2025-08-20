<template>
  <div>
    <b-offcanvas placement="end" class="border-0" header-class="d-flex align-items-center bg-primary p-3" body-class="p-0" id="theme-settings">
      <template v-slot:header>
        <h5 class="text-white m-0">Theme Settings</h5>
        <button type="button" class="btn-close btn-close-white ms-auto" v-b-toggle="'theme-settings'"></button>
      </template>

      <div class="p-0">
        <simplebar class="h-100">
          <div class="p-3 settings-bar">
            <div>
              <h5 class="mb-3 font-16 fw-semibold">Color Scheme</h5>
              <b-form-radio-group stacked v-model="layout.theme" name="theme" @change="setTheme(layout.theme)">
                <b-form-radio value="light">Light</b-form-radio>
                <b-form-radio value="dark">Dark</b-form-radio>
              </b-form-radio-group>
            </div>

            <div>
              <h5 class="my-3 font-16 fw-semibold">Topbar Color</h5>
              <b-form-radio-group stacked v-model="layout.topBarColor" name="topbar-color" @change="setTopBarColor(layout.topBarColor)">
                <b-form-radio value="light">Light</b-form-radio>
                <b-form-radio value="dark">Dark</b-form-radio>
              </b-form-radio-group>
            </div>


            <div>
              <h5 class="my-3 font-16 fw-semibold">Menu Color</h5>
              <b-form-radio-group stacked v-model="layout.leftSideBarColor" name="menu-color" @change="setLeftSideBarColor(layout.leftSideBarColor)">
                <b-form-radio value="light">Light</b-form-radio>
                <b-form-radio value="dark">Dark</b-form-radio>
              </b-form-radio-group>
            </div>

            <div>
              <h5 class="my-3 font-16 fw-semibold">Sidebar Size</h5>
              <b-form-radio-group stacked v-model="layout.leftSideBarSize" name="menu-size" @change="setLeftSideBarSize(layout.leftSideBarSize)">
                <b-form-radio value="default">Default</b-form-radio>
                <b-form-radio value="condensed">Condensed</b-form-radio>
                <b-form-radio value="hidden">Hidden</b-form-radio>
                <b-form-radio value="sm-hover-active">Small Hover Active</b-form-radio>
                <b-form-radio value="sm-hover">Small Hover</b-form-radio>
              </b-form-radio-group>
            </div>

            <div>
              <h5 class="my-3 font-16 fw-semibold">Academic Year</h5>
              <div v-if="academicYearStore.loading" class="text-center">
                <b-spinner small></b-spinner> Loading...
              </div>
              <b-form-select v-else 
                v-model="selectedYearId" 
                :options="yearOptions">
              </b-form-select>
              <small class="text-muted d-block mt-1">
                Current: {{ academicYearStore.currentYearName }}
              </small>
            </div>
          </div>
        </simplebar>
      </div>
      <template v-slot:footer>
        <div class="border-top p-3 text-center">
          <b-row>
            <b-col>
              <b-button type="button" variant="danger" class="w-100" id="reset-layout" @click="reset">Reset</b-button>
            </b-col>
          </b-row>
        </div>
      </template>
    </b-offcanvas>
  </div>
</template>

<script setup>
import simplebar from 'simplebar-vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useLayoutStore } from '@/stores/layout';
import { useAcademicYearStore } from '@/stores/academicYear';

const useLayout = useLayoutStore();
const academicYearStore = useAcademicYearStore();
const selectedYearId = ref(null);

const { layout, setTheme, setTopBarColor, setLeftSideBarColor, setLeftSideBarSize, reset } = useLayout;

// Compute options for the select
const yearOptions = computed(() => {
  return academicYearStore.availableYears.map(year => ({
    value: year.id,
    text: year.name
  }));
});

const changeAcademicYear = async (yearId) => {
  console.log('🔄 Changing academic year to:', yearId);
  try {
    await academicYearStore.setCurrentAcademicYear(parseInt(yearId));
    console.log('✅ Academic year changed successfully');
  } catch (error) {
    console.error('❌ Failed to change academic year:', error);
    // Reset to previous value if error
    selectedYearId.value = academicYearStore.currentYearId;
  }
};

// Watch for changes in current academic year to sync the select
watch(() => academicYearStore.currentYearId, (newId) => {
  selectedYearId.value = newId;
}, { immediate: true });

// Watch for changes in selected year to trigger API call
watch(selectedYearId, async (newId, oldId) => {
  // Avoid infinite loop and only change if user actually selected something different
  if (newId && newId !== oldId && newId !== academicYearStore.currentYearId) {
    await changeAcademicYear(newId);
  }
});

onMounted(() => {
  academicYearStore.init();
});
</script>

<style scoped>
.form-check {
  margin: 10px 0;
}
</style>