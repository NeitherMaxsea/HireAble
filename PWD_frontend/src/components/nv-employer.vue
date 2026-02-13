<template>
  <header class="topbar" :class="{ 'topbar-dark': isDark }">

    <!-- LEFT: BREADCRUMB -->
    <div class="breadcrumb">
      <span class="parent">{{ parent }}</span>
      <span v-if="child" class="separator">|</span>
      <span v-if="child" class="child">{{ child }}</span>
    </div>

    <!-- RIGHT: DATE & TIME -->
    <div class="datetime">
      {{ formattedDate }} | {{ formattedTime }}
    </div>

  </header>
</template>

<script>
export default {
  name: "NavbarAdmin",

  data() {
    return {
      formattedDate: "",
      formattedTime: "",
      isDark: false,
      themeInterval: null
    }
  },

  computed: {
    parent() {
      const path = this.$route.path

      if (path.includes("/job-management/")) return "Job Management"
      if (path.includes("/dashboard")) return "Dashboard"
      if (path.includes("/applicant")) return "Applicants"
      if (path.includes("/employee")) return "Employees"
      if (path.includes("/reports")) return "Reports"

      return "Dashboard"
    },

    child() {
      const path = this.$route.path

      if (path.includes("/job-management/job-list")) return "Job Listings"
      if (path.includes("/job-management/job-post")) return "Job Post"

      return ""
    }
  },

  mounted() {
    this.updateDateTime()
    setInterval(this.updateDateTime, 1000)

    // ✅ INITIAL LOAD
    this.syncTheme()

    // ✅ KEEP CHECKING THEME
    this.themeInterval = setInterval(this.syncTheme, 300)
  },

  beforeUnmount() {
    clearInterval(this.themeInterval)
  },

  methods: {
    syncTheme() {
      this.isDark = localStorage.getItem("sidebarDark") === "true"
    },

    updateDateTime() {
      const now = new Date()

      this.formattedDate = now.toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
      })

      this.formattedTime = now.toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
      })
    }
  }
}
</script>

<style scoped>
/* ================= LIGHT MODE ================= */

.topbar {
  height: 56px;
  background-color: #ffffff;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

/* SMOOTH DARK MODE TRANSITION */
.topbar,
.topbar * {
  transition:
    background-color 0.35s ease,
    color 0.35s ease,
    border-color 0.35s ease;
}


/* BREADCRUMB */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
}

.parent {
  color: #000000;
  font-weight: 500;
}

.separator {
  color: rgba(0, 0, 0, 0.4);
  font-size: 18px;
}

.child {
  color: #000000;
  font-weight: 600;
}

.datetime {
  font-size: 13px;
  color: #000000;
  font-weight: bold;
  letter-spacing: 0.3px;
}

/* ================= DARK MODE ================= */

.topbar-dark {
  background-color: #020617;
  box-shadow: 0 1px 3px rgba(0,0,0,0.4);
}

.topbar-dark .parent {
  color: #94a3b8;
}

.topbar-dark .child {
  color: #f8fafc;
}

.topbar-dark .datetime {
  color: #cbd5f5;
}

.topbar-dark .separator {
  color: rgba(255,255,255,0.25);
}
</style>
