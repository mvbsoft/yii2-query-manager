<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use InvalidArgumentException;
use mvbsoft\queryManager\OperatorAbstract;

class LessThanPeriodDateOperator extends OperatorAbstract
{
    public const PERIOD_YEARS = 'years';
    public const PERIOD_MONTHS = 'months';
    public const PERIOD_WEEKS = 'weeks';
    public const PERIOD_DAYS = 'days';
    public const PERIOD_HOURS = 'hours';
    public const PERIOD_MINUTES = 'minutes';
    public const PERIOD_SECONDS = 'seconds';

    // The quantity is a formula (see formulaBoundary())
    public const PERIOD_CUSTOM = 'custom';

    // Seconds in a formula unit; a month is 30 days and a year is 365 days, like the fixed periods
    public const FORMULA_UNITS = ['y' => 31536000, 'mo' => 2592000, 'w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

    public const FORMULA_MAX_LENGTH = 1000;

    // Formula value types
    private const TYPE_NUMBER = 'number';
    private const TYPE_DURATION = 'duration';
    private const TYPE_DATE = 'date';

    /** @var callable[] Functions added with registerFunction(), by name */
    private static $functions = [];

    public static function getPeriods(): array
    {
        return [
            self::PERIOD_YEARS,
            self::PERIOD_MONTHS,
            self::PERIOD_WEEKS,
            self::PERIOD_DAYS,
            self::PERIOD_HOURS,
            self::PERIOD_MINUTES,
            self::PERIOD_SECONDS,
        ];
    }

    public static function slug(): string
    {
        return 'less_than_period_date_operator';
    }

    public static function name(): string
    {
        return 'Less than period';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Less than period';
    }

    /**
     * The boundary moment: now + quantity * period (a negative quantity points to the past,
     * e.g. ['period' => 'days', 'quantity' => -15] is "15 days ago"). Months are 30 days, years are 365 days.
     *
     * The quantity must be a whole number (an int or a numeric string such as "-15"); a fractional quantity or a
     * boundary outside years 0001..9999 makes the search value invalid.
     *
     * With the period "custom" the quantity is a formula: ['period' => 'custom', 'quantity' => '-10d - 2h'].
     *
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric|string]
     * @return int|null The UNIX timestamp or null if the search value is invalid.
     */
    public static function periodBoundary($searchValue): ?int
    {
        if (self::isCustom($searchValue)) {
            try {
                return self::formulaBoundary($searchValue['quantity'] ?? null);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }

        if (
            !is_array($searchValue) ||
            !in_array($searchValue['period'] ?? null, self::getPeriods(), true) ||
            !is_numeric($searchValue['quantity'] ?? null)
        ) {
            return null;
        }

        $quantity = $searchValue['quantity'] + 0;

        if (!is_finite($quantity) || floor($quantity) != $quantity) {
            return null;
        }

        // Float arithmetic: a huge quantity must not overflow the integer range
        $boundary = Carbon::now()->timestamp + (float)self::convertPeriodToSeconds($searchValue['period']) * $quantity;

        if (!self::isTimestampInRange($boundary)) {
            return null;
        }

        return (int)$boundary;
    }

    /**
     * Whether the search value uses the period "custom" (a formula instead of a fixed period).
     *
     * @param mixed $searchValue
     * @return bool
     */
    public static function isCustom($searchValue): bool
    {
        return is_array($searchValue) && ($searchValue['period'] ?? null) === self::PERIOD_CUSTOM;
    }

    /**
     * Checks a custom formula when conditions are saved. Fixed periods are not checked here: an invalid one keeps
     * matching nothing at run time, as before.
     *
     * @param mixed $searchValue
     * @return string|null The error message or null if the value is valid.
     */
    public static function validateValue($searchValue): ?string
    {
        if (!self::isCustom($searchValue)) {
            return null;
        }

        try {
            self::formulaBoundary($searchValue['quantity'] ?? null);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Adds a formula function. The handler receives the list of arguments (strings, as written in quotes) and the
     * current UNIX timestamp and returns the date (a UNIX timestamp) the function stands for. It throws
     * InvalidArgumentException with a readable message when the arguments are wrong.
     *
     * @param string $name The name used in formulas: letters, digits and "_", not starting with a digit.
     * @param callable $handler function(array $arguments, int $now): int|float
     * @throws InvalidArgumentException When the name can not be used.
     */
    public static function registerFunction(string $name, callable $handler): void
    {
        if (!preg_match('/^[a-zA-Z_]\w*$/', $name) || $name === 'shift') {
            throw new InvalidArgumentException("\"$name\" can not be a function name.");
        }

        self::$functions[$name] = $handler;
    }

    /**
     * Formula functions by name: the built-in ones and those added with registerFunction().
     *
     * @return callable[]
     */
    public static function functions(): array
    {
        return array_merge(['shift' => [self::class, 'shift']], self::$functions);
    }

    /**
     * The boundary moment of a formula.
     *
     * A formula is built from durations (`10d`, `2h`, units from FORMULA_UNITS), plain numbers, `+ - * /`, brackets and
     * functions with arguments in quotes (`shift("2026-10-15", "-10d", "-3d")`). A function stands for a date, so the
     * formula is either a duration added to now (`-10d - 2h` is 10 days and 2 hours ago) or a date (`shift(...) + 1h`).
     *
     * @param mixed $formula
     * @return int The UNIX timestamp.
     * @throws InvalidArgumentException With a readable message when the formula is invalid.
     */
    public static function formulaBoundary($formula): int
    {
        if (!is_string($formula)) {
            throw new InvalidArgumentException('The formula must be a text.');
        }

        if (!preg_match('/\S/', $formula)) {
            throw new InvalidArgumentException('The formula is empty.');
        }

        if (strlen($formula) > self::FORMULA_MAX_LENGTH) {
            throw new InvalidArgumentException('The formula is longer than ' . self::FORMULA_MAX_LENGTH . ' characters.');
        }

        $now = Carbon::now()->timestamp;

        list($type, $value) = self::evaluate($formula, $now);

        if ($type === self::TYPE_NUMBER) {
            throw new InvalidArgumentException('The formula needs a unit, e.g. "-10d".');
        }

        $boundary = $type === self::TYPE_DURATION ? $now + $value : $value;

        if (!is_finite($boundary) || !self::isTimestampInRange($boundary)) {
            throw new InvalidArgumentException('The formula gives a date outside years 0001-9999.');
        }

        return (int)floor($boundary);
    }

    /**
     * shift(start, offset, step[, timezone]): start + offset + step * N, where N is the number of whole step units
     * passed since start (0 before start). The unit of the step sets how often the boundary moves: "-3d" moves it
     * 3 days back once a day, "+2h" 2 hours forward once an hour, "0d" never.
     *
     * Everything is counted on the clock of the timezone (UTC when it is not given): "-3d" with the start at 16:00
     * moves the boundary at 16:00 of that timezone in summer and in winter. Only the result is converted to UTC.
     *
     * @param array $arguments [start, offset, step] or [start, offset, step, timezone]
     * @param int $now
     * @return int|float The UNIX timestamp.
     */
    public static function shift(array $arguments, int $now)
    {
        if (count($arguments) !== 3 && count($arguments) !== 4) {
            throw new InvalidArgumentException('shift() needs 3 or 4 arguments: start, offset, step and an optional timezone.');
        }

        list($start, $offset, $step) = $arguments;

        $timezone = self::parseTimezone($arguments[3] ?? 'UTC');

        // A timestamp start is a moment: when its local time happens twice, it keeps its own one
        $moment = trim($start, " \t\n\r\x0B\f");
        $momentOffset = preg_match('/^\d{5,}$/', $moment) ? self::timezoneOffset($timezone, (float)$moment) : null;

        // Local clock time written as a UNIX timestamp (16:00 in Kyiv is 16:00 UTC here)
        $start = self::parseStart($start, $timezone);

        list($type, $offset) = self::evaluate($offset, $now);

        if ($type !== self::TYPE_DURATION) {
            throw new InvalidArgumentException('The offset of shift() must be a duration, e.g. "-10d".');
        }

        if (!preg_match('/^\s*([+-]?)\s*(\d+)(mo|y|w|d|h|m|s)\s*$/', $step, $match)) {
            throw new InvalidArgumentException('The step of shift() must be a whole number with one unit, e.g. "-3d".');
        }

        $unit = self::FORMULA_UNITS[$match[3]];
        $step = ($match[1] === '-' ? -1 : 1) * (float)$match[2] * $unit;
        $steps = self::stepsPassed($timezone, $start, $unit, $now);

        return self::fromLocalTime($timezone, $start + $offset + $step * $steps, $momentOffset);
    }

    /**
     * N of shift(): the number of whole step units passed since start on the clock of the timezone. A step counts once
     * the clock reaches its local time for the first time, so N never goes back when the clocks do.
     *
     * @param \DateTimeZone $timezone
     * @param int|float $start Local clock time of the start (see shift()).
     * @param int $unit Seconds in the step unit.
     * @param int $now
     * @return int|float
     */
    private static function stepsPassed(\DateTimeZone $timezone, $start, int $unit, int $now)
    {
        // The local clock reached by now lies between these two readings
        $offsets = [self::timezoneOffset($timezone, $now - 86400), self::timezoneOffset($timezone, $now)];
        $low = max(0, floor(($now + min($offsets) - $start) / $unit));
        $high = max(0, floor(($now + max($offsets) - $start) / $unit));

        while ($low < $high) {
            $middle = $low + ceil(($high - $low) / 2);

            if (self::fromLocalTime($timezone, $start + $middle * $unit) <= $now) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }

        return $low;
    }

    /**
     * The timezone of shift(): an IANA name such as "Europe/Kyiv" (any letter case) or "UTC". Fixed offsets ("+03:00")
     * and abbreviations ("EST") are not accepted: they do not follow daylight saving time.
     *
     * @param string $timezone
     * @return \DateTimeZone
     */
    private static function parseTimezone(string $timezone): \DateTimeZone
    {
        static $names = null;

        if ($names === null) {
            $names = [];

            foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC) as $name) {
                // Etc/GMT+3 and the like are fixed offsets
                if ($name === 'UTC' || (strpos($name, '/') !== false && !preg_match('/^Etc\/GMT[+-]/i', $name))) {
                    $names[strtolower($name)] = $name;
                }
            }
        }

        $name = trim($timezone, " \t\n\r\x0B\f");

        if (!isset($names[strtolower($name)])) {
            throw new InvalidArgumentException("Unknown timezone \"$name\". Use a name like \"Europe/Kyiv\" or \"UTC\".");
        }

        return new \DateTimeZone($names[strtolower($name)]);
    }

    /**
     * The UTC offset of the timezone at a moment, in seconds.
     */
    private static function timezoneOffset(\DateTimeZone $timezone, $timestamp): int
    {
        return $timezone->getOffset(new \DateTime('@' . sprintf('%.0f', floor($timestamp))));
    }

    /**
     * Converts local clock time (as a UNIX timestamp, see shift()) to the moment the clock first shows it: a time that
     * happens twice when the clocks go back is the first one (or the one with $preferOffset), a time skipped when the
     * clocks go forward is the moment they jump over it (03:30 on a night when 03:00 becomes 04:00 is 04:00). So a later
     * local time is never an earlier moment. The browser part of the condition builder does the same.
     *
     * @param \DateTimeZone $timezone
     * @param int|float $local
     * @param int|null $preferOffset
     * @return int|float The UNIX timestamp.
     */
    private static function fromLocalTime(\DateTimeZone $timezone, $local, ?int $preferOffset = null)
    {
        if (!is_finite($local) || $local < self::TIMESTAMP_MIN - 172800 || $local > self::TIMESTAMP_MAX + 172800) {
            // Far outside years 0001-9999 in any timezone: formulaBoundary() rejects it
            return $local;
        }

        // The offsets a day before and a day after cover a clock change near this time
        $before = self::timezoneOffset($timezone, $local - 86400);
        $after = self::timezoneOffset($timezone, $local + 86400);

        if ($before === $after) {
            return $local - $before;
        }

        // The moments the clock shows this time, by offset: two for a repeated time, none for a skipped one
        $moments = [];

        foreach ([$before, $after] as $offset) {
            if (self::timezoneOffset($timezone, $local - $offset) === $offset) {
                $moments[$offset] = $local - $offset;
            }
        }

        if (!empty($moments)) {
            return $preferOffset !== null && isset($moments[$preferOffset]) ? $moments[$preferOffset] : min($moments);
        }

        // Skipped: the clocks jump between these two moments, find the second they do
        $low = $local - $after;
        $high = $local - $before;

        while ($high - $low > 1) {
            $middle = floor(($low + $high) / 2);

            if (self::timezoneOffset($timezone, $middle) === $after) {
                $high = $middle;
            } else {
                $low = $middle;
            }
        }

        return $high;
    }

    /**
     * The start of shift(): "YYYY", "YYYY-MM", "YYYY-MM-DD", "YYYY-MM-DD HH", "YYYY-MM-DD HH:MM", "YYYY-MM-DD HH:MM:SS"
     * (time of the timezone, "T" may separate the date and the time; missing parts are the first month / day / second)
     * or a UNIX timestamp of 5+ digits. Relative dates ("today") are not accepted: the start must not move.
     *
     * @param string $start
     * @param \DateTimeZone $timezone
     * @return int Local clock time of the start as a UNIX timestamp (see shift()).
     */
    private static function parseStart(string $start, \DateTimeZone $timezone): int
    {
        $start = trim($start, " \t\n\r\x0B\f");

        if (preg_match('/^\d{5,}$/', $start)) {
            if (!self::isTimestampInRange((float)$start)) {
                throw new InvalidArgumentException("The start \"$start\" is outside years 0001-9999.");
            }

            return (int)$start + self::timezoneOffset($timezone, (int)$start);
        }

        if (!preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2})(?:[ T](\d{2})(?::(\d{2})(?::(\d{2}))?)?)?)?)?$/', $start, $match)) {
            throw new InvalidArgumentException("The start \"$start\" is not a date like \"2026-10-15\" or a timestamp.");
        }

        $year = (int)$match[1];
        $month = (int)($match[2] ?? 1);
        $day = (int)($match[3] ?? 1);
        $hour = (int)($match[4] ?? 0);
        $minute = (int)($match[5] ?? 0);
        $second = (int)($match[6] ?? 0);

        if ($year < 1 || !checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            throw new InvalidArgumentException("The start \"$start\" is not a date that exists.");
        }

        return Carbon::create($year, $month, $day, $hour, $minute, $second, 'UTC')->timestamp;
    }

    /**
     * Evaluates a formula (or a function argument) to a typed value.
     *
     * @param string $formula
     * @param int $now
     * @return array [type, value]: a number, a duration in seconds or a date as a UNIX timestamp.
     */
    private static function evaluate(string $formula, int $now): array
    {
        $tokens = self::tokens($formula);
        $position = 0;

        $result = self::parseSum($tokens, $position, $now);

        if ($position < count($tokens)) {
            throw new InvalidArgumentException('Unexpected "' . $tokens[$position][1] . '" in "' . $formula . '".');
        }

        return $result;
    }

    /**
     * Splits a formula into tokens: [kind, text] where kind is "number", "duration", "name", "string" or the symbol itself.
     *
     * @param string $formula
     * @return array
     */
    private static function tokens(string $formula): array
    {
        $pattern = '/\s*(?:(\d+(?:\.\d+)?)(mo|y|w|d|h|m|s)(?![a-zA-Z])|(\d+(?:\.\d+)?)(?![a-zA-Z.])|([a-zA-Z_]\w*)|"([^"]*)"|\'([^\']*)\'|([-+*\/(),]))/A';

        $tokens = [];
        $offset = 0;
        $length = strlen($formula);

        while ($offset < $length) {
            if (preg_match('/\s*\z/A', $formula, $match, 0, $offset)) {
                break;
            }

            if (!preg_match($pattern, $formula, $match, 0, $offset)) {
                throw new InvalidArgumentException('Unexpected "' . trim(mb_strcut($formula, $offset, 10, 'UTF-8')) . '" in "' . $formula . '". Units: ' . implode(', ', array_keys(self::FORMULA_UNITS)) . '.');
            }

            $offset += strlen($match[0]);

            if (($match[1] ?? '') !== '') {
                $tokens[] = ['duration', $match[1], $match[2]];
            } elseif (($match[3] ?? '') !== '') {
                $tokens[] = ['number', $match[3]];
            } elseif (($match[4] ?? '') !== '') {
                $tokens[] = ['name', $match[4]];
            } elseif (isset($match[7]) && $match[7] !== '') {
                $tokens[] = [$match[7], $match[7]];
            } else {
                $tokens[] = ['string', isset($match[6]) ? $match[6] : $match[5]];
            }
        }

        if (empty($tokens)) {
            throw new InvalidArgumentException("The formula \"$formula\" is empty.");
        }

        return $tokens;
    }

    /** sum := product (("+" | "-") product)* */
    private static function parseSum(array $tokens, int &$position, int $now): array
    {
        $left = self::parseProduct($tokens, $position, $now);

        while (in_array($tokens[$position][0] ?? null, ['+', '-'], true)) {
            $operator = $tokens[$position++][0];
            $left = self::calculate($left, $operator, self::parseProduct($tokens, $position, $now));
        }

        return $left;
    }

    /** product := unary (("*" | "/") unary)* */
    private static function parseProduct(array $tokens, int &$position, int $now): array
    {
        $left = self::parseUnary($tokens, $position, $now);

        while (in_array($tokens[$position][0] ?? null, ['*', '/'], true)) {
            $operator = $tokens[$position++][0];
            $left = self::calculate($left, $operator, self::parseUnary($tokens, $position, $now));
        }

        return $left;
    }

    /** unary := ("+" | "-") unary | value */
    private static function parseUnary(array $tokens, int &$position, int $now): array
    {
        $kind = $tokens[$position][0] ?? null;

        if ($kind === '+' || $kind === '-') {
            $position++;
            list($type, $value) = self::parseUnary($tokens, $position, $now);

            if ($type === self::TYPE_DATE) {
                throw new InvalidArgumentException('A date can not have a sign.');
            }

            return [$type, $kind === '-' ? -$value : $value];
        }

        return self::parseValue($tokens, $position, $now);
    }

    /** value := number | duration | "(" sum ")" | name "(" string ("," string)* ")" */
    private static function parseValue(array $tokens, int &$position, int $now): array
    {
        $token = $tokens[$position++] ?? null;

        switch ($token[0] ?? null) {
            case 'number':
                return [self::TYPE_NUMBER, (float)$token[1]];
            case 'duration':
                return [self::TYPE_DURATION, (float)$token[1] * self::FORMULA_UNITS[$token[2]]];
            case '(':
                $value = self::parseSum($tokens, $position, $now);
                self::expect($tokens, $position, ')');
                return $value;
            case 'name':
                return [self::TYPE_DATE, self::callFunction($token[1], $tokens, $position, $now)];
            case null:
                throw new InvalidArgumentException('The formula ends too early.');
            default:
                throw new InvalidArgumentException('Unexpected "' . $token[1] . '".');
        }
    }

    private static function callFunction(string $name, array $tokens, int &$position, int $now)
    {
        $functions = self::functions();

        if (!isset($functions[$name])) {
            throw new InvalidArgumentException("Unknown function \"$name\". Available: " . implode(', ', array_keys($functions)) . '.');
        }

        self::expect($tokens, $position, '(');

        $arguments = [];

        while (($tokens[$position][0] ?? null) !== ')') {
            if (!empty($arguments)) {
                self::expect($tokens, $position, ',');
            }

            if (($tokens[$position][0] ?? null) !== 'string') {
                throw new InvalidArgumentException("The arguments of $name() must be in quotes, e.g. $name(\"2026-10-15\", \"-10d\", \"-3d\").");
            }

            $arguments[] = $tokens[$position++][1];
        }

        $position++;

        try {
            $date = call_user_func($functions[$name], $arguments, $now);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // A broken function makes the formula invalid instead of breaking the query
            throw new InvalidArgumentException("$name() failed: " . $e->getMessage());
        }

        if (!is_int($date) && !is_float($date)) {
            throw new InvalidArgumentException("$name() did not return a date.");
        }

        return $date;
    }

    private static function expect(array $tokens, int &$position, string $kind): void
    {
        if (($tokens[$position][0] ?? null) !== $kind) {
            throw new InvalidArgumentException('Expected "' . $kind . '"' . (isset($tokens[$position]) ? ' instead of "' . $tokens[$position][1] . '"' : ' at the end') . '.');
        }

        $position++;
    }

    /**
     * Applies an arithmetic operator to two typed values. Allowed: number with number, duration ± duration,
     * date ± duration, duration + date, date - date (a duration), duration * number, number * duration,
     * duration / number, duration / duration (a number).
     */
    private static function calculate(array $left, string $operator, array $right): array
    {
        list($leftType, $a) = $left;
        list($rightType, $b) = $right;
        $types = "$leftType $operator $rightType";

        $results = [
            'number + number' => self::TYPE_NUMBER, 'number - number' => self::TYPE_NUMBER,
            'number * number' => self::TYPE_NUMBER, 'number / number' => self::TYPE_NUMBER,
            'duration + duration' => self::TYPE_DURATION, 'duration - duration' => self::TYPE_DURATION,
            'date + duration' => self::TYPE_DATE, 'date - duration' => self::TYPE_DATE, 'duration + date' => self::TYPE_DATE,
            'date - date' => self::TYPE_DURATION,
            'duration * number' => self::TYPE_DURATION, 'number * duration' => self::TYPE_DURATION,
            'duration / number' => self::TYPE_DURATION, 'duration / duration' => self::TYPE_NUMBER,
        ];

        if (!isset($results[$types])) {
            throw new InvalidArgumentException("Can not calculate $types.");
        }

        if ($operator === '/' && $b == 0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        switch ($operator) {
            case '+': $value = $a + $b; break;
            case '-': $value = $a - $b; break;
            case '*': $value = $a * $b; break;
            default:  $value = $a / $b;
        }

        return [$results[$types], $value];
    }

    /**
     * Checks if the value is a date before now + quantity * period.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $boundary = self::periodBoundary($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($boundary) || is_null($valueTimestamp)) {
            return false;
        }

        return $valueTimestamp < $boundary;
    }

    /**
     * Generate a condition array for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $boundary = self::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$lt' => self::convertToMongoDate($boundary)]];
    }

    /**
     * Generate a condition array for Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $boundary = self::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::postgresqlMatchNothing();
        }

        return ['<', $column, self::postgresqlTimestamp($boundary)];
    }

    public static function convertPeriodToSeconds(string $period, int $quantity = 1)
    {
        switch (strtolower($period)) {
            case self::PERIOD_YEARS:
                $secondsInPeriod = 31536000;
                break;
            case self::PERIOD_MONTHS:
                $secondsInPeriod = 2592000;
                break;
            case self::PERIOD_WEEKS:
                $secondsInPeriod = 604800;
                break;
            case self::PERIOD_DAYS:
                $secondsInPeriod = 86400;
                break;
            case self::PERIOD_HOURS:
                $secondsInPeriod = 3600;
                break;
            case self::PERIOD_MINUTES:
                $secondsInPeriod = 60;
                break;
            default:
                $secondsInPeriod = 1;
        }

        return $secondsInPeriod * $quantity;
    }

}
