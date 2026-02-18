<template>
  <div class="app">
    <SidebarOpEmployer />

    <div class="main-wrapper">
      <NavbarEmployer />

      <main class="content">
        <div class="page-header">
          <div>
            <h2>Training Programs</h2>
            <p class="subtitle">
              Add and manage training programs, assign trainers and duration, and enroll individuals.
            </p>
          </div>
          <div class="header-actions">
            <button class="btn ghost" @click="openEnrollModal">Enroll Individual</button>
            <button class="btn primary" @click="openProgramModal">+ Add Program</button>
          </div>
        </div>

        <section class="panel">
          <div class="panel-head">
            <h3>Program Catalog</h3>
            <input v-model="searchTerm" type="text" placeholder="Search program or trainer..." />
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Program</th>
                  <th>Category</th>
                  <th>Trainer</th>
                  <th>Duration</th>
                  <th>Participants</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="program in filteredPrograms" :key="program.id">
                  <td>{{ program.title }}</td>
                  <td>{{ program.category }}</td>
                  <td>{{ program.trainer }}</td>
                  <td>{{ program.duration }}</td>
                  <td>{{ program.enrolled }}</td>
                  <td>
                    <span class="badge" :class="statusClass(program.status)">
                      {{ program.status }}
                    </span>
                  </td>
                  <td class="actions">
                    <button class="btn-link" @click="editProgram(program)">Edit</button>
                    <button class="btn-link danger" @click="toggleProgramStatus(program)">
                      {{ program.status === "Active" ? "Deactivate" : "Activate" }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <h3>Enrollment List</h3>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Individual</th>
                  <th>Program</th>
                  <th>Start Date</th>
                  <th>Target End</th>
                  <th>Progress</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in enrollmentList" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>{{ item.program }}</td>
                  <td>{{ item.startDate }}</td>
                  <td>{{ item.endDate }}</td>
                  <td>{{ item.progress }}%</td>
                  <td>
                    <span class="badge" :class="statusClass(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>

    <div v-if="showProgramModal" class="modal-overlay">
      <div class="modal">
        <h3>{{ editingProgramId ? "Edit Program" : "Add Program" }}</h3>
        <div class="form-grid">
          <label>
            Program Name
            <input v-model="programForm.title" type="text" />
          </label>
          <label>
            Category
            <select v-model="programForm.category">
              <option value="Technical">Technical</option>
              <option value="Safety">Safety</option>
              <option value="Work Readiness">Work Readiness</option>
              <option value="Operations">Operations</option>
            </select>
          </label>
          <label>
            Trainer
            <input v-model="programForm.trainer" type="text" />
          </label>
          <label>
            Duration
            <input v-model="programForm.duration" type="text" placeholder="e.g. 4 weeks" />
          </label>
        </div>
        <div class="modal-actions">
          <button class="btn ghost" @click="closeProgramModal">Cancel</button>
          <button class="btn primary" @click="saveProgram">Save</button>
        </div>
      </div>
    </div>

    <div v-if="showEnrollModal" class="modal-overlay">
      <div class="modal">
        <h3>Enroll Individual</h3>
        <div class="form-grid">
          <label>
            Name
            <input v-model="enrollForm.name" type="text" />
          </label>
          <label>
            Program
            <select v-model="enrollForm.program">
              <option v-for="program in programs" :key="program.id" :value="program.title">
                {{ program.title }}
              </option>
            </select>
          </label>
          <label>
            Start Date
            <input v-model="enrollForm.startDate" type="date" />
          </label>
          <label>
            Target End Date
            <input v-model="enrollForm.endDate" type="date" />
          </label>
        </div>
        <div class="modal-actions">
          <button class="btn ghost" @click="closeEnrollModal">Cancel</button>
          <button class="btn primary" @click="saveEnrollment">Enroll</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import SidebarOpEmployer from "@/components/sb-employer-operator.vue"
import NavbarEmployer from "@/components/nv-employer.vue"

export default {
  name: "TrainingPrograms",
  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },
  data() {
    return {
      searchTerm: "",
      showProgramModal: false,
      showEnrollModal: false,
      editingProgramId: null,
      programForm: {
        title: "",
        category: "Technical",
        trainer: "",
        duration: ""
      },
      enrollForm: {
        name: "",
        program: "",
        startDate: "",
        endDate: ""
      },
      programs: [
        {
          id: 1,
          title: "Workplace Safety Basics",
          category: "Safety",
          trainer: "Angela Ramos",
          duration: "2 weeks",
          enrolled: 12,
          status: "Active"
        },
        {
          id: 2,
          title: "Customer Service Foundations",
          category: "Work Readiness",
          trainer: "Ryan Flores",
          duration: "3 weeks",
          enrolled: 9,
          status: "Active"
        },
        {
          id: 3,
          title: "Digital Productivity Tools",
          category: "Technical",
          trainer: "Carla Santos",
          duration: "4 weeks",
          enrolled: 15,
          status: "Inactive"
        }
      ],
      enrollmentList: [
        {
          id: 1,
          name: "Juan Dela Cruz",
          program: "Workplace Safety Basics",
          startDate: "2026-02-16",
          endDate: "2026-03-02",
          progress: 65,
          status: "In Progress"
        },
        {
          id: 2,
          name: "Maria Santos",
          program: "Customer Service Foundations",
          startDate: "2026-02-10",
          endDate: "2026-03-03",
          progress: 100,
          status: "Completed"
        }
      ]
    }
  },
  computed: {
    filteredPrograms() {
      const keyword = this.searchTerm.trim().toLowerCase()
      if (!keyword) return this.programs
      return this.programs.filter((program) => {
        return (
          program.title.toLowerCase().includes(keyword) ||
          program.trainer.toLowerCase().includes(keyword)
        )
      })
    }
  },
  methods: {
    statusClass(status) {
      if (status === "Completed" || status === "Active") return "success"
      if (status === "In Progress") return "info"
      return "muted"
    },
    openProgramModal() {
      this.editingProgramId = null
      this.programForm = {
        title: "",
        category: "Technical",
        trainer: "",
        duration: ""
      }
      this.showProgramModal = true
    },
    editProgram(program) {
      this.editingProgramId = program.id
      this.programForm = {
        title: program.title,
        category: program.category,
        trainer: program.trainer,
        duration: program.duration
      }
      this.showProgramModal = true
    },
    closeProgramModal() {
      this.showProgramModal = false
    },
    saveProgram() {
      if (!this.programForm.title || !this.programForm.trainer || !this.programForm.duration) return

      if (this.editingProgramId) {
        const row = this.programs.find((item) => item.id === this.editingProgramId)
        if (row) {
          row.title = this.programForm.title
          row.category = this.programForm.category
          row.trainer = this.programForm.trainer
          row.duration = this.programForm.duration
        }
      } else {
        this.programs.unshift({
          id: Date.now(),
          title: this.programForm.title,
          category: this.programForm.category,
          trainer: this.programForm.trainer,
          duration: this.programForm.duration,
          enrolled: 0,
          status: "Active"
        })
      }

      this.showProgramModal = false
    },
    toggleProgramStatus(program) {
      program.status = program.status === "Active" ? "Inactive" : "Active"
    },
    openEnrollModal() {
      this.enrollForm = {
        name: "",
        program: this.programs[0]?.title || "",
        startDate: "",
        endDate: ""
      }
      this.showEnrollModal = true
    },
    closeEnrollModal() {
      this.showEnrollModal = false
    },
    saveEnrollment() {
      if (!this.enrollForm.name || !this.enrollForm.program || !this.enrollForm.startDate) return

      this.enrollmentList.unshift({
        id: Date.now(),
        name: this.enrollForm.name,
        program: this.enrollForm.program,
        startDate: this.enrollForm.startDate,
        endDate: this.enrollForm.endDate || "-",
        progress: 0,
        status: "Not Started"
      })

      const program = this.programs.find((item) => item.title === this.enrollForm.program)
      if (program) program.enrolled += 1

      this.showEnrollModal = false
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

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.page-header h2 {
  margin: 0;
  color: #0f172a;
}

.subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.header-actions {
  display: flex;
  gap: 8px;
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
  margin-bottom: 12px;
  gap: 10px;
}

.panel-head h3 {
  margin: 0;
  font-size: 16px;
}

.panel-head input {
  width: 280px;
  max-width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
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
  display: inline-block;
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

.badge.muted {
  background: #f1f5f9;
  color: #475569;
}

.actions {
  white-space: nowrap;
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

.btn.ghost {
  background: #e2e8f0;
  color: #1f2937;
}

.btn-link {
  border: none;
  background: none;
  color: #2563eb;
  cursor: pointer;
  font-size: 12px;
  padding: 0 6px;
}

.btn-link.danger {
  color: #b91c1c;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.4);
  display: grid;
  place-items: center;
  padding: 14px;
  z-index: 60;
}

.modal {
  background: #ffffff;
  width: min(560px, 95vw);
  border-radius: 14px;
  border: 1px solid #dbe2ea;
  padding: 16px;
}

.modal h3 {
  margin: 0 0 12px;
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

.form-grid input,
.form-grid select {
  margin-top: 4px;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 10px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 14px;
}

@media (max-width: 900px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
