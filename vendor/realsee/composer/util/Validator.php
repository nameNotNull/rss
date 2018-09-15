<?php

namespace MobileApi\Util;

use Illuminate\Validation\Factory as ValidationFactory;
use Symfony\Component\Translation\Translator;
use Yaf\Registry;

class Validator
{

    /**
     * Variable  _validationFactory
     *
     * @author   wangxuan
     * @static
     * @var      null
     */
    private static $_validationFactory = null;

    /**
     * Variable  _messages
     *
     * @author   wangxuan
     * @static
     * @var      array
     */
    private static $_messages = [
        'accepted'             => 'The :attribute must be accepted.',
        'active_url'           => 'The :attribute is not a valid URL.',
        'after'                => 'The :attribute must be a date after :date.',
        'alpha'                => 'The :attribute may only contain letters.',
        'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
        'alpha_num'            => 'The :attribute may only contain letters and numbers.',
        'array'                => 'The :attribute must be an array.',
        'before'               => 'The :attribute must be a date before :date.',
        'between'              => 'The :attribute must be between :min and :max.',
        'boolean'              => 'The :attribute field must be true or false',
        'confirmed'            => 'The :attribute confirmation does not match.',
        'date'                 => 'The :attribute is not a valid date.',
        'date_format'          => 'The :attribute does not match the format :format.',
        'different'            => 'The :attribute and :other must be different.',
        'digits'               => 'The :attribute must be :digits digits.',
        'digits_between'       => 'The :attribute must be between :min and :max digits.',
        'email'                => 'The :attribute must be a valid email address.',
        'exists'               => 'The selected :attribute is invalid.',
        'filled'               => 'The :attribute field is required.',
        'image'                => 'The :attribute must be an image.',
        'in'                   => 'The selected :attribute is invalid.',
        'integer'              => 'The :attribute must be an integer.',
        'ip'                   => 'The :attribute must be a valid IP address.',
        'json'                 => 'The :attribute must be valid json.',
        'max'                  => 'The :attribute may not be greater than :max.',
        'mimes'                => 'The :attribute must be a file of type: :values.',
        'min'                  => 'The :attribute must be at least :min.',
        'not_in'               => 'The selected :attribute is invalid.',
        'numeric'              => 'The :attribute must be a number.',
        'regex'                => 'The :attribute format is invalid.',
        'required'             => 'The :attribute field is required.',
        'required_if'          => 'The :attribute field is required when :other is :value.',
        'required_with'        => 'The :attribute field is required when :values is present.',
        'required_with_all'    => 'The :attribute field is required when :values is present.',
        'required_without'     => 'The :attribute field is required when :values is not present.',
        'required_without_all' => 'The :attribute field is required when none of :values are present.',
        'same'                 => 'The :attribute and :other must match.',
        'size'                 => 'The :attribute must be :size.',
        'timezone'             => 'The :attribute must be a valid zone.',
        'unique'               => 'The :attribute has already been taken.',
        'url'                  => 'The :attribute format is invalid.',
        'not_null'             => 'The :attribute field is null.',
    ];

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  getValidationFactory
     *
     * @author wangxuan
     * @static
     * @return ValidationFactory
     */
    public static function getValidationFactory()
    {
        if (self::$_validationFactory === null) {
            self::$_validationFactory = new ValidationFactory(new Translator(null));

            self::appendRuleNotNull();
        }

        return self::$_validationFactory;
    }

    /**
     * Method  make
     *
     * @author wangxuan
     * @static
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @return \Illuminate\Validation\Validator
     */
    public static function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        if (empty($messages) || !is_array($messages)) {
            $messages = self::$_messages;
        } else {
            $messages = array_merge(self::$_messages, $messages);
        }

        return self::getValidationFactory()->make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Method  extend
     *
     * @author wangxuan
     * @static
     *
     * @param  string         $rule
     * @param  Closure|string $extension
     * @param  string         $message
     */
    public static function extend($rule, $extension, $message = null)
    {
        self::getValidationFactory()->extend($rule, $extension, $message);
    }

    /**
     * Method  extendImplicit
     *
     * @author wangxuan
     * @static
     *
     * @param  string         $rule
     * @param  Closure|string $extension
     * @param  string         $message
     */
    public static function extendImplicit($rule, $extension, $message = null)
    {
        self::getValidationFactory()->extendImplicit($rule, $extension, $message);
    }

    /**
     * Method  appendRuleNotNull
     *
     * @author wangxuan
     * @static
     */
    public static function appendRuleNotNull()
    {
        // append not_null rule
        self::extendImplicit('not_null', function ($attribute, $value, $parameters) {
            return !is_null($value);
        });
    }

}
