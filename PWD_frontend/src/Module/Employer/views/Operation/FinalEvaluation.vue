<template>
  <div class="app">
    <SidebarOpEmployer />

    <div class="main-wrapper">
      <NavbarEmployer />

      <main class="content">
        <div class="page-header">
          <div>
            <h2>Final Evaluation</h2>
            <p class="subtitle">
              Combine written test, physical evaluation, and training progress to produce final readiness.
            </p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Evaluation Inputs</h3>
          </div>

          <div class="form-grid">
            <label>
              Individual
              <select v-model="form.individual">
                <option v-for="person in individuals" :key="person" :value="person">{{ person }}</option>
              </select>
            </label>
            <label>
              Written Test Score (%)
              <input v-model.number="form.writtenScore" type="number" min="0" max="100" />
            </label>
            <label>
              Physical Evaluation Score (%)
              <input v-model.number="form.physicalScore" type="number" min="0" max="100" />
            </label>
            <label>
              Training Progress (%)
              <input v-model.number="form.trainingScore" type="number" min="0" max="100" />
            </label>
            <label class="wide">
              Operations Recommendation
              <textarea v-model="form.recommendation" rows="3"></textarea>
            </label>
          </div>

          <div class="summary">
            <div class="score-card">
              <p>Final Composite Score</p>
              <h3>{{ compositeScore }}%</h3>
            </div>
            <div class="status-card" :class="finalStatusClass">
              <p>Final Status</p>
              <h4>{{ finalStatus }}</h4>
            </div>
          </div>

          <div class="actions">
            <button class="btn primary" @click="saveFinalEvaluation">Save Final Evaluation</button>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h3>Final Results</h3>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Individual</th>
                  <th>Written</th>
                  <th>Physical</th>
                  <th>Training</th>
                  <th>Composite</th>
                  <th>Status</th>
                  <th>Recommendation</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in finalRows" :key="item.id">
                  <td>{{ item.individual }}</td>
                  <td>{{ item.writtenScore }}%</td>
                  <td>{{ item.physicalScore }}%</td>
                  <td>{{ item.trainingScore }}%</td>
                  <td>{{ item.composite }}%</td>
                  <td>
                    <span class="badge" :class="item.statusClass">{{ item.status }}</span>
                  </td>
                  <td>{{ item.recommendation }}</td>
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
  name: "FinalEvaluation",
  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },
  data() {
    return {
      form: {
        individual: "Juan Dela Cruz",
        writtenScore: 78,
        physicalScore: 85,
        trainingScore: 82,
        recommendation: "Proceed to supervised deployment."
      },
      finalRows: [
        {
          id: 1,
          individual: "Maria Santos",
          writtenScore: 88,
          physicalScore: 86,
          trainingScore: 91,
          composite: 88,
          status: "Ready for Deployment",
          statusClass: "success",
          recommendation: "Eligible for full deployment schedule."
        }
      ]
    }
  },
  computed: {
    individuals() {
      return ["Juan Dela Cruz", "Maria Santos", "Ralph Rivera", "Bea Cruz"]
    },
    compositeScore() {
      const score =
        this.form.writtenScore * 0.35 +
        this.form.physicalScore * 0.35 +
        this.form.trainingScore * 0.3
      return Math.round(score)
    },
    finalStatus() {
      if (this.compositeScore >= 80) return "Ready for Deployment"
      if (this.compositeScore >= 60) return "Needs Retraining"
      return "Not Qualified"
    },
    finalStatusClass() {
      if (this.finalStatus === "Ready for Deployment") return "success"
      if (this.finalStatus === "Needs Retraining") return "warning"
      return "danger"
    }
  },
  methods: {
    saveFinalEvaluation() {
      this.finalRows.unshift({
        id: Date.now(),
        individual: this.form.individual,
        writtenScore: this.form.writtenScore,
        physicalScore: this.form.physicalScore,
        trainingScore: this.form.trainingScore,
        composite: this.compositeScore,
        status: this.finalStatus,
        statusClass: this.finalStatusClass,
        recommendation: this.form.recommendation
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
}

.form-grid label {
  font-size: 12px;
  color: #334155;
}

.form-grid label.wide {
  grid-column: 1 / -1;
}

.form-grid input,
.form-grid select,
.form-grid textarea {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
}

.summary {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.score-card,
.status-card {
  border-radius: 12px;
  padding: 12px;
}

.score-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.score-card p,
.status-card p {
  margin: 0;
  font-size: 12px;
  color: #64748b;
}

.score-card h3,
.status-card h4 {
  margin: 5px 0 0;
}

.status-card.success {
  background: #dcfce7;
  border: 1px solid #86efac;
  color: #166534;
}

.status-card.warning {
  background: #fef3c7;
  border: 1px solid #fcd34d;
  color: #92400e;
}

.status-card.danger {
  background: #fee2e2;
  border: 1px solid #fca5a5;
  color: #991b1b;
}

.actions {
  margin-top: 12px;
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

.badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.badge.danger {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .summary {
    grid-template-columns: 1fr;
  }
}
</style>
