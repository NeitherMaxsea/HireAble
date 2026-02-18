<template>
  <div class="auth-page">
    <button class="back-btn" @click="goBack" aria-label="Back to landing">
      <i class="bi bi-arrow-left"></i>
    </button>

    <!-- CENTER LOADING -->
    <transition name="page-loading-fade">
      <div v-if="pageLoading" class="page-loading">
        <div class="loader" aria-hidden="true"></div>
      </div>
    </transition>

    <div class="container">

      <!-- LEFT IMAGE -->
      <div class="left">
        <img src="@/assets/PWD_login.png" alt="Worker" class="worker" />
      </div>

      <!-- RIGHT FORM -->
      <div class="right fade-wrapper" :class="{ show: isVisible }">

        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" alt="HireAble logo" />
        </div>

        <span class="auth-pill">Secure Sign In</span>
        <h2 class="form-h2">Welcome Back</h2>
        <p class="form-p">
          Your journey to meaningful and inclusive employment starts here.
        </p>

        <!-- EMAIL / USERNAME -->
        <div class="form-group icon-group">
          <i class="bi bi-envelope-fill input-icon"></i>
          <input
            type="text"
            placeholder="Enter email or username"
            v-model="email"
            autocomplete="username"
            @keyup.enter="login"
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
              autocomplete="current-password"
              @keyup.enter="login"
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
        <button class="btn" type="button" @click="login" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Log In</span>
        </button>

        <!-- REGISTER -->
        <p class="auth-link">
          Don't have an account?
          <a href="#" @click.prevent="goRegister">Register here</a>
        </p>

        <div class="divider">
          <span>OR</span>
        </div>

        <button class="google-btn" type="button" disabled>
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

function normalizeRole(value) {
  const role = String(value || "").trim().toLowerCase().replace(/[\s-]+/g, "_")
  if (role === "hr_department") return "hr"
  if (role === "operation_department") return "operation"
  if (role === "financial_department" || role === "finance_department") return "finance"
  if (role === "companyadmin") return "company_admin"
  return role
}

function finishLogin(data, normalizedEmail, acceptedSessionKey = "") {
  const role = normalizeRole(data.role)
  const accountType = String(data.accountType || (role === "admin" ? "admins" : "users")).toLowerCase() === "admins"
    ? "admins"
    : "users"
  if (!role) {
    Toastify({ text: "Role missing in account profile. Please contact admin.", backgroundColor: "#e74c3c" }).showToast()
    return
  }

  auth.currentUser = {
    uid: String(data.id || ""),
    email: data.email || normalizedEmail,
    accountType,
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
  localStorage.setItem("userCollection", accountType)

  Toastify({ text: "Login successful!", backgroundColor: "#2ecc71" }).showToast()

  if (role === "applicant") {
    router.replace("/applicant/job_list")
  } else if (role === "admin") {
    router.replace("/admin/dashboard")
  } else if (role === "company_admin") {
    router.replace("/company-admin/dashboard")
  } else if (role === "hr" || role === "employer") {
    router.replace("/employer/HR/dashboard")
  } else if (role === "operation") {
    router.replace("/employer/operation/dashboard")
  } else if (role === "finance") {
    router.replace("/employer/finance/dashboard")
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
    Toastify({ text: "Please enter your email or username and password", backgroundColor: "#e74c3c" }).showToast()
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
.auth-page {
  position: relative;
  min-height: 100vh;
  padding: 28px 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #ffffff;
}

.auth-page::before,
.auth-page::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
}

.auth-page::before {
  width: 360px;
  height: 360px;
  top: -120px;
  right: -90px;
  background: radial-gradient(circle, rgba(31, 122, 63, 0.24) 0%, rgba(31, 122, 63, 0) 70%);
}

.auth-page::after {
  width: 290px;
  height: 290px;
  bottom: -110px;
  left: -70px;
  background: radial-gradient(circle, rgba(15, 79, 37, 0.28) 0%, rgba(15, 79, 37, 0) 70%);
}

.container {
  width: min(1100px, 100%);
  min-height: 640px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  border-radius: 24px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.32);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 24px 56px rgba(2, 12, 7, 0.35);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  position: relative;
  z-index: 1;
}

.left {
  width: 100%;
  min-width: 0;
  min-height: 640px;
  background: linear-gradient(180deg, rgba(6, 30, 17, 0.22), rgba(6, 30, 17, 0.38));
  position: relative;
}

.worker {
  width: 100%;
  height: 100%;
  max-width: none;
  object-fit: cover;
  object-position: center;
  display: block;
}

.right {
  width: 100%;
  min-width: 0;
  padding: 42px 42px 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: rgba(255, 255, 255, 0.97);
}

.page-loading {
  position: fixed;
  inset: 0;
  background: rgba(30, 41, 59, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(2px);
}

.page-loading-fade-enter-active,
.page-loading-fade-leave-active {
  transition: opacity 0.28s ease;
}

.page-loading-fade-enter-from,
.page-loading-fade-leave-to {
  opacity: 0;
}

.loader {
  width: 58px;
  height: 58px;
  border-radius: 999px;
  background: conic-gradient(
    transparent 0 34%,
    #22c55e 34% 44%,
    transparent 44% 60%,
    rgba(134, 239, 172, 0.95) 60% 74%,
    transparent 74% 100%
  );
  -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 6px), #000 calc(100% - 6px));
  mask: radial-gradient(farthest-side, transparent calc(100% - 6px), #000 calc(100% - 6px));
  animation: spin 0.9s linear infinite;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.35);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.fade-wrapper {
  opacity: 0;
  transform: translateY(14px);
  transition: opacity 0.55s ease, transform 0.55s ease;
}

.fade-wrapper.show {
  opacity: 1;
  transform: translateY(0);
}

.logo-container {
  display: flex;
  justify-content: center;
}

.logo-img {
  width: min(240px, 76%);
  height: auto;
}

.auth-pill {
  margin-top: 10px;
  align-self: center;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 5px 12px;
  border: 1px solid rgba(15, 79, 37, 0.18);
  background: #eef7f2;
  color: #0f4f25;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.form-h2 {
  margin: 14px 0 0;
  text-align: center;
  color: #122815;
  font-size: clamp(1.7rem, 2.7vw, 2.15rem);
  line-height: 1.1;
}

.form-p {
  margin: 10px auto 22px;
  text-align: center;
  color: #4b5563;
  font-size: 0.87rem;
  line-height: 1.55;
  max-width: 390px;
}

.form-group {
  width: 100%;
  margin-bottom: 12px;
}

.icon-group {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 15px;
  color: #6b7280;
  pointer-events: none;
}

.form-group input {
  width: 100%;
  height: 48px;
  border: 1px solid #d4dde7;
  border-radius: 12px;
  background: #f9fbfd;
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 500;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.icon-group input {
  padding-left: 45px;
  padding-right: 45px;
}

.form-group input::placeholder {
  color: #9ca3af;
}

.form-group input:focus {
  outline: none;
  border-color: #1f7a3f;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(31, 122, 63, 0.14);
}

.password-wrapper {
  position: relative;
}

.toggle-eye {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
}

.toggle-eye i {
  font-size: 15px;
  color: #64748b;
  transition: color 0.2s ease;
}

.toggle-eye:hover i {
  color: #0f172a;
}

.forgot-password {
  margin-top: 8px;
  text-align: right;
}

.forgot-password a {
  font-size: 0.78rem;
  font-weight: 600;
  color: #185c30;
  text-decoration: none;
}

.forgot-password a:hover {
  text-decoration: underline;
}

.btn {
  width: 100%;
  margin-top: 8px;
  height: 48px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #0f4f25 0%, #1f7a3f 100%);
  color: #ffffff;
  font-size: 0.93rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(9, 45, 21, 0.28);
  filter: brightness(1.02);
}

.btn:disabled {
  opacity: 0.8;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  animation: spin 0.7s linear infinite;
}

.auth-link {
  margin-top: 14px;
  text-align: center;
  color: #4b5563;
  font-size: 0.84rem;
}

.auth-link a {
  color: #0f4f25;
  font-weight: 700;
  text-decoration: none;
}

.auth-link a:hover {
  text-decoration: underline;
}

.divider {
  display: flex;
  align-items: center;
  text-align: center;
  margin: 16px 0;
  color: #94a3b8;
  font-size: 0.76rem;
  font-weight: 600;
}

.divider::before,
.divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider span {
  padding: 0 10px;
}

.google-btn {
  width: 100%;
  height: 46px;
  border: 1px dashed #c7d2dc;
  border-radius: 12px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: not-allowed;
}

.back-btn {
  position: absolute;
  top: 18px;
  left: 18px;
  z-index: 20;
  width: 42px;
  height: 42px;
  border: 1px solid rgba(15, 79, 37, 0.26);
  border-radius: 12px;
  background: rgba(15, 79, 37, 0.1);
  color: #0f4f25;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(15, 79, 37, 0.14);
  backdrop-filter: blur(6px);
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.back-btn:hover {
  background: rgba(15, 79, 37, 0.16);
  transform: translateY(-1px);
}

.back-btn i {
  font-size: 18px;
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
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 14px 50px rgba(15, 23, 42, 0.2);
  padding: 24px 22px;
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
  height: 46px;
  border: 1px solid #d1d5db;
  border-radius: 11px;
  padding: 0 12px;
  font-size: 0.9rem;
  outline: none;
}

.modal-input:focus {
  border-color: #1f7a3f;
  box-shadow: 0 0 0 4px rgba(31, 122, 63, 0.14);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.modal-btn {
  border: 1px solid transparent;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
}

.modal-btn.cancel {
  background: #f5f5f5;
  color: #0f172a;
  border-color: #e2e8f0;
}

.modal-btn.cancel:hover {
  background: #eceff3;
}

.modal-btn.secondary {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-btn.primary {
  background: linear-gradient(135deg, #0f4f25 0%, #1f7a3f 100%);
  color: #ffffff;
}

.modal-btn.primary:hover {
  filter: brightness(1.03);
}

.modal-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

@media (max-width: 1080px) {
  .right {
    padding: 36px 30px 30px;
  }
}

@media (max-width: 980px) {
  .auth-page {
    padding: 20px 14px;
  }

  .container {
    grid-template-columns: 1fr;
    min-height: 0;
  }

  .left {
    min-height: 260px;
    max-height: 320px;
  }

  .right {
    padding: 28px 22px 24px;
  }

  .logo-img {
    width: min(210px, 70%);
  }
}

@media (max-width: 640px) {
  .auth-page {
    padding: 14px 10px;
  }

  .container {
    border-radius: 18px;
  }

  .right {
    padding: 24px 16px 20px;
  }

  .auth-pill {
    margin-top: 6px;
  }

  .form-h2 {
    font-size: 1.55rem;
  }

  .form-p {
    font-size: 0.8rem;
    margin-bottom: 18px;
  }

  .back-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
  }

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


