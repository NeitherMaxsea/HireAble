<template>
  <section class="company-page">
    <header class="page-header">
      <h2>Company Management</h2>
      <p>View registered companies, account email, and department totals.</p>
    </header>

    <div v-if="loading && !companies.length" class="state-card">
      <i class="bi bi-hourglass-split"></i>
      <p>Loading companies...</p>
    </div>

    <div v-else-if="loadError && !companies.length" class="state-card error">
      <i class="bi bi-exclamation-triangle"></i>
      <p>{{ loadError }}</p>
    </div>

    <div v-else-if="!companies.length" class="state-card">
      <i class="bi bi-building"></i>
      <p>No company records found.</p>
    </div>

    <div v-else class="layout-grid">
      <aside class="company-list">
        <button
          v-for="company in companies"
          :key="company.companyId"
          class="company-item"
          :class="{ active: selectedCompanyId === company.companyId }"
          @click="selectedCompanyId = company.companyId"
        >
          <div class="name-row">
            <strong>{{ company.companyName }}</strong>
            <small>{{ company.companyId }}</small>
          </div>
          <p class="email">{{ company.contactEmail }}</p>
        </button>
      </aside>

      <section class="company-detail" v-if="selectedCompany">
        <div class="detail-head">
          <h3>{{ selectedCompany.companyName }}</h3>
          <p><strong>Email:</strong> {{ selectedCompany.contactEmail }}</p>
          <p><strong>Company ID:</strong> {{ selectedCompany.companyId }}</p>
        </div>

        <div class="count-grid">
          <article class="count-card">
            <span>HR</span>
            <strong>{{ selectedCompany.hrCount }}</strong>
          </article>

          <article class="count-card">
            <span>Operation</span>
            <strong>{{ selectedCompany.operationCount }}</strong>
          </article>

          <article class="count-card">
            <span>Financial</span>
            <strong>{{ selectedCompany.financeCount }}</strong>
          </article>
        </div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { collection, getDocs, onSnapshot, query } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"

const companies = ref([])
const selectedCompanyId = ref("")
const loading = ref(true)
const loadError = ref("")
const unsubscribers = []

const selectedCompany = computed(() => {
  return companies.value.find((company) => company.companyId === selectedCompanyId.value) || null
})

onMounted(() => {
  void initCompanyData()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((fn) => fn && fn())
})

async function initCompanyData() {
  loading.value = true
  loadError.value = ""

  const first = await fetchUsersOnce()
  setCompanies(first.list)
  loading.value = false

  if (!first.list.length && first.error) {
    loadError.value = "Cannot read companies. Check admin access and Laravel Rules."
  }

  subscribeUsersRealtime(first.source)
}

async function fetchUsersOnce() {
  const candidates = ["users", "Users"]
  let lastError = null

  for (const collectionName of candidates) {
    try {
      const snap = await getDocs(query(collection(db, collectionName)))
      const list = normalizeUsers(snap.docs.map((docSnap) => docSnap.data()))
      if (list.length) {
        return { list, source: collectionName, error: null }
      }
    } catch (err) {
      lastError = err
    }
  }

  return { list: [], source: "users", error: lastError }
}

function subscribeUsersRealtime(source) {
  const targets = source === "Users" ? ["Users", "users"] : ["users", "Users"]

  targets.forEach((collectionName) => {
    const unsub = onSnapshot(
      query(collection(db, collectionName)),
      (snapshot) => {
        const list = normalizeUsers(snapshot.docs.map((docSnap) => docSnap.data()))
        if (list.length || !companies.value.length) {
          setCompanies(list)
          loadError.value = ""
        }
      },
      () => {
        if (!companies.value.length) {
          loadError.value = "Realtime sync blocked. Showing latest available data."
        }
      }
    )

    unsubscribers.push(unsub)
  })
}

function normalizeUsers(rawUsers) {
  return rawUsers
    .map((item) => ({
      companyId: String(item.companyId || "").trim(),
      companyName: String(item.companyName || "").trim(),
      email: String(item.email || "").trim().toLowerCase(),
      role: normalizeRole(item.role),
    }))
    .filter((user) => user.companyId && ["company_admin", "hr", "operation", "finance"].includes(user.role))
}

function normalizeRole(role) {
  const value = String(role || "").trim().toLowerCase().replace(/[\s_]+/g, " ")
  if (value === "company admin") return "company_admin"
  if (value === "hr" || value === "hr department") return "hr"
  if (value === "operation" || value === "operation department") return "operation"
  if (value === "finance" || value === "financial" || value === "financial department") return "finance"
  return value.replace(/\s+/g, "_")
}

function setCompanies(users) {
  const companyMap = new Map()

  users.forEach((user) => {
    if (!companyMap.has(user.companyId)) {
      companyMap.set(user.companyId, {
        companyId: user.companyId,
        companyName: user.companyName || `Company ${user.companyId}`,
        contactEmail: user.email || "-",
        hrCount: 0,
        operationCount: 0,
        financeCount: 0,
      })
    }

    const record = companyMap.get(user.companyId)
    if (!record) return

    if (!record.contactEmail || record.contactEmail === "-") {
      record.contactEmail = user.email || "-"
    }

    if (user.role === "company_admin" && user.email) {
      record.contactEmail = user.email
    } else if (user.role === "hr") {
      record.hrCount += 1
    } else if (user.role === "operation") {
      record.operationCount += 1
    } else if (user.role === "finance") {
      record.financeCount += 1
    }
  })

  const next = Array.from(companyMap.values()).sort((a, b) =>
    a.companyName.localeCompare(b.companyName)
  )

  companies.value = next

  if (!next.length) {
    selectedCompanyId.value = ""
    return
  }

  const stillExists = next.some((company) => company.companyId === selectedCompanyId.value)
  if (!stillExists) {
    selectedCompanyId.value = next[0].companyId
  }
}
</script>

<style scoped>
.company-page {
  padding: 10px;
}

.page-header h2 {
  margin: 0;
}

.page-header p {
  margin: 8px 0 16px;
  color: #64748b;
}

.layout-grid {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 14px;
}

.company-list {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 8px;
  display: grid;
  gap: 8px;
  align-content: start;
  max-height: 72vh;
  overflow: auto;
}

.company-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  text-align: left;
  padding: 12px;
  cursor: pointer;
}

.company-item.active {
  border-color: #bfdbfe;
  background: #eff6ff;
}

.name-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.name-row strong {
  color: #0f172a;
}

.name-row small {
  color: #64748b;
}

.email {
  margin: 8px 0 0;
  color: #334155;
  font-size: 13px;
}

.company-detail {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}

.detail-head h3 {
  margin: 0 0 10px;
}

.detail-head p {
  margin: 4px 0;
  color: #334155;
}

.count-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(3, minmax(150px, 1fr));
  gap: 10px;
}

.count-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  padding: 12px;
}

.count-card span {
  display: block;
  color: #64748b;
  font-size: 12px;
}

.count-card strong {
  display: block;
  margin-top: 6px;
  font-size: 26px;
  color: #0f172a;
}

.state-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #64748b;
}

.state-card i {
  font-size: 2rem;
  margin-bottom: 8px;
}

.state-card.error {
  color: #b91c1c;
}

@media (max-width: 1024px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }

  .company-list {
    max-height: 40vh;
  }

  .count-grid {
    grid-template-columns: 1fr;
  }
}
</style>
