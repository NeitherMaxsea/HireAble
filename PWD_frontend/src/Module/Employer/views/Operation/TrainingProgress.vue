<template>
  <div class="app">
    <SidebarOpEmployer />

    <div class="main-wrapper">
      <NavbarEmployer />

      <main class="content">
        <div class="page-header">
          <div>
            <h2>Training Progress</h2>
            <p class="subtitle">
              Log daily or weekly progress and track attendance, participation, and skill improvement.
            </p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Progress Log Entry</h3>
          </div>
          <div class="form-grid">
            <label>
              Individual
              <select v-model="progressForm.individual">
                <option v-for="name in individuals" :key="name" :value="name">{{ name }}</option>
              </select>
            </label>
            <label>
              Frequency
              <select v-model="progressForm.frequency">
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
              </select>
            </label>
            <label>
              Attendance (%)
              <input v-model.number="progressForm.attendance" type="number" min="0" max="100" />
            </label>
            <label>
              Participation (%)
              <input v-model.number="progressForm.participation" type="number" min="0" max="100" />
            </label>
            <label>
              Skill Improvement (%)
              <input v-model.number="progressForm.skill" type="number" min="0" max="100" />
            </label>
          </div>
          <div class="form-actions">
            <button class="btn primary" @click="addProgressLog">Save Progress Log</button>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h3>Progress Dashboard</h3>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Individual</th>
                  <th>Latest Frequency</th>
                  <th>Attendance</th>
                  <th>Participation</th>
                  <th>Skill Improvement</th>
                  <th>Completion</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in progressRows" :key="row.id">
                  <td>{{ row.individual }}</td>
                  <td>{{ row.frequency }}</td>
                  <td>{{ row.attendance }}%</td>
                  <td>{{ row.participation }}%</td>
                  <td>{{ row.skill }}%</td>
                  <td>
                    <div class="progress-wrap">
                      <div class="progress-track">
                        <div class="progress-fill" :style="{ width: `${row.completion}%` }"></div>
                      </div>
                      <span>{{ row.completion }}%</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge" :class="statusClass(row.status)">
                      {{ row.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<script>
import SidebarOpEmployer from "@/components/sb-employer-operator.vue"
import NavbarEmployer from "@/components/nv-employer.vue"

export default {
  name: "TrainingProgress",
  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },
  data() {
    return {
      progressForm: {
        individual: "Juan Dela Cruz",
        frequency: "Daily",
        attendance: 88,
        participation: 84,
        skill: 79
      },
      progressRows: [
        {
          id: 1,
          individual: "Juan Dela Cruz",
          frequency: "Daily",
          attendance: 88,
          participation: 84,
          skill: 79,
          completion: 84,
          status: "On Track"
        },
        {
          id: 2,
          individual: "Maria Santos",
          frequency: "Weekly",
          attendance: 97,
          participation: 92,
          skill: 90,
          completion: 93,
          status: "Completed"
        },
        {
          id: 3,
          individual: "Ralph Rivera",
          frequency: "Daily",
          attendance: 62,
          participation: 58,
          skill: 55,
          completion: 58,
          status: "Needs Attention"
        }
      ]
    }
  },
  computed: {
    individuals() {
      return [...new Set(this.progressRows.map((row) => row.individual))]
    }
  },
  methods: {
    statusClass(status) {
      if (status === "Completed") return "success"
      if (status === "On Track") return "info"
      return "danger"
    },
    computeCompletion(attendance, participation, skill) {
      const score = Math.round(attendance * 0.34 + participation * 0.33 + skill * 0.33)
      return Math.min(100, Math.max(0, score))
    },
    deriveStatus(completion) {
      if (completion >= 90) return "Completed"
      if (completion >= 70) return "On Track"
      return "Needs Attention"
    },
    addProgressLog() {
      const completion = this.computeCompletion(
        Number(this.progressForm.attendance),
        Number(this.progressForm.participation),
        Number(this.progressForm.skill)
      )

      this.progressRows.unshift({
        id: Date.now(),
        individual: this.progressForm.individual,
        frequency: this.progressForm.frequency,
        attendance: Number(this.progressForm.attendance),
        participation: Number(this.progressForm.participation),
        skill: Number(this.progressForm.skill),
        completion,
        status: this.deriveStatus(completion)
      })
    }
  }
}
</script>

<style scoped>
.app {
  display: flex;
  height: 100vh;
  background: #f5f7fb;
  overflow: hidden;
}

.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
}

.content {
  padding: 24px;
  display: grid;
  gap: 16px;
  overflow-y: auto;
  min-height: 0;
}

.page-header h2 {
  margin: 0;
}

.subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
}

.panel-head h3 {
  margin: 0 0 12px;
  font-size: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.form-grid label {
  font-size: 12px;
  color: #334155;
}

.form-grid input,
.form-grid select {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
}

.form-actions {
  margin-top: 12px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 11px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  font-size: 13px;
}

th {
  color: #64748b;
  font-size: 12px;
}

.progress-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-track {
  width: 120px;
  height: 8px;
  border-radius: 999px;
  background: #e2e8f0;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #22c55e);
}

.badge {
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 600;
}

.badge.success {
  background: #dcfce7;
  color: #166534;
}

.badge.info {
  background: #dbeafe;
  color: #1d4ed8;
}

.badge.danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn {
  border: none;
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 13px;
  cursor: pointer;
}

.btn.primary {
  background: #2563eb;
  color: #ffffff;
}

@media (max-width: 1000px) {
  .form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
