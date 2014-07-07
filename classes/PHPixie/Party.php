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

		$curPeriod = $this->getCurrentPeriod();
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
			$sumPrepay += $curPeriod['prices']['prepay'];
			$sumBasic += $curPeriod['prices']['basic'];
			$toPay[$part->id]['prepay'] = $curPeriod['prices']['prepay'];
			$toPay[$part->id]['full'] = $curPeriod['prices']['basic'];
			if($part->meal) {
				$countMeal++;
				$sumMeal += $curPeriod['prices']['daysmeal'][$part->days];
				$toPay[$part->id]['full'] += $curPeriod['prices']['daysmeal'][$part->days];
			}

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
				if($check['toPay'][$part->id]['prepay'] < $user->money) {
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
				if($check['toPay'][$part->id]['full'] < $user->money) {
					$part->money += $check['toPay'][$part->id]['full'];
					$user->money -= $check['toPay'][$part->id]['full'];
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
	public function getCurrentPeriod() {
		$periods = $this->pixie->config->get("party.periods");

		$result = array();
		foreach($periods as $period) {
			if(strtotime($period['lastday'])>strtotime('Today') && (empty($result) || strtotime($period['lastday'])<strtotime($result['lastday'])))
				$result = $period;
		}
		return !empty($result) ? $result : false;
	}



	// очень деловая функция. это то, что мы делаем, принимая деньги в базу
	public function approveOrder($order, $onpay_id, $balance) {
		if($order->payed==1)
			return false;

		$notifyBalance = $balance;

		$order->payed = 1;
		$order->datepayed = date('Y-m-d H:i:s',time());
		$order->onpay_id = $onpay_id;
		$order->balance = $balance;
		$order->save();

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
			// Если регистрация уже закончилась, то деньги остаются просто у пользователя
			if(!$curPeriod = $this->getCurrentPeriod()) {
				if($order->user->loaded()) {
					$order->user->money += $order->balance;
					$order->user->save();
					return true;
				}
				else
					return false;
			}
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

			// если сумма перевода совпадает с суммой предоплаты для целевых участников
			if((float)$balance==$sumPrepay) {
				foreach($parts as $part) {
					$balance -= $toPay[$part->id]['prepay'];
					$part->money += $toPay[$part->id]['prepay'];
					if(!$part->paydate && $part->money>=$curPeriod['prices']['prepay'])
						$part->paydate = date('Y-m-d H:i:s',time());
					$part->save();
				}
			}
			// если сумма перевода совпадает с полным взносом для них
			else if((float)$balance==$sumFull) {
				foreach($parts as $part) {
					$balance -= $toPay[$part->id]['full'];
					$part->money += $toPay[$part->id]['full'];
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

		$message = "PAYMENT: ". $notifyBalance;
		if($order->email)
			$message .= ", FROM: {$order->email}";
		$sms = new \Zelenin\smsru($this->pixie->config->get("party.notification.api_key"));
		$sms->sms_send($this->pixie->config->get("party.notification.admin_number"), $message);

		if($order->email)
			$this->send_confirmation($order->email, $notifyBalance, isset($order->user->name) ? $order->user->name : null);

		return true;
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
