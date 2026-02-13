<template>
  <div class="profile-page">
    <div class="profile-shell">
      <div v-if="loading" class="page-loading">
        <div class="spinner"></div>
        <div class="loading-text">Loading profile...</div>
      </div>

      <div class="left-panel">
        <div class="avatar-card">
          <div class="avatar-image">
            <img v-if="profile.photoURL" :src="profile.photoURL" alt="Profile" class="avatar-photo" @error="handleImageError('profile')" />
            <div v-else class="avatar-circle">{{ userInitial }}</div>
          </div>
          <div class="left-head">
            <h3>{{ profile.username || "User" }}</h3>
            <p>{{ displayRole }}</p>
          </div>
        </div>

        <div class="side-section">
          <div class="side-title">Contact Information</div>
          <div class="side-row">
            <span class="side-label">Email</span>
            <span class="side-value">{{ profile.email || "-" }}</span>
          </div>
          <div class="side-row">
            <span class="side-label">Contact</span>
            <span class="side-value">{{ profile.contact || "-" }}</span>
          </div>
        </div>

        <div class="side-section">
          <div class="side-title">Account</div>
          <div class="side-row">
            <span class="side-label">Created</span>
            <span class="side-value">{{ profile.createdAt || "-" }}</span>
          </div>
        </div>
      </div>

      <div class="right-panel">
        <div class="main-card">
          <div class="top-bar">
            <div>
              <div class="name-line">{{ profile.username || "User" }}</div>
              <div class="sub-line">{{ profile.email || "No email" }}</div>
            </div>
            <button class="btn ghost" @click="toggleEdit">
              {{ isEditing ? "Cancel" : "Edit Profile" }}
            </button>
          </div>

          <div v-if="!isEditing" class="info-grid">
            <div class="info-item">
              <span class="label">Username</span>
              <span class="value">{{ profile.username || "-" }}</span>
            </div>
            <div class="info-item">
              <span class="label">Role</span>
              <span class="value">{{ displayRole }}</span>
            </div>
            <div class="info-item">
              <span class="label">Email</span>
              <span class="value">{{ profile.email || "-" }}</span>
            </div>
            <div class="info-item">
              <span class="label">Contact</span>
              <span class="value">{{ profile.contact || "-" }}</span>
            </div>
            <div v-if="isApplicant" class="info-item">
              <span class="label">Disability</span>
              <span class="value">{{ profile.disability || "-" }}</span>
            </div>
          </div>

          <div v-if="isEditing" class="modal-backdrop" @click.self="toggleEdit">
            <div class="modal">
              <div class="modal-hero">
                <div class="modal-hero-left">
              <div class="modal-avatar">
                <img v-if="profile.photoURL" :src="profile.photoURL" alt="Profile" class="modal-photo" @error="handleImageError('profile')" />
                <span v-else>{{ userInitial }}</span>
              </div>
              <div>
                    <div class="modal-title">Edit Profile</div>
                    <div class="modal-subtitle">Update your personal details and contact.</div>
                  </div>
                </div>
                <button class="btn ghost modal-close" @click="toggleEdit">Close</button>
              </div>
              <div class="modal-body">
                <div class="photo-row">
                  <div class="photo-preview">
                    <img v-if="form.photoURL" :src="form.photoURL" alt="Preview" @error="handleImageError('form')" />
                    <div v-else class="photo-placeholder">{{ userInitial }}</div>
                  </div>
                  <div class="photo-actions">
                    <input
                      ref="photoFileInput"
                      type="file"
                      accept="image/*"
                      class="hidden-input"
                      @change="handlePhotoSelected"
                    />
                    <button class="btn ghost" @click="openPhotoPicker" :disabled="photoUploading">
                      {{ photoUploading ? "Uploading..." : "Upload Photo" }}
                    </button>
                    <div v-if="photoUploading" class="upload-progress">
                      <div class="upload-bar">
                        <div class="upload-fill" :style="{ width: photoProgress + '%' }"></div>
                      </div>
                      <div class="upload-text">{{ photoProgress }}%</div>
                    </div>
                    <button
                      class="btn ghost danger"
                      @click="deletePhoto"
                      :disabled="photoUploading || (!form.photoURL && !form.photoPath)"
                    >
                      Remove Photo
                    </button>
                    <div class="photo-hint">PNG/JPG up to 5MB</div>
                  </div>
                </div>
                <div class="edit-form">
                  <div class="form-group">
                    <label>Username</label>
                    <input type="text" v-model="form.username" placeholder="Enter username" />
                  </div>
                  <div class="form-group">
                    <label>Contact</label>
                    <input type="text" v-model="form.contact" placeholder="Enter contact" />
                  </div>
                  <div v-if="isApplicant" class="form-group">
                    <label>Disability</label>
                    <select v-model="form.disability">
                      <option value="">Select disability</option>
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
              <div class="modal-footer">
                <button class="btn ghost" @click="toggleEdit">Cancel</button>
                <button class="btn save-btn" @click="saveProfile" :disabled="saving">
                  {{ saving ? "Saving..." : "Save Changes" }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { doc, getDoc, updateDoc } from "@/lib/laravel-data"
import { auth, db } from "@/lib/client-platform"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const loading = ref(true)
const profile = ref({
  username: "",
  email: "",
  contact: "",
  disability: "",
  photoURL: "",
  photoPath: "",
  role: "",
  createdAt: ""
})

const isEditing = ref(false)
const saving = ref(false)
const photoUploading = ref(false)
const photoProgress = ref(0)
const photoFileInput = ref(null)
const form = ref({
  username: "",
  contact: "",
  disability: "",
  photoURL: "",
  photoPath: ""
})

const userInitial = computed(() => {
  const base = (profile.value.username || profile.value.email || "U").trim()
  return base ? base[0].toUpperCase() : "U"
})

const displayRole = computed(() => {
  const role = (profile.value.role || "").toLowerCase()
  if (role === "employer") return "HR DEPARTMENT"
  if (role === "applicant") return "APPLICANT"
  return profile.value.role || "-"
})

const isApplicant = computed(() =>
  (profile.value.role || "").toLowerCase() === "applicant"
)

const loadProfile = async () => {
  const storedEmail = localStorage.getItem("userEmail")
  const storedName = localStorage.getItem("userName")
  const storedRole = localStorage.getItem("userRole")
  const storedPhoto = localStorage.getItem("userPhotoURL")

  try {
    const user = auth.currentUser
    if (user?.uid) {
      let snap = await getDoc(doc(db, "users", user.uid))
      if (!snap.exists()) {
        snap = await getDoc(doc(db, "Users", user.uid))
      }
      if (snap.exists()) {
        const data = snap.data()
        profile.value.username = data.username || data.name || storedName || "User"
        profile.value.email = data.email || user.email || storedEmail || ""
        profile.value.contact = data.contact || ""
        profile.value.disability = data.disability || ""
        profile.value.photoURL = data.photoURL || ""
        profile.value.photoPath = data.photoPath || ""
        profile.value.role = data.role || storedRole || ""
        profile.value.createdAt = data.createdAt?.toDate
          ? data.createdAt.toDate().toLocaleDateString("en-US", {
              year: "numeric",
              month: "long",
              day: "numeric"
            })
          : ""
        form.value.username = profile.value.username || ""
        form.value.contact = profile.value.contact || ""
        form.value.disability = profile.value.disability || ""
        form.value.photoURL = profile.value.photoURL || ""
        form.value.photoPath = profile.value.photoPath || ""
        if (profile.value.photoURL) {
          localStorage.setItem("userPhotoURL", profile.value.photoURL)
        }
        loading.value = false
        return
      }
    }

    profile.value.username = storedName || "User"
    profile.value.email = storedEmail || ""
    profile.value.contact = ""
    profile.value.disability = ""
    profile.value.photoURL = storedPhoto || ""
    profile.value.photoPath = ""
    profile.value.role = storedRole || ""
    form.value.username = profile.value.username || ""
    form.value.contact = profile.value.contact || ""
    form.value.disability = profile.value.disability || ""
    form.value.photoURL = profile.value.photoURL || ""
    form.value.photoPath = profile.value.photoPath || ""
  } finally {
    loading.value = false
  }
}

const toggleEdit = () => {
  if (isEditing.value) {
    form.value.username = profile.value.username || ""
    form.value.contact = profile.value.contact || ""
    form.value.disability = profile.value.disability || ""
    form.value.photoURL = profile.value.photoURL || ""
    form.value.photoPath = profile.value.photoPath || ""
  }
  isEditing.value = !isEditing.value
}

const openPhotoPicker = () => {
  photoFileInput.value?.click()
}

const compressImage = (file, maxSize = 1024, quality = 0.82) =>
  new Promise((resolve, reject) => {
    const img = new Image()
    const url = URL.createObjectURL(file)
    img.onload = () => {
      URL.revokeObjectURL(url)
      const scale = Math.min(1, maxSize / Math.max(img.width, img.height))
      const w = Math.round(img.width * scale)
      const h = Math.round(img.height * scale)
      const canvas = document.createElement("canvas")
      canvas.width = w
      canvas.height = h
      const ctx = canvas.getContext("2d")
      if (!ctx) {
        reject(new Error("Canvas not supported"))
        return
      }
      ctx.drawImage(img, 0, 0, w, h)
      canvas.toBlob(
        (blob) => {
          if (!blob) {
            reject(new Error("Compression failed"))
            return
          }
          resolve(blob)
        },
        "image/jpeg",
        quality
      )
    }
    img.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error("Image load failed"))
    }
    img.src = url
  })

const handlePhotoSelected = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const user = auth.currentUser
  if (!user?.uid) {
    Toastify({
      text: "Please login again",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  if (!file.type.startsWith("image/")) {
    Toastify({
      text: "Please select an image file",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    Toastify({
      text: "File too large (max 5MB)",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  photoUploading.value = true
  photoProgress.value = 0
  try {
    const safeName = file.name.replace(/[^\w.-]+/g, "_")
    const compressedBlob = await compressImage(file)
    const formData = new FormData()
    formData.append("image", compressedBlob, safeName)

    const res = await api.post("/uploads", formData, {
      onUploadProgress: (evt) => {
        if (evt.total) {
          photoProgress.value = Math.round((evt.loaded / evt.total) * 100)
        }
      }
    })

    const url = res.data?.url
    const path = res.data?.path || ""
    if (!url) {
      throw new Error("Upload failed")
    }

    form.value.photoURL = url
    form.value.photoPath = path
    profile.value.photoURL = url
    profile.value.photoPath = path
    localStorage.setItem("userPhotoURL", url)

    try {
      await updateDoc(doc(db, "users", user.uid), {
        photoURL: url,
        photoPath: path,
      })
    } catch {
      try {
        await updateDoc(doc(db, "Users", user.uid), {
          photoURL: url,
          photoPath: path,
        })
      } catch {
        // keep local preview even if immediate save fails
      }
    }

    Toastify({
      text: "Photo uploaded",
      backgroundColor: "#2ecc71",
    }).showToast()
  } catch (error) {
    Toastify({
      text: "Failed to upload photo",
      backgroundColor: "#e74c3c",
    }).showToast()
  } finally {
    photoUploading.value = false
    photoProgress.value = 0
    e.target.value = ""
  }
}

const deletePhoto = async () => {
  if (!form.value.photoPath && !form.value.photoURL) {
    return
  }

  photoUploading.value = true
  try {
    form.value.photoURL = ""
    form.value.photoPath = ""
    profile.value.photoURL = ""
    profile.value.photoPath = ""
    localStorage.removeItem("userPhotoURL")

    Toastify({
      text: "Photo removed",
      backgroundColor: "#2ecc71",
    }).showToast()
  } catch (error) {
    Toastify({
      text: "Failed to remove photo",
      backgroundColor: "#e74c3c",
    }).showToast()
  } finally {
    photoUploading.value = false
  }
}

const handleImageError = (target = "profile") => {
  if (target === "form") {
    form.value.photoURL = ""
    return
  }
  profile.value.photoURL = ""
}

const saveProfile = async () => {
  const user = auth.currentUser
  if (!user?.uid) {
    Toastify({
      text: "Please login again",
      backgroundColor: "#e74c3c",
    }).showToast()
    return
  }

  saving.value = true
  try {
    const payload = {
      username: form.value.username.trim(),
      contact: form.value.contact.trim(),
      disability: form.value.disability || "",
      photoURL: form.value.photoURL || "",
      photoPath: form.value.photoPath || "",
    }

    try {
      await updateDoc(doc(db, "users", user.uid), payload)
    } catch {
      await updateDoc(doc(db, "Users", user.uid), payload)
    }

    profile.value.username = payload.username || profile.value.username
    profile.value.contact = payload.contact || ""
    profile.value.disability = payload.disability || ""
    profile.value.photoURL = payload.photoURL || ""
    profile.value.photoPath = payload.photoPath || ""
    localStorage.setItem("userName", profile.value.username || "User")
    if (profile.value.photoURL) {
      localStorage.setItem("userPhotoURL", profile.value.photoURL)
    } else {
      localStorage.removeItem("userPhotoURL")
    }

    Toastify({
      text: "Profile updated",
      backgroundColor: "#2ecc71",
    }).showToast()

    isEditing.value = false
  } catch (error) {
    Toastify({
      text: "Failed to update profile",
      backgroundColor: "#e74c3c",
    }).showToast()
  } finally {
    saving.value = false
  }
}

onMounted(loadProfile)
</script>

<style scoped>
.profile-page{
  min-height:100vh;
  background:
    linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
  padding:28px 16px 64px;
}

.profile-shell{
  max-width:1240px;
  margin:0 auto;
  display:grid;
  grid-template-columns:320px 1fr;
  gap:20px;
  position:relative;
}

.page-loading{
  position:absolute;
  inset:0;
  background:rgba(255,255,255,0.88);
  border-radius:20px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:12px;
  z-index:10;
  backdrop-filter: blur(4px);
}

.spinner{
  width:38px;
  height:38px;
  border:3px solid #e2e8f0;
  border-top-color:#2563eb;
  border-radius:50%;
  animation:spin .8s linear infinite;
}

.loading-text{
  font-size:13px;
  color:#64748b;
  font-weight:600;
  letter-spacing:0.2px;
}

@keyframes spin{
  to { transform: rotate(360deg); }
}

.left-panel{
  display:flex;
  flex-direction:column;
  gap:16px;
}

.avatar-card{
  background:#ffffff;
  border:1px solid #e5e7eb;
  border-radius:16px;
  padding:18px;
  display:flex;
  align-items:center;
  gap:12px;
  box-shadow:0 10px 20px rgba(15, 23, 42, 0.05);
}

.avatar-image{
  width:72px;
  height:72px;
  border-radius:16px;
  background:linear-gradient(135deg, #cbd5f5, #e2e8f0);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
}

.avatar-circle{
  width:52px;
  height:52px;
  border-radius:50%;
  background:linear-gradient(135deg, #2563eb, #38bdf8);
  color:#ffffff;
  font-weight:800;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:20px;
}

.avatar-photo{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.left-head h3{
  margin:0;
  font-size:18px;
  color:#0f172a;
}

.left-head p{
  margin:4px 0 0;
  color:#64748b;
  font-size:12px;
  font-weight:600;
}

.side-section{
  background:#ffffff;
  border:1px solid #e5e7eb;
  border-radius:16px;
  padding:14px 16px;
}

.side-title{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:0.8px;
  color:#64748b;
  margin-bottom:10px;
  font-weight:700;
}

.side-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:8px 0;
  border-bottom:1px dashed #e5e7eb;
}

.side-row:last-child{
  border-bottom:none;
}

.side-label{
  color:#64748b;
  font-size:12px;
}

.side-value{
  color:#0f172a;
  font-size:12px;
  font-weight:700;
}

.right-panel{
  display:flex;
}

.main-card{
  background:#ffffff;
  border:1px solid #e5e7eb;
  border-radius:20px;
  padding:24px;
  width:100%;
  box-shadow:0 16px 30px rgba(15, 23, 42, 0.06);
}

.top-bar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding-bottom:14px;
  border-bottom:1px solid #e5e7eb;
  margin-bottom:14px;
}

.name-line{
  font-size:22px;
  font-weight:800;
  color:#0f172a;
}

.sub-line{
  font-size:13px;
  color:#64748b;
}

.loading{
  color:#94a3b8;
  font-style:italic;
}

.info-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

.info-item{
  background:#f8fafc;
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:12px;
}

.label{
  display:block;
  font-size:11px;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:0.6px;
  margin-bottom:6px;
}

.value{
  font-weight:700;
  color:#0f172a;
  font-size:14px;
}

.edit-form{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}

.form-group{
  display:flex;
  flex-direction:column;
  gap:6px;
}

.form-group label{
  font-size:12px;
  font-weight:600;
  color:#475569;
}

.form-group input{
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:12px 14px;
  font-size:14px;
  background:#ffffff;
  transition:border-color .2s ease, box-shadow .2s ease;
}

.form-group select{
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:12px 14px;
  font-size:14px;
  background:#ffffff;
  transition:border-color .2s ease, box-shadow .2s ease;
}

.form-group input:focus{
  outline:none;
  border-color:#2563eb;
  box-shadow:0 0 0 4px rgba(37, 99, 235, 0.12);
}

.form-group select:focus{
  outline:none;
  border-color:#2563eb;
  box-shadow:0 0 0 4px rgba(37, 99, 235, 0.12);
}

.btn{
  border:none;
  border-radius:10px;
  padding:8px 12px;
  font-weight:700;
  cursor:pointer;
}

.ghost{
  background:#f1f5f9;
  color:#0f172a;
}

.top-bar .btn{
  padding:6px 10px;
  font-size:12px;
  width:auto;
  white-space:nowrap;
}

.save-btn{
  background:#2563eb;
  color:#ffffff;
}

.modal-backdrop{
  position:fixed;
  inset:0;
  background:rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(6px);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:1000;
  padding:16px;
}

.modal{
  width:100%;
  max-width:720px;
  background:#ffffff;
  border-radius:22px;
  border:1px solid #e2e8f0;
  box-shadow:0 30px 80px rgba(15, 23, 42, 0.25);
  overflow:hidden;
}

.modal-hero{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:20px 22px;
  background:linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #fef3c7 100%);
  border-bottom:1px solid #e2e8f0;
}

.modal-title{
  font-size:18px;
  font-weight:800;
  color:#0f172a;
}

.modal-subtitle{
  font-size:12px;
  color:#64748b;
  margin-top:4px;
}

.modal-hero-left{
  display:flex;
  align-items:center;
  gap:14px;
}

.modal-avatar{
  width:44px;
  height:44px;
  border-radius:12px;
  background:linear-gradient(135deg, #2563eb, #38bdf8);
  color:#ffffff;
  font-weight:800;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:16px;
  overflow:hidden;
}

.modal-photo{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.modal-body{
  padding:20px 22px 6px;
}

.modal-footer{
  display:flex;
  justify-content:flex-end;
  gap:10px;
  padding:16px 22px 20px;
  border-top:1px solid #e2e8f0;
}

.photo-row{
  display:grid;
  grid-template-columns:140px 1fr;
  gap:16px;
  margin-bottom:16px;
  padding:14px;
  background:#f8fafc;
  border:1px solid #e2e8f0;
  border-radius:14px;
}

.photo-preview{
  width:140px;
  height:140px;
  border-radius:16px;
  overflow:hidden;
  background:#e2e8f0;
  display:flex;
  align-items:center;
  justify-content:center;
}

.photo-preview img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.photo-placeholder{
  width:100%;
  height:100%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:28px;
  font-weight:800;
  color:#0f172a;
  background:linear-gradient(135deg, #e2e8f0, #f8fafc);
}

.photo-actions{
  display:flex;
  flex-direction:column;
  gap:10px;
}

.upload-progress{
  display:flex;
  align-items:center;
  gap:10px;
}

.upload-bar{
  flex:1;
  height:8px;
  background:#e2e8f0;
  border-radius:999px;
  overflow:hidden;
}

.upload-fill{
  height:100%;
  background:linear-gradient(90deg, #2563eb, #38bdf8);
  width:0%;
  transition:width .2s ease;
}

.upload-text{
  min-width:36px;
  font-size:12px;
  color:#0f172a;
  font-weight:700;
  text-align:right;
}

.photo-hint{
  font-size:11px;
  color:#64748b;
}

.hidden-input{
  display:none;
}

.danger{
  color:#b91c1c;
  background:#fee2e2;
}

@media (max-width: 700px) {
  .photo-row{
    grid-template-columns:1fr;
  }
  .photo-preview{
    width:100%;
    height:200px;
  }
}

.modal-close{
  padding:6px 10px;
  font-size:12px;
}

@media (max-width: 980px) {
  .profile-shell{
    grid-template-columns:1fr;
  }
}

@media (max-width: 700px) {
  .info-grid{
    grid-template-columns:1fr;
  }
  .edit-form{
    grid-template-columns:1fr;
  }
  .top-bar{
    flex-direction:column;
    align-items:flex-start;
    gap:10px;
  }
}
</style>



