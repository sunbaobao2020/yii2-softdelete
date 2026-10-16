Yii 2 ActiveRecord Soft Delete extension Change Log
===================================================

1.0.6, October 16, 2026
-----------------------

- Bug: With `replaceRegularDelete` and a transactional `delete()` (see `ActiveRecord::transactions()`), `delete()` rolled the soft delete back; it now throws `InvalidConfigException` pointing to `softDelete()`


1.0.5, October 16, 2026
-----------------------

- Bug: `SoftDeleteBehavior::softDelete()` returned `false` and rolled back the soft delete in transactional scenarios when `replaceRegularDelete` was enabled
- Bug: `SoftDeleteBehavior::softDelete()` triggered `afterDelete` even if the soft delete was vetoed
- Enh: Supports Yii 2.0.0 - 2.0.12 (no `StaticInstanceTrait` / `ActiveQuery::getTablesUsedInFrom()`), tested up to Yii 2.0.55
- Enh: Test suite runs on PHPUnit 4.8 - 9 (PHP 5.6 - 8.4)


1.0.4, July 30, 2019
--------------------

- Enh #18: Added `SoftDeleteBehavior::$useRestoreAttributeValuesAsDefaults` allowing set default soft-delete attribute values on record insertion (leandrogehlen, klimov-paul)


1.0.3, January 25, 2019
-----------------------

- Enh: Created `SoftDeleteQueryBehavior` allowing records filtering using "soft delete" criteria (klimov-paul)


1.0.2, May 30, 2018
-------------------

- Bug: Fixed `safeDelete()` method does not catch `\Throwable` (klimov-paul)
- Enh #13: Methods `softDelete()` and `restore()` now supports optimistic locking (klimov-paul)
- Enh #15: Methods `softDelete()` and `restore()` now supports transactional operations (tunecino, klimov-paul)


1.0.1, December 8, 2016
-----------------------

- Bug #4: Fixed model attributes changes at 'beforeSoftDelete' and 'beforeRestore' events are not applied (klimov-paul)


1.0.0, December 26, 2015
------------------------

- Initial release.
