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
            <h2>Deployment Scheduling</h2>
            <p class="subtitle">
              Plan, track, and manage deployment schedules.
            </p>
          </div>

          <!-- VIEW TOGGLE -->
          <div class="view-toggle">
            <button
              :class="{active:viewMode==='table'}"
              @click="viewMode='table'"
            >
              Table View
            </button>

            <button
              :class="{active:viewMode==='calendar'}"
              @click="viewMode='calendar'"
            >
              Calendar View
            </button>
          </div>

        </div>

        <!-- FILTER BAR -->
        <div class="toolbar">
          <input
            type="text"
            placeholder="Search employee..."
            v-model="search"
          />

          <select v-model="filterStatus">
            <option value="">All Status</option>
            <option>Scheduled</option>
            <option>Deployed</option>
            <option>Delayed</option>
          </select>
        </div>

        <!-- ================= TABLE VIEW ================= -->
        <div v-if="viewMode==='table'" class="table-card">

          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Department</th>
                <th>Deployment Date</th>
                <th>Location</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="d in filteredDeployments" :key="d.id">
                <td>{{ d.employee }}</td>
                <td>{{ d.position }}</td>
                <td>{{ d.department }}</td>
                <td>{{ d.date }}</td>
                <td>{{ d.location }}</td>

                <td>
                  <span :class="['status', d.status.toLowerCase()]">
                    {{ d.status }}
                  </span>
                </td>

                <td>{{ d.remarks }}</td>

                <td class="actions">
                  <button class="edit" @click="openEdit(d)">Reschedule</button>
                  <button class="view" @click="openView(d)">View</button>
                </td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- ================= CALENDAR VIEW ================= -->
        <div v-if="viewMode==='calendar'" class="calendar-grid">

          <div
            v-for="d in filteredDeployments"
            :key="d.id"
            class="calendar-card"
          >

            <div>
              <h4>{{ d.employee }}</h4>
              <p class="position">{{ d.position }}</p>
            </div>

            <div class="calendar-info">
              <div class="calendar-row">
                <span>Date</span>
                <b>{{ d.date }}</b>
              </div>

              <div class="calendar-row">
                <span>Department</span>
                <b>{{ d.department }}</b>
              </div>

              <div class="calendar-row">
                <span>Location</span>
                <b>{{ d.location }}</b>
              </div>
            </div>

            <div class="calendar-footer">
              <span :class="['status', d.status.toLowerCase()]">
                {{ d.status }}
              </span>

              <div class="card-actions">
                <button class="edit" @click="openEdit(d)">Reschedule</button>
                <button class="view" @click="openView(d)">View</button>
              </div>
            </div>

          </div>

        </div>

      </main>
    </div>

    <!-- ================= RESCHEDULE MODAL ================= -->
    <div v-if="showEditModal" class="modal-overlay">

      <div class="modal">

        <h3>Reschedule Deployment</h3>

        <div class="form-group">
          <label>Deployment Date</label>
          <input type="date" v-model="form.date">
        </div>

        <div class="form-group">
          <label>Location</label>
          <input type="text" v-model="form.location">
        </div>

        <div class="form-group">
          <label>Status</label>
          <select v-model="form.status">
            <option>Scheduled</option>
            <option>Deployed</option>
            <option>Delayed</option>
          </select>
        </div>

        <div class="form-group">
          <label>Deployment Checklist</label>

          <div class="checklist">
            <label>
              <input type="checkbox" v-model="form.checklist.orientation">
              Orientation Completed
            </label>

            <label>
              <input type="checkbox" v-model="form.checklist.equipment">
              Equipment Issued
            </label>

            <label>
              <input type="checkbox" v-model="form.checklist.documents">
              Documents Submitted
            </label>
          </div>
        </div>

        <div class="form-group">
          <label>Notes / Remarks</label>
          <textarea rows="3" v-model="form.remarks"></textarea>
        </div>

        <div class="modal-actions">
          <button class="cancel" @click="showEditModal=false">Cancel</button>
          <button class="save">Save Changes</button>
        </div>

      </div>

    </div>

    <!-- ================= VIEW MODAL ================= -->
    <div v-if="showViewModal" class="modal-overlay">

      <div class="modal">

        <h3>Deployment Details</h3>

        <p><b>Employee:</b> {{ selected.employee }}</p>
        <p><b>Position:</b> {{ selected.position }}</p>
        <p><b>Department:</b> {{ selected.department }}</p>
        <p><b>Date:</b> {{ selected.date }}</p>
        <p><b>Location:</b> {{ selected.location }}</p>
        <p><b>Status:</b> {{ selected.status }}</p>
        <p><b>Remarks:</b> {{ selected.remarks }}</p>

        <div class="modal-actions">
          <button class="cancel" @click="showViewModal=false">Close</button>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import SidebarOpEmployer from '@/components/sb-employer-operator.vue'
import NavbarEmployer from '@/components/nv-employer.vue'

export default {
  name:"DeploymentScheduling",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      viewMode:"table",
      search:"",
      filterStatus:"",
      showEditModal:false,
      showViewModal:false,
      selected:{},

      form:{
        date:"",
        location:"",
        status:"",
        remarks:"",
        checklist:{
          orientation:false,
          equipment:false,
          documents:false
        }
      },

      deployments:[
        {
          id:1,
          employee:"Juan Dela Cruz",
          position:"Office Assistant",
          department:"HR",
          date:"2026-02-20",
          location:"Main Office",
          status:"Scheduled",
          remarks:"Initial deployment"
        },
        {
          id:2,
          employee:"Ana Reyes",
          position:"IT Support",
          department:"IT",
          date:"2026-02-22",
          location:"Data Center",
          status:"Deployed",
          remarks:"Deployed successfully"
        }
      ]
    }
  },

  computed:{
    filteredDeployments(){
      return this.deployments.filter(d=>{
        return (
          d.employee.toLowerCase().includes(this.search.toLowerCase()) &&
          (this.filterStatus==="" || d.status===this.filterStatus)
        )
      })
    }
  },

  methods:{
    openEdit(d){
      this.form = JSON.parse(JSON.stringify(d))
      this.showEditModal = true
    },

    openView(d){
      this.selected = d
      this.showViewModal = true
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

/* Toggle */

.view-toggle button{
  padding:10px 18px;
  border:1px solid #ddd;
  border-radius:10px;
  background:white;
  margin-left:8px;
  cursor:pointer;
  font-weight:600;
}

.view-toggle .active{
  background:#4f46e5;
  color:white;
  border-color:#4f46e5;
}

/* Toolbar */

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
}

/* Table */

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
  border-bottom:1px solid #eee;
}

th{
  background:#f9fafb;
  text-align:left;
}

/* ===== CALENDAR ===== */

.calendar-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
  gap:22px;
}

.calendar-card{
  background:white;
  padding:22px;
  border-radius:14px;
  box-shadow:0 6px 16px rgba(0,0,0,.06);
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  min-height:270px;
}

.calendar-card h4{
  font-size:16px;
  font-weight:600;
}

.position{
  font-size:13px;
  color:#6b7280;
  margin-bottom:14px;
}

.calendar-info{
  display:grid;
  gap:10px;
  margin-bottom:14px;
}

.calendar-row{
  display:flex;
  justify-content:space-between;
  font-size:13px;
}

.calendar-row span{
  color:#6b7280;
}

.calendar-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
}

/* Status */

.status{
  padding:5px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.scheduled{ background:#bfdbfe;color:#1e3a8a; }
.deployed{ background:#bbf7d0;color:#065f46; }
.delayed{ background:#fecaca;color:#7f1d1d; }

/* Actions */

.actions button,
.card-actions button{
  padding:6px 12px;
  border:none;
  border-radius:8px;
  font-size:12px;
  margin-right:6px;
  cursor:pointer;
}

.edit{ background:#22c55e;color:white; }
.view{ background:#e5e7eb; }

/* ================= MODAL ================= */

.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.5);
  display:flex;
  justify-content:center;
  align-items:center;
}

.modal{
  background:white;
  width:420px;
  padding:26px;
  border-radius:14px;
}

.form-group{
  margin-bottom:14px;
}

.form-group label{
  font-size:13px;
  display:block;
  margin-bottom:6px;
}

.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  padding:10px;
  border-radius:8px;
  border:1px solid #ddd;
}

.checklist label{
  display:block;
  font-size:13px;
}

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
}

.cancel{
  background:#e5e7eb;
  padding:10px 16px;
  border:none;
  border-radius:8px;
}

.save{
  background:#4f46e5;
  color:white;
  padding:10px 16px;
  border:none;
  border-radius:8px;
}

</style>
