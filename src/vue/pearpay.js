// import { parse } from 'node-html-parser';
import { createApp  } from 'vue'
import * as Antd from 'ant-design-vue';

import apppay from './page-pay/Pearpay.vue'
import 'ant-design-vue/dist/reset.css';



  const apppayments = createApp({
      components: {
        apppay
      }
  })
  apppayments.use(Antd)
  apppayments.mount("#apppay");

