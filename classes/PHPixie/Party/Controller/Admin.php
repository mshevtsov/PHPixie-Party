<?php

namespace PHPixie\Party\Controller;

class Admin extends \App\Page {
	private $page = array();

	public function before() {
		if(!$this->logged_in('admin'))
			return $this->redirect("/register");
		$this->view = $this->pixie->view('admin2');
		$this->view->user = $this->pixie->auth->user();
	}

	public function after() {
		$this->view->page = $this->page;
		$this->response->body = $this->view->render();
	}

	public function action_logout() {
		$this->pixie->auth->logout();
		$this->redirect('/register');
	}






	public function action_index() {
		$this->page['subview'] = 'admin-dashboard';

		$prepay = $this->pixie->config->get("party.periods.1.prices.prepay");

		$parts = $this->pixie->orm->get('participant')->where('money','<',$prepay)->where('cancelled',0)->count_all();
		$partsToday = $this->pixie->orm->get('participant')->where('money','<',$prepay)->where('created','>',date('Y-m-d 00:00:00',time()))->where('cancelled',0)->count_all();

		// $partsReady = $this->pixie->orm->get('participant')->where('cancelled',0)->where('money','>=',$prepay)->count_all();
		$partsReadyToday = $this->pixie->orm->get('participant')->where('money','>=',$prepay)->where('paydate','>',date('Y-m-d 00:00:00',time()))->where('cancelled',0)->count_all();
		$partsReady = $this->pixie->orm->get('participant')
			->where('cancelled',0)
			->where(array(
				array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
				array('or',array('approved',1)),
				))
			->count_all();

		$partsAttend = $this->pixie->orm->get('participant')->where('attend',1)->count_all();

/*
		$partsWait = $this->pixie->orm->get('participant')
			->where('cancelled',0)
			->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))
			->where('approved',0)
			// ->where('accomodation', 'NOT IN', $this->pixie->db->expr('(3,4)'))
			->find_all()->as_array();
		$count = 0;
		foreach($partsWait as $part) {
			$copy = $this->pixie->orm->get('participant')->where('cancelled',0)->where('firstname',$part->firstname)->where('lastname',$part->lastname)->where('id','<>',$part->id)->count_all();
			if($copy){
				// print "<a href=\"/admin/participants/{$part->id}\">{$part->id}</a><br/>\n";
				continue;
			}
			$orders = $part->user->orders->order_by('datecreated','desc')->find_all()->as_array();
			foreach($orders as $order) {
				$purpose = unserialize($order->purpose);
				if(in_array($part->id, $purpose)) {
					$age = date_diff(date_create($order->datecreated), date_create('today'))->d;
					if($age<30 && $part->user->id!=32) {
						$count++;
						print "<a href=\"/admin/participants/{$part->id}\">{$age}</a><br/>\n";
						break;
					}
				}
			}
		}
		print "таких ". $count;
*/


		$users = $this->pixie->orm->get('user')->count_all();
		$usersToday = $this->pixie->orm->get('user')->where('codesent','>',date('Y-m-d 00:00:00',time()))->count_all();

		$orders = $this->pixie->orm->get('order')->count_all();
		$ordersToday = $this->pixie->orm->get('order')->where('datecreated','>',date('Y-m-d 00:00:00',time()))->count_all();
		$ordersPayedToday = $this->pixie->orm->get('order')->where('payed',1)->where('datepayed','>',date('Y-m-d 00:00:00',time()))->count_all();

		$ordersPayed = $this->pixie->orm->get('order')->where('payed',1)->where('balance','>',0)->where('cash',0)->where('cancelled',0)->find_all()->as_array();
		$payedToday = $payedTotal = 0;
		foreach($ordersPayed as $order) {
			$payedTotal += $order->balance;
			if(strtotime($order->datepayed)>strtotime('today'))
				$payedToday += $order->balance;
		}

		$ordersCash = $this->pixie->orm->get('order')->where('payed',1)->where('balance','>',0)->where('cash',1)->where('cancelled',0)->find_all()->as_array();
		$payedCashTotal = 0;
		foreach($ordersCash as $order)
			$payedCashTotal += $order->balance;


		$parts2 = $this->pixie->orm->get('participant')->where('money','>',0)->find_all();
		$this->page['totalMoney'] = 0;
		foreach($parts2 as $part)
			$this->page['totalMoney'] += $part->money;


		$logs = $this->pixie->orm->get('log')->count_all();
		$logsToday = $this->pixie->orm->get('log')->where('created','>',date('Y-m-d 00:00:00',time()))->count_all();

/*
		$this->page['content'] = "<p>Участников: <strong>{$parts}</strong>(+{$partsToday})</p>\n";
		$this->page['content'] .= "<p>Пользователей: <strong>{$users}</strong>(+{$usersToday})</p>\n";
		$this->page['content'] .= "<p>Платежей: <strong>{$orders}</strong>(+{$ordersPayedToday}/{$ordersToday})</p>\n";
		$this->page['content'] .= "<p>Ошибок: <strong>{$logs}</strong>(+{$logsToday})</p>\n";
*/
		$this->page['participants'] = $parts;
		$this->page['participantsToday'] = $partsToday;
		$this->page['partsReady'] = $partsReady;
		$this->page['partsReadyToday'] = $partsReadyToday;
		$this->page['ordersPayed'] = count($ordersPayed);
		$this->page['ordersPayedToday'] = $ordersPayedToday;
		$this->page['payedTotal'] = $payedTotal;
		$this->page['payedTotalShort'] = floor($payedTotal/1000) ."<small>". sprintf("%03d",$payedTotal % 1000) ."</small>";
		$this->page['payedToday'] = $payedToday;
		$this->page['partsAttend'] = $partsAttend;
		$this->page['payedCashTotalShort'] = floor($payedCashTotal/1000) ."<small>". sprintf("%03d",$payedCashTotal % 1000) ."</small>";

		$settled = $this->pixie->party->getSettlementProgress();
		$this->page['settled'] = $settled[0];

		$countMen = $this->pixie->orm->get('participant')
			->where('cancelled',0)
			->where('gender',1)
			->where(array(
				array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
				array('or',array('approved',1)),
				))
			->count_all();
		$this->page['percentMen'] = round($countMen/$partsReady*100);

		$bd = $this->pixie->party->getBirthdays();
		$this->page['birthdays'] = count($bd);



		$this->page['comments'] = $this->pixie->orm->get('participant')->where('comment','<>','')->order_by('created','desc')->where('cancelled',0)->find_all()->as_array();


		// Строим график

		$statFormatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$statFormatter->setPattern('2014-MM-dd');

		$history1 = $history2 = array();
		$parts = $this->pixie->orm->get('participant')->where('cancelled',0)->find_all()->as_array();
		foreach($parts as $part) {
			if($part->created) {
				$date = $statFormatter->format(new \DateTime($part->created));
				if(!isset($history1[$date]['partsCreated']))
					$history1[$date]['partsCreated'] = 0;
				$history1[$date]['partsCreated']++;
			}
			if(/*$part->paydate>0 && */$part->money) {
				$date = $statFormatter->format(new \DateTime($part->paydate));
				if(!isset($history1[$date]['partsPayed']))
					$history1[$date]['partsPayed'] = 0;
				$history1[$date]['partsPayed']++;
			}
		}
		ksort($history1);

		$orders = $this->pixie->orm->get('order')->where('balance','>',0)->order_by('datepayed','asc')->find_all()->as_array();
		foreach($orders as $order) {
			if($order->datepayed > 0) {
				$date = $statFormatter->format(new \DateTime($order->datepayed));
				if(!isset($history2[$date]['payments']))
					$history2[$date]['payments'] = 0;
				$history2[$date]['payments'] += $order->balance;
			}
		}
		ksort($history2);

		$this->page['script'] = "
	Morris.Area({
		element: 'morris-area-chart1',
		data: [";

		$partsCreated = $partsPayed = 0;
		foreach($history1 as $date=>$value) {
			if(!$date)
				continue;
			$this->page['script'] .= "{\nperiod: '{$date}',\n";
			if(!isset($value['partsCreated']))
				$value['partsCreated'] = 'null';
			if(!isset($value['partsPayed']))
				$value['partsPayed'] = 'null';
			// $this->page['script'] .= "created: {$value['partsCreated']},\n";
			// $this->page['script'] .= "payed: {$value['partsPayed']},\n";
			$partsCreated += $value['partsCreated'];
			$partsPayed += $value['partsPayed'];
			$this->page['script'] .= "created: ". ($partsCreated-$partsPayed) .",\n";
			$this->page['script'] .= "payed: {$partsPayed},\n";
			$this->page['script'] .= "},\n";
		}


		if(strtotime('today')>strtotime($this->pixie->config->get('party.periods.1.lastday')))
			$events = "events: ['". $this->pixie->config->get('party.periods.1.lastday') ."'],\n";
		else
			$events = "";

		$this->page['script'] .= "
],
		xkey: 'period',
		ykeys: ['created','payed'],
		labels: ['без предоплаты', 'с предоплатой'],
		{$events}pointSize: 2,
		hideHover: 'auto',
		resize: true
	});
";
		$this->page['script'] .= "
	Morris.Area({
		element: 'morris-area-chart2',
		data: [";

		$money = 0;
		foreach($history2 as $date=>$value) {
			$this->page['script'] .= "{\nperiod: '{$date}',\n";
			if(!isset($value['payments']))
				$value['payments'] = 0;
			$money += $value['payments'];
			$this->page['script'] .= "money: ". ($money/1000) .",\n";
			$this->page['script'] .= "},\n";
		}

		$this->page['script'] .= "
],
		xkey: 'period',
		ykeys: ['money'],
		labels: ['получено средств'],
		{$events}pointSize: 2,
		postUnits: 'k',
		hideHover: 'auto',
		resize: true
	});
";

	}



/*
	// Одноразовое конвертирование
	public function action_newdays() {
		$parts = $this->pixie->orm->get('participant')->find_all();
		foreach($parts as $part) {
			switch($part->days) {
				case 4:
					$days = array(1,1,1,1);
					break;
				case 3:
					$days = array(0,1,1,1);
					break;
				case 2:
					$days = array(0,0,1,1);
					break;
				case 1:
					$days = array(0,0,0,1);
					break;
				default:
					$days = array(0,0,0,0);
			}
			$part->dayslist = serialize($days);
			$part->save();
		}
	}
*/

	public function action_comments() {
		$this->page['title'] = 'Комментарии участников';
		$this->page['participants'] = $this->pixie->orm->get('participant')->where('cancelled',0)->where('comment','<>','')->order_by('created','desc')->find_all()->as_array();
		$this->page['subview'] = 'admin-comments';
	}







	public function action_repair() {
		$this->page['title'] = "Исправление сведений в неверном формате";
		$this->page['subview'] = "admin-repair";

		$confirm = $this->request->post('action')=='confirm' ? true : false;

		$parts = $this->pixie->orm->get('participant')->where('cancelled',0)->find_all()->as_array();

		// Хранилище для списка кандидатов на исправление.
		// Сначала посмотрим его для проверки
		$this->page['candidates'] = array(
			'phones' => array(),
			'websites' => array(),
			);

		foreach($parts as $part) {
			if($part->phone) {
				$phone = $part->phone;

				$phones = array();
				if(strpos($phone, ',')!==false)
					$phones = explode(',', $phone);
				else if(strpos($phone, ';')!==false)
					$phones = explode(';', $phone);
				else if(preg_match("/([\S]{11,})[\s]([\S]{11,})/", $phone, $match))
					$phones = array($match[1], $match[2]);
				else
					$phones[] = $phone;

				foreach($phones as $key => $p) {
					$p = trim($p);
					$p = preg_replace("/[\D]/", "", $p);
					$p = preg_replace("/^[78](9[\d]{9})$/", "+7$1", $p);
					$p = preg_replace("/^(9[\d]{9})$/", "+7$1", $p);
					$p = preg_replace("/^(38[\d]+)$/", "+$1", $p);
					$phones[$key] = $this->phone($p);
				}
				$phone = implode(', ', $phones);

				if($phone != $part->phone) {
					$this->page['candidates']['phones'][] = array($part->id, $part->phone, $phone);
					if($confirm) {
						$part->phone = $phone;
						$part->save();
					}
				}
			}
			if($part->website) {
				$website = $part->website;
				if(!preg_match("/^https?:\/\//", $website))
					$website = "http://". $website;
				$website = preg_replace("/m\.vk\.com/", "vk.com", $website);
				$website = preg_replace("/http\:\/\/vk\.com/", "https://vk.com", $website);
				$website = preg_replace("/vk\.com\/[\w]*#([\w]*)/", "vk.com$2", $website);

				if($website != $part->website) {
					$this->page['candidates']['websites'][] = array($part->id, $part->website, $website);
					if($confirm) {
						$part->website = $website;
						$part->save();
					}
				}
			}
		}

		if($confirm)
			$this->redirect('/admin/repair');
	}






	public function action_import2() {
		$this->page['title'] = "Импорт домов для размещения";
/*
		$houses = $this->pixie->orm->get('house')->where('coord','<>','')->find_all()->as_array();
		foreach($houses as $newItem) {
			$fields = array(
				'origin'=>'jsapi2Geocoder',
				'geocode'=>"г. {$newItem->city}, {$newItem->address}",
				'format'=>'json',
				'rspn'=>'0',
				'results'=>'1',
				'lang'=>'ru_RU',
				'sco'=>'longlat',
			);
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://geocode-maps.yandex.ru/1.x/');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);			
			$result = curl_exec($curl_handle);
			$httpCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
			curl_close($curl_handle);

			$result = json_decode($result);
			$coord = $result->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
			$coord = explode(" ", $coord);
			$newItem->coord = $coord[1] .','. $coord[0];
			$newItem->save();
		}
		return true;
*/

		$excel = new \XLSXReader\XLSXReader($this->pixie->root_dir .'web/files/houses3.xlsx');
		$data = $excel->getSheetData(1);
		foreach($data as $id=>$item) {
			// Пропускаем нулевую строку - с шапкой таблицы
			if(!$id || !$item[0])
				continue;

			if($this->pixie->orm->get('house')->where('name',$item[0])->where('city',$item[2])->count_all())
				continue;
			$newItem = $this->pixie->orm->get('house');
			$newItem->name = $item[0];
			$newItem->church = $item[1];
			$newItem->city = $item[2];
			if($item[3]) $newItem->district = $item[3];
			if($item[4]) $newItem->busstop = $item[4];
			if($item[5]) $newItem->address = $item[5];
			if($item[6]) $newItem->phone = $item[6];
			if($item[7]) $newItem->places1 = $item[7];
			if($item[8]) $newItem->places2 = $item[8];
			if($item[9]) $newItem->places3 = $item[9];
			if($item[10]) $newItem->places4 = $item[10];
			if($item[11]) $newItem->meal = $item[11];
			if($item[12]) $newItem->transport = $item[12];
			if($item[13]) $newItem->meeting = $item[13];
			if($item[14]) $newItem->delivery = $item[14];
			if($item[15]) $newItem->future = $item[15];
			if($item[16]) $newItem->route = $item[16];
			if($item[17]) $newItem->comment = $item[17];

			if($newItem->address && $newItem->city) {
				$fields = array(
					'origin'=>'jsapi2Geocoder',
					'geocode'=>"г. {$newItem->city}, {$newItem->address}",
					'format'=>'json',
					'rspn'=>'0',
					'results'=>'1',
					'lang'=>'ru_RU',
					'sco'=>'latlong',
				);
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, 'http://geocode-maps.yandex.ru/1.x/');
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);			
				$result = curl_exec($curl_handle);
				$httpCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
				curl_close($curl_handle);

				$result = json_decode($result);
				$coord = $result->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
				// $newItem->coord = str_replace(" ", ",", $coord);
				$coord = explode(" ", $coord);
				$newItem->coord = $coord[1] .','. $coord[0];
			}

			$newItem->save();
		}
		$this->page['content'] = "done!";
	}





	public function action_houses() {
		$param1 = $this->request->param('param1');
		if(is_numeric($param1)) {
			$house = $this->pixie->orm->get('house',(int)$param1);
			if(!$house->loaded())
				throw new \PHPixie\Exception\PageNotFound;

			$this->page['house'] = $house;
			$this->page['title'] = $house->name;

			if($this->request->param('param2')=='edit') {
				// Выдаём форму редактирования дома
				if($this->request->method != 'POST') {
					$this->page['title'] .= " <a href=\"/admin/houses/{$house->id}\" role=\"button\" class=\"btn btn-default\"><i class=\"fa fa-eye\"></i></a>";
					$this->page['subview'] = "admin-house-edit";
				}
				// Сохраняем изменения, переданные через форму
				else {
					$fields = $house->columns();
					$post = $this->request->post();
					$changes = array();
					foreach($fields as $field) {
						// не учитываем эти поля - их в форме не будет
						if($field=='id' || $field=='coord')
							continue;
						// просто разные обозначения поставленных галочек - пропускаем
						if($house->$field==1 && isset($post[$field]) && $post[$field]=='on')
							continue;

						if(isset($post[$field]) && $house->$field != $post[$field]) {
							if($house->$field==0 && $post[$field]=='on')
								$post[$field] = 1;
							$changes[] = "{$field}: {$house->$field}=>{$post[$field]}";
							$house->$field = $post[$field];
						}

						if($house->$field==1 && !isset($post[$field])) {
							$changes[] = "{$field}: 1=>0";
							$house->$field = 0;
						}
					}
					// на память в лог
					$log = $this->pixie->orm->get('log');
					$log->type = 8;
					$log->author = $this->pixie->auth->user()->id;
					$log->id1 = $house->id;
					$log->message = "Дом {$house->name} отредактирован (". implode("; ",$changes) .")";
					$log->save();

					$house->save();
					$this->redirect("/admin/houses/". $house->id);
				}
			}
			else {
				$this->page['title'] .= " <a href=\"/admin/houses/{$house->id}/edit\" role=\"button\" class=\"btn btn-default\"><i class=\"fa fa-wrench\"></i></a>";
				$this->page['subview'] = "admin-house";
			}
		}
		else if($param1=='export') {
			$formatterShort = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
			$formatterShort->setPattern("d MMMM");

			$this->view = $this->pixie->view('admin3');
			$this->page['title'] = "Итоги расселения на ". $formatterShort->format(new \DateTime('now'));
			$this->page['subview'] = "admin-houses-export";
			$this->page['list'] = array();
			$this->page['ready'] = $this->pixie->party->getSettlementProgress();

			$houses = $this->pixie->orm->get('house')->order_by('church','asc')->order_by('name','asc')->find_all()->as_array();
			$transportOptions = $this->pixie->config->get('party.userfields.transport.options');
			foreach($houses as $house) {
				if($house->participants->where('cancelled',0)->count_all()) {
					$houseList = array(
						'id' => $house->id,
						'name' => $house->name,
						'church' => $house->church,
						'phone' => $house->phone,
						'district' => $house->district,
						'meeting' => $house->meeting,
						'transport' => $house->transport,
						'guests' => array(),
						);
					$parts = $house->participants->where('cancelled',0)->find_all()->as_array();
					foreach($parts as $item) {
						$age = date_diff(date_create($item->birthday), date_create('now'))->y;
						if(!$age || $age>100)
							$age = "<i class=\"fa fa-question-circle\"></i>";
						$houseList['guests'][] = array(
							'id' => $item->id,
							'name' => $item->firstname .' '. $item->lastname,
							'age' => $age,
							'days' => $item->days,
							'transport' => isset($transportOptions[$item->transport]) ? $transportOptions[$item->transport] : "<i class=\"fa fa-question-circle text-muted\"></i>",
							'city' => $item->city,
							'phone' => $item->phone,
							'email' => $item->email,
							'attend' => $item->attend,
							);
					}
					empty($house->church)
						? $this->page['list']['nochurch'][] = $houseList
						: $this->page['list'][$house->church][] = $houseList;
				}
			}
		}
		else if($param1=='hosts') {
			$this->view = $this->pixie->view('admin3');
			$this->page['title'] = 'Гостеприимные дома';
			$houses = $this->pixie->orm->get('house')->order_by('church','asc')->find_all();
			
			$housesByChurch = array();
			$this->page['content'] = "<p>Список гостеприимных домов для расселения участников ". $this->pixie->config->get('party.event.title') ."</p>\n";

			foreach($houses as $house) {
				if($house->church)
					$housesByChurch[$house->church][] = $house;
				else
					$housesByChurch['Остальные'][] = $house;
			}

			foreach($housesByChurch as $title=>$church) {
				$this->page['content'] .= "<div class=\"panel panel-default\">\n<div class=\"panel-heading\"><h4 class=\"text-center\">{$title}</h4></div>\n<table class=\"table\">\n";//<ul class=\"list-group\">\n";
				foreach($church as $key=>$house) {
					if(!$house->places1 && !$house->places2 && !$house->places3 && !$house->places4)
						continue;
					$this->page['content'] .= "<tr>\n";
					$this->page['content'] .= "<td>". ($key+1) ."</td>\n";
					$this->page['content'] .= "<td>{$house->name}</td>\n";
					$this->page['content'] .= "<td>{$house->phone}</td>\n";
					$this->page['content'] .= "</tr>\n";
				}
				$this->page['content'] .= "</table>\n</div>\n";
			}
		}
		else if($param1=='printcards') {
			$this->view = $this->pixie->view('admin3');
			$this->page['title'] = 'Гостеприимные дома';
			$houses = $this->pixie->orm->get('house')->where(array(
					array('places1','>',0),
					array('or',array('places2','>',0)),
					array('or',array('places3','>',0)),
					array('or',array('places4','>',0)),
				))->find_all();
			// $this->page['content'] = "<table class=\"table\">\n";
			// foreach($houses as $key=>$house) {
			// 	if(!($key % 2))
			// 		$this->page['content'] .= "<tr>\n";
			// 	$this->page['content'] .= "<td><div class=\"pull-right label label-default\">{$house->id}</div>{$house->name}</td>\n";
			// 	if($key % 2)
			// 		$this->page['content'] .= "</tr>\n";
			// }
			// $this->page['content'] .= "</table>\n";

			$this->page['content'] = "<div class=\"container container-house-card\">\n<div class=\"row\">\n";
			foreach($houses as $key=>$house) {
				$this->page['content'] .= "<div class=\"col-xs-6 house-card\">\n";
				$this->page['content'] .= "<h4 class=\"text-center\">{$house->name}<div class=\"pull-right label label-default\">{$house->id}</div></h4>\n";
				if($house->church)
					$this->page['content'] .= "<div><i>Церковь:</i> {$house->church}</div>\n";
				if($house->phone)
					$this->page['content'] .= "<div><i>Телефон:</i> {$house->phone}</div>\n";
				if($house->address)
					$this->page['content'] .= "<div><i>Адрес:</i> {$house->city}, {$house->address}</div>\n";
				$extra = array();
				if($house->district)
					$extra[] = "<i>Район:</i> {$house->district}.";
				if($house->busstop)
					$extra[] = "<i>Остановка:</i> {$house->busstop}.";
				if($house->route)
					$extra[] = "<i>Маршруты:</i> {$house->route}.";
				if(count($extra))
					$this->page['content'] .= "<div>". implode(" ",$extra) ."</div>\n";
				if($house->coord) {
					$coordHouse = explode(",",$house->coord);
					$this->page['content'] .= "<img src=\"http://static-maps.yandex.ru/1.x/?ll={$coordHouse[1]},{$coordHouse[0]}&size=500,450&z=16&l=map&pt={$coordHouse[1]},{$coordHouse[0]},pm2rdm\" class=\"print-map\"/>";
				}
				$this->page['content'] .= "<div class=\"text-center\">Приветствуем участников конференции Я МОЛОДОЙ!&nbsp;2014, добро&nbsp;пожаловать в Волгоград!</div>\n";
				$this->page['content'] .= "</div>\n";
			}
			$this->page['content'] .= "</div>\n</div>\n";

		}
		else {
			$this->page['title'] = "Места для расселения";
			$this->page['subview'] = "admin-houses-list";
			$this->page['houses'] = $this->pixie->orm->get('house')->find_all()->as_array();
			list($this->page['ready']) = $this->pixie->party->getSettlementProgress();

			$houses = $this->pixie->orm->get('house')->find_all()->as_array();
			$this->page['p1_total'] = $this->page['p2_total'] = $this->page['p3_total'] = $this->page['p4_total'] = $this->page['free'] = $this->page['total'] = 0;
			foreach($houses as $place) {
				$this->page['p1_total'] += $place->places1;
				$this->page['p2_total'] += $place->places2;
				$this->page['p3_total'] += $place->places3;
				$this->page['p4_total'] += $place->places4;
				$this->page['total'] += $place->places1 + $place->places2 + $place->places3 + $place->places4;
				$this->page['free'] += $this->page['total'] - $place->participants->where('cancelled',0)->count_all();
			}

// print $this->page['total'] .", ". count($guests);

		}
	}




	public function action_import() {
		$this->page['title'] = "Импорт старых регистраций";
		$this->page['content'] = "Отключено";
		// $this->page['content'] = "";
		return false;

		$excel = new \XLSXReader\XLSXReader($this->pixie->root_dir .'web/files/old-register3.xlsx');
		// print_r($excel->getSheetNames());
		$data = $excel->getSheetData(1);
		foreach($data as $id=>$user) {
			// Пропускаем нулевую строку - с шапкой таблицы
			if(!$id)
				continue;
			$created = date('Y-m-d H:i:s',strtotime($user[0]));
			$email = strtolower(trim($user[8]));
			// Повторные email пропускаем
			// if($this->pixie->orm->get('user')->where('email',$email)->count_all())
			// 	continue;
			$firstname = trim($user[1]);
			$firstname = mb_strtoupper(mb_substr($firstname, 0, 1, 'utf-8'), 'utf-8') . mb_substr($firstname, 1, mb_strlen($firstname, 'utf-8'), 'utf-8');
			$lastname = trim($user[2]);
			$lastname = mb_strtoupper(mb_substr($lastname, 0, 1, 'utf-8'), 'utf-8') . mb_substr($lastname, 1, mb_strlen($lastname, 'utf-8'), 'utf-8');
			$comment = trim($user[14]);

			if(strtolower(trim($user[3]))=='м')
				$gender = 1;
			else if(strtolower(trim($user[3]))=='ж')
				$gender = 2;
			else
				$gender = 0;

			if(strtolower(trim($user[10]))=='на поезде')
				$transport = 1;
			else if(strtolower(trim($user[10]))=='на машине (своей или с друзьями)')
				$transport = 3;
			else if(strtolower(trim($user[10]))=='на автобусе (рейсовом)')
				$transport = 4;
			else if(strtolower(trim($user[10]))=='на маршрутке'
				|| strtolower(trim($user[10]))=='я живу в Волгограде')
				$transport = 6;
			else {
				$transport = 7;
				$comment .= " + ТРАНСПОРТ(". trim($user[10]) .")";
			}

			if(strtolower(trim($user[11]))=='мне нужно проживание - бесплатно (дома у верующих, в церкви)')
				$accomodation = 1;
			else if(strtolower(trim($user[11]))=='мне нужно проживание - недорогое (хостел)')
				$accomodation = 2;
			else if(strtolower(trim($user[11]))=='мне не нужно проживание - я из Волгограда/пригорода')
				$accomodation = 3;
			else if(strtolower(trim($user[11]))=='мне не нужно проживание - я сам о нем позабочусь')
				$accomodation = 4;
			else {
				$accomodation = 7;
				$comment .= " + ПРОЖИВАНИЕ(". trim($user[11]) .")";
			}

			if(strtolower(trim($user[12]))=='во всей программе: 14-17 августа')
				$days = 4;
			else if(strtolower(trim($user[12]))=='часть программы: 15-17 августа')
				$days = 3;
			else if(strtolower(trim($user[12]))=='часть программы: 16-17 августа')
				$days = 2;
			else {
				$days = 1;
				$comment .= " + ПРОГРАММА(". trim($user[12]) .")";
			}

			$findaboutRaw = explode(", ", trim($user[13]));
			$findabout = array();
			foreach($findaboutRaw as $value) {
				if($value=="объявление в церкви")
					$findabout[] = 1;
				else if($value=="от пастора")
					$findabout[] = 2;
				else if($value=="от молодежного лидера")
					$findabout[] = 3;
				else if($value=="рассылка с сайта imolod.ru")
					$findabout[] = 4;
				else if($value=="реклама ВКонтакте")
					$findabout[] = 5;
				else if($value=="пригласили друзья")
					$findabout[] = 6;
				else if($value=="уже давно жду конференцию!")
					$findabout[] = 7;
			}
			$findabout = serialize($findabout);

			$city = strtolower(trim($user[5]));
			$church = strtolower(trim($user[6]));
			$phone = strtolower(trim($user[7]));
			$website = strtolower(trim($user[9]));

			// $website = preg_replace("/m\.vk\.com/", "vk.com", $website);
			// $website = preg_replace("/^http:\/\/vk\.com/", "https://vk.com", $website);
			// $website = preg_replace("/^vk\.com/", "https://vk.com", $website);

			// $this->page['content'] .= print_r($user,true);
			$this->page['content'] .= "email={$email}, name={$firstname} {$lastname}, codesent={$created}<br/>\n";
			$newUser = $this->pixie->orm->get('user')->where('email',$email)->find();
/*
			if(!$newUser->loaded()) {
				$newUser = $this->pixie->orm->get('user');
				$newUser->email = $email;
				$newUser->name = $firstname .' '. $lastname;
				$newUser->codesent = $created;
				$newUser->save();
			}

			$newParticipant = $this->pixie->orm->get('participant');
			$newParticipant->firstname = $firstname;
			$newParticipant->lastname = $lastname;
			$newParticipant->gender = $gender;
			$newParticipant->city = $city;
			$newParticipant->church = $church;
			$newParticipant->phone = $phone;
			$newParticipant->email = $email;
			if($website)
				$newParticipant->website = $website;
			$newParticipant->days = $days;
			$newParticipant->meal = 1;
			$newParticipant->accomodation = $accomodation;
			$newParticipant->transport = $transport;
			$newParticipant->findabout = $findabout;
			$newParticipant->comment = $comment;
			$newParticipant->created = $created;
			$newParticipant->add('user',$newUser);
			$newParticipant->save();
			print $newParticipant->firstname ."<br/>\n";
*/

			$date = date('d',strtotime($user[0])) ." июня";

			$code = sha1(mt_rand(10000,99999).time().$email);
			$this->page['content'] .= "{$email} ({$firstname}) {$newUser->id} => {$code}<br/>\n";

			$newUser->code = $code;
			$newUser->save();

			$emailTitle = "Подтверждение вашей электронной почты";
			$emailContent = "<p>Мир вам, {$firstname}!</p>\n<p>Вы указали этот адрес электронной почты ({$email}) {$date} при регистрации в конференции Я МОЛОДОЙ! 2014, поэтому спешим сообщить вам важные новости.</p>\n<p>Если вы следили за нашей группой Вконтакте, то, возможно, уже знаете о новом сайте конференции, который открылся 1 июля: <a href=\"http://2014.imolod.ru\" target=\"_blank\">http://2014.imolod.ru</a>. На этом сайте появились новые подробности о самой конференции и правилах участия в ней. Был окончательно уточнён размер регистрационного взноса и введена обязательная предоплата. Сожалеем о том, что приходится её вводить, особенно учитывая, что в середине июня объявлялось, что она не потребуется.</p><p>Регистрационная анкета, которую вы заполняли, остаётся в силе - более того, с сегодняшнего дня у вас есть доступ к её уточнению, добавлению новых участников, едущих вместе с вами, и внесению предоплаты онлайн. Вам нужно только активировать свой доступ, перейдя по этой ссылке: <a href=\"http://2014.imolod.ru/register/confirm?email={$email}&code={$code}\">http://2014.imolod.ru/register/confirm?email={$email}&code={$code}</a></p>\n<p>На открывшейся странице вам нужно будет придумать свой пароль для того, чтобы открывать свою анкету снова. Пожалуйста, укажите там все недостающие сведения. Инструкция с картинками есть вконтакте: <a href=\"http://vk.com/wall-71444548_101\" target=\"_blank\">http://vk.com/wall-71444548_101</a>, а более полные условия регистрации читайте на сайте: <a href=\"http://2014.imolod.ru/register\" target=\"_blank\">http://2014.imolod.ru/register</a></p>\n<p>Если у вас остались вопросы или нужна помощь, пожалуйста пишите по адресу: <a href=\"mailto:info@imolod.ru\">info@imolod.ru</a>.</p>\n<p>Благословений!</p>\n";
			ob_start();
			require_once $this->pixie->find_file('views','email');
			$htmlText = ob_get_clean();

			$result = $this->pixie->email->send(
				array($email => "=?utf-8?B?". base64_encode($firstname .' '. $lastname) ."?="),
				array('info@imolod.ru'=>'Организаторы Я МОЛОДОЙ!'),
				"Конференция Я МОЛОДОЙ: ваша регистрация",
				$htmlText,
				true
				);
print $result
	? $email ." ok<br/>\n"
	: $email ." FAIL!<br/>\n";

// break;
		}
	}




	public function action_resend() {
		$this->page['content'] = 'Повторная отправка активации - отключено';
/*
		$user = $this->pixie->orm->get('user',79);

		$emailTitle = "Подтверждение вашей электронной почты";
		$emailContent = "<p>Мир вам, {$user->name}!</p>\n<p>Вы указали этот адрес электронной почты ({$user->email}) при регистрации на сайте конференции Я МОЛОДОЙ! 2014.</p>\n<p>Для того, чтобы продолжить регистрацию вам нужно подтвердить этот email, просто перейдя по данной ссылке: <a href=\"http://2014.imolod.ru/register/confirm?email={$user->email}&code={$user->code}\">http://2014.imolod.ru/register/confirm?email={$user->email}&code={$user->code}</a></p>\n<p>Если вы не регистрировались на нашем сайте, а ваш email указал кто-то посторонний по ошибке, вам можно просто проигнорировать данное письмо и не отвечать на него. Если же такие сообщения повторяются, пожалуйста, сообщите нам об этом по адресу: info@imolod.ru.</p>\n";
		ob_start();
		require_once $this->pixie->find_file('views','email');
		$htmlText = ob_get_clean();

		$result = $this->pixie->email->send(
			array($user->email => "=?utf-8?B?". base64_encode($user->name) ."?="),
			array('info@imolod.ru'=>'Организаторы Я МОЛОДОЙ!'),
			"Конференция Я МОЛОДОЙ: подтверждение email",
			$htmlText,
			true
			);

		$this->page['content'] .= "=?utf-8?B?". base64_encode($user->name) ."?=<br/>\n";
		$this->page['content'] .= $htmlText;
		print $result ? "Result: {$result} - OK" : "Result: {$result} - FAIL";
*/
	}













	public function action_ajax() {
		if(!$action = $this->request->param('param1'))
			die(json_encode(array("status"=>"error")));

		// Переключаем режим льготной регистрации для пользователя по его номеру
		if($this->request->param('param1')=='grace') {
			if($this->request->param('param2')=='user') {
				if(is_numeric($this->request->param('param3'))) {
					$user = $this->pixie->orm->get('user',$this->request->param('param3'));
					// print "HI";
					if($user->loaded()) {
						if($user->grace) {
							$user->grace = 0;
							$user->save();
							die(json_encode(array("status"=>"success","result"=>"false")));
						}
						else {
							$user->grace = 1;
							$user->save();
							die(json_encode(array("status"=>"success","result"=>"true")));
						}
					}
				}
			}
		}

		else if($this->request->param('param1')=='houses') {
			if($this->request->param('param2')=='list') {
			// if($this->request->post('action')=='list') {
				$houses = $this->pixie->orm->get('house')->find_all()->as_array();
				$result = array();
				foreach($houses as $item) {
					if($item->coord)
						$result[] = array(
							'id' => (int)$item->id,
							'coord' => explode(",", $item->coord),
							'address' => trim(str_replace('"', "'", $item->address)),
							'places1' => (int)$item->places1,
							'places2' => (int)$item->places2,
							'places3' => (int)$item->places3,
							'places4' => (int)$item->places4,
							'meeting' => (int)$item->meeting,
							'name' => $item->name,
							);
				}
				die(json_encode($result));
			}
		}


		// Просто генерируем код активации пользователя
		// (использовал лишь раз, и то вручную, поэтому нужды пока нет)
		else if($this->request->param('param1')=='makemd5') {
			if($this->request->get('email')) {
				die(sha1(mt_rand(10000,99999) . time() . $this->request->get('email')));
			}
		}

		// Узнаём, сколько осталось заплатить участнику
		else if($this->request->param('param1')=='needtopay') {
			if($this->request->param('param2')=='part') {
				if(is_numeric($this->request->param('param3'))) {
					$sum = $this->pixie->party->getSumToPay($this->request->param('param3'));
					die(json_encode(array("status"=>"success","result"=>$sum['needToFull'])));
				}
			}
		}
		// Читаем сообщение для регистратора или от него
		else if($this->request->param('param1')=='usermsg') {
			if($this->request->param('param2')=='read') {
				if(is_numeric($this->request->param('param3'))) {
					$msg = $this->pixie->orm->get('usermsg',$this->request->param('param3'));
					if($msg->loaded()) {
						$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
						$formatter->setPattern("d MMMM',' h:mm");
						die(json_encode(array("status"=>"success","result"=>$msg->content,"title"=>"Сообщение от {$msg->sender->name} за ". $formatter->format(new \DateTime($msg->date)))));
					}
				}
			}
		}
		// Повторное исполнение заказа
		else if($this->request->param('param1')=='approveorder') {
			if(is_numeric($this->request->param('param2'))) {
				$order = $this->pixie->orm->get('order',$this->request->param('param2'));
				if($order->loaded()) {
					$this->pixie->party->approveOrder($order, null, null, true);
					die(json_encode(array("status"=>"success")));
				}
			}
		}
		// Отправляем повторно письмо с подтверждением email
		// else if($this->request->param('param1')=='confirmemail') {
		// 	if(is_numeric($this->request->param('param2'))) {
		// 		$user = $this->pixie->orm->get('user',$this->request->param('param2'));
		else if($this->request->param('param1')=='sendmessage') {
			if(is_numeric($this->request->post('action'))) {
				$user = $this->pixie->orm->get('user',$this->request->param('param2'));
				if($user->loaded()) {
					$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
					$formatter->setPattern('d MMMM');
					$htmlText = "Мир вам, {$user->name}!

Вы указали этот email ({$user->email}) ". $formatter->format(new \DateTime($user->codesent)) ." при регистрации на конференцию Я МОЛОДОЙ! 2014.

Мы заметили, что на сегодняшний день вы по каким-то причинам не прошли регистрацию до конца. Напоминаем, что регистрация считается завершённой после добавления анкеты участника и внесения предоплаты. Можем ли у вас узнать, что вам помешало? Может быть, возникли какие-нибудь трудности с самой регистрацией, и вам нужна помощь?

Пожалуйста, смело обращайтесь: мы готовы ответить на ваши вопросы и постараемся помочь!
";
					if(strtotime($user->codesent) < strtotime('2014-07-01'))
						$htmlText .= "
Вы приступали к регистрации заблаговременно, и мы ценим вашу пунктуальность! К сожалению, на тот момент у нас действовала старая форма регистрации на сайте imolod.ru, которую мы заменили 1 июля на новый сайт со всеми действующими условиями. Просим у вас прощения за то, что вначале мы не предусмотрели предоплату 500 руб., а потом её пришлось ввести! И поэтому индивидуально для вас мы отменяем повышение общего регистрационного взноса после 5 августа до 2000 рублей: для вас он в любое время останется равным 1500 руб.
";
					if($user->pass && $user->active)
						$htmlText .= "
Для продолжения регистрации вам нужно войти на сайт с помощью формы для входа:
http://2014.imolod.ru/register#start
";
					else if($user->code)
						$htmlText .= "
Для продолжения регистрации вам нужно перейти по этой ссылке и придумать для себя пароль:
http://2014.imolod.ru/register/confirm?email={$user->email}&code={$user->code}
";

					$htmlText .= "
Затем вы откроете свой профиль для добавления анкет участников. Проверьте, пожалуйста, что все сведения в анкете указаны верно. Дальнейшие инструкции по оплате вы увидите, нажав кнопку оплаты.

Если у вас есть замечания или предложения по методу регистрации, сообщите их нам, пожалуйста!


Программа конференции:
https://vk.com/page-71444548_48399312

Подробные условия регистрации:
https://2014.imolod.ru/register

Инструкция по регистрации с картинками:
https://vk.com/page-71444548_48108588

Вопросы и ответы в группе ВКонтакте:
https://vk.com/board71444548

Благословений! Ждём вас!

--
Организаторы конференции Я МОЛОДОЙ! 2014 в Волгограде
info@imolod.ru
http://2014.imolod.ru
https://vk.com/imolod14
";

// echo $htmlText;

					$result = $this->pixie->email->send(
						array($user->email => "=?utf-8?B?". base64_encode($user->name) ."?="),
						array($this->pixie->config->get('party.contacts.info') => "=?utf-8?B?". base64_encode('Организаторы '. $this->pixie->config->get('party.event.short_title')) ."?="),
						"Ваша регистрация на ". $this->pixie->config->get('party.event.short_title'),
						$htmlText,
						false
						);
					if($result) {
						$newMsg = $this->pixie->orm->get('usermsg');
						$newMsg->content = $htmlText;
						$newMsg->type = 'email';
						$newMsg->date = date('Y-m-d H:i:s',time());
						$newMsg->save();
						$newMsg->add('sender', $this->pixie->auth->user());
						$newMsg->add('recipient', $user);
						$newMsg->save();
						die(json_encode(array("status"=>"success")));
					}
				}
			}
		}


		// die(json_encode(array("status"=>"error")));
		throw new \PHPixie\Exception\PageNotFound;
	}






	public function action_users() {
		if($id = $this->request->param('param1')) {
			if(is_numeric($id))
				$this->page['user'] = $this->pixie->orm->get('user',$id);
			else
				throw new \PHPixie\Exception\PageNotFound;
			if(!$this->page['user']->loaded())
				throw new \PHPixie\Exception\PageNotFound;
			$this->page['title'] = $this->page['user']->name;
			$this->page['subview'] = "admin-user";
		}
		else {
			$this->page['title'] = "Список регистраторов";
			$this->page['subview'] = "admin-users-list";
			$this->page['users'] = $this->pixie->orm->get('user')->order_by('id','desc')->find_all()->as_array();
		}
	}








	// выводим таблицу попутчиков
	public function action_fellows() {
		if(!$id = $this->request->param('param1'))
			throw new \PHPixie\Exception\PageNotFound;

		$part = $this->pixie->orm->get('participant',$id);
		if(!$part->loaded())
			throw new \PHPixie\Exception\PageNotFound;

		$this->page['title'] = "Попутчики участника {$part->firstname} {$part->lastname}";
		$this->page['subview'] = "admin-fellows";

		$this->page['fellowCity'] = $this->pixie->orm->get('participant')->where('id','<>',$id)->where('cancelled',0)->where('city',$part->city)->find_all()->as_array();
		if($part->region->loaded())
			$this->page['fellowRegion'] = $this->pixie->orm->get('participant')->where('id','<>',$id)->where('cancelled',0)->where('city','<>',$part->city)->where('and',array('region_id',$part->region->id))->find_all()->as_array();

		// $this->page['fellowCity'] = 
	}






	// выводим таблицу всех участников
	public function action_participants() {
		if(is_numeric($this->request->param('param1'))) {
			$id = $this->request->param('param1');
			$part = $this->pixie->orm->get('participant',$id);
			if(!$part->loaded())
				throw new \PHPixie\Exception\PageNotFound;

			$this->page['title'] = $part->firstname .' '. $part->lastname;
			$this->page['subview'] = "admin-participant";
			$this->page['part'] = $part;
			$this->page['partInfo'] = $this->pixie->party->providedPartInfo($part);
		}
		else if($this->request->param('param1')=='email') {
			$this->page['title'] = 'Список Email из всех анкет и пользователей';
			$this->page['content'] = "";
			$results = array();
			$list = $this->pixie->orm->get('user')->find_all();
			foreach($list as $item)
				if($item->email)
					$results[] = trim(strtolower($item->email));
			$list = $this->pixie->orm->get('participant')->where('email','<>','')->find_all();
			foreach($list as $item)
				if($item->email)
					$results[] = trim(strtolower($item->email));
			$results = array_unique($results);
			sort($results);
			$this->page['content'] .= "<div>Найдено ". count($results) . $this->pixie->party->declension(count($results),array(" различный адрес"," различных адреса"," различных адресов")) .":</div>\n<ol>\n";
			foreach($results as $item)
				$this->page['content'] .= "<li>{$item}</li>\n";
			$this->page['content'] .= "</ol>\n";
		}
		else {
			$this->page['title'] = 'Участники';
			$parts = $this->pixie->orm->get('participant')->where('cancelled',0)->order_by('created','desc')->find_all()->as_array();
			$this->page['table'] = array();

			$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
			$formatter->setPattern('d MMMM');

			$subject_types = array(
				array('Республика', 'респ.', 'республики', 0),
				array('Край', 'кр.', 'края', 1),
				array('Область', 'обл.', 'области', 1),
				array('Автономная область', 'АО', 'автономной области', 1),
				array('Автономный округ', 'АО', 'автономного округа', 1),
				array('Город', 'г.', 'города', 0)
			);

			foreach($parts as $part) {
				$check = $this->pixie->party->getSumToPay($part);
				if(!$check['needToFull'])
					$status = "Полный взнос";
				else if(!$check['needToPrepay'])
					$status = "Предоплата";
				else if($part->money > 0)
					$status = "Неполная предоплата";
				else if($part->user->active==0)
					$status = "Не активирован";
				else
					$status = "Ещё не оплатил";

				if(isset($this->pixie->config->get('party.userfields.transport.options')[$part->transport]))
					$transport = $this->pixie->config->get('party.userfields.transport.options')[$part->transport];
				else
					$transport = "";

				$waitingOrders = $part->user->orders->where('payed',0)->find_all()->as_array();
				if(count($waitingOrders)) {
					$waitingMoney = array();
					foreach($waitingOrders as $order)
						$waitingMoney[] = "<a href=\"/admin/orders/{$order->id}\">№{$order->id}</a>";
					if($status == "Ещё не оплатил")
						$status = "";
					$status .= " (в обработке: ". implode(", ", $waitingMoney) .")";

				}

				$this->page['table'][] = array(
					'имя' => array($part->id, "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>". ($this->pixie->party->getPartGrace($part) ? "<sup>л</sup>" : "")),
					// 'сообщения' => $part->user->inbox->count_all(),
					'добавлен' => array(strtotime($part->created), $formatter->format(new \DateTime($part->created))),
					'оплатил' => ($part->paydate && strtotime($part->paydate)) ? array(strtotime($part->paydate), $formatter->format(new \DateTime($part->paydate))) : array(time()+86400, ""),
					'сумма' => $part->money ? $part->money : "",
					'город' => $part->city,
					'регион' => $part->region->name .($part->region->suffix && $part->region->type ? ' '. $subject_types[$part->region->type-1][1] : ''),
					'транспорт' => array($part->transport, $transport),
					'прибыл' => array($part->attend, $part->attend ? "<i class=\"fa fa-check\"></i>" : ""),
				);
			}
			$this->page['subview'] = 'admin-participants-list';


			// Подсчёты количества людей по питанию
			$partsMeal = $this->pixie->orm->get('participant')
				->where('meal',1)
				->where('cancelled',0)
				->where(array(
					array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
					array('or',array('approved',1)),
					))
				->find_all()->as_array();
			$daysMeal = array();
			foreach($partsMeal as $part) {
				if(isset($daysMeal[$part->days]))
					$daysMeal[$part->days]++;
				else
					$daysMeal[$part->days] = 1;
			}
			$this->page['partsMeal'] = count($partsMeal);
			$this->page['daysMeal'] = $daysMeal;

			// Сколько парней и девушек питаются в субботу
			$partsSatMealMale = $this->pixie->orm->get('participant')
				->where('meal',1)
				->where('cancelled',0)
				->where('days','>',1)
				->where('gender',1)
				->where(array(
					array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
					array('or',array('approved',1)),
					))
				->count_all();
			$partsSatMealFemale = $this->pixie->orm->get('participant')
				->where('meal',1)
				->where('cancelled',0)
				->where('days','>',1)
				->where('gender',2)
				->where(array(
					array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
					array('or',array('approved',1)),
					))
				->count_all();
			$this->page['partsSatMealMale'] = $partsSatMealMale;
			$this->page['partsSatMealFemale'] = $partsSatMealFemale;
		}
	}







	public function action_meetings() {
		$this->page['title'] = 'Список прибывающих';

		$parts = $this->pixie->orm->get('participant')
			->where('cancelled',0)
			->where('city','NOT IN', $this->pixie->db->expr('("Волгоград","Волжский")'))
			->where('transport', 'IN', $this->pixie->db->expr('(1,2,4)'))
			->where(array(
				array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
				array('or',array('approved',1)),
				))
			->order_by('transport','asc')
			->order_by('created','asc')
			->find_all()->as_array();

		$waiting = $this->request->param('param1');
		// Нужно ли добавлять ещё тех, от кого оплата задерживается?
		if($waiting) {
			if(!is_numeric($waiting))
				$waiting = 5;
			$partsWait = $this->pixie->orm->get('participant')
				->where('cancelled',0)
				->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))
				->where('transport', 'IN', $this->pixie->db->expr('(1,2,4)'))
				->where('approved',0)
				->find_all()->as_array();
			foreach($partsWait as $part) {
				// Пропускаем дубликаты
				$copy = $this->pixie->orm->get('participant')->where('cancelled',0)->where('firstname',$part->firstname)->where('lastname',$part->lastname)->where('id','<>',$part->id)->count_all();
				if($copy)
					continue;
				// Ищем анкеты с платежами
				$orders = $part->user->orders->order_by('datecreated','desc')->find_all()->as_array();
				foreach($orders as $order) {
					$purpose = unserialize($order->purpose);
					if(in_array($part->id, $purpose)) {
						$age = date_diff(date_create($order->datecreated), date_create('today'))->d;
						if($age<$waiting && $part->user->id!=32) {
							$parts[] = $part;
							break;
						}
					}
				}
			}
		}

		$this->page['content'] = "Всего: ". count($parts) ."<br/>\n";
		$transport = $this->pixie->config->get('party.userfields.transport.options');

		foreach($parts as $part) {
			$age = date_diff(date_create($part->birthday), date_create('now'))->y;
			$this->page['content'] .= "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>".
				(($age>0 && $age<150) ? " ({$age})" : "") .
				"<br/>\n".
				($part->phone ? $part->phone ."<br/>\n" : "") .
				($part->email ? $part->email ."<br/>\n" : "") .
				($part->website ? "<a href=\"{$part->website}\">{$part->website}</a><br/>\n" : "") .
				($part->city ? $part->city ."<br/>\n" : "") .
				($part->transport ? "прибытие: <strong>{$transport[$part->transport]}</strong><br/>\n" : "") .
				($part->comment ? $part->comment ."<br/>\n" : "") .
				($part->house->loaded() ? "проживание: ". $part->house->name ."<br/>\n" : "") .
				"<br/>\n";
		}
	}






	public function action_birthdays() {
		$formatterShort = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$formatterShort->setPattern("d MMMM");
		$birthdays = $this->pixie->party->getBirthdays();

		$this->page['title'] = 'Именинники';
		$this->page['content'] = "";
		foreach($birthdays as $birthday)
			$this->page['content'] .= $formatterShort->format(new \DateTime($birthday['date'])) ." &mdash; {$birthday['name']} {$birthday['age']} ". $this->pixie->party->declension($birthday['age'],array('год','года','лет')) ."<br/>\n";
	}







	// География участия
	public function action_paylist() {
		$this->page['title'] = 'Список платежей';
		$this->page['subview'] = 'admin-paylist';
		$this->page['orders'] = $this->pixie->orm->get('order')->order_by('datecreated','DESC')->find_all();
	}





	// География участия
	public function action_geo() {
		$this->page['title'] = 'География участников';
		$this->page['subview'] = 'admin-geo';

		$parts = $this->pixie->orm->get('participant')->where('cancelled',0)->where('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay'))->find_all();
		
		$this->page['cities'] = $this->page['regions'] = array();
		foreach($parts as $part) {
			if(trim($part->city))
				$this->page['cities'][$part->city][] = $part->id;
			if($part->region->loaded())
				$this->page['regions'][$part->region->name][] = $part->id;
		}
		foreach($this->page['cities'] as $key=>$value)
			$this->page['cities'][$key] = count($value);
		foreach($this->page['regions'] as $key=>$value)
			$this->page['regions'][$key] = count($value);
		asort($this->page['cities']);
		asort($this->page['regions']);
		$this->page['cities'] = array_reverse($this->page['cities'],true);
		$this->page['regions'] = array_reverse($this->page['regions'],true);

	}



	public function action_paycheck() {
		$this->page['title'] = 'Проверка платежей';
		$this->page['content'] = "<p>Несоответствия платежей:</p>\n";
		$bugs = 0;

		// Список платежей, у которых суммарные счета получателей не совпадают с полученными деньгами
		$orders = $this->pixie->orm->get('order')->where('balance','>',0)->find_all()->as_array();
		foreach($orders as $order) {
			$order->purpose = unserialize($order->purpose);
			$moneyGot = 0;
			foreach($order->purpose as $partId) {
				$part = $order->user->participants->where('id',$partId)->find();
				if($part->loaded())
					$moneyGot += $part->money;
			}
			if($moneyGot != $order->balance) {
				$this->page['content'] .= "<a href=\"/admin/users/{$order->user->id}\">{$order->id}</a>: {$order->balance} => {$moneyGot}<br/>\n";
				$bugs++;
			}
		}

		$this->page['content'] .= "<p>Несоответствия получателей:</p>\n";

		$parts = $this->pixie->orm->get('participant')->where('money','>',0)->find_all()->as_array();
		foreach($parts as $part) {
			$moneyPaid = 0;
			$history = array();
			$p = 0;
			foreach($orders as $order) {
				if(in_array($part->id, $order->purpose)) {
					$moneyPaid += $order->balance;
					$history[] = $order->id .": ". implode(", ",$order->purpose);
					$p += count($order->purpose);
				}
			}
			if($p && $moneyPaid/$p != $part->money) {
				$this->page['content'] .= "{$part->firstname} {$part->lastname}: {$part->money} => {$moneyPaid} [". implode("; ",$history) ."]<br/>\n";
				$bugs++;
			}
		}

		if($bugs)
			$this->page['content'] .= "<div class=\"alert alert-danger\">Что-то может быть не в порядке</div>\n";
		else
			$this->page['content'] .= "<div class=\"alert alert-success\">Всё в порядке</div>\n";

	}

	public function action_cash() {
		$this->page['title'] = 'Наличные';
		$this->page['content'] = "";

		$orders = $this->pixie->orm->get('order')->where('cash',1)->where('cancelled',0)->where('payed',1)->find_all()->as_array();
		$users = array();
		foreach($orders as $order) {
			if(!isset($users[$order->user->id]['sum']))
				$users[$order->user->id]['sum'] = 0;
			if(!isset($users[$order->user->id]['orders']))
				$users[$order->user->id]['orders'] = array();
			if(!isset($users[$order->user->id]['name']))
				$users[$order->user->id]['name'] = $order->user->name;
			$users[$order->user->id]['sum'] += $order->balance;
			$purpose = unserialize($order->purpose);
			$part = $this->pixie->orm->get('participant',reset($purpose));
			$purpose = $part->loaded()
				? $part->firstname .' '. $part->lastname
				: "?";
			$users[$order->user->id]['orders'][] = array(
				$order->datepayed,
				$purpose,
				$part->id,
				$order->balance,
				$order->id,
				);
		}
		foreach($users as $id=>$user) {
			$this->page['content'] .= "<h4><a href=\"/admin/users/{$id}\">{$user['name']}</a>: {$user['sum']}</h4>\n<table class=\"table\">\n";
			foreach($user['orders'] as $order)
				$this->page['content'] .= "<tr><td>{$order[0]}</td><td><a href=\"/admin/participants/{$order[2]}\">{$order[1]}</a></td><td>{$order[3]}</td>". ($this->pixie->auth->user()->id==32 ? "<td><button class=\"btn btn-default btn-xs payment-cancel\" data-orderid=\"{$order[4]}\"><i class=\"fa fa-times\"></i></button></td>" : "") ."</tr>\n";
			$this->page['content'] .= "</table>\n";
		}
	}


	public function action_hello() {
		// $param1 = $this->request->param('param1');
		$param1 = $this->request->post('id');
		if(is_numeric($param1)) {
			$part = $this->pixie->orm->get('participant',$param1);
			if($part->loaded()) {
				$this->view = $this->pixie->view('admin-clear');
				// $this->page['subview'] = 'admin-participant-hello';
				// $this->view->part = $part;
				$this->view->subview = "admin-participant";
				$this->page['part'] = $part;
				$this->page['partInfo'] = $this->pixie->party->providedPartInfo($part);
				// $this->view->page = $this->page;
			}
			else
				throw new \PHPixie\Exception\PageNotFound;
		}
		else {
			$this->page['title'] = 'Приветствие';
			$this->page['content'] =<<<EOF
<div id="hello">
<input type="text" class="typeahead form-control input-lg" placeholder="Фамилия или имя"/>
</div>
<div id="hello-wrapper"></div>

EOF;
			$this->page['scripts'] = array("/scripts/typeahead.bundle.min.js");
		}
	}







	// После этого возьмем подходящие участки кода из решений выше и сделаем функцию форматирования:
	private function phone($phone = '', $convert = true, $trim = true)
	{
		global $phoneCodes; // только для примера! При реализации избавиться от глобальной переменной.

		$phoneCodes = Array(
			'7'=>Array(
					'name'=>'Russia',
					'cityCodeLength'=>3,
					'zeroHack'=>false,
					'exceptions'=>Array(8442),
					'exceptions_max'=>5,
					'exceptions_min'=>3,
				),
			'38'=>Array(
					'name'=>'Ukraine',
					'cityCodeLength'=>3,
					'zeroHack'=>false,
					'exceptions'=>Array(),
					'exceptions_max'=>5,
					'exceptions_min'=>3,
				),
		);

		if (empty($phone)) {
			return '';
		}
		// очистка от лишнего мусора с сохранением информации о «плюсе» в начале номера
		$phone=trim($phone);
		$plus = ($phone[ 0] == '+');
		$phone = preg_replace("/[^0-9A-Za-z]/", "", $phone);
		$OriginalPhone = $phone;
	 
		// конвертируем буквенный номер в цифровой
		if ($convert == true && !is_numeric($phone)) {
			$replace = array('2'=>array('a','b','c'),
			'3'=>array('d','e','f'),
			'4'=>array('g','h','i'),
			'5'=>array('j','k','l'),
			'6'=>array('m','n','o'),
			'7'=>array('p','q','r','s'),
			'8'=>array('t','u','v'),
			'9'=>array('w','x','y','z'));
	 
			foreach($replace as $digit=>$letters) {
				$phone = str_ireplace($letters, $digit, $phone);
			}
		}
	 
		// заменяем 00 в начале номера на +
		if (substr($phone,  0, 2)=="00")
		{
			$phone = substr($phone, 2, strlen($phone)-2);
			$plus=true;
		}
	 
		// если телефон длиннее 7 символов, начинаем поиск страны
		if (strlen($phone)>7)
		foreach ($phoneCodes as $countryCode=>$data)
		{
			$codeLen = strlen($countryCode);
			if (substr($phone,  0, $codeLen)==$countryCode)
			{
				// как только страна обнаружена, урезаем телефон до уровня кода города
				$phone = substr($phone, $codeLen, strlen($phone)-$codeLen);
				$zero=false;
				// проверяем на наличие нулей в коде города
				if ($data['zeroHack'] && $phone[ 0]=='0')
				{
					$zero=true;
					$phone = substr($phone, 1, strlen($phone)-1);
				}
	 
				$cityCode=NULL;
				// сначала сравниваем с городами-исключениями
				if ($data['exceptions_max']!= 0)
				for ($cityCodeLen=$data['exceptions_max']; $cityCodeLen>=$data['exceptions_min']; $cityCodeLen--)
				if (in_array(intval(substr($phone,  0, $cityCodeLen)), $data['exceptions']))
				{
					$cityCode = ($zero? "0": "").substr($phone,  0, $cityCodeLen);
					$phone = substr($phone, $cityCodeLen, strlen($phone)-$cityCodeLen);
					break;
				}
				// в случае неудачи с исключениями вырезаем код города в соответствии с длиной по умолчанию
				if (is_null($cityCode))
				{
					$cityCode = substr($phone,  0, $data['cityCodeLength']);
					$phone = substr($phone, $data['cityCodeLength'], strlen($phone)-$data['cityCodeLength']);
				}
				// возвращаем результат
				return ($plus? "+": "").$countryCode.'('.$cityCode.')'.$this->phoneBlocks($phone);
			}
		}
		// возвращаем результат без кода страны и города
		return ($plus? "+": "").$this->phoneBlocks($phone);
	}
	 
	// функция превращает любое число в строку формата XX-XX-... или XXX-XX-XX-... в зависимости от четности кол-ва цифр
	private function phoneBlocks($number){
		$add='';
		if (strlen($number)%2)
		{
			$add = $number[ 0];
			$add .= (strlen($number)<=5? "-": "");
			$number = substr($number, 1, strlen($number)-1);
		}
		return $add.implode("-", str_split($number, 2));
	}

}
