# Frontend Integration Guide: Member Submission

This guide provides all the information needed to integrate the membership application submission feature into your frontend application.

## Table of Contents

- [API Endpoint](#api-endpoint)
- [Authentication](#authentication)
- [Request Format](#request-format)
- [Field Specifications](#field-specifications)
- [Response Structure](#response-structure)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
- [Fee Calculation](#fee-calculation)
- [File Upload Requirements](#file-upload-requirements)

## API Endpoint

**URL:** `POST /api/membership-applications`

**Base URL:** `http://localhost:8000` (or your configured API base URL)

**Authentication:** Not required (public endpoint)

**Content-Type:** `multipart/form-data` (required for file uploads)

## Authentication

This endpoint is **public** and does not require authentication. Anyone can submit a membership application.

## Request Format

The request must be sent as `multipart/form-data` to support file uploads. Use `FormData` when making the request.

### Required Fields

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `membership_type` | string | Required, one of: `GENERAL`, `LIFETIME`, `ASSOCIATE` | Type of membership |
| `full_name` | string | Required, max 255 characters | Full name in English |
| `name_bangla` | string | Required, max 255 characters | Name in Bengali |
| `father_name` | string | Required, max 255 characters | Father's name |
| `gender` | string | Required, one of: `MALE`, `FEMALE`, `OTHER` | Gender |
| `present_address` | string | Required | Current address |
| `permanent_address` | string | Required | Permanent address |
| `mobile_number` | string | Required, max 20 characters | Mobile phone number |
| `profession` | string | Required, max 255 characters | Profession |
| `t_shirt_size` | string | Required, one of: `XXXL`, `XXL`, `XL`, `L`, `M`, `S` | T-shirt size |
| `blood_group` | string | Required, one of: `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-` | Blood group |
| `payment_years` | integer | Required, one of: `1`, `2`, `3` | Number of years to pay for |

### Optional Fields

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `mother_name` | string | Optional, max 255 characters | Mother's name |
| `jsc_year` | integer | Optional, min: 1900, max: current year | JSC passing year |
| `ssc_year` | integer | Optional, min: 1900, max: current year | SSC passing year |
| `studentship_proof_type` | string | Optional, one of: `JSC`, `EIGHT`, `SSC`, `METRIC_CERTIFICATE`, `MARK_SHEET`, `OTHERS` | Type of studentship proof document |
| `studentship_proof_file` | file | Optional, mimes: `pdf,jpg,jpeg,png`, max: 5120 KB (5 MB) | Studentship proof document |
| `highest_educational_degree` | string | Optional, max 255 characters | Highest educational degree |
| `email` | string | Optional, valid email format, max 255 characters | Email address |
| `designation` | string | Optional, max 255 characters | Job designation |
| `institute_name` | string | Optional, max 255 characters | Institute/Organization name |
| `entry_fee` | number | Optional, min: 0 | Entry fee amount |
| `receipt_file` | file | Optional, mimes: `pdf,jpg,jpeg,png`, max: 5120 KB (5 MB) | Payment receipt document |

## Response Structure

### Success Response (201 Created)

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

### Error Response (422 Unprocessable Entity)

When validation fails, the API returns a 422 status with validation errors:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "membership_type": [
      "The membership type field is required."
    ],
    "full_name": [
      "The full name field is required."
    ],
    "email": [
      "The email must be a valid email address."
    ],
    "studentship_proof_file": [
      "The studentship proof file must not be greater than 5120 kilobytes.",
      "The studentship proof file must be a file of type: pdf, jpg, jpeg, png."
    ]
  }
}
```

## Error Handling

### HTTP Status Codes

- `201 Created` - Application submitted successfully
- `422 Unprocessable Entity` - Validation errors
- `500 Internal Server Error` - Server error

### Common Validation Errors

1. **Missing required fields** - All required fields must be provided
2. **Invalid enum values** - Use exact values for `membership_type`, `gender`, `t_shirt_size`, `blood_group`, etc.
3. **File size exceeded** - Files must be 5 MB or less
4. **Invalid file type** - Only PDF, JPG, JPEG, PNG files are allowed
5. **Invalid year range** - Years must be between 1900 and current year
6. **Invalid email format** - Email must be a valid email address

## Code Examples

### JavaScript/TypeScript with Fetch API

```typescript
interface MembershipApplicationData {
  membership_type: 'GENERAL' | 'LIFETIME' | 'ASSOCIATE';
  full_name: string;
  name_bangla: string;
  father_name: string;
  mother_name?: string;
  gender: 'MALE' | 'FEMALE' | 'OTHER';
  jsc_year?: number;
  ssc_year?: number;
  studentship_proof_type?: 'JSC' | 'EIGHT' | 'SSC' | 'METRIC_CERTIFICATE' | 'MARK_SHEET' | 'OTHERS';
  studentship_proof_file?: File;
  highest_educational_degree?: string;
  present_address: string;
  permanent_address: string;
  email?: string;
  mobile_number: string;
  profession: string;
  designation?: string;
  institute_name?: string;
  t_shirt_size: 'XXXL' | 'XXL' | 'XL' | 'L' | 'M' | 'S';
  blood_group: 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';
  entry_fee?: number;
  payment_years: 1 | 2 | 3;
  receipt_file?: File;
}

async function submitMembershipApplication(
  data: MembershipApplicationData,
  apiBaseUrl: string = 'http://localhost:8000'
): Promise<any> {
  const formData = new FormData();

  // Add all text fields
  formData.append('membership_type', data.membership_type);
  formData.append('full_name', data.full_name);
  formData.append('name_bangla', data.name_bangla);
  formData.append('father_name', data.father_name);
  
  if (data.mother_name) {
    formData.append('mother_name', data.mother_name);
  }
  
  formData.append('gender', data.gender);
  
  if (data.jsc_year) {
    formData.append('jsc_year', data.jsc_year.toString());
  }
  
  if (data.ssc_year) {
    formData.append('ssc_year', data.ssc_year.toString());
  }
  
  if (data.studentship_proof_type) {
    formData.append('studentship_proof_type', data.studentship_proof_type);
  }
  
  if (data.studentship_proof_file) {
    formData.append('studentship_proof_file', data.studentship_proof_file);
  }
  
  if (data.highest_educational_degree) {
    formData.append('highest_educational_degree', data.highest_educational_degree);
  }
  
  formData.append('present_address', data.present_address);
  formData.append('permanent_address', data.permanent_address);
  
  if (data.email) {
    formData.append('email', data.email);
  }
  
  formData.append('mobile_number', data.mobile_number);
  formData.append('profession', data.profession);
  
  if (data.designation) {
    formData.append('designation', data.designation);
  }
  
  if (data.institute_name) {
    formData.append('institute_name', data.institute_name);
  }
  
  formData.append('t_shirt_size', data.t_shirt_size);
  formData.append('blood_group', data.blood_group);
  
  if (data.entry_fee !== undefined) {
    formData.append('entry_fee', data.entry_fee.toString());
  }
  
  formData.append('payment_years', data.payment_years.toString());
  
  if (data.receipt_file) {
    formData.append('receipt_file', data.receipt_file);
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/membership-applications`, {
      method: 'POST',
      body: formData,
      // Don't set Content-Type header - browser will set it automatically with boundary
    });

    const responseData = await response.json();

    if (!response.ok) {
      // Handle validation errors
      if (response.status === 422) {
        throw new Error(JSON.stringify(responseData.errors));
      }
      throw new Error(responseData.message || 'Failed to submit application');
    }

    return responseData;
  } catch (error) {
    console.error('Error submitting membership application:', error);
    throw error;
  }
}

// Usage example
const applicationData: MembershipApplicationData = {
  membership_type: 'GENERAL',
  full_name: 'John Doe',
  name_bangla: 'জন ডো',
  father_name: 'Father Name',
  gender: 'MALE',
  jsc_year: 2010,
  ssc_year: 2012,
  present_address: '123 Main St',
  permanent_address: '456 Oak Ave',
  mobile_number: '1234567890',
  profession: 'Engineer',
  t_shirt_size: 'L',
  blood_group: 'O+',
  payment_years: 1,
  email: 'john@example.com',
};

// If you have file inputs
const studentshipProofFile = document.querySelector('#studentship-proof')?.files[0];
if (studentshipProofFile) {
  applicationData.studentship_proof_file = studentshipProofFile;
}

submitMembershipApplication(applicationData)
  .then((result) => {
    console.log('Application submitted successfully:', result);
    // Handle success (e.g., show success message, redirect)
  })
  .catch((error) => {
    console.error('Submission failed:', error);
    // Handle error (e.g., show error message, display validation errors)
  });
```

### React Example with Axios

```tsx
import axios from 'axios';
import { useState } from 'react';

interface FormData {
  membership_type: 'GENERAL' | 'LIFETIME' | 'ASSOCIATE';
  full_name: string;
  name_bangla: string;
  father_name: string;
  mother_name?: string;
  gender: 'MALE' | 'FEMALE' | 'OTHER';
  jsc_year?: number;
  ssc_year?: number;
  studentship_proof_file?: File;
  present_address: string;
  permanent_address: string;
  email?: string;
  mobile_number: string;
  profession: string;
  designation?: string;
  institute_name?: string;
  t_shirt_size: 'XXXL' | 'XXL' | 'XL' | 'L' | 'M' | 'S';
  blood_group: 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';
  entry_fee?: number;
  payment_years: 1 | 2 | 3;
  receipt_file?: File;
}

function MembershipApplicationForm() {
  const [formData, setFormData] = useState<FormData>({
    membership_type: 'GENERAL',
    full_name: '',
    name_bangla: '',
    father_name: '',
    gender: 'MALE',
    present_address: '',
    permanent_address: '',
    mobile_number: '',
    profession: '',
    t_shirt_size: 'L',
    blood_group: 'O+',
    payment_years: 1,
  });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});
    setSuccess(false);

    const formDataToSend = new FormData();
    
    // Append all fields to FormData
    Object.entries(formData).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        if (value instanceof File) {
          formDataToSend.append(key, value);
        } else if (typeof value === 'number') {
          formDataToSend.append(key, value.toString());
        } else {
          formDataToSend.append(key, value);
        }
      }
    });

    try {
      const response = await axios.post(
        'http://localhost:8000/api/membership-applications',
        formDataToSend,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );

      setSuccess(true);
      console.log('Application submitted:', response.data);
      // Reset form or redirect
    } catch (error: any) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors || {});
      } else {
        console.error('Error:', error);
        // Handle other errors
      }
    } finally {
      setLoading(false);
    }
  };

  const handleFileChange = (field: 'studentship_proof_file' | 'receipt_file', e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setFormData((prev) => ({ ...prev, [field]: file }));
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Form fields */}
      <div>
        <label>Membership Type</label>
        <select
          value={formData.membership_type}
          onChange={(e) => setFormData({ ...formData, membership_type: e.target.value as any })}
        >
          <option value="GENERAL">General</option>
          <option value="LIFETIME">Lifetime</option>
          <option value="ASSOCIATE">Associate</option>
        </select>
        {errors.membership_type && <div className="error">{errors.membership_type[0]}</div>}
      </div>

      {/* Add other form fields similarly */}
      
      <div>
        <label>Studentship Proof File</label>
        <input
          type="file"
          accept=".pdf,.jpg,.jpeg,.png"
          onChange={(e) => handleFileChange('studentship_proof_file', e)}
        />
        {errors.studentship_proof_file && <div className="error">{errors.studentship_proof_file[0]}</div>}
      </div>

      <div>
        <label>Receipt File</label>
        <input
          type="file"
          accept=".pdf,.jpg,.jpeg,.png"
          onChange={(e) => handleFileChange('receipt_file', e)}
        />
        {errors.receipt_file && <div className="error">{errors.receipt_file[0]}</div>}
      </div>

      <button type="submit" disabled={loading}>
        {loading ? 'Submitting...' : 'Submit Application'}
      </button>

      {success && <div className="success">Application submitted successfully!</div>}
    </form>
  );
}
```

### Vue.js Example

```vue
<template>
  <form @submit.prevent="submitApplication">
    <div>
      <label>Membership Type</label>
      <select v-model="formData.membership_type">
        <option value="GENERAL">General</option>
        <option value="LIFETIME">Lifetime</option>
        <option value="ASSOCIATE">Associate</option>
      </select>
      <div v-if="errors.membership_type" class="error">
        {{ errors.membership_type[0] }}
      </div>
    </div>

    <!-- Add other form fields -->

    <div>
      <label>Studentship Proof File</label>
      <input
        type="file"
        accept=".pdf,.jpg,.jpeg,.png"
        @change="handleFileChange('studentship_proof_file', $event)"
      />
      <div v-if="errors.studentship_proof_file" class="error">
        {{ errors.studentship_proof_file[0] }}
      </div>
    </div>

    <button type="submit" :disabled="loading">
      {{ loading ? 'Submitting...' : 'Submit Application' }}
    </button>

    <div v-if="success" class="success">
      Application submitted successfully!
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const formData = ref({
  membership_type: 'GENERAL',
  full_name: '',
  name_bangla: '',
  father_name: '',
  gender: 'MALE',
  present_address: '',
  permanent_address: '',
  mobile_number: '',
  profession: '',
  t_shirt_size: 'L',
  blood_group: 'O+',
  payment_years: 1,
});

const errors = ref<Record<string, string[]>>({});
const loading = ref(false);
const success = ref(false);

const handleFileChange = (field: string, event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files?.[0]) {
    formData.value[field] = input.files[0];
  }
};

const submitApplication = async () => {
  loading.value = true;
  errors.value = {};
  success.value = false;

  const formDataToSend = new FormData();
  
  Object.entries(formData.value).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      if (value instanceof File) {
        formDataToSend.append(key, value);
      } else if (typeof value === 'number') {
        formDataToSend.append(key, value.toString());
      } else {
        formDataToSend.append(key, value);
      }
    }
  });

  try {
    const response = await axios.post(
      'http://localhost:8000/api/membership-applications',
      formDataToSend,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    success.value = true;
    console.log('Application submitted:', response.data);
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    } else {
      console.error('Error:', error);
    }
  } finally {
    loading.value = false;
  }
};
</script>
```

## Fee Calculation

The backend automatically calculates fees based on the membership type and payment years. You don't need to calculate these on the frontend, but here's the information for reference:

### Yearly Fees

- **GENERAL**: 500.0
- **LIFETIME**: 10,000.0
- **ASSOCIATE**: 300.0

### Total Paid Amount Formula

```
total_paid_amount = (yearly_fee × payment_years) + entry_fee
```

### Examples

- GENERAL membership, 1 year, no entry fee: `500 × 1 + 0 = 500`
- GENERAL membership, 2 years, 100 entry fee: `500 × 2 + 100 = 1,100`
- LIFETIME membership, 1 year: `10,000 × 1 + 0 = 10,000`
- ASSOCIATE membership, 3 years, 50 entry fee: `300 × 3 + 50 = 950`

The calculated `yearly_fee` and `total_paid_amount` are returned in the response.

## File Upload Requirements

### Supported File Types

- PDF (`.pdf`)
- Images: JPG (`.jpg`), JPEG (`.jpeg`), PNG (`.png`)

### File Size Limits

- Maximum file size: **5 MB (5120 KB)** per file
- Both `studentship_proof_file` and `receipt_file` have the same limits

### File Upload Best Practices

1. **Validate files on the frontend** before submission:
   - Check file size (should be ≤ 5 MB)
   - Check file type (should be PDF, JPG, JPEG, or PNG)
   - Provide user feedback for invalid files

2. **Show upload progress** for better UX (especially for large files)

3. **Preview files** before submission if possible

4. **Handle file upload errors** gracefully

### Frontend File Validation Example

```typescript
function validateFile(file: File): { valid: boolean; error?: string } {
  const maxSize = 5 * 1024 * 1024; // 5 MB in bytes
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
  
  if (file.size > maxSize) {
    return { valid: false, error: 'File size must be less than 5 MB' };
  }
  
  if (!allowedTypes.includes(file.type)) {
    return { valid: false, error: 'File must be PDF, JPG, JPEG, or PNG' };
  }
  
  return { valid: true };
}

// Usage
const fileInput = document.querySelector('#file-input') as HTMLInputElement;
fileInput.addEventListener('change', (e) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    const validation = validateFile(file);
    if (!validation.valid) {
      alert(validation.error);
      fileInput.value = ''; // Clear the input
    }
  }
});
```

## Important Notes

1. **Content-Type Header**: When using `FormData`, do NOT manually set the `Content-Type` header. The browser will automatically set it with the correct boundary parameter.

2. **File URLs**: The response includes full URLs for uploaded files (e.g., `studentship_proof_file` and `receipt_file`). These URLs point to the public storage location.

3. **Application Status**: New applications are created with status `PENDING`. They require admin approval before a user account is created.

4. **Email Requirement**: While `email` is optional for submission, it's required if the application needs to be approved (admin action). Consider making it required in your frontend form.

5. **Member ID Generation**: Member IDs are automatically generated when an application is approved by an admin. The format depends on membership type and year (e.g., `G-2020-0001` for GENERAL membership with SSC year 2020).

6. **CORS**: Ensure your backend CORS configuration allows requests from your frontend domain.

## Testing

You can test the endpoint using tools like:

- **Postman**: Create a POST request with form-data body
- **cURL**: Use `-F` flag for file uploads
- **Browser DevTools**: Use the Network tab to inspect requests

### cURL Example

```bash
curl -X POST http://localhost:8000/api/membership-applications \
  -F "membership_type=GENERAL" \
  -F "full_name=John Doe" \
  -F "name_bangla=জন ডো" \
  -F "father_name=Father Name" \
  -F "gender=MALE" \
  -F "present_address=123 Main St" \
  -F "permanent_address=456 Oak Ave" \
  -F "mobile_number=1234567890" \
  -F "profession=Engineer" \
  -F "t_shirt_size=L" \
  -F "blood_group=O+" \
  -F "payment_years=1" \
  -F "studentship_proof_file=@/path/to/file.pdf"
```

## Support

For issues or questions, please contact the backend development team or refer to the API documentation.
