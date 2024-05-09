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

QueryManager provides methods for generating conditions for different condition types (PHP, MongoDB, PostgreSQL). 
You can use these methods to construct complex conditions easily.

```php
// Generate a condition for a given query element
$queryElements = [
    // Your query elements here...
];
$conditionType = 'postgresql'; // or 'mongodb' or 'php'
$data = []; // Your data array here...

$condition = $queryBuilder->generateCondition($queryElements, $conditionType, $data);
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
        "type" => QueryBuilder::CONDITION_ELEMENT_TYPE_INDIVIDUAL,
        "operator" => BetweenDateOperator::slug(),
        "value" => ["07.05.2024", "09.05.2024"]
    ],
    [
        "id" => 2,
        "condition" => "AND",
        "column" => BetweenIntOperator::slug(),
        "type" => QueryBuilder::CONDITION_ELEMENT_TYPE_INDIVIDUAL,
        "operator" => BetweenIntOperator::slug(),
        "value" => ["1", "3"]
    ],
    // Other conditions...
];
```

### Available Operators

Here's a list of operators available in this package along with their descriptions:

1. **Between Date Operator**
 - Slug: between_date_operator 
 - Group: date 
 - Description: Matches values within a specified date range.
2. **Between Integer Operator**
 - Slug: between_int_operator 
 - Group: number 
 - Description: Checks if an integer value falls within a specified range.
3. **Contains String Operator**
 - Slug: contains_string_operator 
 - Group: string 
 - Description: Matches strings containing a specific substring.
4. **Current Date Operator**
 - Slug: current_date_operator
 - Group: date
 - Description: Matches records with the current date.
5. **End With String Operator**
 - Slug: end_with_string_operator
 - Group: string
 - Description: Matches strings ending with a specific substring.
6. **Equal Date Operator**
 - Slug: equal_date_operator
 - Group: date
 - Description: Matches records with a specific date.
7. **Equal Integer Operator**
 - Slug: equal_int_operator
 - Group: number
 - Description: Matches records with a specific integer value.
8. **Equal String Operator**
 - Slug: equal_string_operator
 - Group: string
 - Description: Matches records with a specific string value.
9. **In Range Integer Operator**
 - Slug: in_range_int_operator
 - Group: number
 - Description: Matches records within a specified integer range.
10. **In Range String Operator**
 - Slug: in_range_string_operator
 - Group: string
 - Description: Matches records within a specified string range.
11. **Is False Boolean Operator**
 - Slug: is_false_boolean_operator
 - Group: boolean
 - Description: Matches records where the boolean value is false.
12. **Is Not Null Operator**
 - Slug: is_not_null_operator
 - Group: default
 - Description: Matches records where the value is not null.
13. **Is Null Operator**
 - Slug: is_null_operator
 - Group: default
 - Description: Matches records where the value is null.
14. **Is True Boolean Operator**
 - Slug: is_true_boolean_operator
 - Group: boolean
 - Description: Matches records where the boolean value is true.
15. **Less Than Date Operator**
 - Slug: less_than_date_operator
 - Group: date
 - Description: Matches records with a date less than a specified value.
16. **Less Than Integer Operator**
 - Slug: less_than_int_operator
 - Group: number
 - Description: Matches records with an integer value less than a specified value.
17. **More Than Date Operator**
 - Slug: more_than_date_operator
 - Group: date
 - Description: Matches records with a date greater than a specified value.
18. **More Than Integer Operator**
 - Slug: more_than_int_operator
 - Group: number
 - Description: Matches records with an integer value greater than a specified value.
19. **Not Contains String Operator**
 - Slug: not_contains_string_operator
 - Group: string
 - Description: Matches strings not containing a specific substring.
20. **Not Equal Date Operator**
 - Slug: not_equal_date_operator
 - Group: date
 - Description: Matches records with a date not equal to a specified value.
21. **Not Equal Integer Operator**
 - Slug: not_equal_int_operator
 - Group: number
 - Description: Matches records with an integer value not equal to a specified value.
22. **Not Equal String Operator**
 - Slug: not_equal_string_operator
 - Group: string
 - Description: Matches records with a string value not equal to a specified value.
23. **Start With String Operator**
 - Slug: start_with_string_operator
 - Group: string
 - Description: Matches strings starting with a specific substring.

### License

This package is open-source software licensed under the MIT license.