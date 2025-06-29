<template>
  <div class="summary-page">
    <button class="back-btn" @click="$router.push('/')">Back to Home</button>

    <h1>Summary Page</h1>

    <div v-if="loading" class="loading-message">
      [LOADING] Fetching summary data...
    </div>

    <div v-else>
      <div v-if="error" class="error-message">[ERROR] {{ error }}</div>

      <div v-if="!error && shortSummary.length > 0" class="summary-section">
        <h2>Short Summary (Bullet Points)</h2>
        <ul class="bullet-list">
          <li v-for="(point, index) in shortSummary" :key="index">• {{ point }}</li>
        </ul>
      </div>
      <div v-else-if="!error" class="error-message">[INFO] No short summary available.</div>

      <div v-if="!error && longSummary" class="full-summary-section">
        <h2>Full Summary</h2>
        <p class="full-summary-text">{{ longSummary }}</p>
      </div>
      <div v-else-if="!error" class="error-message">[INFO] No full summary available.</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SummaryView",
  data() {
    return {
      loading: true,
      longSummary: "",
      shortSummary: [],
      error: null,
    };
  },
  async created() {
    const id = this.$route.params.id;
    if (!id) {
      this.error = "No ID provided in route.";
      this.loading = false;
      return;
    }

    try {
      const baseUrl = import.meta.env.VITE_API_URL;
      const endpoint = `${baseUrl}/summaries/${id}`;
      const response = await fetch(endpoint);

      if (!response.ok) {
        throw new Error(`[HTTP ERROR] ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();
      const data = result.data || result;

      this.longSummary = (data.summary || "").trim();

      const raw = data.bullet_summary;
      if (Array.isArray(raw)) {
        this.shortSummary = raw.filter(item => typeof item === 'string' && item.trim() !== '');
      } else {
        this.shortSummary = [];
      }

    } catch (err) {
      this.error = err.message;
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
  top: 1rem;
  left: 2rem;
  background-color: #5d3dbd;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.3s ease;
}
.back-btn:hover {
  background-color: #3b259c;
}

h1 {
  color: #a366ff;
  font-size: 2rem;
  margin: 2rem 0;
}

.summary-section,
.full-summary-section {
  background-color: #1a0033;
  border-radius: 12px;
  padding: 1.5rem;
  margin: 1rem 0;
  width: 100%;
  max-width: 900px;
  box-shadow: 0 0 10px rgba(163, 102, 255, 0.15);
}

h2 {
  color: #caa6ff;
  margin-bottom: 1rem;
}

.bullet-list {
  list-style-type: disc;
  padding-left: 1.5rem;
  color: #f0e7ff;
}

.bullet-list li {
  margin-bottom: 0.6rem;
}

.full-summary-text {
  color: #e0d2ff;
  line-height: 1.6;
}

.loading-message,
.error-message {
  color: #ff9999;
  font-size: 1.2rem;
  text-align: center;
  margin-top: 2rem;
}
</style>
