<?php
namespace Mageseller\Emailpreview\Controller\Adminhtml\Order;
use \Magento\Framework\App\Config\ScopeConfigInterface;
class Preview extends \Magento\Backend\App\Action
{
	protected $_emailFactory;
    protected $_emulation;
    protected $_orderSender;
    protected $_paymentHelper;
    protected $_addressRenderer;
    protected $_template;
    protected $_storeManager;
 	protected $_orderFactory;
    protected $_invoiceFactory;
 	protected $_config;
    public function __construct(
	 	\Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Email\Model\TemplateFactory $emailFactory,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Email\Model\Template $template,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory,
        ScopeConfigInterface $config
    ) {
        parent::__construct($context);
		$this->_emailFactory = $emailFactory;
        $this->_emulation = $emulation;
        $this->_orderSender = $orderSender;
        $this->_paymentHelper = $paymentHelper;
        $this->_addressRenderer = $addressRenderer;
        $this->_template = $template;
        $this->_storeManager = $storeManager;
        $this->_orderFactory = $orderFactory;
        $this->_invoiceFactory = $invoiceFactory;
        $this->_config = $config;
    }
    /**
     * Hello test controller page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $transport = [];
        $templateOption = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId];
        if($orderId = $this->getRequest()->getParam('order_id')){
            $order = $this->_orderFactory->create()->load($orderId);
            $paymentHtml = $this->_paymentHelper->getInfoBlockHtml($order->getPayment(), $order->getStore()->getStoreId());
            $formattedShippingAddress = $order->getIsVirtual() ? null : $this->_addressRenderer->format($order->getShippingAddress(), 'html');
            $formattedBillingAddress = $this->_addressRenderer->format($order->getBillingAddress(), 'html');
            $transport = [
                'order' => $order,
                'billing' => $order->getBillingAddress(),
                'payment_html' => $paymentHtml,
                'store' => $order->getStore(),
                'formattedShippingAddress' => $formattedShippingAddress,
                'formattedBillingAddress' => $formattedBillingAddress,
            ];
            $templateId = $this->_config->getValue('sales_email/order/template');
        }
        if($invoiceId = $this->getRequest()->getParam('invoice_id')){
            $invoice = $this->_invoiceFactory->create()->load($invoiceId);
            $order = $invoice->getOrder();
            $paymentHtml = $this->_paymentHelper->getInfoBlockHtml($order->getPayment(), $order->getStore()->getStoreId());
            $formattedShippingAddress = $order->getIsVirtual() ? null : $this->_addressRenderer->format($order->getShippingAddress(), 'html');
            $formattedBillingAddress = $this->_addressRenderer->format($order->getBillingAddress(), 'html');
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
            $templateId = $this->_config->getValue('sales_email/invoice/template');
        }
        
        
        if ($transport) {
            $transport = new \Magento\Framework\DataObject($transport);
            $templateVars = $transport->getData();
            $template = $this->_emailFactory->create();
            $template->setTemplateText($template->getTemplateText());
            $template->setVars($templateVars);
            $template->setId($templateId);
            $template->setOptions($templateOption);
            $template->load($templateId);
            $template->emulateDesign($storeId);

            $this->_emulation->startEnvironmentEmulation($storeId);

            $templateProcessed = $template->processTemplate();

            $this->_emulation->stopEnvironmentEmulation();

            $template->revertDesign();

            echo $templateProcessed;
        } else {
            echo "Template cannot be generated";
        }
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mageseller_Emailpreview::emailpreview');
    }
}