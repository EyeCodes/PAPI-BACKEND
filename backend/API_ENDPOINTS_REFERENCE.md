# API Endpoints Reference

## Overview
This document provides all API endpoints with JSON examples for the Loyalty System. All endpoints use Sanctum authentication.

## Authentication
All endpoints require the following header:
```
Authorization: Bearer {token}
```

## Financial API Endpoints

### 1. Get Assets
**Endpoint:** `GET /api/assets`

**Response:**
```json
{
    "success": true,
    "data": {
        "assets": [
            {
                "id": 1,
                "user_id": 2,
                "title": "Ferrari",
                "amount": 8000000.00,
                "asset_type": "asset",
                "asset_value": 8000000.00,
                "liability_amount": null,
                "ai_categorized_category": "Asset",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            },
            {
                "id": 2,
                "user_id": 2,
                "title": "House",
                "amount": 10000000.00,
                "asset_type": "asset",
                "asset_value": 10000000.00,
                "liability_amount": null,
                "ai_categorized_category": "Asset",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "summary": {
            "salary": 50000.00,
            "assets": 18000000.00,
            "liabilities": 0.00,
            "expenses": 197281140.00,
            "net_worth": 18000000.00
        }
    }
}
```

### 2. Get Liabilities
**Endpoint:** `GET /api/liabilities`

**Response:**
```json
{
    "success": true,
    "data": {
        "liabilities": [
            {
                "id": 3,
                "user_id": 2,
                "title": "Car Loan",
                "amount": 4000000.00,
                "asset_type": "liability",
                "asset_value": null,
                "liability_amount": 4000000.00,
                "ai_categorized_category": "Liability",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            },
            {
                "id": 4,
                "user_id": 2,
                "title": "Mortgage",
                "amount": 8000000.00,
                "asset_type": "liability",
                "asset_value": null,
                "liability_amount": 8000000.00,
                "ai_categorized_category": "Liability",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "summary": {
            "salary": 50000.00,
            "assets": 18000000.00,
            "liabilities": 12000000.00,
            "expenses": 197281140.00,
            "net_worth": 6000000.00
        }
    }
}
```

### 3. Get Financial Info
**Endpoint:** `GET /api/financial-info`

**Response:**
```json
{
    "success": true,
    "data": {
        "salary": 50000.00,
        "assets": 18000000.00,
        "liabilities": 12000000.00,
        "expenses": 197281140.00,
        "net_worth": 6000000.00
    }
}
```

## Profile Management API Endpoints

### 4. Get Profile
**Endpoint:** `GET /api/profile`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Juan Dela Cruz",
        "email": "juan@example.com",
        "salary": 50000.00,
        "email_verified_at": "2024-01-15T10:30:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

### 5. Update Profile
**Endpoint:** `PUT /api/profile`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body (Full Update):**
```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "salary": 75000.00
}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "salary": 75000.00,
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

**Partial Update Examples:**

**Update Only Name:**
```json
{
    "name": "Jane Doe"
}
```

**Update Only Salary:**
```json
{
    "salary": 100000.00
}
```

**Update Only Email:**
```json
{
    "email": "jane@example.com"
}
```

### 6. Change Password
**Endpoint:** `POST /api/change-password`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

### 7. Check Salary
**Endpoint:** `GET /api/check-salary`

**Response (Salary Set):**
```json
{
    "success": true,
    "data": {
        "has_salary": true,
        "salary": 50000.00,
        "message": "Salary is set"
    }
}
```

**Response (Salary Not Set):**
```json
{
    "success": true,
    "data": {
        "has_salary": false,
        "salary": null,
        "message": "Salary is not set"
    }
}
```

## Error Response Examples

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "salary": ["The salary must be at least 0."]
    }
}
```

### Authentication Error (401)
```json
{
    "message": "Unauthenticated."
}
```

### Password Error (400)
```json
{
    "success": false,
    "message": "Current password is incorrect"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Failed to update profile",
    "error": "Error details"
}
```

## Complete Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/assets` | Get user assets with financial summary |
| GET | `/api/liabilities` | Get user liabilities with financial summary |
| GET | `/api/financial-info` | Get overall financial summary |
| GET | `/api/profile` | Get user profile information |
| PUT | `/api/profile` | Update user profile |
| POST | `/api/change-password` | Change user password |
| GET | `/api/check-salary` | Check if salary is set |

## Validation Rules

### Profile Update Validation
- **name**: string, max 255 characters
- **email**: valid email format, unique (excluding current user)
- **salary**: numeric, minimum 0, maximum 999,999,999.99

### Password Change Validation
- **current_password**: required, string
- **new_password**: required, string, minimum 8 characters
- **new_password_confirmation**: required, string, must match new_password

## Financial Summary Fields

### Summary Object Fields
- **salary**: User's monthly salary (decimal)
- **assets**: Total value of all assets (decimal)
- **liabilities**: Total amount of all liabilities (decimal)
- **expenses**: Total amount of all purchases (decimal)
- **net_worth**: Assets - Liabilities (decimal)

### Asset/Liability Object Fields
- **id**: Unique identifier
- **user_id**: User who owns the item
- **title**: Name/description of the item
- **amount**: Original purchase amount
- **asset_type**: "asset", "liability", or null for expenses
- **asset_value**: Value for assets (null for liabilities/expenses)
- **liability_amount**: Amount for liabilities (null for assets/expenses)
- **ai_categorized_category**: AI categorization result
- **created_at**: Creation timestamp
- **updated_at**: Last update timestamp 
