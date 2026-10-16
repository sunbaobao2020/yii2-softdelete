<?php

namespace yii2tech\tests\unit\ar\softdelete;

use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\tests\unit\ar\softdelete\data\Category;
use yii2tech\tests\unit\ar\softdelete\data\Item;
use yii2tech\tests\unit\ar\softdelete\data\TransactionalItem;
use yii2tech\tests\unit\ar\softdelete\data\VersionedItem;

class SoftDeleteBehaviorTest extends TestCase
{
    public function testSoftDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);

        $result = $item->softDelete();

        $this->assertEquals(1, $result);
        $this->assertEquals(true, $item->isDeleted);
    }

    public function testReplaceDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        /* @var $behavior SoftDeleteBehavior */

        $item = Item::findOne(2);
        $behavior = $item->getBehavior('softDelete');
        $behavior->replaceRegularDelete = true;
        $item->delete();

        $this->assertEquals(true, $item->isDeleted);
        $this->assertEquals(2, Item::find()->count());
    }

    /**
     * @depends testReplaceDelete
     */
    public function testSoftDeleteWithReplaceRegularDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);
        $item->getBehavior('softDelete')->replaceRegularDelete = true;
        $afterDeleteCount = 0;
        $item->on(BaseActiveRecord::EVENT_AFTER_DELETE, function () use (&$afterDeleteCount) {
            $afterDeleteCount++;
        });

        $result = $item->softDelete();

        $this->assertEquals(1, $result);
        $this->assertEquals(true, Item::findOne(2)->isDeleted);
        $this->assertEquals(1, $afterDeleteCount);
    }

    /**
     * @depends testSoftDeleteWithReplaceRegularDelete
     */
    public function testTransactionalSoftDeleteWithReplaceRegularDelete()
    {
        /* @var $item TransactionalItem|SoftDeleteBehavior */
        $item = TransactionalItem::findOne(2);
        $item->getBehavior('softDelete')->replaceRegularDelete = true;

        $this->assertEquals(1, $item->softDelete());
        $this->assertEquals(true, TransactionalItem::findOne(2)->isDeleted);
    }

    /**
     * @depends testTransactionalSoftDeleteWithReplaceRegularDelete
     */
    public function testTransactionalRegularDeleteWithReplaceRegularDelete()
    {
        /* @var $item TransactionalItem|SoftDeleteBehavior */
        $item = TransactionalItem::findOne(2);
        $item->getBehavior('softDelete')->replaceRegularDelete = true;
        try {
            $item->delete();
            $this->fail('transactional delete() must not silently roll back the soft delete');
        } catch (\yii\base\InvalidConfigException $exception) {
        }
        $this->assertEquals(false, TransactionalItem::findOne(2)->isDeleted);
        $this->assertEquals(2, TransactionalItem::find()->count());
    }

    /**
     * @depends testSoftDeleteWithReplaceRegularDelete
     */
    public function testExceptionInBeforeDeleteDuringSoftDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);
        $item->getBehavior('softDelete')->replaceRegularDelete = true;
        $item->throwOnDeleteException = true;
        try {
            $item->softDelete();
            $this->fail('exception from beforeDelete() expected');
        } catch (\yii\db\IntegrityException $exception) {
        }
        $this->assertEquals(false, Item::findOne(2)->isDeleted);

        $item->throwOnDeleteException = false;
        $item->delete();
        $this->assertEquals(true, Item::findOne(2)->isDeleted);
    }

    /**
     * @depends testSoftDelete
     */
    public function testAllowDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        /* @var $behavior SoftDeleteBehavior */

        $item = Item::findOne(1);
        $behavior = $item->getBehavior('softDelete');
        $behavior->replaceRegularDelete = true;
        $item->name = 'allow-delete';
        $item->softDelete();

        $this->assertEquals(1, Item::find()->count());
    }

    /**
     * @depends testSoftDelete
     */
    public function testRestore()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);

        $item->softDelete();
        $result = $item->restore();

        $this->assertEquals(1, $result);
        $this->assertEquals(false, $item->isDeleted);
    }

    /**
     * @depends testRestore
     */
    public function testCallback()
    {
        /* @var $item Item|SoftDeleteBehavior */
        /* @var $behavior SoftDeleteBehavior */

        $item = Item::findOne(1);
        $behavior = $item->getBehavior('softDelete');
        $behavior->softDeleteAttributeValues = [
            'deletedAt' => function() {
                return time();
            }
        ];
        $item->softDelete();

        $this->assertTrue($item->deletedAt >= time());

        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);
        $behavior = $item->getBehavior('softDelete');
        $behavior->restoreAttributeValues = [
            'deletedAt' => function() {
                return null;
            }
        ];
        $item->restore();

        $this->assertNull($item->deletedAt);
    }

    /**
     * @depends testSoftDelete
     */
    public function testSafeDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        /* @var $behavior SoftDeleteBehavior */

        // actual delete
        $item = Item::findOne(1);
        $result = $item->safeDelete();

        $this->assertEquals(1, $result);
        $this->assertNull(Item::findOne(1));

        // fallback
        $item = Item::findOne(2);
        $item->throwOnDeleteException = true;
        $result = $item->safeDelete();

        $this->assertEquals(1, $result);
        $item = Item::findOne(2);
        $this->assertNotNull($item);
        $this->assertEquals(true, $item->isDeleted);

        // custom exception class
        $item = Item::findOne(2);
        $item->throwOnDeleteException = true;
        $item->onDeleteExceptionClass = 'yii\base\InvalidValueException';
        $behavior = $item->getBehavior('softDelete');
        $behavior->deleteFallbackException = $item->onDeleteExceptionClass;

        $item->safeDelete();
        $this->assertNotNull(Item::findOne(2));
        $this->assertEquals(true, $item->isDeleted);

        $item->onDeleteExceptionClass = 'yii\db\IntegrityException';

        try {
            $item->isDeleted = false;
            $item->safeDelete();
            $this->assertTrue(false, 'No exception thrown');
        } catch (\Exception $exception) {
            $this->assertEquals('yii\db\IntegrityException', get_class($exception));
            $this->assertEquals(false, $item->isDeleted);
        }
    }

    /**
     * @depends testSoftDelete
     */
    /**
     * @depends testSoftDelete
     */
    public function testNoAfterDeleteOnVetoedSoftDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);
        $afterDeleteCount = 0;
        $item->on(BaseActiveRecord::EVENT_AFTER_DELETE, function () use (&$afterDeleteCount) {
            $afterDeleteCount++;
        });
        $item->on(SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE, function (ModelEvent $event) {
            $event->isValid = false;
        });

        $this->assertFalse($item->softDelete());
        $this->assertEquals(false, Item::findOne(2)->isDeleted);
        $this->assertEquals(0, $afterDeleteCount);
    }

    public function testBeforeSoftDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);

        $item->on(SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE, function (ModelEvent $event) {
            $item = $event->sender;
            $item->deletedAt = 100;
        });

        $item->softDelete();

        $item = Item::findOne(1);
        $this->assertEquals(100, $item->deletedAt);
    }

    /**
     * @depends testRestore
     */
    public function testBeforeRestore()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);
        $item->softDelete();

        $item = Item::findOne(1);
        $item->on(SoftDeleteBehavior::EVENT_BEFORE_RESTORE, function (ModelEvent $event) {
            $item = $event->sender;
            $item->deletedAt = 200;
        });
        $item->restore();

        $item = Item::findOne(1);
        $this->assertEquals(200, $item->deletedAt);
    }

    /**
     * @depends testRestore
     */
    public function testOptimisticLock()
    {
        /* @var $item VersionedItem|SoftDeleteBehavior */

        $item = new VersionedItem();
        $item->name = 'optimistic lock';
        $item->version = 1;
        $item->save(false);

        $item = VersionedItem::findOne($item->id);
        $this->assertTrue($item->softDelete() > 0);
        $this->assertEquals(2, $item->version);

        $item = VersionedItem::findOne($item->id);
        $this->assertTrue($item->restore() > 0);
        $this->assertEquals(3, $item->version);

        $item = VersionedItem::findOne($item->id);
        $item->version = 0;
        $this->expectException('yii\db\StaleObjectException');
        $item->softDelete();
    }

    /**
     * @depends testRestore
     */
    public function testUseRestoreAttributeValuesAsDefaults()
    {
        $category = new Category();
        $category->name = 'apply restore attribute';
        $category->save(false);
        $this->assertSame(false, $category->isDeleted);

        $category = new Category();
        $category->name = 'prevent restore attribute application';
        $category->isDeleted = true;
        $category->save(false);
        $this->assertSame(true, $category->isDeleted);
    }
}