<template>
  <div class="app">

    <!-- SIDEBAR -->
    <SidebarOpEmployer />

    <!-- MAIN -->
    <div class="main-wrapper">

      <!-- NAVBAR -->
      <NavbarEmployer />

      <main>
        <section class="content">

          <!-- HEADER -->
          <div class="page-header">
            <div>
              <h2>Operations Dashboard</h2>
              <p class="subtitle">
                Overview of post-hiring operations and employee readiness.
              </p>
            </div>
          </div>

          <!-- KPI CARDS -->
          <div class="kpi-grid">

            <div class="kpi-card">
              <p>Deployed Employees</p>
              <h3>28</h3>
            </div>

            <div class="kpi-card">
              <p>Pending Assignment</p>
              <h3>6</h3>
            </div>

            <div class="kpi-card">
              <p>Ongoing Trainings</p>
              <h3>9</h3>
            </div>

            <div class="kpi-card">
              <p>Pending Accommodations</p>
              <h3>4</h3>
            </div>

          </div>

          <!-- MAIN GRID -->
          <div class="dashboard-grid">

            <!-- DEPLOYMENT STATUS -->
            <div class="panel">

              <h4>Deployment Status</h4>

              <div class="status-row">
                <span>Assigned</span>
                <div class="bar">
                  <div class="fill assigned"></div>
                </div>
                <small>70%</small>
              </div>

              <div class="status-row">
                <span>Pending</span>
                <div class="bar">
                  <div class="fill pending"></div>
                </div>
                <small>30%</small>
              </div>

            </div>

            <!-- TRAINING PIPELINE -->
            <div class="panel">

              <h4>Training Pipeline</h4>

              <ul>
                <li>
                  Not Started <strong>5</strong>
                </li>
                <li>
                  Ongoing <strong>9</strong>
                </li>
                <li>
                  Completed <strong>14</strong>
                </li>
              </ul>

            </div>

            <!-- UPCOMING SCHEDULES -->
            <div class="panel">

              <h4>Upcoming Schedules</h4>

              <div class="schedule-item">
                <p>Juan Dela Cruz - Web Training</p>
                <small>May 10, 2025</small>
              </div>

              <div class="schedule-item">
                <p>Maria Santos - Orientation</p>
                <small>May 12, 2025</small>
              </div>

            </div>

          </div>

        </section>
      </main>

    </div>

  </div>
</template>

<script>
import SidebarOpEmployer from '@/components/sb-employer-operator.vue'
import NavbarEmployer from '@/components/nv-employer.vue'

export default {
  name:"OperationsDashboard",
  components:{
    SidebarOpEmployer,
    NavbarEmployer
  }
}
</script>

<style scoped>

/* LAYOUT */

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

.content{
  padding:25px;
}

/* HEADER */

.page-header{
  margin-bottom:20px;
}

.subtitle{
  font-size:13px;
  color:#6b7280;
}

/* KPI */

.kpi-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:16px;
  margin-bottom:25px;
}

.kpi-card{
  background:white;
  padding:20px;
  border-radius:14px;
  box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.kpi-card p{
  color:#6b7280;
  font-size:13px;
}

.kpi-card h3{
  font-size:28px;
}

/* GRID */

.dashboard-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
  gap:20px;
}

/* PANEL */

.panel{
  background:white;
  padding:18px;
  border-radius:14px;
  box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.panel h4{
  margin-bottom:15px;
}

/* STATUS BAR */

.status-row{
  display:flex;
  align-items:center;
  gap:10px;
  margin-bottom:10px;
}

.bar{
  flex:1;
  height:8px;
  background:#e5e7eb;
  border-radius:20px;
  overflow:hidden;
}

.fill{
  height:100%;
}

.fill.assigned{
  width:70%;
  background:#22c55e;
}

.fill.pending{
  width:30%;
  background:#f59e0b;
}

/* PIPELINE LIST */

.panel ul{
  list-style:none;
  padding:0;
}

.panel li{
  display:flex;
  justify-content:space-between;
  padding:8px 0;
  border-bottom:1px solid #e5e7eb;
}

/* SCHEDULE */

.schedule-item{
  padding:8px 0;
  border-bottom:1px solid #e5e7eb;
}

.schedule-item small{
  color:#6b7280;
}

</style>
