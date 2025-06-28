import { createApp } from 'vue';
import App from './App.vue';    // This will be the layout component now
import router from './router';  // import router

const app = createApp(App);
app.use(router);
app.mount('#app');
