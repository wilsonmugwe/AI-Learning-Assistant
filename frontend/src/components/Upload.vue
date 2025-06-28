<template>
  <div class="upload-container">

    <!-- Back to homepage -->
    <button class="back-btn" @click="$router.push('/')">Back</button>

    <h1>Upload Learning Material</h1>

    <!-- File Upload Section -->
    <section class="upload-section">
      <!-- File picker button -->
      <label for="fileInput" class="file-label">
        Choose PDF or Text File
        <input
          id="fileInput"
          type="file"
          accept=".pdf,.txt"
          @change="handleFileChange"
          ref="fileInput"
          hidden
        />
      </label>

      <!-- Drag and drop support -->
      <div
        class="drop-area"
        @dragover.prevent
        @drop.prevent="handleDrop"
        tabindex="0"
        @keydown.enter.prevent="triggerFilePicker"
        @click="triggerFilePicker"
        role="button"
        aria-label="Drag and drop or click to upload"
      >
        Drag and drop a file here, or click to select
      </div>

      <!-- Show selected file name -->
      <div v-if="fileName" class="file-name">Selected file: {{ fileName }}</div>

      <!-- Upload rules -->
      <small class="file-note">Accepted formats: .pdf, .txt | Max size: 5MB</small>
    </section>

    <!-- Optional text input -->
    <section class="content-section">
      <label for="textContent" class="content-label">
        Or paste your text content
      </label>
      <textarea
        id="textContent"
        v-model="textContent"
        placeholder="Paste your text content here..."
        rows="6"
      ></textarea>
    </section>

    <!-- Submit Button -->
    <button
      class="upload-btn"
      :disabled="!canUpload || uploading"
      @click="uploadFile"
    >
      {{ uploading ? 'Uploading and summarizing...' : 'Upload and Summarize' }}
    </button>

    <!-- Loading spinner -->
    <div v-if="uploading" class="progress-container">
      <div class="spinner"></div>
    </div>

    <!-- Upload error message -->
    <div v-if="uploadError" class="error-message">{{ uploadError }}</div>
  </div>
</template>

<script>
export default {
  name: "Upload",
  data() {
    return {
      file: null,            // File object
      fileName: "",          // File name for display
      textContent: "",       // Text content if no file
      uploading: false,      // Flag to show spinner and disable UI
      uploadError: ""        // Error message shown in UI
    };
  },
  computed: {
    // Enable upload button only if there’s a file or text
    canUpload() {
      return this.file !== null || this.textContent.trim().length > 0;
    }
  },
  methods: {
    // Open file picker manually
    triggerFilePicker() {
      this.$refs.fileInput.click();
    },

    // Handle file selection via input
    handleFileChange(event) {
      const selected = event.target.files[0];
      if (selected) {
        this.file = selected;
        this.fileName = selected.name;
        this.textContent = "";
        this.uploadError = "";
      }
    },

    // Handle drag-and-drop file upload
    handleDrop(event) {
      const file = event.dataTransfer.files[0];
      if (!file) return;

      const allowedTypes = ["application/pdf", "text/plain"];
      if (!allowedTypes.includes(file.type)) {
        this.uploadError = "Unsupported file type. Please upload a PDF or TXT file.";
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        this.uploadError = "File is too large. Maximum allowed size is 5MB.";
        return;
      }

      this.file = file;
      this.fileName = file.name;
      this.textContent = "";
      this.uploadError = "";
    },

    // Main upload logic
    async uploadFile() {
      if (!this.canUpload) return;

      this.uploading = true;
      this.uploadError = "";

      try {
        const formData = new FormData();
        if (this.file) {
          formData.append("file", this.file);
        } else {
          formData.append("content", this.textContent);
        }

        // Send data to backend endpoint
        const response = await fetch(`${import.meta.env.VITE_API_URL}/ai/upload-and-summarize`, {
          method: "POST",
          body: formData,
        });

        // Handle failed response
        if (!response.ok) {
          const error = await response.json().catch(() => ({}));
          throw new Error(error.message || "Upload failed. Please try again.");
        }

        const result = await response.json();
        const materialId = result.material_id;

        // Reset form after success
        this.file = null;
        this.fileName = "";
        this.textContent = "";
        this.$refs.fileInput.value = "";

        // Go to summary page
        this.$router.push(`/summary/${materialId}`);
      } catch (err) {
        this.uploadError = err.message;
      } finally {
        this.uploading = false;
      }
    }
  }
};
</script>

<style scoped>
.upload-container {
  height: 100vh;
  width: 100vw;
  background-color: #0b0020;
  color: white;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  box-sizing: border-box;
}

h1 {
  font-size: 2.2rem;
  font-weight: bold;
  color: #a366ff;
  margin: 0;
}

.back-btn {
  align-self: flex-start;
  background-color: #5d3dbd;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  margin-bottom: 1rem;
  font-weight: bold;
}
.back-btn:hover {
  background-color: #3b259c;
}

.upload-section,
.content-section {
  width: 100%;
  max-width: 600px;
  background-color: #1a0033;
  border-radius: 10px;
  padding: 1rem 1.5rem;
  box-sizing: border-box;
}

.file-label {
  display: block;
  background-color: #6b4aff;
  padding: 0.7rem 1.2rem;
  border-radius: 10px;
  color: white;
  font-weight: bold;
  cursor: pointer;
  text-align: center;
  margin-bottom: 0.8rem;
}
.file-label:hover {
  background-color: #4a33b8;
}

.drop-area {
  border: 2px dashed #4b2e83;
  border-radius: 10px;
  padding: 1.5rem;
  color: #d7b8ff;
  text-align: center;
  margin-bottom: 0.5rem;
  cursor: pointer;
}
.drop-area:hover {
  background-color: #2d1655;
}

.file-name {
  color: #d7b8ff;
  font-style: italic;
  margin-bottom: 0.5rem;
  text-align: center;
}

.file-note {
  font-size: 0.8rem;
  color: #aaa3d1;
  text-align: center;
  display: block;
}

textarea {
  width: 100%;
  background-color: #1a0033;
  border: 1.5px solid #4b2e83;
  border-radius: 10px;
  color: white;
  padding: 0.8rem;
  font-size: 1rem;
  resize: vertical;
}
textarea:focus {
  border-color: #a366ff;
  background-color: #331a66;
  outline: none;
}
textarea::placeholder {
  color: #cbbcff;
}

.upload-btn {
  width: 100%;
  max-width: 600px;
  background-color: #6b4aff;
  color: white;
  font-weight: bold;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.upload-btn:hover:not(:disabled) {
  background-color: #4a33b8;
}
.upload-btn:disabled {
  background-color: #3b1f99;
  cursor: not-allowed;
}

.error-message {
  max-width: 600px;
  color: #ff5c5c;
  font-weight: bold;
  text-align: center;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 4px solid #2b0e66;
  border-top: 4px solid #a366ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 1rem auto;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
