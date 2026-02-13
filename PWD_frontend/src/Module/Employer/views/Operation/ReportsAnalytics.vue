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
            <h2>Reports & Analytics</h2>
            <p class="subtitle">
              Performance and compliance insights.
            </p>
          </div>

          <div class="header-actions">
            <button class="secondary-btn">Export PDF</button>
            <button class="primary-btn">Export Excel</button>
          </div>

        </div>

        <!-- ================= KPI CARDS ================= -->
        <div class="kpi-grid">

          <div class="kpi-card">
            <h4>Deployment Completion Rate</h4>
            <div class="kpi-value">92%</div>
          </div>

          <div class="kpi-card">
            <h4>Training Completion Rate</h4>
            <div class="kpi-value">88%</div>
          </div>

          <div class="kpi-card">
            <h4>Accommodation Fulfillment Rate</h4>
            <div class="kpi-value">95%</div>
          </div>

        </div>

        <!-- ================= CHART PLACEHOLDERS ================= -->
        <div class="chart-grid">

          <div class="chart-card">
            <h3>Deployment vs Completion</h3>
            <div class="chart-placeholder">
              Chart Placeholder
            </div>
          </div>

          <div class="chart-card">
            <h3>Training Completion Trend</h3>
            <div class="chart-placeholder">
              Chart Placeholder
            </div>
          </div>

        </div>

        <!-- ================= DEPARTMENT TABLE ================= -->
        <div class="table-card">

          <h3>Department-wise Deployment</h3>

          <table>
            <thead>
              <tr>
                <th>Department</th>
                <th>Total Employees</th>
                <th>Deployed</th>
                <th>Pending</th>
                <th>Completion Rate</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>HR</td>
                <td>10</td>
                <td>9</td>
                <td>1</td>
                <td>90%</td>
              </tr>

              <tr>
                <td>IT</td>
                <td>8</td>
                <td>7</td>
                <td>1</td>
                <td>88%</td>
              </tr>

              <tr>
                <td>Finance</td>
                <td>6</td>
                <td>6</td>
                <td>0</td>
                <td>100%</td>
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
  name:"ReportsAnalytics",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
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

.header-actions{
  display:flex;
  gap:12px;
}

.primary-btn{
  background:#4f46e5;
  color:white;
  border:none;
  padding:10px 18px;
  border-radius:10px;
  font-weight:600;
}

.secondary-btn{
  background:white;
  color:#4f46e5;
  border:1px solid #4f46e5;
  padding:10px 18px;
  border-radius:10px;
  font-weight:600;
}

/* ================= KPI ================= */

.kpi-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:20px;
  margin-bottom:30px;
}

.kpi-card{
  background:white;
  padding:22px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
}

.kpi-card h4{
  font-size:14px;
  color:#6b7280;
  margin-bottom:6px;
}

.kpi-value{
  font-size:34px;
  font-weight:700;
  color:#4f46e5;
}

/* ================= CHARTS ================= */

.chart-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:20px;
  margin-bottom:30px;
}

.chart-card{
  background:white;
  padding:22px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
}

.chart-placeholder{
  margin-top:12px;
  height:200px;
  background:#f3f4f6;
  border-radius:10px;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#9ca3af;
}

/* ================= TABLE ================= */

.table-card{
  background:white;
  padding:22px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
}

table{
  width:100%;
  border-collapse:collapse;
  margin-top:12px;
}

th,td{
  padding:14px;
  border-bottom:1px solid #eee;
  text-align:left;
}

th{
  background:#f9fafb;
}

</style>
