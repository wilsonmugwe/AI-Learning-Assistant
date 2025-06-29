<template>
  <div class="summary-page">
    <!-- Navigation -->
    <button class="back-btn" @click="$router.push('/')">Back to Home</button>

    <h1>Summary</h1>

    <!-- Show while data is loading -->
    <div v-if="loading" class="loading-message">Loading summary...</div>

    <!-- After loading completes -->
    <div v-else>
      <!-- Bullet summary section -->
      <div v-if="shortSummary.length > 0" class="summary-section">
        <h2>Short Summary (Bullet Points)</h2>
        <ul class="bullet-list">
          <li v-for="(point, index) in shortSummary" :key="index">{{ point }}</li>
        </ul>
      </div>
      <div v-else class="error-message">No short summary available.</div>

      <!-- Full paragraph summary -->
      <div v-if="longSummary" class="full-summary-section">
        <h2>Full Summary</h2>
        <p class="full-summary-text">{{ longSummary }}</p>
      </div>
      <div v-else class="error-message">No full summary available.</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SummaryView",
  data() {
    return {
      loading: true,       // Show loading spinner
      longSummary: "",     // Full paragraph-style summary
      shortSummary: [],    // Bullet point summary
    };
  },
  async created() {
    // Get material ID from the URL route
    const id = this.$route.params.id;

    try {
      const baseUrl = import.meta.env.VITE_API_URL;
      const res = await fetch(`${baseUrl}/summaries/${id}`);
      const data = await res.json();

      console.log("[DEBUG] Summary API response:", data);

      // Try both possible keys for the full summary
      this.longSummary = (data.summary || data.long_summary || "").trim();

      // Handle bullet summary from either key
      const rawBullets = data.bullet_summary || data.short_summary || "";

      if (typeof rawBullets === "string" && rawBullets.trim().length > 0) {
        // Split by either \n or actual newline, remove dash markers, trim
        this.shortSummary = rawBullets
          .split(/\\n|[\n\r]/) // Covers \n and real line breaks
          .map(line => line.replace(/^[-•*]\s*/, "").trim()) // Strip bullets
          .filter(Boolean); // Remove any empty strings
      } else {
        this.shortSummary = [];
      }
    } catch (err) {
      // Log and reset if any issue occurs
      console.error("[ERROR] Failed to fetch summary:", err);
      this.shortSummary = [];
      this.longSummary = "";
    } finally {
      // Always stop loading spinner
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
  display: flex;
  flex-direction: column;
  align-items: center;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
}

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
  color: #caa6ff;
  margin-bottom: 1rem;
  font-size: 1.4rem;
}

.bullet-list {
  list-style-type: disc;
  padding-left: 1.5rem;
  line-height: 1.6;
  color: #f0e7ff;
}
.bullet-list li {
  margin-bottom: 0.5rem;
}

.full-summary-section {
  width: 100%;
  max-width: 1200px;
  margin-bottom: 2rem;
}

.full-summary-text {
  line-height: 1.6;
  color: #e0d2ff;
}

.loading-message {
  font-size: 1.2rem;
  color: #ccc;
  margin-top: 2rem;
}

.error-message {
  color: red;
  font-size: 1.2rem;
  margin-top: 2rem;
  text-align: center;
}
</style>
