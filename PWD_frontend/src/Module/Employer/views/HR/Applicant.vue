<template>
  <div class="app">

  

    <!-- MAIN AREA -->
    <div class="main-wrapper">

 
      <!-- MAIN -->
      <main>
        <section class="content">

          <!-- HEADER -->
          <div class="page-header">
            <div>
              <h2>Applicants Management</h2>
              <p class="subtitle">Manage and review job applicants.</p>
            </div>
          </div>

          <!-- CONTROLS -->
          <div class="controls">
            <input
              type="text"
              class="search"
              placeholder="Search applicant..."
            >
          </div>

          <!-- TABLE CARD -->
          <div class="card">

            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Application Date</th>
                  <th>Status</th>
                  <th>Position</th>
                  <th style="text-align:center;">Actions</th>
                </tr>
              </thead>

              <tbody>

                <!-- SAMPLE DATA -->
                <tr>
                  <td>
                    <strong>Juan Dela Cruz</strong><br>
                    <small>Applicant ID: APP-001</small>
                  </td>

                  <td>
                    +639123456789<br>
                    <small>juan@email.com</small>
                  </td>

                  <td>
                    <span class="badge active">New</span><br>
                    <small>April 25, 2025</small>
                  </td>

                  <td>New</td>

                  <td>
                    Web Developer<br>
                    <small>IT Department</small>
                  </td>

                  <td class="center">
                    <button class="icon-btn">⋮</button>
                  </td>
                </tr>

                <tr>
                  <td>
                    <strong>Maria Santos</strong><br>
                    <small>Applicant ID: APP-002</small>
                  </td>

                  <td>
                    +639987654321<br>
                    <small>maria@email.com</small>
                  </td>

                  <td>
                    <span class="badge leave">Shortlisted</span><br>
                    <small>April 23, 2025</small>
                  </td>

                  <td>Shortlisted</td>

                  <td>
                    Data Encoder<br>
                    <small>Operations</small>
                  </td>

                  <td class="center">
                    <button class="icon-btn">⋮</button>
                  </td>
                </tr>

              </tbody>
            </table>

            <!-- FOOTER -->
            <div class="table-footer">
              <p>Showing 1 to 2 of 2 results</p>

              <div class="pagination">
                <span>Items per page</span>
                <select>
                  <option>5</option>
                  <option>10</option>
                  <option>20</option>
                </select>

                <button>«</button>
                <button>‹</button>
                <button>›</button>
                <button>»</button>
              </div>
            </div>

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
  name: "Applicants",
  components:{
    SidebarEmployer,
    NavbarEmployer
  }
}
</script>

<style scoped>

/* ===== LAYOUT ===== */

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

main{
  flex:1;
}

.content{
  padding:25px;
}

/* ===== HEADER ===== */

.page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:15px;
}

.subtitle{
  font-size:13px;
  color:#6b7280;
}

/* ===== CONTROLS ===== */

.controls{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:15px;
}

.search{
  padding:8px 12px;
  width:220px;
  border-radius:8px;
  border:1px solid #d1d5db;
}

.search:focus{
  outline:none;
  border-color:#2563eb;
}

/* ===== CARD ===== */

.card{
  background:white;
  padding:20px;
  border-radius:12px;
  box-shadow:0 2px 6px rgba(0,0,0,0.04);
}

/* ===== TABLE ===== */

table{
  width:100%;
  border-collapse:collapse;
}

th{
  font-size:13px;
  color:#6b7280;
}

th, td{
  padding:12px;
  border-bottom:1px solid #e5e7eb;
  text-align:left;
}

small{
  color:#6b7280;
  font-size:12px;
}

.center{
  text-align:center;
}

/* ===== BADGES ===== */

.badge{
  padding:4px 10px;
  border-radius:20px;
  font-size:12px;
}

.active{
  background:#bfdbfe;
  color:#1e40af;
}

.leave{
  background:#bbf7d0;
  color:#166534;
}

/* ===== ICON BUTTON ===== */

.icon-btn{
  border:none;
  background:transparent;
  font-size:18px;
  cursor:pointer;
}

/* ===== FOOTER ===== */

.table-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-top:15px;
  font-size:13px;
}

.pagination{
  display:flex;
  align-items:center;
  gap:8px;
}

.pagination select,
.pagination button{
  padding:5px 8px;
  border-radius:6px;
  border:1px solid #d1d5db;
  background:white;
  cursor:pointer;
}

</style>
