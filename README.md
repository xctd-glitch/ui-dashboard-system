# UI Dashboard System — Dashboards, Redirect System, and REST API

A comprehensive dashboard management system with multi-tier architecture for Superadmin, Admin, and User roles, featuring a sophisticated redirect logic system and REST API.

## Architecture Overview

### 1. **Superadmin Dashboard**
- **Admin Management**: Create, update, delete admins; reset passwords; assign tags (read-only after assignment)
- **Global Configuration**:
  - Countries management via textarea (ISO alpha-2 codes)
  - Target URL management (add/edit/delete)
  - Tag creation and management
- **System Controls**: Global On/Off toggle
- **Privileges**: Full system configuration and elevated actions

### 2. **Admin (Pre-admin) Dashboard**
- **User Management**: Create, update, delete users; reset passwords; assign tags
- **Routing Inputs**:
  - Manage target URLs per user
  - Device scope selection (WAP, WEB, ALL)
  - Countries configuration with ISO alpha-2 validation
- **Parked Domains**:
  - Add/delete domains via textarea
  - Automatic wildcard handling
  - Optional Cloudflare DNS sync
  - Limits: 1–10 domains
- **History & Control**: Activity logs, reporting

### 3. **User Dashboard**
- **Parked Domains**:
  - Add/delete domains line-by-line with auto-wildcard
  - 1–10 domain limits
  - Domain selection based on preferences
- **Domain Selection Options**:
  - Random global domain (Admin-tagged)
  - Random user domain (User-tagged)
  - Specific domain (e.g., domain.com, example.us)
- **Redirect Rules**: Mute/unmute, random route, static route
- **Metrics**: Clicks, events by country, IP, device
- **Routing Configuration**: Device scope, countries, target URLs

### 4. **REST API Endpoints**

#### Authentication
- `POST /api/auth.php?action=logout` - User logout

#### Admin Management (Superadmin only)
- `GET /api/admins.php?action=list` - List all admins
- `POST /api/admins.php?action=create` - Create admin
- `PUT /api/admins.php?action=update` - Update admin
- `POST /api/admins.php?action=reset_password` - Reset admin password
- `DELETE /api/admins.php?action=delete&id=X` - Delete admin

#### User Management (Admin only)
- `GET /api/users.php?action=list` - List users
- `POST /api/users.php?action=create` - Create user
- `PUT /api/users.php?action=update` - Update user
- `POST /api/users.php?action=reset_password` - Reset user password
- `DELETE /api/users.php?action=delete&id=X` - Delete user

#### Domain Management
- `GET /api/domains.php?action=admin_domains` - Get admin domains
- `GET /api/domains.php?action=user_domains` - Get user domains
- `POST /api/domains.php?action=add_admin_domains` - Add admin domains
- `POST /api/domains.php?action=add_user_domains` - Add user domains
- `DELETE /api/domains.php?action=delete_admin_domain&id=X` - Delete admin domain
- `DELETE /api/domains.php?action=delete_user_domain&id=X` - Delete user domain

#### Countries Management
- `GET /api/countries.php?action=admin_countries` - Get admin countries
- `GET /api/countries.php?action=user_countries` - Get user countries
- `POST /api/countries.php?action=update_admin_countries` - Update admin countries
- `POST /api/countries.php?action=update_user_countries` - Update user countries

#### Target URLs Management
- `GET /api/target_urls.php?action=admin_urls` - Get admin URLs
- `GET /api/target_urls.php?action=user_urls` - Get user URLs
- `POST /api/target_urls.php?action=add_admin_url` - Add admin URL
- `POST /api/target_urls.php?action=add_user_url` - Add user URL
- `PUT /api/target_urls.php?action=update_admin_url` - Update admin URL
- `PUT /api/target_urls.php?action=update_user_url` - Update user URL
- `DELETE /api/target_urls.php?action=delete_admin_url&id=X` - Delete admin URL
- `DELETE /api/target_urls.php?action=delete_user_url&id=X` - Delete user URL

#### Redirect Rules
- `GET /api/rules.php?action=list` - List user rules
- `POST /api/rules.php?action=create` - Create rule
- `PUT /api/rules.php?action=update` - Update rule
- `DELETE /api/rules.php?action=delete&id=X` - Delete rule

#### Metrics & Reporting
- `GET /api/metrics.php?action=summary&range=X` - Get summary metrics
- `GET /api/metrics.php?action=by_country&range=X` - Metrics by country
- `GET /api/metrics.php?action=by_device&range=X` - Metrics by device
- `GET /api/metrics.php?action=by_ip&range=X` - Top IP addresses

#### Redirect System
- `GET /api/redirect.php?user_id=X&token=Y` - Process redirect decision

#### Tags Management
- `GET /api/tags.php?action=list` - List all tags
- `POST /api/tags.php?action=create` - Create tag
- `DELETE /api/tags.php?action=delete&id=X` - Delete tag

## Redirect System (Safe Query)

### Decision Flow
```
system_on = true
   → is_rule ?
       → Match: is_countries ?, is_device ?, is_vpn ?
           → target_url
     else
       → normal (no decision)
```

### Rule Types

1. **Mute/Unmute Cycle (2m on / 5m off)**
   - While enabled, returns redirect decision for 2 minutes
   - Then behaves normally for 5 minutes
   - Cycle repeats until disabled

2. **Random Route**
   - Randomizes target from eligible traffic
   - Filters by country, device, VPN status

3. **Static Route**
   - Always returns configured target
   - No exceptions

### Security Features
- Mini WAF with strict validators
- Clean URL handling
- Anti-abuse safeguards
- Input validation and prepared statements
- HTTPS security headers

## Database Schema

### Core Tables
- `system_config` - Global system settings
- `superadmins` - Superadmin accounts
- `admins` - Admin accounts
- `users` - User accounts
- `tags` - Tag definitions

### Configuration Tables
- `admin_countries` - Countries per admin
- `user_countries` - Countries per user
- `admin_target_urls` - URLs per admin
- `user_target_urls` - URLs per user
- `admin_parked_domains` - Parked domains per admin
- `user_parked_domains` - Parked domains per user
- `user_routing_config` - User routing settings
- `user_domain_selection` - User domain selection preferences

### Rules & Analytics Tables
- `redirect_rules` - Redirect rules configuration
- `rule_state` - State tracking for rules
- `redirect_logs` - All redirect requests
- `system_logs` - Audit trail

## Setup & Installation

### Requirements
- PHP 8.3+
- MySQL 5.7+
- PDO MySQL extension
- HTTPS support

### Configuration

1. **Database Setup**:
```bash
mysql -u root -p < db/schema.sql
```

2. **Update Database Credentials**:
Edit `config/database.php` with your credentials

3. **Web Server Configuration**:
Point document root to `/public` directory

## Features

### Input Validation
- Username validation (3-255 chars)
- Password strength (min 8 characters)
- Email validation
- URL validation
- ISO country code validation
- Domain name validation
- CSV/textarea auto-validation

### Security
- Password hashing with Argon2ID
- Prepared statements for all queries
- Session-based authentication
- Role-based access control
- HTTPS headers
- XSS protection

### Performance
- Efficient database queries with indexing
- Stateless API design
- Fast redirect response times

## License

Proprietary - All rights reserved