<?php
namespace lib;

use Exception;

class Payment {

	// 页面支付返回信息
	static public function echoDefault($result){
		global $cdnpublic,$order,$conf,$sitename,$ordername;
		$type = $result['type'];
		if(!$type) return false;
		switch($type){
			case 'jump': //跳转
				echo '<script>window.location.replace(\''.$result['url'].'\');</script>';
				break;
			case 'html': //显示html
				echo $result['data'];
				break;
			case 'json': //显示JSON
				echo json_encode($result['data']);
				break;
			case 'page': //显示指定页面
				include_once SYSTEM_ROOT.'txprotect.php';
				if(isset($result['data'])) extract($result['data']);
				if($conf['pageordername']==1)$order['name']=$ordername?$ordername:'onlinepay';
				include PAYPAGE_ROOT.$result['page'].'.php';
				break;
			case 'qrcode': //扫码页面
			case 'scheme': //跳转urlscheme页面
				include_once SYSTEM_ROOT.'txprotect.php';
				$code_url = $result['url'];
				if($conf['pageordername']==1)$order['name']=$ordername?$ordername:'onlinepay';
				include PAYPAGE_ROOT.$result['page'].'.php';
				break;
			case 'return': //同步回调
				returnTemplate($result['url']);
				break;
			case 'error': //错误提示
				sysmsg($result['msg']);
				break;
			default:break;
		}
	}

	// API支付返回信息
	static public function echoJson($result){
		global $order,$siteurl;
		if(!$result) return false;
		$type = $result['type'];
		if(!$type) return false;
		$json['code'] = 1;
		$json['trade_no'] = TRADE_NO;
		switch($type){
			case 'jump':
				$json['payurl'] = $result['url'];
				break;
			case 'qrcode':
				$json['qrcode'] = $result['url'];
				break;
			case 'scheme':
				$json['urlscheme'] = $result['url'];
				break;
			case 'error':
				$json['code'] = -2;
				$json['msg'] = $result['msg'];
				break;
			default:
				$json['payurl'] = $siteurl.'pay/submit/'.TRADE_NO.'/';
				break;
		}
		exit(json_encode($json));
	}

	// 订单回调处理
	static public function processOrder($isnotify, $order, $api_trade_no, $buyer){
		global $DB,$conf;
		if($order['status']==0){
			if($DB->exec("UPDATE `pre_order` SET `status`=1 WHERE `trade_no`='".$order['trade_no']."'")){

				$data = ['endtime'=>'NOW()', 'date'=>'CURDATE()'];
				if(!empty($api_trade_no)) $data['api_trade_no'] = $api_trade_no;
				if(!empty($buyer)) $data['buyer'] = $buyer;
				$DB->update('order', $data, ['trade_no'=>$order['trade_no']]);

				processOrder($order, $isnotify);
			}
		}elseif(empty($order['api_trade_no']) && !empty($api_trade_no)){
			$data = ['api_trade_no'=>$api_trade_no];
			if(!empty($buyer)) $data['buyer'] = $buyer;
			$DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
		}
		if(!$isnotify){
			include_once SYSTEM_ROOT.'txprotect.php';
			// 支付完成5分钟后禁止跳转回网站
			if(!empty($order['endtime']) && time() - strtotime($order['endtime']) > 300){
				$jumpurl = '/payok.html';
			}else{
				$url=creat_callback($order);
				$jumpurl = $url['return'];
			}
			returnTemplate($jumpurl);
		}
    }

	// 更新订单信息
	static public function updateOrder($trade_no, $api_trade_no, $buyer = null){
		global $DB;
		$data = ['api_trade_no'=>$api_trade_no];
		if(!empty($buyer)) $data['buyer'] = $buyer;
		$DB->update('order', $data, ['trade_no'=>$trade_no]);
    }
}
