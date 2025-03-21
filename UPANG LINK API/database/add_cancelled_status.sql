-- Add 'cancelled' to the request status options
USE upang_link;

-- Alter the requests table to modify the status ENUM
ALTER TABLE requests 
MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'PENDING';

-- Verify the change
DESCRIBE requests; 