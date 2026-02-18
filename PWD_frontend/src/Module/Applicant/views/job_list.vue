<template>
  <div class="dashboard">
    <!-- LEFT -->
    <section class="job-list">
      <h2>Available Jobs</h2>

      <p v-if="jobs.length === 0" class="empty">
        No job posts available
      </p>

      <div
        v-for="job in jobs"
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
      <div v-if="jobs.length === 0" class="empty">
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

          <div class="detail-section">
            <h4>Description</h4>
            <p>{{ selectedJob.description || "No description provided." }}</p>
          </div>

        </div>

        <!-- RIGHT MAP + PHOTO -->
        <div class="map-right">
          <!-- MAP -->
          <iframe
            v-if="selectedJob.location"
            :src="mapUrl"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>

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
          <button class="apply-btn" @click="applyJob">
            Apply for this Job
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
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from "vue"
import { db } from "@/lib/client-platform"
import { addDoc, collection, serverTimestamp, onSnapshot } from "@/lib/laravel-data"

const jobs = ref([])
const selectedJob = ref(null)
const currentPhotoIndex = ref(0)
const isLightboxOpen = ref(false)
let unsubscribeJobs = null

onMounted(() => {
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
            ""
        }
      })
      .filter(job => job.status === "open")
      .sort((a, b) => getCreatedAtMillis(b.createdAt) - getCreatedAtMillis(a.createdAt))

    if (selectedJob.value) {
      selectedJob.value = jobs.value.find(j => j.id === selectedJob.value.id) || null
    }
  })
})

onBeforeUnmount(() => {
  if (typeof unsubscribeJobs === "function") {
    unsubscribeJobs()
  }
})

const getCreatedAtMillis = (ts) => {
  if (!ts) return 0
  if (typeof ts?.toMillis === "function") return ts.toMillis()
  if (typeof ts?.seconds === "number") return ts.seconds * 1000
  if (ts instanceof Date) return ts.getTime()
  return 0
}

// auto clear if deleted
watch(jobs, (newJobs) => {
  if (
    selectedJob.value &&
    !newJobs.find(j => j.id === selectedJob.value.id)
  ) {
    selectedJob.value = null
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

// APPLY
const applyJob = async () => {
  if (!selectedJob.value) return

  try {
    await addDoc(collection(db, "applications"), {
      jobId: selectedJob.value.id,
      jobTitle: selectedJob.value.title,
      appliedAt: serverTimestamp(),
      status: "pending"
    })

    alert("Application sent!")

  } catch (err) {
    console.error(err)
    alert("Failed to apply")
  }
}
</script>

<style scoped>
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

/* EMPTY */
.empty{
  color:#9ca3af;
  font-style:italic;
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
}
</style>


