<?php

namespace Codilar\Core\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Customer extends AbstractHelper
{
    const CUSTOMER_ASSOC_ATTR = 'customer_associated_with';
    const AUTHOR_OPTION_LABEL = 'Article Writer';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var customerFactory
     */
    protected $customerFactory;

    /**
     * @var collectionFactory
     */
    protected $collectionFactory;

    /**
     * @var eavAttributeRepository
     */
    protected $eavAttributeRepository;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Customer constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CustomerFactory $customerFactory
     * @param AttributeRepositoryInterface $eavAttributeRepository
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CustomerFactory $customerFactory,
        AttributeRepositoryInterface $eavAttributeRepository,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_customerFactory = $customerFactory;
        $this->eavAttributeRepository = $eavAttributeRepository;
        $this->_request = $context->getRequest();
        $this->_logger = $logger;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * @param $customAttributeCode
     * @return array
     */
    public function retrieveOptions($customAttributeCode)
    {
        try {
            $attributes = $this->eavAttributeRepository->get(
                CustomerModel::ENTITY,
                $customAttributeCode
            );
            return $attributes->getSource()->getAllOptions(false);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }

    /**
     * @param $arr
     * @param $field
     * @return bool
     */
    public function searchArrayVal($arr, $field)
    {
        try {
            foreach ($arr as $data) {
                if ($data['label'] == $field) {
                    return $data['value'];
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param $authorAttribute
     * @param $authorLabel
     * @return array|Collection
     */
    public function getCustomerList($authorAttribute, $authorLabel)
    {
        try {
            $attrOptions = $this->retrieveOptions($authorAttribute);
            $authorKey = $this->searchArrayVal($attrOptions, $authorLabel);
            if ($authorKey) {
                $collection = $this->_collectionFactory->create();
                $collection->addFieldToFilter(
                    [
                        ['attribute' => 'customer_associated_with', 'finset' => [$authorKey]]
                    ]
                );
            } else {
                $collection = [];
            }

            return $collection;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }

    /**
     * @param string $authorLabel
     * @param string $authorAttribute
     * @return array
     */
    public function getOptionsArray(
        $authorLabel = self::AUTHOR_OPTION_LABEL,
        $authorAttribute = self::CUSTOMER_ASSOC_ATTR
    ) {
        try {
            $authorList = [];
            $customers = $this->getCustomerList($authorAttribute, $authorLabel);
            $authorList[] = [
                'value' => '',
                'label' => __('Please Select %1', $authorLabel)
            ];
            if (count($customers) > 0) {
                foreach ($customers as $customer) {
                    $authorList[] = [
                        'value' => $customer['entity_id'],
                        'label' => $customer['firstname'] . ' ' . $customer['lastname']
                    ];
                }
            }

            return $authorList;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return [];
        }
    }
}
