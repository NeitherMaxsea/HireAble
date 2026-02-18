<template>
  <div class="auth-page">
    <button class="back-btn" @click="$router.push('/role')" aria-label="Back to choose role">
      <i class="bi bi-arrow-left"></i>
    </button>

    <div class="container">
      <div class="left">
        <img src="@/assets/PWD_worker.png" alt="Worker" class="worker" />
      </div>

      <div class="right" :class="{ 'fade-in': isVisible }">
        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" alt="HireAble logo" />
        </div>

        <span class="auth-pill">Account Setup</span>
        <h2 class="form-h2">Create Account</h2>
        <p class="form-p">
          Create your account and start your journey to meaningful and inclusive employment.
        </p>

        <p class="form-p role-copy">
          Registering as:
          <span class="role-label" :class="selectedRole">
            {{ roleLabel }}
          </span>
        </p>

        <div class="form-group">
          <label>Username</label>
          <div class="input-wrapper icon-group">
            <i class="bi bi-person-fill input-icon"></i>
            <input type="text" v-model="username" placeholder="Enter username" autocomplete="username" />
          </div>
        </div>

        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper icon-group">
            <i class="bi bi-envelope-fill input-icon"></i>
            <input type="email" v-model="email" placeholder="Enter email" autocomplete="email" />
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

        <div class="form-group password-group">
          <label>Password</label>
          <div class="password-wrapper icon-group">
            <i class="bi bi-lock-fill input-icon"></i>
            <input
              :type="showPassword ? 'text' : 'password'"
              v-model="password"
              placeholder="Enter password"
              autocomplete="new-password"
            />
            <span v-if="password" class="toggle-eye" @click="togglePassword">
              <i :class="showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
            </span>
          </div>
        </div>

        <div class="form-group password-group">
          <label>Confirm Password</label>
          <div class="password-wrapper icon-group">
            <i class="bi bi-shield-lock-fill input-icon"></i>
            <input
              :type="showConfirmPassword ? 'text' : 'password'"
              v-model="confirmPassword"
              placeholder="Confirm password"
              autocomplete="new-password"
            />
            <span v-if="confirmPassword" class="toggle-eye" @click="toggleConfirmPassword">
              <i :class="showConfirmPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
            </span>
          </div>
        </div>

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
  </div>
</template>

<script>
import Toastify from "toastify-js";
import "toastify-js/src/toastify.css";
import api from "@/services/api";

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
    };
  },

  mounted() {
    const queryRole = String(this.$route.query?.role || "")
      .trim()
      .toLowerCase();
    const roleFromQuery =
      queryRole === "employer" || queryRole === "applicant" ? queryRole : "";
    const role = roleFromQuery || localStorage.getItem("selectedRole");
    if (!role) {
      this.$router.replace("/role");
      return;
    }

    localStorage.setItem("selectedRole", role);
    this.selectedRole = role;
    requestAnimationFrame(() => {
      this.isVisible = true;
    });
  },

  computed: {
    isEmployerRegistration() {
      return String(this.selectedRole || "").toLowerCase() === "employer";
    },

    roleLabel() {
      return this.isEmployerRegistration ? "company admin" : this.selectedRole;
    },

    rules() {
      const companyOk = this.isEmployerRegistration
        ? this.companyName && this.companyAddress && this.companyIndustry
        : true;
      const checks = this.passwordChecks;

      return {
        filled:
          this.username &&
          this.email &&
          companyOk &&
          this.password &&
          this.confirmPassword,
        length: this.password.length >= 8,
        categoriesMet:
          [checks.lower, checks.upper, checks.number, checks.special].filter(
            Boolean
          ).length >= 3,
        noTripleRepeat: !/(.)\1\1/.test(this.password),
        match:
          this.password === this.confirmPassword && this.password.length > 0,
      };
    },

    passwordChecks() {
      return {
        lower: /[a-z]/.test(this.password),
        upper: /[A-Z]/.test(this.password),
        number: /\d/.test(this.password),
        special: /[^A-Za-z0-9]/.test(this.password),
      };
    },

    allValid() {
      return Object.values(this.rules).every(Boolean);
    },
  },

  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword;
    },

    toggleConfirmPassword() {
      this.showConfirmPassword = !this.showConfirmPassword;
    },

    async register() {
      if (!this.allValid) {
        Toastify({
          text: "Please meet all password requirements",
          backgroundColor: "#e74c3c",
        }).showToast();
        return;
      }

      this.loading = true;
      try {
        const normalizedRole = this.isEmployerRegistration
          ? "company_admin"
          : this.selectedRole;

        const res = await api.post("/auth/register", {
          name: this.username.trim(),
          email: this.email.trim().toLowerCase(),
          password: this.password,
          role: normalizedRole,
          companyName: this.isEmployerRegistration
            ? this.companyName.trim()
            : null,
          companyAddress: this.isEmployerRegistration
            ? this.companyAddress.trim()
            : null,
          companyIndustry: this.isEmployerRegistration
            ? this.companyIndustry.trim()
            : null,
        });

        const normalizedEmail = this.email.trim().toLowerCase();
        localStorage.removeItem("selectedRole");
        localStorage.setItem("pendingOtpEmail", normalizedEmail);
        const otpSent = res?.data?.otpSent !== false;
        Toastify({
          text: otpSent
            ? "Registration successful. OTP sent to your email."
            : "Registered, but OTP was not sent. Please resend OTP.",
          backgroundColor: otpSent ? "#2ecc71" : "#f59e0b",
        }).showToast();
        this.$router.replace({
          path: "/auth/otp",
          query: otpSent
            ? { email: normalizedEmail, mode: "register", force: "1" }
            : {
                email: normalizedEmail,
                mode: "register",
                otpSendFailed: "1",
                force: "1",
              },
        });
      } catch (error) {
        let message = "Registration failed";
        if (error?.response?.data?.message) message = error.response.data.message;
        else if (error?.message) message = error.message;

        Toastify({ text: message, backgroundColor: "#e74c3c" }).showToast();
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-page {
  position: relative;
  min-height: 100vh;
  padding: 64px 18px 36px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-x: hidden;
  overflow-y: auto;
  scroll-padding-top: 64px;
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
  border: 1px solid rgba(15, 79, 37, 0.14);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 24px 56px rgba(15, 79, 37, 0.18);
  position: relative;
  z-index: 1;
}

.left {
  width: 100%;
  min-width: 0;
  min-height: 640px;
  background: linear-gradient(180deg, rgba(6, 30, 17, 0.22), rgba(6, 30, 17, 0.38));
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
  padding: 40px 42px 34px;
  background: rgba(255, 255, 255, 0.97);
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.45s ease, transform 0.45s ease;
}

.fade-in {
  opacity: 1;
  transform: translateY(0);
}

.logo-container {
  display: flex;
  justify-content: center;
}

.logo-img {
  width: min(220px, 74%);
  height: auto;
}

.auth-pill {
  margin-top: 8px;
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
  margin: 12px 0 0;
  text-align: center;
  color: #122815;
  font-size: clamp(1.6rem, 2.5vw, 2rem);
  line-height: 1.15;
}

.form-p {
  margin: 8px auto 0;
  text-align: center;
  color: #4b5563;
  font-size: 0.84rem;
  line-height: 1.5;
  max-width: 390px;
}

.role-copy {
  margin-bottom: 16px;
}

.role-label {
  font-weight: 700;
  text-transform: capitalize;
}

.role-label.applicant {
  color: #15803d;
}

.role-label.employer {
  color: #0f4f25;
}

.form-group {
  width: 100%;
  margin-bottom: 11px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  color: #334155;
  font-size: 0.77rem;
  font-weight: 700;
  letter-spacing: 0.2px;
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
  padding-right: 45px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease,
    background-color 0.2s ease;
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

.rules {
  margin-top: 8px;
  border: 1px solid #d8e2ee;
  border-radius: 12px;
  padding: 12px 13px;
  background: #f8fafc;
  color: #334155;
  font-size: 0.76rem;
}

.rules strong {
  color: #0f172a;
  font-size: 0.78rem;
}

.rules ul {
  list-style: none;
  margin: 10px 0 0;
  padding: 0;
  display: grid;
  gap: 6px;
}

.rules li {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 4px 6px;
  color: #64748b;
  line-height: 1.35;
  transition: background-color 0.18s ease, color 0.18s ease;
}

.rules li::before {
  content: "•";
  width: 16px;
  margin-top: 1px;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 800;
  line-height: 1;
  color: #94a3b8;
  transition: color 0.18s ease, transform 0.18s ease;
}

.rules li.valid {
  color: #15803d;
}

.rules li.valid::before {
  content: "✓";
  color: #16a34a;
  transform: translateY(-1px);
}

.rules .sub-rules {
  margin-top: 6px;
  padding-left: 18px;
}

.btn {
  width: 100%;
  margin-top: 12px;
  min-height: 48px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #0f4f25 0%, #1f7a3f 100%);
  color: #ffffff;
  font-size: 0.9rem;
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

.auth-link {
  margin-top: 12px;
  text-align: center;
  color: #4b5563;
  font-size: 0.82rem;
}

.auth-link a {
  color: #0f4f25;
  font-weight: 700;
  text-decoration: none;
}

.auth-link a:hover {
  text-decoration: underline;
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

input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
  display: none;
}

@media (max-width: 980px) {
  .auth-page {
    padding: 54px 14px 24px;
    scroll-padding-top: 54px;
  }

  .container {
    grid-template-columns: 1fr;
    min-height: 0;
  }

  .left {
    min-height: 250px;
    max-height: 320px;
  }

  .right {
    padding: 30px 22px 24px;
  }
}

@media (max-width: 640px) {
  .auth-page {
    padding: 46px 10px 18px;
    scroll-padding-top: 46px;
  }

  .container {
    border-radius: 18px;
  }

  .right {
    padding: 24px 16px 20px;
  }

  .logo-img {
    width: min(200px, 72%);
  }

  .form-h2 {
    font-size: 1.45rem;
  }

  .form-p {
    font-size: 0.79rem;
  }

  .back-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
  }
}
</style>
