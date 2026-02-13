<template>
  <div class="app">

    <!-- SIDEBAR -->
    <SidebarOpEmployer />

    <div class="main-wrapper">

      <!-- NAVBAR -->
      <NavbarEmployer />

      <!-- CONTENT -->
      <main class="content">

        <!-- ================= HEADER ================= -->
        <div class="page-header">

          <div class="header-left">
            <h2>Training Progress & Completion Tracking</h2>
            <p class="subtitle">
              Monitor completion of trainings per employee.
            </p>
          </div>

        </div>

        <!-- ================= FILTER BAR ================= -->
        <div class="toolbar">

          <input
            type="text"
            placeholder="Search employee..."
            v-model="search"
          />

          <select v-model="filterStatus">
            <option value="">All Status</option>
            <option>Completed</option>
            <option>In Progress</option>
            <option>Not Started</option>
          </select>

        </div>

        <!-- ================= TABLE ================= -->
        <div class="table-card">

          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Training Name</th>
                <th>Progress</th>
                <th>Completion Date</th>
                <th>Certificate</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="t in filteredTrainings" :key="t.id">

                <td>{{ t.employee }}</td>
                <td>{{ t.department }}</td>
                <td>{{ t.training }}</td>

                <!-- Progress -->
                <td>
                  <div class="progress-wrapper">
                    <div class="progress-bar">
                      <div
                        class="progress-fill"
                        :style="{width: t.progress + '%'}"
                      ></div>
                    </div>
                    <span class="progress-text">
                      {{ t.progress }}%
                    </span>
                  </div>
                </td>

                <td>{{ t.completionDate || '—' }}</td>

                <td>
                  <button
                    v-if="t.certificate"
                    class="cert-btn"
                  >
                    View
                  </button>

                  <span v-else>—</span>
                </td>

                <td>
                  <span :class="['status', t.status.toLowerCase().replace(' ','-')]">
                    {{ t.status }}
                  </span>
                </td>

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
  name:"TrainingProgress",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      search:"",
      filterStatus:"",

      trainings:[
        {
          id:1,
          employee:"Juan Dela Cruz",
          department:"HR",
          training:"PWD Workplace Orientation",
          progress:100,
          completionDate:"2026-03-02",
          certificate:true,
          status:"Completed"
        },
        {
          id:2,
          employee:"Ana Reyes",
          department:"IT",
          training:"Accessibility Tools Training",
          progress:60,
          completionDate:"",
          certificate:false,
          status:"In Progress"
        },
        {
          id:3,
          employee:"Mark Santos",
          department:"Finance",
          training:"Soft Skills Workshop",
          progress:0,
          completionDate:"",
          certificate:false,
          status:"Not Started"
        }
      ]
    }
  },

  computed:{
    filteredTrainings(){
      return this.trainings.filter(t=>{
        return (
          t.employee.toLowerCase().includes(this.search.toLowerCase()) &&
          (this.filterStatus==="" || t.status===this.filterStatus)
        )
      })
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

/* ================= HEADER ================= */

.page-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  margin-bottom:28px;
}

.header-left h2{
  font-size:26px;
  font-weight:700;
  margin-bottom:6px;
}

.subtitle{
  color:#6b7280;
  font-size:14px;
}

/* ================= TOOLBAR ================= */

.toolbar{
  display:flex;
  gap:12px;
  margin-bottom:24px;
}

.toolbar input,
.toolbar select{
  padding:10px;
  border-radius:8px;
  border:1px solid #ddd;
  min-width:220px;
}

/* ================= TABLE ================= */

.table-card{
  background:white;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
  overflow:auto;
}

table{
  width:100%;
  border-collapse:collapse;
}

th,td{
  padding:14px;
  text-align:left;
  border-bottom:1px solid #eee;
}

th{
  background:#f9fafb;
  font-weight:600;
}

/* ================= PROGRESS BAR ================= */

.progress-wrapper{
  display:flex;
  align-items:center;
  gap:10px;
}

.progress-bar{
  width:120px;
  height:8px;
  background:#e5e7eb;
  border-radius:10px;
  overflow:hidden;
}

.progress-fill{
  height:100%;
  background:#4f46e5;
  border-radius:10px;
}

/* ================= CERT BUTTON ================= */

.cert-btn{
  background:#22c55e;
  color:white;
  border:none;
  padding:6px 12px;
  border-radius:8px;
  font-size:12px;
  cursor:pointer;
}

/* ================= STATUS ================= */

.status{
  padding:5px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.completed{ background:#bbf7d0;color:#065f46; }
.in-progress{ background:#bfdbfe;color:#1e3a8a; }
.not-started{ background:#e5e7eb;color:#374151; }

</style>
