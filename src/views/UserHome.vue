<template>
  <div class="container">
    <h1 class="text-center main-title">RMS白名单管理系统</h1>
    
    <!-- 申请白名单表单 -->
    <div class="card mb-4">
      <div class="card-header">
        申请白名单
      </div>
      <div class="card-body">
        <form @submit.prevent="submitApplication">
          <div class="mb-3">
            <label for="username" class="form-label">玩家名称</label>
            <input 
              type="text" 
              class="form-control" 
              id="username" 
              v-model="username"
              required
            >
          </div>
          <button type="submit" class="btn btn-primary">提交申请</button>
        </form>
      </div>
    </div>

    <!-- 提示消息 -->
    <AlertMessage 
      :message="alertMessage" 
      :is-error="alertIsError" 
      :visible="alertVisible"
      @hide="hideAlert"
    />

    <!-- 申请列表 -->
    <div class="card mb-4">
      <div class="card-header">
        当前申请列表
        <button class="btn btn-sm btn-primary float-end" @click="refreshApplications">刷新</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>玩家名称</th>
                <th>申请时间</th>
                <th>状态</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in applications" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.username }}</td>
                <td>{{ item.created_at }}</td>
                <td :class="getStatusClass(item.status)">{{ getStatusText(item.status) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 白名单列表 -->
    <div class="card">
      <div class="card-header">
        当前白名单
        <button class="btn btn-sm btn-primary float-end" @click="refreshWhitelist">刷新</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>玩家名称</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in whitelist" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.username }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useWhitelistStore } from '../stores/whitelist'
import AlertMessage from '../components/AlertMessage.vue'

const store = useWhitelistStore()
const username = ref('')
const alertMessage = ref('')
const alertIsError = ref(false)
const alertVisible = ref(false)
const applications = ref([])
const whitelist = ref([])

const showAlert = (message, isError = false) => {
  alertMessage.value = message
  alertIsError.value = isError
  alertVisible.value = true
}

const hideAlert = () => {
  alertVisible.value = false
}

const getStatusClass = (status) => {
  return status === 'pending' ? 'text-warning fw-bold' : 'text-danger fw-bold'
}

const getStatusText = (status) => {
  return status === 'pending' ? '待处理' : '已拒绝'
}

const refreshApplications = async () => {
  try {
    const result = await store.getApplications()
    if (result.success) {
      applications.value = result.data
    } else {
      showAlert(result.message, true)
    }
  } catch (error) {
    showAlert('获取申请列表失败', true)
  }
}

const refreshWhitelist = async () => {
  try {
    const result = await store.getWhitelist()
    if (result.success) {
      whitelist.value = result.data
    } else {
      showAlert(result.message, true)
    }
  } catch (error) {
    showAlert('获取白名单列表失败', true)
  }
}

const submitApplication = async () => {
  try {
    const result = await store.submitApplication(username.value)
    showAlert(result.message, !result.success)
    
    if (result.success) {
      username.value = ''
      await refreshApplications()
    }
  } catch (error) {
    showAlert('提交申请失败', true)
  }
}

onMounted(async () => {
  await Promise.all([refreshWhitelist(), refreshApplications()])
})
</script>