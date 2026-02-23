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
              Customize your own physical assessment form and score model per business needs.
            </p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Evaluation Setup</h3>
          </div>

          <div class="setup-grid">
            <label class="individual-wrap">
              Individual
              <input
                v-model="individualSearch"
                type="text"
                placeholder="Search applicant name..."
                @focus="showIndividualList = true"
              />
              <div v-if="showIndividualList && filteredIndividuals.length" class="individual-list">
                <button
                  v-for="person in filteredIndividuals"
                  :key="person.id"
                  class="individual-item"
                  @click="selectIndividual(person)"
                  type="button"
                >
                  {{ person.name }}
                </button>
              </div>
            </label>

            <label>
              Evaluator
              <input v-model="form.evaluator" type="text" />
            </label>

            <label>
              Rating Minimum
              <input v-model.number="scale.min" type="number" min="0" />
            </label>

            <label>
              Rating Maximum
              <input v-model.number="scale.max" type="number" :min="scale.min + 1" />
            </label>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h3>Custom Criteria Builder</h3>
            <button class="btn primary" @click="addCriterion">+ Add Criterion</button>
          </div>

          <div class="criteria-table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Criterion</th>
                  <th>Weight (%)</th>
                  <th>Rating ({{ scale.min }}-{{ scale.max }})</th>
                  <th>Remarks</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="criterion in criteriaRows" :key="criterion.id">
                  <td>
                    <input v-model="criterion.label" type="text" placeholder="Criterion name" />
                  </td>
                  <td>
                    <input
                      v-model.number="criterion.weight"
                      type="number"
                      min="1"
                      max="100"
                    />
                  </td>
                  <td>
                    <input
                      v-model.number="criterion.rating"
                      type="number"
                      :min="scale.min"
                      :max="scale.max"
                    />
                  </td>
                  <td>
                    <input v-model="criterion.remarks" type="text" placeholder="Remarks..." />
                  </td>
                  <td class="center">
                    <button
                      class="btn danger sm"
                      type="button"
                      :disabled="criteriaRows.length <= 1"
                      @click="removeCriterion(criterion.id)"
                    >
                      Remove
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="summary-row">
            <div class="score-box">
              Weighted Score:
              <strong>{{ totalPhysicalScore }}</strong>
              <span>/ 100</span>
              <small>Total Weight: {{ totalWeight }}%</small>
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
                  <th>Score</th>
                  <th>Criteria Count</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in recordedEvaluations" :key="row.id">
                  <td>{{ row.individual }}</td>
                  <td>{{ row.evaluator }}</td>
                  <td>{{ row.score }}</td>
                  <td>{{ row.criteriaCount }}</td>
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
        individualId: null,
        individualName: "",
        evaluator: "Ops Team A"
      },
      scale: {
        min: 1,
        max: 10
      },
      individualSearch: "",
      showIndividualList: false,
      applicants: [
        { id: "app-001", name: "Juan Dela Cruz" },
        { id: "app-002", name: "Maria Santos" },
        { id: "app-003", name: "Ralph Rivera" },
        { id: "app-004", name: "Bea Cruz" }
      ],
      criteriaRows: [
        this.makeCriterion("Strength", 20, 8, "Good upper-body control"),
        this.makeCriterion("Endurance", 20, 7, "Needs pacing"),
        this.makeCriterion("Agility", 20, 7, "Responsive movement"),
        this.makeCriterion("Coordination", 20, 8, "Consistent drills"),
        this.makeCriterion("Discipline", 20, 9, "Excellent compliance")
      ],
      recordedEvaluations: [
        {
          id: 1,
          individual: "Maria Santos",
          evaluator: "Ops Team B",
          score: 84,
          criteriaCount: 5,
          status: "Passed",
          statusClass: "success"
        }
      ]
    }
  },
  computed: {
    filteredIndividuals() {
      const keyword = this.individualSearch.trim().toLowerCase()
      if (!keyword) return this.applicants
      return this.applicants.filter((person) => person.name.toLowerCase().includes(keyword))
    },
    totalWeight() {
      return this.criteriaRows.reduce((sum, row) => sum + Number(row.weight || 0), 0)
    },
    totalPhysicalScore() {
      const min = Number(this.scale.min)
      const max = Number(this.scale.max)
      const range = Math.max(1, max - min)
      const totalWeight = this.totalWeight || 1

      const weighted = this.criteriaRows.reduce((sum, row) => {
        const rating = Number(row.rating || 0)
        const clamped = Math.min(max, Math.max(min, rating))
        const normalized = ((clamped - min) / range) * 100
        return sum + normalized * Number(row.weight || 0)
      }, 0)

      return Math.round(weighted / totalWeight)
    }
  },
  mounted() {
    document.addEventListener("click", this.handleOutsideClick)
    this.loadApplicants()
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleOutsideClick)
  },
  methods: {
    makeCriterion(label = "", weight = 20, rating = 1, remarks = "") {
      return {
        id: Date.now() + Math.floor(Math.random() * 10000),
        label,
        weight,
        rating,
        remarks
      }
    },
    addCriterion() {
      this.criteriaRows.push(this.makeCriterion("", 10, this.scale.min, ""))
    },
    removeCriterion(id) {
      if (this.criteriaRows.length <= 1) return
      this.criteriaRows = this.criteriaRows.filter((row) => row.id !== id)
    },
    selectIndividual(person) {
      this.form.individualId = person.id
      this.form.individualName = person.name
      this.individualSearch = person.name
      this.showIndividualList = false
    },
    handleOutsideClick(event) {
      const target = event.target
      if (!(target instanceof Element)) return
      if (!target.closest(".individual-wrap")) {
        this.showIndividualList = false
      }
    },
    async loadApplicants() {
      // Future-ready hook:
      // Replace this block with API call once applicant endpoint is available.
      // Keep fallback static list so page works even without backend data.
      return
    },
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

      const selectedName = this.form.individualName || this.individualSearch || "Unassigned"

      this.recordedEvaluations.unshift({
        id: Date.now(),
        individual: selectedName,
        evaluator: this.form.evaluator || "N/A",
        score,
        criteriaCount: this.criteriaRows.length,
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

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.panel-head h3 {
  margin: 0;
  font-size: 16px;
}

.setup-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.setup-grid label {
  font-size: 12px;
  color: #334155;
}

.setup-grid input {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
}

.individual-wrap {
  position: relative;
}

.individual-list {
  position: absolute;
  z-index: 10;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  max-height: 170px;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
}

.individual-item {
  width: 100%;
  text-align: left;
  border: none;
  background: #ffffff;
  padding: 9px 10px;
  font-size: 13px;
  cursor: pointer;
}

.individual-item:hover {
  background: #f8fafc;
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

.center {
  text-align: center;
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
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.score-box strong {
  font-size: 18px;
}

.score-box small {
  color: #64748b;
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

.btn.danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn.sm {
  padding: 6px 10px;
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

.badge.info {
  background: #dbeafe;
  color: #1d4ed8;
}

.badge.warning {
  background: #fef3c7;
  color: #92400e;
}

@media (max-width: 900px) {
  .setup-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .summary-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
