<template>
  <header class="navbar">

    <!-- Brand -->
    <div class="brand">
    </div>

    <!-- Navigation -->
  


    <!-- User + Logout -->
    <div class="user-area">

    </div>

  </header>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { signOut } from '@/lib/session-auth'

const router = useRouter()
const userName = ref('Juan Dela Cruz')

async function logout(){
  await signOut()
  router.push('/login')
}
</script>

<style scoped>

/* ===== NAVBAR ===== */

.navbar {
  height: 70px;
  background: #6ea8ff;
  color: white;
  display: flex;
  align-items: center;
  padding: 0 24px;
  justify-content: space-between;
}


</style>
