<template>
  <div class="app">
    <div class="main-wrapper">
      <main>
        <section class="content">

          <!-- BASIC INFO (REQUIRED) -->
          <div class="card">
            <h3>Basic Information</h3>

            <div class="grid">
              <div>
                <label>Job Title *</label>
                <input v-model="job.title" placeholder="e.g. Data Encoder">
              </div>

              <div>
                <label>Location *</label>
                <input v-model="job.location" placeholder="e.g. Manila">
              </div>

              <div>
                <label>Employment Type *</label>
                <select v-model="job.type">
                  <option disabled value="">Select type</option>
                  <option>Full-time</option>
                  <option>Part-time</option>
                  <option>Contract</option>
                </select>
              </div>

              <div>
                <label>Disability *</label>
                <select v-model="job.disabilityType">
                  <option disabled value="">Select Disability</option>
                  <option>Hearing</option>
                  <option>Visual</option>
                  <option>Mobility</option>
                  <option>Physical Impairment</option>
                  <option>Intellectual</option>
                  <option>Psychosocial</option>
                  <option>Multiple</option>
                  <option>Others</option>
                </select>
              </div>
            </div>
          </div>

          <!-- DESCRIPTION -->
          <div class="card">
            <h3>Job Description *</h3>
            <textarea
              v-model="job.description"
              rows="4"
              placeholder="Describe the tasks and responsibilities"
            ></textarea>
          </div>

          <!-- JOB PHOTOS -->
          <div class="card">
            <h3>Job Photos (Max 2)</h3>
            <div class="upload-grid">
              <label class="upload-box">
                <input type="file" accept="image/*" @change="handleImage">
                <span class="upload-title">Photo 1</span>
                <span class="upload-hint">Click to upload</span>
                <img v-if="preview" :src="preview" class="preview" alt="Preview 1">
              </label>

              <label class="upload-box">
                <input type="file" accept="image/*" @change="handleImage2">
                <span class="upload-title">Photo 2</span>
                <span class="upload-hint">Click to upload</span>
                <img v-if="preview2" :src="preview2" class="preview" alt="Preview 2">
              </label>
            </div>
          </div>


          <!-- TOGGLE OPTIONAL -->
          <button class="toggle-btn" @click="showMore = !showMore">
            {{ showMore ? "Hide Optional Fields" : "Add More Details (Optional)" }}
          </button>

          <!-- OPTIONAL FIELDS -->
          <div v-if="showMore">

            <div class="card">
              <h3>Qualifications</h3>
              <textarea v-model="job.qualifications" rows="3"></textarea>
            </div>

            <div class="card">
              <h3>Accessibility Information</h3>

              <div class="grid">
                <div>
                  <label>Supported Disability Type</label>
                  <select v-model="job.disabilityType">
                    <option disabled value="">Select Disability</option>
                    <option>Hearing</option>
                    <option>Visual</option>
                    <option>Mobility</option>
                    <option>Physical Impairment</option>
                    <option>Intellectual</option>
                    <option>Psychosocial</option>
                    <option>Multiple</option>
                    <option>Others</option>
                  </select>
                </div>

                <div>
                  <label>Workplace Accommodations</label>
                  <input v-model="job.accommodations">
                </div>
              </div>
            </div>

            <div class="card">
              <h3>Salary & Benefits</h3>

              <div class="grid">
                <div>
                  <label>Salary Range</label>
                  <input v-model="job.salary">
                </div>

                <div>
                  <label>Benefits</label>
                  <input v-model="job.benefits">
                </div>
              </div>
            </div>

          </div>

          <!-- ACTION -->
          <div class="actions">
            <button class="save" @click="saveJob">
              Save Job Post
            </button>
          </div>

        </section>
      </main>
    </div>
  </div>
</template>

<script>
import { auth, db } from "@/lib/client-platform"
import { onAuthStateChanged } from "@/lib/session-auth"
import { collection, addDoc, serverTimestamp, doc, getDoc } from "@/lib/laravel-data"
import api from "@/services/api"
import { toast } from "vue3-toastify"

export default {
  name: "JobPost",

  data() {
      return {
        showMore: false,
        imageFile: null,
        preview: null,
        imageFile2: null,
        preview2: null,

        job: {
          title: "",
          location: "",
          type: "",
          description: "",
          qualifications: "",
          disabilityType: "",
          accommodations: "",
          salary: "",
          benefits: "",
          imageUrl: "",
          imageUrl2: ""
        }
      }
    },

  methods: {
    waitForAuthUser(timeoutMs = 4000) {
      return new Promise((resolve) => {
        if (auth.currentUser) {
          resolve(auth.currentUser)
          return
        }

        let settled = false
        const timer = setTimeout(() => {
          if (settled) return
          settled = true
          unsubscribe()
          resolve(auth.currentUser || null)
        }, timeoutMs)

        const unsubscribe = onAuthStateChanged(auth, (user) => {
          if (settled) return
          settled = true
          clearTimeout(timer)
          unsubscribe()
          resolve(user || null)
        })
      })
    },

    async resolvePosterMeta() {
      const user = await this.waitForAuthUser()
      if (!user?.uid) return null

      let profile = {}
      try {
        const profileSnap = await getDoc(doc(db, "users", user.uid))
        if (profileSnap.exists()) {
          profile = profileSnap.data() || {}
        }
      } catch (err) {
        console.warn("Could not load user profile for post metadata:", err)
      }

      const email = String(
        profile.email || user.email || localStorage.getItem("userEmail") || ""
      ).trim()
      const name = String(
        profile.username ||
        profile.name ||
        localStorage.getItem("userName") ||
        email
      ).trim()
      const role = String(
        profile.role || localStorage.getItem("userRole") || ""
      ).trim()
      const companyId = String(
        profile.companyId || localStorage.getItem("companyId") || ""
      ).trim()
      const companyName = String(
        profile.companyName || localStorage.getItem("companyName") || ""
      ).trim()
      const createdByCompanyAdminUid = String(
        profile.createdByCompanyAdminUid || ""
      ).trim()

      if (!email) return null

      return {
        uid: user.uid,
        email,
        name,
        role,
        companyId,
        companyName,
        createdByCompanyAdminUid
      }
    },

    handleImage(e) {
      this.imageFile = e.target.files[0]
      if (this.imageFile) {
        this.preview = URL.createObjectURL(this.imageFile)
      }
    },

    handleImage2(e) {
      this.imageFile2 = e.target.files[0]
      if (this.imageFile2) {
        this.preview2 = URL.createObjectURL(this.imageFile2)
      }
    },

    async uploadToBackend(file) {
      const form = new FormData()
      form.append("image", file)
      const res = await api.post("/uploads", form, {
      })
      return res.data?.url
    },

    async saveJob() {

      if (
        !this.job.title ||
        !this.job.location ||
        !this.job.type ||
        !this.job.description
      ) {
        toast.warning("Please fill required fields")
        return
      }

      try {
        const poster = await this.resolvePosterMeta()
        if (!poster) {
          toast.error("Cannot identify poster account. Please login again.")
          return
        }

        // UPLOAD IMAGE (OPTIONAL)
        if (this.imageFile) {
          try {
            this.job.imageUrl = await this.uploadToBackend(this.imageFile)
          } catch (uploadErr) {
            console.error(uploadErr)
            toast.warning(uploadErr?.message || "Image 1 upload failed. Posting without image.")
          }
        }
        if (this.imageFile2) {
          try {
            this.job.imageUrl2 = await this.uploadToBackend(this.imageFile2)
          } catch (uploadErr) {
            console.error(uploadErr)
            toast.warning(uploadErr?.message || "Image 2 upload failed. Posting without image.")
          }
        }

        await addDoc(collection(db, "jobs"), {
          ...this.job,
          images: [this.job.imageUrl, this.job.imageUrl2].filter(Boolean),
          status: "open",
          postedByName: poster.name,
          postedByEmail: poster.email,
          postedByRole: poster.role,
          postedByUid: poster.uid,
          companyId: poster.companyId || "",
          companyName: poster.companyName || "",
          createdByCompanyAdminUid: poster.createdByCompanyAdminUid || "",
          createdAt: serverTimestamp()
        })

        toast.success("Job posted successfully!")

        // RESET FORM
        Object.keys(this.job).forEach(k => this.job[k] = "")
        this.imageFile = null
        this.preview = null
        this.imageFile2 = null
        this.preview2 = null
        this.showMore = false

      } catch (err) {
        console.error(err)
        toast.error("Failed to post job")
      }
    }
  }
}
</script>

<style scoped>
/* UPLOAD */
.upload-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}

.upload-box{
  border:1px dashed #cbd5f5;
  background:#f7f9ff;
  border-radius:12px;
  padding:14px;
  cursor:pointer;
  display:flex;
  flex-direction:column;
  gap:6px;
  align-items:flex-start;
  min-height:140px;
}

.upload-box input{
  display:none;
}

.upload-title{
  font-weight:700;
  color:#1f2937;
}

.upload-hint{
  font-size:12px;
  color:#6b7280;
}

.preview{
  margin-top:6px;
  width:100%;
  max-height:140px;
  border-radius:10px;
  border:1px solid #e5e7eb;
  object-fit:cover;
}

/* TOGGLE */
.toggle-btn{
  background:none;
  border:none;
  color:#2563eb;
  cursor:pointer;
  font-size:14px;
  margin-bottom:10px;
}

/* ROOT */
.app {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #f5f7fb;
}

.main-wrapper {
  flex: 1;
}

main {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.content {
  flex: 1;
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  overflow-y: auto;
}

/* CARD */
.card {
  background: #ffffff;
  padding: 22px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.card h3 {
  margin-bottom: 15px;
  color: #111827;
}

/* GRID */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
}

/* FORM */
label {
  font-size: 14px;
  color: #374151;
}

input,
select,
textarea {
  width: 100%;
  padding: 9px;
  margin-top: 5px;
  border-radius: 8px;
  border: 1px solid #d1d5db;
  font-size: 14px;
}

input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: #2563eb;
}

/* ACTION */
.actions {
  display: flex;
  justify-content: center;
  padding-top: 10px;
}

.save {
  background: #2563eb;
  color: white;
  border: none;
  padding: 12px 40px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 15px;
}

.save:hover {
  background: #1d4ed8;
}
</style>


