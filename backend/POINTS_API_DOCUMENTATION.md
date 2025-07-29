# Points API Documentation

## Overview

The Points API provides comprehensive functionality for managing loyalty points across multiple merchants. All endpoints support both Laravel Sanctum and Firebase authentication.

## Authentication

All endpoints require authentication. Use either:
- **Laravel Sanctum**: Include `Authorization: Bearer {token}` header
- **Firebase**: Include Firebase ID token in the request

## Base URLs

- **Sanctum**: `/api/points/`
- **Firebase**: `/api/firebase/points/`

## Endpoints

### 1. Get Points Summary
**GET** `/summary`

Returns a summary of user's points across all merchants.

**Response:**
```json
{
  "success": true,
  "message": "Points summary retrieved successfully",
  "data": {
    "user_id": 1,
    "points_summary": [
      {
        "merchant_name": "Coffee Shop",
        "points": 150,
        "total_earned": 200,
        "total_spent": 50,
        "last_earned": "2025-07-27T10:00:00.000000Z",
        "last_spent": "2025-07-26T15:30:00.000000Z"
      }
    ],
    "total_points": 150,
    "merchants_count": 1
  }
}
```

### 2. Get Merchant Points
**GET** `/merchant/{merchant_id}`

Returns user's points for a specific merchant.

**Parameters:**
- `merchant_id` (path): Merchant ID

**Response:**
```json
{
  "success": true,
  "message": "Merchant points retrieved successfully",
  "data": {
    "user_id": 1,
    "merchant_id": 1,
    "current_points": 150,
    "can_earn_points": true,
    "message": "You can earn points at this merchant"
  }
}
```

### 3. Get Merchant Points Info
**GET** `/merchant/{merchant_id}/info`

Returns information about a merchant's points program.

**Parameters:**
- `merchant_id` (path): Merchant ID

**Response:**
```json
{
  "success": true,
  "message": "Merchant points info retrieved successfully",
  "data": {
    "merchant_id": 1,
    "merchant_name": "Coffee Shop",
    "can_earn_points": true,
    "points_rules": [
      {
        "type": "fixed",
        "description": "Earn 10 points per purchase",
        "parameters": {
          "points": 10
        }
      }
    ]
  }
}
```

### 4. Get Points History
**GET** `/history/{merchant_id}`

Returns transaction history for a specific merchant.

**Parameters:**
- `merchant_id` (path): Merchant ID

**Response:**
```json
{
  "success": true,
  "message": "Points history retrieved successfully",
  "data": {
    "user_id": 1,
    "merchant_id": 1,
    "current_balance": 150,
    "total_earned": 200,
    "total_spent": 50,
    "transactions": [
      {
        "id": 1,
        "amount": 25.00,
        "awarded_points": 10,
        "created_at": "2025-07-27T10:00:00.000000Z"
      }
    ],
    "transaction_count": 1
  }
}
```

### 5. List Products
**GET** `/products/{merchant_id}`

Returns all products for a merchant with their points rules.

**Parameters:**
- `merchant_id` (path): Merchant ID

**Response:**
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": {
    "merchant_id": 1,
    "products": [
      {
        "id": 1,
        "name": "Espresso",
        "description": "Single shot espresso",
        "price": 3.50,
        "currency": "USD",
        "stock": 100,
        "points_rules": []
      }
    ],
    "count": 1
  }
}
```

### 6. List Transactions
**GET** `/transactions`

Returns user's transaction history across all merchants.

**Response:**
```json
{
  "success": true,
  "message": "Transactions retrieved successfully",
  "data": {
    "user_id": 1,
    "transactions": [
      {
        "id": 1,
        "merchant_id": 1,
        "amount": 25.00,
        "awarded_points": 10,
        "created_at": "2025-07-27T10:00:00.000000Z",
        "merchant": {
          "id": 1,
          "name": "Coffee Shop"
        },
        "items": [
          {
            "product": {
              "id": 1,
              "name": "Espresso",
              "price": 3.50
            }
          }
        ]
      }
    ],
    "count": 1
  }
}
```

### 7. Calculate Points
**POST** `/calculate`

Calculate potential points for a transaction without creating it.

**Request Body:**
```json
{
  "merchant_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Points calculated successfully",
  "data": {
    "merchant_id": 1,
    "amount": 7.00,
    "items_count": 1,
    "potential_points": 10
  }
}
```

### 8. Create Transaction
**POST** `/create-transaction`

Create a transaction and generate QR code for customer to scan.

**Request Body:**
```json
{
  "merchant_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "transaction_id": 1,
    "merchant_id": 1,
    "amount": 7.00,
    "potential_points": 10,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "items_count": 1,
    "created_at": "2025-07-27T10:00:00.000000Z"
  }
}
```

### 9. Scan QR Code
**POST** `/scan-qr`

Process QR code scan to award points to customer.

**Request Body:**
```json
{
  "qr_data": "encrypted_qr_data_or_base64_image"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Points awarded successfully",
  "data": {
    "user_id": 1,
    "transaction_id": 1,
    "points_awarded": 10,
    "new_balance": 160,
    "merchant_id": 1,
    "amount": 7.00
  }
}
```

### 10. Redeem Points
**POST** `/redeem`

Redeem points for a specific merchant.

**Request Body:**
```json
{
  "points": 50,
  "merchant_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Points redeemed successfully",
  "data": {
    "user_id": 1,
    "points_redeemed": 50,
    "new_balance": 110,
    "merchant_id": 1
  }
}
```

### 11. Transfer Points
**POST** `/transfer`

Transfer points between merchants.

**Request Body:**
```json
{
  "from_merchant_id": 1,
  "to_merchant_id": 2,
  "points": 25
}
```

**Response:**
```json
{
  "success": true,
  "message": "Points transferred successfully",
  "data": {
    "user_id": 1,
    "points_transferred": 25,
    "from_merchant_id": 1,
    "to_merchant_id": 2,
    "from_merchant_balance": 85,
    "to_merchant_balance": 25
  }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `400`: Bad Request (validation errors)
- `401`: Unauthorized (authentication required)
- `404`: Not Found
- `422`: Unprocessable Entity (validation errors)
- `500`: Internal Server Error

## Points Rules

The system supports various points earning rules:

1. **Fixed**: Earn a fixed number of points per transaction
2. **Dynamic**: Earn points based on transaction amount
3. **Combo**: Earn points based on amount and quantity
4. **Threshold**: Earn points only above a minimum amount
5. **First Purchase**: Earn bonus points on first purchase
6. **Limited Time**: Earn points within a date range
7. **Custom Formula**: Use custom mathematical formulas

## Features

- **Multi-merchant support**: Users can earn and spend points at different merchants
- **Points transfer**: Transfer points between merchants
- **QR code integration**: Easy point earning through QR code scanning
- **Transaction history**: Complete audit trail of all point activities
- **Flexible rules**: Configurable points earning rules per merchant
- **Real-time balance**: Always up-to-date point balances
- **Authentication support**: Works with both Sanctum and Firebase

## Rate Limiting

API endpoints are subject to rate limiting to prevent abuse. Contact support if you need higher limits.

## Support

For API support or questions, please contact the development team. 
