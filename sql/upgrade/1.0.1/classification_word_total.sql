CREATE OR REPLACE VIEW classification_word_total AS
SELECT w.id AS word_id, 
COUNT(tec.id) AS total, 
w.dictionary_id AS dictionary_id 
FROM word w
LEFT JOIN test_entry_classification tec ON tec.word_id=w.id
JOIN dictionary d ON d.id=w.dictionary_id
GROUP BY w.id;
