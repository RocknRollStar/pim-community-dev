<?php
namespace Akeneo\CatalogBundle\Doctrine;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Entity type manager, a general doctrine implementation, not depends on storage (entity or document)
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright Copyright (c) 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
abstract class EntityTypeManager extends AbstractManager
{

    /**
    * Load embedded entity type
    *
    * @param string $code
    * @return EntityTypeManager
    */
    public function find($code)
    {
        // get entity type
        $type = $this->repository->findOneByCode($code);
        if ($type) {
            $this->object = $type;
            return $this;
        } else {
            return null;
        }
    }

     /**
     * Create an embeded type entity
     * @param string $code
     * @param string $title
     * @return EntityTypeManager
     */
    public function create($code, $title = null)
    {
        // check if exists
        $type = $this->repository->findOneByCode($code);
        if ($type) {
            // TODO create custom exception
            throw new \Exception("There is already an entity type {$this->class} with the code {$code}");
        } else {
            $this->object = new $this->class();
            $this->object->setCode($code);
            if (!$title) {
                $title = $code;
            }
            $this->object->setTitle($title);
        }
        return $this;
    }

    /**
     * Get type object code
     * @return string code
     */
    public function getCode()
    {
        return $this->getObject()->getCode();
    }

    /**
     * Get product type title
     * @return string
     */
    public function getTitle()
    {
        return $this->object->getTitle();
    }

    /**
     * Set product type title
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->object->setTitle($title);
    }

    /**
     * Add a group to a product type
     *
     * @param string $groupCode
     * @return EntityTypeManager
     */
    public abstract function addGroup($groupCode);

    /**
     * Get a group by code
     *
     * @param string $fieldGroup
     * @return mixed
     */
    public abstract function getGroup($groupCode);

    /**
     * Remove group by code
     *
     * @param $code
     */
    public abstract function removeGroup($groupCode);

    /**
     * Add a field to the type
     *
     * @param string $fieldCode
     * @param string $fieldType
     * @param string $groupCode
     * @return ProductType
     */
    public abstract function addField($fieldCode, $fieldType, $groupCode, $title = null);

    /**
     * Get field by code
     *
     * @param string $fieldCode
     * @return mixed
     */
     public abstract function getField($fieldCode);

    /**
     * Remove field from group
     *
     * @param $code
     */
    //public abstract function removeFieldFromType($fieldCode);

    /**
     * Remove field
     *
     * @param $code
     */
    public abstract function removeField($fieldCode);

    /**
     * Create and return flexible product of current type
     *
     * @return ProductManager
     */
     public abstract function newProductInstance();

}