# 🚀 Mstore API Guide - Products & Authentication

Welcome! This guide is specifically designed for the **Pearl XP** team to integrate with the Mstore backend. It focuses on the core functionalities: **Product Management**, **Categories**, **Attributes**, and **Authentication**.

---

## 🔐 Authentication

All authenticated requests must include the `Authorization` header with a valid Bearer token.

**Base URL:** `https://mstore.primeads.ai/api`

### 1. Login
Authenticate and receive a token.
- **Endpoint:** `POST /login`
- **Body:**
  ```json
  {
    "email": "admin@example.com",
    "password": "yourpassword"
  }
  ```
- **Response:**
  ```json
  {
    "access_token": "1|abc123xyz...",
    "permissions": ["product.create", "category.index"],
    "success": true
  }
  ```

### 2. Logout
Revoke the current access token.
- **Endpoint:** `POST /logout`
- **Headers:** `Authorization: Bearer <token>`

---

## 📦 Product Management

Manage the catalog, including both simple and variable (classified) products.

### 1. List Products
- **Endpoint:** `GET /product`
- **Filters:** `category`, `min_price`, `max_price`, `status`

### 2. Create Product (Simple)
- **Endpoint:** `POST /product`
- **Body:**
  ```json
  {
    "name": "Classic T-Shirt",
    "type": "simple",
    "price": 25.00,
    "sale_price": 22.00,
    "cost": 15.50,
    "discount": 10,
    "quantity": 100,
    "sku": "TSHIRT-001",
    "categories": [1, 5],
    "status": 1,
    "product_thumbnail_id": 123
  }
  ```

### 3. Update Product
- **Endpoint:** `PUT /product/{id}`
- **Body:** (Partial updates supported, including `cost`)

### 4. Toggle Status
- **Endpoint:** `PUT /product/{id}/{status}`
  - Example: `/api/product/15/0` to deactivate product ID 15.

---

## 🛠 Supporting Data

Use these to populate dropdowns or relate to products.

### 1. Categories
- `GET /category`: List all categories.
- `POST /category`: Create a category.

### 2. Attributes
- `GET /attribute`: List attributes (e.g., Color, Size).
- `GET /attribute-value`: List values for attributes.

---

## ⚠️ Response Codes

| Code | Description |
| :--- | :--- |
| `200` | Success |
| `201` | Created successfully |
| `400` | Bad Request (Check parameters) |
| `401` | Unauthenticated (Missing or invalid token) |
| `403` | Forbidden (Insufficient permissions) |
| `422` | Validation Error (Check `errors` field in response) |

---

**Built by DMSG Team.**
For support, contact the system administrator.
