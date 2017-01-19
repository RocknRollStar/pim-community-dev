<?php

namespace Akeneo\Component\StorageUtils\Exception;

/**
 * Exception and updater can throw when updating a property which is has an invalid type.
 * For example, when a scalar is provided instead of an array.
 *
 * Due to the nature of PHP, a property could only have three global types : scalar, array and object.
 *
 * In case of a scalar type, you should not raised this exception if the property has the wrong type
 * (boolean instead of string). Use InvalidPropertyException instead.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class InvalidPropertyTypeException extends ObjectUpdaterException
{
    const EXPECTED_CODE = 100;
    const SCALAR_EXPECTED_CODE = 101;
    const ARRAY_EXPECTED_CODE = 102;
    const VALID_ARRAY_STRUCTURE_EXPECTED_CODE = 103;

    /** @var string */
    protected $propertyName;

    /** @var mixed */
    protected $propertyValue;

    /**
     * @param string          $propertyName
     * @param mixed           $propertyValue
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($propertyName, $propertyValue, $message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->propertyName  = $propertyName;
        $this->propertyValue = $propertyValue;
    }

    /**
     * Build an exception when the data is not a scalar value.
     *
     * @param string $propertyName
     * @param string $action
     * @param string $type
     * @param mixed  $propertyValue a value which should be not a scalar (array, object, null)
     *
     * @return InvalidPropertyException
     */
    public static function scalarExpected($propertyName, $action, $type, $propertyValue)
    {
        $message = 'Property "%s" expects a scalar (for %s %s).';

        return new self(
            $propertyName,
            $propertyValue,
            sprintf($message, $action, $type),
            self::SCALAR_EXPECTED_CODE
        );
    }

    /**
     * Build an exception when the data is not an array value.
     *
     * @param string $propertyName
     * @param string $action
     * @param string $type
     * @param mixed  $propertyValue a value which should be not an array (scalar, object, null)
     *
     * @return InvalidPropertyException
     */
    public static function arrayExpected($propertyName, $action, $type, $propertyValue)
    {
        $message = 'Property "%s" expects an array (for %s %s).';

        return new self(
            $propertyName,
            $propertyValue,
            sprintf($message, $action, $type),
            self::ARRAY_EXPECTED_CODE
        );
    }

    /**
     * Build an exception when the data inside the array does not have the structure expected.
     * For example, when the array contains scalar values instead of array values.
     *
     * @param string $propertyName
     * @param string $because
     * @param string $action
     * @param string $type
     * @param array  $propertyValue
     *
     * @return InvalidPropertyException
     */
    public static function validArrayStructureExpected($propertyName, $because, $action, $type, array $propertyValue)
    {
        $message = 'Property "%s" expects a valid array, %s (for %s %s).';

        return new self(
            $propertyName,
            $propertyValue,
            sprintf($message, $because, $action, $type),
            self::ARRAY_EXPECTED_CODE
        );
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return string
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }
}
