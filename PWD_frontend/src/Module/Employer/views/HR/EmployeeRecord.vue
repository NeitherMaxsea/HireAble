<template>
  <div class="app">
    <div class="main-wrapper">
      <main>
        <section class="content">
          <div class="page-header">
            <div>
              <h2>Employee Management</h2>
              <p class="subtitle">Manage all employee information here.</p>
            </div>
          </div>

          <div class="controls">
            <input
              v-model="search"
              type="text"
              class="search"
              placeholder="Search employee..."
            >
          </div>

          <p v-if="loadError" class="error">{{ loadError }}</p>

          <div class="card">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Application Date</th>
                  <th>Gender</th>
                  <th>Status</th>
                  <th>Position</th>
                  <th style="text-align:center;">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="loading">
                  <td colspan="7" class="center">Loading records...</td>
                </tr>

                <tr v-else-if="filteredEmployees.length === 0">
                  <td colspan="7" class="center">No employee records found.</td>
                </tr>

                <tr v-for="employee in filteredEmployees" :key="employee.id">
                  <td>
                    <strong>{{ employee.name }}</strong><br>
                    <small>{{ employee.licenseLabel }}</small>
                  </td>

                  <td>
                    {{ employee.contact }}<br>
                    <small>{{ employee.email }}</small>
                  </td>

                  <td>
                    <span class="badge" :class="statusClass(employee.status)">
                      {{ employee.statusLabel }}
                    </span><br>
                    <small>{{ employee.applicationDateLabel }}</small>
                  </td>

                  <td>{{ employee.gender }}</td>
                  <td>{{ employee.civilStatus }}</td>

                  <td>
                    {{ employee.position }}<br>
                    <small>{{ employee.department }}</small>
                  </td>

                  <td class="center">
                    <button class="icon-btn" type="button">...</button>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="table-footer">
              <p>Showing {{ filteredEmployees.length }} result(s)</p>

              <div class="pagination">
                <span>Items per page</span>
                <select disabled>
                  <option>5</option>
                  <option>10</option>
                  <option>20</option>
                </select>

                <button type="button" disabled>&lt;&lt;</button>
                <button type="button" disabled>&lt;</button>
                <button type="button" disabled>&gt;</button>
                <button type="button" disabled>&gt;&gt;</button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<script>
import { auth, db } from "@/lib/client-platform"
import { collection, doc, getDoc, onSnapshot, query, where } from "@/lib/laravel-data"

export default {
  name: "EmployeeRecords",

  data() {
    return {
      search: "",
      loading: true,
      loadError: "",
      employees: [],
      stopSync: null
    }
  },

  computed: {
    filteredEmployees() {
      const term = String(this.search || "").trim().toLowerCase()
      if (!term) return this.employees

      return this.employees.filter((employee) => {
        return (
          String(employee.name || "").toLowerCase().includes(term) ||
          String(employee.email || "").toLowerCase().includes(term) ||
          String(employee.contact || "").toLowerCase().includes(term) ||
          String(employee.position || "").toLowerCase().includes(term) ||
          String(employee.department || "").toLowerCase().includes(term)
        )
      })
    }
  },

  async mounted() {
    await this.bootstrapRecords()
  },

  beforeUnmount() {
    if (typeof this.stopSync === "function") {
      this.stopSync()
      this.stopSync = null
    }
  },

  methods: {
    async bootstrapRecords() {
      this.loading = true
      this.loadError = ""

      const uid = auth.currentUser?.uid
      if (!uid) {
        this.loading = false
        this.loadError = "Session expired. Please login again."
        return
      }

      try {
        const profile = await this.getCurrentUserProfile(uid)
        const sourceCollection = profile?.sourceCollection || "users"
        const companyId = String(
          profile?.companyId || localStorage.getItem("companyId") || ""
        ).trim()

        this.subscribeEmployees(sourceCollection, companyId)
      } catch (err) {
        console.error(err)
        this.loading = false
        this.loadError = "Failed to load employee records from Laravel."
      }
    },

    async getCurrentUserProfile(uid) {
      const lower = await getDoc(doc(db, "users", uid))
      if (lower.exists()) {
        return {
          sourceCollection: "users",
          ...lower.data()
        }
      }

      const upper = await getDoc(doc(db, "Users", uid))
      if (upper.exists()) {
        return {
          sourceCollection: "Users",
          ...upper.data()
        }
      }

      return null
    },

    subscribeEmployees(usersCollectionName, companyId) {
      if (typeof this.stopSync === "function") {
        this.stopSync()
        this.stopSync = null
      }

      const baseCollection = collection(db, usersCollectionName)
      const q = companyId
        ? query(baseCollection, where("companyId", "==", companyId))
        : query(baseCollection)

      this.stopSync = onSnapshot(
        q,
        (snapshot) => {
          this.employees = snapshot.docs
            .map((docSnap) => this.normalizeEmployee(docSnap.id, docSnap.data()))
            .filter((employee) => this.includeInEmployeeRecords(employee))
            .sort((a, b) => b.createdAtMs - a.createdAtMs)
          this.loading = false
          this.loadError = ""
        },
        (err) => {
          console.error(err)
          this.loading = false
          this.loadError =
            err?.code === "permission-denied"
              ? "Permission denied in Laravel rules for employee records."
              : "Realtime sync failed. Please check Laravel connection."
        }
      )
    },

    includeInEmployeeRecords(employee) {
      const role = String(employee.role || "").toLowerCase()
      if (!role) return true
      if (["admin", "company_admin", "applicant"].includes(role)) return false
      return true
    },

    normalizeEmployee(id, raw) {
      const createdAtMs = this.toMillis(
        raw.createdAt || raw.applicationDate || raw.hiredAt || raw.temporaryPasswordIssuedAt
      )

      const status = String(
        raw.employmentStatus ||
        raw.status ||
        (raw.isActive === false ? "inactive" : "active")
      ).toLowerCase()

      const applicationDate = raw.applicationDate || raw.createdAt || raw.hiredAt

      return {
        id,
        role: String(raw.role || ""),
        name: String(raw.username || raw.name || raw.displayName || raw.fullName || "Unnamed"),
        licenseLabel: this.buildLicenseLabel(raw),
        contact: String(raw.phone || raw.mobile || raw.contactNumber || "N/A"),
        email: String(raw.email || "N/A"),
        applicationDateLabel: this.formatDate(applicationDate),
        createdAtMs,
        gender: String(raw.gender || "Not specified"),
        civilStatus: String(raw.civilStatus || raw.maritalStatus || "Not specified"),
        status,
        statusLabel: this.formatStatus(status),
        position: String(raw.position || raw.jobTitle || raw.role || "Not specified"),
        department: String(raw.department || raw.companyName || "Not specified")
      }
    },

    buildLicenseLabel(raw) {
      const value = String(raw.licenseNo || raw.pwdId || raw.employeeId || "").trim()
      return value ? `License No: ${value}` : "License No: N/A"
    },

    toMillis(value) {
      if (!value) return 0
      if (typeof value?.toMillis === "function") return value.toMillis()
      if (typeof value?.seconds === "number") return value.seconds * 1000
      if (value instanceof Date) return value.getTime()

      const parsed = Date.parse(String(value))
      return Number.isNaN(parsed) ? 0 : parsed
    },

    formatDate(value) {
      const ms = this.toMillis(value)
      if (!ms) return "N/A"

      return new Date(ms).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric"
      })
    },

    formatStatus(status) {
      const value = String(status || "").toLowerCase()
      if (!value) return "Unknown"
      return value.charAt(0).toUpperCase() + value.slice(1)
    },

    statusClass(status) {
      const value = String(status || "").toLowerCase()
      if (["active", "open", "employed"].includes(value)) return "active"
      if (["on leave", "leave", "pending"].includes(value)) return "leave"
      return "inactive"
    }
  }
}
</script>

<style scoped>
.app{
  display:flex;
  min-height:100vh;
  background:#f5f7fb;
}

.main-wrapper{
  flex:1;
  display:flex;
  flex-direction:column;
}

main{
  flex:1;
}

.content{
  padding:25px;
}

.page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:15px;
}

.subtitle{
  font-size:13px;
  color:#6b7280;
}

.controls{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:15px;
}

.search{
  padding:8px 12px;
  width:220px;
  border-radius:8px;
  border:1px solid #d1d5db;
}

.search:focus{
  outline:none;
  border-color:#2563eb;
}

.error{
  color:#b91c1c;
  font-size:13px;
  margin-bottom:10px;
}

.card{
  background:white;
  padding:20px;
  border-radius:12px;
  box-shadow:0 2px 6px rgba(0,0,0,0.04);
}

table{
  width:100%;
  border-collapse:collapse;
}

th{
  font-size:13px;
  color:#6b7280;
}

th, td{
  padding:12px;
  border-bottom:1px solid #e5e7eb;
  text-align:left;
}

small{
  color:#6b7280;
  font-size:12px;
}

.center{
  text-align:center;
}

.badge{
  padding:4px 10px;
  border-radius:20px;
  font-size:12px;
}

.active{
  background:#bbf7d0;
  color:#166534;
}

.leave{
  background:#fde68a;
  color:#92400e;
}

.inactive{
  background:#e5e7eb;
  color:#374151;
}

.icon-btn{
  border:none;
  background:transparent;
  font-size:18px;
  cursor:pointer;
}

.table-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-top:15px;
  font-size:13px;
}

.pagination{
  display:flex;
  align-items:center;
  gap:8px;
}

.pagination select,
.pagination button{
  padding:5px 8px;
  border-radius:6px;
  border:1px solid #d1d5db;
  background:white;
  cursor:pointer;
}

.pagination select:disabled,
.pagination button:disabled{
  opacity:0.6;
  cursor:not-allowed;
}
</style>


