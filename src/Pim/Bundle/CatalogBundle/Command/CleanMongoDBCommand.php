<?php

namespace Pim\Bundle\CatalogBundle\Command;

use Akeneo\Bundle\StorageUtilsBundle\DependencyInjection\AkeneoStorageUtilsExtension;
use Pim\Component\ReferenceData\Model\ConfigurationInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that removes obsolete relations and migrate normalizedData for MongoDB documents.
 *
 * @author    Remy Betus <remy.betus@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CleanMongoDBCommand extends ContainerAwareCommand
{
    /** @const string */
    const MONGODB_PRODUCT_COLLECTION = 'pim_catalog_product';

    /** @var array $familyIds list of family ID */
    protected $familyIds;

    /** @var array $categoryIds list of category ID */
    protected $categoryIds;

    /** @var array $attributes list of attribute (ID and code) */
    protected $attributes;

    /** @var array $optionIds list of option IDs */
    protected $optionIds;

    /** @var array $violations list of missing entities */
    protected $missingEntities;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:mongodb:clean')
            ->setDescription(
                'Cleans MongoDB documents: removes missing related entities and then fix normalizedData'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                "Do the checks, display errors but do not update any products."
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storageDriver = $this->getContainer()->getParameter('pim_catalog_product_storage_driver');

        if (AkeneoStorageUtilsExtension::DOCTRINE_MONGODB_ODM !== $storageDriver) {
            $output->writeln('<error>This command could be only launched on MongoDB storage</error>');

            return -1;
        }

        $this->findMissingEntities($output, $input->getOption('dry-run'));

        return 0;
    }

    /**
     * Finds missing entities (family, channel, attribute, option(s), reference data)
     *
     * @param OutputInterface $output
     * @param bool            $dryRun     if set to true, it won't fix MongoDB documents containing relations to deleted
     *                                    entities.
     */
    public function findMissingEntities(OutputInterface $output, $dryRun = false)
    {
        $db = $this->getMongoConnection();
        $productCollection = new \MongoCollection($db, self::MONGODB_PRODUCT_COLLECTION);

        $products = $productCollection->find([]);
        $output->writeln(
            sprintf(
                'Cleaning MongoDB documents for <comment>%s</comment> (<comment>%s</comment> entries).',
                self::MONGODB_PRODUCT_COLLECTION,
                $products->count()
            )
        );

        foreach ($products as $product) {
            $product = $this->checkFamily($product);
            $product = $this->checkCategories($product);
            $product = $this->checkValues($product);

            if (!$dryRun) {
                $productCollection->update(
                    ['_id' => new \MongoId($product['_id'])],
                    $product
                );
            }
        }
        $output->writeln('<comment>finished!</comment>');
    }

    /**
     * Checks entities related to product values and removes them if they no longer exist.
     *
     * Checked entities are:
     * - attributes
     * - attribute options
     * - reference data (assets included)
     *
     * @param array $product
     *
     * @return array the changes to perform on current MongoBD document to fix missing related entities.
     */
    public function checkValues(array $product)
    {
        foreach ($product['values'] as $valueIndex => $value) {
            $product = $this->checkAttribute($product, $valueIndex);

            if (!isset($product['values'][$valueIndex])) {
                continue;
            }

            if (isset($value['option'])) {
                $product = $this->checkAttributeOption($product, $valueIndex);
            }

            if (!isset($product['values'][$valueIndex])) {
                continue;
            }

            if (isset($value['optionIds'])) {
                $product = $this->checkAttributeOptions($product, $valueIndex);
            }

            if (!isset($product['values'][$valueIndex])) {
                continue;
            }

            $product = $this->checkReferenceDataFields($product, $valueIndex);
        }

        return $product;
    }

    /**
     * Checks if the reference data ID of a product values exists and removes it otherwise.
     *
     * @param array $product
     * @param int   $valueIndex
     *
     * @return bool
     */
    public function checkReferenceDataFields(array $product, $valueIndex)
    {
        $referenceDataFields = $this->getReferenceDataFields();
        foreach ($referenceDataFields as $name => $referenceData) {
            if (isset($product[$valueIndex][$referenceData])) {
                $product = $this->checkReferenceDataField($referenceData, $product, $valueIndex);
            }
        }

        return $product;
    }

    /**
     * Checks if a reference data exists.
     *
     * @param array   $referenceData configuration (name, class) of the reference data
     * @param array   $product
     * @param integer $valueIndex    index of the prodcut value
     *
     * @return bool whether the reference data exists or not.
     */
    public function checkReferenceDataField(array $referenceData, array $product, $valueIndex)
    {
        $referenceDataField = $product['values'][$valueIndex][$referenceData['field']];

        if (!is_array($referenceDataField)) {
            if (null !== $this->findEntity($referenceData['class'], $referenceDataField)) {
                $this->addMissingEntity($referenceData['class'], $referenceDataField);
            }

            unset($product['values'][$valueIndex][$referenceData['field']]);
        } else {
            foreach ($referenceDataField as $key => $referenceDataId) {
                if (null !== $this->findEntity($referenceData['class'], $referenceDataId)) {
                    $this->addMissingEntity($referenceData['class'], $referenceDataId);
                }

                unset($product['values'][$valueIndex][$referenceData['field']][$key]);
            }
        }
    }

    /**
     * Adds a missing entity in the list
     *
     * @param string $entityName
     * @param int    $id
     */
    protected function addMissingEntity($entityName, $id)
    {
        if (!isset($this->missingEntities[$entityName])) {
            $this->missingEntities[$entityName][$id] = 0;
        }

        $this->missingEntities[$entityName][$id]++;
    }

    /**
     * Finds an entity given its class and its ID
     *
     * @param string $entityClass
     * @param int    $id
     *
     * @return null|object
     */
    protected function findEntity($entityClass, $id)
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager')->find($entityClass, $id);
    }

    /**
     * Checks if the attribute options ID of a product values exists and removes it otherwise.
     *
     * @param array $product
     * @param int   $valueIndex
     *
     * @return bool
     */
    public function checkAttributeOptions(array $product, $valueIndex)
    {
        if (null === $this->optionIds) {
            $qb = $this->getContainer()->get('pim_catalog.repository.attribute_option')->createQueryBuilder('ao')
                ->select('ao.id');
            $results = $qb->getQuery()->getArrayResult();

            $this->optionIds = array_column($results, 'id');
        }

        $attributeId = $product['values'][$valueIndex]['attribute'];

        foreach ($product['values'][$valueIndex]['optionIds'] as $key => $optionId) {
            if (!in_array($optionId, $this->optionIds)) {
                $this->addMissingEntity(
                    $this->getContainer()->getParameter('pim_catalog.entity.attribute_option.class'),
                    sprintf(
                        'attribute %s > option %s',
                        isset($this->attributes[$attributeId]) ? $this->attributes[$attributeId]['code'] : 'unknown',
                        $optionId
                    )
                );

                unset($product['values'][$valueIndex]['optionIds'][$key]);
            }
        }

        return $product;
    }

    /**
     * Checks if the attribute option ID of a product values exists and removes it otherwise.
     *
     * @param array $product
     * @param int   $valueIndex
     *
     * @return bool
     */
    public function checkAttributeOption(array $product, $valueIndex)
    {
        if (null === $this->optionIds) {
            $qb = $this->getContainer()->get('pim_catalog.repository.attribute_option')->createQueryBuilder('ao')
                ->select('ao.id');
            $results = $qb->getQuery()->getArrayResult();

            $this->optionIds = array_column($results, 'id');
        }

        $optionId = $product['values'][$valueIndex]['option'];
        $attributeId = $product['values'][$valueIndex]['attribute'];

        if (!in_array($optionId, $this->optionIds)) {
            $this->addMissingEntity(
                $this->getContainer()->getParameter('pim_catalog.entity.attribute_option.class'),
                sprintf(
                    'attribute %s > option %s',
                    isset($this->attributes[$attributeId]) ? $this->attributes[$attributeId]['code'] : 'unknown',
                    $optionId
                )
            );

            unset($product['values'][$valueIndex]);
        }

        return $product;
    }

    /**
     * Checks if the attribute ID of a product values exists and removes it otherwise.
     *
     * @param array $product
     * @param int   $valueIndex
     *
     * @return array
     */
    public function checkAttribute(array $product, $valueIndex)
    {
        if (null === $this->attributes) {
            $qb = $this->getContainer()->get('doctrine.orm.entity_manager')->createQueryBuilder()
                ->select(['a.id', 'a.code'])
                ->from($this->getContainer()->getParameter('pim_catalog.entity.attribute.class'), 'a', 'a.id');

            $this->attributes = $qb->getQuery()->getArrayResult();
        }

        $attributeId = $product['values'][$valueIndex]['attribute'];

        if (!isset($this->attributes[$attributeId])) {
            $this->addMissingEntity(
                $this->getContainer()->getParameter('pim_catalog.entity.attribute.class'),
                $attributeId
            );

            unset($product['values'][$valueIndex]);
        }

        return $product;
    }

    /**
     * Checks if the category IDs exit and removes it from the product otherwise.
     *
     * @param array $product
     *
     * @return array
     */
    public function checkCategories(array $product)
    {
        if (!isset($product['categoryIds'])) {
            return $product;
        }

        if (null === $this->categoryIds) {
            $qb = $this->getContainer()->get('pim_catalog.repository.category')->createQueryBuilder('c')
                ->select('c.id');
            $results = $qb->getQuery()->getArrayResult();

            $this->categoryIds = array_column($results, 'id');
        }

        foreach ($product['categoryIds'] as $key => $categoryId) {
            if (!in_array($categoryId, $this->categoryIds)) {
                $this->addMissingEntity(
                    $this->getContainer()->getParameter('pim_catalog.entity.category.class'),
                    $categoryId
                );

                unset($product['categoryIds'][$key]);
            }
        }

        return $product;
    }

    /**
     * Checks if the family ID exists and removes it from the product otherwise. It also migrate "label" field that
     * was renamed between 1.5 and 1.6.
     *
     * @param array $product
     *
     * @return array
     */
    public function checkFamily(array $product)
    {
        if (!isset($product['family'])) {
            return $product;
        }

        if (isset($product['normalizedData']['family']['label'])) {
            $product['normalizedData']['family']['labels'] = $product['normalizedData']['family']['label'];
            unset($product['normalizedData']['family']['label']);
        }

        if (null === $this->familyIds) {
            $qb = $this->getContainer()->get('pim_catalog.repository.family')->createQueryBuilder('f')
                ->select('f.id');
            $results = $qb->getQuery()->getArrayResult();

            $this->familyIds = array_column($results, 'id');
        }

        if (in_array($product['family'], $this->familyIds)) {
            return $product;
        }

        $this->addMissingEntity(
            $this->getContainer()->getParameter('pim_catalog.entity.family.class'),
            $product['family']
        );

        unset($product['family']);
        unset($product['normalizedData']['family']);
        unset($product['normalizedData']['completenesses']);
        unset($product['completenesses']);

        return $product;
    }

    /**
     * Search in Doctrine mapping what is the field name defined for all the reference data.
     *
     * @throws \LogicException if any error of mapping for the reference data.
     *
     * @return array
     */
    protected function getReferenceDataFields()
    {
        $valueClass = $this->getContainer()->getParameter('pim_catalog.entity.product_value.class');
        $manager = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');

        $metadata = $manager->getClassMetadata($valueClass);
        $fields = [];
        foreach ($this->getReferenceDataConfiguration() as $referenceData) {
            $referenceDataName = $referenceData->getName();
            if (ConfigurationInterface::TYPE_MULTI === $referenceData->getType()) {
                $fieldName = $metadata->getFieldMapping($referenceDataName);

                if (!isset($fieldName['idsField'])) {
                    throw new \LogicException(
                        sprintf(
                            'No field name defined for reference data "%s"',
                            $referenceDataName
                        )
                    );
                }

                $idField = $fieldName['idsField'];
            } else {
                $idField = $referenceDataName;
            }

            $fields[$referenceDataName] = ['field' => $idField, 'class' => $referenceData->getClass()];
        }

        return $fields;
    }

    /**
     * Get configuration for reference data.
     *
     * @return \Pim\Component\ReferenceData\Model\ConfigurationInterface[]
     */
    protected function getReferenceDataConfiguration()
    {
        $referenceDataRegistry = $this->getContainer()->get('pim_reference_data.registry');

        return $referenceDataRegistry->all();
    }

    /**
     * Get MongoDB Connection
     *
     * @return \MongoDB the database
     */
    public function getMongoConnection()
    {
        $mongoConnection = $this->getContainer()->get('doctrine_mongodb.odm.default_connection');

        $dbName = $this->getContainer()->getParameter('mongodb_database');

        return $mongoConnection->getMongoClient()->$dbName;
    }
}
