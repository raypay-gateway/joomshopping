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
defined('_JEXEC') or die('Restricted access');

$db = JFactory::getDbo();
$query = $db->getQuery(true);
$columns = array('name_en-GB', 'name_de-DE', 'description_en-GB', 'description_de-DE', 'payment_code', 'payment_class', 'scriptname', 'payment_publish', 'payment_ordering', 'payment_params', 'payment_type', 'tax_id', 'price', 'show_descr_in_email','name_fa-IR');
$values = array($db->q('RayPay'), $db->q('RayPay'), $db->q(''), $db->q(''), $db->q('raypay'), $db->q('pm_raypay'), $db->q('pm_raypay'), $db->q(0), $db->q(2), $db->q('user_id='), $db->q(2), $db->q(1), $db->q(0), $db->q(0),$db->q('RayPay'));
$query->insert($db->qn('#__jshopping_payment_method'));
$query->columns($db->qn($columns));
$query->values(implode(',', $values));
$db->setQuery((string)$query); 
$db->execute();

?>
