Typo3-Content-Service
==============

## Content Element Injection

```
public function __invoke(TtContent $element): Response
```

Currently the content element model can only be injected using the method argument `TtContent $element`. In order to inject the content service the symfony `FilterControllerEvent` needs to be dispatched before the arguments are passed to the content element.

## Project Specific Overrides

In order to add custom methods to content elements and pages you should create your own `TtContent`, `Page` and `PageOverlay` classes and extend the base ones.

```
class TtContent extends Typo3ContentService\Models\TtContent {}
class Page extends Typo3ContentService\Models\Page {}
class PageOverlay extends Typo3ContentService\Models\PageOverlay {}
```

## Accessing the Database

```
/**
* @property int uid
* @property int pid
*/
class Dummy extends Typo3ContentService\Models\AbstractModel {
    protected $table = 'dummies';
}
```

The models uses the magic getter and setter to access their properties. Property names represent the respective column with the same name of the associated database table. It's recommended to specify all columns in the class doc block (`@property int uid`) to get full auto completion.

Use the `protected $table = '';` class property to specify which database table is used to read / store this model.

## Create a new Model

```
$model = new AbstractModel();
$model->property = 'lorem ipsum';
$model->store();
```

## Read multiple Models

```
$models = AbstractModel::all();
$models = AbstractModel::findAllBy($where);
$models = AbstractModel::findMultiple($keys);
```

## Read a single Model

```
$model = AbstractModel::findBy($where);
$model = AbstractModel::findByColumn($column, $value);
$model = AbstractModel::find($key);
```

## Store a single Model

```
$model->store();
```

The `store` method performs an insert or update depending if the model already exists or not. In both ways the updated timestamp of the model is set to now (`\time()`). For inserts the creation timestamp will be set once and its unique id will be updated on success.
By default all model properties will be used for the insert/update statement (model property name = table column name). If only a bunch of properties should be used instead you can specify them in the `protected $fillables = [];` array (`uid, pid, crdate, tstamp, deleted` are added automatically).

## Delete a single Model

```
$model->hardDelete();
$model->softDelete();
$model->delete();
```

Hard deletion REALLY deletes the model / database row. Soft deletion updates the soft deleted flag of the model (update statement). Soft deletions are only usable if `protected $softDeletionColumnName = 'deleted';` is specified (default).
The `delete();` method uses the soft deletion method if the model has a soft deletion column and is not soft deleted yet, otherwise a hard delete is performed.

## Type Cast Properties

```
protected $casts = [
    'uid' => 'int',
    'pid' => 'int',
    'header' => 'string',
];

protected function castUid($value): int;
```

By default model properties store the raw database value on read or keep the raw value when set. If a model property / column is specified in the `$casts` array its value will be converted to the specified data type on read/write. If you need a more complex type cast you can create a `protected function castColumnname($value);` class method.
The following type casts are supported by the `$casts` array:
* int (int)
* integer (int)
* double (float)
* float (float)
* numeric (float)
* decimal (float)
* array (array)
* collection (array)
* list (array)
* string (string)
* bool (bool)
* boolean (bool)
* file (TYPO3\CMS\Core\Resource\FileReference)
* files (TYPO3\CMS\Core\Resource\FileReference[])

## Fetch associated Inline Records

```
$records = $model->getInlineRecords(AbstractModel::class);
```

Fetches all inline records of the specified class name attached to this model using the `parent_table` and `parent_id` columns. Any sub class of `AbstractModel` can be used as method argument.

## Fetch associated Files

```
$files = $model->resolveFiles($columnName);
$file = $model->resolveFile($columnName);
```

Methods to either resolve all or a single / the first Typo3 File Reference associated to the specified column name.

**Missing: Verify the number of files fetched (should match the integer value of the specified column property)**

## Translation

```
$translation = $model->translate($targetLanguageIndex);
```

The method uses the default Typo3 PageRepository / RecordOverlay to fetch the translation for the specified target sys language uid. The translation is mapped to a model instance and returned. Translations are disabled if `protected $languageIndexColumnName = 'sys_language_uid';` is set to an empty string or `null`.

**Missing: Custom translation for `pages` records as their translations are handled differently.**
