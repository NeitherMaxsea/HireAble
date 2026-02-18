import 'bootstrap-icons/font/bootstrap-icons.css'
import './tailwind.css'
import { createApp } from 'vue'
import App from './App.vue'
import router from './Module/routes'
import api from './services/api'
import { clearLocalSession } from './lib/session-auth'
import Toastify from 'toastify-js'
import 'toastify-js/src/toastify.css'

let pingInFlight = false

function readSessionPayload() {
  const uid = String(localStorage.getItem('uid') || localStorage.getItem('userUid') || '').trim()
  const sessionKey = String(localStorage.getItem('activeSessionId') || '').trim()
  const accountType = String(localStorage.getItem('userCollection') || 'users').trim().toLowerCase() === 'admins'
    ? 'admins'
    : 'users'
  if (!uid || !sessionKey) return null
  return { uid, sessionKey, accountType }
}

async function pingSessionLock() {
  const payload = readSessionPayload()
  if (!payload || pingInFlight) return

  pingInFlight = true
  try {
    await api.post('/auth/session/ping', payload)
  } catch (error) {
    const status = error?.response?.status
    if (status === 409 || status === 404) {
      const message = error?.response?.data?.message || 'This account is now in use on another device.'
      clearLocalSession()
      Toastify({ text: message, backgroundColor: '#e74c3c', duration: 3500 }).showToast()
      if (router.currentRoute.value.path !== '/login') {
        await router.replace({ path: '/login', query: { force: '1' } })
      }
    }
  } finally {
    pingInFlight = false
  }
}

function startSessionHeartbeat() {
  setInterval(() => {
    void pingSessionLock()
  }, 30000)

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void pingSessionLock()
    }
  })

  void pingSessionLock()
}

startSessionHeartbeat()

createApp(App).use(router).mount('#app')

