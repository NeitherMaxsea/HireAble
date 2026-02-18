<template>
  <div class="auth-page">
    <button class="back-btn" @click="goBack" aria-label="Back to login">
      <i class="bi bi-arrow-left"></i>
    </button>

    <div class="container">
      <div class="left">
        <img src="@/assets/PWD_choose.png" alt="Worker" class="worker" />
      </div>

      <div
        class="right"
        :class="{
          'is-visible': isVisible,
          'is-fading': isFading
        }"
      >
        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" alt="HireAble logo" />
        </div>

        <span class="auth-pill">Account Setup</span>
        <h2 class="form-h2">Choose Your Role</h2>
        <p class="form-p">Select how you would like to continue in the platform.</p>

        <button
          class="role-btn role-btn-applicant"
          :disabled="isFading"
          @click="goRegister('applicant')"
        >
          Continue as Applicant
        </button>

        <button
          class="role-btn role-btn-employer"
          :disabled="isFading"
          @click="goRegister('employer')"
        >
          Continue as Employer
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SelectRole",
  data() {
    return {
      isVisible: false,
      isFading: false,
    };
  },
  mounted() {
    localStorage.removeItem("selectedRole");
    setTimeout(() => {
      this.isVisible = true;
    }, 0);
  },
  methods: {
    goRegister(role) {
      if (this.isFading) return;
      localStorage.setItem("selectedRole", role);
      this.isFading = true;
      this.isVisible = false;

      setTimeout(() => {
        this.$router.push({
          path: "/register",
          query: { role, force: "1" },
        });
      }, 300);
    },
    goBack() {
      if (this.isFading) return;
      localStorage.removeItem("selectedRole");
      this.isFading = true;
      this.isVisible = false;

      setTimeout(() => {
        this.$router.push("/login");
      }, 300);
    },
  },
};
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
  padding: 42px 42px 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: rgba(255, 255, 255, 0.97);
  opacity: 0;
  visibility: hidden;
  transform: translateY(14px);
  transition: opacity 0.35s ease, transform 0.35s ease;
}

.is-visible {
  visibility: visible;
  opacity: 1;
  transform: translateY(0);
}

.is-fading {
  opacity: 0;
  visibility: hidden;
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
  font-size: clamp(1.65rem, 2.6vw, 2.05rem);
  line-height: 1.15;
}

.form-p {
  margin: 10px auto 22px;
  text-align: center;
  color: #4b5563;
  font-size: 0.87rem;
  line-height: 1.55;
  max-width: 360px;
}

.role-btn {
  width: 100%;
  min-height: 50px;
  border-radius: 12px;
  border: 1px solid #dbe4ee;
  background: #ffffff;
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.15px;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.role-btn + .role-btn {
  margin-top: 12px;
}

.role-btn:hover {
  transform: translateY(-1px);
  border-color: #b8c6d6;
  box-shadow: 0 10px 22px rgba(15, 79, 37, 0.12);
}

.role-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
  transform: none;
}

.role-btn-applicant {
  background: linear-gradient(135deg, #0f4f25 0%, #1f7a3f 100%) !important;
  border-color: transparent !important;
  color: #ffffff !important;
}

.role-btn-applicant:hover,
.role-btn-applicant:focus-visible {
  background: linear-gradient(135deg, #0d441f 0%, #1b6d39 100%) !important;
  color: #ffffff !important;
}

.role-btn-applicant:disabled {
  background: #d8e5dc !important;
  border-color: #c3d5c8 !important;
  color: #60706a !important;
}

.role-btn-employer {
  background: #f8fafc;
  color: #0f4f25;
  border-color: #cfe0d4;
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
    padding: 30px 22px 24px;
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

  .form-h2 {
    font-size: 1.5rem;
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
}
</style>
