<template>
  <section class="logs-page">
    <header class="logs-header">
      <h2>System Logs</h2>
      <p>Realtime activity from users, jobs, and applications.</p>
    </header>

    <div class="summary-grid">
      <div class="summary-card">
        <span>Total Events</span>
        <strong>{{ events.length }}</strong>
      </div>
      <div class="summary-card">
        <span>User Events</span>
        <strong>{{ userEventCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Job Events</span>
        <strong>{{ jobEventCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Application Events</span>
        <strong>{{ applicationEventCount }}</strong>
      </div>
    </div>

    <div class="logs-card">
      <div v-if="loading" class="logs-empty">
        <i class="bi bi-hourglass-split"></i>
        <p>Loading logs...</p>
      </div>

      <div v-else-if="loadError && !events.length" class="logs-empty">
        <i class="bi bi-exclamation-triangle"></i>
        <p>{{ loadError }}</p>
      </div>

      <div v-else-if="!events.length" class="logs-empty">
        <i class="bi bi-journal-text"></i>
        <p>No log entries found in Laravel.</p>
      </div>

      <div v-else class="logs-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Source</th>
              <th>Event</th>
              <th>Account</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in events" :key="event.id">
              <td class="time">{{ formatDateTime(event.timestampMs) }}</td>
              <td>
                <span class="badge source" :class="event.sourceClass">{{ event.sourceLabel }}</span>
              </td>
              <td>
                <span class="badge event">{{ event.eventLabel }}</span>
              </td>
              <td class="account">{{ event.accountLabel }}</td>
              <td class="details">{{ event.details }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { collection, onSnapshot } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"

const loading = ref(true)
const loadError = ref("")
const userEventsLower = ref([])
const userEventsUpper = ref([])
const jobEvents = ref([])
const applicationEvents = ref([])
const unsubscribers = []

const userEvents = computed(() => {
  return [...userEventsLower.value, ...userEventsUpper.value]
})

const events = computed(() => {
  return [...userEvents.value, ...jobEvents.value, ...applicationEvents.value]
    .filter((item) => item.timestampMs > 0)
    .sort((a, b) => b.timestampMs - a.timestampMs)
    .slice(0, 200)
})

const userEventCount = computed(() => userEvents.value.length)
const jobEventCount = computed(() => jobEvents.value.length)
const applicationEventCount = computed(() => applicationEvents.value.length)

onMounted(() => {
  subscribeUsers("users")
  subscribeUsers("Users")
  subscribeJobs()
  subscribeApplications()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((unsub) => unsub && unsub())
})

function subscribeUsers(collectionName) {
  const unsub = onSnapshot(
    collection(db, collectionName),
    (snapshot) => {
      const list = snapshot.docs.flatMap((docSnap) => {
        const data = docSnap.data() || {}
        const name = data.username || data.name || data.email || "Unknown user"
        const role = String(data.role || "user")
        const createdAtMs = toMillis(data.createdAt)
        const loginAtMs = toMillis(data.lastLoginAt)
        const items = []

        if (createdAtMs) {
          items.push({
            id: `user-created-${docSnap.id}`,
            timestampMs: createdAtMs,
            sourceLabel: "Users",
            sourceClass: "users",
            eventLabel: "User Created",
            accountLabel: data.email || docSnap.id,
            details: `${name} (${role}) account created`
          })
        }
        if (loginAtMs) {
          items.push({
            id: `user-login-${docSnap.id}-${loginAtMs}`,
            timestampMs: loginAtMs,
            sourceLabel: "Users",
            sourceClass: "users",
            eventLabel: "User Login",
            accountLabel: data.email || docSnap.id,
            details: `${name} (${role}) signed in`
          })
        }
        return items
      })

      if (collectionName === "Users") {
        userEventsUpper.value = list
      } else {
        userEventsLower.value = list
      }

      loading.value = false
      loadError.value = ""
    },
    () => {
      if (!userEvents.value.length && !jobEvents.value.length && !applicationEvents.value.length) {
        loadError.value = "Cannot read logs. Check Laravel Rules/admin access."
      }
      loading.value = false
    }
  )

  unsubscribers.push(unsub)
}

function subscribeJobs() {
  const unsub = onSnapshot(
    collection(db, "jobs"),
    (snapshot) => {
      jobEvents.value = snapshot.docs
        .map((docSnap) => {
          const data = docSnap.data() || {}
          const createdAtMs = toMillis(data.createdAt)
          return {
            id: `job-created-${docSnap.id}`,
            timestampMs: createdAtMs,
            sourceLabel: "Jobs",
            sourceClass: "jobs",
            eventLabel: "Job Posted",
            accountLabel: resolveJobAccount(data),
            details: `${data.title || "Untitled job"} (${data.location || "No location"})`
          }
        })
        .filter((item) => item.timestampMs > 0)
      loading.value = false
    },
    () => {
      loading.value = false
    }
  )

  unsubscribers.push(unsub)
}

function subscribeApplications() {
  const unsub = onSnapshot(
    collection(db, "applications"),
    (snapshot) => {
      applicationEvents.value = snapshot.docs
        .map((docSnap) => {
          const data = docSnap.data() || {}
          const appliedAtMs = toMillis(data.appliedAt)
          return {
            id: `application-${docSnap.id}`,
            timestampMs: appliedAtMs,
            sourceLabel: "Applications",
            sourceClass: "applications",
            eventLabel: "Application Submitted",
            accountLabel: resolveApplicationAccount(data),
            details: `${data.jobTitle || "Unknown job"} (${String(data.status || "pending")})`
          }
        })
        .filter((item) => item.timestampMs > 0)
      loading.value = false
    },
    () => {
      loading.value = false
    }
  )

  unsubscribers.push(unsub)
}

function toMillis(value) {
  if (!value) return 0
  if (typeof value?.toMillis === "function") return value.toMillis()
  if (typeof value === "number") return value
  const parsed = Date.parse(value)
  return Number.isNaN(parsed) ? 0 : parsed
}

function resolveJobAccount(data) {
  const direct =
    firstNonEmpty(
      data.postedByEmail,
      data.postedByName,
      data.postedByUid,
      data.createdByEmail,
      data.createdByName,
      data.createdByUid,
      data.ownerEmail,
      data.ownerName,
      data.ownerUid,
      data.userEmail,
      data.userName,
      data.userUid,
      data.email,
      data.username,
      data.uid
    )
  if (direct) return direct

  const org = firstNonEmpty(data.company, data.department, data.organization)
  if (org) return `Legacy post (${org})`

  return "Legacy post (no account saved)"
}

function resolveApplicationAccount(data) {
  return (
    firstNonEmpty(
      data.applicantEmail,
      data.applicantName,
      data.applicantUid,
      data.userEmail,
      data.userName,
      data.userUid,
      data.email,
      data.username,
      data.uid
    ) || "Legacy entry"
  )
}

function firstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value || "").trim()
    if (text) return text
  }
  return ""
}

function formatDateTime(ms) {
  return new Date(ms).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  })
}
</script>

<style scoped>
.logs-page {
  padding: 10px;
}

.logs-header h2 {
  margin: 0;
}

.logs-header p {
  color: #6b7280;
  margin: 8px 0 20px;
}

.summary-grid {
  margin-bottom: 14px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
}

.summary-card span {
  color: #64748b;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 4px;
  font-size: 20px;
  color: #0f172a;
}

.logs-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
}

.logs-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #6b7280;
  min-height: 180px;
  text-align: center;
}

.logs-empty i {
  font-size: 2rem;
  color: #0d6efd;
}

.logs-empty p {
  margin: 0;
}

.logs-table-wrap {
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

th,
td {
  text-align: left;
  padding: 12px 10px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
}

th {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  background: #f8fafc;
}

.time {
  white-space: nowrap;
  color: #334155;
}

.details {
  color: #0f172a;
}

.account {
  color: #334155;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.badge.source.users {
  background: #dbeafe;
  color: #1d4ed8;
}

.badge.source.jobs {
  background: #dcfce7;
  color: #166534;
}

.badge.source.applications {
  background: #fff7ed;
  color: #9a3412;
}

.badge.event {
  background: #e2e8f0;
  color: #334155;
}
</style>


