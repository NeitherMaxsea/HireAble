import { createRouter, createWebHistory } from "vue-router"

// AUTH
import LoginPages from "@/Module/Authentication/login_pages.vue"
import RegisterPages from "@/Module/Authentication/register_pages.vue"
import RolePages from "@/Module/Authentication/roled-based-pages.vue"
import ForgetPass from "@/Module/Authentication/forgot-password-pages.vue"
import OtpForm from "@/Module/Authentication/form_otp_email_pages.vue"
import ResetPassword from "@/Module/Authentication/reset-password-pages.vue"

// LANDING PAGE
import LandingPage from "@/Landingpage.vue"

// ADMIN
import AdminLayout from "@/Module/Admin/layout/adminlayout.vue"
import AdminDashboard from "@/Module/Admin/views/Dashboard.vue"
import AdminUsers from "@/Module/Admin/views/Users.vue"
import AdminCompanyManagement from "@/Module/Admin/views/CompanyManagement.vue"
import AdminLogs from "@/Module/Admin/views/Logs.vue"

// EMPLOYER HR
import EmployerLayout from "@/Module/Employer/views/layout/EmployerLayout.vue"
import EmployerDashboard from "@/Module/Employer/views/HR/dashboard.vue"
import Applicant from "@/Module/Employer/views/HR/Applicant.vue"
import EmployeeRecord from "@/Module/Employer/views/HR/EmployeeRecord.vue"
import EmployerProfile from "@/Module/Employer/views/HR/Profile.vue"

// EMPLOYER OPERATIONS
import OperationsDashboard from "@/Module/Employer/views/Operation/OperationsDashboard.vue"
import AssignmentManagement from "@/Module/Employer/views/Operation/AssignmentManagement.vue"
import DeploymentScheduling from "@/Module/Employer/views/Operation/DeploymentScheduling.vue"
import TrainingManagement from "@/Module/Employer/views/Operation/TrainingManagement.vue"
import TrainingProgress from "@/Module/Employer/views/Operation/TrainingProgress.vue"
import WorkAssignment from "@/Module/Employer/views/Operation/WorkAssignment.vue"
import ReportsAnalytics from "@/Module/Employer/views/Operation/ReportsAnalytics.vue"
import EmployeeOperationsProfile from "@/Module/Employer/views/Operation/EmployeeProfile.vue"
import FinanceDashboard from "@/Module/Employer/views/Finance/FinanceDashboard.vue"

// COMPANY ADMIN
import CompanyAdminLayout from "@/Module/CompanyAdmin/layout/CompanyAdminLayout.vue"
import CompanyAdminDashboard from "@/Module/CompanyAdmin/views/Dashboard.vue"
import CompanyAdminAddEmployee from "@/Module/CompanyAdmin/views/AddEmployee.vue"
import CompanyAdminLogs from "@/Module/CompanyAdmin/views/Logs.vue"

// APPLICANT
import ApplicantLayout from "@/Module/Applicant/views/layout/ApplicantLayout.vue"
import ApplicantDashboard from "@/Module/Applicant/views/job_list.vue"

// Auth helper function
function isUserAuthenticated() {
  return !!(
    localStorage.getItem("userUid") ||
    localStorage.getItem("uid") ||
    localStorage.getItem("userEmail")
  )
}

// Get user role from localStorage
function getUserRole() {
  const role = localStorage.getItem("userRole")
  if (!role) return null
  return String(role).trim().toLowerCase().replace(/[\s-]+/g, "_")
}

function getHomeRouteByRole(role, fromRoute = null) {
  if (role === "applicant") return { path: "/applicant/job_list" }
  if (role === "admin") return { path: "/admin/dashboard" }
  if (role === "company_admin") return { path: "/company-admin/dashboard" }
  if (role === "employer") return { path: "/employer/HR/dashboard" }

  // If role is missing/unknown but user came from an authenticated route,
  // keep them inside the protected area instead of sending to landing page.
  if (fromRoute?.meta?.requiresAuth && fromRoute?.fullPath) {
    return { path: fromRoute.fullPath }
  }

  return { name: "LandingPage" }
}

const routes = [
  { path: "/", redirect: "/landingpage" },

  // ================= LANDING =================
  {
    path: "/landingpage",
    name: "LandingPage",
    component: LandingPage,
    meta: { title: "PWD Job Portal | Home", requiresAuth: false },
  },
  {
    path: "/find-jobs",
    redirect: "/applicant/job_list",
  },

  // ================= AUTH =================
  {
    path: "/login",
    name: "Login",
    component: LoginPages,
    meta: { title: "PWD Job Portal | Login", requiresAuth: false },
  },
  {
    path: "/register",
    name: "Register",
    component: RegisterPages,
    meta: { title: "PWD Job Portal | Register", requiresAuth: false },
  },
  {
    path: "/role",
    name: "Role",
    component: RolePages,
    meta: { title: "PWD Job Portal | Role Selection", requiresAuth: false },
  },
  {
    path: "/auth/forget-password",
    name: "ForgetPassword",
    component: ForgetPass,
    meta: { title: "PWD Job Portal | Forget Password", requiresAuth: false },
  },
  {
    path: "/auth/otp",
    name: "OtpForm",
    component: OtpForm,
    meta: { title: "PWD Job Portal | OTP Verification", requiresAuth: false },
  },
  {
    path: "/auth/reset-password",
    name: "ResetPassword",
    component: ResetPassword,
    meta: { title: "PWD Job Portal | Reset Password", requiresAuth: false },
  },

  // ================= ADMIN =================
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAuth: true, requiredRole: "admin" },
    children: [
      {
        path: "dashboard",
        name: "AdminDashboard",
        component: AdminDashboard,
        meta: {
          title: "PWD Job Portal | Admin Dashboard",
          requiresAuth: true,
          requiredRole: "admin",
        },
      },
      {
        path: "users",
        name: "AdminUsers",
        component: AdminUsers,
        meta: {
          title: "PWD Job Portal | Admin Users",
          requiresAuth: true,
          requiredRole: "admin",
        },
      },
      {
        path: "company-management",
        name: "AdminCompanyManagement",
        component: AdminCompanyManagement,
        meta: {
          title: "PWD Job Portal | Company Management",
          requiresAuth: true,
          requiredRole: "admin",
        },
      },
      {
        path: "logs",
        name: "AdminLogs",
        component: AdminLogs,
        meta: {
          title: "PWD Job Portal | Admin Logs",
          requiresAuth: true,
          requiredRole: "admin",
        },
      },
    ],
  },

  // ================= EMPLOYER HR =================
  {
    path: "/employer/HR",
    component: EmployerLayout,
    meta: { requiresAuth: true, requiredRole: "employer" },
    children: [
      {
        path: "dashboard",
        name: "EmployerDashboard",
        component: EmployerDashboard,
        meta: {
          title: "PWD Job Portal | Employer Dashboard",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "applicant-lists",
        name: "Applicant",
        component: Applicant,
        meta: {
          title: "PWD Job Portal | Applicant Lists",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "employer-records",
        name: "EmployeeRecord",
        component: EmployeeRecord,
        meta: {
          title: "PWD Job Portal | Employee Records",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "profile",
        name: "EmployerProfile",
        component: EmployerProfile,
        meta: {
          title: "PWD Job Portal | Employer Profile",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
    ],
  },

  // ================= EMPLOYER OPERATIONS =================
  {
    path: "/employer/operation",
    meta: { requiresAuth: true, requiredRole: "employer" },
    children: [
      {
        path: "dashboard",
        name: "OperationsDashboard",
        component: OperationsDashboard,
        meta: {
          title: "PWD Job Portal | Operations Dashboard",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "assignment-management",
        name: "AssignmentManagement",
        component: AssignmentManagement,
        meta: {
          title: "PWD Job Portal | Assignment Management",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "deployment-scheduling",
        name: "DeploymentScheduling",
        component: DeploymentScheduling,
        meta: {
          title: "PWD Job Portal | Deployment Scheduling",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "training-management",
        name: "TrainingManagement",
        component: TrainingManagement,
        meta: {
          title: "PWD Job Portal | Training Management",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "training-progress",
        name: "TrainingProgress",
        component: TrainingProgress,
        meta: {
          title: "PWD Job Portal | Training Progress",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "work-assignment",
        name: "WorkAssignment",
        component: WorkAssignment,
        meta: {
          title: "PWD Job Portal | Work Assignment",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "reports-analytics",
        name: "ReportsAnalytics",
        component: ReportsAnalytics,
        meta: {
          title: "PWD Job Portal | Reports & Analytics",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
      {
        path: "employee-profile",
        name: "EmployeeOperationsProfile",
        component: EmployeeOperationsProfile,
        meta: {
          title: "PWD Job Portal | Employee Profile",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
    ],
  },

  // ================= EMPLOYER FINANCE =================
  {
    path: "/employer/finance",
    meta: { requiresAuth: true, requiredRole: "employer" },
    children: [
      {
        path: "dashboard",
        name: "FinanceDashboard",
        component: FinanceDashboard,
        meta: {
          title: "PWD Job Portal | Finance Dashboard",
          requiresAuth: true,
          requiredRole: "employer",
        },
      },
    ],
  },

  // ================= COMPANY ADMIN =================
  {
    path: "/company-admin",
    component: CompanyAdminLayout,
    meta: { requiresAuth: true, requiredRole: "company_admin" },
    children: [
      {
        path: "dashboard",
        name: "CompanyAdminDashboard",
        component: CompanyAdminDashboard,
        meta: {
          title: "PWD Job Portal | Company Admin Dashboard",
          requiresAuth: true,
          requiredRole: "company_admin",
        },
      },
      {
        path: "employers-management",
        name: "CompanyAdminEmployersManagement",
        component: CompanyAdminAddEmployee,
        meta: {
          title: "PWD Job Portal | Employers Management",
          requiresAuth: true,
          requiredRole: "company_admin",
        },
      },
      {
        path: "add-employee",
        name: "CompanyAdminAddEmployee",
        component: CompanyAdminAddEmployee,
        meta: {
          title: "PWD Job Portal | Add Employee",
          requiresAuth: true,
          requiredRole: "company_admin",
        },
      },
      {
        path: "logs",
        name: "CompanyAdminLogs",
        component: CompanyAdminLogs,
        meta: {
          title: "PWD Job Portal | Company Admin Logs",
          requiresAuth: true,
          requiredRole: "company_admin",
        },
      },
    ],
  },

  // ================= APPLICANT =================
  {
    path: "/applicant",
    component: ApplicantLayout,
    meta: { requiresAuth: true, requiredRole: "applicant" },
    children: [
      {
        path: "job_list",
        name: "ApplicantDashboard",
        component: ApplicantDashboard,
        meta: {
          title: "PWD Job Portal | Applicant Dashboard",
          requiresAuth: true,
          requiredRole: "applicant",
        },
      },
      {
        path: "profile",
        name: "ApplicantProfile",
        component: EmployerProfile,
        meta: {
          title: "PWD Job Portal | Applicant Profile",
          requiresAuth: true,
          requiredRole: "applicant",
        },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Route guard for authentication
router.beforeEach((to, from, next) => {
  const isAuthenticated = isUserAuthenticated()
  const userRole = getUserRole()
  const requiresAuth = to.meta.requiresAuth
  const requiredRole = to.meta.requiredRole
  const forceAuthPage = String(to.query?.force || "") === "1"

  // Set page title
  document.title = to.meta.title || "PWD Job Portal"

  // If route requires authentication
  if (requiresAuth) {
    if (!isAuthenticated) {
      // User not logged in, redirect to login
      next({ name: "Login", query: { redirect: to.fullPath } })
    } else if (
      requiredRole &&
      ((Array.isArray(requiredRole) && !requiredRole.includes(userRole)) ||
        (!Array.isArray(requiredRole) && userRole !== requiredRole))
    ) {
      // User logged in but doesn't have required role
      next({ name: "LandingPage" })
    } else {
      // User is authenticated and has required role
      next()
    }
  } else {
    // Route doesn't require authentication
    const authPages = [
      "Login",
      "Register",
      "Role",
      "ForgetPassword",
      "OtpForm",
      "ResetPassword",
    ]

    if (!isAuthenticated) {
      next()
      return
    }

    // Never allow authenticated users to go back to Login via history.
    if (to.name === "Login") {
      next(getHomeRouteByRole(userRole, from))
      return
    }

    // Keep explicit force behavior for other auth pages when needed.
    if (authPages.includes(to.name) && !forceAuthPage) {
      next(getHomeRouteByRole(userRole, from))
      return
    }

    next()
  }
})

export default router
