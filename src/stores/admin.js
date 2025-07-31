import { defineStore } from 'pinia'
import { apiService } from '../services/api'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    token: '',
    isAuthenticated: false,
    loading: false
  }),

  actions: {
    async login(password) {
      this.loading = true
      try {
        const response = await apiService.post('/admin-login.php', { password })
        if (response.success) {
          this.token = response.data.token
          this.isAuthenticated = true
          apiService.setAuthToken(this.token)
        }
        return response
      } catch (error) {
        return { success: false, message: '登录失败' }
      } finally {
        this.loading = false
      }
    },

    async getPendingApplications() {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.get('/pending-applications.php')
        return response
      } catch (error) {
        return { success: false, message: '获取待处理申请失败' }
      }
    },

    async handleApplication(id, approve) {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.post('/handle-application.php', { id, approve })
        return response
      } catch (error) {
        return { success: false, message: '处理申请失败' }
      }
    },

    async removeFromWhitelist(id) {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.post('/remove.php', { id })
        return response
      } catch (error) {
        return { success: false, message: '删除白名单失败' }
      }
    },

    async getTempRequests() {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.get('/temp-requests.php')
        return response
      } catch (error) {
        return { success: false, message: '获取临时登录请求失败' }
      }
    },

    async handleTempRequest(id, status) {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.post('/handle-temp-request.php', { id, status })
        return response
      } catch (error) {
        return { success: false, message: '处理临时登录请求失败' }
      }
    },

    async checkForUpdates() {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.get('/update.php?action=check')
        return response
      } catch (error) {
        return { success: false, message: '检查更新失败' }
      }
    },

    async performUpdate() {
      if (!this.isAuthenticated) return { success: false, message: '未登录' }
      
      try {
        const response = await apiService.get('/update.php?action=update')
        return response
      } catch (error) {
        return { success: false, message: '执行更新失败' }
      }
    },

    logout() {
      this.token = ''
      this.isAuthenticated = false
      apiService.setAuthToken('')
    }
  }
})