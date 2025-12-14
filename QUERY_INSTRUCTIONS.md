# How to Check Your Rulebooks Database

## Quick Start

You have **6 separate SQL queries** in `rulebooks_query.sql`. Each query answers a different question.

## How to Run These Queries

### Option 1: Using phpMyAdmin (Web Interface)
1. Log into your hosting control panel
2. Open phpMyAdmin
3. Select the `working_vbn` database
4. Click the "SQL" tab
5. Copy and paste **ONE query at a time** from `rulebooks_query.sql`
6. Click "Go" to run it
7. Repeat for each query you want to run

### Option 2: Using MySQL Command Line
1. Connect to your database:
   ```
   mysql -h vdb5.pit.pair.com -u working_64 -p working_vbn
   ```
2. Enter your password when prompted
3. Copy and paste **ONE query at a time** from `rulebooks_query.sql`
4. Press Enter to run it

### Option 3: Using MySQL Workbench or Other Database Tool
1. Connect to your database server
2. Open a new query window
3. Copy and paste **ONE query at a time** from `rulebooks_query.sql`
4. Execute the query

## What Each Query Does

### Query 1: Count Total Books
- **What it does**: Counts how many books are in your database
- **When to use**: First thing to run - verifies the table exists
- **Result**: A single number (e.g., "56")

### Query 2: List All Books
- **What it does**: Shows every book with all its details
- **When to use**: When you want to see the complete list
- **Result**: A table with all books, their categories, and page counts

### Query 3: Summary Statistics
- **What it does**: Gives you overall statistics
- **When to use**: To get a quick overview
- **Result**: Total books, how many have content, how many don't, total pages

### Query 4: Total Pages Count
- **What it does**: Counts all extracted pages
- **When to use**: To see total extracted content
- **Result**: A single number (e.g., "9,000")

### Query 5: Top 10 Books
- **What it does**: Shows the 10 books with the most extracted pages
- **When to use**: To see which books have the most content
- **Result**: A table of the top 10 books

### Query 6: Missing Content
- **What it does**: Lists books that have NO extracted pages
- **When to use**: To find books that need extraction
- **Result**: A table of books without content

## Important Notes

- **Run queries ONE AT A TIME** - Don't copy all 6 at once
- Each query is separated by blank lines and comments
- Start with Query 1 to verify everything works
- Query 2 will show you all your books - this is probably what you want most

## Example: Finding Your Books

If you just want to see what books you have:

1. Run **Query 1** first (to verify it works)
2. Then run **Query 2** (to see all books)
3. That's it! Query 2 shows everything you need
