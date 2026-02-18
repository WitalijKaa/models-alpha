<?php

namespace ModelsAlpha\Reflection;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\Attributes\TimeFormats\AbstractTimeFormat;
use ModelsAlpha\Attributes\TimeFormats\YmdTimeFormat;
use ModelsAlpha\Attributes\TimeZones\AbstractTimeZone;
use ModelsAlpha\Attributes\TimeZones\UtcTimeZone;
use ModelsAlpha\BaseModel;

class ReflectionProperty
{
    private const int LOGIC_MISS_SINGLE_PROP = -4;
    private const int LOGIC_MISS_ALL_PROPS = -10000000;

    public bool $allowsNull = false;

    private const string DEFAULT_TIME_ZONE_ATTR = UtcTimeZone::class;

    public string $name;
    public ?string $hardName = null;
    public bool $preventToArrayOnNull = false;
    public bool $isClassForeign;
    public string $className;

    public bool $isCollection = false;
    public array $classesOfCollection; // keys are class names and values are $isClassForeign

    public bool $isCarbon = false;
    public string|array $carbonParseFormat;
    public string $carbonParseTimeZone;

    public function __construct(public \ReflectionProperty $refProp)
    {
        $modelClass = $this->refProp->class;
        /** @var \ModelsAlpha\BaseModel $modelClass */
        $this->name = $this->refProp->name;
        $refType = $this->refProp->getType();

        $refAttrs = [];
        foreach ($refProp->getAttributes() as $attr) {
            if (HardName::class == $attr->getName()) {
                $this->hardName = $attr->getArguments()[0];
            } else if (PreventToArrayOnNull::class == $attr->getName()) {
                $this->preventToArrayOnNull = true;
            } else {
                $refAttrs[] = $attr;
            }
        }

        if ($refType instanceof \ReflectionNamedType) {
            $this->allowsNull = $refType->allowsNull();

            if ($refType->isBuiltin()) {
                return;
            }

            if ($modelClass::isClassCollection($refType->getName()) && $refAttrs) {

                if ($this->allowsNull) {
                    throw new \UnexpectedValueException($this->refProp->class . ' # ' . $this->refProp->name . ' collection should not be nullable type');
                }

                $this->isCollection = true;
                foreach ($refAttrs as $refAttr) {
                    $isForeignClass = !is_subclass_of($refAttr->getName(), BaseModel::class);
                    if ($isForeignClass && !(new \ReflectionClass($refAttr->getName()))->hasMethod('toArray')) {
                        continue;
                    }
                    $this->classesOfCollection[$refAttr->getName()] = $isForeignClass;
                }
            }
            else if ($modelClass::isClassCarbon($refType->getName()) && $refAttrs) {
                $this->isCarbon = true;

                $classes = $this->findSubClassesFromReflectionAttributes($refAttrs, [
                    'classFormat' => AbstractTimeFormat::class,
                    'classTimeZone' => AbstractTimeZone::class,
                ]);
                extract($classes);

                if (empty($classFormat)) {
                    throw new \UnexpectedValueException($this->refProp->class . ' # ' . $this->refProp->name . ' empty $classFormat');
                }

                /** @var $classFormat \ModelsAlpha\Attributes\TimeFormats\AbstractTimeFormat */
                $this->carbonParseFormat = $classFormat::invoke();

                $classTimeZone = empty($classTimeZone) ? self::DEFAULT_TIME_ZONE_ATTR : $classTimeZone;
                /** @var $classTimeZone \ModelsAlpha\Attributes\TimeZones\AbstractTimeZone */
                $this->carbonParseTimeZone = $classTimeZone::invoke();
            }
            else {
                $this->className = $refType->getName();
                $this->isClassForeign = !is_subclass_of($this->className, BaseModel::class);
            }
        } else if ($refType instanceof \ReflectionUnionType || $refType instanceof \ReflectionIntersectionType) {
            foreach ($refType->getTypes() as $refSubType) {
                if (!method_exists($refSubType, 'allowsNull')) {
                    continue;
                }

                if ($refSubType->allowsNull()) {
                    $this->allowsNull = true;
                    return;
                }
            }
        }
    }

    private function hasAttribute(array $refAttrs, string $attr): bool
    {
        return array_any($refAttrs, fn($refAttr) => $refAttr->getName() == $attr);
    }

    private function findAttribute(array $refAttrs, string $attr): ?\ReflectionAttribute
    {
        return array_find($refAttrs, fn($refAttr) => $refAttr->getName() == $attr);
    }

    private function findSubClassesFromReflectionAttributes(array $refAttrs, array $config): array
    {
        $return = [];
        foreach ($refAttrs as $refAttr)
        {
            /** @var \ReflectionAttribute $refAttr */
            foreach ($config as $returnProperty => $baseClass) {

                if (empty($return[$returnProperty]) && is_subclass_of($refAttr->getName(), $baseClass)) {
                    $return[$returnProperty] = $refAttr->getName();
                    continue 2;
                }
            }
        }
        return $return;
    }

    public function getCastClass(): ?string
    {
        return (!empty($this->className) && !$this->isCollection) ? $this->className : null;
    }

    public function findOnlyOneClassOfCollection(): ?string
    {
        if (empty($this->classesOfCollection) || count($this->classesOfCollection) > 1) {
            return null;
        }
        return array_key_first($this->classesOfCollection);
    }

    public function guessClassOfCollection(array $item, callable $getRef): string
    {
        if (empty($this->classesOfCollection) || count($this->classesOfCollection) < 2) {
            throw new \Exception('guessClassOfCollection Exception');
        }
        $guess = [];
        $onlyOneForeignClass = null;
        foreach ($this->classesOfCollection as $className => $notSubClassOfBaseModel) {
            if (false == $notSubClassOfBaseModel) {
                $ref = $getRef($className);
                /** @var $ref \ModelsAlpha\Reflection\ReflectionDto */
                $guess[$className] = $this->countKeysDiff($item, $ref->fields);
            }
            else {
                if (is_null($onlyOneForeignClass)) {
                    $onlyOneForeignClass = $className;
                } else {
                    $onlyOneForeignClass = false;
                }
            }
        }
        return $this->findMaxClass($guess, $onlyOneForeignClass);
    }

    private function countKeysDiff(array $item, array $fields): int
    {
        $count = [
            'has' => 0,
            'miss' => 0,
            'result' => 0,
        ];
        foreach ($item as $key => $val) {
            isset($fields[$key]) ? $count['has']++ : $count['miss'] = $count['miss'] += self::LOGIC_MISS_SINGLE_PROP;
        }
        if (!$count['has']) {
            $count['result'] += self::LOGIC_MISS_ALL_PROPS;
        }
        $count['result'] += $count['has'] + $count['miss'];
        return $count['result'];
    }

    private function findMaxClass(array $guess, null|string|false $onlyOneForeignClass): string
    {
        $maxClass = null;
        $maxRate = PHP_INT_MIN;
        $hasAtLeastOneField = false;
        foreach ($guess as $class => $rate) {
            if ($rate > $maxRate) {
                $maxRate = $rate;
                $maxClass = $class;
            }
            if ($rate > self::LOGIC_MISS_ALL_PROPS) {
                $hasAtLeastOneField = true;
            }
        }
        if (!$hasAtLeastOneField && $onlyOneForeignClass) {
            return $onlyOneForeignClass;
        }
        return $maxClass;
    }

    public function isClassOfCollectionSubClassOfBaseModel(string $className): bool
    {
        return !$this->classesOfCollection[$className];
    }

    public function makeCarbon(string $value, string $field, BaseModel $model): void
    {
        $carbonFormats = is_array($this->carbonParseFormat) ? $this->carbonParseFormat : [$this->carbonParseFormat];

        foreach ($carbonFormats as $ix => $carbonFormat) {
            $isLastVariant = count($carbonFormats) == $ix + 1;
            try {
                $model->$field = Carbon::createFromFormat($carbonFormat, $value, $this->carbonParseTimeZone);
                break;
            } catch (InvalidFormatException $ex) {
                if ($isLastVariant) {
                    throw $ex;
                }
            }
        }
        //$model->$field->setTimezone($this->carbonParseTimeZone);

        if (YmdTimeFormat::invoke() === $carbonFormat) {
            $model->$field->setTime(0, 0, 0, 0);
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}