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
      loading: true,           // Indicates loading state
      longSummary: "",         // Full paragraph-style summary
      shortSummary: []         // Parsed bullet point array
    };
  },
  async created() {
    const id = this.$route.params.id;
    console.log("[DEBUG] Material ID:", id);

    try {
      const baseUrl = import.meta.env.VITE_API_URL;
      const response = await fetch(`${baseUrl}/summaries/${id}`);
      const result = await response.json();

      console.log("[DEBUG] Raw response from API:", result);

      // Directly use long_summary string (if present)
      this.longSummary = (result.long_summary || "").trim();

      // Handle bullet-style summary as a raw string
      let rawBullets = result.short_summary || "";

      // If bullet string uses literal '\n', convert to real line breaks
      rawBullets = rawBullets.replace(/\\n/g, '\n');

      // Convert the raw bullet string to an array of cleaned lines
      this.shortSummary = rawBullets
        .split(/\r?\n/) // split on newlines
        .map(line => line.replace(/^[-•*]\s*/, "").trim()) // remove bullets like - or •
        .filter(Boolean); // remove empty lines

      console.log("[DEBUG] Final bullet summary:", this.shortSummary);

    } catch (error) {
      console.error("[ERROR] Failed to load summary:", error);
      this.longSummary = "";
      this.shortSummary = [];
    } finally {
      this.loading = false;
    }
  }
};
</script>
