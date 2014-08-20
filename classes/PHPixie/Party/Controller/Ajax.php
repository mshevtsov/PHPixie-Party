<?php

namespace PHPixie\Party\Controller;

class Ajax extends \App\Page {
	private $page = array();

	public function before() {
		if(!$this->logged_in('admin'))
			return $this->redirect("/register");
		$this->view = $this->pixie->view('admin2');
		$this->view->user = $this->pixie->auth->user();
	}

	public function after() {
		die(json_encode(array("status"=>"error")));
	}

	public function action_index() {
		throw new \PHPixie\Exception\PageNotFound;
	}

	// Переключаем режим льготной регистрации для пользователя по его номеру
	public function action_grace() {
		if($this->request->post('type')=='user') {
			if(is_numeric($this->request->post('id'))) {
				$user = $this->pixie->orm->get('user',$this->request->post('id'));
				$sum = array("needToFull"=>"null");
				if($user->loaded()) {
					$user->grace = $user->grace ? 0 : 1;
					$user->save();
					if(is_numeric($this->request->post('part')))
						$sum = $this->pixie->party->getSumToPay($this->request->post('part'));

					$this->pixie->party->log(8,"Для {$user->name} в". ($user->grace ? "" : "ы") ."ключена льгота",$user->id,$user->grace);

					die(json_encode(array("status"=>"success","result"=>($user->grace ? "true" : "false"),"needtopay"=>$sum['needToFull'])));
				}
			}
		}
		die(json_encode(array("status"=>"error")));
	}

	public function action_houses() {
		$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$formatter->setPattern("d MMMM");
		$formatterShort = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$formatterShort->setPattern("d.MM");
		$transport = array(
			1=>"поезд",
			2=>"самолёт",
			3=>"машина",
			4=>"рейс/авт",
			5=>"церк/авт",
			6=>"местный",
			7=>"другое",
			);

		if($this->request->post('action')=='list') {
			$model = $this->request->post('type');

			if($model=='house') {
				$items = $this->pixie->orm->get($model)->find_all()->as_array();
				$result = array();
				foreach($items as $item) {
					$places1 = $item->places1;
					$places2 = $item->places2;
					$places3 = $item->places3;
					$places4 = $item->places4;

					$inhabs = $item->participants->find_all()->as_array();
					if($this->request->post('mode')=='free') {
						// Считаем, сколько каких мест сейчас уже занято
						foreach($inhabs as $inhab) {
							if($inhab->gender==1 && $places1 || $inhab->gender==2 && $places2) {
								$inhab->gender==1
									? $places1--
									: $places2--;
							}
							else if($places3)
								$places3--;
							else if($places4)
								$places4--;
						}
						if($item->coord
							&& ($places1+$places2+$places3+$places4))
							$result[] = array(
								'id' => (int)$item->id,
								'coord' => explode(",", $item->coord),
								'address' => trim(str_replace('"', "'", $item->address)),
								'places1' => (int)$places1,
								'places2' => (int)$places2,
								'places3' => (int)$places3,
								'places4' => (int)$places4,
								'meeting' => (int)$item->meeting,
								'name' => $item->name,
								);
					}
					else if($this->request->post('mode')=='table') {
						$places = array();
						if($item->places1) $places[] = $item->places1 ."М";
						if($item->places2) $places[] = $item->places2 ."Ж";
						if($item->places3) $places[] = $item->places3 ."Л";
						if($item->places4) $places[] = $item->places4 ."в";
						$partNames = array();
						foreach($inhabs as $part)
							$partNames[] = "{$part->firstname} {$part->lastname}";
						$result[] = array(
							'id' => (int)$item->id,
							'name' => $item->name,
							'places' => array((int)($item->places1 + $item->places2 + $item->places3 + $item->places4), implode(' ',$places)),
							'parts' => array(count($inhabs), implode(', ', $partNames)),
							'phone' => array((int)($item->phone ? 1 : 0), $item->phone),
							'meeting' => (int)$item->meeting,
							'district' => $item->district,
							'church' => $item->church,
							);
					}
				}
				if($this->request->post('mode')=='table')
					die(json_encode(array("data"=>$result)));
				else
					die(json_encode($result));
			}
			else if($model=='participant') {
				$items = $this->pixie->orm->get($model)->where('cancelled',0)->where('transport','<>',6)->where('accomodation', 'NOT IN', $this->pixie->db->expr('(3,4)'));

				// Фильтр по наличию предоплаты
				if($this->request->post('payed')=='yes')
					$items = $items->where(array(
						array('money','>=',$this->pixie->config->get('party.periods.1.prices.prepay')),
						array('or',array('approved',1)),
						));
				else if($this->request->post('payed')=='no')
					$items = $items->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'));

				// Фильтр по наличию расселения
				if($this->request->post('settled')=='yes')
					$items = $items->where(array('house_id','<>',0));
				else if($this->request->post('settled')=='no')
					$items = $items->where(array('house_id',0));

				// Фильтр по полам
				if($this->request->post('gender')=='male')
					$items = $items->where('gender',1);
				else if($this->request->post('gender')=='female')
					$items = $items->where('gender',2);

				$items = $items->find_all()->as_array();


				$waiting = $this->request->post('waiting');
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
						$orders = $part->user->orders->order_by('datecreated','desc')->find_all()->as_array();
						foreach($orders as $order) {
							$purpose = unserialize($order->purpose);
							if(in_array($part->id, $purpose)) {
								$age = date_diff(date_create($order->datecreated), date_create('today'))->d;
								if($age<$waiting && $part->user->id!=32) {
									// print "<a href=\"/admin/participants/{$part->id}\">{$age}</a><br/>\n";
									$items[] = $part;
									break;
								}
							}
						}
					}
				}

				$result = array();
				foreach($items as $item) {
					$age = date_diff(date_create($item->birthday), date_create('now'))->y;
					if(!$age || $age>100)
						$age = 0;
					$comment = trim(str_replace('"', "'", $item->comment));
					$result[] = array(
						'id' => (int)$item->id,
						'name' => array((int)$item->id, $item->firstname .' '. $item->lastname),
						'gender' => ($item->gender==1 ? "М" : "Ж"),
						'age' => (int)$age,
						'region' => array(mb_substr($item->region->name,0,6,'utf-8'). "&hellip;",$item->region->name),
						'transport' => ($item->transport ? $transport[$item->transport] : "?"),
						'user' => (int)$item->user->id,
						'created' => array(strtotime($item->created),$formatterShort->format(new \DateTime($item->created)),$item->money ? 1 : 0),
						'comment' => array((int)($comment ? 1 : 0), $comment),
						'accomodation' => $item->accomodation,
						'attend' => $item->attend,
						);
				}
				die(json_encode(array("data"=>$result)));
			}
		}
		else if($this->request->post('action')=='settle') {
			$house_id = $this->request->post('house');
			$parts_ids = $this->request->post('parts');
			if(is_numeric($house_id) && is_array($parts_ids) && count($parts_ids)) {
				$house = $this->pixie->orm->get('house',$house_id);

				// на всякий случай проверяем, чтобы в массиве гостей были только номера
				foreach($parts_ids as $p)
					if(!is_numeric($p))
						die(json_encode(array("status"=>"error")));

				$parts = $this->pixie->orm->get('participant')->where('id', 'IN', $this->pixie->db->expr('('. implode(',',$parts_ids) .')'))->find_all()->as_array();
				if($house->loaded() && count($parts)) {
					$places1 = $house->places1;
					$places2 = $house->places2;
					$places3 = $house->places3;
					$places4 = $house->places4;

					// Считаем, сколько каких мест сейчас уже занято
					$inhabs = $house->participants->find_all()->as_array();
					foreach($inhabs as $inhab) {
						if($inhab->gender==1 && $places1 || $inhab->gender==2 && $places2) {
							$inhab->gender==1
								? $places1--
								: $places2--;
						}
						else if($places3)
							$places3--;
						else if($places4)
							$places4--;
					}

					// Считаем, сколько каких мест будет теперь занято
					$parts1 = $parts2 = $parts3 = 0;
					foreach($parts as $part)
						$part->gender == 1
							? $parts1++
							: $parts2++;
					if($parts1 > $places1)
						$parts3 += ($parts1 - $places1);
					if($parts2 > $places2)
						$parts3 += ($parts2 - $places2);

					// Сохраняем в базу и лог
					if($parts3 <= ($places3 + $places4)) {
						$parts_ids = array();
						foreach($parts as $part) {
							$part->add('house', $house);
							$parts_ids[] = $part->id;
							$this->pixie->party->log(6, "{$part->firstname} {$part->lastname} поселён к {$house->name}", $part->id, $house->id);
						}
						die(json_encode(array("status"=>"success")));
					}
				}
			}
		}
		// Выселение участника из занимаемого им гостеприимного дома
		else if($this->request->post('action')=='moveout') {
			if(is_numeric($this->request->post('id'))) {
				$part = $this->pixie->orm->get('participant',$this->request->post('id'));
				$house = $part->house;
				$part->remove('house');
				$this->pixie->party->log(7, "{$part->firstname} {$part->lastname} выселен от {$house->name}", $part->id, $house->id);
				die(json_encode(array("status"=>"success", "guests"=>(int)$house->participants->count_all())));
			}
		}

	}


	// Подтверждение участия участника
	public function action_approve() {
		if(is_numeric($this->request->post('id'))) {
			$part = $this->pixie->orm->get('participant',$this->request->post('id'));
			if($part->loaded()) {
				$part->approved = $part->approved ? 0 : 1;
				$part->save();
				$this->pixie->party->log(9, ($part->approved ? "П" : "Не п") ."одтверждено участие {$part->firstname} {$part->lastname}", $part->id, $part->approved);
				die(json_encode(array("status"=>"success","result"=>($part->approved ? "true" : "false"))));
			}
		}
	}

	// Подтверждение участия участника
	public function action_attend() {
		if(is_numeric($this->request->post('id'))) {
			$part = $this->pixie->orm->get('participant',$this->request->post('id'));
			if($part->loaded()) {
				$part->attend = $part->attend ? 0 : 1;
				$part->save();
				$this->pixie->party->log(10, ($part->attend ? "П" : "Не п") ."рибыл". ($part->gender==1 ? "" : "а") ." {$part->firstname} {$part->lastname}", $part->id, $part->attend);
				die(json_encode(array("status"=>"success","result"=>($part->attend ? "true" : "false"))));
			}
		}
	}

	// Отмена участия участника
	public function action_cancel() {
		if(is_numeric($this->request->post('id'))) {
			$part = $this->pixie->orm->get('participant',$this->request->post('id'));
			if($part->loaded()) {
				$part->cancelled = $part->cancelled ? 0 : 1;
				if($part->cancelled && $part->house->loaded())
					$part->remove('house',$part->house);
				$part->save();
				$this->pixie->party->log(11, ($part->cancelled ? "О" : "Не о") ."тменяется {$part->firstname} {$part->lastname}", $part->id, $part->cancelled);
				die(json_encode(array("status"=>"success","result"=>($part->cancelled ? "true" : "false"))));
			}
		}
	}

	// Переключение отдельного дня участия
	public function action_days() {
		if(is_numeric($this->request->post('id')) &&
			is_numeric($this->request->post('day')) &&
			is_numeric($this->request->post('state'))) {
			$part = $this->pixie->orm->get('participant',$this->request->post('id'));
			if($part->loaded()) {
				$dayToggle = $this->request->post('day');
				$days = unserialize($part->dayslist);
				$days[$dayToggle] = (isset($days[$dayToggle]) && $days[$dayToggle]) ? 0 : 1;
				$part->dayslist = serialize($days);
				$part->save();
				$this->pixie->party->log(13, "В ". ($dayToggle+1) ."-й день {$part->firstname} {$part->lastname} ". ($days[$dayToggle] ? "" : "не ") ."питается", $part->id, $dayToggle);

				$check = $this->pixie->party->getSumToPay($part);

				die(json_encode(array(
					"status"=>"success",
					"result"=>($days[$dayToggle] ? "true" : "false"),
					"needtofull"=>$check['needToFull'],
					"sumexcess"=>$check['sumExcess'],
					)));
			}
		}
	}


	public function action_hello() {
		$query = $this->request->get('query');
		if($query)
			$parts = $this->pixie->orm->get('participant')
				->where('cancelled',0)
				->where(array(
					array('firstname','LIKE',$this->pixie->db->expr("'{$query}%'")),
					array('or',array('lastname','LIKE',$this->pixie->db->expr("'{$query}%'"))),
					))
				->find_all();
		else
			$parts = $this->pixie->orm->get('participant')
				->where('cancelled',0)
				->find_all();
		$result = array();
		foreach($parts as $part) {
			$result[] = array(
				'id' => $part->id,
				'name' => $part->firstname .' '. $part->lastname,
				'city' => $part->city,
				'tokens' => array(
					$part->firstname,
					$part->lastname,
					$part->city,
					),
			);
		}
		die(json_encode($result));
	}


	public function action_payment() {
		if(is_numeric($this->request->post('id')) && is_numeric($this->request->post('sum'))) {
			$sum = $this->request->post('sum');
			$id = $this->request->post('id');
			$part = $this->pixie->orm->get('participant',$id);
			if($part->loaded()) {
				$order = $this->pixie->orm->get('order');
				$order->add('user', $this->pixie->auth->user());
				$order->sum = $sum;
				$order->balance = $sum;
				$order->datecreated = date('Y-m-d H:i:s',time());
				$order->datepayed = date('Y-m-d H:i:s',time());
				$order->purpose = serialize(array($id));
				$order->payed = 1;
				$order->cash = 1;
				$part->money = $part->money + $sum;
				$order->save();
				$part->save();
				$this->pixie->party->log(12, "Принят взнос {$sum} руб. для {$part->firstname} {$part->lastname}", $id, $sum);
				$check = $this->pixie->party->getSumToPay($part);
				$needtofull = $check['needToFull'];
				$sumexcess = $check['sumExcess'];
				die(json_encode(array("status"=>"success","result"=>$part->money,"needtofull"=>$needtofull,"sumexcess"=>$sumexcess)));
			}
		}
	}


	public function action_cancelorder() {
		if(is_numeric($this->request->post('id')) && $this->pixie->auth->user()->id) {
			$order = $this->pixie->orm->get('order',$this->request->post('id'));
			if($order->cash && !$order->cancelled) {
				$purpose = unserialize($order->purpose);
				if(count($purpose)==1) {
					$order->cancelled = 1;
					$part = $this->pixie->orm->get('participant',reset($purpose));
					$part->money -= $order->balance;
					$part->save();
					$order->save();

					$this->pixie->party->log(14, "Отменён платёж {$order->id} на {$order->balance} руб. для {$part->firstname} {$part->lastname}", $order->id, $order->balance);
					die(json_encode(array("status"=>"success")));
				}
				else
					die(json_encode(array("status"=>"error","result"=>"many purposes")));
			}
			else
				die(json_encode(array("status"=>"error","result"=>"not cash or already cancelled")));
		}
	}


	// Просто генерируем код активации пользователя
	// (использовал лишь раз, и то вручную, поэтому нужды пока нет)
	public function action_makemd5() {
		if($this->request->get('email'))
			die(sha1(mt_rand(10000,99999) . time() . $this->request->get('email')));
	}

	// Узнаём, сколько осталось заплатить участнику
	public function action_needtopay() {
		if($this->request->post('type')=='part') {
			if(is_numeric($this->request->post('id'))) {
				$sum = $this->pixie->party->getSumToPay($this->request->post('id'));
				if($sum!==false)
					die(json_encode(array("status"=>"success","result"=>$sum['needToFull'])));
			}
		}
	}

	// Повторное исполнение заказа
	public function action_approveorder() {
		if(is_numeric($this->request->post('id'))) {
			$order = $this->pixie->orm->get('order',$this->request->post('id'));
			if($order->loaded()) {
				$this->pixie->party->approveOrder($order, null, null, true);
				die(json_encode(array("status"=>"success")));
			}
		}
	}




	public function action_geocode() {
		if($this->request->post('city') && $this->request->post('address')) {
			$city = mysql_real_escape_string(trim($this->request->post('city')));
			$address = mysql_real_escape_string(trim($this->request->post('address')));
			$fields = array(
				'origin'=>'jsapi2Geocoder',
				'geocode'=>"г. {$city}, {$address}",
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
			die(json_encode(array("status"=>"success","result"=>$coord[1] .','. $coord[0])));
		}
	}


	// Читаем сообщение для регистратора или от него
	public function action_message() {
		if($this->request->post('action')=='read') {
			if($this->request->post('type')=='user') {
				if(is_numeric($this->request->post('id'))) {
					$msg = $this->pixie->orm->get('usermsg',$this->request->post('id'));
					if($msg->loaded()) {
						$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
						$formatter->setPattern("d MMMM',' h:mm");
						die(json_encode(array("status"=>"success","result"=>$msg->content,"title"=>"Сообщение от {$msg->sender->name} за ". $formatter->format(new \DateTime($msg->date)))));
					}
				}
			}
		}
		else if($this->request->post('action')=='send') {
			if($this->request->post('type')=='user'
				&& is_numeric($this->request->post('id'))
				&& is_numeric($this->request->post('template'))
				) {
				$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
				$formatter->setPattern('d MMMM');
				$htmlText = "";

				$user = $this->pixie->orm->get('user',$this->request->post('id'));
				if($user->loaded()) {

					// Шаблон "Напомнить об активации"
					if($this->request->post('template')==1) {

						$htmlText .= "Мир вам, {$user->name}!

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


					} // template 1

					// Шаблон "Поторопить с оплатой"
					else if($this->request->post('template')==2) {
						$htmlText .= "Мир вам, {$user->name}!

Мы, организаторы \"Я молодой\", очень рады, что вы собираетесь на эту конференцию и ". $formatter->format(new \DateTime($user->codesent)) ." решили зарегистрироваться на нашем сайте! Сейчас мы вовсю готовимся, ведь до начала осталось всего ". $this->pixie->party->daysLeft() .".

Ваша регистрация очень важна для нас, чтобы спланировать все расходы и расселение. Ещё мы просим всех участников пожертвовать на нужды конференции по ". $this->pixie->config->get('party.periods.1.prices.full') ." рублей, из которых ". $this->pixie->config->get('party.periods.1.prices.prepay') ." нужно отправить заранее (можно и позже, но тогда вся сумма выйдет больше). Наше общее желание - чтобы во время всей конференции молодёжь могла вместе поклоняться Богу спокойно от бытовых проблем и суеты!

";
$partsCount = $user->participants->where('cancelled',0)->count_all();
if($partsCount > 1) {
	$partsUnpayed = $user->participants->where('cancelled',0)->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))->find_all()->as_array();
	$names = array();
	foreach($partsUnpayed as $part)
		$names[] = $part->firstname .' '. $part->lastname;
	$htmlText .= "Вы уже добавили анкеты {$partsCount} ". $this->pixie->party->declension($partsCount, array('участника','участников','участников')) .", и для успешного завершения регистрации осталось внести предоплату за следующих из них: ". implode(", ", $names) .".";
}
else if($partsCount)
	$htmlText .= "Вы уже добавили свою анкету участника, и для успешного завершения регистрации осталось только внести предоплату.";
else
	$htmlText .= "Для успешного завершения регистрации вам нужно заполнить свою анкету участника и внести предоплату.";

$htmlText .= "

А сделать нам денежный перевод совсем не сложно, так как доступны разные способы оплаты, и многие участники уже с этим справились. По этой ссылке вы перейдёте к подробным условиям и ко входу в свой профиль на сайте:
http://2014.imolod.ru/register

Наша группа Вконтакте, в которой есть программа конференции и ответы на вопросы:
http://vk.com/imolod14

";

if(!$user->grace) {
	if(strtotime('today')==strtotime($this->pixie->config->get('party.periods.1.lastday')))
		$htmlText .= "Сегодня, ". $formatter->format(new \DateTime('today')) .", последний день для внесения предоплаты, чтобы для вас общий размер взноса за одного участника сохранился равным ". $this->pixie->config->get('party.periods.1.prices.full') ." руб. ";
	else if(strtotime('tomorrow')==strtotime($this->pixie->config->get('party.periods.1.lastday')))
		$htmlText .= "Завтра, ". $formatter->format(new \DateTime('tomorrow')) .", последний день для внесения предоплаты, чтобы для вас общий размер взноса за одного участника сохранился равным ". $this->pixie->config->get('party.periods.1.prices.full') ." руб. ";
}
$htmlText .= "Пожалуйста, смело обращайтесь к нам за помощью или с вопросами - мы готовы ответить и пойти вам на встречу!

--
Команда организаторов конференции \"Я МОЛОДОЙ\" в Волгограде
info@imolod.ru
http://2014.imolod.ru
http://vk.com/imolod14
";




					} // template 2

					// Шаблон "Поторопить с оплатой"
					else if($this->request->post('template')==3) {
						$htmlText .= "Мир вам, {$user->name}!

Система регистрации нам показывает, что вы заполнили анкету участника и пробовали отправить регистрационный взнос, но отправка не получилась. Вы можете проверить нынешнее состояние оплаты в своём профиле, в таблице с участниками:
http://". $this->pixie->config->get('party.event.domain') ."/register/profile

Если вы совершали оплату через банковский перевод или Сбербанк Онлайн, то нормальный срок обработки составляет 2 рабочих дня. Остальные виды платежей исполняются всего за несколько минут. Если все сроки вышли, а в профиле сумма оплаты не появилась, значит платёж неудачный.

Можем ли мы вам помочь? Пожалуйста, обращайтесь к нам со всеми вопросами и замечаниями! Осталось всего ". $this->pixie->party->daysLeft() ." до конференции, но ещё можно успеть. Мы учитываем, что вы пытались всё сделать ещё до конца льготного периода регистрации, поэтому, если вы сообщите нам свою проблему, размер взноса для вас не увеличится.

--
Команда организаторов конференции \"Я МОЛОДОЙ\" в Волгограде
info@imolod.ru
http://2014.imolod.ru
http://vk.com/imolod14
";
// die($htmlText);
					} // template 3

					// Осталось отправить, если есть что:
					if($htmlText) {
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
					} // Отправка

				}
			}
		}
	}


}
