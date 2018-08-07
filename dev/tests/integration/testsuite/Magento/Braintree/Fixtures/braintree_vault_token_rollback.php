<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;

$objectManager = Bootstrap::getObjectManager();

$hash = '4ddf9ab5a15c76e23eb53856';

/** @var PaymentTokenManagementInterface $tokenManagement */
$tokenManagement = $objectManager->get(PaymentTokenManagementInterface::class);
$paymentToken = $tokenManagement->getByPublicHash($hash, 0);
if ($paymentToken !== null) {
    /** @var PaymentTokenResourceModel $resourceModel */
    $resourceModel = $objectManager->get(PaymentTokenResourceModel::class);
    $resourceModel->delete($paymentToken);
}
