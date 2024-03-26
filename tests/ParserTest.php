<?php

namespace Tests\CronExpression;

use CronExpression\Expression;
use CronExpression\Parser;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {

	private static Parser $parser;

	public static function setUpBeforeClass(): void {
		self::$parser = new Parser();
	}

	#[DataProvider("expressionValidityProvider")]
	public function testExpressionValidity(string $expression, bool $isValid = true) {
		if (!$isValid)
			$this->expectException(Exception::class);

		$this->assertInstanceOf(Expression::class, self::$parser->parse($expression));
	}

	#[DataProvider("minutesIsDueProvider")]
	#[DataProvider("hoursIsDueProvider")]
	public function testMinutesIsDue(string $expression, bool $isDue, string $dateStr = "now") {
		$date = new \DateTime($dateStr, new \DateTimeZone(date_default_timezone_get()));
		$expression = self::$parser->parse($expression, $date);

		$this->assertEquals($isDue, $expression->isDue($date));
	}

	public static function expressionValidityProvider(): array {
		return [
			["*",        false],
			["* *",      false],
			["* * *",    false],
			["* * * *",  false],
			["* * * * *"],
		];
	}

	/**
	 * Examples extracted from crontab guru.
	 * {@link https://crontab.guru/examples.html}
	 */
	public static function minutesIsDueProvider(): array {
		return [
			// Every minute: https://crontab.guru/every-minute
			["* * * * *"  , true                       ],
			// Every 2 minutes: https://crontab.guru/every-2-minutes
			["*/2 * * * *", true,  "2024-03-26 2:00:00"],
			["*/2 * * * *", false, "2024-03-26 2:01:00"],
			["*/2 * * * *", true,  "2024-03-26 2:02:00"],
			["*/2 * * * *", false, "2024-03-26 2:03:00"],
			// Every uneven minute: https://crontab.guru/every-uneven-minute
			["1-59/2 * * * *", true,  "2024-03-26 2:05:00"],
			["1-59/2 * * * *", false, "2024-03-26 2:06:00"],
			["1-59/2 * * * *", true,  "2024-03-26 2:07:00"],
			["1-59/2 * * * *", false, "2024-03-26 2:08:00"],
			// Every three minutes: https://crontab.guru/every-3-minutes
			["*/3 * * * *", true,  "2024-03-26 2:09:00"],
			["*/3 * * * *", false, "2024-03-26 2:10:00"],
			["*/3 * * * *", false, "2024-03-26 2:11:00"],
			["*/3 * * * *", true,  "2024-03-26 2:12:00"],
			["*/3 * * * *", false, "2024-03-26 2:13:00"],
			["*/3 * * * *", false, "2024-03-26 2:14:00"],
			// Every four minutes: https://crontab.guru/every-4-minutes
			["*/4 * * * *", false, "2024-03-26 2:15:00"],
			["*/4 * * * *", true,  "2024-03-26 2:16:00"],
			["*/4 * * * *", false, "2024-03-26 2:17:00"],
			["*/4 * * * *", false, "2024-03-26 2:18:00"],
			["*/4 * * * *", false, "2024-03-26 2:19:00"],
			["*/4 * * * *", true,  "2024-03-26 2:20:00"],
			["*/4 * * * *", false, "2024-03-26 2:21:00"],
			// Every five minutes: https://crontab.guru/every-5-minutes
			["*/5 * * * *", false, "2024-03-26 2:22:00"],
			["*/5 * * * *", false, "2024-03-26 2:23:00"],
			["*/5 * * * *", false, "2024-03-26 2:24:00"],
			["*/5 * * * *", true,  "2024-03-26 2:25:00"],
			["*/5 * * * *", false, "2024-03-26 2:26:00"],
			["*/5 * * * *", false, "2024-03-26 2:27:00"],
			["*/5 * * * *", false, "2024-03-26 2:28:00"],
			["*/5 * * * *", false, "2024-03-26 2:29:00"],
			["*/5 * * * *", true,  "2024-03-26 2:30:00"],
			// Every six minutes: https://crontab.guru/every-6-minutes
			["*/6 * * * *", false, "2024-03-26 2:31:00"],
			["*/6 * * * *", false, "2024-03-26 2:32:00"],
			["*/6 * * * *", false, "2024-03-26 2:33:00"],
			["*/6 * * * *", false, "2024-03-26 2:34:00"],
			["*/6 * * * *", false, "2024-03-26 2:35:00"],
			["*/6 * * * *", true,  "2024-03-26 2:36:00"],
			["*/6 * * * *", false, "2024-03-26 2:37:00"],
			["*/6 * * * *", false, "2024-03-26 2:38:00"],
			["*/6 * * * *", false, "2024-03-26 2:39:00"],
			["*/6 * * * *", false, "2024-03-26 2:40:00"],
			["*/6 * * * *", false, "2024-03-26 2:41:00"],
			["*/6 * * * *", true,  "2024-03-26 2:42:00"],
			["*/6 * * * *", false, "2024-03-26 2:43:00"],
			// Every ten minutes: https://crontab.guru/every-10-minutes
			["*/10 * * * *", false, "2024-03-26 2:53:00"],
			["*/10 * * * *", true,  "2024-03-26 3:00:00"],
			["*/10 * * * *", false, "2024-03-26 3:01:00"],
			["*/10 * * * *", false, "2024-03-26 3:02:00"],
			["*/10 * * * *", false, "2024-03-26 3:03:00"],
			["*/10 * * * *", false, "2024-03-26 3:04:00"],
			["*/10 * * * *", false, "2024-03-26 3:05:00"],
			["*/10 * * * *", false, "2024-03-26 3:06:00"],
			["*/10 * * * *", false, "2024-03-26 3:07:00"],
			["*/10 * * * *", false, "2024-03-26 3:08:00"],
			["*/10 * * * *", false, "2024-03-26 3:09:00"],
			["*/10 * * * *", true,  "2024-03-26 3:10:00"],
			["*/10 * * * *", false, "2024-03-26 3:11:00"],
			// Every fifteen minutes: https://crontab.guru/every-15-minutes
			["*/15 * * * *", false, "2024-03-26 3:12:00"],
			["*/15 * * * *", false, "2024-03-26 3:13:00"],
			["*/15 * * * *", false, "2024-03-26 3:14:00"],
			["*/15 * * * *", true,  "2024-03-26 3:15:00"],
			["*/15 * * * *", false, "2024-03-26 3:16:00"],
			["*/15 * * * *", false, "2024-03-26 3:17:00"],
			["*/15 * * * *", false, "2024-03-26 3:18:00"],
			["*/15 * * * *", false, "2024-03-26 3:19:00"],
			["*/15 * * * *", false, "2024-03-26 3:20:00"],
			["*/15 * * * *", false, "2024-03-26 3:21:00"],
			["*/15 * * * *", false, "2024-03-26 3:22:00"],
			["*/15 * * * *", false, "2024-03-26 3:23:00"],
			["*/15 * * * *", false, "2024-03-26 3:24:00"],
			["*/15 * * * *", false, "2024-03-26 3:25:00"],
			["*/15 * * * *", false, "2024-03-26 3:26:00"],
			["*/15 * * * *", false, "2024-03-26 3:27:00"],
			["*/15 * * * *", false, "2024-03-26 3:28:00"],
			["*/15 * * * *", false, "2024-03-26 3:29:00"],
			["*/15 * * * *", true,  "2024-03-26 3:30:00"],
			["*/15 * * * *", false, "2024-03-26 3:31:00"],
			// Every thirty minutes: https://crontab.guru/every-30-minutes
			["*/30 * * * *", false, "2024-03-26 3:32:00"],
			["*/30 * * * *", false, "2024-03-26 3:33:00"],
			["*/30 * * * *", false, "2024-03-26 3:34:00"],
			["*/30 * * * *", false, "2024-03-26 3:35:00"],
			["*/30 * * * *", false, "2024-03-26 3:36:00"],
			["*/30 * * * *", false, "2024-03-26 3:37:00"],
			["*/30 * * * *", false, "2024-03-26 3:38:00"],
			["*/30 * * * *", false, "2024-03-26 3:39:00"],
			["*/30 * * * *", false, "2024-03-26 3:40:00"],
			["*/30 * * * *", false, "2024-03-26 3:41:00"],
			["*/30 * * * *", false, "2024-03-26 3:42:00"],
			["*/30 * * * *", false, "2024-03-26 3:43:00"],
			["*/30 * * * *", false, "2024-03-26 3:44:00"],
			["*/30 * * * *", false, "2024-03-26 3:45:00"],
			["*/30 * * * *", false, "2024-03-26 3:46:00"],
			["*/30 * * * *", false, "2024-03-26 3:47:00"],
			["*/30 * * * *", false, "2024-03-26 3:48:00"],
			["*/30 * * * *", false, "2024-03-26 3:49:00"],
			["*/30 * * * *", false, "2024-03-26 3:50:00"],
			["*/30 * * * *", false, "2024-03-26 3:51:00"],
			["*/30 * * * *", false, "2024-03-26 3:52:00"],
			["*/30 * * * *", false, "2024-03-26 3:53:00"],
			["*/30 * * * *", false, "2024-03-26 3:54:00"],
			["*/30 * * * *", false, "2024-03-26 3:55:00"],
			["*/30 * * * *", false, "2024-03-26 3:56:00"],
			["*/30 * * * *", false, "2024-03-26 3:57:00"],
			["*/30 * * * *", false, "2024-03-26 3:58:00"],
			["*/30 * * * *", false, "2024-03-26 3:59:00"],
			["*/30 * * * *", true,  "2024-03-26 4:00:00"],
			// Every hour at minute 30: https://crontab.guru/every-hour-at-30-minutes
			["30 * * * *", false, "2024-03-26 4:00:00"],
			["30 * * * *", true,  "2024-03-26 4:30:00"],
			["30 * * * *", false, "2024-03-26 5:00:00"],
			["30 * * * *", true,  "2024-03-26 5:30:00"],
		];
	}

	/**
	 * Examples extracted from crontab guru.
	 * {@link https://crontab.guru/examples.html}
	 */
	public static function hoursIsDueProvider(): array {
		return [
			// Every sixty minutes: https://crontab.guru/every-60-minutes
			["0 * * * *", true,  "2024-03-26 6:00:00"],
			["0 * * * *", false, "2024-03-26 6:10:00"],
			["0 * * * *", false, "2024-03-26 6:20:00"],
			["0 * * * *", false, "2024-03-26 6:30:00"],
			["0 * * * *", false, "2024-03-26 6:40:00"],
			["0 * * * *", false, "2024-03-26 6:50:00"],
			["0 * * * *", true,  "2024-03-26 7:00:00"],
			// Every two hours: https://crontab.guru/every-2-hours
			["0 */2 * * *", false, "2024-03-26 7:00:00"],
			["0 */2 * * *", false, "2024-03-26 7:30:00"],
			["0 */2 * * *", true,  "2024-03-26 8:00:00"],
			["0 */2 * * *", false, "2024-03-26 8:30:00"],
			["0 */2 * * *", false, "2024-03-26 9:00:00"],
			["0 */2 * * *", false, "2024-03-26 9:30:00"],
			["0 */2 * * *", true,  "2024-03-26 10:00:00"],
			// Every three hours: https://crontab.guru/every-3-hours
			["0 */3 * * *", false, "2024-03-26 11:00:00"],
			["0 */3 * * *", false, "2024-03-26 11:30:00"],
			["0 */3 * * *", true,  "2024-03-26 12:00:00"],
			["0 */3 * * *", false, "2024-03-26 12:30:00"],
			["0 */3 * * *", false, "2024-03-26 13:00:00"],
			["0 */3 * * *", false, "2024-03-26 13:30:00"],
			["0 */3 * * *", false, "2024-03-26 14:00:00"],
			["0 */3 * * *", false, "2024-03-26 14:30:00"],
			["0 */3 * * *", true,  "2024-03-26 15:00:00"],
			// Every four hours: https://crontab.guru/every-4-hours
			["0 */4 * * *", true,  "2024-03-26 16:00:00"],
			["0 */4 * * *", false, "2024-03-26 16:30:00"],
			["0 */4 * * *", false, "2024-03-26 17:00:00"],
			["0 */4 * * *", false, "2024-03-26 17:30:00"],
			["0 */4 * * *", false, "2024-03-26 18:00:00"],
			["0 */4 * * *", false, "2024-03-26 18:30:00"],
			["0 */4 * * *", false, "2024-03-26 19:00:00"],
			["0 */4 * * *", false, "2024-03-26 19:30:00"],
			["0 */4 * * *", true,  "2024-03-26 20:00:00"],
			// Every six hours: https://crontab.guru/every-6-hours
			["0 */6 * * *", false, "2024-03-26 21:00:00"],
			["0 */6 * * *", false, "2024-03-26 21:30:00"],
			["0 */6 * * *", false, "2024-03-26 22:00:00"],
			["0 */6 * * *", false, "2024-03-26 22:30:00"],
			["0 */6 * * *", false, "2024-03-26 23:00:00"],
			["0 */6 * * *", false, "2024-03-26 23:30:00"],
			["0 */6 * * *", true,  "2024-03-26 00:00:00"],
			["0 */6 * * *", false, "2024-03-26 00:30:00"],
			["0 */6 * * *", false, "2024-03-26 1:00:00"],
			["0 */6 * * *", false, "2024-03-26 1:30:00"],
			["0 */6 * * *", false, "2024-03-26 2:00:00"],
			["0 */6 * * *", false, "2024-03-26 3:00:00"],
			["0 */6 * * *", false, "2024-03-26 4:00:00"],
			["0 */6 * * *", false, "2024-03-26 5:00:00"],
			["0 */6 * * *", true,  "2024-03-26 6:00:00"],
			// Every eight hours: https://crontab.guru/every-8-hours
			["0 */8 * * *", true,  "2024-03-26 0:00:00"],
			["0 */8 * * *", false, "2024-03-26 1:00:00"],
			["0 */8 * * *", false, "2024-03-26 2:00:00"],
			["0 */8 * * *", false, "2024-03-26 3:00:00"],
			["0 */8 * * *", false, "2024-03-26 4:00:00"],
			["0 */8 * * *", false, "2024-03-26 5:00:00"],
			["0 */8 * * *", false, "2024-03-26 6:00:00"],
			["0 */8 * * *", false, "2024-03-26 7:00:00"],
			["0 */8 * * *", true,  "2024-03-26 8:00:00"],
			["0 */8 * * *", false, "2024-03-26 9:00:00"],
			["0 */8 * * *", false, "2024-03-26 10:00:00"],
			["0 */8 * * *", false, "2024-03-26 11:00:00"],
			["0 */8 * * *", false, "2024-03-26 12:00:00"],
			["0 */8 * * *", false, "2024-03-26 13:00:00"],
			["0 */8 * * *", false, "2024-03-26 14:00:00"],
			["0 */8 * * *", false, "2024-03-26 15:00:00"],
			["0 */8 * * *", true,  "2024-03-26 16:00:00"],
			["0 */8 * * *", false, "2024-03-26 17:00:00"],
			["0 */8 * * *", false, "2024-03-26 18:00:00"],
			["0 */8 * * *", false, "2024-03-26 19:00:00"],
			["0 */8 * * *", false, "2024-03-26 20:00:00"],
			["0 */8 * * *", false, "2024-03-26 21:00:00"],
			["0 */8 * * *", false, "2024-03-26 22:00:00"],
			["0 */8 * * *", false, "2024-03-26 23:00:00"],
			// Every twelve hours: https://crontab.guru/every-12-hours
			["0 */12 * * *", true,  "2024-03-26 0:00:00"],
			["0 */12 * * *", false, "2024-03-26 1:00:00"],
			["0 */12 * * *", false, "2024-03-26 2:00:00"],
			["0 */12 * * *", false, "2024-03-26 3:00:00"],
			["0 */12 * * *", false, "2024-03-26 4:00:00"],
			["0 */12 * * *", false, "2024-03-26 5:00:00"],
			["0 */12 * * *", false, "2024-03-26 6:00:00"],
			["0 */12 * * *", false, "2024-03-26 7:00:00"],
			["0 */12 * * *", false, "2024-03-26 8:00:00"],
			["0 */12 * * *", false, "2024-03-26 9:00:00"],
			["0 */12 * * *", false, "2024-03-26 10:00:00"],
			["0 */12 * * *", false, "2024-03-26 11:00:00"],
			["0 */12 * * *", true,  "2024-03-26 12:00:00"],
			["0 */12 * * *", false, "2024-03-26 13:00:00"],
			["0 */12 * * *", false, "2024-03-26 14:00:00"],
			["0 */12 * * *", false, "2024-03-26 15:00:00"],
			["0 */12 * * *", false, "2024-03-26 16:00:00"],
			["0 */12 * * *", false, "2024-03-26 17:00:00"],
			["0 */12 * * *", false, "2024-03-26 18:00:00"],
			["0 */12 * * *", false, "2024-03-26 19:00:00"],
			["0 */12 * * *", false, "2024-03-26 20:00:00"],
			["0 */12 * * *", false, "2024-03-26 21:00:00"],
			["0 */12 * * *", false, "2024-03-26 22:00:00"],
			["0 */12 * * *", false, "2024-03-26 23:00:00"],
			// Hour range:  https://crontab.guru/hour-range
			["0 9-17 * * *", false, "2024-03-26 0:00:00"],
			["0 9-17 * * *", false, "2024-03-26 1:00:00"],
			["0 9-17 * * *", false, "2024-03-26 2:00:00"],
			["0 9-17 * * *", false, "2024-03-26 3:00:00"],
			["0 9-17 * * *", false, "2024-03-26 4:00:00"],
			["0 9-17 * * *", false, "2024-03-26 5:00:00"],
			["0 9-17 * * *", false, "2024-03-26 6:00:00"],
			["0 9-17 * * *", false, "2024-03-26 7:00:00"],
			["0 9-17 * * *", false, "2024-03-26 8:00:00"],
			["0 9-17 * * *", true,  "2024-03-26 9:00:00"],
			["0 9-17 * * *", true,  "2024-03-26 10:00:00"],
			["0 9-17 * * *", false, "2024-03-26 10:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 11:00:00"],
			["0 9-17 * * *", false, "2024-03-26 11:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 12:00:00"],
			["0 9-17 * * *", false, "2024-03-26 12:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 13:00:00"],
			["0 9-17 * * *", false, "2024-03-26 13:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 14:00:00"],
			["0 9-17 * * *", false, "2024-03-26 14:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 15:00:00"],
			["0 9-17 * * *", false, "2024-03-26 15:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 16:00:00"],
			["0 9-17 * * *", false, "2024-03-26 16:01:00"],
			["0 9-17 * * *", true,  "2024-03-26 17:00:00"],
			["0 9-17 * * *", false, "2024-03-26 17:01:00"],
			["0 9-17 * * *", false, "2024-03-26 18:00:00"],
			["0 9-17 * * *", false, "2024-03-26 19:00:00"],
			["0 9-17 * * *", false, "2024-03-26 20:00:00"],
			["0 9-17 * * *", false, "2024-03-26 21:00:00"],
			["0 9-17 * * *", false, "2024-03-26 22:00:00"],
			["0 9-17 * * *", false, "2024-03-26 23:00:00"],
		];
	}
}