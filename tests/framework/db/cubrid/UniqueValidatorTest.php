<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\cubrid;

use yiiunit\base\validators\BaseUniqueValidator;

/**
 * @group db
 * @group cubrid
 * @group validators
 */
class UniqueValidatorTest extends BaseUniqueValidator
{
    public $driverName = 'cubrid';
}
