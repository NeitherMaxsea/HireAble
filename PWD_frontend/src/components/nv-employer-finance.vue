<template>
  <header class="topbar">
    <div class="breadcrumb">
      <span class="parent">Finance</span>
      <span class="separator">|</span>
      <span class="child">{{ child }}</span>
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
              @click="openNotification"
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

      <div class="datetime">{{ formattedDate }} | {{ formattedTime }}</div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { db } from "@/lib/client-platform"
import { collection, onSnapshot, query, where } from "@/lib/laravel-data"

const route = useRoute()
const router = useRouter()
const formattedDate = ref("")
const formattedTime = ref("")
const isNotifOpen = ref(false)
const notifications = ref([])
const seenNotificationIds = ref([])
const notifUnsub = ref(null)
const financeIdentity = ref({ companyId: "", uid: "" })

let timer = null

const notificationStorageKey = computed(() => {
  const key = financeIdentity.value.companyId || financeIdentity.value.uid || "anonymous"
  return `finance_last_seen_notifications_${key}`
})

const latestNotifications = computed(() => notifications.value.slice(0, 5))
const unreadNotificationsCount = computed(() =>
  notifications.value.filter((item) => !hasSeenNotification(item.id)).length
)

const child = computed(() => {
  if (route.path.includes("/job-approval/")) return "Job Approval"
  return "Dashboard"
})

function updateDateTime() {
  const now = new Date()
  formattedDate.value = now.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  })
  formattedTime.value = now.toLocaleTimeString("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
}

function toMillis(value) {
  const raw = String(value || "").trim()
  if (!raw) return 0

  // Treat Laravel/MySQL naive datetime strings as UTC (backend commonly stores UTC).
  const sqlMatch = raw.match(
    /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/
  )
  if (sqlMatch) {
    const [, y, m, d, hh, mm, ss = "0"] = sqlMatch
    const utcMs = Date.UTC(
      Number(y),
      Number(m) - 1,
      Number(d),
      Number(hh),
      Number(mm),
      Number(ss)
    )
    return Number.isFinite(utcMs) ? utcMs : 0
  }

  const ms = new Date(raw).getTime()
  return Number.isFinite(ms) ? ms : 0
}

function formatRelativeTime(ms) {
  const value = Number(ms || 0)
  if (!value) return "Just now"
  const diff = Date.now() - value
  const minute = 60 * 1000
  const hour = minute * 60
  const day = hour * 24
  if (diff < minute) return "Just now"
  if (diff < hour) return `${Math.floor(diff / minute)}m ago`
  if (diff < day) return `${Math.floor(diff / hour)}h ago`
  return `${Math.floor(diff / day)}d ago`
}

function markNotificationsSeen() {
  if (!notifications.value.length) return
  const ids = notifications.value.map((item) => String(item.id || "").trim()).filter(Boolean)
  const merged = Array.from(new Set([...(seenNotificationIds.value || []), ...ids]))
  seenNotificationIds.value = merged
  localStorage.setItem(notificationStorageKey.value, JSON.stringify(merged))
}

function hasSeenNotification(id) {
  const key = String(id || "").trim()
  if (!key) return false
  return seenNotificationIds.value.includes(key)
}

function loadSeenNotificationIds() {
  try {
    const raw = localStorage.getItem(notificationStorageKey.value)
    const parsed = JSON.parse(raw || "[]")
    seenNotificationIds.value = Array.isArray(parsed)
      ? parsed.map((id) => String(id || "").trim()).filter(Boolean)
      : []
  } catch {
    seenNotificationIds.value = []
  }
}

function toggleNotifPanel() {
  isNotifOpen.value = !isNotifOpen.value
  if (isNotifOpen.value) markNotificationsSeen()
}

function openNotification() {
  isNotifOpen.value = false
  markNotificationsSeen()
  router.push("/employer/finance/job-approval")
}

function handleDocumentClick() {
  isNotifOpen.value = false
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

function buildNotificationTitle(raw) {
  const title = String(raw?.title || raw?.jobTitle || "Job post").trim()
  const financeStatus = normalizeFinanceStatus(raw)
  if (financeStatus === "pending") return `Job pending finance review`
  if (financeStatus === "approved") return `Job approved`
  if (financeStatus === "rejected") return `Job rejected`
  return `Job updated`
}

function buildSourceLabel(raw) {
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

  const financeStatus = normalizeFinanceStatus(raw)
  if (financeStatus === "pending") return "Human Resource Department"
  return "Finance Department"
}

function setupNotifications() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const uid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  financeIdentity.value = { companyId, uid }

  loadSeenNotificationIds()

  let target = null
  if (companyId) {
    target = query(collection(db, "jobs"), where("companyId", "==", companyId))
  } else if (uid) {
    target = query(collection(db, "jobs"), where("postedByUid", "==", uid))
  }

  if (!target) {
    notifications.value = []
    return
  }

  notifUnsub.value = onSnapshot(
    target,
    (snapshot) => {
      notifications.value = snapshot.docs
        .map((docSnap) => {
          const raw = docSnap.data ? docSnap.data() : {}
          const title = String(raw.title || "Job post").trim()
          const financeStatus = normalizeFinanceStatus(raw)
          const timestampMillis = toMillis(raw.updatedAt || raw.createdAt || raw.financeReviewedAt)
          const actorUid = pickActorUid(raw)
          const myUid = String(financeIdentity.value?.uid || "").trim()
          const isSelfTriggered = !!myUid && actorUid !== "" && actorUid === myUid

          // Finance should mainly be notified about jobs awaiting finance review.
          if (financeStatus !== "pending") return null
          if (isSelfTriggered) return null

          return {
            id: String(raw.id || docSnap.id || `notif-${Math.random().toString(36).slice(2)}`),
            title: `${buildNotificationTitle(raw)}: ${title}`,
            subtitle: `From: ${buildSourceLabel(raw)} • Finance status: ${financeStatus}`,
            timestampMillis,
          }
        })
        .filter(Boolean)
        .sort((a, b) => b.timestampMillis - a.timestampMillis)
    },
    () => {
      notifications.value = []
    }
  )
}

onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
  setupNotifications()
  document.addEventListener("click", handleDocumentClick)
})

onBeforeUnmount(() => {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
  if (typeof notifUnsub.value === "function") {
    notifUnsub.value()
    notifUnsub.value = null
  }
  document.removeEventListener("click", handleDocumentClick)
})
</script>

<style scoped>
.topbar {
  height: 56px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
}

.parent {
  color: #64748b;
  font-weight: 600;
}

.separator {
  color: #94a3b8;
}

.child {
  color: #0f172a;
  font-weight: 700;
}

.datetime {
  color: #334155;
  font-size: 13px;
  font-weight: 600;
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

@media (max-width: 720px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    height: auto;
    padding: 10px 14px;
  }

  .topbar-right {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
