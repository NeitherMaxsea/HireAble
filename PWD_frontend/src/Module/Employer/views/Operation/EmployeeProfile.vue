<template>
  <div class="app">

    <!-- SIDEBAR -->
    <SidebarOpEmployer />

    <div class="main-wrapper">

      <!-- NAVBAR -->
      <NavbarEmployer />

      <!-- CONTENT -->
      <main class="content">

        <!-- ================= PROFILE HEADER ================= -->
        <div class="profile-card">

          <div class="avatar">JD</div>

          <div class="profile-info">
            <h2>Juan Dela Cruz</h2>
            <p>Office Assistant • HR Department</p>

            <div class="profile-meta">
              <span>Status: <b>Active</b></span>
              <span>Employment Type: <b>Full-time</b></span>
            </div>
          </div>

        </div>

        <!-- ================= TABS ================= -->
        <div class="tabs">

          <button
            v-for="tab in tabs"
            :key="tab"
            :class="{active:activeTab===tab}"
            @click="activeTab=tab"
          >
            {{ tab }}
          </button>

        </div>

        <!-- ================= TAB CONTENT ================= -->

        <!-- PERSONAL INFO -->
        <div v-if="activeTab==='Personal Info'" class="tab-card">

          <h3>Personal Information</h3>

          <div class="info-grid">
            <div><b>Email:</b> juan@gmail.com</div>
            <div><b>Phone:</b> 09123456789</div>
            <div><b>Address:</b> Quezon City</div>
            <div><b>Birthday:</b> Jan 5, 1998</div>
          </div>

        </div>

        <!-- ASSIGNMENT HISTORY -->
        <div v-if="activeTab==='Assignment History'" class="tab-card">

          <h3>Assignment History</h3>

          <table>
            <thead>
              <tr>
                <th>Department</th>
                <th>Unit</th>
                <th>Supervisor</th>
                <th>Start Date</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>HR</td>
                <td>Admin</td>
                <td>Maria Santos</td>
                <td>2026-02-10</td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- DEPLOYMENT RECORDS -->
        <div v-if="activeTab==='Deployment Records'" class="tab-card">

          <h3>Deployment Records</h3>

          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Location</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>2026-02-20</td>
                <td>Main Office</td>
                <td>Deployed</td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- TRAINING RECORDS -->
        <div v-if="activeTab==='Training Records'" class="tab-card">

          <h3>Training Records</h3>

          <table>
            <thead>
              <tr>
                <th>Training</th>
                <th>Progress</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>PWD Orientation</td>
                <td>100%</td>
                <td>Completed</td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- ACCOMMODATION NEEDS -->
        <div v-if="activeTab==='Accommodation Needs'" class="tab-card">

          <h3>Accommodation Needs</h3>

          <ul>
            <li>Screen reader software</li>
            <li>Flexible schedule</li>
          </ul>

        </div>

        <!-- WORK ASSIGNMENTS -->
        <div v-if="activeTab==='Work Assignments'" class="tab-card">

          <h3>Work Assignments</h3>

          <table>
            <thead>
              <tr>
                <th>Task</th>
                <th>Shift</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>Document Filing</td>
                <td>8AM - 5PM</td>
                <td>Ongoing</td>
              </tr>
            </tbody>
          </table>

        </div>

      </main>
    </div>

  </div>
</template>

<script>
import SidebarOpEmployer from '@/components/sb-employer-operator.vue'
import NavbarEmployer from '@/components/nv-employer.vue'

export default {
  name:"EmployeeOperationsProfile",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      activeTab:"Personal Info",
      tabs:[
        "Personal Info",
        "Assignment History",
        "Deployment Records",
        "Training Records",
        "Accommodation Needs",
        "Work Assignments"
      ]
    }
  }
}
</script>

<style scoped>

/* Layout */

.app{
  display:flex;
  background:#f5f7fb;
  min-height:100vh;
}

.main-wrapper{
  flex:1;
  display:flex;
  flex-direction:column;
}

.content{
  padding:32px;
}

/* ================= PROFILE HEADER ================= */

.profile-card{
  background:white;
  border-radius:14px;
  padding:22px;
  display:flex;
  align-items:center;
  gap:20px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
  margin-bottom:24px;
}

.avatar{
  width:70px;
  height:70px;
  border-radius:50%;
  background:#4f46e5;
  color:white;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:22px;
  font-weight:700;
}

.profile-info h2{
  margin-bottom:4px;
}

.profile-meta{
  display:flex;
  gap:20px;
  font-size:13px;
  color:#6b7280;
}

/* ================= TABS ================= */

.tabs{
  display:flex;
  gap:10px;
  margin-bottom:20px;
}

.tabs button{
  padding:10px 16px;
  border-radius:10px;
  border:1px solid #ddd;
  background:white;
  cursor:pointer;
  font-weight:600;
}

.tabs .active{
  background:#4f46e5;
  color:white;
  border-color:#4f46e5;
}

/* ================= TAB CARD ================= */

.tab-card{
  background:white;
  padding:20px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
}

.tab-card h3{
  margin-bottom:12px;
}

/* Tables */

table{
  width:100%;
  border-collapse:collapse;
}

th,td{
  padding:12px;
  border-bottom:1px solid #eee;
  text-align:left;
}

th{
  background:#f9fafb;
}

/* Info Grid */

.info-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:14px;
}

</style>
