# Frontend Integration Guide: Super Admin

This guide provides all the information needed to integrate super admin features into your frontend application.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Authorization](#authorization)
- [API Endpoints](#api-endpoints)
  - [Authentication](#authentication-endpoints)
  - [Membership Applications Management](#membership-applications-management)
- [Request/Response Formats](#requestresponse-formats)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
- [Best Practices](#best-practices)

## Overview

Super admins have exclusive access to manage membership applications, including:
- Viewing all membership applications (with filtering)
- Viewing individual application details
- Approving applications (creates user accounts)
- Rejecting applications

**Note:** For member management features (viewing and managing member users), see the separate [Member Management Guide](./FRONTEND_MEMBER_MANAGEMENT_GUIDE.md).

**Base URL:** `http://localhost:8000` (or your configured API base URL)

## Authentication

All super admin endpoints require authentication using Laravel Sanctum. You must include the authentication token in the `Authorization` header for all protected requests.

### Authentication Flow

1. **Login** - POST `/api/login` to get an authentication token
2. **Store Token** - Save the token securely (localStorage, sessionStorage, or secure cookie)
3. **Include Token** - Add token to all subsequent requests in the `Authorization` header
4. **Logout** - POST `/api/logout` to invalidate the current token

### Token Format

```
Authorization: Bearer {token}
```

## Authorization

Only users with the `super_admin` role can access super admin endpoints. The backend automatically checks the user's role and returns `403 Forbidden` if the user is not a super admin.

**Important:** Always check the user's role on the frontend before showing super admin UI elements, but never rely solely on frontend checks for security.

## API Endpoints

### Authentication Endpoints

#### Login

**URL:** `POST /api/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Success Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com",
    "role": "super_admin",
    "primary_member_type": null,
    "secondary_member_type_id": null,
    "member_id": null,
    "email_verified_at": null,
    "created_at": "2024-01-15T10:00:00+00:00",
    "updated_at": "2024-01-15T10:00:00+00:00"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "The provided credentials are incorrect."
}
```

#### Logout

**URL:** `POST /api/logout`

**Authentication:** Required (Bearer token)

**Request Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

#### Get Current User

**URL:** `GET /api/user`

**Authentication:** Required (Bearer token)

**Success Response (200 OK):**
```json
{
  "id": 1,
  "name": "Super Admin",
  "email": "admin@example.com",
  "role": "super_admin",
  "primary_member_type": null,
  "secondary_member_type_id": null,
  "member_id": null,
  "email_verified_at": null,
  "created_at": "2024-01-15T10:00:00+00:00",
  "updated_at": "2024-01-15T10:00:00+00:00"
}
```

### Membership Applications Management

#### List All Applications

**URL:** `GET /api/membership-applications`

**Authentication:** Required (Bearer token, Super Admin only)

**Query Parameters:**
- `status` (optional): Filter by status (`PENDING`, `APPROVED`, `REJECTED`)

**Example:** `GET /api/membership-applications?status=PENDING`

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "membership_type": "GENERAL",
      "full_name": "John Doe",
      "name_bangla": "জন ডো",
      "father_name": "Father Name",
      "mother_name": null,
      "gender": "MALE",
      "jsc_year": 2010,
      "ssc_year": 2012,
      "studentship_proof_type": null,
      "studentship_proof_file": "http://localhost:8000/storage/membership-applications/proof_abc123.pdf",
      "highest_educational_degree": null,
      "present_address": "123 Main St",
      "permanent_address": "456 Oak Ave",
      "email": "john@example.com",
      "mobile_number": "1234567890",
      "profession": "Engineer",
      "designation": "Senior Engineer",
      "institute_name": "Tech Corp",
      "t_shirt_size": "L",
      "blood_group": "O+",
      "entry_fee": 0,
      "yearly_fee": 500.0,
      "payment_years": 1,
      "total_paid_amount": 500.0,
      "receipt_file": null,
      "status": "PENDING",
      "approved_by": null,
      "approved_at": null,
      "created_at": "2024-01-15T10:30:00+00:00",
      "updated_at": "2024-01-15T10:30:00+00:00"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/membership-applications?page=1",
    "last": "http://localhost:8000/api/membership-applications?page=10",
    "prev": null,
    "next": "http://localhost:8000/api/membership-applications?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "http://localhost:8000/api/membership-applications",
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Unauthorized action."
}
```

#### View Single Application

**URL:** `GET /api/membership-applications/{id}`

**Authentication:** Required (Bearer token, Super Admin only)

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "membership_type": "GENERAL",
    "full_name": "John Doe",
    "name_bangla": "জন ডো",
    "father_name": "Father Name",
    "mother_name": null,
    "gender": "MALE",
    "jsc_year": 2010,
    "ssc_year": 2012,
    "studentship_proof_type": null,
    "studentship_proof_file": "http://localhost:8000/storage/membership-applications/proof_abc123.pdf",
    "highest_educational_degree": null,
    "present_address": "123 Main St",
    "permanent_address": "456 Oak Ave",
    "email": "john@example.com",
    "mobile_number": "1234567890",
    "profession": "Engineer",
    "designation": "Senior Engineer",
    "institute_name": "Tech Corp",
    "t_shirt_size": "L",
    "blood_group": "O+",
    "entry_fee": 0,
    "yearly_fee": 500.0,
    "payment_years": 1,
    "total_paid_amount": 500.0,
    "receipt_file": null,
    "status": "PENDING",
    "approved_by": null,
    "approved_at": null,
    "created_at": "2024-01-15T10:30:00+00:00",
    "updated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\MembershipApplication] {id}"
}
```

#### Approve Application

**URL:** `POST /api/membership-applications/{id}/approve`

**Authentication:** Required (Bearer token, Super Admin only)

**Request Body:** None required

**Success Response (200 OK):**
```json
{
  "message": "Application approved successfully. User account created.",
  "application": {
    "id": 1,
    "membership_type": "GENERAL",
    "full_name": "John Doe",
    "name_bangla": "জন ডো",
    "status": "APPROVED",
    "approved_by": 1,
    "approved_at": "2024-01-15T11:30:00+00:00",
    ...
  },
  "user": {
    "id": 2,
    "name": "John Doe",
    "email": "john@example.com",
    "member_id": "G-2012-0001"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "Application is not pending approval."
}
```

or

```json
{
  "message": "Application must have an email address to be approved."
}
```

**Important Notes:**
- Only applications with status `PENDING` can be approved
- The application must have an `email` address
- Approving an application:
  - Creates a new user account with role `member`
  - Generates a unique member ID (format: `{TYPE}-{YEAR}-{NUMBER}`)
  - Generates a random 12-character password
  - Sends an email to the user with their credentials
  - Updates the application status to `APPROVED`

#### Reject Application

**URL:** `POST /api/membership-applications/{id}/reject`

**Authentication:** Required (Bearer token, Super Admin only)

**Request Body:** None required

**Success Response (200 OK):**
```json
{
  "message": "Application rejected successfully.",
  "application": {
    "id": 1,
    "membership_type": "GENERAL",
    "full_name": "John Doe",
    "name_bangla": "জন ডো",
    "status": "REJECTED",
    "approved_by": 1,
    "approved_at": "2024-01-15T11:30:00+00:00",
    ...
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "Application is not pending approval."
}
```

**Important Notes:**
- Only applications with status `PENDING` can be rejected
- Rejecting an application updates the status to `REJECTED` and records who rejected it and when

**Note:** For member management endpoints (viewing and managing member users), see the separate [Member Management Guide](./FRONTEND_MEMBER_MANAGEMENT_GUIDE.md).

## Request/Response Formats

### Application Status Values

- `PENDING` - Application is awaiting review
- `APPROVED` - Application has been approved and user account created
- `REJECTED` - Application has been rejected

### Membership Type Values

- `GENERAL` - General membership
- `LIFETIME` - Lifetime membership
- `ASSOCIATE` - Associate membership

### Gender Values

- `MALE`
- `FEMALE`
- `OTHER`

### T-Shirt Size Values

- `XXXL`
- `XXL`
- `XL`
- `L`
- `M`
- `S`

### Blood Group Values

- `A+`, `A-`
- `B+`, `B-`
- `AB+`, `AB-`
- `O+`, `O-`

### Studentship Proof Type Values

- `JSC`
- `EIGHT`
- `SSC`
- `METRIC_CERTIFICATE`
- `MARK_SHEET`
- `OTHERS`

## Error Handling

### HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `401 Unauthorized` - Authentication required or invalid token
- `403 Forbidden` - User is not authorized (not a super admin)
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors or business logic errors
- `500 Internal Server Error` - Server error

### Common Error Scenarios

1. **Invalid Token** - Token expired or invalid
   - Response: `401 Unauthorized`
   - Action: Redirect to login page

2. **Not Super Admin** - User is authenticated but not a super admin
   - Response: `403 Forbidden`
   - Action: Show error message, redirect to appropriate page

3. **Application Not Pending** - Trying to approve/reject a non-pending application
   - Response: `422 Unprocessable Entity`
   - Action: Show error message to user

4. **Missing Email** - Trying to approve application without email
   - Response: `422 Unprocessable Entity`
   - Action: Show error message indicating the application cannot be approved without an email address

5. **Validation Errors** - Invalid input data
   - Response: `422 Unprocessable Entity`
   - Action: Display validation errors next to relevant form fields

## Code Examples

### TypeScript/JavaScript API Client

```typescript
// apiClient.ts
class ApiClient {
  private baseUrl: string;
  private token: string | null = null;

  constructor(baseUrl: string = 'http://localhost:8000') {
    this.baseUrl = baseUrl;
    this.token = localStorage.getItem('auth_token');
  }

  setToken(token: string): void {
    this.token = token;
    localStorage.setItem('auth_token', token);
  }

  clearToken(): void {
    this.token = null;
    localStorage.removeItem('auth_token');
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(url, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'An error occurred' }));
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
  }

  // Authentication
  async login(email: string, password: string) {
    const data = await this.request<{ user: any; token: string }>('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    this.setToken(data.token);
    return data;
  }

  async logout() {
    await this.request('/api/logout', { method: 'POST' });
    this.clearToken();
  }

  async getCurrentUser() {
    return this.request('/api/user');
  }

  // Membership Applications
  async getApplications(status?: 'PENDING' | 'APPROVED' | 'REJECTED') {
    const query = status ? `?status=${status}` : '';
    return this.request(`/api/membership-applications${query}`);
  }

  async getApplication(id: number) {
    return this.request(`/api/membership-applications/${id}`);
  }

  async approveApplication(id: number) {
    return this.request(`/api/membership-applications/${id}/approve`, {
      method: 'POST',
    });
  }

  async rejectApplication(id: number) {
    return this.request(`/api/membership-applications/${id}/reject`, {
      method: 'POST',
    });
  }
}

export const apiClient = new ApiClient();
```

### React Example: Application List Component

```tsx
import { useState, useEffect } from 'react';
import { apiClient } from './apiClient';

interface Application {
  id: number;
  full_name: string;
  email: string;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  membership_type: string;
  created_at: string;
}

function ApplicationList() {
  const [applications, setApplications] = useState<Application[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState<'PENDING' | 'APPROVED' | 'REJECTED' | undefined>();

  useEffect(() => {
    loadApplications();
  }, [statusFilter]);

  const loadApplications = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiClient.getApplications(statusFilter);
      setApplications(response.data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (id: number) => {
    if (!confirm('Are you sure you want to approve this application?')) {
      return;
    }

    try {
      await apiClient.approveApplication(id);
      alert('Application approved successfully!');
      loadApplications();
    } catch (err: any) {
      alert(`Error: ${err.message}`);
    }
  };

  const handleReject = async (id: number) => {
    if (!confirm('Are you sure you want to reject this application?')) {
      return;
    }

    try {
      await apiClient.rejectApplication(id);
      alert('Application rejected successfully!');
      loadApplications();
    } catch (err: any) {
      alert(`Error: ${err.message}`);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>Membership Applications</h1>
      
      <div>
        <label>Filter by Status:</label>
        <select
          value={statusFilter || ''}
          onChange={(e) => setStatusFilter(e.target.value as any || undefined)}
        >
          <option value="">All</option>
          <option value="PENDING">Pending</option>
          <option value="APPROVED">Approved</option>
          <option value="REJECTED">Rejected</option>
        </select>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Type</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {applications.map((app) => (
            <tr key={app.id}>
              <td>{app.id}</td>
              <td>{app.full_name}</td>
              <td>{app.email}</td>
              <td>{app.membership_type}</td>
              <td>{app.status}</td>
              <td>{new Date(app.created_at).toLocaleDateString()}</td>
              <td>
                {app.status === 'PENDING' && (
                  <>
                    <button onClick={() => handleApprove(app.id)}>Approve</button>
                    <button onClick={() => handleReject(app.id)}>Reject</button>
                  </>
                )}
                <a href={`/applications/${app.id}`}>View</a>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default ApplicationList;
```

### React Example: Application Detail Component

```tsx
import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { apiClient } from './apiClient';

function ApplicationDetail() {
  const { id } = useParams<{ id: string }>();
  const [application, setApplication] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadApplication();
  }, [id]);

  const loadApplication = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiClient.getApplication(Number(id));
      setApplication(response.data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!confirm('Are you sure you want to approve this application?')) {
      return;
    }

    try {
      const response = await apiClient.approveApplication(Number(id));
      alert(`Application approved! User created with member ID: ${response.user.member_id}`);
      loadApplication();
    } catch (err: any) {
      alert(`Error: ${err.message}`);
    }
  };

  const handleReject = async () => {
    if (!confirm('Are you sure you want to reject this application?')) {
      return;
    }

    try {
      await apiClient.rejectApplication(Number(id));
      alert('Application rejected successfully!');
      loadApplication();
    } catch (err: any) {
      alert(`Error: ${err.message}`);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!application) return <div>Application not found</div>;

  return (
    <div>
      <h1>Application #{application.id}</h1>
      
      <div>
        <p><strong>Status:</strong> {application.status}</p>
        {application.status === 'PENDING' && (
          <div>
            <button onClick={handleApprove}>Approve</button>
            <button onClick={handleReject}>Reject</button>
          </div>
        )}
      </div>

      <div>
        <h2>Application Details</h2>
        <div>
          <p><strong>Full Name:</strong> {application.full_name}</p>
          <p><strong>Name (Bangla):</strong> {application.name_bangla}</p>
          <p><strong>Email:</strong> {application.email}</p>
          <p><strong>Mobile:</strong> {application.mobile_number}</p>
          <p><strong>Membership Type:</strong> {application.membership_type}</p>
          <p><strong>Status:</strong> {application.status}</p>
          {application.studentship_proof_file && (
            <p>
              <strong>Studentship Proof:</strong>{' '}
              <a href={application.studentship_proof_file} target="_blank" rel="noopener noreferrer">
                View File
              </a>
            </p>
          )}
          {application.receipt_file && (
            <p>
              <strong>Receipt:</strong>{' '}
              <a href={application.receipt_file} target="_blank" rel="noopener noreferrer">
                View File
              </a>
            </p>
          )}
        </div>
      </div>
    </div>
  );
}

export default ApplicationDetail;
```

**Note:** For member management code examples, see the separate [Member Management Guide](./FRONTEND_MEMBER_MANAGEMENT_GUIDE.md).

### Vue.js Example: Login Component

```vue
<template>
  <form @submit.prevent="handleLogin">
    <div>
      <label>Email:</label>
      <input v-model="email" type="email" required />
      <div v-if="errors.email" class="error">{{ errors.email[0] }}</div>
    </div>

    <div>
      <label>Password:</label>
      <input v-model="password" type="password" required />
      <div v-if="errors.password" class="error">{{ errors.password[0] }}</div>
    </div>

    <div v-if="error" class="error">{{ error }}</div>

    <button type="submit" :disabled="loading">
      {{ loading ? 'Logging in...' : 'Login' }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from './apiClient';

const router = useRouter();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref<string | null>(null);
const errors = ref<Record<string, string[]>>({});

const handleLogin = async () => {
  loading.value = true;
  error.value = null;
  errors.value = {};

  try {
    const response = await apiClient.login(email.value, password.value);
    
    // Check if user is super admin
    if (response.user.role !== 'super_admin') {
      error.value = 'Access denied. Super admin access required.';
      await apiClient.logout();
      return;
    }

    // Redirect to admin dashboard
    router.push('/admin/dashboard');
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
    } else {
      error.value = err.message || 'Login failed';
    }
  } finally {
    loading.value = false;
  }
};
</script>
```

## Best Practices

### Security

1. **Token Storage**
   - Store tokens securely (consider using httpOnly cookies for production)
   - Never expose tokens in URLs or logs
   - Implement token refresh mechanism if needed

2. **Authorization Checks**
   - Always verify user role on the frontend before showing admin UI
   - Never rely solely on frontend checks - backend enforces security
   - Handle 403 errors gracefully

3. **Error Handling**
   - Implement proper error boundaries
   - Show user-friendly error messages
   - Log errors for debugging

### User Experience

1. **Loading States**
   - Show loading indicators during API calls
   - Disable buttons during operations
   - Provide feedback for all actions

2. **Confirmation Dialogs**
   - Always confirm destructive actions (approve, reject, delete)
   - Show clear success/error messages

3. **Pagination**
   - Implement pagination for application lists
   - Show total count and current page
   - Provide navigation controls

4. **Filtering**
   - Allow filtering by status
   - Consider adding search functionality
   - Remember filter preferences

5. **File Handling**
   - Show file previews when possible
   - Provide download/view links for application files (studentship proof, receipts)
   - Handle missing files gracefully

### Performance

1. **Caching**
   - Cache application lists when appropriate
   - Refresh data after mutations (approve/reject)

2. **Debouncing**
   - Debounce search/filter inputs
   - Avoid unnecessary API calls

3. **Lazy Loading**
   - Load application details on demand
   - Implement infinite scroll for large lists

### Code Organization

1. **API Client**
   - Centralize API calls in a dedicated client
   - Handle authentication automatically
   - Provide type-safe interfaces

2. **Error Handling**
   - Create reusable error handling utilities
   - Standardize error message display

3. **State Management**
   - Use appropriate state management solution
   - Keep UI state separate from server state

## Testing

### Manual Testing Checklist

- [ ] Login with super admin credentials
- [ ] View list of applications
- [ ] Filter applications by status
- [ ] View single application details
- [ ] Approve pending application
- [ ] Reject pending application
- [ ] Handle errors (403, 404, 422)
- [ ] Logout functionality

### API Testing Tools

You can test endpoints using:
- **Postman** - Create a collection with all endpoints
- **cURL** - Command-line testing
- **Browser DevTools** - Network tab for debugging

### Example cURL Commands

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Get applications (replace TOKEN with actual token)
curl -X GET http://localhost:8000/api/membership-applications \
  -H "Authorization: Bearer TOKEN"

# Approve application
curl -X POST http://localhost:8000/api/membership-applications/1/approve \
  -H "Authorization: Bearer TOKEN"
```

## Support

For issues or questions, please contact the backend development team or refer to the API documentation.
