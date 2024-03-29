# cron-expression

Cron expression parser to get the previous run date, next run date, and determine if the 
expression is due to run based on the next run date. This implementation supports the minute, hour,
day of the month, month, and day of the week attributes and only supports a limited amount of non-standard
characters/macros, see the [Cron Expression Format](#cron-expression-format) below.

### Cron Expression Format:

Refer to the [Cron Wikipedia Page](https://en.wikipedia.org/wiki/Cron) if desired, which is where most this 
content is from, but keep in mind this library doesn't support everything documented within there.

```php
# ┌───────────── minute (0–59)
# │ ┌───────────── hour (0–23)
# │ │ ┌───────────── day of the month (1–31)
# │ │ │ ┌───────────── month (1–12)
# │ │ │ │ ┌───────────── day of the week (0–6) (Sunday to Saturday;
# │ │ │ │ │                                   7 is also Sunday on some systems)
# │ │ │ │ │
# │ │ │ │ │
# * * * * * <command to execute>
```

**Supported Characters:**

| Character                         | Description                                                                | Example Expression   |
|-----------------------------------|----------------------------------------------------------------------------|----------------------|
| Asterisk `*`                      | Equivalent to "all" or the entire allowed range of values.                 | `* * * * *`          | 
| Comma `,`                         | Comma separated value list for scheduling multiple different times.        | `3,57 * * * SUN,MON` |
| Hyphen `-`                        | Used to specify a range of values for scheduling multiple different times. | `* * * * TUE-SAT`    |
| Slash `/`                         | Used to specify stepped values throughout the allowed range of values. | `*/5 * * * *` |       |
| Numeric (or) Non-standard Literal | Schedule using a specific number within the allowed range of values. | `5 * * * *` |

**Supported Non-standard Literals:**

| Attribute | Literal | Mapped Numeric |
|-----------|---------|----------------|
| month     | JAN     | 1              |
| month     | FEB     | 2              |
| month     | MAR     | 3              |
| month     | APR     | 4              |
| month     | MAY     | 5              |
| month     | JUN     | 6              |
| month     | JUL     | 7              |
| month     | AUG     | 8              |
| month     | SEP     | 9              |
| month     | OCT     | 10             |
| month     | NOV     | 11             |
| month     | DEC     | 12             |
| day of the week | SUN     | 0              |
| day of the week | MON     | 1              |
| day of the week | TUE     | 2              |
| day of the week | WED     | 3              |
| day of the week | THU     | 4              |
| day of the week | FRI     | 5              |
| day of the week | SAT     | 6              |
| day of the week | 7       | 0              |

**Supported Non-standard Macros:**

| Macro       | Description                                                | Mapped Expression |
|-------------|------------------------------------------------------------| ----------------- |
| `@yearly`   | Run once a year at midnight of 1 January                   | `0 0 1 1 *` |
| `@annually` | Run once a year at midnight of 1 January                   | `0 0 1 1 *` |
| `@monthly`  | Run once a month at midnight of the first day of the month | `0 0 1 1 *` |
| `@weekly`   | Run once a week at midnight on Sunday                      | `0 0 1 1 *` |
| `@daily`    | Run once a day at midnight                                 | `0 0 1 1 *` |
| `@midnight` | Run once a day at midnight                                 | `0 0 1 1 *` |
| `@hourly`   | Run once an hour at the beginning of the hour              | `0 0 1 1 *` |


## Getting Started

Install the package into your project using composer via the command below.

```
composer require nephifey/cron-expression
```

## Usage

### Basic Example

Parse cron expressions and get the previous run date, next run date, or check if the 
expression is due.

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

Use as a crontab alternative to keep expressions within the codebase or database.

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

### Additional Examples

You can pass a custom datetime to the isDue method instead of the current datetime/timezone. 
Additionally, you can enable strict mode, so then it must match the exact datetime/timezone.

```php
require_once "vendor/autoload.php";

$parser = new \CronExpression\Parser();
$expression = $parser->parse("* * * * *", new DateTime("now", new DateTimeZone("America/Chicago")));

$isDue = $expression->isDue();
echo "Is Due: " . ($isDue ? "Yes" : "No") . "\r\n"; // Yes

$isDue = $expression->isDue(new DateTime("now", new DateTimeZone("America/New_York")));
echo "Is Due: " . ($isDue ? "Yes" : "No") . "\r\n"; // Yes

$isDue = $expression->isDue(new DateTime("now", new DateTimeZone("America/New_York")), true);
echo "Is Due: " . ($isDue ? "Yes" : "No") . "\r\n"; // No
```

You can reparse cron expressions directly on the expression object based on a new datetime/timezone later on. This can be done in a mutable or immutable way depending on your need.

```php
require_once __DIR__ . "/vendor/autoload.php";

$parser = new \CronExpression\Parser();
$expression = $parser->parse("* * * * *", new DateTime("now", new DateTimeZone("America/Chicago")));

echo "{$expression->getNextRunDate()->format("Y-m-d H:i:s")}\r\n"; // current time

$expression->reparse(new DateTime("now +1 minute", new DateTimeZone("America/Chicago")));

echo "{$expression->getNextRunDate()->format("Y-m-d H:i:s")}\r\n"; // one minute ahead

$newExpression = $expression->reparseImmutable(new DateTime("now +2 minute", new DateTimeZone("America/Chicago")));

echo "{$expression->getNextRunDate()->format("Y-m-d H:i:s")}\r\n"; // one minute ahead
echo "{$newExpression->getNextRunDate()->format("Y-m-d H:i:s")}\r\n"; // two minutes ahead
```