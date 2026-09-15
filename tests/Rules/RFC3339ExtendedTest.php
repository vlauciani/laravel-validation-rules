<?php

namespace VLauciani\LaravelValidationRules\Tests\Rules;

use VLauciani\LaravelValidationRules\Rules\RFC3339ExtendedRule;
use VLauciani\LaravelValidationRules\Tests\TestCase;

/**
 * What this rule accepts and what it turns away is part of the contract of every API
 *  that uses it ('arrival_time', 'ot', 'starttime', ...), not an implementation detail.
 *
 * The matrices are plain arrays walked in a loop instead of PHPUnit data providers, so the
 *  same file works on PHPUnit 9 ('@dataProvider') and PHPUnit 12 ('#[DataProvider]').
 *  Kept aligned with the copy of this rule living in caravel/dante8.
 *
 * @see https://gitlab.rm.ingv.it/caravel/dante8/-/issues/162
 * @see https://gitlab.rm.ingv.it/caravel/dante8/-/issues/164
 */
class RFC3339ExtendedTest extends TestCase
{
    /**
     * Datetimes that RFC3339 allows and the rule must therefore accept.
     *
     * The fractional part is free-form in RFC3339 ('time-secfrac = "." 1*DIGIT'), so any
     *  number of digits is inside the contract. Six of them is not an exotic case: Python's
     *  'datetime.isoformat()' emits microseconds, and the Java generated clients emit
     *  whatever precision the picker produced.
     */
    private $accepted = [
        '2021-10-06T06:33:36.440+00:00',        // the pre-existing cases
        '2021-10-06T06:33:36.440Z',
        '2021-10-06T06:33:36.44Z',
        '2021-10-06T06:33:36Z',
        '2026-07-01T00:00:00Z',                 // no fraction, Z
        '2026-07-01T00:00:00+02:00',            // no fraction, positive offset
        '2026-07-01T00:00:00.78+00:00',         // two digits
        '2026-07-01T00:00:00.780Z',             // three digits
        '2026-07-01T00:00:00.000000Z',          // six zeroed digits
        '2026-07-01T00:00:00.780000+00:00',     // six digits, zero sub-milliseconds
        '2026-07-01T00:00:00.780456Z',          // six digits, non-zero sub-milliseconds
        '2026-07-01T00:00:00.780456+00:00',     // the same, with an explicit offset
        '2026-07-01T00:00:00.7804560Z',         // seven digits
        /* '.999999' is the case that tells truncation from rounding: rounding would carry to
           '1000', four digits, and the value would be rejected again. */
        '2026-07-01T00:00:00.999999Z',
        /* Sub-millisecond fractions that round() would collapse to a bare '1' or '0': with no
           '.' left in the rounded number the old code died on 'Undefined array key 1' and the
           API answered 500. The first is the exact value a hyp2000 request carried. */
        '2026-08-29T15:27:17.99958Z',
        '2026-07-01T00:00:00.0004Z',
        /* Negative offsets. RFC3339 gives no privilege to the eastern hemisphere, and the
           normalisation used to split the fraction from the offset on '+' alone, so these
           three were rejected - the last two with a 500. */
        '2026-07-01T00:00:00-05:00',            // no fraction, negative offset
        '2026-07-01T00:00:00.780-05:00',        // three digits, negative offset
        '2026-07-01T00:00:00.780456-05:00',     // six digits, negative offset
        '2026-07-01T00:00:00-00:00',            // '-00:00' is legal RFC3339 ("offset unknown")
    ];

    /**
     * Datetimes the rule must keep rejecting.
     *
     * Guarding the rejections matters as much as guarding the acceptances: a normalisation
     *  loose enough to let these through would silently accept datetimes with no instant
     *  attached.
     */
    private $rejected = [
        'I\'m not a valid RFC3339Extended!',
        '2021-10-06 06:33:36',
        '2026-07-01T00:00:00',                  // no UTC offset, which RFC3339 makes mandatory
        '2026-07-01 00:00:00.780000+00:00',     // space instead of 'T'
        'hello',
        '',
    ];

    public function testRulePasses()
    {
        $rule = new RFC3339ExtendedRule();

        foreach ($this->accepted as $value) {
            $this->assertTrue($rule->passes('arrival_time', $value), "'{$value}' is valid RFC3339 and must be accepted.");
        }
    }

    public function testRuleFails()
    {
        $rule = new RFC3339ExtendedRule();

        foreach ($this->rejected as $value) {
            $this->assertFalse($rule->passes('arrival_time', $value), "'{$value}' is not valid RFC3339 and must be rejected.");
        }
    }
}
