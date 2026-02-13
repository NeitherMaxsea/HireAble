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
          Forgot Password
          <p class="form-p">
            Enter your email and weâ€™ll send you a one-time password (OTP).
          </p>
        </h2>

        <!-- EMAIL -->
        <div class="form-group">
          <label>Email</label>
          <input
            type="email"
            placeholder="Enter your email"
            v-model="email"
          />
        </div>

        <!-- SEND BUTTON -->
        <button class="btn" @click="sendReset" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Send OTP</span>
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
import { useRouter } from "vue-router"
import { sendOtp } from "@/services/otp.services"

import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import "@/assets/Styles/styles.css"

const router = useRouter()

// STATE
const email = ref("")
const loading = ref(false)
const pageLoading = ref(true)
const isVisible = ref(false)

onMounted(() => {
  setTimeout(() => {
    pageLoading.value = false
    isVisible.value = true
  }, 400)
})

// SEND OTP
const sendReset = async () => {
  if (!email.value) {
    Toastify({
      text: "Please enter your email",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  loading.value = true

  try {
    const normalizedEmail = email.value.trim().toLowerCase()
    await sendOtp(normalizedEmail)
    localStorage.setItem("pendingOtpEmail", normalizedEmail)

    Toastify({
      text: "OTP sent to your email.",
      backgroundColor: "#2563eb",
    }).showToast()

    router.push({
      path: "/auth/otp",
      query: { email: normalizedEmail, mode: "reset" },
    })
  } catch (error) {
    let message = "Failed to send OTP"

    Toastify({
      text: message,
      backgroundColor: "#e74c3c",
    }).showToast()
  } finally {
    loading.value = false
  }
}

// BACK TO LOGIN
const goLogin = () => {
  router.push("/login")
}
</script>
