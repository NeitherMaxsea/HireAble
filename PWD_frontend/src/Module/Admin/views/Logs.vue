<template>
  <section class="logs-page">
    <header class="logs-header">
      <h2>System Logs</h2>
      <p>Realtime activity feed.</p>
    </header>

    <div class="filters">
      <label>
        <span>From</span>
        <input v-model="fromDate" type="date" />
      </label>
      <label>
        <span>To</span>
        <input v-model="toDate" type="date" />
      </label>
      <label>
        <span>Source</span>
        <select v-model="sourceFilter">
          <option value="all">All</option>
          <option value="users">Users</option>
          <option value="jobs">Jobs</option>
          <option value="applications">Applications</option>
        </select>
      </label>
      <button type="button" class="clear-btn" @click="clearFilters">Clear</button>
    </div>

    <div class="summary-grid">
      <div class="summary-card">
        <span>Total Events</span>
        <strong>{{ filteredEvents.length }}</strong>
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

      <div v-else-if="!filteredEvents.length" class="logs-empty">
        <i class="bi bi-journal-text"></i>
        <p>No logs match the current filters.</p>
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
            <tr v-for="event in visibleEvents" :key="event.id">
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

        <div v-if="canLoadMore" class="load-more-wrap">
          <button type="button" class="load-more" @click="visibleCount += PAGE_SIZE">Load more</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { collection, onSnapshot } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"

const loading = ref(true)
const loadError = ref("")
const userEventsLower = ref([])
const userEventsUpper = ref([])
const jobEvents = ref([])
const applicationEvents = ref([])
const unsubscribers = []
const fromDate = ref("")
const toDate = ref("")
const sourceFilter = ref("all")
const PAGE_SIZE = 50
const visibleCount = ref(PAGE_SIZE)

const userEvents = computed(() => {
  return [...userEventsLower.value, ...userEventsUpper.value]
})

const events = computed(() => {
  return [...userEvents.value, ...jobEvents.value, ...applicationEvents.value]
    .filter((item) => item.timestampMs > 0)
    .sort((a, b) => b.timestampMs - a.timestampMs)
})

const filteredEvents = computed(() => {
  const minMs = parseDateStart(fromDate.value)
  const maxMs = parseDateEnd(toDate.value)
  return events.value.filter((item) => {
    const sourceMatch = sourceFilter.value === "all" || item.sourceClass === sourceFilter.value
    const minMatch = !minMs || item.timestampMs >= minMs
    const maxMatch = !maxMs || item.timestampMs <= maxMs
    return sourceMatch && minMatch && maxMatch
  })
})

const visibleEvents = computed(() => filteredEvents.value.slice(0, visibleCount.value))
const canLoadMore = computed(() => visibleEvents.value.length < filteredEvents.value.length)
const userEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "users").length)
const jobEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "jobs").length)
const applicationEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "applications").length)

onMounted(() => {
  subscribeUsers("users")
  subscribeUsers("Users")
  subscribeJobs()
  subscribeApplications()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((unsub) => unsub && unsub())
})

watch([fromDate, toDate, sourceFilter], () => {
  visibleCount.value = PAGE_SIZE
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

function parseDateStart(value) {
  if (!value) return 0
  const parsed = new Date(`${value}T00:00:00`)
  return Number.isNaN(parsed.getTime()) ? 0 : parsed.getTime()
}

function parseDateEnd(value) {
  if (!value) return 0
  const parsed = new Date(`${value}T23:59:59.999`)
  return Number.isNaN(parsed.getTime()) ? 0 : parsed.getTime()
}

function clearFilters() {
  fromDate.value = ""
  toDate.value = ""
  sourceFilter.value = "all"
  visibleCount.value = PAGE_SIZE
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
  color: #0f766e;
  margin: 8px 0 14px;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
  align-items: end;
}

.filters label {
  display: grid;
  gap: 4px;
}

.filters span {
  font-size: 12px;
  color: #0f766e;
}

.filters input,
.filters select {
  border: 1px solid #cce7e3;
  background: #f6fcfb;
  color: #1f2937;
  border-radius: 8px;
  padding: 7px 10px;
  min-width: 150px;
}

.clear-btn {
  border: 1px solid #94d5cd;
  border-radius: 8px;
  background: #ecf9f7;
  color: #0f766e;
  font-weight: 600;
  padding: 8px 12px;
  cursor: pointer;
}

.summary-grid {
  margin-bottom: 14px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #d8ece9;
  border-radius: 10px;
  padding: 12px;
}

.summary-card span {
  color: #0f766e;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 4px;
  font-size: 20px;
  color: #0f3b39;
}

.logs-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px;
  border: 1px solid #d8ece9;
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
  color: #0d9488;
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
  color: #0f766e;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  background: #f2fbfa;
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
  background: #ccfbf1;
  color: #115e59;
}

.badge.source.jobs {
  background: #dcfce7;
  color: #166534;
}

.badge.source.applications {
  background: #ffedd5;
  color: #9a3412;
}

.badge.event {
  background: #e2e8f0;
  color: #334155;
}

.load-more-wrap {
  margin-top: 12px;
  display: flex;
  justify-content: center;
}

.load-more {
  border: 1px solid #8cd4cc;
  background: #ecf9f7;
  color: #0f766e;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 760px) {
  .filters input,
  .filters select {
    min-width: 130px;
  }
}
</style>


