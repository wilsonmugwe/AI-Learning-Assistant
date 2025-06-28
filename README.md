<<<<<<< HEAD
# AI Learning Assistant

AI Learning Assistant is a web-based education tool built using Laravel 11 (backend) and Vue 3 (frontend). It allows teachers to upload learning materials in PDF or plain-text format. The system then uses OpenAI to automatically generate a structured summary, which students can later read and interact with. Students can also ask follow-up questions, and the AI responds based on the uploaded content. The app separates frontend and backend for clarity, modularity, and maintainability.

The main features of this app include:
- Teacher uploads of `.pdf` or `.txt` files
- AI-generated summaries in bullet points and paragraphs
- Student access to summaries
- A chat system for students to ask content-specific questions
- AI answers based on the source material
- Cleanly separated Laravel + Vue project structure

The technology stack used in this project includes:
- Backend: Laravel 11
- Frontend: Vue 3 (Vite)
- AI Integration: OpenAI GPT model
- Database: MySQL or SQLite (for local testing)

To run the project locally, follow these setup steps:

Backend (Laravel):
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Frontend (Vue.js):
```bash
cd frontend
npm install
npm run dev
```



Environment Variables

Make sure to configure your environment variables. At minimum, your .env file in the backend should include:
OPENAI_API_KEY=your_openai_key_here


API Endpoints

| Method | Endpoint             | Description                              |
|--------|----------------------|------------------------------------------|
| POST   | /api/materials       | Upload a new learning material           |
| GET    | /api/materials       | Retrieve all uploaded materials          |
| GET    | /api/materials/{id}  | Get details and summary of a file        |
| POST   | /api/chat            | Submit a question and receive a response |




Project Structure

ai-learning-assistant/
|--- backend/     # Laravel backend for file upload, summarization, and Q&A
|---frontend/    # Vue frontend for teacher and student interfaces
|--- README.md
|--- .env.example


WALKTHROUGH
Teachers begin by uploading course materials through the web interface. Once uploaded, the backend processes the file and generates a summary using OpenAI. Students can then browse a clean list of uploaded materials, read the summaries, and ask follow-up questions via chat. The system responds intelligently using the uploaded document as context. This allows for a more interactive and accessible learning experience.

AUTHOR
Wilson Mugwe Gathii
Bachelor of Computer Science, Edith Cowan University
=======
# AI-learning-assistant
>>>>>>> e6f4c758fcc81e748d9586f18c87db9f295b02a8
