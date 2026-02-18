<template>
  <div class="app">
    <SidebarOpEmployer />

    <div class="main-wrapper">
      <NavbarEmployer />

      <main class="content">
        <div class="page-header">
          <div>
            <h2>Physical Evaluation</h2>
            <p class="subtitle">
              Record physical assessment by criterion and compute total physical score.
            </p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Evaluation Form</h3>
          </div>

          <div class="form-grid">
            <label>
              Individual
              <input v-model="form.individual" type="text" />
            </label>
            <label>
              Evaluator
              <input v-model="form.evaluator" type="text" />
            </label>
          </div>

          <div class="criteria-table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Criterion</th>
                  <th>Rating (1-10)</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="criterion in criteriaRows" :key="criterion.key">
                  <td>{{ criterion.label }}</td>
                  <td>
                    <input
                      v-model.number="ratings[criterion.key]"
                      type="number"
                      min="1"
                      max="10"
                    />
                  </td>
                  <td>
                    <input
                      v-model="remarks[criterion.key]"
                      type="text"
                      :placeholder="`Remarks for ${criterion.label}`"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="summary-row">
            <div class="score-box">
              Total Physical Score:
              <strong>{{ totalPhysicalScore }}</strong>
              <span>/ 100</span>
            </div>
            <button class="btn primary" @click="saveEvaluation">Save Evaluation</button>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h3>Recorded Evaluations</h3>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Individual</th>
                  <th>Evaluator</th>
                  <th>Total Score</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in recordedEvaluations" :key="row.id">
                  <td>{{ row.individual }}</td>
                  <td>{{ row.evaluator }}</td>
                  <td>{{ row.score }}</td>
                  <td>
                    <span class="badge" :class="row.statusClass">{{ row.status }}</span>
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
  name: "PhysicalEvaluation",
  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },
  data() {
    return {
      form: {
        individual: "Juan Dela Cruz",
        evaluator: "Ops Team A"
      },
      criteriaRows: [
        { key: "strength", label: "Strength" },
        { key: "endurance", label: "Endurance" },
        { key: "agility", label: "Agility" },
        { key: "coordination", label: "Coordination" },
        { key: "discipline", label: "Discipline" }
      ],
      ratings: {
        strength: 8,
        endurance: 7,
        agility: 7,
        coordination: 8,
        discipline: 9
      },
      remarks: {
        strength: "Good upper-body control",
        endurance: "Needs pacing",
        agility: "Responsive movement",
        coordination: "Consistent drills",
        discipline: "Excellent compliance"
      },
      recordedEvaluations: [
        {
          id: 1,
          individual: "Maria Santos",
          evaluator: "Ops Team B",
          score: 84,
          status: "Passed",
          statusClass: "success"
        }
      ]
    }
  },
  computed: {
    totalPhysicalScore() {
      const total = this.criteriaRows.reduce((sum, row) => {
        const value = Number(this.ratings[row.key] || 0)
        return sum + Math.min(10, Math.max(1, value))
      }, 0)
      return total * 2
    }
  },
  methods: {
    saveEvaluation() {
      const score = this.totalPhysicalScore
      let status = "Needs Improvement"
      let statusClass = "warning"
      if (score >= 80) {
        status = "Passed"
        statusClass = "success"
      } else if (score >= 65) {
        status = "Conditional"
        statusClass = "info"
      }

      this.recordedEvaluations.unshift({
        id: Date.now(),
        individual: this.form.individual,
        evaluator: this.form.evaluator,
        score,
        status,
        statusClass
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
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 12px;
}

.form-grid label {
  font-size: 12px;
  color: #334155;
}

.form-grid input {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
}

.criteria-table-wrap,
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

tbody input[type="number"],
tbody input[type="text"] {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 10px;
}

.summary-row {
  margin-top: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.score-box {
  font-size: 14px;
  color: #1e293b;
}

.score-box strong {
  font-size: 18px;
  margin-left: 4px;
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

.badge.warning {
  background: #fef3c7;
  color: #92400e;
}

@media (max-width: 760px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .summary-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
