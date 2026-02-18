<template>
  <div class="page" :class="{ 'page-ready': !showPageLoader }">
    <transition name="page-loader-fade">
      <div
        v-if="showPageLoader"
        class="page-loader-overlay"
        role="status"
        aria-live="polite"
        aria-label="Loading landing page"
      >
        <div class="dot-spinner" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </transition>

  <!-- 
<div v-if="maintenanceMode" class="maintenance-overlay">
  <div class="maintenance-box">
    <div class="warning-top">
      <span class="warning-icon">⚠</span>
    </div>

    <h1>MAINTENANCE MODE</h1>

    <p>
      This system is currently under maintenance.<br />
      Do not access at this time.
    </p>

    <span class="maintenance-sub">
      Please leave the page and come back later.
    </span>

  </div>
</div>
 -->


    <!-- TOP NAVBAR -->

<header class="navbar" :class="{ 'navbar-scrolled': isScrolled }">
  <div class="nav-inner">
    <button class="nav-left" type="button" @click="scrollTop">
      <img :src="isScrolled ? logoScrolled : logoDefault" class="logo" alt="HireAble logo" />
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
            <button type="button" class="nav-dropdown-item" role="menuitem" @click="scrollTo('mission')">
              Mission
            </button>
            <button type="button" class="nav-dropdown-item" role="menuitem" @click="scrollTo('vision')">
              Vision
            </button>
          </div>
        </transition>
      </div>
      <a href="#tutorial" class="nav-link" @click.prevent="scrollTo('tutorial')">Read First</a>
      <a href="#privacy" class="nav-link" @click.prevent="scrollTo('privacy')">Privacy</a>
      <a href="#contact" class="nav-link" @click.prevent="scrollTo('contact')">Contact Us</a>
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

    <nav class="section-nav" aria-label="Page sections">
      <button
        v-for="item in sectionNavItems"
        :key="item.id"
        type="button"
        class="section-nav-btn"
        :class="{ active: activeSection === item.id }"
        @click="scrollTo(item.id)"
      >
        <span class="section-nav-dot" aria-hidden="true"></span>
        <span class="section-nav-label">{{ item.label }}</span>
      </button>
    </nav>

    <!-- HERO -->
    <section id="home" class="hero" :style="heroParallaxStyle">
      <div class="hero-shell">
        <div class="hero-content">
          <h1>
            Employment Assistance Platform for Persons with Disabilities in The
            City of Dasmarinas with Decision Support System
          </h1>
          <p class="hero-desc">
            Helping Persons with Disabilities discover opportunities that match
            their skills and potential.
          </p>

          <form
            class="hero-search"
            role="search"
            aria-label="Hero job search filters"
            @submit.prevent="submitHeroSearch"
          >
            <label class="search-field search-field-text">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.4-3.4"></path>
              </svg>
              <input
                v-model.trim="heroFilters.keyword"
                type="text"
                placeholder="Job Title Keywords"
                aria-label="Job title keywords"
              />
            </label>

            <label class="search-field search-field-select">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 21s6-5.3 6-10a6 6 0 1 0-12 0c0 4.7 6 10 6 10z"></path>
                <circle cx="12" cy="11" r="2.4"></circle>
              </svg>
              <select v-model="heroFilters.location" aria-label="Barangay location">
                <option value="">All Barangays in Dasmarinas, Cavite</option>
                <option
                  v-for="barangay in dasmaBarangays"
                  :key="barangay"
                  :value="barangay"
                >
                  {{ barangay }}
                </option>
              </select>
              <svg class="search-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"></path>
              </svg>
            </label>

            <label class="search-field search-field-select">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
              </svg>
              <select v-model="heroFilters.category" aria-label="Job category">
                <option value="">All Job Categories</option>
                <option
                  v-for="category in heroCategories"
                  :key="category"
                  :value="category"
                >
                  {{ category }}
                </option>
              </select>
              <svg class="search-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"></path>
              </svg>
            </label>
            <button type="submit" class="search-submit">Search</button>
          </form>
        </div>

        <div class="hero-logo-wrap">
          <img :src="heroSeal" alt="HireAble Proximity Development logo" class="hero-seal" />
        </div>
      </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="section">
      <div class="about-panel">
        <div class="about-image-wrap">
          <img
            :src="aboutImages[0]"
            alt="About platform image"
            class="about-single-image"
          />
        </div>

        <div class="about-copy"> 
          <h3>About Us</h3>
          <p>
            The Employment Assistance Platform for Persons with Disabilities is
            designed to connect qualified PWD job seekers with inclusive
            employers in the City of Dasmarinas.
          </p>
          <p>
            With built-in decision support, the system helps make hiring more
            accessible, fair, and data-guided for both applicants and
            organizations.
          </p>
          <p>
            Employers can post opportunities with clear accessibility details,
            while applicants receive role recommendations aligned with their
            capabilities, preferences, and qualifications.
          </p>
          <p>
            This approach supports inclusive growth by improving visibility of
            PWD talent, reducing hiring barriers, and creating a more connected
            local employment ecosystem.
          </p>

          <div id="mission" class="about-focus-card">
            <h4>Mission</h4>
            <p>
              To empower Persons with Disabilities by connecting them to inclusive
              employment opportunities through an accessible and data-guided platform.
            </p>
          </div>

          <div id="vision" class="about-focus-card">
            <h4>Vision</h4>
            <p>
              A community where every qualified PWD can access fair, meaningful,
              and sustainable work opportunities without barriers.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- TUTORIAL -->
    <section id="tutorial" ref="tutorialSection" class="tutorial-section">
      <div class="tutorial-card">
        <div class="video-pane">
          <div class="video-placeholder">
            <div class="tutorial-video tutorial-video-blank" aria-label="Blank tutorial area"></div>
          </div>
        </div>

        <div class="tutorial-pane">
          <div class="tutorial-header">
            <span class="faq-badge">Quick Guide</span>
            <h2>Tutorial Section</h2>
            <p>Step-by-step guides for applicants and employers.</p>
          </div>

          <div
            v-for="(item, index) in tutorials"
            :key="item.q"
            class="tutorial-step"
          >
            <span class="step-dot">{{ index + 1 }}</span>
            <div>
              <h3>{{ item.q }}</h3>
              <p>{{ item.a }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <!-- FAQ -->
    <section id="contact" class="faq-section">
      <div class="faq-shell">
        <div class="faq-header">
          <span class="faq-badge">Frequently Asked Questions</span>
          <h2>Common Questions & Answers</h2>
          <p>Everything you need to know about our platform</p>
        </div>

        <div class="faq-list">
          <div
            v-for="(faq, index) in faqs"
            :key="index"
            class="faq-item"
          >
            <button type="button" class="faq-question" @click="toggleFaq(index)">
              <span>{{ faq.q }}</span>
              <svg class="arrow" :class="{ open: activeFaq === index }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                <path d="m6 9 6 7 6-7"></path>
              </svg>
            </button>

            <transition name="faq-slide">
              <div v-show="activeFaq === index" class="faq-answer">
                {{ faq.a }}
              </div>
            </transition>
          </div>
        </div>
      </div>
    </section>

    <footer id="privacy" class="footer">
      <div class="footer-container">
        <div class="footer-brand">
          <img :src="footerLogo" alt="HireAble Logo" class="footer-logo" />
          <p class="brand-text">
            This site is managed by RCST students as part of the development of a web-based job employment assistance
            platform for Persons with Disabilities in the City of Dasmarinas with Decision Support System.
          </p>
          <div class="social-wrapper">
            <a href="#" class="social-link" aria-label="Facebook">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
            </a>
            <a href="#" class="social-link" aria-label="Instagram">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </a>
            <a href="#" class="social-link" aria-label="LinkedIn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            </a>
          </div>
        </div>

        <div class="footer-nav">
          <div class="nav-group about-group">
            <h3>About PWD Hireable Proximity</h3>
            <p>
              A powerful job site that aims to connect Persons with Disabilities with companies while promoting awareness
              of the importance of providing equal and gainful employment opportunities.
            </p>
          </div>

          <div class="nav-group services-group">
            <h3>Services</h3>
            <ul>
              <li><a href="#">Job Matching</a></li>
              <li><a href="#">DSS Analysis</a></li>
              <li><a href="#">Employer Portal</a></li>
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
import proximityLogo from "@/assets/proximity.png";
import heroSeal from "@/assets/logoproximity.png";
import aboutPhoto from "@/assets/PWD_worker.png";

export default {
  name: "LandingPage",

  data() {
    return {
      logoDefault: proximityLogo,
      logoScrolled: proximityLogo,
      heroSeal,
      footerLogo: proximityLogo,
      showPageLoader: true,
      pageLoaderTimer: null,
        maintenanceMode: true, // 🔥 toggle mo lang to false pag live na
      tutorialObserver: null,
      isTutorialInView: false,
      openDropdown: null,
      lastY: 0,
      hideNav: false,
      isScrolled: false,
      heroParallaxY: 0,
      activeFaq: null,
      activeSection: "home",
      sectionNavItems: [
        { id: "home", label: "Home" },
        { id: "about", label: "About" },
        { id: "mission", label: "Mission" },
        { id: "vision", label: "Vision" },
        { id: "tutorial", label: "Tutorial" },
        { id: "contact", label: "FAQs" },
        { id: "privacy", label: "Privacy" }
      ],
      heroFilters: {
        keyword: "",
        location: "",
        category: ""
      },
      dasmaBarangays: [
        "Burol I",
        "Burol II",
        "Burol III",
        "Datu Esmael (Bago-a-ingud)",
        "Emmanuel Bergado I",
        "Emmanuel Bergado II",
        "Fatima I",
        "Fatima II",
        "Fatima III",
        "H-2",
        "Langkaan I",
        "Langkaan II",
        "Luzviminda I",
        "Luzviminda II",
        "Paliparan I",
        "Paliparan II",
        "Paliparan III",
        "Sabang",
        "Salawag",
        "Salitran I",
        "Salitran II",
        "Salitran III",
        "Salitran IV",
        "Sampaloc I",
        "Sampaloc II",
        "Sampaloc III",
        "Sampaloc IV",
        "Sampaloc V",
        "San Agustin I",
        "San Agustin II",
        "San Agustin III",
        "San Andres I",
        "San Andres II",
        "San Antonio de Padua I",
        "San Antonio de Padua II",
        "San Dionisio (Barangay I)",
        "San Esteban (Barangay IV)",
        "San Francisco I",
        "San Francisco II",
        "San Isidro Labrador I",
        "San Isidro Labrador II",
        "San Jose",
        "San Juan (San Juan I)",
        "San Lorenzo Ruiz I",
        "San Lorenzo Ruiz II",
        "San Luis I",
        "San Luis II",
        "San Manuel I",
        "San Manuel II",
        "San Mateo",
        "San Miguel",
        "San Nicolas I",
        "San Nicolas II",
        "San Roque (Sta. Cristina II)",
        "San Simon (Barangay VII)",
        "Santa Cristina I",
        "Santa Cruz I",
        "Santa Cruz II",
        "Santa Fe",
        "Santa Lucia (San Juan II)",
        "Santa Maria (Barangay XX)",
        "Santo Cristo (Barangay II-A)",
        "Santo Nino I",
        "Santo Nino II",
        "Victoria Reyes",
        "Zone I (Poblacion)",
        "Zone I-A (Poblacion)",
        "Zone II (Poblacion)",
        "Zone III (Poblacion)",
        "Zone IV (Poblacion)",
        "Zone IV-A (Poblacion)",
        "Zone V (Poblacion)",
        "Zone VI (Poblacion)",
        "Zone VII (Poblacion)",
        "Zone VIII (Poblacion)",
        "Zone IX (Poblacion)",
        "Zone X (Poblacion)",
        "Zone XI (Poblacion)",
        "Zone XII (Poblacion)"
      ],
      heroCategories: [
        "Administrative and Office Support",
        "Construction and Skilled Trades",
        "Customer Service and BPO",
        "Education and Training",
        "Food and Hospitality",
        "Government and Public Service",
        "Healthcare and Caregiving",
        "Information Technology",
        "Logistics and Transportation",
        "Manufacturing and Production",
        "Retail and Sales",
        "Security and Safety"
      ],
      aboutImages: [
        aboutPhoto
      ],


      navItems: [
        { id: "home", label: "Home" },
        {
          id: "about",
          label: "About",
          children: [
            { id: "about", label: "Overview" },
            { id: "tutorial", label: "Tutorial" }
          ]
        },
        { id: "contact", label: "Contact" }
      ],

      tutorials: [
        {
          q: "How do I register as a PWD job seeker?",
          a: "Open the Register page, choose the PWD applicant option, complete your personal details, upload required information, then submit your account for verification."
        },
        {
          q: "How do I register as an employer?",
          a: "Select employer registration, provide company information, add your contact details, then wait for admin approval before posting job openings."
        },
        {
          q: "How do I log in and update my profile?",
          a: "Use your registered email and password on the Login page. After logging in, open your profile settings to update contact info, skills, and work preferences."
        },
        {
          q: "How do I apply for a job listing?",
          a: "Go to Find Jobs, review job requirements and accessibility details, then click Apply. Keep your resume and profile updated to improve your match results."
        },
        {
          q: "How do employers review and shortlist applicants?",
          a: "Employers can open posted jobs, check candidate profiles, and use decision support recommendations to shortlist applicants based on role fit."
        }
      ],

      faqs: [
  {
    q: "What is this system all about?",
    a: "This system is a web-based job employment assistance platform developed to help Persons with Disabilities (PWDs) find suitable and inclusive employment opportunities in the City of Dasmarinas using a Decision Support System."
  },
  {
    q: "Who are the intended users of the system?",
    a: "The system is intended for Persons with Disabilities (PWDs), employers, and administrators who manage job postings and applicant matching."
  },
  {
    q: "What is the purpose of the Decision Support System?",
    a: "The Decision Support System helps analyze applicants’ skills, qualifications, and preferences to recommend the most suitable job opportunities."
  },
  {
    q: "How does the system help PWD job seekers?",
    a: "The system allows PWD job seekers to create profiles, upload resumes, and receive job recommendations based on their abilities and skills."
  },
  {
    q: "How does the system help employers?",
    a: "Employers can post job vacancies and view recommended PWD applicants who best match their job requirements."
  },
  {
    q: "Is the system free to use?",
    a: "Yes. The system is free to use for PWD job seekers and registered employers."
  }
]

    };
  },
mounted() {
  this.pageLoaderTimer = window.setTimeout(() => {
    this.showPageLoader = false;
    this.pageLoaderTimer = null;
  }, 850);
  window.addEventListener("scroll", this.onScroll, { passive: true });
  document.addEventListener("click", this.closeDropdown);
  this.onScroll();
  this.setupTutorialObserver();
},
beforeUnmount() {
  if (this.pageLoaderTimer) {
    window.clearTimeout(this.pageLoaderTimer);
    this.pageLoaderTimer = null;
  }
  window.removeEventListener("scroll", this.onScroll);
  document.removeEventListener("click", this.closeDropdown);
  this.teardownTutorialObserver();
},

computed: {
  heroParallaxStyle() {
    return {
      backgroundPosition: `center calc(50% + ${this.heroParallaxY}px)`
    };
  },
  tutorialVideoSrc() {
    const base = "https://www.youtube.com/embed/J1Ip2sC_lss";
    return this.isTutorialInView
      ? `${base}?autoplay=1&mute=1&playsinline=1`
      : `${base}?autoplay=0&mute=1&playsinline=1`;
  }
},

methods: {
setupTutorialObserver() {
  const section = this.$refs.tutorialSection;
  if (!section || typeof IntersectionObserver === "undefined") return;

  this.tutorialObserver = new IntersectionObserver(
    (entries) => {
      const [entry] = entries;
      this.isTutorialInView = Boolean(entry?.isIntersecting);
    },
    { threshold: 0.6 }
  );

  this.tutorialObserver.observe(section);
},

teardownTutorialObserver() {
  if (this.tutorialObserver) {
    this.tutorialObserver.disconnect();
    this.tutorialObserver = null;
  }
},

toggleFaq(i) {
  if (this.activeFaq === i) {
    // Start the closing animation transition first
    // We wait 200ms before setting to null so Vue doesn't 
    // unmount the content before the slide-up finishes.
    setTimeout(() => {
      if (this.activeFaq === i) {
        this.activeFaq = null;
      }
    }, 200); 
  } else {
    this.activeFaq = i;
  }
},

  toggleAboutDropdown() {
    this.openDropdown =
      this.openDropdown === "about-menu" ? null : "about-menu";
  },


  onNavClick(item) {
    if (item.children) {
      this.openDropdown =
        this.openDropdown === item.id ? null : item.id;
    } else {
      this.handleNavTarget(item.id);
    }
  },

  handleNavTarget(id) {
    if (id === "find-jobs") {
      this.openDropdown = null;
      this.$router.push("/search-jobs");
      return;
    }

    if (id === "post-job") {
      this.openDropdown = null;
      this.$router.push({ path: "/role", query: { force: "1" } });
      return;
    }

    this.scrollTo(id);
  },

  scrollTo(id) {
    this.openDropdown = null;
    this.activeSection = id;
    document.getElementById(id)?.scrollIntoView({
      behavior: "smooth"
    });
  },

  updateActiveSection() {
    const offsetY = 140;
    const probeY = window.scrollY + offsetY;
    const items = Array.isArray(this.sectionNavItems) ? this.sectionNavItems : [];
    if (!items.length) return;

    let current = items[0].id;
    for (const item of items) {
      const el = document.getElementById(item.id);
      if (!el) continue;
      const top = el.getBoundingClientRect().top + window.scrollY;
      if (top <= probeY) current = item.id;
    }
    this.activeSection = current;
  },

  scrollTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  },

  submitHeroSearch() {
    const query = {};
    if (this.heroFilters.keyword) query.keyword = this.heroFilters.keyword;
    if (this.heroFilters.location) query.location = this.heroFilters.location;
    if (this.heroFilters.category) query.category = this.heroFilters.category;

    this.$router.push({ name: "SearchJobs", query });
  },

  onScroll() {
    const y = window.scrollY;
    this.hideNav = y > this.lastY && y > 120;
    this.isScrolled = y > 10;
    this.heroParallaxY = Math.min(y * 0.22, 180);
    this.lastY = y;
    this.updateActiveSection();
  },

  closeDropdown(e) {
    if (!e.target.closest(".nav-dropdown")) {
      this.openDropdown = null;
    }
  }
}

};
</script>

<style scoped>
/* GLOBAL */
:global(html),
:global(body),
:global(#app){
  height:auto;
  overflow-y:auto;
  overflow-x:hidden;
  font-family: "Poppins", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

.page{
  background:#f6f7fb;
  color:#0f172a;
  font-family: "Poppins", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath d='M3 2l7 17 2-6 6-2z' fill='%23000000' stroke='%23ffffff' stroke-width='1'/%3E%3C/svg%3E") 3 2, auto;
}

.page button,
.page a,
.page [role="button"] {
  cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath d='M3 2l7 17 2-6 6-2z' fill='%23000000' stroke='%23ffffff' stroke-width='1'/%3E%3C/svg%3E") 3 2, pointer;
}

h1, h2, h3, h4, h5, h6{
  font-family: "Poppins", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  letter-spacing: 0.2px;
}

.page-loader-fade-enter-active,
.page-loader-fade-leave-active {
  transition: opacity 0.35s ease;
}

.page-loader-fade-enter-from,
.page-loader-fade-leave-to {
  opacity: 0;
}

.page-loader-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: grid;
  place-items: center;
  background: #dedfe1;
}

.dot-spinner {
  position: relative;
  width: 56px;
  height: 56px;
  animation: dot-ring-spin 1.08s linear infinite;
}

.dot-spinner span {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 11px;
  height: 11px;
  margin: -5.5px 0 0 -5.5px;
  border-radius: 999px;
  background: #2cb9b0;
  opacity: 0.38;
  animation: dot-fade 0.95s ease-in-out infinite;
}

.dot-spinner span:nth-child(1) {
  transform: rotate(0deg) translateY(-20px);
  animation-delay: 0s;
}

.dot-spinner span:nth-child(2) {
  transform: rotate(45deg) translateY(-20px);
  animation-delay: 0.08s;
}

.dot-spinner span:nth-child(3) {
  transform: rotate(90deg) translateY(-20px);
  animation-delay: 0.16s;
}

.dot-spinner span:nth-child(4) {
  transform: rotate(135deg) translateY(-20px);
  animation-delay: 0.24s;
}

.dot-spinner span:nth-child(5) {
  transform: rotate(180deg) translateY(-20px);
  animation-delay: 0.32s;
}

.dot-spinner span:nth-child(6) {
  transform: rotate(225deg) translateY(-20px);
  animation-delay: 0.4s;
}

.dot-spinner span:nth-child(7) {
  transform: rotate(270deg) translateY(-20px);
  animation-delay: 0.48s;
}

.dot-spinner span:nth-child(8) {
  transform: rotate(315deg) translateY(-20px);
  animation-delay: 0.56s;
}

@keyframes dot-ring-spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes dot-fade {
  0%,
  100% {
    opacity: 0.35;
  }
  50% {
    opacity: 1;
  }
}
/* NAVBAR */
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

.nav-right {
  display: flex;
  justify-content: flex-end;
}

.section-nav {
  position: fixed;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 950;
  display: grid;
  gap: 10px;
  align-items: center;
}

.section-nav-btn {
  border: 0;
  background: transparent;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  opacity: 0.78;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.section-nav-btn:hover {
  opacity: 1;
}

.section-nav-btn:focus-visible {
  outline: 3px solid rgba(34, 197, 94, 0.24);
  outline-offset: 4px;
  border-radius: 999px;
}

.section-nav-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.28);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.14);
  transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
}

.section-nav-label {
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(255, 255, 255, 0.96);
  color: #0f172a;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1px;
  box-shadow: 0 12px 24px rgba(2, 6, 23, 0.14);
  backdrop-filter: blur(8px);
  transform: translateX(6px);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.section-nav-btn:hover .section-nav-label,
.section-nav-btn.active .section-nav-label {
  opacity: 1;
  transform: translateX(0);
}

.section-nav-btn.active .section-nav-dot {
  background: #16a34a;
  box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.18), 0 12px 24px rgba(15, 79, 37, 0.2);
  transform: scale(1.18);
}

.section-nav-btn.active .section-nav-label {
  color: #0f4f25;
  border-color: rgba(22, 163, 74, 0.28);
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

.btn {
  padding: 12px 28px;
  border-radius: 999px;
  font-size: 0.9rem;
  min-width: 170px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.3s ease;
  text-align: center;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* HERO */
.hero {
  min-height: 520px;
  padding: 130px 6% 72px;
  background:
    linear-gradient(180deg, rgba(10, 27, 20, 0.78) 0%, rgba(10, 27, 20, 0.58) 45%, rgba(10, 27, 20, 0.88) 100%),
    url("@/assets/bg.jpg");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  will-change: background-position;
  position: relative;
  overflow: hidden;
  isolation: isolate;
}

.hero::after {
  content: "";
  position: absolute;
  inset: -20% -10%;
  background:
    radial-gradient(circle at 30% 30%, rgba(244, 196, 31, 0.18) 0%, rgba(244, 196, 31, 0) 55%),
    radial-gradient(circle at 70% 70%, rgba(44, 185, 176, 0.14) 0%, rgba(44, 185, 176, 0) 58%);
  opacity: 0;
  transform: scale(1.05);
  pointer-events: none;
  z-index: 0;
}

.hero-shell {
  width: 100%;
  max-width: 1220px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
  align-items: center;
  gap: 30px;
  position: relative;
  z-index: 1;
}

.page:not(.page-ready) .hero-content h1,
.page:not(.page-ready) .hero-desc,
.page:not(.page-ready) .hero-search,
.page:not(.page-ready) .hero-seal {
  opacity: 0;
  transform: translateY(14px);
}

@keyframes hero-rise {
  from {
    opacity: 0;
    transform: translateY(14px);
    filter: blur(2px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
    filter: blur(0);
  }
}

@keyframes hero-pop {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes hero-float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

@keyframes hero-glow {
  from {
    opacity: 0;
    transform: scale(1.06);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.page.page-ready .hero::after {
  animation: hero-glow 1.05s ease both 0.12s;
}

.page.page-ready .hero-content h1 {
  will-change: transform, opacity, filter;
  animation: hero-rise 0.85s cubic-bezier(0.2, 0.9, 0.2, 1) both 0.18s;
}

.page.page-ready .hero-desc {
  will-change: transform, opacity, filter;
  animation: hero-rise 0.85s cubic-bezier(0.2, 0.9, 0.2, 1) both 0.3s;
}

.page.page-ready .hero-search {
  will-change: transform, opacity;
  animation: hero-pop 0.95s cubic-bezier(0.2, 0.9, 0.2, 1) both 0.42s;
}

.page.page-ready .hero-seal {
  will-change: transform, opacity, filter;
  animation:
    hero-rise 0.95s cubic-bezier(0.2, 0.9, 0.2, 1) both 0.26s,
    hero-float 5.2s ease-in-out infinite 1.35s;
}

.page.page-ready .hero-search .search-submit {
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.page.page-ready .hero-search .search-submit:hover {
  transform: translateY(-1px);
}

@media (prefers-reduced-motion: reduce) {
  .page:not(.page-ready) .hero-content h1,
  .page:not(.page-ready) .hero-desc,
  .page:not(.page-ready) .hero-search,
  .page:not(.page-ready) .hero-seal {
    opacity: 1;
    transform: none;
  }

  .page.page-ready .hero::after,
  .page.page-ready .hero-content h1,
  .page.page-ready .hero-desc,
  .page.page-ready .hero-search,
  .page.page-ready .hero-seal {
    animation: none;
  }
}

.hero-content {
  max-width: 680px;
  text-align: left;
}

.hero-content h1 {
  margin: 0;
  font-size: clamp(2rem, 3.6vw, 3rem);
  line-height: 1.12;
  color: #f8fafc;
}

.hero-desc {
  margin-top: 16px;
  font-size: clamp(0.95rem, 1.4vw, 1.05rem);
  color: #facc15;
  font-weight: 700;
  max-width: 560px;
}

.hero-search {
  margin-top: 20px;
  max-width: 760px;
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1fr) auto;
  background: #ffffff;
  border-radius: 999px;
  padding: 6px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
}

.search-field {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border-right: 1px solid #e5e7eb;
  min-width: 0;
}

.search-field:last-child {
  border-right: 0;
}

.search-field > svg {
  width: 14px;
  height: 14px;
  color: #6b7280;
  flex: 0 0 auto;
}

.search-field input,
.search-field select {
  width: 100%;
  border: 0;
  outline: none;
  background: transparent;
  color: #0f172a;
  font-size: 0.76rem;
  font-weight: 600;
  font-family: inherit;
  min-width: 0;
}

.search-field input::placeholder {
  color: #6b7280;
  font-weight: 500;
}

.search-field select {
  padding-right: 18px;
  appearance: none;
  cursor: pointer;
  color: #374151;
}

.search-field-select .search-caret {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 13px;
  height: 13px;
  color: #64748b;
  pointer-events: none;
}

.search-submit {
  border: 0;
  background: #0f4f25;
  color: #ffffff;
  border-radius: 999px;
  padding: 0 18px;
  font-size: 0.76rem;
  font-weight: 700;
  cursor: pointer;
  min-height: 36px;
  align-self: center;
}

.search-submit:hover {
  background: #0b3d1d;
}

.hero-logo-wrap {
  display: flex;
  justify-content: center;
}

.hero-seal {
  width: min(370px, 100%);
  height: auto;
  filter: drop-shadow(0 16px 26px rgba(0, 0, 0, 0.38));
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

  .section-nav {
    display: none;
  }

  .hero {
    padding: 110px 6% 60px;
  }

  .hero-shell {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .hero-content {
    max-width: none;
    text-align: center;
  }

  .hero-desc {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-search {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-seal {
    width: min(300px, 72vw);
  }
}

@media (max-width: 720px) {
  .sign-in-btn {
    padding: 8px 10px;
    font-size: 0.72rem;
  }

  .sign-in-btn svg {
    width: 13px;
    height: 13px;
  }

  .hero-search {
    border-radius: 18px;
    grid-template-columns: 1fr;
    max-width: 390px;
  }

  .search-field {
    border-right: 0;
    border-bottom: 1px solid #e5e7eb;
    justify-content: flex-start;
    padding: 11px 12px;
  }

  .search-field:last-child {
    border-bottom: 0;
  }

  .search-submit {
    margin-top: 8px;
    width: 100%;
    border-radius: 10px;
    min-height: 38px;
  }
}

/* SECTIONS */
.section{
  padding:140px 20px;
  background:white;
  text-align:center;
}

#about,
#tutorial,
#privacy,
#contact,
#mission,
#vision {
  scroll-margin-top: 108px;
}

/* About section border */
#about.section{
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  margin: 0 6%;
}

.section.alt{
  background:#eef1f7;
}

h2{
  font-size:36px;
  margin-bottom:40px;
  color:#111;
}

/* ABOUT PANEL */
.about-panel{
  width:100%;
  max-width:none;
  margin:0;
  display:grid;
  grid-template-columns:50% 50%;
  gap:0;
  padding:0;
  border:1px solid #d1d5db;
  border-radius:14px;
  background:#f9fafb;
  overflow:hidden;
  min-height:360px;
}

.about-image-wrap{
  position:relative;
  border:0;
  min-height:100%;
  background:linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
  padding:34px;
  display:grid;
  place-items:center;
}

.about-single-image{
  width:100%;
  max-width:560px;
  height:auto;
  aspect-ratio:4/3;
  border-radius:12px;
  border:1px solid #dbe4f1;
  box-shadow:0 18px 40px rgba(15, 23, 42, 0.2);
  object-fit:cover;
  display:block;
}

.about-copy{
  text-align:left;
  display:flex;
  flex-direction:column;
  justify-content:center;
  gap:14px;
  padding:32px;
  box-sizing:border-box;
}

.about-copy h3{
  margin:0;
  font-size:2rem;
  color:#111827;
}

.about-copy p{
  margin:0;
  color:#374151;
  line-height:1.7;
}

.about-focus-card {
  margin-top: 6px;
  border: 1px solid #dbe4f1;
  background: #f8fbff;
  border-radius: 12px;
  padding: 14px 16px;
}

.about-focus-card h4 {
  margin: 0;
  color: #0f4f25;
  font-size: 1rem;
}

.about-focus-card p {
  margin-top: 6px;
}

@media (max-width: 900px){
  .about-panel{
    grid-template-columns:1fr;
    min-height:auto;
  }

  .about-image-wrap{
    padding:24px 18px;
  }

  .about-single-image{
    max-width:100%;
  }
}

/* MISSION */
.mission-box{
  max-width:800px;
  margin:auto;
  background:#fff;
  padding:40px;
  border-radius:20px;
  box-shadow:0 15px 35px rgba(0,0,0,.08);
  transition:.3s;
}

.mission-box:hover{
  transform:scale(1.02);
}

/* CTA */
.cta-box{
  max-width:600px;
  margin:auto;
  background:#fff;
  padding:40px;
  border-radius:20px;
  box-shadow:0 15px 35px rgba(0,0,0,.08);
  transition:.3s;
}

.cta-box:hover{
  transform:translateY(-6px);
}

/* CONTACT */
.contact-box{
  max-width:500px;
  margin:auto;
  background:#fff;
  padding:30px;
  border-radius:16px;
  box-shadow:0 12px 30px rgba(0,0,0,.08);
  transition:.3s;
}

.contact-box:hover{
  transform:translateY(-5px);
}

/* FOOTER */
.footer {
  background: linear-gradient(180deg, #0b4c23 0%, #093c1d 58%, #072f18 100%);
  color: #dce8da;
  padding: 34px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
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
  display: flex;
  flex-direction: column;
  align-items: flex-start;
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

.social-wrapper {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-top: 10px;
}

.social-link {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background: #e0b118;
  color: #083b1c;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #c99904;
  transition: transform 0.2s ease, background 0.2s ease;
}

.social-link svg {
  width: 12px;
  height: 12px;
}

.social-link:hover {
  background: #e9bc24;
  transform: translateY(-1px);
}

.footer-nav {
  display: grid;
  grid-template-columns: minmax(220px, 1fr) minmax(145px, 180px);
  gap: 34px;
  align-content: start;
  align-items: start;
}

.footer .nav-group h3 {
  margin: 0 0 10px;
  color: #f4c41f;
  font-size: 0.86rem;
  font-weight: 700;
  letter-spacing: 0.1px;
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
}

.footer .nav-group li + li {
  margin-top: 10px;
}

.footer .nav-group a {
  text-decoration: none;
  color: #f4c41f;
  font-size: 0.8rem;
  font-weight: 600;
  transition: opacity 0.2s ease;
}

.footer .nav-group a:hover {
  opacity: 0.85;
}

.footer .about-group p {
  max-width: 300px;
}

.footer .services-group ul {
  display: grid;
  gap: 8px;
}

.footer .services-group li + li {
  margin-top: 0;
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
  letter-spacing: 0.04px;
}

@media (max-width: 980px) {
  .footer-container {
    grid-template-columns: 1fr;
    gap: 26px;
  }

  .footer-nav {
    grid-template-columns: minmax(0, 1fr) minmax(140px, 180px);
    gap: 24px;
  }

  .footer .about-group p {
    max-width: none;
  }
}

@media (max-width: 640px) {
  .footer {
    padding: 28px 0 0;
  }

  .footer-logo {
    height: 72px;
    width: auto;
  }

  .brand-text,
  .footer .nav-group p,
  .footer .nav-group a {
    font-size: 0.76rem;
  }

  .footer .nav-group h3 {
    font-size: 0.82rem;
  }

  .footer-nav {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .bottom-inner p {
    font-size: 0.58rem;
    line-height: 1.35;
  }
}




















.tutorial-section{
  padding:90px 6%;
  background:#f8fafc;
}

.tutorial-card{
  width:100%;
  background:#ffffff;
  border:1px solid #d1d5db;
  border-radius:14px;
  display:grid;
  grid-template-columns:50% 50%;
  overflow:hidden;
  min-height:420px;
}

.video-pane{
  padding:24px;
  border-right:1px solid #e5e7eb;
  background:#f3f4f6;
  display:flex;
  align-items:stretch;
}

.video-placeholder{
  width:100%;
  height:100%;
  min-height:100%;
  max-height:none;
  flex:1;
  border-radius:18px;
  border:1px solid transparent;
  background:
    linear-gradient(170deg, #f8fbff 0%, #eef3fb 45%, #dde7f4 100%) padding-box,
    linear-gradient(135deg, #0f172a 0%, #2563eb 55%, #60a5fa 100%) border-box;
  box-shadow:
    0 20px 45px rgba(15, 23, 42, 0.20),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  display:block;
  padding:10px;
  box-sizing:border-box;
  position:relative;
  overflow:hidden;
}

.video-placeholder::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(120deg, rgba(255,255,255,0.28) 0%, rgba(255,255,255,0) 38%);
  pointer-events:none;
}

.tutorial-video{
  width:100%;
  height:100%;
  border:0;
  border-radius:12px;
  background:#000;
  box-shadow:
    0 8px 18px rgba(2, 6, 23, 0.35),
    inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.tutorial-video-blank{
  background:#ffffff;
  box-shadow:none;
}

.tutorial-pane{
  padding:26px 24px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  gap:10px;
}

.tutorial-header{
  text-align:left;
  margin-bottom:8px;
}

.tutorial-header h2{
  font-size:2rem;
  margin:14px 0 8px;
}

.tutorial-header p{
  margin:0;
  color:#64748b;
}

.tutorial-step{
  border:1px solid #e5e7eb;
  border-radius:10px;
  background:#fff;
  padding:14px;
  display:grid;
  grid-template-columns:32px 1fr;
  gap:12px;
  align-items:flex-start;
}

.step-dot{
  width:32px;
  height:32px;
  border-radius:999px;
  display:grid;
  place-items:center;
  font-weight:700;
  color:#1e3a8a;
  background:#dbeafe;
}

.tutorial-step h3{
  margin:0;
  font-size:1rem;
  color:#111827;
}

.tutorial-step p{
  margin:6px 0 0;
  color:#4b5563;
  line-height:1.55;
}

@media (max-width: 1024px){
  .tutorial-card{
    grid-template-columns:1fr;
  }

  .video-pane{
    border-right:0;
    border-bottom:1px solid #e5e7eb;
  }

  .video-placeholder{
    height:260px;
    min-height:260px;
    max-height:260px;
  }
}

.faq-section{
  padding:86px 6% 66px;
  background:#ffffff;
  border-top:1px solid #e5e7eb;
}

.faq-shell{
  max-width:1080px;
  margin:0 auto;
}

.faq-header{
  text-align:center;
  margin-bottom:26px;
}

.faq-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 14px;
  border-radius:999px;
  font-size:0.72rem;
  font-weight:700;
}

.faq-section .faq-badge{
  background:#e1b31a;
  color:#103a1b;
  border:1px solid #c69500;
}

.faq-header h2{
  margin:14px 0 6px;
  font-size:clamp(2rem, 4vw, 3rem);
  line-height:1.1;
  color:#0f4f25;
}

.faq-header p{
  margin:0;
  color:#475569;
  font-size:1.02rem;
  font-weight:600;
}

.faq-list{
  max-width:950px;
  margin:0 auto;
  display:grid;
  gap:10px;
}

.faq-item{
  overflow:hidden;
}

.faq-question{
  width:100%;
  padding:14px 18px;
  background:#e0b118;
  border:1px solid #bf9000;
  border-radius:10px;
  color:#122815;
  font-size:0.94rem;
  font-weight:700;
  text-align:left;
  cursor:pointer;
  display:flex;
  justify-content:space-between;
  align-items:center;
  transition:background-color .16s ease;
}

.faq-question:hover{
  background:#e8bc25;
}

.arrow{
  width:15px;
  height:15px;
  color:#1d5c21;
  flex:0 0 auto;
  transition:transform .22s ease;
}

.arrow.open{
  transform:rotate(180deg);
}

.faq-answer{
  margin-top:4px;
  padding:13px 18px 18px;
  border:1px solid #d1d5db;
  border-top:0;
  border-radius:0 0 10px 10px;
  background:#f8fafc;
  color:#111827;
  line-height:1.6;
}

.faq-slide-enter-active,
.faq-slide-leave-active {
  transition:max-height 0.35s ease, opacity 0.26s ease, padding 0.35s ease;
  max-height:300px;
}

.faq-slide-enter-from,
.faq-slide-leave-to {
  max-height:0 !important;
  opacity:0;
  padding-top:0 !important;
  padding-bottom:0 !important;
  margin:0 !important;
}

@media (max-width: 768px){
  .faq-section{
    padding:70px 5% 56px;
  }

  .faq-header h2{
    font-size:clamp(1.62rem, 7vw, 2.2rem);
  }

  .faq-header p{
    font-size:0.9rem;
  }

  .faq-question{
    font-size:0.82rem;
    padding:12px 14px;
  }

  .faq-answer{
    font-size:0.86rem;
    padding:12px 14px 15px;
  }
}
/* ================= MAINTENANCE OVERLAY ================= */

.maintenance-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.95); /* dark blur */
  backdrop-filter: blur(6px);
  z-index: 9999;

  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.maintenance-box {
  background: #0f172a;
  border: 1px solid rgba(255,255,255,0.1);
  padding: 60px 70px;
  border-radius: 20px;
  box-shadow: 0 30px 80px rgba(0,0,0,0.6);
  animation: fadeInUp .5s ease;
}

.maintenance-box h1 {
  font-size: 42px;
  color: #facc15; /* warning yellow */
  margin-bottom: 20px;
  letter-spacing: 1px;
}

.maintenance-box p {
  font-size: 18px;
  color: #e5e7eb;
  line-height: 1.6;
}

.maintenance-sub {
  display: block;
  margin-top: 18px;
  font-size: 14px;
  color: #94a3b8;
  letter-spacing: .5px;
}

/* subtle entrance animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


/* WARNING BLINK */

.warning-wrap{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:14px;
  margin-bottom:20px;
}

.warning-icon{
  font-size:46px;
  color:#facc15;
  animation: blink 1.2s infinite;
}

/* subtle blink only (not flashy) */
@keyframes blink{
  0%{ opacity:1; }
  50%{ opacity:.3; }
  100%{ opacity:1; }
}

</style>

