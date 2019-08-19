<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 *
 * @author Novalnet AG <technic@novalnet.de>
 * @copyright Novalnet
 * @license GNU General Public License
 *
 * Script : NovalnetOrderConfirmationDataProvider.php
 *
 */

namespace Novalnet\Providers;

use Plenty\Plugin\Templates\Twig;

use Novalnet\Helper\PaymentHelper;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use \Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;
use Novalnet\Models\TransactionLog;
use Novalnet\Services\PaymentService;
/**
 * Class NovalnetOrderConfirmationDataProvider
 *
 * @package Novalnet\Providers
 */
class NovalnetOrderConfirmationDataProvider
{
	/**
	 * Setup the Novalnet transaction comments for the requested order
	 *
	 * @param Twig $twig
	 * @param PaymentRepositoryContract $paymentRepositoryContract
	 * @param Arguments $arg
	 * @return string
	 */
	public function call(Twig $twig, PaymentRepositoryContract $paymentRepositoryContract, $arg)
	{
		$paymentHelper = pluginApp(PaymentHelper::class);
		$paymentService = pluginApp(PaymentHelper::class);
		$sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);
		$order = $arg[0];
		$barzhlentoken = '';
		$barzahlenurl = '';
		$payments = $paymentRepositoryContract->getPaymentsByOrderId($order['id']);
		
		if (!empty ($order['id'])) {
			foreach($payments as $payment)
			{
				if($paymentHelper->getPaymentKeyByMop($payment->mopId))
				{
					if ($payment->method['paymentKey'] == 'NOVALNET_CASHPAYMENT')
					{
						$barzhlentoken = html_entity_decode((string)$sessionStorage->getPlugin()->getValue('novalnet_checkout_token'));
						$barzahlenurl = html_entity_decode((string)$sessionStorage->getPlugin()->getValue('novalnet_checkout_url'));
					}
					$orderId = (int) $payment->order['orderId'];
					$database = pluginApp(DataBase::class);
					$bank_details = $database->query(TransactionLog::class)->where('orderNo', '=', $orderId)->get();
					$comment = '';
					if (!empty($bank_details)) {	
				
				//Typecasting object to array
				$bank_details = (array)($bank_details[0]);
				
				$bank_details['order_no'] = $bank_details['orderNo'];
				
				//Decoding the json as array
				$bank_details['bankDetails'] = json_decode( $bank_details['bankDetails'], true );
				//Merging the array
				$bank_details = array_merge($bank_details, $bank_details['bankDetails']);				
				//Unsetting the redundant key
				unset($bank_details['bankDetails']);
				$comments = PHP_EOL . $paymentService->getInvoicePrepaymentComments($bank_details);
					
						$comment .= (string) $comments;
						$comment .= PHP_EOL;
					
					}	
					

				  $payment_type = (string)$paymentHelper->getPaymentKeyByMop($payment->mopId);
				  return $twig->render('Novalnet::NovalnetOrderHistory', ['comments' => html_entity_decode($comment),'barzahlentoken' => $barzhlentoken,'payment_type' => html_entity_decode($payment_type),'barzahlenurl' => $barzahlenurl]);
				}
			}
		}
	}
}
