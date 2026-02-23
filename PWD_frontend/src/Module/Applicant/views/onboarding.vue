<template>
  <div class="onboarding-page">
    <div class="onboarding-shell">
      <header class="onboarding-head">
        <p class="eyebrow">Applicant Onboarding</p>
        <h1>Complete your profile first</h1>
        <p class="copy">
          Fill out your information step-by-step before you can access the applicant portal.
        </p>
      </header>

      <div class="stepper" aria-label="Onboarding steps">
        <div
          v-for="(label, idx) in stepLabels"
          :key="label"
          class="step-item"
          :class="{ active: step === idx + 1, done: step > idx + 1 }"
        >
          <span class="step-dot">{{ idx + 1 }}</span>
          <span class="step-label">{{ label }}</span>
        </div>
      </div>

      <section class="card">
        <transition name="fade-overlay">
          <div v-if="showExitConfirm" class="success-guide-overlay" aria-live="polite">
            <div class="success-guide-content">
              <h3>Exit onboarding?</h3>
              <p>Are you sure you want to exit onboarding?</p>
              <p>If you continue, you will be logged out and need to sign in again.</p>
              <div class="confirm-actions">
                <button class="btn ghost" type="button" @click="showExitConfirm = false" :disabled="exitLoading">
                  No
                </button>
                <button class="btn primary" type="button" @click="confirmExitOnboarding" :disabled="exitLoading">
                  <template v-if="exitLoading">
                    <span class="btn-spinner" aria-hidden="true"></span>
                    <span>Logging out...</span>
                  </template>
                  <template v-else>
                    <span>Yes</span>
                  </template>
                </button>
              </div>
            </div>
          </div>
        </transition>

        <transition name="fade-overlay">
          <div v-if="saving" class="submit-loading-overlay" aria-live="polite" aria-busy="true">
            <div class="submit-loading-content">
              <div class="spinner"></div>
              <p class="step-loading-title">Submitting your information...</p>
              <p class="step-loading-copy">Please wait while we complete your applicant onboarding.</p>
            </div>
          </div>
        </transition>

        <transition name="fade-overlay">
          <div v-if="showCongrats" class="congrats-overlay" aria-live="polite">
            <div class="congrats-content">
              <div class="congrats-ring"></div>
              <div class="congrats-check">✓</div>
              <h3>Congratulations!</h3>
              <p>Your profile is complete. Welcome to the applicant portal.</p>
            </div>
          </div>
        </transition>

        <transition name="fade-overlay">
          <div v-if="showSuccessGuide" class="success-guide-overlay" aria-live="polite">
            <div class="success-guide-content">
              <h3>You are all set</h3>
              <p>
                Your applicant information has been saved successfully.
              </p>
              <p>
                You will now proceed to the applicant portal where you can browse jobs and continue your application journey.
              </p>
              <div class="guide-points">
                <span>Complete profile</span>
                <span>Browse jobs</span>
                <span>Apply to roles</span>
              </div>
            </div>
          </div>
        </transition>

        <transition name="fade-overlay">
          <div v-if="nextLoading" class="step-loading-overlay" aria-live="polite" aria-busy="true">
            <div class="step-loading-box">
              <div class="spinner"></div>
              <p class="step-loading-title">Loading next step...</p>
              <p class="step-loading-copy">
                {{ pendingStepMessage || "Please wait while we prepare your next step." }}
              </p>
            </div>
          </div>
        </transition>

        <div v-if="loading" class="loading-wrap">
          <div class="spinner"></div>
          <p>Loading your account...</p>
        </div>

        <template v-else>
          <div v-if="step === 1" class="step-panel">
            <h2>Basic Information</h2>
            <p class="step-copy">Fill the key details to unlock the right opportunities.</p>

            <div class="step-form-scroll">
              <div class="form-grid">
                <div class="form-group form-group-full">
                  <label>Disability</label>
                  <select v-model="form.pwdCategory">
                    <option value="">-- SELECT --</option>
                    <option v-for="option in disabilityOptions" :key="option" :value="option">
                      {{ option }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label>First Name</label>
                  <input v-model="form.firstName" type="text" placeholder="John" />
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input v-model="form.lastName" type="text" placeholder="Doe" />
                </div>

                <div class="form-group">
                  <label>Sex at Birth</label>
                  <select v-model="form.sexAtBirth">
                    <option value="">-- SELECT --</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Prefer not to say</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input v-model="form.birthDate" type="date" />
                </div>

                <div class="form-group form-group-full">
                  <label>Academic Level</label>
                  <select v-model="form.academicLevel">
                    <option value="">-- SELECT --</option>
                    <option>Elementary</option>
                    <option>Junior High School</option>
                    <option>Senior High School</option>
                    <option>Vocational / TESDA</option>
                    <option>College Undergraduate</option>
                    <option>College Graduate</option>
                    <option>Postgraduate</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Address Province</label>
                  <input v-model="form.addressProvince" type="text" placeholder="Cavite" />
                </div>
                <div class="form-group">
                  <label>Address City</label>
                  <input v-model="form.addressCity" type="text" placeholder="Dasmarinas" />
                </div>

                <div class="form-group form-group-full">
                  <label>Mobile Phone Number</label>
                  <input v-model="form.mobileNumber" type="text" placeholder="Enter mobile number (09XXXXXXXXX)" />
                </div>

                <div class="form-group form-group-full">
                  <label>Preferred Role</label>
                  <select v-model="form.preferredRole">
                    <option value="">-- SELECT --</option>
                    <option>Administrative Assistant</option>
                    <option>Office Staff</option>
                    <option>Clerk / Office Clerk</option>
                    <option>Encoder / Data Entry Specialist</option>
                    <option>Document Controller</option>
                    <option>Records Assistant</option>
                    <option>Receptionist</option>
                    <option>Front Desk Assistant</option>
                    <option>Customer Service Representative</option>
                    <option>Call Center Agent (Voice)</option>
                    <option>Call Center Agent (Non-Voice)</option>
                    <option>Chat / Email Support Specialist</option>
                    <option>Sales Associate</option>
                    <option>Cashier</option>
                    <option>Store Crew / Retail Staff</option>
                    <option>Service Crew</option>
                    <option>Production Worker</option>
                    <option>Factory Worker</option>
                    <option>Warehouse Assistant</option>
                    <option>Inventory Clerk</option>
                    <option>Quality Control Assistant</option>
                    <option>Packing Staff</option>
                    <option>Machine Operator Assistant</option>
                    <option>Messenger / Utility Staff</option>
                    <option>Housekeeping Staff</option>
                    <option>Janitorial Staff</option>
                    <option>Security Guard</option>
                    <option>Driver / Delivery Rider</option>
                    <option>Cook / Kitchen Staff</option>
                    <option>Baker / Kitchen Helper</option>
                    <option>Tailor / Sewing Operator</option>
                    <option>Technician Assistant</option>
                    <option>IT Support Staff</option>
                    <option>Computer Operator</option>
                    <option>Graphic Design Assistant</option>
                    <option>Social Media Assistant</option>
                    <option>Content / Admin Support</option>
                    <option>Bookkeeping Assistant</option>
                    <option>Accounting Staff</option>
                    <option>Finance Assistant</option>
                    <option>HR Assistant</option>
                    <option>Recruitment Assistant</option>
                    <option>Training Assistant</option>
                    <option>Project Assistant</option>
                    <option>Community Liaison Assistant</option>
                    <option>Freelance / Home-based Work</option>
                    <option>Remote Virtual Assistant</option>
                    <option>Self-Employment / Livelihood</option>
                    <option>Others</option>
                  </select>
                </div>

                <div class="form-group form-group-full">
                  <label>Years of Experience</label>
                  <select v-model="form.yearsExperience">
                    <option value="">Select years of experience</option>
                    <option>None / Fresh Graduate</option>
                    <option>Less than 1 year</option>
                    <option>1-2 years</option>
                    <option>3-5 years</option>
                    <option>6+ years</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Preferred Province</label>
                  <input v-model="form.preferredProvince" type="text" placeholder="Cavite" />
                </div>
                <div class="form-group">
                  <label>Preferred City</label>
                  <input v-model="form.preferredCity" type="text" placeholder="Dasmarinas" />
                </div>

                <div class="form-group form-group-full">
                  <label>Work Mode</label>
                  <select v-model="form.workMode">
                    <option value="">-- SELECT --</option>
                    <option>On-site</option>
                    <option>Remote</option>
                    <option>Hybrid</option>
                    <option>Any</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="step === 2" class="step-panel">
            <h2>Account Setup</h2>
            <p class="step-copy">Set how your applicant profile will appear in the portal.</p>

            <div class="form-grid">
              <div class="form-group">
                <label>Username</label>
                <input
                  v-model="form.username"
                  type="text"
                  placeholder="Username from registration"
                  readonly
                  class="readonly-input"
                />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input :value="form.email" type="email" disabled />
              </div>
              <div class="form-group">
                <label>Monthly Salary (Min)</label>
                <input v-model="form.salaryMin" type="number" min="0" placeholder="5000" />
              </div>
              <div class="form-group">
                <label>Monthly Salary (Max)</label>
                <input v-model="form.salaryMax" type="number" min="0" placeholder="11000" />
              </div>
              <div class="form-group form-group-full">
                <label>PWD ID Number</label>
                <input v-model="form.pwdIdNumber" type="text" placeholder="Enter your PWD ID number" />
              </div>
              <div class="form-group">
                <label>Profile Picture</label>
                <input
                  ref="profileImageInput"
                  type="file"
                  accept="image/*"
                  class="hidden-file"
                  @change="onProfileImageSelected"
                />
                <button class="upload-btn" type="button" @click="triggerProfileImageUpload" :disabled="uploadBusy">
                  {{ uploadBusy ? "Uploading..." : "Upload Profile Picture" }}
                </button>
                <div v-if="form.profilePictureUrl" class="upload-preview">
                  <img :src="form.profilePictureUrl" alt="Profile preview" />
                </div>
              </div>
              <div class="form-group">
                <label>PWD ID Image</label>
                <input
                  ref="pwdIdImageInput"
                  type="file"
                  accept="image/*"
                  class="hidden-file"
                  @change="onPwdIdImageSelected"
                />
                <button class="upload-btn" type="button" @click="triggerPwdIdUpload" :disabled="uploadBusy">
                  {{ uploadBusy ? "Uploading..." : "Upload PWD ID Image" }}
                </button>
                <div v-if="form.pwdIdImageUrl" class="upload-preview">
                  <img :src="form.pwdIdImageUrl" alt="PWD ID preview" />
                </div>
              </div>
              <div class="form-group form-group-full">
                <label>Profile Summary (Optional)</label>
                <textarea
                  v-model="form.profileSummary"
                  rows="4"
                  placeholder="Tell employers about your strengths, preferred tasks, or accessibility support you may need."
                ></textarea>
              </div>
            </div>
          </div>

          <div v-else class="step-panel">
            <h2>Review & Submit</h2>
            <p class="step-copy">Please confirm your details before finishing onboarding.</p>

            <div class="review-layout">
              <div class="profile-preview-card">
                <div class="profile-preview-banner"></div>
                <div class="profile-preview-body">
                  <div class="profile-preview-avatar">
                    <img
                      v-if="form.profilePictureUrl"
                      :src="form.profilePictureUrl"
                      alt="Applicant profile preview"
                    />
                    <span v-else>{{ (form.firstName || form.username || "U").slice(0, 1).toUpperCase() }}</span>
                  </div>

                  <div class="profile-preview-main">
                    <h3>{{ [form.firstName, form.lastName].filter(Boolean).join(" ") || form.username || "Applicant" }}</h3>
                    <p class="profile-preview-handle">@{{ form.username || "username" }}</p>
                    <p class="profile-preview-meta">{{ form.preferredRole || "Preferred role not set" }}</p>
                    <p class="profile-preview-summary">
                      {{ form.profileSummary || "No profile summary provided yet." }}
                    </p>
                  </div>

                  <div class="profile-preview-tags">
                    <span>{{ form.pwdCategory || "Disability not set" }}</span>
                    <span>{{ form.workMode || "Work mode not set" }}</span>
                    <span>{{ form.preferredCity || form.addressCity || "City not set" }}</span>
                  </div>
                </div>
              </div>

              <div class="review-grid">
                <div class="review-row">
                  <span>First Name</span>
                  <strong>{{ form.firstName || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Last Name</span>
                  <strong>{{ form.lastName || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Username</span>
                  <strong>{{ form.username || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Email</span>
                  <strong>{{ form.email || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Mobile Number</span>
                  <strong>{{ form.mobileNumber || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Disability</span>
                  <strong>{{ form.pwdCategory || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Preferred Role</span>
                  <strong>{{ form.preferredRole || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>PWD ID Number</span>
                  <strong>{{ form.pwdIdNumber || "-" }}</strong>
                </div>
                <div class="review-row">
                  <span>Work Mode</span>
                  <strong>{{ form.workMode || "-" }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="actions">
            <button class="btn ghost" type="button" @click="handleBackAction" :disabled="saving || nextLoading">
              Back
            </button>
            <button
              v-if="step < 3"
              class="btn primary"
              type="button"
              @click="nextStep"
              :disabled="saving || nextLoading"
            >
              <template v-if="nextLoading">
                <span class="btn-spinner" aria-hidden="true"></span>
                <span>Loading...</span>
              </template>
              <template v-else>
                <span>Next</span>
              </template>
            </button>
            <button
              v-else
              class="btn primary"
              type="button"
              @click="finishOnboarding"
              :disabled="saving"
            >
              {{ saving ? "Saving..." : "Finish & Continue" }}
            </button>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useRouter } from "vue-router"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import api from "@/services/api"
import { auth, db } from "@/lib/client-platform"
import { doc, updateDoc } from "@/lib/laravel-data"

const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const uploadBusy = ref(false)
const nextLoading = ref(false)
const pendingStepMessage = ref("")
const showCongrats = ref(false)
const showSuccessGuide = ref(false)
const showExitConfirm = ref(false)
const exitLoading = ref(false)
const step = ref(1)
const stepLabels = ["Basic Info", "Contact & Disability", "Review"]

const disabilityOptions = [
  "Deaf or Hard of Hearing",
  "Visual Disability (Blind / Low Vision)",
  "Mobility / Orthopedic Disability",
  "Physical Disability",
  "Speech and Language Impairment",
  "Intellectual Disability",
  "Learning Disability",
  "Psychosocial Disability",
  "Neurological Disability",
  "Multiple Disabilities",
  "Chronic Illness / Invisible Disability",
  "Others",
]

const form = ref({
  username: "",
  email: "",
  firstName: "",
  lastName: "",
  sexAtBirth: "",
  birthDate: "",
  academicLevel: "",
  addressProvince: "",
  addressCity: "",
  mobileNumber: "",
  salaryMin: "",
  salaryMax: "",
  pwdCategory: "",
  preferredRole: "",
  yearsExperience: "",
  preferredProvince: "",
  preferredCity: "",
  workMode: "",
  profileSummary: "",
  pwdIdNumber: "",
  profilePictureUrl: "",
  profilePicturePath: "",
  pwdIdImageUrl: "",
  pwdIdImagePath: "",
})

const profileImageInput = ref(null)
const pwdIdImageInput = ref(null)

function getOnboardingDraftKey() {
  const uid = String(auth.currentUser?.uid || localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  const email = String(localStorage.getItem("userEmail") || "").trim().toLowerCase()
  if (uid) return `applicantOnboardingDraft:${uid}`
  if (email) return `applicantOnboardingDraft:${email}`
  return "applicantOnboardingDraft"
}

function saveDraftToLocal() {
  try {
    localStorage.setItem(
      getOnboardingDraftKey(),
      JSON.stringify({
        step: step.value,
        form: form.value,
      }),
    )
  } catch {
    // ignore localStorage quota/serialization issues
  }
}

function loadDraftFromLocal() {
  try {
    const raw = localStorage.getItem(getOnboardingDraftKey())
    if (!raw) return
    const parsed = JSON.parse(raw)
    if (parsed?.form && typeof parsed.form === "object") {
      form.value = { ...form.value, ...parsed.form }
    }
    if (Number.isInteger(parsed?.step)) {
      step.value = Math.min(3, Math.max(1, Number(parsed.step)))
    }
  } catch {
    // ignore bad draft data
  }
}

function clearDraftFromLocal() {
  try {
    localStorage.removeItem(getOnboardingDraftKey())
  } catch {
    // ignore
  }
}

function toast(text, backgroundColor = "#e74c3c") {
  Toastify({ text, backgroundColor }).showToast()
}

async function nextStep() {
  if (step.value === 1) {
    if (!form.value.firstName.trim() || !form.value.lastName.trim()) {
      toast("Please enter your first and last name.")
      return
    }
    if (!form.value.sexAtBirth) {
      toast("Please select your sex at birth.")
      return
    }
    if (!form.value.birthDate) {
      toast("Please enter your date of birth.")
      return
    }
    if (!form.value.mobileNumber.trim()) {
      toast("Please enter your mobile number.")
      return
    }
    if (!form.value.pwdCategory) {
      toast("Please select your PWD category.")
      return
    }
    if (!form.value.preferredRole) {
      toast("Please select your preferred role.")
      return
    }
  }

  if (step.value === 2) {
    if (!form.value.username.trim()) {
      toast("Please enter a username.")
      return
    }
    if (!form.value.pwdIdNumber.trim()) {
      toast("Please enter your PWD ID number.")
      return
    }
    if (!form.value.profilePictureUrl) {
      toast("Please upload your profile picture.")
      return
    }
    if (!form.value.pwdIdImageUrl) {
      toast("Please upload your PWD ID image.")
      return
    }
  }

  nextLoading.value = true
  try {
    const nextStepNumber = Math.min(3, step.value + 1)
    pendingStepMessage.value = `You can go to Step ${nextStepNumber}.`
    await new Promise((resolve) => setTimeout(resolve, 500))
    step.value = nextStepNumber
    toast(`You can go to Step ${nextStepNumber}.`, "#2ecc71")
  } finally {
    nextLoading.value = false
    pendingStepMessage.value = ""
  }
}

function prevStep() {
  if (step.value <= 1) {
    showExitConfirm.value = true
    return
  }
  step.value = Math.max(1, step.value - 1)
}

function handleBackAction() {
  if (step.value <= 1) {
    showExitConfirm.value = true
    return
  }
  prevStep()
}

async function loadUser() {
  const uid = String(auth.currentUser?.uid || localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  if (!uid) {
    toast("Session missing. Please login again.")
    router.replace({ path: "/login", query: { force: "1" } })
    return
  }

  loading.value = true
  try {
    const res = await api.get(`/users/${uid}`)
    const user = res?.data || {}
    const serverOnboarding = user?.onboardingData && typeof user.onboardingData === "object"
      ? user.onboardingData
      : null
    form.value.username = String(user.username || user.name || localStorage.getItem("userName") || "").trim()
    form.value.email = String(user.email || localStorage.getItem("userEmail") || "").trim()
    form.value.mobileNumber = String(user.contact || "").trim()
    form.value.pwdCategory = String(user.disability || "").trim()
    if (serverOnboarding) {
      form.value = {
        ...form.value,
        ...serverOnboarding,
        username: String(user.username || serverOnboarding.username || form.value.username || "").trim(),
        email: String(user.email || serverOnboarding.email || form.value.email || "").trim(),
        mobileNumber: String(user.contact || serverOnboarding.mobileNumber || form.value.mobileNumber || "").trim(),
        pwdCategory: String(user.disability || serverOnboarding.pwdCategory || form.value.pwdCategory || "").trim(),
      }
    }
    if (!form.value.firstName && form.value.username) {
      const parts = form.value.username.split(/\s+/).filter(Boolean)
      if (parts.length > 1) {
        form.value.firstName = parts[0]
        form.value.lastName = parts.slice(1).join(" ")
      }
    }

    if (user.profileCompleted === true) {
      localStorage.setItem("userProfileCompleted", "true")
      router.replace("/applicant/job_list")
      return
    }

    loadDraftFromLocal()
  } catch (error) {
    toast(error?.response?.data?.message || "Failed to load onboarding info.")
  } finally {
    loading.value = false
  }
}

function triggerProfileImageUpload() {
  profileImageInput.value?.click()
}

function triggerPwdIdUpload() {
  pwdIdImageInput.value?.click()
}

async function uploadImageFile(file, target) {
  if (!file) return
  if (!file.type.startsWith("image/")) {
    toast("Please upload an image file.")
    return
  }

  uploadBusy.value = true
  try {
    const formData = new FormData()
    formData.append("image", file)
    const res = await api.post("/uploads", formData)
    const url = String(res?.data?.url || "").trim()
    const path = String(res?.data?.path || "").trim()
    if (!url) throw new Error("Upload failed")

    if (target === "profile") {
      form.value.profilePictureUrl = url
      form.value.profilePicturePath = path
    } else {
      form.value.pwdIdImageUrl = url
      form.value.pwdIdImagePath = path
    }

    toast("Image uploaded successfully.", "#2ecc71")
  } catch (error) {
    toast(error?.response?.data?.message || "Image upload failed.")
  } finally {
    uploadBusy.value = false
  }
}

async function onProfileImageSelected(event) {
  const file = event.target.files?.[0]
  await uploadImageFile(file, "profile")
  event.target.value = ""
}

async function onPwdIdImageSelected(event) {
  const file = event.target.files?.[0]
  await uploadImageFile(file, "pwd")
  event.target.value = ""
}

async function finishOnboarding() {
  if (!form.value.username.trim() || !form.value.mobileNumber.trim() || !form.value.pwdCategory) {
    toast("Please complete all required information before submitting.")
    return
  }

  const uid = String(auth.currentUser?.uid || localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  if (!uid) {
    toast("Session missing. Please login again.")
    router.replace({ path: "/login", query: { force: "1" } })
    return
  }

  saving.value = true
  try {
    const payload = {
      username: form.value.username.trim(),
      contact: form.value.mobileNumber.trim(),
      disability: form.value.pwdCategory,
      photoURL: form.value.profilePictureUrl || "",
      photoPath: form.value.profilePicturePath || "",
      profileCompleted: true,
      onboardingData: {
        firstName: form.value.firstName.trim(),
        lastName: form.value.lastName.trim(),
        sexAtBirth: form.value.sexAtBirth,
        birthDate: form.value.birthDate,
        academicLevel: form.value.academicLevel,
        addressProvince: form.value.addressProvince.trim(),
        addressCity: form.value.addressCity.trim(),
        mobileNumber: form.value.mobileNumber.trim(),
        salaryMin: form.value.salaryMin,
        salaryMax: form.value.salaryMax,
        pwdCategory: form.value.pwdCategory,
        preferredRole: form.value.preferredRole,
        yearsExperience: form.value.yearsExperience,
        preferredProvince: form.value.preferredProvince.trim(),
        preferredCity: form.value.preferredCity.trim(),
        workMode: form.value.workMode,
        profileSummary: form.value.profileSummary.trim(),
        pwdIdNumber: form.value.pwdIdNumber.trim(),
        profilePictureUrl: form.value.profilePictureUrl,
        profilePicturePath: form.value.profilePicturePath,
        pwdIdImageUrl: form.value.pwdIdImageUrl,
        pwdIdImagePath: form.value.pwdIdImagePath,
        username: form.value.username.trim(),
        email: form.value.email.trim(),
      },
    }
    await api.put(`/users/${uid}`, payload)

    try {
      await updateDoc(doc(db, "users", uid), {
        username: payload.username,
        contact: payload.contact,
        disability: payload.disability,
        photoURL: payload.photoURL,
        photoPath: payload.photoPath,
        onboardingBasicInfo: {
          firstName: form.value.firstName.trim(),
          lastName: form.value.lastName.trim(),
          sexAtBirth: form.value.sexAtBirth,
          birthDate: form.value.birthDate,
          academicLevel: form.value.academicLevel,
          addressProvince: form.value.addressProvince.trim(),
          addressCity: form.value.addressCity.trim(),
          salaryMin: form.value.salaryMin,
          salaryMax: form.value.salaryMax,
          preferredRole: form.value.preferredRole,
          yearsExperience: form.value.yearsExperience,
          preferredProvince: form.value.preferredProvince.trim(),
          preferredCity: form.value.preferredCity.trim(),
          workMode: form.value.workMode,
          profileSummary: form.value.profileSummary.trim(),
          pwdIdNumber: form.value.pwdIdNumber.trim(),
          profilePictureUrl: form.value.profilePictureUrl,
          profilePicturePath: form.value.profilePicturePath,
          pwdIdImageUrl: form.value.pwdIdImageUrl,
          pwdIdImagePath: form.value.pwdIdImagePath,
        },
      })
    } catch {
      try {
        await updateDoc(doc(db, "Users", uid), {
          username: payload.username,
          contact: payload.contact,
          disability: payload.disability,
          photoURL: payload.photoURL,
          photoPath: payload.photoPath,
          onboardingBasicInfo: {
            firstName: form.value.firstName.trim(),
            lastName: form.value.lastName.trim(),
            sexAtBirth: form.value.sexAtBirth,
            birthDate: form.value.birthDate,
            academicLevel: form.value.academicLevel,
            addressProvince: form.value.addressProvince.trim(),
            addressCity: form.value.addressCity.trim(),
            salaryMin: form.value.salaryMin,
            salaryMax: form.value.salaryMax,
            preferredRole: form.value.preferredRole,
            yearsExperience: form.value.yearsExperience,
            preferredProvince: form.value.preferredProvince.trim(),
            preferredCity: form.value.preferredCity.trim(),
            workMode: form.value.workMode,
            profileSummary: form.value.profileSummary.trim(),
            pwdIdNumber: form.value.pwdIdNumber.trim(),
            profilePictureUrl: form.value.profilePictureUrl,
            profilePicturePath: form.value.profilePicturePath,
            pwdIdImageUrl: form.value.pwdIdImageUrl,
            pwdIdImagePath: form.value.pwdIdImagePath,
          },
        })
      } catch {
        // Ignore Firestore sync failure; backend remains source of truth for gate.
      }
    }

    localStorage.setItem("userName", payload.username)
    if (payload.photoURL) {
      localStorage.setItem("userPhotoURL", payload.photoURL)
    }
    localStorage.setItem("userProfileCompleted", "true")
    localStorage.removeItem("newApplicantNeedsProfileFill")
    clearDraftFromLocal()

    showCongrats.value = true
    await new Promise((resolve) => setTimeout(resolve, 1400))
    showCongrats.value = false
    showSuccessGuide.value = true
    await new Promise((resolve) => setTimeout(resolve, 1700))
    showSuccessGuide.value = false
    toast("Profile completed successfully!", "#2ecc71")
    router.replace("/applicant/job_list")
  } catch (error) {
    toast(error?.response?.data?.message || "Failed to save onboarding information.")
  } finally {
    saving.value = false
  }
}

async function confirmExitOnboarding() {
  exitLoading.value = true
  try {
    const uid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
    const sessionKey = String(localStorage.getItem("activeSessionId") || "").trim()
    const accountType = String(localStorage.getItem("userCollection") || "users").trim()

    if (uid && sessionKey) {
      try {
        await api.post("/auth/session/logout", {
          uid,
          sessionKey,
          accountType,
        })
      } catch {
        // Best-effort logout only.
      }
    }

    auth.currentUser = null
    clearDraftFromLocal()
    localStorage.removeItem("userUid")
    localStorage.removeItem("uid")
    localStorage.removeItem("userEmail")
    localStorage.removeItem("userName")
    localStorage.removeItem("userRole")
    localStorage.removeItem("companyId")
    localStorage.removeItem("companyName")
    localStorage.removeItem("activeSessionId")
    localStorage.removeItem("sessionUid")
    localStorage.removeItem("userCollection")
    localStorage.removeItem("userProfileCompleted")
    localStorage.removeItem("userPhotoURL")

    showExitConfirm.value = false
    router.replace({ path: "/login", query: { force: "1" } })
  } finally {
    exitLoading.value = false
  }
}

onMounted(loadUser)

watch(
  [form, step],
  () => {
    if (!loading.value && !saving.value) {
      saveDraftToLocal()
    }
  },
  { deep: true },
)
</script>

<style scoped>
.onboarding-page {
  min-height: 100vh;
  padding: 24px 16px 30px;
  background: linear-gradient(180deg, #f8fafc 0%, #eef4f9 100%);
}

.onboarding-shell {
  max-width: 920px;
  margin: 0 auto;
}

.onboarding-head {
  margin-bottom: 14px;
}

.eyebrow {
  margin: 0;
  color: #0f6a32;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.onboarding-head h1 {
  margin: 6px 0 0;
  color: #0f172a;
  font-size: clamp(1.35rem, 2vw, 1.8rem);
}

.copy {
  margin: 7px 0 0;
  color: #475569;
  line-height: 1.55;
}

.stepper {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  margin-bottom: 12px;
}

.step-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 12px;
  border: 1px solid #dbe5ef;
  background: #f8fbff;
  color: #64748b;
}

.step-item.active {
  border-color: #a8d5b7;
  background: #eef9f1;
  color: #14532d;
}

.step-item.done {
  border-color: #cae8d4;
  background: #f4fbf6;
  color: #166534;
}

.step-dot {
  width: 24px;
  height: 24px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  background: #e8edf4;
  color: inherit;
  flex-shrink: 0;
}

.step-item.active .step-dot,
.step-item.done .step-dot {
  background: #d9f0e1;
}

.step-label {
  font-size: 0.84rem;
  font-weight: 600;
}

.card {
  position: relative;
  border-radius: 0;
  background: transparent;
  border: 0;
  box-shadow: none;
  padding: 0;
}

.step-loading-overlay {
  position: absolute;
  inset: 0;
  z-index: 4;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(2px);
  display: grid;
  place-items: center;
  border-radius: 0;
  padding: 16px;
}

.submit-loading-overlay,
.congrats-overlay {
  position: absolute;
  inset: 0;
  z-index: 6;
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(3px);
  display: grid;
  place-items: center;
  padding: 16px;
}

.success-guide-overlay {
  position: absolute;
  inset: 0;
  z-index: 7;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(3px);
  display: grid;
  place-items: center;
  padding: 16px;
}

.submit-loading-content,
.congrats-content {
  text-align: center;
  max-width: 360px;
}

.success-guide-content {
  text-align: center;
  max-width: 460px;
  animation: congratsPop 0.35s ease;
}

.success-guide-content h3 {
  margin: 0;
  color: #0f172a;
  font-size: 1.2rem;
}

.success-guide-content p {
  margin: 8px 0 0;
  color: #475569;
  line-height: 1.5;
}

.guide-points {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}

.guide-points span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f4f8fc;
  border: 1px solid #dbe6f0;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 600;
}

.confirm-actions {
  margin-top: 14px;
  display: flex;
  justify-content: center;
  gap: 10px;
}

.congrats-content {
  position: relative;
  animation: congratsPop 0.45s ease;
}

.congrats-ring {
  width: 88px;
  height: 88px;
  margin: 0 auto -58px;
  border-radius: 999px;
  border: 3px solid rgba(34, 197, 94, 0.25);
  animation: congratsPulse 1.1s ease-out infinite;
}

.congrats-check {
  width: 64px;
  height: 64px;
  margin: 0 auto 10px;
  border-radius: 999px;
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: #fff;
  font-size: 1.9rem;
  font-weight: 800;
  display: grid;
  place-items: center;
  box-shadow: 0 14px 28px rgba(21, 128, 61, 0.25);
}

.congrats-content h3 {
  margin: 2px 0 4px;
  color: #0f172a;
  font-size: 1.25rem;
}

.congrats-content p {
  margin: 0;
  color: #475569;
  line-height: 1.5;
}

.step-loading-box {
  width: min(360px, 100%);
  border-radius: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
  text-align: center;
  padding: 16px 14px;
}

.step-loading-title {
  margin: 8px 0 0;
  color: #0f172a;
  font-weight: 700;
}

.step-loading-copy {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.45;
}

.step-form-scroll {
  margin-top: 14px;
  max-height: min(56vh, 520px);
  overflow: auto;
  padding-right: 4px;
}

.loading-wrap {
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #64748b;
}

.spinner {
  width: 18px;
  height: 18px;
  border-radius: 999px;
  border: 2px solid rgba(15, 106, 50, 0.2);
  border-top-color: #0f6a32;
  animation: spin 0.9s linear infinite;
  margin: 0 auto 8px;
}

.step-panel h2 {
  margin: 0;
  color: #0f172a;
  font-size: 1.1rem;
}

.step-copy {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 0.9rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-group-full {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  min-height: 44px;
  border-radius: 10px;
  border: 1px solid #d4dde7;
  background: #f9fbfd;
  padding: 0 12px;
  font-size: 0.9rem;
  color: #0f172a;
}

.form-group textarea {
  min-height: 110px;
  padding: 10px 12px;
  resize: vertical;
}

.hidden-file {
  display: none;
}

.upload-btn {
  width: 100%;
  min-height: 44px;
  border-radius: 10px;
  border: 1px dashed #bcd2c3;
  background: #f7fbf8;
  color: #14532d;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.upload-btn:hover:not(:disabled) {
  border-color: #8fc3a3;
  background: #f0faf3;
}

.upload-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.upload-preview {
  margin-top: 8px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #dde7f0;
  background: #fff;
}

.upload-preview img {
  display: block;
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1f7a3f;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(31, 122, 63, 0.12);
}

.form-group input:disabled {
  color: #64748b;
  background: #f1f5f9;
}

.readonly-input {
  color: #475569;
  background: #f1f5f9;
  cursor: not-allowed;
}

.review-grid {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.review-layout {
  margin-top: 14px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
  gap: 14px;
  align-items: start;
}

.profile-preview-card {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #dbe7f3;
  background: #ffffff;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.07);
}

.profile-preview-banner {
  height: 74px;
  background:
    radial-gradient(circle at 20% 30%, rgba(34, 197, 94, 0.2), transparent 42%),
    radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.2), transparent 45%),
    linear-gradient(135deg, #eaf7f0 0%, #e8f1ff 100%);
  border-bottom: 1px solid #e5eef7;
}

.profile-preview-body {
  padding: 0 12px 12px;
}

.profile-preview-avatar {
  width: 78px;
  height: 78px;
  margin-top: -28px;
  border-radius: 999px;
  border: 4px solid #fff;
  background: #f1f5f9;
  display: grid;
  place-items: center;
  overflow: hidden;
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
}

.profile-preview-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-preview-avatar span {
  font-size: 1.35rem;
  font-weight: 800;
  color: #334155;
}

.profile-preview-main h3 {
  margin: 10px 0 0;
  font-size: 1rem;
  color: #0f172a;
}

.profile-preview-handle {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 0.83rem;
}

.profile-preview-meta {
  margin: 8px 0 0;
  color: #14532d;
  font-size: 0.82rem;
  font-weight: 700;
}

.profile-preview-summary {
  margin: 8px 0 0;
  color: #475569;
  font-size: 0.84rem;
  line-height: 1.45;
}

.profile-preview-tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.profile-preview-tags span {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 4px 8px;
  border-radius: 999px;
  background: #f4f8fc;
  border: 1px solid #e0e8f1;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 600;
}

.review-row {
  display: grid;
  grid-template-columns: 160px 1fr;
  gap: 12px;
  padding: 11px 12px;
  background: #fff;
  border-bottom: 1px solid #eef2f7;
}

.review-row:last-child {
  border-bottom: 0;
}

.review-row span {
  color: #64748b;
  font-size: 0.84rem;
}

.review-row strong {
  color: #0f172a;
  font-size: 0.9rem;
}

.actions {
  margin-top: 16px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.btn {
  min-height: 42px;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 700;
  border: 1px solid transparent;
  cursor: pointer;
}

.btn.ghost {
  background: #fff;
  border-color: #d8e2ee;
  color: #334155;
}

.btn.primary {
  background: linear-gradient(135deg, #0f6a32, #0d5c2d);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes congratsPop {
  0% {
    opacity: 0;
    transform: scale(0.92) translateY(8px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes congratsPulse {
  0% {
    transform: scale(0.92);
    opacity: 0.9;
  }
  100% {
    transform: scale(1.18);
    opacity: 0;
  }
}

.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.2s ease;
}

.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}

@media (max-width: 760px) {
  .stepper {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group-full {
    grid-column: auto;
  }

  .review-layout {
    grid-template-columns: 1fr;
  }

  .review-row {
    grid-template-columns: 1fr;
    gap: 4px;
  }

  .actions {
    flex-direction: column;
  }
}
</style>
