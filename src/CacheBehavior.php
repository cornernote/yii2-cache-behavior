<?php
/**
 * @author Brett O'Donnell <cornernote@gmail.com>
 * @copyright 2018 Mr PHP
 * @link https://github.com/cornernote/yii2-cache-behavior
 * @license BSD-3-Clause https://raw.github.com/cornernote/yii2-cache-behavior/master/LICENSE.md
 */

namespace cornernote\cachebehavior;

use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\caching\Cache;
use yii\caching\TagDependency;
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
 * @property Cacge $cacheComponent
 * @property string $cachePrefix
 * @property string $cacheTags
 *
 */
class CacheBehavior extends Behavior
{
    /**
     * The name of the Yii application cache component to use.
     *
     * @var string
     */
    public $cache = 'cache';

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
    protected $_cachePrefix;

    /**
     * @var array
     */
    protected $_cacheTags;

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
     * @return mixed
     */
    public function getCache($key)
    {
        return $this->cacheComponent->get($this->cachePrefix . '.' . $key);
    }

    /**
     * Set a cached value.
     *
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return mixed
     */
    public function setCache($key, $value, $duration = 0)
    {
        $fullKey = $this->cachePrefix . '.' . $key;
        $dependency = new TagDependency(['tags' => $this->cacheTags]);
        $this->cacheComponent->set($fullKey, $value, $duration, $dependency);
        return $value;
    }

    /**
     * Clear cache for model and relations.
     */
    public function clearCache()
    {
        TagDependency::invalidate($this->cacheComponent, $this->cacheTags);
    }

    /**
     * Get the cache prefix name.
     *
     * @return string
     */
    public function getCachePrefix()
    {
        if (!$this->_cachePrefix) {
            $owner = $this->owner;
            $pk = is_array($owner->primaryKey) ? implode('-', $owner->primaryKey) : $owner->primaryKey;
            $this->_cachePrefix = md5($owner->className() . '.cachePrefix.' . $pk);
        }
        return $this->_cachePrefix;
    }

    /**
     * Get the tags that need to be cleared when this model is cleared.
     *
     * @return array
     */
    public function getCacheTags()
    {
        $this->_cacheTags = $this->cacheComponent->get($this->cachePrefix . '.tags');
        if ($this->_cacheTags) return $this->_cacheTags;
        $this->_cacheTags = [];

        $this->_cacheTags = [$this->cachePrefix];
        foreach ($this->cacheRelations as $cacheRelation) {
            $models = is_array($this->owner->$cacheRelation) ? $this->owner->$cacheRelation : [$this->owner->$cacheRelation];
            foreach ($models as $model) {
                if ($model instanceof BaseActiveRecord) {
                    $this->_cacheTags[] = $model->cachePrefix;
                }
            }
        }

        $this->cacheComponent->set($this->cachePrefix . '.tags', $this->_cacheTags, 0);
        return $this->_cacheTags;
    }

    /**
     * Get the Yii application cache component
     *
     * @return Cache
     */
    protected function getCacheComponent()
    {
        return Yii::$app->{$this->cache};
    }

}
