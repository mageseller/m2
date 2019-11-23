<?php


namespace Mageseller\Emailpreview\Block\Magento\Sales\Block\Adminhtml\Order;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
class View extends \Magento\Sales\Block\Adminhtml\Order\View
{
	protected $object_manager;
    protected $_backendUrl;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        array $data = []
    ) {
        parent::__construct($context,$registry,$salesConfig,$reorderHelper,$data);
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $orderId = $this->getOrder() ? $this->getOrder()->getId() : null;
        $sendOrder = $this->_backendUrl->getUrl('emailpreview/order/preview/order_id/'.$orderId );
        
        $this->addButton(
            'emailpreview',
            [
                'label' => __('Preview Order Email'),
                'onclick' => "window.open('" . $sendOrder. "','name','height=1050,width=800,toolbar=0,menubar=0,location=0,scrollbars=no,resizable=no,status=no').focus()",
                'class' => 'ship primary'
            ]
        );
    }
}
