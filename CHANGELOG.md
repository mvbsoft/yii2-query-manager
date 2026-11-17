# Changelog

## 1.3.0 (unreleased)

### Added
- `QueryBuilder::$eventHistory` (off by default): an event holds a list of arrivals and an event block means "there is
  an arrival where all conditions of the block match" — `$elemMatch` in MongoDB, `EXISTS` over a `jsonb` column of
  the event in PostgreSQL 16+, any arrival of `$data['<event>']` in PHP. `"matching": false` on an event block
  negates it (no arrival matches); only `false` itself negates, any other value (no key, `null`, `"false"`, `0`) means
  matching and is accepted by `validateConditions()`. `time_range_operator` / `days_of_week_operator` inside a block match nothing.
  An empty block means "there is at least one arrival": a non-empty list (`"<event>.0": {"$exists": true}` in
  MongoDB, a non-empty `jsonb` array in PostgreSQL). See README → "Event history".
- With `eventHistory` PHP reads an event only from `$data['<event>']`: no key, `null` and `[]` are no arrivals, flat
  `<event>.<field>` keys are not read, and any other value (not a list, or a list with an element that is not an
  array) is a `QueryBuilderException` naming the event.
- With `eventHistory` `validateConditions()` also checks the inside of an event block: every column must start with
  `<event>.` (error on `column`), and another event or `nodes` block is rejected (error on `type`). At run time such a
  block with conditions throws a `QueryBuilderException`, before the PHP data is read.
- History search as a reusable part: `HistoryBlock` checks a block against a list of records kept under one key
  (MongoDB field, PostgreSQL `jsonb` column, PHP data key); `QueryBuilder::historyBlock()` connects element types to
  it — today event blocks. See README → "History search".
- `OperatorAbstract::notUsedInEventHistory()`: the slugs of the operators that do not work inside an event block
  (`time_range_operator` and `days_of_week_operator`). `OperatorAbstract::history()` is `false` for them; `toArray`
  returns the flag as `not_used_in_event_history`, so a UI can hide such operators. Inside a history search
  `validateConditions()` rejects them.
- `QueryBuilder::evaluatePhpWhere()`, `QueryBuilder::eventHistoryModel()`.

### Changed — behaviour that changes results (with and without `eventHistory`)
- An empty event block (no condition inside) without `eventHistory` means "the client has data of the event" in
  MongoDB (a non-empty array or object) and PHP (a `<event>.*` key). Before, the block was dropped: every client
  passed, and a filter of only empty blocks selected everyone in MongoDB but nobody in PHP. In PostgreSQL without
  `eventHistory` the block is still dropped.

### Unchanged
- The operator list (`toArray`) only gains the `not_used_in_event_history` key.
- Without `eventHistory` everything else works exactly as before (operators, groups, client conditions, validation,
  `filterConditions()`, PostgreSQL).
- Blocks of type `nodes` and everything inside them are never affected, also with `eventHistory`.

## 1.2.0

### Added
- Custom period for `less_than_period_date_operator` / `more_than_period_date_operator`:
  `{"period": "custom", "quantity": "<formula>"}` with units `y mo w d h m s`, `+ - * /`, brackets and functions.
- `shift(start, offset, step[, timezone])` — a boundary that moves by a step on a schedule, on the clock of an IANA
  timezone (UTC by default).
- `LessThanPeriodDateOperator::registerFunction()` to add formula functions.
- `OperatorAbstract::validateValue()`; `QueryBuilder::validateConditions()` reports an invalid custom formula.

### Unchanged
- Fixed periods behave exactly as before (more than still excludes the boundary second).

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
