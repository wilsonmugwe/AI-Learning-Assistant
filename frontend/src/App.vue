<template>
  <!-- Root div for the entire Vue app -->
  <div id="app">
    <!-- This is where the current page/component will be rendered -->
    <!-- Vue Router swaps components in/out here based on the route -->
    <router-view />
  </div>
</template>

<script>
// Main App component — acts as the entry point for everything
export default {
  name: 'App', // Naming it helps with debugging and tools like Vue DevTools
};
</script>

<style>
/* Leaving this blank for now — might add some global styles later */
/* Could also move styling to individual components if needed */
</style>
