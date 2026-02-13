<template>
  <Navbar />

  <div class="container">
    <!-- LEFT IMAGE -->
    <div class="left">
      <img src="@/assets/PWD_worker.png" alt="Worker" class="worker" />
    </div>

    <!-- RIGHT FORM -->
    <div class="right" :class="{ 'fade-in': isVisible }">

      <button class="back-btn-outline" @click="$router.push('/role')">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Choose Role</span>
      </button>

      <div class="logo-container">
        <img src="@/assets/titlelogo.png" class="logo-img" />
      </div>

      <h2>Sign Up</h2>

      <p class="form-p">
        Create your account and start your journey to meaningful and inclusive employment.
      </p>

      <p class="form-p">
        Registering as:
        <span class="role-label" :class="selectedRole">
          {{ roleLabel }}
        </span>
      </p>

      <!-- USERNAME -->
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrapper icon-group">
          <i class="bi bi-person-fill input-icon"></i>
          <input type="text" v-model="username" placeholder="Enter username" />
        </div>
      </div>

      <!-- EMAIL -->
      <div class="form-group">
        <label>Email</label>
        <div class="input-wrapper icon-group">
          <i class="bi bi-envelope-fill input-icon"></i>
          <input type="email" v-model="email" placeholder="Enter email" />
        </div>
      </div>

      <template v-if="isEmployerRegistration">
        <div class="form-group">
          <label>Company Name</label>
          <div class="input-wrapper icon-group">
            <i class="bi bi-building-fill input-icon"></i>
            <input type="text" v-model="companyName" placeholder="Enter company name" />
          </div>
        </div>

        <div class="form-group">
          <label>Company Address</label>
          <div class="input-wrapper icon-group">
            <i class="bi bi-geo-alt-fill input-icon"></i>
            <input type="text" v-model="companyAddress" placeholder="Enter company address" />
          </div>
        </div>

        <div class="form-group">
          <label>Industry</label>
          <div class="input-wrapper icon-group">
            <i class="bi bi-briefcase-fill input-icon"></i>
            <input type="text" v-model="companyIndustry" placeholder="Enter company industry" />
          </div>
        </div>
      </template>

      <!-- PASSWORD -->
      <div class="form-group password-group">
        <label>Password</label>

        <div class="password-wrapper icon-group">
          <i class="bi bi-lock-fill input-icon"></i>
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            placeholder="Enter password"
          />

          <!-- SAME EYE AS LOGIN -->
          <span v-if="password" class="toggle-eye" @click="togglePassword">
            <i :class="showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
          </span>
        </div>
      </div>

      <!-- CONFIRM PASSWORD -->
      <div class="form-group password-group">
        <label>Confirm Password</label>

        <div class="password-wrapper icon-group">
          <i class="bi bi-shield-lock-fill input-icon"></i>
          <input
            :type="showConfirmPassword ? 'text' : 'password'"
            v-model="confirmPassword"
            placeholder="Confirm password"
          />

          <!-- SAME EYE AS LOGIN -->
          <span v-if="confirmPassword" class="toggle-eye" @click="toggleConfirmPassword">
            <i :class="showConfirmPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
          </span>
        </div>
      </div>

      <!-- PASSWORD RULES -->
      <div class="rules">
        <strong>Your password must contain:</strong>
        <ul>
          <li :class="{ valid: rules.length }">At least 8 characters</li>
          <li :class="{ valid: rules.categoriesMet }">At least 3 of the following:</li>
        </ul>
        <ul class="sub-rules">
          <li :class="{ valid: passwordChecks.lower }">Lower case letters (a-z)</li>
          <li :class="{ valid: passwordChecks.upper }">Upper case letters (A-Z)</li>
          <li :class="{ valid: passwordChecks.number }">Numbers (0-9)</li>
          <li :class="{ valid: passwordChecks.special }">Special characters (e.g. !@#$%^&*)</li>
        </ul>
        <ul>
          <li :class="{ valid: rules.noTripleRepeat }">No more than 2 identical characters in a row</li>
          <li :class="{ valid: rules.match }">Passwords match</li>
        </ul>
      </div>

      <!-- REGISTER BUTTON -->
      <button class="btn" @click="register" :disabled="loading">
        <span v-if="loading"><i class="bi bi-hourglass-split"></i> Creating account...</span>
        <span v-else><i class="bi bi-person-plus-fill"></i> Register</span>
      </button>

      <p class="auth-link">
        Already have an account?
        <router-link to="/login">Login here</router-link>
      </p>

    </div>
  </div>
</template>

<script>
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import api from "@/services/api"

export default {
  name: "Register",

  data() {
    return {
      username: "",
      email: "",
      companyName: "",
      companyAddress: "",
      companyIndustry: "",
      password: "",
      confirmPassword: "",
      showPassword: false,
      showConfirmPassword: false,
      loading: false,
      selectedRole: "",
      isVisible: false,
    }
  },

  mounted() {
    const queryRole = String(this.$route.query?.role || "").trim().toLowerCase()
    const roleFromQuery = queryRole === "employer" || queryRole === "applicant" ? queryRole : ""
    const role = roleFromQuery || localStorage.getItem("selectedRole")
    if (!role) {
      this.$router.replace("/role")
      return
    }

    localStorage.setItem("selectedRole", role)
    this.selectedRole = role
    requestAnimationFrame(() => {
      this.isVisible = true
    })
  },

  computed: {
    isEmployerRegistration() {
      return String(this.selectedRole || "").toLowerCase() === "employer"
    },

    roleLabel() {
      return this.isEmployerRegistration ? "company admin" : this.selectedRole
    },

    rules() {
      const companyOk = this.isEmployerRegistration
        ? this.companyName && this.companyAddress && this.companyIndustry
        : true
      const checks = this.passwordChecks

      return {
        filled:
          this.username &&
          this.email &&
          companyOk &&
          this.password &&
          this.confirmPassword,
        length: this.password.length >= 8,
        categoriesMet: [checks.lower, checks.upper, checks.number, checks.special].filter(Boolean).length >= 3,
        noTripleRepeat: !/(.)\1\1/.test(this.password),
        match: this.password === this.confirmPassword && this.password.length > 0,
      }
    },

    passwordChecks() {
      return {
        lower: /[a-z]/.test(this.password),
        upper: /[A-Z]/.test(this.password),
        number: /\d/.test(this.password),
        special: /[^A-Za-z0-9]/.test(this.password),
      }
    },

    allValid() {
      return Object.values(this.rules).every(Boolean)
    },
  },

  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword
    },

    toggleConfirmPassword() {
      this.showConfirmPassword = !this.showConfirmPassword
    },

    async register() {
      if (!this.allValid) {
        Toastify({ text: "Please meet all password requirements", backgroundColor: "#e74c3c" }).showToast()
        return
      }

      this.loading = true
      try {
        const normalizedRole = this.isEmployerRegistration ? "company_admin" : this.selectedRole

        const res = await api.post("/auth/register", {
          name: this.username.trim(),
          email: this.email.trim().toLowerCase(),
          password: this.password,
          role: normalizedRole,
          companyName: this.isEmployerRegistration ? this.companyName.trim() : null,
          companyAddress: this.isEmployerRegistration ? this.companyAddress.trim() : null,
          companyIndustry: this.isEmployerRegistration ? this.companyIndustry.trim() : null,
        })

        const normalizedEmail = this.email.trim().toLowerCase()
        localStorage.removeItem("selectedRole")
        localStorage.setItem("pendingOtpEmail", normalizedEmail)
        const otpSent = res?.data?.otpSent !== false
        Toastify({
          text: otpSent
            ? "Registration successful. OTP sent to your email."
            : "Registered, but OTP was not sent. Please resend OTP.",
          backgroundColor: otpSent ? "#2ecc71" : "#f59e0b",
        }).showToast()
        this.$router.replace({
          path: "/auth/otp",
          query: otpSent
            ? { email: normalizedEmail, mode: "register", force: "1" }
            : { email: normalizedEmail, mode: "register", otpSendFailed: "1", force: "1" },
        })
      } catch (error) {
        let message = "Registration failed"
        if (error?.response?.data?.message) message = error.response.data.message
        else if (error?.message) message = error.message

        Toastify({ text: message, backgroundColor: "#e74c3c" }).showToast()
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
/* FADE IN */
.right {
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.fade-in {
  opacity: 1;
  transform: translateY(0);
}

/* BACK BUTTON */
.back-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  position: absolute;
  top: 20px;
  left: 20px;
  padding: 8px 16px;
  font-size: 14px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
}

.back-btn-outline:hover {
  background: #f0f2f5;
}

.icon-group {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #64748b;
  pointer-events: none;
}

.icon-group input {
  width: 100%;
  padding-left: 42px;
}

/* ROLE LABEL */
.role-label {
  font-weight: 600;
  text-transform: capitalize;
}

.role-label.applicant {
  color: #22c55e;
}

.role-label.employer {
  color: #3b82f6;
}

/* PASSWORD FIELD */
.password-wrapper {
  position: relative;
}

.password-wrapper input {
  width: 100%;
  padding-right: 42px;
}

/* âœ… SAME AS LOGIN */
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

.rules {
  margin-top: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 12px;
  background: #f8fafc;
}

.rules ul {
  margin: 8px 0 0;
  padding-left: 18px;
}

.rules li {
  color: #334155;
}

.rules li.valid {
  color: #15803d;
}

.rules .sub-rules {
  margin-top: 4px;
}

/* REMOVE BROWSER EYE */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
  display: none;
}
</style>

