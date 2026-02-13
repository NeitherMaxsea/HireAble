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
            <h2>Assignment Management</h2>
            <p class="subtitle">
              Assign hired PWD applicants to departments or units.
            </p>
          </div>

          <button class="primary-btn" @click="openBulkAssign">
            Bulk Assign
          </button>

        </div>

        <!-- ================= SEARCH & FILTER ================= -->
        <div class="toolbar">

          <input
            type="text"
            placeholder="Search employee..."
            v-model="search"
          />

          <select v-model="filterDepartment">
            <option value="">All Departments</option>
            <option>HR</option>
            <option>IT</option>
            <option>Finance</option>
            <option>Operations</option>
          </select>

          <select v-model="filterStatus">
            <option value="">All Status</option>
            <option>Pending</option>
            <option>Assigned</option>
            <option>Reassigned</option>
          </select>

        </div>

        <!-- ================= TABLE ================= -->
        <div class="table-card">
          <table>
            <thead>
              <tr>
                <th><input type="checkbox" /></th>
                <th>Employee Name</th>
                <th>Position</th>
                <th>Department / Unit</th>
                <th>Supervisor</th>
                <th>Employment Type</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="emp in filteredEmployees" :key="emp.id">

                <td><input type="checkbox" /></td>

                <td>{{ emp.name }}</td>
                <td>{{ emp.position }}</td>
                <td>{{ emp.department }}</td>
                <td>{{ emp.supervisor }}</td>
                <td>{{ emp.type }}</td>

                <td>
                  <span :class="['status', emp.status.toLowerCase()]">
                    {{ emp.status }}
                  </span>
                </td>

                <td class="actions">
                  <button class="assign" @click="openAssign(emp)">
                    Assign
                  </button>

                  <button class="edit" @click="openAssign(emp)">
                    Edit
                  </button>

                  <button class="view">
                    View
                  </button>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>

    <!-- ================= ASSIGN MODAL ================= -->
    <div class="modal-overlay" v-if="showModal">

      <div class="modal">

        <h3>Assign Employee</h3>

        <div class="form-group">
          <label>Department</label>
          <select v-model="form.department">
            <option>HR</option>
            <option>IT</option>
            <option>Finance</option>
            <option>Operations</option>
          </select>
        </div>

        <div class="form-group">
          <label>Unit</label>
          <input type="text" v-model="form.unit" />
        </div>

        <div class="form-group">
          <label>Supervisor</label>
          <input type="text" v-model="form.supervisor" />
        </div>

        <div class="form-group">
          <label>Start Date</label>
          <input type="date" v-model="form.startDate" />
        </div>

        <div class="form-group">
          <label>Remarks</label>
          <textarea rows="3" v-model="form.remarks"></textarea>
        </div>

        <div class="modal-actions">
          <button class="cancel" @click="showModal=false">
            Cancel
          </button>

          <button class="save">
            Save Assignment
          </button>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import SidebarOpEmployer from '@/components/sb-employer-operator.vue'
import NavbarEmployer from '@/components/nv-employer.vue'

export default {
  name: "AssignmentManagement",

  components: {
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      search:"",
      filterDepartment:"",
      filterStatus:"",
      showModal:false,

      form:{
        department:"",
        unit:"",
        supervisor:"",
        startDate:"",
        remarks:""
      },

      employees:[
        {
          id:1,
          name:"Juan Dela Cruz",
          position:"Office Assistant",
          department:"HR",
          supervisor:"Maria Santos",
          type:"Full-time",
          status:"Pending"
        },
        {
          id:2,
          name:"Ana Reyes",
          position:"IT Support",
          department:"IT",
          supervisor:"Carlos Lim",
          type:"Contract",
          status:"Assigned"
        }
      ]
    }
  },

  computed:{
    filteredEmployees(){
      return this.employees.filter(emp=>{
        return (
          emp.name.toLowerCase().includes(this.search.toLowerCase()) &&
          (this.filterDepartment==="" || emp.department===this.filterDepartment) &&
          (this.filterStatus==="" || emp.status===this.filterStatus)
        )
      })
    }
  },

  methods:{
    openAssign(emp){
      this.form.department = emp.department
      this.form.supervisor = emp.supervisor
      this.showModal = true
    },

    openBulkAssign(){
      this.showModal = true
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

/* Button */

.primary-btn{
  background:#4f46e5;
  color:white;
  border:none;
  padding:10px 18px;
  border-radius:10px;
  font-weight:600;
  cursor:pointer;
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
  min-width:200px;
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

/* Actions */

.actions button{
  margin-right:6px;
  padding:6px 12px;
  border-radius:8px;
  border:none;
  font-size:12px;
  cursor:pointer;
}

.assign{ background:#4f46e5;color:white; }
.edit{ background:#22c55e;color:white; }
.view{ background:#e5e7eb; }

/* Status */

.status{
  padding:5px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.pending{ background:#fde68a;color:#92400e; }
.assigned{ background:#bbf7d0;color:#065f46; }
.reassigned{ background:#bfdbfe;color:#1e3a8a; }

/* ================= MODAL ================= */

.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.5);
  display:flex;
  align-items:center;
  justify-content:center;
}

.modal{
  background:white;
  padding:26px;
  width:420px;
  border-radius:14px;
}

.modal h3{
  margin-bottom:16px;
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

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
  margin-top:18px;
}

.cancel{
  background:#e5e7eb;
  border:none;
  padding:10px 16px;
  border-radius:8px;
}

.save{
  background:#4f46e5;
  color:white;
  border:none;
  padding:10px 16px;
  border-radius:8px;
}

</style>
