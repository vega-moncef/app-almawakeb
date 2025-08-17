<template>
  <AuthLayout>
    <b-col xl="5">
      <b-card no-body class="auth-card">
        <b-card-body class="px-3 py-5">
          <div class="mx-auto text-center">
            <LogoBox custom-class="auth-logo mb-4" :logo-height="30"/>
          </div>

          <h2 class="fw-bold text-center fs-18">Sign In</h2>
          <p class="text-muted text-center mt-1 mb-4">Enter your email address and password to access admin panel.</p>

          <div class="px-4">
            <b-form @submit.prevent="handleSignIn" class="authentication-form">
              <div v-if="error.length > 0" class="mb-2 text-danger">{{ error }}</div>

              <b-form-group label="Email" class="mb-3">
                <b-form-input type="email" id="example-email" name="email"
                              class="bg-light bg-opacity-50 border-light py-2" v-model="v.email.$model"
                              placeholder="Enter your email"/>
                <div v-if="v.email.$error" class="text-danger">
                <span v-for="(err, idx) in v.email.$errors" :key="idx">
                  {{ err.$message }}
                </span>
                </div>
              </b-form-group>

              <b-form-group label="Password" class="mb-3">
                <b-form-input type="password" id="example-password" name="password"
                              class="bg-light bg-opacity-50 border-light py-2" v-model="v.password.$model"
                              placeholder="Enter your password"/>
                <div v-if="v.password.$errors" class="text-danger">
                <span v-for="(err, idx) in v.password.$errors" :key="idx">
                  {{ err.$message }}
                </span>
                </div>
              </b-form-group>

              <div class="mb-3">
                <b-form-checkbox>Remember me</b-form-checkbox>
              </div>

              <div class="mb-3 d-flex justify-content-center">
                <router-link :to="{ name: 'auth.reset-password' }" class="text-muted text-unline-dashed"> Reset
                  password
                </router-link>
              </div>

              <div class="mb-1 text-center d-grid">
                <b-button variant="danger" type="submit"> Sign In</b-button>
              </div>
            </b-form>

            <p class="mt-3 fw-semibold no-span">OR sign with</p>

            <div class="text-center">
              <a href="javascript:void(0);" class="btn btn-outline-light shadow-none"><i
                  class='bx bxl-google fs-20'></i></a>
              <a href="javascript:void(0);" class="btn btn-outline-light shadow-none"><i
                  class='ri-facebook-fill fs-20'></i></a>
              <a href="javascript:void(0);" class="btn btn-outline-light shadow-none"><i
                  class='bx bxl-github fs-20'></i></a>
            </div>
          </div>
        </b-card-body>
      </b-card>
      <p class="mb-0 text-center text-white">New here?
        <router-link :to="{ name: 'auth.sign-up' }"
                     class="text-reset text-unline-dashed fw-bold ms-1">Sign Up
        </router-link>
      </p>
    </b-col>
  </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';

import {required, email} from '@vuelidate/validators';
import {useVuelidate} from '@vuelidate/core';

import {ref, reactive, computed} from 'vue';
import {useRoute} from 'vue-router';

import HttpClient from '@/helpers/http-client';
import {useAuthStore} from '@/stores/auth';

import type {AxiosResponse} from 'axios';
import type {User} from '@/types/auth';
import router from '@/router';
import LogoBox from "@/components/LogoBox.vue";

const credentials = reactive({
  email: 'user@email.com',
  password: 'password'
});

const vuelidateRules = computed(() => ({
  email: {required, email},
  password: {required}
}));

const v = useVuelidate(vuelidateRules, credentials);

const useAuth = useAuthStore();
const route = useRoute();
const query = route.query;

const error = ref('');
const checked = ref(false);

const handleSignIn = async () => {
  const result = await v.value.$validate();

  if (result) {
    try {
      const res: AxiosResponse<User> = await HttpClient.post('/sign-in', credentials);
      console.log("res", res);

      if (res.data.token) {
        useAuth.saveSession({
          ...res.data,
          token: res.data.token
        });
        redirectUser();
      }
    } catch (e: any) {
      if (e.response?.data?.error) {
        if (error.value.length == 0) error.value = e.response?.data?.error;
      }
    }
  }
};

const redirectUser = () => {
  if (query.redirectedFrom) {
    return router.push(`${query.redirectedFrom}`);
  }
  return router.push('/');
};
</script>
