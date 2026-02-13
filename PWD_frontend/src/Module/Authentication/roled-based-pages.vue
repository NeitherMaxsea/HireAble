<template>
  <div>
    <Navbar />

    <div class="container">
      <!-- LEFT IMAGE (NO ANIMATION, NEVER TOUCHED) -->
      <div class="left">
        <img src="@/assets/PWD_choose.png" alt="Worker" class="worker" />
      </div>

      <!-- RIGHT SIDE -->
      <div
        class="right"
        :class="{ 
          'is-visible': isVisible,
          'is-fading': isFading
        }"
      >
        <!-- BACK BUTTON -->
        <button class="back-btn-outline" @click="goBack">
          ← Back to login
        </button>

        <div class="logo-container">
          <img src="@/assets/titlelogo.png" class="logo-img" />
        </div>

        <h2 class="form-h2">
          User Role Selection
          <p class="form-p">
            Choose how you would like to continue
          </p>
        </h2>

        <!-- ROLE OPTIONS -->
        <button
          class="role-btn"
          :disabled="isFading"
          @click="goRegister('applicant')"
        >
          Continue as Applicant
        </button>

        <button
          class="role-btn"
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
      isFading: false
    };
  },

  mounted() {
    // 🔒 clear any previous role (important)
    localStorage.removeItem("selectedRole");

    setTimeout(() => {
      this.isVisible = true;
    }, 0);
  },

  methods: {
    // 🔥 ROLE-AWARE REGISTER
    goRegister(role) {
      if (this.isFading) return;

      // SAVE ROLE
      localStorage.setItem("selectedRole", role);

      // FADE OUT
      this.isFading = true;
      this.isVisible = false;

      setTimeout(() => {
        this.$router.push({
          path: "/register",
          query: { role, force: "1" }
        });
      }, 300);
    },

    goBack() {
      if (this.isFading) return;

      // clear role when going back
      localStorage.removeItem("selectedRole");

      this.isFading = true;
      this.isVisible = false;

      setTimeout(() => {
        this.$router.push("/login");
      }, 300);
    }
  }
};
</script>

<style scoped>
/* ================= NO-BLINK FADE SYSTEM ================= */

/* BASE: completely invisible, not painted */
.right {
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease;
}

/* FADE IN */
.is-visible {
  visibility: visible;
  opacity: 1;
}

/* FADE OUT */
.is-fading {
  opacity: 0;
  visibility: hidden;
}

/* BACK BUTTON — UNCHANGED */
.back-btn-outline {
  position: absolute;
  top: 20px;
  left: 20px;

  padding: 8px 16px;
  font-size: 14px;

  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;

  color: #333;
  cursor: pointer;
  transition: 0.2s ease;
}

.back-btn-outline:hover {
  background: #f0f2f5;
}
</style>
