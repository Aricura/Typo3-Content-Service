<?php

declare(strict_types=1);

/*
 * The Typo3-Content-Service is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Typo3-Content-Service is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the Typo3-Content-Service. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Typo3ContentService\Models;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Abstract base model used as I/O for any database table record.
 */
abstract class AbstractModel
{
    /**
     * Database table name this model is associated with.
     *
     * @var string
     */
    protected $table = '';

    /**
     * Column name where the unique identifier is stored in.
     * Default set to 'uid'.
     *
     * @var string
     */
    protected $keyColumnName = 'uid';

    /**
     * Column name where the parent information is stored in.
     * This information may be empty if the model has no parent information.
     * Default set to 'pid'.
     *
     * @var string
     */
    protected $parentColumnName = 'pid';

    /**
     * Column name where the creation unix timestamp of the model is stored in.
     * This information may be empty if the model has no creation timestamp.
     * Default set to 'crdate'.
     *
     * @var string
     */
    protected $createdAtColumnName = 'crdate';

    /**
     * Column name where the last modified unix timestamp of the model is stored in.
     * This information may be empty if the model has no updated timestamp.
     * Default set to 'tstamp'.
     *
     * @var string
     */
    protected $updatedAtColumnName = 'tstamp';

    /**
     * Column name where the soft deletion flag of the model is stored in.
     * This information may be empty if the model has no soft deletion.
     * Default set to 'deleted'.
     *
     * @var string
     */
    protected $softDeletionColumnName = 'deleted';

    /**
     * Column name where the language index of the model is stored in.
     * This information may be empty if the model has no language index / isn't translatable.
     * Default set to 'sys_language_uid'.
     *
     * @var string
     */
    protected $languageIndexColumnName = 'sys_language_uid';

    /**
     * Collection of all column names which can be written into the database.
     * If this information is empty all existing model attributes will be stored in the database.
     * Core information (key, parent, created at, updated at) are automatically stored and must not be defined here.
     *
     * @var array
     */
    protected $fillables = [];

    /**
     * Collection of all model attributes and their type casts.
     * The type cast will be applied whenever an attribute is set.
     * Attributes without a special type cast remain as they are.
     * The attribute names and type casts should be written in all lowercase.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Collection of all model attributes.
     *
     * @var array
     */
    private $attributes = [];

    /**
     * Flag if the model exists in the database or not.
     *
     * @var bool
     */
    private $exists = false;

    /**
     * Flag if any model attribute was changed since it was last read from the database.
     * New models are always dirty as long as they are not inserted into the database.
     *
     * @var bool
     */
    private $isDirty = true;

    /**
     * Connection pool reference to access the database.
     *
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * File repository reference to fetch related media files.
     *
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * Page repository reference to translate models.
     *
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * Checks if the specified attribute name is already defined.
     *
     * @param string $key attribute key name
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }

    /**
     * Magic getter to return the type casted value of the attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        $value = $this->__isset($key) ? $this->attributes[$key] : null;

        return $this->cast($key, $value);
    }

    /**
     * Magic setter to update the value of the attribute.
     * The type cast will be applied before the value is stored.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set(string $key, $value)
    {
        $this->attributes[$key] = $this->cast($key, $value);
        $this->isDirty = true;
    }

    /**
     * Possibility to inject the connection pool reference.
     *
     * @param ConnectionPool|object $connectionPool
     */
    public function injectConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Possibility to inject the file repository reference.
     *
     * @param FileRepository|object $fileRepository
     */
    public function injectFileRepository(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Possibility to inject the page repository reference.
     *
     * @param PageRepository|object $pageRepository
     */
    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * Returns and initializes (if not injected) the connection pool reference.
     *
     * @return ConnectionPool
     */
    public function getConnectionPool(): ConnectionPool
    {
        if (!$this->connectionPool) {
            $this->injectConnectionPool(GeneralUtility::makeInstance(ConnectionPool::class));
        }

        return $this->connectionPool;
    }

    /**
     * Returns and initializes (if not injected) the file repository reference.
     *
     * @return FileRepository
     */
    public function getFileRepository(): FileRepository
    {
        if (!$this->fileRepository) {
            $this->injectFileRepository(GeneralUtility::makeInstance(FileRepository::class));
        }

        return $this->fileRepository;
    }

    /**
     * Returns and initializes (if not injected) the page repository reference.
     *
     * @return PageRepository
     */
    public function getPageRepository(): PageRepository
    {
        if (!$this->pageRepository) {
            $this->injectPageRepository(GeneralUtility::makeInstance(PageRepository::class));
        }

        return $this->pageRepository;
    }

    /**
     * Returns a database connection interface for the model's table.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->getConnectionPool()->getConnectionForTable($this->getTableName());
    }

    /**
     * Returns a database query builder interface for the model's table.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getConnectionPool()->getQueryBuilderForTable($this->getTableName());
    }

    /**
     * Returns the database table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Returns the column name where the unique identifier is stored in.
     *
     * @return string
     */
    public function getKeyColumnName(): string
    {
        return $this->keyColumnName;
    }

    /**
     * Returns the column name where the parent identifier is stored in.
     *
     * @return string
     */
    public function getParentColumnName(): string
    {
        return $this->parentColumnName;
    }

    /**
     * Checks if this model has a parent information column.
     *
     * @return bool
     */
    public function hasParentColumn(): bool
    {
        return '' !== $this->getParentColumnName();
    }

    /**
     * Returns the column name where the creation unix timestamp is stored in.
     *
     * @return string
     */
    public function getCreatedAtColumnName(): string
    {
        return $this->createdAtColumnName;
    }

    /**
     * Checks if this model has a creation timestamp column.
     *
     * @return bool
     */
    public function hasCreatedAtColumn(): bool
    {
        return '' !== $this->getCreatedAtColumnName();
    }

    /**
     * Returns the column name where the updated unix timestamp is stored in.
     *
     * @return string
     */
    public function getUpdatedAtColumnName(): string
    {
        return $this->updatedAtColumnName;
    }

    /**
     * Checks if this model has an updated timestamp column.
     *
     * @return bool
     */
    public function hasUpdatedAtColumn(): bool
    {
        return '' !== $this->getUpdatedAtColumnName();
    }

    /**
     * Returns the column name where the soft deletion information is stored in.
     *
     * @return string
     */
    public function getSoftDeletedColumnName(): string
    {
        return $this->softDeletionColumnName;
    }

    /**
     * Checks if this model has a soft deletion column.
     *
     * @return bool
     */
    public function hasSoftDeletedColumn(): bool
    {
        return '' !== $this->getSoftDeletedColumnName();
    }

    /**
     * Returns the column name where the language index is stored in.
     *
     * @return string
     */
    public function getLanguageIndexColumnName(): string
    {
        return $this->languageIndexColumnName;
    }

    /**
     * Checks if this model has a language index column.
     *
     * @return bool
     */
    public function hasLanguageIndexColumn(): bool
    {
        return '' !== $this->getLanguageIndexColumnName();
    }

    /**
     * Returns all model attributes as array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the model's unique identifier.
     * Returns 0 (zero) for new models.
     *
     * @return int
     */
    public function getKey(): int
    {
        return (int) $this->getAttribute($this->getKeyColumnName());
    }

    /**
     * Returns the model's parent identifier.
     * Returns 0 (zero) if the model has no parent identifier.
     *
     * @return int
     */
    public function getParentKey(): int
    {
        return (int) $this->getAttribute($this->getParentColumnName());
    }

    /**
     * Returns the model's creation timestamp (unix).
     * Returns 0 (zero) if the model has no creation timestamp column.
     *
     * @return int
     */
    public function getCreatedAtTimestamp(): int
    {
        return (int) $this->getAttribute($this->getCreatedAtColumnName());
    }

    /**
     * Returns the model's last modified timestamp (unix).
     * Returns 0 (zero) if the model has no last modified timestamp column.
     *
     * @return int
     */
    public function getUpdatedAtTimestamp(): int
    {
        return (int) $this->getAttribute($this->getUpdatedAtColumnName());
    }

    /**
     * Returns the created at timestamp as DateTime object.
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return new \DateTime($this->getCreatedAtTimestamp());
    }

    /**
     * Returns the updated at timestamp as DateTime object.
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return new \DateTime($this->getUpdatedAtTimestamp());
    }

    /**
     * Checks if the model exists in the database or not.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists && $this->getKey() > 0;
    }

    /**
     * Checks if any attribute was changed since the model was last read.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->isDirty;
    }

    /**
     * Checks if this model is already soft deleted.
     *
     * @return bool
     */
    public function isSoftDeleted(): bool
    {
        if (!$this->hasSoftDeletedColumn()) {
            return false;
        }

        return 0 !== (int) $this->getAttribute($this->getSoftDeletedColumnName());
    }

    /**
     * Returns the language index of this model.
     * Returns 0 (zero) if the model has no language index/information.
     *
     * @return int
     */
    public function getLanguageIndex(): int
    {
        return (int) $this->getAttribute($this->getLanguageIndexColumnName());
    }

    /**
     * Wrapper method for the magic getter.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        return $this->__get($key);
    }

    /**
     * Wrapper method for the magic setter.
     *
     * @param string $key
     * @param        $value
     */
    public function setAttribute(string $key, $value)
    {
        $this->__set($key, $value);
    }

    /**
     * Wrapper method for the magic __isset method.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Performs a hard delete action for this model.
     *
     * @return bool
     */
    public function hardDelete(): bool
    {
        // the model can't be hard deleted if it doesn't even exist
        if (!$this->exists()) {
            return false;
        }

        $where = [$this->getKeyColumnName() => $this->getKey()];

        return 1 === $this->getConnection()->delete($this->getTableName(), $where);
    }

    /**
     * Performs a soft delete action for this model (only if the soft deletion column is defined).
     *
     * @return bool
     */
    public function softDelete(): bool
    {
        // the model can't be soft deleted if it doesn't even exist
        // the model can't be soft deleted if it has no column to store the soft deletion information
        // the model can't be soft deleted again
        if (!$this->exists() || !$this->hasSoftDeletedColumn() || $this->isSoftDeleted()) {
            return false;
        }

        $update = [$this->getSoftDeletedColumnName() => 1];
        $where = [$this->getKeyColumnName() => $this->getKey()];

        $success = 1 === $this->getConnection()->update($this->getTableName(), $update, $where);

        if ($success) {
            // update the soft deletion attribute as the record was successfully updated
            $this->setAttribute($this->getSoftDeletedColumnName(), 1);
        }

        return $success;
    }

    /**
     * Performs either a soft or hard delete depending on the current state of the model.
     * Soft delete if the model has a soft deleted column and isn't soft deleted yet.
     * Hard delete if the model has no soft deleted column or is already soft deleted.
     *
     * @return bool
     */
    public function delete(): bool
    {
        // the model can't be deleted if it doesn't even exist
        if (!$this->exists()) {
            return false;
        }

        // perform a soft delete if the model has a soft deleted column but isn't soft deleted yet
        if ($this->hasSoftDeletedColumn() && !$this->isSoftDeleted()) {
            return $this->softDelete();
        }

        // the model either has no soft deleted information or is already soft deleted
        return $this->hardDelete();
    }

    /**
     * Performs an insert or update of all fillable attributes depending if the model already exists or not.
     * The insert/update is only performed if at least one attribute changed (isDirty() return true).
     *
     * @return bool
     */
    public function store(): bool
    {
        // abort if nothing changed
        if (!$this->isDirty()) {
            return false;
        }

        // get all fillable attributes and their current values
        $fillableAttributes = $this->getFillableAttributes();

        // abort if no attribute is specified
        if (!\count($fillableAttributes)) {
            return false;
        }

        // update the last modified timestamp
        if ($this->hasUpdatedAtColumn()) {
            $fillableAttributes[$this->getUpdatedAtColumnName()] = \time();
        }

        // perform an insert if the model doesn't exist yet
        if (!$this->exists()) {
            // set the creation timestamp timestamp
            if ($this->hasCreatedAtColumn()) {
                $fillableAttributes[$this->getCreatedAtColumnName()] = \time();
            }

            // insert now
            $success = 1 === $this->getConnection()->insert($this->getTableName(), $fillableAttributes);

            if ($success) {
                // get the unique id of the newly inserted record
                $uniqueKey = $this->getConnection()->lastInsertId($this->getTableName(), $this->getKeyColumnName());
                $this->setAttribute($this->getKeyColumnName(), (int) $uniqueKey);

                if ($this->hasCreatedAtColumn()) {
                    // update the create at timestamp as the record was successfully inserted
                    $this->setAttribute($this->getCreatedAtColumnName(),
                        $fillableAttributes[$this->getCreatedAtColumnName()]);
                }
            }
        } else {
            // perform an update as the model already exists
            $where = [$this->getKeyColumnName() => $this->getKey()];
            $success = 1 === $this->getConnection()->update($this->getTableName(), $fillableAttributes, $where);
        }

        if ($success) {
            if ($this->hasUpdatedAtColumn()) {
                // update the last modified timestamp as the record was successfully inserted/updated
                $this->setAttribute($this->getUpdatedAtColumnName(),
                    $fillableAttributes[$this->getUpdatedAtColumnName()]);
            }

            // mask the record as existing and non-dirty as all changes were successfully applied
            $this->isDirty = false;
            $this->exists = true;
        }

        return $success;
    }

    /**
     * Returns all records which matches the specified key-value-pairs.
     * Soft deleted and hidden models are excluded.
     *
     * @param array  $where       collection of all column names and their values used as where statement to filter the
     *                            query the value may be an array to specify the expression operator (operator =>
     *                            value), default operator: equals
     * @param string $conjunction optional conjunction when using multiple key-value-pairs (default 'AND')
     * @param int    $offset      optional define an offset as starting index (default 0 = start at the first record)
     * @param int    $limit       optional limit the number of records (default -1 = no limitation)
     * @param string $sortBy      optional column to sort the results by (default 'sorting' column)
     * @param string $sortOrder   optional sort order, either ASC or DESC (default ASC)
     *
     * @return array|static[]
     */
    public static function findAllBy(array $where, string $conjunction = 'AND', $offset = 0, $limit = -1, $sortBy = 'sorting', $sortOrder = 'ASC'): array
    {
        // unify the conjunction string
        $conjunction = \mb_strtoupper(\trim($conjunction));

        // create a dummy model to access model properties
        $dummy = new static();
        $builder = $dummy->getQueryBuilder();

        $builder
            ->select('*')
            ->from($dummy->getTableName())
        ;

        $loopCounter = 0;

        // loop through all key-value-pairs / where statements
        foreach ($where as $columnName => $value) {
            if (\is_array($value)) {
                // custom operator is specified
                $operator = \mb_strtolower(\trim($value[0]));
                $realValue = $value[1];
            } else {
                // default operator will be used
                $operator = 'eq';
                $realValue = $value;
            }

            // quote strings
            if (\is_string($realValue)) {
                $realValue = $builder->quote($realValue);
            }

            $whereClause = $builder->expr()->$operator($columnName, $realValue);

            if (0 === $loopCounter) {
                $builder->where($whereClause);
            } elseif ('AND' === $conjunction) {
                $builder->andWhere($whereClause);
            } else {
                $builder->orWhere($whereClause);
            }

            ++$loopCounter;
        }

        if ($offset > 0) {
            $builder->setFirstResult($offset);
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        if ('' !== $sortBy) {
            $builder->orderBy($sortBy, $sortOrder);
        }

        // fetch all table rows matching the specified query and map them to an instance of this model
        $rows = $builder->execute()->fetchAll();

        return self::mapArraysToModels($rows);
    }

    /**
     * Returns a collection of all models.
     *
     * @return array|static[]
     */
    public static function getAll(): array
    {
        return self::findAllBy([]);
    }

    /**
     * Returns the first record found which matches the specified column-value combination or a new instance of nothing
     * was found.
     *
     * @param string $columnName lookup column name
     * @param mixed  $value      lookup value
     * @param string $operator   optional lookup operator, default equals (eq)
     *
     * @return static
     */
    public static function findBy(string $columnName, $value, string $operator = 'eq')
    {
        $where = [
            $columnName => [$operator, $value],
        ];

        $models = self::findAllBy($where, 'AND', 0, 1);

        return \count($models) ? $models[0] : new static();
    }

    /**
     * Returns the first record which matches the specified unique identifier.
     *
     * @param int $key the unique identifier to fetch a single record
     *
     * @return static
     */
    public static function find(int $key)
    {
        $dummy = new static();

        return self::findBy($dummy->getKeyColumnName(), $key);
    }

    /**
     * Injects the specified array into a new model.
     *
     * @param array $data
     *
     * @return static
     */
    public static function inject(array $data)
    {
        $model = self::mapArrayToModel($data);
        $model->exists = $model->getKey() > 0;

        return $model;
    }

    /**
     * Returns all files associated to the specified model's attribute / column name.
     *
     * @param string $columnName
     *
     * @return array|FileReference[]
     */
    public function resolveFiles(string $columnName): array
    {
        $files = $this->getFileRepository()->findByRelation($this->getTableName(), $columnName, $this->getKey());

        return \is_array($files) && \count($files) ? $files : [];
    }

    /**
     * Returns a single file associated to the specified model's attribute / column name or NULL if no file is
     * associated.
     *
     * @param string $columnName
     *
     * @return FileReference|null
     */
    public function resolveFile(string $columnName)
    {
        $files = $this->resolveFiles($columnName);

        return \count($files) ? $files[0] : null;
    }

    /**
     * Returns the record overlay of this model for the specified language index / uid.
     *
     * @param int $targetLanguageIndex
     *
     * @return static
     */
    public function translate(int $targetLanguageIndex)
    {
        // nothing to translate if the model has no translation index or it's the same
        if (!$this->hasLanguageIndexColumn() || $this->getLanguageIndex() === $targetLanguageIndex) {
            return $this;
        }

        $translatedRecord = $this->getPageRepository()
            ->getRecordOverlay($this->getTableName(), $this->toArray(), $targetLanguageIndex)
        ;

        return self::mapArrayToModel($translatedRecord);
    }

    /**
     * Maps the specified record as array to an instance of this model.
     *
     * @param array $record
     *
     * @return static
     */
    protected static function mapArrayToModel(array $record)
    {
        $model = new static();
        $model->attributes = $record;
        $model->exists = true;
        $model->isDirty = false;

        return $model;
    }

    /**
     * Maps multiple records specified as array to an instance of this model.
     *
     * @param array $records
     *
     * @return array
     */
    protected static function mapArraysToModels(array $records): array
    {
        $models = [];

        foreach ($records as $record) {
            $models[] = self::mapArrayToModel($record);
        }

        return $models;
    }

    /**
     * Applies the type case for the specified attribute name.
     *
     * @param string $attributeName the attribute's name to get the type case which should be applied
     * @param mixed  $value         the value which is casted to the type defined by the attribute
     *
     * @return mixed
     */
    private function cast(string $attributeName, $value)
    {
        $lowerCaseAttribute = (string) \mb_strtolower(\trim($attributeName));

        // some keys may have a special type cast method named 'cast{$attribute}'
        // example: protected function castUid($value)
        $methodName = 'cast'.\ucfirst($lowerCaseAttribute);

        if (\method_exists($this, $methodName)) {
            return $this->{$methodName}($value);
        }

        // check if a type cast for this attribute is defined in the $casts property
        // otherwise the unfiltered value is returned
        if (!\array_key_exists($lowerCaseAttribute, $this->casts)) {
            return $value;
        }

        // apply the type cast
        switch (\mb_strtolower($this->casts[$lowerCaseAttribute])) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'double':
            case 'float':
            case 'numeric':
            case 'decimal':
                return (float) $value;
            case 'array':
            case 'collection':
            case 'list':
                return (array) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
        }

        // type cast is specified but unknown (maybe a typo or something like 'object')
        return $value;
    }

    /**
     * Returns a collection of all fillable attributes and their current values.
     *
     * @return array
     */
    private function getFillableAttributes(): array
    {
        // return all attributes if the model doesn't have any fillable attributes specified
        if (!\count($this->fillables)) {
            return $this->toArray();
        }

        $fillableAttributes = [];

        // append all fillable attributes and their current values to the collection
        foreach ($this->fillables as $fillable) {
            $fillableAttributes[$fillable] = $this->getAttribute($fillable);
        }

        // append the unique identifier
        $fillableAttributes[$this->getKeyColumnName()] = $this->getKey();

        // append the parent information
        if ($this->hasParentColumn()) {
            $fillableAttributes[$this->getParentColumnName()] = $this->getParentKey();
        }

        // append the creation timestamp
        if ($this->hasCreatedAtColumn()) {
            $fillableAttributes[$this->getCreatedAtColumnName()] = $this->getCreatedAtTimestamp();
        }

        // append the last modified timestamp
        if ($this->hasUpdatedAtColumn()) {
            $fillableAttributes[$this->getUpdatedAtColumnName()] = $this->getUpdatedAtTimestamp();
        }

        // append the soft deleted state
        if ($this->hasSoftDeletedColumn()) {
            $fillableAttributes[$this->getSoftDeletedColumnName()] = $this->isSoftDeleted() ? 1 : 0;
        }

        return $fillableAttributes;
    }
}
