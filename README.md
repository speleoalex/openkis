# OpenKIS

**Opensource Karst Information System** - A PHP/MySQL web application for managing speleological and karst geological data (caves, springs, glaciers, fauna surveys).

## Features

- GIS mapping with coordinate system transformations (Proj4)
- 3D cave visualization (CaveView.js)
- Multi-region support with customizable themes
- Data export: KML, GPX, JSON, Shapefile
- REST API endpoints

## Requirements

- PHP 7+
- MySQL/MariaDB
- Apache/Nginx web server

## Installation

1. Copy `extra/openkis_config.local.example.php` to `extra/openkis_config.local.php`
2. Configure database credentials:

   ```php
   $_FN['xmetadb_mysqlhost'] = 'localhost';
   $_FN['xmetadb_mysqldatabase'] = 'your_database';
   $_FN['xmetadb_mysqlusername'] = 'your_user';
   $_FN['xmetadb_mysqlpassword'] = 'your_password';
   ```

3. Upload files to your web server
4. Navigate to `http://[your-domain]/`
5. Run the installation wizard

## Support the Project

[![Donate with PayPal](paypal.png)](https://www.paypal.com/donate/?business=TKQWLKGENEP7L&no_recurring=0&item_name=Openkis+project%3A%0AOpensource+Karst+Information+System%0A&currency_code=EUR)

