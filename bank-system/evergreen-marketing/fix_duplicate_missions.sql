-- Fix Duplicate Mission Collection Issue
-- This script ensures missions can only be collected once per user

-- Select the database (change 'BankingDB' to your actual database name if different)
USE BankingDB;

-- Step 1: Check if user_missions table exists and has the right structure
DESCRIBE user_missions;

-- Step 2: Remove any duplicate entries (keep only the first occurrence)
DELETE t1 FROM user_missions t1
INNER JOIN user_missions t2 
WHERE 
    t1.id > t2.id AND
    t1.user_id = t2.user_id AND 
    t1.mission_id = t2.mission_id;

-- Step 3: Add UNIQUE constraint if it doesn't exist
-- This prevents the same user from collecting the same mission twice
ALTER TABLE user_missions 
ADD UNIQUE KEY unique_user_mission (user_id, mission_id);

-- Step 4: Verify the constraint was added
SHOW INDEX FROM user_missions WHERE Key_name = 'unique_user_mission';

-- Step 5: Check for any remaining duplicates (should return 0 rows)
SELECT user_id, mission_id, COUNT(*) as count
FROM user_missions
GROUP BY user_id, mission_id
HAVING count > 1;

-- Step 6: Verify total points match collected missions
SELECT 
    bc.customer_id,
    bc.first_name,
    bc.last_name,
    bc.total_points as current_points,
    COALESCE(SUM(um.points_earned), 0) as calculated_points,
    (bc.total_points - COALESCE(SUM(um.points_earned), 0)) as difference
FROM bank_customers bc
LEFT JOIN user_missions um ON bc.customer_id = um.customer_id
GROUP BY bc.customer_id, bc.first_name, bc.last_name, bc.total_points
HAVING difference != 0;

-- If there are discrepancies, you can fix them with:
-- UPDATE bank_customers bc
-- SET total_points = (
--     SELECT COALESCE(SUM(points_earned), 0)
--     FROM user_missions
--     WHERE customer_id = bc.customer_id
-- );
