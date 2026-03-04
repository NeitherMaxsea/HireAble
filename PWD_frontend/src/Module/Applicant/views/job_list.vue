<template>
  <div class="applicant-job-list-page">
    <transition name="intro-fade">
      <div v-if="showWelcomeOverlay" class="intro-overlay">
        <div class="intro-card">
          <p class="intro-eyebrow">Applicant Portal</p>
          <h2>Welcome to {{ systemName }}</h2>
          <p>{{ welcomeName ? `Hello, ${welcomeName}.` : "Welcome back." }} Preparing your job feed...</p>
        </div>
      </div>
    </transition>

    <transition name="intro-fade">
      <div v-if="showSkillsOverlay" class="intro-overlay skills-overlay">
        <div class="skills-picker-card">
          <div class="skills-picker-head">
            <div>
              <p class="intro-eyebrow">Job Preferences</p>
              <h3>Select skills / roles you are looking for</h3>
              <p>Choose one or more. We will show job posts based on your selection.</p>
            </div>
          </div>

          <div class="skills-grid">
            <label
              v-for="skill in skillOptions"
              :key="skill"
              class="skill-option"
              :class="{ active: selectedSkills.includes(skill) }"
            >
              <input
                type="checkbox"
                :checked="selectedSkills.includes(skill)"
                @change="toggleSkill(skill)"
              />
              <span>{{ skill }}</span>
            </label>
          </div>

          <div class="skills-actions">
            <button class="skip-btn" type="button" @click="continueWithoutSkills">
              Show all jobs
            </button>
            <button class="continue-btn" type="button" @click="confirmSkillsSelection">
              Continue ({{ selectedSkills.length }})
            </button>
          </div>
        </div>
      </div>
    </transition>

  <div class="dashboard" :class="{ 'intro-locked': showWelcomeOverlay || showSkillsOverlay }">
    <!-- LEFT -->
    <section class="job-list">
      <h2>Available Jobs</h2>

      <div class="list-filters">
        <div class="filter-input-wrap">
          <i class="bi bi-search"></i>
          <input
            v-model.trim="jobSearchKeyword"
            type="text"
            placeholder="Search keywords (job, company, location)"
          />
        </div>

        <select v-model="jobDisabilityFilter" class="filter-select">
          <option value="all">All disability types</option>
          <option v-for="opt in jobDisabilityOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
      </div>

      <div v-if="isJobsLoading" class="panel-loading">
        <div class="panel-spinner"></div>
        <p>Loading job posts...</p>
      </div>

      <p v-else-if="displayJobs.length === 0" class="empty">
        No job posts available
      </p>

      <div
        v-for="job in displayJobs"
        :key="job.id"
        class="job-card"
        :class="{ active: selectedJob?.id === job.id }"
        @click="selectJob(job)"
      >
        <div class="logo-wrap">
          <img
            v-if="job.logoUrl"
            :src="job.logoUrl"
            alt="Company logo"
            class="logo-img"
          />
          <div v-else class="logo-fallback">
            {{ (job.companyName || job.company || job.department || "CO").slice(0,2).toUpperCase() }}
          </div>
        </div>

        <div class="card-content">
          <div class="card-top">
            <h3>{{ job.title }}</h3>
            <span class="type-badge">{{ job.type || "Open" }}</span>
          </div>

          <p class="company-line">{{ job.companyName || "Company" }}</p>
          <p class="dept">{{ job.description || "-" }}</p>

          <div class="meta">
            <span class="meta-item">
              <i class="bi bi-geo-alt"></i>
              {{ job.location || "Not specified" }}
            </span>
            <span class="meta-item">
              <i class="bi bi-people"></i>
              Vacancies: {{ job.vacancies }}
            </span>
            <span class="pwd-pill">
              <i class="bi bi-universal-access"></i>
              {{ job.disabilityType || "PWD-friendly" }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- RIGHT -->
    <section class="job-preview">
      <div v-if="isJobsLoading" class="panel-loading right">
        <div class="panel-spinner"></div>
        <p>Preparing job details...</p>
      </div>

      <div v-else-if="displayJobs.length === 0" class="empty">
        No job posts available
      </div>

      <div v-else-if="!selectedJob" class="empty">
        Select a job to view details
      </div>

      <!-- JOB DETAILS -->
      <div v-else class="job-detail-card map-layout">
        <!-- LEFT DETAILS -->
        <div class="details-left">
          <div class="detail-header">
            <div>
              <div class="company-row">
                <div class="company-logo">
                  <img
                    v-if="selectedJob.logoUrl"
                    :src="selectedJob.logoUrl"
                    alt="Company logo"
                  />
                  <div v-else class="company-logo-fallback">
                    {{ (selectedJob.companyName || selectedJob.company || selectedJob.department || "CO").slice(0,2).toUpperCase() }}
                  </div>
                </div>
                <div class="company-text">
                  <p class="company-name">
                    {{ selectedJob.companyName || selectedJob.company || selectedJob.department || "Company" }}
                  </p>
                  <h2>{{ selectedJob.title }}</h2>
                </div>
              </div>
              <div class="badges">
                <span class="badge">{{ selectedJob.type || "Open" }}</span>
                <span class="pwd-pill">
                  <i class="bi bi-universal-access"></i>
                  {{ selectedJob.disabilityType || "PWD-friendly" }}
                </span>
              </div>
            </div>
            <span class="location">
              <i class="bi bi-geo-alt"></i>
              {{ selectedJob.location || "Not specified" }}
            </span>
          </div>

          <div class="detail-section grid-2">
            <div>
              <h4>Department</h4>
              <p>{{ selectedJob.department || "Not specified" }}</p>
            </div>

            <div>
              <h4>Accessibility</h4>
              <p>{{ selectedJob.disabilityType || "Not specified" }}</p>
            </div>
          </div>

          <div class="detail-section grid-2">
            <div>
              <h4>Salary</h4>
              <p>{{ selectedJob.salary || "Negotiable" }}</p>
            </div>

            <div>
              <h4>Status</h4>
              <p class="status">Open</p>
            </div>
          </div>

          <div class="detail-section grid-2">
            <div>
              <h4>Vacancies</h4>
              <p>{{ selectedJob.vacancies }}</p>
            </div>
          </div>

          <div class="detail-section">
            <h4>Description</h4>
            <p>{{ selectedJob.description || "No description provided." }}</p>
          </div>

        </div>

        <!-- RIGHT MAP + PHOTO -->
        <div class="map-right">
          <!-- MAP -->
          <div
            v-if="selectedJob.location"
            class="map-preview-wrap"
            role="button"
            tabindex="0"
            @click="openMapModal"
            @keydown.enter.prevent="openMapModal"
            @keydown.space.prevent="openMapModal"
          >
            <iframe
              :src="mapUrl"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
            <div class="map-click-overlay">
              <span>Click to view location</span>
            </div>
          </div>

          <p v-else class="empty">No location provided</p>

          <!-- JOB PHOTOS -->
          <div class="job-photos">
            <img
              v-if="selectedJob.imageUrl"
              :src="selectedJob.imageUrl"
              class="job-photo"
              alt="Job Photo 1"
              @click="openGallery(selectedJob.imageUrl)"
            />
            <div v-else class="job-photo placeholder">No photo</div>

            <img
              v-if="selectedJob.imageUrl2"
              :src="selectedJob.imageUrl2"
              class="job-photo"
              alt="Job Photo 2"
              @click="openGallery(selectedJob.imageUrl2)"
            />
            <div v-else class="job-photo placeholder">No photo</div>
          </div>
        </div>

        <div class="detail-actions bottom">
          <button
            class="apply-btn"
            :class="{ disabled: hasExistingApplication }"
            :disabled="hasExistingApplication"
            @click="applyJob"
          >
            {{ applicationButtonLabel }}
          </button>
        </div>
      </div>
    </section>
  </div>

  <!-- PHOTO LIGHTBOX -->
  <transition name="lb-fade">
    <div v-if="isLightboxOpen" class="lightbox" @click.self="closeGallery">
      <button class="lb-close" @click="closeGallery" aria-label="Close">Ã—</button>
      <button class="lb-nav left" @click="prevPhoto" aria-label="Previous photo">
        &lt;
      </button>
      <img :src="photoList[currentPhotoIndex]" class="lb-image" alt="Job Photo" />
      <button class="lb-nav right" @click="nextPhoto" aria-label="Next photo">
        &gt;
      </button>
      <div class="lb-dots">
        <span
          v-for="(p, idx) in photoList"
          :key="p + idx"
          class="dot"
          :class="{ active: idx === currentPhotoIndex }"
          @click="setPhoto(idx)"
        ></span>
      </div>
    </div>
  </transition>

  <transition name="lb-fade">
    <div v-if="showMapModal" class="lightbox map-lightbox" @click.self="closeMapModal">
      <div class="map-modal-card">
        <div class="map-modal-head">
          <div>
            <p class="map-modal-label">Job Location</p>
            <h3>{{ selectedJob?.location || "Not specified" }}</h3>
          </div>
          <button class="lb-close" @click="closeMapModal" aria-label="Close map">×</button>
        </div>
        <iframe
          v-if="selectedJob?.location"
          :src="mapUrl"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          class="map-modal-frame"
        ></iframe>
      </div>
    </div>
  </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from "vue"
import { db } from "@/lib/client-platform"
import { addDoc, collection, onSnapshot, serverTimestamp } from "@/lib/laravel-data"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const jobs = ref([])
const isJobsLoading = ref(true)
const selectedJob = ref(null)
const applicantId = ref("")
const applicantEmail = ref("")
const applicationStatusByJob = ref({})
const jobSearchKeyword = ref("")
const jobDisabilityFilter = ref("all")
const selectedSkills = ref([])
const showWelcomeOverlay = ref(false)
const showSkillsOverlay = ref(false)
const showMapModal = ref(false)
const currentPhotoIndex = ref(0)
const isLightboxOpen = ref(false)
let unsubscribeJobs = null
let unsubscribeApplications = null
let jobsLoadingWatchdog = null

onMounted(() => {
  applicantId.value = String(
    localStorage.getItem("userUid") ||
    localStorage.getItem("uid") ||
    localStorage.getItem("sessionUid") ||
    ""
  ).trim()
  applicantEmail.value = String(localStorage.getItem("userEmail") || "")
    .trim()
    .toLowerCase()

  jobsLoadingWatchdog = setTimeout(() => {
    isJobsLoading.value = false
  }, 15000)

  unsubscribeJobs = onSnapshot(collection(db, "jobs"), snapshot => {
    const list = snapshot.docs.map(d => ({
      id: d.id,
      ...d.data()
    }))

    jobs.value = list
      .map(job => {
        const images = Array.isArray(job.images) ? job.images.filter(Boolean) : []

        return {
          ...job,
          companyName:
            String(
              job.companyName ||
              job.company_name ||
              job.company ||
              job.department ||
              ""
            ).trim(),
          imageUrl:
            job.imageUrl ||
            job.imageURL ||
            job.photo1 ||
            images[0] ||
            "",
          imageUrl2:
            job.imageUrl2 ||
            job.imageURL2 ||
            job.photo2 ||
            images[1] ||
            "",
          vacancies: normalizeVacancies(job.vacancies ?? job.slots),
          statusNormalized: normalizeJobStatus(job.status),
          financeApprovalNormalized: normalizeFinanceApproval(job),
        }
      })
      .filter((job) => isVisibleToApplicant(job))
      .sort((a, b) => getCreatedAtMillis(b.createdAt) - getCreatedAtMillis(a.createdAt))

    if (selectedJob.value) {
      selectedJob.value = jobs.value.find(j => j.id === selectedJob.value.id) || null
    }
    isJobsLoading.value = false
    if (jobsLoadingWatchdog) {
      clearTimeout(jobsLoadingWatchdog)
      jobsLoadingWatchdog = null
    }
  }, () => {
    jobs.value = []
    isJobsLoading.value = false
    if (jobsLoadingWatchdog) {
      clearTimeout(jobsLoadingWatchdog)
      jobsLoadingWatchdog = null
    }
  })

  // Read once from applications collection and match by applicant ID OR email.
  // This avoids missing pending records when older rows only have one identifier.
  unsubscribeApplications = onSnapshot(collection(db, "applications"), snapshot => {
    const nextStatusByJob = {}

    snapshot.docs.forEach((docSnap) => {
      const raw = docSnap.data() || {}
      const applicationJobId = String(raw.jobId || "").trim()
      const applicationApplicantId = String(raw.applicantId || "").trim()
      const applicationApplicantEmail = String(raw.applicantEmail || "").trim().toLowerCase()
      const applicationStatus = String(raw.status || "pending").trim().toLowerCase()

      if (!applicationJobId) return

      const hasIdMatch = applicantId.value && applicationApplicantId === applicantId.value
      const hasEmailMatch = applicantEmail.value && applicationApplicantEmail === applicantEmail.value
      if (!hasIdMatch && !hasEmailMatch) {
        return
      }

      // Keep the latest known status for this job application.
      nextStatusByJob[applicationJobId] = applicationStatus || "pending"
    })

    applicationStatusByJob.value = nextStatusByJob
  }, () => {
    applicationStatusByJob.value = {}
  })

  runIntroSequence()
})

onBeforeUnmount(() => {
  if (jobsLoadingWatchdog) {
    clearTimeout(jobsLoadingWatchdog)
    jobsLoadingWatchdog = null
  }
  if (typeof unsubscribeJobs === "function") {
    unsubscribeJobs()
  }
  if (typeof unsubscribeApplications === "function") {
    unsubscribeApplications()
  }
})

const getCreatedAtMillis = (ts) => {
  if (!ts) return 0
  if (typeof ts === "string") {
    const raw = ts.trim()
    if (!raw) return 0
    const parsed = Date.parse(raw.includes("T") ? raw : raw.replace(" ", "T"))
    return Number.isFinite(parsed) ? parsed : 0
  }
  if (typeof ts?.toMillis === "function") return ts.toMillis()
  if (typeof ts?.seconds === "number") return ts.seconds * 1000
  if (ts instanceof Date) return ts.getTime()
  return 0
}

const normalizeJobStatus = (status) => String(status || "").trim().toLowerCase()

const normalizeVacancies = (value) => {
  const parsed = parseInt(String(value ?? "").trim(), 10)
  if (!Number.isFinite(parsed) || parsed < 1) return 1
  return parsed
}

const normalizeFinanceApproval = (job) => {
  const direct = String(job?.financeApprovalStatus || job?.finance_approval_status || "").trim().toLowerCase()
  if (["approved", "rejected", "pending"].includes(direct)) return direct

  const status = normalizeJobStatus(job?.status)
  if (status === "open" || status === "closed") return "approved"
  if (status === "finance_rejected") return "rejected"
  if (status.includes("pending")) return "pending"
  return "pending"
}

const isVisibleToApplicant = (job) => {
  const status = normalizeJobStatus(job?.statusNormalized || job?.status)
  const approval = normalizeFinanceApproval(job)
  if (["open", "approved", "published", "active"].includes(status)) return true
  if (status === "closed") return false
  if (status === "finance_rejected") return false
  return approval === "approved"
}

const systemName = "HireAble"
const welcomeName = computed(() =>
  String(localStorage.getItem("userName") || localStorage.getItem("name") || "Applicant").trim()
)

const defaultSkillOptions = [
  "Cashier",
  "Encoder / Data Entry",
  "Office Staff",
  "Administrative Assistant",
  "Customer Service",
  "Chat Support",
  "Service Crew",
  "Production Worker",
  "Warehouse Assistant",
  "Packing Staff",
  "Sales Associate",
  "Security Guard",
  "Housekeeping",
  "Remote Work",
]

const skillOptions = computed(() => {
  const fromJobs = jobs.value.flatMap((job) => {
    const values = []
    const title = String(job.title || "").trim()
    const category = String(job.category || "").trim()
    const disabilityType = String(job.disabilityType || "").trim()
    if (title) values.push(title)
    if (category) values.push(category)
    if (disabilityType) values.push(disabilityType)
    return values
  })

  return [...new Set([...defaultSkillOptions, ...fromJobs].filter(Boolean))].slice(0, 40)
})

const displayJobs = computed(() => {
  const keyword = jobSearchKeyword.value.trim().toLowerCase()
  const picked = selectedSkills.value.map((s) => String(s || "").toLowerCase())
  const base = jobs.value.filter((job) => {
    const disabilityValue = String(job.disabilityType || job.disability || "").trim()
    const haystack = [
      job.title,
      job.description,
      job.department,
      job.companyName,
      job.location,
      job.disabilityType,
      job.category,
      job.type,
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()

    const keywordMatch = !keyword || haystack.includes(keyword)
    const disabilityMatch =
      jobDisabilityFilter.value === "all" ||
      disabilityValue.toLowerCase() === String(jobDisabilityFilter.value || "").toLowerCase()

    return keywordMatch && disabilityMatch
  })

  const withSkillFilter = base.filter((job) => {
    const haystack = [
      job.title,
      job.description,
      job.department,
      job.companyName,
      job.location,
      job.disabilityType,
      job.category,
      job.type,
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()

    const skillMatch = !picked.length || picked.some((skill) => haystack.includes(skill))
    return skillMatch
  })

  // If stored skills are too restrictive, don't hide all jobs from applicant.
  if (picked.length > 0 && withSkillFilter.length === 0) {
    return base
  }

  return withSkillFilter
})

const jobDisabilityOptions = computed(() => {
  return [...new Set(
    jobs.value
      .map((j) => String(j.disabilityType || j.disability || "").trim())
      .filter(Boolean)
  )].sort((a, b) => a.localeCompare(b))
})

function getApplicantSkillPrefKey() {
  const uid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || localStorage.getItem("sessionUid") || "").trim()
  const email = String(localStorage.getItem("userEmail") || "").trim().toLowerCase()
  if (uid) return `applicantSkillsPref:${uid}`
  if (email) return `applicantSkillsPref:${email}`
  return "applicantSkillsPref"
}

function getApplicantIntroSeenKey() {
  const uid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || localStorage.getItem("sessionUid") || "").trim()
  const email = String(localStorage.getItem("userEmail") || "").trim().toLowerCase()
  if (uid) return `applicantIntroShown:${uid}`
  if (email) return `applicantIntroShown:${email}`
  return "applicantIntroShown"
}

function hasSeenApplicantIntro() {
  try {
    return localStorage.getItem(getApplicantIntroSeenKey()) === "1"
  } catch {
    return false
  }
}

function markApplicantIntroSeen() {
  try {
    localStorage.setItem(getApplicantIntroSeenKey(), "1")
    localStorage.setItem("userIsFirstLogin", "false")
  } catch {
    // ignore localStorage errors
  }
}

function readIsFirstLoginFlag() {
  try {
    const raw = localStorage.getItem("userIsFirstLogin")
    if (raw == null) return null
    const normalized = String(raw).trim().toLowerCase()
    if (normalized === "true") return true
    if (normalized === "false") return false
    return null
  } catch {
    return null
  }
}

function loadSelectedSkills() {
  try {
    const raw = localStorage.getItem(getApplicantSkillPrefKey())
    if (!raw) return
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) {
      selectedSkills.value = parsed.filter(Boolean).map((x) => String(x).trim()).filter(Boolean)
    }
  } catch {
    selectedSkills.value = []
  }
}

function saveSelectedSkills() {
  try {
    localStorage.setItem(getApplicantSkillPrefKey(), JSON.stringify(selectedSkills.value))
  } catch {
    // ignore localStorage errors
  }
}

function toggleSkill(skill) {
  const key = String(skill || "").trim()
  if (!key) return
  const next = [...selectedSkills.value]
  const index = next.indexOf(key)
  if (index >= 0) next.splice(index, 1)
  else next.push(key)
  selectedSkills.value = next
}

function continueWithoutSkills() {
  selectedSkills.value = []
  saveSelectedSkills()
  showSkillsOverlay.value = false
}

function confirmSkillsSelection() {
  if (!selectedSkills.value.length) {
    showToast("Select at least one skill, or click Show all jobs.", "#f59e0b")
    return
  }
  saveSelectedSkills()
  showSkillsOverlay.value = false
}

async function runIntroSequence() {
  loadSelectedSkills()
  const isFirstLogin = readIsFirstLoginFlag()
  const shouldSkipIntro = isFirstLogin === false || (isFirstLogin === null && hasSeenApplicantIntro())
  if (shouldSkipIntro) {
    showWelcomeOverlay.value = false
    showSkillsOverlay.value = false
    return
  }
  showWelcomeOverlay.value = true
  await new Promise((resolve) => setTimeout(resolve, 1200))
  showWelcomeOverlay.value = false
  await new Promise((resolve) => setTimeout(resolve, 180))
  showSkillsOverlay.value = true
  markApplicantIntroSeen()
}

// auto clear if deleted / filtered out
watch(displayJobs, (newJobs) => {
  if (
    selectedJob.value &&
    !newJobs.find(j => j.id === selectedJob.value.id)
  ) {
    selectedJob.value = null
  }

  if (!selectedJob.value && newJobs.length) {
    selectedJob.value = newJobs[0]
  }
})

const selectJob = (job) => {
  selectedJob.value = job
  currentPhotoIndex.value = 0
}

// GOOGLE MAP URL
const mapUrl = computed(() => {
  if (!selectedJob.value?.location) return ""
  return `https://www.google.com/maps?q=${encodeURIComponent(
    selectedJob.value.location
  )}&output=embed`
})

const selectedJobApplicationStatus = computed(() => {
  const jobId = String(selectedJob.value?.id || "").trim()
  if (!jobId) return ""
  return String(applicationStatusByJob.value[jobId] || "").trim().toLowerCase()
})

const hasExistingApplication = computed(() => {
  return !!selectedJobApplicationStatus.value
})

const applicationButtonLabel = computed(() => {
  const status = selectedJobApplicationStatus.value
  if (status === "pending") return "Pending"
  if (status === "rejected") return "Rejected"
  if (status === "accepted") return "Accepted"
  if (status === "interviewed" || status === "interview") return "Interview"
  if (status) return "Applied"
  return "Apply for this Job"
})

const photoList = computed(() => {
  if (!selectedJob.value) return []
  return [selectedJob.value.imageUrl, selectedJob.value.imageUrl2].filter(Boolean)
})

const openGallery = (url) => {
  if (!url || !photoList.value.length) return
  const idx = photoList.value.indexOf(url)
  currentPhotoIndex.value = idx >= 0 ? idx : 0
  isLightboxOpen.value = true
}

const closeGallery = () => {
  isLightboxOpen.value = false
}

const openMapModal = () => {
  if (!selectedJob.value?.location) return
  showMapModal.value = true
}

const closeMapModal = () => {
  showMapModal.value = false
}

const prevPhoto = () => {
  if (!photoList.value.length) return
  currentPhotoIndex.value =
    (currentPhotoIndex.value - 1 + photoList.value.length) % photoList.value.length
}

const nextPhoto = () => {
  if (!photoList.value.length) return
  currentPhotoIndex.value =
    (currentPhotoIndex.value + 1) % photoList.value.length
}

const setPhoto = (idx) => {
  if (idx < 0 || idx >= photoList.value.length) return
  currentPhotoIndex.value = idx
}

const showToast = (text, backgroundColor, noTimer = false) => {
  Toastify({
    text,
    duration: 3000,
    gravity: "top",
    position: "right",
    stopOnFocus: true,
    backgroundColor,
    className: noTimer ? "toast-no-timer" : ""
  }).showToast()
}

// APPLY
const applyJob = async () => {
  if (!selectedJob.value) return
  if (hasExistingApplication.value) {
    const status = selectedJobApplicationStatus.value
    const label =
      status === "pending" ? "pending" :
      status === "rejected" ? "rejected" :
      status || "submitted"
    showToast(`Application already ${label}.`, "#f59e0b")
    return
  }

  try {
    const applicantIdValue = String(
      localStorage.getItem("userUid") ||
      localStorage.getItem("uid") ||
      localStorage.getItem("sessionUid") ||
      ""
    ).trim()
    const applicantName = String(localStorage.getItem("userName") || "").trim()
    const applicantEmailValue = String(localStorage.getItem("userEmail") || "").trim()
    const selectedJobId = String(selectedJob.value.id || "").trim()

    await addDoc(collection(db, "applications"), {
      jobId: selectedJobId,
      jobTitle: String(selectedJob.value.title || "").trim(),
      applicantId: applicantIdValue || null,
      applicantName: applicantName || "Applicant",
      applicantEmail: applicantEmailValue || null,
      appliedAt: serverTimestamp(),
      status: "pending"
    })

    applicationStatusByJob.value = {
      ...applicationStatusByJob.value,
      [selectedJobId]: "pending",
    }
    showToast("Application sent!", "#16a34a", true)

  } catch (err) {
    console.error(err)
    const message =
      err?.response?.data?.message ||
      err?.message ||
      "Failed to apply"
    showToast(message, "#dc2626")
  }
}
</script>

<style scoped>
.applicant-job-list-page{
  position:relative;
  min-height:100%;
}

.intro-overlay{
  position:fixed;
  inset:0;
  z-index:3000;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
  background:rgba(15, 23, 42, 0.38);
  backdrop-filter: blur(8px);
}

.intro-card{
  width:min(560px, 92vw);
  background:linear-gradient(180deg, #0f6b34, #0b5127);
  color:#fff;
  border-radius:22px;
  padding:28px 30px;
  box-shadow:0 24px 60px rgba(2, 6, 23, 0.28);
  border:1px solid rgba(255,255,255,0.12);
  text-align:left;
}

.intro-eyebrow{
  margin:0 0 8px;
  font-size:12px;
  font-weight:700;
  letter-spacing:.08em;
  text-transform:uppercase;
  color:rgba(255,255,255,0.82);
}

.intro-card h2{
  margin:0 0 8px;
  font-size:28px;
  line-height:1.15;
}

.intro-card p{
  margin:0;
  color:rgba(255,255,255,0.92);
}

.skills-overlay{
  align-items:flex-start;
  padding-top:76px;
}

.skills-picker-card{
  width:min(980px, 95vw);
  max-height:calc(100vh - 120px);
  overflow:hidden;
  background:#ffffff;
  border-radius:20px;
  border:1px solid #dbe3ef;
  box-shadow:0 22px 64px rgba(2, 6, 23, 0.22);
  display:flex;
  flex-direction:column;
}

.skills-picker-head{
  padding:20px 22px 14px;
  border-bottom:1px solid #e5e7eb;
  background:linear-gradient(180deg, #f6fbf7, #ffffff);
}

.skills-picker-head h3{
  margin:0 0 6px;
  color:#0f172a;
  font-size:22px;
}

.skills-picker-head p:last-child{
  margin:0;
  color:#64748b;
  font-size:14px;
}

.skills-grid{
  padding:18px 22px;
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
  gap:12px;
  overflow:auto;
}

.skill-option{
  display:flex;
  align-items:center;
  gap:10px;
  border:1px solid #dbe3ef;
  border-radius:14px;
  padding:12px 14px;
  background:#f8fafc;
  cursor:pointer;
  transition:.18s ease;
  min-height:52px;
}

.skill-option:hover{
  border-color:#86efac;
  background:#f0fdf4;
}

.skill-option.active{
  border-color:#22c55e;
  background:#ecfdf5;
  box-shadow:0 0 0 2px rgba(34, 197, 94, 0.10) inset;
}

.skill-option input{
  width:16px;
  height:16px;
  accent-color:#16a34a;
  margin:0;
  flex:0 0 auto;
}

.skill-option span{
  color:#0f172a;
  font-weight:600;
  font-size:13px;
  line-height:1.25;
}

.skills-actions{
  display:flex;
  justify-content:space-between;
  gap:12px;
  padding:14px 22px 18px;
  border-top:1px solid #e5e7eb;
  background:#fff;
}

.skip-btn,
.continue-btn{
  border:none;
  border-radius:12px;
  padding:12px 16px;
  font-weight:700;
  cursor:pointer;
}

.skip-btn{
  background:#eef2f7;
  color:#334155;
}

.continue-btn{
  background:linear-gradient(135deg, #15803d, #22c55e);
  color:#fff;
  min-width:170px;
  box-shadow:0 10px 24px rgba(22, 163, 74, 0.22);
}

.intro-locked{
  pointer-events:none;
  user-select:none;
  filter: blur(1px);
}

.intro-fade-enter-active,
.intro-fade-leave-active{
  transition:opacity .28s ease;
}

.intro-fade-enter-from,
.intro-fade-leave-to{
  opacity:0;
}

.dashboard{
  display:flex;
  gap:20px;
  min-height:100%;
  background:#f5f7fb;
}

/* LEFT */
.job-list{
  width:350px;
  background:#ffffff;
  padding:20px;
  border:1px solid #e5e7eb;
  border-radius:14px;
  overflow-y:auto;
}

.job-card{
  padding:12px;
  border:1px solid #e5e7eb;
  margin-bottom:12px;
  cursor:pointer;
  border-radius:10px;
  transition:0.2s;
  background:#ffffff;
  display:flex;
  gap:12px;
  align-items:flex-start;
}

.job-card:hover{
  background:#f0f6ff;
}

.job-card.active{
  background:#e0ecff;
  border-color:#2563eb;
}

.card-top{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
}

.logo-wrap{
  width:46px;
  height:46px;
  flex:0 0 46px;
  border-radius:10px;
  background:#f1f5ff;
  border:1px solid #e5e7eb;
  display:flex;
  align-items:center;
  justify-content:center;
}

.logo-img{
  width:100%;
  height:100%;
  object-fit:cover;
  border-radius:10px;
}

.logo-fallback{
  font-size:14px;
  font-weight:700;
  color:#2563eb;
  letter-spacing:0.5px;
}

.card-content{
  flex:1;
  min-width:0;
}

.card-top h3{
  margin:0;
  font-size:16px;
  color:#111827;
}

.type-badge{
  font-size:11px;
  padding:4px 10px;
  background:#e0ecff;
  color:#2563eb;
  border-radius:999px;
  font-weight:600;
}

.dept{
  margin:6px 0 8px;
  color:#6b7280;
  font-size:13px;
}

.company-line{
  margin:6px 0 4px;
  color:#1f2937;
  font-size:12px;
  font-weight:700;
}

.meta{
  display:flex;
  flex-direction:column;
  gap:6px;
  font-size:12px;
  color:#6b7280;
}

.meta-item{
  display:flex;
  align-items:center;
  gap:6px;
}

.meta-item i{
  color:#2563eb;
}

.pwd-pill{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:4px 8px;
  border-radius:999px;
  background:rgba(34, 197, 94, 0.12);
  color:#15803d;
  font-size:11px;
  font-weight:600;
  width:fit-content;
}

.pwd-pill i{
  color:#15803d;
}

/* RIGHT */
.job-preview{
  flex:1;
  padding:10px 0;
  display:flex;
  justify-content:center;
}

/* DETAIL CARD */
.job-detail-card{
  background:#ffffff;
  width:100%;
  max-width:900px;
  padding:25px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,0.06);
  border:1px solid #e5e7eb;
}

/* MAP LAYOUT */
.map-layout{
  display:grid;
  grid-template-columns:1.2fr 1fr;
  grid-template-rows:auto 1fr auto;
  gap:20px;
}

.details-left,
.map-right{
  grid-row:1 / 3;
}

/* HEADER */
.detail-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:16px;
  margin-bottom:20px;
}

.detail-header h2{
  margin:0;
  color:#111827;
}

.company-row{
  display:flex;
  align-items:center;
  gap:12px;
}

.company-logo{
  width:48px;
  height:48px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  background:#f1f5ff;
  display:flex;
  align-items:center;
  justify-content:center;
  flex:0 0 48px;
  overflow:hidden;
}

.company-logo img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.company-logo-fallback{
  font-size:14px;
  font-weight:700;
  color:#2563eb;
  letter-spacing:0.5px;
}

.company-text h2{
  margin:2px 0 0;
}

.company-name{
  margin:0;
  font-size:12px;
  color:#6b7280;
  font-weight:600;
}

.badges{
  display:flex;
  gap:8px;
  margin-top:8px;
  flex-wrap:wrap;
}

.badge{
  display:inline-block;
  padding:4px 12px;
  background:#e0ecff;
  color:#2563eb;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.location{
  font-size:13px;
  color:#6b7280;
  display:flex;
  align-items:center;
  gap:6px;
  max-width:360px;
  white-space:normal;
  text-align:right;
}

.location i{
  color:#2563eb;
}

/* DETAILS */
.detail-section{
  margin-bottom:18px;
}

.detail-section h4{
  margin:0 0 6px;
  color:#111827;
}

.detail-section p{
  margin:0;
  color:#4b5563;
}

.grid-2{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}

.status{
  color:#16a34a;
  font-weight:600;
}

.detail-actions.bottom{
  grid-column:1 / -1;
  grid-row:3;
  display:flex;
  justify-content:flex-start;
  margin-top:8px;
}

/* MAP */
.map-right iframe{
  width:100%;
  min-height:260px;
  border:0;
  border-radius:12px;
}

.list-filters{
  display:grid;
  gap:8px;
  margin:10px 0 12px;
}

.filter-input-wrap{
  display:grid;
  grid-template-columns:auto 1fr;
  align-items:center;
  gap:8px;
  border:1px solid #dbe4ee;
  border-radius:10px;
  background:#f8fafc;
  min-height:40px;
  padding:0 10px;
}

.filter-input-wrap i{
  color:#64748b;
  font-size:13px;
}

.filter-input-wrap input{
  border:0;
  outline:0;
  background:transparent;
  color:#0f172a;
  font-size:12px;
  min-width:0;
}

.filter-select{
  min-height:40px;
  border:1px solid #dbe4ee;
  border-radius:10px;
  background:#ffffff;
  color:#334155;
  font-size:12px;
  padding:0 10px;
  outline:none;
}

.map-preview-wrap{
  position:relative;
  border-radius:12px;
  overflow:hidden;
  cursor:pointer;
}

.map-click-overlay{
  position:absolute;
  inset:0;
  display:flex;
  align-items:flex-end;
  justify-content:flex-end;
  padding:10px;
  background:linear-gradient(to top, rgba(15, 23, 42, 0.14), rgba(15, 23, 42, 0.02) 36%, transparent 70%);
}

.map-click-overlay span{
  background:rgba(15, 23, 42, 0.75);
  color:#fff;
  border-radius:999px;
  padding:6px 10px;
  font-size:11px;
  font-weight:600;
}

/* IMAGE */
.job-photo{
  width:100%;
  border-radius:12px;
  object-fit:cover;
  max-height:160px;
}

.job-photo.placeholder{
  display:flex;
  align-items:center;
  justify-content:center;
  background:#f3f4f6;
  color:#9ca3af;
  font-size:12px;
  border:1px dashed #e5e7eb;
  min-height:160px;
}

.job-photos{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
  margin-top:14px;
}

/* LIGHTBOX */
.lightbox{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.75);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:2000;
}

.lb-image{
  max-width:80vw;
  max-height:80vh;
  border-radius:12px;
  box-shadow:0 12px 40px rgba(0,0,0,0.4);
}

.lb-fade-enter-active,
.lb-fade-leave-active{
  transition:opacity .22s ease;
}

.lb-fade-enter-from,
.lb-fade-leave-to{
  opacity:0;
}

.lb-fade-enter-active .lb-image{
  transition:transform .22s ease, opacity .22s ease;
}

.lb-fade-enter-from .lb-image{
  transform:scale(.98);
  opacity:0;
}

.lb-nav{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  width:44px;
  height:44px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,0.4);
  background:rgba(0,0,0,0.45);
  color:#fff;
  font-size:18px;
  cursor:pointer;
}

.lb-nav.left{ left:24px; }
.lb-nav.right{ right:24px; }

.lb-close{
  position:absolute;
  top:16px;
  right:16px;
  width:36px;
  height:36px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,0.4);
  background:rgba(0,0,0,0.45);
  color:#fff;
  font-size:20px;
  cursor:pointer;
}

.lb-dots{
  position:absolute;
  bottom:24px;
  left:50%;
  transform:translateX(-50%);
  display:flex;
  gap:8px;
}

.dot{
  width:8px;
  height:8px;
  border-radius:999px;
  background:rgba(255,255,255,0.4);
  cursor:pointer;
}

.dot.active{
  background:#ffffff;
}

/* ACTION */
.apply-btn{
  background:#2563eb;
  color:#fff;
  padding:4px 10px;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
  font-size:11px;
  line-height:1;
  height:28px;
  min-height:0;
  width:auto;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
}

.apply-btn:hover{
  background:#1d4ed8;
}

.map-lightbox{
  padding:24px;
}

.map-modal-card{
  width:min(1000px, 94vw);
  background:#fff;
  border-radius:16px;
  overflow:hidden;
  border:1px solid #e5e7eb;
  box-shadow:0 18px 44px rgba(0,0,0,0.28);
}

.map-modal-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:12px;
  padding:16px 18px 12px;
  border-bottom:1px solid #e5e7eb;
}

.map-modal-label{
  margin:0 0 4px;
  font-size:11px;
  color:#64748b;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.06em;
}

.map-modal-head h3{
  margin:0;
  font-size:18px;
  color:#0f172a;
}

.map-modal-frame{
  width:100%;
  height:min(68vh, 560px);
  border:0;
  display:block;
}

.apply-btn.disabled,
.apply-btn:disabled{
  background:#9ca3af;
  cursor:not-allowed;
}

/* EMPTY */
.empty{
  color:#9ca3af;
  font-style:italic;
}

.panel-loading{
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:10px;
  min-height: calc(100vh - 260px);
  color:#64748b;
  text-align:center;
  border:1px dashed #dbe4ee;
  border-radius:12px;
  background:#f8fafc;
  margin-top:8px;
  width:100%;
}

.panel-loading.right{
  min-height: calc(100vh - 240px);
  width:100%;
  max-width:900px;
}

.panel-loading p{
  margin:0;
  font-size:13px;
  font-weight:600;
}

.panel-spinner{
  width:28px;
  height:28px;
  border-radius:999px;
  border:3px solid #dbe4ee;
  border-top-color:#16a34a;
  animation: panelSpin .75s linear infinite;
}

@keyframes panelSpin{
  to { transform: rotate(360deg); }
}

@media (max-width: 1100px) {
  .dashboard{
    flex-direction:column;
  }

  .job-list{
    width:100%;
  }

  .map-layout{
    grid-template-columns:1fr;
  }

  .panel-loading{
    min-height:220px;
  }

  .panel-loading.right{
    min-height:260px;
  }

  .skills-overlay{
    padding-top:24px;
  }

  .skills-picker-card{
    max-height:calc(100vh - 48px);
  }

  .skills-actions{
    flex-direction:column-reverse;
  }

  .skip-btn,
  .continue-btn{
    width:100%;
  }
}
</style>


