-- Accounts with snapshot balance for user
SELECT
    a.id,
    a.name,
    json_extract(j.value, '$.balance') AS balance,
    a.retirement_type,
    a.currency,
    a.conversion_rate
FROM accounts a
         LEFT JOIN snapshots s
                   ON s.id = 41
         LEFT JOIN json_each(s.accounts) j
                   ON a.id = j.key
WHERE a.user_id = 11;
