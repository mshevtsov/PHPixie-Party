<?php
namespace PHPixie;

Class Party {
	public $pixie;
	public $dirs = array();
	public $rootDir;

	public function __construct($pixie) {
		$this->pixie = $pixie;
		$this->rootDir = $this->pixie->root_dir;
		$pixie->assets_dirs[] = dirname(dirname(dirname(__FILE__))).'/assets/';
	}

	public function getSumToPay($parts=null) {
		if(!$parts)
			$parts = $this->getParticipants();
		if(!$parts)
			return false;
		if(!is_array($parts))
			$parts = array($parts);

		// Временный тест
		// if($this->pixie->auth->user()->role=='admin')
		// 	$curPeriod = $this->getCurrentPeriod('Today +2 day');
		// else
			$curPeriod = $this->getCurrentPeriod();
		$periods = $this->pixie->config->get("party.periods");
		$gracePeriod = reset($periods);
		$sumFree = $this->getFreeMoney();

		// Подсчитываем разные отдельные суммы к оплате для вывода
		// sumPaid - уже положено на счёт к этому моменту
		// sumMeal - стоимость питания всех участников вместе
		// sumBasic - сумма базовых взносов всех участников за вычетом питания
		// sumPrepay - сумма предоплат всех участников
		// sumFree - сумма, свободно висящая на счету пользователя
		// countMeal - число участников с оплатой питания
		// sumFull - полная окончательная стоимость
		// needToPrepay - сколько осталось заплатить за предоплату
		// needToFull - сколько осталось заплатить до полного взноса
		// toPay - массив с этими числами индивидуально для каждого
		// sumExcess - сколько лишних денег (уплачено уже больше полного взноса)

		$toPay = array();
		$sumPaid = $sumPrepay = $sumBasic = $sumMeal = $countMeal = $needToPrepay = $needToFull = 0;
		foreach($parts as $part) {
			// Учитываем, действует ли льготный период для каждого участника

			if($this->getPartGrace($part/*, $this->pixie->auth->user()->role=='admin' ? "Today +2 day" : "Today")*/))
				$partPeriod = $gracePeriod;
			else {
				if($part->paydate)
					$partPeriod = $this->getCurrentPeriod($part->paydate);
				// если неудачные попытки платежа засчитывать как успевание в срок
				else if($graceExtraPeriod = $this->getPartPayPeriod($part))
					$partPeriod = $graceExtraPeriod;
				else {
					$lastPeriod = end($periods);
					if($part->approved && $curPeriod==$lastPeriod)
						$partPeriod = prev($periods);
					else
						$partPeriod = $curPeriod;
				}
			}
// print_r($partPeriod);


			$sumPrepay += $partPeriod['prices']['prepay'];
			$sumBasic += $partPeriod['prices']['basic'];
			$toPay[$part->id]['prepay'] = $partPeriod['prices']['prepay'];
			$toPay[$part->id]['full'] = $partPeriod['prices']['basic'];
			// Подсчёт стоимости питания за выбранные дни
			if($part->meal) {
				$countMeal++;
				$dayslist = unserialize($part->dayslist);
				if(is_array($dayslist) && count($dayslist)) {
					foreach($dayslist as $key=>$value) {
						if($value) {
							$sumMeal += $partPeriod['prices']['mealbyday'][$key];
							$toPay[$part->id]['full'] += $partPeriod['prices']['mealbyday'][$key];
						}
					}
				}
			}

// if($this->pixie->auth->user()->role=='admin') {
// 	print $sumMeal;
// }
			// сколько денег уже заплачено за участника
			$sumPaid += $part->money;

			// сколько ещё не хватает для предоплаты у этого участника
			$toPay[$part->id]['prepay'] = ($toPay[$part->id]['prepay'] > $part->money)
				? ($toPay[$part->id]['prepay'] - $part->money)
				: 0;
			$needToPrepay += $toPay[$part->id]['prepay'];

			// сколько ещё не хватает для полного взноса у этого участника
			$toPay[$part->id]['full'] = ($toPay[$part->id]['full'] > $part->money)
				? ($toPay[$part->id]['full'] - $part->money)
				: 0;
			$needToFull += $toPay[$part->id]['full'];
		}
		$sumFull = $sumBasic + $sumMeal;

		// остающиеся деньги от полного взноса
		$sumExcess = ($sumPaid + $sumFree) > $sumFull
			? ($sumPaid + $sumFree - $sumFull)
			: 0;

		return array(
			'sumPrepay' =>		$sumPrepay,
			'sumBasic' =>		$sumBasic,
			'sumMeal' =>		$sumMeal,
			'sumFull' =>		$sumFull,
			'sumPaid' =>		$sumPaid,
			'sumFree' =>		$sumFree,
			'countMeal' =>		$countMeal,
			'needToPrepay' =>	$needToPrepay,
			'needToFull' =>		$needToFull,
			'sumExcess' =>		$sumExcess,
			'toPay' =>			$toPay,
		);
	}



	// Распределить все свободные деньги по нуждающимся участникам
	public function spendFreeMoney($parts, $check) {
		if(!$user = $this->pixie->auth->user())
			return false;

		// Когда нечего тратить
		if(!$this->getFreeMoney())
			return $check;

		if(!$parts)
			$parts = $this->getParticipants();
		if(!is_array($parts))
			$parts = array($parts);

		// пробегаемся по нуждам указанных участников по порядку
		// сначала по нуждающимся в предоплате
		$curPeriod = $this->getCurrentPeriod();
		foreach($parts as $part) {
			if($check['toPay'][$part->id]['prepay']) {
				if($check['toPay'][$part->id]['prepay'] <= $user->money) {
					$part->money += $check['toPay'][$part->id]['prepay'];
					$user->money -= $check['toPay'][$part->id]['prepay'];
					if(!$part->paydate && $part->money>=$curPeriod['prices']['prepay'])
						$part->paydate = date('Y-m-d H:i:s',time());
					$part->save();
					$user->save();
				}
				else {
					$part->money += $user->money;
					$user->money = 0;
					$part->save();
					$user->save();
					return $this->getSumToPay($parts);
				}
			}
		}
		// теперь по нуждающимся в полном взносе
		foreach($parts as $part) {
			if($check['toPay'][$part->id]['full']) {
				if($check['toPay'][$part->id]['full'] <= $user->money) {
					$part->money += $check['toPay'][$part->id]['full'];
					$user->money -= $check['toPay'][$part->id]['full'];
					if(!$part->paydate && $part->money>=$curPeriod['prices']['prepay'])
						$part->paydate = date('Y-m-d H:i:s',time());
					$part->save();
					$user->save();
				}
				else {
					$part->money += $user->money;
					$user->money = 0;
					$part->save();
					$user->save();
					return $this->getSumToPay($parts);
				}
			}
		}

		return getSumToPay($parts);
	}





	// Возвращает в виде массива одного или всех участников, зарегистрированных
	// текущим пользователем и не удалённых.
	// Если id - число, то возвращаем участника с этим id
	// Если id - 'all', то всех
	public function getParticipants($id='all') {
		if(!$user = $this->pixie->auth->user())
			return false;
		if($id=='all')
			$participants = $user->participants->where('cancelled','<>','1')->find_all()->as_array();
		else if(is_numeric($id))
			$participants = $user->participants->where('id',$id)->where('cancelled','<>','1')->find_all()->as_array();
		else
			return false;
		return empty($participants) ? false : $participants;
	}




	// Подсчитываем деньги пользователя, находящиеся в подвешенном состоянии:
	// на счету самого пользователя и у его отказавшихся участников,
	// за вычетом суммы их предоплат.
	// Переводим деньги с удалённых участников в карман пользователю
	public function getFreeMoney() {
		if(!$user = $this->pixie->auth->user())
			return false;

		$curPeriod = $this->getCurrentPeriod();

		$participants = $user->participants->where('cancelled','1')->where('money','>',$curPeriod['prices']['prepay'])->find_all()->as_array();

		if(count($participants)) {
			$userChanged = false;
			foreach($participants as $part) {
				if($part->money > $curPeriod['prices']['prepay']) {
					$user->money += ($part->money - $curPeriod['prices']['prepay']);
					$part->money = $curPeriod['prices']['prepay'];
					$part->save();
					if(!$userChanged)
						$userChanged = true;
				}
			}
			if($userChanged)
				$user->save();
		}
		return $user->money;
	}



	// Возвращаем текущий ценовой период либо false, если регистрация закончилась
	// Формат результата в виде ветки ассоциативного массива из конфига
	public function getCurrentPeriod($day="Today") {
		$periods = $this->pixie->config->get("party.periods");

		// Округляем дату-время до одной даты
		if($day!="Today")
			$day = date('Y-m-d',strtotime($day));

		$result = array();
		foreach($periods as $period) {
			if(strtotime($period['lastday'])>=strtotime($day) &&
				(empty($result) || strtotime($period['lastday'])<strtotime($result['lastday'])))
				$result = $period;

			// if(strtotime($period['lastday'])>=strtotime($day)) {
			// 	$result = $period;
			// 	break;
			// }
		}
		return !empty($result) ? $result : false;
	}



	// очень деловая функция. это то, что мы делаем, принимая деньги в базу
	public function approveOrder(&$order, $onpay_id, $balance, $again=false) {
		if($order->payed==1 && !$again)
			return false;

		// Если регистрация уже закончилась, то деньги остаются просто у пользователя
		if(!$curPeriod = $this->getCurrentPeriod($order->datepayed ? $order->datepayed : null)) {
			if($order->user->loaded()) {
				$order->user->money += $order->balance;
				$order->user->save();
				return true;
			}
			else
				return false;
		}

		if(!$again) {
			$notifyBalance = $balance;
			$order->payed = 1;
			$order->datepayed = date('Y-m-d H:i:s',time());
			$order->onpay_id = $onpay_id;
			$order->balance = $balance;
			$order->save();
		}
		else
			$balance = $order->balance;

		if($order->purpose) {
			$ids = unserialize($order->purpose);

			// считаем сколько кому положить
			// Собираем всех участников, к которым относится запрос
			if(count($ids)==1)
				$parts = $this->pixie->orm->get('participant')
					->where('id',reset($ids))
					->where('cancelled','<>','1')
					->find_all()->as_array();
			else
				$parts = $this->pixie->orm->get('participant')
					->where('id', 'IN', $this->pixie->db->expr('('. implode(',',$ids) .')'))
					->where('cancelled','<>','1')
					->find_all()->as_array();

			$sumToPay = $this->getSumToPay($parts);
/*
			// ведём массив участников со значениями полной и предоплаты
			$toPay = array();
			$sumMeal = $sumBasic = $sumPrepay = 0;
			foreach($parts as $part) {
				$sumPrepay += $curPeriod['prices']['prepay'];
				$sumBasic += $curPeriod['prices']['basic'];
				$toPay[$part->id]['prepay'] = $curPeriod['prices']['prepay'];
				$toPay[$part->id]['full'] = $curPeriod['prices']['basic'];
				if($part->meal) {
					$sumMeal += $curPeriod['prices']['daysmeal'][$part->days];
					$toPay[$part->id]['full'] += $curPeriod['prices']['daysmeal'][$part->days];
				}
			}
			$sumFull = $sumBasic + $sumMeal;
*/

// http://2014.imolod.ru/register/onpay?type=pay&onpay_id=9738938&amount=2000.0&balance_amount=2000.0&balance_currency=RUR&order_amount=2000.0&order_currency=RUR&exchange_rate=1.0&pay_for=1212&paymentDateTime=2014-08-04T11%3A30%3A23%2B04%3A00&note&user_email=rovinskay_kiss74%40mail.ru&user_phone=%2B89128032815&protection_code&day_to_expiry&paid_amount=2000.0&md5=67FAE64EB560DD69D46191543A1D27EC

			// если сумма перевода совпадает с суммой предоплаты для целевых участников
			// if((float)$balance==$sumPrepay) {
			if((float)$balance==$sumToPay['needToPrepay']) {
				foreach($parts as $part) {
					// $balance -= $toPay[$part->id]['prepay'];
					// $part->money += $toPay[$part->id]['prepay'];

$balance -= $sumToPay['toPay'][$part->id]['prepay'];
$part->money += $sumToPay['toPay'][$part->id]['prepay'];



					if(!$part->paydate && $part->money>=$curPeriod['prices']['prepay'])
						$part->paydate = date('Y-m-d H:i:s',time());
					$part->save();
				}
			}
			// если сумма перевода совпадает с полным взносом для них
			// else if((float)$balance==$sumFull) {
			else if((float)$balance==$sumToPay['needToFull']) {
				foreach($parts as $part) {
					// $balance -= $toPay[$part->id]['full'];
					// $part->money += $toPay[$part->id]['full'];

$balance -= $sumToPay['toPay'][$part->id]['full'];
$part->money += $sumToPay['toPay'][$part->id]['full'];

					if(!$part->paydate && $part->money>=$curPeriod['prices']['prepay'])
						$part->paydate = date('Y-m-d H:i:s',time());
					$part->save();
				}
			}
			// если не совпадает ни с тем, ни с другим, кладём на счёт пользователя
			else if($order->user->loaded()) {
				$order->user->money += $order->balance;
				$order->user->save();
			}
			// если даже не нашёлся такой пользователь, ничего не делаем
			else
				return false;
		}
		// если в заказе не указано, кому он предназначен,
		// то кладём на счёт пользователя
		else if($order->user->loaded()) {
			$order->user->money += $order->balance;
			$order->user->save();
		}
		else
			return false;

		if(!$again) {
			$message = "PAYMENT: ". $notifyBalance;
			if($order->email)
				$message .= ", FROM: {$order->email}";
			$sms = new \Zelenin\smsru($this->pixie->config->get("party.notification.api_key"));
			$sms->sms_send($this->pixie->config->get("party.notification.admin_number"), $message);

			if($order->email)
				$this->send_confirmation($order->email, $notifyBalance, isset($order->user->name) ? $order->user->name : null);
		}

		return true;
	}





	public function providedPartInfo(&$part) {
		$result = array(
			'totalPoints' => 0,
			'userPoints' => 0,
			'percent' => 0,
			'canProvide' => array(),
		);
		$fields = $this->pixie->config->get("party.userfields");

		// Берём участника
		if(is_numeric($part))
			if(!$part = $this->pixie->orm->get('participant',$part))
				return false;

		foreach($fields as $key => $value) {
			if(isset($value['points'])) {

				// Что мы делаем с местными:
				if(in_array($part->city, $this->pixie->config->get("party.event.host_city"))) {
					// пропускаем поля "жильё" и "транспорт"
					if($key=='accomodation' || $key=='transport')
						continue;
				}
				$result['totalPoints'] += $value['points'];

				// Определяем незаполненные поля, чтобы пропустить их учёт участнику
				if($value['type']=='date' && $part->$key=='0000-00-00') {
					$result['canProvide'][] = $key;
					continue;
				}
				if($key=='birthday' && date_diff(date_create($part->$key), date_create('now'))->y <= 1) {
					$result['canProvide'][] = $key;
					continue;
				}
				if(($value['type']=='text' || $value['type']=='tel' || $value['type']=='url' || $value['type']=='email')
					&& trim($part->$key)=='') {
					$result['canProvide'][] = $key;
					continue;
				}
				if($value['type']=='boolset' && !count(unserialize($part->$key))) {
					$result['canProvide'][] = $key;
					continue;
				}
				if($value['type']=='select' && is_array($value['options']) && !in_array($part->$key,array_keys($value['options']))) {
					$result['canProvide'][] = $key;
					continue;
				}
				else if($value['type']=='select' && $value['options']=='relationship' && !$part->$key->loaded()) {
					$result['canProvide'][] = $key;
					continue;
				}

				$result['userPoints'] += $value['points'];
			}
		}
		$result['percent'] = round($result['userPoints'] / $result['totalPoints'] * 100);
		return $result;
	}



	// Выдаёт количество попутчиков участника
	public function getFellowsCountByPart(&$part, $numberOnly=true, $cityOnly=false) {
		if(is_numeric($part)) {
			$part = $this->pixie->orm->get('participant',$part);
			if(!$part->loaded())
				return false;
		}
		$result = $this->pixie->orm->get('participant')->where('id','<>',$part->id)->where('cancelled',0)->where('city',$part->city)->count_all();
		if(!$cityOnly && $part->region->loaded())
			$result += $this->pixie->orm->get('participant')->where('id','<>',$part->id)->where('cancelled',0)->where('city','<>',$part->city)->where('region_id',$part->region->id)->count_all();
		if(!$numberOnly) {
			if(!$result)
				$result = 'нет попутчиков';
			else
				$result .= ' '. $this->declension($result, array('попутчик','попутчика','попутчиков'));
		}
		return $result;
	}



	// Узнаём, применима ли льготная регистрация участнику
	public function getPartGrace(&$part, $day="Today") {
		if(is_numeric($part)) {
			$part = $this->pixie->orm->get('participant',$part);
			if(!$part->loaded())
				return false;
		}

		$periods = $this->pixie->config->get("party.periods");
		$gracePeriod = reset($periods);

		// Проверяем наиболее явные условия:
		// 1) Установленная вручную льгота для участника
		// 2) Установленная вручную льгота для его регистраторв
		// 3) Оплата за участника получена до окончания льготного периода
		// 4) Льготный период прямо сейчас ещё не кончился
		if($part->grace
			|| $part->user->grace
			|| ($part->paydate && strtotime($part->paydate) < strtotime($gracePeriod['lastday'] ." +1 day"))
			|| (strtotime($day) <= strtotime($gracePeriod['lastday']))
			)
			return true;
		// Если ни одно из этих 4 условий не выполнилось, смотрим историю платежей:
		// 5) Есть хоть один успешный платёж для участника до конца льготного периода
		else {
			$orders = $part->user->orders->find_all()->as_array();
			foreach($orders as $order) {
				if(!$order->payed)
					continue;
				$purpose = unserialize($order->purpose);
				if(in_array($part->id, $purpose) && (strtotime($order->datecreated) < strtotime($gracePeriod['lastday'] ." +1 day")))
					return true;
			}
			return false;
		}
	}




	// Узнаём, применима ли дополнительная льгота из-за неполадок при платежах
	public function getPartPayPeriod(&$part) {
		if(is_numeric($part)) {
			$part = $this->pixie->orm->get('participant',$part);
			if(!$part->loaded())
				return false;
		}

		$periods = $this->pixie->config->get("party.periods");
		$gracePeriod = reset($periods);
		$numPeriods = array_keys($periods);

		// Находим самый ранний платёж, даже несостоявшийся
		$orders = $part->user->orders->find_all();
		foreach($orders as $order) {
			$purpose = unserialize($order->purpose);
			if(in_array($part->id, $purpose)) {
				$candidates = array();
				foreach($periods as $key=>$period) {
					if($key!=1 && strtotime($order->datecreated) < strtotime($period['lastday'] ." +1 day")) {
						$candidates[] = $key;
						break;
					}
				}
				$result = max($candidates);

				if(end($numPeriods)==$result && $part->approved)
					$result--;
				return $periods[$result];
			}
		}
		return false;
	}




	// Возвращает статус процесса расселения в массиве:
	// 0 => процент расселённых участников из тех, которые нуждающихся в нём
	// 1 => число расселённых участников
	// 2 => число нуждающихся в нём участников
	// 3 => число занятых домов
	public function getSettlementProgress($waiting=true) {
		$guests = $this->pixie->orm->get('participant')
			->where('cancelled',0)
			->where('accomodation', 'NOT IN', $this->pixie->db->expr('(3,4)'))
			->where(array(
				array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
				array('or',array('approved',1)),
				))
			->find_all()->as_array();

		// Нужно ли добавлять ещё тех, от кого оплата задерживается?
		if($waiting) {
			if(!is_numeric($waiting))
				$waiting = 5;
			$partsWait = $this->pixie->orm->get('participant')
				->where('cancelled',0)
				->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))
				->where('accomodation', 'NOT IN', $this->pixie->db->expr('(3,4)'))
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
							$guests[] = $part;
							break;
						}
					}
				}
			}
		}


		$settled = 0;
		$houses = array();
		foreach($guests as $guest)
			if($guest->house->loaded()) {
				$settled++;
				$houses[] = $guest->house->id;
			}
		$houses = array_unique($houses);
		return array(
			round(($settled / count($guests))*100),
			$settled,
			count($guests),
			count($houses),
			);
	}




	public function getBirthdays() {
		$date_start = $this->pixie->config->get('party.event.date_start');
		$date_end = $this->pixie->config->get('party.event.date_end');
		$parts = $this->pixie->orm->get('participant')->where('cancelled',0)->find_all()->as_array();
		$dsy = date('Y-',strtotime($date_start));
		$dey = date('Y-',strtotime($date_end));
		$results = array();
		$resultSort = array();
		foreach($parts as $part) {
			$userbd = date('m-d',strtotime($part->birthday));
			if(strtotime($dsy . $userbd)>=strtotime($date_start) && strtotime($dsy . $userbd)<=strtotime($date_end) || strtotime($dey . $userbd)>=strtotime($date_start) && strtotime($dey . $userbd)<=strtotime($date_end)) {
				if(strtotime($dsy . $userbd)>=strtotime($date_start) && strtotime($dsy . $userbd)<=strtotime($date_end)) {
					$age = date_diff(date_create($part->birthday), date_create($date_end))->y;
					$results[] = array(
						'age' => $age,
						'date' => $dsy . $userbd,
						'name' => "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>",
						);
					$resultSort[] = $dsy . $userbd;
				}
			}
		}
		array_multisort($resultSort, $results);
		return $results;
	}




	public function log($type, $comment, $id1=false, $id2=false) {
		$log = $this->pixie->orm->get('log');
		$log->type = $type;
		$log->author = $this->pixie->auth->user()->id;
		if($id1)
			$log->id1 = $id1;
		if($id2)
			$log->id2 = $id2;
		$log->message = $comment;
		$log->save();
		return true;
	}



	public function daysLeft($human = true) {
		$result = (strtotime($this->pixie->config->get('party.event.date_start')) - strtotime('today')) / 86400;
		if($human)
			$result .= " ". $this->declension($result, array("день","дня","дней"));
		return $result;
	}


	// Склонение числительных
	public function declension($int, $expr) {
		if(count($expr) < 3)
			$expr[2] = $expr[1];
		$count = (int)$int % 100;
		if($count>4 && $count<21)
			return $expr[2];
		else {
			$count = $count % 10;
			if($count==1)
				return $expr[0];
			elseif($count>1 && $count<5)
				return $expr[1];
			else
				return $expr[2];
		}
	}




	public function makeWebsiteLink($str) {
		$match = array();
		if(preg_match("/^(https?\:\/\/)?(www\.)?([\w-\.]+)(\/(.+))?$/", $str, $match)) {
			switch($match[3]) {
				case 'vk.com':
					$icon = "vk";
					break;
				case 'facebook.com':
					$icon = "facebook-square";
					break;
				case 'twitter.com':
					$icon = "twitter";
					break;
				default:
					$icon = "external-link";
			}
			return "<i class=\"fa fa-{$icon} fa-fw\"></i> <a href=\"http://{$match[3]}{$match[4]}\" target=\"_blank\">". ($icon=="external-link" ? $match[3].$match[4] : $match[5]) ."</a>";
		}
		else
			return false;
	}



	/**
	 * Calculates the great-circle distance between two points, with
	 * the Vincenty formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 * Нашёл здесь: http://stackoverflow.com/a/10054282
	 */
	public function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);

		$lonDelta = $lonTo - $lonFrom;
		$a = pow(cos($latTo) * sin($lonDelta), 2) +
		pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

		$angle = atan2(sqrt($a), $b);
		return $angle * $earthRadius;
	}



	private function send_confirmation($email, $sum, $name=null) {
		$emailTitle = "Подтверждение оплаты";
		$emailContent = "<p>Мир вам". ($name ? ", {$name}!" : "") ."</p>\n<p>Уведомляем, что ваш перевод на сумму {$sum} получен. Спасибо!</p>\n<p>Следите за новостями о конференции в группе <a href=\"". $this->pixie->config->get("party.contacts.group") ."\">". $this->pixie->config->get("party.contacts.group") ."</a> и пользуйтесь системой управления вашим участием: <a href=\"http://". $this->pixie->config->get("party.event.domain") ."/register/profile\">http://". $this->pixie->config->get("party.event.domain") ."/register/profile</a></p>\n";
		ob_start();
		require_once $this->pixie->find_file('views','email');
		$htmlText = ob_get_clean();

		$recepient = $name
			? array($email => "=?utf-8?B?". base64_encode($name) ."?=")
			: $email;
		$result = $this->pixie->email->send(
			$recepient,
			array($this->pixie->config->get("party.contacts.info")=>'Организаторы '. $this->pixie->config->get("party.event.short_title")),
			$this->pixie->config->get("party.event.short_title") .": оплата получена",
			$htmlText,
			true
			);
		return $result;
	}

}
