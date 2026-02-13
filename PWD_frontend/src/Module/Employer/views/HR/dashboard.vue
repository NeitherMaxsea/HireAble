<template>
  <div class="app">


    <!-- MAIN AREA -->
    <div class="main-wrapper">


      <main>

        <!-- CONTENT -->
        <section class="content">

          <!-- HEADER -->
          <div class="page-header">
            <h2>Employer Dashboard</h2>

            <input
              type="text"
              class="search"
              placeholder="Search..."
            >
          </div>

          <!-- STAT CARDS -->
          <div class="stats">

            <div class="stat-card">
              <h4>Total Applicants</h4>
              <p>120</p>
            </div>

            <div class="stat-card">
              <h4>Active Job Posts</h4>
              <p>35</p>
            </div>

            <div class="stat-card">
              <h4>Interviews Scheduled</h4>
              <p>18</p>
            </div>

            <div class="stat-card">
              <h4>Employees</h4>
              <p>56</p>
            </div>

          </div>

          <!-- TABLE -->
          <div class="card">

            <h3>Recent Applications</h3>

            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Status</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td>Juan Dela Cruz</td>
                  <td>Web Developer</td>
                  <td>Pending</td>
                </tr>

                <tr>
                  <td>Maria Santos</td>
                  <td>Data Encoder</td>
                  <td>Approved</td>
                </tr>

              </tbody>
            </table>

          </div>

        </section>

      </main>

    </div>

  </div>
</template>

<script>
import SidebarEmployer from '@/components/sb-employer.vue'
import NavbarEmployer from '@/components/nv-employer.vue'

export default {
  name: "EmployerDashboard",

  components: {
    SidebarEmployer,
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

/* MAIN */
main{
  flex:1;
  display:flex;
  flex-direction:column;
  overflow:hidden;
}

/* CONTENT */
.content{
  flex:1;
  padding:25px;
  overflow-y:auto;
}

/* HEADER */
.page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
}

.search{
  padding:8px 12px;
  border-radius:8px;
  border:1px solid #d1d5db;
}

/* STATS */
.stats{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:20px;
  margin-bottom:25px;
}

.stat-card{
  background:white;
  padding:20px;
  border-radius:12px;
  box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.stat-card h4{
  margin:0;
  color:#6b7280;
}

.stat-card p{
  font-size:32px;
  margin-top:10px;
  font-weight:bold;
}

/* TABLE */
.card{
  background:white;
  padding:20px;
  border-radius:12px;
  box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

table{
  width:100%;
  border-collapse:collapse;
  margin-top:10px;
}

th, td{
  padding:10px;
  border-bottom:1px solid #e5e7eb;
}

</style>
