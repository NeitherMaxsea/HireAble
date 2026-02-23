<template>
  <header class="topbar">
    <div class="breadcrumb">
      <span class="parent">Finance</span>
      <span class="separator">|</span>
      <span class="child">{{ child }}</span>
    </div>

    <div class="datetime">{{ formattedDate }} | {{ formattedTime }}</div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRoute } from "vue-router"

const route = useRoute()
const formattedDate = ref("")
const formattedTime = ref("")

let timer = null

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

onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
})

onBeforeUnmount(() => {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
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

@media (max-width: 720px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    height: auto;
    padding: 10px 14px;
  }
}
</style>
