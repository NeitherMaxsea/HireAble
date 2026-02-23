<template>
  <div class="search-page">
    <transition name="loading-fade">
      <div
        v-if="showLoadingOverlay"
        class="loading-overlay"
        role="status"
        aria-live="polite"
        aria-label="Loading search results"
      >
        <div class="loading-card">
          <span class="loading-spinner" aria-hidden="true"></span>
          <p>Loading search results...</p>
        </div>
      </div>
    </transition>

    <header class="navbar" :class="{ 'navbar-scrolled': isScrolled }">
      <div class="nav-inner">
        <button class="nav-left" type="button" @click="$router.push('/landingpage')">
          <img :src="logoDefault" class="logo" alt="HireAble logo" />
        </button>

        <nav class="nav-center">
          <router-link to="/search-jobs" class="nav-link">Find Job</router-link>
          <div class="nav-dropdown" :class="{ open: openDropdown === 'about-menu' }">
            <button
              type="button"
              class="nav-link nav-dropdown-toggle"
              aria-haspopup="menu"
              :aria-expanded="openDropdown === 'about-menu'"
              @click.stop="toggleAboutDropdown"
            >
              About Us
              <svg class="nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"></path>
              </svg>
            </button>

            <transition name="nav-dropdown">
              <div
                v-if="openDropdown === 'about-menu'"
                class="nav-dropdown-menu"
                role="menu"
                aria-label="About Us menu"
              >
                <button type="button" class="nav-dropdown-item" role="menuitem" @click="goToLandingSection('#mission')">
                  Mission
                </button>
                <button type="button" class="nav-dropdown-item" role="menuitem" @click="goToLandingSection('#vision')">
                  Vision
                </button>
              </div>
            </transition>
          </div>
          <button type="button" class="nav-link nav-link-btn" @click="goToLandingSection('#tutorial')">Read First</button>
          <button type="button" class="nav-link nav-link-btn" @click="goToLandingSection('#privacy')">Privacy</button>
          <button type="button" class="nav-link nav-link-btn" @click="goToLandingSection('#contact')">Contact Us</button>
        </nav>

        <div class="nav-right">
          <router-link :to="{ path: '/login', query: { force: '1' } }" class="sign-in-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="8" r="3.25"></circle>
              <path d="M5 19c0-3.1 2.52-5.6 5.63-5.6h.74c3.1 0 5.63 2.5 5.63 5.6"></path>
              <path d="M19 8h3"></path>
            </svg>
            Sign In
          </router-link>
        </div>
      </div>
    </header>

    <main class="results-main">
      <section class="results-shell">
        <button type="button" class="page-back-btn" @click="$router.push('/landingpage')">
          <span aria-hidden="true">&larr;</span>
          Back to Landing Page
        </button>

        <div class="results-head">
          <span class="results-badge">Search Results</span>
          <h1>Job Search Results</h1>
          <p>Showing results based on your filters from the landing page.</p>
        </div>

        <div class="applied-filters">
          <span class="filter-tag">
            Keyword: {{ queryFilters.keyword || "Any" }}
          </span>
          <span class="filter-tag">
            Location: {{ queryFilters.location || "All Barangays in Dasmarinas, Cavite" }}
          </span>
          <span class="filter-tag">
            Category: {{ queryFilters.category || "All Categories" }}
          </span>
        </div>

        <div v-if="!jobsLoading && filteredJobs.length === 0" class="empty-card">
          <h2>No matching jobs found</h2>
          <p>
            Empty result for the selected filters. Try updating your search from the
            landing page.
          </p>
        </div>

        <div v-else-if="!jobsLoading" class="results-grid">
          <article v-for="job in filteredJobs" :key="job.id" class="result-card">
            <div class="result-top">
              <h3>{{ job.title || "Untitled Role" }}</h3>
              <span class="result-type">{{ job.type || "Open" }}</span>
            </div>
            <p class="company">{{ job.companyName || "Company" }}</p>
            <p class="meta">{{ job.location || "Location not specified" }}</p>
            <p class="desc">{{ job.description || "No description provided." }}</p>
            <div class="result-actions">
              <button type="button" class="action-btn view-btn" @click="openJobDetails(job)">
                View
              </button>
              <button type="button" class="action-btn apply-btn" @click="applyJobSoon">
                Apply
              </button>
            </div>
          </article>
        </div>
      </section>
    </main>

    <transition name="detail-modal-fade">
      <div
        v-if="selectedJob"
        class="detail-modal-overlay"
        @click="closeJobDetails"
      >
        <article class="detail-modal" @click.stop>
          <div class="detail-head">
            <div>
              <h2>{{ selectedJob.title || "Untitled Role" }}</h2>
              <p class="detail-company">{{ selectedJob.companyName || "Company" }}</p>
            </div>
            <button type="button" class="detail-close-btn" @click="closeJobDetails" aria-label="Close job details">
              &times;
            </button>
          </div>
          <div class="detail-body">
            <p><strong>Location:</strong> {{ selectedJob.location || "Location not specified" }}</p>
            <p><strong>Category:</strong> {{ selectedJob.category || "Not specified" }}</p>
            <p><strong>Type:</strong> {{ selectedJob.type || "Open" }}</p>
            <p><strong>Description:</strong></p>
            <p>{{ selectedJob.description || "No description provided." }}</p>
          </div>
          <div class="detail-actions">
            <button type="button" class="action-btn view-btn" @click="closeJobDetails">Close</button>
            <button type="button" class="action-btn apply-btn" @click="applyJobSoon">Apply</button>
          </div>
        </article>
      </div>
    </transition>

    <footer id="privacy" class="footer">
      <div class="footer-container">
        <div class="footer-brand">
          <img :src="footerLogo" alt="HireAble Logo" class="footer-logo" />
          <p class="brand-text">
            This site is managed by RCST students as part of the development of a web-based
            job employment assistance platform for Persons with Disabilities in the City of
            Dasmarinas with Decision Support System.
          </p>
        </div>

        <div class="footer-nav">
          <div class="nav-group">
            <h3>About PWD Hireable Proximity</h3>
            <p>
              A powerful job site that aims to connect Persons with Disabilities with
              companies while promoting awareness of the importance of providing equal and
              gainful employment opportunities.
            </p>
          </div>

          <div class="nav-group">
            <h3>Services</h3>
            <ul>
              <li>Job Matching</li>
              <li>DSS Analysis</li>
              <li>Employer Portal</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="bottom-inner">
          <p>&copy; 2026 PWD Employment Assistance Platform. Developed for Research Purposes.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import logoDefault from "@/assets/proximity.png";
import footerLogo from "@/assets/proximity.png";
import { db, collection, onSnapshot } from "@/lib/laravel-data";

export default {
  name: "SearchJobsPage",
  data() {
    return {
      logoDefault,
      footerLogo,
      openDropdown: null,
      isScrolled: false,
      jobsLoading: true,
      showLoadingOverlay: true,
      loadingOverlayMinMs: 560,
      loadingOverlayStartAt: 0,
      loadingOverlayTimer: null,
      allJobs: [],
      jobsUnsubscribe: null,
      selectedJob: null,
    };
  },
  computed: {
    queryFilters() {
      return {
        keyword: String(this.$route.query.keyword || "").trim(),
        location: String(this.$route.query.location || "").trim(),
        category: String(this.$route.query.category || "").trim(),
      };
    },
    filteredJobs() {
      const keyword = this.queryFilters.keyword.toLowerCase();
      const location = this.queryFilters.location.toLowerCase();
      const category = this.queryFilters.category.toLowerCase();

      return this.allJobs.filter((job) => {
        const title = String(job.title || "").toLowerCase();
        const desc = String(job.description || "").toLowerCase();
        const company = String(job.companyName || "").toLowerCase();
        const jobLocation = String(job.location || "").toLowerCase();
        const jobCategory = String(job.category || job.department || job.type || "").toLowerCase();

        const keywordMatch = !keyword || title.includes(keyword) || desc.includes(keyword) || company.includes(keyword);
        const locationMatch = !location || jobLocation.includes(location);
        const categoryMatch = !category || jobCategory.includes(category);

        return keywordMatch && locationMatch && categoryMatch;
      });
    },
  },
  mounted() {
    this.startJobsFeed();
    window.addEventListener("scroll", this.onScroll, { passive: true });
    window.addEventListener("keydown", this.onKeydown);
    document.addEventListener("click", this.closeDropdown);
    this.onScroll();
  },
  beforeUnmount() {
    this.stopJobsFeed();
    this.clearLoadingOverlayTimer();
    window.removeEventListener("scroll", this.onScroll);
    window.removeEventListener("keydown", this.onKeydown);
    document.removeEventListener("click", this.closeDropdown);
  },
  methods: {
    toggleAboutDropdown() {
      this.openDropdown =
        this.openDropdown === "about-menu" ? null : "about-menu";
    },
    goToLandingSection(hash) {
      this.openDropdown = null;
      this.$router.push({ path: "/landingpage", hash });
    },
    onScroll() {
      this.isScrolled = window.scrollY > 10;
    },
    closeDropdown(e) {
      if (!e.target.closest(".nav-dropdown")) {
        this.openDropdown = null;
      }
    },
    onKeydown(e) {
      if (e.key === "Escape" && this.selectedJob) {
        this.closeJobDetails();
      }
    },
    openJobDetails(job) {
      this.selectedJob = job;
    },
    closeJobDetails() {
      this.selectedJob = null;
    },
    applyJobSoon() {
      window.alert("Apply feature is coming soon.");
    },
    startJobsFeed() {
      this.jobsLoading = true;
      this.showLoadingOverlay = true;
      this.loadingOverlayStartAt = Date.now();
      this.clearLoadingOverlayTimer();
      this.stopJobsFeed();

      this.jobsUnsubscribe = onSnapshot(
        collection(db, "jobs"),
        (snapshot) => {
          this.allJobs = snapshot.docs
            .map((docRef) => {
              const raw = docRef.data ? docRef.data() : {};
              return {
                id: docRef.id,
                ...raw,
                title: String(raw.title || raw.position || raw.jobTitle || "").trim(),
                companyName: String(
                  raw.companyName || raw.company_name || raw.company || raw.department || ""
                ).trim(),
                location: String(raw.location || raw.address || "").trim(),
                type: String(raw.type || raw.employmentType || raw.jobType || "").trim(),
                category: String(raw.category || raw.industry || raw.department || "").trim(),
                description: String(raw.description || "").trim(),
                status: String(raw.status || "open").toLowerCase(),
              };
            })
            .filter((job) => job.status === "open");

          if (this.jobsLoading) {
            this.jobsLoading = false;
            this.hideLoadingOverlay();
          }
        },
        () => {
          this.allJobs = [];
          if (this.jobsLoading) {
            this.jobsLoading = false;
            this.hideLoadingOverlay();
          }
        }
      );
    },
    hideLoadingOverlay() {
      const elapsed = Date.now() - this.loadingOverlayStartAt;
      const waitMs = Math.max(0, this.loadingOverlayMinMs - elapsed);
      this.clearLoadingOverlayTimer();
      this.loadingOverlayTimer = window.setTimeout(() => {
        this.showLoadingOverlay = false;
        this.loadingOverlayTimer = null;
      }, waitMs);
    },
    clearLoadingOverlayTimer() {
      if (this.loadingOverlayTimer) {
        window.clearTimeout(this.loadingOverlayTimer);
        this.loadingOverlayTimer = null;
      }
    },
    stopJobsFeed() {
      if (typeof this.jobsUnsubscribe === "function") {
        this.jobsUnsubscribe();
        this.jobsUnsubscribe = null;
      }
    },
  },
};
</script>

<style scoped>
.search-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  color: #0f172a;
}

.loading-fade-enter-active,
.loading-fade-leave-active {
  transition: opacity 0.32s ease;
}

.loading-fade-enter-from,
.loading-fade-leave-to {
  opacity: 0;
}

.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 2400;
  display: grid;
  place-items: center;
  background: rgba(7, 28, 16, 0.72);
  backdrop-filter: blur(4px);
}

.loading-card {
  min-width: min(90vw, 320px);
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: linear-gradient(180deg, rgba(6, 24, 14, 0.94) 0%, rgba(8, 34, 18, 0.94) 100%);
  padding: 18px 22px;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 16px 34px rgba(0, 0, 0, 0.32);
}

.loading-card p {
  margin: 0;
  color: #e9f8ec;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.26);
  border-top-color: #f4c41f;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.navbar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 86px;
  background: rgba(255, 255, 255, 0.95);
  border-bottom: 1px solid #d6d7db;
  box-shadow: 0 0 0 rgba(15, 23, 42, 0);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 1000;
  padding: 0 20px;
  display: flex;
  align-items: center;
  transition:
    background-color 0.28s ease,
    border-color 0.28s ease,
    box-shadow 0.28s ease,
    height 0.28s ease;
}

.navbar.navbar-scrolled {
  height: 78px;
  background: rgba(10, 59, 30, 0.95);
  border-bottom-color: rgba(244, 196, 31, 0.35);
  box-shadow: 0 14px 28px rgba(2, 16, 11, 0.3);
}

.nav-inner {
  width: 100%;
  max-width: 1220px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.nav-left {
  padding: 0;
  border: 0;
  background: transparent;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
}

.logo {
  display: block;
  height: 72px;
  width: auto;
  transition: height 0.28s ease, filter 0.28s ease;
}

.navbar.navbar-scrolled .logo {
  height: 62px;
  filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.32));
}

.nav-center {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 28px;
}

.nav-dropdown {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.nav-dropdown-toggle {
  border: 0;
  background: transparent;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.nav-caret {
  width: 12px;
  height: 12px;
  transition: transform 0.2s ease;
}

.nav-dropdown.open .nav-caret {
  transform: rotate(180deg);
}

.nav-dropdown-menu {
  position: absolute;
  top: calc(100% + 12px);
  left: 50%;
  transform: translateX(-50%);
  min-width: 156px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  box-shadow: 0 14px 26px rgba(2, 16, 11, 0.15);
  padding: 8px;
  display: grid;
  gap: 6px;
  z-index: 12;
}

.nav-dropdown-item {
  border: 0;
  background: transparent;
  border-radius: 8px;
  padding: 9px 10px;
  text-align: left;
  color: #111827;
  font-size: 0.75rem;
  font-weight: 600;
}

.nav-dropdown-item:hover {
  background: #f3f4f6;
  color: #0f5132;
}

.nav-dropdown-enter-active,
.nav-dropdown-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.nav-dropdown-enter-from,
.nav-dropdown-leave-to {
  opacity: 0;
  transform: translate(-50%, -4px);
}

.nav-link {
  color: #111827;
  text-decoration: none;
  font-size: 0.73rem;
  font-weight: 600;
  letter-spacing: 0.12px;
  transition: color 0.24s ease, opacity 0.24s ease;
}

.nav-link:hover {
  color: #0f5132;
}

.nav-link-btn {
  border: 0;
  background: transparent;
  padding: 0;
}

.navbar.navbar-scrolled .nav-link {
  color: #f8fafc;
}

.navbar.navbar-scrolled .nav-link:hover {
  color: #facc15;
}

.navbar.navbar-scrolled .nav-dropdown-menu {
  background: #123a23;
  border-color: rgba(244, 196, 31, 0.35);
  box-shadow: 0 16px 30px rgba(2, 16, 11, 0.32);
}

.navbar.navbar-scrolled .nav-dropdown-item {
  color: #f8fafc;
}

.navbar.navbar-scrolled .nav-dropdown-item:hover {
  background: rgba(248, 250, 252, 0.14);
  color: #facc15;
}

.sign-in-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #111827;
  border-radius: 10px;
  padding: 8px 14px;
  background: #ffffff;
  color: #111827;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 600;
  transition:
    background-color 0.24s ease,
    border-color 0.24s ease,
    color 0.24s ease,
    transform 0.2s ease;
}

.sign-in-btn:hover {
  background: #f3f4f6;
  transform: translateY(-1px);
}

.sign-in-btn svg {
  width: 14px;
  height: 14px;
}

.navbar.navbar-scrolled .sign-in-btn {
  border-color: rgba(248, 250, 252, 0.82);
  background: transparent;
  color: #f8fafc;
}

.navbar.navbar-scrolled .sign-in-btn:hover {
  background: rgba(248, 250, 252, 0.16);
}

.results-main {
  padding: 132px 5% 80px;
  flex: 1 0 auto;
}

.results-shell {
  max-width: 1180px;
  margin: 0 auto;
}

.page-back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 14px;
  background: #ffffff;
  color: #0f172a;
  font-size: 0.8rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 14px;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.page-back-btn:hover {
  transform: translateY(-1px);
  border-color: #94a3b8;
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
}

.page-back-btn:focus-visible {
  outline: 3px solid rgba(29, 78, 216, 0.2);
  outline-offset: 2px;
}

.results-head {
  margin-bottom: 18px;
}

.results-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: #e8f0ff;
  color: #1d4ed8;
  font-size: 0.72rem;
  font-weight: 700;
}

.results-head h1 {
  margin: 14px 0 6px;
  font-size: clamp(1.5rem, 2.2vw, 2.2rem);
}

.results-head p {
  margin: 0;
  color: #475569;
}

.applied-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.filter-tag {
  border: 1px solid #d1d5db;
  border-radius: 999px;
  padding: 6px 12px;
  background: #ffffff;
  color: #374151;
  font-size: 0.74rem;
  font-weight: 600;
}

.empty-card {
  border: 1px solid #d1d5db;
  border-radius: 12px;
  background: #ffffff;
  padding: 28px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.empty-card h2 {
  margin: 0;
  font-size: 1.2rem;
}

.empty-card p {
  margin: 12px 0 0;
  color: #475569;
}

.results-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.result-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #ffffff;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.result-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.result-top h3 {
  margin: 0;
  font-size: 1rem;
  line-height: 1.35;
}

.result-type {
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #dbeafe;
  color: #1e3a8a;
}

.company {
  margin: 0;
  font-weight: 600;
}

.meta {
  margin: 0;
  color: #475569;
  font-size: 0.9rem;
}

.desc {
  margin: 0;
  color: #64748b;
  line-height: 1.5;
}

.result-actions {
  margin-top: 4px;
  display: flex;
  gap: 8px;
}

.action-btn {
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 8px 12px;
  background: #ffffff;
  color: #0f172a;
  font-size: 0.76rem;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.action-btn:hover {
  transform: translateY(-1px);
}

.view-btn {
  border-color: #86efac;
  background: #f0fdf4;
  color: #166534;
}

.view-btn:hover {
  background: #dcfce7;
}

.apply-btn {
  border-color: #fde68a;
  background: #fefce8;
  color: #92400e;
}

.apply-btn:hover {
  background: #fef9c3;
}

.detail-modal-fade-enter-active,
.detail-modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.detail-modal-fade-enter-from,
.detail-modal-fade-leave-to {
  opacity: 0;
}

.detail-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2200;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: grid;
  place-items: center;
  padding: 16px;
}

.detail-modal {
  width: min(94vw, 700px);
  border-radius: 14px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.3);
  padding: 18px;
}

.detail-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}

.detail-head h2 {
  margin: 0;
  font-size: 1.18rem;
}

.detail-company {
  margin: 6px 0 0;
  color: #475569;
  font-weight: 600;
}

.detail-close-btn {
  border: 0;
  background: transparent;
  color: #475569;
  font-size: 1.7rem;
  line-height: 1;
  padding: 0;
  cursor: pointer;
}

.detail-body p {
  margin: 0 0 10px;
  color: #334155;
  line-height: 1.55;
}

.detail-actions {
  display: flex;
  gap: 8px;
  margin-top: 6px;
}

.footer {
  background: linear-gradient(180deg, #0b4c23 0%, #093c1d 58%, #072f18 100%);
  color: #dce8da;
  padding: 34px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-top: auto;
  flex-shrink: 0;
}

.footer-container {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 5%;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: minmax(260px, 1fr) minmax(340px, 500px);
  gap: 56px;
  align-items: start;
}

.footer-brand {
  max-width: 470px;
}

.footer-logo {
  width: auto;
  height: clamp(78px, 8vw, 102px);
  max-width: 100%;
  display: block;
  margin-bottom: 12px;
}

.brand-text {
  margin: 0;
  max-width: 430px;
  color: rgba(227, 237, 224, 0.95);
  font-size: 0.8rem;
  line-height: 1.58;
}

.footer-nav {
  display: grid;
  grid-template-columns: minmax(220px, 1fr) minmax(145px, 180px);
  gap: 34px;
}

.footer .nav-group h3 {
  margin: 0 0 10px;
  color: #f4c41f;
  font-size: 0.86rem;
  font-weight: 700;
}

.footer .nav-group p {
  margin: 0;
  color: rgba(227, 237, 224, 0.95);
  font-size: 0.8rem;
  line-height: 1.52;
}

.footer .nav-group ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 8px;
}

.footer .nav-group li {
  color: #f4c41f;
  font-size: 0.8rem;
  font-weight: 600;
}

.footer-bottom {
  margin-top: 16px;
  width: 100%;
  background: #e0af08;
  border-top: 1px solid #cb9900;
}

.bottom-inner {
  max-width: 1180px;
  margin: 0 auto;
  padding: 5px 5%;
  min-height: 26px;
  display: flex;
  align-items: center;
  box-sizing: border-box;
}

.bottom-inner p {
  margin: 0;
  color: #0a3417;
  font-size: 0.62rem;
  font-weight: 700;
}

@media (max-width: 980px) {
  .navbar {
    height: 78px;
    padding: 0 14px;
  }

  .logo {
    height: 56px;
  }

  .nav-center {
    display: none;
  }

  .results-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .footer-container {
    grid-template-columns: 1fr;
    gap: 26px;
  }

  .footer-nav {
    grid-template-columns: minmax(0, 1fr) minmax(140px, 180px);
    gap: 24px;
  }
}

@media (max-width: 640px) {
  .results-main {
    padding: 108px 5% 60px;
  }

  .results-grid {
    grid-template-columns: 1fr;
  }

  .footer-nav {
    grid-template-columns: 1fr;
    gap: 18px;
  }
}
</style>
