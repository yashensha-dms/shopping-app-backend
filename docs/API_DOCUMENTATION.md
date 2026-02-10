# Mstore API Documentation

**Complete API Reference for Software Engineers**

**Version:** 1.0.0  
**Base URL:** `https://your-domain.com/api`  
**Content-Type:** `application/json`

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [Products](#products)
4. [Categories](#categories)
5. [Tags](#tags)
6. [Attributes](#attributes)
7. [Stores (Vendors)](#stores-vendors)
8. [Orders](#orders)
9. [Cart](#cart)
10. [Wishlist](#wishlist)
11. [Compare](#compare)
12. [Reviews](#reviews)
13. [Coupons](#coupons)
14. [Addresses](#addresses)
15. [Users](#users)
16. [Attachments (Media)](#attachments-media)
17. [Blogs](#blogs)
18. [Pages](#pages)
19. [FAQs](#faqs)
20. [Question & Answers](#question--answers)
21. [Taxes](#taxes)
22. [Shipping](#shipping)
23. [Currencies](#currencies)
24. [Countries & States](#countries--states)
25. [Order Status](#order-status)
26. [Wallet & Points](#wallet--points)
27. [Notifications](#notifications)
28. [Settings & Configuration](#settings--configuration)
29. [Dashboard (Admin)](#dashboard-admin)
30. [Response Codes](#response-codes)

---

## Getting Started

### Base URL
All API requests should be made to:
```
https://your-domain.com/api
```

### Request Headers
```
Content-Type: application/json
Accept: application/json
```

For authenticated endpoints, add:
```
Authorization: Bearer {your_access_token}
```

### Pagination
Most list endpoints support pagination:
```
GET /product?paginate=20&page=1
```

Response includes:
```json
{
  "current_page": 1,
  "data": [...],
  "last_page": 10,
  "per_page": 20,
  "total": 200
}
```

---

## Authentication

### Login
```http
POST /login
```

Authenticate a user and receive an access token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your_password"
}
```

**Success Response (200):**
```json
{
  "access_token": "1|abc123xyz456...",
  "permissions": ["product.create", "product.edit", "order.index", ...],
  "success": true
}
```

**Error Response (401):**
```json
{
  "message": "Invalid credentials",
  "success": false
}
```

---

### Register
```http
POST /register
```

Create a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "country_code": "+1",
  "phone": "1234567890"
}
```

**Success Response (200):**
```json
{
  "access_token": "1|abc123xyz456...",
  "permissions": [],
  "success": true
}
```

---

### Forgot Password
```http
POST /forgot-password
```

Request a password reset token.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "message": "We have e-mailed verification code in registered mail!",
  "success": true
}
```

---

### Verify Token
```http
POST /verify-token
```

Verify the password reset token.

**Request Body:**
```json
{
  "token": "12345",
  "email": "user@example.com"
}
```

---

### Update Password
```http
POST /update-password
```

Reset password using the verified token.

**Request Body:**
```json
{
  "token": "12345",
  "email": "user@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

### Logout
```http
POST /logout
```
🔒 **Requires Authentication**

Invalidate the current access token.

**Success Response (200):**
```json
{
  "message": "Logged out successfully",
  "success": true
}
```

---

## Products

### List Products
```http
GET /product
```

Retrieve a paginated list of products with optional filters.

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `paginate` | integer | Items per page | `20` |
| `page` | integer | Page number | `1` |
| `category` | string | Category slug(s), comma-separated | `electronics,phones` |
| `tag` | string | Tag slug(s), comma-separated | `featured,sale` |
| `min` | number | Minimum price | `10.00` |
| `max` | number | Maximum price | `100.00` |
| `rating` | string | Rating filter(s), comma-separated | `4,5` |
| `sortBy` | string | Sort order | `asc`, `desc`, `a-z`, `z-a`, `high-to-low`, `low-to-high` |
| `store_id` | integer | Filter by store | `1` |
| `status` | boolean | Active status | `1` |
| `field` | string | Sort field | `created_at` |
| `sort` | string | Sort direction | `asc`, `desc` |

**Example Request:**
```
GET /product?paginate=10&category=electronics&sortBy=low-to-high&min=50&max=500
```

**Success Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Wireless Headphones",
      "slug": "wireless-headphones",
      "description": "High-quality wireless headphones...",
      "short_description": "Premium wireless headphones",
      "type": "simple",
      "price": 99.99,
      "sale_price": 79.99,
      "discount": 20,
      "sku": "WH-001",
      "stock_status": "in_stock",
      "quantity": 50,
      "is_featured": true,
      "status": true,
      "product_thumbnail": {
        "id": 123,
        "original_url": "https://..."
      },
      "categories": [
        {"id": 1, "name": "Electronics", "slug": "electronics"}
      ],
      "tags": [
        {"id": 1, "name": "Featured", "slug": "featured"}
      ],
      "store": {
        "id": 1,
        "store_name": "Tech Store"
      },
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:45:00Z"
    }
  ],
  "last_page": 5,
  "per_page": 10,
  "total": 48
}
```

---

### Get Product by ID
```http
GET /product/{id}
```

Retrieve a single product by its ID.

**Example:**
```
GET /product/1
```

**Success Response (200):**
```json
{
  "id": 1,
  "name": "Wireless Headphones",
  "slug": "wireless-headphones",
  "description": "High-quality wireless headphones with noise cancellation...",
  "short_description": "Premium wireless headphones",
  "type": "simple",
  "price": 99.99,
  "sale_price": 79.99,
  "discount": 20,
  "sku": "WH-001",
  "stock_status": "in_stock",
  "quantity": 50,
  "is_featured": true,
  "is_cod": true,
  "is_return": true,
  "is_free_shipping": false,
  "status": true,
  "product_thumbnail": {...},
  "product_galleries": [...],
  "categories": [...],
  "tags": [...],
  "attributes": [...],
  "variations": [...],
  "reviews": [...],
  "related_products": [...],
  "cross_sell_products": [...]
}
```

---

### Get Product by Slug
```http
GET /product/slug/{slug}
```

Retrieve a product using its URL-friendly slug.

**Example:**
```
GET /product/slug/wireless-headphones
```

---

### Create Product
```http
POST /product
```
🔒 **Requires Authentication**

Create a new product.

**Request Body (Simple Product):**
```json
{
  "name": "Wireless Headphones",
  "description": "High-quality wireless headphones with noise cancellation. Features include 30-hour battery life, Bluetooth 5.0, and premium audio drivers.",
  "short_description": "Premium wireless headphones",
  "type": "simple",
  "price": 99.99,
  "quantity": 50,
  "sku": "WH-001",
  "stock_status": "in_stock",
  "status": 1,
  "categories": [1, 5],
  "tags": [2, 8],
  "tax_id": 1,
  "store_id": 1,
  "product_thumbnail_id": 123,
  "product_galleries_id": [124, 125, 126],
  "product_meta_image_id": 127,
  "is_featured": 1,
  "is_cod": 1,
  "is_return": 1,
  "is_free_shipping": 0,
  "discount": 10.00,
  "sale_starts_at": "2024-02-01",
  "sale_expired_at": "2024-02-28",
  "cross_sell_products": [10, 11],
  "related_products": [12, 13, 14]
}
```

**Request Body (Classified Product with Variations):**
```json
{
  "name": "Classic T-Shirt",
  "description": "Comfortable cotton t-shirt available in multiple sizes and colors.",
  "short_description": "Cotton t-shirt",
  "type": "classified",
  "status": 1,
  "categories": [3],
  "tags": [1],
  "tax_id": 1,
  "store_id": 1,
  "attributes_ids": [1, 2],
  "variations": [
    {
      "name": "Small Red",
      "price": 29.99,
      "sale_price": 24.99,
      "sku": "TS-S-RED",
      "stock_status": "in_stock",
      "quantity": 20,
      "attribute_values": [1, 5],
      "status": 1,
      "variation_image_id": 130
    },
    {
      "name": "Medium Blue",
      "price": 29.99,
      "sku": "TS-M-BLUE",
      "stock_status": "in_stock",
      "quantity": 15,
      "attribute_values": [2, 6],
      "status": 1
    },
    {
      "name": "Large Green",
      "price": 29.99,
      "sku": "TS-L-GREEN",
      "stock_status": "out_of_stock",
      "quantity": 0,
      "attribute_values": [3, 7],
      "status": 1
    }
  ]
}
```

**Required Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Product name (max 255 characters) |
| `description` | string | Full description (minimum 10 characters) |
| `short_description` | string | Brief description for listings |
| `type` | string | `simple` or `classified` |
| `status` | integer | 1 = active, 0 = inactive |
| `categories` | array | Array of category IDs (at least one required) |
| `tax_id` | integer | Tax configuration ID |

**Required for Simple Products:**

| Field | Type | Description |
|-------|------|-------------|
| `price` | number | Base price |
| `quantity` | integer | Stock quantity |
| `sku` | string | Unique stock keeping unit |
| `stock_status` | string | `in_stock` or `out_of_stock` |

**Required for Classified Products:**

| Field | Type | Description |
|-------|------|-------------|
| `attributes_ids` | array | Attribute IDs used for variations |
| `variations` | array | Array of variation objects |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `tags` | array | Array of tag IDs |
| `store_id` | integer | Vendor store ID |
| `product_thumbnail_id` | integer | Main image attachment ID |
| `product_galleries_id` | array | Gallery image attachment IDs |
| `product_meta_image_id` | integer | SEO meta image attachment ID |
| `size_chart_image_id` | integer | Size chart image attachment ID |
| `is_featured` | integer | 1 = featured product |
| `is_cod` | integer | 1 = cash on delivery allowed |
| `is_return` | integer | 1 = returnable |
| `is_free_shipping` | integer | 1 = free shipping |
| `is_changeable` | integer | 1 = exchangeable |
| `is_sale_enable` | integer | 1 = sale pricing enabled |
| `is_external` | integer | 1 = external/affiliate product |
| `external_url` | string | External product URL |
| `external_button_text` | string | Buy button text |
| `discount` | number | Discount percentage (0-99.99) |
| `sale_starts_at` | date | Sale start date (YYYY-MM-DD) |
| `sale_expired_at` | date | Sale end date |
| `cross_sell_products` | array | Cross-sell product IDs |
| `related_products` | array | Related product IDs |
| `visible_time` | datetime | Scheduled visibility time |
| `secure_checkout` | integer | 1 = show secure checkout badge |
| `safe_checkout` | integer | 1 = show safe checkout badge |
| `social_share` | integer | 1 = enable social sharing |
| `encourage_order` | integer | 1 = show order encouragement |
| `encourage_view` | integer | 1 = show view count |
| `show_stock_quantity` | integer | 1 = display stock count |

**Success Response (201):**
```json
{
  "id": 1,
  "name": "Wireless Headphones",
  "slug": "wireless-headphones",
  ...
}
```

---

### Update Product
```http
PUT /product/{id}
```
🔒 **Requires Authentication**

Update an existing product. Send only the fields you want to change.

**Example Request:**
```json
{
  "price": 89.99,
  "discount": 15.00,
  "quantity": 75,
  "categories": [1, 5, 12],
  "tags": [2, 8, 15]
}
```

---

### Delete Product
```http
DELETE /product/{id}
```
🔒 **Requires Authentication**

Soft delete a product.

**Success Response (200):**
```json
{
  "message": "Product deleted successfully",
  "success": true
}
```

---

### Update Product Status
```http
PUT /product/{id}/{status}
```
🔒 **Requires Authentication**

Toggle product active/inactive status.

**Parameters:**
- `id` - Product ID
- `status` - `0` or `1`

**Example:**
```
PUT /product/1/1
```

---

### Replicate Product
```http
POST /product/replicate
```
🔒 **Requires Authentication**

Create a copy of an existing product.

**Request Body:**
```json
{
  "id": 1
}
```

---

### Delete Multiple Products
```http
POST /product/deleteAll
```
🔒 **Requires Authentication**

Delete multiple products at once.

**Request Body:**
```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

---

### Import Products (CSV)
```http
POST /product/csv/import
```
🔒 **Requires Authentication**

Import products from a CSV file.

**Request:** `multipart/form-data`
```
file: products.csv
```

---

### Export Products (CSV)
```http
POST /product/csv/export
```
🔒 **Requires Authentication**

Export products to a CSV file.

**Response:** CSV file download

---

## Categories

### List Categories
```http
GET /category
```

Retrieve all categories with subcategories.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `paginate` | integer | Items per page |
| `type` | string | Category type filter |
| `status` | boolean | Active status filter |
| `ids` | string | Filter by IDs, comma-separated |
| `field` | string | Sort field |
| `sort` | string | Sort direction (`asc`/`desc`) |

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Electronic devices and accessories",
      "type": "product",
      "status": true,
      "category_image": {
        "id": 45,
        "original_url": "https://..."
      },
      "subcategories": [
        {
          "id": 5,
          "name": "Smartphones",
          "slug": "smartphones",
          "parent_id": 1
        },
        {
          "id": 6,
          "name": "Laptops",
          "slug": "laptops",
          "parent_id": 1
        }
      ],
      "products_count": 48
    }
  ]
}
```

---

### Get Category by ID
```http
GET /category/{id}
```

Retrieve a single category with its subcategories and products.

---

### Create Category
```http
POST /category
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "Electronics",
  "description": "Electronic devices and accessories",
  "type": "product",
  "status": 1,
  "category_image_id": 45
}
```

**Creating a Subcategory:**
```json
{
  "name": "Smartphones",
  "description": "Mobile phones and accessories",
  "parent_id": 1,
  "status": 1
}
```

**Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | ✅ | Category name |
| `description` | string | ❌ | Category description |
| `type` | string | ❌ | Category type (e.g., `product`, `blog`) |
| `parent_id` | integer | ❌ | Parent category ID for subcategories |
| `category_image_id` | integer | ❌ | Image attachment ID |
| `status` | integer | ❌ | 1 = active, 0 = inactive |

---

### Update Category
```http
PUT /category/{id}
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "Updated Category Name",
  "description": "Updated description"
}
```

---

### Delete Category
```http
DELETE /category/{id}
```
🔒 **Requires Authentication**

---

### Update Category Status
```http
PUT /category/{id}/{status}
```
🔒 **Requires Authentication**

---

### Import Categories (CSV)
```http
POST /category/csv/import
```
🔒 **Requires Authentication**

---

### Export Categories (CSV)
```http
POST /category/csv/export
```
🔒 **Requires Authentication**

---

## Tags

### List Tags
```http
GET /tag
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `paginate` | integer | Items per page |
| `type` | string | Tag type filter |
| `status` | boolean | Active status filter |
| `ids` | string | Filter by IDs, comma-separated |

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Featured",
      "slug": "featured",
      "description": "Featured products",
      "type": "product",
      "status": true
    },
    {
      "id": 2,
      "name": "Best Seller",
      "slug": "best-seller",
      "type": "product",
      "status": true
    }
  ]
}
```

---

### Get Tag by ID
```http
GET /tag/{id}
```

---

### Create Tag
```http
POST /tag
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "Best Seller",
  "description": "Top selling products",
  "type": "product",
  "status": 1
}
```

---

### Update Tag
```http
PUT /tag/{id}
```
🔒 **Requires Authentication**

---

### Delete Tag
```http
DELETE /tag/{id}
```
🔒 **Requires Authentication**

---

### Update Tag Status
```http
PUT /tag/{id}/{status}
```
🔒 **Requires Authentication**

---

### Delete Multiple Tags
```http
POST /tag/deleteAll
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "ids": [1, 2, 3]
}
```

---

## Attributes

Attributes define product variations (e.g., Size, Color).

### List Attributes
```http
GET /attribute
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Size",
      "slug": "size",
      "status": true,
      "attribute_values": [
        {"id": 1, "value": "Small", "slug": "small"},
        {"id": 2, "value": "Medium", "slug": "medium"},
        {"id": 3, "value": "Large", "slug": "large"}
      ]
    },
    {
      "id": 2,
      "name": "Color",
      "slug": "color",
      "status": true,
      "attribute_values": [
        {"id": 5, "value": "Red", "slug": "red"},
        {"id": 6, "value": "Blue", "slug": "blue"},
        {"id": 7, "value": "Green", "slug": "green"}
      ]
    }
  ]
}
```

---

### Get Attribute by ID
```http
GET /attribute/{id}
```

---

### Create Attribute
```http
POST /attribute
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "Material",
  "status": 1,
  "attribute_values": [
    {"value": "Cotton"},
    {"value": "Polyester"},
    {"value": "Wool"}
  ]
}
```

---

### Update Attribute
```http
PUT /attribute/{id}
```
🔒 **Requires Authentication**

---

### Delete Attribute
```http
DELETE /attribute/{id}
```
🔒 **Requires Authentication**

---

## Attribute Values

### List Attribute Values
```http
GET /attribute-value
```

---

### Get Attribute Value by ID
```http
GET /attribute-value/{id}
```

---

### Create Attribute Value
```http
POST /attribute-value
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "attribute_id": 1,
  "value": "Extra Large",
  "status": 1
}
```

---

### Update Attribute Value
```http
PUT /attribute-value/{id}
```
🔒 **Requires Authentication**

---

### Delete Attribute Value
```http
DELETE /attribute-value/{id}
```
🔒 **Requires Authentication**

---

## Stores (Vendors)

### List Stores
```http
GET /store
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `paginate` | integer | Items per page |
| `status` | boolean | Active status filter |
| `top_vendor` | boolean | Get top vendors only |
| `filter_by` | string | Filter criteria |

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "store_name": "Tech World",
      "slug": "tech-world",
      "description": "Your one-stop shop for electronics",
      "email": "contact@techworld.com",
      "phone": "+1234567890",
      "country": {"id": 1, "name": "United States"},
      "state": {"id": 5, "name": "California"},
      "city": "San Francisco",
      "address": "123 Tech Street",
      "status": true,
      "is_approved": true,
      "store_logo": {...},
      "store_cover": {...},
      "vendor": {
        "id": 2,
        "name": "John Vendor"
      },
      "products_count": 125,
      "rating": 4.5
    }
  ]
}
```

---

### Get Store by ID
```http
GET /store/{id}
```

---

### Get Store by Slug
```http
GET /store/slug/{slug}
```

**Example:**
```
GET /store/slug/tech-world
```

---

### Create Store
```http
POST /store
```

Register a new vendor store.

**Request Body:**
```json
{
  "store_name": "My New Store",
  "description": "Welcome to my store",
  "email": "store@example.com",
  "phone": "+1234567890",
  "country_id": 1,
  "state_id": 5,
  "city": "New York",
  "address": "456 Commerce Ave",
  "store_logo_id": 50,
  "store_cover_id": 51
}
```

---

### Update Store
```http
PUT /store/{id}
```
🔒 **Requires Authentication**

---

### Delete Store
```http
DELETE /store/{id}
```
🔒 **Requires Authentication**

---

### Update Store Status
```http
PUT /store/{id}/{status}
```
🔒 **Requires Authentication**

---

### Approve Store
```http
PUT /store/approve/{id}/{status}
```
🔒 **Requires Authentication** (Admin only)

---

## Orders

### List Orders
```http
GET /order
```
🔒 **Requires Authentication**

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `paginate` | integer | Items per page |
| `status` | string | Order status filter |
| `start_date` | date | Filter from date (YYYY-MM-DD) |
| `end_date` | date | Filter to date (YYYY-MM-DD) |
| `field` | string | Sort field |
| `sort` | string | Sort direction |

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-2024-001",
      "consumer": {
        "id": 5,
        "name": "John Customer"
      },
      "amount": 299.99,
      "shipping_total": 15.00,
      "tax_total": 24.00,
      "total": 338.99,
      "payment_method": "stripe",
      "payment_status": "completed",
      "order_status": {
        "id": 3,
        "name": "Delivered"
      },
      "billing_address": {...},
      "shipping_address": {...},
      "products": [
        {
          "product_id": 1,
          "name": "Wireless Headphones",
          "quantity": 2,
          "price": 99.99,
          "subtotal": 199.98
        }
      ],
      "created_at": "2024-01-20T10:30:00Z"
    }
  ]
}
```

---

### Get Order by ID
```http
GET /order/{id}
```
🔒 **Requires Authentication**

---

### Create Order (Checkout)
```http
POST /order
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "products": [
    {
      "product_id": 1,
      "variation_id": null,
      "quantity": 2
    },
    {
      "product_id": 5,
      "variation_id": 12,
      "quantity": 1
    }
  ],
  "billing_address_id": 1,
  "shipping_address_id": 2,
  "payment_method": "stripe",
  "coupon": "SAVE10",
  "delivery_description": "Please leave at the door",
  "points_amount": 50
}
```

**Payment Methods:**
- `cod` - Cash on Delivery
- `stripe` - Stripe
- `paypal` - PayPal
- `razorpay` - Razorpay
- `mollie` - Mollie
- `wallet` - Wallet Balance

**Success Response (201):**
```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-2024-001",
    "total": 338.99,
    ...
  },
  "payment_redirect_url": "https://checkout.stripe.com/...",
  "success": true
}
```

---

### Update Order
```http
PUT /order/{id}
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "order_status_id": 3
}
```

---

### Delete Order
```http
DELETE /order/{id}
```
🔒 **Requires Authentication**

---

### Track Order
```http
GET /trackOrder/{order_number}
```
🔒 **Requires Authentication**

**Example:**
```
GET /trackOrder/ORD-2024-001
```

**Success Response (200):**
```json
{
  "id": 1,
  "order_number": "ORD-2024-001",
  "order_status": {
    "id": 2,
    "name": "Shipped",
    "slug": "shipped"
  },
  "order_status_activities": [
    {
      "status": "Pending",
      "created_at": "2024-01-20T10:30:00Z"
    },
    {
      "status": "Processing",
      "created_at": "2024-01-20T12:00:00Z"
    },
    {
      "status": "Shipped",
      "created_at": "2024-01-21T09:00:00Z"
    }
  ]
}
```

---

### Verify Payment
```http
GET /verifyPayment/{order_number}
```
🔒 **Requires Authentication**

Verify payment status after returning from payment gateway.

---

### Re-Payment
```http
POST /rePayment
```
🔒 **Requires Authentication**

Retry payment for a failed order.

**Request Body:**
```json
{
  "order_number": "ORD-2024-001",
  "payment_method": "paypal"
}
```

---

### Verify Checkout
```http
POST /checkout
```
🔒 **Requires Authentication**

Validate cart and calculate totals before creating order.

**Request Body:**
```json
{
  "products": [...],
  "coupon": "SAVE10"
}
```

---

## Cart

### Get Cart
```http
GET /cart
```
🔒 **Requires Authentication**

**Success Response (200):**
```json
{
  "items": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Wireless Headphones",
        "price": 99.99,
        "product_thumbnail": {...}
      },
      "variation": null,
      "quantity": 2,
      "sub_total": 199.98
    }
  ],
  "total": 199.98
}
```

---

### Add to Cart
```http
POST /cart
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2,
  "variation_id": null
}
```

---

### Update Cart Item
```http
PUT /cart
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "id": 1,
  "product_id": 1,
  "quantity": 3
}
```

---

### Remove from Cart
```http
DELETE /cart/{id}
```
🔒 **Requires Authentication**

---

### Sync Cart
```http
POST /sync/cart
```
🔒 **Requires Authentication**

Sync local cart with server (useful after login).

**Request Body:**
```json
{
  "items": [
    {"product_id": 1, "quantity": 2},
    {"product_id": 5, "variation_id": 12, "quantity": 1}
  ]
}
```

---

### Replace Cart
```http
PUT /replace/cart
```
🔒 **Requires Authentication**

Replace entire cart contents.

---

## Wishlist

### Get Wishlist
```http
GET /wishlist
```
🔒 **Requires Authentication**

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Wireless Headphones",
        "price": 99.99,
        "product_thumbnail": {...}
      }
    }
  ]
}
```

---

### Add to Wishlist
```http
POST /wishlist
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1
}
```

---

### Remove from Wishlist
```http
DELETE /wishlist/{id}
```
🔒 **Requires Authentication**

---

## Compare

### Get Compare List
```http
GET /compare
```
🔒 **Requires Authentication**

---

### Add to Compare
```http
POST /compare
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1
}
```

---

### Remove from Compare
```http
DELETE /compare/{id}
```
🔒 **Requires Authentication**

---

## Reviews

### Get Reviews (Public)
```http
GET /front/review
```

Get public product reviews.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `product_id` | integer | Filter by product |
| `rating` | integer | Filter by rating (1-5) |
| `paginate` | integer | Items per page |

---

### List All Reviews
```http
GET /review
```
🔒 **Requires Authentication**

---

### Get Review by ID
```http
GET /review/{id}
```
🔒 **Requires Authentication**

---

### Create Review
```http
POST /review
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1,
  "rating": 5,
  "description": "Excellent product! Great sound quality and comfortable to wear.",
  "review_image_id": 200
}
```

---

### Update Review
```http
PUT /review/{id}
```
🔒 **Requires Authentication**

---

### Delete Review
```http
DELETE /review/{id}
```
🔒 **Requires Authentication**

---

## Coupons

### List Coupons
```http
GET /coupon
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "code": "SAVE10",
      "type": "percentage",
      "amount": 10,
      "min_spend": 50,
      "is_unlimited": false,
      "usage_limit": 100,
      "used": 45,
      "start_date": "2024-01-01",
      "end_date": "2024-12-31",
      "status": true
    }
  ]
}
```

---

### Get Coupon by ID
```http
GET /coupon/{id}
```

---

### Create Coupon
```http
POST /coupon
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "code": "SUMMER20",
  "type": "percentage",
  "amount": 20,
  "min_spend": 100,
  "is_unlimited": false,
  "usage_limit": 500,
  "start_date": "2024-06-01",
  "end_date": "2024-08-31",
  "status": 1,
  "is_apply_all": true,
  "exclude_products": [10, 11],
  "products": [],
  "categories": []
}
```

**Coupon Types:**
- `percentage` - Percentage discount
- `fixed` - Fixed amount discount
- `free_shipping` - Free shipping

---

### Update Coupon
```http
PUT /coupon/{id}
```
🔒 **Requires Authentication**

---

### Delete Coupon
```http
DELETE /coupon/{id}
```
🔒 **Requires Authentication**

---

## Addresses

### List Addresses
```http
GET /address
```
🔒 **Requires Authentication**

---

### Get Address by ID
```http
GET /address/{id}
```
🔒 **Requires Authentication**

---

### Create Address
```http
POST /address
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "title": "Home",
  "street": "123 Main Street",
  "city": "New York",
  "state_id": 5,
  "country_id": 1,
  "pincode": "10001",
  "phone": "+1234567890",
  "is_default": true
}
```

---

### Update Address
```http
PUT /address/{id}
```
🔒 **Requires Authentication**

---

### Delete Address
```http
DELETE /address/{id}
```
🔒 **Requires Authentication**

---

## Users

### List Users
```http
GET /user
```
🔒 **Requires Authentication** (Admin)

---

### Get User by ID
```http
GET /user/{id}
```
🔒 **Requires Authentication**

---

### Create User
```http
POST /user
```
🔒 **Requires Authentication** (Admin)

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "country_code": "+1",
  "role_id": 2,
  "status": 1
}
```

---

### Update User
```http
PUT /user/{id}
```
🔒 **Requires Authentication**

---

### Delete User
```http
DELETE /user/{id}
```
🔒 **Requires Authentication** (Admin)

---

### Get Current User
```http
GET /self
```
🔒 **Requires Authentication**

Returns the authenticated user's profile.

---

### Update Profile
```http
PUT /updateProfile
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "phone": "+1234567890",
  "profile_image_id": 55
}
```

---

### Update Password
```http
PUT /updatePassword
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

## Attachments (Media)

### List Attachments
```http
GET /attachment
```
🔒 **Requires Authentication**

---

### Get Attachment by ID
```http
GET /attachment/{id}
```
🔒 **Requires Authentication**

---

### Upload Attachment
```http
POST /attachment
```
🔒 **Requires Authentication**

**Request:** `multipart/form-data`
```
file: image.jpg
```

**Success Response (201):**
```json
{
  "id": 123,
  "name": "image.jpg",
  "file_name": "1706789012_image.jpg",
  "mime_type": "image/jpeg",
  "size": 102400,
  "original_url": "https://your-domain.com/storage/attachments/1706789012_image.jpg",
  "created_at": "2024-02-01T10:30:00Z"
}
```

---

### Delete Attachment
```http
DELETE /attachment/{id}
```
🔒 **Requires Authentication**

---

### Delete Multiple Attachments
```http
POST /attachment/deleteAll
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "ids": [123, 124, 125]
}
```

---

## Blogs

### List Blogs
```http
GET /blog
```

---

### Get Blog by ID
```http
GET /blog/{id}
```

---

### Get Blog by Slug
```http
GET /blog/slug/{slug}
```

---

### Create Blog
```http
POST /blog
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "title": "How to Choose the Best Headphones",
  "content": "Full blog content here...",
  "description": "A guide to selecting headphones",
  "blog_thumbnail_id": 100,
  "blog_meta_image_id": 101,
  "categories": [10],
  "tags": [5, 6],
  "is_featured": 1,
  "status": 1
}
```

---

### Update Blog
```http
PUT /blog/{id}
```
🔒 **Requires Authentication**

---

### Delete Blog
```http
DELETE /blog/{id}
```
🔒 **Requires Authentication**

---

## Pages

### List Pages
```http
GET /page
```

---

### Get Page by ID
```http
GET /page/{id}
```

---

### Get Page by Slug
```http
GET /page/slug/{slug}
```

**Example:**
```
GET /page/slug/about-us
```

---

### Create Page
```http
POST /page
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "title": "About Us",
  "content": "Full page content here...",
  "meta_title": "About Our Company",
  "meta_description": "Learn about our company history...",
  "page_meta_image_id": 110,
  "status": 1
}
```

---

### Update Page
```http
PUT /page/{id}
```
🔒 **Requires Authentication**

---

### Delete Page
```http
DELETE /page/{id}
```
🔒 **Requires Authentication**

---

## FAQs

### List FAQs
```http
GET /faq
```

---

### Get FAQ by ID
```http
GET /faq/{id}
```

---

### Create FAQ
```http
POST /faq
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "title": "How do I track my order?",
  "description": "You can track your order by...",
  "status": 1
}
```

---

### Update FAQ
```http
PUT /faq/{id}
```
🔒 **Requires Authentication**

---

### Delete FAQ
```http
DELETE /faq/{id}
```
🔒 **Requires Authentication**

---

## Question & Answers

Product Q&A system.

### List Questions
```http
GET /question-and-answer
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `product_id` | integer | Filter by product |

---

### Get Question by ID
```http
GET /question-and-answer/{id}
```

---

### Ask a Question
```http
POST /question-and-answer
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "product_id": 1,
  "question": "Is this product waterproof?"
}
```

---

### Answer a Question
```http
PUT /question-and-answer/{id}
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "answer": "Yes, this product is IPX7 waterproof rated."
}
```

---

### Delete Question
```http
DELETE /question-and-answer/{id}
```
🔒 **Requires Authentication**

---

### Submit Feedback
```http
POST /question-and-answer/feedback
```
🔒 **Requires Authentication**

Mark an answer as helpful.

**Request Body:**
```json
{
  "question_and_answer_id": 1,
  "reaction": "liked"
}
```

---

## Taxes

### List Taxes
```http
GET /tax
```

---

### Get Tax by ID
```http
GET /tax/{id}
```

---

### Create Tax
```http
POST /tax
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "GST",
  "rate": 18.00,
  "status": 1
}
```

---

### Update Tax
```http
PUT /tax/{id}
```
🔒 **Requires Authentication**

---

### Delete Tax
```http
DELETE /tax/{id}
```
🔒 **Requires Authentication**

---

## Shipping

### List Shipping Methods
```http
GET /shipping
```
🔒 **Requires Authentication**

---

### Get Shipping by ID
```http
GET /shipping/{id}
```
🔒 **Requires Authentication**

---

### Create Shipping
```http
POST /shipping
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "title": "Standard Shipping",
  "description": "Delivery in 5-7 business days",
  "status": 1
}
```

---

### Update Shipping
```http
PUT /shipping/{id}
```
🔒 **Requires Authentication**

---

### Delete Shipping
```http
DELETE /shipping/{id}
```
🔒 **Requires Authentication**

---

## Shipping Rules

### List Shipping Rules
```http
GET /shippingRule
```
🔒 **Requires Authentication**

---

### Create Shipping Rule
```http
POST /shippingRule
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "shipping_id": 1,
  "name": "Under 1kg",
  "rule_type": "base_on_weight",
  "min": 0,
  "max": 1,
  "shipping_type": "fixed",
  "amount": 5.00,
  "status": 1
}
```

---

### Update Shipping Rule
```http
PUT /shippingRule/{id}
```
🔒 **Requires Authentication**

---

### Delete Shipping Rule
```http
DELETE /shippingRule/{id}
```
🔒 **Requires Authentication**

---

## Currencies

### List Currencies
```http
GET /currency
```

---

### Get Currency by ID
```http
GET /currency/{id}
```

---

### Create Currency
```http
POST /currency
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "code": "EUR",
  "symbol": "€",
  "no_of_decimal": 2,
  "exchange_rate": 0.92,
  "status": 1
}
```

---

### Update Currency
```http
PUT /currency/{id}
```
🔒 **Requires Authentication**

---

### Delete Currency
```http
DELETE /currency/{id}
```
🔒 **Requires Authentication**

---

## Countries & States

### List Countries
```http
GET /country
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "United States",
      "code": "US",
      "states": [
        {"id": 1, "name": "Alabama"},
        {"id": 2, "name": "Alaska"},
        {"id": 3, "name": "Arizona"}
      ]
    }
  ]
}
```

---

### Get Country by ID
```http
GET /country/{id}
```

---

### List States
```http
GET /state
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `country_id` | integer | Filter by country |

---

### Get State by ID
```http
GET /state/{id}
```

---

## Order Status

### List Order Statuses
```http
GET /orderStatus
```

**Success Response (200):**
```json
{
  "data": [
    {"id": 1, "name": "Pending", "slug": "pending", "sequence": 1},
    {"id": 2, "name": "Processing", "slug": "processing", "sequence": 2},
    {"id": 3, "name": "Shipped", "slug": "shipped", "sequence": 3},
    {"id": 4, "name": "Delivered", "slug": "delivered", "sequence": 4},
    {"id": 5, "name": "Cancelled", "slug": "cancelled", "sequence": 5}
  ]
}
```

---

### Get Order Status by ID
```http
GET /orderStatus/{id}
```

---

### Create Order Status
```http
POST /orderStatus
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "name": "On Hold",
  "sequence": 3,
  "status": 1
}
```

---

### Update Order Status
```http
PUT /orderStatus/{id}
```
🔒 **Requires Authentication**

---

### Delete Order Status
```http
DELETE /orderStatus/{id}
```
🔒 **Requires Authentication**

---

## Wallet & Points

### Consumer Wallet

#### Get Consumer Wallets
```http
GET /wallet/consumer
```
🔒 **Requires Authentication** (Admin)

#### Credit Wallet
```http
POST /credit/wallet
```
🔒 **Requires Authentication** (Admin)

**Request Body:**
```json
{
  "consumer_id": 5,
  "balance": 100.00
}
```

#### Debit Wallet
```http
POST /debit/wallet
```
🔒 **Requires Authentication** (Admin)

**Request Body:**
```json
{
  "consumer_id": 5,
  "balance": 50.00
}
```

---

### Consumer Points

#### Get Consumer Points
```http
GET /points/consumer
```
🔒 **Requires Authentication** (Admin)

#### Credit Points
```http
POST /credit/points
```
🔒 **Requires Authentication** (Admin)

**Request Body:**
```json
{
  "consumer_id": 5,
  "point": 100
}
```

#### Debit Points
```http
POST /debit/points
```
🔒 **Requires Authentication** (Admin)

---

### Vendor Wallet

#### Get Vendor Wallets
```http
GET /wallet/vendor
```
🔒 **Requires Authentication** (Admin)

#### Credit Vendor Wallet
```http
POST /credit/vendorWallet
```
🔒 **Requires Authentication** (Admin)

#### Debit Vendor Wallet
```http
POST /debit/vendorWallet
```
🔒 **Requires Authentication** (Admin)

---

## Notifications

### Get Notifications
```http
GET /notifications
```
🔒 **Requires Authentication**

---

### Mark Notifications as Read
```http
PUT /notifications/markAsRead
```
🔒 **Requires Authentication**

---

### Delete Notification
```http
DELETE /notifications/{id}
```
🔒 **Requires Authentication**

---

## Settings & Configuration

### Get Public Settings
```http
GET /settings
```

Returns public application settings.

**Success Response (200):**
```json
{
  "general": {
    "site_title": "Mstore",
    "site_tagline": "Your E-commerce Store",
    "default_currency": "USD",
    "min_order_amount": 10,
    "min_order_free_shipping": 100
  },
  "wallet_points": {
    "signup_points": 100,
    "min_per_order_percent": 10,
    "point_currency_ratio": 10
  },
  "email": {...},
  "payment_methods": {...}
}
```

---

### Update Settings
```http
PUT /settings
```
🔒 **Requires Authentication** (Admin)

---

### Get Theme Options
```http
GET /themeOptions
```

Returns theme configuration options.

---

### Update Theme Options
```http
PUT /themeOptions
```
🔒 **Requires Authentication** (Admin)

---

### Get App Settings
```http
GET /app/settings
```

Returns mobile app configuration.

---

### Get Home Page Content
```http
GET /home
```

Returns homepage sections and content.

---

### Update Home Page
```http
PUT /home/{id}
```
🔒 **Requires Authentication** (Admin)

---

### Get Themes
```http
GET /theme
```

---

### Update Theme
```http
PUT /theme/{id}
```
🔒 **Requires Authentication** (Admin)

---

## Dashboard (Admin)

### Get Statistics
```http
GET /statistics/count
```
🔒 **Requires Authentication**

**Success Response (200):**
```json
{
  "total_orders": 1250,
  "total_products": 485,
  "total_customers": 892,
  "total_stores": 23,
  "total_revenue": 125000.00,
  "pending_orders": 15,
  "pending_products": 8,
  "pending_stores": 2
}
```

---

### Get Chart Data
```http
GET /dashboard/chart
```
🔒 **Requires Authentication**

Returns sales and revenue chart data.

---

### Get Badge Counts
```http
GET /badge
```
🔒 **Requires Authentication**

Returns notification counts for various items.

---

## Refunds

### List Refunds
```http
GET /refund
```
🔒 **Requires Authentication**

---

### Get Refund by ID
```http
GET /refund/{id}
```
🔒 **Requires Authentication**

---

### Request Refund
```http
POST /refund
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "order_id": 1,
  "reason": "Product damaged during shipping",
  "payment_type": "wallet",
  "product_id": 5
}
```

---

### Update Refund
```http
PUT /refund/{id}
```
🔒 **Requires Authentication**

---

### Delete Refund
```http
DELETE /refund/{id}
```
🔒 **Requires Authentication**

---

## Withdraw Requests (Vendors)

### List Withdraw Requests
```http
GET /withdrawRequest
```
🔒 **Requires Authentication**

---

### Get Withdraw Request by ID
```http
GET /withdrawRequest/{id}
```
🔒 **Requires Authentication**

---

### Create Withdraw Request
```http
POST /withdrawRequest
```
🔒 **Requires Authentication**

**Request Body:**
```json
{
  "amount": 500.00,
  "message": "Monthly withdrawal"
}
```

---

### Update Withdraw Request
```http
PUT /withdrawRequest/{id}
```
🔒 **Requires Authentication**

---

### Delete Withdraw Request
```http
DELETE /withdrawRequest/{id}
```
🔒 **Requires Authentication**

---

## Commission History

### List Commission History
```http
GET /commissionHistory
```
🔒 **Requires Authentication**

---

### Get Commission by ID
```http
GET /commissionHistory/{id}
```
🔒 **Requires Authentication**

---

## Roles & Permissions

### List Roles
```http
GET /role
```
🔒 **Requires Authentication** (Admin)

---

### Get Role by ID
```http
GET /role/{id}
```
🔒 **Requires Authentication**

---

### Create Role
```http
POST /role
```
🔒 **Requires Authentication** (Admin)

**Request Body:**
```json
{
  "name": "Editor",
  "permissions": ["product.index", "product.create", "product.edit"]
}
```

---

### Update Role
```http
PUT /role/{id}
```
🔒 **Requires Authentication** (Admin)

---

### Delete Role
```http
DELETE /role/{id}
```
🔒 **Requires Authentication** (Admin)

---

### Get Available Modules/Permissions
```http
GET /module
```
🔒 **Requires Authentication** (Admin)

Returns list of all available permissions.

---

## Contact Us

### Submit Contact Form
```http
POST /contact-us
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "subject": "Product Inquiry",
  "message": "I have a question about..."
}
```

---

## Response Codes

| Code | Description |
|------|-------------|
| `200` | Success - Request completed successfully |
| `201` | Created - Resource created successfully |
| `204` | No Content - Request successful, no content to return |
| `400` | Bad Request - Invalid request format or parameters |
| `401` | Unauthorized - Missing or invalid authentication token |
| `403` | Forbidden - Insufficient permissions for the requested resource |
| `404` | Not Found - Requested resource does not exist |
| `422` | Validation Error - Request validation failed |
| `429` | Too Many Requests - Rate limit exceeded |
| `500` | Server Error - Internal server error |

---

## Error Response Format

All errors return a consistent JSON format:

```json
{
  "message": "The name field is required.",
  "success": false
}
```

For validation errors:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## Rate Limiting

API requests are rate limited to prevent abuse:
- **Authenticated users:** 60 requests per minute
- **Unauthenticated requests:** 30 requests per minute

When rate limited, you'll receive a `429 Too Many Requests` response.

---

## Best Practices

1. **Always use HTTPS** - All API requests should use HTTPS
2. **Store tokens securely** - Never expose access tokens in client-side code
3. **Handle pagination** - Always implement pagination for list endpoints
4. **Implement retry logic** - Handle temporary failures with exponential backoff
5. **Validate before sending** - Reduce API calls by validating data client-side
6. **Cache responses** - Cache frequently accessed data like categories and settings
7. **Use slugs for SEO** - Use slug-based endpoints for public-facing pages

---

## Interactive Documentation

For interactive API testing with a user interface, visit:
```
https://your-domain.com/api/documentation
```

---

*Last Updated: February 2024*
