<?php
/**
 * @author Brett O'Donnell <cornernote@gmail.com>
 * @copyright 2016 Mr PHP
 * @link https://github.com/cornernote/yii2-cache-behavior
 * @license BSD-3-Clause https://raw.github.com/cornernote/yii2-cache-behavior/master/LICENSE.md
 */

namespace cornernote\cachebehavior;

use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\BaseActiveRecord;

/**
 * CacheBehavior
 *
 * @usage:
 * ```
 * public function behaviors() {
 *     return [
 *         [
 *             'class' => 'cornernote\cachebehavior\CacheBehavior',
 *         ],
 *     ];
 * }
 * ```
 *
 * @property BaseActiveRecord $owner
 */
class CacheBehavior extends Behavior
{
    /**
     * The cache component to use.
     *
     * @var string
     */
    public $cache = 'cache';

    /**
     * A backup cache component to use.
     * For example if your main cache is MemCache then you may want to use
     * FileCache or DbCache as a backup cache storage.
     *
     * @var bool
     */
    public $backupCache = false;

    /**
     * An array of the models to clear cache when this models cache is cleared
     *
     * @var array
     */
    public $cacheRelations = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'clearCacheEvent',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'clearCacheEvent',
        ];
    }

    /**
     * Set the attribute with the current timestamp to mark as deleted
     *
     * @param Event $event
     */
    public function clearCacheEvent($event)
    {
        $this->clearCache();
    }

    /**
     * Get a cached value.
     *
     * @param string $key
     * @param bool $useBackupCache
     * @return mixed
     */
    public function getCache($key, $useBackupCache = false)
    {
        $fullKey = $this->getCacheKeyPrefix() . '.' . $key;
        $value = Yii::$app->{$this->cache}->get($fullKey);
        if (!$value && $useBackupCache && $this->backupCache) {
            $value = Yii::$app->{$this->backupCache}->get($fullKey);
        }
        return $value;
    }

    /**
     * Set a cached value.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $useBackupCache
     * @return mixed
     */
    public function setCache($key, $value, $useBackupCache = false)
    {
        $fullKey = $this->getCacheKeyPrefix() . '.' . $key;
        Yii::$app->{$this->cache}->set($fullKey, $value);
        if ($useBackupCache && $this->backupCache) {
            Yii::$app->{$this->backupCache}->set($fullKey, $value);
        }
        return $value;
    }

    /**
     * Clear cache for this model
     */
    public function clearCache()
    {
        $owner = $this->owner;
        // clear related cache
        foreach ($this->cacheRelations as $cacheRelation) {
            $models = is_array($owner->$cacheRelation) ? $owner->$cacheRelation : array($owner->$cacheRelation);
            foreach ($models as $cacheRelationModel) {
                if ($cacheRelationModel instanceof BaseActiveRecord) {
                    $cacheRelationModel->clearCache();
                }
            }
        }
        // clear own cache
        $this->setCacheKeyPrefix();
    }

    /**
     * Get the cache prefix.
     *
     * @return bool|string
     */
    public function getCacheKeyPrefix()
    {
        $cacheKeyPrefixName = $this->getCacheKeyPrefixName();
        $cacheKeyPrefix = Yii::$app->{$this->cache}->get($cacheKeyPrefixName);
        if (!$cacheKeyPrefix && $this->backupCache) {
            Yii::$app->{$this->backupCache}->get($cacheKeyPrefixName);
        }
        if (!$cacheKeyPrefix) {
            $cacheKeyPrefix = uniqid();
            Yii::$app->{$this->cache}->set($cacheKeyPrefixName, $cacheKeyPrefix);
        }
        return $cacheKeyPrefix;
    }

    /**
     * Set the cache prefix.
     *
     * @param null|string $cacheKeyPrefix
     */
    public function setCacheKeyPrefix($cacheKeyPrefix = null)
    {
        $cacheKeyPrefixName = $this->getCacheKeyPrefixName();
        if (!$cacheKeyPrefix) {
            $cacheKeyPrefix = uniqid();
        }
        Yii::$app->{$this->cache}->set($cacheKeyPrefixName, $cacheKeyPrefix);
        if ($this->backupCache) {
            Yii::$app->{$this->backupCache}->set($cacheKeyPrefixName, $cacheKeyPrefix);
        }
    }

    /**
     * Get the cache prefix name.
     *
     * @return string
     */
    protected function getCacheKeyPrefixName()
    {
        $owner = $this->owner;
        $pk = is_array($owner->getPrimaryKey()) ? implode('-', $owner->getPrimaryKey()) : $owner->getPrimaryKey();
        return md5($owner->className() . '.getCacheKeyPrefix.' . $pk);
    }

}