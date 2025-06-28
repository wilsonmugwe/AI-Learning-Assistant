<template>
  <div class="summary-page">
    <!-- Button to go back to the homepage -->
    <button class="back-btn" @click="$router.push('/')">Back to Home</button>

    <h1>Summary</h1>

    <!-- While data is being fetched, show loading message -->
    <div v-if="loading" class="loading-message">Loading summary...</div>

    <div v-else>
      <!-- Short summary: bullet points (if available) -->
      <div v-if="shortSummary.length > 0" class="summary-section">
        <h2>Short Summary (Bullet Points)</h2>
        <ul class="bullet-list">
          <li v-for="(point, index) in shortSummary" :key="index">{{ point }}</li>
        </ul>
      </div>

      <!-- If no short summary, show fallback message -->
      <div v-else class="error-message">No short summary available.</div>

      <!-- Long summary: full paragraph version (if available) -->
      <div v-if="longSummary" class="full-summary-section">
        <h2>Full Summary</h2>
        <p class="full-summary-text">{{ longSummary }}</p>
      </div>

      <!-- If no long summary, show fallback message -->
      <div v-else class="error-message">No full summary available.</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SummaryView",
  data() {
    return {
      loading: true,        // Show loading state until fetch is complete
      longSummary: "",      // Full paragraph summary
      shortSummary: [],     // Bullet point summary
    };
  },
  async created() {
    // Grab the ID from the route params
    const id = this.$route.params.id;
    try {
      // Fetch summary data from backend
      const res = await fetch(`http://localhost:8000/api/summaries/${id}`);
      const data = await res.json();

      console.log("Fetched summary data:", data);

      // Assign long summary text (fallback to empty string)
      this.longSummary = data.long_summary || "";

      // Clean and assign short summary if it's a valid array
      if (Array.isArray(data.short_summary) && data.short_summary.length > 0) {
        this.shortSummary = data.short_summary
          .map(line => line.trim())
          .filter(Boolean); // remove empty lines
      } else {
        this.shortSummary = [];
      }

    } catch (err) {
      // Handle error (already logged)
      console.error("Failed to load summary:", err);
    } finally {
      // Stop showing loading state no matter what
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.summary-page {
  background-color: #0b0020;
  color: white;
  min-height: 100vh;
  padding: 2rem;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.back-btn {
  position: absolute;
  top: 1.5rem;
  left: 2rem;
  background-color: #5d3dbd;
  color: white;
  border: none;
  padding: 0.5rem 1.2rem;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.back-btn:hover {
  background-color: #3b259c;
}

h1 {
  font-size: 2.2rem;
  color: #a366ff;
  margin-bottom: 2rem;
  text-align: center;
}

/* Box around bullet summary */
.summary-section {
  width: 100%;
  max-width: 1200px;
  background-color: #1a0033;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 0 10px rgba(163, 102, 255, 0.1);
}

h2 {
  margin-bottom: 1rem;
  color: #caa6ff;
  font-size: 1.4rem;
}

/* Bullet list formatting */
.bullet-list {
  list-style-type: disc;
  padding-left: 1.5rem;
  line-height: 1.6;
  color: #f0e7ff;
}
.bullet-list li {
  margin-bottom: 0.5rem;
}

/* Full paragraph summary section */
.full-summary-section {
  width: 100%;
  max-width: 1200px;
  margin-bottom: 2rem;
}

.full-summary-section h2 {
  color: #caa6ff;
  font-size: 1.4rem;
  margin-bottom: 0.5rem;
}

.full-summary-text {
  line-height: 1.6;
  color: #e0d2ff;
  padding: 0;
  background: none;
  border: none;
  margin: 0;
}

/* While loading content */
.loading-message {
  font-size: 1.2rem;
  color: #ccc;
  margin-top: 2rem;
}

/* For fallback or API failure messages */
.error-message {
  color: red;
  font-size: 1.2rem;
  margin-top: 2rem;
  text-align: center;
}
</style>
