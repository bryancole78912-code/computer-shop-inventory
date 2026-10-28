# Computer Shop Inventory Management System

**Platform:** Web Application  
**Prepared by:** Bryan Cahayag Cole  
**Date:** September 2026  

---

## 1. Project Overview

The **Computer Shop Inventory Management System** is a responsive web application designed specifically for computer shops handling hardware components (CPUs, GPUs, RAM, Motherboards, Storage, Power Supplies), peripherals, cables, accessories, and pre-built systems.

It transitions computer hardware shops away from paper logbooks and manual spreadsheets into an automated, role-based platform that enforces real-time stock counting, supplier restock tracking, sales dispatch recording, and low-stock alerts.

---

## 2. Default User Roles & Credentials

The system implements Role-Based Access Control (RBAC) with three distinct operational roles:

| Role | Username | Password | Access Privileges |
| :--- | :--- | :--- | :--- |
| **Shop Manager / Admin** | `admin` | `admin123` | Full system authority: manage users, add/edit/delete hardware components, customize minimum thresholds, oversee valuation reports, and audit logs. |
| **Computer Shop Staff** | `staff` | `staff123` | Operational floor access: execute Stock-In (restock) and Stock-Out (sales/transfers), view hardware catalog, and review low-stock alerts. |
| **Auditor / Supervisor** | `auditor` | `auditor123` | Read-only access: review stock balances, inspect chronological audit logs, and export reports without editing rights. |

---

## 3. System Features & Modules

### 1. Executive Dashboard (`index.php`)
- **Metric Cards:** Total Hardware SKUs, Total Units in Stock, Total Inventory Cost vs. Retail Value, Low-Stock Item Count, and Out-of-Stock count.
- **Stock Distribution Chart:** Real-time visual category breakdown powered by Chart.js.
- **Restock Priority Feed:** Immediate alerts for hardware items below minimum reorder thresholds.
- **Recent Stock Movements:** Latest transaction ledger directly on the dashboard.

### 2. Hardware Catalog Management (`products.php`)
- Component categories: Processors (CPU), Graphics Cards (GPU), Motherboards, Memory (RAM), Storage (NVMe/SATA/HDD), Power Supplies (PSU), PC Cases & Cooling, Peripherals (Monitors, Keyboards, Mice), Cables & Accessories, Pre-builts/Laptops.
- Details tracked: SKU / Part Code, Name, Category, Brand, Technical Specs, Shelf/Bin Location, Unit Cost, Retail Selling Price, Available Quantity, Minimum Alert Threshold.
- Live client-side search across name, SKU, brand, and specifications.
- Category filtering and Stock Level status filtering (In Stock, Low Stock, Out of Stock).
- CSV export capability.

### 3. Category Management (`categories.php`)
- Categorization and classification of computer hardware components with icons and live product counts.

### 4. Stock-In (Supplier Restock) (`stock_in.php`)
- Record component shipments from distributors (TechFrontier, Apex Hardware, Nexus Components).
- Automatically increments inventory stock counts, updates unit costs, and logs an immutable `IN` transaction with reference numbers.

### 5. Stock-Out (Sales / Issue) (`stock_out.php`)
- Record customer sales, custom PC builds, supplier RMA replacements, and internal shop testing.
- **Inventory Bound Validation:** Prevents issuing more units than are currently available in stock to eliminate negative quantities.
- Decrements stock counts and logs an immutable `OUT` transaction.

### 6. Low-Stock Alerts & Reorder Manager (`low_stock.php`)
- Highlights depleted and low-quantity hardware parts.
- Automatically calculates **Suggested Reorder Quantities** and **Estimated Supplier Restock Budget**.
- One-click **"Print Purchase Reorder Sheet"** formatted cleanly for physical supplier ordering.
- CSV export of reorder lists.

### 7. Transaction History & Audit Log (`transactions.php`)
- Complete historical ledger of every stock movement.
- Multi-parameter filtering by Movement Type (IN / OUT), Reason/Purpose, and Date Range.
- Printable audit report and CSV export.

### 8. User Administration (`users.php` - Admin Only)
- User account creation, role assignment, password modification, and account status toggling (Active / Disabled).

---

## 4. How to Run the Application

### Option A: Instant Standalone Browser Demo (Zero Configuration)
If you want to immediately test, evaluate, or present the system without setting up MySQL or Apache first:
1. Navigate to:  
   `C:\Users\bryan\.gemini\antigravity\scratch\computer-shop-inventory\demo.html`
2. Double-click `demo.html` to open it in **Google Chrome**, **Microsoft Edge**, or any modern web browser.
3. The standalone edition runs completely client-side using `localStorage` with preloaded computer hardware data.
4. You can switch between **Admin**, **Staff**, and **Auditor** roles via the top-bar role selector to test all permissions, execute stock transactions, add components, and print reports.
5. Click **"Reset Data"** at any time to restore default inventory figures.

---

### Option B: Localhost Deployment (XAMPP / WAMP / PHP + MySQL)
To deploy the application as specified in the proposal:

1. **Install & Start XAMPP**:
   - Open the **XAMPP Control Panel**.
   - Start the **Apache** and **MySQL** services.

2. **Copy Project to `htdocs`**:
   - Copy the entire folder:  
     `C:\Users\bryan\.gemini\antigravity\scratch\computer-shop-inventory`  
     into your XAMPP root:  
     `C:\xampp\htdocs\computer-shop-inventory`

3. **Import the Database**:
   - Open your browser and visit: `http://localhost/phpmyadmin`
   - Create a new database named: `computer_shop_inventory`
   - Click the **Import** tab.
   - Choose and import `database/schema.sql`.
   - Next, choose and import `database/seed_data.sql`.

4. **Access the System**:
   - Open your browser and navigate to:  
     `http://localhost/computer-shop-inventory/`
   - Log in using one of the demo credentials:
     - **Admin:** `admin` / `admin123`
     - **Staff:** `staff` / `staff123`
     - **Auditor:** `auditor` / `auditor123`
   *(Quick 1-click login buttons are also provided on the login screen for convenience).*

---

## 5. Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons 1.11, Chart.js.
- **Backend:** PHP (PDO database abstraction, secure password hashing, prepared statements).
- **Database:** MySQL / MariaDB (InnoDB with foreign keys, cascading rules, and indexes).
- **Environment:** Localhost (XAMPP / WAMP / Native PHP Server).
