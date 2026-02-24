<template>
  <div class="page" :class="{ 'page-ready': !showPageLoader }">
    <transition name="page-loader-fade">
      <div
        v-if="showPageLoader || heroSearchLoading"
        class="page-loader-overlay"
        role="status"
        aria-live="polite"
        :aria-label="heroSearchLoading ? 'Loading search results' : 'Loading landing page'"
      >
        <div v-if="heroSearchLoading" class="search-loading-card">
          <div class="search-loading-icon" aria-hidden="true">
            <span class="search-loading-spinner-ring"></span>
            <span class="search-loading-spinner-core"></span>
          </div>
          <div class="search-loading-copy">
            <p class="search-loading-title">Finding the best job matches</p>
            <p class="search-loading-subtitle">Please wait while we fetch fresh openings for you.</p>
            <div class="search-loading-dots" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
        <div v-else class="dot-spinner" aria-hidden="true">
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
      <router-link to="/about-us" class="nav-link">About Us</router-link>
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
      <button
        type="button"
        class="mobile-menu-btn"
        :aria-expanded="isMobileMenuOpen"
        aria-controls="mobile-nav-drawer"
        aria-label="Open navigation menu"
        @click="toggleMobileMenu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</header>

    <transition name="mobile-nav-fade">
      <div
        v-if="isMobileMenuOpen"
        class="mobile-nav-overlay"
        @click.self="closeMobileMenu"
      >
        <aside id="mobile-nav-drawer" class="mobile-nav-drawer" role="dialog" aria-label="Mobile Navigation">
          <div class="mobile-nav-head">
            <img :src="logoDefault" alt="HireAble logo" class="mobile-nav-logo" />
            <button type="button" class="mobile-nav-close" aria-label="Close navigation menu" @click="closeMobileMenu">
              <span aria-hidden="true">×</span>
            </button>
          </div>

          <nav class="mobile-nav-links">
            <router-link to="/about-us" class="mobile-nav-link" @click="closeMobileMenu">About Us</router-link>
            <button type="button" class="mobile-nav-link" @click="onMobileNavSelect('tutorial')">Read First</button>
            <button type="button" class="mobile-nav-link" @click="onMobileNavSelect('privacy')">Privacy</button>
            <button type="button" class="mobile-nav-link" @click="onMobileNavSelect('contact')">Contact Us</button>
          </nav>

          <router-link :to="{ path: '/login', query: { force: '1' } }" class="mobile-nav-signin" @click="closeMobileMenu">
            Sign In
          </router-link>
        </aside>
      </div>
    </transition>

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
              <select v-model="heroFilters.location" aria-label="Disability type">
                <option value="">All Disability Types</option>
                <option
                  v-for="disability in heroDisabilities"
                  :key="disability"
                  :value="disability"
                >
                  {{ disability }}
                </option>
              </select>
              <svg class="search-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"></path>
              </svg>
            </label>

            <button type="submit" class="search-submit" :disabled="heroSearchLoading">
              {{ heroSearchLoading ? "Searching..." : "Search" }}
            </button>
          </form>
        </div>

        <div class="hero-logo-wrap">
          <img :src="heroSeal" alt="HireAble Proximity Development logo" class="hero-seal" />
        </div>
      </div>
    </section>

    <!-- ABOUT CTA -->
    <section
      id="about"
      ref="aboutSection"
      class="section about-cta-section"
      :class="{ 'about-visible': isAboutVisible }"
    >
      <div class="about-cta-shell">
        <h3 class="about-cta-title">Inclusive Work Opportunities</h3>
        <p>
          Narito ang mga halimbawa ng work opportunities at disability-friendly
          roles na puwedeng i-match sa inclusive companies.
        </p>

        <div class="about-cta-content" aria-label="Featured inclusive job posts">
          <div class="about-cta-panel about-cta-actions about-cta-jobs-list">
            <h4>Featured Job Posts</h4>
            <p class="about-cta-actions-copy">
              Preview ng job posts na puwedeng makita ng applicants, kasama ang
              disability support / fit sa company setup.
            </p>
            <div class="landing-job-preview-list">
              <article
                v-for="(job, index) in featuredJobPosts"
                :key="`${job.title}-${job.company}-${job.postedDate}`"
                class="landing-job-card"
                :class="{ 'is-active': selectedFeaturedJobIndex === index }"
                role="button"
                tabindex="0"
                @click="selectFeaturedJob(index)"
                @keydown.enter.prevent="selectFeaturedJob(index)"
                @keydown.space.prevent="selectFeaturedJob(index)"
              >
                <div class="landing-job-head">
                  <div class="landing-job-logo" aria-hidden="true">
                    {{ job.companyInitials }}
                  </div>
                  <div class="landing-job-title-wrap">
                    <h5>{{ job.title }}</h5>
                    <p>{{ job.company }}</p>
                  </div>
                </div>

                <p class="landing-job-desc">
                  {{ job.description }}
                </p>

                <div class="landing-job-meta">
                  <span class="landing-job-chip">{{ job.location }}</span>
                  <span class="landing-job-chip">{{ job.setup }}</span>
                  <span class="landing-job-chip">{{ job.vacancies }} Vacancies</span>
                  <span class="landing-job-chip">{{ job.salary }}</span>
                  <span class="landing-job-chip landing-job-chip-accent">{{ job.disabilityFit }}</span>
                  <span class="landing-job-chip">{{ job.postedDate }}</span>
                </div>
              </article>
            </div>
          </div>

          <div v-if="selectedFeaturedJob" class="about-cta-panel about-cta-actions landing-job-detail-panel">
            <h4>Job Details</h4>
            <p class="about-cta-actions-copy">
              Makikita dito sa right side ang details ng job post na pinindot mo.
            </p>

            <div class="landing-job-detail-head">
              <div class="landing-job-logo landing-job-logo-lg" aria-hidden="true">
                {{ selectedFeaturedJob.companyInitials }}
              </div>
              <div class="landing-job-title-wrap">
                <h5>{{ selectedFeaturedJob.title }}</h5>
                <p>{{ selectedFeaturedJob.company }}</p>
              </div>
            </div>

            <p class="landing-job-detail-desc">{{ selectedFeaturedJob.description }}</p>

            <div class="landing-job-meta">
              <span class="landing-job-chip">{{ selectedFeaturedJob.location }}</span>
              <span class="landing-job-chip">{{ selectedFeaturedJob.setup }}</span>
              <span class="landing-job-chip">{{ selectedFeaturedJob.vacancies }} Vacancies</span>
              <span class="landing-job-chip">{{ selectedFeaturedJob.salary }}</span>
              <span class="landing-job-chip landing-job-chip-accent">{{ selectedFeaturedJob.disabilityFit }}</span>
              <span class="landing-job-chip">{{ selectedFeaturedJob.postedDate }}</span>
            </div>

            <div class="landing-job-detail-grid">
              <div class="landing-job-detail-block">
                <h6>Qualifications</h6>
                <ul>
                  <li v-for="item in selectedFeaturedJob.qualifications" :key="`qual-${item}`">
                    {{ item }}
                  </li>
                </ul>
              </div>
              <div class="landing-job-detail-block">
                <h6>Responsibilities</h6>
                <ul>
                  <li v-for="item in selectedFeaturedJob.responsibilities" :key="`resp-${item}`">
                    {{ item }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- TUTORIAL -->
    <section
      id="tutorial"
      ref="tutorialSection"
      class="tutorial-section"
      :class="{ 'tutorial-visible': isTutorialVisible }"
    >
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
            @click="openTutorialVideo(index)"
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

    <transition name="tutorial-modal-fade">
      <div
        v-if="selectedTutorialIndex !== null"
        class="tutorial-modal-overlay"
        @click="closeTutorialVideo"
      >
        <button
          type="button"
          class="tutorial-modal-close"
          @click="closeTutorialVideo"
          aria-label="Close tutorial video"
        >
          &times;
        </button>
        <div class="tutorial-modal" @click.stop>
          <iframe
            class="tutorial-modal-video"
            :src="selectedTutorialVideoSrc"
            :title="tutorials[selectedTutorialIndex]?.q || 'Tutorial video'"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>
      </div>
    </transition>

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
          <div class="brand-badges" aria-label="Platform highlights">
            <span class="brand-badge">Dasmarinas, Cavite</span>
            <span class="brand-badge">Inclusive Hiring</span>
            <span class="brand-badge">Research Project</span>
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
import aboutPhoto2 from "@/assets/PWD_choose.png";
import aboutPhoto3 from "@/assets/PWD_login.png";

export default {
  name: "LandingPage",

  data() {
    return {
      logoDefault: proximityLogo,
      logoScrolled: proximityLogo,
      heroSeal,
      footerLogo: proximityLogo,
      showPageLoader: true,
      heroSearchLoading: false,
      pageLoaderTimer: null,
      aboutSlideTimer: null,
      activeAboutSlide: 0,
      aboutObserver: null,
      isAboutVisible: false,
        maintenanceMode: true, // 🔥 toggle mo lang to false pag live na
      tutorialObserver: null,
      isTutorialInView: false,
      isTutorialVisible: false,
      selectedTutorialIndex: null,
      selectedFeaturedJobIndex: 0,
      isMobileMenuOpen: false,
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
        { id: "tutorial", label: "Tutorial" },
        { id: "contact", label: "FAQs" },
        { id: "privacy", label: "Privacy" }
      ],
      heroFilters: {
        keyword: "",
        location: "",
        category: ""
      },
      heroDisabilities: [
        "Visual Impairment",
        "Hearing Impairment",
        "Speech and Language Impairment",
        "Physical Disability / Orthopedic",
        "Psychosocial Disability",
        "Intellectual Disability",
        "Learning Disability",
        "Autism Spectrum Disorder",
        "Chronic Illness",
        "Multiple Disabilities"
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
      featuredJobPosts: [
        {
          title: "Data Encoder",
          company: "Inclusive Business Solutions",
          companyInitials: "IB",
          description: "Encode and validate records, prepare reports, and support admin tasks using accessible office tools and clear workflows.",
          location: "Dasmarinas, Cavite",
          setup: "On-site / Hybrid",
          vacancies: 3,
          salary: "PHP 12,000 - PHP 15,000",
          disabilityFit: "Hearing / Speech Friendly",
          postedDate: "Posted: Feb 24, 2026",
          qualifications: [
            "Basic computer literacy and typing skills",
            "Attention to detail in data checking",
            "Can follow written instructions and task lists"
          ],
          responsibilities: [
            "Encode and verify daily records",
            "Organize files and update tracking sheets",
            "Coordinate with admin team for document requests"
          ]
        },
        {
          title: "Customer Support (Chat)",
          company: "CareConnect BPO",
          companyInitials: "CC",
          description: "Handle chat-based customer concerns, document tickets, and coordinate with team leads through text-first communication channels.",
          location: "Imus / Dasmarinas",
          setup: "Hybrid",
          vacancies: 5,
          salary: "PHP 16,000 - PHP 21,000",
          disabilityFit: "Hearing / Physical Friendly",
          postedDate: "Posted: Feb 22, 2026",
          qualifications: [
            "Good written communication skills",
            "Basic customer service experience is a plus",
            "Can use chat or ticketing tools"
          ],
          responsibilities: [
            "Respond to customer concerns through chat",
            "Create ticket notes and follow-up updates",
            "Escalate urgent issues to supervisors"
          ]
        },
        {
          title: "QA Tester Assistant",
          company: "BrightPath Tech",
          companyInitials: "BT",
          description: "Test website features, report bugs, and support regression checks. Screen-reader compatible tools can be provided when needed.",
          location: "Remote / Cavite",
          setup: "Remote",
          vacancies: 2,
          salary: "PHP 18,000 - PHP 24,000",
          disabilityFit: "Physical / Visual Support",
          postedDate: "Posted: Feb 20, 2026",
          qualifications: [
            "Basic understanding of web/app testing",
            "Detail-oriented in checking outputs",
            "Can document bugs clearly"
          ],
          responsibilities: [
            "Run test cases and report defects",
            "Record expected vs actual results",
            "Support regression tests before release"
          ]
        }
      ],
      aboutImages: [
        { src: aboutPhoto, alt: "PWD worker collaborating in an inclusive workspace" },
        { src: aboutPhoto2, alt: "PWD job seeker onboarding and support scene" },
        { src: aboutPhoto3, alt: "PWD applicant profile and employment readiness" }
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
  document.addEventListener("keydown", this.onGlobalKeydown);
  this.onScroll();
  this.setupAboutObserver();
  this.setupTutorialObserver();
},
beforeUnmount() {
  if (this.pageLoaderTimer) {
    window.clearTimeout(this.pageLoaderTimer);
    this.pageLoaderTimer = null;
  }
  document.body.style.overflow = "";
  window.removeEventListener("scroll", this.onScroll);
  document.removeEventListener("click", this.closeDropdown);
  document.removeEventListener("keydown", this.onGlobalKeydown);
  this.teardownAboutObserver();
  this.teardownTutorialObserver();
},

computed: {
  heroParallaxStyle() {
    return {
      backgroundPosition: `calc(50% + 34px) calc(60% + ${this.heroParallaxY}px)`
    };
  },
  tutorialVideoSrc() {
    const base = "https://www.youtube.com/embed/J1Ip2sC_lss";
    return this.isTutorialInView
      ? `${base}?autoplay=1&mute=1&playsinline=1`
      : `${base}?autoplay=0&mute=1&playsinline=1`;
  },
  selectedTutorialVideoSrc() {
    if (this.selectedTutorialIndex === null) return "";
    const links = [
      "https://www.youtube.com/embed/J1Ip2sC_lss",
      "https://www.youtube.com/embed/J1Ip2sC_lss",
      "https://www.youtube.com/embed/J1Ip2sC_lss",
      "https://www.youtube.com/embed/J1Ip2sC_lss",
      "https://www.youtube.com/embed/J1Ip2sC_lss"
    ];
    const safeIndex = Math.max(0, Math.min(this.selectedTutorialIndex, links.length - 1));
    return `${links[safeIndex]}?autoplay=1&mute=0&playsinline=1&rel=0`;
  },
  selectedFeaturedJob() {
    if (!Array.isArray(this.featuredJobPosts) || this.featuredJobPosts.length === 0) return null;
    const safeIndex = Math.max(0, Math.min(this.selectedFeaturedJobIndex, this.featuredJobPosts.length - 1));
    return this.featuredJobPosts[safeIndex];
  }
},

methods: {
syncBodyScrollLock() {
  const shouldLock = this.selectedTutorialIndex !== null || this.isMobileMenuOpen;
  document.body.style.overflow = shouldLock ? "hidden" : "";
},

toggleMobileMenu() {
  this.isMobileMenuOpen = !this.isMobileMenuOpen;
  this.syncBodyScrollLock();
},

closeMobileMenu() {
  if (!this.isMobileMenuOpen) return;
  this.isMobileMenuOpen = false;
  this.syncBodyScrollLock();
},

onMobileNavSelect(id) {
  this.closeMobileMenu();
  this.scrollTo(id);
},

onGlobalKeydown(e) {
  if (e.key !== "Escape") return;
  if (this.openDropdown) this.openDropdown = null;
  if (this.isMobileMenuOpen) this.closeMobileMenu();
  if (this.selectedTutorialIndex !== null) this.closeTutorialVideo();
},

selectFeaturedJob(index) {
  this.selectedFeaturedJobIndex = index;
},

startAboutSlider() {
  this.stopAboutSlider();
  if (!Array.isArray(this.aboutImages) || this.aboutImages.length <= 1) return;
  this.aboutSlideTimer = window.setInterval(() => {
    this.activeAboutSlide =
      (this.activeAboutSlide + 1) % this.aboutImages.length;
  }, 4200);
},

stopAboutSlider() {
  if (this.aboutSlideTimer) {
    window.clearInterval(this.aboutSlideTimer);
    this.aboutSlideTimer = null;
  }
},

aboutFrameClass(index) {
  const total = Array.isArray(this.aboutImages) ? this.aboutImages.length : 0;
  if (!total) return "";
  const offset = (index - this.activeAboutSlide + total) % total;
  if (offset === 0) return "frame-front";
  if (offset === 1) return "frame-middle";
  return "frame-back";
},

setupAboutObserver() {
  const section = this.$refs.aboutSection;
  if (!section || typeof IntersectionObserver === "undefined") {
    this.isAboutVisible = true;
    return;
  }

  this.aboutObserver = new IntersectionObserver(
    (entries) => {
      const [entry] = entries;
      if (entry?.isIntersecting) {
        this.isAboutVisible = true;
        this.teardownAboutObserver();
      }
    },
    { threshold: 0.28 }
  );

  this.aboutObserver.observe(section);
},

teardownAboutObserver() {
  if (this.aboutObserver) {
    this.aboutObserver.disconnect();
    this.aboutObserver = null;
  }
},
setupTutorialObserver() {
  const section = this.$refs.tutorialSection;
  if (!section || typeof IntersectionObserver === "undefined") {
    this.isTutorialVisible = true;
    return;
  }

  this.tutorialObserver = new IntersectionObserver(
    (entries) => {
      const [entry] = entries;
      const inView = Boolean(entry?.isIntersecting);
      this.isTutorialInView = inView;
      if (inView) {
        this.isTutorialVisible = true;
      }
    },
    { threshold: 0.45 }
  );

  this.tutorialObserver.observe(section);
},

teardownTutorialObserver() {
  if (this.tutorialObserver) {
    this.tutorialObserver.disconnect();
    this.tutorialObserver = null;
  }
},

openTutorialVideo(index) {
  this.selectedTutorialIndex = index;
  this.syncBodyScrollLock();
},

closeTutorialVideo() {
  this.selectedTutorialIndex = null;
  this.syncBodyScrollLock();
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
    this.isMobileMenuOpen = false;
    this.syncBodyScrollLock();
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

  async submitHeroSearch() {
    if (this.heroSearchLoading) return;
    const query = {};
    if (this.heroFilters.keyword) query.keyword = this.heroFilters.keyword;
    if (this.heroFilters.location) query.location = this.heroFilters.location;

    this.heroSearchLoading = true;
    try {
      await this.$router.push({ name: "SearchJobs", query });
    } finally {
      if (this.$route.name === "LandingPage") {
        this.heroSearchLoading = false;
      }
    }
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
  background: rgba(15, 23, 42, 0.22);
  backdrop-filter: blur(4px);
}

.search-loading-card {
  min-width: min(92vw, 420px);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.24);
  background: linear-gradient(135deg, rgba(7, 29, 16, 0.96) 0%, rgba(8, 44, 23, 0.96) 58%, rgba(7, 36, 19, 0.96) 100%);
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.36);
  position: relative;
  overflow: hidden;
}

.search-loading-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(105deg, rgba(255, 255, 255, 0) 12%, rgba(255, 255, 255, 0.14) 48%, rgba(255, 255, 255, 0) 84%);
  transform: translateX(-130%);
  animation: searchLoadingSheen 1.6s ease-in-out infinite;
  pointer-events: none;
}

.search-loading-icon {
  width: 44px;
  height: 44px;
  position: relative;
  flex-shrink: 0;
}

.search-loading-spinner-ring {
  width: 44px;
  height: 44px;
  border-radius: 999px;
  border: 3px solid rgba(255, 255, 255, 0.2);
  border-top-color: #f4c41f;
  border-right-color: #99e0ac;
  display: block;
  animation: searchLoadingSpin 0.9s linear infinite;
}

.search-loading-spinner-core {
  position: absolute;
  inset: 12px;
  border-radius: 999px;
  background: radial-gradient(circle at 35% 30%, #fef3c7 0%, #f4c41f 46%, #d2a806 100%);
  box-shadow: 0 0 0 4px rgba(244, 196, 31, 0.16);
  animation: searchLoadingPulse 1.2s ease-in-out infinite;
}

.search-loading-copy {
  display: grid;
  gap: 4px;
}

.search-loading-title {
  margin: 0;
  color: #e9f8ec;
  font-size: 0.96rem;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.search-loading-subtitle {
  margin: 0;
  color: rgba(236, 253, 244, 0.86);
  font-size: 0.79rem;
  line-height: 1.45;
}

.search-loading-dots {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.search-loading-dots span {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: rgba(233, 248, 236, 0.86);
  animation: searchDotBounce 1s ease-in-out infinite;
}

.search-loading-dots span:nth-child(2) {
  animation-delay: 0.15s;
}

.search-loading-dots span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes searchLoadingSpin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes searchLoadingPulse {
  0%,
  100% {
    transform: scale(0.94);
  }
  50% {
    transform: scale(1);
  }
}

@keyframes searchDotBounce {
  0%,
  80%,
  100% {
    transform: translateY(0);
    opacity: 0.5;
  }
  40% {
    transform: translateY(-3px);
    opacity: 1;
  }
}

@keyframes searchLoadingSheen {
  100% {
    transform: translateX(130%);
  }
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
  align-items: center;
  gap: 10px;
}

.mobile-menu-btn {
  display: none;
  width: 38px;
  height: 38px;
  border-radius: 11px;
  border: 1px solid rgba(15, 23, 42, 0.3);
  background: #ffffff;
  padding: 9px 8px;
  align-items: center;
  justify-items: center;
  gap: 4px;
}

.mobile-menu-btn span {
  width: 18px;
  height: 2px;
  border-radius: 999px;
  background: #111827;
  display: block;
}

.navbar.navbar-scrolled .mobile-menu-btn {
  background: rgba(248, 250, 252, 0.1);
  border-color: rgba(248, 250, 252, 0.62);
}

.navbar.navbar-scrolled .mobile-menu-btn span {
  background: #f8fafc;
}

.mobile-nav-fade-enter-active,
.mobile-nav-fade-leave-active {
  transition: opacity 0.22s ease;
}

.mobile-nav-fade-enter-from,
.mobile-nav-fade-leave-to {
  opacity: 0;
}

.mobile-nav-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(2, 6, 23, 0.35);
  backdrop-filter: blur(7px);
  -webkit-backdrop-filter: blur(7px);
  display: flex;
  justify-content: flex-end;
}

.mobile-nav-drawer {
  width: min(84vw, 320px);
  height: 100%;
  background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
  border-left: 1px solid #d4deec;
  box-shadow: -14px 0 36px rgba(2, 6, 23, 0.28);
  padding: 16px 14px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  animation: mobileDrawerIn 0.24s ease;
}

.mobile-nav-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.mobile-nav-logo {
  height: 46px;
  width: auto;
  display: block;
}

@keyframes mobileDrawerIn {
  from {
    transform: translateX(16px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.mobile-nav-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #c9d5e7;
  background: #ffffff;
  color: #0f172a;
  font-size: 1.25rem;
  line-height: 1;
  display: grid;
  place-items: center;
}

.mobile-nav-links {
  display: grid;
  gap: 8px;
}

.mobile-nav-link {
  width: 100%;
  border: 1px solid #d6e0ee;
  border-radius: 10px;
  background: #ffffff;
  color: #0f172a;
  text-decoration: none;
  text-align: left;
  font-size: 0.9rem;
  font-weight: 600;
  padding: 11px 12px;
}

.mobile-nav-signin {
  margin-top: auto;
  border: 1px solid #0f4f25;
  border-radius: 10px;
  background: #0f4f25;
  color: #ffffff;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 700;
  text-align: center;
  padding: 11px 12px;
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
  background-size: 114% auto;
  background-position: 58% center;
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
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) auto;
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

  .mobile-menu-btn {
    display: grid;
  }

  .section-nav {
    display: none;
  }

  .hero {
    padding: 110px 6% 60px;
    background-size: cover;
    background-position: 56% center;
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
  .logo {
    height: 40px;
  }

  .navbar {
    height: 72px;
    padding: 0 10px;
  }

  .sign-in-btn {
    padding: 7px 10px;
    font-size: 0.7rem;
  }

  .nav-right .sign-in-btn {
    display: none;
  }

  .sign-in-btn svg {
    width: 13px;
    height: 13px;
  }

  .hero {
    min-height: 500px;
    padding: 86px 4.5% 42px;
    background-size: 178% auto;
    background-position: 50% 14%;
  }

  .hero::after {
    opacity: 0.72;
  }

  .hero-shell {
    gap: 10px;
    align-content: start;
  }

  .hero-logo-wrap {
    order: -1;
    margin-bottom: 2px;
  }

  .hero-content h1 {
    font-size: clamp(1.5rem, 7.2vw, 2rem);
    line-height: 1.16;
  }

  .hero-desc {
    margin-top: 12px;
    font-size: 0.92rem;
    max-width: 96%;
  }

  .hero-search {
    margin-top: 16px;
    border-radius: 18px;
    grid-template-columns: 1fr;
    max-width: 420px;
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

  .hero-seal {
    width: min(132px, 40vw);
  }

  .section {
    padding: 86px 14px;
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
#contact {
  scroll-margin-top: 108px;
}

/* About section border */
#about.section{
  position:relative;
  isolation:isolate;
  border: 0;
  border-radius: 0;
  margin: 0;
  background: linear-gradient(180deg, #f8fbff 0%, #f0f6ff 100%);
  overflow:hidden;
}

#about.section::before,
#about.section::after{
  content:"";
  position:absolute;
  z-index:0;
  pointer-events:none;
  border-radius:999px;
  opacity:0.55;
  filter:blur(0.4px);
}

#about.section::before{
  width:340px;
  height:340px;
  top:-110px;
  left:-70px;
  background:radial-gradient(circle at 35% 35%, rgba(22, 163, 74, 0.22), rgba(59, 130, 246, 0.08) 62%, transparent 76%);
  animation:aboutAuraDriftA 14s ease-in-out infinite alternate;
}

#about.section::after{
  width:300px;
  height:300px;
  right:-80px;
  bottom:-120px;
  background:radial-gradient(circle at 60% 55%, rgba(234, 179, 8, 0.22), rgba(34, 197, 94, 0.08) 60%, transparent 78%);
  animation:aboutAuraDriftB 16s ease-in-out infinite alternate;
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
  position:relative;
  z-index:1;
  width:100%;
  max-width:1140px;
  margin:0 auto;
  display:grid;
  grid-template-columns:0.96fr 1.04fr;
  gap:0;
  padding:0;
  border:1px solid #d7e2ef;
  border-radius:16px;
  background:#ffffff;
  overflow:hidden;
  min-height:400px;
  box-shadow:0 14px 36px rgba(15, 23, 42, 0.09);
  opacity:0;
  transform:translateY(30px) scale(0.985);
  transition:opacity .85s ease, transform .85s cubic-bezier(.2,.72,.2,1);
}

#about.about-visible .about-panel{
  opacity:1;
  transform:translateY(0) scale(1);
}

.about-image-wrap{
  position:relative;
  border:0;
  min-height:100%;
  background:
    radial-gradient(circle at 16% 16%, rgba(15, 79, 37, 0.14), transparent 45%),
    linear-gradient(180deg, #f1f7ff 0%, #e6f0fd 100%);
  padding:34px;
  display:grid;
  place-items:center;
}

.about-slider{
  width:100%;
  max-width:560px;
  border-radius:16px;
  overflow:visible;
  perspective:1200px;
}

.about-frame{
  width:100%;
  aspect-ratio:4/3;
  position:relative;
  overflow:visible;
  transform-origin:center;
}

.about-single-image{
  width:100%;
  height:100%;
  min-width:0;
  max-width:none;
  position:absolute;
  inset:0;
  border-radius:14px;
  border:1px solid #d4e1f2;
  box-shadow:0 18px 42px rgba(15, 23, 42, 0.22);
  object-fit:cover;
  display:block;
  transform-origin:center center;
  opacity:.55;
  transform:translate3d(0, 34px, -108px) rotateX(-14deg) scale(.92);
  filter:blur(.95px) brightness(.82);
  transition:
    transform 1.05s cubic-bezier(.22,.61,.36,1),
    opacity .95s ease,
    filter 1.05s ease;
  backface-visibility:hidden;
  pointer-events:none;
  z-index:1;
  will-change:transform, opacity, filter;
}

.about-single-image.frame-front{
  opacity:1;
  transform:translate3d(0, 0, 20px) rotateX(0deg) scale(1);
  filter:blur(0) brightness(1);
  z-index:3;
}

.about-single-image.frame-middle{
  opacity:.82;
  transform:translate3d(0, 16px, -48px) rotateX(-8deg) scale(.96);
  filter:blur(.35px) brightness(.91);
  z-index:2;
}

.about-single-image.frame-back{
  opacity:.62;
  transform:translate3d(0, 32px, -96px) rotateX(-13deg) scale(.92);
  filter:blur(.95px) brightness(.82);
  z-index:1;
}

.about-slider:hover .about-single-image.frame-front{
  transform:translate3d(0, 0, 20px) rotateX(0deg) scale(1.01);
  filter:saturate(1.05);
}

@keyframes aboutAuraDriftA{
  0%{
    transform:translate3d(0, 0, 0) scale(1);
  }
  100%{
    transform:translate3d(22px, 18px, 0) scale(1.08);
  }
}

@keyframes aboutAuraDriftB{
  0%{
    transform:translate3d(0, 0, 0) scale(1);
  }
  100%{
    transform:translate3d(-24px, -16px, 0) scale(1.1);
  }
}

.about-copy{
  text-align:left;
  display:flex;
  flex-direction:column;
  justify-content:center;
  gap:14px;
  padding:36px;
  box-sizing:border-box;
  opacity:0;
  transform:translateY(20px);
  transition:opacity .8s ease .18s, transform .8s ease .18s;
}

#about.about-visible .about-copy{
  opacity:1;
  transform:translateY(0);
}

.about-copy h3{
  margin:2px 0 2px;
  font-size:2rem;
  color:#0f172a;
}

.about-copy p{
  margin:0;
  color:#334155;
  line-height:1.72;
}

.about-focus-grid{
  margin-top:8px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}

.about-focus-card {
  margin-top: 0;
  border: 1px solid #dbe7f5;
  background: #f8fbff;
  border-radius: 12px;
  padding: 15px 16px;
  transition:transform .22s ease, box-shadow .22s ease;
}

.about-focus-card:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 22px rgba(15, 23, 42, 0.1);
}

.about-focus-card h4 {
  margin: 0;
  color: #0f4f25;
  font-size: 1rem;
}

.about-focus-card p {
  margin-top: 6px;
}

.about-cta-section {
  display: flex;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.about-cta-section::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    linear-gradient(110deg, rgba(5, 46, 22, 0.86) 0%, rgba(9, 77, 36, 0.74) 42%, rgba(22, 101, 52, 0.66) 100%),
    url("@/assets/PWD_worker.png") center / cover no-repeat;
}

.about-cta-section::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(245, 250, 255, 0.12) 0%, rgba(245, 250, 255, 0.04) 100%);
}

.about-cta-shell {
  width: min(1060px, 100%);
  text-align: center;
  padding: 36px 14px 34px;
  position: relative;
  z-index: 1;
  opacity: 0;
  transform: translateY(26px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}

#about.about-visible .about-cta-shell {
  opacity: 1;
  transform: translateY(0);
}

.about-cta-title {
  margin: 0 0 10px;
  font-size: clamp(2.8rem, 7vw, 4.8rem);
  line-height: 1.08;
  letter-spacing: -0.4px;
  color: #14532d;
}

.about-cta-shell > p {
  margin: 0 auto;
  max-width: 760px;
  color: rgba(241, 245, 249, 0.96);
  line-height: 1.7;
}

.about-cta-content {
  margin-top: 24px;
  display: grid;
  grid-template-columns: 1.2fr 0.9fr;
  gap: 16px;
  align-items: stretch;
}

.about-cta-panel {
  border-radius: 18px;
  border: 1px solid rgba(177, 219, 193, 0.35);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 252, 247, 0.9) 100%);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
  backdrop-filter: blur(3px);
  text-align: left;
  opacity: 0;
  transform: translateY(18px);
  transition: opacity 0.55s ease, transform 0.55s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

#about.about-visible .about-cta-panel {
  opacity: 1;
  transform: translateY(0);
}

#about.about-visible .about-cta-panel:nth-child(1) {
  transition-delay: 0.12s;
}

#about.about-visible .about-cta-panel:nth-child(2) {
  transition-delay: 0.22s;
}

.about-cta-panel:hover {
  border-color: rgba(34, 197, 94, 0.4);
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.1);
}

.about-cta-benefits {
  padding: 18px 18px 16px;
}

.about-cta-actions {
  padding: 18px;
  display: flex;
  flex-direction: column;
}

.about-cta-jobs-list {
  min-width: 0;
}

.landing-job-detail-panel {
  min-width: 0;
}

.about-cta-panel h4 {
  margin: 0;
  color: #10231a;
  font-size: 1.08rem;
}

.about-cta-list {
  list-style: none;
  margin: 14px 0 0;
  padding: 0;
  display: grid;
  gap: 12px;
}

.about-cta-list li {
  display: grid;
  grid-template-columns: 34px 1fr;
  gap: 10px;
  align-items: start;
  padding: 10px 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.62);
  border: 1px solid rgba(216, 233, 222, 0.9);
}

.about-cta-check {
  width: 30px;
  height: 30px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eaf7f0;
  color: #166534;
  border: 1px solid #cbead7;
  margin-top: 1px;
}

.about-cta-check svg {
  width: 15px;
  height: 15px;
}

.about-cta-list strong {
  display: block;
  color: #0f172a;
  font-size: 0.92rem;
  line-height: 1.2;
}

.about-cta-list p {
  margin: 4px 0 0;
  color: #475569;
  line-height: 1.45;
  font-size: 0.85rem;
}

.about-cta-actions-copy {
  margin: 10px 0 0;
  color: #475569;
  line-height: 1.55;
  font-size: 0.9rem;
}

.about-cta-actions-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.landing-job-preview-list {
  margin-top: 14px;
  display: grid;
  gap: 12px;
}

.landing-job-card {
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: rgba(255, 255, 255, 0.94);
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.landing-job-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 22px rgba(15, 23, 42, 0.1);
  border-color: rgba(34, 197, 94, 0.35);
}

.landing-job-card.is-active {
  border-color: rgba(22, 163, 74, 0.55);
  box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.12), 0 14px 22px rgba(15, 23, 42, 0.1);
}

.landing-job-head {
  display: grid;
  grid-template-columns: 46px 1fr;
  gap: 10px;
  align-items: center;
}

.landing-job-logo {
  width: 46px;
  height: 46px;
  border-radius: 999px;
  border: 1px solid #dbe7df;
  background: linear-gradient(180deg, #f8fafc 0%, #eef7f1 100%);
  display: grid;
  place-items: center;
  color: #0f6a32;
  font-weight: 800;
  font-size: 0.88rem;
}

.landing-job-logo-lg {
  width: 56px;
  height: 56px;
  font-size: 1rem;
}

.landing-job-title-wrap h5 {
  margin: 0;
  color: #0f172a;
  font-size: 1rem;
  line-height: 1.2;
}

.landing-job-title-wrap p {
  margin: 3px 0 0;
  color: #475569;
  font-size: 0.88rem;
}

.landing-job-desc {
  margin: 12px 0 0;
  color: #334155;
  line-height: 1.52;
  font-size: 0.86rem;
}

.landing-job-detail-head {
  margin-top: 10px;
  display: grid;
  grid-template-columns: 56px 1fr;
  gap: 10px;
  align-items: center;
}

.landing-job-detail-desc {
  margin: 12px 0 0;
  color: #334155;
  line-height: 1.58;
  font-size: 0.9rem;
}

.landing-job-meta {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.landing-job-chip {
  border: 1px solid #dbe3ea;
  background: #f8fafc;
  color: #0f172a;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.76rem;
  font-weight: 600;
  line-height: 1.1;
}

.landing-job-chip-accent {
  border-color: #b7e3c6;
  background: #edf9f1;
  color: #166534;
}

.landing-job-detail-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.landing-job-detail-block {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 12px;
  padding: 12px;
}

.landing-job-detail-block h6 {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 0.85rem;
}

.landing-job-detail-block ul {
  margin: 0;
  padding-left: 16px;
  color: #475569;
  line-height: 1.45;
  font-size: 0.82rem;
}

.landing-job-detail-block li + li {
  margin-top: 4px;
}

.about-cta-action {
  min-height: 42px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #d7e4db;
  background: rgba(255, 255, 255, 0.82);
  color: #123b24;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.86rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.about-cta-action:hover {
  transform: translateY(-1px);
  border-color: #9fd0b3;
  background: #ffffff;
  box-shadow: 0 8px 14px rgba(15, 23, 42, 0.07);
}

.about-cta-action-primary {
  background: linear-gradient(180deg, #0f6a32 0%, #0d5629 100%);
  border-color: #0f6a32;
  color: #f3f9f4;
}

.about-cta-action-primary:hover {
  background: linear-gradient(180deg, #11803b 0%, #0f6a32 100%);
  border-color: #11803b;
}

@media (max-width: 980px){
  .about-panel{
    grid-template-columns:1fr;
    min-height:auto;
  }

  .about-image-wrap{
    padding:24px 18px;
  }

  .about-copy{
    padding:26px 18px 22px;
  }

  .about-single-image{
    max-width:100%;
  }

  .about-focus-grid{
    grid-template-columns:1fr;
  }

  .about-cta-shell {
    padding: 28px 4px 26px;
  }

  .about-cta-content {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .landing-job-detail-head {
    grid-template-columns: 46px 1fr;
  }

  .landing-job-logo-lg {
    width: 46px;
    height: 46px;
    font-size: 0.86rem;
  }
}

@media (max-width: 640px){
  #about.section{
    padding-top:64px;
    padding-bottom:24px;
  }

  #about.section::before{
    width:220px;
    height:220px;
    top:-78px;
    left:-66px;
  }

  #about.section::after{
    width:210px;
    height:210px;
    right:-62px;
    bottom:-76px;
  }

  .about-panel{
    border-radius:12px;
    min-height:auto;
  }

  .about-image-wrap{
    padding:16px 14px;
  }

  .about-slider{
    max-width:100%;
  }

  .about-frame{
    aspect-ratio: 16 / 11;
  }

  .about-single-image{
    border-radius:12px;
    transform:translate3d(0, 22px, -62px) rotateX(-9deg) scale(.95);
  }

  .about-single-image.frame-front{
    transform:translate3d(0, 0, 14px) rotateX(0deg) scale(1);
  }

  .about-single-image.frame-middle{
    transform:translate3d(0, 10px, -34px) rotateX(-6deg) scale(.97);
  }

  .about-single-image.frame-back{
    transform:translate3d(0, 19px, -58px) rotateX(-9deg) scale(.94);
  }

  .about-copy{
    padding:20px 14px 16px;
    gap:11px;
  }

  .about-copy h3{
    font-size:1.65rem;
  }

  .about-copy p{
    font-size:0.92rem;
    line-height:1.62;
  }

  .about-focus-card{
    padding:13px 12px;
  }

  .about-focus-card h4{
    font-size:0.94rem;
  }

  .about-cta-title{
    font-size: clamp(1.9rem, 10vw, 2.4rem);
  }

  .about-cta-panel {
    border-radius: 14px;
  }

  .about-cta-benefits,
  .about-cta-actions {
    padding: 14px;
  }

  .about-cta-list li {
    grid-template-columns: 30px 1fr;
    gap: 8px;
    padding: 9px;
  }

  .about-cta-check {
    width: 26px;
    height: 26px;
  }

  .about-cta-actions-grid{
    grid-template-columns: 1fr;
  }

  .landing-job-card {
    padding: 12px;
    border-radius: 14px;
  }

  .landing-job-head {
    grid-template-columns: 40px 1fr;
    gap: 8px;
  }

  .landing-job-logo {
    width: 40px;
    height: 40px;
    font-size: 0.78rem;
  }

  .landing-job-title-wrap h5 {
    font-size: 0.92rem;
  }

  .landing-job-title-wrap p {
    font-size: 0.8rem;
  }

  .landing-job-desc {
    font-size: 0.8rem;
  }

  .landing-job-chip {
    font-size: 0.7rem;
    padding: 5px 9px;
  }
}

@media (prefers-reduced-motion: reduce){
  #about.section::before,
  #about.section::after,
  .about-frame,
  .about-single-image{
    animation:none !important;
  }

  .about-panel,
  .about-copy,
  .tutorial-card{
    opacity:1 !important;
    transform:none !important;
    transition:none !important;
  }

  .about-single-image{
    transition:none !important;
  }

  .about-single-image.frame-middle,
  .about-single-image.frame-back{
    opacity:0 !important;
  }
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

.brand-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid rgba(244, 196, 31, 0.35);
  background: rgba(255, 255, 255, 0.06);
  color: #eef5ea;
  font-size: 0.74rem;
  font-weight: 600;
  letter-spacing: 0.01em;
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
  padding:78px 6%;
  background:#f8fafc;
}

.tutorial-card{
  width:100%;
  max-width:1140px;
  margin:0 auto;
  background:#ffffff;
  border:1px solid #d1d5db;
  border-radius:14px;
  display:grid;
  grid-template-columns:50% 50%;
  overflow:hidden;
  min-height:400px;
  opacity:0;
  transform:translateY(30px) scale(0.985);
  transition:opacity .85s ease, transform .85s cubic-bezier(.2,.72,.2,1);
}

.tutorial-section.tutorial-visible .tutorial-card{
  opacity:1;
  transform:translateY(0) scale(1);
}

.video-pane{
  padding:24px;
  border-right:1px solid #e5e7eb;
  background:
    radial-gradient(circle at 16% 16%, rgba(15, 79, 37, 0.14), transparent 45%),
    linear-gradient(180deg, #f1f7ff 0%, #e6f0fd 100%);
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
  border:1px solid #d8e0ea;
  background:#ffffff;
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
  cursor:pointer;
  transition:transform .24s ease, box-shadow .24s ease, border-color .24s ease, background-color .24s ease;
}

.tutorial-step:hover{
  transform:translateX(8px) translateY(-1px);
  border-color:#c7d2fe;
  background:#f8fbff;
  box-shadow:0 10px 22px rgba(15, 23, 42, 0.1);
}

.tutorial-step:hover .step-dot{
  background:#bfdbfe;
  color:#1e40af;
}

.tutorial-modal-overlay{
  position:fixed;
  inset:0;
  z-index:1400;
  display:grid;
  place-items:center;
  background:rgba(2, 6, 23, 0.4);
  backdrop-filter:blur(8px);
  -webkit-backdrop-filter:blur(8px);
  padding:22px;
}

.tutorial-modal{
  width:min(94vw, 900px);
  border-radius:16px;
  background:#0f172a;
  border:1px solid rgba(148, 163, 184, 0.35);
  box-shadow:0 24px 54px rgba(2, 6, 23, 0.55);
  overflow:hidden;
  position:relative;
}

.tutorial-modal-video{
  width:100%;
  aspect-ratio:16/9;
  border:0;
  display:block;
  background:#000;
}

.tutorial-modal-close{
  position:absolute;
  top:18px;
  right:18px;
  width:42px;
  height:42px;
  border-radius:12px;
  border:1px solid rgba(148, 163, 184, 0.45);
  background:rgba(15, 23, 42, 0.72);
  color:#f8fafc;
  font-size:1.35rem;
  font-weight:700;
  line-height:1;
  cursor:pointer;
  z-index:3;
  display:grid;
  place-items:center;
  backdrop-filter:blur(8px);
  -webkit-backdrop-filter:blur(8px);
  box-shadow:0 10px 22px rgba(2, 6, 23, 0.45);
  transition:transform .18s ease, background-color .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.tutorial-modal-close:hover{
  background:rgba(30, 41, 59, 0.95);
  border-color:rgba(96, 165, 250, 0.75);
  transform:translateY(-1px) scale(1.03);
  box-shadow:0 14px 26px rgba(2, 6, 23, 0.52);
}

.tutorial-modal-close:active{
  transform:translateY(0) scale(0.97);
}

.tutorial-modal-close:focus-visible{
  outline:2px solid #60a5fa;
  outline-offset:2px;
}

.tutorial-modal-fade-enter-active,
.tutorial-modal-fade-leave-active{
  transition:opacity .3s ease;
}

.tutorial-modal-fade-enter-from,
.tutorial-modal-fade-leave-to{
  opacity:0;
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

@media (max-width: 640px){
  .tutorial-section{
    padding:26px 4.5% 52px;
  }

  .tutorial-card{
    border-radius:12px;
  }

  .video-pane{
    padding:14px;
  }

  .video-placeholder{
    height:210px;
    min-height:210px;
    max-height:210px;
    border-radius:14px;
  }

  .tutorial-pane{
    padding:18px 14px;
    gap:8px;
  }

  .tutorial-header h2{
    font-size:1.7rem;
    margin:12px 0 6px;
  }

  .tutorial-header p{
    font-size:0.9rem;
  }

  .tutorial-step{
    padding:12px;
    grid-template-columns:30px 1fr;
    gap:10px;
  }

  .tutorial-step:hover{
    transform:none;
    box-shadow:none;
  }

  .tutorial-step h3{
    font-size:0.94rem;
  }

  .tutorial-step p{
    margin-top:4px;
    font-size:0.86rem;
    line-height:1.5;
  }

  .step-dot{
    width:30px;
    height:30px;
    font-size:0.86rem;
  }

  .tutorial-modal-overlay{
    padding:12px;
  }

  .tutorial-modal{
    width:min(96vw, 900px);
    border-radius:12px;
  }

  .tutorial-modal-close{
    top:10px;
    right:10px;
    width:38px;
    height:38px;
    border-radius:10px;
    font-size:1.15rem;
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
  background:#ececec;
  border:none;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
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
    font-size:0.88rem;
    padding:12px 14px;
  }

  .faq-answer{
    font-size:0.9rem;
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





