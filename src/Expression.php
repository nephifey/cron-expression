<?php

namespace CronExpression;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

final class Expression {

    /**
     * @var string The original expression that was parsed.
     */
    private string $expression;

    /**
     * @var Parser The reference to the parser object.
     */
    private Parser $parser;

    /**
     * @var DateTimeImmutable The next run date.
     */
    private DateTimeImmutable $nextRunDate;

    /**
     * @var DateTimeImmutable The previous run date.
     */
    private DateTimeImmutable $prevRunDate;

    /**
     * @param string $expression The original expression that was parsed.
     * @param Parser $parser The reference to the parser object.
     * @param DateTimeImmutable $nextRunDate The next run date.
     * @param DateTimeImmutable $prevRunDate The previous run date.
     */
    public function __construct(string $expression, Parser $parser, DateTimeImmutable $nextRunDate, DateTimeImmutable $prevRunDate) {
        $this->expression = $expression;
        $this->parser = $parser;
        $this->nextRunDate = $nextRunDate;
        $this->prevRunDate = $prevRunDate;
    }

    /**
     * @return string
     */
    public function getExpression(): string {
        return $this->expression;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getNextRunDate(): DateTimeImmutable {
        return $this->nextRunDate;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getPrevRunDate(): DateTimeImmutable {
        return $this->prevRunDate;
    }

    /**
     * Attempts to reparse the expression based on the date passed.
     * @param DateTime|null $date The date to use as a reference point.
     * @return $this
     * @throws Exception
     */
    public function reparse(?DateTime $date = null): self {
        $parsedExpression = $this->parser->parse($this->expression, $date);
        $this->prevRunDate = $parsedExpression->prevRunDate;
        $this->nextRunDate = $parsedExpression->nextRunDate;

        return $this;
    }

    /**
     * Attempts to reparse the expression immutably based on the date passed.
     * @param DateTime|null $date The date to use as a reference point.
     * @return Expression The parsed expression.
     * @throws Exception
     */
    public function reparseImmutable(?DateTime $date = null): Expression {
        return $this->parser->parse($this->expression, $date);
    }

    /**
     * Checks to see if an expression is due based on the date passed.
     * @param DateTime|null $date The date to use as a reference point.
     * @param bool $strict Must be the same datetime and timezone.
     * @throws Exception
     */
    public function isDue(?DateTime $date = null, bool $strict = false): bool {
        $date = $date ?? new DateTime("now", new DateTimeZone(date_default_timezone_get()));
        $date->setTime($date->format("G"), $date->format("i"));

        if ($strict) {
            return (
				$this->nextRunDate->format("Y-m-d H:i:s") === $date->format("Y-m-d H:i:s")
				&& $this->nextRunDate->getTimezone()->getName() === $date->getTimezone()->getName()
			);
        }

        return $this->nextRunDate == $date;
    }
}
