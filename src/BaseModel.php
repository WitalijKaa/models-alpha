<?php

namespace ModelsAlpha;

use Carbon\Carbon;
use JsonSerializable;
use ModelsAlpha\Helpers\Collection;
use ModelsAlpha\Reflection\ReflectionCache;
use ModelsAlpha\Reflection\ReflectionDto;
use ModelsAlpha\Reflection\ReflectionProperty;

abstract class BaseModel implements JsonSerializable {

    protected ?string $jsonEncodeSmart = null;

    protected static array $collectionClasses = [Collection::class];
    protected static array $carbonClasses = [Carbon::class];

    protected static bool $careOriginal = true;
    protected static bool $careOriginalAsAttributes = true;
    protected array $originalPart = [];

    /** BASIC */

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

    public static final function fromJsonStr(string $json): static
    {
        return self::fromArray(json_decode($json, true));
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

            if (!$refProp = $ref->fields[$field] ?? null) {
                static::$careOriginal && static::careOriginalField($field, $value, $model);
                continue;
            }
            $handledFields[$field] = true;

            if (is_null($value)) {
                if ($refProp->allowsNull) {
                    $model->$field = null;
                }
                continue;
            }

            if ($refProp->isCollection) {
                $model->$field = new static::$collectionClasses[0]();
                if (empty($value) || !is_array($value)) {
                    continue;
                }
            }

            $class = $refProp->getCastClass();
            /** @var string|BaseModel|mixed $class */

            if ($refProp->isCollection && is_array($value)) {
                if ($onlyOneItemClass = $refProp->findOnlyOneClassOfCollection()) {
                    /** @var string|BaseModel|mixed $onlyOneItemClass */
                    foreach ($value as $subItem) {
                        $model->$field->add($refProp->isClassOfCollectionSubClassOfBaseModel($onlyOneItemClass) ? $onlyOneItemClass::fromArray($subItem) : new $onlyOneItemClass(...$value));
                    }
                } else {
                    foreach ($value as $subItem) {
                        $itemClass = $refProp->guessClassOfCollection($subItem, [static::class, 'getRef']);
                        /** @var string|BaseModel|mixed $itemClass */
                        $model->$field->add($refProp->isClassOfCollectionSubClassOfBaseModel($itemClass) ? $itemClass::fromArray($subItem) : new $itemClass(...$value));
                    }
                }
            }
            else if ($refProp->isCarbon and is_string($value)) {
                $refProp->makeCarbon($value, $field, $model);
            }
            else if ($class && is_array($value)) {
                $model->$field = $refProp->isClassForeign ? new $class(...$value) : $class::byArr($value);
            }
            else {
                $model->$field = $value;
            }
        }

        static::fillNullableFields($model, $handledFields);

        $model->afterFill();
        return $model;
    }

    private static function careOriginalField(string $field, mixed $value, BaseModel $model): void
    {
        if (array_key_exists($field, static::ref()->construct)) {
            return;
        }
        $model->originalPart[$field] = $value;
    }

    private static function makeWithConstructor(array $item): static
    {
        $construct = [];
        foreach (array_intersect_key(static::ref()->construct, $item) as $propName => $val) {
            $construct[$propName] = $item[$propName];
        }
        return $construct ? new static(...$construct) : new static();
    }

    private static function fillNullableFields(BaseModel $model, array $handledFields): void
    {
        foreach (static::ref()->fields as $field => $refProp) {
            if (!empty($model->$field) || array_key_exists($field, $handledFields)) {
                continue;
            }

            if ($refProp->isCollection) {
                $model->$field = new static::$collectionClasses[0]();
            } else if ($refProp->allowsNull && !isset($model->$field)) {
                $model->$field = null;
            }
        }
    }

    public function __clone(): void { static::makeByArray($this->toApi()); }

    /** to API */

    public function toApi(?string $smartName = null): array
    {
        if (empty($this->originalPart) || !static::$careOriginal) {
            return $this->toArray($smartName);
        }
        return array_merge($this->originalPart, $this->toArray($smartName));
    }

    public function toApiJsonStr(?string $smartName = null): string {
        return json_encode($this->toApi($smartName), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** to ARRAY */

    private function constructToArrayOnly(array &$return, ?array $fieldsArr): void
    {
        if (is_null($fieldsArr)) {
            foreach (static::ref()->construct as $propName => $refProp) {
                $return[$propName] = $this->$propName;
            }
        } else {
            foreach (static::ref()->construct as $propName => $refProp) {
                if (!array_key_exists($propName, $fieldsArr)) {
                    continue;
                }
                $return[$propName] = $this->$propName;
            }
        }
    }

    private function fieldsToArrayOnly(array &$return, array $fieldsArr, ?string $smartName): void
    {
        foreach ($fieldsArr as $field => $refProp) {

            if (!$refProp) {
                if ($this->hasAttribute($field)) {
                    $return[$field] = $this->$field;
                }
                continue;
            }
            /** @var $refProp \ModelsAlpha\Reflection\ReflectionProperty */

            if ($refProp->preventToArrayOnNull && $this->fieldIsNull($refProp)) {
                continue;
            }

            $jsonName = !empty($refProp->hardName) ? $refProp->hardName : $field;

            if (!isset($this->$field)) {
                $return[$jsonName] = $refProp->isCollection ? [] : null;
            }
            else if ($refProp->isCollection) {
                $return[$jsonName] = [];
                foreach ($this->$field as $item) {
                    /** @var $item \ModelsAlpha\BaseModel */
                    $return[$jsonName][] = $item->toArray($smartName);
                }
            }
            else if ($refProp->isCarbon) {
                $carbon = $this->$field;
                /** @var \Carbon\Carbon $carbon */
                $return[$jsonName] = $carbon->avoidMutation()
                    ->setTimezone($refProp->carbonParseTimeZone)
                    ->format(is_array($refProp->carbonParseFormat) ? $refProp->carbonParseFormat[0] : $refProp->carbonParseFormat);
            }
            else if (!empty($refProp->className) && !$refProp->isClassForeign) {
                $return[$jsonName] = $this->$field->toArray($smartName);
            }
            else {
                $return[$jsonName] = $this->$field;
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
        if ($this->hasAttribute($name)) {
            $method = static::ref()->attributes[$name];
            return $this->$method();
        } else if ($this->hasAttributeOriginal($name)) {
            return $this->originalPart[$name];
        }
        throw new \ErrorException('Undefined property: ' . static::class . ' ::$' . $name);
    }

    public function __isset(string $name): bool
    {
        return $this->hasAttribute($name) || $this->hasAttributeOriginal($name);
    }

    public final function hasAttribute(string $name): bool
    {
        static::prepareRef();
        return array_key_exists($name, static::ref()->attributes);
    }

    public final function hasAttributeOriginal(string $name): bool
    {
        return static::$careOriginalAsAttributes && array_key_exists($name, $this->originalPart);
    }

    /** SPECIFICs */

    protected function fieldIsNull(ReflectionProperty $refProp): bool
    {
        return is_null($this->{$refProp->name} ?? null) || ($refProp->isCollection && !$this->{$refProp->name}->count());
    }

    /** HELPERs */

    protected static function ref(?string $className = null): ReflectionDto
    {
        $className = $className ?? static::class;
        return ReflectionCache::$repo[$className];
    }

    protected static function prepareRef(?string $className = null): void
    {
        $className = $className ?? static::class;
        ReflectionCache::prepare($className);
    }

    public static function getRef(string $className): ?ReflectionDto
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
