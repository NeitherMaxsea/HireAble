<template>
  <header class="topbar" :class="{ 'topbar-dark': isDark }">
    <div class="breadcrumb">
      <span class="parent">{{ parent }}</span>
      <span v-if="child" class="separator">|</span>
      <span v-if="child" class="child">{{ child }}</span>
    </div>

    <div class="datetime">
      <div class="topbar-notif" ref="notifRef">
        <button
          class="notif-bell"
          :class="{ 'has-new': unreadNotificationsCount > 0 }"
          type="button"
          aria-label="Notifications"
          @click="toggleNotifications"
        >
          <i class="bi bi-bell-fill"></i>
          <span v-if="unreadNotificationsCount > 0" class="notif-count">
            {{ unreadNotificationsCount > 99 ? "99+" : unreadNotificationsCount }}
          </span>
        </button>

        <div v-if="isNotifOpen" class="topbar-notif-panel">
          <div class="topbar-notif-head">
            <h4>Notifications</h4>
          </div>

          <div class="topbar-notif-list-wrap">
            <div v-if="latestNotifications.length === 0" class="topbar-notif-empty">
              No notifications yet
            </div>

            <ul v-else class="topbar-notif-list">
              <li v-for="event in latestNotifications" :key="event.id" class="topbar-notif-item">
                <span class="topbar-notif-dot"></span>
                <div class="topbar-notif-copy">
                  <p class="topbar-notif-title">{{ event.title }}</p>
                  <p class="topbar-notif-time">{{ formatRelativeTime(event.timestampMillis) }}</p>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <span>{{ formattedDate }}</span>
      <span class="separator-inline">|</span>
      <span>{{ formattedTime }}</span>
    </div>
  </header>
</template>

<script>
import api from "@/services/api"

export default {
  name: "NavbarAdmin",

  data() {
    return {
      formattedDate: "",
      formattedTime: "",
      isDark: false,
      themeInterval: null,
      timeInterval: null,
      notifInterval: null,
      isNotifOpen: false,
      notifications: [],
      lastSeenMillis: 0,
      notificationStorageKey: "hr_last_seen_notifications",
    }
  },

  computed: {
    parent() {
      const path = this.$route.path

      if (path.includes("/job-management/")) return "Job Management"
      if (path.includes("/applicant-lists") || path.includes("/application-status-tracking")) return "Applicant Management"
      if (path.includes("/interview-management")) return "Interview Management"
      if (path.includes("/add-employee")) return "Add Employee"
      if (path.includes("/training-assessment/")) return "Training & Assessment"
      if (path.includes("/dashboard")) return "Dashboard"
      if (path.includes("/employee")) return "Employees"
      if (path.includes("/reports")) return "Reports"

      return "Dashboard"
    },

    child() {
      const path = this.$route.path

      if (path.includes("/job-management/job-list")) return "Job Listings"
      if (path.includes("/job-management/job-post")) return "Job Post"
      if (path.includes("/applicant-lists")) return "Applicant Lists"
      if (path.includes("/application-status-tracking")) return "Application Status"
      if (path.includes("/interview-management")) return "Interview Management"
      if (path.includes("/add-employee")) return "Add Employee"
      if (path.includes("/training-programs")) return "Training Programs"
      if (path.includes("/written-test-management")) return "Written Test Management"
      if (path.includes("/physical-evaluation")) return "Physical Evaluation"
      if (path.includes("/training-progress")) return "Training Progress"
      if (path.includes("/final-evaluation")) return "Final Evaluation"

      return ""
    },

    latestNotifications() {
      return this.notifications.slice(0, 6)
    },

    unreadNotificationsCount() {
      return this.notifications.filter((item) => item.timestampMillis > this.lastSeenMillis).length
    },
  },

  mounted() {
    const identity = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "anonymous").trim()
    this.notificationStorageKey = `hr_last_seen_notifications_${identity}`
    const cachedRaw = localStorage.getItem(this.notificationStorageKey)
    if (cachedRaw === null) {
      this.lastSeenMillis = Date.now()
      localStorage.setItem(this.notificationStorageKey, String(this.lastSeenMillis))
    } else {
      const cachedLastSeen = Number(cachedRaw || 0)
      this.lastSeenMillis = Number.isFinite(cachedLastSeen) ? cachedLastSeen : 0
    }

    this.updateDateTime()
    this.timeInterval = setInterval(this.updateDateTime, 1000)

    this.loadNotifications()
    this.notifInterval = setInterval(this.loadNotifications, 30000)

    this.syncTheme()
    this.themeInterval = setInterval(this.syncTheme, 300)
    document.addEventListener("click", this.handleClickOutside)
  },

  beforeUnmount() {
    if (this.timeInterval) clearInterval(this.timeInterval)
    if (this.themeInterval) clearInterval(this.themeInterval)
    if (this.notifInterval) clearInterval(this.notifInterval)
    document.removeEventListener("click", this.handleClickOutside)
  },

  methods: {
    async loadNotifications() {
      try {
        const companyId = String(localStorage.getItem("companyId") || "").trim()
        const postedByUid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
        const scope = companyId ? { companyId } : (postedByUid ? { postedByUid } : {})

        const [applicationsRes, jobsRes] = await Promise.all([
          api.get("/applications", { params: scope }),
          api.get("/jobs", { params: scope }),
        ])

        const applicationEvents = (Array.isArray(applicationsRes?.data) ? applicationsRes.data : []).map((row) => {
          const ts = this.toMillis(row?.createdAt || row?.appliedAt || row?.updatedAt)
          const applicant = String(row?.applicantName || "Applicant")
          const jobTitle = String(row?.jobTitle || "your job post")

          return {
            id: `application-${String(row?.id || Math.random().toString(36).slice(2))}`,
            title: `${applicant} applied for ${jobTitle}`,
            timestampMillis: ts || Date.now(),
          }
        })

        const financeEvents = (Array.isArray(jobsRes?.data) ? jobsRes.data : [])
          .filter((job) => {
            const status = String(job?.financeApprovalStatus || "").trim().toLowerCase()
            return status === "approved" || status === "rejected"
          })
          .map((job) => {
            const status = String(job?.financeApprovalStatus || "").trim().toLowerCase()
            const ts = this.toMillis(job?.financeReviewedAt || job?.updatedAt)
            const verb = status === "approved" ? "approved" : "rejected"

            return {
              id: `finance-${String(job?.id || Math.random().toString(36).slice(2))}-${status}`,
              title: `Finance ${verb} job post: ${String(job?.title || "Untitled Job")}`,
              timestampMillis: ts || Date.now(),
            }
          })

        this.notifications = [...applicationEvents, ...financeEvents]
          .sort((a, b) => b.timestampMillis - a.timestampMillis)
          .slice(0, 50)
      } catch {
        // Keep previous notifications on API errors.
      }
    },

    toggleNotifications() {
      this.isNotifOpen = !this.isNotifOpen
      if (this.isNotifOpen) this.markNotificationsAsSeen()
    },

    markNotificationsAsSeen() {
      const latest = this.notifications[0]?.timestampMillis || Date.now()
      this.lastSeenMillis = latest
      localStorage.setItem(this.notificationStorageKey, String(latest))
    },

    handleClickOutside(event) {
      const container = this.$refs.notifRef
      if (!container) return
      if (!container.contains(event.target)) {
        this.isNotifOpen = false
      }
    },

    toMillis(value) {
      if (!value) return 0
      if (typeof value === "number" && Number.isFinite(value)) return value

      const text = String(value).trim()
      if (!text) return 0

      const direct = new Date(text)
      if (!Number.isNaN(direct.getTime())) return direct.getTime()

      const mysqlLike = new Date(text.replace(" ", "T"))
      if (!Number.isNaN(mysqlLike.getTime())) return mysqlLike.getTime()

      return 0
    },

    formatRelativeTime(timestampMillis) {
      const ts = Number(timestampMillis || 0)
      if (!ts) return ""

      const diffMs = Date.now() - ts
      const minute = 60 * 1000
      const hour = 60 * minute
      const day = 24 * hour

      if (diffMs < minute) return "Now"
      if (diffMs < hour) return `${Math.max(1, Math.floor(diffMs / minute))}m ago`
      if (diffMs < day) return `${Math.max(1, Math.floor(diffMs / hour))}h ago`

      return new Date(ts).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      })
    },

    syncTheme() {
      this.isDark = localStorage.getItem("sidebarDark") === "true"
    },

    updateDateTime() {
      const now = new Date()

      this.formattedDate = now.toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      })

      this.formattedTime = now.toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      })
    },
  },
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
  transition:
    background-color 0.35s ease,
    color 0.35s ease,
    border-color 0.35s ease;
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
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #000000;
  font-weight: bold;
  letter-spacing: 0.3px;
  position: relative;
}

.separator-inline {
  color: rgba(0, 0, 0, 0.4);
}

.topbar-notif {
  position: relative;
}

.notif-bell {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #dbe2ea;
  background: #ffffff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
}

.notif-bell i {
  font-size: 15px;
}

.notif-bell.has-new {
  border-color: #f59e0b;
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
}

.notif-count {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 17px;
  height: 17px;
  border-radius: 999px;
  padding: 0 4px;
  background: #dc2626;
  color: #ffffff;
  font-size: 10px;
  font-weight: 700;
  line-height: 17px;
  text-align: center;
}

.topbar-notif-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: min(430px, 88vw);
  background: #f7f7f7;
  border: 1px solid #d6d6d6;
  border-radius: 8px;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
  z-index: 70;
  max-height: 420px;
  overflow: hidden;
}

.topbar-notif-head {
  padding: 12px 14px;
  border-bottom: 1px solid #dddddd;
}

.topbar-notif-head h4 {
  margin: 0;
  font-size: 18px;
  color: #2e2e2e;
  font-weight: 700;
}

.topbar-notif-list-wrap {
  max-height: 340px;
  overflow-y: auto;
}

.topbar-notif-empty {
  padding: 14px;
  color: #8b8b8b;
  font-size: 13px;
}

.topbar-notif-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.topbar-notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px;
  border-bottom: 1px solid #e6e6e6;
  background: #f7f7f7;
}

.topbar-notif-item:last-child {
  border-bottom: none;
}

.topbar-notif-dot {
  width: 9px;
  height: 9px;
  border-radius: 999px;
  background: #3b82f6;
  margin-top: 6px;
  flex: 0 0 9px;
}

.topbar-notif-copy {
  min-width: 0;
}

.topbar-notif-title {
  margin: 0;
  color: #2f2f2f;
  font-size: 14px;
  line-height: 1.35;
}

.topbar-notif-time {
  margin: 5px 0 0;
  color: #9ca3af;
  font-size: 12px;
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

.topbar-dark .separator,
.topbar-dark .separator-inline {
  color: rgba(255, 255, 255, 0.25);
}

.topbar-dark .notif-bell {
  background: #0f172a;
  color: #e2e8f0;
  border-color: #334155;
}

.topbar-dark .topbar-notif-panel {
  background: #020617;
  border-color: #1e293b;
}

.topbar-dark .topbar-notif-head {
  border-color: #1e293b;
}

.topbar-dark .topbar-notif-head h4,
.topbar-dark .topbar-notif-title {
  color: #e2e8f0;
}

.topbar-dark .topbar-notif-item {
  background: #020617;
  border-color: #1e293b;
}

.topbar-dark .topbar-notif-empty,
.topbar-dark .topbar-notif-time {
  color: #94a3b8;
}

@media (max-width: 768px) {
  .topbar {
    padding: 0 14px;
  }

  .datetime {
    font-size: 12px;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
    text-align: right;
  }
}
</style>
