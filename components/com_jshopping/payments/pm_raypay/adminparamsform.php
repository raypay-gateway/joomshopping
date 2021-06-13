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
?>
<div class="col100">
<fieldset class="adminform">
<table class="admintable" width = "100%" >
 <tr>
   <td  class="key">
     <?php echo 'شناسه کاربری';?>
   </td>
    <td style="text-align: right;">
     <input type = "text" class = "inputbox" name = "pm_params[user_id]" size="100" value = "<?php echo $params['user_id']?>" />
     <?php echo JHTML::tooltip('لطفا شناسه کاربری خود را از پنل رای پی دریافت کنید . ');?>
   </td>
 </tr>
    <tr>
        <td  class="key">
            <?php echo 'کد پذیرنده';?>
        </td>
        <td style="text-align: right;">
            <input type = "text" class = "inputbox" name = "pm_params[acceptor_code]" size="100" value = "<?php echo $params['acceptor_code']?>" />
            <?php echo JHTML::tooltip('لطفا کد پذیرنده خود را از پنل رای پی دریافت کنید . ');?>
        </td>
    </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_END;?>
   </td>
   <td style="text-align: right;">
     <?php              
     print JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_end_status'] );
     ?>
   </td>
 </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_PENDING;?>
   </td>
   <td style="text-align: right;">
     <?php 
     echo JHTML::_('select.genericlist',$orders->getAllOrderStatus(), 'pm_params[transaction_pending_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_pending_status']);
     ?>
   </td>
 </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_FAILED;?>
   </td>
   <td style="text-align: right;">
     <?php 
     echo JHTML::_('select.genericlist',$orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_failed_status']);
     ?>
   </td>
 </tr>
</table>
</fieldset>
</div>
<div class="clr"></div>
