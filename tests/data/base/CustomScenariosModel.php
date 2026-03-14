<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\base;

use yii\base\Model;

/**
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class CustomScenariosModel extends Model
{
    public $id;
    public $name;

    public function rules()
    {
        return [
            [['id', 'name'], 'required'],
            ['id', 'integer'],
            ['name', 'string'],
        ];
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => ['id', 'name'],
            'secondScenario' => ['id'],
        ];
    }
}
