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
        </div>

          <!-- SEARCH -->
          <input
            type="text"
            class="search"
            placeholder="Search job title..."
            v-model="search"
          >

          <div v-if="filteredJobs.length > 0" class="bulk-actions">
            <button class="ghost-btn" type="button" @click="toggleSelectionMode">
              {{ selectionMode ? "Done" : "Manage Jobs" }}
            </button>

            <template v-if="selectionMode">
              <label class="bulk-check">
                <input
                  type="checkbox"
                  :checked="isAllFilteredSelected"
                  :disabled="deletingSelected"
                  @change="toggleSelectAllFiltered"
                >
                <span>Select all</span>
              </label>
              <p class="bulk-meta">{{ selectedJobIds.length }} selected</p>
              <button
                class="ghost-btn"
                type="button"
                :disabled="selectedJobIds.length === 0 || deletingSelected"
                @click="clearSelectedJobs"
              >
                Clear
              </button>
              <button
                class="danger-soft-btn"
                type="button"
                :disabled="selectedJobIds.length === 0 || deletingSelected"
                @click="deleteSelectedJobs"
              >
                {{ deletingSelected ? "Deleting..." : "Delete selected" }}
              </button>
            </template>
          </div>

          <!-- JOB CARDS -->
          <div v-if="filteredJobs.length === 0" class="empty">
            No job posts yet.
          </div>

          <div v-else class="job-grid">
            <div
              class="job-card"
              :class="{ selected: isJobSelected(job.id) }"
              v-for="job in filteredJobs"
              :key="job.id"
              @click="openView(job)"
            >
              <div v-if="selectionMode" class="card-check">
                <label class="item-check">
                  <input
                    type="checkbox"
                    :checked="isJobSelected(job.id)"
                    :disabled="deletingSelected"
                    @click.stop
                    @change="toggleJobSelection(job.id)"
                  >
                  <span>Select</span>
                </label>
              </div>

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
                  :class="statusBadgeClass(job)"
                >
                  {{ statusLabel(job) }}
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
                    {{ isRejectedJob(job) ? "Review" : "View" }}
                  </button>

                  <button
                    v-if="isOpenJob(job)"
                    class="close"
                    type="button"
                    :disabled="busyJobId === job.id || deletingSelected"
                    @click.stop.prevent="closeJob(job)"
                  >
                    Close
                  </button>

                  <button
                    v-else-if="isClosedJob(job)"
                    class="reopen"
                    type="button"
                    :disabled="busyJobId === job.id || deletingSelected"
                    @click.stop.prevent="reopenJob(job)"
                  >
                    Reopen
                  </button>

                  <button
                    v-else-if="isRejectedJob(job)"
                    class="appeal"
                    type="button"
                    :disabled="busyJobId === job.id || deletingSelected"
                    @click.stop.prevent="openAppeal(job)"
                  >
                    Revise & Appeal
                  </button>

                  <button
                    class="delete"
                    type="button"
                    :disabled="busyJobId === job.id || deletingSelected"
                    @click.stop.prevent="promptDeleteJob(job)"
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
            <p class="subtitle">{{ isRejectedJob(viewJobData) ? "Finance rejection review" : "Review full job information" }}</p>
          </div>
          <button class="close-x" @click="showViewModal=false">Ã—</button>
        </div>

        <div v-if="isRejectedJob(viewJobData)" class="view-body view-body-pro review-focus-body">
          <div class="review-focus-head">
            <p class="review-focus-kicker">Finance Review Result</p>
            <div class="review-focus-title-row">
              <h4 class="review-focus-title">Rejected</h4>
              <p class="status-pill rejected">rejected</p>
            </div>
          </div>

          <div class="review-reason-panel">
            <p class="review-reason-label">Rejection Reason</p>
            <p class="review-reason-text">{{ getRejectionReason(viewJobData) }}</p>
          </div>

          <div class="review-support-grid">
            <div class="review-support-item">
              <span>Finance Status</span>
              <strong>{{ viewJobData.financeApprovalStatus || "pending" }}</strong>
            </div>
            <div class="review-support-item">
              <span>Job Title</span>
              <strong>{{ viewJobData.title || "Untitled job" }}</strong>
            </div>
            <div class="review-support-item">
              <span>Posted By</span>
              <strong>{{ viewJobData.postedByName || viewJobData.postedByEmail || "N/A" }}</strong>
            </div>
            <div class="review-support-item">
              <span>Location</span>
              <strong>{{ viewJobData.location || "Not specified" }}</strong>
            </div>
          </div>
        </div>

        <div v-else class="view-body view-body-pro">
          <div class="view-summary-card">
            <div class="view-summary-top">
              <div>
                <p class="view-summary-label">Job Overview</p>
                <h4 class="view-summary-title">{{ viewJobData.title || "Untitled Job" }}</h4>
                <p class="view-summary-subtitle">{{ viewJobData.companyName || "Company not specified" }}</p>
              </div>
              <p
                class="status-pill"
                :class="statusBadgeClass(viewJobData)"
              >
                {{ statusLabel(viewJobData) }}
              </p>
            </div>

            <div class="view-chip-row">
              <span class="detail-chip"><strong>Location:</strong> {{ viewJobData.location || "Not specified" }}</span>
              <span class="detail-chip"><strong>Type:</strong> {{ viewJobData.type || "Open" }}</span>
              <span class="detail-chip"><strong>Vacancies:</strong> {{ viewJobData.vacancies || viewJobData.slots || 1 }}</span>
              <span class="detail-chip"><strong>Salary:</strong> {{ viewJobData.salary || "Negotiable" }}</span>
              <span class="detail-chip"><strong>Disability:</strong> {{ viewJobData.disabilityType || "Not specified" }}</span>
            </div>
          </div>

          <div class="view-layout-grid">
            <div class="view-main-stack">
              <div class="detail-block">
                <h4>Description</h4>
                <p>{{ viewJobData.description || "No description provided." }}</p>
              </div>

              <div class="detail-block">
                <h4>Qualifications</h4>
                <ul v-if="toDetailList(viewJobData.qualifications).length" class="detail-list">
                  <li v-for="(item, idx) in toDetailList(viewJobData.qualifications)" :key="`qual-${idx}`">
                    {{ item }}
                  </li>
                </ul>
                <p v-else>No qualifications provided.</p>
              </div>

              <div class="detail-block">
                <h4>Workplace Accommodations</h4>
                <ul v-if="toDetailList(viewJobData.accommodations).length" class="detail-list">
                  <li v-for="(item, idx) in toDetailList(viewJobData.accommodations)" :key="`acc-${idx}`">
                    {{ item }}
                  </li>
                </ul>
                <p v-else>No accommodations provided.</p>
              </div>

              <div class="detail-block">
                <h4>Job Photos</h4>
                <div class="photo-grid photo-grid-pro">
                  <template v-if="viewJobData.imageUrl || viewJobData.imageUrl2">
                    <div v-if="viewJobData.imageUrl" class="photo-card">
                      <img :src="viewJobData.imageUrl" alt="Job Photo 1" />
                      <span class="photo-caption">Photo 1</span>
                    </div>
                    <div v-else class="photo-placeholder">No photo 1</div>

                    <div v-if="viewJobData.imageUrl2" class="photo-card">
                      <img :src="viewJobData.imageUrl2" alt="Job Photo 2" />
                      <span class="photo-caption">Photo 2</span>
                    </div>
                    <div v-else class="photo-placeholder">No photo 2</div>
                  </template>
                  <template v-else>
                    <div class="photo-placeholder photo-placeholder-wide">No uploaded images for this job post</div>
                  </template>
                </div>
              </div>
            </div>

            <div class="view-side-stack">
              <div class="detail-block detail-card-compact">
                <h4>Posting Information</h4>
                <div class="detail-kv-list">
                  <div class="detail-kv-item">
                    <span>Exact Address</span>
                    <strong>{{ viewJobData.exactAddress || "Not specified" }}</strong>
                  </div>
                  <div class="detail-kv-item">
                    <span>Finance Status</span>
                    <strong>{{ viewJobData.financeApprovalStatus || "pending" }}</strong>
                  </div>
                  <div class="detail-kv-item">
                    <span>Posted By</span>
                    <strong>{{ viewJobData.postedByName || viewJobData.postedByEmail || "N/A" }}</strong>
                  </div>
                </div>
              </div>

              <div class="detail-block detail-card-compact">
                <h4>Map Location</h4>
                <iframe
                  v-if="viewJobData.location"
                  :src="getMapUrl(viewJobData)"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
                <p v-else class="map-empty">No location provided</p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="save" @click="openEditFromView">{{ isRejectedJob(viewJobData) ? "Revise & Appeal" : "Edit" }}</button>
          <button class="cancel" @click="showViewModal=false">Close</button>
        </div>
      </div>
    </div>
    </transition>

    <transition name="modal-fade">
      <div v-if="showDeleteConfirmModal" class="modal-overlay" @click="closeDeleteConfirm">
        <div class="modal confirm-modal" @click.stop>
          <div class="edit-header">
            <div>
              <h3>Delete Job Post</h3>
              <p class="subtitle">This action cannot be undone.</p>
            </div>
            <button class="close-x" @click="closeDeleteConfirm">×</button>
          </div>

          <div class="view-body">
            <p>
              Are you sure you want to delete
              <strong>{{ deleteTargetJob?.title || "this job" }}</strong>?
            </p>
          </div>

          <div class="modal-actions">
            <button class="cancel confirm-cancel-btn" type="button" @click="closeDeleteConfirm">Cancel</button>
            <button
              class="delete confirm-delete-btn"
              type="button"
              :disabled="busyJobId === (deleteTargetJob?.id || null) || deletingSelected"
              @click="confirmDeleteJob"
            >
              {{ busyJobId === (deleteTargetJob?.id || null) ? "Deleting..." : "Delete" }}
            </button>
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
      selectedJobIds: [],
      selectionMode: false,
      deletingSelected: false,
      showDeleteConfirmModal: false,
      deleteTargetJob: null,
      showModal: false,
      editJobData: {},
      isAppealEdit: false,
      appealOriginalSnapshot: null,
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
    },
    isAllFilteredSelected() {
      if (this.filteredJobs.length === 0) return false
      return this.filteredJobs.every((job) => this.selectedJobIds.includes(String(job?.id || "")))
    },
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
        // Auth state can be late after refresh; retry shortly before showing a hard failure.
        setTimeout(async () => {
          const retryActor = await this.resolveActorMeta()
          if (!retryActor?.uid) {
            toast.error("Cannot load jobs. Please login again.")
            return
          }
          await this.loadJobs(retryActor)
        }, 1200)
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

        const validIds = new Set(this.jobs.map((job) => String(job?.id || "")).filter(Boolean))
        this.selectedJobIds = this.selectedJobIds.filter((id) => validIds.has(id))
        if (this.selectionMode && this.jobs.length === 0) {
          this.selectionMode = false
        }

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
      const fallbackUid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
      const uid = String(user?.uid || fallbackUid).trim()
      if (!uid) return null

      const email = String(
        user?.email || localStorage.getItem("userEmail") || ""
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
        uid,
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
      this.isAppealEdit = this.isRejectedJob(job)
      this.appealOriginalSnapshot = this.snapshotForAppeal(this.editJobData)
      this.editImageFile1 = null
      this.editImageFile2 = null
      this.editImagePreview1 = job.imageUrl || ""
      this.editImagePreview2 = job.imageUrl2 || ""
      this.showModal = true
    },

    openAppeal(job) {
      this.openEdit(job)
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

    normalizeStatus(value) {
      return String(value || "").trim().toLowerCase()
    },

    resolvedStatus(job) {
      const status = this.normalizeStatus(job?.status)
      const financeStatus = this.normalizeStatus(job?.financeApprovalStatus)
      if (financeStatus === "rejected" || status === "finance_rejected" || status === "rejected") {
        return "rejected"
      }
      if (status === "open") return "open"
      if (status === "closed") return "closed"
      if (status === "pending_finance_review" || financeStatus === "pending") return "pending_finance_review"
      return status || "pending_finance_review"
    },

    statusLabel(job) {
      const status = this.resolvedStatus(job)
      if (status === "pending_finance_review") return "pending"
      return status.replace(/_/g, " ")
    },

    statusBadgeClass(job) {
      const status = this.resolvedStatus(job)
      if (status === "open") return "open"
      if (status === "pending_finance_review") return "pending"
      if (status === "rejected") return "rejected"
      return "closed"
    },

    isOpenJob(job) {
      return this.resolvedStatus(job) === "open"
    },

    isClosedJob(job) {
      return this.resolvedStatus(job) === "closed"
    },

    isRejectedJob(job) {
      return this.resolvedStatus(job) === "rejected"
    },

    getRejectionReason(job) {
      const reason = String(job?.financeRejectionReason || job?.financeApprovalNote || "").trim()
      return reason || "No rejection reason provided by Finance."
    },

    snapshotForAppeal(job) {
      return {
        title: String(job?.title || "").trim(),
        description: String(job?.description || "").trim(),
        location: String(job?.location || "").trim(),
        exactAddress: String(job?.exactAddress || "").trim(),
        salary: String(job?.salary || "").trim(),
        type: String(job?.type || "").trim(),
        imageUrl: String(job?.imageUrl || "").trim(),
        imageUrl2: String(job?.imageUrl2 || "").trim(),
      }
    },

    hasAppealRevision() {
      if (!this.isAppealEdit) return true
      if (this.editImageFile1 || this.editImageFile2) return true
      const current = this.snapshotForAppeal(this.editJobData)
      const original = this.appealOriginalSnapshot || {}
      return Object.keys(current).some((key) => current[key] !== String(original[key] || "").trim())
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

    toDetailList(value) {
      if (Array.isArray(value)) {
        return value.map((item) => String(item || "").trim()).filter(Boolean)
      }

      const text = String(value || "").trim()
      if (!text) return []

      if (text.startsWith("[") && text.endsWith("]")) {
        try {
          const parsed = JSON.parse(text)
          if (Array.isArray(parsed)) {
            return parsed.map((item) => String(item || "").trim()).filter(Boolean)
          }
        } catch {
          // fallback to text parsing below
        }
      }

      return text
        .split(/\r?\n|\u2022|;/g)
        .map((item) => item.replace(/^\s*-\s*/, "").trim())
        .filter(Boolean)
    },

    async updateJob() {
      try {
        const submittingAppeal = this.isAppealEdit
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

        if (submittingAppeal && !this.hasAppealRevision()) {
          toast.warning("Please revise the rejected job post before submitting an appeal.")
          return
        }

        const updatePayload = {
          title: this.editJobData.title,
          description: this.editJobData.description,
          location: this.editJobData.location,
          exactAddress: this.editJobData.exactAddress,
          salary: this.editJobData.salary,
          type: this.editJobData.type || "",
          imageUrl: this.editJobData.imageUrl || "",
          imageUrl2: this.editJobData.imageUrl2 || "",
          images: [this.editJobData.imageUrl, this.editJobData.imageUrl2].filter(Boolean)
        }

        if (submittingAppeal) {
          updatePayload.status = "pending_finance_review"
          updatePayload.financeApprovalStatus = "pending"
          updatePayload.financeApprovalNote = "Appeal submitted by HR after revision."
          updatePayload.financeRejectionReason = ""
          updatePayload.financeReviewedAt = ""
          updatePayload.publishedAt = ""
        }

        await api.put(`/jobs/${this.editJobData.id}`, updatePayload)

        this.showModal = false
        this.isAppealEdit = false
        this.appealOriginalSnapshot = null
        this.editImageFile1 = null
        this.editImageFile2 = null
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success(submittingAppeal ? "Appeal submitted to Finance for re-review." : "Job updated successfully")
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

    promptDeleteJob(job) {
      if (this.deletingSelected) return
      const id = job?.id
      if (!id) {
        toast.error("Invalid job record.")
        return
      }
      this.deleteTargetJob = job
      this.showDeleteConfirmModal = true
    },

    closeDeleteConfirm(force = false) {
      if ((this.busyJobId || this.deletingSelected) && !force) return
      this.showDeleteConfirmModal = false
      this.deleteTargetJob = null
    },

    async confirmDeleteJob() {
      if (!this.deleteTargetJob) return
      await this.deleteJob(this.deleteTargetJob)
    },

    async deleteJob(job) {
      const id = job?.id
      if (!id) {
        toast.error("Invalid job record.")
        return
      }

      this.busyJobId = id
      try {
        await api.delete(`/jobs/${id}`)
        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)
        toast.success("Job deleted.")
        this.closeDeleteConfirm(true)
      } catch (err) {
        console.error(err)
        toast.error(err?.message || "Failed to delete job.")
      } finally {
        this.selectedJobIds = this.selectedJobIds.filter((jobId) => jobId !== String(id))
        this.busyJobId = null
      }
    },

    toggleSelectionMode() {
      this.selectionMode = !this.selectionMode
      if (!this.selectionMode) {
        this.selectedJobIds = []
      }
    },

    isJobSelected(jobId) {
      return this.selectedJobIds.includes(String(jobId || ""))
    },

    toggleJobSelection(jobId) {
      const id = String(jobId || "")
      if (!id || this.deletingSelected) return
      if (this.selectedJobIds.includes(id)) {
        this.selectedJobIds = this.selectedJobIds.filter((item) => item !== id)
      } else {
        this.selectedJobIds = [...this.selectedJobIds, id]
      }
    },

    toggleSelectAllFiltered(event) {
      if (this.deletingSelected) return
      const checked = Boolean(event?.target?.checked)
      const filteredIds = this.filteredJobs.map((job) => String(job?.id || "")).filter(Boolean)
      if (!checked) {
        this.selectedJobIds = this.selectedJobIds.filter((id) => !filteredIds.includes(id))
        return
      }
      const merged = new Set(this.selectedJobIds)
      filteredIds.forEach((id) => merged.add(id))
      this.selectedJobIds = Array.from(merged)
    },

    clearSelectedJobs() {
      this.selectedJobIds = []
    },

    async deleteSelectedJobs() {
      if (this.deletingSelected) return
      const ids = this.selectedJobIds.filter((id) =>
        this.jobs.some((job) => String(job?.id || "") === id)
      )
      if (!ids.length) {
        toast.warning("No selected jobs to delete.")
        return
      }

      this.deletingSelected = true
      const failedIds = []
      let successCount = 0
      try {
        for (const id of ids) {
          try {
            await api.delete(`/jobs/${id}`)
            successCount += 1
          } catch (err) {
            console.error(err)
            failedIds.push(id)
          }
        }

        const actor = await this.resolveActorMeta()
        if (actor?.uid) await this.loadJobs(actor)

        if (successCount > 0) {
          toast.success(`Deleted ${successCount} job post${successCount > 1 ? "s" : ""}.`)
        }
        if (failedIds.length > 0) {
          toast.warning(`Failed to delete ${failedIds.length} job post${failedIds.length > 1 ? "s" : ""}.`)
        }

        this.selectedJobIds = failedIds
        if (failedIds.length === 0) {
          this.selectionMode = false
        }
      } finally {
        this.deletingSelected = false
      }
    },

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

.bulk-actions{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
  margin-bottom:16px;
  padding:10px 12px;
  border:1px solid #e2e8f0;
  border-radius:12px;
  background:#f8fafc;
}

.bulk-check{
  display:inline-flex;
  align-items:center;
  gap:8px;
  color:#334155;
  font-size:13px;
  font-weight:600;
}

.bulk-meta{
  margin:0;
  color:#64748b;
  font-size:12px;
  font-weight:600;
}

.ghost-btn,
.danger-soft-btn{
  border:none;
  border-radius:10px;
  padding:8px 12px;
  cursor:pointer;
  font-size:12px;
  font-weight:600;
}

.ghost-btn{
  background:#e2e8f0;
  color:#0f172a;
}

.danger-soft-btn{
  background:#7f1d1d;
  color:#ffffff;
}

.ghost-btn:disabled,
.danger-soft-btn:disabled{
  opacity:.65;
  cursor:not-allowed;
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

.job-card.selected{
  border-color:#7c3aed;
  box-shadow:0 14px 26px rgba(124,58,237,.14);
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

.card-check{
  margin-bottom:8px;
}

.item-check{
  display:inline-flex;
  align-items:center;
  gap:6px;
  color:#334155;
  font-size:12px;
  font-weight:600;
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

.rejected{
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
.appeal{ background:linear-gradient(135deg, #7c3aed, #5b21b6); }
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

.view-body-pro{
  gap:14px;
}

.review-focus-body{
  gap:12px;
}

.review-focus-head{
  border:1px solid #fecaca;
  background:linear-gradient(180deg, #fff1f2 0%, #fee2e2 100%);
  border-radius:14px;
  padding:14px;
}

.review-focus-kicker{
  margin:0;
  font-size:11px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.7px;
  color:#991b1b;
}

.review-focus-title-row{
  margin-top:8px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}

.review-focus-title{
  margin:0;
  font-size:22px;
  color:#7f1d1d;
  line-height:1.2;
}

.review-reason-panel{
  border:1px solid #ef4444;
  background:#fff;
  border-radius:14px;
  padding:16px;
  box-shadow:0 8px 22px rgba(127, 29, 29, 0.12);
}

.review-reason-label{
  margin:0 0 8px;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.7px;
  color:#991b1b;
  font-weight:700;
}

.review-reason-text{
  margin:0;
  color:#7f1d1d;
  font-size:16px;
  line-height:1.6;
  font-weight:600;
  white-space:pre-wrap;
}

.review-support-grid{
  display:grid;
  grid-template-columns:repeat(2, minmax(0, 1fr));
  gap:10px;
}

.review-support-item{
  border:1px solid #e5e7eb;
  background:#f8fafc;
  border-radius:10px;
  padding:10px;
  display:flex;
  flex-direction:column;
  gap:4px;
}

.review-support-item span{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.5px;
  color:#64748b;
}

.review-support-item strong{
  font-size:13px;
  color:#0f172a;
  line-height:1.4;
}

.view-summary-card{
  border:1px solid #dbeafe;
  background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
  border-radius:14px;
  padding:14px;
}

.view-summary-top{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}

.view-summary-label{
  margin:0 0 4px;
  font-size:11px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.7px;
  color:#64748b;
}

.view-summary-title{
  margin:0;
  font-size:20px;
  line-height:1.2;
  color:#0f172a;
}

.view-summary-subtitle{
  margin:4px 0 0;
  color:#475569;
  font-size:13px;
}

.view-chip-row{
  margin-top:12px;
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}

.detail-chip{
  display:inline-flex;
  align-items:center;
  gap:4px;
  padding:7px 10px;
  border-radius:999px;
  border:1px solid #dbe2ea;
  background:#ffffff;
  color:#334155;
  font-size:12px;
}

.detail-chip strong{
  color:#0f172a;
  font-weight:700;
}

.view-layout-grid{
  display:grid;
  grid-template-columns:minmax(0, 1.55fr) minmax(280px, .95fr);
  gap:14px;
  align-items:start;
}

.view-main-stack,
.view-side-stack{
  display:flex;
  flex-direction:column;
  gap:14px;
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
  box-shadow:0 4px 14px rgba(15, 23, 42, 0.04);
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
  font-weight:700;
}

.detail-list{
  margin:0;
  padding-left:18px;
  display:flex;
  flex-direction:column;
  gap:6px;
}

.detail-list li{
  color:#334155;
  font-size:14px;
  line-height:1.45;
}

.photo-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
}

.photo-grid-pro{
  grid-template-columns:1fr 1fr;
}

.photo-card{
  display:flex;
  flex-direction:column;
  gap:6px;
}

.photo-grid img{
  width:100%;
  height:160px;
  object-fit:cover;
  border-radius:12px;
  border:1px solid #e5e7eb;
}

.photo-grid-pro img{
  height:190px;
  background:#f8fafc;
}

.photo-caption{
  font-size:12px;
  color:#64748b;
  font-weight:600;
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

.photo-placeholder-wide{
  grid-column:1 / -1;
  height:180px;
}

.detail-card-compact iframe{
  height:240px;
}

.detail-kv-list{
  display:flex;
  flex-direction:column;
  gap:10px;
}

.detail-kv-item{
  border:1px solid #e5e7eb;
  background:#f8fafc;
  border-radius:10px;
  padding:10px;
  display:flex;
  flex-direction:column;
  gap:4px;
}

.detail-kv-item span{
  font-size:11px;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:.5px;
}

.detail-kv-item strong{
  color:#0f172a;
  font-size:13px;
  line-height:1.35;
}

.detail-kv-item.rejection-reason strong{
  color:#991b1b;
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

.confirm-modal{
  width:min(460px, 92vw);
}

.confirm-modal .modal-actions{
  padding-top: 6px;
}

.confirm-cancel-btn{
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #334155;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.confirm-cancel-btn:hover{
  background: #f8fafc;
  border-color: #94a3b8;
}

.confirm-delete-btn{
  border: 1px solid transparent;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #ffffff;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 700;
  box-shadow: 0 8px 18px rgba(220, 38, 38, 0.2);
}

.confirm-delete-btn:hover{
  filter: brightness(1.02);
  transform: translateY(-1px);
}

.confirm-delete-btn:disabled,
.confirm-cancel-btn:disabled{
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
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
  .view-layout-grid{
    grid-template-columns:1fr;
  }
  .photo-grid-pro{
    grid-template-columns:1fr;
  }
  .photo-placeholder-wide{
    height:140px;
  }
  .view-summary-top{
    flex-direction:column;
    align-items:flex-start;
  }
  .review-support-grid{
    grid-template-columns:1fr;
  }
  .edit-grid{
    grid-template-columns:1fr;
  }
  .edit-uploads{
    grid-template-columns:1fr;
  }
}

</style>



