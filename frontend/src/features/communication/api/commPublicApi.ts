import axios from 'axios'

/** Public communication API — never attach office/panel auth tokens */
const commPublicApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  timeout: 25000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

commPublicApi.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error),
)

export default commPublicApi
