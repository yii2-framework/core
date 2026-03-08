<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\validators\client;

use yii\base\Model;
use yii\validators\Validator;
use yii\web\View;

/**
 * Defines the contract for client-side validator script strategies.
 *
 * Implementations provide JavaScript validation code and client options for a specific JS framework (for example,
 * jQuery).
 *
 * This allows decoupling validators from any particular client-side library.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
interface ClientValidatorScriptInterface
{
    /**
     * Returns the client-side validation options for the given validator.
     *
     * @param Validator $validator the validator instance.
     * @param Model $model the data model being validated.
     * @param string $attribute the attribute name being validated.
     * @return array the client-side validation options.
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array;

    /**
     * Returns the JavaScript code needed for performing client-side validation.
     *
     * @param Validator $validator the validator instance.
     * @param Model $model the data model being validated.
     * @param string $attribute the attribute name being validated.
     * @param View $view the view object that is going to be used to render views or view files.
     * @return string|null the client-side validation script, or `null` if not supported.
     */
    public function register(Validator $validator, Model $model, string $attribute, View $view): ?string;
}
