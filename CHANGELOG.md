# Changelog

## 1.1.0

PHP, MongoDB and PostgreSQL conditions of every operator now select exactly the same rows. The public API is unchanged
(only additions); the behaviour changes below are bug fixes.

### Fixed — behaviour that changes results
- A group (or event block) with a single false condition was dropped in PHP, so `A AND (B)` with B false evaluated as A.
- Numbers are compared exactly (no `intval` truncation): `5.7` is no longer equal to `5`.
- `contains` / `not_contains` / `start_with` / `end_with` are case-insensitive for non-Latin letters in PHP too
  (Cyrillic "Київ" contains "київ"); `end_with` no longer ignores a trailing newline.
- `time_range` in PHP compared the value with today's window only; it now uses the time of day of any date.
- `days_of_week` and the period operators treated an empty value as "now".
- `between_date` with the list form `[from, to]` used `from` as the upper bound.
- `less_than_period` / `more_than_period` had no MongoDB / PostgreSQL condition: the condition was silently dropped.
- An invalid search value (wrong type, broken range or list, infinite number, date outside years 0001…9999,
  fractional period quantity) and an unknown operator now match nothing in every backend, instead of being dropped in
  MongoDB / PostgreSQL (which selected every row) or crashing.
- MongoDB: `not_equal_date` compared the exact second instead of the whole day; negative operators matched documents
  without the field; `days_of_week` / `time_range` failed the whole query on a non-date value.
- PostgreSQL: `more_than_int` used `<`; date conditions written as `[new Expression(...)]` could not be built; date
  literals were not quoted; `is_null` / `is_not_null` failed on non-text columns; `%` / `_` were wildcards in
  `start_with` / `end_with`; column names in expressions are quoted.
- Dates are compared with whole-second precision in every backend (like PHP reads them).
- `validateConditions()` reports errors of nested elements; an element without a `value` key no longer throws.

### Kept as before
- `not_in_range_string_operator` in PHP treats an array value as "not in the list".
- A search text that is not valid UTF-8 is compared byte-wise in PHP.
- An empty list of conditions: `phpQuery()` returns false, MongoDB / PostgreSQL queries have no condition.

See README → "Behaviour shared by PHP, MongoDB and PostgreSQL" for the full rules and the data requirements.
