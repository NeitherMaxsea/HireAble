<template>
  <header class="topbar" :class="{ 'topbar-dark': isDark }">
    <div class="breadcrumb">
      <span class="parent">{{ parent }}</span>
      <span v-if="child" class="separator">|</span>
      <span v-if="child" class="child">{{ child }}</span>
    </div>

    <div class="datetime">
      {{ formattedDate }} | {{ formattedTime }}
    </div>
  </header>
</template>

<script>
export default {
  name: "NavbarCompanyAdmin",

  data() {
    return {
      formattedDate: "",
      formattedTime: "",
      isDark: false,
      timeIntervalId: null,
      themeIntervalId: null
    }
  },

  computed: {
    parent() {
      const path = this.$route.path
      if (path.includes("/company-admin/add-employee")) return "Company Admin"
      if (path.includes("/company-admin/dashboard")) return "Company Admin"
      return "Company Admin"
    },

    child() {
      const path = this.$route.path
      if (path.includes("/company-admin/add-employee")) return "Add Employee"
      if (path.includes("/company-admin/dashboard")) return "Dashboard"
      return ""
    }
  },

  mounted() {
    this.updateDateTime()
    this.syncTheme()
    this.timeIntervalId = setInterval(this.updateDateTime, 1000)
    this.themeIntervalId = setInterval(this.syncTheme, 300)
  },

  beforeUnmount() {
    if (this.timeIntervalId) clearInterval(this.timeIntervalId)
    if (this.themeIntervalId) clearInterval(this.themeIntervalId)
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
.topbar {
  height: 56px;
  background-color: #ffffff;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.topbar,
.topbar * {
  transition: background-color 0.35s ease, color 0.35s ease, border-color 0.35s ease;
}

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

.topbar-dark {
  background-color: #020617;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
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
  color: rgba(255, 255, 255, 0.25);
}
</style>
