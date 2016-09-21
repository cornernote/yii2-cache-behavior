<?php
/**
 * @link https://github.com/cornernote/yii2-softdelete
 * @copyright Copyright (c) 2013-2015 Mr PHP <info@mrphp.com.au>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\models;

use cornernote\cachebehavior\CacheBehavior;
use yii\db\ActiveRecord;

/**
 * PostA
 *
 * @property integer $id
 * @property string $title
 * @property string $body
 *
 * @mixin CacheBehavior
 */
class Post extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'post';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            CacheBehavior::className(),
        ];
    }

}
