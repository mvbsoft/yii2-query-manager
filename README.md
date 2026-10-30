Query Manager
=============
QueryManager is a PHP package for constructing complex queries easily. It provides methods for generating conditions and queries based on query elements.

Installation
------------

Requirements PHP version ^7.2 || ^8.0

You can install the QueryManager package via [Сomposer](https://getcomposer.org/download/). Run the following command in your terminal.

Either run

```
php composer.phar require --prefer-dist mvbsoft/yii2-query-manager "dev-main"
```

or add

```
"mvbsoft/yii2-query-manager": "dev-main"
```

to the require section of your `composer.json` file.


Usage
-----

### Getting Started

1. **Import the QueryBuilder class:**  First, you need to import the QueryBuilder class from the package:
2. **Create a QueryBuilder instance:** Instantiate the QueryBuilder class to start building queries:

```php
use mvbsoft\queryManager\QueryBuilder;

$queryBuilder = new QueryBuilder();
```
### Generating Conditions

QueryManager provides methods for generating conditions for different conditions types (PHP, MongoDB, PostgreSQL). 
You can use these methods to construct complex conditions easily.

```php
// Generate a conditions for a given query element
$queryElements = [
    // Your query elements here...
];
$conditionsType = 'postgresql'; // or 'mongodb' or 'php'
$data = []; // Your data array here...

$conditions = $queryBuilder->generateConditions($queryElements, $conditionsType, $data);
```

### Filtering Query Elements
This method is designed to delete unnecessary keys from an array of query elements. It ensures that only relevant information is retained for further processing.
```php
// Deleted no needed keys in array of query elements
$result = $queryBuilder->filterConditions($queryElements, $data);
```

### Executing Queries

QueryManager allows you to execute queries based on the provided query elements and data.

```php
// Execute a PHP query
$result = $queryBuilder->phpQuery($queryElements, $data);

// Execute a MongoDB query
$mongodbQuery = $queryBuilder->mongodbQuery($queryElements);
$result = $mongodbQuery->all();

// Execute a PostgreSQL query
$postgresqlQuery = $queryBuilder->postgresqlQuery($queryElements);
$result = $postgresqlQuery->all();
```

### Custom Operators

QueryManager supports custom operators. You can define your own operators by creating classes in the operators folder 
and extending the OperatorAbstract class.

### Validation

QueryManager provides methods for validating query conditions. You can use these methods to validate query elements 
before executing queries.

```php
// Validate query conditions
$errors = $queryBuilder->validateConditions($queryElements);
```

### Example Usage of Conditions

Here's an example array of conditions that can be used to generate queries. This array includes various operators along with their corresponding values:

```php
$conditions = [
    [
        "id" => 1,
        "condition" => "AND",
        "column" => BetweenDateOperator::slug(),
        "type" => QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL,
        "operator" => BetweenDateOperator::slug(),
        "value" => ["07.05.2024", "09.05.2024"]
    ],
    [
        "id" => 2,
        "condition" => "AND",
        "column" => BetweenIntOperator::slug(),
        "type" => QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL,
        "operator" => BetweenIntOperator::slug(),
        "value" => ["1", "3"]
    ],
    [
        "id" => 2,
        "condition" => "AND",
        "type" => QueryBuilder::CONDITIONS_ELEMENT_TYPE_GROUP,
        "name" => "Group 1"
        "elements" => [
                [
                "id" => 1,
                "condition" => "AND",
                "column" => BetweenDateOperator::slug(),
                "type" => QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL,
                "operator" => BetweenDateOperator::slug(),
                "value" => ["07.05.2024", "09.05.2024"]
            ],
            [
                "id" => 2,
                "condition" => "OR",
                "column" => BetweenIntOperator::slug(),
                "type" => QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL,
                "operator" => BetweenIntOperator::slug(),
                "value" => ["1", "3"]
            ]
        ]
    ],
    // Other conditions...
];
```

### Available Operators

| Group | Slug | Value |
|---|---|---|
| number | `equal_int_operator`, `not_equal_int_operator`, `less_than_int_operator`, `more_than_int_operator` | number or numeric string |
| number | `between_int_operator` | `{"from": x, "to": y}` or `[x, y]`, bounds included |
| number | `in_range_int_operator`, `not_in_range_int_operator` | list of numbers (non-numeric items are ignored) |
| string | `equal_string_operator`, `not_equal_string_operator` | text, case-sensitive |
| string | `contains_string_operator`, `not_contains_string_operator`, `start_with_string_operator`, `end_with_string_operator` | text, case-insensitive (Unicode), `%` and `_` are literal |
| string | `in_range_string_operator`, `not_in_range_string_operator` | list of texts |
| date | `equal_date_operator`, `not_equal_date_operator` | date — compared by the whole calendar day |
| date | `less_than_date_operator`, `more_than_date_operator` | date |
| date | `between_date_operator` | `{"from": date, "to": date}` or `[date, date]`, bounds included |
| date | `current_day_operator`, `current_month_operator`, `current_year_operator` | not used |
| date | `less_than_period_date_operator`, `more_than_period_date_operator` | `{"period": "days", "quantity": -15}` — compares with now + quantity × period |
| date | `days_of_week_operator` | list of weekdays `0` (Sunday) … `6` (Saturday), as ints or strings `"0"`…`"6"` |
| date | `time_range_operator` | `{"from": int, "to": int}` — seconds since midnight (`0…86400`); `from > to` wraps over midnight |
| boolean | `is_true_boolean_operator`, `is_false_boolean_operator` | not used |
| default | `is_null_operator`, `is_not_null_operator` | not used — null, a missing value and `''` are "null" |

A date is a UNIX timestamp or any string `strtotime()` understands (`2026-09-16`, `2026-09-16 10:00:00`, `today -15 days`),
between `0001-01-01 00:00:00 UTC` and `9999-12-31 23:59:59 UTC` (timestamps -62135596800…253402300799).
Periods: `seconds`, `minutes`, `hours`, `days`, `weeks`, `months` (30 days), `years` (365 days); the quantity is a whole number.

#### Custom period (formula)

With `{"period": "custom", "quantity": "<formula>"}` the boundary is calculated by a formula. The boundary is
excluded by `less_than_period_date_operator` and **included** by `more_than_period_date_operator`, so a range written
as two conditions (more than + less than) covers whole days with no gap between neighbouring ranges.

| Element | Example | Meaning |
|---|---|---|
| Units | `y` `mo` `w` `d` `h` `m` `s` | year = 365 days, month = 30 days |
| Arithmetic | `+ - * /`, `( )` | `-(1w + 2d) / 2` is 4.5 days ago |
| Duration | `-10d - 2h` | now minus 10 days and 2 hours |
| Function | `shift("2026-10-15", "-10d", "-3d", "Europe/Kyiv")` | a date; `shift(...) + 1h` is allowed |

A formula of durations is added to now; a formula with a function is a date. A date can be moved by a duration and two
dates can be subtracted (a duration); `date * number`, `date + date` and a plain number without a unit are errors.

`shift(start, offset, step[, timezone])` = start + offset + step × N, where N is the number of whole step units passed
since start (0 before start). The step is one signed unit: `"-3d"` moves the boundary 3 days back once a day, `"+2h"`
2 hours forward once an hour, `"-1w"` a week back once a week, `"0d"` never. Start: `YYYY`, `YYYY-MM`, `YYYY-MM-DD`,
`YYYY-MM-DD HH`, `YYYY-MM-DD HH:MM`, `YYYY-MM-DD HH:MM:SS` (missing parts are the minimum) or a UNIX timestamp
of 5+ digits (`"1792022400"`; `"20261015"` is a timestamp too, write `"2026-10-15"`). Function arguments are always in
quotes.

The timezone is an IANA name (`"Europe/Kyiv"`, `"America/Toronto"`, any letter case) or `"UTC"`, the default. Fixed
offsets (`"+03:00"`) and abbreviations (`"EST"`) are errors: they do not follow daylight saving time. The start,
offset, step and N are counted on the clock of the timezone and only the result is converted to UTC, so a boundary
with the start at 16:00 and the step `"-3d"` moves at 16:00 of that timezone in summer and in winter (a local day of
the clock change is 23 or 25 hours). An hourly step follows the clock as well: on the night of the change one step is
0 or 2 real hours. A step counts once the clock first shows its time, so the boundary never moves back while the
clocks repeat an hour. A local time is the moment the clock first shows it: a time that happens twice when the clocks
go back is the first one, a time skipped when they go forward is the moment they jump (03:30 in Kyiv on 2026-03-29,
when 03:00 becomes 04:00, is 04:00). A timestamp start is a moment, also inside a repeated hour; the timezone then
only sets when the steps happen. `Etc/GMT+3` and the like are fixed offsets and are rejected too.

```php
// Today (Oct 15) select Oct 5-7, tomorrow Oct 2-4, ... (days start at midnight in Kyiv)
['operator' => 'more_than_period_date_operator', 'value' => ['period' => 'custom', 'quantity' => 'shift("2026-10-15", "-10d", "-3d", "Europe/Kyiv")']],
['operator' => 'less_than_period_date_operator', 'value' => ['period' => 'custom', 'quantity' => 'shift("2026-10-15", "-7d", "-3d", "Europe/Kyiv")']],
```

More functions can be added (a name of letters, digits and `_`, `shift` is reserved); the handler gets the arguments
(strings) and the current timestamp and returns a date. An exception of the handler makes the formula invalid:

```php
LessThanPeriodDateOperator::registerFunction('daysAgo', function (array $arguments, int $now) {
    return $now - 86400 * (int)($arguments[0] ?? 0);
});
```

`QueryBuilder::validateConditions()` reports an invalid formula under the `value` key of the condition
(`OperatorAbstract::validateValue()`); at run time an invalid formula matches nothing.

### Behaviour shared by PHP, MongoDB and PostgreSQL

The three condition types select exactly the same rows:

- **Empty values.** A null / missing value never matches number and date operators. String operators read it as `''`
  (so it matches `not_equal_string_operator` / `not_contains_string_operator` with a non-empty text, and
  `equal_string_operator` with `''`).
- **Invalid search values** (wrong type, broken range or list, infinite numbers, dates outside the range above,
  fractional period quantity) and **unknown operators** match nothing. They are never dropped from the query, so
  `A AND <invalid>` selects nothing in every backend, and they never cause an SQL or MongoDB error.
- **Numbers** are compared exactly: `5.7` is not equal to `5`.
- **Dates** are compared with whole-second precision, like PHP reads them: a stored `23:59:59.600` belongs to the
  second `23:59:59`.
- **Calendar days, weekdays and time of day** are evaluated in the PHP default timezone
  (`date_default_timezone_get()`); PostgreSQL uses its session timezone — keep both the same.

Requirements on the stored data (otherwise PHP, which converts types, and MongoDB / PostgreSQL, which compare stored
types strictly, can disagree):

- every value is stored with the type of its field: numbers as numbers, texts as strings, dates as dates
  (`MongoDB\BSON\UTCDateTime` in MongoDB), booleans as booleans;
- stored dates are within years 0001…9999 both in UTC and in the PHP default timezone (PHP can not read a
  5-digit year), and PHP receives them in an unambiguous form (`Y-m-d H:i:s` is ambiguous for one hour a year when
  the PHP default timezone has daylight saving time — use UTC or an offset);
- a MongoDB document keeps the fields of one table in a sub-document (`{"clients": {"num": 5, ...}}`);
- PostgreSQL case-insensitive operators use `ILIKE`, which follows the database `LC_CTYPE`: with the `C` locale
  non-Latin letters (Cyrillic) are compared case-sensitively.

Known differences:

- An empty list of conditions (or only empty groups): `phpQuery()` returns false, while `mongodbQuery()` /
  `postgresqlQuery()` have no condition and select every row — check for an empty list before querying.
- A search text that is not valid UTF-8 (it can not come from JSON): PHP compares it byte-wise, MongoDB and
  PostgreSQL treat it as equal to / contained in no stored text (so `not_equal` / `not_contains` select every row).
  They can differ when the bytes also occur inside valid texts (e.g. a lone `"\xd0"`).
- Array values (multi-select fields) are not supported by string operators; in PHP `not_in_range_string_operator`
  treats an array value as "not in the list".

### License

This package is open-source software licensed under the MIT license.