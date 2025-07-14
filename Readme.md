
 🛒 Multi-Vendor E-Commerce Platform

A core PHP-based multi-vendor e-commerce application that allows multiple vendors to register, list products, manage orders, and operate under a single platform.

 Tech Stack

-Backend: Core PHP
-Database: MySQL
-Frontend: HTML, CSS, JavaScript
-Payment Gateway: PayPal (Sandbox/Live)

 Features

- Vendor registration and login  
- Vendor product management (add/edit/delete)  
- Customer cart and checkout  
- Order placement and basic tracking  
- PayPal integration for secure transactions  

 Project Structure

```
multi-vendor-ecommerce/
app/           # Business logic and application flow
config/        # Configuration files (e.g., DB settings)
core/          # Core classes (e.g., routing, controllers) database/      # SQL scripts or DB setup files
public/        # Public-facing files (index.php, assets)
screenshots/   # Screenshots of major sections 
```

 Setup Instructions

1.Clone this repository

   git clone https://github.com/SreejaSreekumarSujatha/multi-vendor-ecommerce


2.Move Project to Local Server
Place the project inside your web root (e.g., htdocs for XAMPP or www for WAMP).

3.Import the database
   - Create a MySQL database (e.g., `multi_vendor`)
   - Import the SQL file from the `database/` directory using phpMyAdmin or MySQL CLI

4. Update configuration
   - Open `config/database.php` or `.env` (if exists)
   - Set your DB credentials
   
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multi_vendor');

5.. Configure PayPal Integration
Sign up at PayPal Developer Portal

Create a sandbox app and get your Client ID and Secret

Add your PayPal credentials to a config file like config/paypal.php

💳 PayPal Integration
This application uses PayPal to securely process transactions.

PayPal sandbox ready for test mode

Seamless checkout process

Configurable PayPal settings in one place

6.Screenshots


![Register](Screenshots/register.png)
![Login](Screenshots/login.png)
![Vendor Dashboard](Screenshots/vendor_dashboard.png)
![Vendor Product List](Screenshots/vendor_pdtlist.png)
![Vendor Add new Product ](Screenshots/vendor_addnew_pdt.png)
![Vendor Order List](Screenshots/vendor_orderlist.png)
![Vendor Earnings](Screenshots/vendor_earnings.png)
![Customer Dashboard](Screenshots/customer_dashboard.png)
![Customer Product List](Screenshots/customer_pdtcatlog.png)
![CustomerShpg Cart](Screenshots/customer_shpgcart.png)
![Oaypal Integration](Screenshots/paypal_integration.png)
![Customer Product List](Screenshots/customer_pdtcatlog.png)
![Customer Order History](Screenshots/customer_orderhistory.png)
![Customer Addtocart](Screenshots/customer_addtocart.png)

 Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

 License

This project is open-source and available under the [MIT License](LICENSE).
