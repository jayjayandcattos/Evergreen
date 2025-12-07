# Location Table Structure - Database Normalization

## Overview

The database has been restructured to use normalized location tables for efficient address management.

## Database Structure

### 1. **provinces** Table

Stores all provinces in the Philippines.

```sql
CREATE TABLE provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    country VARCHAR(100) DEFAULT 'Philippines',
    region VARCHAR(100) DEFAULT NULL,
    INDEX idx_province_name (province_name)
);
```

**Fields:**

- `province_id`: Primary key
- `province_name`: Name of the province (e.g., "Metro Manila", "Cebu")
- `country`: Country name (defaults to "Philippines")
- `region`: Region classification (e.g., "NCR", "Region VII")

---

### 2. **cities** Table

Stores all cities and municipalities, linked to provinces.

```sql
CREATE TABLE cities (
    city_id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    province_id INT NOT NULL,
    city_type ENUM('city','municipality') DEFAULT 'city',
    zip_code VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_city_name (city_name),
    INDEX idx_province_id (province_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id) ON DELETE CASCADE
);
```

**Fields:**

- `city_id`: Primary key
- `city_name`: Name of city/municipality (e.g., "Makati", "Quezon City")
- `province_id`: Foreign key to provinces table
- `city_type`: Either 'city' or 'municipality'
- `zip_code`: Optional ZIP code for the city
- `created_at`: Timestamp of record creation

**Relationships:**

- Many cities belong to one province

---

### 3. **barangays** Table

Stores all barangays (smallest administrative division), linked to cities.

```sql
CREATE TABLE barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_id INT NOT NULL,
    zip_code VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_barangay_name (barangay_name),
    INDEX idx_city_id (city_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id) ON DELETE CASCADE
);
```

**Fields:**

- `barangay_id`: Primary key
- `barangay_name`: Name of barangay (e.g., "Poblacion", "San Antonio")
- `city_id`: Foreign key to cities table
- `zip_code`: Optional specific ZIP code for the barangay
- `created_at`: Timestamp of record creation

**Relationships:**

- Many barangays belong to one city

---

### 4. **addresses** Table (UPDATED)

Now uses foreign keys to reference normalized location tables.

```sql
CREATE TABLE addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_line VARCHAR(200) NOT NULL,
    barangay_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,
    province_id INT DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    address_type VARCHAR(20) DEFAULT 'home',
    is_primary BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_barangay_id (barangay_id),
    INDEX idx_city_id (city_id),
    INDEX idx_province_id (province_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);
```

**Changes Made:**

- ❌ **REMOVED**: `city VARCHAR(100)` - text field
- ✅ **ADDED**: `barangay_id INT` - foreign key to barangays table
- ✅ **ADDED**: `city_id INT` - foreign key to cities table
- ✅ **ENHANCED**: Foreign key constraints for referential integrity

**Fields:**

- `address_id`: Primary key
- `customer_id`: Foreign key to bank_customers table
- `address_line`: Street address, building, etc.
- `barangay_id`: Foreign key to barangays table (NEW)
- `city_id`: Foreign key to cities table (NEW - replaces VARCHAR city)
- `province_id`: Foreign key to provinces table
- `postal_code`: ZIP/postal code
- `address_type`: Type of address (home, work, billing, etc.)
- `is_primary`: Boolean flag for primary address
- `created_at`: Timestamp of record creation

---

## Data Hierarchy

```
provinces (Province Level)
    ↓
cities (City/Municipality Level)
    ↓
barangays (Barangay Level)
    ↓
addresses (Complete Address)
```

**Example:**

```
Province: Metro Manila
    └─ City: Makati City
        └─ Barangay: Poblacion
            └─ Address: 123 Main Street, Poblacion, Makati City, Metro Manila
```

---

## Benefits of Normalization

### 1. **Data Integrity**

- Prevents typos and inconsistencies in location names
- Enforces referential integrity through foreign keys
- Cascading deletes maintain data consistency

### 2. **Storage Efficiency**

- Stores location names once instead of repeating in every address
- Reduces database size significantly
- Integer foreign keys are more efficient than VARCHAR fields

### 3. **Query Performance**

- Indexed foreign keys enable faster lookups
- Easier to filter and group by location
- Better JOIN performance

### 4. **Data Maintenance**

- Update location names in one place
- Easy to add new locations
- Centralized location management

### 5. **Reporting & Analytics**

- Easy to generate location-based reports
- Count customers by city, province, or barangay
- Geographic analysis simplified

---

## Usage Examples

### Insert a Complete Address

```sql
-- First, ensure location data exists
-- 1. Province
INSERT INTO provinces (province_name, region) VALUES ('Metro Manila', 'NCR');

-- 2. City
INSERT INTO cities (city_name, province_id, city_type, zip_code)
VALUES ('Makati City', 1, 'city', '1200');

-- 3. Barangay
INSERT INTO barangays (barangay_name, city_id, zip_code)
VALUES ('Poblacion', 1, '1210');

-- 4. Customer Address
INSERT INTO addresses (customer_id, address_line, barangay_id, city_id, province_id, postal_code, address_type, is_primary)
VALUES (123, '456 Ayala Avenue', 1, 1, 1, '1210', 'home', 1);
```

### Query Addresses with Location Details

```sql
SELECT
    a.address_line,
    b.barangay_name,
    c.city_name,
    p.province_name,
    a.postal_code
FROM addresses a
LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
LEFT JOIN cities c ON a.city_id = c.city_id
LEFT JOIN provinces p ON a.province_id = p.province_id
WHERE a.customer_id = 123;
```

### Find All Customers in a City

```sql
SELECT
    bc.customer_id,
    bc.first_name,
    bc.last_name,
    c.city_name
FROM bank_customers bc
JOIN addresses a ON bc.customer_id = a.customer_id
JOIN cities c ON a.city_id = c.city_id
WHERE c.city_name = 'Makati City'
AND a.is_primary = 1;
```

---

## Import Instructions

1. **Execute the schema file:**

   ```sql
   SOURCE unified_schema.sql;
   ```

2. **Populate location data** (after schema creation):

   - First populate `provinces` table
   - Then populate `cities` table (linking to provinces)
   - Then populate `barangays` table (linking to cities)
   - Finally, customer addresses can reference these tables

3. **Data migration** (if you have existing addresses with VARCHAR city):
   ```sql
   -- This would need to be done carefully to map old text values to new foreign keys
   -- Contact database administrator for migration scripts
   ```

---

## Status

✅ **Schema Updated**: The `unified_schema.sql` file has been updated with normalized location tables.

⚠️ **No Data Added**: As requested, only table structures have been created. No sample data has been inserted.

📋 **Next Steps**:

1. Import the schema to phpMyAdmin
2. Populate location reference data (provinces, cities, barangays)
3. Begin using normalized addresses for new customer records

---

## Database File Location

**File**: `c:\xampp\htdocs\SIASIANOVA\Evergreen\accounting-and-finance\database\sql\unified_schema.sql`

This file is ready to import into phpMyAdmin.
