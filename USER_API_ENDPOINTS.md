# User API Endpoints Documentation

This document outlines all API endpoints available for different user types: Public, Student, Teacher, and Admin.

---

## 🔓 PUBLIC ROUTES (No Authentication Required)

These routes are accessible to anyone without needing to be logged in.

### Landing Page & Browse Content

| Method | Endpoint                          | Description                         |
| ------ | --------------------------------- | ----------------------------------- |
| GET    | `/api/landing_page`               | Get landing page data               |
| GET    | `/api/subjects`                   | Get all subjects                    |
| GET    | `/api/subjects/{subject}/units`   | Get units for a specific subject    |
| GET    | `/api/units/{unit}/lessons`       | Get lessons for a specific unit     |
| GET    | `/api/lessons/{lesson}/subtopics` | Get subtopics for a specific lesson |

### Search & Browse Teachers

| Method | Endpoint                           | Description                     |
| ------ | ---------------------------------- | ------------------------------- |
| GET    | `/api/subjects/{subject}/teachers` | Get teachers for a subject      |
| GET    | `/api/teachers/{teacher}/lessons`  | Get lessons taught by a teacher |
| GET    | `/api/search/`                     | Search content by keywords      |

### Teacher Info

| Method | Endpoint              | Description           |
| ------ | --------------------- | --------------------- |
| GET    | `/api/test/{teacher}` | Test teacher endpoint |

---

## 🔐 AUTHENTICATION ROUTES (Public + Protected)

Authentication endpoints for user registration and login.

### Public (No Auth Required)

| Method | Endpoint        | Description                  | Rate Limit         |
| ------ | --------------- | ---------------------------- | ------------------ |
| POST   | `/api/register` | Register a new user          | 5 requests/minute  |
| POST   | `/api/login`    | Login user and get JWT token | 10 requests/minute |

### Protected (Auth Required)

| Method | Endpoint      | Description                 | Role                   |
| ------ | ------------- | --------------------------- | ---------------------- |
| POST   | `/api/logout` | Logout and invalidate token | Any authenticated user |
| GET    | `/api/me`     | Get current user profile    | Any authenticated user |
| PUT    | `/api/user`   | Update current user profile | Any authenticated user |

---

## 👨‍🎓 STUDENT ROUTES (Student Role Required)

Routes exclusively for students to track progress and access learning features.

### Dashboard & Progress

| Method | Endpoint                 | Description                               | Auth | Role    |
| ------ | ------------------------ | ----------------------------------------- | ---- | ------- |
| GET    | `/api/student/dashboard` | Get student dashboard with progress stats | ✅   | student |

### Quiz & Answers

| Method | Endpoint                      | Description                                   | Auth | Role    |
| ------ | ----------------------------- | --------------------------------------------- | ---- | ------- |
| POST   | `/api/quiz/{quiz}/answer`     | Submit answer to a quiz question              | ✅   | student |
| GET    | `/api/student/answers/{quiz}` | Get student answers and evaluation for a quiz | ✅   | student |

### Recommendations

| Method | Endpoint                                                              | Description                                 | Auth | Role    |
| ------ | --------------------------------------------------------------------- | ------------------------------------------- | ---- | ------- |
| GET    | `/api/student/recommendations/subtopics/{subtopic}`                   | Get AI recommendations for a subtopic       | ✅   | student |
| GET    | `/api/student/recommendations/subtopics/{subtopic}/questions`         | Get recommendation questions for a subtopic | ✅   | student |
| POST   | `/api/student/recommendations/subtopics/{subtopic}/questions/answers` | Submit answers to recommendation questions  | ✅   | student |

### AI Features

| Method   | Endpoint      | Description      | Auth     | Role |
| -------- | ------------- | ---------------- | -------- | ---- |
| GET/POST | `/api/aiTest` | Test AI features | Optional | -    |

---

## 👨‍🏫 TEACHER ROUTES (Teacher Role Required)

Routes for teachers to manage content, create quizzes, and track student progress.

### Dashboard & Access

| Method | Endpoint                                           | Description                            | Auth | Role    |
| ------ | -------------------------------------------------- | -------------------------------------- | ---- | ------- |
| GET    | `/api/teachers/dashboard`                          | Get teacher dashboard with class stats | ✅   | teacher |
| GET    | `/api/teachers/{teacher}/lessons/{lesson}/content` | Get lesson content details             | ✅   | -       |

### Video Management (CRUD)

| Method | Endpoint              | Description        | Auth | Role    |
| ------ | --------------------- | ------------------ | ---- | ------- |
| GET    | `/api/videos`         | List all videos    | ✅   | teacher |
| POST   | `/api/videos`         | Create a new video | ✅   | teacher |
| GET    | `/api/videos/{video}` | Get video details  | ✅   | teacher |
| PUT    | `/api/videos/{video}` | Update video       | ✅   | teacher |
| DELETE | `/api/videos/{video}` | Delete video       | ✅   | teacher |

### Quiz Management (CRUD)

| Method | Endpoint                      | Description                   | Auth | Role    |
| ------ | ----------------------------- | ----------------------------- | ---- | ------- |
| GET    | `/api/quizzes`                | List all quizzes              | ✅   | teacher |
| POST   | `/api/quizzes`                | Create a new quiz             | ✅   | teacher |
| GET    | `/api/quizzes/{quiz}`         | Get quiz details              | ✅   | teacher |
| PUT    | `/api/quizzes/{quiz}`         | Update quiz                   | ✅   | teacher |
| DELETE | `/api/quizzes/{quiz}`         | Delete quiz                   | ✅   | teacher |
| GET    | `/api/quizzes-details/{quiz}` | Get detailed quiz information | ✅   | -       |

### Student Evaluation

| Method | Endpoint                | Description                        | Auth | Role    |
| ------ | ----------------------- | ---------------------------------- | ---- | ------- |
| GET    | `/api/subtopic-answers` | Get student answers for evaluation | ✅   | teacher |

---

## 🛡️ ADMIN ROUTES (Admin Role Required)

Complete system administration and management endpoints. All require JWT auth and admin role.

### Dashboard & Analytics

| Method | Endpoint               | Description                                |
| ------ | ---------------------- | ------------------------------------------ |
| GET    | `/api/admin/dashboard` | Get admin dashboard with system statistics |

### User Management

| Method | Endpoint                                | Description               |
| ------ | --------------------------------------- | ------------------------- |
| GET    | `/api/admin/users`                      | List all users            |
| GET    | `/api/admin/users/statistics`           | Get user statistics       |
| GET    | `/api/admin/users/{userId}`             | Get specific user details |
| PUT    | `/api/admin/users/{userId}`             | Update user information   |
| DELETE | `/api/admin/users/{userId}`             | Delete a user             |
| POST   | `/api/admin/users/{userId}/assign-role` | Assign role to user       |
| POST   | `/api/admin/users/{userId}/remove-role` | Remove role from user     |
| GET    | `/api/admin/users/{userId}/activity`    | Get user activity logs    |

### Subject Management

| Method | Endpoint                                  | Description         |
| ------ | ----------------------------------------- | ------------------- |
| GET    | `/api/admin/content/subjects`             | List all subjects   |
| GET    | `/api/admin/content/subjects/{subjectId}` | Get subject details |
| DELETE | `/api/admin/content/subjects/{subjectId}` | Delete subject      |

### Quiz Management

| Method | Endpoint                              | Description      |
| ------ | ------------------------------------- | ---------------- |
| GET    | `/api/admin/content/quizzes`          | List all quizzes |
| GET    | `/api/admin/content/quizzes/{quizId}` | Get quiz details |
| DELETE | `/api/admin/content/quizzes/{quizId}` | Delete quiz      |

### Video Management

| Method | Endpoint                              | Description       |
| ------ | ------------------------------------- | ----------------- |
| GET    | `/api/admin/content/videos`           | List all videos   |
| GET    | `/api/admin/content/videos/{videoId}` | Get video details |
| DELETE | `/api/admin/content/videos/{videoId}` | Delete video      |

### Lesson Management

| Method | Endpoint                                | Description      |
| ------ | --------------------------------------- | ---------------- |
| GET    | `/api/admin/content/lessons`            | List all lessons |
| DELETE | `/api/admin/content/lessons/{lessonId}` | Delete lesson    |

### Content Statistics

| Method | Endpoint                        | Description                         |
| ------ | ------------------------------- | ----------------------------------- |
| GET    | `/api/admin/content/statistics` | Get content overview and statistics |

---

## 📋 AUTHENTICATION & AUTHORIZATION SUMMARY

| User Type     | Can Access                    | Key Features                                       |
| ------------- | ----------------------------- | -------------------------------------------------- |
| **Anonymous** | Public routes, Auth endpoints | Browse subjects, search, register/login            |
| **Student**   | Public + Student routes       | Take quizzes, view recommendations, track progress |
| **Teacher**   | Public + Teacher routes       | Create quizzes/videos, view student answers        |
| **Admin**     | All routes                    | Full system management, user/content control       |

---

## 🔑 JWT Token Requirements

- Include token in Authorization header: `Authorization: Bearer {token}`
- Token obtained from `/api/login` endpoint
- Token expires based on JWT configuration
- Use `/api/logout` to invalidate token

---

## 🚦 Rate Limiting

- Register: 5 requests per minute
- Login: 10 requests per minute
- Other endpoints: Default Laravel rate limiting

---

## 📝 Notes

- All responses follow REST conventions
- Timestamps are in UTC
- Pagination available on list endpoints (add ?page=N)
- Sorting available on list endpoints (add ?sort=column)
