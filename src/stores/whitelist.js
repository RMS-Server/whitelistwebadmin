import { defineStore } from 'pinia'
import { apiService } from '../services/api'

export const useWhitelistStore = defineStore('whitelist', {
  state: () => ({
    whitelist: [],
    applications: [],
    loading: false
  }),

  actions: {
    async getWhitelist() {
      this.loading = true
      try {
        const response = await apiService.get('/list.php')
        return response
      } catch (error) {
        return { success: false, message: '获取白名单失败' }
      } finally {
        this.loading = false
      }
    },

    async getApplications() {
      this.loading = true
      try {
        const response = await apiService.get('/applications.php')
        return response
      } catch (error) {
        return { success: false, message: '获取申请列表失败' }
      } finally {
        this.loading = false
      }
    },

    async submitApplication(username) {
      this.loading = true
      try {
        const response = await apiService.post('/apply.php', { username })
        return response
      } catch (error) {
        return { success: false, message: '提交申请失败' }
      } finally {
        this.loading = false
      }
    }
  }
})