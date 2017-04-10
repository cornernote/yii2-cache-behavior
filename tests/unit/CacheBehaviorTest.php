<?php
/**
 * SoftDeleteTest.php
 *
 * @author Brett O'Donnell <cornernote@gmail.com>
 * @link https://mrphp.com.au/
 */

namespace tests;

use tests\models\Post;

/**
 * CacheBehaviorTest
 */
class CacheBehaviorTest extends DatabaseTestCase
{

    /**
     * Test Cache
     */
    public function testCache()
    {
        $post = Post::findOne(2);
        $post->setCache('test', 'foobar');
        $this->assertEquals('foobar', $post->getCache('test'));
    }

}