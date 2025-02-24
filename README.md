Digital Traffic Tracker

Simple website traffic tracker that allows website owners to track unique visits to their web pages. It consists of three main components:
- JavaScript Tracker: A client-side script, to be embedded by website owners, that tracks visits and sends data to the server.
- PHP Backend: A server-side application that processes and stores tracking data in a MySQL database.
- User Interface: A simple UI to view tracking data in a table and filter it by date time and/or website owner.

Features

- Unique Visitor Tracking: Tracks unique visits by creating a persistent cookie with a unique generated identifier.
- API Token Authentication: Ensures only authorized clients can send tracking data.
- Configurable cookie name on client side
- Debug mode for tracker script to view unwanted errors in console
- Multiple Clients: Data is stored by client. Depending on the use, data can be aggregated or fetch for a specific client.
- CSRF Protection and Input Sanitization: Secures the submitted tracking data and form submissions.
- Router with HT_ACCESS: Protected routing for public access
- Filtering by Date Time: Allows filtering of tracking data by a specified date range.
- Filtering by Client: Allows filtering by website owner.

Database Integration: Stores tracking data in a MySQL database and joins it with client information.

Prerequisites:
PHP (version 8.0 or higher)
MySQL
A web server (e.g., Apache, Nginx)

Run migrations:
```sql
CREATE DATABASE IF NOT EXISTS digital_traffic_tracker;
USE digital_traffic_tracker;

CREATE TABLE IF NOT EXISTS clients
(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    api_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS trackings
(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    url VARCHAR(255) NOT NULL,
    tracked_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE ON UPDATE CASCADE
);
```

Usage Examples:
```javascript
<script src="https://yourdomain.com/tracker.js" data-token="your_api_token_here"></script>
```

With debug and different cookie name
```javascript
<script src="https://yourdomain.com/tracker.js" data-token="your_api_token_here" data-cookie="cookie_name" data-debug="1"></script>
```

Project Structure:
/app
    /Classes
        Helpers.php                 # Helper class with reusable helper functions
    /Controllers
        TrackerController.php       # PHP class for handling database operations and actions
/database
    create_clients_table.sql        # Clients table database schema
    create_trackings_table.sql      # Trackings table database schema
/public
    .ht_access                      # Public HT_ACCESS file for route protection
    index.php                       # Main php file where routes are defined
    tracker.js                      # Tracker script to be embedded in website
    trackings.html                  # Page for displaying tracked data
/src
    functions.php                   # Router implementation
    queue.php                       # Redis queue worker (NOT USED - Possible improvement)
.ht_access                          # Main HT_ACCESS file for route protection
README.md                           # Project readme file

Possible improvements and design choices:
- Clients table: Scaling possibility to multiple clients
- Redis queue for processing tracking requests to improve efficiency
- GDPR consent for using cookie
- Tracked Data page: Nicer UI, pagination, ajax calls for filtering, sort by column
- Dynamic table view: Depending on who is wanted to see the tracked data table
- Determining visitor uniqueness could be done with libraries like FingerprintJS, which generates a unique identifier based on more intricate collected data
- Access timestamps displayed in website owner's timezone (Now it's UTC for convenience)