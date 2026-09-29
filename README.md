# Store Order & Inventory Mini-System

## Project Overview

A Laravel application for browsing store inventory, creating customer orders, viewing customer order history, and finding products below a chosen stock threshold. Order totals and stock decisions are calculated by the Laravel backend; browser-side totals are estimates only.

## Features

- Product and customer listings with Bootstrap tables and pagination.
- Dashboard metrics and a low-stock product list.
- Order creation with multiple distinct product lines.
- Server-calculated subtotal, tax, and grand total.
- Transactional stock validation and deduction using MySQL row locks.
- Customer order history and configurable low-stock APIs.
- Queued simulated order-confirmation logging.
- Factories, seeders, and PHPUnit feature tests.

## Technology Stack

- Laravel 12 and PHP 8.2+
- MySQL with InnoDB
- Eloquent ORM and Blade
- Bootstrap 5.3.3, loaded from jsDelivr in the shared Blade layout
- JavaScript for API calls and user-interface updates
- Laravel Queue with the database driver
- PHPUnit 11

The current Blade pages use local files under `public/css` and `public/js`; they do not require a Vite build. Bootstrap styling and its JavaScript bundle require access to the configured CDN.

## Requirements

- PHP 8.2 or newer with `pdo_mysql` enabled
- Composer
- MySQL or MariaDB with InnoDB support
- A web browser with access to jsDelivr for Bootstrap assets

Node.js/npm are not required to run the current Blade pages.

## Installation

From the project directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database and set the connection values described below before migrating.

## Environment Configuration

The checked-in `.env.example` defaults to SQLite. For this project, update `.env` to use MySQL and the database queue. For example:

```dotenv
APP_NAME="Store Order Inventory"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_order_inventory
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

Use the MySQL username/password configured on your own machine; do not commit real credentials. `DB_PASSWORD` may be blank for a local XAMPP installation if that matches its configuration.

## MySQL Database Setup

Create the database in MySQL or phpMyAdmin. For example, from the MySQL client:

```sql
CREATE DATABASE store_order_inventory
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Then confirm the `DB_*` values in `.env` point to this database. The products, customers, orders, and order-items migrations explicitly select InnoDB so row-level locking and transaction rollback are available.

## Migration

Create the tables with:

```powershell
php artisan migrate
```

The migrations include Laravel's users, cache, and database-queue tables as well as the store tables.

## Seeding

Load sample users, products, and customers with:

```powershell
php artisan db:seed
```

The sample products include low-stock items for the threshold page. Orders are not seeded. The fixed starter user and named product/customer records are reused on later runs. Each run also adds four factory-generated products and five factory-generated customers, so run it once for the standard demo dataset.

## Running the Application

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000`. The available pages are:

- `/` — dashboard
- `/products` — products and stock status
- `/customers` — customers and order-history actions
- `/orders/create` — create an order
- `/orders/history` — look up order history by customer email
- `/products/low-stock` — filter products by a stock threshold

## Running Queue Worker

In a separate terminal, run:

```powershell
php artisan queue:work
```

The default queue connection is `database`, and the jobs migration creates its tables. Keep the worker running while testing order submission to process queued confirmations.

## Running Tests

Run the PHPUnit suite with:

```powershell
php artisan test
```

The test configuration uses an in-memory SQLite database and a synchronous queue. It covers order creation and calculations, customer reuse, stock failure and rollback, queue dispatch, order history, low-stock filtering, and storefront pages. SQLite tests do not exercise simultaneous MySQL row locking; run a separate integration test against a dedicated MySQL test database to verify that race condition.

## API Documentation

For JSON validation responses, send `Accept: application/json`. API validation failures use HTTP `422 Unprocessable Entity`.

### Create an Order

- **Purpose:** Create a customer order, store line-item price/tax snapshots, and deduct stock.
- **Method:** `POST`
- **URL:** `/api/orders`
- **Headers:** `Accept: application/json`, `Content-Type: application/json`

Request:

```json
{
  "customer_name": "Ramkumar",
  "customer_email": "ram@example.com",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 2, "quantity": 1 }
  ]
}
```

Representative success response, HTTP `201 Created` (timestamps and some nested product fields omitted here):

```json
{
  "message": "Order created successfully.",
  "data": {
    "id": 15,
    "customer_id": 1,
    "subtotal": "25.50",
    "tax": "2.55",
    "grand_total": "28.05",
    "customer": {
      "id": 1,
      "name": "Ramkumar",
      "email": "ram@example.com"
    },
    "items": [
      {
        "id": 31,
        "order_id": 15,
        "product_id": 1,
        "quantity": 2,
        "unit_price": "10.25",
        "tax_percentage": "10.00",
        "subtotal": "20.50",
        "tax": "2.05",
        "total": "22.55",
        "product": {
          "id": 1,
          "name": "Keyboard",
          "code": "ELEC-KEY-001"
        }
      }
    ]
  }
}
```

Possible errors:

- `422 Unprocessable Entity` — invalid customer fields, missing/empty items, invalid product ID, duplicate product line, or non-positive quantity.
- `409 Conflict` — requested quantity exceeds current stock. The transaction rolls back; no partial order is retained.

### Customer Order History

- **Purpose:** Return customer details and all saved orders and items for an email address.
- **Method:** `GET`
- **URL:** `/api/customers/orders?email=customer@example.com`

Example request:

```http
GET /api/customers/orders?email=ram@example.com
Accept: application/json
```

Representative success response, HTTP `200 OK`:

```json
{
  "data": {
    "customer": {
      "id": 1,
      "name": "Ramkumar",
      "email": "ram@example.com"
    },
    "orders": [
      {
        "id": 15,
        "order_date": "2026-09-28T10:15:00.000000Z",
        "subtotal": "20.50",
        "tax": "2.05",
        "grand_total": "22.55",
        "items": [
          {
            "product_name": "Keyboard",
            "quantity": 2,
            "unit_price": "10.25",
            "subtotal": "20.50",
            "tax": "2.05",
            "total": "22.55"
          }
        ]
      }
    ]
  }
}
```

Possible errors/states:

- `422 Unprocessable Entity` — email is missing or invalid.
- `404 Not Found` — no customer exists for the email.
- `200 OK` with `"orders": []` — customer exists but has no orders.

### Low-Stock Products

- **Purpose:** List products whose stock is strictly less than the supplied threshold.
- **Method:** `GET`
- **URL:** `/api/products/low-stock?threshold=5`

Example request:

```http
GET /api/products/low-stock?threshold=5
Accept: application/json
```

Representative success response, HTTP `200 OK`:

```json
{
  "data": [
    {
      "id": 4,
      "name": "Monitor",
      "code": "ELEC-MON-001",
      "price": "349.99",
      "tax_percentage": "18.00",
      "stock": 2
    }
  ]
}
```

Possible errors/states:

- `422 Unprocessable Entity` — threshold is missing, negative, or not an integer.
- `200 OK` with `"data": []` — no products are below the threshold.

## Database Design

- **Customer → Orders:** `customers` has many `orders`; each order belongs to one customer.
- **Order → Order Items:** `orders` has many `order_items`; each item belongs to one order.
- **Product → Order Items:** `products` has many `order_items`; each item refers to one product.

`order_items` is the line-item table connecting orders and products. It saves `unit_price` and `tax_percentage` at purchase time so later product changes do not rewrite historical order pricing. A unique `(order_id, product_id)` constraint allows each product at most once per order. Customer email and product code are unique.

## Order Calculation

The server calculates amounts from the current product price/tax and requested quantity; it ignores client-submitted totals.

- **Item subtotal:** unit price × quantity.
- **Item tax:** item subtotal × tax percentage ÷ 100, rounded to the nearest cent per item using half-up rounding.
- **Item total:** item subtotal + item tax.
- **Order subtotal:** sum of item subtotals.
- **Order tax:** sum of rounded item taxes.
- **Grand total:** order subtotal + order tax.

The service performs money arithmetic using integer cents before saving decimal amounts.

## Concurrency-Safe Stock Handling

The order service starts a MySQL transaction and selects all requested product rows with `lockForUpdate()`, in ascending product ID order. MySQL/InnoDB holds these row locks while the service checks stock, creates the order and items, and deducts stock.

If two requests compete for the final unit, one locks the product first. The other waits, then sees the committed lower stock and receives an insufficient-stock response. The exception occurs inside the transaction, so order/customer/item writes and stock changes are rolled back together. The tables participating in this operation explicitly use InnoDB.

The current PHPUnit configuration uses SQLite, so its tests verify transactional failure behavior but not two simultaneous MySQL requests.

## Queue

After a successful order transaction returns, Laravel dispatches `SendOrderConfirmation` with the created order. The queued job loads its customer and logs a simulated confirmation message. It does not send SMTP email. Failed order transactions do not dispatch the job.

## Frontend

The shared Blade/Bootstrap layout links the six pages: dashboard, products, customers, create order, order history, and low-stock products. Products/customers use tables, status badges, and pagination. The order form uses JavaScript for line editing and estimate display; order creation and final calculations remain server-side. Order history and low-stock search call their corresponding APIs. Bootstrap 5 CSS/JS is loaded from jsDelivr.

## Assumptions

- A customer is identified by email. Creating an order with an existing email reuses that customer; it does not overwrite their saved name.
- A product can appear only once in an order; combine its quantity into one line.
- Low-stock results use the strict comparison `stock < threshold`. The dashboard and product status badges use 5 as their displayed baseline; the low-stock API accepts a threshold in its query string.
- The interface displays monetary values with a dollar sign; there is no currency conversion or multi-currency configuration.
- The order-confirmation job is a logging simulation, not an email integration.
- The API and storefront do not include authentication or customer ownership checks; they are intended for this take-home/demo setup, not direct public deployment without additional access controls.
