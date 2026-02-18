<template>
  <section class="training-page">
    <header class="page-header">
      <div>
        <h2>Training Progress</h2>
        <p>
          Static training data for applicants. This will be connected to
          operations data in a future update.
        </p>
      </div>
      <span class="tag">Static Data</span>
    </header>

    <div class="summary-grid">
      <article class="summary-card">
        <p class="label">Total Modules</p>
        <p class="value">6</p>
      </article>
      <article class="summary-card">
        <p class="label">Completed</p>
        <p class="value">3</p>
      </article>
      <article class="summary-card">
        <p class="label">In Progress</p>
        <p class="value">2</p>
      </article>
      <article class="summary-card">
        <p class="label">Overall Progress</p>
        <p class="value">58%</p>
      </article>
    </div>

    <section class="table-card">
      <table>
        <thead>
          <tr>
            <th>Training Module</th>
            <th>Coach</th>
            <th>Schedule</th>
            <th>Status</th>
            <th>Progress</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in trainingRows" :key="item.id">
            <td>{{ item.module }}</td>
            <td>{{ item.coach }}</td>
            <td>{{ item.schedule }}</td>
            <td>
              <span class="status" :class="statusClass(item.status)">
                {{ item.status }}
              </span>
            </td>
            <td>
              <div class="progress-wrap">
                <div class="progress-track">
                  <div class="progress-fill" :style="{ width: `${item.progress}%` }"></div>
                </div>
                <span class="progress-text">{{ item.progress }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </section>
</template>

<script setup>
const trainingRows = [
  {
    id: "tp-001",
    module: "Workplace Safety Basics",
    coach: "Angela Ramos",
    schedule: "March 2, 2026",
    status: "Completed",
    progress: 100
  },
  {
    id: "tp-002",
    module: "Communication in Teams",
    coach: "Ryan Flores",
    schedule: "March 5, 2026",
    status: "Completed",
    progress: 100
  },
  {
    id: "tp-003",
    module: "Digital Productivity Tools",
    coach: "Carla Santos",
    schedule: "March 9, 2026",
    status: "In Progress",
    progress: 70
  },
  {
    id: "tp-004",
    module: "Customer Service Foundations",
    coach: "Nina Cruz",
    schedule: "March 12, 2026",
    status: "In Progress",
    progress: 45
  },
  {
    id: "tp-005",
    module: "Department Orientation",
    coach: "Mark Reyes",
    schedule: "March 16, 2026",
    status: "Not Started",
    progress: 0
  }
]

function statusClass(status) {
  if (status === "Completed") return "done"
  if (status === "In Progress") return "active"
  return "pending"
}
</script>

<style scoped>
.training-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 14px;
}

.page-header h2 {
  margin: 0;
  color: #0f172a;
}

.page-header p {
  margin: 6px 0 0;
  font-size: 13px;
  color: #64748b;
  max-width: 640px;
}

.tag {
  padding: 6px 10px;
  border-radius: 999px;
  background: #e0e7ff;
  color: #3730a3;
  font-size: 12px;
  font-weight: 600;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
}

.summary-card .label {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.summary-card .value {
  margin: 6px 0 0;
  color: #0f172a;
  font-size: 24px;
  font-weight: 700;
}

.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  font-size: 13px;
}

th {
  font-size: 12px;
  color: #64748b;
}

.status {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.status.done {
  background: #dcfce7;
  color: #166534;
}

.status.active {
  background: #dbeafe;
  color: #1d4ed8;
}

.status.pending {
  background: #f1f5f9;
  color: #475569;
}

.progress-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-track {
  width: 120px;
  height: 7px;
  border-radius: 999px;
  background: #e2e8f0;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #22c55e);
}

.progress-text {
  font-size: 12px;
  color: #334155;
  min-width: 36px;
}

@media (max-width: 900px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
