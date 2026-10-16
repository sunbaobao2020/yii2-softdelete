<?php

namespace yii2tech\tests\unit\ar\softdelete\data;

/**
 * Item with all operations wrapped in a transaction.
 */
class TransactionalItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
}
