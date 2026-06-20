# Admin API - Quick Reference & Example Requests

## Authentication

```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

---

## Quick Admin Tasks

### 1. Create Admin User via CLI

```bash
php artisan make:admin
# Then follow the interactive prompts:
# - Enter Admin Email
# - Enter Admin Password
# - Admin account successfully created!
```

### 2. View All Students

```bash
curl -X GET "http://localhost/api/admin/users?role=student&per_page=50" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Find Specific User

```bash
curl -X GET "http://localhost/api/admin/users?search=john" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Get Detailed User Profile

```bash
curl -X GET "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Update User Name

```bash
curl -X PUT "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Name Here"
  }'
```

### 6. Delete Student Account

```bash
curl -X DELETE "http://localhost/api/admin/users/42" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 7. Convert Student to Teacher

```bash
# Remove student role
curl -X POST "http://localhost/api/admin/users/42/remove-role" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "student"}'

# Assign teacher role
curl -X POST "http://localhost/api/admin/users/42/assign-role" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "teacher"}'
```

### 8. View User Activity (Student)

```bash
curl -X GET "http://localhost/api/admin/users/42/activity" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Quiz attempts count and average score
# - Lesson attempts count
# - Evaluation count
# - Last activity date
```

### 9. Get Platform Dashboard

```bash
curl -X GET "http://localhost/api/admin/dashboard" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Total users, teachers, students
# - Quiz statistics
# - Teacher rankings
# - Weak topics
# - Daily activity
```

### 10. Dashboard with Date Filter

```bash
curl -X GET "http://localhost/api/admin/dashboard?from=2024-01-01&to=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 11. Get User Statistics

```bash
curl -X GET "http://localhost/api/admin/users/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Total users, admins, teachers, students
# - New users today/week/month
# - Users by role breakdown
```

### 12. View All Subjects

```bash
curl -X GET "http://localhost/api/admin/content/subjects" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 13. Get Subject Details

```bash
curl -X GET "http://localhost/api/admin/content/subjects/1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Subject info
# - Teachers teaching this subject
# - Units and lessons count
# - Full subject structure
```

### 14. Delete Subject (All Related Content)

```bash
curl -X DELETE "http://localhost/api/admin/content/subjects/1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# WARNING: This deletes:
# - All units in subject
# - All lessons in units
# - All quizzes in lessons
# - All videos in lessons
# - All subtopics in lessons
```

### 15. List Quizzes by Teacher

```bash
curl -X GET "http://localhost/api/admin/content/quizzes?teacher_id=5&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 16. Get Quiz Details with Statistics

```bash
curl -X GET "http://localhost/api/admin/content/quizzes/15" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Quiz details
# - Total questions
# - Number of attempts
# - Average score
# - Teacher info
```

### 17. Delete Quiz

```bash
curl -X DELETE "http://localhost/api/admin/content/quizzes/15" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 18. List All Videos

```bash
curl -X GET "http://localhost/api/admin/content/videos?per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 19. Filter Videos by Teacher

```bash
curl -X GET "http://localhost/api/admin/content/videos?teacher_id=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 20. Delete Video

```bash
curl -X DELETE "http://localhost/api/admin/content/videos/30" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 21. List All Lessons

```bash
curl -X GET "http://localhost/api/admin/content/lessons" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 22. Delete Lesson

```bash
curl -X DELETE "http://localhost/api/admin/content/lessons/20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 23. Get Content Statistics

```bash
curl -X GET "http://localhost/api/admin/content/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
# - Total subjects, units, lessons
# - Total quizzes, videos
# - Recent quizzes and videos
```

### 24. Search Users by Email

```bash
curl -X GET "http://localhost/api/admin/users?search=student@example.com&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 25. Paginate Through Teachers

```bash
# Page 1
curl -X GET "http://localhost/api/admin/users?role=teacher&page=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Page 2
curl -X GET "http://localhost/api/admin/users?role=teacher&page=2&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Common Response Codes

| Code | Meaning              | Common Cause                          |
| ---- | -------------------- | ------------------------------------- |
| 200  | OK - Success         | Request completed successfully        |
| 201  | Created              | Resource successfully created         |
| 400  | Bad Request          | Invalid request format                |
| 403  | Forbidden            | Permission denied (e.g., self-delete) |
| 404  | Not Found            | User/resource doesn't exist           |
| 422  | Unprocessable Entity | Validation error (see errors field)   |
| 500  | Server Error         | Internal server error                 |

---

## Important Notes

### About Deletions

- ⚠️ **Permanent**: Deletions cannot be undone. Data is permanently removed.
- 🔗 **Cascading**: Deleting subjects cascades to all related content
- 📝 **Logged**: All deletions are logged to system logs
- 🛡️ **Protected**: Cannot delete your own admin account

### About Pagination

- Default: 15 items per page
- Maximum: 100 items per page
- Minimum page: 1
- Use `page` and `per_page` parameters to navigate

### About Searching

- Case-insensitive
- Searches: name, email, title (depending on endpoint)
- Looks for substring matches
- Example: searching "john" finds "John Doe" and "johnny"

### About Caching

- Dashboard is cached for 15 minutes
- Cache automatically cleared when data changes
- Clear manually: `php artisan cache:clear`

### About Roles

- User can have one or more roles
- When assigning a role with `assignRole`, it replaces existing roles
- Use `syncRoles()` to set multiple roles

---

## Postman Collection Setup

### 1. Create Environment

```json
{
    "name": "Focus Platform Admin",
    "values": [
        {
            "key": "base_url",
            "value": "http://localhost",
            "enabled": true
        },
        {
            "key": "admin_token",
            "value": "YOUR_JWT_TOKEN_HERE",
            "enabled": true
        }
    ]
}
```

### 2. Set Base URL in Postman

- Pre-request URL: `{{base_url}}/api/admin/...`
- Authorization: Bearer {{admin_token}}

### 3. Example Request

```
GET {{base_url}}/api/admin/users?role=student&per_page=20
Headers:
  Authorization: Bearer {{admin_token}}
```

---

## Performance Tips

1. **Use Pagination**: Don't fetch all users at once

    ```bash
    # Good
    curl -X GET "http://localhost/api/admin/users?per_page=20&page=1"

    # Avoid
    curl -X GET "http://localhost/api/admin/users"  # Could be 1000s of users!
    ```

2. **Use Filters**: Narrow results with search/role filters

    ```bash
    # More efficient
    curl -X GET "http://localhost/api/admin/users?role=student&search=john"

    # Less efficient
    curl -X GET "http://localhost/api/admin/users"  # Get all then filter client-side
    ```

3. **Cache Dashboard**: After first load, data is cached for 15 minutes

4. **Date Filtering**: Use date range to limit dashboard data
    ```bash
    curl -X GET "http://localhost/api/admin/dashboard?from=2024-01-01&to=2024-01-31"
    ```

---

## Troubleshooting API Calls

### Getting 404 User Not Found?

- Verify user ID is correct
- Check user actually exists in database
- Try listing users first to confirm ID

### Getting 403 Forbidden?

- Verify you have admin role
- Verify JWT token is valid
- Check not trying to self-delete

### Getting 422 Validation Error?

- Check email format is valid
- Check email is not already taken
- Check all required fields are provided
- Review error message for specific field

### Getting 500 Error?

- Check server logs: `storage/logs/laravel.log`
- Verify database connection
- Try request again (might be temporary)

---

## Advanced Usage

### Get Activity for All Teachers

```bash
curl -X GET "http://localhost/api/admin/users?role=teacher&per_page=100" \
  -H "Authorization: Bearer YOUR_TOKEN" | \
  jq '.data[].id' | \
  while read id; do
    curl -X GET "http://localhost/api/admin/users/$id/activity" \
      -H "Authorization: Bearer YOUR_TOKEN"
  done
```

### Find Students with Low Engagement

```bash
curl -X GET "http://localhost/api/admin/users/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN" | \
  jq '.total_students'

# Then manually review students with activity
```

### Generate Report of Weak Topics

```bash
curl -X GET "http://localhost/api/admin/dashboard" \
  -H "Authorization: Bearer YOUR_TOKEN" | \
  jq '.weak_subtopics'
```

---

**Need Help?**

- Check `ADMIN_API.md` for full endpoint documentation
- Check `ADMIN_FEATURES.md` for feature overview
- Review server logs: `storage/logs/laravel.log`
- Verify admin role assignment: `php artisan tinker`
