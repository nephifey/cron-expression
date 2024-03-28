# cron-expression

Cron expression parser to get the previous run date, next run date, and determine if the expression is due to run based on the next run date.

## Getting Started

Install the package into your project using composer via the command below.

```
composer require nephifey/cron-expression
```

## Usage

### Basic Example

Can be used to parse cron expressions and get the previous run date, next run date, or check if the expression is due.

```php
require_once "vendor/autoload.php";

$parser = new \CronExpression\Parser();
$expression = $parser->parse("* * * * *");

$isDue = $expression->isDue();
$nextDateFormatted = $expression->getNextRunDate()->format("Y-m-d H:i:s");
$prevDateFormatted = $expression->getPrevRunDate()->format("Y-m-d H:i:s");

echo "Is Due: " . ($isDue ? "Yes" : "No") . "\r\n";
echo "Next Date: {$nextDateFormatted}\r\n";
echo "Prev Date: {$prevDateFormatted}\r\n";

if ($expression->isDue()) {
    echo "-- Run important code here --\r\n";
}
```

### Scheduler Example

Can be used as a crontab alternative to keep expressions within the codebase or database.

**Crontab Setup:**

```
* * * * * apache php crontab.php > /dev/null 2>&1
```

**Codebase Setup:**

```php
# crontab.php
require_once "vendor/autoload.php";

$jobs = [
    "* * * * *" => [
        function () { echo "-- Run important code here every minute --\r\n"; },
        function () { echo "-- Run important code here every minute #2 --\r\n"; },
    ],
    "*/5 * * * *" => [
        function () { echo "-- Run important code here every five minutes --\r\n"; },
    ],
];

foreach ($jobs as $expression => $callables) {
    $parser = $parser ?? new \CronExpression\Parser();
    if ($parser->parse($expression)->isDue())
        $combinedCallables = array_merge(($combinedCallables ?? []), $callables);
}

foreach (($combinedCallables ?? []) as $callable) {
    call_user_func($callable);
}
```
