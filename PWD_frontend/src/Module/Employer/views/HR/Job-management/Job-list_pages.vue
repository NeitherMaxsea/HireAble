<template>
  <div class="app">

    <div class="main-wrapper">
      <main class="page-scroll">
        <section class="content">

          <!-- HEADER -->
          <div class="page-header">
            <div>
              <h2>Job Listings</h2>
              <p class="subtitle">Browse and manage your posted jobs.</p>
            </div>
            <button class="backfill-btn" :disabled="backfilling" @click="backfillLegacyJobAccounts">
              {{ backfilling ? "Fixing..." : "Fix Legacy Accounts" }}
            </button>
          </div>

          <!-- SEARCH -->
          <input
            type="text"
            class="search"
            placeholder="Search job title..."
            v-model="search"
          >

          <!-- JOB CARDS -->
          <div v-if="filteredJobs.length === 0" class="empty">
            No job posts yet.
          </div>

          <div v-else class="job-grid">
            <div
              class="job-card"
              v-for="job in filteredJobs"
              :key="job.id"
              @click="openView(job)"
            >
              <!-- TOP ROW -->
              <div class="job-top">
                <div class="location-map">
                  <iframe
                    v-if="job.location"
                    :src="getMapUrl(job)"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                  ></iframe>
                  <div v-else class="map-empty">No location</div>
                  <span class="location-label">
                    {{ job.location || "Not specified" }}
                  </span>
                </div>

                <span
                  class="badge"
                  :class="job.status === 'open' ? 'open' : (job.status === 'pending_finance_review' ? 'pending' : 'closed')"
                >
                  {{ job.status }}
                </span>
              </div>

              <!-- BODY -->
              <div class="job-body">
                <h3>{{ job.title }}</h3>
                <p>{{ job.description }}</p>

                <div class="job-meta">
                  <span>Type: {{ job.type || "Open" }}</span>
                  <span>Salary: {{ job.salary || "Negotiable" }}</span>
                </div>

                <div class="actions">
                  <button class="view" type="button" @click.stop.prevent="openView(job)">
                    View
                  </button>

                  <button
                    v-if="job.status === 'open'"
                    class="close"
                    type="button"
                    :disabled="busyJobId === job.id"
                    @click.stop.prevent="closeJob(job)"
                  >
                    Close
                  </button>

                  <button
                    v-else-if="job.status === 'closed'"
                    class="reopen"
                    type="button"
                    :disabled="busyJobId === job.id"
                    @click.stop.prevent="reopenJob(job)"
                  >
                    Reopen
                  </button>

                  <button
                    class="delete"
                    type="button"
                    :disabled="busyJobId === job.id"
                    @click.stop.prevent="deleteJob(job)"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>

        </section>
      </main>
    </div>

    <!-- EDIT MODAL -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal edit-modal">

        <div class="edit-header">
          <div>
            <h3>Edit Job</h3>
            <p class="subtitle">Update details and photos</p>
          </div>
          <button class="close-x" @click="showModal=false">Ã—</button>
        </div>

        <div class="edit-grid">
          <div class="field">
            <label>Job Title</label>
            <input v-model="editJobData.title" placeholder="Job Title">
          </div>

          <div class="field">
            <label>Location</label>
            <select v-model="editJobData.location">
              <option disabled value="">Select barangay</option>
              <option
                v-for="barangay in dasmaBarangays"
                :key="barangay"
                :value="barangay"
              >
                {{ barangay }}
              </option>
            </select>
          </div>

          <div class="field">
            <label>Exact Address</label>
            <input v-model="editJobData.exactAddress" placeholder="Street, block, lot, landmark">
          </div>

          <div class="field">
            <label>Employment Type</label>
            <select v-model="editJobData.type">
              <option disabled value="">Select type</option>
              <option>Full-time</option>
              <option>Part-time</option>
              <option>Contract</option>
            </select>
          </div>

          <div class="field">
            <label>Salary</label>
            <input v-model="editJobData.salary" placeholder="Salary">
          </div>

          <div class="field full">
            <label>Description</label>
            <textarea v-model="editJobData.description" placeholder="Description"></textarea>
          </div>
        </div>

        <div class="edit-uploads">
          <div class="edit-upload">
            <label>Photo 1</label>
            <input type="file" accept="image/*" @change="handleEditImage($event, 1)">
            <img v-if="editImagePreview1" :src="editImagePreview1" class="edit-preview" alt="Preview 1">
            <button
              v-if="editImagePreview1"
              class="remove-photo"
              type="button"
              @click="clearEditImage(1)"
            >
              Remove
            </button>
          </div>

          <div class="edit-upload">
            <label>Photo 2</label>
            <input type="file" accept="image/*" @change="handleEditImage($event, 2)">
            <img v-if="editImagePreview2" :src="editImagePreview2" class="edit-preview" alt="Preview 2">
            <button
              v-if="editImagePreview2"
              class="remove-photo"
              type="button"
              @click="clearEditImage(2)"
            >
              Remove
            </button>
          </div>
        </div>

        <div class="modal-actions">
          <button class="save" @click="updateJob">Save</button>
          <button class="cancel" @click="showModal=false">Cancel</button>
        </div>

      </div>
    </div>

    <!-- VIEW MODAL -->
    <transition name="modal-fade">
      <div v-if="showViewModal" class="modal-overlay">
        <div class="modal view-modal">
        <div class="view-header">
          <div>
            <h3>{{ viewJobData.title || "Job Details" }}</h3>
            <p class="subtitle">Review full job information</p>
          </div>
          <button class="close-x" @click="showViewModal=false">Ã—</button>
        </div>

        <div class="view-body">
          <div class="detail-grid">
            <div>
              <h4>Location</h4>
              <p>{{ viewJobData.location || "Not specified" }}</p>
            </div>
            <div>
              <h4>Exact Address</h4>
              <p>{{ viewJobData.exactAddress || "Not specified" }}</p>
            </div>
            <div>
              <h4>Type</h4>
              <p>{{ viewJobData.type || "Open" }}</p>
            </div>
            <div>
              <h4>Salary</h4>
              <p>{{ viewJobData.salary || "Negotiable" }}</p>
            </div>
            <div>
              <h4>Status</h4>
              <p class="status-pill" :class="viewJobData.status === 'open' ? 'open' : 'closed'">
                {{ viewJobData.status || "open" }}
              </p>
            </div>
          </div>

          <div class="detail-block">
            <h4>Description</h4>
            <p>{{ viewJobData.description || "No description provided." }}</p>
          </div>

          <div class="detail-block">
            <h4>Map</h4>
            <iframe
              v-if="viewJobData.location"
              :src="getMapUrl(viewJobData)"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
            <p v-else class="map-empty">No location provided</p>
          </div>

          <div class="detail-block">
            <h4>Photos</h4>
            <div class="photo-grid">
              <img
                v-if="viewJobData.imageUrl"
                :src="viewJobData.imageUrl"
                alt="Job Photo 1"
              />
              <div v-else class="photo-placeholder">No photo</div>

              <img
                v-if="viewJobData.imageUrl2"
                :src="viewJobData.imageUrl2"
                alt="Job Photo 2"
              />
              <div v-else class="photo-placeholder">No photo</div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="save" @click="openEditFromView">Edit</button>
          <button class="cancel" @click="showViewModal=false">Close</button>
        </div>
      </div>
    </div>
    </transition>

  </div>
</template>

<script>
import { auth } from "@/lib/client-platform"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import { onAuthStateChanged } from "@/lib/session-auth"
import { DASMA_BARANGAYS } from "@/constants/dasmaBarangays"

let pollTimer = null

const toast = {
  success(text) {
    Toastify({ text, backgroundColor: "#16a34a" }).showToast()
  },
  error(text) {
    Toastify({ text, backgroundColor: "#dc2626" }).showToast()
  },
  warning(text) {
    Toastify({ text, backgroundColor: "#f59e0b" }).showToast()
  },
  info(text) {
    Toastify({ text, backgroundColor: "#2563eb" }).showToast()
  },
}

export default {
  name: "JobListings",

  data() {
    return {
      jobs: [],
      dasmaBarangays: DASMA_BARANGAYS,
      search: "",
      backfilling: false,
      busyJobId: null,
      showModal: false,
      editJobData: {},
      showViewModal: false,
      viewJobData: {},
      editImageFile1: null,
      editImageFile2: null,
      editImagePreview1: "",
      editImagePreview2: ""
    }
  },

  computed: {
    filteredJobs() {
      return this.jobs.filter(job =>
        String(job?.title || "").toLowerCase().includes(this.search.toLowerCase())
      )
    }
  },

  async mounted() {
    await this.startJobsSync()
  },

  beforeUnmount() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
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

    async startJobsSync() {
      const actor = await this.resolveActorMeta()
      if (!actor?.uid) {
        toast.error("Cannot load jobs. Please login again.")
        return
      }

      await this.loadJobs(actor)
      if (pollTimer) clearInterval(pollTimer)
      pollTimer = setInterval(async () => {
        const freshActor = await this.resolveActorMeta()
        if (!freshActor?.uid) return
        await this.loadJobs(freshActor)
      }, 30000)
    },

    async loadJobs(actor) {
      try {
        const params = actor?.companyId
          ? { companyId: actor.companyId }
          : { postedByUid: actor.uid }

        const response = await api.get("/jobs", { params })
        const rows = Array.isArray(response?.data) ? response.data : []

        this.jobs = rows
          .map((raw) => {
            const images = Array.isArray(raw?.images) ? raw.images.filter(Boolean) : []
            return {
              id: String(raw?.id || ""),
              ...raw,
              imageUrl: raw?.imageUrl || images[0] || "",
              imageUrl2: raw?.imageUrl2 || images[1] || "",
            }
          })
          .sort((a, b) => Date.parse(String(b.createdAt || "")) - Date.parse(String(a.createdAt || "")))

        if (this.showViewModal && this.viewJobData?.id) {
          const fresh = this.jobs.find((job) => job.id === this.viewJobData.id)
          this.viewJobData = fresh ? { ...fresh } : {}
        }

        if (this.showModal && this.editJobData?.id) {
          const fresh = this.jobs.find((job) => job.id === this.editJobData.id)
          if (fresh) this.editJobData = { ...fresh }
        }
      } catch (err) {
        console.error(err)
        toast.error(err?.response?.data?.message || "Failed to load jobs.")
      }
    },

    async resolveActorMeta() {
      const user = await this.waitForAuthUser()
      if (!user?.uid) return null

      const email = String(
        user.email || localStorage.getItem("userEmail") || ""
      ).trim()
      const name = String(
        localStorage.getItem("userName") ||
        email
      ).trim()
      const role = String(localStorage.getItem("userRole") || "").trim()
      const companyId = String(localStorage.getItem("companyId") || "").trim()
      const companyName = String(localStorage.getItem("companyName") || "").trim()

      if (!email) return null
      return {
        uid: user.uid,
        email,
        name,
        role,
        companyId,
        companyName
      }
    },

    hasPostedBy(job) {
      return !!(
        String(job.postedByEmail || "").trim() ||
        String(job.postedByName || "").trim() ||
        String(job.postedByUid || "").trim()
      )
    },

    async backfillLegacyJobAccounts() {
      const legacyJobs = this.jobs.filter((job) => !this.hasPostedBy(job))
      if (!legacyJobs.length) {
        toast.info("No legacy jobs found. All accounts are already set.")
        return
      }

      const actor = await this.resolveActorMeta()
      if (!actor) {
        toast.error("Cannot identify your account. Please login again.")
        return
      }

      this.backfilling = true
      try {
        await Promise.all(
          legacyJobs.map((job) =>
            api.put(`/jobs/${job.id}`, {
            postedByName: actor.name,
            postedByEmail: actor.email,
            postedByRole: actor.role,
            postedByUid: actor.uid,
            companyId: actor.companyId || "",
            companyName: actor.companyName || "",
          })
        ))
        await this.loadJobs(actor)
        toast.success(`Updated ${legacyJobs.length} legacy job account(s).`)
      } catch (err) {
        console.error(err)
        toast.error("Failed to update legacy job accounts.")
      } finally {
        this.backfilling = false
      }
    },

    openEdit(job) {
      this.editJobData = { ...job }
      this.editImageFile1 = null
      this.editImageFile2 = null
      this.editImagePreview1 = job.imageUrl || ""
      this.editImagePreview2 = job.imageUrl2 || ""
      this.showModal = true
    },

    openView(job) {
      if (!job?.id) {
        toast.error("Unable to open job details.")
        return
      }
      this.viewJobData = { ...job }
      this.showViewModal = true
    },

    openEditFromView() {
      this.showViewModal = false
      this.openEdit(this.viewJobData)
    },

    handleEditImage(e, slot) {
      const file = e.target.files?.[0]
      if (!file) return
      if (slot === 1) {
        this.editImageFile1 = file
        this.editImagePreview1 = URL.createObjectURL(file)
      } else {
        this.editImageFile2 = file
        this.editImagePreview2 = URL.createObjectURL(file)
      }
    },

    clearEditImage(slot) {
      if (slot === 1) {
        this.editImageFile1 = null
        this.editImagePreview1 = ""
        this.editJobData.imageUrl = ""
      } else {
        this.editImageFile2 = null
        this.editImagePreview2 = ""
        this.editJobData.imageUrl2 = ""
      }
    },

    async uploadImage(file) {
      const form = new FormData()
      form.append("image", file)
      const res = await api.post("/uploads", form)
      const payload = this.normalizeUploadResponse(res?.data)
      const url = String(payload?.url || "").trim()
      if (url) return url

      const path = String(payload?.path || "").trim()
      if (path) return this.buildUploadUrlFromPath(path)

      throw new Error("Upload response missing image URL.")
    },

    isLikelyPath(value) {
      const text = String(value || "").trim().replace(/\\+/g, "/")
      if (!text) return false
      if (/^https?:\/\//i.test(text)) return false
      return /^\/?(uploads|storage)\//i.test(text) || /\/uploads\//i.test(text)
    },

    normalizePathString(value) {
      const text = String(value || "").trim().replace(/\\+/g, "/")
      if (!text) return ""

      const withoutQuery = text.split("?")[0].split("#")[0]

      if (/^https?:\/\//i.test(withoutQuery)) {
        try {
          const url = new URL(withoutQuery)
          const marker = "/uploads/"
          const idx = url.pathname.toLowerCase().indexOf(marker)
          if (idx >= 0) {
            const tail = url.pathname.slice(idx + marker.length).replace(/^\/+/, "")
            if (!tail) return ""
            return /^uploads\//i.test(tail) ? tail : `uploads/${tail}`
          }
        } catch {
          return ""
        }
      }

      const marker = "/uploads/"
      const lower = withoutQuery.toLowerCase()
      const markerIdx = lower.indexOf(marker)
      if (markerIdx >= 0) {
        const tail = withoutQuery.slice(markerIdx + marker.length).replace(/^\/+/, "")
        if (!tail) return ""
        return /^uploads\//i.test(tail) ? tail : `uploads/${tail}`
      }

      return withoutQuery.replace(/^\/+/, "")
    },

    pickFirstStringByKeys(source, keys) {
      if (!source || typeof source !== "object") return ""
      for (const key of keys) {
        const value = source[key]
        if (typeof value === "string" && value.trim()) {
          return value.trim()
        }
      }
      return ""
    },

    findFirstStringDeep(value, predicate, depth = 0) {
      if (depth > 4 || value == null) return ""
      if (typeof value === "string" && predicate(value)) return value.trim()
      if (Array.isArray(value)) {
        for (const item of value) {
          const found = this.findFirstStringDeep(item, predicate, depth + 1)
          if (found) return found
        }
        return ""
      }
      if (typeof value === "object") {
        for (const nested of Object.values(value)) {
          const found = this.findFirstStringDeep(nested, predicate, depth + 1)
          if (found) return found
        }
      }
      return ""
    },

    normalizeUploadResponse(raw) {
      let payload = raw

      if (typeof payload === "string") {
        const text = payload.trim()
        try {
          payload = JSON.parse(text)
        } catch {
          const objectStart = text.indexOf("{")
          const objectEnd = text.lastIndexOf("}")
          if (objectStart >= 0 && objectEnd > objectStart) {
            try {
              payload = JSON.parse(text.slice(objectStart, objectEnd + 1))
            } catch {
              payload = text
            }
          } else {
            payload = text
          }
        }
      }

      if (Array.isArray(payload)) {
        payload = payload[0] ?? {}
      }

      if (payload && typeof payload === "object") {
        if (payload.data != null) {
          payload = payload.data
        } else if (payload.result != null) {
          payload = payload.result
        } else if (payload.upload != null) {
          payload = payload.upload
        }
      }

      if (Array.isArray(payload)) {
        payload = payload[0] ?? {}
      }

      if (typeof payload === "string") {
        const normalized = payload.replace(/\\\//g, "/")
        const urlMatch = normalized.match(/https?:\/\/[^\s"'<>()]+/i)
        const normalizedPath = this.normalizePathString(normalized)
        return {
          url: urlMatch ? urlMatch[0] : "",
          path: this.isLikelyPath(normalizedPath) ? normalizedPath : "",
        }
      }

      const explicitUrl = this.pickFirstStringByKeys(payload, [
        "url",
        "downloadUrl",
        "downloadURL",
        "secure_url",
        "fileUrl",
        "fileURL",
        "image_url",
        "imageUrl",
        "photo_url",
        "photoUrl",
        "src",
        "location",
      ])

      const explicitPath = this.pickFirstStringByKeys(payload, [
        "path",
        "filePath",
        "filepath",
        "image_path",
        "imagePath",
        "photo_path",
        "photoPath",
      ])

      const deepUrl = this.findFirstStringDeep(payload, (text) => /https?:\/\//i.test(String(text)))
      const deepPath = this.findFirstStringDeep(payload, (text) => this.isLikelyPath(text))

      const url = explicitUrl || deepUrl
      const normalizedPath = this.normalizePathString(explicitPath || deepPath)

      return {
        url: String(url || "").trim(),
        path: this.isLikelyPath(normalizedPath) ? normalizedPath : "",
      }
    },

    getApiOrigin() {
      const apiBase = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api"
      try {
        return new URL(apiBase).origin
      } catch {
        return window.location.origin
      }
    },

    buildUploadUrlFromPath(path) {
      const cleaned = String(path || "").replace(/^\/+/, "").replace(/\\/g, "/")
      if (!cleaned) return ""
      const encodedPath = cleaned
        .split("/")
        .filter(Boolean)
        .map((segment) => encodeURIComponent(segment))
        .join("/")
      return `${this.getApiOrigin()}/api/uploads/${encodedPath}`
    },

    getMapUrl(job) {
      const location = String(job?.location || "").trim()
      if (!location) return ""
      const exactAddress = String(job?.exactAddress || "").trim()
      const query = [exactAddress, location, "Dasmarinas, Cavite"].filter(Boolean).join(", ")
      return `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`
    },

    async updateJob() {
      try {
        let uploadFailed = false

        if (this.editImageFile1) {
          try {
            const url = await this.uploadImage(this.editImageFile1)
            this.editJobData.imageUrl = url
          } catch (uploadErr) {
            console.error("Photo 1 upload failed:", uploadErr)
            toast.warning(uploadErr?.message || "Photo 1 upload failed.")
            uploadFailed = true
          }
        }
        if (this.editImageFile2) {
          try {
            const url = await this.uploadImage(this.editImageFile2)
            this.editJobData.imageUrl2 = url
          } catch (uploadErr) {
            console.error("Photo 2 upload failed:", uploadErr)
            toast.warning(uploadErr?.message || "Photo 2 upload failed.")
            uploadFailed = true
          }
        }
        if (uploadFailed) {
          toast.error("Job update cancelled. Please re-upload failed photo(s).")
          return
        }
        if (
          this.editImageFile1 &&
          this.editImageFile2 &&
          this.editJobData.imageUrl &&
          this.editJobData.imageUrl === this.editJobData.imageUrl2
        ) {
          toast.error("Photo 1 and Photo 2 are the same URL. Please upload two different photos.")
          return
        }

        await api.put(`/jobs/${this.editJobData.id}`, {
          title: this.editJobData.title,
          description: this.editJobData.description,
          location: this.editJobData.location,
          exactAddress: this.editJobData.exactAddress,
          salary: this.editJobData.salary,
          type: this.editJobData.type || "",
          imageUrl: this.editJobData.imageUrl || "",
          imageUrl2: this.editJobData.imageUrl2 || "",
          images: [this.editJobData.imageUrl, this.editJobData.imageUrl2].filter(Boolean)
        })

        this.showModal = false
        this.editImageFile1 = null
        this.editImageFile2 = null
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success("Job updated successfully")
      } catch (err) {
        console.error(err)
        const message =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to update job"
        toast.error(message)
      }
    },

    async closeJob(job) {
      const id = job?.id
      if (!id) {
        toast.error("Invalid job record.")
        return
      }

      this.busyJobId = id
      try {
        await api.put(`/jobs/${id}`, {
          status: "closed",
        })
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success("Job closed.")
      } catch (err) {
        console.error(err)
        toast.error(err?.message || "Failed to close job.")
      } finally {
        this.busyJobId = null
      }
    },

    async reopenJob(job) {
      const id = job?.id
      if (!id) {
        toast.error("Invalid job record.")
        return
      }

      this.busyJobId = id
      try {
        await api.put(`/jobs/${id}`, {
          status: "open",
        })
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success("Job reopened.")
      } catch (err) {
        console.error(err)
        toast.error(err?.message || "Failed to reopen job.")
      } finally {
        this.busyJobId = null
      }
    },

    async deleteJob(job) {
      const id = job?.id
      if (!id) {
        toast.error("Invalid job record.")
        return
      }

      if (!window.confirm("Delete this job?")) return

      this.busyJobId = id
      try {
        await api.delete(`/jobs/${id}`)
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success("Job deleted.")
      } catch (err) {
        console.error(err)
        toast.error(err?.message || "Failed to delete job.")
      } finally {
        this.busyJobId = null
      }
    }

  }
}
</script>

<style scoped>

/* ROOT */
.app{
  display:flex;
  background:radial-gradient(1200px 400px at 10% -10%, #e0f2fe 0%, rgba(224,242,254,0) 60%),
             radial-gradient(900px 500px at 110% 10%, #fef3c7 0%, rgba(254,243,199,0) 55%),
             #f7f8fc;
  height:100%;
}

/* WRAPPER */
.main-wrapper{
  flex:1;
  display:flex;
  flex-direction:column;
  min-height:0;
}

/* SCROLL */
.page-scroll{
  flex:1;
  overflow-y:auto;
}

/* CONTENT */
.content{
  padding:30px;
  max-width:1200px;
  width:100%;
  margin:0 auto;
}

/* HEADER */
.page-header{
  margin-bottom:18px;
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:16px;
}

.page-header h2{
  font-size:26px;
  letter-spacing:-0.2px;
}

.subtitle{
  font-size:14px;
  color:#64748b;
}

.backfill-btn{
  border:none;
  border-radius:10px;
  background:#2563eb;
  color:#ffffff;
  padding:10px 14px;
  font-size:13px;
  font-weight:600;
  cursor:pointer;
  white-space:nowrap;
}

.backfill-btn:disabled{
  opacity:.7;
  cursor:not-allowed;
}

/* SEARCH */
.search{
  width:100%;
  max-width:420px;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid #e2e8f0;
  background:#ffffff;
  margin-bottom:22px;
  box-shadow:0 6px 18px rgba(15, 23, 42, 0.06);
}

/* GRID */
.job-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
  gap:22px;
}

/* CARD */
.job-card{
  background:white;
  border-radius:18px;
  padding:16px;
  border:1px solid #e5e7eb;
  box-shadow:0 12px 24px rgba(15, 23, 42, 0.08);
  transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  position:relative;
  overflow:hidden;
  cursor:pointer;
}

.job-card:hover{
  transform:translateY(-4px);
  box-shadow:0 18px 36px rgba(15, 23, 42, 0.12);
  border-color:#c7d2fe;
}

/* TOP ROW */
.job-top{
  display:flex;
  justify-content:center;
  align-items:center;
  margin:-16px -16px 10px;
  padding:12px 14px;
  border-bottom:1px solid #e5e7eb;
  border-radius:18px 18px 12px 12px;
  position:relative;
  box-shadow:0 6px 16px rgba(15, 23, 42, 0.08);
  background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

/* LOCATION */
.location{
  position:relative;
  z-index:1;
  background:rgba(255,255,255,0.85);
  color:#1f2937;
  padding:6px 10px;
  border-radius:10px;
  font-size:12px;
  font-weight:600;
  border:1px solid rgba(148,163,184,0.35);
  backdrop-filter: blur(6px);
}

.location-map{
  position:relative;
  width:100%;
  max-width:520px;
  height:180px;
  border-radius:12px;
  overflow:hidden;
  border:1px solid #e2e8f0;
  background:#f8fafc;
  margin:0 auto;
}

.location-map iframe{
  width:100%;
  height:100%;
  border:0;
  pointer-events:none;
}

.location-label{
  position:absolute;
  left:8px;
  bottom:6px;
  background:rgba(255,255,255,0.92);
  color:#111827;
  font-size:11px;
  font-weight:600;
  padding:3px 6px;
  border-radius:8px;
  border:1px solid rgba(148,163,184,0.4);
  max-width:90%;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.map-empty{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#94a3b8;
  font-size:12px;
}

/* BADGE */
.badge{
  padding:5px 12px;
  border-radius:999px;
  font-size:12px;
  font-weight:600;
  text-transform:capitalize;
}

.job-top .badge{
  position:absolute;
  top:12px;
  right:14px;
}

.open{
  background:#dcfce7;
  color:#166534;
}

.pending{
  background:#fef3c7;
  color:#92400e;
}

.closed{
  background:#fee2e2;
  color:#991b1b;
}

/* BODY */
.job-body h3{
  margin:8px 0 6px;
  font-size:18px;
  color:#111827;
}

.job-body p{
  font-size:14px;
  color:#475569;
}

/* META */
.job-meta{
  display:flex;
  gap:14px;
  font-size:13px;
  margin:12px 0 14px;
  color:#475569;
}

/* ACTIONS */
.actions{
  display:flex;
  gap:8px;
  margin-top:6px;
  flex-wrap:wrap;
}

.actions button{
  padding:8px 12px;
  border:none;
  border-radius:12px;
  font-size:12px;
  cursor:pointer;
  color:white;
  letter-spacing:0.2px;
  font-weight:600;
  display:inline-flex;
  align-items:center;
  gap:6px;
  box-shadow:0 6px 14px rgba(15, 23, 42, 0.15);
  transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
}

.actions button:hover{
  transform:translateY(-1px);
  box-shadow:0 10px 20px rgba(15, 23, 42, 0.2);
  filter:brightness(1.03);
}

.actions button:active{
  transform:translateY(0);
  box-shadow:0 6px 12px rgba(15, 23, 42, 0.18);
}

.actions button:disabled{
  opacity:.65;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}

.view{ background:linear-gradient(135deg, #0ea5e9, #0284c7); }
.close{ background:linear-gradient(135deg, #ef4444, #b91c1c); }
.reopen{ background:linear-gradient(135deg, #f59e0b, #d97706); }
.delete{ background:linear-gradient(135deg, #7f1d1d, #4c0519); }

.empty{
  background:#ffffff;
  border:1px dashed #cbd5f5;
  padding:20px;
  border-radius:14px;
  color:#64748b;
  font-style:italic;
}

/* MODAL */
.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.5);
  display:flex;
  align-items:center;
  justify-content:center;
}

.modal-fade-enter-active,
.modal-fade-leave-active{
  transition:opacity .22s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to{
  opacity:0;
}

.modal-fade-enter-active .modal{
  transition:transform .22s ease, opacity .22s ease;
}

.modal-fade-enter-from .modal{
  transform:translateY(6px);
  opacity:0;
}

.modal{
  background:white;
  padding:22px;
  border-radius:14px;
  width:340px;
  box-shadow:0 18px 40px rgba(15, 23, 42, 0.18);
}

.edit-modal{
  width:min(860px, 94vw);
}

.view-modal{
  width:min(820px, 92vw);
}

.edit-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
}

.edit-grid{
  display:grid;
  grid-template-columns:repeat(2, minmax(0, 1fr));
  gap:12px;
}

.field label{
  display:block;
  font-size:12px;
  color:#64748b;
  margin-bottom:6px;
  text-transform:uppercase;
  letter-spacing:0.6px;
}

.field.full{
  grid-column:1 / -1;
}

.edit-grid input,
.edit-grid textarea,
.edit-grid select{
  margin:0;
}

.edit-grid textarea{
  min-height:90px;
  resize:vertical;
}

.view-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:12px;
}

.close-x{
  border:none;
  background:#f1f5f9;
  color:#334155;
  width:32px;
  height:32px;
  border-radius:999px;
  cursor:pointer;
  font-size:18px;
  line-height:1;
}

.view-body{
  display:flex;
  flex-direction:column;
  gap:16px;
}

.detail-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
  gap:12px;
  background:#f8fafc;
  border:1px solid #e2e8f0;
  padding:12px;
  border-radius:12px;
}

.detail-grid h4,
.detail-block h4{
  margin:0 0 6px;
  font-size:12px;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:0.6px;
}

.detail-grid p,
.detail-block p{
  margin:0;
  color:#111827;
  font-size:14px;
}

.detail-block{
  background:#ffffff;
  border:1px solid #e5e7eb;
  border-radius:12px;
  padding:12px;
}

.detail-block iframe{
  width:100%;
  height:320px;
  border:0;
  border-radius:12px;
}

.map-empty{
  color:#94a3b8;
  font-size:13px;
  margin:0;
}

.status-pill{
  display:inline-flex;
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  text-transform:capitalize;
}

.photo-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
}

.photo-grid img{
  width:100%;
  height:160px;
  object-fit:cover;
  border-radius:12px;
  border:1px solid #e5e7eb;
}

.photo-placeholder{
  height:160px;
  border-radius:12px;
  border:1px dashed #e5e7eb;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#94a3b8;
  background:#f8fafc;
  font-size:12px;
}

.modal input,
.modal textarea{
  width:100%;
  margin-bottom:12px;
  padding:10px;
  border-radius:8px;
  border:1px solid #cbd5f5;
}

.modal select{
  width:100%;
  margin-bottom:12px;
  padding:10px;
  border-radius:8px;
  border:1px solid #cbd5f5;
  background:#ffffff;
}

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
}

.save{
  background:#2563eb;
  color:white;
  padding:9px 16px;
}

.cancel{
  background:#64748b;
  color:white;
  padding:9px 16px;
}

.edit-uploads{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
  margin-top:6px;
}

.edit-upload{
  border:1px dashed #cbd5f5;
  border-radius:12px;
  padding:10px;
  background:#f8fafc;
}

.edit-upload label{
  display:block;
  font-size:12px;
  color:#475569;
  margin-bottom:6px;
}

.edit-preview{
  width:100%;
  height:120px;
  object-fit:cover;
  border-radius:10px;
  border:1px solid #e5e7eb;
  margin-top:8px;
}

.remove-photo{
  margin-top:6px;
  border:none;
  background:#e2e8f0;
  color:#334155;
  font-size:12px;
  padding:6px 10px;
  border-radius:8px;
  cursor:pointer;
}

@media (max-width: 700px){
  .edit-grid{
    grid-template-columns:1fr;
  }
  .edit-uploads{
    grid-template-columns:1fr;
  }
}

</style>



