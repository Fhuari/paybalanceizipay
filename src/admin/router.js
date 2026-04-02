import { createRouter, createWebHashHistory } from 'vue-router'
import SettingsView from './views/Settings.vue'
import CreateView from './views/Create.vue'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', redirect: '/settings' },
    { path: '/settings', component: SettingsView },
    { path: '/create', component: CreateView },
  ],
})

export default router

