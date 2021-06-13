<?php
/**
 * RayPay payment plugin
 *
 * @developer     hanieh729
 * @publisher     RayPay
 * @package       Joomla - > Site and Administrator payment info
 * @subpackage    com_Jshopping
 * @subpackage 	  pm_raypay
 * @copyright (C) 2021 RayPay
 * @license       http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * https://raypay.ir
 */
defined('_JEXEC') or die();
use Joomla\CMS\Http\Http;
use Joomla\CMS\Http\HttpFactory;

if (!class_exists ('checkHack')) {
    require_once dirname(__FILE__). '/raypay_inputcheck.php';
}


class pm_raypay extends PaymentRoot{
    
    function showPaymentForm($params, $pmconfigs){	
        include(dirname(__FILE__)."/paymentform.php");
    }

	//function call in admin
	function showAdminFormParams($params){
		$array_params = array('transaction_end_status', 'transaction_pending_status', 'transaction_failed_status');
		foreach ($array_params as $key){
			if (!isset($params[$key])) $params[$key] = '';
		} 
		$orders = JSFactory::getModel('orders', 'JshoppingModel'); //admin model
		include(dirname(__FILE__)."/adminparamsform.php");
	}

	function showEndForm($pmconfigs, $order){
		$app	= JFactory::getApplication();
        $this->http = HttpFactory::getHttp();
        $uri = JURI::getInstance(); 
        $pm_method = $this->getPmMethod();       
        $liveurlhost = $uri->toString(array("scheme",'host', 'port'));
        //$callback = ltrim( $liveurlhost.SEFLink('index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_raypay', 0, 1), '/');

        //$callback = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=".$pm_method->payment_class .'&orderId='. $order->order_id . '&');
       //$callback = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=".$pm_method->payment_class).'&orderId='. $order->order_id . '&';
        $callback = JRoute::_( JURI::root() . "index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_raypay" . "&orderId=". $order->order_id . "&" ) ;
    	$notify_url2 = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step2&act=notify&js_paymentclass=".$pm_method->payment_class."&no_lang=1");
		$desc = 'خرید محصول از فروشگاه JoomShopping  ';
        $user_id = $pmconfigs['user_id'];
        $acceptor_code = $pmconfigs['acceptor_code'];
        $invoice_id             = round(microtime(true) * 1000);
        $amount = strval(round($this->fixOrderTotal($order),0));

        if (!isset($user_id) || $user_id == '' || !isset($acceptor_code) || $acceptor_code == '') {
			$app->redirect($notify_url2, '<h2>لطفا تنظیمات درگاه رای پی را بررسی کنید</h2>', $msgType='Error');
		}
		
		try {
            $data = array(
                'amount'       => $amount,
                'invoiceID'    => strval($invoice_id),
                'userID'       => $user_id,
                'redirectUrl'  => $callback,
                'factorNumber' => strval($order->order_id),
                'acceptorCode' => $acceptor_code,
                'comment'      => $desc
            );

            $url  = 'http://185.165.118.211:14000/raypay/api/v1/Payment/getPaymentTokenWithUserID';
            $options = array('Content-Type' => 'application/json');
            $result = $this->http->post($url, json_encode($data, true), $options);
            $result = json_decode($result->body);
            $http_status = $result->StatusCode;

            if ( $http_status != 200 || empty($result) || empty($result->Data) )
            {
                $msg         = sprintf('خطا هنگام ایجاد تراکنش. کد خطا: %s - پیام خطا: %s', $http_status, $result->Message);
                $app->enqueueMessage( $msg, 'Error' );
            }

            $access_token = $result->Data->Accesstoken;
            $terminal_id  = $result->Data->TerminalID;

            echo '<p style="color:#ff0000; font:18px Tahoma; direction:rtl;">در حال اتصال به درگاه بانکی. لطفا صبر کنید ...</p>';
            echo '<form name="frmRayPayPayment" method="post" action=" https://mabna.shaparak.ir:8080/Pay ">';
            echo '<input type="hidden" name="TerminalID" value="' . $terminal_id . '" />';
            echo '<input type="hidden" name="token" value="' . $access_token . '" />';
            echo '<input class="submit" type="submit" value="پرداخت" /></form>';
            echo '<script>document.frmRayPayPayment.submit();</script>';

            exit();

		}
		catch(Exception $e) {
			$msg= 'خطا هنگام ایجاد تراکنش';
			$app->redirect($notify_url2, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}

	}
    
		function checkTransaction($pmconfigs, $order, $act){
			$app	= JFactory::getApplication();
            $this->http = HttpFactory::getHttp();
			$jinput = $app->input;
            $invoiceId = $jinput->get->get('?invoiceID', '', 'STRING');
            $orderId = $jinput->get->get('orderId', '', 'STRING');
			$uri = JURI::getInstance();
			$pm_method = $this->getPmMethod();       
			$liveurlhost = $uri->toString(array("scheme",'host', 'port'));
			$cancel_return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=".$pm_method->payment_class.'&orderId='. $orderId);

            if ( empty( $invoiceId ) || empty( $orderId ) )
            {
                $msg = 'خطا هنگام بازگشت از درگاه پرداخت';
                saveToLog("payment.log", "gateway return failed. Order ID ".$order->order_id.". message: ".$msg );
                $app->redirect($cancel_return, '<h2>'.$msg.'</h2>' , $msgType='Error');
            }
                $data = array('order_id' => $orderId);
                $url = 'http://185.165.118.211:14000/raypay/api/v1/Payment/checkInvoice?pInvoiceID=' . $invoiceId;;
                $options = array('Content-Type' => 'application/json');
                $result = $this->http->post($url, json_encode($data, true), $options);
                $result = json_decode($result->body);
                $http_status = $result->StatusCode;

                if ( $http_status != 200 )
                {
                    $msg = sprintf('خطا هنگام بررسی تراکنش. کد خطا: %s - پیام خطا: %s', $http_status, $result->Message);
                    $app->enqueueMessage( $msg, 'Error' );
                    saveToLog("payment.log", "Status failed. Order ID ".$orderId.". message: ".$msg );
                    $app->redirect($cancel_return, '<h4>'.$msg.'</h4>', $msgType='Error');
                }
                $state           = $result->Data->State;
                $verify_order_id = $result->Data->FactorNumber;
                $verify_amount   = $result->Data->Amount;

                if ( empty($verify_order_id) || empty($verify_amount) || $state !== 1 )
                {
                    $msg  = 'پرداخت ناموفق بوده است. شناسه ارجاع بانکی رای پی : ' . $invoiceId;
                    saveToLog("payment.log", "Status failed. Order ID ".$orderId.". message: ".$msg );
                    $app->redirect($cancel_return, '<h4>'.$msg.'</h4>', $msgType='Error');
                }
                else
                {
                    $msg  = 'پرداخت شما با موفقیت انجام شد.';
                    $app->enqueueMessage( '<h2>'.$msg.'</h2>', 'message' );
                    saveToLog("payment.log", "Status Complete. Order ID ".$order->order_id.". message: ".$msg . " invoice_id: " . $invoiceId);
                    return array(1, "");
                }
            return false;
	}
    
	function fixOrderTotal($order){
        $total = $order->order_total;
        if ($order->currency_code_iso=='HUF'){
            $total = round($total);
        }else{
            $total = number_format($total, 2, '.', '');
        }
    return $total;
    }
    function getUrlParams($pmconfigs){
        $app	= JFactory::getApplication();
        $jinput = $app->input;
        $oId = $jinput->get->get('orderId', '', 'STRING');
        $params = array();
        $params['order_id'] = $oId;
        $params['hash'] = "";
        $params['checkHash'] = 0;
        $params['checkReturnParams'] = 1;
        return $params;
    }
}
