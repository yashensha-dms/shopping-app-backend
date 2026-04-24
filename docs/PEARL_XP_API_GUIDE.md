# 🚀 Mstore API Guide - Products & Users

Welcome! This guide is specifically designed for the **Pearl XP** team to integrate with the Mstore backend. It focuses on the core functionalities: **Product Management** and **User Management (Admins & Consumers)**.

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
    "permissions": ["user.edit", "product.create"],
    "success": true
  }
  ```

---

## 👤 User Management

Focuses on Admins and Consumers (Customers). Vendor management is excluded from this guide.

### 1. List Users
- **Endpoint:** `GET /user`
- **Query Params:** `role=consumer` or `role=admin`
- **Permissions:** `user.index`

### 2. Create User
- **Endpoint:** `POST /user`
- **Body:**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "country_code": "94",
    "phone": "771234567",
    "status": 1,
    "role_id": 2 
  }
  ```
  *(Note: Default role_id for Consumers is typically 2, Admin is 1)*

### 3. Update User
- **Endpoint:** `PUT /user/{id}`
- **Body:** (Partial updates supported)
  ```json
  {
    "name": "John Updated",
    "status": 0
  }
  ```

### 4. Delete User
- **Endpoint:** `DELETE /user/{id}`
- **Permissions:** `user.destroy`

---

## 📦 Product Management

Manage the catalog, including both simple and variable (classified) products.

### 1. List Products
- **Endpoint:** `GET /product`
- **Filters:** `category`, `tag`, `min_price`, `max_price`, `status`

### 2. Create Product (Simple)
- **Endpoint:** `POST /product`
- **Body:**
  ```json
  {
    "name": "Classic T-Shirt",
    "type": "simple",
    "price": 25.00,
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

### 4. Toggle Status
- **Endpoint:** `PUT /product/{id}/{status}`
  - Example: `/api/product/15/0` to deactivate product ID 15.

---

## 🛠 Supporting Data

Use these to populate dropdowns or relate to products.

### 1. Categories
- `GET /category`: List all categories.
- `POST /category`: Create a category.

### 2. Tags
- `GET /tag`: List all tags.

### 3. Attributes
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
