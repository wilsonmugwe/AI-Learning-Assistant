<template>
  <div class="app">
    <!-- Navigates back to home page -->
    <button class="back-btn" @click="$router.push('/')">Back</button>

    <h1>Ask a Question</h1>

    <!-- Dropdown for selecting a material -->
    <label for="material">Select Material</label>
    <select v-model="materialId" id="material">
      <option value="">-- Select material --</option>
      <!-- Each material comes from the backend -->
      <option v-for="m in materials" :key="m.id" :value="m.id">
        {{ m.title ? m.title : (m.filename ? m.filename : 'Untitled material') }}
      </option>
    </select>

    <!-- If no materials are available -->
    <p v-if="!loading && materials.length === 0" class="no-materials">
      No materials available. Please upload some first.
    </p>

    <!-- Show summary of the selected material -->
    <p v-if="selectedMaterial" class="material-summary">
      {{ selectedMaterial.summary || 'No summary available for this material.' }}
    </p>

    <!-- Textarea for user's question -->
    <label for="question">Your Question</label>
    <textarea
      id="question"
      placeholder="Type your question here..."
      v-model="question"
      rows="5"
    ></textarea>

    <!-- Button to send question to backend -->
    <button :disabled="!materialId || !question.trim()" @click="askQuestion" class="ask-btn">
      Ask
    </button>

    <!-- Spinner while waiting for response -->
    <div v-if="loading" class="loading-spinner" role="progressbar" aria-live="polite"></div>

    <!-- Show answer if available -->
    <div v-if="answer" class="answer">{{ answer }}</div>

    <!-- Show error if request fails -->
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
export default {
  name: "AskQuestion",
  data() {
    return {
      materials: [],      // Fetched list of materials
      materialId: "",     // Selected material ID
      question: "",       // User's question input
      answer: "",         // Response from AI
      error: "",          // Error message
      loading: false,     // Whether a request is in progress
    };
  },
  computed: {
    // Finds the full object of the selected material
    selectedMaterial() {
      return this.materials.find(m => m.id === this.materialId) || null;
    }
  },
  async mounted() {
    // Load all materials from the backend when page loads
    try {
      const res = await fetch("http://127.0.0.1:8000/api/summaries");
      if (!res.ok) throw new Error("Failed to load materials");
      this.materials = await res.json();
    } catch (err) {
      this.error = "Error loading materials";
      console.error(err);
    }
  },
  methods: {
    // Sends question + material ID to the backend
    async askQuestion() {
      this.answer = "";
      this.error = "";
      if (!this.materialId || !this.question.trim()) return;

      this.loading = true;

      try {
        const res = await fetch("http://127.0.0.1:8000/api/question", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            question: this.question,
            material_id: this.materialId,
          }),
        });

        if (!res.ok) throw new Error("API error");

        const data = await res.json();
        this.answer = data.answer || "No answer found.";
      } catch (err) {
        this.error = "Error fetching answer.";
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
/* Base app styles */
html, body, #app {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
  background-color: #0e0026;
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  overflow-x: hidden;
}

.app {
  min-height: 100vh;
  width: 100vw;
  background-color: #0e0026;
  color: white;
  padding: 2rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  margin-left: auto;
  margin-right: auto;
}

/* Back button */
.back-btn {
  align-self: flex-start;
  background-color: #5d3dbd;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
  margin-bottom: 1.5rem;
  transition: background-color 0.3s ease;
}
.back-btn:hover {
  background-color: #442a89;
}

/* Page heading */
h1 {
  color: #a566ff;
  font-weight: 700;
  font-size: 2.8rem;
  margin-bottom: 1.2rem;
  text-align: center;
}

/* Form labels */
label {
  font-weight: 600;
  color: #cbbcff;
  margin-bottom: 0.4rem;
  margin-top: 1.2rem;
  display: block;
}

/* Select and Textarea inputs */
select,
textarea {
  width: 100%;
  padding: 0.7rem 1rem;
  border-radius: 8px;
  border: 1.5px solid #4b3a8a;
  background-color: #24184f;
  color: white;
  font-size: 1rem;
  resize: vertical;
  transition: border-color 0.3s ease, background-color 0.3s ease;
  box-sizing: border-box;
}
select:hover,
textarea:hover {
  border-color: #a366ff;
  background-color: #3a2580;
  cursor: pointer;
}
select:focus,
textarea:focus {
  border-color: #c8aaff;
  background-color: #3a2580;
  outline: none;
}

/* Ask button */
.ask-btn {
  margin-top: 1.5rem;
  background-color: #a566ff;
  color: #1a0033;
  font-weight: 700;
  border: none;
  padding: 0.9rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s ease;
}
.ask-btn:disabled {
  background-color: #5b4b8b;
  cursor: not-allowed;
}
.ask-btn:hover:not(:disabled) {
  background-color: #7e57ff;
}

/* Summary of selected material */
.material-summary {
  margin-top: 0.7rem;
  font-style: italic;
  color: #d0bcff;
  font-size: 0.9rem;
  min-height: 3rem;
}

/* Message when there are no materials */
.no-materials {
  margin-top: 1rem;
  font-style: italic;
  color: #e2b7ff;
  text-align: center;
}

/* AI answer output */
.answer {
  margin-top: 1.8rem;
  background-color: #331a66;
  padding: 1.2rem;
  border-radius: 8px;
  font-size: 1.15rem;
  line-height: 1.5;
  color: #cbbcff;
  white-space: pre-wrap;
  max-height: 40vh;
  overflow-y: auto;
  box-sizing: border-box;
}

/* Error display */
.error {
  margin-top: 1rem;
  font-weight: 700;
  color: #ff6b6b;
  text-align: center;
}

/* Spinner for loading state */
.loading-spinner {
  margin: 2rem auto;
  border: 6px solid #24184f;
  border-top: 6px solid #a366ff;
  border-radius: 50%;
  width: 45px;
  height: 45px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg);}
  100% { transform: rotate(360deg);}
}
</style>
