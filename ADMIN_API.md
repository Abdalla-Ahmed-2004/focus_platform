# Admin API Documentation

## Overview

The admin API provides comprehensive management capabilities for the Focus Platform, including user management, content management, and analytics/monitoring.

## Authentication

All admin endpoints require:

- **JWT Bearer Token** in Authorization header
- **Admin Role** assigned to the authenticated user

Example:

```
Authorization: Bearer your_jwt_token_here
```

## User Management API

### 1. List All Users

**Endpoint:** `GET /api/admin/users`

**Query Parameters:**

- `role` (optional): Filter by role - `admin`, `teacher`, `student`
- `search` (optional): Search by name or email
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number (default: 1)

**Example Request:**

```bash
curl -X GET "http://localhost/api/admin/users?role=student&search=john&per_page=20&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "profile_picture": "https://...",
            "created_at": "2024-01-01T10:00:00Z",
            "updated_at": "2024-01-15T15:30:00Z",
            "roles": ["student"],
            "permissions": []
        }
    ],
    "pagination": {
        "total": 150,
        "per_page": 20,
        "current_page": 1,
        "last_page": 8,
        "from": 1,
        "to": 20
    }
}
```

---

### 2. Get User Statistics

**Endpoint:** `GET /api/admin/users/statistics`

**Response:**

```json
{
    "total_users": 500,
    "total_admins": 3,
    "total_teachers": 50,
    "total_students": 447,
    "users_by_role": {
        "admin": 3,
        "teacher": 50,
        "student": 447
    },
    "new_users_today": 5,
    "new_users_this_week": 25,
    "new_users_this_month": 120
}
```

---

### 3. Get User Details

**Endpoint:** `GET /api/admin/users/{userId}`

**Path Parameters:**

- `userId` (required): User ID

**Example Request:**

```bash
curl -X GET "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
    "id": 42,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "profile_picture": "https://...",
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-15T15:30:00Z",
    "roles": ["teacher"],
    "permissions": [],
    "teacher": {
        "id": 5,
        "user_id": 42,
        "subject_id": 2
    },
    "teacher_stats": {
        "subject": "Mathematics",
        "total_quizzes": 12,
        "total_videos": 45,
        "total_lesson_attempts": 320
    }
}
```

---

### 4. Update User Information

**Endpoint:** `PUT /api/admin/users/{userId}`

**Request Body:**

```json
{
    "name": "Jane Doe Smith",
    "email": "jane.new@example.com",
    "profile_picture": "https://example.com/avatar.jpg"
}
```

**Validation Rules:**

- `name`: string, max 255
- `email`: valid email, unique (except self)
- `profile_picture`: valid URL

**Example Request:**

```bash
curl -X PUT "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Updated",
    "email": "jane.updated@example.com"
  }'
```

---

### 5. Delete User

**Endpoint:** `DELETE /api/admin/users/{userId}`

**Important:**

- Cannot delete your own account
- Cascades to delete related Student/Teacher records
- All user data will be permanently removed

**Example Request:**

```bash
curl -X DELETE "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
    "message": "User deleted successfully.",
    "deleted_user": {
        "id": 42,
        "name": "Jane Smith",
        "email": "jane@example.com"
    }
}
```

---

### 6. Assign Role to User

**Endpoint:** `POST /api/admin/users/{userId}/assign-role`

**Request Body:**

```json
{
    "role": "teacher"
}
```

**Available Roles:** `admin`, `teacher`, `student`

**Example Request:**

```bash
curl -X POST "http://localhost/api/admin/users/42/assign-role" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "teacher"}'
```

---

### 7. Remove Role from User

**Endpoint:** `POST /api/admin/users/{userId}/remove-role`

**Request Body:**

```json
{
    "role": "student"
}
```

---

### 8. Get User Activity

**Endpoint:** `GET /api/admin/users/{userId}/activity`

**Response for Student:**

```json
{
    "user_id": 42,
    "user_name": "John Student",
    "user_email": "john@example.com",
    "roles": ["student"],
    "created_at": "2024-01-01T10:00:00Z",
    "last_update": "2024-01-15T15:30:00Z",
    "student_activity": {
        "last_quiz_attempt": "2024-01-15T14:20:00Z",
        "total_quiz_attempts": 45,
        "avg_quiz_score": 75.5,
        "last_lesson_attempt": "2024-01-15T13:00:00Z",
        "total_lesson_attempts": 120,
        "total_evaluations": 250
    }
}
```

---

## Content Management API

### 1. Get Content Statistics

**Endpoint:** `GET /api/admin/content/statistics`

**Response:**

```json
{
  "total_subjects": 15,
  "total_units": 120,
  "total_lessons": 800,
  "total_quizzes": 300,
  "total_videos": 500,
  "subjects_with_units": [
    {
      "id": 1,
      "title": "Mathematics",
      "code": "MATH101",
      "units_count": 12
    }
  ],
  "recent_quizzes": [...],
  "recent_videos": [...]
}
```

---

### 2. List Subjects

**Endpoint:** `GET /api/admin/content/subjects`

**Query Parameters:**

- `search` (optional): Search by title or code
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Mathematics",
      "code": "MATH101",
      "units_count": 12,
      "teachers_count": 3
    }
  ],
  "pagination": {...}
}
```

---

### 3. Show Subject Details

**Endpoint:** `GET /api/admin/content/subjects/{subjectId}`

**Response:**

```json
{
    "id": 1,
    "title": "Mathematics",
    "code": "MATH101",
    "created_at": "2024-01-01T10:00:00Z",
    "units_count": 12,
    "lessons_count": 120,
    "teachers": [
        {
            "id": 1,
            "name": "Dr. Ahmed",
            "email": "ahmed@example.com"
        }
    ],
    "units": [
        {
            "id": 1,
            "title": "Algebra Basics",
            "lessons_count": 10
        }
    ]
}
```

---

### 4. Delete Subject

**Endpoint:** `DELETE /api/admin/content/subjects/{subjectId}`

**Important:** Cascades to delete all units, lessons, quizzes, and videos

**Example Request:**

```bash
curl -X DELETE "http://localhost/api/admin/content/subjects/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 5. List Quizzes

**Endpoint:** `GET /api/admin/content/quizzes`

**Query Parameters:**

- `search` (optional): Search quiz title
- `teacher_id` (optional): Filter by teacher
- `per_page` (optional): Items per page
- `page` (optional): Page number

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Algebra Quiz 1",
      "teacher_id": 5,
      "total_marks": 100,
      "questions_count": 20,
      "attempts_count": 45,
      "created_at": "2024-01-01T10:00:00Z"
    }
  ],
  "pagination": {...}
}
```

---

### 6. Show Quiz Details

**Endpoint:** `GET /api/admin/content/quizzes/{quizId}`

**Response:**

```json
{
    "id": 1,
    "title": "Algebra Quiz 1",
    "total_marks": 100,
    "questions_count": 20,
    "teacher": {
        "id": 5,
        "name": "Dr. Ahmed"
    },
    "lesson": {
        "id": 10,
        "title": "Quadratic Equations"
    },
    "attempts_count": 45,
    "avg_score": 72.5,
    "created_at": "2024-01-01T10:00:00Z"
}
```

---

### 7. Delete Quiz

**Endpoint:** `DELETE /api/admin/content/quizzes/{quizId}`

---

### 8. List Videos

**Endpoint:** `GET /api/admin/content/videos`

**Query Parameters:**

- `search` (optional): Search by title
- `teacher_id` (optional): Filter by teacher
- `per_page` (optional): Items per page
- `page` (optional): Page number

---

### 9. Delete Video

**Endpoint:** `DELETE /api/admin/content/videos/{videoId}`

---

### 10. List Lessons

**Endpoint:** `GET /api/admin/content/lessons`

**Query Parameters:**

- `unit_id` (optional): Filter by unit
- `search` (optional): Search lesson title
- `per_page` (optional): Items per page
- `page` (optional): Page number

---

### 11. Delete Lesson

**Endpoint:** `DELETE /api/admin/content/lessons/{lessonId}`

---

## Dashboard API

### Get Platform Analytics

**Endpoint:** `GET /api/admin/dashboard`

**Query Parameters:**

- `from` (optional): Date filter (YYYY-MM-DD)
- `to` (optional): Date filter (YYYY-MM-DD)

**Response:**

```json
{
    "summary": {
        "total_students": 447,
        "total_teachers": 50,
        "total_subjects": 15,
        "total_quizzes": 300,
        "total_quiz_attempts": 5000,
        "total_lesson_attempts": 8000,
        "avg_quiz_score": 72.5,
        "avg_quiz_score_percent": 72.5
    },
    "activity": {
        "quiz_attempts_by_day": [
            {
                "day": "2024-01-15",
                "attempts_count": 150,
                "avg_score": 73.2
            }
        ]
    },
    "teacher_rankings": [
        {
            "teacher_id": 1,
            "teacher_name": "Dr. Ahmed",
            "quizzes_count": 25,
            "quiz_attempts_count": 500,
            "avg_score_percent": 85.5
        }
    ],
    "weak_subtopics": [
        {
            "subtopic_id": 1,
            "subtopic_title": "Integration",
            "avg_mastery": 45.2,
            "evaluations_count": 120
        }
    ]
}
```

---

## Error Responses

### 404 Not Found

```json
{
    "message": "User not found."
}
```

### 403 Forbidden

```json
{
    "message": "Cannot delete your own account."
}
```

### 422 Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "role": ["The selected role is invalid."]
    }
}
```

### 500 Server Error

```json
{
    "message": "Error updating user.",
    "error": "Exception message here"
}
```

---

## Common Use Cases

### 1. Delete a Student Account

```bash
curl -X DELETE "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Change User Role from Student to Teacher

```bash
# First remove student role
curl -X POST "http://localhost/api/admin/users/42/remove-role" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "student"}'

# Then assign teacher role
curl -X POST "http://localhost/api/admin/users/42/assign-role" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "teacher"}'
```

### 3. Remove Problematic Content

```bash
# Delete a quiz
curl -X DELETE "http://localhost/api/admin/content/quizzes/15" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Delete a lesson
curl -X DELETE "http://localhost/api/admin/content/lessons/20" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Delete an entire subject (cascades all content)
curl -X DELETE "http://localhost/api/admin/content/subjects/5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Monitor Platform Health

```bash
# Get dashboard analytics for date range
curl -X GET "http://localhost/api/admin/dashboard?from=2024-01-01&to=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get content statistics
curl -X GET "http://localhost/api/admin/content/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get user statistics
curl -X GET "http://localhost/api/admin/users/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Rate Limiting & Performance

- Default pagination: 15 items per page
- Maximum per page: 100 items
- Dashboard caching: 15 minutes
- All endpoints are rate-limited by JWT token (server-wide)

---

## Logging

All admin operations are logged to `storage/logs/laravel.log` with:

- Operation type (create, update, delete)
- User performing the action
- Target resource and IDs
- Timestamp
- Any errors that occurred

---

## Testing Admin API with Postman

1. **Create Postman Collection**
2. **Add Base URL Variable:** `{{base_url}}` = `http://localhost`
3. **Add Token Variable:** `{{admin_token}}` = Your JWT token
4. **Set Authorization** in collection: `Bearer {{admin_token}}`

Example requests included in this documentation can be directly imported into Postman.
