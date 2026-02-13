<template>
  <section class="auth-page">
    <div class="card">
      <h1>Login</h1>
      <p class="subtitle">Sign in using your Laravel account.</p>

      <label>Email</label>
      <input v-model.trim="email" type="email" placeholder="you@example.com" />

      <label>Password</label>
      <input v-model="password" type="password" placeholder="Enter password" />

      <button class="btn" :disabled="loading" @click="login">
        {{ loading ? "Signing in..." : "Login" }}
      </button>

      <p class="link-line">
        No account yet?
        <router-link :to="{ path: '/register', query: { force: '1' } }">Register</router-link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import api from "@/services/api"
import { auth } from "@/lib/client-platform"

const router = useRouter()
const email = ref("")
const password = ref("")
const loading = ref(false)

const login = async () => {
  if (!email.value || !password.value) {
    alert("Please enter email and password.")
    return
  }

  loading.value = true
  try {
    const res = await api.post("/auth/login", {
      email: email.value,
      password: password.value,
    })
    const user = res.data?.user
    if (!user?.id) {
      throw new Error("Invalid login response")
    }

    localStorage.setItem("userUid", String(user.id))
    localStorage.setItem("uid", String(user.id))
    localStorage.setItem("userName", user.username || user.name || "User")
    localStorage.setItem("userEmail", user.email || "")
    localStorage.setItem("userRole", user.role || "")
    localStorage.setItem("companyId", user.companyId || "")
    localStorage.setItem("companyName", user.companyName || "")

    auth.currentUser = { uid: String(user.id), email: user.email || "" }

    const role = String(user.role || "").toLowerCase()
    if (role === "admin") {
      router.replace("/admin/dashboard")
    } else if (role === "applicant") {
      router.replace("/applicant/job_list")
    } else {
      router.replace("/landingpage")
    }
  } catch (error) {
    alert(error?.response?.data?.message || "Login failed.")
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: linear-gradient(160deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 16px;
}

.card {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.1);
}

h1 {
  margin: 0 0 8px;
}

.subtitle {
  margin: 0 0 18px;
  color: #64748b;
}

label {
  display: block;
  margin: 10px 0 6px;
  font-size: 13px;
  font-weight: 600;
}

input {
  width: 100%;
  height: 42px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 0 12px;
  box-sizing: border-box;
}

.btn {
  width: 100%;
  margin-top: 16px;
  height: 42px;
  border: 0;
  border-radius: 10px;
  background: #0f172a;
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.link-line {
  margin: 14px 0 0;
  font-size: 14px;
}
</style>

