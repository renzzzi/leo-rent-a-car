# Leo Rent-a-Car System

For managing and tracking bookings, customers, vehicles, drivers, expense, profit and maintenance of the Leo Rent-a-Car business.

## Technical Standards
- **config** - Remove the trailing .example
- **public/index.php** - Assembles the views in an organized manner from top to down: header.php -> x_view.php -> footer.php
- **public/css** - Where CSS dependencies and custom styles go
- **public/images** - All images used in the system will go here
- **public/js** - Each x_view.php file in the views folder will have an xViewScript.js equivalent
- **src** - Stores the Repository and Service files for each database entity
- **views** - Stores all the HTML for the system. Separated to different folders for each "tab" in the system. Make sure folder name match the table name in schema and _GET value in header.php.
- **views/layouts/header.php** - Contains the header HTML up to the opening main tag, includes CSS dependencies, and _GET values for each tab.
- **views/layouts/header.php** - Contains the closing main, body, and html tag. Also contains JS dependencies.
- **schema.sql** - Stores the tables definitions and seeders for them. Paste everything and execute to get a fresh version of the database again.


# Repository Files (/src)

Contains all raw SQL commands and is responsible for directly controlling the database. Functions within repository files must not be called by any other external files except the service file equivalent.

# Service Files (/src)

Interacts with the respository file and must not contain any SQL. Also does any needed calculations and logic before passing data to the repository file. The functions within service files are the main gateway for external files to interact with the database.