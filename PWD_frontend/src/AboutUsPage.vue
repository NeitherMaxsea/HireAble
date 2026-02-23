<template>
  <div class="about-page">
    <header class="navbar" :class="{ 'navbar-scrolled': isScrolled }">
      <div class="nav-inner">
        <button class="nav-left" type="button" @click="$router.push('/landingpage')">
          <img :src="isScrolled ? logoScrolled : logoDefault" class="logo" alt="HireAble logo" />
        </button>

        <nav class="nav-center">
          <router-link to="/search-jobs" class="nav-link">Find Job</router-link>
          <router-link to="/about-us" class="nav-link">About Us</router-link>
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
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <nav class="mobile-nav-links">
            <router-link to="/search-jobs" class="mobile-nav-link" @click="closeMobileMenu">Find Job</router-link>
            <router-link to="/about-us" class="mobile-nav-link" @click="closeMobileMenu">About Us</router-link>
            <button type="button" class="mobile-nav-link" @click="goToLandingSection('#tutorial')">Read First</button>
            <button type="button" class="mobile-nav-link" @click="goToLandingSection('#privacy')">Privacy</button>
            <button type="button" class="mobile-nav-link" @click="goToLandingSection('#contact')">Contact Us</button>
          </nav>

          <router-link :to="{ path: '/login', query: { force: '1' } }" class="mobile-nav-signin" @click="closeMobileMenu">
            Sign In
          </router-link>
        </aside>
      </div>
    </transition>

    <main class="about-main">
      <section
        id="about"
        ref="aboutSection"
        class="section"
        :class="{ 'about-visible': isAboutVisible }"
      >
        <div class="about-panel">
          <div class="about-image-wrap">
            <div
              class="about-slider"
              aria-label="PWD worker highlights"
              @mouseenter="stopAboutSlider"
              @mouseleave="startAboutSlider"
            >
              <div class="about-frame">
                <img
                  v-for="(img, index) in aboutImages"
                  :key="index"
                  :src="img.src"
                  :alt="img.alt"
                  class="about-single-image"
                  :class="aboutFrameClass(index)"
                />
              </div>
            </div>
          </div>

          <div class="about-copy">
            <span class="about-badge">Who We Are</span>
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
          </div>
        </div>

        <section class="about-pillars-section" aria-label="Mission and Vision">
          <div class="about-pillars-head">
            <span class="about-pillars-eyebrow">Guiding Principles</span>
            <h4>Our Mission and Vision</h4>
            <p>The principles that guide our platform and inclusive hiring goals.</p>
          </div>
          <div class="about-pillars">
            <article id="mission" class="about-pillar about-pillar-mission">
              <div class="about-pillar-heading">
                <span class="about-pillar-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 3l2.6 5.3L20 11l-5.4 2.7L12 19l-2.6-5.3L4 11l5.4-2.7L12 3z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <div class="about-pillar-title-wrap">
                  <h4>Mission</h4>
                  <span class="about-pillar-subtitle">Empowering PWD talent through access</span>
                </div>
              </div>
              <p>
                To empower Persons with Disabilities by opening pathways to inclusive
                employment through an accessible, supportive, and opportunity-driven platform.
              </p>
            </article>

            <article id="vision" class="about-pillar about-pillar-vision">
              <div class="about-pillar-heading">
                <span class="about-pillar-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.7" />
                    <path d="M3.5 12s3.1-5.7 8.5-5.7S20.5 12 20.5 12s-3.1 5.7-8.5 5.7S3.5 12 3.5 12z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <div class="about-pillar-title-wrap">
                  <h4>Vision</h4>
                  <span class="about-pillar-subtitle">Barrier-free and meaningful employment</span>
                </div>
              </div>
              <p>
                A future where every qualified PWD is seen for their strengths and
                can access fair, meaningful, and sustainable work without barriers.
              </p>
            </article>
          </div>
        </section>

        <section class="about-gallery" aria-label="PWD success moments">
          <div class="about-gallery-head">
            <h4>In Action</h4>
            <p>
              Highlights of the platform experience from onboarding to employment readiness.
            </p>
          </div>
          <div class="about-gallery-grid">
            <button
              v-for="(img, index) in aboutImages"
              :key="`gallery-${index}`"
              type="button"
              class="about-gallery-card"
              :class="{ 'about-gallery-card-active': activeAboutSlide === index }"
              @click="activeAboutSlide = index"
            >
              <img :src="img.src" :alt="img.alt" class="about-gallery-image" />
            </button>
          </div>
        </section>
      </section>
    </main>

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
import aboutPhoto from "@/assets/PWD_worker.png";
import aboutPhoto2 from "@/assets/PWD_choose.png";
import aboutPhoto3 from "@/assets/PWD_login.png";

export default {
  name: "AboutUsPage",
  data() {
    return {
      logoDefault: proximityLogo,
      logoScrolled: proximityLogo,
      footerLogo: proximityLogo,
      isScrolled: false,
      isMobileMenuOpen: false,
      isAboutVisible: false,
      aboutSlideTimer: null,
      aboutObserver: null,
      activeAboutSlide: 0,
      aboutImages: [
        { src: aboutPhoto, alt: "PWD worker collaborating in an inclusive workspace" },
        { src: aboutPhoto2, alt: "PWD job seeker onboarding and support scene" },
        { src: aboutPhoto3, alt: "PWD applicant profile and employment readiness" }
      ]
    };
  },
  mounted() {
    window.addEventListener("scroll", this.onScroll, { passive: true });
    document.addEventListener("keydown", this.onGlobalKeydown);
    this.onScroll();
    this.startAboutSlider();
    this.setupAboutObserver();
  },
  beforeUnmount() {
    this.stopAboutSlider();
    document.body.style.overflow = "";
    window.removeEventListener("scroll", this.onScroll);
    document.removeEventListener("keydown", this.onGlobalKeydown);
    this.teardownAboutObserver();
  },
  methods: {
    syncBodyScrollLock() {
      document.body.style.overflow = this.isMobileMenuOpen ? "hidden" : "";
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
    onGlobalKeydown(e) {
      if (e.key !== "Escape") return;
      if (this.isMobileMenuOpen) this.closeMobileMenu();
    },
    onScroll() {
      this.isScrolled = window.scrollY > 10;
    },
    goToLandingSection(hash) {
      this.closeMobileMenu();
      this.$router.push({ path: "/landingpage", hash });
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
    }
  }
};
</script>

<style scoped>
.about-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f7fbff 0%, #f9fcfb 55%, #f5f9fd 100%);
}

.about-main {
  padding-top: 88px;
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

.nav-link {
  color: #111827;
  text-decoration: none;
  font-size: 0.73rem;
  font-weight: 600;
  letter-spacing: 0.12px;
  transition: color 0.24s ease, opacity 0.24s ease;
}

.nav-link-btn {
  border: 0;
  background: transparent;
  font-family: inherit;
  cursor: pointer;
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

.nav-right {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
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

#about.section {
  width: min(1160px, 100%);
  margin-inline: auto;
  margin-bottom: 14px;
  padding: 44px 24px 18px;
  position: relative;
}

#about.section::before,
#about.section::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  z-index: 0;
}

#about.section::before {
  width: 320px;
  height: 320px;
  top: -120px;
  left: -90px;
  background: radial-gradient(circle, rgba(52, 211, 153, 0.26) 0%, rgba(52, 211, 153, 0) 70%);
}

#about.section::after {
  width: 300px;
  height: 300px;
  right: -80px;
  bottom: -120px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, rgba(59, 130, 246, 0) 72%);
}

.about-panel {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: 0;
  align-items: stretch;
  background: linear-gradient(160deg, #fff 0%, #f8fcff 57%, #eefaf2 100%);
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.1);
  overflow: hidden;
  min-height: 470px;
  opacity: 0;
  transform: translateY(22px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}

#about.about-visible .about-panel {
  opacity: 1;
  transform: translateY(0);
}

.about-image-wrap {
  padding: 24px;
  background: radial-gradient(circle at 20% 20%, rgba(15, 79, 37, 0.12), transparent 56%), #f3faf6;
  display: grid;
  place-items: center;
}

.about-slider {
  width: 100%;
  max-width: 560px;
  border-radius: 16px;
  overflow: visible;
  perspective: 1200px;
}

.about-frame {
  width: 100%;
  aspect-ratio: 4/3;
  position: relative;
  overflow: visible;
  transform-origin: center;
}

.about-single-image {
  width: 100%;
  height: 100%;
  position: absolute;
  inset: 0;
  border-radius: 14px;
  border: 1px solid #d4e1f2;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.22);
  object-fit: cover;
  display: block;
  transform-origin: center center;
  opacity: .55;
  transform: translate3d(0, 34px, -108px) rotateX(-14deg) scale(.92);
  filter: blur(.95px) brightness(.82);
  transition:
    transform 1.05s cubic-bezier(.22,.61,.36,1),
    opacity .95s ease,
    filter 1.05s ease;
  pointer-events: none;
  z-index: 1;
}

.about-single-image.frame-front {
  opacity: 1;
  transform: translate3d(0, 0, 20px) rotateX(0deg) scale(1);
  filter: blur(0) brightness(1);
  z-index: 3;
}

.about-single-image.frame-middle {
  opacity: .82;
  transform: translate3d(0, 16px, -48px) rotateX(-8deg) scale(.96);
  filter: blur(.35px) brightness(.91);
  z-index: 2;
}

.about-single-image.frame-back {
  opacity: .62;
  transform: translate3d(0, 32px, -96px) rotateX(-13deg) scale(.92);
  filter: blur(.95px) brightness(.82);
  z-index: 1;
}

.about-copy {
  text-align: left;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 14px;
  padding: 36px;
  box-sizing: border-box;
}

.about-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: max-content;
  padding: 7px 14px;
  border-radius: 999px;
  background: #e6f6ed;
  color: #0f4f25;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  border: 1px solid #cce8d6;
}

.about-copy h3 {
  margin: 2px 0 2px;
  font-size: 2rem;
  color: #0f172a;
}

.about-copy p {
  margin: 0;
  color: #334155;
  line-height: 1.72;
}

.about-pillars {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 28px;
  position: relative;
}

.about-pillars::before {
  content: "";
  position: absolute;
  top: 8px;
  bottom: 8px;
  left: 50%;
  width: 1px;
  background: linear-gradient(
    180deg,
    rgba(148, 163, 184, 0) 0%,
    rgba(148, 163, 184, 0.55) 20%,
    rgba(148, 163, 184, 0.55) 80%,
    rgba(148, 163, 184, 0) 100%
  );
  transform: translateX(-0.5px);
  pointer-events: none;
}

.about-pillars-section {
  position: relative;
  margin-top: 24px;
  padding: 22px 24px 20px;
  border-radius: 20px;
  background:
    radial-gradient(110% 110% at 0% 0%, rgba(14, 116, 144, 0.1) 0%, rgba(14, 116, 144, 0) 46%),
    radial-gradient(110% 110% at 100% 100%, rgba(22, 101, 52, 0.11) 0%, rgba(22, 101, 52, 0) 42%),
    linear-gradient(150deg, rgba(251, 253, 255, 0.9) 0%, rgba(244, 249, 255, 0.88) 52%, rgba(238, 250, 243, 0.88) 100%);
  border: 1px solid rgba(217, 231, 247, 0.85);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
  overflow: hidden;
}

.about-pillars-section::before {
  content: "";
  position: absolute;
  top: -42px;
  right: -62px;
  width: 210px;
  height: 210px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(37, 99, 235, 0) 70%);
  pointer-events: none;
}

.about-pillars-section::after {
  content: "";
  position: absolute;
  bottom: -88px;
  left: -36px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(15, 118, 110, 0.13) 0%, rgba(15, 118, 110, 0) 72%);
  pointer-events: none;
}

.about-pillars-head h4 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 0.2px;
  color: #0b1220;
  position: relative;
  z-index: 1;
  text-align: center;
}

.about-pillars-eyebrow {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  margin-bottom: 10px;
  border-radius: 999px;
  border: 1px solid #cfe2f6;
  background: rgba(255, 255, 255, 0.8);
  color: #0f4f25;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  position: relative;
  z-index: 1;
}

.about-pillars-head {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.about-pillars-head p {
  margin: 8px 0 0;
  color: #334155;
  line-height: 1.6;
  max-width: 62ch;
  position: relative;
  z-index: 1;
  text-align: center;
}

.about-pillar {
  position: relative;
  z-index: 1;
  border-radius: 0;
  padding: 16px 10px 12px;
  border: 0;
  background: transparent;
  box-shadow: none;
  transition: none;
  overflow: visible;
}

.about-pillar::before {
  content: "";
  position: absolute;
  inset: auto auto 0 50%;
  width: 72px;
  height: 3px;
  background: currentColor;
  opacity: 0.85;
  border-radius: 999px;
  transform: translateX(-50%);
}

.about-pillar:hover {
  transform: none;
  box-shadow: none;
}

.about-pillar-heading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  text-align: center;
}

.about-pillar-title-wrap {
  min-width: 0;
  width: 100%;
}

.about-pillar h4 {
  margin: 2px 0 0;
  font-size: 1.18rem;
  font-weight: 800;
  line-height: 1.15;
  text-align: center;
  letter-spacing: 0.02em;
}

.about-pillar-subtitle {
  display: inline-block;
  margin-top: 5px;
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.3;
  letter-spacing: 0.02em;
  opacity: 0.78;
}

.about-pillar-icon {
  width: 56px;
  height: 56px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  animation: aboutPillarIconSpin 5.4s linear infinite;
  transform-origin: center;
  background: transparent;
  border: none;
  box-shadow: none;
}

.about-pillar-icon svg {
  width: 28px;
  height: 28px;
  filter: drop-shadow(0 6px 14px rgba(15, 23, 42, 0.14));
}

.about-pillar p {
  margin: 14px auto 0;
  color: #1f2937;
  line-height: 1.65;
  text-align: center;
  max-width: 36ch;
}

.about-pillar-mission {
  color: #0f766e;
  border-left: 0;
  background: transparent;
}

.about-pillar-mission h4 {
  color: #0f766e;
}

.about-pillar-mission .about-pillar-icon {
  color: #0f766e;
}

.about-pillar-mission .about-pillar-subtitle {
  color: #0f766e;
}

.about-pillar-vision {
  color: #1e40af;
  border-left: 0;
  background: transparent;
}

.about-pillar-vision h4 {
  color: #1e40af;
}

.about-pillar-vision .about-pillar-icon {
  color: #1e40af;
}

.about-pillar-vision .about-pillar-subtitle {
  color: #1e40af;
}

.about-pillar-vision .about-pillar-icon {
  animation-delay: 0.35s;
}

@keyframes aboutPillarIconSpin {
  0% {
    transform: rotate(0deg) scale(1);
  }
  100% {
    transform: rotate(360deg) scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .about-pillar-icon {
    animation: none;
  }
}

.about-gallery {
  margin-top: 20px;
  background: #ffffff;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.09);
  padding: 18px;
}

.about-gallery-head h4 {
  margin: 0;
  font-size: 1.08rem;
  color: #0f172a;
}

.about-gallery-head p {
  margin: 6px 0 0;
  color: #475569;
  font-size: 0.92rem;
}

.about-gallery-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.about-gallery-card {
  border: 1px solid #d2dfef;
  background: #f8fbff;
  padding: 0;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
}

.about-gallery-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
}

.about-gallery-card-active {
  border-color: #0f4f25;
  box-shadow: 0 10px 20px rgba(15, 79, 37, 0.2);
}

.about-gallery-image {
  display: block;
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
}

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
}

.social-link svg {
  width: 12px;
  height: 12px;
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

  .about-main {
    padding-top: 78px;
  }

  #about.section {
    padding: 34px 16px 14px;
  }

  .about-panel {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .about-image-wrap {
    padding: 24px 18px;
  }

  .about-copy {
    padding: 26px 18px 22px;
  }

  .about-pillars {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .about-pillars::before {
    display: none;
  }

  .about-pillars-section {
    padding: 18px;
  }

  .about-pillar {
    padding: 12px 2px 10px;
  }

  .about-pillar p {
    max-width: 100%;
  }

  .about-gallery-grid {
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

  .footer .about-group p {
    max-width: none;
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

  .about-main {
    padding-top: 72px;
  }

  .sign-in-btn {
    padding: 7px 10px;
    font-size: 0.7rem;
  }

  .nav-right .sign-in-btn {
    display: none;
  }

  .about-gallery-grid {
    grid-template-columns: 1fr;
  }

  .about-pillars-head h4 {
    font-size: 1.15rem;
  }

  .about-pillar h4 {
    font-size: 1.05rem;
  }

  .about-pillar-subtitle {
    font-size: 0.74rem;
  }

  .about-pillar-icon {
    width: 48px;
    height: 48px;
  }

  .about-pillar-icon svg {
    width: 24px;
    height: 24px;
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
</style>
