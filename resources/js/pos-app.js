import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosApp from './vue/PosApp.vue';

const app = createApp(PosApp);
const pinia = createPinia();

app.use(pinia);
app.mount('#pos-app');
