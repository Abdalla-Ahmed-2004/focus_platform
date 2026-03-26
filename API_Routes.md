# API Routes Documentation

This document describes all the available API routes for the backend, including their purpose and usage.

## Public Routes (No Authentication Required)

### Authentication Routes

- **POST** `/register`
    - Description: Register a new user.
    - Rate limit: 5 requests per minute
    - **Request Body:**
        ```json
        {
            "name": "string (required, max 255 characters)",
            "email": "string (required, valid email, unique)",
            "password": "string (required, min 6 characters)",
            "password_confirmation": "string (required, must match password)",
            "teacher": "boolean (required if student is false)",
            "student": "boolean (required if teacher is false)",
            "subject_id": "integer (required if teacher is true, must exist)"
        }
        ```
        **Example:**
        ```json
        {
            "name": "John Teacher",
            "email": "john@example.com",
            "password": "password123",
            "password_confirmation": "password123",
            "teacher": true,
            "student": false,
            "subject_id": 1
        }
        ```

- **POST** `/login`
    - Description: Log in an existing user.
    - Rate limit: 10 requests per minute
    - **Request Body:**
        ```json
        {
            "email": "string (required, valid email)",
            "password": "string (required)"
        }
        ```
        **Example:**
        ```json
        {
            "email": "john@example.com",
            "password": "password123"
        }
        ```

### Content Browsing Routes (Student)

- **GET** `/subjects`
    - Description: Retrieve all subjects.

- **GET** `/subjects/{subject}/units`
    - Description: Retrieve all units for a specific subject.

- **GET** `/units/{unit}/lessons`
    - Description: Retrieve all lessons for a specific unit.

- **GET** `/lesson/{lesson}/subtopics`
    - Description: Retrieve all subtopics for a specific lesson.

- **GET** `/subjects/{subject}/teachers`
    - Description: Retrieve all teachers for a specific subject.

- **GET** `/teachers/{teacher}/lessons`
    - Description: Retrieve all lessons taught by a specific teacher.

- **GET** `/teachers/{teacher}/lessons/{lesson}/content`
    - Description: Retrieve the content of a specific lesson taught by a teacher.

---

## Protected Routes (Authentication Required)

All routes in this section require the `auth:api` middleware.

### Authentication Routes

- **POST** `/logout`
    - Description: Log out the current user.

- **GET** `/me`
    - Description: Get the current authenticated user's details.

- **PUT** `/user`
    - Description: Update the current user's profile.
    - **Request Body:**
        ```json
        {
            "name": "string (optional, max 255 characters)",
            "email": "string (optional, valid email, unique)",
            "password": "string (optional, min 8 characters)",
            "password_confirmation": "string (required if password is provided)"
        }
        ```
        **Example:**
        ```json
        {
            "name": "John Updated",
            "email": "john.new@example.com"
        }
        ```

### Quiz Attempt Routes (Student Role)

- **POST** `/attempts/{attempt}/answer`
    - Description: Submit an answer for a quiz attempt.
    - Requires: Student role
    - **Request Body:**
        ```json
        {
            "question_id": "integer (required, must exist)",
            "answer_text": "string (required)"
        }
        ```

- **POST** `/quiz/{quiz}/answer`
    - Description: Submit an answer for a quiz.
    - Requires: Student role
    - **Request Body:**
        ```json
        {
            "answers": [
                {
                    "question_id": "integer (required, must exist)",
                    "answer_text": "string (required)"
                }
            ]
        }
        ```
        **Example:**
        ```json
        {
            "answers": [
                {
                    "question_id": 1,
                    "answer_text": "Option A"
                },
                {
                    "question_id": 2,
                    "answer_text": "Option B"
                }
            ]
        }
        ```

- **GET** `/students/attempts`
    - Description: Retrieve all quiz attempts for the current student.
    - Requires: Student role

### Quiz Management Routes (Teacher Role)

- **API Resource** `/quizzes`
    - Description: Full CRUD operations for quizzes.
    - Requires: Teacher role
    - Methods:
        - **GET** `/quizzes`: Retrieve all quizzes
        - **POST** `/quizzes`: Create a new quiz
            - **Request Body:**
                ```json
                {
                    "video_id": "integer (required)",
                    "questions": [
                        {
                            "question": "string (required)",
                            "option": ["array of strings (required)"],
                            "correct_answer": "string (required)",
                            "subtopic_id": "integer (required, must exist)"
                        }
                    ]
                }
                ```
                **Example:**
                ```json
                {
                    "video_id": 1,
                    "questions": [
                        {
                            "question": "What is the capital of France?",
                            "option": ["Paris", "London", "Berlin", "Madrid"],
                            "correct_answer": "Paris",
                            "subtopic_id": 1
                        }
                    ]
                }
                ```
        - **GET** `/quizzes/{quiz}`: Retrieve a specific quiz
        - **PUT** `/quizzes/{quiz}`: Update a specific quiz
            - **Request Body:** Same as POST
        - **DELETE** `/quizzes/{quiz}`: Delete a specific quiz

### Video Management Routes (Teacher Role)

- **API Resource** `/videos`
    - Description: Full CRUD operations for videos.
    - Requires: Teacher role
    - Methods:
        - **GET** `/videos`: Retrieve all videos
        - **POST** `/videos`: Create a new video
            - **Request Body (form-data):**
                ```
                lesson_id: integer (required, must exist)
                title: string (required, max 255 characters)
                file: file (required, max 100MB, formats: mp4, avi, mov, wmv)
                ```
        - **GET** `/videos/{video}`: Retrieve a specific video
        - **PUT** `/videos/{video}`: Update a specific video
            - **Request Body:**
                ```
                lesson_id: integer (optional, must exist)
                title: string (optional, max 255 characters)
                file: file (optional, max 100MB, formats: mp4, avi, mov, wmv)
                ```
        - **DELETE** `/videos/{video}`: Delete a specific video

### Subject Routes

- **GET** `/quizzes-details/{quiz}`
    - Description: Retrieve detailed information about a specific quiz.

- **GET** `/subjects/{subject}/subtopics`
    - Description: Retrieve all subtopics for a specific subject.

---

### Notes

- Replace `{parameter}` with the actual ID or value for the resource.
- Use appropriate HTTP methods (GET, POST, PUT, DELETE) as specified.
- Teacher role required for content management (videos, quizzes).
- Student role required for quiz attempts.
- Throttle limits apply: Register (5/min), Login (10/min).
