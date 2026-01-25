# Frontend Integration Guide: Member Management

This guide provides all the information needed to integrate member management features into your frontend application for super admin users.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Authorization](#authorization)
- [API Endpoints](#api-endpoints)
  - [List All Members](#list-all-members)
  - [View Individual Member](#view-individual-member)
- [Request/Response Formats](#requestresponse-formats)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
- [Best Practices](#best-practices)

## Overview

Super admins can view and manage all member users in the system. This includes:
- Viewing all member users and their details
- Searching members by name, email, or member ID
- Filtering members by primary member type
- Viewing paginated member lists

**Base URL:** `http://localhost:8000` (or your configured API base URL)

## Authentication

All member management endpoints require authentication using Laravel Sanctum. You must include the authentication token in the `Authorization` header for all protected requests.

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

Only users with the `super_admin` role can access member management endpoints. The backend automatically checks the user's role and returns `403 Forbidden` if the user is not a super admin.

**Important:** Always check the user's role on the frontend before showing member management UI elements, but never rely solely on frontend checks for security.

## API Endpoints

### List All Members

**URL:** `GET /api/members`

**Authentication:** Required (Bearer token, Super Admin only)

**Query Parameters:**
- `search` (optional): Search by name, email, or member ID (case-insensitive)
- `primary_member_type` (optional): Filter by primary member type (`GENERAL`, `LIFETIME`, `ASSOCIATE`)

**Example Requests:**
- `GET /api/members` - Get all members (first page)
- `GET /api/members?search=john` - Search for members with "john" in name, email, or member ID
- `GET /api/members?primary_member_type=GENERAL` - Filter by general members only
- `GET /api/members?search=john&primary_member_type=GENERAL` - Combined search and filter
- `GET /api/members?page=2` - Get second page of results

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "member",
      "primary_member_type": "GENERAL",
      "secondary_member_type": {
        "id": 1,
        "name": "Life Member",
        "description": "Lifetime membership type"
      },
      "member_id": "G-2012-0001",
      "email_verified_at": "2024-01-15T10:30:00+00:00",
      "created_at": "2024-01-15T10:30:00+00:00",
      "updated_at": "2024-01-15T10:30:00+00:00"
    },
    {
      "id": 3,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "role": "member",
      "primary_member_type": "LIFETIME",
      "secondary_member_type": null,
      "member_id": "LT-2010-0001",
      "email_verified_at": null,
      "created_at": "2024-01-16T08:15:00+00:00",
      "updated_at": "2024-01-16T08:15:00+00:00"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/members?page=1",
    "last": "http://localhost:8000/api/members?page=10",
    "prev": null,
    "next": "http://localhost:8000/api/members?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "http://localhost:8000/api/members",
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

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

**Important Notes:**
- Only users with role `member` are returned in the list
- Results are paginated with 15 members per page
- Search is case-insensitive and searches across name, email, and member_id fields
- Results are ordered by creation date (newest first)
- Secondary member type relationship is automatically loaded when available
- The `secondary_member_type` field will be `null` if the member does not have a secondary member type assigned
- The `member_id` field may be `null` for members who haven't been assigned a member ID yet

### View Individual Member

**URL:** `GET /api/members/{id}`

**Authentication:** Required (Bearer token, Super Admin only)

**URL Parameters:**
- `id` (required): The ID of the member user to retrieve

**Example:** `GET /api/members/2`

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "member",
    "primary_member_type": "GENERAL",
    "secondary_member_type": {
      "id": 1,
      "name": "Life Member",
      "description": "Lifetime membership type"
    },
    "member_id": "G-2012-0001",
    "email_verified_at": "2024-01-15T10:30:00+00:00",
    "created_at": "2024-01-15T10:30:00+00:00",
    "updated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

**Note:** The response is wrapped in a `data` object for consistency with the list endpoint.

**Error Response (403 Forbidden):**
```json
{
  "message": "Unauthorized action."
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Member not found."
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

**Important Notes:**
- The endpoint will return 404 if the user ID exists but the user is not a member (e.g., if it's a super admin)
- Secondary member type relationship is automatically loaded
- The `secondary_member_type` field will be `null` if the member does not have a secondary member type assigned
- The `member_id` field may be `null` for members who haven't been assigned a member ID yet

## Request/Response Formats

### Primary Member Type Values

- `GENERAL` - General membership
- `LIFETIME` - Lifetime membership
- `ASSOCIATE` - Associate membership

### Member Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique member user ID |
| `name` | string | Member's full name |
| `email` | string | Member's email address |
| `role` | string | User role (always "member" for this endpoint) |
| `primary_member_type` | string \| null | Primary membership type (GENERAL, LIFETIME, ASSOCIATE) |
| `secondary_member_type` | object \| null | Secondary member type object with id, name, and description |
| `member_id` | string \| null | Unique member ID (format: TYPE-YEAR-NUMBER, e.g., "G-2012-0001") |
| `email_verified_at` | string \| null | ISO 8601 timestamp when email was verified |
| `created_at` | string | ISO 8601 timestamp when member account was created |
| `updated_at` | string | ISO 8601 timestamp when member account was last updated |

### Pagination Response

The response includes pagination metadata in the `links` and `meta` objects:

- `links.first` - URL to first page
- `links.last` - URL to last page
- `links.prev` - URL to previous page (null if on first page)
- `links.next` - URL to next page (null if on last page)
- `meta.current_page` - Current page number
- `meta.from` - Starting record number for current page
- `meta.last_page` - Total number of pages
- `meta.path` - Base path for pagination links
- `meta.per_page` - Number of items per page (15)
- `meta.to` - Ending record number for current page
- `meta.total` - Total number of members

## Error Handling

### HTTP Status Codes

- `200 OK` - Request successful
- `401 Unauthorized` - Authentication required or invalid token
- `403 Forbidden` - User is not authorized (not a super admin)
- `404 Not Found` - Member not found
- `500 Internal Server Error` - Server error

### Common Error Scenarios

1. **Invalid Token** - Token expired or invalid
   - Response: `401 Unauthorized`
   - Action: Redirect to login page

2. **Not Super Admin** - User is authenticated but not a super admin
   - Response: `403 Forbidden`
   - Action: Show error message, redirect to appropriate page

3. **Member Not Found** - Member ID doesn't exist or user is not a member
   - Response: `404 Not Found`
   - Action: Show error message, redirect to member list

4. **Network Error** - Request failed due to network issues
   - Action: Show error message, allow retry

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

  // Member Management
  async getMembers(search?: string, primaryMemberType?: string, page: number = 1) {
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (primaryMemberType) params.append('primary_member_type', primaryMemberType);
    if (page > 1) params.append('page', page.toString());
    const query = params.toString();
    return this.request(`/api/members${query ? `?${query}` : ''}`);
  }

  async getMember(id: number) {
    return this.request(`/api/members/${id}`);
  }
}

export const apiClient = new ApiClient();
```

### React Example: Member List Component

```tsx
import { useState, useEffect } from 'react';
import { apiClient } from './apiClient';

interface Member {
  id: number;
  name: string;
  email: string;
  role: string;
  primary_member_type: string | null;
  secondary_member_type: {
    id: number;
    name: string;
    description: string;
  } | null;
  member_id: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

interface PaginationMeta {
  current_page: number;
  from: number;
  last_page: number;
  path: string;
  per_page: number;
  to: number;
  total: number;
}

function MemberList() {
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [primaryMemberType, setPrimaryMemberType] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState<PaginationMeta | null>(null);

  useEffect(() => {
    loadMembers();
  }, [currentPage, primaryMemberType]);

  const loadMembers = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiClient.getMembers(
        search || undefined,
        primaryMemberType || undefined,
        currentPage
      );
      setMembers(response.data);
      setPagination(response.meta);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentPage(1);
    loadMembers();
  };

  const handleClearSearch = () => {
    setSearch('');
    setPrimaryMemberType('');
    setCurrentPage(1);
    loadMembers();
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>Members</h1>
      
      <form onSubmit={handleSearch}>
        <div>
          <input
            type="text"
            placeholder="Search by name, email, or member ID"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <select
            value={primaryMemberType}
            onChange={(e) => {
              setPrimaryMemberType(e.target.value);
              setCurrentPage(1);
            }}
          >
            <option value="">All Types</option>
            <option value="GENERAL">General</option>
            <option value="LIFETIME">Lifetime</option>
            <option value="ASSOCIATE">Associate</option>
          </select>
          <button type="submit">Search</button>
          <button type="button" onClick={handleClearSearch}>Clear</button>
        </div>
      </form>

      {pagination && (
        <div>
          <p>Showing {pagination.from} to {pagination.to} of {pagination.total} members</p>
        </div>
      )}

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Member ID</th>
            <th>Primary Type</th>
            <th>Secondary Type</th>
            <th>Email Verified</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          {members.length === 0 ? (
            <tr>
              <td colSpan={8}>No members found</td>
            </tr>
          ) : (
            members.map((member) => (
              <tr key={member.id}>
                <td>{member.id}</td>
                <td>{member.name}</td>
                <td>{member.email}</td>
                <td>{member.member_id || 'N/A'}</td>
                <td>{member.primary_member_type || 'N/A'}</td>
                <td>{member.secondary_member_type?.name || 'N/A'}</td>
                <td>{member.email_verified_at ? 'Yes' : 'No'}</td>
                <td>{new Date(member.created_at).toLocaleDateString()}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>

      {pagination && pagination.last_page > 1 && (
        <div>
          <button
            disabled={currentPage === 1}
            onClick={() => setCurrentPage(currentPage - 1)}
          >
            Previous
          </button>
          <span>Page {pagination.current_page} of {pagination.last_page}</span>
          <button
            disabled={currentPage === pagination.last_page}
            onClick={() => setCurrentPage(currentPage + 1)}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}

export default MemberList;
```

### React Example: Member Detail Component

```tsx
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiClient } from './apiClient';

interface Member {
  id: number;
  name: string;
  email: string;
  role: string;
  primary_member_type: string | null;
  secondary_member_type: {
    id: number;
    name: string;
    description: string;
  } | null;
  member_id: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

function MemberDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [member, setMember] = useState<Member | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadMember();
  }, [id]);

  const loadMember = async () => {
    if (!id) {
      setError('Member ID is required');
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError(null);
      const response = await apiClient.getMember(Number(id));
      setMember(response.data);
    } catch (err: any) {
      setError(err.message || 'Failed to load member details');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!member) return <div>Member not found</div>;

  return (
    <div>
      <button onClick={() => navigate('/members')}>Back to Members</button>
      <h1>Member Details</h1>
      
      <div>
        <h2>Basic Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>ID:</strong></td>
              <td>{member.id}</td>
            </tr>
            <tr>
              <td><strong>Name:</strong></td>
              <td>{member.name}</td>
            </tr>
            <tr>
              <td><strong>Email:</strong></td>
              <td>{member.email}</td>
            </tr>
            <tr>
              <td><strong>Member ID:</strong></td>
              <td>{member.member_id || 'N/A'}</td>
            </tr>
            <tr>
              <td><strong>Role:</strong></td>
              <td>{member.role}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h2>Membership Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>Primary Member Type:</strong></td>
              <td>{member.primary_member_type || 'N/A'}</td>
            </tr>
            <tr>
              <td><strong>Secondary Member Type:</strong></td>
              <td>
                {member.secondary_member_type ? (
                  <div>
                    <strong>{member.secondary_member_type.name}</strong>
                    {member.secondary_member_type.description && (
                      <p>{member.secondary_member_type.description}</p>
                    )}
                  </div>
                ) : (
                  'N/A'
                )}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h2>Account Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>Email Verified:</strong></td>
              <td>
                {member.email_verified_at ? (
                  <span>Yes ({new Date(member.email_verified_at).toLocaleString()})</span>
                ) : (
                  'No'
                )}
              </td>
            </tr>
            <tr>
              <td><strong>Account Created:</strong></td>
              <td>{new Date(member.created_at).toLocaleString()}</td>
            </tr>
            <tr>
              <td><strong>Last Updated:</strong></td>
              <td>{new Date(member.updated_at).toLocaleString()}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberDetail;
```

### Vue.js Example: Member List Component

```vue
<template>
  <div>
    <h1>Members</h1>
    
    <form @submit.prevent="handleSearch">
      <div>
        <input
          v-model="search"
          type="text"
          placeholder="Search by name, email, or member ID"
        />
        <select v-model="primaryMemberType" @change="handleFilterChange">
          <option value="">All Types</option>
          <option value="GENERAL">General</option>
          <option value="LIFETIME">Lifetime</option>
          <option value="ASSOCIATE">Associate</option>
        </select>
        <button type="submit">Search</button>
        <button type="button" @click="handleClearSearch">Clear</button>
      </div>
    </form>

    <div v-if="pagination">
      <p>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} members</p>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Member ID</th>
          <th>Primary Type</th>
          <th>Secondary Type</th>
          <th>Email Verified</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="members.length === 0">
          <td colspan="8">No members found</td>
        </tr>
        <tr v-for="member in members" :key="member.id">
          <td>{{ member.id }}</td>
          <td>{{ member.name }}</td>
          <td>{{ member.email }}</td>
          <td>{{ member.member_id || 'N/A' }}</td>
          <td>{{ member.primary_member_type || 'N/A' }}</td>
          <td>{{ member.secondary_member_type?.name || 'N/A' }}</td>
          <td>{{ member.email_verified_at ? 'Yes' : 'No' }}</td>
          <td>{{ new Date(member.created_at).toLocaleDateString() }}</td>
        </tr>
      </tbody>
    </table>

    <div v-if="pagination && pagination.last_page > 1">
      <button
        :disabled="currentPage === 1"
        @click="currentPage--"
      >
        Previous
      </button>
      <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <button
        :disabled="currentPage === pagination.last_page"
        @click="currentPage++"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { apiClient } from './apiClient';

interface Member {
  id: number;
  name: string;
  email: string;
  role: string;
  primary_member_type: string | null;
  secondary_member_type: {
    id: number;
    name: string;
    description: string;
  } | null;
  member_id: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

interface PaginationMeta {
  current_page: number;
  from: number;
  last_page: number;
  path: string;
  per_page: number;
  to: number;
  total: number;
}

const members = ref<Member[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const search = ref('');
const primaryMemberType = ref('');
const currentPage = ref(1);
const pagination = ref<PaginationMeta | null>(null);

watch([currentPage, primaryMemberType], () => {
  loadMembers();
});

const loadMembers = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await apiClient.getMembers(
      search.value || undefined,
      primaryMemberType.value || undefined,
      currentPage.value
    );
    members.value = response.data;
    pagination.value = response.meta;
  } catch (err: any) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  loadMembers();
};

const handleFilterChange = () => {
  currentPage.value = 1;
  loadMembers();
};

const handleClearSearch = () => {
  search.value = '';
  primaryMemberType.value = '';
  currentPage.value = 1;
  loadMembers();
};

loadMembers();
</script>
```

### Vue.js Example: Member Detail Component

```vue
<template>
  <div>
    <button @click="$router.push('/members')">Back to Members</button>
    <h1>Member Details</h1>
    
    <div v-if="loading">Loading...</div>
    <div v-else-if="error">Error: {{ error }}</div>
    <div v-else-if="!member">Member not found</div>
    <div v-else>
      <div>
        <h2>Basic Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>ID:</strong></td>
              <td>{{ member.id }}</td>
            </tr>
            <tr>
              <td><strong>Name:</strong></td>
              <td>{{ member.name }}</td>
            </tr>
            <tr>
              <td><strong>Email:</strong></td>
              <td>{{ member.email }}</td>
            </tr>
            <tr>
              <td><strong>Member ID:</strong></td>
              <td>{{ member.member_id || 'N/A' }}</td>
            </tr>
            <tr>
              <td><strong>Role:</strong></td>
              <td>{{ member.role }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h2>Membership Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>Primary Member Type:</strong></td>
              <td>{{ member.primary_member_type || 'N/A' }}</td>
            </tr>
            <tr>
              <td><strong>Secondary Member Type:</strong></td>
              <td>
                <div v-if="member.secondary_member_type">
                  <strong>{{ member.secondary_member_type.name }}</strong>
                  <p v-if="member.secondary_member_type.description">
                    {{ member.secondary_member_type.description }}
                  </p>
                </div>
                <span v-else>N/A</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h2>Account Information</h2>
        <table>
          <tbody>
            <tr>
              <td><strong>Email Verified:</strong></td>
              <td>
                <span v-if="member.email_verified_at">
                  Yes ({{ new Date(member.email_verified_at).toLocaleString() }})
                </span>
                <span v-else>No</span>
              </td>
            </tr>
            <tr>
              <td><strong>Account Created:</strong></td>
              <td>{{ new Date(member.created_at).toLocaleString() }}</td>
            </tr>
            <tr>
              <td><strong>Last Updated:</strong></td>
              <td>{{ new Date(member.updated_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClient } from './apiClient';

interface Member {
  id: number;
  name: string;
  email: string;
  role: string;
  primary_member_type: string | null;
  secondary_member_type: {
    id: number;
    name: string;
    description: string;
  } | null;
  member_id: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

const route = useRoute();
const router = useRouter();

const member = ref<Member | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const loadMember = async () => {
  const id = route.params.id as string;
  
  if (!id) {
    error.value = 'Member ID is required';
    loading.value = false;
    return;
  }

  try {
    loading.value = true;
    error.value = null;
    const response = await apiClient.getMember(Number(id));
    member.value = response.data;
  } catch (err: any) {
    error.value = err.message || 'Failed to load member details';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadMember();
});
</script>
```

## Best Practices

### Security

1. **Token Storage**
   - Store tokens securely (consider using httpOnly cookies for production)
   - Never expose tokens in URLs or logs
   - Implement token refresh mechanism if needed

2. **Authorization Checks**
   - Always verify user role on the frontend before showing member management UI
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

2. **Search and Filtering**
   - Debounce search input to avoid excessive API calls
   - Remember filter preferences
   - Provide clear visual feedback for active filters
   - Show empty state when no results are found

3. **Pagination**
   - Implement pagination for member lists
   - Show total count and current page
   - Provide navigation controls (first, previous, next, last)
   - Display range of items being shown (e.g., "Showing 1-15 of 150")

4. **Data Display**
   - Format dates in a user-friendly format
   - Handle null values gracefully (show "N/A" or similar)
   - Make tables responsive for mobile devices
   - Consider adding sorting functionality

### Performance

1. **Caching**
   - Cache member lists when appropriate
   - Refresh data after mutations
   - Consider implementing optimistic updates

2. **Debouncing**
   - Debounce search inputs (wait 300-500ms after user stops typing)
   - Avoid unnecessary API calls

3. **Lazy Loading**
   - Load member details on demand
   - Consider implementing infinite scroll for large lists

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
- [ ] View list of members
- [ ] Test pagination (next, previous, first, last)
- [ ] Search members by name
- [ ] Search members by email
- [ ] Search members by member ID
- [ ] Filter by primary member type (GENERAL, LIFETIME, ASSOCIATE)
- [ ] Combine search and filter
- [ ] Handle empty search results
- [ ] Handle errors (403, 401, network errors)
- [ ] Verify member details are displayed correctly
- [ ] Check secondary member type display (when present and when null)
- [ ] Verify email verification status display
- [ ] View individual member details
- [ ] Handle 404 error when member not found
- [ ] Navigate between member list and detail views

### API Testing Tools

You can test endpoints using:
- **Postman** - Create a collection with all endpoints
- **cURL** - Command-line testing
- **Browser DevTools** - Network tab for debugging

### Example cURL Commands

```bash
# Get all members (replace TOKEN with actual token)
curl -X GET http://localhost:8000/api/members \
  -H "Authorization: Bearer TOKEN"

# Search for members
curl -X GET "http://localhost:8000/api/members?search=john" \
  -H "Authorization: Bearer TOKEN"

# Filter by primary member type
curl -X GET "http://localhost:8000/api/members?primary_member_type=GENERAL" \
  -H "Authorization: Bearer TOKEN"

# Combined search and filter with pagination
curl -X GET "http://localhost:8000/api/members?search=john&primary_member_type=GENERAL&page=2" \
  -H "Authorization: Bearer TOKEN"

# Get individual member details
curl -X GET http://localhost:8000/api/members/2 \
  -H "Authorization: Bearer TOKEN"
```

## Support

For issues or questions, please contact the backend development team or refer to the API documentation.
