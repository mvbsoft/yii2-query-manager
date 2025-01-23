<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class TimeRangeOperator extends OperatorAbstract
{
    // Return the slug of the operator (unique identifier)
    public static function slug(): string
    {
        return 'time_range_operator';
    }

    // Return the human-readable name of the operator
    public static function name(): string
    {
        return 'Time Range';
    }

    // Return the group of the operator (Date filter in this case)
    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    // Return a brief description of the operator's functionality
    public static function description(): string
    {
        return 'Date filtering by hour and minute in range';
    }

    // Apply the filtering conditions for PHP-based queries
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Prepare the search value by extracting the range (from-to)
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // Return false if the prepared search value is empty (invalid input)
        if(empty($preparedSearchValue)){
            return false;
        }

        // Get the value for comparison from the data array
        $value = self::getValue($column, $data);

        // Convert the value into a Unix timestamp
        $valueTimestamp = self::convertToTimestamp($value);

        // Return false if the value could not be converted into a valid timestamp
        if(is_null($valueTimestamp)){
            return false; // Invalid timestamp, return false
        }

        // Extract the from and to date values from the prepared search value
        $fromDate = Carbon::now()->startOfDay()->addSeconds($preparedSearchValue['from'])->timestamp;
        $toDate   = Carbon::now()->startOfDay()->addSeconds($preparedSearchValue['to'])->timestamp;

        if($fromDate <= $toDate){
            return $valueTimestamp >= $fromDate && $valueTimestamp <= $toDate;
        }
        else{
            $from1 = $fromDate;
            $to1 = 86400;

            $from2 = 0;
            $to2 = $toDate;

            return ($valueTimestamp >= $from1 && $valueTimestamp <= $to1) ||
                   ($valueTimestamp >= $from2 && $valueTimestamp <= $to2);
        }
    }

    // Apply the filtering conditions for MongoDB queries
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        // Prepare the search value by extracting the range (from-to)
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // Return an empty array if the prepared search value is empty (invalid input)
        if(empty($preparedSearchValue)){
            return [];
        }
        // Extract the from and to date values from the prepared search value
        $fromDate = $preparedSearchValue['from'];
        $toDate   = $preparedSearchValue['to'];

        $getBaseCondition = function(int $seconds, string $column){
            return [
                [
                    '$add' => [
                        ['$multiply' => [['$hour' => '$' . $column], 3600]],
                        ['$multiply' => [['$minute' => '$' . $column], 60]],
                        ['$second' => '$' . $column]
                    ]
                ],
                $seconds
            ];
        };

        if($fromDate <= $toDate){
            // Construct and return the MongoDB query condition for the specified time range
            return [
                '$expr' => [
                    '$and' => [
                        [
                            '$gte' => $getBaseCondition($fromDate, $column)
                        ],
                        [
                            '$lte' => $getBaseCondition($toDate, $column)
                        ]
                    ]
                ]
            ];
        }
        else{
            $from1 = $fromDate;
            $to1 = 86400;

            $from2 = 0;
            $to2 = $toDate;

            // Construct and return the MongoDB query condition for the specified time range
            return [
                '$or' => [
                    [
                        '$expr' => [
                            '$and' => [
                                [
                                    '$gte' => $getBaseCondition($from1, $column)
                                ],
                                [
                                    '$lte' => $getBaseCondition($to1, $column)
                                ]
                            ]
                        ]
                    ],
                    [
                        '$expr' => [
                            '$and' => [
                                [
                                    '$gte' => $getBaseCondition($from2, $column)
                                ],
                                [
                                    '$lte' => $getBaseCondition($to2, $column)
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }
    }

    // Apply the filtering conditions for PostgreSQL queries
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        // Prepare the search value (from and to range).
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // If no valid range, return empty array (no condition).
        if (empty($preparedSearchValue)) {
            return [];
        }

        // Extract the 'from' and 'to' range values.
        $fromDate = $preparedSearchValue['from'];
        $toDate   = $preparedSearchValue['to'];

        if($fromDate <= $toDate){
            // Return a condition for PostgreSQL, comparing seconds from the start of the day.
            return [new Expression("EXTRACT(EPOCH FROM ($column - DATE_TRUNC('day', $column))) BETWEEN $fromDate AND $toDate")];
        }
        else{
            $from1 = $fromDate;
            $to1 = 86400;

            $from2 = 0;
            $to2 = $toDate;

            // Return a condition for PostgreSQL, comparing seconds from the start of the day.
            return ["OR",
                [new Expression("EXTRACT(EPOCH FROM ($column - DATE_TRUNC('day', $column))) BETWEEN $from1 AND $to1")],
                [new Expression("EXTRACT(EPOCH FROM ($column - DATE_TRUNC('day', $column))) BETWEEN $from2 AND $to2")],
            ];
        }
    }

    // Helper function to prepare the search value (validate and convert the range)
    private static function _preparedSearchValue($searchValue): array
    {
        // Check if $searchValue is an array with 'from' and 'to' keys, and both are valid integers
        if(
            !is_array($searchValue) ||
            !array_key_exists('from', $searchValue) ||
            !array_key_exists('to', $searchValue) ||
            !is_int($searchValue['from']) ||
            !is_int($searchValue['to'])
        ){
            return []; // Return an empty array if validation fails
        }
        // Return the prepared range as Unix timestamps
        return [
            'from' => $searchValue['from'],
            'to'   => $searchValue['to']
        ];
    }

}
