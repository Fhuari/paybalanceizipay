// import { parse } from 'node-html-parser';
import { createApp } from 'vue'
import * as Antd from 'ant-design-vue'
import Pearpay from './Pearpay.vue'
import 'ant-design-vue/dist/reset.css'

const roots = document.querySelectorAll('.pearpay-root')
roots.forEach((el) => {
  if (el.__pearpayMounted) return
  el.__pearpayMounted = true
  const lang = el.dataset.lang || 'en'
  const devmode = el.dataset.devmode || 'dev'
  const mode = el.dataset.mode || 'all'
  const app = createApp(Pearpay, { lang, devmode, mode })
  app.use(Antd)
  app.mount(el)
})
 

