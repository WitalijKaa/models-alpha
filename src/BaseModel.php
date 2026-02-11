<?php

namespace ModelsAlpha;

use Carbon\Carbon;
use JsonSerializable;
use ModelsAlpha\Helpers\Collection;
use ModelsAlpha\Reflection\ReflectionCache;
use ModelsAlpha\Reflection\SmartReflectionDto;

abstract class BaseModel implements JsonSerializable {

    protected ?string $jsonEncodeSmart = null;

    protected static array $collectionClasses = [Collection::class];
    protected static array $carbonClasses = [Carbon::class];

    public final function parseArray(array $json): static
    {
        static::prepareRef();
        self::makeByArray($json, $this);
        return $this;
    }

    public static final function fromArray(array $json): static
    {
        static::prepareRef();
        return self::makeByArray($json);
    }

    protected function beforeFill(array &$item): void
    {
    }

    protected function afterFill(): void
    {
    }

    /** to MODEL */

    private static function makeByArray(array $item, ?BaseModel $model = null): static
    {
        $ref = static::ref();
        $model = empty($model) ? static::makeWithConstructor($item) : $model;
        $model->beforeFill($item);
        $handledFields = [];

        foreach ($item as $field => $value) {
            $field = $ref->hardNames[$field] ?? $field;

            if (!$smartProp = $ref->fields[$field] ?? null) {
                continue;
            }
            $handledFields[$field] = true;

            if (is_null($value)) {
                if ($smartProp->allowsNull) {
                    $model->$field = null;
                }
                continue;
            }

            if ($smartProp->isCollection) {
                $model->$field = new static::$collectionClasses[0]();
                if (empty($value) || !is_array($value)) {
                    continue;
                }
            }

            $class = $smartProp->getCastClass();
            /** @var string|BaseModel|mixed $class */

            if ($smartProp->isCollection && is_array($value)) {
                if ($onlyOneItemClass = $smartProp->findOnlyOneClassOfCollection()) {
                    /** @var string|BaseModel|mixed $onlyOneItemClass */
                    foreach ($value as $subItem) {
                        $model->$field->add($smartProp->isClassOfCollectionSubClassOfBaseModel($onlyOneItemClass) ? $onlyOneItemClass::fromArray($subItem) : new $onlyOneItemClass(...$value));
                    }
                } else {
                    foreach ($value as $subItem) {
                        $itemClass = $smartProp->guessClassOfCollection($subItem, [static::class, 'getRef']);
                        /** @var string|BaseModel|mixed $itemClass */
                        $model->$field->add($smartProp->isClassOfCollectionSubClassOfBaseModel($itemClass) ? $itemClass::fromArray($subItem) : new $itemClass(...$value));
                    }
                }
            }
            else if ($smartProp->isCarbon and is_string($value)) {
                $smartProp->makeCarbon($value, $field, $model);
            }
            else if ($class && is_array($value)) {
                $model->$field = $smartProp->isClassForeign ? new $class(...$value) : $class::byArr($value);
            }
            else {
                $model->$field = $value;
            }
        }

        static::fillNullableFields($model, $handledFields);

        $model->afterFill();
        return $model;
    }

    private static function makeWithConstructor(array $item): static
    {
        $construct = [];
        foreach (array_intersect_key(static::ref()->construct, $item) as $key => $val) {
            $construct[$key] = $item[$key];
        }
        return $construct ? new static(...$construct) : new static();
    }

    private static function fillNullableFields(BaseModel $model, array $handledFields): void
    {
        foreach (static::ref()->fields as $field => $smartProp) {
            if (array_key_exists($field, $handledFields) || !empty($model->$field)) {
                continue;
            }

            if ($smartProp->isCollection) {
                $model->$field = new static::$collectionClasses[0]();
            } else if ($smartProp->allowsNull && !isset($model->$field)) {
                $model->$field = null;
            }
        }
    }

    public function __clone(): void { static::makeByArray($this->toArray()); }

    /** to ARRAY */

    private function constructToArrayOnly(array &$return, ?array $fieldsArr): void
    {
        foreach (static::ref()->construct as $propName => $smartProp) {
            if (!is_null($fieldsArr) && !array_key_exists($propName, $fieldsArr)) {
                continue;
            }
            $return[$propName] = $this->$propName;
        }
    }

    private function fieldsToArrayOnly(array &$return, array $fieldsArr, ?string $smartName): void
    {
        foreach ($fieldsArr as $fieldName => $smartField) {

            if (!$smartField) {
                if ($this->hasAttribute($fieldName)) {
                    $return[$fieldName] = $this->$fieldName;
                }
                continue;
            }
            /** @var $smartField \ModelsAlpha\Reflection\SmartReflectionProperty */

            if ($smartField->preventToArrayOnNull &&
                (is_null($this->$fieldName ?? null) || ($smartField->isCollection && !$this->$fieldName->count()))
            ) {
                continue;
            }

            $jsonName = !empty($smartField->hardName) ? $smartField->hardName : $fieldName;

            if (!isset($this->$fieldName)) {
                $return[$jsonName] = $smartField->isCollection ? [] : null;
            }
            else if ($smartField->isCollection) {
                $return[$jsonName] = [];
                foreach ($this->$fieldName as $item) {
                    /** @var $item \ModelsAlpha\BaseModel */
                    $return[$jsonName][] = $item->toArray($smartName);
                }
            }
            else if ($smartField->isCarbon) {
                $carbon = $this->$fieldName;
                /** @var \Carbon\Carbon $carbon */
                $return[$jsonName] = $carbon->avoidMutation()
                    ->setTimezone($smartField->carbonParseTimeZone)
                    ->format(is_array($smartField->carbonParseFormat) ? $smartField->carbonParseFormat[0] : $smartField->carbonParseFormat);
            }
            else if (!empty($smartField->className)) {
                $return[$jsonName] = $this->$fieldName->toArray($smartName);
            }
            else {
                $return[$jsonName] = $this->$fieldName;
            }
        }
    }

    public final function toArray(?string $smartName = null): array
    {
        static::prepareRef();
        $ref = static::ref();
        $return = [];

        $smartOnly = null;
        if (!is_null($smartName) && !is_null($smartOnly = $ref->smartArrays[ucfirst($smartName)] ?? null)) {
            $smartOnly = array_flip($smartOnly);
        }

        $this->constructToArrayOnly($return, $smartOnly);

        if (!is_null($smartOnly)) {
            $fieldsArr = $smartOnly;
            foreach ($fieldsArr as $fieldName => $someInt) {
                $fieldsArr[$fieldName] = $ref->fields[$fieldName] ?? false;
            }
        } else {
            $fieldsArr = $ref->fields;
        }

        $this->fieldsToArrayOnly($return, $fieldsArr, $smartName);

        return $return;
    }

    public final function only(array $only, ?string $smartName = null): array
    {
        static::prepareRef();
        $ref = static::ref();
        $return = [];

        $only = array_flip($only);
        foreach ($only as $fieldName => $someInt) {
            $only[$fieldName] = $ref->fields[$fieldName] ?? false;
        }

        $this->constructToArrayOnly($return, $only);
        $this->fieldsToArrayOnly($return, $only, $smartName);
        return $return;
    }

    public function jsonSerialize(): mixed { return $this->toArray($this->jsonEncodeSmart); }

    public function toJsonStr(?string $smartName = null): string {
        return json_encode($this->toArray($smartName), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** ATTRIBUTES */

    public function __get(string $name)
    {
        static::prepareRef();
        if ($this->hasAttribute($name)) {
            $method = static::ref()->attributes[$name];
            return $this->$method();
        }
        throw new \ErrorException('Undefined property: ' . static::class . ' ::$' . $name);
    }

    public function __isset(string $name): bool
    {
        static::prepareRef();
        return $this->hasAttribute($name);
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, static::ref()->attributes);
    }

    /** HELPERs */

    protected static function ref(?string $className = null): SmartReflectionDto
    {
        $className = $className ?? static::class;
        return ReflectionCache::$repo[$className];
    }

    protected static function prepareRef(?string $className = null): void
    {
        $className = $className ?? static::class;
        ReflectionCache::prepare($className);
    }

    public static function getRef(string $className): ?SmartReflectionDto
    {
        static::prepareRef($className);
        return static::ref($className);
    }

    protected static array $collectionClassesCache;
    public static function isClassCollection(string $className): bool
    {
        if (empty(static::$collectionClassesCache)) {
            static::$collectionClassesCache = array_flip(static::$collectionClasses);
        }
        return array_key_exists($className, static::$collectionClassesCache);
    }

    protected static array $carbonClassesCache;
    public static function isClassCarbon(string $className): bool
    {
        if (empty(static::$carbonClassesCache)) {
            static::$carbonClassesCache = array_flip(static::$carbonClasses);
        }
        return array_key_exists($className, static::$carbonClassesCache);
    }
}
