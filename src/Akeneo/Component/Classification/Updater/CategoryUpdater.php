<?php

namespace Akeneo\Component\Classification\Updater;

use Akeneo\Component\Classification\Model\CategoryInterface;
use Akeneo\Component\StorageUtils\Exception\InvalidObjectException;
use Akeneo\Component\StorageUtils\Exception\InvalidPropertyException;
use Akeneo\Component\StorageUtils\Exception\InvalidPropertyStructureException;
use Akeneo\Component\StorageUtils\Exception\InvalidPropertyTypeException;
use Akeneo\Component\StorageUtils\Exception\UnknownPropertyException;
use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Updates a category.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryUpdater implements ObjectUpdaterInterface
{
    /** @var PropertyAccessor */
    protected $accessor;

    /** @var IdentifiableObjectRepositoryInterface */
    protected $categoryRepository;

    /**
     * @param IdentifiableObjectRepositoryInterface $categoryRepository
     */
    public function __construct(IdentifiableObjectRepositoryInterface $categoryRepository)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function update($category, array $data, array $options = [])
    {
        if (!$category instanceof CategoryInterface) {
            throw InvalidObjectException::objectExpected(
                ClassUtils::getClass($category),
                'Akeneo\Component\Classification\Model\CategoryInterface'
            );
        }

        foreach ($data as $field => $value) {
            $this->validateDatatype($field, $value);
            $this->setData($category, $field, $value);
        }

        return $this;
    }

    /**
     * Validate the input data type.
     *
     * @param string            $field
     * @param mixed             $data
     *
     * @throws UnknownPropertyException
     */
    protected function validateDatatype($field, $data)
    {
        if ('label' === $field) {
            if(!is_array($field)) {
                throw InvalidPropertyTypeException::arrayExpected(
                    'label',
                    'update',
                    'category',
                    $field
                );
            }

            foreach ($data as $localeCode => $label) {
                if (null !== $label && !is_scalar($label)) {
                    throw InvalidPropertyTypeException::validArrayStructureExpected(
                        'label',
                        'A label is not a scalar',
                        'update',
                        'category',
                        $field
                    );
                }
            }
        } elseif ('code' === $field) {
            if (!is_scalar($field)) {
                throw InvalidPropertyTypeException::scalarExpected(
                    'code',
                    'update',
                    'category',
                    $field
                );
            }
        } elseif ('parent' === $field) {
            if (!is_scalar($field)) {
                throw InvalidPropertyTypeException::scalarExpected(
                    'parent',
                    'update',
                    'category',
                    $field
                );
            }
        } else {
            throw UnknownPropertyException::unknownProperty($field);
        }
    }

    /**
     * @param CategoryInterface $category
     * @param string            $field
     * @param mixed             $data
     *
     * @throws InvalidPropertyException
     * @throws UnknownPropertyException
     */
    protected function setData(CategoryInterface $category, $field, $data)
    {
        if ('labels' === $field) {
            foreach ($data as $localeCode => $label) {
                $category->setLocale($localeCode);
            }
        } elseif ('parent' === $field) {
            $this->updateParent($category, $data);
        } else {
            try {
                $this->accessor->setValue($category, $field, $data);
            } catch (NoSuchPropertyException $e) {
                throw UnknownPropertyException::unknownProperty($field, $e);
            }
        }
    }

    /**
     * @param CategoryInterface $category
     * @param string            $data
     *
     * @throws InvalidPropertyException
     */
    protected function updateParent(CategoryInterface $category, $data) {
        if (null === $data || '' === $data) {
            $category->setParent(null);

            return;
        }

        $categoryParent = $this->categoryRepository->findOneByIdentifier($data);
        if (null === $categoryParent) {
            throw InvalidPropertyException::validEntityCodeExpected(
                'parent',
                'category code',
                'The category does not exist',
                'updater',
                'category',
                $data
            );
        }

        $category->setParent($categoryParent);
    }
}
