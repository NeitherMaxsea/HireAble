<template>
  <section class="dashboard">
    <div class="hero">
      <div>
        <h2>Welcome, Company Admin</h2>
        <p>Control your organization roles, team access, and workforce setup from one place.</p>
      </div>
      <router-link class="cta" to="/company-admin/add-employee">
        <i class="bi bi-person-plus"></i>
        <span>Add Employee</span>
      </router-link>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-head">
          <span>Company</span>
          <i class="bi bi-buildings"></i>
        </div>
        <strong class="value">{{ companyName }}</strong>
      </div>
      <div class="card">
        <div class="card-head">
          <span>Company ID</span>
          <i class="bi bi-fingerprint"></i>
        </div>
        <strong class="value mono">{{ companyId || "-" }}</strong>
      </div>
      <div class="card">
        <div class="card-head">
          <span>Default Employee Roles</span>
          <i class="bi bi-diagram-3"></i>
        </div>
        <strong class="value">HR, Finance, Operation</strong>
      </div>
    </div>

    <article class="profile-card">
      <div class="profile-head">
        <h3>Company Profile</h3>
        <span class="profile-badge">From Registration</span>
      </div>
      <div class="profile-grid">
        <div class="profile-item">
          <span>Admin Name</span>
          <strong>{{ adminName || "-" }}</strong>
        </div>
        <div class="profile-item">
          <span>Admin Email</span>
          <strong>{{ adminEmail || "-" }}</strong>
        </div>
        <div class="profile-item">
          <span>Company Name</span>
          <strong>{{ companyName || "-" }}</strong>
        </div>
        <div class="profile-item">
          <span>Company Address</span>
          <strong>{{ companyAddress || "-" }}</strong>
        </div>
        <div class="profile-item">
          <span>Industry</span>
          <strong>{{ companyIndustry || "-" }}</strong>
        </div>
      </div>
    </article>

    <div class="panel-grid">
      <article class="panel">
        <h3>How Team Provisioning Works</h3>
        <ul>
          <li>Create employee credentials with temporary password.</li>
          <li>Assign role per user: HR, Finance, or Operation.</li>
          <li>Users login and are redirected to their role-specific workspace.</li>
        </ul>
      </article>

      <article class="panel accent">
        <h3>Security Reminder</h3>
        <p>
          Use strong temporary passwords and share credentials through secure channels.
          Advise employees to change password immediately after first sign-in.
        </p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { auth, db } from "@/lib/client-platform"
import { doc, getDoc } from "@/lib/laravel-data"

const companyName = ref(localStorage.getItem("companyName") || "My Company")
const companyId = ref(localStorage.getItem("companyId") || "")
const adminName = ref(localStorage.getItem("userName") || "Company Admin")
const adminEmail = ref(localStorage.getItem("userEmail") || "")
const companyAddress = ref(localStorage.getItem("companyAddress") || "")
const companyIndustry = ref(localStorage.getItem("companyIndustry") || "")

onMounted(() => {
  void hydrateProfile()
})

async function hydrateProfile() {
  const uid = auth.currentUser?.uid || localStorage.getItem("uid") || localStorage.getItem("userUid")
  if (!uid) return

  const candidates = ["users", "Users"]
  for (const collectionName of candidates) {
    try {
      const snap = await getDoc(doc(db, collectionName, uid))
      if (!snap.exists()) continue
      const profile = snap.data() || {}

      adminName.value =
        String(profile.name || profile.username || localStorage.getItem("userName") || "Company Admin")
      adminEmail.value = String(profile.email || localStorage.getItem("userEmail") || "")
      companyName.value = String(profile.companyName || localStorage.getItem("companyName") || "My Company")
      companyId.value = String(profile.companyId || localStorage.getItem("companyId") || "")
      companyAddress.value = String(profile.companyAddress || localStorage.getItem("companyAddress") || "")
      companyIndustry.value = String(profile.companyIndustry || localStorage.getItem("companyIndustry") || "")

      localStorage.setItem("userName", adminName.value)
      localStorage.setItem("userEmail", adminEmail.value)
      localStorage.setItem("companyName", companyName.value)
      localStorage.setItem("companyId", companyId.value)
      localStorage.setItem("companyAddress", companyAddress.value)
      localStorage.setItem("companyIndustry", companyIndustry.value)
      return
    } catch {
      // try next collection fallback
    }
  }
}
</script>

<style scoped>
.dashboard {
  display: grid;
  gap: 14px;
}

.hero {
  background: linear-gradient(135deg, #1d4ed8, #2563eb 55%, #3b82f6);
  color: #fff;
  border-radius: 18px;
  padding: 22px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  box-shadow: 0 16px 30px rgba(37, 99, 235, 0.25);
}

.hero h2 {
  margin: 0;
  font-size: 24px;
}

.hero p {
  margin: 7px 0 0;
  color: #dbeafe;
  max-width: 620px;
}

.cta {
  text-decoration: none;
  color: #1e3a8a;
  background: #fff;
  border-radius: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border: 1px solid rgba(255, 255, 255, 0.65);
  white-space: nowrap;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 10px;
}

.profile-card {
  background: #fff;
  border: 1px solid #dbe3f4;
  border-radius: 14px;
  padding: 14px;
}

.profile-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.profile-head h3 {
  margin: 0;
  color: #0f172a;
}

.profile-badge {
  font-size: 11px;
  font-weight: 700;
  color: #1e40af;
  background: #dbeafe;
  border-radius: 999px;
  padding: 4px 10px;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.profile-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px;
  background: #f8fafc;
}

.profile-item span {
  display: block;
  font-size: 12px;
  color: #64748b;
}

.profile-item strong {
  display: block;
  margin-top: 5px;
  color: #0f172a;
  font-size: 14px;
  line-height: 1.4;
  word-break: break-word;
}

.card {
  background: #fff;
  border: 1px solid #dbe3f4;
  border-radius: 14px;
  padding: 14px;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-head span {
  font-size: 12px;
  color: #64748b;
}

.card-head i {
  color: #1e40af;
}

.value {
  display: block;
  margin-top: 8px;
  color: #0f172a;
  font-size: 17px;
  line-height: 1.3;
}

.mono {
  font-family: Consolas, monospace;
  font-size: 13px;
}

.panel-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 10px;
}

.panel {
  background: #fff;
  border: 1px solid #dbe3f4;
  border-radius: 14px;
  padding: 16px;
}

.panel h3 {
  margin: 0 0 10px;
  color: #0f172a;
}

.panel ul {
  margin: 0;
  padding-left: 18px;
  color: #334155;
  display: grid;
  gap: 8px;
}

.panel p {
  margin: 0;
  color: #334155;
  line-height: 1.6;
}

.panel.accent {
  background: linear-gradient(180deg, #fff 0%, #eff6ff 100%);
}

@media (max-width: 900px) {
  .hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .panel-grid {
    grid-template-columns: 1fr;
  }
}
</style>
