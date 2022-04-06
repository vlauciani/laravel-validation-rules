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
         * Split a date by '.'
         *  Example_1: '2020-12-23T12:33:44.12+00:00' will be $explode_value[0]='2020-12-23T12:33:44' and $explode_value[1]='12+00:00'
         *  Example_2: '2020-12-23T12:33:44+00:00' will be $explode_value[0]='2020-12-23T12:33:44+00:00' and $explode_value[1]=null
         */
        //\Log::debug(" b) replace from:" . $value);
        $explode_value = explode('.', $value);
        if (count($explode_value) == 1) {
            $value = Str::replaceLast('+', '.000+', $explode_value[0]);
        } else {
            $explode_value2 = explode('+', $explode_value[1]); // Split '12+00:00' by '+' to get millisec
            $sec_fraction = $explode_value2[0]; // $explode_value2[0]='12'
            $numoffset = $explode_value2[1] ?? null; // $explode_value2[1]='00:00'
            if (strlen($sec_fraction) < 3) {
                $millisec = str_pad($sec_fraction, 3, '0', STR_PAD_RIGHT); // Add leading zero to RIGHT to obtain: '120'
            } else if (strlen($sec_fraction) == 3) {
                $millisec = $sec_fraction;
            } else {
                (float)$sec_fraction = '0.' . $sec_fraction;
                $millisec = explode('.', round($sec_fraction, 3))[1];
            }
            $value = $explode_value[0] . '.' . $millisec . '+' . $numoffset;
        }
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
