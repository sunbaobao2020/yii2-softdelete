<?php

namespace yii2tech\tests\unit\ar\softdelete;

use yii\helpers\ArrayHelper;
use Yii;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * `@before` instead of `setUp()`: works on PHPUnit 4.8 - 9 without the PHP 7.1 `void` return type.
     * @before
     */
    protected function setUpTestApplication()
    {
        $this->mockApplication();

        $this->setupTestDbData();
    }

    /**
     * @after
     */
    protected function tearDownTestApplication()
    {
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ], $config));
    }

    /**
     * @return string vendor path
     */
    protected function getVendorPath()
    {
        return dirname(__DIR__) . '/vendor';
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Setup tables for test ActiveRecord
     */
    protected function setupTestDbData()
    {
        $db = Yii::$app->getDb();

        // Structure :

        $db->createCommand()
            ->createTable('Category', [
                'id' => 'pk',
                'name' => 'string',
                'isDeleted' => 'boolean',
            ])
            ->execute();

        $db->createCommand()
            ->createTable('Item', [
                'id' => 'pk',
                'categoryId' => 'integer',
                'name' => 'string',
                'isDeleted' => 'boolean DEFAULT 0',
                'deletedAt' => 'integer',
                'version' => 'integer',
            ])
            ->execute();

        // Data :
        $categoryIds = [];
        foreach (['category1', 'category2', 'category3'] as $name) {
            $db->createCommand()->insert('Category', ['name' => $name, 'isDeleted' => false])->execute();
            $categoryIds[] = $db->getLastInsertID();
        }

        $db->createCommand()->batchInsert('Item', ['name', 'categoryId'], [
            ['item1', $categoryIds[0]],
            ['item2', $categoryIds[1]],
        ])->execute();
    }
}
