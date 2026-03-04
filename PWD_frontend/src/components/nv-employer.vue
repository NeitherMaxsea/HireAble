<template>
  <header class="topbar" :class="{ 'topbar-dark': isDark }">
    <div class="breadcrumb">
      <span class="parent">{{ parent }}</span>
      <span v-if="child" class="separator">|</span>
      <span v-if="child" class="child">{{ child }}</span>
    </div>

    <div class="topbar-right">
      <div class="topbar-notif" @click.stop>
        <button
          type="button"
          class="notif-bell"
          :class="{ 'has-new': unreadNotificationsCount > 0 }"
          @click="toggleNotifPanel"
          aria-label="Open notifications"
          :aria-expanded="isNotifOpen"
        >
          <i class="bi bi-bell-fill"></i>
          <span v-if="unreadNotificationsCount > 0" class="notif-count">
            {{ unreadNotificationsCount > 9 ? "9+" : unreadNotificationsCount }}
          </span>
        </button>

        <div v-if="isNotifOpen" class="topbar-notif-panel">
          <div class="topbar-notif-head">
            <h4>Notifications</h4>
            <small>{{ latestNotifications.length }} latest</small>
          </div>

          <div v-if="latestNotifications.length === 0" class="topbar-notif-empty">
            No notifications yet
          </div>

          <ul v-else class="topbar-notif-list">
            <li
              v-for="item in latestNotifications"
              :key="item.id"
              class="topbar-notif-item"
              @click="openNotification(item)"
            >
              <span class="topbar-notif-dot"></span>
              <div class="topbar-notif-copy">
                <p class="topbar-notif-title">{{ item.title }}</p>
                <p class="topbar-notif-time">{{ item.subtitle }}</p>
                <p class="topbar-notif-time">{{ formatRelativeTime(item.timestampMillis) }}</p>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="datetime">
        <span>{{ formattedDate }}</span>
        <span class="separator-inline">|</span>
        <span>{{ formattedTime }}</span>
      </div>
    </div>
  </header>
</template>

<script>
import { db } from "@/lib/client-platform"
import { collection, onSnapshot, query, where } from "@/lib/laravel-data"

function toMillis(value) {
  if (!value) return 0
  const raw = String(value).trim()
  if (!raw) return 0

  // Treat Laravel/MySQL naive datetime strings as UTC (backend commonly stores UTC).
  const sqlMatch = raw.match(
    /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/
  )
  if (sqlMatch) {
    const [, y, m, d, hh, mm, ss = "0"] = sqlMatch
    const ms = Date.UTC(
      Number(y),
      Number(m) - 1,
      Number(d),
      Number(hh),
      Number(mm),
      Number(ss)
    )
    return Number.isFinite(ms) ? ms : 0
  }

  const ms = new Date(raw).getTime()
  return Number.isFinite(ms) ? ms : 0
}

function normalizeStatus(value) {
  const text = String(value || "pending").trim().toLowerCase()
  if (!text) return "pending"
  return text
}

function normalizeFinanceStatus(raw) {
  const financeStatus = String(raw?.financeApprovalStatus || raw?.finance_approval_status || "").trim().toLowerCase()
  const generalStatus = String(raw?.status || "").trim().toLowerCase()
  if (generalStatus === "open" || generalStatus === "closed") return "approved"
  if (generalStatus === "finance_rejected") return "rejected"
  if (financeStatus) return financeStatus
  if (generalStatus.includes("pending")) return "pending"
  return generalStatus || "updated"
}

function buildJobSourceLabel(raw, financeStatus) {
  const explicit =
    raw?.sourceDepartment ||
    raw?.source_department ||
    raw?.updatedByDepartment ||
    raw?.updated_by_department ||
    raw?.createdByDepartment ||
    raw?.created_by_department ||
    raw?.postedByDepartment ||
    raw?.posted_by_department

  const explicitText = String(explicit || "").trim()
  if (explicitText) return explicitText

  if (financeStatus === "approved" || financeStatus === "rejected") {
    return "Finance Department"
  }
  return "Human Resource Department"
}

function pickActorUid(raw) {
  return String(
    raw?.financeReviewedByUid ||
    raw?.finance_reviewed_by_uid ||
    raw?.updatedByUid ||
    raw?.updated_by_uid ||
    raw?.reviewedByUid ||
    raw?.reviewed_by_uid ||
    raw?.createdByUid ||
    raw?.created_by_uid ||
    raw?.postedByUid ||
    raw?.posted_by_uid ||
    ""
  ).trim()
}

function pickFinanceDecisionActorUid(raw) {
  return String(
    raw?.financeReviewedByUid ||
    raw?.finance_reviewed_by_uid ||
    raw?.updatedByUid ||
    raw?.updated_by_uid ||
    raw?.reviewedByUid ||
    raw?.reviewed_by_uid ||
    ""
  ).trim()
}

export default {
  name: "NavbarAdmin",

  data() {
    return {
      formattedDate: "",
      formattedTime: "",
      isDark: false,
      themeInterval: null,
      timeInterval: null,
      isNotifOpen: false,
      applicationNotifications: [],
      jobNotifications: [],
      applicationNotificationsUnsubscribe: null,
      jobNotificationsUnsubscribe: null,
      seenNotificationIds: [],
      hrIdentity: {
        companyId: "",
        uid: "",
      },
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
      if (path.includes("/interview-management/scheduling")) return "Interview Scheduling"
      if (path.includes("/interview-management/schedule-status")) return "Schedule Status"
      if (path.includes("/interview-management")) return "Interview Scheduling"
      if (path.includes("/add-employee")) return "Add Employee"
      if (path.includes("/training-programs")) return "Training Programs"
      if (path.includes("/written-test-management")) return "Written Test Management"
      if (path.includes("/physical-evaluation")) return "Physical Evaluation"
      if (path.includes("/training-progress")) return "Training Progress"
      if (path.includes("/final-evaluation")) return "Final Evaluation"

      return ""
    },
    notificationStorageKey() {
      const key = this.hrIdentity.companyId || this.hrIdentity.uid || "anonymous"
      return `hr_last_seen_notifications_${key}`
    },
    notifications() {
      return [...this.applicationNotifications, ...this.jobNotifications]
        .sort((a, b) => b.timestampMillis - a.timestampMillis)
    },
    latestNotifications() {
      return this.notifications.slice(0, 5)
    },
    unreadNotificationsCount() {
      return this.notifications.filter((item) => !this.hasSeenNotification(item.id)).length
    },
  },

  mounted() {
    this.updateDateTime()
    this.timeInterval = setInterval(this.updateDateTime, 1000)

    this.syncTheme()
    this.themeInterval = setInterval(this.syncTheme, 300)
    this.setupNotifications()
    document.addEventListener("click", this.handleDocumentClick)
  },

  beforeUnmount() {
    if (this.timeInterval) clearInterval(this.timeInterval)
    if (this.themeInterval) clearInterval(this.themeInterval)
    if (typeof this.applicationNotificationsUnsubscribe === "function") {
      this.applicationNotificationsUnsubscribe()
      this.applicationNotificationsUnsubscribe = null
    }
    if (typeof this.jobNotificationsUnsubscribe === "function") {
      this.jobNotificationsUnsubscribe()
      this.jobNotificationsUnsubscribe = null
    }
    document.removeEventListener("click", this.handleDocumentClick)
  },

  methods: {
    handleDocumentClick() {
      this.isNotifOpen = false
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
    setupNotifications() {
      const companyId = String(localStorage.getItem("companyId") || "").trim()
      const uid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
      this.hrIdentity = { companyId, uid }

      this.loadSeenNotificationIds()

      let applicationsTarget = null
      let jobsTarget = null
      if (companyId) {
        applicationsTarget = query(collection(db, "applications"), where("companyId", "==", companyId))
        jobsTarget = query(collection(db, "jobs"), where("companyId", "==", companyId))
      } else if (uid) {
        applicationsTarget = query(collection(db, "applications"), where("postedByUid", "==", uid))
        jobsTarget = query(collection(db, "jobs"), where("postedByUid", "==", uid))
      }

      if (!applicationsTarget && !jobsTarget) {
        this.applicationNotifications = []
        this.jobNotifications = []
        return
      }

      if (applicationsTarget) {
        this.applicationNotificationsUnsubscribe = onSnapshot(
          applicationsTarget,
          (snapshot) => {
            const rows = snapshot.docs.map((docSnap) => {
              const raw = docSnap.data ? docSnap.data() : {}
              const timestampMillis = toMillis(raw.updatedAt || raw.createdAt || raw.appliedAt)
              const applicantName = String(raw.applicantName || "Applicant").trim()
              const jobTitle = String(raw.jobTitle || "Job").trim()
              const status = normalizeStatus(raw.status)

              return {
                id: `app-${String(raw.id || docSnap.id || Math.random().toString(36).slice(2))}`,
                title: status === "pending"
                  ? `New application from ${applicantName}`
                  : `${applicantName} application ${status}`,
                subtitle: jobTitle,
                status,
                route: "/employer/HR/applicant-lists",
                timestampMillis,
              }
            })

            this.applicationNotifications = rows.sort((a, b) => b.timestampMillis - a.timestampMillis)
          },
          () => {
            this.applicationNotifications = []
          }
        )
      }

      if (jobsTarget) {
        this.jobNotificationsUnsubscribe = onSnapshot(
          jobsTarget,
          (snapshot) => {
            const rows = snapshot.docs.map((docSnap) => {
              const raw = docSnap.data ? docSnap.data() : {}
              const financeStatus = normalizeFinanceStatus(raw)
              const title = String(raw.title || raw.jobTitle || "Job post").trim()
              const timestampMillis = toMillis(raw.updatedAt || raw.financeReviewedAt || raw.createdAt)
              const financeReviewedAt = toMillis(raw.financeReviewedAt)
              const actorUid = pickActorUid(raw)
              const financeActorUid = pickFinanceDecisionActorUid(raw)
              const hrUid = String(this.hrIdentity?.uid || "").trim()
              const isSelfTriggered = !!hrUid && actorUid !== "" && actorUid === hrUid
              const isSelfFinanceDecision = !!hrUid && financeActorUid !== "" && financeActorUid === hrUid
              const shouldHighlightFinance =
                financeStatus === "approved" || financeStatus === "rejected" || financeStatus === "pending"

              // HR should primarily see finance decisions, not its own newly-created pending job posts.
              if (financeStatus === "pending") {
                return null
              }
              if ((financeStatus === "approved" || financeStatus === "rejected") && isSelfFinanceDecision) {
                return null
              }
              if (financeStatus !== "approved" && financeStatus !== "rejected" && isSelfTriggered) {
                return null
              }

              return {
                id: `job-${String(raw.id || docSnap.id || Math.random().toString(36).slice(2))}`,
                title: shouldHighlightFinance
                  ? `Finance ${financeStatus}: ${title}`
                  : `Job updated: ${title}`,
                subtitle: `From: ${buildJobSourceLabel(raw, financeStatus)} • Finance status: ${financeStatus}`,
                route: "/employer/HR/job-management/job-list",
                timestampMillis: financeReviewedAt || timestampMillis,
              }
            }).filter(Boolean)

            this.jobNotifications = rows.sort((a, b) => b.timestampMillis - a.timestampMillis)
          },
          () => {
            this.jobNotifications = []
          }
        )
      }
    },
    loadSeenNotificationIds() {
      try {
        const raw = localStorage.getItem(this.notificationStorageKey)
        const parsed = JSON.parse(raw || "[]")
        this.seenNotificationIds = Array.isArray(parsed)
          ? parsed.map((id) => String(id || "").trim()).filter(Boolean)
          : []
      } catch {
        this.seenNotificationIds = []
      }
    },
    hasSeenNotification(id) {
      const key = String(id || "").trim()
      if (!key) return false
      return this.seenNotificationIds.includes(key)
    },
    markNotificationsSeen() {
      if (!this.notifications.length) return
      const ids = this.notifications
        .map((item) => String(item.id || "").trim())
        .filter(Boolean)
      const merged = Array.from(new Set([...(this.seenNotificationIds || []), ...ids]))
      this.seenNotificationIds = merged
      localStorage.setItem(this.notificationStorageKey, JSON.stringify(merged))
    },
    toggleNotifPanel() {
      this.isNotifOpen = !this.isNotifOpen
      if (this.isNotifOpen) this.markNotificationsSeen()
    },
    openNotification(item) {
      this.isNotifOpen = false
      this.markNotificationsSeen()
      this.$router.push(item?.route || "/employer/HR/applicant-lists")
    },
    formatRelativeTime(ms) {
      const value = Number(ms || 0)
      if (!value) return "Just now"
      const diff = Date.now() - value
      const minute = 60 * 1000
      const hour = 60 * minute
      const day = 24 * hour
      if (diff < minute) return "Just now"
      if (diff < hour) return `${Math.floor(diff / minute)}m ago`
      if (diff < day) return `${Math.floor(diff / hour)}h ago`
      return `${Math.floor(diff / day)}d ago`
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

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
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

.topbar-notif {
  position: relative;
}

.notif-bell {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #0f172a;
  display: grid;
  place-items: center;
  position: relative;
  cursor: pointer;
}

.notif-bell.has-new {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  border-color: #bfdbfe;
}

.notif-bell i {
  font-size: 14px;
}

.notif-count {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  border: 2px solid #fff;
  font-size: 10px;
  font-weight: 700;
  display: grid;
  place-items: center;
  padding: 0 4px;
}

.topbar-notif-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: min(340px, 82vw);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 20px 32px rgba(15, 23, 42, 0.14);
  overflow: hidden;
  z-index: 30;
}

.topbar-notif-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.topbar-notif-head h4 {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
}

.topbar-notif-head small {
  color: #64748b;
  font-size: 11px;
}

.topbar-notif-empty {
  padding: 14px 12px;
  color: #64748b;
  font-size: 12px;
}

.topbar-notif-list {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 320px;
  overflow-y: auto;
}

.topbar-notif-item {
  display: grid;
  grid-template-columns: 10px 1fr;
  gap: 10px;
  align-items: start;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
}

.topbar-notif-item:hover {
  background: #f8fafc;
}

.topbar-notif-dot {
  width: 8px;
  height: 8px;
  margin-top: 5px;
  border-radius: 999px;
  background: #2563eb;
}

.topbar-notif-copy p {
  margin: 0;
}

.topbar-notif-title {
  color: #0f172a;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.35;
}

.topbar-notif-time {
  margin-top: 2px !important;
  color: #64748b;
  font-size: 11px;
}

.separator-inline {
  color: rgba(0, 0, 0, 0.4);
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

.topbar-dark .notif-bell {
  background: #0f172a;
  border-color: #334155;
  color: #e2e8f0;
}

.topbar-dark .notif-count {
  border-color: #020617;
}

.topbar-dark .topbar-notif-panel {
  background: #0f172a;
  border-color: #334155;
}

.topbar-dark .topbar-notif-head {
  border-color: #1e293b;
}

.topbar-dark .topbar-notif-head h4,
.topbar-dark .topbar-notif-title {
  color: #f8fafc;
}

.topbar-dark .topbar-notif-head small,
.topbar-dark .topbar-notif-empty,
.topbar-dark .topbar-notif-time {
  color: #94a3b8;
}

.topbar-dark .topbar-notif-item {
  border-color: #1e293b;
}

.topbar-dark .topbar-notif-item:hover {
  background: #111827;
}

.topbar-dark .separator,
.topbar-dark .separator-inline {
  color: rgba(255, 255, 255, 0.25);
}

@media (max-width: 768px) {
  .topbar {
    padding: 0 14px;
  }

  .topbar-right {
    gap: 8px;
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
