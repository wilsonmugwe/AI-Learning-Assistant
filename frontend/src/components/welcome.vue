<template>
  <!-- Main container for the landing screen -->
  <div class="welcome-container">

    <!-- Motivational quote that sets the tone -->
    <blockquote class="quote">
      “Education is not the learning of facts,<br />
      but the training of the mind to think.”<br />
      — Albert Einstein
    </blockquote>

    <div class="content">
      <!-- Left side: title and subheading -->
      <div class="welcome-text">
        <h1>Welcome to AI Learning Assistant</h1>
        <p>Your intelligent assistant for learning and teaching.</p>
      </div>

      <!-- Right side: two main call-to-action buttons -->
      <div class="buttons">
        <!-- Teachers use this to upload materials -->
        <router-link to="/upload" class="btn">Teacher Upload</router-link>

        <!-- Students go here to ask questions based on the materials -->
        <router-link to="/ask" class="btn">Ask Question</router-link>
      </div>
    </div>

    <!-- Footer with app name and copyright -->
    <footer>
      <small>© 2025 AI Learning Assistant</small>
    </footer>
  </div>
</template>

<script>
// Just exporting the component and giving it a name for clarity
export default {
  name: "WelcomePage",
};
</script>

<style scoped>
/* Main container styling — full screen with gradient background */
.welcome-container {
  height: 100vh;
  width: 100vw;
  background: linear-gradient(135deg, #090015, #16002a); /* dark gradient */
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 2rem 5vw;
  box-sizing: border-box;
  overflow: hidden;
  position: relative;
}

/* Styling for the Einstein quote — subtle and elegant */
.quote {
  position: absolute;
  top: 2rem;
  left: 5vw;
  max-width: 300px;
  font-style: italic;
  color: #ffffffcc;
  border-left: 5px solid #7a55cc;
  padding-left: 1rem;
  line-height: 1.4;
  font-size: 1.1rem;
  user-select: none;
  opacity: 0.85;
  animation: fadeInUp 1.2s ease forwards;
}

/* Content section with text and buttons side by side */
.content {
  margin-top: 6rem;
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  padding-right: 13rem;
  padding-left: 0rem;
}

/* Left side text block */
.welcome-text {
  max-width: 70%;
}

.welcome-text h1 {
  font-size: 3.5rem;
  font-weight: 900;
  letter-spacing: 0.1rem;
  margin-bottom: 1rem;
  color: #d1bbff;
}

.welcome-text p {
  font-size: 1.4rem;
  color: #ffffffcc;
  max-width: 600px;
  user-select: none;
}

/* Right side buttons stacked vertically */
.buttons {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 180px;
  align-items: flex-end;
}

/* Shared styling for both buttons */
.btn {
  background-color: #3a1f6f;
  color: white;
  font-weight: 700;
  text-align: center;
  padding: 1.2rem 2rem;
  border-radius: 12px;
  font-size: 1.3rem;
  text-decoration: none;
  user-select: none;
  box-shadow: none;
  transition: all 0.3s ease;
  min-width: 180px;
}

/* Button hover effect to make it feel more interactive */
.btn:hover {
  background-color: #583594;
  color: #f4eefe;
  transform: scale(1.05);
  cursor: pointer;
}

/* Footer at the very bottom */
footer {
  text-align: center;
  padding: 1rem 0;
  font-size: 0.9rem;
  color: #8a79b9cc;
  user-select: none;
}

/* Animation for fading in the quote */
@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }

  100% {
    opacity: 0.85;
    transform: translateY(0);
  }
}
</style>
