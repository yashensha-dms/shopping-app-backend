# Product Attribute Comparison Analysis

This document provides a detailed comparison between the requested product attributes in `productelements.md` and the existing implementation in the `shopping-app-backend`.

## 1. Existing Attributes (Matches)
The following attributes from `productelements.md` are already present in the codebase.

| Requested Attribute | Existing Field / Implementation |
| :--- | :--- |
| **Custom SKU** | `sku` (String, nullable) |
| **Product Name** | `name` (String, nullable) |
| **MRP** | `price` (Decimal) |
| **Selling Price** | `sale_price` (Decimal) |
| **Product Category** | `categories()` (BelongsToMany Relationship) |
| **Product Description** | `description` (LongText) |
| **Variant Relationship**| `variations()` (HasMany Relationship) |
| **Size** | Managed via `attributes()` relationship & `Variation` attribute values. |
| **Colour Code / Name** | Managed via `attributes()` relationship (Color attribute). |
| **Product Images (1-6)**| `product_thumbnail_id` (Image 1) & `product_galleries()` (Images 2-6 via `product_images` table). |
| **Size Guide** | `size_chart_image_id` (Foreign key to `attachments` table). |

---

## 2. Missing Attributes
These attributes were requested in `productelements.md` but are currently not present in the database schema or model.

| Missing Attribute | Recommendation |
| :--- | :--- |
| **HSN Code** | **Action Required:** Add `hsn_code` (String, nullable) to the `products` table via a migration. This is essential for tax compliance. |

---

## 3. Suggested Attributes to Replace
These current fields are serving similar purposes but could be renamed or aliased to match the terminology in `productelements.md`.

| Current Field | Requested Term | Logic / Suggestion |
| :--- | :--- | :--- |
| `categories` (with type filter) | **Business Category** | Use the `type` column in the `categories` table to distinguish between "Business" and "Product" categories. |
| `is_trending` | **Best Seller** | Keep `is_trending` but alias it in the API response or use `is_featured` if it better represents best-selling status. |
| `price` | **MRP** | While `price` is common, renaming it to `mrp` would clear confusion with `sale_price`. |

---

## 4. Attributes Not in `productelements.md` (Unused or Additional)
The following attributes exist in the current codebase but were not mentioned in your specification. You may want to consider if these are still needed.

### Logistics & Sales
- `unit`: (e.g., kg, pcs)
- `weight`: (Mass/Weight of product)
- `quantity`: (Stock level)
- `discount`: (Automatic calculation field)
- `stock_status`: (in_stock, out_of_stock)
- `shipping_days`: (Delivery timeframe)
- `is_cod`: (Cash on Delivery availability)
- `is_free_shipping`: (Shipping cost toggle)
- `estimated_delivery_text`: (Custom text for UI)

### Visibility & SEO
- `meta_title`: (SEO Title)
- `meta_description`: (SEO Description)
- `product_meta_image_id`: (Social share image)
- `slug`: (URL-friendly version of name)
- `status`: (Active/Inactive toggle)
- `visible_time`: (Scheduled visibility)

### Policies & Trust
- `is_return`: (Returns toggle)
- `return_policy_text`: (Detailed policy description)
- `safe_checkout`: (Safety badges)
- `secure_checkout`: (Security badges)
- `social_share`: (Social share buttons toggle)

### External/Affiliate
- `is_external`: (Toggle for external products)
- `external_url`: (Link to external store)
- `external_button_text`: (Button CTA)
