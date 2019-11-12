<?php

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);

$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

/* $orderId = 853;// Put your Order Id Here

\Magento\Store\Model\App\Emulation $appEmulation,
\Magento\Email\Model\TemplateFactory $emailFactory
$order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId); */
$emailFactory = $objectManager->create('\Magento\Email\Model\TemplateFactory');
$appEmulation = $objectManager->create('\Magento\Store\Model\App\Emulation');

$emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
$paymentHelper = $objectManager->create('\Magento\Payment\Helper\Data');
$addressRenderer = $objectManager->create('\Magento\Sales\Model\Order\Address\Renderer');
$templateModel = $objectManager->create('\Magento\Email\Model\Template');
$storeManager = $objectManager->create('Magento\Store\Model\StoreManagerInterface');
$storeId = $storeManager->getStore()->getId();
$config = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";
?>
    <form method="post">
        <label>Order Id</label>
        <input type="text" name="order_id" value="<?= isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : "" ?>"/>
        <select name='type'>
            <option <?= ($type == 'order') ? "selected" : "" ?>>order</option>
            <option <?= ($type == 'invoice') ? "selected" : "" ?> >invoice</option>
            <option <?= ($type == 'shipment') ? "selected" : "" ?>>shipment</option>
            <option <?= ($type == 'credit_memo') ? "selected" : "" ?>>credit_memo</option>
        </select>

        <input type="submit" value="submit"/>
    </form>
<?php

if ($_POST && isset($_REQUEST['order_id']) && isset($_REQUEST['type'])) {

    if (isset($_REQUEST['type'])) {

        $orderId = $_REQUEST['order_id'];// Put your Order Id Here
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
        $paymentHtml = $paymentHelper->getInfoBlockHtml($order->getPayment(), $order->getStore()->getStoreId());
        $formattedShippingAddress = $order->getIsVirtual() ? null : $addressRenderer->format($order->getShippingAddress(), 'html');
        $formattedBillingAddress = $addressRenderer->format($order->getBillingAddress(), 'html');

        $templateOption = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId];
        $transport = [];
        if ($_REQUEST['type'] == "order") {
            //$emailSender->send($order);
            $transport = [
                'order' => $order,
                'billing' => $order->getBillingAddress(),
                'payment_html' => $paymentHtml,
                'store' => $order->getStore(),
                'formattedShippingAddress' => $formattedShippingAddress,
                'formattedBillingAddress' => $formattedBillingAddress,
            ];
            $templateId = $config->getValue('sales_email/order/template');

        } else if ($_REQUEST['type'] == "invoice") {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $transport = [
                    'order' => $order,
                    'invoice' => $invoice,
                    'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
                    'billing' => $order->getBillingAddress(),
                    'payment_html' => $paymentHtml,
                    'store' => $order->getStore(),
                    'formattedShippingAddress' => $formattedShippingAddress,
                    'formattedBillingAddress' => $formattedBillingAddress
                ];
                $templateId = $config->getValue('sales_email/invoice/template');
            }
        } else if ($_REQUEST['type'] == "shipment") {
            foreach ($order->getShipmentsCollection() as $shipment) {
                $transport = [
                    'order' => $order,
                    'shipment' => $shipment,
                    'comment' => $shipment->getCustomerNoteNotify() ? $shipment->getCustomerNote() : '',
                    'billing' => $order->getBillingAddress(),
                    'payment_html' => $paymentHtml,
                    'store' => $order->getStore(),
                    'formattedShippingAddress' => $formattedShippingAddress,
                    'formattedBillingAddress' => $formattedBillingAddress
                ];
                $templateId = $config->getValue('sales_email/shipment/template');
            }
        } else if ($_REQUEST['type'] == "credit_memo") {
            foreach ($order->getCreditmemosCollection() as $creditmemo) {
                $transport = [
                    'order' => $order,
                    'creditmemo' => $creditmemo,
                    'comment' => $creditmemo->getCustomerNoteNotify() ? $creditmemo->getCustomerNote() : '',
                    'billing' => $order->getBillingAddress(),
                    'payment_html' => $paymentHtml,
                    'store' => $order->getStore(),
                    'formattedShippingAddress' => $formattedShippingAddress,
                    'formattedBillingAddress' => $formattedBillingAddress
                ];
                $templateId = $config->getValue('sales_email/creditmemo/template');
            }
        } else if ($_REQUEST['type'] == "reset_password") {
            foreach ($order->getCreditmemosCollection() as $creditmemo) {
                $accountManagement = $objectManager->create('Magento\Customer\Model\AccountManagement');
                $customerRepository = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
                $customerResourceModel = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer');
                $customerModel = $objectManager->create('Magento\Customer\Model\Customer');
                $mathRandom = $objectManager->create('Magento\Framework\Math\Random');

                $customerRegistry = $objectManager->create('Magento\Customer\Model\CustomerRegistry');

                $dataProcessor = $objectManager->create('Magento\Framework\Reflection\DataObjectProcessor');
                $customerViewHelper = $objectManager->create('Magento\Customer\Helper\View');

                $_customers = $objectManager->create('Magento\Customer\Api\AccountManagementInterface');
                $customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
                $customerCollection->load();
                $i = 0;
                /*foreach ($customerCollection as $customer) {*/


                try {
                    //$email = $customer->getData('email');
                    $email = "dev@magentoguys.com";
                    $websiteId = $storeManager->getStore()->getWebsiteId();

                    // load customer by email

                    $customer = $customerRepository->get($email, $websiteId);
                    $_customer = $customerModel->load(4);
                    $newPasswordToken = $mathRandom->getUniqueHash();
                    $customerResourceModel->changeResetPasswordLinkToken($_customer, $newPasswordToken);
                    $mergedCustomerData = $customerRegistry->retrieveSecureData($customer->getId());
                    $customerData = $dataProcessor->buildOutputDataArray($customer, \Magento\Customer\Api\Data\CustomerInterface::class);
                    $mergedCustomerData->addData($customerData);
                    $mergedCustomerData->setData('name', $customerViewHelper->getCustomerName($customer));
                    $customerEmailData = $mergedCustomerData;
                    $templateId = 11;
                    //$transport = new \Magento\Framework\DataObject($transport);
                    $templateVars = ['customer' => $customerEmailData, 'store' => $storeManager->getStore($storeId)];
                    $templateModel->load($templateId)->setVars($templateVars)->setOptions($templateOption);
                    $body = $templateModel->processTemplate();
                    echo $body;
                    exit();

                } catch (NoSuchEntityException $e) {
                    // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
                } catch (\Exception $exception) {
                    echo $exception->getMessage();
                    echo "<pre>";
                    print_r($exception->getTraceAsString());
                    echo __('We\'re unable to send the password reset email.');
                }
                /* echo "<pre>";
                    echo $i . " Email :-" . $email . " Mail Sent";
                    $i++;
                }*/


            }
        }
        if ($transport) {
            $transport = new \Magento\Framework\DataObject($transport);
            $templateVars = $transport->getData();
            $template = $emailFactory->create();
            $template->setTemplateText($template->getTemplateText());
            $template->setVars($templateVars);
            $template->setId($templateId);
            $template->setOptions($templateOption);
            $template->load($templateId);
            $template->emulateDesign($storeId);
            $appEmulation->startEnvironmentEmulation($storeId);
            $templateProcessed = $template->processTemplate();
            $appEmulation->stopEnvironmentEmulation();
            $template->revertDesign();
            echo $templateProcessed;
        } else {
            echo "Template cannot be generated";
        }

        exit();
    }
}


?>