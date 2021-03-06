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
        $callback = JRoute::_( JURI::root() . "index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_raypay" . "&orderId=". $order->order_id ) ;
    	$notify_url2 = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step2&act=notify&js_paymentclass=".$pm_method->payment_class."&no_lang=1");
		$desc = '???????? ?????????? ???? ?????????????? JoomShopping  ';
        $user_id = $pmconfigs['user_id'];
        $marketing_id = $pmconfigs['marketing_id'];
        $sandbox = !($pmconfigs['sandbox'] == 0);
        $invoice_id             = round(microtime(true) * 1000);
        $amount = strval(round($this->fixOrderTotal($order),0));

        if (!isset($user_id) || $user_id == '' || !isset($marketing_id) || $marketing_id == '') {
			$app->redirect($notify_url2, '<h2>???????? ?????????????? ?????????? ?????? ???? ???? ?????????? ????????</h2>', $msgType='Error');
		}
		
		try {
            $data = array(
                'amount'       => $amount,
                'invoiceID'    => strval($invoice_id),
                'userID'       => $user_id,
                'redirectUrl'  => $callback,
                'factorNumber' => strval($order->order_id),
                'marketingID' => $marketing_id,
                'enableSandBox' => $sandbox,
                'comment'      => $desc
            );

            $url  = 'https://api.raypay.ir/raypay/api/v1/Payment/pay';
			$options = array('Content-Type: application/json');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER,$options );
			$result = curl_exec($ch);
			$result = json_decode($result );
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
            //$options = array('Content-Type' => 'application/json');
            //$result = $this->http->post($url, json_encode($data, true), $options);
            //$result = json_decode($result->body);
            //$http_status = $result->StatusCode;

            if ( $http_status != 200 || empty($result) || empty($result->Data) )
            {
                $msg         = sprintf('?????? ?????????? ?????????? ????????????. ???? ??????: %s - ???????? ??????: %s', $http_status, $result->Message);
                $app->enqueueMessage( $msg, 'Error' );
            }

            $token = $result->Data;
            $link='https://my.raypay.ir/ipg?token=' . $token;
            Header('Location: '.$link);

		}
		catch(Exception $e) {
			$msg= '?????? ?????????? ?????????? ????????????';
			$app->redirect($notify_url2, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}

	}
    
		function checkTransaction($pmconfigs, $order, $act){
			$app	= JFactory::getApplication();
            $this->http = HttpFactory::getHttp();
			$jinput = $app->input;
            $orderId = $jinput->get->get('orderId', '', 'STRING');
			$uri = JURI::getInstance();
			$pm_method = $this->getPmMethod();       
			$liveurlhost = $uri->toString(array("scheme",'host', 'port'));
			$cancel_return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=".$pm_method->payment_class.'&orderId='. $orderId);

            if ( empty( $orderId ) )
            {
                $msg = '?????? ?????????? ???????????? ???? ?????????? ????????????';
                saveToLog("payment.log", "gateway return failed. Order ID ".$order->order_id.". message: ".$msg );
                $app->redirect($cancel_return, '<h2>'.$msg.'</h2>' , $msgType='Error');
            }
                $url = 'https://api.raypay.ir/raypay/api/v1/Payment/verify';
				$options = array('Content-Type: application/json');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HTTPHEADER,$options );
				$result = curl_exec($ch);
				$result = json_decode($result );
				$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
                //$options = array('Content-Type' => 'application/json');
                //$result = $this->http->post($url, json_encode($data, true), $options);
                //$result = json_decode($result->body);
                //$http_status = $result->StatusCode;

                if ( $http_status != 200 )
                {
                    $msg = sprintf('?????? ?????????? ?????????? ????????????. ???? ??????: %s - ???????? ??????: %s', $http_status, $result->Message);
                    $app->enqueueMessage( $msg, 'Error' );
                    saveToLog("payment.log", "Status failed. Order ID ".$orderId.". message: ".$msg );
                    $app->redirect($cancel_return, '<h4>'.$msg.'</h4>', $msgType='Error');
                }
                $state           = $result->Data->Status;
                $verify_order_id = $result->Data->FactorNumber;
                $verify_invoice_id = $result->Data->InvoiceID;
                $verify_amount   = $result->Data->Amount;

                if ( empty($verify_order_id) || empty($verify_amount) || $state !== 1 )
                {
                    $msg  = '???????????? ???????????? ???????? ??????. ?????????? ?????????? ?????????? ?????? ???? : ' . $verify_invoice_id;
                    saveToLog("payment.log", "Status failed. Order ID ".$orderId.". message: ".$msg );
                    $app->redirect($cancel_return, '<h4>'.$msg.'</h4>', $msgType='Error');
                }
                else
                {
                    $msg  = '???????????? ?????? ???? ???????????? ?????????? ????.';
                    $app->enqueueMessage( '<h2>'.$msg.'</h2>', 'message' );
                    saveToLog("payment.log", "Status Complete. Order ID ".$order->order_id.". message: ".$msg . " invoice_id: " . $verify_invoice_id);
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
