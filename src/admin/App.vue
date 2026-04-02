<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const selectedKey = computed(() => {
  if (route.path.startsWith('/create')) return ['create']
  return ['settings']
})

const onMenuClick = ({ key }) => {
  router.push(key === 'create' ? '/create' : '/settings')
}
</script>

<template>
  <div class="pearpay-admin">
    <a-layout>
      <a-layout-sider width="220">
        <div style="padding: 16px; font-weight: 600;">Pear Pay</div>
        <a-menu :selectedKeys="selectedKey" mode="inline" @click="onMenuClick">
          <a-menu-item key="settings">Settings</a-menu-item>
          <a-menu-item key="create">Pay Options</a-menu-item>
        </a-menu>
      </a-layout-sider>
      <a-layout>
        <a-layout-content>
          <router-view />
        </a-layout-content>
      </a-layout>
    </a-layout>
  </div>
</template>

