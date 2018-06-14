<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Vault\Test\Unit\Model;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentToken;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Magento\Vault\Model\TokenDeleter;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Log\LoggerInterface;

class TokenDeleterTest extends \PHPUnit\Framework\TestCase
{
    private static $hash = '4ddf9ab5a15c76e23eb53856';

    /**
     * @var PaymentTokenManagementInterface|MockObject
     */
    private $paymentTokenManagement;

    /**
     * @var CommandManagerPoolInterface|MockObject
     */
    private $commandManagerPool;

    /**
     * @var StoreResolverInterface|MockObject
     */
    private $storeResolver;

    /**
     * @var PaymentTokenResourceModel|MockObject
     */
    private $resourceModel;

    /**
     * @var TokenDeleter
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->paymentTokenManagement = $this->getMockForAbstractClass(PaymentTokenManagementInterface::class);
        $this->commandManagerPool = $this->getMockForAbstractClass(CommandManagerPoolInterface::class);
        $this->storeResolver = $this->getMockForAbstractClass(StoreResolverInterface::class);

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->resourceModel = $this->getMockBuilder(PaymentTokenResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new TokenDeleter(
            $this->paymentTokenManagement,
            $this->commandManagerPool,
            $this->storeResolver,
            $logger,
            $this->resourceModel
        );
    }

    /**
     * Checks a case when Payment Token not found by the provided.
     *
     * @expectedException \Magento\Framework\Exception\NotFoundException
     * @expectedExceptionMessage The payment token by the provided hash not found.
     */
    public function testExecuteWithInvalidHash()
    {
        $this->paymentTokenManagement->method('getByPublicHash')
            ->with(self::$hash)
            ->willReturn(null);

        $this->commandManagerPool->expects(self::never())
            ->method('get');
        $this->model->execute(self::$hash);
    }

    /**
     * Checks a case when payment method is not supported.
     *
     * @expectedException \Magento\Framework\Exception\NotFoundException
     * @expectedExceptionMessage The "unknown" command executor isn't defined. Verify the executor and try again.
     */
    public function testExecuteWithUnsupportedPaymentMethod()
    {
        $paymentMethodCode = 'unknown';
        $paymentToken = $this->getPaymentToken($paymentMethodCode);
        $this->paymentTokenManagement->method('getByPublicHash')
            ->with(self::$hash)
            ->willReturn($paymentToken);
        $exceptionMessage = 'The "unknown" command executor isn\'t defined. Verify the executor and try again.';
        $this->commandManagerPool->method('get')
            ->with($paymentMethodCode)
            ->willThrowException(new NotFoundException(__($exceptionMessage)));

        $this->resourceModel->expects(self::never())
            ->method('delete');

        $this->model->execute(self::$hash);
    }

    /**
     * Checks a case when Payment Token can't delete from the database.
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Payment Token can't be deleted.
     */
    public function testExecuteWithFailedDelete()
    {
        $paymentMethodCode = 'vault_payment';
        $paymentToken = $this->getPaymentToken($paymentMethodCode);
        $this->paymentTokenManagement->method('getByPublicHash')
            ->with(self::$hash)
            ->willReturn($paymentToken);
        $executor = $this->getMockForAbstractClass(CommandManagerInterface::class);
        $this->commandManagerPool->method('get')
            ->with($paymentMethodCode)
            ->willReturn($executor);

        $this->resourceModel->method('delete')
            ->with($paymentToken)
            ->willThrowException(new \Exception('Something wrong.'));
        $executor->expects(self::never())
            ->method('executeByCode');

        $this->model->execute(self::$hash);
    }

    /**
     * Checks a case when Payment Token is successfully deleted.
     */
    public function testExecute()
    {
        $storeId = 1;
        $paymentMethodCode = 'vault_payment';
        $paymentToken = $this->getPaymentToken($paymentMethodCode);
        $this->paymentTokenManagement->method('getByPublicHash')
            ->with(self::$hash)
            ->willReturn($paymentToken);
        $executor = $this->getMockForAbstractClass(CommandManagerInterface::class);
        $this->commandManagerPool->method('get')
            ->with($paymentMethodCode)
            ->willReturn($executor);

        $this->resourceModel->method('delete')
            ->with($paymentToken);
        $this->storeResolver->method('getCurrentStoreId')
            ->willReturn($storeId);
        $executor->method('executeByCode')
            ->with(
                'delete',
                null,
                [
                    'storeId' => $storeId,
                    'paymentToken' => $paymentToken
                ]
            );

        $this->model->execute(self::$hash);
    }

    /**
     * Gets mock for payment token.
     *
     * @param string $paymentMethodCode
     * @return PaymentToken|MockObject
     */
    private function getPaymentToken(string $paymentMethodCode): PaymentToken
    {
        $paymentToken = $this->getMockBuilder(PaymentToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentToken->method('getPaymentMethodCode')
            ->willReturn($paymentMethodCode);

        return $paymentToken;
    }
}
