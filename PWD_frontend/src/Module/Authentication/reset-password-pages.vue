<template>
  <div>
    <div class="container">
      <!-- LEFT IMAGE -->
      <div class="left">
        <img src="@/assets/PWD_forgot.png" alt="Worker" class="worker" />
      </div>

      <!-- RIGHT FORM -->
      <div class="right" :class="{ 'fade-in': isVisible }">
        <!-- PAGE LOADING -->
        <div v-if="pageLoading" class="page-loading">
          <div class="loader"></div>
        </div>

        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" />
        </div>

        <h2 class="form-h2">
          Reset Password
          <p class="form-p">
            Create a new password for <strong>{{ email }}</strong>
          </p>
        </h2>

        <!-- NEW PASSWORD -->
        <div class="form-group password-group">
          <label>New Password</label>
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            placeholder="Enter new password"
          />
        </div>

        <!-- CONFIRM PASSWORD -->
        <div class="form-group password-group">
          <label>Confirm Password</label>
          <input
            :type="showConfirmPassword ? 'text' : 'password'"
            v-model="confirmPassword"
            placeholder="Confirm new password"
          />
        </div>

        <!-- RESET BUTTON -->
        <button class="btn" @click="resetPassword" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Update Password</span>
        </button>

        <!-- BACK TO LOGIN -->
        <p class="auth-link">
          Remember your password?
          <a href="#" @click.prevent="goLogin">Back to login</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { resetPassword as resetPasswordRequest } from "@/services/otp.services"

import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import "@/assets/Styles/styles.css"

const router = useRouter()
const route = useRoute()

const email = ref(route.query.email || "")
const password = ref("")
const confirmPassword = ref("")
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const pageLoading = ref(true)
const isVisible = ref(false)

onMounted(() => {
  setTimeout(() => {
    pageLoading.value = false
    isVisible.value = true
  }, 400)

  if (!email.value) {
    router.replace("/auth/forget-password")
  }
})

const resetPassword = async () => {
  if (!password.value || !confirmPassword.value) {
    Toastify({
      text: "Please fill in all fields",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  if (password.value !== confirmPassword.value) {
    Toastify({
      text: "Passwords do not match",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  loading.value = true

  try {
    await resetPasswordRequest(email.value, password.value)
    Toastify({
      text: "Password updated successfully.",
      backgroundColor: "#2ecc71",
    }).showToast()
    router.push("/login")
  } catch (error) {
    const message =
      error?.response?.data?.message ||
      "Failed to reset password. Verify OTP first."
    Toastify({
      text: message,
      backgroundColor: "#e74c3c",
    }).showToast()
  } finally {
    loading.value = false
  }
}

const goLogin = () => {
  router.push("/login")
}
</script>
