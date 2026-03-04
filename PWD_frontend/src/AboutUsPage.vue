<template>
  <div class="about-page">
    <header class="navbar" :class="{ 'navbar-scrolled': isScrolled }">
      <div class="nav-inner">
        <button class="nav-left" type="button" @click="$router.push('/landingpage')">
          <img :src="isScrolled ? logoScrolled : logoDefault" class="logo" alt="HireAble logo" />
        </button>

        <nav class="nav-center">
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
        <div class="about-intro-copy">
          <span class="about-badge">Who We Are</span>
          <h3>About Us</h3>
          <p>
            The Employment Assistance Platform for Persons with Disabilities is designed
            to connect qualified PWD job seekers with inclusive employers in the City of Dasmarinas.
          </p>
        </div>

        <article
          class="about-parallax-block"
          :style="sectionBgStyle(0)"
          aria-label="About platform overview"
        >
          <div class="about-parallax-overlay">
            <h4>Inclusive Employment Through Technology</h4>
            <p>
              With built-in decision support, the platform helps make hiring more accessible,
              fair, and data-guided for both applicants and organizations.
            </p>
          </div>
        </article>

        <article
          class="about-parallax-block"
          :style="sectionBgStyle(1)"
          aria-label="Mission"
        >
          <div class="about-parallax-overlay">
            <h4>Mission</h4>
            <p>
              To empower Persons with Disabilities by opening pathways to inclusive employment
              through an accessible, supportive, and opportunity-driven platform.
            </p>
          </div>
        </article>

        <article
          class="about-parallax-block"
          :style="sectionBgStyle(2)"
          aria-label="Vision"
        >
          <div class="about-parallax-overlay">
            <h4>Vision</h4>
            <p>
              A future where every qualified PWD is seen for their strengths and can access fair,
              meaningful, and sustainable work without barriers.
            </p>
          </div>
        </article>

        <div class="about-outro-copy">
          <p>
            Employers can post opportunities with clear accessibility details, while applicants
            receive role recommendations aligned with their capabilities, preferences, and qualifications.
          </p>
          <p>
            This approach supports inclusive growth by improving visibility of PWD talent,
            reducing hiring barriers, and creating a more connected local employment ecosystem.
          </p>
        </div>
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
      aboutObserver: null,
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
    this.setupAboutObserver();
  },
  beforeUnmount() {
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
    sectionBgStyle(index) {
      const image = this.aboutImages?.[index]?.src || this.aboutImages?.[0]?.src || "";
      return {
        backgroundImage: `linear-gradient(120deg, rgba(2, 6, 23, 0.52), rgba(15, 79, 37, 0.36)), url(${image})`
      };
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
  margin-bottom: 18px;
  padding: 38px 24px 22px;
  position: relative;
}

.about-intro-copy {
  margin: 0 auto 16px;
  max-width: 820px;
  text-align: center;
  padding: 20px 18px;
  border-radius: 16px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(180deg, #ffffff 0%, #f3f9f6 100%);
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
  opacity: 0;
  transform: translateY(22px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}

#about.about-visible .about-intro-copy {
  opacity: 1;
  transform: translateY(0);
}

.about-intro-copy p {
  margin: 10px 0 0;
  color: #334155;
  line-height: 1.65;
}

.about-parallax-block {
  min-height: 360px;
  border-radius: 18px;
  overflow: hidden;
  margin-top: 16px;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  background-attachment: fixed;
  border: 1px solid rgba(203, 213, 225, 0.65);
  box-shadow: 0 22px 36px rgba(15, 23, 42, 0.2);
  position: relative;
  display: grid;
  align-items: end;
}

.about-parallax-overlay {
  padding: 28px 24px 24px;
  background: linear-gradient(180deg, rgba(2, 6, 23, 0.08) 0%, rgba(2, 6, 23, 0.68) 65%, rgba(2, 6, 23, 0.82) 100%);
  color: #f8fafc;
}

.about-parallax-overlay h4 {
  margin: 0;
  font-size: clamp(1.5rem, 3vw, 2rem);
  line-height: 1.1;
}

.about-parallax-overlay p {
  margin: 10px 0 0;
  max-width: 62ch;
  line-height: 1.7;
  color: rgba(248, 250, 252, 0.95);
}

.about-outro-copy {
  margin-top: 16px;
  border-radius: 16px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  padding: 20px 18px;
}

.about-outro-copy p {
  margin: 0;
  color: #334155;
  line-height: 1.7;
}

.about-outro-copy p + p {
  margin-top: 12px;
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

.about-intro-copy h3 {
  margin: 2px 0 2px;
  font-size: 2rem;
  color: #0f172a;
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

  .about-parallax-block {
    min-height: 320px;
    background-attachment: scroll;
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

  .about-parallax-block {
    min-height: 286px;
  }

  .about-parallax-overlay {
    padding: 20px 14px 16px;
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
