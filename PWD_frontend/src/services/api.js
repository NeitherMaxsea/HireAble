const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api").replace(/\/+$/, "")

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

  const response = await fetch(url, {
    method,
    headers: finalHeaders,
    body,
  })

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
