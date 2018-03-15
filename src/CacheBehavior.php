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
 * @property string $cacheKeyPrefixName
 *
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
     * The relations to clear cache when this models cache is cleared.
     *
     * @var array
     */
    public $cacheRelations = [];

    /**
     * Unique md5 for this model.
     *
     * @var string
     */
    protected $_cacheKeyPrefixName;

    /**
     * Keys that have already been cleared.
     *
     * @var array
     */
    protected $_cacheKeysCleared = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'clearCacheEvent',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'clearCacheEvent',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'clearCacheEvent',
        ];
    }

    /**
     * Event to clear cache.
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
            if ($value !== false) {
                Yii::$app->{$this->cache}->set($fullKey, $value);
            }
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
     * Clear cache for model and relations.
     *
     * @param bool $resetCleared
     */
    public function clearCache($resetCleared = true)
    {
        // check for recursion
        if ($resetCleared) {
            $this->_cacheKeysCleared = [];
        }
        if (isset($this->_cacheKeysCleared[$this->cacheKeyPrefixName])) {
            return;
        }
        $this->_cacheKeysCleared[$this->cacheKeyPrefixName] = true;
        // clear related cache
        foreach ($this->cacheRelations as $cacheRelation) {
            $models = is_array($this->owner->$cacheRelation) ? $this->owner->$cacheRelation : [$this->owner->$cacheRelation];
            foreach ($models as $cacheRelationModel) {
                if ($cacheRelationModel instanceof BaseActiveRecord) {
                    $cacheRelationModel->clearCache(false);
                }
            }
        }
        // clear own cache
        $this->clearCacheKeyPrefix();
    }

    /**
     * Get the cache prefix.
     *
     * @return bool|string
     */
    public function getCacheKeyPrefix()
    {
        $cacheKeyPrefix = Yii::$app->{$this->cache}->get($this->cacheKeyPrefixName);
        if (!$cacheKeyPrefix && $this->backupCache) {
            $cacheKeyPrefix = Yii::$app->{$this->backupCache}->get($this->cacheKeyPrefixName);
        }
        if (!$cacheKeyPrefix) {
            $cacheKeyPrefix = uniqid();
            Yii::$app->{$this->cache}->set($this->cacheKeyPrefixName, $cacheKeyPrefix);
            if ($this->backupCache) {
                Yii::$app->{$this->backupCache}->set($this->cacheKeyPrefixName, $cacheKeyPrefix);
            }
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
     * Clear the cache prefix.
     *
     * @param null|string $cacheKeyPrefix
     */
    protected function clearCacheKeyPrefix()
    {
        Yii::$app->{$this->cache}->delete($this->cacheKeyPrefixName);
        if ($this->backupCache) {
            Yii::$app->{$this->backupCache}->delete($this->cacheKeyPrefixName);
        }
    }

    /**
     * Get the cache prefix name.
     *
     * @return string
     */
    public function getCacheKeyPrefixName()
    {
        if (!$this->_cacheKeyPrefixName) {
            $owner = $this->owner;
            $pk = is_array($owner->getPrimaryKey()) ? implode('-', $owner->getPrimaryKey()) : $owner->getPrimaryKey();
            $this->_cacheKeyPrefixName = md5($owner->className() . '.getCacheKeyPrefix.' . $pk);
        }
        return $this->_cacheKeyPrefixName;
    }

}
