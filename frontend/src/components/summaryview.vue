<template>
  <div class="summary-page">
    <!-- Navigation back to home -->
    <button class="back-btn" @click="$router.push('/')">Back to Home</button>

    <h1>Summary</h1>

    <!-- Loading state -->
    <div v-if="loading" class="loading-message">Loading summary...</div>

    <!-- Display content once loaded -->
    <div v-else>
      <!-- Bullet point summary -->
      <div v-if="shortSummary.length > 0" class="summary-section">
        <h2>Short Summary (Bullet Points)</h2>
        <ul class="bullet-list">
          <li v-for="(point, index) in shortSummary" :key="index">{{ point }}</li>
        </ul>
      </div>
      <!-- Message if no bullet summary available -->
      <div v-else class="error-message">No short summary available.</div>

      <!-- Full paragraph summary -->
      <div v-if="longSummary" class="full-summary-section">
        <h2>Full Summary</h2>
        <p class="full-summary-text">{{ longSummary }}</p>
      </div>
      <!-- Message if no full summary available -->
      <div v-else class="error-message">No full summary available.</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SummaryView",
  data() {
    return {
      loading: true,         // Controls whether loading message is shown
      longSummary: "",       // Stores the paragraph-style summary
      shortSummary: []       // Stores bullet points as an array of strings
    };
  },
  async created() {
    // Get material ID from the route parameter
    const id = this.$route.params.id;
    console.log("[DEBUG] Material ID:", id);

    try {
      // Base URL from environment file
      const baseUrl = import.meta.env.VITE_API_URL;

      // Make request to fetch summary data
      const response = await fetch(`${baseUrl}/summaries/${id}`);
      const result = await response.json();

      console.log("[DEBUG] Raw API response:", result);

      // If backend wraps the data in a 'data' object, extract it
      const summaryData = result.data || result;

      // Extract and clean the full summary
      this.longSummary = (summaryData.summary || summaryData.long_summary || "").trim();

      // Extract bullet-style summary string
      let rawBullets = summaryData.bullet_summary || summaryData.short_summary || "";

      // Convert escaped newline characters to real line breaks
      rawBullets = rawBullets.replace(/\\n/g, "\n");

      // Convert string to array, clean each line
      this.shortSummary = rawBullets
        .split(/\r?\n/)                                // Split on newlines
        .map(line => line.replace(/^[-•*]\s*/, "").trim()) // Remove bullet marks
        .filter(Boolean);                              // Remove empty lines
    } catch (error) {
      // Log any fetch or parsing errors
      console.error("[ERROR] Failed to load summary:", error);
      this.longSummary = "";
      this.shortSummary = [];
    } finally {
      // Stop showing loading message
      this.loading = false;
    }
  }
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
