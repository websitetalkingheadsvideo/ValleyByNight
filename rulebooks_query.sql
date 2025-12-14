-- ============================================
-- RULEBOOKS DATABASE QUERIES
-- ============================================
-- These are SEPARATE queries - run them ONE AT A TIME
-- Each query answers a different question about your books
-- ============================================

-- ============================================
-- QUERY 1: Count Total Books
-- ============================================
-- Purpose: See how many books are in the database
-- Run this first to verify the table exists
-- ============================================
SELECT COUNT(*) as total_books FROM rulebooks;


-- ============================================
-- QUERY 2: List All Books with Details
-- ============================================
-- Purpose: See every book with its page counts
-- Shows: ID, Title, Category, System, PDF pages, Extracted pages
-- ============================================
SELECT 
    id,
    title,
    category,
    system,
    total_pages,
    (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) as extracted_pages
FROM rulebooks 
ORDER BY title;


-- ============================================
-- QUERY 3: Summary Statistics
-- ============================================
-- Purpose: Get overall statistics about your books
-- Shows: Total books, books with content, books without content, total pages
-- ============================================
SELECT 
    COUNT(*) as total_books,
    SUM(CASE WHEN (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) > 0 THEN 1 ELSE 0 END) as books_with_content,
    SUM(CASE WHEN (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) = 0 THEN 1 ELSE 0 END) as books_without_content,
    SUM((SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id)) as total_extracted_pages
FROM rulebooks;


-- ============================================
-- QUERY 4: Total Pages in Database
-- ============================================
-- Purpose: Count all extracted pages across all books
-- ============================================
SELECT COUNT(*) as total_pages FROM rulebook_pages;


-- ============================================
-- QUERY 5: Top 10 Books by Page Count
-- ============================================
-- Purpose: See which books have the most extracted content
-- ============================================
SELECT 
    r.id,
    r.title,
    COUNT(rp.id) as page_count,
    SUM(LENGTH(rp.content)) as total_characters
FROM rulebooks r
LEFT JOIN rulebook_pages rp ON r.id = rp.rulebook_id
GROUP BY r.id, r.title
ORDER BY page_count DESC
LIMIT 10;


-- ============================================
-- QUERY 6: Books Missing Extracted Content
-- ============================================
-- Purpose: Find books that have NO extracted pages
-- These are books that need to be extracted
-- ============================================
SELECT 
    id,
    title,
    category,
    system,
    total_pages
FROM rulebooks
WHERE (SELECT COUNT(*) FROM rulebook_pages WHERE rulebook_id = rulebooks.id) = 0
ORDER BY title;
