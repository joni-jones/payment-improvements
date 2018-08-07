<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Vault\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\TokenDeleterInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Psr\Log\LoggerInterface;

/**
 * Base implementation of service to delete Payment Token from the database and
 * on the Payment Gateway side.
 */
class TokenDeleter implements TokenDeleterInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var CommandManagerPoolInterface
     */
    private $commandManagerPool;

    /**
     * @var StoreResolverInterface
     */
    private $storeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentTokenResourceModel
     */
    private $resourceModel;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param StoreResolverInterface $storeResolver
     * @param LoggerInterface $logger
     * @param PaymentTokenResourceModel $resourceModel
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        CommandManagerPoolInterface $commandManagerPool,
        StoreResolverInterface $storeResolver,
        LoggerInterface $logger,
        PaymentTokenResourceModel $resourceModel
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->commandManagerPool = $commandManagerPool;
        $this->storeResolver = $storeResolver;
        $this->logger = $logger;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $publicHash): void
    {
        $paymentToken = $this->paymentTokenManagement->getByPublicHash($publicHash, 0);
        if ($paymentToken === null) {
            throw new NotFoundException(__('The payment token by the provided hash not found.'));
        }

        $commandExecutor = $this->commandManagerPool->get($paymentToken->getPaymentMethodCode());

        try {
            $this->resourceModel->delete($paymentToken);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(__('Payment Token can\'t be deleted.'));
        }

        try {
            $commandExecutor->executeByCode(
                'delete_token',
                null,
                [
                    'paymentToken' => $paymentToken,
                    'storeId' => (int)$this->storeResolver->getCurrentStoreId()
                ]
            );
        } catch (CommandException | NotFoundException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
