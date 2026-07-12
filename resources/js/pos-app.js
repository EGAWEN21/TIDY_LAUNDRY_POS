import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosApp from './vue/PosApp.vue';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const app = createApp(PosApp);
const pinia = createPinia();

app.use(pinia);
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  theme: 'colored'
});
app.mount('#pos-app');
