<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Add Employee</h2>
        <p class="subtitle">Create company employee accounts and manage active staff records.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadEmployees">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </div>

    <div class="card form-card">
      <h3>Create Employee Account</h3>
      <p v-if="formError" class="error">{{ formError }}</p>

      <form class="form-grid" @submit.prevent="createEmployee">
        <label class="field">
          <span>Full Name</span>
          <input v-model.trim="form.username" type="text" placeholder="Enter employee name" required />
        </label>

        <label class="field">
          <span>Email</span>
          <input v-model.trim="form.email" type="email" placeholder="Enter employee email" required />
        </label>

        <label class="field">
          <span>Role</span>
          <select v-model="form.role" required>
            <option value="hr">HR Department</option>
            <option value="operation">Operation Department</option>
            <option value="finance">Finance Department</option>
          </select>
        </label>

        <label class="field">
          <span>Temporary Password</span>
          <input
            v-model="form.password"
            type="password"
            minlength="8"
            placeholder="At least 8 characters"
            required
          />
        </label>

        <label class="field">
          <span>Position</span>
          <input v-model.trim="form.position" type="text" placeholder="Position title" />
        </label>

        <label class="field">
          <span>Department Label</span>
          <input v-model.trim="form.department" type="text" placeholder="Optional department label" />
        </label>

        <div class="actions full">
          <button type="submit" class="btn primary" :disabled="saving">
            {{ saving ? "Creating..." : "Create Employee" }}
          </button>
          <button type="button" class="btn ghost" :disabled="saving" @click="resetForm">Reset</button>
        </div>
      </form>
    </div>

    <div class="filters">
      <input
        v-model.trim="search"
        type="text"
        class="search"
        placeholder="Search by name, email, or position"
      />
      <select v-model="roleFilter" class="role-filter">
        <option value="all">All roles</option>
        <option value="hr">HR</option>
        <option value="operation">Operation</option>
        <option value="finance">Finance</option>
      </select>
    </div>

    <div class="card">
      <table v-if="filteredEmployees.length > 0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Position</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in filteredEmployees" :key="employee.id">
            <td>
              <strong>{{ employee.username }}</strong><br />
              <small>ID: {{ employee.id }}</small>
            </td>
            <td>{{ employee.email }}</td>
            <td>
              <span class="badge role" :class="employee.role">{{ roleLabel(employee.role) }}</span>
            </td>
            <td>{{ employee.position || "-" }}</td>
            <td>
              <span class="badge status" :class="employee.status">{{ statusLabel(employee.status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty">{{ loading ? "Loading employees..." : "No employee records found." }}</div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const loading = ref(false)
const saving = ref(false)
const search = ref("")
const roleFilter = ref("all")
const formError = ref("")
const employees = ref([])

const form = reactive({
  username: "",
  email: "",
  role: "hr",
  password: "",
  position: "",
  department: "",
})

const filteredEmployees = computed(() => {
  const needle = search.value.toLowerCase()
  return employees.value
    .filter((item) => roleFilter.value === "all" || item.role === roleFilter.value)
    .filter((item) => {
      if (!needle) return true
      return (
        item.username.toLowerCase().includes(needle) ||
        item.email.toLowerCase().includes(needle) ||
        String(item.position || "").toLowerCase().includes(needle)
      )
    })
})

function notify(text, color = "#2563eb") {
  Toastify({
    text,
    backgroundColor: color,
    duration: 2600,
    gravity: "top",
    position: "right",
    close: true,
  }).showToast()
}

function normalizeRole(value) {
  const role = String(value || "").trim().toLowerCase()
  if (role === "hr" || role === "operation" || role === "finance") return role
  return "hr"
}

function normalizeStatus(value) {
  const status = String(value || "").trim().toLowerCase()
  if (status === "inactive" || status === "suspended") return status
  return "active"
}

function roleLabel(value) {
  const role = normalizeRole(value)
  if (role === "operation") return "Operation"
  if (role === "finance") return "Finance"
  return "HR"
}

function statusLabel(value) {
  const status = normalizeStatus(value)
  return `${status.charAt(0).toUpperCase()}${status.slice(1)}`
}

function resolveCompanyScope() {
  return String(localStorage.getItem("companyId") || "").trim()
}

function resetForm() {
  form.username = ""
  form.email = ""
  form.role = "hr"
  form.password = ""
  form.position = ""
  form.department = ""
  formError.value = ""
}

async function loadEmployees() {
  loading.value = true
  try {
    const companyId = resolveCompanyScope()
    const response = await api.get("/users", { params: companyId ? { companyId } : {} })
    const rows = Array.isArray(response?.data) ? response.data : []

    employees.value = rows
      .map((row) => ({
        id: String(row?.id || ""),
        username: String(row?.username || row?.name || "User"),
        email: String(row?.email || ""),
        role: normalizeRole(row?.role),
        status: normalizeStatus(row?.status),
        position: String(row?.position || ""),
      }))
      .sort((a, b) => a.username.localeCompare(b.username))
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to load employees.", "#dc2626")
  } finally {
    loading.value = false
  }
}

async function createEmployee() {
  formError.value = ""
  if (!form.username || !form.email || !form.password) {
    formError.value = "Please complete required fields."
    return
  }
  if (form.password.length < 8) {
    formError.value = "Password must be at least 8 characters."
    return
  }

  saving.value = true
  try {
    const companyId = resolveCompanyScope()
    const payload = {
      username: form.username,
      name: form.username,
      email: form.email.toLowerCase(),
      password: form.password,
      role: normalizeRole(form.role),
      status: "active",
      isActive: true,
      companyId: companyId || null,
      companyName: String(localStorage.getItem("companyName") || "").trim() || null,
      position: form.position || null,
      department: form.department || roleLabel(form.role),
    }

    const response = await api.post("/users", payload)
    const created = response?.data || {}

    employees.value = [
      {
        id: String(created?.id || ""),
        username: String(created?.username || created?.name || form.username),
        email: String(created?.email || form.email).toLowerCase(),
        role: normalizeRole(created?.role || form.role),
        status: normalizeStatus(created?.status),
        position: String(created?.position || form.position || ""),
      },
      ...employees.value,
    ]

    notify("Employee account created.", "#16a34a")
    resetForm()
  } catch (err) {
    console.error(err)
    formError.value = err?.response?.data?.message || "Failed to create employee."
    notify(formError.value, "#dc2626")
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  void loadEmployees()
})
</script>

<style scoped>
.content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.subtitle {
  font-size: 13px;
  color: #64748b;
}

.refresh-btn {
  border: none;
  border-radius: 10px;
  padding: 9px 14px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.refresh-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  overflow-x: auto;
}

.form-card h3 {
  margin: 0 0 12px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field span {
  font-size: 12px;
  color: #6b7280;
}

.field input,
.field select {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 14px;
}

.actions {
  display: flex;
  gap: 8px;
}

.full {
  grid-column: 1 / -1;
}

.btn {
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
}

.btn.primary {
  background: #2563eb;
}

.btn.ghost {
  background: #64748b;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.search,
.role-filter {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  background: #ffffff;
  font-size: 14px;
}

.search {
  width: min(420px, 100%);
  flex: 1 1 320px;
}

.role-filter {
  min-width: 170px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  vertical-align: middle;
  padding: 11px 10px;
  font-size: 14px;
}

th {
  color: #6b7280;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

small {
  color: #64748b;
  font-size: 12px;
}

.badge {
  display: inline-flex;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.badge.role.hr {
  background: #dbeafe;
  color: #1e40af;
}

.badge.role.operation {
  background: #dcfce7;
  color: #166534;
}

.badge.role.finance {
  background: #ede9fe;
  color: #5b21b6;
}

.badge.status.active {
  background: #dcfce7;
  color: #166534;
}

.badge.status.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.badge.status.suspended {
  background: #ffedd5;
  color: #9a3412;
}

.error {
  margin: 0 0 10px;
  color: #b91c1c;
  font-size: 13px;
}

.empty {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .refresh-btn {
    width: 100%;
  }
}
</style>
