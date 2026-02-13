import api from "@/services/api"

export async function sendOtp(email) {
  return api.post("/auth/otp/send", { email })
}

export async function verifyOtp(email, otp, mode = "") {
  return api.post("/auth/otp/verify", { email, otp, mode: mode || undefined })
}

export async function resetPassword(email, password, otp) {
  return api.post("/auth/password/reset", { email, password, otp })
}
