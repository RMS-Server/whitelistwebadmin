<template>
  <div class="container">
    <!-- 登录表单 -->
    <div v-if="!adminStore.isAuthenticated" class="login-form-container">
      <h1 class="text-center main-title">RMS白名单管理系统</h1>
      <h2 class="text-center mb-4">管理员登录</h2>
      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="password" class="form-label">管理员口令</label>
          <input 
            type="password" 
            class="form-control" 
            id="password" 
            v-model="password"
            required
          >
        </div>
        <div class="text-center">
          <button type="submit" class="btn btn-primary" :disabled="adminStore.loading">
            {{ adminStore.loading ? '登录中...' : '登录' }}
          </button>
        </div>
        <AlertMessage 
          v-if="loginError" 
          :message="loginError" 
          :is-error="true" 
          :visible="true"
          @hide="loginError = ''"
        />
      </form>
    </div>

    <!-- 主要内容 -->
    <div v-else>
      <h1 class="text-center main-title">RMS白名单管理系统 - 管理员</h1>
      
      <!-- 提示消息 -->
      <AlertMessage 
        :message="alertMessage" 
        :is-error="alertIsError" 
        :visible="alertVisible"
        @hide="hideAlert"
      />
      
      <!-- 临时登录请求 -->
      <div class="card mb-4">
        <div class="card-header">
          <h2>临时登录请求</h2>
          <button class="btn btn-sm btn-primary" @click="refreshTempRequests">刷新</button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>玩家名称</th>
                  <th>请求时间</th>
                  <th>状态</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in tempRequests" :key="item.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ item.username }}</td>
                  <td>{{ item.request_time }}</td>
                  <td v-html="getStatusBadge(item.status)"></td>
                  <td>
                    <template v-if="item.status === 'pending'">
                      <button class="btn btn-sm btn-success me-2" @click="handleTempRequest(item.id, 'approved')">
                        同意
                      </button>
                      <button class="btn btn-sm btn-danger" @click="handleTempRequest(item.id, 'rejected')">
                        拒绝
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 改名提示卡片 -->
      <div class="mb-4 p-3 rename-tip">
        <p class="mb-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#FF9800" class="bi bi-exclamation-triangle-fill me-2" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
          </svg>
          <strong>提示：</strong>玩家改名需要重新添加白名单的，请记得删除之前的ID
        </p>
      </div>

      <!-- 待处理申请 -->
      <div class="card mb-4">
        <div class="card-header">
          <h2>待处理申请</h2>
          <button class="btn btn-sm btn-primary" @click="refreshApplications">刷新</button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>玩家名称</th>
                  <th>申请时间</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in applications" :key="item.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ item.username }}</td>
                  <td>{{ item.created_at }}</td>
                  <td>
                    <button class="btn btn-sm btn-success me-2" @click="handleApplication(item.id, true)">
                      同意
                    </button>
                    <button class="btn btn-sm btn-danger" @click="handleApplication(item.id, false)">
                      拒绝
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 白名单列表 -->
      <div class="card">
        <div class="card-header">
          <h2>白名单列表</h2>
          <button class="btn btn-sm btn-primary" @click="refreshWhitelist">刷新</button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>玩家名称</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in whitelist" :key="item.id">
                  <td>{{ item.index }}</td>
                  <td>{{ item.username }}</td>
                  <td>
                    <button class="btn btn-sm btn-danger" @click="removeWhitelist(item.id, item.username)">
                      删除
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAdminStore } from '../stores/admin'
import { useWhitelistStore } from '../stores/whitelist'
import AlertMessage from '../components/AlertMessage.vue'

const adminStore = useAdminStore()
const whitelistStore = useWhitelistStore()

const password = ref('')
const loginError = ref('')
const alertMessage = ref('')
const alertIsError = ref(false)
const alertVisible = ref(false)
const applications = ref([])
const whitelist = ref([])
const tempRequests = ref([])

const showAlert = (message, isError = false) => {
  alertMessage.value = message
  alertIsError.value = isError
  alertVisible.value = true
}

const hideAlert = () => {
  alertVisible.value = false
}

const handleLogin = async () => {
  const result = await adminStore.login(password.value)
  if (result.success) {
    loginError.value = ''
    await refreshAll()
  } else {
    loginError.value = result.message
    password.value = ''
  }
}

const refreshWhitelist = async () => {
  const result = await whitelistStore.getWhitelist()
  if (result.success) {
    whitelist.value = result.data
  } else {
    showAlert(result.message, true)
  }
}

const refreshApplications = async () => {
  const result = await adminStore.getPendingApplications()
  if (result.success) {
    applications.value = result.data
  } else {
    showAlert(result.message, true)
  }
}

const refreshTempRequests = async () => {
  const result = await adminStore.getTempRequests()
  if (result.success) {
    tempRequests.value = result.data
  } else {
    showAlert(result.message, true)
  }
}

const handleApplication = async (id, approve) => {
  const result = await adminStore.handleApplication(id, approve)
  showAlert(result.message, !result.success)
  
  if (result.success) {
    await refreshApplications()
    await refreshWhitelist()
  }
}

const removeWhitelist = async (id, username) => {
  if (!confirm(`确定要删除玩家 ${username} 的白名单吗？`)) {
    return
  }
  
  const result = await adminStore.removeFromWhitelist(id)
  showAlert(result.message, !result.success)
  
  if (result.success) {
    await refreshWhitelist()
  }
}

const handleTempRequest = async (id, status) => {
  const result = await adminStore.handleTempRequest(id, status)
  showAlert(result.message, !result.success)
  
  if (result.success) {
    await refreshTempRequests()
  }
}

const getStatusBadge = (status) => {
  const badges = {
    'pending': '<span class="badge bg-warning">等待中</span>',
    'approved': '<span class="badge bg-success">已批准</span>',
    'rejected': '<span class="badge bg-danger">已拒绝</span>',
    'timeout': '<span class="badge bg-secondary">已超时</span>'
  }
  return badges[status] || status
}


const refreshAll = async () => {
  await Promise.all([
    refreshWhitelist(),
    refreshApplications(),
    refreshTempRequests()
  ])
}

onMounted(() => {
  if (adminStore.isAuthenticated) {
    refreshAll()
  }
})
</script>

<style scoped>
.login-form-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  max-width: 500px;
  padding: 2rem;
  border-radius: 15px;
  background-color: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.login-form-container .main-title {
  color: #4CAF50;
  margin-bottom: 1.5rem;
  font-size: 2.5rem;
  font-weight: bold;
}

.login-form-container h2 {
  color: #333;
  margin-bottom: 2rem;
}

.login-form-container .form-control {
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(76, 175, 80, 0.3);
  padding: 0.8rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.login-form-container .form-control:focus {
  background: rgba(255, 255, 255, 1);
  border-color: #4CAF50;
  box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
  outline: none;
}

.login-form-container .btn-primary {
  background-color: #4CAF50;
  border: none;
  padding: 0.8rem 2rem;
  font-size: 1.1rem;
  margin-top: 1rem;
  border-radius: 8px;
  transition: all 0.3s ease;
  font-weight: 500;
}

.login-form-container .btn-primary:hover {
  background-color: #45a049;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.login-form-container .btn-primary:disabled {
  background-color: #a5d6a7;
  transform: none;
  box-shadow: none;
}

.card-header {
  position: relative;
  padding: 1rem 1.25rem;
}

.card-header h2 {
  margin: 0;
  display: inline-block;
  font-size: 1.5rem;
}

.card-header .btn {
  position: absolute;
  right: 1.25rem;
  top: 50%;
  transform: translateY(-50%);
}

.rename-tip {
  border: 1px solid #FF9800;
  border-radius: 10px;
  background-color: rgba(255, 243, 224, 0.8);
  backdrop-filter: blur(5px);
  transition: transform 0.3s, box-shadow 0.3s;
}

.rename-tip:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

/* 响应式支持 */
@media (max-width: 768px) {
  .login-form-container {
    width: calc(100% - 20px);
    max-width: 450px;
    padding: 1.5rem;
    left: 50%;
    transform: translate(-50%, -50%);
  }
  
  .login-form-container .main-title {
    font-size: 2rem !important;
  }
  
  .login-form-container .btn-primary {
    width: 100%;
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .login-form-container {
    width: calc(100% - 16px);
    max-width: 400px;
    padding: 1rem;
    left: 50%;
    transform: translate(-50%, -50%);
  }
  
  .login-form-container .main-title {
    font-size: 1.8rem !important;
  }
}
</style>