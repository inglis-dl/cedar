-- -----------------------------------------------------
-- Dictionaries
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

INSERT IGNORE INTO dictionary( id, name, description ) 
VALUES( 1, "confirmation", "yes and no response words for MAT confirmation type tests" );

INSERT IGNORE INTO dictionary( id, name, description ) 
VALUES( 2, "alpha_numeric", "alpha numeric response words for the MAT (alternation) test" );

INSERT IGNORE INTO dictionary( id, name, description ) 
VALUES( 3, "REY_words", "predefined words for the REY test" );

INSERT IGNORE INTO dictionary( id, name, description ) 
VALUES( 4, "REY_intrusions", "REY test intrusions" );

LOAD DATA LOCAL INFILE 'confirmation_dictionary.csv' IGNORE 
INTO TABLE word 
CHARACTER SET latin1
FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n'
( word, language, dictionary_id );

LOAD DATA LOCAL INFILE 'alpha_numeric_dictionary.csv' IGNORE
INTO TABLE word
CHARACTER SET latin1
FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n'
( word, language, dictionary_id );

LOAD DATA LOCAL INFILE 'rey_dictionary.csv' IGNORE
INTO TABLE word
CHARACTER SET latin1
FIELDS TERMINATED BY ',' ENCLOSED BY '"'  LINES TERMINATED BY '\n'
( word, language, dictionary_id );

COMMIT;
