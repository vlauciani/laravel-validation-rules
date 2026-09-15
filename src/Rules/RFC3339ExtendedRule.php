<?php

namespace VLauciani\LaravelValidationRules\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\Concerns\ValidatesAttributes;

class RFC3339ExtendedRule implements Rule
{
    use ValidatesAttributes;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //\Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);


        /**
         * !!! - !!!
         * PHP doesn't validate correctly RFC3339: https://github.com/laravel/framework/issues/35387
         * for example '2020-12-21T23:59:59+00:00' or '2020-12-21T23:59:59Z' return 'false' but it is 'true'.
         * The code below uniform the date in format: 'YYYY-MM-DDThh:mm:ss.mmm+nn:nn'
         */

        /* Set format to validate */
        $format = \DateTimeInterface::RFC3339_EXTENDED;

        /**
         * Substitue 'Z' with '+00:00'
         */
        if (Str::endsWith($value, 'Z')) {
            //\Log::debug(" replace from:" . $value);
            $value = Str::replaceLast('Z', '+00:00', $value);
            //\Log::debug(" to:" . $value);
        } else {
            /**
             * In the GET Method, the '+' (plus) symbol is substitute with ' ' (space); for example:
             *  'dateend=2020-12-01T12:30:25+00:00' 
             * will replaced in GET with: 
             *  'dateend=2020-12-01T12:30:25 00:00'
             * 
             * This code below "restore" the '+' symbol in the date string.
             */
            if (Str::substr($value, -6, 1) == ' ') {
                //\Log::debug(" a) replace from:" . $value);
                $value = Str::replaceLast(' ', '+', $value);
                //\Log::debug(" a) to:" . $value);
            }
        }

        /**
         * Split '<datetime>[.<fraction>]<offset>' in one match, keeping the sign of the offset.
         *  Example_1: '2020-12-23T12:33:44.12+00:00' -> ['2020-12-23T12:33:44', '12', '+00:00']
         *  Example_2: '2020-12-23T12:33:44-05:00'    -> ['2020-12-23T12:33:44', '',   '-05:00']
         *
         * The previous code exploded on '.' and then on '+', so a negative offset was never
         *  split off: the fraction became '780-05:00' and the value was rebuilt with a
         *  hard-coded '+' and an empty offset, which could not validate. Matching the three
         *  parts at once is what makes '-05:00' survive.
         *
         * 'Z' has already been rewritten to '+00:00' above, so an offset is always present
         *  here; the anchored pattern therefore also subsumes the positional separator checks
         *  ('-' at 4 and 7, 'T' at 10, ':' at 13 and 16) this block used to be guarded by.
         */
        //\Log::debug(" b) replace from:" . $value);
        if (!preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.(\d+))?([+-]\d{2}:\d{2})$/', $value, $matches)) {
            return false;
        }
        $sec_fraction = $matches[2]; // '12', or '' when the datetime carries no fraction (the offset group follows, so PHP fills the gap)
        $numoffset = $matches[3];          // '+00:00' or '-05:00'

        /**
         * '-00:00' is legal RFC3339 (4.3: "the offset to local time is unknown") and denotes
         *  the same instant as '+00:00'. DateTime normalises it to '+00:00', and
         *  validateDateFormat() compares the reformatted date with the input string, so the
         *  value would be turned away for a difference that carries no information.
         */
        if ($numoffset === '-00:00') {
            $numoffset = '+00:00';
        }

        if ($sec_fraction === '') {
            $millisec = '000'; // RFC3339_EXTENDED demands a fraction, so supply an empty one
        } elseif (strlen($sec_fraction) <= 3) {
            $millisec = str_pad($sec_fraction, 3, '0', STR_PAD_RIGHT); // Add zeros to the RIGHT to obtain: '120'
        } else {
            /**
             * Truncate to milliseconds: '780456' -> '780'.
             *
             * RFC3339 fractional digits are positional, so the first three ARE the
             *  milliseconds and cutting the rest is the correct operation - the same
             *  truncation a millisecond-precision column performs. The previous code
             *  went through floatval() + round(): whenever the fraction rounded to a
             *  bare '1' or '0' (e.g. '99958', '0004') the string carried no '.' any more,
             *  explode() returned a single element and the rule died on
             *  'Undefined array key 1', turning a valid datetime into a 500.
             */
            $millisec = substr($sec_fraction, 0, 3);
        }
        $value = $matches[1] . '.' . $millisec . $numoffset;
        //\Log::debug(" b) to:" . $value);

        /* Validate */
        //\Log::debug(" validate attribute \"" . $attribute . "\" -> \"" . $value . "\" with format:\"" . $format . "\"");
        $return = $this->validateDateFormat($attribute, $value, [$format]);
        //\Log::debug(" output:" . var_export($return, true));

        //\Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        return $return;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $return = Str::replaceFirst(':format', 'RFC3339 (https://tools.ietf.org/html/rfc3339)', \trans('validation.date_format'));
        return $return;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    /*
    public function message(): string
    {
        return __('validationRules.hostname');
    }
    */
}
