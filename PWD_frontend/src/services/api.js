function isPrivateIpv4(hostname) {
  const match = hostname.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/)
  if (!match) return false

  const parts = match.slice(1).map((value) => Number(value))
  if (parts.some((value) => Number.isNaN(value) || value < 0 || value > 255)) return false

  const [a, b] = parts
  if (a === 10) return true
  if (a === 127) return true
  if (a === 192 && b === 168) return true
  if (a === 172 && b >= 16 && b <= 31) return true
  return false
}

function inferDefaultApiBaseUrl() {
  if (typeof window === "undefined" || !window.location) return "http://127.0.0.1:8000/api"

  const { hostname } = window.location
  const isLocal =
    hostname === "localhost" ||
    hostname === "127.0.0.1" ||
    hostname === "0.0.0.0" ||
    hostname.endsWith(".local") ||
    isPrivateIpv4(hostname)

  if (isLocal) {
    const resolvedHost = hostname === "0.0.0.0" ? "127.0.0.1" : hostname
    return `http://${resolvedHost}:8000/api`
  }

  return `${window.location.origin.replace(/\/+$/, "")}/api`
}

function normalizeApiBaseUrl(value) {
  const raw = String(value || "").trim()
  if (!raw) return inferDefaultApiBaseUrl().replace(/\/+$/, "")

  let base = raw.replace(/\/+$/, "")
  try {
    const parsed = new URL(base)
    if (parsed.pathname === "" || parsed.pathname === "/") {
      base = `${base}/api`
    }
  } catch {
    // Ignore non-absolute URLs.
  }

  return base.replace(/\/+$/, "")
}

const API_BASE_URL = normalizeApiBaseUrl(import.meta.env.VITE_API_BASE_URL)

function buildUrl(path, params) {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`
  const url = new URL(`${API_BASE_URL}${normalizedPath}`)

  if (params && typeof params === "object") {
    Object.entries(params).forEach(([key, value]) => {
      if (value === undefined || value === null || value === "") return
      url.searchParams.set(key, String(value))
    })
  }

  return url.toString()
}

function parseJsonLoose(text) {
  if (!text) return null

  try {
    return JSON.parse(text)
  } catch {
    // Some PHP warnings can prepend/append noise to JSON.
    const firstObjectStart = text.indexOf("{")
    const lastObjectEnd = text.lastIndexOf("}")
    if (firstObjectStart >= 0 && lastObjectEnd > firstObjectStart) {
      try {
        return JSON.parse(text.slice(firstObjectStart, lastObjectEnd + 1))
      } catch {
        // continue
      }
    }

    const firstArrayStart = text.indexOf("[")
    const lastArrayEnd = text.lastIndexOf("]")
    if (firstArrayStart >= 0 && lastArrayEnd > firstArrayStart) {
      try {
        return JSON.parse(text.slice(firstArrayStart, lastArrayEnd + 1))
      } catch {
        // continue
      }
    }
  }

  return null
}

async function request(method, path, data, options = {}) {
  const { headers = {}, params, onUploadProgress } = options
  const url = buildUrl(path, params)

  let body
  const finalHeaders = { ...headers }

  if (data instanceof FormData) {
    body = data
    // Let the browser set multipart boundary automatically.
    if (finalHeaders["Content-Type"]) delete finalHeaders["Content-Type"]
    if (finalHeaders["content-type"]) delete finalHeaders["content-type"]
  } else if (data !== undefined) {
    body = JSON.stringify(data)
    if (!finalHeaders["Content-Type"]) {
      finalHeaders["Content-Type"] = "application/json"
    }
  }

  if (typeof onUploadProgress === "function") {
    onUploadProgress({ loaded: 1, total: 1 })
  }

  let response
  try {
    response = await fetch(url, {
      method,
      headers: finalHeaders,
      body,
    })
  } catch (cause) {
    const error = new Error(
      `Network error while requesting ${url}. Check that the API server is running and VITE_API_BASE_URL is correct.`,
    )
    error.cause = cause
    throw error
  }

  const text = await response.text()
  const json = parseJsonLoose(text)

  if (!response.ok) {
    const error = new Error(json?.message || `Request failed with status ${response.status}`)
    error.response = {
      status: response.status,
      data: json ?? text ?? null,
    }
    throw error
  }

  return {
    status: response.status,
    data: json ?? text ?? null,
  }
}

const api = {
  get(path, options = {}) {
    return request("GET", path, undefined, options)
  },
  post(path, data, options = {}) {
    return request("POST", path, data, options)
  },
  put(path, data, options = {}) {
    return request("PUT", path, data, options)
  },
  patch(path, data, options = {}) {
    return request("PATCH", path, data, options)
  },
  delete(path, options = {}) {
    return request("DELETE", path, undefined, options)
  },
}

export default api
