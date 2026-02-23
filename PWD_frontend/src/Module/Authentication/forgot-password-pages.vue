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
          <div class="input-wrapper icon-group">
            <i class="bi bi-envelope-fill input-icon"></i>
            <input
              type="email"
              placeholder="Enter your email"
              v-model="email"
              autocomplete="email"
            />
          </div>
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

<style scoped>
.form-group label {
  display: block;
  margin-bottom: 6px;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 700;
}

.icon-group {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-size: 15px;
  pointer-events: none;
}

.icon-group input {
  width: 100%;
  height: 46px;
  border: 1px solid #d4dde7;
  border-radius: 11px;
  background: #f9fbfd;
  color: #0f172a;
  font-size: 0.88rem;
  font-weight: 500;
  padding-left: 45px;
  padding-right: 14px;
  box-sizing: border-box;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.icon-group input::placeholder {
  color: #9ca3af;
}

.icon-group input:focus {
  outline: none;
  border-color: #1f7a3f;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(31, 122, 63, 0.14);
}
</style>
