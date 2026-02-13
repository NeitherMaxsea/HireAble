<template>
  <div class="auth-page">
    <button class="back-btn" @click="goBack" aria-label="Back to landing">
      <i class="bi bi-arrow-left"></i>
    </button>

    <!-- CENTER LOADING -->
    <div v-if="pageLoading" class="page-loading">
      <div class="loader"></div>
    </div>

    <div class="container">

      <!-- LEFT IMAGE -->
      <div class="left">
        <img src="@/assets/PWD_login.png" alt="Worker" class="worker" />
      </div>

      <!-- RIGHT FORM -->
      <div class="right fade-wrapper" :class="{ show: isVisible }">

        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" />
        </div>

        <h2 class="form-h2">
          Welcome!
          <p class="form-p">
            Your journey to meaningful and inclusive employment starts here.
          </p>
        </h2>

        <!-- EMAIL -->
        <div class="form-group icon-group">
          <i class="bi bi-envelope-fill input-icon"></i>
          <input
            type="email"
            placeholder="Enter email"
            v-model="email"
          />
        </div>

        <!-- PASSWORD -->
        <div class="form-group password-group">

          <div class="password-wrapper icon-group">
            <i class="bi bi-lock-fill input-icon"></i>

            <input
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter password"
              v-model="password"
            />

            <span
              v-if="password"
              class="toggle-eye"
              @click="togglePassword"
            >
              <i :class="showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
            </span>
          </div>

          <div class="forgot-password">
            <router-link to="/auth/forget-password">
              Forgot password?
            </router-link>
          </div>

        </div>

        <!-- LOGIN BUTTON -->
        <button class="btn" @click="login" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Login</span>
        </button>

        <!-- REGISTER -->
        <p class="auth-link">
          Don't have an account?
          <a href="#" @click.prevent="goRegister">Register here</a>
        </p>

        <div class="divider">
          <span>OR</span>
        </div>

        <button class="google-btn" disabled>
          Continue with Google (coming soon)
        </button>

      </div>

    </div>

    <div v-if="showCompanyVerificationModal" class="modal-backdrop">
      <div class="company-modal">
        <h3>Company Admin Verification</h3>
        <p class="modal-text">
          Enter your 6-digit company admin verification code.
        </p>
        <div class="modal-input-wrap">
          <input
            class="modal-input"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="Enter verification code"
            v-model="verificationCode"
          />
        </div>
        <div class="modal-actions">
          <button class="modal-btn cancel" @click="cancelCompanyVerification" :disabled="verificationLoading">
            Cancel
          </button>
          <button class="modal-btn primary" @click="confirmCompanyVerification" :disabled="verificationLoading">
            <span v-if="verificationLoading" class="spinner"></span>
            <span v-else>Verify & Login</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="showSessionInUseModal" class="modal-backdrop">
      <div class="company-modal">
        <h3>Account In Use</h3>
        <p class="modal-text">{{ sessionInUseMessage }}</p>
        <div class="modal-actions">
          <button class="modal-btn primary" @click="closeSessionInUseModal">
            OK
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRouter } from "vue-router"
import api from "@/services/api"
import { auth } from "@/lib/client-platform"

import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import "@/assets/Styles/styles.css"

const router = useRouter()

function goBack() {
  router.push("/")
}

const email = ref("")
const password = ref("")
const verificationCode = ref("")
const showPassword = ref(false)
const loading = ref(false)
const showCompanyVerificationModal = ref(false)
const verificationLoading = ref(false)
const pendingSessionKey = ref("")
const showSessionInUseModal = ref(false)
const sessionInUseMessage = ref("This account is currently in use on another device.")

const pageLoading = ref(true)
const isVisible = ref(false)

onMounted(() => {
  setTimeout(() => {
    pageLoading.value = false
    isVisible.value = true
  }, 500)
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const goRegister = () => {
  router.push({ path: "/role", query: { force: "1" } })
}

function createSessionId() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function finishLogin(data, normalizedEmail, acceptedSessionKey = "") {
  const role = String(data.role || "").toLowerCase()
  if (!role) {
    Toastify({ text: "Role missing in account profile. Please contact admin.", backgroundColor: "#e74c3c" }).showToast()
    return
  }

  auth.currentUser = {
    uid: String(data.id || ""),
    email: data.email || normalizedEmail,
  }

  const sessionId = acceptedSessionKey || createSessionId()

  localStorage.setItem("userName", data.username || data.name || "User")
  localStorage.setItem("userEmail", data.email || normalizedEmail)
  localStorage.setItem("userRole", role)
  localStorage.setItem("companyId", data.companyId || "")
  localStorage.setItem("companyName", data.companyName || "")
  localStorage.setItem("userUid", String(data.id || ""))
  localStorage.setItem("uid", String(data.id || ""))
  localStorage.setItem("activeSessionId", sessionId)
  localStorage.setItem("sessionUid", String(data.id || ""))
  localStorage.setItem("userCollection", "users")

  Toastify({ text: "Login successful!", backgroundColor: "#2ecc71" }).showToast()

  if (role === "applicant") {
    router.replace("/applicant/job_list")
  } else if (role === "admin") {
    router.replace("/admin/dashboard")
  } else if (role === "company_admin") {
    router.replace("/company-admin/dashboard")
  } else {
    router.replace("/landingpage")
  }
}

function closeSessionInUseModal() {
  showSessionInUseModal.value = false
}

const login = async () => {
  const normalizedEmail = email.value.trim()

  if (!normalizedEmail || !password.value) {
    Toastify({ text: "Please enter your email and password", backgroundColor: "#e74c3c" }).showToast()
    return
  }

  loading.value = true
  showSessionInUseModal.value = false
  try {
    const sessionKey = createSessionId()
    pendingSessionKey.value = sessionKey

    const res = await api.post("/auth/login", {
      email: normalizedEmail,
      password: password.value,
      sessionKey,
    })

    if (res.data?.requiresCompanyAdminVerification) {
      showCompanyVerificationModal.value = true
      verificationCode.value = ""
      return
    }

    const acceptedSessionKey = String(res.data?.sessionKey || pendingSessionKey.value || sessionKey)
    finishLogin(res.data?.user || {}, normalizedEmail, acceptedSessionKey)
    pendingSessionKey.value = ""
  } catch (error) {
    let message = "Login failed"
    if (error?.response?.status === 401) message = "Incorrect email or password"
    else if (error?.response?.status === 409) {
      message = error?.response?.data?.message || "This account is currently in use on another device."
      sessionInUseMessage.value = message
      showSessionInUseModal.value = true
    }
    else if (error?.response?.data?.message) message = error.response.data.message
    else if (error?.message) message = error.message

    if (error?.response?.status !== 409) {
      Toastify({ text: message, backgroundColor: "#e74c3c" }).showToast()
    }
  } finally {
    loading.value = false
  }
}

function cancelCompanyVerification() {
  showCompanyVerificationModal.value = false
  verificationCode.value = ""
  pendingSessionKey.value = ""
}

async function confirmCompanyVerification() {
  const normalizedEmail = email.value.trim()
  const code = verificationCode.value.trim()
  if (!code) {
    Toastify({ text: "Please enter the verification code.", backgroundColor: "#e74c3c" }).showToast()
    return
  }

  verificationLoading.value = true
  try {
    const sessionKey = pendingSessionKey.value || createSessionId()
    const res = await api.post("/auth/login", {
      email: normalizedEmail,
      password: password.value,
      verificationCode: code,
      sessionKey,
    })

    showCompanyVerificationModal.value = false
    const acceptedSessionKey = String(res.data?.sessionKey || sessionKey)
    finishLogin(res.data?.user || {}, normalizedEmail, acceptedSessionKey)
    pendingSessionKey.value = ""
  } catch (error) {
    if (error?.response?.status === 409) {
      sessionInUseMessage.value = error?.response?.data?.message || "This account is currently in use on another device."
      showSessionInUseModal.value = true
      showCompanyVerificationModal.value = false
      pendingSessionKey.value = ""
    } else {
      const message = error?.response?.data?.message || "Verification failed"
      Toastify({ text: message, backgroundColor: "#e74c3c" }).showToast()
    }
  } finally {
    verificationLoading.value = false
  }
}
</script>

<style scoped>
html {
  font-family: "Poppins", sans-serif;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

/* PAGE WRAPPER */
.auth-page {
  position: relative;
  min-height: 100vh;
}

/* CENTER LOADING */
.page-loading {
  position: fixed;
  inset: 0;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

/* LOADER */
.loader {
  width: 46px;
  height: 46px;
  border: 4px solid #e5e7eb;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* FADE IN FORM */
.fade-wrapper {
  opacity: 0;
  transform: translateY(16px);
  transition: all 0.6s ease;
}

.fade-wrapper.show {
  opacity: 1;
  transform: translateY(0);
}

/* ICON GROUP */
.icon-group {
  position: relative;
}

/* LEFT ICON */
.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #64748b;
  pointer-events: none;
}

/* PASSWORD WRAPPER */
.password-wrapper {
  position: relative;
}

/* INPUT SPACING */
.icon-group input {
  padding-left: 42px;
  padding-right: 42px;
}

/* EYE ICON */
.toggle-eye {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
}

.toggle-eye i {
  font-size: 16px;
  color: #64748b;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.58);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 20px;
}

.company-modal {
  width: 100%;
  max-width: 520px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 14px 50px rgba(15, 23, 42, 0.2);
  padding: 22px 20px;
}

.company-modal h3 {
  margin: 0 0 10px;
  color: #0f172a;
}

.modal-text {
  margin: 0 0 12px;
  color: #334155;
  line-height: 1.45;
}

.modal-input-wrap {
  margin-bottom: 14px;
}

.modal-input {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 11px 12px;
  font-size: 14px;
  outline: none;
}

.modal-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.modal-waiting {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 10px 0 14px;
  color: #1e293b;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.otp-inputs {
  display: flex;
  gap: 8px;
  margin: 12px 0;
}

.otp-inputs.disabled {
  opacity: 0.7;
}

.otp-input {
  width: 44px;
  height: 44px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  text-align: center;
  font-size: 18px;
  font-weight: 600;
  outline: none;
}

.otp-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.modal-resend {
  margin: 0 0 14px;
  color: #334155;
}

.resend-link {
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
  font-weight: 600;
  padding: 0;
}

.resend-link:disabled {
  color: #94a3b8;
  cursor: not-allowed;
}

/* BACK BUTTON */
.back-btn{
  position: absolute;
  top: 18px;
  left: 18px;
  z-index: 1300;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width:40px;
  height:40px;
  background: rgba(255,255,255,0.95);
  border: 1px solid #e5e7eb;
  border-radius:8px;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(2,6,23,0.08);
}

.back-btn i{ font-size:18px; color:#0f172a }

.modal-btn {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  cursor: pointer;
}

.modal-btn.cancel {
  background: #ef4444;
  color: #ffffff;
}

.modal-btn.secondary {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-btn.primary {
  background: #2563eb;
  color: #ffffff;
}

@media (max-width: 640px) {
  .company-modal {
    padding: 18px 16px;
  }

  .modal-actions {
    flex-wrap: wrap;
  }

  .modal-btn {
    flex: 1 1 120px;
  }
}
</style>


