// Import necessary functions from Vue Router
import { createRouter, createWebHistory } from 'vue-router';

// Import the components that map to routes
import Welcome from './components/welcome.vue';         // Landing page
import AskQuestion from './components/AskQuestion.vue'; // Question input and response view
import Upload from './components/Upload.vue';           // File/text upload screen
import SummaryView from './components/SummaryView.vue'; // View generated summary by ID

// Define the routes and their corresponding components
const routes = [
  { path: '/', name: 'Welcome', component: Welcome },                       // Home route
  { path: '/ask', name: 'AskQuestion', component: AskQuestion },           // Ask a question page
  { path: '/upload', name: 'Upload', component: Upload },                  // Upload material page
  { path: '/summary/:id', name: 'SummaryView', component: SummaryView },  // Dynamic route to view summary
];

// Create router instance with HTML5 history mode
const router = createRouter({
  history: createWebHistory(), // Uses browser history API (no hash in URL)
  routes,
});

// Export the router so it can be used in main.js
export default router;
