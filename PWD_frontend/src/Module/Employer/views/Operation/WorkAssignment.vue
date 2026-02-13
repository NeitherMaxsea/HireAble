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
            <h2>Work Assignment Coordination</h2>
            <p class="subtitle">
              Define actual work tasks after deployment.
            </p>
          </div>

          <button class="primary-btn" @click="openAssignTask">
            Assign Task
          </button>

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
            <option>Pending</option>
            <option>Ongoing</option>
            <option>Completed</option>
          </select>

        </div>

        <!-- ================= TABLE ================= -->
        <div class="table-card">

          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Role</th>
                <th>Task / Work Assignment</th>
                <th>Shift Schedule</th>
                <th>Supervisor</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="w in filteredAssignments" :key="w.id">

                <td>{{ w.employee }}</td>
                <td>{{ w.department }}</td>
                <td>{{ w.role }}</td>
                <td>{{ w.task }}</td>
                <td>{{ w.shift }}</td>
                <td>{{ w.supervisor }}</td>

                <td>
                  <span :class="['status', w.status.toLowerCase()]">
                    {{ w.status }}
                  </span>
                </td>

                <td class="actions">
                  <button class="edit" @click="openUpdateTask(w)">
                    Update
                  </button>

                  <button class="view" @click="openHistory(w)">
                    History
                  </button>
                </td>

              </tr>
            </tbody>
          </table>

        </div>

      </main>
    </div>

    <!-- ================= ASSIGN / UPDATE MODAL ================= -->
    <div v-if="showTaskModal" class="modal-overlay">

      <div class="modal">

        <h3>{{ isEdit ? 'Update Task' : 'Assign Task' }}</h3>

        <div class="form-group">
          <label>Employee</label>
          <input type="text" v-model="form.employee">
        </div>

        <div class="form-group">
          <label>Department</label>
          <input type="text" v-model="form.department">
        </div>

        <div class="form-group">
          <label>Role</label>
          <input type="text" v-model="form.role">
        </div>

        <div class="form-group">
          <label>Task / Work Assignment</label>
          <textarea rows="3" v-model="form.task"></textarea>
        </div>

        <div class="form-group">
          <label>Shift Schedule</label>
          <input type="text" v-model="form.shift">
        </div>

        <div class="form-group">
          <label>Supervisor</label>
          <input type="text" v-model="form.supervisor">
        </div>

        <div class="form-group">
          <label>Status</label>
          <select v-model="form.status">
            <option>Pending</option>
            <option>Ongoing</option>
            <option>Completed</option>
          </select>
        </div>

        <div class="modal-actions">
          <button class="cancel" @click="showTaskModal=false">
            Cancel
          </button>

          <button class="save">
            Save
          </button>
        </div>

      </div>

    </div>

    <!-- ================= HISTORY MODAL ================= -->
    <div v-if="showHistoryModal" class="modal-overlay">

      <div class="modal">

        <h3>Task History</h3>

        <ul class="history-list">
          <li v-for="(h,index) in history" :key="index">
            {{ h }}
          </li>
        </ul>

        <div class="modal-actions">
          <button class="cancel" @click="showHistoryModal=false">
            Close
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
  name:"WorkAssignmentCoordination",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      search:"",
      filterStatus:"",
      showTaskModal:false,
      showHistoryModal:false,
      isEdit:false,

      form:{
        employee:"",
        department:"",
        role:"",
        task:"",
        shift:"",
        supervisor:"",
        status:"Pending"
      },

      assignments:[
        {
          id:1,
          employee:"Juan Dela Cruz",
          department:"HR",
          role:"Office Assistant",
          task:"Filing documents",
          shift:"8AM - 5PM",
          supervisor:"Maria Santos",
          status:"Ongoing"
        }
      ],

      history:[
        "Task assigned: Filing documents",
        "Task updated: Data encoding"
      ]
    }
  },

  computed:{
    filteredAssignments(){
      return this.assignments.filter(w=>{
        return (
          w.employee.toLowerCase().includes(this.search.toLowerCase()) &&
          (this.filterStatus==="" || w.status===this.filterStatus)
        )
      })
    }
  },

  methods:{
    openAssignTask(){
      this.isEdit=false
      this.form={
        employee:"",
        department:"",
        role:"",
        task:"",
        shift:"",
        supervisor:"",
        status:"Pending"
      }
      this.showTaskModal=true
    },

    openUpdateTask(w){
      this.isEdit=true
      this.form={...w}
      this.showTaskModal=true
    },

    openHistory(w){
      this.showHistoryModal=true
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

/* ================= STATUS ================= */

.status{
  padding:5px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.pending{ background:#fde68a;color:#92400e; }
.ongoing{ background:#bfdbfe;color:#1e3a8a; }
.completed{ background:#bbf7d0;color:#065f46; }

/* ================= ACTIONS ================= */

.actions button{
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
.form-group textarea,
.form-group select{
  width:100%;
  padding:10px;
  border-radius:8px;
  border:1px solid #ddd;
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

.history-list{
  margin-top:10px;
  padding-left:18px;
}

.history-list li{
  font-size:13px;
  margin-bottom:6px;
}

</style>
