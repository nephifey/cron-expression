<?php

namespace CronExpression;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * # ┌───────────── minute (0–59)
 * # │ ┌───────────── hour (0–23)
 * # │ │ ┌───────────── day of the month (1–31)
 * # │ │ │ ┌───────────── month (1–12)
 * # │ │ │ │ ┌───────────── day of the week (0–6) (Sunday to Saturday;
 * # │ │ │ │ │                                   7 is also Sunday on some systems)
 * # │ │ │ │ │
 * # │ │ │ │ │
 * # * * * * * <command to execute>
 *
 * {@link https://en.wikipedia.org/wiki/Cron}
 */
final class Parser {

    /**
     * The attributes and their expected positions.
     */
    private const array ATTRIBUTES = [
        "minute"    => 0,
        "hour"      => 1,
        "day_month" => 2,
        "month"     => 3,
        "day_week"  => 4,
    ];

    /**
     * The PHP datetime format characters for supported attributes.
     * {@link https://www.php.net/manual/en/datetime.format.php}
     */
    private const array FORMATTERS = [
        self::ATTRIBUTES["minute"]    => "i",
        self::ATTRIBUTES["hour"]      => "G",
        self::ATTRIBUTES["day_month"] => "j",
        self::ATTRIBUTES["month"]     => "n",
        self::ATTRIBUTES["day_week"]  => "w",
    ];

    /**
     * The numeric boundaries for supported attributes.
     */
    private const array BOUNDARIES = [
        self::ATTRIBUTES["minute"]    => [0, 59],
        self::ATTRIBUTES["hour"]      => [0, 23],
        self::ATTRIBUTES["day_month"] => [1, 31],
        self::ATTRIBUTES["month"]     => [1, 12],
        self::ATTRIBUTES["day_week"]  => [0, 6],
    ];

    /**
     * The non-standard literals for supported attributes.
     */
    private const array NONSTANDARD_VALUES = [
        self::ATTRIBUTES["month"]   => [
            "JAN" => 1,
            "FEB" => 2,
            "MAR" => 3,
            "APR" => 4,
            "MAY" => 5,
            "JUN" => 6,
            "JUL" => 7,
            "AUG" => 8,
            "SEP" => 9,
            "OCT" => 10,
            "NOV" => 11,
            "DEC" => 12,
        ],
        self::ATTRIBUTES["day_week"] => [
            "SUN" => 0,
            "MON" => 1,
            "TUE" => 2,
            "WED" => 3,
            "THU" => 4,
            "FRI" => 5,
            "SAT" => 6,
            "7"   => 0,
        ],
    ];

    /**
     * The non-standard supported macros.
     */
    private const array NONSTANDARD_MACROS = [
        "@yearly"   => "0 0 1 1 *",
        "@annually" => "0 0 1 1 *",
        "@monthly"  => "0 0 1 * *",
        "@weekly"   => "0 0 * * 0",
        "@daily"    => "0 0 * * *",
        "@midnight" => "0 0 * * *",
        "@hourly"   => "0 * * * *",
    ];

    /**
     * @var array Current raw attributes.
     */
    private array $rawAttributes;

    /**
     * @var array Current parsed attributes.
     */
    private array $parsedAttributes;

    /**
     * Attempts to parse the cron expression.
     * @param string $expression The cron expression to parse.
     * @param DateTime|null $date The date to use as a reference point.
     * @return Expression The parsed expression.
     * @throws Exception
     */
    public function parse(string $expression, ?DateTime $date = null): Expression {
        $expression = trim($expression);
        $expression = self::NONSTANDARD_MACROS[$expression] ?? $expression;
        $attributes = preg_split("/\s+/", trim($expression));

        if (count(self::ATTRIBUTES) !== count($attributes)) {
            throw new Exception(sprintf(
                "The expression \"%s\" has an attribute count mismatch. Received %d, but expected %d.",
                $expression, count($attributes), count(self::ATTRIBUTES)
            ));
        }

        foreach ($attributes as $attributePosition => $value) {
            foreach ($this->parseAttribute($attributePosition, $value) as $parsedValue) {
                $this->rawAttributes[$attributePosition] = $value;
                $this->parsedAttributes[$attributePosition][$parsedValue] = true;
            }
        }

        $date = $date ?? new DateTime("now", new DateTimeZone(date_default_timezone_get()));
        $date->setTime((int) $date->format(self::FORMATTERS[self::ATTRIBUTES["hour"]]), (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["minute"]]));
        [$nextRunDate, $prevRunDate] = $this->getRunDates($date);

        unset($this->rawAttributes, $this->parsedAttributes);

        return new Expression($expression, $this, $nextRunDate, $prevRunDate);
    }

    /**
     * Gets the run dates based on the date passed.
     * @param DateTime $date The date to use as a reference point.
     * @return array{
     *     nextRunDate: DateTimeImmutable,
     *     prevRunDate: DateTimeImmutable,
     * }
     * @throws Exception
     */
    private function getRunDates(DateTime $date): array {
        return [
            $this->getNextRunDate(clone $date),
            $this->getPrevRunDate(clone $date),
        ];
    }

    /**
     * Gets the next run date based on the date passed.
     * @param DateTime $date The date to use as a reference point.
     * @return DateTimeImmutable
     * @throws Exception
     */
    private function getNextRunDate(DateTime $date): DateTimeImmutable {
        return $this->getRunDate($date, true);
    }

    /**
     * Gets the previous run date based on the date passed.
     * @param DateTime $date The date to use as a reference point.
     * @return DateTimeImmutable
     * @throws Exception
     */
    private function getPrevRunDate(DateTime $date): DateTimeImmutable {
        $date->sub(new DateInterval("PT1M"));

        return $this->getRunDate($date, false);
    }

    /**
     * Gets the next or previous run date based on the date passed.
     * @param DateTime $date The date to use as a reference point.
     * @param bool $increment True = next, false = previous.
     * @return DateTimeImmutable
     * @throws Exception
     */
    private function getRunDate(DateTime $date, bool $increment): DateTimeImmutable {
        $datetimeAddOrSub = ($increment ? "add" : "sub");
        $interval = new DateInterval("PT1M");

        $dayMonthIsDefault = ("*" === $this->rawAttributes[self::ATTRIBUTES["day_month"]]);
        $dayWeekIsDefault = ("*" === $this->rawAttributes[self::ATTRIBUTES["day_week"]]);

        while (true) {
            $month = (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["month"]]);
            $dayMonth = (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["day_month"]]);
            $dayWeek = (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["day_week"]]);
            $hour = (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["hour"]]);
            $minute = (int) $date->format(self::FORMATTERS[self::ATTRIBUTES["minute"]]);

            if (isset(
                $this->parsedAttributes[self::ATTRIBUTES["month"]][$month],
                $this->parsedAttributes[self::ATTRIBUTES["hour"]][$hour],
                $this->parsedAttributes[self::ATTRIBUTES["minute"]][$minute],
            ) && (
                (!$dayWeekIsDefault && !$dayMonthIsDefault && (
                    isset($this->parsedAttributes[self::ATTRIBUTES["day_month"]][$dayMonth])
                    || isset($this->parsedAttributes[self::ATTRIBUTES["day_week"]][$dayWeek])
                )) || (isset(
                    $this->parsedAttributes[self::ATTRIBUTES["day_month"]][$dayMonth],
                    $this->parsedAttributes[self::ATTRIBUTES["day_week"]][$dayWeek],
                ))
            )) break;

            $date->$datetimeAddOrSub($interval);
        }


        return DateTimeImmutable::createFromMutable($date);
    }

    /**
     * Attempts to parse an attribute of the cron expression.
     * @param int $attributePosition The attribute position.
     * @param string $value The value of the attribute.
     * @return array
     * @throws Exception
     */
    private function parseAttribute(int $attributePosition, string $value): array {
        $value = trim($value);
        $nonstandardValue = strtoupper($value);

        if (isset(self::NONSTANDARD_VALUES[$attributePosition][$nonstandardValue]))
            return $this->parseAttribute($attributePosition, (string) self::NONSTANDARD_VALUES[$attributePosition][$nonstandardValue]);

        switch (true) {
            case str_contains($value, ","):
                $parsedAttribute = [];

                foreach (explode(",", $value) as $listValue) {
                    foreach ($this->parseAttribute($attributePosition, $listValue) as $parsedValue) {
                        $parsedAttribute[] = $parsedValue;
                    }
                }

                return $parsedAttribute;
			case str_contains($value, "/"):
				[$range, $step] = $this->assertValidStep($attributePosition, $value);
				$min = current($range);
				$max = end($range);
				$values = [$min];

				while (true) {
					$nextValue = end($values) + $step;

					if ($nextValue > $max)
						break;

					$values[] = $nextValue;
				}

				return $values;
            case str_contains($value, "-"):
                return range(...$this->assertValidRange($attributePosition, $value));
            case is_numeric($value):
                return [$this->assertValidNumeric($attributePosition, $value)];
            case "*" === $value:
                return range(...self::BOUNDARIES[$attributePosition]);
        }

        throw new Exception(sprintf(
            "The attribute \"%s\" is invalid.",
            $value
        ));
    }

    /**
     * Attribute valid range and within range validity check.
     * @param int $attributePosition The attribute position.
     * @param string $value The value of the attribute.
     * @return array The valid range in array format.
     * @throws Exception
     */
    private function assertValidRange(int $attributePosition, string $value): array {
        $parts = explode("-", $value);

        if (count(self::BOUNDARIES[$attributePosition]) !== count($parts))
            throw new Exception(sprintf(
                "The attribute \"%s\" contains a range with more than %d parts.",
                $value, count(self::BOUNDARIES[$attributePosition])
            ));

        $parts[0] = $this->assertValidNumeric($attributePosition, $parts[0]);
        $parts[1] = $this->assertValidNumeric($attributePosition, $parts[1]);

        if ($parts[0] > $parts[1])
            throw new Exception(sprintf(
                "The attribute \"%s\" cannot have a lower bound that is greater than the higher bound.",
                $value
            ));

        return $parts;
    }

    /**
     * Attribute is valid step format and within range validity check.
     * @param int $attributePosition The attribute position.
     * @param string $value The value of the attribute.
     * @return array{
	 *     range: array,
	 *     step: string,
	 * } The extracted step information.
     * @throws Exception
     */
    private function assertValidStep(int $attributePosition, string $value): array {
        $parts = explode("/", $value);

        if (count(self::BOUNDARIES[$attributePosition]) !== count($parts))
			throw new Exception(sprintf(
				"The attribute \"%s\" contains a step with more than %d parts.",
				$value, count(self::BOUNDARIES[$attributePosition])
			));

        if ($parts[0] !== "*") {
			if (is_numeric($parts[0])) {
				$parts[0] = "{$parts[0]}-" . self::BOUNDARIES[$attributePosition][1];
			}

			$range = $this->parseAttribute($attributePosition, $parts[0]);
		}

        return [
            $range ?? range(...self::BOUNDARIES[$attributePosition]),
            $this->assertValidNumeric($attributePosition, $parts[1]),
        ];
    }

    /**
     * Attribute within range validity check.
     * @param int $attributePosition The attribute position.
     * @param string $value The value of the attribute.
     * @return string The valid number.
     * @throws Exception
     */
    private function assertValidNumeric(int $attributePosition, string $value): string {
        [$min, $max] = self::BOUNDARIES[$attributePosition];
        $nonstandardValue = strtoupper($value);

        if (isset(self::NONSTANDARD_VALUES[$attributePosition][$nonstandardValue]))
            $value = (string) self::NONSTANDARD_VALUES[$attributePosition][$nonstandardValue];

        if ($min <= $value && $max >= $value)
            return $value;

        throw new Exception(sprintf(
            "The attribute \"%s\" is not within the valid boundaries of %d-%d.",
            $value, $min, $max
        ));
    }
}
