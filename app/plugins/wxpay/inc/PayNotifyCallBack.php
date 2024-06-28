<?php
require PAY_ROOT."inc/WxPay.Api.php";
require PAY_ROOT."inc/WxPay.Notify.php";

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		if(isset($result["return_code"])
			&& isset($result["result_code"])
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		//file_put_contents('log.txt',"call back:" . json_encode($data));

		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		global $order;
		if($data['return_code']=='SUCCESS'){
			if($data['result_code']=='SUCCESS'){
				if($data['out_trade_no'] == TRADE_NO && $data['total_fee']==strval($order['realmoney']*100)){
					processNotify($order, $data['transaction_id'], $data['openid']);
				}
				return true;
			}else{
				$msg='['.$data['err_code'].']'.$data['err_code_des'];
				return false;
			}
		}else{
			$msg='['.$data['return_code'].']'.$data['return_msg'];
			return false;
		}
	}
}

