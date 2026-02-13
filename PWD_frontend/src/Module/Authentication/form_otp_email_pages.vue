<template>
  <div class="auth-page">
    <div v-if="redirecting" class="page-loading">
      <div class="loader"></div>
    </div>
    <div class="container">
      <!-- LEFT -->
      <div class="left">
        <img src="@/assets/PWD_worker.png" class="worker" />
      </div>

      <!-- RIGHT -->
      <div class="right fade-in">
        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" />
        </div>

        <h2>OTP verification</h2>
        <p class="form-p">
          We sent a 6-digit code to <strong>{{ email }}</strong>
        </p>

        <div class="otp-group">
          <label class="otp-label">OTP Code</label>
          <div
            class="otp-inputs"
            :class="{
              'otp-error': otpStatus === 'error',
              'otp-success': otpStatus === 'success'
            }"
          >
            <input
              v-for="(digit, i) in otpDigits"
              :key="i"
              class="otp-input"
              type="text"
              inputmode="numeric"
              maxlength="1"
              :value="digit"
              @input="onOtpInput($event, i)"
              @keydown="onOtpKeydown($event, i)"
              @paste="onOtpPaste($event)"
              :ref="el => (otpRefs[i] = el)"
            />
          </div>
        </div>

        <button class="btn" @click="handleVerify" :disabled="loading">
          {{ loading ? (redirecting ? "Loading..." : "Verifying...") : "Verify OTP" }}
        </button>

        <!-- RESEND SECTION -->
        <p class="auth-link">
          <template v-if="resendCount < maxResend">
            <a
              href="#"
              @click.prevent="handleResend"
              :style="{ pointerEvents: canResend ? 'auto' : 'none', opacity: canResend ? 1 : 0.5 }"
            >
              Resend OTP
            </a>
            <span v-if="!canResend">
              (wait {{ countdown }}s)
            </span>
            <br />
            <small>
              Resends left: {{ maxResend - resendCount }}
            </small>
          </template>

          <template v-else>
            <span style="color:#e74c3c;">
              Resend limit reached
            </span>
          </template>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from "vue"
import { useRoute, useRouter } from "vue-router"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

import { verifyOtp, sendOtp } from "@/services/otp.services"

const route = useRoute()
const router = useRouter()

const email = ref(String(route.query.email || localStorage.getItem("pendingOtpEmail") || "").trim().toLowerCase())
const mode = route.query.mode || ""

const otpDigits = ref(Array(6).fill(""))
const otpRefs = []
const loading = ref(false)
const otpStatus = ref("idle")
const redirecting = ref(false)

// 🔒 RESEND LIMITATION
const maxResend = 2
const resendCount = ref(0)
const countdown = ref(60)
const canResend = ref(false)
let timer = null

// START TIMER
const startTimer = () => {
  canResend.value = false
  countdown.value = 60

  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      canResend.value = true
      clearInterval(timer)
      timer = null
    }
  }, 1000)
}

onMounted(() => {
  if (!email.value) {
    Toastify({
      text: "Missing OTP email. Please request OTP again.",
      backgroundColor: "#e74c3c"
    }).showToast()
    router.replace("/auth/forget-password")
    return
  }

  localStorage.setItem("pendingOtpEmail", email.value)

  if (route.query.otpSendFailed === "1") {
    Toastify({
      text: "OTP not sent earlier. Click Resend OTP.",
      backgroundColor: "#f59e0b"
    }).showToast()
  }

  startTimer()
  nextTick(() => {
    otpRefs[0]?.focus()
  })
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const handleVerify = async () => {
  const otpValue = otpDigits.value.join("")
  if (otpValue.length !== 6) {
    otpStatus.value = "error"
    Toastify({
      text: "Invalid OTP",
      backgroundColor: "#e74c3c"
    }).showToast()
    return
  }

  loading.value = true

  try {
    const res = await verifyOtp(email.value.trim().toLowerCase(), otpValue, mode)

    if (res.data.valid) {
      otpStatus.value = "success"
      Toastify({
        text: "Email verified successfully.",
        backgroundColor: "#2ecc71"
      }).showToast()

      localStorage.removeItem("pendingOtpEmail")

      if (mode === "reset") {
        redirecting.value = true
        await nextTick()
        await new Promise((resolve) => setTimeout(resolve, 600))
        await router.push({
          path: "/auth/reset-password",
          query: { email: email.value }
        })
      } else {
        router.push("/login")
      }
    } else {
      throw new Error()
    }
  } catch (error) {
    otpStatus.value = "error"
    const message = error?.response?.data?.message || "Incorrect or expired OTP"
    Toastify({
      text: message,
      backgroundColor: "#e74c3c"
    }).showToast()
  } finally {
    if (!redirecting.value) {
      loading.value = false
    }
  }
}

const handleResend = async () => {
  if (!canResend.value || resendCount.value >= maxResend) return

  try {
    await sendOtp(email.value.trim().toLowerCase())
    resendCount.value++
    startTimer()
    Toastify({
      text: "OTP resent",
      backgroundColor: "#2563eb"
    }).showToast()
  } catch {
    Toastify({
      text: "Failed to resend OTP. Check backend PHPMailer/SMTP settings.",
      backgroundColor: "#e74c3c"
    }).showToast()
  }
}

const onOtpInput = (e, index) => {
  const val = e.target.value.replace(/\D/g, "")
  otpDigits.value[index] = val
  if (otpStatus.value !== "idle") {
    otpStatus.value = "idle"
  }
  if (val && index < otpDigits.value.length - 1) {
    otpRefs[index + 1]?.focus()
  }
}

const onOtpKeydown = (e, index) => {
  if (e.key === "Backspace" && !otpDigits.value[index] && index > 0) {
    otpRefs[index - 1]?.focus()
  }
}

const onOtpPaste = (e) => {
  const paste = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6)
  if (!paste) return
  otpDigits.value = paste.split("").concat(Array(6).fill("")).slice(0, 6)
  if (otpStatus.value !== "idle") {
    otpStatus.value = "idle"
  }
  const nextIndex = Math.min(paste.length, 5)
  otpRefs[nextIndex]?.focus()
  e.preventDefault()
}
</script>

<style scoped>
.fade-in {
  opacity: 1;
}

.otp-group{
  margin: 18px 0 10px;
}

.otp-label{
  display:block;
  font-weight:600;
  margin-bottom:10px;
}

.otp-inputs{
  display:flex;
  gap:10px;
  justify-content:flex-start;
}

.otp-input{
  width:48px;
  height:48px;
  border:1px solid #e5e7eb;
  border-radius:10px;
  text-align:center;
  font-size:18px;
  font-weight:600;
  outline:none;
  transition:border-color .2s ease, box-shadow .2s ease;
}

.otp-input:focus{
  border-color:#2563eb;
  box-shadow:0 0 0 3px rgba(37, 99, 235, 0.15);
}

.otp-inputs.otp-error .otp-input{
  border-color:#e74c3c;
  box-shadow:0 0 0 3px rgba(231, 76, 60, 0.15);
}

.otp-inputs.otp-error .otp-input:focus{
  border-color:#e74c3c;
  box-shadow:0 0 0 3px rgba(231, 76, 60, 0.2);
}

.otp-inputs.otp-success .otp-input{
  border-color:#22c55e;
  box-shadow:0 0 0 3px rgba(34, 197, 94, 0.15);
}

.otp-inputs.otp-success .otp-input:focus{
  border-color:#22c55e;
  box-shadow:0 0 0 3px rgba(34, 197, 94, 0.2);
}

.page-loading {
  position: fixed;
  inset: 0;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

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
</style>

