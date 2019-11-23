<?php


namespace Mageseller\Emailpreview\Block\Magento\Sales\Block\Adminhtml\Order\Invoice;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
class View extends \Magento\Sales\Block\Adminhtml\Order\Invoice\View 
{
	protected $object_manager;
    protected $_backendUrl;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\Auth\Session $backendSession,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context,$backendSession,$registry,$data);
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $invoiceId = $this->getInvoice() ? $this->getInvoice()->getId() : null;
        $sendOrder = $this->_backendUrl->getUrl('emailpreview/order/preview/invoice_id/'.$invoiceId );
        
        $this->buttonList->add(
            'emailpreview',
            [
                'label' => __('Preview Invoice Email'),
                'onclick' => "window.open('" . $sendOrder. "','name','height=1050,width=800,toolbar=0,menubar=0,location=0,scrollbars=no,resizable=no,status=no').focus()",
                'class' => 'ship primary'
            ]
        );
    }
}
