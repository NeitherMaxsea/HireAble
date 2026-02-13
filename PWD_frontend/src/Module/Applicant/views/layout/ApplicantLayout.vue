<template>
  <div class="layout">
    <SidebarApplicant
      :mobile-open="isMobileSidebarOpen"
      @close-mobile="closeMobileSidebar"
    />

    <div
      v-if="isMobileSidebarOpen"
      class="sidebar-overlay"
      @click="closeMobileSidebar"
    ></div>

    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" @click="toggleMobileSidebar" aria-label="Open sidebar">
            <i class="bi bi-list"></i>
          </button>
          <h2 class="page-title">Applicant Portal</h2>
          <p class="page-subtitle">Find jobs and track your applications</p>
        </div>

        <div class="datetime">
          <span>{{ formattedDate }}</span>
          <span class="dot">|</span>
          <span>{{ formattedTime }}</span>
        </div>
      </header>

      <div class="main-body">
        <main class="content">
          <router-view />
        </main>

        <aside class="right-panel">
          <div class="widget-stack">
            <div class="calendar-card">
              <div class="calendar-head">
                <button class="nav-btn" @click="goPrevMonth" aria-label="Previous month">
                  &lt;
                </button>
                <h3>{{ monthYearLabel }}</h3>
                <button class="nav-btn" @click="goNextMonth" aria-label="Next month">
                  &gt;
                </button>
              </div>

              <div class="weekdays">
                <span v-for="day in weekdays" :key="day">{{ day }}</span>
              </div>

              <div class="days-grid">
                <button
                  v-for="(day, idx) in calendarDays"
                  :key="`${day.dateKey}-${idx}`"
                  type="button"
                  :class="[
                    'day-cell',
                    { muted: !day.currentMonth },
                    { today: day.isToday },
                    { selected: day.isSelected },
                    { 'has-event': day.eventCount > 0 }
                  ]"
                  @click="selectDate(day.dateObj)"
                >
                  <span>{{ day.dayNumber }}</span>
                  <span v-if="day.eventCount > 0" class="event-dot"></span>
                </button>
              </div>
            </div>

            <div class="events-card">
              <div class="events-head">
                <h4>Notifications</h4>
                <p>{{ selectedDateLabel }}</p>
              </div>

              <div v-if="selectedDateEvents.length === 0" class="events-empty">
                No notifications for this date
              </div>

              <ul v-else class="events-list">
                <li
                  v-for="event in selectedDateEvents"
                  :key="event.id"
                  class="event-item"
                  role="button"
                  tabindex="0"
                  @click="openNotification(event)"
                  @keydown.enter="openNotification(event)"
                >
                  <div class="event-time">{{ event.time }}</div>
                  <div class="event-info">
                    <p class="event-title">{{ event.title }}</p>
                    <p class="event-location">{{ event.location }}</p>
                  </div>
                  <span class="event-type" :class="event.type">{{ event.type }}</span>
                </li>
              </ul>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>

  <transition name="notif-fade">
    <div
      v-if="isNotificationModalOpen"
      class="notif-modal-overlay"
      @click.self="closeNotificationModal"
    >
      <div class="notif-modal">
        <div class="notif-modal-head">
          <h4>Notification Details</h4>
          <button class="notif-close" @click="closeNotificationModal">x</button>
        </div>

        <div v-if="activeNotification" class="notif-body">
          <p class="notif-title">{{ activeNotification.title }}</p>
          <p class="notif-meta">
            <strong>Time:</strong> {{ activeNotification.time }}
          </p>
          <p class="notif-meta">
            <strong>Location:</strong> {{ activeNotification.location }}
          </p>
          <p class="notif-meta">
            <strong>Type:</strong> {{ activeNotification.type }}
          </p>
        </div>

        <div class="notif-actions">
          <button class="notif-secondary" @click="closeNotificationModal">Close</button>
          <button class="notif-primary" @click="goToNotificationPage">Go to Page</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import SidebarApplicant from "@/components/sb-applicant.vue"

const router = useRouter()
const now = ref(new Date())
const visibleMonth = ref(new Date(now.value.getFullYear(), now.value.getMonth(), 1))
const selectedDate = ref(startOfDay(new Date()))
const timerId = ref(null)
const isMobileSidebarOpen = ref(false)
const resizeHandler = ref(null)
const isNotificationModalOpen = ref(false)
const activeNotification = ref(null)
const scheduleEvents = ref([
  {
    id: "ev-1",
    dateKey: toDateKey(addDays(new Date(), 1)),
    time: "09:00 AM",
    title: "Interview - Data Encoder",
    location: "HR Office, La Salle",
    type: "interview"
  },
  {
    id: "ev-2",
    dateKey: toDateKey(addDays(new Date(), 2)),
    time: "01:30 PM",
    title: "Job Fair Orientation",
    location: "Online Meeting",
    type: "orientation"
  },
  {
    id: "ev-3",
    dateKey: toDateKey(new Date()),
    time: "04:00 PM",
    title: "Application Follow-up",
    location: "Recruitment Team",
    type: "followup"
  }
])

const updateTime = () => {
  now.value = new Date()
}

onMounted(() => {
  updateTime()
  timerId.value = setInterval(updateTime, 1000)

  resizeHandler.value = () => {
    if (window.innerWidth > 1024) {
      isMobileSidebarOpen.value = false
    }
  }
  window.addEventListener("resize", resizeHandler.value)
})

onBeforeUnmount(() => {
  if (timerId.value) clearInterval(timerId.value)
  if (resizeHandler.value) {
    window.removeEventListener("resize", resizeHandler.value)
  }
})

const formattedDate = computed(() =>
  now.value.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric"
  })
)

const formattedTime = computed(() =>
  now.value.toLocaleTimeString("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit"
  })
)

const weekdays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]

const monthYearLabel = computed(() =>
  visibleMonth.value.toLocaleDateString("en-US", {
    month: "long",
    year: "numeric"
  })
)

const selectedDateLabel = computed(() =>
  selectedDate.value.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric"
  })
)

const selectedDateEvents = computed(() =>
  scheduleEvents.value.filter(event => event.dateKey === toDateKey(selectedDate.value))
)

const calendarDays = computed(() => {
  const year = visibleMonth.value.getFullYear()
  const month = visibleMonth.value.getMonth()

  const startDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const daysInPrevMonth = new Date(year, month, 0).getDate()

  const list = []

  for (let i = startDay - 1; i >= 0; i--) {
    const dayNumber = daysInPrevMonth - i
    const dateObj = new Date(year, month - 1, dayNumber)
    list.push({
      dayNumber,
      currentMonth: false,
      isToday: isSameDate(dateObj, now.value),
      isSelected: isSameDate(dateObj, selectedDate.value),
      eventCount: eventCountByDate(dateObj),
      dateKey: toDateKey(dateObj),
      dateObj
    })
  }

  for (let dayNumber = 1; dayNumber <= daysInMonth; dayNumber++) {
    const dateObj = new Date(year, month, dayNumber)
    list.push({
      dayNumber,
      currentMonth: true,
      isToday: isSameDate(dateObj, now.value),
      isSelected: isSameDate(dateObj, selectedDate.value),
      eventCount: eventCountByDate(dateObj),
      dateKey: toDateKey(dateObj),
      dateObj
    })
  }

  while (list.length < 42) {
    const dayNumber = list.length - (startDay + daysInMonth) + 1
    const dateObj = new Date(year, month + 1, dayNumber)
    list.push({
      dayNumber,
      currentMonth: false,
      isToday: isSameDate(dateObj, now.value),
      isSelected: isSameDate(dateObj, selectedDate.value),
      eventCount: eventCountByDate(dateObj),
      dateKey: toDateKey(dateObj),
      dateObj
    })
  }

  return list
})

function goPrevMonth() {
  visibleMonth.value = new Date(
    visibleMonth.value.getFullYear(),
    visibleMonth.value.getMonth() - 1,
    1
  )
}

function goNextMonth() {
  visibleMonth.value = new Date(
    visibleMonth.value.getFullYear(),
    visibleMonth.value.getMonth() + 1,
    1
  )
}

function selectDate(dateObj) {
  selectedDate.value = startOfDay(dateObj)
}

function startOfDay(dateObj) {
  return new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate())
}

function toDateKey(dateObj) {
  const y = dateObj.getFullYear()
  const m = String(dateObj.getMonth() + 1).padStart(2, "0")
  const d = String(dateObj.getDate()).padStart(2, "0")
  return `${y}-${m}-${d}`
}

function addDays(baseDate, days) {
  const d = new Date(baseDate)
  d.setDate(d.getDate() + days)
  return d
}

function eventCountByDate(dateObj) {
  const key = toDateKey(dateObj)
  return scheduleEvents.value.filter(event => event.dateKey === key).length
}

function openNotification(event) {
  if (!event) return

  activeNotification.value = event
  isNotificationModalOpen.value = true
}

function toggleMobileSidebar() {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

function closeMobileSidebar() {
  isMobileSidebarOpen.value = false
}

function closeNotificationModal() {
  isNotificationModalOpen.value = false
}

function goToNotificationPage() {
  const event = activeNotification.value
  if (!event) return

  if (event.type === "interview") {
    router.push("/applicant/interviews")
  } else if (event.type === "followup") {
    router.push("/applicant/applications")
  } else {
    router.push("/applicant/notifications")
  }

  closeNotificationModal()
}

function isSameDate(a, b) {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: auto 1fr;
  min-height: 100vh;
  background: #f5f7fb;
}

/* MAIN */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  min-height: 64px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid #e5e7eb;
  padding: 10px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.topbar-left {
  display: flex;
  flex-direction: column;
}

.menu-toggle {
  display: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #dbe2ea;
  background: #ffffff;
  color: #334155;
  cursor: pointer;
  margin-bottom: 4px;
}

.menu-toggle i {
  font-size: 20px;
}

.page-title {
  font-weight: 600;
  font-size: 18px;
  color: #111827;
  margin: 0;
}

.page-subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  color: #64748b;
}

.datetime {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #6b7280;
}

.dot {
  color: #94a3b8;
}

.main-body {
  flex: 1;
  display: flex;
  min-height: 0;
  min-width: 0;
}

.content {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}

.right-panel {
  width: 320px;
  border-left: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
  padding: 20px 14px;
  overflow-y: auto;
}

.widget-stack {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.calendar-card {
  background:
    radial-gradient(140px 60px at 10% 0%, rgba(59, 130, 246, 0.16), transparent 70%),
    radial-gradient(140px 60px at 95% 10%, rgba(16, 185, 129, 0.14), transparent 65%),
    #ffffff;
  border: 1px solid #dbe2ea;
  border-radius: 18px;
  padding: 14px 14px 12px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.calendar-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.calendar-head h3 {
  margin: 0;
  font-size: 15px;
  color: #0f172a;
}

.nav-btn {
  width: 30px;
  height: 30px;
  border: 1px solid #cdd6e0;
  background: #ffffff;
  color: #334155;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  margin-bottom: 6px;
}

.weekdays span {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
}

.days-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}

.day-cell {
  height: 36px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 13px;
  color: #1f2937;
  background: #f8fafc;
  border: 1px solid transparent;
  cursor: pointer;
  padding: 0;
  transition: 0.18s ease;
}

.day-cell:hover {
  transform: translateY(-1px);
  border-color: #c7d2fe;
  background: #eef2ff;
}

.day-cell.muted {
  color: #9ca3af;
}

.day-cell.today {
  background: #dbeafe;
  color: #1d4ed8;
  border-color: #93c5fd;
  font-weight: 700;
}

.day-cell.selected {
  background: #2563eb;
  color: #ffffff;
  border-color: #1d4ed8;
  font-weight: 700;
}

.day-cell.has-event {
  font-weight: 600;
}

.event-dot {
  position: absolute;
  bottom: 5px;
  width: 5px;
  height: 5px;
  border-radius: 999px;
  background: #10b981;
}

.day-cell.selected .event-dot {
  background: #fde68a;
}

.events-card {
  background: #ffffff;
  border: 1px solid #dbe2ea;
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
}

.events-head {
  margin-bottom: 10px;
}

.events-head h4 {
  margin: 0;
  font-size: 14px;
  color: #0f172a;
}

.events-head p {
  margin: 2px 0 0;
  font-size: 12px;
  color: #64748b;
}

.events-empty {
  padding: 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px dashed #dbe2ea;
  color: #64748b;
  font-size: 12px;
}

.events-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.event-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 8px;
  align-items: center;
  padding: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
  transition: 0.16s ease;
}

.event-item:hover {
  transform: translateY(-1px);
  border-color: #c7d2fe;
  background: #eef2ff;
}

.event-item:focus {
  outline: 2px solid #93c5fd;
  outline-offset: 2px;
}

.notif-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
  padding: 14px;
}

.notif-modal {
  width: min(460px, 96vw);
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #dbe2ea;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.2);
  padding: 14px;
}

.notif-modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.notif-modal-head h4 {
  margin: 0;
  color: #0f172a;
}

.notif-close {
  border: none;
  background: #f1f5f9;
  color: #334155;
  width: 30px;
  height: 30px;
  border-radius: 999px;
  cursor: pointer;
}

.notif-body {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  padding: 12px;
}

.notif-title {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 15px;
  font-weight: 700;
}

.notif-meta {
  margin: 4px 0;
  font-size: 13px;
  color: #334155;
}

.notif-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.notif-secondary,
.notif-primary {
  border: none;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
  cursor: pointer;
}

.notif-secondary {
  background: #e2e8f0;
  color: #1f2937;
}

.notif-primary {
  background: #2563eb;
  color: #ffffff;
}

.notif-fade-enter-active,
.notif-fade-leave-active {
  transition: opacity 0.18s ease;
}

.notif-fade-enter-from,
.notif-fade-leave-to {
  opacity: 0;
}

.event-time {
  font-size: 11px;
  color: #334155;
  font-weight: 700;
  min-width: 62px;
}

.event-title {
  margin: 0;
  font-size: 12px;
  color: #0f172a;
  font-weight: 600;
}

.event-location {
  margin: 1px 0 0;
  font-size: 11px;
  color: #64748b;
}

.event-type {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  border-radius: 999px;
  padding: 4px 8px;
  font-weight: 700;
}

.event-type.interview {
  background: #dbeafe;
  color: #1d4ed8;
}

.event-type.orientation {
  background: #ecfdf5;
  color: #047857;
}

.event-type.followup {
  background: #fef3c7;
  color: #92400e;
}

@media (max-width: 1024px) {
  .layout {
    grid-template-columns: 1fr;
  }
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.35);
  z-index: 50;
}

@media (max-width: 1200px) {
  .right-panel {
    width: 280px;
  }
}

@media (max-width: 1024px) {
  .menu-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .main-body {
    flex-direction: column;
  }

  .right-panel {
    width: 100%;
    border-left: none;
    border-top: 1px solid #e5e7eb;
  }
}

@media (max-width: 768px) {
  .topbar {
    padding: 10px 14px;
  }

  .datetime {
    font-size: 12px;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
    text-align: right;
  }

  .content {
    padding: 14px;
  }

  .right-panel {
    padding: 12px;
  }
}
</style>
