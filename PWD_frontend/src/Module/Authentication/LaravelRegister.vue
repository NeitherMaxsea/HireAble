<template>
  <section class="auth-page">
    <div class="card">
      <h1>Register</h1>
      <p class="subtitle">Create account using Laravel backend.</p>

      <label>Full Name</label>
      <input v-model.trim="name" type="text" placeholder="Juan Dela Cruz" />

      <label>Email</label>
      <input v-model.trim="email" type="email" placeholder="you@example.com" />

      <label>Password</label>
      <input v-model="password" type="password" placeholder="At least 8 characters" />

      <label>Role</label>
      <select v-model="role">
        <option value="applicant">Applicant</option>
        <option value="admin">Admin</option>
      </select>

      <button class="btn" :disabled="loading" @click="register">
        {{ loading ? "Creating..." : "Create Account" }}
      </button>

      <p class="link-line">
        Already have account?
        <router-link to="/login">Login</router-link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import api from "@/services/api"

const router = useRouter()
const name = ref("")
const email = ref("")
const password = ref("")
const role = ref("applicant")
const loading = ref(false)

const register = async () => {
  if (!name.value || !email.value || !password.value) {
    alert("Please complete all required fields.")
    return
  }

  loading.value = true
  try {
    await api.post("/auth/register", {
      name: name.value,
      email: email.value,
      password: password.value,
      role: role.value,
    })
    alert("Registration successful. Please login.")
    router.replace("/login")
  } catch (error) {
    alert(error?.response?.data?.message || "Registration failed.")
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

input,
select {
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
