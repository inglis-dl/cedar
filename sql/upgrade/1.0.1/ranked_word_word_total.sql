CREATE OR REPLACE VIEW ranked_word_word_total AS
SELECT w.id AS word_id, 
COUNT(terw.id) AS total, 
w.dictionary_id AS dictionary_id 
FROM word w
LEFT JOIN test_entry_ranked_word terw ON terw.word_id=w.id
JOIN dictionary d ON d.id=w.dictionary_id
GROUP BY w.id;
