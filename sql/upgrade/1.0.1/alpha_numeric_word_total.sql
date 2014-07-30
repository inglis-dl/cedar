CREATE OR REPLACE VIEW alpha_numeric_word_total AS
SELECT w.id AS word_id, 
COUNT(tean.id) AS total, 
w.dictionary_id AS dictionary_id 
FROM word w
LEFT JOIN test_entry_alpha_numeric tean ON tean.word_id=w.id
JOIN dictionary d ON d.id=w.dictionary_id
GROUP BY w.id;
