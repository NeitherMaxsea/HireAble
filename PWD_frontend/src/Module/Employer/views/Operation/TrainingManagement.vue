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
            <h2>Training Management</h2>
            <p class="subtitle">
              Manage training programs and employee enrollment.
            </p>
          </div>

          <div class="header-actions">
            <button class="secondary-btn" @click="showEnroll=true">
              Enroll Employee
            </button>

            <button class="primary-btn" @click="showAddTraining=true">
              + Add Training Program
            </button>
          </div>

        </div>

        <!-- ================= TRAINING PROGRAMS ================= -->
        <div class="section-card">

          <div class="section-header">
            <h3>Training Programs</h3>
          </div>

          <table>
            <thead>
              <tr>
                <th>Training Title</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Trainer</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="t in trainings" :key="t.id">
                <td>{{ t.title }}</td>
                <td>{{ t.type }}</td>
                <td>{{ t.duration }}</td>
                <td>{{ t.trainer }}</td>

                <td>
                  <span :class="['status', t.status.toLowerCase()]">
                    {{ t.status }}
                  </span>
                </td>

                <td class="actions">
                  <button class="upload">
                    Upload Materials
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- ================= ENROLLED EMPLOYEES ================= -->
        <div class="section-card">

          <div class="section-header">
            <h3>Enrolled Employees</h3>
          </div>

          <table>
            <thead>
              <tr>
                <th>Employee Name</th>
                <th>Training</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Completion %</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="e in enrollments" :key="e.id">
                <td>{{ e.employee }}</td>
                <td>{{ e.training }}</td>
                <td>{{ e.start }}</td>
                <td>{{ e.end }}</td>
                <td>{{ e.progress }}%</td>

                <td>
                  <span :class="['status', e.status.toLowerCase()]">
                    {{ e.status }}
                  </span>
                </td>

                <td class="actions">
                  <button class="complete" @click="markCompleted(e)">
                    Mark Completed
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

        </div>

      </main>
    </div>

    <!-- ================= ADD TRAINING MODAL ================= -->
    <div v-if="showAddTraining" class="modal-overlay">

      <div class="modal">

        <h3>Add Training Program</h3>

        <div class="form-group">
          <label>Training Title</label>
          <input type="text" v-model="trainingForm.title">
        </div>

        <div class="form-group">
          <label>Type</label>
          <select v-model="trainingForm.type">
            <option>Onboarding</option>
            <option>Technical</option>
            <option>Soft Skills</option>
            <option>Accessibility</option>
          </select>
        </div>

        <div class="form-group">
          <label>Duration</label>
          <input type="text" v-model="trainingForm.duration">
        </div>

        <div class="form-group">
          <label>Trainer</label>
          <input type="text" v-model="trainingForm.trainer">
        </div>

        <div class="modal-actions">
          <button class="cancel" @click="showAddTraining=false">
            Cancel
          </button>
          <button class="save">
            Save
          </button>
        </div>

      </div>

    </div>

    <!-- ================= ENROLL MODAL ================= -->
    <div v-if="showEnroll" class="modal-overlay">

      <div class="modal">

        <h3>Enroll Employee</h3>

        <div class="form-group">
          <label>Employee Name</label>
          <input type="text" v-model="enrollForm.employee">
        </div>

        <div class="form-group">
          <label>Training Program</label>
          <select v-model="enrollForm.training">
            <option v-for="t in trainings" :key="t.id">
              {{ t.title }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Start Date</label>
          <input type="date" v-model="enrollForm.start">
        </div>

        <div class="form-group">
          <label>End Date</label>
          <input type="date" v-model="enrollForm.end">
        </div>

        <div class="modal-actions">
          <button class="cancel" @click="showEnroll=false">
            Cancel
          </button>
          <button class="save">
            Enroll
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
  name:"TrainingManagement",

  components:{
    SidebarOpEmployer,
    NavbarEmployer
  },

  data(){
    return{
      showAddTraining:false,
      showEnroll:false,

      trainings:[
        {
          id:1,
          title:"PWD Workplace Orientation",
          type:"Onboarding",
          duration:"2 Days",
          trainer:"HR Team",
          status:"Active"
        }
      ],

      enrollments:[
        {
          id:1,
          employee:"Juan Dela Cruz",
          training:"PWD Workplace Orientation",
          start:"2026-03-01",
          end:"2026-03-02",
          progress:60,
          status:"Ongoing"
        }
      ],

      trainingForm:{
        title:"",
        type:"",
        duration:"",
        trainer:""
      },

      enrollForm:{
        employee:"",
        training:"",
        start:"",
        end:""
      }
    }
  },

  methods:{
    markCompleted(e){
      e.status="Completed"
      e.progress=100
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
  margin-bottom:32px;
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

/* Buttons */

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

/* ================= SECTIONS ================= */

.section-card{
  background:white;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
  padding:20px;
  margin-bottom:28px;
}

.section-header{
  margin-bottom:14px;
}

.section-header h3{
  font-size:18px;
  font-weight:600;
}

/* Table */

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

/* Status */

.status{
  padding:4px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.active{ background:#bbf7d0;color:#065f46; }
.ongoing{ background:#bfdbfe;color:#1e3a8a; }
.completed{ background:#bbf7d0;color:#065f46; }

/* Actions */

.actions button{
  padding:6px 12px;
  border:none;
  border-radius:8px;
  font-size:12px;
}

.upload{ background:#3b82f6;color:white; }
.complete{ background:#22c55e;color:white; }

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
  margin-bottom:6px;
  display:block;
}

.form-group input,
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

</style>
