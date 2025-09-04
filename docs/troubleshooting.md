# Troubleshooting

## Dates

Date, as opposed to DateTime, fields can cause an issue especially with comparisons.

E.g. in Snapshot, `date` is of type `Date`. It also appears that way in the SQLite database however I believe the Date type is not strict in SQLite. 

* Snapshot::create(['date' => now()]) may persist to SQLite with a time.
  * EVEN THOUGH if you fetch it via $snapshot->date it may display a date only, or date with time reset to 0:00:00
    $query->where('date', '<', $beforeDate);
  * So Snapshot has a mutator where it strips the time before persisting.
* For a query of `$query->where('date', '<', $beforeDate);` where $beforeDate is a Carbon date
  * If the date in the database is '2025-09-03' and $beforeDate is '2025-09-03 00:00:00.000000' then it will match even though it should be considered EQUAL not LESS THAN. This is presumably because it's doing a string compare not a date compare. The solution is to strip the time from the date before the compare.
