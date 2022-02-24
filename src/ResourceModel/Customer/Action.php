<?php
declare(strict_types=1);

namespace Synerise\Integration\ResourceModel\Customer;

use Magento\Catalog\Model\AbstractModel;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Validator\Factory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer Mass processing resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Action extends \Magento\Customer\Model\ResourceModel\Customer
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Entity type id values to save
     *
     * @var array
     */
    private $typeIdValuesToSave = [];

    /**
     * Customer constructor.
     *
     * @param Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param ScopeConfigInterface $scopeConfig
     * @param Factory $validatorFactory
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param AccountConfirmation|null $accountConfirmation
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $productMetadata,
        Factory $validatorFactory,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        array $data = [],
        ?AccountConfirmation $accountConfirmation = null
    ) {
        if (version_compare($productMetadata->getVersion(), '2.4', 'lt')) {
            parent::__construct(
                $context,
                $entitySnapshot,
                $entityRelationComposite,
                $scopeConfig,
                $validatorFactory,
                $dateTime,
                $storeManager,
                $data
            );
        } else {
            parent::__construct(
                $context,
                $entitySnapshot,
                $entityRelationComposite,
                $scopeConfig,
                $validatorFactory,
                $dateTime,
                $storeManager,
                $data,
                $accountConfirmation
            );
        }

        $this->setConnection($this->_resource->getConnection('customer'));
    }

    /**
     * Update attribute values for entity list per store
     *
     * @param array $entityIds
     * @param array $attrData
     * @return $this
     * @throws \Exception
     */
    public function updateAttributes($entityIds, $attrData)
    {
        $object = new DataObject();

        $this->getConnection()->beginTransaction();
        try {
            foreach ($attrData as $attrCode => $value) {
                $attribute = $this->getAttribute($attrCode);
                if (!$attribute->getAttributeId()) {
                    continue;
                }

                $i = 0;
                foreach ($entityIds as $entityId) {
                    $i++;
                    $object->setId($entityId);
                    $object->setEntityId($entityId);
                    // collect data for save
                    $this->_saveAttributeValue($object, $attribute, $value);
                    // save collected data every 1000 rows
                    if ($i % 1000 == 0) {
                        $this->_processAttributeValues();
                    }
                }
                $this->_processAttributeValues();
            }
            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Insert or Update attribute data
     *
     * @param AbstractModel $object
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return $this
     */
    protected function _saveAttributeValue($object, $attribute, $value)
    {
        $table = $attribute->getBackend()->getTable();
        $entityId = $this->resolveEntityId($object->getId());
        $data = $attribute->isStatic()
            ? new DataObject(
                [
                    $this->getLinkField() => $entityId,
                    $attribute->getAttributeCode() => $this->_prepareValueForSave($value, $attribute),
                ]
            )
            : new DataObject(
                [
                    'attribute_id' => $attribute->getAttributeId(),
                    $this->getLinkField() => $entityId,
                    'value' => $this->_prepareValueForSave($value, $attribute),
                ]
            );
        $bind = $this->_prepareDataForTable($data, $table);
        $this->_attributeValuesToSave[$table][] = $bind;
        return $this;
    }

    /**
     * Resolve entity id for current entity
     *
     * @param int $entityId
     *
     * @return int
     */
    protected function resolveEntityId($entityId)
    {
        if ($this->getIdFieldName() == $this->getLinkField()) {
            return $entityId;
        }
        $select = $this->getConnection()->select();
        $tableName = $this->_resource->getTableName('customer_entity');
        $select->from($tableName, [$this->getLinkField()])
            ->where('entity_id = ?', $entityId);
        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Update type id values
     *
     * @return $this
     */
    private function processTypeIdValues(): self
    {
        $connection = $this->getConnection();
        $table = $this->getTable('customer_entity');

        foreach ($this->typeIdValuesToSave as $typeId => $entityIds) {
            $connection->update(
                $table,
                ['type_id' => $typeId],
                ['entity_id IN (?)' => $entityIds]
            );
        }
        $this->typeIdValuesToSave = [];

        return $this;
    }
}
