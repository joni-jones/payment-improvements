<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Braintree\Model;

use Braintree\Result\Successful;
use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Braintree\Model\Adapter\BraintreeAdapterFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\TokenDeleter;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class TokenDeleterTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ObjectManager|MockObject
     */
    private $stubObjectManager;

    /**
     * @var TokenDeleter
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->create(TokenDeleter::class);

        $this->stubObjectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapterFactory = new BraintreeAdapterFactory(
            $this->stubObjectManager,
            $this->objectManager->get(Config::class)
        );

        $this->objectManager->addSharedInstance($adapterFactory, BraintreeAdapterFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->objectManager->removeSharedInstance(BraintreeAdapterFactory::class);
    }

    /**
     * @magentoDataFixture Magento/Braintree/Fixtures/braintree_vault_token.php
     */
    public function testExecute()
    {
        $publicHash = '4ddf9ab5a15c76e23eb53856';

        /** @var BraintreeAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(BraintreeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stubObjectManager->method('create')
            ->willReturn($adapter);

        $result = new Successful();
        $adapter->method('deleteToken')
            ->with('mx29vk')
            ->willReturn($result);

        $this->model->execute($publicHash);

        $token = $this->getToken($publicHash);
        self::assertNull($token);
    }

    /**
     * Gets payment token.
     *
     * @param string $publicHash
     * @return PaymentTokenInterface|null
     */
    private function getToken(string $publicHash): ?PaymentTokenInterface
    {
        /** @var PaymentTokenManagementInterface $tokenManagement */
        $tokenManagement = $this->objectManager->get(PaymentTokenManagementInterface::class);
        return $tokenManagement->getByPublicHash($publicHash, 0);
    }
}
