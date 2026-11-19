import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

export const get = (url, params) => api.get(url, { params }).then((r) => r.data)
export const post = (url, data) => api.post(url, data).then((r) => r.data)
export const put = (url, data) => api.put(url, data).then((r) => r.data)
export const del = (url) => api.delete(url).then((r) => r.data)
