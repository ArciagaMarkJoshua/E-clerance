-- First, add ProgramCode column if it doesn't exist
ALTER TABLE sections ADD COLUMN IF NOT EXISTS ProgramCode VARCHAR(50) NULL;

-- Update existing sections to set ProgramCode to BSIT where it's NULL
UPDATE sections SET ProgramCode = 'BSIT' WHERE ProgramCode IS NULL;

-- Make ProgramCode NOT NULL after setting default values
ALTER TABLE sections MODIFY COLUMN ProgramCode VARCHAR(50) NOT NULL;

-- Add foreign key constraint if it doesn't exist
SET @constraint_name = 'fk_sections_program';
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sections'
    AND CONSTRAINT_NAME = @constraint_name
);

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE sections ADD CONSTRAINT fk_sections_program FOREIGN KEY (ProgramCode) REFERENCES programs (ProgramCode)',
    'SELECT "Foreign key constraint already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update section codes to new format
UPDATE sections 
SET SectionCode = CONCAT(ProgramCode, YearLevel, SUBSTRING(SectionCode, -1)),
    SectionTitle = CONCAT(ProgramCode, YearLevel, SUBSTRING(SectionCode, -1));

-- Insert new sections for BSIT program
INSERT INTO sections (SectionCode, SectionTitle, YearLevel, ProgramCode)
SELECT 
    CONCAT('BSIT', l.LevelID, s.section_letter),
    CONCAT('BSIT', l.LevelID, s.section_letter),
    l.LevelID,
    'BSIT'
FROM 
    levels l
CROSS JOIN (
    SELECT 'A' as section_letter UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D'
) s
WHERE NOT EXISTS (
    SELECT 1 FROM sections 
    WHERE SectionCode = CONCAT('BSIT', l.LevelID, s.section_letter)
);

-- Insert new sections for BSCS program
INSERT INTO sections (SectionCode, SectionTitle, YearLevel, ProgramCode)
SELECT 
    CONCAT('BSCS', l.LevelID, s.section_letter),
    CONCAT('BSCS', l.LevelID, s.section_letter),
    l.LevelID,
    'BSCS'
FROM 
    levels l
CROSS JOIN (
    SELECT 'A' as section_letter UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D'
) s
WHERE NOT EXISTS (
    SELECT 1 FROM sections 
    WHERE SectionCode = CONCAT('BSCS', l.LevelID, s.section_letter)
); 