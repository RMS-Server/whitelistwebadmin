import axios from 'axios'

class ApiService {
  constructor() {
    this.client = axios.create({
      baseURL: '/api',
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json'
      }
    })

    this.client.interceptors.response.use(
      response => response.data,
      error => {
        console.error('API Error:', error)
        return Promise.reject(error)
      }
    )
  }

  setAuthToken(token) {
    if (token) {
      this.client.defaults.headers.Authorization = token
    } else {
      delete this.client.defaults.headers.Authorization
    }
  }

  async get(url, params = {}) {
    try {
      const response = await this.client.get(url, { params })
      return response
    } catch (error) {
      throw error
    }
  }

  async post(url, data = {}) {
    try {
      const response = await this.client.post(url, data)
      return response
    } catch (error) {
      throw error
    }
  }

  async put(url, data = {}) {
    try {
      const response = await this.client.put(url, data)
      return response
    } catch (error) {
      throw error
    }
  }

  async delete(url) {
    try {
      const response = await this.client.delete(url)
      return response
    } catch (error) {
      throw error
    }
  }
}

export const apiService = new ApiService()