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

		$partsReady = $this->pixie->orm->get('participant')->where('money','>=',$prepay)->count_all();
		$partsReadyToday = $this->pixie->orm->get('participant')->where('money','>=',$prepay)->where('paydate','>',date('Y-m-d 00:00:00',time()))->where('cancelled',0)->count_all();


		$users = $this->pixie->orm->get('user')->count_all();
		$usersToday = $this->pixie->orm->get('user')->where('codesent','>',date('Y-m-d 00:00:00',time()))->count_all();

		$orders = $this->pixie->orm->get('order')->count_all();
		$ordersToday = $this->pixie->orm->get('order')->where('datecreated','>',date('Y-m-d 00:00:00',time()))->count_all();
		$ordersPayedToday = $this->pixie->orm->get('order')->where('payed',1)->where('datepayed','>',date('Y-m-d 00:00:00',time()))->count_all();

		$ordersPayed = $this->pixie->orm->get('order')->where('payed',1)->where('balance','>',0)->find_all()->as_array();
		$payedToday = $payedTotal = 0;
		foreach($ordersPayed as $order) {
			$payedTotal += $order->balance;
			if(strtotime($order->datepayed)>strtotime('today'))
				$payedToday += $order->balance;
		}

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
		$this->page['payedTotalShort'] = floor($payedTotal/1000) ."<small>". ($payedTotal % 1000) ."</small>";
		$this->page['payedToday'] = $payedToday;


		$this->page['comments'] = $this->pixie->orm->get('participant')->where('comment','<>','')->order_by('created','desc')->where('cancelled',0)->find_all()->as_array();

	}







	public function action_comments() {
		$this->page['title'] = 'Комментарии участников';
		$this->page['participants'] = $this->pixie->orm->get('participant')->where('comment','<>','')->order_by('created','desc')->find_all()->as_array();
		$this->page['subview'] = 'admin-comments';
	}







	public function action_repair() {
		$this->page['title'] = "Исправление сведений в неверном формате";
		$this->page['subview'] = "admin-repair";

		$parts = $this->pixie->orm->get('participant')->find_all()->as_array();

		// Хранилище для списка кандидатов на исправление.
		// Сначала посмотрим его для проверки
		$this->page['candidates'] = array(
			'phones' => array(),
			'websites' => array(),
			);

		foreach($parts as $part) {
			if($part->phone) {
				$phone = $part->phone;
				$phone = preg_replace("/^(79\d{2}[\-\s]?\d{3}[\-\s]?\d{2}[\-\s]?\d{2})$/", "+$1", $phone);
				$phone = preg_replace("/^(9\d{2}[\-\s]?\d{3}[\-\s]?\d{2}[\-\s]?\d{2})$/", "+7$1", $phone);
				$phone = preg_replace("/^8[\-\s\(]?9/", "+7-9", $phone);
				$phone = $this->phone($phone);

				if($phone != $part->phone)
					$this->page['candidates']['phones'][] = array($part->phone, $phone);

				// $part->save();
			}
			if($part->website) {
				$website = $part->website;
				if(!preg_match("/^https?:\/\//", $website))
					$website = "http://". $website;
				$website = preg_replace("/m\.vk\.com/", "vk.com", $website);
				$website = preg_replace("/http\:\/\/vk\.com/", "https://vk.com", $website);
				$website = preg_replace("/vk\.com\/[\w]*#([\w]*)/", "vk.com$2", $website);

				if($website != $part->website)
					$this->page['candidates']['websites'][] = array($part->website, $website);

				// $part->save();

			}
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
			// $this->page['content'] .= "email={$email}, name={$firstname} {$lastname}, codesent={$created}<br/>\n";
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



















	// выводим таблицу попутчиков
	public function action_fellows() {
		if(!$id = $this->request->param('id'))
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
		if($id = $this->request->param('id')) {
			$part = $this->pixie->orm->get('participant',$id);
			if(!$part->loaded())
				return $this->redirect("/admin/participants");

			$this->page['title'] = $part->firstname .' '. $part->lastname;
			$this->page['subview'] = "admin-participant";
			$this->page['part'] = $part;
			$this->page['partInfo'] = $this->pixie->party->providedPartInfo($part);
			// $this->view->config = 

		}
		else {
			$this->page['title'] = 'Участники';
			$parts = $this->pixie->orm->get('participant')->order_by('money','desc')->order_by('created','asc')->find_all()->as_array();
			$this->page['table'] = array();

			$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
			$formatter->setPattern('d MMMM h:mm');

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
					'имя' => "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>",
					'email' => array_unique(array($part->user->email, $part->email)),
					'оплачено' => $part->money ? $part->money : "",
					'дата оплаты' => ($part->paydate ? $formatter->format(new \DateTime($part->paydate)) : ""),
					'статус' => $status,
				);
			}
			$this->page['subview'] = 'admin-participants-list';
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
