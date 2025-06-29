<template>
  <div class="summary-page">
    <!-- Navigation -->
    <button class="back-btn" @click="$router.push('/')">Back to Home</button>

    <h1>Summary</h1>

    <!-- Show while loading -->
    <div v-if="loading" class="loading-message">Loading summary...</div>

    <!-- Show summaries when loaded -->
    <div v-else>
      <!-- Bullet Summary -->
      <div v-if="shortSummary.length > 0" class="summary-section">
        <h2>Short Summary (Bullet Points)</h2>
        <ul class="bullet-list">
          <li v-for="(point, index) in shortSummary" :key="index">{{ point }}</li>
        </ul>
      </div>
      <div v-else class="error-message">No short summary available.</div>

      <!-- Paragraph Summary -->
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
      loading: true,          // Loading spinner control
      longSummary: "",        // Full paragraph-style summary
      shortSummary: []        // Bullet points as array
    };
  },
  async created() {
    const id = this.$route.params.id;
    console.log("[DEBUG] Material ID:", id);

    try {
      const baseUrl = import.meta.env.VITE_API_URL;
      const response = await fetch(`${baseUrl}/summaries/${id}`);
      const result = await response.json();

      console.log("[DEBUG] API Raw Response:", result);

      // In case API wraps data in `data` object
      const summaryData = result.data || result;

      // Handle full summary
      this.longSummary = (summaryData.summary || summaryData.long_summary || "").trim();
      console.log("[DEBUG] Long Summary:", this.longSummary);

      // Handle bullet summary (convert literal \n to real newlines)
      let rawBullets = summaryData.bullet_summary || summaryData.short_summary || "";
      rawBullets = rawBullets.replace(/\\n/g, '\n');  // Convert escaped newlines

      this.shortSummary = rawBullets
        .split(/\r?\n/)
        .map(line => line.replace(/^[-•*]\s*/, "").trim()) // Remove leading bullet chars
        .filter(Boolean);

      console.log("[DEBUG] Bullet Summary (array):", this.shortSummary);

    } catch (error) {
      console.error("[ERROR] Failed to fetch or process summary:", error);
      this.longSummary = "";
      this.shortSummary = [];
    } finally {
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
